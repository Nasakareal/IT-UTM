<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles, LogsActivity;

    protected $fillable = [
        'name',
        'nombres',
        'apellido_paterno',
        'apellido_materno',
        'curp',
        'correo_institucional',
        'correo_personal',
        'password',
        'estado',
        'foto_perfil',
        'area',
        'categoria',
        'caracter',
        'teacher_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'name',
                'nombres',
                'apellido_paterno',
                'apellido_materno',
                'curp',
                'correo_institucional',
                'correo_personal',
                'estado',
                'foto_perfil',
                'area',
                'categoria',
                'caracter'
            ])
            ->setLogName('users')
            ->logOnlyDirty();
    }

    public function getDescriptionForEvent(string $eventName): string
    {
        return "El usuario ha sido {$eventName}";
    }
}
