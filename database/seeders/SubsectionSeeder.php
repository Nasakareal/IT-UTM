<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Subsection;

class SubsectionSeeder extends Seeder
{
    public function run()
    {
        $subsections = [
            [
                'nombre'     => 'LICENCIATURA EN INGENIERÍA EN MANTENIMIENTO INDUSTRIAL',
                'modulo_id'  => 1,
                'parent_id'  => null,
            ],
            [
                'nombre'     => 'LICENCIATURA EN ASESOR FINANCIERO',
                'modulo_id'  => 1,
                'parent_id'  => null,
            ],
            [
                'nombre'     => 'LICENCIATURA EN INGENIERÍA EN ENERGÍA Y DESARROLLO SOSTENIBLE',
                'modulo_id'  => 1,
                'parent_id'  => null,
            ],
        ];

        foreach ($subsections as $data) {
            Subsection::create($data);
        }
    }
}
