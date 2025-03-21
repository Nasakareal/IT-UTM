<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subsection extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre',
        'modulo_id',
    ];

    public function carpetas() {
        return $this->hasMany(Carpeta::class, 'subsection_id');
    }

    public function modulo()
    {
        return $this->belongsTo(Modulo::class);
    }

    public function parent()
    {
        return $this->belongsTo(Subsection::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Subsection::class, 'parent_id');
    }

    public function submodulos()
    {
        return $this->hasMany(Submodulo::class);
    }

}
