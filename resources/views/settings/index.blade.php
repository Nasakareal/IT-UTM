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
                <div class="card-header text-white" style="background-color: #FF9800;">
                    <h5 class="mb-0"><i class="fa-solid fa-user me-2"></i> Usuarios</h5>
                </div>
                <div class="card-body text-center">
                    <span class="badge bg-secondary">Administración</span>
                    <p class="card-text mt-2">Gestiona los usuarios del sistema</p>
                    <a href="{{ url('settings/users') }}" class="btn btn-primary btn-sm">Acceder</a>
                </div>
            </div>
        </div>

        <!-- ROLES -->
        <div class="col-md-4">
            <div class="card mb-3 shadow border-0">
                <div class="card-header text-white" style="background-color: #001f3f;">
                    <h5 class="mb-0"><i class="fa-regular fa-flag me-2"></i> Roles</h5>
                </div>
                <div class="card-body text-center">
                    <span class="badge bg-secondary">Permisos</span>
                    <p class="card-text mt-2">Asigna roles y permisos a los usuarios</p>
                    <a href="{{ url('settings/roles') }}" class="btn btn-primary btn-sm">Acceder</a>
                </div>
            </div>
        </div>

        <!-- SECCIONES -->
        <div class="col-md-4">
            <div class="card mb-3 shadow border-0">
                <div class="card-header text-white" style="background-color: #FDB7EA;">
                    <h5 class="mb-0"><i class="bi bi-layout-text-window"></i> Secciones</h5>
                </div>
                <div class="card-body text-center">
                    <span class="badge bg-secondary">Secciones</span>
                    <p class="card-text mt-2">Asignar Secciones de inicio</p>
                    <a href="{{ url('settings/secciones') }}" class="btn btn-primary btn-sm">Acceder</a>
                </div>
            </div>
        </div>

        <!-- MÓDULOS -->
        <div class="col-md-4">
            <div class="card mb-3 shadow border-0">
                <div class="card-header text-white" style="background-color: #B2A5FF;">
                    <h5 class="mb-0"><i class="bi bi-grid-1x2"></i> Módulos</h5>
                </div>
                <div class="card-body text-center">
                    <span class="badge bg-secondary">Módulos</span>
                    <p class="card-text mt-2">Asignar Módulos de inicio</p>
                    <a href="{{ url('settings/modulos') }}" class="btn btn-primary btn-sm">Acceder</a>
                </div>
            </div>
        </div>

        <!-- CARPETAS -->
        <div class="col-md-4">
            <div class="card mb-3 shadow border-0">
                <div class="card-header text-white" style="background-color: #E5D0AC;">
                    <h5 class="mb-0"><i class="bi bi-folder2-open"></i> Carpetas</h5>
                </div>
                <div class="card-body text-center">
                    <span class="badge bg-secondary">Secciones</span>
                    <p class="card-text mt-2">Asignar Carpetas para los archivos con archivos</p>
                    <a href="{{ url('settings/carpetas') }}" class="btn btn-primary btn-sm">Acceder</a>
                </div>
            </div>
        </div>

        <!-- ARCHIVOS -->
        <div class="col-md-4">
            <div class="card mb-3 shadow border-0">
                <div class="card-header text-white" style="background-color: #ECEBDE;">
                    <h5 class="mb-0"><i class="bi bi-file-earmark-text"></i> Archivos</h5>
                </div>
                <div class="card-body text-center">
                    <span class="badge bg-secondary">Archivos</span>
                    <p class="card-text mt-2">Ver todos los archivos dentro de carpetas</p>
                    <a href="{{ url('settings/archivos') }}" class="btn btn-primary btn-sm">Acceder</a>
                </div>
            </div>
        </div>

        <!-- SUBSECCIONES -->
        <div class="col-md-4">
            <div class="card mb-3 shadow border-0">
                <div class="card-header text-white" style="background-color: #D0DDD0;">
                    <h5 class="mb-0"><i class="bi bi-folder2-open"></i> Subsecciones</h5>
                </div>
                <div class="card-body text-center">
                    <span class="badge bg-secondary">Subsecciones</span>
                    <p class="card-text mt-2">Asignar Subsecciones para las carpetas de Archivos</p>
                    <a href="{{ url('settings/subsections') }}" class="btn btn-primary btn-sm">Acceder</a>
                </div>
            </div>
        </div>

        <!-- COMUNICADOS -->
        <div class="col-md-4">
            <div class="card mb-3 shadow border-0">
                <div class="card-header text-white" style="background-color: #F0A04B;">
                    <h5 class="mb-0"><i class="bi bi-folder2-open"></i> Comunicados</h5>
                </div>
                <div class="card-body text-center">
                    <span class="badge bg-secondary">Comunicados</span>
                    <p class="card-text mt-2">Listado de Comunicados para la vista General</p>
                    <a href="{{ url('settings/comunicados') }}" class="btn btn-primary btn-sm">Acceder</a>
                </div>
            </div>
        </div>

        <!-- SUBMODULOS -->
        <div class="col-md-4">
            <div class="card mb-3 shadow border-0">
                <div class="card-header text-white" style="background-color: #624E88;">
                    <h5 class="mb-0"><i class="bi bi-columns"></i> Submodulos</h5>
                </div>
                <div class="card-body text-center">
                    <span class="badge bg-secondary">Submodulos</span>
                    <p class="card-text mt-2">Listado de Submodulos, donde los profesores suben documentos</p>
                    <a href="{{ url('settings/submodulos') }}" class="btn btn-primary btn-sm">Acceder</a>
                </div>
            </div>
        </div>

        <!-- DOCUMENTOS ACADÉMICOS -->
        <div class="col-md-4">
            <div class="card mb-3 shadow border-0">
                <div class="card-header text-white" style="background-color: #3F51B5;">
                    <h5 class="mb-0"><i class="fas fa-folder-open me-2"></i> Documentos Académicos</h5>
                </div>
                <div class="card-body text-center">
                    <span class="badge bg-secondary">Profesores</span>
                    <p class="card-text mt-2">Ver los documentos subidos por cada profesor</p>
                    <a href="{{ url('settings/documentos-profesores') }}" class="btn btn-primary btn-sm">Acceder</a>
                </div>
            </div>
        </div>

        <!-- SNAPSHOT CARGA HORARIA (SweetAlert2) -->
        <div class="col-md-4">
          <div class="card mb-3 shadow border-0">
            <div class="card-header text-white" style="background-color:#28a745;">
              <h5 class="mb-0"><i class="fa-solid fa-camera me-2"></i> Snapshot Carga Horaria</h5>
            </div>
            <div class="card-body text-center">
              <span class="badge bg-secondary">Mantenimiento</span>
              <p class="card-text mt-2">Toma una “foto” de profesor–materia–grupo–programa–unidades desde <em>cargahoraria</em> al sistema.</p>

              @can('ver configuraciones')
              <button id="btnOpenSnapshot" class="btn btn-success btn-sm" type="button">
                Ejecutar snapshot
              </button>
              @endcan

              @if(session('success'))
                <div class="alert alert-success mt-3 mb-0" role="alert">{{ session('success') }}</div>
              @endif
              @if(session('error'))
                <div class="alert alert-danger mt-3 mb-0" role="alert">{{ session('error') }}</div>
              @endif
            </div>
          </div>
        </div>

           <!-- CALIFICACIONES -->
            <div class="col-md-4">
                <div class="card mb-3 shadow border-0">
                    <div class="card-header text-white" style="background-color: #3F51B5;">
                        <h5 class="mb-0"><i class="fas fa-folder-open me-2"></i> Documentos Académicos</h5>
                    </div>
                    <div class="card-body text-center">
                        <span class="badge bg-secondary">Calificaciones</span>
                        <p class="card-text mt-2">Ver las calificaciones por cada profesor</p>
                        <a href="{{ url('settings/calificaciones') }}" class="btn btn-primary btn-sm">Acceder</a>
                    </div>
                </div>
            </div>

    </div>
