@extends('layouts.app')

@section('title', 'TI-UTM - Inicio')

@section('content')
    <!-- 🔹 Alerta dinámica -->
    @if($documentosPendientes->isNotEmpty())
        <div class="alert-box">
            <div class="alert-content">
                <i class="fas fa-exclamation-triangle"></i>
                <span>Se informa que con fecha de corte al <strong>{{ now()->format('Y-m-d') }}</strong>, la Universidad no ha recibido la siguiente documentación:</span>
                <ul>
                    @foreach ($documentosPendientes as $documento)
                        <li>📌 {{ $documento->nombre }} - Fecha límite: <span class="text-danger">{{ $documento->fecha_limite }}</span></li>
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
@endsection


@section('scripts')
    <script>
        // ---------- Lógica de Carrusel ----------
        const carousel = document.getElementById('comunicadoCarousel');
        const slides = document.querySelectorAll('.comunicado-slide');
        const prevBtn = document.getElementById('prevBtn');
        const nextBtn = document.getElementById('nextBtn');

        let currentIndex = 0;          // Índice de la diapositiva actual
        let intervalTime = 5000;       // Tiempo en ms para pasar de diapositiva (auto-rotación)
        let autoSlide;                 // Variable para setInterval
        let isDragging = false;        // Controla si se está arrastrando
        let startPos = 0;             // Posición inicial (x) del ratón/touch
        let currentTranslate = 0;     // Traslación actual
        let prevTranslate = 0;        // Traslación previa (para restaurar en caso de no avanzar)

        // Función para actualizar la posición del carrusel
        function updateCarousel() {
            carousel.style.transform = `translateX(-${currentIndex * 100}%)`;
        }

        // Funciones para ir a la diapositiva anterior/siguiente
        function showNextSlide() {
            currentIndex = (currentIndex + 1) % slides.length;
            updateCarousel();
        }
        function showPrevSlide() {
            currentIndex = (currentIndex - 1 + slides.length) % slides.length;
            updateCarousel();
        }

        // Auto-rotación
        function startAutoSlide() {
            autoSlide = setInterval(showNextSlide, intervalTime);
        }
        function stopAutoSlide() {
            clearInterval(autoSlide);
        }

        // Manejadores de eventos para arrastrar
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

            // Si se movió suficiente a la izquierda o derecha, cambiamos de slide
            if (movedBy < -100) {
                // siguiente
                currentIndex = (currentIndex + 1) % slides.length;
            }
            if (movedBy > 100) {
                // anterior
                currentIndex = (currentIndex - 1 + slides.length) % slides.length;
            }
            updateCarousel();
            startAutoSlide();
        }
        function getPositionX(event) {
            return event.type.includes('mouse') ? event.pageX : event.touches[0].clientX;
        }

        // Eventos de los botones
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

        // Configurar arrastre/touch para cada diapositiva
        slides.forEach((slide, index) => {
            // Mousedown / touchstart
            slide.addEventListener('mousedown', touchStart(index));
            slide.addEventListener('touchstart', touchStart(index), { passive: true });

            // Mousemove / touchmove
            slide.addEventListener('mousemove', touchMove);
            slide.addEventListener('touchmove', touchMove, { passive: true });

            // Mouseup / touchend
            slide.addEventListener('mouseup', touchEnd);
            slide.addEventListener('touchend', touchEnd);

            // Evitar arrastrar la imagen por defecto (para no interferir con el swipe)
            slide.addEventListener('dragstart', (e) => e.preventDefault());
        });

        // Iniciamos el carrusel
        updateCarousel();
        startAutoSlide();
    </script>
@endsection
