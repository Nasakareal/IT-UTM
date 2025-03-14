<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Requerimiento extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'nombre', 'fecha_limite', 'estado'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
