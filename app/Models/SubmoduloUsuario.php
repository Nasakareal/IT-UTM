<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubmoduloUsuario extends Model
{
    protected $table = 'submodulo_usuario';

    protected $fillable = [
        'user_id',
        'submodulo_id',
        'estatus',
    ];
}
