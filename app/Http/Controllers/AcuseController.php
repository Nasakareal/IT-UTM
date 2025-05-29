<?php

namespace App\Http\Controllers;

use App\Models\Submodulo;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class AcuseController extends Controller
{
    /**
     * Genera y descarga el acuse en PDF (descarga).
     */
    public function generarAcuse($submoduloId)
    {
        $submodulo = Submodulo::with('archivos')->findOrFail($submoduloId);
        $usuario   = Auth::user();

        $archivo = $submodulo->archivos()
            ->where('nombre', 'like', '%oficio_entrega%')
            ->latest('fecha_firma')
            ->firstOrFail();

        $hashContent = @file_get_contents(storage_path('app/public/' . $archivo->ruta)) ?: $archivo->firma_sat;
        $hashArchivo = hash('sha256', $hashContent);

        $dataAcuse = [
            'institucion'   => 'Universidad Tecnológica de Morelia',
            'ciudad'        => 'Morelia, Michoacán',
            'fecha'         => Carbon::now()->format('d \\d\\e F \\d\\e Y'),
            'tituloAcuse'   => 'Acuse de recepción del submódulo "' . ($submodulo->titulo ?? 'No especificado') . '" correspondiente al cuatrimestre Mayo–Agosto 2025.',
            'materia'       => $submodulo->titulo ?? 'No especificado',
            'tipo'          => 'Oficio Entrega',
            'usuario'       => $usuario->nombres ?? 'No especificado',
            'rfc'           => $usuario->curp ?? 'N/A',
            'fecha_firma'   => Carbon::parse($archivo->fecha_firma)->format('Y-m-d H:i:s'),
            'hashArchivo'   => $hashArchivo,
            'programa'      => 'No especificado',
            'atentamente'   => 'Universidad Tecnológica de Morelia',
        ];

        $urlVer = route('submodulos.ver-acuse', ['submodulo' => $submodulo->id]);

        $qr = Builder::create()
            ->writer(new PngWriter())
            ->data($urlVer)
            ->logoPath(public_path('utm_logo2.png'))
            ->logoResizeToWidth(40)
            ->logoResizeToHeight(30)
            ->size(190)
            ->margin(10)
            ->build()
            ->getDataUri();

        $pdf = Pdf::loadView('pdf.acuse', array_merge($dataAcuse, [
            'qrDataUri' => $qr,
        ]))->setPaper('letter', 'portrait');

        return $pdf->download("acuse_submodulo_{$submodulo->id}.pdf");
    }

    /**
     * Muestra el acuse inline (navegador).
     */
    public function verAcuse($submoduloId)
    {
        $submodulo = Submodulo::with('archivos')->findOrFail($submoduloId);
        $usuario   = Auth::user();

        $archivo = $submodulo->archivos()
            ->where('nombre', 'like', '%oficio_entrega%')
            ->latest('fecha_firma')
            ->firstOrFail();

        $hashContent = @file_get_contents(storage_path('app/public/' . $archivo->ruta)) ?: $archivo->firma_sat;
        $hashArchivo = hash('sha256', $hashContent);

        $dataAcuse = [
            'institucion'   => 'Universidad Tecnológica de Morelia',
            'ciudad'        => 'Morelia, Michoacán',
            'fecha'         => Carbon::now()->format('d \\d\\e F \\d\\e Y'),
            'tituloAcuse'   => 'Acuse de recepción del submódulo "' . ($submodulo->titulo ?? 'No especificado') . '" correspondiente al cuatrimestre Mayo–Agosto 2025.',
            'materia'       => $submodulo->titulo ?? 'No especificado',
            'tipo'          => 'Oficio Entrega',
            'usuario'       => $usuario->nombres ?? 'No especificado',
            'rfc'           => $usuario->curp ?? 'N/A',
            'fecha_firma'   => Carbon::parse($archivo->fecha_firma)->format('Y-m-d H:i:s'),
            'hashArchivo'   => $hashArchivo,
            'programa'      => 'No especificado',
            'atentamente'   => 'Universidad Tecnológica de Morelia',
        ];

        $qr = Builder::create()
            ->writer(new PngWriter())
            ->data(route('submodulos.ver-acuse', ['submodulo' => $submodulo->id]))
            ->logoPath(public_path('utm_logo2.png'))
            ->logoResizeToWidth(40)
            ->logoResizeToHeight(30)
            ->size(190)
            ->margin(10)
            ->build()
            ->getDataUri();

        $pdf = Pdf::loadView('pdf.acuse', array_merge($dataAcuse, [
            'qrDataUri' => $qr,
        ]))->setPaper('letter', 'portrait');

        return $pdf->stream("acuse_submodulo_{$submodulo->id}.pdf");
    }
}
