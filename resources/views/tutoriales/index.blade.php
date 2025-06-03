@extends('layouts.app')

@section('title', 'TI-UTM - Tutoriales')

@section('content')
    <div class="container my-4">
        {{-- 🔹 Botón “Crear Nuevo Tutorial” (solo para Administrador) --}}
        @if(auth()->check() && auth()->user()->hasRole('Administrador'))
            <div class="mb-4">
                <a href="{{ route('tutoriales.create') }}" class="btn btn-sm" style="background-color: #FFFFFF; color: #000;">
                    <i class="fa-solid fa-plus"></i> Crear Nuevo Tutorial
                </a>
            </div>
        @endif

        {{-- 🔹 Lista de Tutoriales --}}
        @if($tutoriales->isEmpty())
            <div class="alert alert-info">
                No hay tutoriales registrados en el sistema.
            </div>
        @else
            {{-- El contenedor “sortable” solo existe si el usuario es Administrador --}}
            <div class="row gy-3"
                 @if(auth()->check() && auth()->user()->hasRole('Administrador'))
                     id="tutoriales-sortable"
                 @endif>
                @foreach($tutoriales as $tutorial)
                    <div class="col-md-6 col-lg-4 tutorial-item" data-id="{{ $tutorial->id }}">
                        <div class="card shadow-sm" style="border-radius: 10px; overflow: hidden;">
                            <div class="card-body d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="mb-1">{{ $tutorial->titulo }}</h5>
                                    <small class="text-muted">{{ ucfirst($tutorial->tipo) }}</small>
                                </div>
                                <div class="d-flex gap-1">
                                    {{-- Ver --}}
                                    <a href="{{ route('tutoriales.show', $tutorial->id) }}"
                                       class="btn btn-info btn-sm" title="Ver">
                                        <i class="fa fa-eye"></i>
                                    </a>
                                    {{-- Editar --}}
                                    @can('editar tutoriales')
                                        <a href="{{ route('tutoriales.edit', $tutorial->id) }}"
                                           class="btn btn-success btn-sm" title="Editar">
                                            <i class="fa fa-pen"></i>
                                        </a>
                                    @endcan
                                    {{-- Eliminar --}}
                                    @can('eliminar tutoriales')
                                        <form action="{{ route('tutoriales.destroy', $tutorial->id) }}"
                                              method="POST"
                                              onsubmit="return confirm('¿Seguro que deseas eliminar este tutorial?');"
                                              style="display:inline-block;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger btn-sm" title="Eliminar">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </form>
                                    @endcan
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
@endsection

@section('styles')
<style>
    /* Permitir arrastrar toda la tarjeta (card-body) */
    .tutorial-item {
        cursor: move;
    }
    .tutorial-item .card {
        transition: transform 0.2s, box-shadow 0.3s;
    }
    .tutorial-item .card:hover {
        transform: translateY(-3px);
        box-shadow: 0 4px 10px rgba(0,0,0,0.15);
    }
</style>
@endsection

@section('scripts')
    @if(auth()->check() && auth()->user()->hasRole('Administrador'))
        <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
        <script>
            $(function () {
                // Solo si existe el contenedor con ID “tutoriales-sortable”
                $('#tutoriales-sortable').sortable({
                    handle: '.card-body',
                    items: '.tutorial-item',
                    update() {
                        const orden = [];
                        $('#tutoriales-sortable .tutorial-item').each(function(i) {
                            orden.push({ id: $(this).data('id'), orden: i + 1 });
                        });

                        $.ajax({
                            url: '{{ route("tutoriales.sort") }}',
                            method: 'POST',
                            data: {
                                orden: orden,
                                _token: '{{ csrf_token() }}'
                            },
                            success() {
                                console.log('Orden de tutoriales actualizado');
                            },
                            error() {
                                alert('Error al guardar el orden');
                            }
                        });
                    }
                });
            });
        </script>
    @endif
@endsection
