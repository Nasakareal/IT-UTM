<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/login', [App\Http\Controllers\AuthController::class, 'loginForm'])->name('login');
Route::post('/login', [App\Http\Controllers\AuthController::class, 'login']);
Route::post('/logout', [App\Http\Controllers\AuthController::class, 'logout'])->name('logout');

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

// Rutas públicas para subsecciones (visualización)
Route::middleware('auth')->group(function () {
    Route::get('/subsections/{subsection}', [App\Http\Controllers\SubsectionController::class, 'show'])->name('subsections.show');
});


// Rutas Públicas para Carpetas (visualización)
Route::middleware('auth')->group(function () {
    // Mostrar carpeta (y subcarpetas, archivos)
    Route::get('/carpetas/{carpeta}', [App\Http\Controllers\CarpetaController::class, 'show'])->name('carpetas.show');
});

// Rutas Públicas para Módulos (visualización)
Route::middleware('auth')->group(function () {
    // Mostrar individualmente el contenido de un módulo
    Route::get('/modulos/{modulo}', [App\Http\Controllers\ModuloController::class, 'show'])->name('modulos.show');
});

// Rutas de Configuraciones Generales
Route::prefix('settings')->middleware('can:ver configuraciones')->group(function () {
    // Configuración general
    Route::get('/', [App\Http\Controllers\SettingsController::class, 'index'])->name('settings.index');

    // Usuarios
    Route::prefix('users')->middleware('can:ver usuarios')->group(function () {
        Route::get('/', [App\Http\Controllers\UserController::class, 'index'])->name('users.index');
        Route::get('/create', [App\Http\Controllers\UserController::class, 'create'])->middleware('can:crear usuarios')->name('users.create');
        Route::post('/', [App\Http\Controllers\UserController::class, 'store'])->middleware('can:crear usuarios')->name('users.store');
        Route::get('/{user}', [App\Http\Controllers\UserController::class, 'show'])->middleware('can:ver usuarios')->name('users.show');
        Route::get('/{user}/edit', [App\Http\Controllers\UserController::class, 'edit'])->middleware('can:editar usuarios')->name('users.edit');
        Route::put('/{user}', [App\Http\Controllers\UserController::class, 'update'])->middleware('can:editar usuarios')->name('users.update');
        Route::delete('/{user}', [App\Http\Controllers\UserController::class, 'destroy'])->middleware('can:eliminar usuarios')->name('users.destroy');
    });

    // Roles
    Route::prefix('roles')->middleware('can:ver roles')->group(function () {
        Route::get('/', [App\Http\Controllers\RoleController::class, 'index'])->name('roles.index');
        Route::get('/create', [App\Http\Controllers\RoleController::class, 'create'])->middleware('can:crear roles')->name('roles.create');
        Route::post('/', [App\Http\Controllers\RoleController::class, 'store'])->middleware('can:crear roles')->name('roles.store');
        Route::get('/{role}', [App\Http\Controllers\RoleController::class, 'show'])->name('roles.show');
        Route::get('/{role}/edit', [App\Http\Controllers\RoleController::class, 'edit'])->middleware('can:editar roles')->name('roles.edit');
        Route::put('/{role}', [App\Http\Controllers\RoleController::class, 'update'])->middleware('can:editar roles')->name('roles.update');
        Route::delete('/{role}', [App\Http\Controllers\RoleController::class, 'destroy'])->middleware('can:eliminar roles')->name('roles.destroy');
        Route::get('/{role}/permissions', [App\Http\Controllers\RoleController::class, 'permissions'])->middleware('can:editar roles')->name('roles.permissions');
        Route::post('/{role}/permissions', [App\Http\Controllers\RoleController::class, 'assignPermissions'])->middleware('can:editar roles')->name('roles.assignPermissions');
    });

    // Actividad
    Route::prefix('actividad')->middleware('can:ver actividades')->group(function () {
        Route::get('/', [App\Http\Controllers\ActividadController::class, 'index'])->name('actividades.index');
        Route::get('/actividad', [App\Http\Controllers\ActividadController::class, 'create'])->middleware('can:crear actividades')->name('actividades.create');
        Route::post('/', [App\Http\Controllers\ActividadController::class, 'store'])->middleware('can:crear actividades')->name('actividades.store');
        Route::get('/{actividad}', [App\Http\Controllers\ActividadController::class, 'show'])->middleware('can:ver actividades')->name('actividades.show');
        Route::get('/{actividad}/edit', [App\Http\Controllers\ActividadController::class, 'edit'])->middleware('can:editar actividades')->name('actividades.edit');
        Route::put('/{actividad}', [App\Http\Controllers\ActividadController::class, 'update'])->middleware('can:editar actividades')->name('actividades.update');
        Route::delete('/{actividad}', [App\Http\Controllers\ActividadController::class, 'destroy'])->middleware('can:eliminar actividades')->name('actividades.destroy');
    });

    // Gestión de Módulos (solo para administración)
    Route::prefix('modulos')->middleware('can:ver modulos')->group(function () {
        Route::get('/', [App\Http\Controllers\ModuloController::class, 'index'])->name('modulos.index');
        Route::get('/create', [App\Http\Controllers\ModuloController::class, 'create'])->middleware('can:crear modulos')->name('modulos.create');
        Route::post('/', [App\Http\Controllers\ModuloController::class, 'store'])->middleware('can:crear modulos')->name('modulos.store');
        Route::get('/{modulo}/edit', [App\Http\Controllers\ModuloController::class, 'edit'])->middleware('can:editar modulos')->name('modulos.edit');
        Route::put('/{modulo}', [App\Http\Controllers\ModuloController::class, 'update'])->middleware('can:editar modulos')->name('modulos.update');
        Route::delete('/{modulo}', [App\Http\Controllers\ModuloController::class, 'destroy'])->middleware('can:eliminar modulos')->name('modulos.destroy');
    });

    // Gestión de Carpetas (solo para administración)
    Route::prefix('carpetas')->middleware('can:ver carpetas')->group(function () {
        Route::get('/', [App\Http\Controllers\CarpetaController::class, 'index'])->name('carpetas.index');
        Route::get('/create', [App\Http\Controllers\CarpetaController::class, 'create'])->middleware('can:crear carpetas')->name('carpetas.create');
        Route::post('/', [App\Http\Controllers\CarpetaController::class, 'store'])->middleware('can:crear carpetas')->name('carpetas.store');
        Route::get('/{carpeta}/edit', [App\Http\Controllers\CarpetaController::class, 'edit'])->middleware('can:editar carpetas')->name('carpetas.edit');
        Route::put('/{carpeta}', [App\Http\Controllers\CarpetaController::class, 'update'])->middleware('can:editar carpetas')->name('carpetas.update');
        Route::delete('/{carpeta}', [App\Http\Controllers\CarpetaController::class, 'destroy'])->middleware('can:eliminar carpetas')->name('carpetas.destroy');
    });

    // Gestión de Subsecciones (solo para administración)
    Route::prefix('subsections')->middleware('can:ver subsecciones')->group(function () {
        Route::get('/', [App\Http\Controllers\SubsectionController::class, 'index'])->name('subsections.index');
        Route::get('/create', [App\Http\Controllers\SubsectionController::class, 'create'])->middleware('can:crear subsecciones')->name('subsections.create');
        Route::post('/', [App\Http\Controllers\SubsectionController::class, 'store'])->middleware('can:crear subsecciones')->name('subsections.store');
        Route::get('/{subsection}/edit', [App\Http\Controllers\SubsectionController::class, 'edit'])->middleware('can:editar subsecciones')->name('subsections.edit');
        Route::put('/{subsection}', [App\Http\Controllers\SubsectionController::class, 'update'])->middleware('can:editar subsecciones')->name('subsections.update');
        Route::delete('/{subsection}', [App\Http\Controllers\SubsectionController::class, 'destroy'])->middleware('can:eliminar subsecciones')->name('subsections.destroy');
    });
});

