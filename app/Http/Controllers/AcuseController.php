<?php

namespace App\Http\Controllers;

use App\Models\Submodulo;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Endroid\QrCode\Builder\Builder;
use Illuminate\Support\Facades\Auth;

class AcuseController extends Controller
{
    public function generarAcuse($submoduloId)
    {
        $submodulo = Submodulo::findOrFail($submoduloId);
        $user = Auth::user();

        $dataAcuse = [
            'institucion'   => 'Universidad Tecnológica de Morelia',
            'ciudad'        => 'Morelia',
            'fecha'         => now()->format('d \d\e F \d\e Y'),
            'tituloAcuse'   => 'Acuse de recepción del reporte: ' . $submodulo->titulo,
            'subtitulo'     => '(RENASME-ENPA) correspondiente al primer trimestre del ejercicio fiscal ' . $submodulo->anio,
            'cuerpo'        => 'En el marco de las Acciones en Salud Mental y de la Estrategia Nacional de Prevención de Adicciones, se hace constar que la Universidad Tecnológica de Morelia, a ' . now()->format('Y-m-d H:i:s') . ', entregó el reporte "' . $submodulo->titulo . '". La información será revisada y, de encontrar errores o inconsistencias, se procederá a solicitar su corrección.',
            'atentamente'   => 'Universidad Tecnológica de Morelia',
            'impresion'     => now()->format('(Y-m-d H:i:s)'),
            'archivos'      => 'ENPA 2024.pdf, 1er RENASME-ENPA 8.0 ene 2024 UTM.xls',
        ];


        $qrContent = $submodulo->id . '|' . $user->id . '|' . now()->timestamp;

        $qrResult = Builder::create()
            ->data($qrContent)
            ->size(150)
            ->margin(10)
            ->build();

        $qrDataUri = $qrResult->getDataUri();

        $pdf = Pdf::loadView('pdf.acuse', [
            'dataAcuse' => $dataAcuse,
            'qrDataUri' => $qrDataUri,
            'qrContent' => $qrContent,
            'submodulo' => $submodulo,
            'user'      => $user,
        ]);


        return $pdf->download('acuse_' . $submodulo->id . '.pdf');
    }
}
