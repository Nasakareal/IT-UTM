@section('head')
    <link rel="icon" href="{{ asset('favicons.ico') }}" type="image/x-icon">
@endsection

@php
    $collapseId = 'folder-' . $folder->id;
    $level = $level ?? 0;
    $class = $level === 0 ? 'folder-parent' : 'folder-child';
@endphp

@once
<style>
    .folder-parent,
    .folder-parent i {
        color: #5C7285 !important;
    }

    .folder-child,
    .folder-child i {
        color: #818C78 !important;
    }
</style>
@endonce

<li>
    <!-- Enlace para abrir/cerrar la carpeta -->
    <span class="folder-toggle {{ $class }}"
          style="cursor: pointer; text-decoration: none;"
          onclick="toggleFolder('{{ $collapseId }}', this)">
        <i class="fa fa-folder"></i> {{ $folder->nombre }}
    </span>

    <!-- Contenedor oculto de archivos y subcarpetas -->
    @if($folder->archivos->count() || $folder->children->count())
        <ul id="{{ $collapseId }}" class="list-unstyled d-none" style="margin-left: 20px;">
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
                    {{-- Se incrementa el nivel para las subcarpetas --}}
                    @include('partials.folder_tree', ['folder' => $child, 'level' => $level + 1])
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
