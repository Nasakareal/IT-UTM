@extends('layouts.app')

@section('title', 'TI-UTM - Inicio')

@section('content')
    <!-- 🔹 Alerta dinámica de Documentos Pendientes -->
    @if($documentosPendientes->isNotEmpty())
        <div class="alert-box">
            <div class="alert-content">
                <i class="fas fa-exclamation-triangle"></i>
                <span>
                    Se informa que, la Universidad no ha recibido la siguiente documentación:
                </span>
                <ul>
                    @foreach ($documentosPendientes as $documento)
                        <li>
                            📌 {{ $documento->titulo }} - Fecha límite: 
                            <span class="text-danger">
                                {{ \Carbon\Carbon::parse($documento->fecha_limite)->format('d/m/Y') }}
                            </span>
                        </li>

                    @endforeach
                </ul>
                <p>Se recomienda entregar la documentación a la brevedad.</p>
            </div>
        </div>
    @else
        <div class="alert-box success">
            <div class="alert-content">
                <i class="fas fa-check-circle"></i>
                <span>✅ No hay documentos pendientes. Todo está en orden.</span>
            </div>
        </div>
    @endif

    <!-- 🔹 Botón "Crear Nuevo Comunicado" (solo para Administrador) -->
    @if(auth()->check() && auth()->user()->hasRole('Administrador'))
        <div class="mb-4">
            <a href="{{ url('/settings/comunicados/create') }}" class="btn btn-sm" style="background-color: #FFFFFF; color: #000;">
                <i class="fa-solid fa-plus"></i> Crear Nuevo Comunicado
            </a>
        </div>
    @endif

    <!-- 🔹 Carrusel de comunicados -->
    @if($comunicados->isNotEmpty())
        <div class="comunicado-carousel-wrapper">
            <div class="comunicado-carousel" id="comunicadoCarousel">
                @foreach($comunicados as $comunicado)
                    <div class="comunicado-slide">
                        @if($comunicado->tipo === 'imagen')
                            <!-- Si el comunicado es una imagen -->
                            <div class="comunicado-image">
                                <img src="{{ asset('storage/'.$comunicado->ruta_imagen) }}" alt="Imagen del comunicado">
                            </div>
                        @else
                            <!-- Si el comunicado es de texto (o ambos) -->
                            <div class="comunicado-content">
                                <div class="comunicado-title">{{ $comunicado->titulo }}</div>
                                <div class="comunicado-date">{{ $comunicado->fecha }}</div>
                                <div class="comunicado-body">
                                    {!! $comunicado->contenido !!}
                                </div>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
            <!-- Botones de navegación -->
            <button class="carousel-control prev" id="prevBtn">&lt;</button>
            <button class="carousel-control next" id="nextBtn">&gt;</button>
        </div>
    @else
        <p>No hay comunicados por el momento.</p>
    @endif

    <br>

    <!-- 🔹 Botón "Crear Nueva Sección" (solo para Administrador) -->
    @if(auth()->check() && auth()->user()->hasRole('Administrador'))
        <div class="mb-4">
            <a href="{{ url('/settings/secciones/create') }}" class="btn btn-sm" style="background-color: #FFFFFF; color: #000;">
                <i class="fa-solid fa-plus"></i> Crear Nueva Sección
            </a>
        </div>
    @endif

    <!-- 🔹 Secciones dinámicas (cada sección con sus módulos) -->
