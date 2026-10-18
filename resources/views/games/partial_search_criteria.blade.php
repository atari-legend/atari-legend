{{-- The criteria the search was made with, each removable on its own, and a
     way back to the search form with all of them filled in --}}
@php
    $query = Arr::except(request()->query(), ['page']);
@endphp
<div class="card-body p-2">
    @if (count($criteria) > 0)
        <p class="card-text mb-2">You searched for games matching:</p>
        <ul class="list-unstyled d-flex flex-wrap gap-3 mb-4" aria-label="Search criteria">
            @foreach ($criteria as $criterion)
                @php
                    $without = Arr::except($query, $criterion['params']);
                    $remaining = count($criteria) > 1;
                @endphp
                <li class="btn-group btn-group-sm">
                    @if (is_bool($criterion['value']))
                        <span class="btn btn-outline-secondary text-body pe-none">{{ $criterion['label'] }}</span>
                        {{-- Yes/no criteria flip in place, without going through the form --}}
                        @foreach ([true => 'Yes', false => 'No'] as $flag => $text)
                            @if ($criterion['value'] === (bool) $flag)
                                <span class="btn btn-primary pe-none" aria-current="true">{{ $text }}</span>
                            @else
                                <a href="{{ route('games.search', array_merge($without, [$criterion['params'][0] => (int) $flag])) }}"
                                    class="btn btn-outline-primary" title="Search for {{ $text }} instead"
                                    aria-label="{{ $criterion['label'] }}: {{ $text }}">{{ $text }}</a>
                            @endif
                        @endforeach
                    @else
                        <span class="btn btn-outline-secondary text-body pe-none">{{ $criterion['label'] }}: <b>{{ $criterion['value'] }}</b></span>
                    @endif
                    <a href="{{ $remaining ? route('games.search', $without) : route('games.index') }}"
                        class="btn btn-outline-secondary" title="Remove this criterion"
                        aria-label="Remove {{ $criterion['label'] }} from the search"><i class="fas fa-times"></i></a>
                </li>
            @endforeach
        </ul>
    @else
        <p class="card-text mb-2">No search criteria were given.</p>
    @endif
    <div class="text-center pt-3 mb-3">
        <a href="{{ route('games.index', $query) }}" class="btn btn-primary me-2">
            <i class="fas fa-sliders-h me-1"></i>Refine search
        </a>
        <a href="{{ route('games.index') }}" class="btn btn-outline-primary">New search</a>
    </div>
</div>
