<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MateriaDocenteSnapshot extends Model
{
    protected $table = 'materias_docentes_snapshots';

    protected $fillable = [
        'teacher_id',
        'materia',
        'grupo',
        'programa',
        'unidades',
        'subject_id',
        'group_id',
        'program_id',
        'cuatrimestre_id',
        'quarter_name',
        'captured_at',
        'source',
    ];
}