<div class="container my-4" @if(auth()->check() && auth()->user()->hasRole('Administrador')) id="secciones-sortable" @endif>
    @foreach ($secciones as $seccion)
        <div class="seccion-item" data-id="{{ $seccion->id }}">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="mb-0" @if(auth()->check() && auth()->user()->hasRole('Administrador')) style="cursor: move;" @endif>{{ $seccion->nombre }}</h2>
                @if(auth()->check() && auth()->user()->hasRole('Administrador'))
                    <div class="btn-group" role="group">
                        <a href="{{ route('secciones.edit', $seccion->id) }}" class="btn btn-success btn-sm">
                            <i class="fa-regular fa-pen-to-square"></i>
                        </a>
                        <form action="{{ route('secciones.destroy', $seccion->id) }}" method="POST" style="display:inline-block;">
                            @csrf
                            @method('DELETE')
                            <button type="button" class="btn btn-danger btn-sm delete-btn">
                                <i class="fa-regular fa-trash-can"></i>
                            </button>
                        </form>
                    </div>
                @endif
            </div>

            @if(auth()->check() && auth()->user()->hasRole('Administrador'))
                <div class="mb-4">
                    <a href="{{ url('/settings/modulos/create') }}" class="btn btn-sm" style="background-color: #FFFFFF; color: #000;">
                        <i class="fa-solid fa-plus"></i> Crear Nuevo Modulo
                    </a>
                </div>
            @endif

            <div class="row">
                @forelse ($seccion->modulos as $modulo)
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="d-flex module-card shadow" style="border-radius: 10px; overflow: hidden; border: none;">
                            <div class="module-left d-flex flex-column justify-content-between align-items-center p-2"
                                 style="width: 80px; background-color: {{ $modulo->color ?? $seccion->color ?? '#009688' }};">
                                @if(!empty($modulo->imagen))
                                    <img src="{{ asset('storage/'.$modulo->imagen) }}" alt="Ícono del módulo" style="max-width: 50px; max-height: 50px;" class="my-2">
                                @elseif(!empty($modulo->icono))
                                    <i class="fas {{ $modulo->icono }} fa-2x text-white my-2"></i>
                                @else
                                    <i class="fas fa-cube fa-2x text-white my-2"></i>
                                @endif
                                <div class="text-white fw-bold mb-2">{{ $modulo->anio }}</div>
                            </div>

                            <div class="module-right p-3 flex-grow-1 d-flex flex-column justify-content-between">
                                <div>
                                    <h5 class="card-title">{{ $modulo->titulo }}</h5>
                                    @if($modulo->descripcion)
                                        <p class="card-text">{{ $modulo->descripcion }}</p>
                                    @endif
                                </div>

                                <div class="d-flex justify-content-between align-items-center mt-3">
                                    <a href="{{ route('modulos.show', $modulo->id) }}" class="btn text-white" style="background-color: {{ $modulo->color ?? $seccion->color ?? '#009688' }};">
                                        Ingresar
                                    </a>
                                    @if(auth()->check() && auth()->user()->hasRole('Administrador'))
                                        <div class="btn-group ms-2" role="group">
                                            <a href="{{ route('modulos.edit', $modulo->id) }}" class="btn btn-success btn-sm">
                                                <i class="fa-regular fa-pen-to-square"></i>
                                            </a>
                                            <form action="{{ route('modulos.destroy', $modulo->id) }}" method="POST" onsubmit="return confirm('¿Estás seguro de eliminar este módulo?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-danger btn-sm">
                                                    <i class="fa-regular fa-trash-can"></i>
                                                </button>
                                            </form>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-12"><p>No hay módulos en esta sección.</p></div>
                @endforelse
            </div>
        </div>
    @endforeach
</div>


@endsection

@section('styles')
<style>
    .module-card {
        /* Quitamos altura fija */
        min-height: 180px;
        display: flex;
        border-radius: 10px;
        overflow: hidden;
    }

    .module-left {
        width: 80px;
        min-width: 80px;
        background-color: #009688;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        align-items: center;
        padding: 0.5rem;
    }

    .module-right {
        padding: 1rem;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }

    .card-title {
        font-size: 1.1rem;
        font-weight: bold;
        white-space: normal; /* Permite salto de línea */
        overflow: visible;
    }

    .card-text {
        flex-grow: 1;
        font-size: 1rem;
        color: #444;
        overflow: visible;
        display: block;
        white-space: normal;
    }

    .btn-ingresar {
        width: fit-content;
        white-space: nowrap;
    }
</style>
@endsection



