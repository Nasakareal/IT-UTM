@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4><i class="bi bi-envelope"></i> Bandeja de Enviados</h4>
        <div>
            <a href="#" class="btn btn-success"><i class="bi bi-plus-circle"></i> Nuevo</a>
            <a href="{{ route('correspondencias.index') }}" class="btn btn-primary"><i class="bi bi-arrow-clockwise"></i> Actualizar</a>
            <a href="{{ route('home') }}" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Regresar</a>
        </div>
    </div>

    <div class="row">
        <!-- Panel Izquierdo: Listado de Correspondencias -->
        <div class="col-md-3">
            <input type="text" class="form-control mb-3" placeholder="🔍 Filtrar...">
            <div class="list-group">
                @foreach($correspondencias as $correspondencia)
                    <a href="{{ route('correspondencias.show', $correspondencia) }}" class="list-group-item list-group-item-action">
                        <div class="d-flex justify-content-between">
                            <strong>{{ str_pad($correspondencia->id, 6, '0', STR_PAD_LEFT) }}</strong>
                            <span class="badge 
                                @if($correspondencia->estado == 'Pendiente') bg-danger 
                                @elseif($correspondencia->estado == 'En proceso') bg-warning 
                                @else bg-success @endif">
                                {{ ucfirst($correspondencia->estado) }}
                            </span>
                        </div>
                        <small>{{ $correspondencia->oficio }}</small>
                        <br>
                        <small class="text-muted">{{ $correspondencia->tema }}</small>
                    </a>
                @endforeach
            </div>
        </div>

        <!-- Panel Derecho: Formulario de Nueva Correspondencia -->
        <div class="col-md-9">
            <div class="card">
                <div class="card-header bg-warning text-dark">
                    <strong><i class="bi bi-pencil-square"></i> NUEVO CORREO</strong>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('correspondencias.store') }}" enctype="multipart/form-data">
                        @csrf
                        <div class="row">
                            <div class="col-md-4">
                                <label class="form-label"><strong>* Remitente</strong></label>
                                <input type="text" class="form-control" name="remitente" required>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label"><strong>* Clasificación</strong></label>
                                <input type="text" class="form-control" name="clasificacion" required>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label"><strong>* Oficio</strong></label>
                                <input type="text" class="form-control" name="oficio" required>
                            </div>
                        </div>

                        

                        <div class="row">
                            <div class="col-md-6">
                                <label class="form-label"><strong>* Tipo de documento</strong></label>
                                <select class="form-control" name="tipo_documento" required>
                                    <option value="">Seleccionar...</option>
                                    <option value="Oficio">Oficio</option>
                                    <option value="Nota Informativa">Nota Informativa</option>
                                    <option value="Otro">Otro</option>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label"><strong>* Fecha de elaboración del documento</strong></label>
                                <input type="date" class="form-control" name="fecha_elaboracion" required>
                            </div>
                        </div>

                        

                        <div class="mb-3">
                            <label class="form-label"><strong>* Tema</strong> (250 máx.)</label>
                            <input type="text" class="form-control" name="tema" maxlength="250" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label"><strong>* Descripción del asunto</strong> (1500 máx.)</label>
                            <textarea class="form-control" name="descripcion_asunto" rows="3" maxlength="1500" required></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label"><strong>Adjuntar archivos</strong></label>
                            <input type="file" class="form-control" name="archivo_pdf" accept="application/pdf">
                        </div>

                        <div class="d-flex justify-content-between">
                            <button type="reset" class="btn btn-warning"><i class="bi bi-x-circle"></i> Cancelar</button>
                            <button type="submit" class="btn btn-success"><i class="bi bi-send"></i> Guardar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
