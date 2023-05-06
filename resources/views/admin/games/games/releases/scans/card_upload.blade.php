<div class="card bg-light mb-3">
    <div class="card-body">
        <h2 class="card-title fs-4">Add scans</h2>

        <form action="{{ route('admin.games.releases.scans.store', ['game' => $release->game, 'release' => $release]) }}"
            method="POST">
            @csrf
            <input type="file" name="file[]" multiple class="filepond" data-filepond-filetypes="image/*">
            <button class="btn btn-success">Upload all files</button>
        </form>

    </div>
</div>
