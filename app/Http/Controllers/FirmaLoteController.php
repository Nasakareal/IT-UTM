<?php

namespace App\Http\Controllers;

use App\Models\DocumentoSubido;
use App\Models\FirmaLote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;

class FirmaLoteController extends Controller
{
    public function firmarLote(Request $request)
    {
        Log::info('[firmarLote] hit', $request->only('materia','grupo','unidad'));

        $data = $request->validate([
            'materia'            => ['required','string','max:100'],
            'grupo'              => ['required','string','max:125'],
            'unidad'             => ['required','integer','min:1'],
            'tipos_requeridos'   => ['required','array','min:1'],
            'tipos_requeridos.*' => ['string','max:100'],
            'firma_sat'          => ['required','string'],
            'efirma_pass'        => ['required','string'],
        ]);

        $user = Auth::user();

        // 1) Validar que estén todos los tipos requeridos subidos (no firmados necesariamente)
        $docsUnidad = DocumentoSubido::where('user_id', $user->id)
            ->where('materia', $data['materia'])
            ->where('grupo',   $data['grupo'])
            ->where('unidad',  $data['unidad'])
            ->whereIn('tipo_documento', $data['tipos_requeridos'])
            ->get()
            ->keyBy('tipo_documento');

        $faltantes = [];
        foreach ($data['tipos_requeridos'] as $tipo) {
            if (!isset($docsUnidad[$tipo])) $faltantes[] = $tipo;
        }
        if (!empty($faltantes)) {
            Log::warning('[firmarLote] faltan docs', ['faltantes' => $faltantes]);
            return $this->fail('Faltan por subir: '.implode(', ', $faltantes), 422, $request);
        }

        // 2) Cargar .p12 (base64) y extraer CN / RFC + llave
        try {
            $p12raw = base64_decode($data['firma_sat'], true);
            if ($p12raw === false) {
                return $this->fail('El archivo .p12 (firma_sat) no es base64 válido.', 422, $request);
            }
            Log::info('[firmarLote] p12 bytes', ['len' => strlen((string)$p12raw)]);
            $certInfo = $this->loadP12($p12raw, $data['efirma_pass']);
            Log::info('[firmarLote] cert', ['cn' => $certInfo['cn'], 'rfc' => $certInfo['rfc']]);
        } catch (\Throwable $e) {
            Log::error('[firmarLote] loadP12 failed: '.$e->getMessage());
            return $this->fail('No se pudo leer el .p12: '.$e->getMessage(), 422, $request);
        }

        // 3) Transacción: firmar cada archivo que aún no esté firmado y crear lote
        $firmadosAhora = [];
        $skipped       = [];
        DB::beginTransaction();
        try {
            $lote = FirmaLote::create([
                'user_id'          => $user->id,
                'materia'          => $data['materia'],
                'grupo'            => $data['grupo'],
                'unidad'           => $data['unidad'],
                'firmado_at'       => now(),
                'acuse_lote'       => '',
                'total_documentos' => count($data['tipos_requeridos']),
                'certificado_cn'   => $certInfo['cn'],
                'certificado_rfc'  => $certInfo['rfc'],
            ]);

            foreach ($data['tipos_requeridos'] as $tipo) {
                /** @var DocumentoSubido $doc */
                $doc = $docsUnidad[$tipo];

                if ($doc->fecha_firma) {
                    // Ya estaba firmado
                    $skipped[] = $tipo;
                    if (!$doc->lote_id) {
                        $doc->lote_id = $lote->id;
                        $doc->save();
                    }
                    continue;
                }

                // Archivo debe existir
                if (!Storage::disk('public')->exists($doc->archivo)) {
                    throw new \RuntimeException("Archivo no encontrado en storage: {$doc->archivo}");
                }
                $abs = Storage::disk('public')->path($doc->archivo);

                // Hash + firma detach
                clearstatcache();
                $hash    = hash_file('sha256', $abs);
                $dataBin = file_get_contents($abs);
                $sigBin  = $this->signDetached($dataBin, $certInfo['pkey']);
                $sigRel  = 'firmas/doc_'.$doc->id.'.sig';

                if (!Storage::disk('public')->exists('firmas')) {
                    Storage::disk('public')->makeDirectory('firmas');
                }
                Storage::disk('public')->put($sigRel, $sigBin);

                // Actualizar documento
                $doc->hash_sha256 = $hash;
                $doc->firma_sig   = $sigRel;
                $doc->fecha_firma = now();
                $doc->lote_id     = $lote->id;
                $doc->save();

                $firmadosAhora[] = $tipo;
            }

            DB::commit();
            Log::info('[firmarLote] TX committed', [
                'lote_id'  => $lote->id,
                'firmados_ahora' => $firmadosAhora,
                'omitidos' => $skipped
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('[firmarLote] TX failed: '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return $this->fail('No se pudo firmar el lote: '.$e->getMessage(), 500, $request);
        }

        // 4) Preparar ITEMS para el ACUSE GENERAL (todos los requeridos de la unidad)
        $itemsParaPdf = [];
        foreach ($data['tipos_requeridos'] as $tipo) {
            /** @var DocumentoSubido $doc */
            $doc = DocumentoSubido::where('user_id', $user->id)
                ->where('materia', $data['materia'])
                ->where('grupo',   $data['grupo'])
                ->where('unidad',  $data['unidad'])
                ->where('tipo_documento', $tipo)
                ->first();

            if (!$doc) {
                $itemsParaPdf[] = [
                    'tipo'        => $tipo,
                    'nombre'      => '—',
                    'ruta'        => null,
                    'bytes'       => 0,
                    'hash'        => null,
                    'estado'      => 'FALTANTE',
                    'fecha_firma' => null,
                    'firma_sig'   => null,
                ];
                continue;
            }

            $bytes = 0;
            if ($doc->archivo && Storage::disk('public')->exists($doc->archivo)) {
                $abs   = Storage::disk('public')->path($doc->archivo);
                $bytes = @filesize($abs) ?: 0;
            }

            $itemsParaPdf[] = [
                'tipo'        => $doc->tipo_documento,
                'nombre'      => basename($doc->archivo),
                'ruta'        => $doc->archivo,
                'bytes'       => $bytes,
                'hash'        => $doc->hash_sha256,
                'estado'      => $doc->fecha_firma ? 'FIRMADO' : 'SUBIDO',
                'fecha_firma' => optional($doc->fecha_firma)->format('Y-m-d H:i:s'),
                'firma_sig'   => $doc->firma_sig,
                'doc_id'      => $doc->id,
            ];
        }

        // 4.1) Programa educativo para footer dinámico
        $programa = DB::connection('cargahoraria')
            ->table('teacher_subjects as ts')
            ->join('subjects as s',  'ts.subject_id', '=', 's.subject_id')
            ->join('groups   as g',  'ts.group_id',   '=', 'g.group_id')
            ->join('programs as p',  's.program_id',  '=', 'p.program_id')
            ->where('ts.teacher_id', $user->teacher_id)
            ->where('s.subject_name', $data['materia'])
            ->where('g.group_name',   $data['grupo'])
            ->value('p.program_name') ?? '';

        // 4.2) Hash único del paquete (hashArchivo)
        $manifiesto = [];
        foreach ($itemsParaPdf as $it) {
            $manifiesto[] = ($it['tipo'] ?? '')
                .'|'.(($it['nombre'] ?? ''))
                .'|'.((string)($it['bytes'] ?? 0))
                .'|'.((string)($it['hash']  ?? ''));
        }
        $hashArchivo = $manifiesto ? hash('sha256', implode("\n", $manifiesto)) : '';

        // 5) ACUSE GENERAL ÚNICO (Blade)
        $acuseRel = null;
        try {
            if (!Storage::disk('public')->exists('acuses_lote')) {
                Storage::disk('public')->makeDirectory('acuses_lote');
            }

            $pdfLote = Pdf::loadView('pdf.acuse_lote', [
                'tituloAcuse' => 'Acuse de recepción y firma de documentación (Unidad)',
                'loteId'      => $lote->id,
                'materia'     => $data['materia'],
                'grupo'       => $data['grupo'],
                'unidad'      => (int)$data['unidad'],

                // Nombre REAL del maestro (evita "Usuario X")
                'usuario'     => $user->nombres ?? $user->name ?? ('Usuario '.$user->id),

                'rfc'         => $certInfo['rfc'],
                'certCN'      => $certInfo['cn'],
                'fecha'       => $lote->firmado_at,

                'items'       => $itemsParaPdf,
                'programa'    => $programa,
                'hashArchivo' => $hashArchivo,

                // (opcional) resumen, por si aún lo usas en otro template
                'resumen'     => [
                    'total_requeridos' => count($data['tipos_requeridos']),
                    'firmados_ahora'   => count($firmadosAhora),
                    'ya_firmados'      => count($skipped),
                ],
            ])->setPaper('letter');

            $acuseRel = 'acuses_lote/acuse_lote_'.$lote->id.'.pdf';
            Storage::disk('public')->put($acuseRel, $pdfLote->output());

            $lote->update(['acuse_lote' => $acuseRel]);

            Log::info('[firmarLote] PDF lote generado', ['lote_id' => $lote->id, 'acuse' => $acuseRel]);
        } catch (\Throwable $e) {
            Log::error('[firmarLote] PDF lote failed: '.$e->getMessage());
        }

        // 6) Responder
        $baseMsg = 'Lote firmado correctamente.';
        if ($acuseRel === null) {
            $baseMsg .= ' (El acuse del lote no pudo generarse ahora, pero las firmas quedaron guardadas).';
        }
        if (!empty($skipped)) {
            $baseMsg .= ' Documentos ya firmados omitidos: '.implode(', ', $skipped).'.';
        }

        $payload = [
            'ok'          => true,
            'msg'         => $baseMsg,
            'lote_id'     => $lote->id,
            'acuse_lote'  => $acuseRel,
            'materia'     => $data['materia'],
            'grupo'       => $data['grupo'],
            'unidad'      => (int)$data['unidad'],
            'firmados'    => count($firmadosAhora),
            'omitidos'    => $skipped,
            'cert'        => ['CN' => $certInfo['cn'], 'RFC' => $certInfo['rfc']],
        ];
        return $this->success($payload, $request);
    }

    /**
     * GET /firma-lotes/{lote}/acuse
     * Muestra/descarga el acuse del lote.
     */
    public function verAcuse(FirmaLote $lote)
    {
        $user = Auth::user();

        if ($lote->user_id !== $user->id && Gate::denies('ver revisiones')) {
            abort(403, 'No autorizado.');
        }

        if (!$lote->acuse_lote || !Storage::disk('public')->exists($lote->acuse_lote)) {
            abort(404, 'Acuse no encontrado.');
        }

        $abs = Storage::disk('public')->path($lote->acuse_lote);
        return response()->file($abs, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="acuse_lote_'.$lote->id.'.pdf"',
        ]);
    }

    // ========================
    // Helpers privados
    // ========================

    /**
     * Carga .p12 (binario) y regresa llave y metadatos.
     * @return array{pkey:resource|string, cn:string|null, rfc:string|null}
     */
    private function loadP12(string $p12raw, string $password): array
    {
        $certs = [];
        if (!@openssl_pkcs12_read($p12raw, $certs, $password)) {
            throw new \RuntimeException('Contraseña incorrecta o .p12 inválido.');
        }
        if (empty($certs['cert']) || empty($certs['pkey'])) {
            throw new \RuntimeException('El .p12 no contiene certificado/llave.');
        }
        $parsed = openssl_x509_parse($certs['cert']);
        return [
            'pkey' => $certs['pkey'],
            'cn'   => $parsed['subject']['CN'] ?? null,
            'rfc'  => $parsed['subject']['serialNumber'] ?? null,
        ];
    }

    /**
     * Firma desacoplada (detached) con SHA256 (devuelve binario .sig)
     */
    private function signDetached(string $data, $privateKey): string
    {
        $signature = '';
        if (!openssl_sign($data, $signature, $privateKey, OPENSSL_ALGO_SHA256)) {
            throw new \RuntimeException('Error al firmar.');
        }
        return $signature;
    }

    // Respuestas helper: JSON si AJAX, redirect con flash si no.
    private function success(array $payload, Request $req)
    {
        if ($req->expectsJson() || $req->wantsJson()) {
            return response()->json($payload);
        }
        return back()->with('success', $payload['msg'] ?? 'OK')->with('firma_lote', $payload);
    }

    private function fail(string $msg, int $status, Request $req)
    {
        if ($req->expectsJson() || $req->wantsJson()) {
            return response()->json(['ok'=>false,'msg'=>$msg], $status);
        }
        return back()->withErrors(['firmar_lote' => $msg])->withInput();
    }
}
