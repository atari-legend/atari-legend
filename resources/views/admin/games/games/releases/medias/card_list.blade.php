<h2 class="mb-3 fs-4">{{ $release->medias->count() }} media</h2>

<form class="mb-3"
    action="{{ route('admin.games.releases.medias.store', [
        'game' => $release->game,
        'release' => $release,
    ]) }}"
    method="post">
    @csrf
    <button type="submit" class="btn btn-primary">Add media</button>
</form>

@foreach ($release->medias->sortBy(['type', 'label']) as $media)
    @include('admin.games.games.releases.medias.card_media')
@endforeach
