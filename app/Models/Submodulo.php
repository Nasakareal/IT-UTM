<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Submodulo extends Model
{
    use HasFactory;

    protected $fillable = [
        'subsection_id', 
        'titulo', 
        'descripcion', 
        'documento_solicitado',
        'fecha_apertura' => 'datetime',
        'fecha_limite' => 'datetime', 
        'fecha_cierre' => 'datetime',
        'estatus', 
        'acuse_pdf', 
        'documento_url',
        'orden'
    ];

    public function subsection()
    {
        return $this->belongsTo(Subsection::class);
    }

    public function archivos()
    {
        return $this->hasMany(SubmoduloArchivo::class);
    }

    public function submoduloUsuarios()
    {
        return $this->hasMany(\App\Models\SubmoduloUsuario::class);
    }
}
