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
        .folder-parent, .folder-parent i  { color: #618264!important; }
        .folder-child,  .folder-child i   { color: #79AC78!important; }
    </style>
@endonce

<li class="carpeta-item" data-id="{{ $folder->id }}">
    <span class="folder-toggle {{ $class }}"
          style="cursor: pointer; text-decoration: none;"
          onclick="toggleFolder('{{ $collapseId }}', this)">
        <i class="fa fa-folder"></i> {{ $folder->nombre }}
    </span>

    @if(auth()->check() && auth()->user()->hasRole('Administrador'))
        <div class="btn-group ml-2" role="group">
            {{-- editar --}}
            <form action="{{ route('carpetas.edit', $folder->id) }}" method="GET" style="display:inline;">
                <button type="submit" class="btn btn-success btn-sm">
                    <i class="fa-regular fa-pen-to-square"></i>
                </button>
            </form>
            {{-- subir archivo --}}
            <form action="{{ route('carpetas.upload', $folder->id) }}" method="POST" enctype="multipart/form-data" style="display:inline;">
                @csrf
                <input type="file" name="archivo" id="archivo_{{ $folder->id }}" style="display:none;" onchange="this.form.submit()">
                <label for="archivo_{{ $folder->id }}" class="btn btn-warning btn-sm mb-0">
                    <i class="bi bi-file-earmark-arrow-up"></i>
                </label>
            </form>
            {{-- eliminar --}}
            <form action="{{ route('carpetas.destroy', $folder->id) }}" method="POST" style="display:inline-block;">
                @csrf
                @method('DELETE')
                <button type="button" class="btn btn-danger btn-sm delete-btn">
                    <i class="fa-regular fa-trash-can"></i>
                </button>
            </form>
        </div>
    @endif

    @if($folder->archivos->count() || $folder->children->count())
        <ul id="{{ $collapseId }}" class="list-unstyled d-none" style="margin-left:20px;">
            {{-- Archivos ordenados y con íconos dinámicos --}}
            @foreach($folder->archivos->sortBy('nombre') as $archivo)
                @php
                    $ext = strtolower(pathinfo($archivo->ruta, PATHINFO_EXTENSION));
                    $iconoMap = [
                        'pdf'  => 'bi-filetype-pdf text-danger',
                        'doc'  => 'bi-file-earmark-word text-primary',
                        'docx' => 'bi-file-earmark-word text-primary',
                        'xls'  => 'bi-file-earmark-excel text-success',
                        'xlsx' => 'bi-file-earmark-excel text-success',
                        'ppt'  => 'bi-file-earmark-ppt text-warning',
                        'pptx' => 'bi-file-earmark-ppt text-warning',
                        'zip'  => 'bi-file-earmark-zip text-muted',
                        'rar'  => 'bi-file-earmark-zip text-muted',
                        'jpg'  => 'bi-file-earmark-image text-info',
                        'jpeg' => 'bi-file-earmark-image text-info',
                        'png'  => 'bi-file-earmark-image text-info',
                    ];
                    $icono = $iconoMap[$ext] ?? 'bi-file-earmark text-secondary';
                @endphp
                <li class="archivo-item" data-id="{{ $archivo->id }}">
                    <i class="bi {{ $icono }}"></i>
                    <a href="{{ asset('storage/'.$archivo->ruta) }}" target="_blank">
                        {{ $archivo->nombre }}
                    </a>
                </li>
            @endforeach

            {{-- Subcarpetas --}}
            @foreach($folder->children->sortBy('nombre') as $child)
                @include('partials.folder_tree', ['folder' => $child, 'level' => $level + 1])
            @endforeach
        </ul>
    @endif
</li>

@push('scripts')
<script>
    function toggleFolder(folderId, btn) {
        const el = document.getElementById(folderId);
        if (!el) return;
        el.classList.toggle('d-none');
        const ico = btn.querySelector('i');
        ico?.classList.toggle('fa-folder-open');
        ico?.classList.toggle('fa-folder');
    }
</script>
@endpush
