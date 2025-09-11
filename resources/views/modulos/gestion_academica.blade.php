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

{{-- Mostrar enlace directo al acuse general cuando regresa de firmar --}}
@if(session('firma_lote'))
  @php $fl = session('firma_lote'); @endphp
  <div class="alert alert-info d-flex justify-content-between align-items-center">
      <div>
          <strong>Lote #{{ $fl['lote_id'] ?? '—' }} firmado.</strong>
          @if(!empty($fl['materia'])) — {{ $fl['materia'] }} @endif
          @if(!empty($fl['grupo'])) / Grupo {{ $fl['grupo'] }} @endif
          @if(!empty($fl['unidad'])) / Unidad {{ $fl['unidad'] }} @endif
      </div>
      @if(!empty($fl['acuse_lote']))
        <a href="{{ asset('storage/'.$fl['acuse_lote']) }}" target="_blank" class="btn btn-sm btn-outline-primary">
            <i class="fa fa-file-pdf"></i> Ver acuse general
        </a>
      @endif
  </div>
@endif

@php
    use Illuminate\Support\Str;
    // Ventana de edición en minutos (por defecto 120 si no existe el config)
    $VENTANA_EDIT_MIN = config('academico.minutos_edicion', 120);
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

    /* Countdown badge */
    .badge-info.edit-countdown { font-weight: 600; }
    .badge-danger.edit-countdown { font-weight: 700; }
    .countdown-muted { font-size:.85rem; color:#6c757d; }
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
                                $acuseLote     = $docsUnidad->first()['acuse_lote'] ?? null;
                                $loteId        = $docsUnidad->first()['lote_id']     ?? null;

                                $batchId       = $slug.'_'.$u;
                            @endphp

                            <div class="docs-unidad docs-unidad-{{ $u }}" @if($u!=$default) style="display:none" @endif>

                                {{-- ================= LISTA DE DOCUMENTOS (SOLO SUBIR) ================ --}}
                                <div class="list-group">
                                    @foreach($docsUnidad as $doc)
                                        @php
                                            // Determinar deadline ISO si aplica (prioriza un cierre explícito si te llega en el arreglo)
                                            $deadlineIso = $doc['cierre_edicion_iso'] ?? null;
                                            if (!$deadlineIso && !empty($doc['entregado']) && (!isset($doc['editable']) || $doc['editable'])) {
                                                if (!empty($doc['created_at'])) {
                                                    $deadlineIso = \Carbon\Carbon::parse($doc['created_at'])
                                                                    ->addMinutes($VENTANA_EDIT_MIN)
                                                                    ->toIso8601String();
                                                }
                                            }
                                        @endphp
                                        <div class="list-group-item" @if($deadlineIso) data-lock-after="{{ $deadlineIso }}" @endif>
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <div class="d-flex align-items-center">
                                                    <strong>{{ $doc['documento'] }}</strong>

                                                    {{-- Badge de countdown si hay ventana vigente --}}
                                                    @if($deadlineIso)
                                                        <span class="badge badge-info ml-2 edit-countdown"
                                                              data-deadline="{{ $deadlineIso }}">
                                                            <i class="far fa-clock"></i>
                                                            <span class="t">--:--:--</span>
                                                        </span>
                                                        <span class="countdown-muted ml-2">(tiempo para editar)</span>
                                                    @endif

                                                    @if(!empty($doc['firmado']))
                                                        <span class="badge badge-success ml-2">
                                                            <i class="fa fa-check"></i> Firmado
                                                        </span>
                                                    @endif
                                                </div>

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

                                                    {{-- Ya NO mostramos acuse individual por documento --}}
                                                    {{-- @if($doc['acuse']) ... @endif --}}

                                                    @if($doc['entregado'] && (!isset($doc['editable']) || !$doc['editable']))
                                                        <i class="fas fa-lock text-danger ml-2" title="Ya no se puede editar este documento"></i>
                                                    @endif
                                                </div>
                                            </div>

                                            @if(!$doc['entregado'] || ($doc['entregado'] && $doc['editable']))
                                                <form action="{{ route('documentos.subir') }}" method="POST" enctype="multipart/form-data" class="row align-items-end js-subir-doc">
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
                                        @if($acuseLote)
                                            <div class="mt-1">
                                                <span class="badge badge-primary">
                                                    <i class="fa fa-file-pdf"></i> Acuse general listo (Lote #{{ $loteId ?? '—' }})
                                                </span>
                                            </div>
                                        @endif
                                    </div>

                                    <div class="d-flex align-items-center mt-2">
                                        @if($acuseLote)
                                            <a href="{{ asset('storage/'.$acuseLote) }}" target="_blank" class="btn btn-sm btn-outline-primary mr-2">
                                                <i class="fa fa-file-pdf"></i> Ver acuse general
                                            </a>
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
                                                   @if($completa && !$acuseLote) required @endif>
                                            <input type="hidden" name="firma_sat" id="firma_sat_batch_{{ $slug }}_{{ $u }}">
                                            @error('firma_sat')<div class="text-danger">{{ $message }}</div>@enderror
                                        </div>

                                        <div class="col-md-4">
                                            <label class="input-label" for="efirma_pass_batch_{{ $slug }}_{{ $u }}">Contraseña e.firma</label>
                                            <input type="password"
                                                   id="efirma_pass_batch_{{ $slug }}_{{ $u }}"
                                                   name="efirma_pass"
                                                   class="form-control form-control-sm"
                                                   @if($completa && !$acuseLote) required @endif>
                                            @error('efirma_pass')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                        </div>

                                        <div class="col-md-3 text-right">
                                            <button type="submit" class="btn btn-sm btn-primary"
                                                @if(!$completa || $acuseLote) disabled @endif>
                                                <i class="fas fa-file-signature"></i> Firmar Todo
                                            </button>
                                            @if($acuseLote)
                                                <div class="text-muted mt-1" style="font-size:.85rem;">
                                                    Ya existe acuse general para esta unidad.
                                                </div>
                                            @endif
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

  // Mover modales al <body>
  document.querySelectorAll('.modal').forEach(function(modal){
    document.body.appendChild(modal);
  });

  // Cambiar unidad visible en cada modal
  document.querySelectorAll('[id^="unidad_select_"]').forEach(function(sel){
    sel.addEventListener('change', function(){
      var val = this.value;
      var modalContent = this.closest('.modal-content');
      if (!modalContent) return;
      var display = modalContent.querySelector('.unidad-display');
      if (display) display.textContent = val;
      modalContent.querySelectorAll('.docs-unidad').forEach(function(div){
        div.style.display = div.classList.contains('docs-unidad-' + val) ? 'block' : 'none';
      });
    });
  });

  // Leer .p12 y guardar en hidden (base64)
  document.querySelectorAll('[id^="certFileBatch_"]').forEach(function(input){
    input.addEventListener('change', function(){
      var file = this.files[0];
      if (!file) return;
      var reader = new FileReader();
      reader.onload = function(evt){
        var base64 = (evt.target.result || '').split(',')[1] || '';
        var hiddenId = this.id.replace('certFileBatch_', 'firma_sat_batch_');
        var hidden = document.getElementById(hiddenId);
        if (hidden) hidden.value = base64;
      }.bind(this);
      reader.readAsDataURL(file);
    });
  });

  // ===== Countdown edición por documento=====
  (function(){
    function fmt(n){ return n < 10 ? '0'+n : ''+n; }
    function renderCountdown(el){
      const deadlineStr = el.dataset.deadline;
      if(!deadlineStr) return;
      const deadline = new Date(deadlineStr);
      const now = new Date();
      let diff = Math.floor((deadline - now)/1000);
      if (isNaN(diff)) return;
      const tSpan = el.querySelector('.t');

      if (diff <= 0) {
        if (tSpan) tSpan.textContent = '00:00:00';
        el.classList.remove('badge-info');
        el.classList.add('badge-danger');
        el.innerHTML = '<i class="fas fa-lock"></i> Edición cerrada';
        const item = el.closest('.list-group-item');
        if (item) {
          item.querySelectorAll('form input, form button, form select').forEach(x => { x.disabled = true; });
          const lockIcon = item.querySelector('.fa-lock');
          if (lockIcon) { lockIcon.classList.remove('text-muted'); lockIcon.classList.add('text-danger'); }
        }
        return;
      }

      const d = Math.floor(diff / 86400); diff -= d * 86400;
      const h = Math.floor(diff / 3600);  diff -= h * 3600;
      const m = Math.floor(diff / 60);    diff -= m * 60;
      const s = diff;
      if (tSpan) tSpan.textContent = d > 0 ? `${d}d ${fmt(h)}:${fmt(m)}:${fmt(s)}` : `${fmt(h)}:${fmt(m)}:${fmt(s)}`;
    }
    function tick(){ document.querySelectorAll('.edit-countdown').forEach(renderCountdown); }
    tick(); setInterval(tick, 1000);
  })();

  /* ======= ARREGLO CLAVE: interceptar “Solo Subir” por AJAX ======= */

  document.addEventListener('click', function(ev){
    const btn = ev.target.closest('button[type="submit"]');
    if (btn && btn.form) btn.form._lastSubmitter = btn;
  }, true);

  const SUBIR_URL = @json(route('documentos.subir'));
  const subirPath = new URL(SUBIR_URL, window.location.href).pathname;

  document.addEventListener('submit', function(ev){
    const form = ev.target;
    if (!(form instanceof HTMLFormElement)) return;

    const actionUrl = form.getAttribute('action') || form.action;
    const fpath = new URL(actionUrl, window.location.href).pathname;

    if (fpath !== subirPath) return;

    const submitter = ev.submitter || form._lastSubmitter || null;
    const isSoloSubir = submitter
      && submitter.name === 'action'
      && submitter.value === 'upload_only';
    if (!isSoloSubir) return;

    ev.preventDefault();
    ev.stopPropagation();
    if (ev.stopImmediatePropagation) ev.stopImmediatePropagation();

    const item = form.closest('.list-group-item');
    let fb = item ? item.querySelector('.upload-feedback') : null;
    if (!fb && item) {
      fb = document.createElement('div');
      fb.className = 'upload-feedback mt-2';
      item.appendChild(fb);
    }
    if (fb) fb.innerHTML = '<div class="alert alert-info py-1 mb-2">Subiendo…</div>';
    if (submitter) submitter.disabled = true;

    const fd = new FormData(form);
    if (!fd.has('action')) fd.append('action', 'upload_only');

    fetch(actionUrl, {
      method: 'POST',
      body: fd,
      headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
      credentials: 'same-origin'
    })
    .then(async (res) => {
      let data = null; try { data = await res.json(); } catch(_){}
      if (!res.ok || !data || data.ok !== true) {
        const msg = (data && (data.message || data.msg)) || 'Error al subir el documento.';
        throw new Error(msg);
      }

      if (fb) fb.innerHTML = `<div class="alert alert-success py-1 mb-2">${data.msg || 'Documento subido correctamente.'}</div>`;

      if (item && data.archivo_url) {
        const acciones = item.querySelector('.d-flex.align-items-center');
        if (acciones) {
          let ver = acciones.querySelector('.btn-outline-primary[target="_blank"]');
          if (!ver) {
            ver = document.createElement('a');
            ver.className = 'btn btn-sm btn-outline-primary ml-2';
            ver.target = '_blank';
            ver.innerHTML = '<i class="fas fa-file-alt"></i> Ver Archivo';
            acciones.appendChild(ver);
          }
          ver.href = data.archivo_url;
        }
      }

      const fileInput = form.querySelector('input[type="file"][name="archivo"]');
      if (fileInput) fileInput.value = '';

      if (item) {
        const status = item.closest('.docs-unidad')?.querySelector('.batch-sign .status');
        if (status) {
          const m = status.textContent.match(/(\d+)\s*\/\s*(\d+)/);
          if (m) {
            const entreg = parseInt(m[1], 10);
            const total  = parseInt(m[2], 10);
            if (entreg < total) {
              status.innerHTML = status.innerHTML.replace(/(\d+)\s*\/\s*(\d+)/, (entreg + 1) + ' / ' + total);
              const missing = status.parentElement.querySelector('.missing');
              if (missing) {
                const tipo = form.querySelector('input[name="tipo_documento"]')?.value || '';
                const arr = missing.textContent.replace('Faltan por subir:', '')
                          .split(',').map(s => s.trim()).filter(Boolean);
                const idx = arr.indexOf(tipo);
                if (idx > -1) arr.splice(idx, 1);
                if (arr.length) missing.textContent = 'Faltan por subir: ' + arr.join(', ');
                else missing.remove();
              }
            }
          }
        }
      }
    })
    .catch((err) => {
      if (fb) fb.innerHTML = `<div class="alert alert-danger py-1 mb-2">${err.message}</div>`;
    })
    .finally(() => {
      if (submitter) submitter.disabled = false;
    });

  }, true); // captura
});
</script>
@endpush

