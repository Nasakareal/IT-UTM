<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Archivo extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre',
        'ruta',
        'carpeta_id'
    ];

    public function carpeta()
    {
        return $this->belongsTo(Carpeta::class);
    }
}
