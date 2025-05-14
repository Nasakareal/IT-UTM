@extends('layouts.app')

@section('title', 'TI-UTM - Gestión Académica')

@section('content_header')
    <h1>Gestión Académica - Documentos Obligatorios</h1>
@stop

@section('content')
    <div class="mb-4">
        <h2 class="p-2 mb-3 text-white text-center"
            style="background-color: #1976d2; border-radius: 8px;">
            DOCUMENTOS POR UNIDAD
        </h2>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <table class="table table-bordered table-hover table-sm align-middle">
                <thead class="table-primary text-center">
                    <tr>
                        <th>Materia</th>
                        <th>Unidad</th>
                        <th>Documento</th>
                        <th>Descargar</th>
                        <th>Estatus</th>
                        <th>Subir</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($documentos as $doc)
                        <tr>
                            <td>{{ $doc['materia'] }}</td>
                            <td class="text-center">{{ $doc['unidad'] }}</td>
                            <td>{{ $doc['documento'] }}</td>
                            <td class="text-center">
                                <a href="{{ asset('formatos_academicos/' . $doc['archivo']) }}"
                                   class="btn btn-sm btn-outline-success" download>
                                   <i class="fas fa-download"></i>
                                </a>
                            </td>
                            <td class="text-center">
                                @if($doc['entregado'])
                                    <span class="badge bg-success"><i class="fas fa-check"></i> Entregado</span><br>
                                    @if(isset($doc['archivo_subido']))
                                        <a href="{{ asset('storage/' . $doc['archivo_subido']) }}"
                                           class="btn btn-sm btn-link mt-1" target="_blank">
                                            <i class="fas fa-file-alt"></i> Ver archivo
                                        </a>
                                    @endif
                                @else
                                    <span class="badge bg-danger"><i class="fas fa-times"></i> Pendiente</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if(!$doc['entregado'])
                                    <form action="{{ route('documentos.subir') }}" method="POST" enctype="multipart/form-data">
                                        @csrf
                                        <input type="hidden" name="materia" value="{{ $doc['materia'] }}">
                                        <input type="hidden" name="unidad" value="{{ $doc['unidad'] }}">
                                        <input type="hidden" name="tipo_documento" value="{{ $doc['documento'] }}">

                                        <div class="d-flex align-items-center gap-2">
                                            <input type="file" name="archivo" accept=".pdf,.docx,.xlsx"
                                                   class="form-control form-control-sm" required>
                                            <button type="submit" class="btn btn-sm btn-primary">
                                                <i class="fas fa-upload"></i>
                                            </button>
                                        </div>
                                    </form>
                                @else
                                    <i class="fas fa-lock text-muted"></i>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@stop
