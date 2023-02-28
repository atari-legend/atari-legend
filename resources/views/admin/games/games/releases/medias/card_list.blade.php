<h2 class="mb-3 fs-4">{{ $release->medias->count() }} media</h2>

@foreach ($release->medias as $media)
    @include('admin.games.games.releases.medias.card_media')
@endforeach
