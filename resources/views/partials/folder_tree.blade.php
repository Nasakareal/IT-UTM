@php
    $collapseId = 'folder-' . $folder->id;
@endphp

<li>
    <!-- Enlace para abrir/cerrar la carpeta -->
    <span class="folder-toggle text-primary" style="cursor: pointer; text-decoration: none;" onclick="toggleFolder('{{ $collapseId }}', this)">
        <i class="fa fa-folder"></i> {{ $folder->nombre }}
    </span>

    <!-- Contenedor oculto de archivos y subcarpetas -->
    @if($folder->archivos->count() || $folder->children->count())
        <ul id="{{ $collapseId }}" class="list-unstyled ms-3 d-none">
            @if($folder->archivos->count())
                @foreach($folder->archivos as $archivo)
                    <li>
                        <i class="fa fa-file"></i>
                        <a href="{{ asset('storage/' . $archivo->ruta) }}" target="_blank">
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

@push('scripts')
<script>
    function toggleFolder(folderId, element) {
        let folderContent = document.getElementById(folderId);
        if (folderContent) {
            folderContent.classList.toggle('d-none');

            let icon = element.querySelector('i');
            if (icon) {
                icon.classList.toggle('fa-folder');
                icon.classList.toggle('fa-folder-open');
            }
        }
    }
</script>
@endpush
