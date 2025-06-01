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
        'ruta',
        'firma_sat',
        'fecha_firma'
    ];

    public function submodulo()
    {
        return $this->belongsTo(Submodulo::class, 'submodulo_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function subsection()
    {
        return $this->belongsTo(Subsection::class, 'subsection_id');
    }

}
