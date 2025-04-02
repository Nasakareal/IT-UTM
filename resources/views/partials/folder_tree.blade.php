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
        color: #618264 !important;
    }
    .folder-child,
    .folder-child i {
        color: #79AC78 !important;
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

    <!-- Botones de acción solo para usuarios con rol "Administrador" -->
    @if(auth()->check() && auth()->user()->hasRole('Administrador'))
        <div class="btn-group ml-2" role="group">
            <!-- Editar carpeta -->
            <form action="{{ route('carpetas.edit', $folder->id) }}" method="GET" style="display: inline;">
                <button type="submit" class="btn btn-success btn-sm">
                    <i class="fa-regular fa-pen-to-square"></i>
                </button>
            </form>

            <!-- Subir archivos -->
            <form action="{{ route('carpetas.upload', $folder->id) }}" method="POST" enctype="multipart/form-data" style="display: inline;">
                @csrf
                <input type="file" name="archivo" id="archivo_{{ $folder->id }}" style="display: none;" onchange="this.form.submit()">
                <label for="archivo_{{ $folder->id }}" class="btn btn-warning btn-sm mb-0">
                    <i class="bi bi-file-earmark-arrow-up"></i>
                </label>
            </form>

            <!-- Eliminar carpeta -->
            <form action="{{ route('carpetas.destroy', $folder->id) }}" method="POST" style="display:inline-block;">
                @csrf
                @method('DELETE')
                <button type="button" class="btn btn-danger btn-sm delete-btn">
                    <i class="fa-regular fa-trash-can"></i>
                </button>
            </form>
        </div>
    @endif


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
