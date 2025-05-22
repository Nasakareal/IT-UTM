<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentoSubido extends Model
{
    protected $table = 'documentos_subidos';

    protected $fillable = [
        'user_id',
        'materia',
        'unidad',
        'tipo_documento',
        'archivo',
        'firma_sat',
        'fecha_firma',
        'acuse_pdf',
    ];
}
