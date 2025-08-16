@extends('layouts.app')

@section('title', 'TI-UTM - Gestión Académica')

@section('content_header')
    <h1>Gestión Académica - Documentos Obligatorios</h1>
@stop

@section('content')
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

<style>
    /* Asegura que el navbar quede por debajo del modal */
    .navbar, .navbar-fixed-top { z-index: 1000 !important; }

    .folder-card {
        cursor: pointer; transition: transform .2s, box-shadow .2s;
        background: #f8f9fa; border-radius: .5rem; padding: 1rem;
        height: 180px; display: flex; flex-direction: column;
        align-items: center; justify-content: center;
    }
    .folder-card:hover { transform: scale(1.05); box-shadow: 0 8px 20px rgba(0,0,0,.1); }
    .folder-icon { font-size: 2rem; color: #ffc107; }
    .input-label { font-size: .85rem; font-weight: 500; }

    /* Asegura que el backdrop y el modal estén por encima de todo */
    .modal-backdrop { z-index: 1050 !important; }
    .modal { z-index: 1060 !important; }

    /* Ajustes internos del modal */
    .modal-header.d-flex { padding-bottom: 0; }
    .modal-header.d-flex .modal-title {
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        flex-grow: 1; margin-right: .75rem;
    }
    .modal-body { max-height: 75vh; overflow-y: auto; }
    .docs-unidad { padding: 1rem; border: 1px solid #ccc; border-radius: 8px; background: #fff; margin-bottom: 1rem; }

    .folder-card h5 { font-size: 1rem; font-weight: 600; text-align: center; margin-bottom: 0.25rem; }
</style>

    <div class="row">
        @foreach(collect($documentos)->groupBy(fn($d) => $d['materia'].'|'.$d['grupo']) as $key => $docs)
            @php
                list($materia, $grupo) = explode('|', $key);
                $slug     = Str::slug($materia.'-'.$grupo);
                $programa = $docs->first()['programa'];
            @endphp

            <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                <div class="folder-card" data-toggle="modal" data-target="#modal-{{ $slug }}">
                    <i class="fas fa-folder-open folder-icon"></i>
                    <h5 class="mt-2">{{ $materia }}</h5>
                    <small class="text-muted">{{ $programa }} <br> Grupo {{ $grupo }}</small>
                </div>
            </div>
        @endforeach
    </div>

    @foreach(collect($documentos)->groupBy(fn($d) => $d['materia'].'|'.$d['grupo']) as $key => $docs)
        @php
            list($materia, $grupo) = explode('|', $key);
            $slug     = Str::slug($materia.'-'.$grupo);
            $unidades = $docs->pluck('unidad')->unique()->sort()->values();
            $default  = $unidades->first();
        @endphp

        <div class="modal fade" id="modal-{{ $slug }}" tabindex="-1" role="dialog" aria-labelledby="label-{{ $slug }}" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
                <div class="modal-content">

                    <div class="modal-header d-flex align-items-center">
                        <h5 class="modal-title" id="label-{{ $slug }}">
                            {{ $materia }} — Grupo {{ $grupo }} (Unidad <span class="unidad-display">{{ $default }}</span>)
                        </h5>

                        {{-- En BS4 usa custom-select o form-control --}}
                        <select id="unidad_select_{{ $slug }}" class="custom-select custom-select-sm w-auto mx-3">
                            @foreach($unidades as $u)
                                <option value="{{ $u }}" @if($u==$default) selected @endif>Unidad {{ $u }}</option>
                            @endforeach
                        </select>

                        {{-- Botón cerrar en BS4 --}}
                        <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>

                    <div class="modal-body">
                        @foreach($docs->groupBy('unidad') as $u => $docsUnidad)
                            <div class="docs-unidad docs-unidad-{{ $u }}" @if($u!=$default) style="display:none" @endif>
                                <div class="list-group">
                                    @foreach($docsUnidad as $doc)
                                        <div class="list-group-item">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <strong>{{ $doc['documento'] }}</strong>
                                                <div class="d-flex align-items-center">
                                                    @if(!$doc['entregado'] && $doc['archivo'])
                                                        <a href="{{ asset('formatos_academicos/'.$doc['archivo']) }}" class="btn btn-sm btn-outline-success" download>
                                                            <i class="fas fa-download"></i> Descargar Plantilla
                                                        </a>
                                                    @endif

                                                    @if($doc['entregado'] && $doc['archivo_subido'])
                                                        <a href="{{ asset('storage/'.$doc['archivo_subido']) }}" class="btn btn-sm btn-outline-primary ml-2" target="_blank">
                                                            <i class="fas fa-file-alt"></i> Ver Archivo
                                                        </a>
                                                    @endif

                                                    @if($doc['acuse'])
                                                        <a href="{{ asset('storage/'.$doc['acuse']) }}" class="btn btn-sm btn-outline-secondary ml-2" target="_blank">
                                                            <i class="fa fa-file-pdf"></i> Ver Acuse
                                                        </a>
                                                    @endif

                                                    @if($doc['entregado'] && (!isset($doc['editable']) || !$doc['editable']))
                                                        <i class="fas fa-lock text-danger ml-2" title="Ya no se puede editar este documento (fuera del tiempo permitido)"></i>
                                                    @endif
                                                </div>
                                            </div>

                                            @if(!$doc['entregado'] || ($doc['entregado'] && $doc['editable']))
                                                <form action="{{ route('documentos.subir') }}" method="POST" enctype="multipart/form-data" class="row align-items-end">
                                                    @csrf
                                                    <input type="hidden" name="materia"        value="{{ $doc['materia'] }}">
                                                    <input type="hidden" name="grupo"          value="{{ $doc['grupo'] }}">
                                                    <input type="hidden" name="unidad"         value="{{ $u }}">
                                                    <input type="hidden" name="tipo_documento" value="{{ $doc['documento'] }}">

                                                    <div class="col-md-4">
                                                        <label class="input-label" for="archivo_{{ $slug }}_{{ $u }}">Archivo (PDF/DOC/XLS)</label>
                                                        <input type="file" id="archivo_{{ $slug }}_{{ $u }}" name="archivo" accept=".pdf,.doc,.docx,.xls,.xlsx"
                                                               class="form-control form-control-sm @error('archivo') is-invalid @enderror" required>
                                                        @error('archivo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                                    </div>
                                                    <div class="col-md-3">
                                                        <label class="input-label">Certificado (.p12)</label>
                                                        <input type="file" id="certFile_{{ $slug }}_{{ $u }}" accept=".p12" class="form-control form-control-sm" required>
                                                        <input type="hidden" name="firma_sat" id="firma_sat_{{ $slug }}_{{ $u }}">
                                                        @error('firma_sat')<div class="text-danger">{{ $message }}</div>@enderror
                                                    </div>
                                                    <div class="col-md-3">
                                                        <label class="input-label" for="efirma_pass_{{ $slug }}_{{ $u }}">Contraseña e.firma</label>
                                                        <input type="password" id="efirma_pass_{{ $slug }}_{{ $u }}" name="efirma_pass"
                                                               class="form-control form-control-sm @error('efirma_pass') is-invalid @enderror" required>
                                                        @error('efirma_pass')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                                    </div>
                                                    <div class="col-md-2 text-right">
                                                        <button type="submit" name="action" value="sign_upload" class="btn btn-sm btn-primary">
                                                            <i class="fas fa-upload"></i> Firmar y Subir
                                                        </button>
                                                        <button type="submit"
                                                                name="action"
                                                                value="upload_only"
                                                                class="btn btn-sm btn-secondary ml-2"
                                                                formnovalidate>
                                                            <i class="fas fa-upload"></i> Solo Subir
                                                        </button>
                                                    </div>
                                                </form>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="modal-footer">
                        <button class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                    </div>

                </div>
            </div>
        </div>
    @endforeach
@stop

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function(){
        // Mover todos los modales al body para evitar stacking contexts
        document.querySelectorAll('.modal').forEach(function(modal){
            document.body.appendChild(modal);
        });

        // Cambiar unidad visible
        document.querySelectorAll('[id^="unidad_select_"]').forEach(function(sel){
            sel.addEventListener('change', function(){
                var val = this.value;
                var modalContent = this.closest('.modal-content');
                modalContent.querySelector('.unidad-display').textContent = val;
                modalContent.querySelectorAll('.docs-unidad').forEach(function(div){
                    if (div.classList.contains('docs-unidad-' + val)) {
                        div.style.display = 'block';
                    } else {
                        div.style.display = 'none';
                    }
                });
            });
        });

        // Leer .p12 y guardar Base64
        document.querySelectorAll('[id^="certFile_"]').forEach(function(input){
            input.addEventListener('change', function(){
                var file = this.files[0];
                if (!file) return;
                var reader = new FileReader();
                reader.onload = function(evt){
                    input.nextElementSibling.value = evt.target.result.split(',')[1];
                };
                reader.readAsDataURL(file);
            });
        });
    });
</script>
@endpush
