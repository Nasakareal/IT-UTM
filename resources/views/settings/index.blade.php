@extends('layouts.app')

@section('title', 'TI-UTM - Configuraciones')

@section('content_header')
    <h1>Configuraciones del Sistema</h1>
@stop

@section('content')
<div class="container my-4">
    <div class="row">

        <!-- USUARIOS -->
        <div class="col-md-4">
            <div class="card mb-3 shadow border-0">
                <!-- Cabecera con color e ícono -->
                <div class="card-header text-white" style="background-color: #FF9800;">
                    <h5 class="mb-0">
                        <i class="fa-solid fa-user me-2"></i> Usuarios
                    </h5>
                </div>
                <div class="card-body text-center">
                    <!-- Etiqueta secundaria -->
                    <span class="badge bg-secondary">Administración</span>
                    <!-- Texto descriptivo -->
                    <p class="card-text mt-2">Gestiona los usuarios del sistema</p>
                    <!-- Botón de acción -->
                    <a href="{{ url('settings/users') }}" class="btn btn-primary btn-sm">
                        Acceder
                    </a>
                </div>
            </div>
        </div>

        <!-- ROLES -->
        <div class="col-md-4">
            <div class="card mb-3 shadow border-0">
                <div class="card-header text-white" style="background-color: #001f3f;">
                    <h5 class="mb-0">
                        <i class="fa-regular fa-flag me-2"></i> Roles
                    </h5>
                </div>
                <div class="card-body text-center">
                    <span class="badge bg-secondary">Permisos</span>
                    <p class="card-text mt-2">Asigna roles y permisos a los usuarios</p>
                    <a href="{{ url('settings/roles') }}" class="btn btn-primary btn-sm">
                        Acceder
                    </a>
                </div>
            </div>
        </div>

        <!-- SECCIONES -->
        <div class="col-md-4">
            <div class="card mb-3 shadow border-0">
                <div class="card-header text-white" style="background-color: #FDB7EA;">
                    <h5 class="mb-0">
                        <i class="bi bi-layout-text-window"></i> Secciones
                    </h5>
                </div>
                <div class="card-body text-center">
                    <span class="badge bg-secondary">Secciones</span>
                    <p class="card-text mt-2">Asignar Secciones de inicio</p>
                    <a href="{{ url('settings/secciones') }}" class="btn btn-primary btn-sm">
                        Acceder
                    </a>
                </div>
            </div>
        </div>

        <!-- MÓDULOS -->
        <div class="col-md-4">
            <div class="card mb-3 shadow border-0">
                <div class="card-header text-white" style="background-color: #B2A5FF;">
                    <h5 class="mb-0">
                        <i class="bi bi-grid-1x2"></i> Módulos
                    </h5>
                </div>
                <div class="card-body text-center">
                    <span class="badge bg-secondary">Módulos</span>
                    <p class="card-text mt-2">Asignar Módulos de inicio</p>
                    <a href="{{ url('settings/modulos') }}" class="btn btn-primary btn-sm">
                        Acceder
                    </a>
                </div>
            </div>
        </div>

        <!-- CARPETAS -->
        <div class="col-md-4">
            <div class="card mb-3 shadow border-0">
                <div class="card-header text-white" style="background-color: #E5D0AC;">
                    <h5 class="mb-0">
                        <i class="bi bi-folder2-open"></i> Carpetas
                    </h5>
                </div>
                <div class="card-body text-center">
                    <span class="badge bg-secondary">Secciones</span>
                    <p class="card-text mt-2">Asignar Carpetas para los archivos con archivos</p>
                    <a href="{{ url('settings/carpetas') }}" class="btn btn-primary btn-sm">
                        Acceder
                    </a>
                </div>
            </div>
        </div>

        <!-- ARCHIVOS -->
        <div class="col-md-4">
            <div class="card mb-3 shadow border-0">
                <div class="card-header text-white" style="background-color: #ECEBDE;">
                    <h5 class="mb-0">
                        <i class="bi bi-file-earmark-text"></i> Archivos
                    </h5>
                </div>
                <div class="card-body text-center">
                    <span class="badge bg-secondary">Archivos</span>
                    <p class="card-text mt-2">Ver todos los archivos dentro de carpetas</p>
                    <a href="{{ url('settings/archivos') }}" class="btn btn-primary btn-sm">
                        Acceder
                    </a>
                </div>
            </div>
        </div>

        <!-- SUBSECCIONES -->
        <div class="col-md-4">
            <div class="card mb-3 shadow border-0">
                <div class="card-header text-white" style="background-color: #D0DDD0;">
                    <h5 class="mb-0">
                        <i class="bi bi-folder2-open"></i> Subsecciones
                    </h5>
                </div>
                <div class="card-body text-center">
                    <span class="badge bg-secondary">Subsecciones</span>
                    <p class="card-text mt-2">Asignar Subsecciones para las carpetas de Archivos</p>
                    <a href="{{ url('settings/subsections') }}" class="btn btn-primary btn-sm">
                        Acceder
                    </a>
                </div>
            </div>
        </div>

        <!-- COMUNICADOS -->
        <div class="col-md-4">
            <div class="card mb-3 shadow border-0">
                <div class="card-header text-white" style="background-color: #F0A04B;">
                    <h5 class="mb-0">
                        <i class="bi bi-folder2-open"></i> Comunicados
                    </h5>
                </div>
                <div class="card-body text-center">
                    <span class="badge bg-secondary">Comunicados</span>
                    <p class="card-text mt-2">Listado de Comunicados para la vista General</p>
                    <a href="{{ url('settings/comunicados') }}" class="btn btn-primary btn-sm">
                        Acceder
                    </a>
                </div>
            </div>
        </div>

        <!-- SUBMODULOS -->
        <div class="col-md-4">
            <div class="card mb-3 shadow border-0">
                <div class="card-header text-white" style="background-color: #624E88;">
                    <h5 class="mb-0">
                        <i class="bi bi-columns"></i> Submodulos
                    </h5>
                </div>
                <div class="card-body text-center">
                    <span class="badge bg-secondary">Submodulos</span>
                    <p class="card-text mt-2">Listado de Submodulos, donde los profesores suben documentos</p>
                    <a href="{{ url('settings/submodulos') }}" class="btn btn-primary btn-sm">
                        Acceder
                    </a>
                </div>
            </div>
        </div>

        <!-- DOCUMENTOS ACADÉMICOS -->
        <div class="col-md-4">
            <div class="card mb-3 shadow border-0">
                <div class="card-header text-white" style="background-color: #3F51B5;">
                    <h5 class="mb-0">
                        <i class="fas fa-folder-open me-2"></i> Documentos Académicos
                    </h5>
                </div>
                <div class="card-body text-center">
                    <span class="badge bg-secondary">Profesores</span>
                    <p class="card-text mt-2">Ver los documentos subidos por cada profesor</p>
                    <a href="{{ url('settings/documentos-profesores') }}" class="btn btn-primary btn-sm">
                        Acceder
                    </a>
                </div>
            </div>
        </div>

        <!-- VACIAR BASE DE DATOS -->
        <div class="col-md-4">
            <div class="card mb-3 shadow border-0">
                <div class="card-header text-white" style="background-color: #dc3545;">
                    <h5 class="mb-0">
                        <i class="fa-solid fa-dumpster me-2"></i> Vaciar Base de Datos
                    </h5>
                </div>
                <div class="card-body text-center">
                    <span class="badge bg-secondary">Mantenimiento</span>
                    <p class="card-text mt-2">Elimina todos los registros de la base de datos</p>
                    <a href="{{ url('vaciados') }}" class="btn btn-primary btn-sm">
                        Acceder
                    </a>
                </div>
            </div>
        </div>

        <!-- REGISTRO DE ACTIVIDAD -->
        <div class="col-md-4">
            <div class="card mb-3 shadow border-0">
                <div class="card-header text-white" style="background-color: #6610f2;">
                    <h5 class="mb-0">
                        <i class="fa-solid fa-user-secret me-2"></i> Registro de Actividad
                    </h5>
                </div>
                <div class="card-body text-center">
                    <span class="badge bg-secondary">Auditoría</span>
                    <p class="card-text mt-2">Consulta el historial de acciones realizadas</p>
                    <a href="{{ url('settings/actividad') }}" class="btn btn-primary btn-sm">
                        Acceder
                    </a>
                </div>
            </div>
        </div>

    </div>
</div>
@stop

@section('css')
<style>
    /* Ajustes opcionales de estilo */
    .card-header h5 {
        margin: 0;
    }
    .badge.bg-secondary {
        font-size: 0.9rem;
    }
</style>
@stop

@section('js')
<script>
    console.log("Configuraciones del Sistema cargadas.");
</script>
@stop
