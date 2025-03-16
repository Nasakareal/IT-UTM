<ul class="list-unstyled ms-4">
    @foreach($folders as $folder)
        <li>
            <i class="fa fa-folder text-secondary"></i>
            <a href="{{ route('carpetas.show', $folder->id) }}" class="text-secondary">
                {{ $folder->nombre }}
            </a>
            @if($folder->children->count())
                @include('partials.folder_tree', ['folders' => $folder->children])
            @endif
        </li>
    @endforeach
</ul>
