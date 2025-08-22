<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CalificacionSubmoduloArchivo extends Model
{
    use HasFactory;

    protected $table = 'calificacion_submodulo_archivos';

    protected $fillable = [
        'submodulo_archivo_id',
        'evaluador_id',
        'profesor_id',
        'calificacion',
    ];

    protected $casts = [
        'calificacion' => 'integer',
    ];

    public function submoduloArchivo()
    {
        return $this->belongsTo(SubmoduloArchivo::class, 'submodulo_archivo_id');
    }

    public function evaluador()
    {
        return $this->belongsTo(User::class, 'evaluador_id');
    }

    public function profesor()
    {
        return $this->belongsTo(User::class, 'profesor_id');
    }
}
