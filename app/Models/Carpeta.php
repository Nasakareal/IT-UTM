<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Carpeta extends Model
{
    protected $fillable = [
        'nombre',
        'color',
        'parent_id',
        'subsection_id',
        'orden'
    ];

    public function parent()
    {
        return $this->belongsTo(Carpeta::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Carpeta::class, 'parent_id');
    }

    public function archivos()
    {
        return $this->hasMany(Archivo::class);
    }
    public function submodulos()
    {
        return $this->hasMany(Submodulo::class, 'subsection_id', 'subsection_id');
    }

}
