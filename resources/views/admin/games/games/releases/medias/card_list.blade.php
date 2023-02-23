<div class="card mb-3 bg-light">
    <div class="card-body">
        <h2 class="card-title fs-4">{{ $release->medias->count() }} media</h2>

        @foreach ($release->medias as $media)
            @include('admin.games.games.releases.medias.card_media')
        @endforeach
    </div>
</div>
