<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Models\DocumentoSubido;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use PhpOffice\PhpWord\IOFactory          as WordIOFactory;
use PhpOffice\PhpWord\Element\Table;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx as XlsxReader;
use PhpOffice\PhpSpreadsheet\Reader\Xls  as XlsReader;
use PhpOffice\PhpSpreadsheet\IOFactory   as SpreadsheetIOFactory;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Settings;
use PhpOffice\PhpSpreadsheet\CachedObjectStorageFactory;
use ZipArchive;

class DocumentoSubidoController extends Controller
{
    public function store(Request $request)
    {
        /* ------------------------------------------------------------------ *
         * 1. DETERMINAR LA ACCIÓN (qué botón se pulsó)                        *
         * ------------------------------------------------------------------ */
        $action = $request->input('action', 'sign_upload');   // sign_upload | upload_only

        /* ------------------------------------------------------------------ *
         * 2. VALIDACIÓN DINÁMICA                                              *
         * ------------------------------------------------------------------ */
        $rules = [
            'materia'        => 'required|string',
            'unidad'         => 'required|integer|min:1',
            'tipo_documento' => 'required|string',
            'archivo'        => 'required|file|mimes:pdf,doc,docx,xls,xlsx|max:5120',
        ];
        if ($action === 'sign_upload') {
            // Solo pedimos firma y contraseña si es “Firmar y Subir”
            $rules['firma_sat']   = 'required|string';
            $rules['efirma_pass'] = 'required|string';
        }
        $request->validate($rules);

        /* ------------------------------------------------------------------ *
         * 3. SUBIR ARCHIVO AL STORAGE                                         *
         * ------------------------------------------------------------------ */
        if (!Storage::disk('public')->exists('documentos_subidos')) {
            Storage::disk('public')->makeDirectory('documentos_subidos');
        }
        $file     = $request->file('archivo');
        $ext      = strtolower($file->getClientOriginalExtension());
        $filename = Str::random(40) . '.' . $ext;
        $relPath  = $file->storeAs('documentos_subidos', $filename, 'public');
        $absPath  = Storage::disk('public')->path($relPath);

        /* ------------------------------------------------------------------ *
         * 4. FLUJO “SOLO SUBIR” (sin firma, sin acuse)                        *
         * ------------------------------------------------------------------ */
        if ($action === 'upload_only') {
            DocumentoSubido::updateOrCreate(
                [
                    'user_id'        => Auth::id(),
                    'materia'        => $request->materia,
                    'unidad'         => $request->unidad,
                    'tipo_documento' => $request->tipo_documento,
                ],
                [
                    'archivo'     => $relPath,
                    'firma_sat'   => null,
                    'fecha_firma' => null,
                    'acuse_pdf'   => null,
                ]
            );

            return back()->with('success', 'Documento subido correctamente.');
        }

        /* ------------------------------------------------------------------ *
         * 5. FLUJO “FIRMAR Y SUBIR”                                           *
         * ------------------------------------------------------------------ */

        /* 5.1  Obtener datos de la e-firma (.p12) --------------------------- */
        $p12raw = base64_decode($request->firma_sat);
        $certName = Auth::user()->name;   // valor por defecto
        $certRFC  = 'N/A';

        if (!@openssl_pkcs12_read($p12raw, $certs, $request->efirma_pass) || empty($certs['cert'])) {
            return back()
                ->withErrors(['efirma_pass' => 'La contraseña de la e.firma es incorrecta o el archivo está dañado.'])
                ->withInput();
        }

        $info  = openssl_x509_parse($certs['cert']);
        $certName = $info['subject']['CN'] ?? $certName;
        $certRFC  = $info['subject']['serialNumber'] ?? $certRFC;

        /* 5.2  Crear/actualizar registro BD antes de modificar archivo ------ */
        $registro = DocumentoSubido::updateOrCreate(
            [
                'user_id'        => Auth::id(),
                'materia'        => $request->materia,
                'unidad'         => $request->unidad,
                'tipo_documento' => $request->tipo_documento,
            ],
            [
                'archivo'     => $relPath,
                'firma_sat'   => $request->firma_sat,
                'fecha_firma' => now(),
            ]
        );

        /* 5.3  Calcular hash original --------------------------------------- */
        clearstatcache();
        $hashOriginal = hash_file('sha256', $absPath);

        /* 5.4  Insertar firma y hash en DOCX / XLS(X) ----------------------- */
        try {
            /* ----------- WORD (.docx) ------------- */
            if ($ext === 'docx') {
                $doc  = WordIOFactory::load($absPath);
                $done = false;

                foreach ($doc->getSections() as $section) {
                    foreach ($section->getElements() as $element) {
                        if (!($element instanceof Table)) continue;
                        foreach ($element->getRows() as $row) {
                            foreach ($row->getCells() as $cell) {
                                $txt = '';
                                foreach ($cell->getElements() as $e) {
                                    if (method_exists($e, 'getText')) $txt .= $e->getText();
                                }
                                if (stripos($txt, 'Nombre y Firma del Profesor') !== false) {
                                    $cell->addTextBreak();
                                    $cell->addText("Firmado por: {$certName}",
                                        ['name' => 'Calibri', 'size' => 11], ['spaceAfter' => 0]);
                                    $cell->addText("Hash: {$hashOriginal}",
                                        ['name' => 'Calibri', 'size' => 9],  ['spaceAfter' => 0]);
                                    $done = true;
                                    break;
                                }
                            }
                            if ($done) break;
                        }
                    }
                    if ($done) break;
                }

                /* Mantener encabezados con imágenes ------------------------ */
                if ($done) {
                    $tmp = $absPath . '.tmp';
                    WordIOFactory::createWriter($doc, 'Word2007')->save($tmp);

                    $zipOld = new ZipArchive;
                    $zipNew = new ZipArchive;
                    if ($zipOld->open($absPath) === true && $zipNew->open($tmp) === true) {
                        for ($i = 0; $i < $zipOld->numFiles; $i++) {
                            $name = $zipOld->getNameIndex($i);
                            if (preg_match('#^word/(header\d+\.xml|_rels/header\d+\.xml\.rels)$#', $name)) {
                                $zipNew->addFromString($name, $zipOld->getFromName($name));
                            }
                        }
                        $zipOld->close();
                        $zipNew->close();
                    }
                    rename($tmp, $absPath);
                }
            }

            /* ----------- EXCEL (.xls / .xlsx) ----------- */
            if (in_array($ext, ['xls', 'xlsx'])) {
                ini_set('memory_limit', '512M');
                Settings::setCacheStorageMethod(
                    CachedObjectStorageFactory::cache_to_phpTemp,
                    ['memoryCacheSize' => '32MB']
                );

                $reader = $ext === 'xlsx' ? new XlsxReader() : new XlsReader();
                $spreadsheet = $reader->load($absPath);
                $sheet = $spreadsheet->getActiveSheet();
                $done  = false;
                $label = 'nombre y firma del profesor';

                /* 1) Buscar etiqueta en celdas normales --------------------- */
                foreach ($sheet->getRowIterator() as $row) {
                    foreach ($row->getCellIterator() as $cell) {
                        $value = strtolower(trim((string) $cell->getValue()));
                        if (strpos($value, $label) !== false) {
                            $col       = $cell->getColumn();
                            $rowDest   = $cell->getRow() + 1;
                            $coordDest = $col . $rowDest;
                            $sheet->setCellValueExplicit(
                                $coordDest,
                                "Firmado por: {$certName}\nHash: {$hashOriginal}",
                                DataType::TYPE_STRING
                            );
                            $sheet->getStyle($coordDest)->getAlignment()->setWrapText(true);
                            $done = true;
                            break 2;
                        }
                    }
                }

                /* 2) Buscar etiqueta en celdas combinadas ------------------ */
                if (!$done) {
                    foreach ($sheet->getMergeCells() as $range) {
                        [$topLeft] = explode(':', $range);
                        $cell  = $sheet->getCell($topLeft);
                        $value = strtolower(trim((string) $cell->getValue()));
                        if (strpos($value, $label) !== false) {
                            preg_match('/^([A-Z]+)(\d+)$/', $topLeft, $m);
                            $col       = $m[1];
                            $rowDest   = $m[2] + 1;
                            $coordDest = $col . $rowDest;
                            $sheet->setCellValueExplicit(
                                $coordDest,
                                "Firmado por: {$certName}\nHash: {$hashOriginal}",
                                DataType::TYPE_STRING
                            );
                            $sheet->getStyle($coordDest)->getAlignment()->setWrapText(true);
                            $done = true;
                            break;
                        }
                    }
                }

                /* 3) Guardar solo si insertamos algo ----------------------- */
                if ($done) {
                    $writer = SpreadsheetIOFactory::createWriter(
                        $spreadsheet,
                        $ext === 'xlsx' ? 'Xlsx' : 'Xls'
                    );
                    $writer->setPreCalculateFormulas(false);
                    $writer->save($absPath);
                }
            }

        } catch (\Throwable $e) {
            \Log::warning('Incrustar firma falló: ' . $e->getMessage());
        }

        /* 5.5  Calcular hash final ----------------------------------------- */
        clearstatcache();
        $hashFinal = file_exists($absPath) ? hash_file('sha256', $absPath) : $hashOriginal;

        /* ------------------------------------------------------------------ *
         * 6. GENERAR ACUSE PDF                                               *
         * ------------------------------------------------------------------ */
        $programa = DB::connection('cargahoraria')
            ->table('teacher_subjects as ts')
            ->join('subjects as s', 'ts.subject_id', '=', 's.subject_id')
            ->join('programs as p', 's.program_id', '=', 'p.program_id')
            ->where('ts.teacher_id', Auth::user()->teacher_id)
            ->where('s.subject_name', $request->materia)
            ->value('p.program_name') ?? 'Programa desconocido';

        $pdf = Pdf::loadView('pdf.acuse_individual', [
            'institucion'  => 'Universidad Tecnológica de Morelia',
            'ciudad'       => 'Morelia, Michoacán',
            'fecha'        => now()->translatedFormat('d \\d\\e F \\d\\e Y'),
            'tituloAcuse'  => 'Acuse de recepción de documentación',
            'materia'      => $request->materia,
            'unidad'       => $request->unidad,
            'tipo'         => $request->tipo_documento,
            'usuario'      => $certName,
            'rfc'          => $certRFC,
            'fecha_firma'  => $registro->fecha_firma->format('Y-m-d H:i:s'),
            'hashArchivo'  => $hashFinal,
            'programa'     => $programa,
            'cuerpo'       => "La Universidad Tecnológica de Morelia hace constar que ha recibido en tiempo y forma la documentación correspondiente al programa educativo {$programa}.",
            'atentamente'  => 'Universidad Tecnológica de Morelia',
        ])->setPaper('letter');

        if (!Storage::disk('public')->exists('acuses')) {
            Storage::disk('public')->makeDirectory('acuses');
        }
        $acuseRel = 'acuses/acuse_' . $registro->id . '.pdf';
        Storage::disk('public')->put($acuseRel, $pdf->output());

        $registro->update(['acuse_pdf' => $acuseRel]);

        return back()->with('success', 'Documento subido, firmado e integrado correctamente.');
    }
}
