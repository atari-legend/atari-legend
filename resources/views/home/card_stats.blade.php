<div class="card bg-dark mb-4">
    <div class="card-header text-center">
        <h2 class="text-uppercase">Statistics</h2>
    </div>
    <div class="card-body p-2">
        <p class="card-text">
            <ul>
                @foreach ($stats as $key => $stat)
                    <li>{{ $key }}: {{ $stat }}
                @endforeach
            </ul>
        </p>
    </div>
</div>
