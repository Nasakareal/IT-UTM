@php
    // Genera un ID único para el contenedor (si quieres mostrar archivos y subcarpetas de forma anidada)
    $collapseId = 'folder-' . $folder->id;
@endphp

<li>
    <!-- Enlace para ir al show de la carpeta al hacer clic en el nombre -->
    <a href="{{ route('carpetas.show', $folder->id) }}" class="folder-toggle text-primary" style="text-decoration: none;">
        <i class="fa fa-folder"></i> {{ $folder->nombre }}
    </a>

    <!-- Opcional: Si deseas mostrar de forma anidada los archivos y subcarpetas (sin collapse), puedes hacerlo -->
    @if($folder->archivos->count() || $folder->children->count())
        <ul class="list-unstyled ml-3">
            @if($folder->archivos->count())
                @foreach($folder->archivos as $archivo)
                    <li>
                        <i class="fa fa-file"></i>
                        <a href="{{ Storage::url($archivo->ruta) }}" target="_blank">
                            {{ $archivo->nombre }}
                        </a>
                    </li>
                @endforeach
            @endif
            @if($folder->children->count())
                @foreach($folder->children as $child)
                    @include('partials.folder_tree', ['folder' => $child])
                @endforeach
            @endif
        </ul>
    @endif
</li>
