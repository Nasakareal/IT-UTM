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
        /* 1. VALIDAR --------------------------------------------------------- */
        $request->validate([
            'materia'        => 'required|string',
            'unidad'         => 'required|integer|min:1',
            'tipo_documento' => 'required|string',
            'archivo'        => 'required|file|mimes:pdf,doc,docx,xls,xlsx|max:5120',
            'firma_sat'      => 'required|string',
            'efirma_pass'    => 'required|string',
        ]);

        /* 2. SUBIR ARCHIVO --------------------------------------------------- */
        if (!Storage::disk('public')->exists('documentos_subidos')) {
            Storage::disk('public')->makeDirectory('documentos_subidos');
        }
        $file     = $request->file('archivo');
        $ext      = strtolower($file->getClientOriginalExtension());
        $filename = Str::random(40).'.'.$ext;
        $relPath  = $file->storeAs('documentos_subidos', $filename, 'public');
        $absPath  = Storage::disk('public')->path($relPath);

        /* 3. DATOS DE e-FIRMA ----------------------------------------------- */
        $p12raw = base64_decode($request->firma_sat);  // <- ¡Correcto!

        $certName = Auth::user()->name;
        $certRFC  = 'N/A';

        if (!@openssl_pkcs12_read($p12raw, $certs, $request->efirma_pass) || empty($certs['cert'])) {
            return back()->withErrors(['efirma_pass' => 'La contraseña de la e.firma es incorrecta o el archivo está dañado.'])->withInput();
        }

        $info    = openssl_x509_parse($certs['cert']);
        $subject = $info['subject'] ?? [];

        $certName = $subject['CN']           ?? $certName;
        $certRFC  = $subject['serialNumber'] ?? $certRFC;



        /* 4. Registro BD se crea YA – por si algo falla después -------------- */
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

        /* 5. HASH ORIGINAL --------------------------------------------------- */
        clearstatcache();
        $hashOriginal = hash_file('sha256', $absPath);

        /* 6. INSERTAR FIRMA/HASH -------------------------------------------- */
        try {
            if ($ext === 'docx') {
                /* -------- Word -------- */
                $doc   = WordIOFactory::load($absPath);
                $done  = false;

                foreach ($doc->getSections() as $section) {
                    foreach ($section->getElements() as $element) {
                        if (!($element instanceof Table)) continue;
                        foreach ($element->getRows() as $row) {
                            foreach ($row->getCells() as $cell) {
                                $txt='';
                                foreach ($cell->getElements() as $e)
                                    if (method_exists($e,'getText')) $txt.=$e->getText();

                                if (stripos($txt, 'Nombre y Firma del Profesor') !== false) {
                                    $cell->addTextBreak();
                                    $cell->addText(
                                        "Firmado por: {$certName}",
                                        ['name'=>'Calibri','size'=>11],
                                        ['spaceAfter'=>0]
                                    );
                                    $cell->addText(
                                        "Hash: {$hashOriginal}",
                                        ['name'=>'Calibri','size'=>9],
                                        ['spaceAfter'=>0]
                                    );
                                    $done = true;
                                    break;
                                }
                            }
                            if ($done) break;
                        }
                    }
                    if ($done) break;
                }

                if ($done) {
                    /* -- mantener encabezados con imágenes ------------------- */
                    $tmp = $absPath.'.tmp';
                    WordIOFactory::createWriter($doc, 'Word2007')->save($tmp);

                    $zipOld = new ZipArchive;
                    $zipNew = new ZipArchive;
                    if ($zipOld->open($absPath) === true && $zipNew->open($tmp) === true) {
                        for ($i=0;$i<$zipOld->numFiles;$i++) {
                            $name = $zipOld->getNameIndex($i);
                            if (preg_match('#^word/(header\d+\.xml|_rels/header\d+\.xml\.rels)$#',$name)) {
                                $zipNew->addFromString($name,$zipOld->getFromName($name));
                            }
                        }
                        $zipOld->close();
                        $zipNew->close();
                    }
                    rename($tmp, $absPath);
                }
            }

           /* ===== XLS / XLSX ===== */
        /* ===== XLS / XLSX ===== */
            if ($ext === 'xls' || $ext === 'xlsx') {
                ini_set('memory_limit','512M');
                Settings::setCacheStorageMethod(
                    CachedObjectStorageFactory::cache_to_phpTemp,
                    ['memoryCacheSize'=>'32MB']
                );

                $reader = $ext==='xlsx' ? new XlsxReader() : new XlsReader();
                $spreadsheet = $reader->load($absPath);
                $sheet = $spreadsheet->getActiveSheet();
                $done  = false;
                $label = 'nombre y firma del profesor';

                /* 1) Barrido por celdas para hallar la etiqueta ----------------------- */
                foreach ($sheet->getRowIterator() as $row) {
                    foreach ($row->getCellIterator() as $cell) {
                        $value = strtolower(trim((string)$cell->getValue()));
                        if (strpos($value, $label) !== false) {

                            /* Colocamos el texto en la FILA INFERIOR (misma columna) */
                            $col         = $cell->getColumn();
                            $rowDest     = $cell->getRow() + 1;
                            $coordDest   = $col.$rowDest;

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

                /* 2) Si no lo hallamos arriba, revisamos los rangos combinados -------- */
                if (!$done) {
                    foreach ($sheet->getMergeCells() as $range) {
                        [$topLeft,] = explode(':', $range);
                        $cell = $sheet->getCell($topLeft);
                        $value = strtolower(trim((string)$cell->getValue()));

                        if (strpos($value, $label) !== false) {
                            /* topLeft = A36  → destino = A37  */
                            preg_match('/^([A-Z]+)(\d+)$/', $topLeft, $m);
                            $col       = $m[1];
                            $rowDest   = $m[2] + 1;
                            $coordDest = $col.$rowDest;

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

                /* 3) Guardamos solo si realmente insertamos algo --------------------- */
                if ($done) {
                    $writer = SpreadsheetIOFactory::createWriter(
                        $spreadsheet,
                        $ext==='xlsx' ? 'Xlsx' : 'Xls'
                    );
                    $writer->setPreCalculateFormulas(false);
                    $writer->save($absPath);
                }
            }


        } catch (\Throwable $e) {
            \Log::warning('Incrustar firma falló: '.$e->getMessage());
        }

        /* 7. HASH FINAL ------------------------------------------------------ */
        clearstatcache();
        $hashFinal = file_exists($absPath) ? hash_file('sha256',$absPath) : $hashOriginal;

        /* 8. GENERAR ACUSE PDF ---------------------------------------------- */
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


        $acuseRel = 'acuses/acuse_'.$registro->id.'.pdf';
        Storage::disk('public')->put($acuseRel, $pdf->output());
        $registro->update(['acuse_pdf'=>$acuseRel]);

        return back()->with('success','Documento subido, firmado e integrado correctamente.');
    }
}
