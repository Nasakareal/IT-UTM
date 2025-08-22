<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class CalificacionesExport implements FromArray, WithHeadings
{
    protected array $rows;

    public function __construct(array $rows = [])
    {
        // Puedes inyectar datos desde el controller; si no, arma aquí tus consultas
        $this->rows = $rows ?: [
            // Ejemplo de fila
            ['Profesor','TeacherID','Tipo','Esperados','Entregados','Cumplimiento','Promedio','UnidadHasta'],
            // ...
        ];
    }

    public function headings(): array
    {
        return ['Profesor','TeacherID','Tipo','Esperados','Entregados','Cumplimiento','Promedio','UnidadHasta'];
    }

    public function array(): array
    {
        // Si en headings ya pusiste los títulos, aquí devuelve solo datos (sin encabezado)
        return array_filter($this->rows, fn($r) => $r[0] !== 'Profesor');
    }
}
