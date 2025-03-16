<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Carpeta extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre',
        'color',
        'parent_id'
    ];

    // Relación con la carpeta padre
    public function parent()
    {
        return $this->belongsTo(Carpeta::class, 'parent_id');
    }

    // Relación con las subcarpetas (hijas)
    public function children()
    {
        return $this->hasMany(Carpeta::class, 'parent_id');
    }

    // Relación con archivos
    public function archivos()
    {
        return $this->hasMany(Archivo::class);
    }
}
