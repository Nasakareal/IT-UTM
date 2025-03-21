<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubmoduloArchivo extends Model
{
    use HasFactory;

    protected $fillable = [
        'submodulo_id',
        'user_id',
        'nombre', 
        'ruta'
    ];

    public function submodulo()
    {
        return $this->belongsTo(Submodulo::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
