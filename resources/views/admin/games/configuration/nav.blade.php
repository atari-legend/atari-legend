@foreach ($categories as $category => $typesForCategory)
    <ul class="nav nav-pills bg-light mb-2 p-2">
        <li class="nav-item disabled"><a class="nav-link disabled">{{ $category }}</a></li>
        @foreach ($typesForCategory as $typeForCategory)
            <li class="nav-item">
                <a class="nav-link @if ($typeForCategory == $type) active @endif" href="{{ route('admin.games.configuration.show', $typeForCategory) }}">
                    {{ $types[$typeForCategory] }}
                </a>
            </li>
        @endforeach
    </ul>
@endforeach
