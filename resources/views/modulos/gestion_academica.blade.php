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
        .folder-card { cursor: pointer; transition: transform .2s, box-shadow .2s; background: #f8f9fa; border-radius: .5rem; padding: 1rem; height: 180px; display: flex; flex-direction: column; align-items: center; justify-content: center; }
        .folder-card:hover { transform: scale(1.05); box-shadow: 0 8px 20px rgba(0,0,0,.1); }
        .folder-icon { font-size: 4rem; color: #ffc107; }
        .input-label { font-size: .85rem; font-weight: 500; }
    </style>

    <div class="row gx-4 gy-4">
      @foreach(collect($documentos)->groupBy('materia') as $materia => $docs)
        @php $slug = \Illuminate\Support\Str::slug($materia); @endphp
        <div class="col-lg-3 col-md-4 col-sm-6">
          <div class="folder-card" data-bs-toggle="modal" data-bs-target="#modal-{{ $slug }}">
            <i class="fas fa-folder-open folder-icon"></i>
            <h5 class="mt-2">{{ $materia }}</h5>
          </div>
        </div>
      @endforeach
    </div>

    @foreach(collect($documentos)->groupBy('materia') as $materia => $docs)
      @php
        $slug   = \Illuminate\Support\Str::slug($materia);
        $unidad = $docs->first()['unidad'];
      @endphp
      <div class="modal fade" id="modal-{{ $slug }}" data-bs-backdrop="false" tabindex="-1" aria-labelledby="label-{{ $slug }}" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title" id="label-{{ $slug }}">{{ $materia }} — Unidad {{ $unidad }}</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
              <div class="list-group">
                @foreach($docs as $doc)
                  <div class="list-group-item">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                      <strong>{{ $doc['documento'] }}</strong>
                      <div class="d-flex gap-2">
                        <a href="{{ asset('formatos_academicos/' . $doc['archivo']) }}" class="btn btn-sm btn-outline-success" download>
                          <i class="fas fa-download"></i>
                        </a>
                        @if($doc['entregado'])
                          @if($doc['archivo_subido'])
                            <a href="{{ asset('storage/' . $doc['archivo_subido']) }}" class="btn btn-sm btn-outline-primary" target="_blank">
                              <i class="fas fa-file-alt"></i>
                            </a>
                          @endif
                          @if($doc['acuse'])
                            <a href="{{ asset('storage/' . $doc['acuse']) }}" class="btn btn-sm btn-outline-secondary" target="_blank">
                              <i class="fa fa-file-pdf"></i> Ver Acuse
                            </a>
                          @endif
                          <i class="fas fa-lock text-muted" title="Ya entregado"></i>
                        @endif
                      </div>
                    </div>

                    @unless($doc['entregado'])
                      <form action="{{ route('documentos.subir') }}" method="POST" enctype="multipart/form-data" class="row g-2 align-items-end">
                        @csrf
                        <input type="hidden" name="materia"        value="{{ $doc['materia'] }}">
                        <input type="hidden" name="unidad"         value="{{ $doc['unidad'] }}">
                        <input type="hidden" name="tipo_documento" value="{{ $doc['documento'] }}">

                        <div class="col-md-4">
                          <label class="input-label" for="archivo_{{ $slug }}">Archivo (PDF/DOC/XLS)</label>
                          <input type="file" id="archivo_{{ $slug }}" name="archivo" accept=".pdf,.doc,.docx,.xls,.xlsx"
                                 class="form-control form-control-sm @error('archivo') is-invalid @enderror" required>
                          @error('archivo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-3">
                          <label class="input-label">Certificado (.p12)</label>
                          <input type="file" id="certFile_{{ $slug }}" accept=".p12" class="form-control form-control-sm" required>
                          <input type="hidden" name="firma_sat" id="firma_sat_{{ $slug }}">
                          @error('firma_sat')<div class="text-danger">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-3">
                          <label class="input-label" for="efirma_pass_{{ $slug }}">Contraseña e.firma</label>
                          <input type="password" id="efirma_pass_{{ $slug }}" name="efirma_pass"
                                 class="form-control form-control-sm @error('efirma_pass') is-invalid @enderror" required>
                          @error('efirma_pass')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-2 text-end">
                          <button type="submit" class="btn btn-sm btn-primary w-100">
                            <i class="fas fa-upload"></i> Firmar y Subir
                          </button>
                        </div>
                      </form>
                    @endunless
                  </div>
                @endforeach
              </div>
            </div>
            <div class="modal-footer">
              <button class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
          </div>
        </div>
      </div>
    @endforeach

    <script>
      document.querySelectorAll('[id^="certFile_"]').forEach(input => {
        input.addEventListener('change', function() {
          const file = this.files[0];
          if (!file) return;
          const slug = this.id.replace('certFile_','');
          const reader = new FileReader();
          reader.onload = evt => {
            document.getElementById(`firma_sat_${slug}`)
                    .value = evt.target.result.split(',')[1];
          };
          reader.readAsDataURL(file);
        });
      });
    </script>
@stop
