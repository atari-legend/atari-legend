<div class="card mb-3 bg-light">
    <div class="card-body">
        <h2 class="card-title fs-4">Dumps</h2>

        @foreach ($release->medias as $media)
            {{ $media }}
            @foreach ($media->scans as $scan)
                {{ $scan }}
            @endforeach
            @foreach ($media->dumps as $dump)
                {{ $dump }}
            @endforeach
        @endforeach
    </div>
</div>
