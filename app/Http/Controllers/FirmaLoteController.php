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

        // 1) Validar que estén todos los tipos requeridos subidos
        $docs = DocumentoSubido::where('user_id', $user->id)
            ->where('materia', $data['materia'])
            ->where('grupo',   $data['grupo'])
            ->where('unidad',  $data['unidad'])
            ->whereIn('tipo_documento', $data['tipos_requeridos'])
            ->get()
            ->keyBy('tipo_documento');

        $faltantes = [];
        foreach ($data['tipos_requeridos'] as $tipo) {
            if (!isset($docs[$tipo])) $faltantes[] = $tipo;
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
            $certInfo = $this->loadP12($p12raw, $data['efirma_pass']); // [pkey, cn, rfc]
            Log::info('[firmarLote] cert', ['cn' => $certInfo['cn'], 'rfc' => $certInfo['rfc']]);
        } catch (\Throwable $e) {
            Log::error('[firmarLote] loadP12 failed: '.$e->getMessage());
            return $this->fail('No se pudo leer el .p12: '.$e->getMessage(), 422, $request);
        }

        // 3) Transacción: firmar cada archivo y crear lote
        $items   = [];
        $skipped = [];
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
                $doc = $docs[$tipo];

                // Si ya estaba firmado, lo omitimos
                if ($doc->fecha_firma) {
                    $skipped[] = $tipo;
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

                $items[] = [
                    'id'     => $doc->id,
                    'tipo'   => $doc->tipo_documento,
                    'nombre' => basename($doc->archivo),
                    'ruta'   => $doc->archivo,
                    'hash'   => $hash,
                    'bytes'  => @filesize($abs) ?: 0,
                    'sig'    => $sigRel,
                ];
            }

            DB::commit();
            Log::info('[firmarLote] TX committed', [
                'lote_id'  => $lote->id,
                'firmados' => count($items),
                'omitidos' => $skipped
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('[firmarLote] TX failed: '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return $this->fail('No se pudo firmar el lote: '.$e->getMessage(), 500, $request);
        }

        // 4) ACUSE CONSOLIDADO (Blade) — pdf/acuse_lote.blade.php
        $acuseRel = null;
        try {
            if (!Storage::disk('public')->exists('acuses_lote')) {
                Storage::disk('public')->makeDirectory('acuses_lote');
            }

            $pdfLote = Pdf::loadView('pdf.acuse_lote', [
                'tituloAcuse' => 'Acuse de firma en lote',
                'loteId'      => $lote->id,
                'materia'     => $data['materia'],
                'grupo'       => $data['grupo'],
                'unidad'      => (int)$data['unidad'],
                'usuario'     => $user->name ?? ('Usuario '.$user->id),
                'rfc'         => $certInfo['rfc'],
                'certCN'      => $certInfo['cn'],
                'fecha'       => $lote->firmado_at,
                'items'       => $items,
            ])->setPaper('letter');

            $acuseRel = 'acuses_lote/acuse_lote_'.$lote->id.'.pdf';
            Storage::disk('public')->put($acuseRel, $pdfLote->output());

            $lote->update(['acuse_lote' => $acuseRel]);

            Log::info('[firmarLote] PDF lote generado', ['lote_id' => $lote->id, 'acuse' => $acuseRel]);
        } catch (\Throwable $e) {
            Log::error('[firmarLote] PDF lote failed: '.$e->getMessage());
        }

        // 5) ACUSES INDIVIDUALES (reusa pdf/acuse_individual.blade.php)
        try {
            if (!Storage::disk('public')->exists('acuses')) {
                Storage::disk('public')->makeDirectory('acuses');
            }

            // Programa educativo (una sola consulta)
            $programa = DB::connection('cargahoraria')
                ->table('teacher_subjects as ts')
                ->join('subjects as s',  'ts.subject_id', '=', 's.subject_id')
                ->join('groups   as g',  'ts.group_id',   '=', 'g.group_id')
                ->join('programs as p',  's.program_id',  '=', 'p.program_id')
                ->where('ts.teacher_id', Auth::user()->teacher_id)
                ->where('s.subject_name', $data['materia'])
                ->where('g.group_name',   $data['grupo'])
                ->value('p.program_name') ?? 'Programa desconocido';

            foreach ($items as $it) {
                $doc = DocumentoSubido::find($it['id']);
                if (!$doc) continue;

                $pdfInd = Pdf::loadView('pdf.acuse_individual', [
                    'tituloAcuse' => 'Acuse de recepción de documentación',
                    'materia'     => $data['materia'],
                    'grupo'       => $data['grupo'],
                    'unidad'      => $data['unidad'],
                    'tipo'        => $it['tipo'],
                    'usuario'     => $user->name ?? ('Usuario '.$user->id),
                    'rfc'         => $certInfo['rfc'],
                    'fecha_firma' => optional($doc->fecha_firma)->format('Y-m-d H:i:s'),
                    'hashArchivo' => $it['hash'],
                    'programa'    => $programa,
                ])->setPaper('letter');

                $acuseDocRel = 'acuses/acuse_doc_'.$doc->id.'.pdf';
                Storage::disk('public')->put($acuseDocRel, $pdfInd->output());

                $doc->update(['acuse_pdf' => $acuseDocRel]);
            }

            Log::info('[firmarLote] PDFs individuales generados', ['count' => count($items)]);
        } catch (\Throwable $e) {
            Log::error('[firmarLote] PDF individual failed: '.$e->getMessage());
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
            'firmados'    => count($items),
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
