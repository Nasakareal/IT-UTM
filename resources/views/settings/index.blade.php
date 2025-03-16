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
