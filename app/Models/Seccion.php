<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Seccion extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre',
        'slug',
        'color',
        'orden',
    ];

    // Relación 1 a muchos con Modulo
    public function modulos()
    {
        return $this->hasMany(Modulo::class);
    }
}
