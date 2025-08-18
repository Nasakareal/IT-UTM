@extends('layouts.app')

@section('title', 'TI-UTM - Gestión Académica')

@section('content_header')
    <h1>Gestión Académica - Documentos Obligatorios</h1>
@stop

@section('content')
@if($errors->any())
  <div class="alert alert-danger">{{ $errors->first() }}</div>
@endif
@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

@php
    use Illuminate\Support\Str;
@endphp

<style>
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

    .modal-backdrop { z-index: 1050 !important; }
    .modal { z-index: 1060 !important; }

    .modal-header.d-flex { padding-bottom: 0; }
    .modal-header.d-flex .modal-title {
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        flex-grow: 1; margin-right: .75rem;
    }
    .modal-body { max-height: 75vh; overflow-y: auto; }
    .docs-unidad { padding: 1rem; border: 1px solid #ccc; border-radius: 8px; background: #fff; margin-bottom: 1rem; }

    .folder-card h5 { font-size: 1rem; font-weight: 600; text-align: center; margin-bottom: 0.25rem; }

    /* Bloque Firmar Todo */
    .batch-sign {
        background: #f1f8ff; border: 1px dashed #69a7ff; border-radius: 8px; padding: .75rem 1rem; margin-top: .75rem;
    }
    .batch-sign .status { font-size: .9rem; }
    .batch-sign .missing { color: #cc0000; font-size: .85rem; margin-top: .25rem; }
</style>

    <div class="row">
        @foreach(collect($documentos)->groupBy(fn($d) => $d['materia'].'|'.$d['grupo']) as $key => $docs)
            @php
                [$materia, $grupo] = explode('|', $key);
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
            [$materia, $grupo] = explode('|', $key);
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

                        <select id="unidad_select_{{ $slug }}" class="custom-select custom-select-sm w-auto mx-3">
                            @foreach($unidades as $u)
                                <option value="{{ $u }}" @if($u==$default) selected @endif>Unidad {{ $u }}</option>
                            @endforeach
                        </select>

                        <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>

                    <div class="modal-body">
                        @foreach($docs->groupBy('unidad') as $u => $docsUnidad)
                            @php
                                $totalUnidad   = $docsUnidad->count();
                                $entregados    = $docsUnidad->where('entregado', true)->count();
                                $faltantes     = $docsUnidad->filter(fn($d)=>!$d['entregado'])->pluck('documento')->values();
                                $completa      = $entregados === $totalUnidad && $totalUnidad > 0;
                                $batchId       = $slug.'_'.$u;
                            @endphp

                            <div class="docs-unidad docs-unidad-{{ $u }}" @if($u!=$default) style="display:none" @endif>

                                {{-- ================= LISTA DE DOCUMENTOS (SOLO SUBIR) ================ --}}
                                <div class="list-group">
                                    @foreach($docsUnidad as $doc)
                                        <div class="list-group-item">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <strong>{{ $doc['documento'] }}</strong>
                                                <div class="d-flex align-items-center">
                                                    @if(!$doc['entregado'] && $doc['archivo'])
                                                        <a href="{{ asset('formatos_academicos/'.$doc['archivo']) }}" class="btn btn-sm btn-outline-success">
                                                            <i class="fas fa-download"></i> Plantilla
                                                        </a>
                                                    @endif

                                                    @if($doc['entregado'] && $doc['archivo_subido'])
                                                        <a href="{{ asset('storage/'.$doc['archivo_subido']) }}" class="btn btn-sm btn-outline-primary ml-2" target="_blank">
                                                            <i class="fas fa-file-alt"></i> Ver Archivo
                                                        </a>
                                                    @endif

                                                    @if($doc['acuse'])
                                                        <a href="{{ asset('storage/'.$doc['acuse']) }}" class="btn btn-sm btn-outline-secondary ml-2" target="_blank">
                                                            <i class="fa fa-file-pdf"></i> Acuse
                                                        </a>
                                                    @endif

                                                    @if($doc['entregado'] && (!isset($doc['editable']) || !$doc['editable']))
                                                        <i class="fas fa-lock text-danger ml-2" title="Ya no se puede editar este documento"></i>
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

                                                    <div class="col-md-8">
                                                        <label class="input-label" for="archivo_{{ $slug }}_{{ $u }}_{{ Str::slug($doc['documento']) }}">Archivo (PDF/DOC/XLS)</label>
                                                        <input type="file" id="archivo_{{ $slug }}_{{ $u }}_{{ Str::slug($doc['documento']) }}" name="archivo" accept=".pdf,.doc,.docx,.xls,.xlsx"
                                                               class="form-control form-control-sm @error('archivo') is-invalid @enderror" required>
                                                        @error('archivo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                                    </div>

                                                    <div class="col-md-4 text-right">
                                                        {{-- SOLO SUBIR --}}
                                                        <button type="submit"
                                                                name="action"
                                                                value="upload_only"
                                                                class="btn btn-sm btn-secondary"
                                                                formnovalidate>
                                                            <i class="fas fa-upload"></i> Solo Subir
                                                        </button>
                                                    </div>
                                                </form>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                                {{-- ================= FIN LISTA DOCUMENTOS ================= --}}

                                {{-- =================== FIRMAR TODO (AL FINAL) =================== --}}
                                <div class="batch-sign">
                                    <div class="status">
                                        <strong>Unidad {{ $u }}:</strong>
                                        {{ $entregados }} / {{ $totalUnidad }} documentos cargados.
                                        @if(!$completa)
                                            <div class="missing">
                                                Faltan por subir: {{ $faltantes->implode(', ') }}
                                            </div>
                                        @endif
                                    </div>

                                    <form action="{{ route('documentos.firmarLote') }}" method="POST" class="row align-items-end mt-2">
                                        @csrf
                                        <input type="hidden" name="materia" value="{{ $materia }}">
                                        <input type="hidden" name="grupo"   value="{{ $grupo }}">
                                        <input type="hidden" name="unidad"  value="{{ $u }}">

                                        {{-- Tipos requeridos = todos los tipos mostrados para esta unidad --}}
                                        @foreach($docsUnidad as $docu)
                                            <input type="hidden" name="tipos_requeridos[]" value="{{ $docu['documento'] }}">
                                        @endforeach

                                        <div class="col-md-5">
                                            <label class="input-label">Certificado (.p12)</label>
                                            <input type="file"
                                                   id="certFileBatch_{{ $slug }}_{{ $u }}"
                                                   accept=".p12"
                                                   class="form-control form-control-sm"
                                                   @if($completa) required @endif>
                                            <input type="hidden" name="firma_sat" id="firma_sat_batch_{{ $slug }}_{{ $u }}">
                                            @error('firma_sat')<div class="text-danger">{{ $message }}</div>@enderror
                                        </div>

                                        <div class="col-md-4">
                                            <label class="input-label" for="efirma_pass_batch_{{ $slug }}_{{ $u }}">Contraseña e.firma</label>
                                            <input type="password"
                                                   id="efirma_pass_batch_{{ $slug }}_{{ $u }}"
                                                   name="efirma_pass"
                                                   class="form-control form-control-sm"
                                                   @if($completa) required @endif>
                                            @error('efirma_pass')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                        </div>

                                        <div class="col-md-3 text-right">
                                            <button type="submit" class="btn btn-sm btn-primary" @if(!$completa) disabled @endif>
                                                <i class="fas fa-file-signature"></i> Firmar Todo
                                            </button>
                                        </div>
                                    </form>
                                </div>
                                {{-- ================= FIN FIRMAR TODO ================= --}}

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
    // Mover modales al body
    document.querySelectorAll('.modal').forEach(function(modal){
        document.body.appendChild(modal);
    });

    // Cambiar unidad visible en modal
    document.querySelectorAll('[id^="unidad_select_"]').forEach(function(sel){
        sel.addEventListener('change', function(){
            var val = this.value;
            var modalContent = this.closest('.modal-content');
            modalContent.querySelector('.unidad-display').textContent = val;
            modalContent.querySelectorAll('.docs-unidad').forEach(function(div){
                div.style.display = div.classList.contains('docs-unidad-' + val) ? 'block' : 'none';
            });
        });
    });

    // Leer .p12 del bloque "Firmar Todo" y guardar Base64 en su hidden
    document.querySelectorAll('[id^="certFileBatch_"]').forEach(function(input){
        input.addEventListener('change', function(){
            var file = this.files[0];
            if (!file) return;
            var reader = new FileReader();
            reader.onload = function(evt){
                var base64 = evt.target.result.split(',')[1];
                var hidden = document.getElementById(this._targetHiddenId);
                if (hidden) hidden.value = base64;
            }.bind({ _targetHiddenId: this.id.replace('certFileBatch_', 'firma_sat_batch_') });
            reader.readAsDataURL(file);
        });
    });
});
</script>
@endpush
