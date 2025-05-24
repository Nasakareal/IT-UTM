<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Ruta para ver el acuse en el navegador
Route::get('submodulos/{submodulo}/ver-acuse', [App\Http\Controllers\AcuseController::class, 'verAcuse'])
     ->name('submodulos.ver-acuse');

Route::post('/secciones/sort', [App\Http\Controllers\SeccionController::class, 'sort'])->name('secciones.sort');
Route::post('documentos/subir',  [DocumentoSubidoController::class,'store'])->name('documentos.subir');
Route::post('documentos/firmar', [DocumentoSubidoController::class,'sign'])->name('documentos.firmar');



Route::get('/login', [App\Http\Controllers\AuthController::class, 'loginForm'])->name('login');
Route::post('/login', [App\Http\Controllers\AuthController::class, 'login']);
Route::post('/logout', [App\Http\Controllers\AuthController::class, 'logout'])->name('logout');

Route::middleware(['auth', 'password.changed'])->get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

// Rutas públicas para subsecciones (visualización)
Route::middleware('auth', 'password.changed')->group(function () {
    Route::get('/subsections/{subsection}', [App\Http\Controllers\SubsectionController::class, 'show'])->name('subsections.show');
});

// Rutas Públicas para Carpetas (visualización)
Route::middleware('auth', 'password.changed')->group(function () {
    // Mostrar carpeta (y subcarpetas, archivos)
    Route::get('/carpetas/{carpeta}', [App\Http\Controllers\CarpetaController::class, 'show'])->name('carpetas.show');
});

// Rutas Públicas para Módulos (visualización)
Route::middleware('auth', 'password.changed')->group(function () {
    // Mostrar individualmente el contenido de un módulo
    Route::get('/modulos/{modulo}', [App\Http\Controllers\ModuloController::class, 'show'])->name('modulos.show');

    // Gestión Académica – Documentos obligatorios
    Route::get('/modulos/5/gestion-academica', [App\Http\Controllers\GestionAcademicaController::class, 'index'])->name('modulo5.gestion');

    // Subida de documentos académicos
    Route::post('/documentos/subir', [App\Http\Controllers\DocumentoSubidoController::class, 'store'])->name('documentos.subir');
});



// Rutas de submódulos
Route::middleware('auth', 'password.changed')->group(function () {
    Route::get('/submodulos/{submodulo}', [App\Http\Controllers\SubmoduloController::class, 'show'])->name('submodulos.show');
    Route::post('/submodulos/subir-archivos', [App\Http\Controllers\SubmoduloController::class, 'subirArchivos'])->name('submodulos.subirArchivos');
    Route::get('/submodulos/{id}/archivos-usuario', [App\Http\Controllers\SubmoduloController::class, 'archivosUsuario'])->name('submodulos.archivosUsuario');
    Route::get('/submodulos/{id}/generar-acuse', [App\Http\Controllers\AcuseController::class, 'generarAcuse'])->name('submodulos.generarAcuse');
});

// Correspondencias
Route::prefix('correspondencias')->middleware('auth', 'password.changed', 'can:ver correspondencias')->group(function () {
    Route::get('/', [App\Http\Controllers\CorrespondenciaController::class, 'index'])->name('correspondencias.index');
    Route::get('/create', [App\Http\Controllers\CorrespondenciaController::class, 'create'])->middleware('can:crear correspondencias')->name('correspondencias.create');
    Route::post('/', [App\Http\Controllers\CorrespondenciaController::class, 'store'])->middleware('can:crear correspondencias')->name('correspondencias.store');
    Route::get('/{correspondencia}', [App\Http\Controllers\CorrespondenciaController::class, 'show'])->middleware('can:ver correspondencias')->name('correspondencias.show');
    Route::get('/{correspondencia}/edit', [App\Http\Controllers\CorrespondenciaController::class, 'edit'])->middleware('can:editar correspondencias')->name('correspondencias.edit');
    Route::put('/{correspondencia}', [App\Http\Controllers\CorrespondenciaController::class, 'update'])->middleware('can:editar correspondencias')->name('correspondencias.update');
    Route::delete('/{correspondencia}', [App\Http\Controllers\CorrespondenciaController::class, 'destroy'])->middleware('can:eliminar correspondencias')->name('correspondencias.destroy');
});

// Ruta para cambiar contraseña
Route::middleware(['auth'])->group(function () {
    Route::get('/settings/change-password', [App\Http\Controllers\PasswordController::class, 'form'])->name('password.change.form');
    Route::post('/settings/change-password', [App\Http\Controllers\PasswordController::class, 'update'])->name('password.change.update');
});


