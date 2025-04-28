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
        // Lógica idéntica a antes hasta el paso de generar el QR...
        $submodulo   = Submodulo::with('archivos')->findOrFail($submoduloId);
        $remitente   = Auth::user();
        $archivo     = $submodulo->archivos()->where('nombre','like','%oficio_entrega%')->latest('fecha_firma')->firstOrFail();
        $hashContent = @file_get_contents(storage_path('app/'.$submodulo->documento_url)) ?: $archivo->firma_sat;
        $hashArchivo = hash('sha256', $hashContent);

        // Datos del acuse…
        $dataAcuse = [
            'institucion' => 'Universidad Tecnológica de Morelia',
            'ciudad'      => 'Morelia, Michoacán',
            'fecha'       => Carbon::now()->format('d \\d\\e F \\d\\e Y'),
            'tituloAcuse' => 'Acuse de recepción de documentación',
            'subtitulo'   => 'Documentación correspondiente al submódulo: '.$submodulo->titulo,
            'cuerpo'      => 'La Universidad Tecnológica de Morelia hace constar que ha recibido en tiempo y forma la documentación solicitada correspondiente al submódulo "'
                              .$submodulo->titulo
                              .'". La información será revisada y, en caso de detectar inconsistencias, se solicitarán las aclaraciones necesarias.',
            'atentamente'  => 'Universidad Tecnológica de Morelia',
            'impresion'    => Carbon::now()->format('Y-m-d H:i:s'),
            'firmante'     => $remitente->name,
            'rfc'          => $remitente->rfc ?? 'N/A',
            'fecha_firma'  => Carbon::parse($archivo->fecha_firma)->format('Y-m-d H:i:s'),
            'firma_dig'    => $archivo->firma_sat,
        ];

        // **Nuevo**: URL pública para visualizar el acuse
        $urlVer = route('submodulos.ver-acuse', ['submodulo' => $submodulo->id]);

        // Generar QR con la URL
        $writer = new PngWriter();
        $qr = Builder::create()
            ->writer($writer)
            ->data($urlVer)
            ->logoPath(public_path('utm_logo2.png'))
            ->logoResizeToWidth(40)
            ->logoResizeToHeight(30)
            ->size(190)
            ->margin(10)
            ->build();
        $qrDataUri = $qr->getDataUri();

        // Descargar PDF
        $pdf = Pdf::loadView('pdf.acuse', [
            'dataAcuse'   => $dataAcuse,
            'qrDataUri'   => $qrDataUri,
            'hashArchivo' => $hashArchivo,
        ])->setPaper('letter','portrait');

        return $pdf->download("acuse_submodulo_{$submodulo->id}.pdf");
    }

    /**
     * Muestra el acuse inline (navegador) para la ruta QR.
     */
    public function verAcuse($submoduloId)
    {
        $submodulo = Submodulo::with('archivos')->findOrFail($submoduloId);
        // Reproducimos la misma lógica de armado de PDF:
        // (podrías extraer a un método privado para no duplicar)
        $remitente   = Auth::user();
        $archivo     = $submodulo->archivos()->where('nombre','like','%oficio_entrega%')->latest('fecha_firma')->firstOrFail();
        $hashContent = @file_get_contents(storage_path('app/'.$submodulo->documento_url)) ?: $archivo->firma_sat;
        $hashArchivo = hash('sha256', $hashContent);

        $dataAcuse = [
            'institucion' => 'Universidad Tecnológica de Morelia',
            'ciudad'      => 'Morelia, Michoacán',
            'fecha'       => Carbon::now()->format('d \\d\\e F \\d\\e Y'),
            'tituloAcuse'=> 'Acuse de recepción de documentación',
            'subtitulo'  => 'Documentación correspondiente al submódulo: '.$submodulo->titulo,
            'cuerpo'     => 'La Universidad Tecnológica de Morelia hace constar que ha recibido en tiempo y forma la documentación solicitada correspondiente al submódulo "'
                             .$submodulo->titulo
                             .'". La información será revisada y, en caso de detectar inconsistencias, se solicitarán las aclaraciones necesarias.',
            'atentamente'=> 'Universidad Tecnológica de Morelia',
            'impresion'  => Carbon::now()->format('Y-m-d H:i:s'),
            'firmante'   => $remitente->name,
            'rfc'        => $remitente->rfc ?? 'N/A',
            'fecha_firma'=> Carbon::parse($archivo->fecha_firma)->format('Y-m-d H:i:s'),
            'firma_dig'  => $archivo->firma_sat,
        ];

        $qrDataUri = Builder::create()
            ->writer(new PngWriter())
            ->data(route('submodulos.ver-acuse',['submodulo'=>$submodulo->id]))
            ->logoPath(public_path('utm_logo2.png'))
            ->logoResizeToWidth(40)
            ->logoResizeToHeight(40)
            ->size(150)
            ->margin(10)
            ->build()
            ->getDataUri();

        $pdf = Pdf::loadView('pdf.acuse',[
            'dataAcuse'   => $dataAcuse,
            'qrDataUri'   => $qrDataUri,
            'hashArchivo' => $hashArchivo,
        ])->setPaper('letter','portrait');

        // Aquí indicamos inline en lugar de download:
        return $pdf->stream("acuse_submodulo_{$submodulo->id}.pdf");
    }
}
