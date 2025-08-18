<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FirmaLote extends Model
{
    protected $table = 'firma_lotes';

    protected $fillable = [
        'user_id',
        'materia',
        'grupo',
        'unidad',
        'firmado_at',
        'acuse_lote',
        'total_documentos',
        'certificado_cn',
        'certificado_rfc',
    ];

    protected $casts = [
        'firmado_at'  => 'datetime',
        'created_at'  => 'datetime',
        'updated_at'  => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function documentos()
    {
        return $this->hasMany(DocumentoSubido::class, 'lote_id');
    }
}
