@extends('layouts.app')

@section('title', 'TI-UTM - Gestión Académica')

@section('content_header')
    <h1>Gestión Académica - Documentos Obligatorios</h1>
@stop

@php
    use Illuminate\Support\Str;
@endphp

@section('content')
    {{-- Estilos específicos --}}
    <style>
        .folder-card {
            cursor: pointer;
            transition: transform .2s, box-shadow .2s;
            background: #f8f9fa;
            border-radius: .5rem;
            padding: 1rem;
            height: 180px;              /* Altura fija */
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }
        .folder-card:hover {
            transform: scale(1.05);
            box-shadow: 0 8px 20px rgba(0, 0, 0, .1);
        }
        .folder-icon {
            font-size: 4rem;
            color: #ffc107;
        }
    </style>

    <div class="row gx-4 gy-4">
        @foreach(collect($documentos)->groupBy('materia') as $materia => $docs)
            @php $slug = Str::slug($materia); @endphp
            <div class="col-lg-3 col-md-4 col-sm-6">
                <div class="folder-card" data-bs-toggle="modal" data-bs-target="#modal-{{ $slug }}">
                    <i class="fas fa-folder-open folder-icon"></i>
                    <h5 class="mt-2">{{ $materia }}</h5>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Modales por materia (sin backdrop) --}}
    @foreach(collect($documentos)->groupBy('materia') as $materia => $docs)
        @php 
            $slug   = Str::slug($materia);
            $unidad = $docs->first()['unidad'];
        @endphp
        <div class="modal fade" id="modal-{{ $slug }}" data-bs-backdrop="false" tabindex="-1" aria-labelledby="label-{{ $slug }}" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="label-{{ $slug }}">
                            {{ $materia }} — Unidad {{ $unidad }}
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="list-group">
                            @foreach($docs as $doc)
                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong>{{ $doc['documento'] }}</strong>
                                    </div>
                                    <div class="d-flex align-items-center gap-2">
                                        {{-- Descargar formato --}}
                                        <a href="{{ asset('formatos_academicos/' . $doc['archivo']) }}"
                                           class="btn btn-sm btn-outline-success"
                                           download>
                                            <i class="fas fa-download"></i>
                                        </a>
                                        {{-- Ver archivo subido --}}
                                        @if($doc['entregado'] && isset($doc['archivo_subido']))
                                            <a href="{{ asset('storage/' . $doc['archivo_subido']) }}"
                                               class="btn btn-sm btn-outline-primary"
                                               target="_blank">
                                                <i class="fas fa-file-alt"></i>
                                            </a>
                                        @endif
                                        {{-- Formulario de subida --}}
                                        @if(!$doc['entregado'])
                                            <form action="{{ route('documentos.subir') }}"
                                                  method="POST"
                                                  enctype="multipart/form-data"
                                                  class="d-flex align-items-center m-0">
                                                @csrf
                                                <input type="hidden" name="materia"        value="{{ $doc['materia'] }}">
                                                <input type="hidden" name="unidad"         value="{{ $doc['unidad'] }}">
                                                <input type="hidden" name="tipo_documento" value="{{ $doc['documento'] }}">
                                                <input type="file" name="archivo"
                                                       accept=".pdf,.docx,.xlsx"
                                                       class="form-control form-control-sm"
                                                       required>
                                                <button type="submit" class="btn btn-sm btn-primary ms-2">
                                                    <i class="fas fa-upload"></i>
                                                </button>
                                            </form>
                                        @else
                                            <i class="fas fa-lock text-muted" title="Ya entregado"></i>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button"
                                class="btn btn-secondary"
                                data-bs-dismiss="modal">
                            Cerrar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
@stop
