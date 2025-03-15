<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/login', [App\Http\Controllers\AuthController::class, 'loginForm'])->name('login');
Route::post('/login', [App\Http\Controllers\AuthController::class, 'login']);
Route::post('/logout', [App\Http\Controllers\AuthController::class, 'logout'])->name('logout');

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

// Rutas para Módulos
Route::prefix('modulos')->middleware('auth')->group(function () {
    Route::get('/', [App\Http\Controllers\ModuloController::class, 'index'])->name('modulos.index');
    Route::get('/create', [App\Http\Controllers\ModuloController::class, 'create'])->middleware('can:crear modulos')->name('modulos.create');
    Route::post('/', [App\Http\Controllers\ModuloController::class, 'store'])->middleware('can:crear modulos')->name('modulos.store');
    Route::get('/{modulo}', [App\Http\Controllers\ModuloController::class, 'show'])->middleware('can:ver modulos') ->name('modulos.show');
    Route::get('/{modulo}/edit', [App\Http\Controllers\ModuloController::class, 'edit'])->middleware('can:editar modulos')->name('modulos.edit');
    Route::put('/{modulo}', [App\Http\Controllers\ModuloController::class, 'update'])->middleware('can:editar modulos') ->name('modulos.update');
    Route::delete('/{modulo}', [App\Http\Controllers\ModuloController::class, 'destroy'])->middleware('can:eliminar modulos')->name('modulos.destroy');
});
