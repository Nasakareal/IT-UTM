<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\Correspondencia;
use App\Policies\CorrespondenciaPolicy;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Correspondencia::class => CorrespondenciaPolicy::class,
    ];

    public function boot()
    {
        $this->registerPolicies();

        //
    }
}
