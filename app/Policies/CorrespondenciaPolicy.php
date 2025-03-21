<?php

namespace App\Policies;

use App\Models\Correspondencia;
use App\Models\User;

class CorrespondenciaPolicy
{
    public function viewAny(User $user)
    {
        return $user->hasRole('Administrador');
    }

    public function view(User $user, Correspondencia $correspondencia)
    {
        return $user->hasRole('Administrador') || $correspondencia->usuario_id === $user->id;
    }

    public function update(User $user, Correspondencia $correspondencia)
    {
        return $user->hasRole('Administrador');
    }
}
