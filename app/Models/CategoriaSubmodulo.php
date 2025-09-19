<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CategoriaSubmodulo extends Model
{
    protected $table = 'categoria_submodulos';

    public $timestamps = false;

    protected $fillable = ['submodulo_id', 'categoria'];

    public function submodulo()
    {
        return $this->belongsTo(Submodulo::class, 'submodulo_id');
    }
}
