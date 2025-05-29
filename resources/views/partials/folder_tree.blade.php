@section('head')
    <link rel="icon" href="{{ asset('favicons.ico') }}" type="image/x-icon">
@endsection

@php
    $collapseId = 'folder-' . $folder->id;
    $level      = $level ?? 0;
    $class      = $level === 0 ? 'folder-parent' : 'folder-child';
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

<li class="carpeta-item" data-id="{{ $folder->id }}">
    <!-- Enlace para abrir/cerrar la carpeta -->
    <span class="folder-toggle {{ $class }}"
          style="cursor: pointer; text-decoration: none;"
          onclick="toggleFolder('{{ $collapseId }}', this)">
        <i class="fa fa-folder"></i> {{ $folder->nombre }}
    </span>

    <!-- Botones de acción solo para Administrador -->
    @if(auth()->check() && auth()->user()->hasRole('Administrador'))
        <div class="btn-group ml-2" role="group">
            <!-- Editar carpeta -->
            <form action="{{ route('carpetas.edit', $folder->id) }}" method="GET" style="display:inline;">
                <button type="submit" class="btn btn-success btn-sm">
                    <i class="fa-regular fa-pen-to-square"></i>
                </button>
            </form>

            <!-- Subir archivos -->
            <form action="{{ route('carpetas.upload', $folder->id) }}" method="POST" enctype="multipart/form-data" style="display:inline;">
                @csrf
                <input type="file" name="archivo" id="archivo_{{ $folder->id }}" style="display:none;" onchange="this.form.submit()">
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

    <!-- Archivos y subcarpetas ocultos -->
    @foreach($folder->archivos->sortBy('nombre') as $archivo)

        @php
            $extension = pathinfo($archivo->ruta, PATHINFO_EXTENSION);
            switch(strtolower($extension)) {
                case 'pdf':
                    $icono = 'bi-filetype-pdf text-danger';
                    break;
                case 'doc':
                case 'docx':
                    $icono = 'bi-file-earmark-word text-primary';
                    break;
                case 'xls':
                case 'xlsx':
                    $icono = 'bi-file-earmark-excel text-success';
                    break;
                case 'ppt':
                case 'pptx':
                    $icono = 'bi-file-earmark-ppt text-warning';
                    break;
                case 'zip':
                case 'rar':
                    $icono = 'bi-file-earmark-zip text-muted';
                    break;
                case 'jpg':
                case 'jpeg':
                case 'png':
                    $icono = 'bi-file-earmark-image text-info';
                    break;
                default:
                    $icono = 'bi-file-earmark text-secondary';
            }
        @endphp

        <li>
            <i class="bi {{ $icono }}"></i>
            <a href="{{ asset('storage/' . $archivo->ruta) }}" target="_blank">
                {{ $archivo->nombre }}
            </a>
        </li>
    @endforeach

</li>

@push('scripts')
<script>
    function toggleFolder(folderId, element) {
        const container = document.getElementById(folderId);
        if (!container) return;

        container.classList.toggle('d-none');
        const icon = element.querySelector('i');
        if (icon) {
            icon.classList.toggle('fa-folder');
            icon.classList.toggle('fa-folder-open');
        }
    }
</script>
@endpush
