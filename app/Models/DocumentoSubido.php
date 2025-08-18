<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentoSubido extends Model
{
    protected $table = 'documentos_subidos';

    protected $fillable = [
        'user_id',
        'materia',
        'unidad',
        'tipo_documento',
        'archivo',
        'hash_sha256',
        'firma_sig',
        'lote_id',
        'firma_sat',
        'fecha_firma',
        'acuse_pdf',
        'grupo',
    ];

    protected $casts = [
        'fecha_firma' => 'datetime',
        'created_at'  => 'datetime',
        'updated_at'  => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function lote()
    {
        return $this->belongsTo(FirmaLote::class, 'lote_id');
    }
}
