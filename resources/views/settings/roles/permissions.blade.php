@extends('layouts.app')

@section('title', 'TI-UTM - Permisos Roles')

@section('content')
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card card-outline card-warning">
                <div class="card-header">
                    <h3 class="card-title">Asignar Permisos</h3>
                </div>
                <div class="card-body">
                    <form action="{{ route('roles.assignPermissions', $role->id) }}" method="POST">
                        @csrf
                        <table class="table table-striped table-bordered table-hover table-sm">
                            <thead>
                                <tr>
                                    <th><center>Número</center></th>
                                    <th><center>Descripción del Permiso</center></th>
                                    <th><center>Asignar</center></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($permissions as $index => $permission)
                                    <tr>
                                        <td style="text-align: center">{{ $index + 1 }}</td>
                                        <td>{{ $permission->name }}</td>
                                        <td style="text-align: center">
                                            <input type="checkbox" name="permissions[]" value="{{ $permission->id }}"
                                                {{ in_array($permission->id, $rolePermissions) ? 'checked' : '' }}>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        <div class="form-group mt-3 text-center">
                            <button type="submit" class="btn btn-primary">
                                <i class="fa-solid fa-check"></i> Guardar Permisos
                            </button>
                            <a href="{{ route('roles.index') }}" class="btn btn-secondary">
                                <i class="fa-solid fa-ban"></i> Cancelar
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@stop

@section('styles')
    <style>
        .form-group label {
            font-weight: bold;
        }
    </style>
@stop

@section('scripts')
    <script>
    $(document).ready(function(){
        @if ($errors->any())
            Swal.fire({
                icon: 'error',
                title: 'Error en el formulario',
                html: `
                    <ul style="text-align: left;">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                `,
                confirmButtonText: 'Aceptar'
            });
        @endif
    });
    </script>
@stop

