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
        'link',
        'seccion_id',
        'icono'
    ];

    public function seccion()
    {
        return $this->belongsTo(Seccion::class);
    }

    public function subsections()
    {
        return $this->hasMany(Subsection::class)->orderBy('orden');
    }

}