// Rutas de Configuraciones Generales
Route::prefix('settings')->middleware('auth', 'password.changed', 'can:ver configuraciones')->group(function () {
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
        Route::get('/{role}', [App\Http\Controllers\RoleController::class, 'show'])->middleware('can:ver roles')->name('roles.show');
        Route::get('/{role}/edit', [App\Http\Controllers\RoleController::class, 'edit'])->middleware('can:editar roles')->name('roles.edit');
        Route::put('/{role}', [App\Http\Controllers\RoleController::class, 'update'])->middleware('can:editar roles')->name('roles.update');
        Route::delete('/{role}', [App\Http\Controllers\RoleController::class, 'destroy'])->middleware('can:eliminar roles')->name('roles.destroy');
        Route::get('/{role}/permissions', [App\Http\Controllers\RoleController::class, 'permissions'])->middleware('can:editar roles')->name('roles.permissions');
        Route::post('/{role}/permissions', [App\Http\Controllers\RoleController::class, 'assignPermissions'])->middleware('can:editar roles')->name('roles.assignPermissions');
    });

    // Gestión de Secciones (solo para administración)
    Route::prefix('secciones')->middleware('can:ver secciones')->group(function () {
        Route::get('/', [App\Http\Controllers\SeccionController::class, 'index'])->name('secciones.index');
        Route::get('/create', [App\Http\Controllers\SeccionController::class, 'create'])->middleware('can:crear secciones')->name('secciones.create');
        Route::post('/', [App\Http\Controllers\SeccionController::class, 'store'])->middleware('can:crear secciones')->name('secciones.store');
        Route::get('/{seccion}/edit', [App\Http\Controllers\SeccionController::class, 'edit'])->middleware('can:editar secciones')->name('secciones.edit');
        Route::put('/{seccion}', [App\Http\Controllers\SeccionController::class, 'update'])->middleware('can:editar secciones')->name('secciones.update');
        Route::delete('/{seccion}', [App\Http\Controllers\SeccionController::class, 'destroy'])->middleware('can:eliminar secciones')->name('secciones.destroy');
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
        Route::post('/{carpeta}/upload', [App\Http\Controllers\CarpetaController::class, 'upload'])->middleware('can:subir archivos')->name('carpetas.upload');
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

    // Comunicados
    Route::prefix('comunicados')->middleware('can:ver comunicados')->group(function () {
        Route::get('/', [App\Http\Controllers\ComunicadoController::class, 'index'])->name('comunicados.index');
        Route::get('/create', [App\Http\Controllers\ComunicadoController::class, 'create'])->middleware('can:crear comunicados')->name('comunicados.create');
        Route::post('/', [App\Http\Controllers\ComunicadoController::class, 'store'])->middleware('can:crear comunicados')->name('comunicados.store');
        Route::get('/{comunicado}', [App\Http\Controllers\ComunicadoController::class, 'show'])->middleware('can:ver comunicados')->name('comunicados.show');
        Route::get('/{comunicado}/edit', [App\Http\Controllers\ComunicadoController::class, 'edit'])->middleware('can:editar comunicados')->name('comunicados.edit');
        Route::put('/{comunicado}', [App\Http\Controllers\ComunicadoController::class, 'update'])->middleware('can:editar comunicados')->name('comunicados.update');
        Route::delete('/{comunicado}', [App\Http\Controllers\ComunicadoController::class, 'destroy'])->middleware('can:eliminar comunicados')->name('comunicados.destroy');
    });

    // Submodulos
    Route::prefix('submodulos')->middleware('can:ver submodulos')->group(function () {
        Route::get('/', [App\Http\Controllers\SubmoduloController::class, 'index'])->name('submodulos.index');
        Route::get('/create', [App\Http\Controllers\SubmoduloController::class, 'create'])->middleware('can:crear submodulos')->name('submodulos.create');
        Route::post('/', [App\Http\Controllers\SubmoduloController::class, 'store'])->middleware('can:crear submodulos')->name('submodulos.store');
        Route::get('/{submodulo}', [App\Http\Controllers\SubmoduloController::class, 'show'])->middleware('can:ver submodulos')->name('submodulos.show');
        Route::get('/{submodulo}/edit', [App\Http\Controllers\SubmoduloController::class, 'edit'])->middleware('can:editar submodulos')->name('submodulos.edit');
        Route::put('/{submodulo}', [App\Http\Controllers\SubmoduloController::class, 'update'])->middleware('can:editar submodulos')->name('submodulos.update');
        Route::delete('/{submodulo}', [App\Http\Controllers\SubmoduloController::class, 'destroy'])->middleware('can:eliminar submodulos')->name('submodulos.destroy');
    });

    // Archivos
    Route::prefix('archivos')->middleware('can:ver archivos')->group(function () {
        Route::get('/', [App\Http\Controllers\ArchivoController::class, 'index'])->name('archivos.index');
        Route::get('/create', [App\Http\Controllers\ArchivoController::class, 'create'])->middleware('can:crear archivos')->name('archivos.create');
        Route::post('/', [App\Http\Controllers\ArchivoController::class, 'store'])->middleware('can:crear archivos')->name('archivos.store');
        Route::get('/{archivo}', [App\Http\Controllers\ArchivoController::class, 'show'])->middleware('can:ver archivos')->name('archivos.show');
        Route::get('/{archivo}/edit', [App\Http\Controllers\ArchivoController::class, 'edit'])->middleware('can:editar archivos')->name('archivos.edit');
        Route::put('/{archivo}', [App\Http\Controllers\ArchivoController::class, 'update'])->middleware('can:editar archivos')->name('archivos.update');
        Route::delete('/{archivo}', [App\Http\Controllers\ArchivoController::class, 'destroy'])->middleware('can:eliminar archivos')->name('archivos.destroy');
    });

    // Documentos por Profesor
    Route::prefix('documentos-profesores')->middleware('can:ver documentos profesores')->group(function () {
        // Lista de todos los profesores
        Route::get('/', [App\Http\Controllers\ProfesorDocumentoController::class, 'index'])->name('documentos-profesores.index');

        // Ver documentos de un profesor específico
        Route::get('/{user}', [App\Http\Controllers\ProfesorDocumentoController::class, 'show'])->name('documentos-profesores.show');
    });

});
