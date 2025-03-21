<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubmoduloArchivo extends Model
{
    use HasFactory;

    protected $fillable = [
        'submodulo_id', 
        'nombre', 
        'ruta'
    ];

    public function submodulo()
    {
        return $this->belongsTo(Submodulo::class);
    }
}
