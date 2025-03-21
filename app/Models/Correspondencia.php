<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Correspondencia extends Model
{
    use HasFactory;

    protected $fillable = [
        'remitente',
        'referencia',
        'oficio',
        'tipo_documento',
        'fecha_elaboracion',
        'tema',
        'descripcion_asunto',
        'archivo_pdf',
        'observaciones',
        'estado',
        'usuario_id',
    ];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
