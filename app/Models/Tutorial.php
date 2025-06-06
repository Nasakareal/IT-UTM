<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tutorial extends Model
{
    use HasFactory;

    protected $fillable = [
        'titulo',
        'descripcion',
        'tipo',
        'url',
        'imagenes',
        'activo',
        'orden',
    ];

    protected $casts = [
        'imagenes' => 'array',
        'activo' => 'boolean',
    ];
}
