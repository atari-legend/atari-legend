<div class="card bg-dark mb-4">
    <div class="card-header text-center">
        <h2 class="text-uppercase">Statistics</h2>
    </div>
    <div class="card-body p-0">
        <ul class="p-0 list-unstyled striped">
            @foreach ($stats as $key => $stat)
                <li class="px-2 py-1">{{ $key }}: {{ $stat }}
            @endforeach
        </ul>
    </div>
</div>