</div>

<!-- FORM oculto para enviar al backend -->
<form id="snapshotFormHidden" method="POST" action="{{ route('admin.snapshot.cargahoraria') }}" class="d-none">
  @csrf
  <input type="hidden" name="cuatrimestre_id" id="h_cuatrimestre_id">
  <input type="hidden" name="quarter_name"     id="h_quarter_name">
</form>
@stop

{{-- OJO: tu layout llama @yield('styles'), no 'css' --}}
@section('styles')
<style>
  .card-header h5 { margin: 0; }
  .badge.bg-secondary { font-size: 0.9rem; }

  /* SweetAlert2 siempre por encima de navbar/overlays */
  .swal2-container{ z-index: 2147483647 !important; }
</style>
@stop

{{-- OJO: tu layout llama @yield('scripts'), no 'js' --}}
@section('scripts')
<script>
(function(){
  function init(){
    const btn   = document.getElementById('btnOpenSnapshot');
    const form  = document.getElementById('snapshotFormHidden');
    const hCid  = document.getElementById('h_cuatrimestre_id');
    const hQn   = document.getElementById('h_quarter_name');
    if (!btn || !form || !hCid || !hQn) return;

    btn.addEventListener('click', async function(){
      const { value: values, isConfirmed } = await Swal.fire({
        title: 'Confirmar Snapshot',
        html: `
          <p class="mb-2">
            Esto copiará las materias actuales desde <b>cargahoraria</b> a la tabla local de snapshots.
          </p>

          <a href="#" id="sw-adv-toggle" class="small">Opciones avanzadas (cuatrimestre)</a>
          <div id="sw-adv" style="display:none;text-align:left;margin-top:.75rem;">
            <label class="form-label">Cuatrimestre ID (opcional)</label>
            <input id="sw_cuatri" type="number" class="swal2-input" placeholder="Ej. 3" style="width:100%;margin:0 0 .5rem 0;">
            <label class="form-label">Nombre de cuatrimestre (opcional)</label>
            <input id="sw_quarter" type="text" class="swal2-input" placeholder="Ej. Mayo - Agosto 2025" style="width:100%;margin:0;">
          </div>

          <div class="alert alert-warning mt-3 mb-0" style="text-align:left;">
            <i class="fa-solid fa-triangle-exclamation me-1"></i>
            Asegúrate de que la conexión <code>cargahoraria</code> esté disponible.
          </div>
        `,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ejecutar ahora',
        cancelButtonText: 'Cancelar',
        focusConfirm: false,
        didOpen: () => {
          const t  = document.getElementById('sw-adv-toggle');
          const bx = document.getElementById('sw-adv');
          if (t && bx) t.addEventListener('click', (e)=>{ e.preventDefault(); bx.style.display = bx.style.display==='none'?'block':'none'; });
        },
        preConfirm: () => {
          const cuatri  = (document.getElementById('sw_cuatri')  || {}).value || '';
          const quarter = (document.getElementById('sw_quarter') || {}).value || '';
          return { cuatri, quarter };
        }
      });

      if (!isConfirmed) return;

      Swal.fire({
        title: 'Ejecutando snapshot…',
        html: 'Por favor espera…',
        allowOutsideClick: false,
        didOpen: () => Swal.showLoading()
      });

      try {
        hCid.value = values.cuatri || '';
        hQn.value  = values.quarter || '';
        form.submit();
      } catch (e) {
        Swal.fire('Error', 'No se pudo enviar la solicitud.', 'error');
      }
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init, { once:true });
  } else {
    init();
  }
})();
</script>
@stop
