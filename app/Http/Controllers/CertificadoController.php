<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CertificadoController extends Controller
{
    public function generarP12(Request $request)
    {
        // 1) validación
        $request->validate([
            'cer'      => 'required|file',
            'key'      => 'required|file',
            'password' => 'required|string',
        ]);

        // 2) guarda temporales
        $cerPath = $request->file('cer')->store('temp');
        $keyPath = $request->file('key')->store('temp');
        $keyPass = $request->password;

        // 3) carga bytes
        $derCert = file_get_contents(storage_path("app/{$cerPath}"));
        $derKey  = file_get_contents(storage_path("app/{$keyPath}"));

        // 4) convierte DER→PEM para certificado
        $pemCert  = "-----BEGIN CERTIFICATE-----\n"
                  . chunk_split(base64_encode($derCert), 64, "\n")
                  . "-----END CERTIFICATE-----\n";

        // 5) convierte DER→PEM para clave privada (PKCS#8 Encrypted)
        $pemKey   = "-----BEGIN ENCRYPTED PRIVATE KEY-----\n"
                  . chunk_split(base64_encode($derKey), 64, "\n")
                  . "-----END ENCRYPTED PRIVATE KEY-----\n";

        // 6) lee recursos OpenSSL
        $cert = @openssl_x509_read($pemCert);
        $pkey = @openssl_pkey_get_private($pemKey, $keyPass);

        if (! $cert || ! $pkey) {
            return back()->with('error', 'Error al leer .cer o .key. Verifica contraseña y formato.');
        }

        // 7) exporta PKCS#12
        $p12     = null;
        $p12Pass = $keyPass;
        if (! openssl_pkcs12_export($cert, $p12, $pkey, $p12Pass)) {
            return back()->with('error', 'No se pudo generar el P12. Verifica la clave.');
        }

        // 8) crea carpeta si hace falta
        $dir = storage_path('app/p12');
        if (! is_dir($dir)) mkdir($dir, 0755, true);

        // 9) escribe y descarga
        $filename = 'certificado_' . time() . '.p12';
        $fullPath = "$dir/$filename";
        file_put_contents($fullPath, $p12);

        // 10) limpia temporales
        \Storage::delete([$cerPath, $keyPath]);

        // 11) fuerza descarga y borra
        return response()->download($fullPath, $filename, [
            'Content-Type' => 'application/x-pkcs12',
        ])->deleteFileAfterSend(true);
    }

}
