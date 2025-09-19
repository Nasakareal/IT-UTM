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
        'fecha_apertura',
        'fecha_limite', 
        'fecha_cierre',
        'estatus', 
        'acuse_pdf', 
        'documento_url',
        'orden'
    ];

    public function subsection()
    {
        return $this->belongsTo(Subsection::class, 'subsection_id');
    }

    public function archivos()
    {
        return $this->hasMany(SubmoduloArchivo::class);
    }

    public function submoduloUsuarios()
    {
        return $this->hasMany(\App\Models\SubmoduloUsuario::class);
    }

    public function categoriasPermitidas()
    {
        return $this->hasMany(\App\Models\CategoriaSubmodulo::class, 'submodulo_id');
    }

}