@section('scripts')
    <script>
        // ---------- Lógica de Carrusel ----------
        const carousel = document.getElementById('comunicadoCarousel');
        const slides = document.querySelectorAll('.comunicado-slide');
        const prevBtn = document.getElementById('prevBtn');
        const nextBtn = document.getElementById('nextBtn');

        let currentIndex = 0;
        let intervalTime = 500000;
        let autoSlide;
        let isDragging = false;
        let startPos = 0;
        let currentTranslate = 0;
        let prevTranslate = 0;

        function updateCarousel() {
            carousel.style.transform = `translateX(-${currentIndex * 100}%)`;
        }

        function showNextSlide() {
            currentIndex = (currentIndex + 1) % slides.length;
            updateCarousel();
        }
        function showPrevSlide() {
            currentIndex = (currentIndex - 1 + slides.length) % slides.length;
            updateCarousel();
        }

        function startAutoSlide() {
            autoSlide = setInterval(showNextSlide, intervalTime);
        }
        function stopAutoSlide() {
            clearInterval(autoSlide);
        }

        function touchStart(index) {
            return function(event) {
                stopAutoSlide();
                isDragging = true;
                startPos = getPositionX(event);
                currentIndex = index;
                prevTranslate = currentIndex * -window.innerWidth;
                carousel.classList.add('grabbing');
            };
        }
        function touchMove(event) {
            if (!isDragging) return;
            const currentPosition = getPositionX(event);
            currentTranslate = prevTranslate + (currentPosition - startPos);
            carousel.style.transform = `translateX(${currentTranslate}px)`;
        }
        function touchEnd() {
            isDragging = false;
            carousel.classList.remove('grabbing');
            const movedBy = currentTranslate - prevTranslate;
            if (movedBy < -100) {
                currentIndex = (currentIndex + 1) % slides.length;
            }
            if (movedBy > 100) {
                currentIndex = (currentIndex - 1 + slides.length) % slides.length;
            }
            updateCarousel();
            startAutoSlide();
        }
        function getPositionX(event) {
            return event.type.includes('mouse') ? event.pageX : event.touches[0].clientX;
        }

        prevBtn.addEventListener('click', () => {
            stopAutoSlide();
            showPrevSlide();
            startAutoSlide();
        });
        nextBtn.addEventListener('click', () => {
            stopAutoSlide();
            showNextSlide();
            startAutoSlide();
        });

        slides.forEach((slide, index) => {
            slide.addEventListener('mousedown', touchStart(index));
            slide.addEventListener('touchstart', touchStart(index), { passive: true });
            slide.addEventListener('mousemove', touchMove);
            slide.addEventListener('touchmove', touchMove, { passive: true });
            slide.addEventListener('mouseup', touchEnd);
            slide.addEventListener('touchend', touchEnd);
            slide.addEventListener('dragstart', (e) => e.preventDefault());
        });

        updateCarousel();
        startAutoSlide();
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function(){
            const deleteButtons = document.querySelectorAll('.delete-btn');
            deleteButtons.forEach(function(button){
                button.addEventListener('click', function(e){
                    e.preventDefault();
                    const form = this.closest('form');
                    Swal.fire({
                        title: '¿Estás seguro?',
                        text: "Esta acción no se puede revertir",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'Sí, eliminar',
                        cancelButtonText: 'Cancelar'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            form.submit();
                        }
                    });
                });
            });
        });
    </script>

@if(auth()->check() && auth()->user()->hasRole('Administrador'))
    <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
    <script>
        $(function () {
            $('#secciones-sortable').sortable({
                handle: 'h2',
                update: function () {
                    let orden = [];
                    $('.seccion-item').each(function (index) {
                        orden.push({
                            id: $(this).data('id'),
                            orden: index + 1
                        });
                    });

                    $.ajax({
                        url: '{{ route("secciones.sort") }}',
                        method: 'POST',
                        data: {
                            orden: orden,
                            _token: '{{ csrf_token() }}'
                        },
                        success: function () {
                            console.log('Orden de secciones actualizado');
                        },
                        error: function () {
                            alert('Error al guardar el orden');
                        }
                    });
                }
            });
        });
    </script>
@endif



@endsection
