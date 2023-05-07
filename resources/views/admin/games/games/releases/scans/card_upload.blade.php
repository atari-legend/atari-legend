<div class="card bg-light mb-3">
    <div class="card-body">
        <h2 class="card-title fs-4">Add scans</h2>

        <p class="text-muted">
            Add scans for the release (box, goodies, ...). After the upload you can change their types
            and add notes.
        </p>

        <form action="{{ route('admin.games.releases.scans.store', ['game' => $release->game, 'release' => $release]) }}"
            method="POST">
            @csrf
            <input type="file" name="file[]" multiple class="filepond" data-filepond-filetypes="image/*" data-filepond-button="#upload">
            <button class="btn btn-success" id="upload" disabled>Upload all files</button>
        </form>

    </div>
</div>
