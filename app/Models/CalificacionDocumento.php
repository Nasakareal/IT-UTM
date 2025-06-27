<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CalificacionDocumento extends Model
{
    use HasFactory;

    protected $fillable = [
        'documento_id',
        'evaluador_id',
        'calificacion',
    ];
}
