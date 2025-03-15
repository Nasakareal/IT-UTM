<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Modulo extends Model
{
    use HasFactory;

    protected $fillable = [
        'titulo',
        'anio',
        'categoria',
        'color',
        'descripcion',
        'link'
    ];

    public function seccion()
    {
        return $this->belongsTo(Seccion::class);
    }
}
