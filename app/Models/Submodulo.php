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
        'fecha_limite', 
        'estatus', 
        'acuse_pdf', 
        'documento_url'
    ];

    public function subsection()
    {
        return $this->belongsTo(Subsection::class);
    }

    public function archivos()
    {
        return $this->hasMany(SubmoduloArchivo::class);
    }
}
