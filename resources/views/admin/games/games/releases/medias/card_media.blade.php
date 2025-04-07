<div class="card bg-light mb-3">
    <div class="card-body">
        <div class="row">
            <div class="col">
                <h3>Info</h3>
                <form
                    action="{{ route('admin.games.releases.medias.update', [
                        'game' => $media->release->game,
                        'release' => $media->release,
                        'media' => $media,
                    ]) }}"
                    method="POST">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label for="type-{{ $media->getKey() }}" class="form-label">Type</label>
                        <select id="type-{{ $media->getKey() }}"
                            class="form-select @error('type') is-invald @endif" name="type">
                            <option value="" @if (old('type', $media->type?->getKey()) === null) selected @endif>-</option>
                            @foreach ($mediaTypes as $type)
                                <option value="{{ $type->getKey() }}" @if (old('type', $media->type?->getKey()) === $type->getKey()) selected @endif>{{ $type->name }}</option>
                            @endforeach
                        </select>

                        @error('type')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>
                    <div class="mb-3">
                            <label for="label-{{ $media->getKey() }}" class="form-label">Label</label>
                            <input id="label-{{ $media->getKey() }}" name="label" id="label"
                                class="form-control @error('label') is-invald @endif" placeholder="e.g. 'Disk A'"
                            value="{{ old('label', $media->label) }}">

                        @error('label')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>
                    <div class="col">
                            <button class="btn btn-success">Save</button>
                            <button class="btn btn-danger ms-2" name="delete"
                                onclick="javascript:return confirm('This media will be deleted')" value="delete">Delete
                                media</button>
                    </div>
                </form>
            </div>
            <div class="col">
                <h3>Scans</h3>
                <div class="row mb-3">
                    <form
                        action="{{ route('admin.games.releases.medias.scans.store', [
                            'game' => $release->game,
                            'release' => $release,
                            'media' => $media,
                        ]) }}"
                        method="POST">
                        @csrf
                        <input type="file" name="file[]" multiple class="filepond" data-filepond-filetypes="image/*"
                            data-filepond-button="[data-upload-media='{{ $media->getKey() }}']">
                        <button class="btn btn-success" data-upload-media="{{ $media->getKey() }}" disabled>Upload all
                            files</button>
                    </form>
                </div>
                <div class="row row-cols-3">
                    @if ($media->scans->isNotEmpty())
                        @foreach ($media->scans as $scan)
                            <div class="col text-center">
                                <form class="position-relative"
                                    action="{{ route('admin.games.releases.medias.scans.destroy', [
                                        'game' => $release->game,
                                        'release' => $release,
                                        'media' => $media,
                                        'scan' => $scan,
                                    ]) }}"
                                    onsubmit="javascript:return confirm('This scan will be deleted')" method="POST">
                                    @csrf
                                    @method('DELETE')

                                    <button title="Remove scan" class="btn position-absolute end-0 pe-2 pt-2">
                                        <i class="fas fa-trash fa-fw text-danger" aria-hidden="true"></i>
                                    </button>

                                    <img class="img-fluid border border-dark mb-1" src="{{ $scan->url }}">
                                </form>
                                <form
                                    action="{{ route('admin.games.releases.medias.scans.update', [
                                        'game' => $release->game,
                                        'release' => $release,
                                        'media' => $media,
                                        'scan' => $scan,
                                    ]) }}"
                                    method="POST">
                                    @csrf
                                    @method('PUT')


                                    <div class="input-group">
                                        <select class="form-select form-select-sm" name="type">
                                            @foreach ($mediaScanTypes as $type)
                                                <option value="{{ $type->getKey() }}"
                                                    @if (old('type', $scan->type?->getKey()) === $type->getKey()) selected @endif>
                                                    {{ $type->name }}</option>
                                            @endforeach
                                        </select>
                                        <button class="btn btn-outline-success">Update</button>
                                    </div>

                                </form>
                            </div>
                        @endforeach
                    @else
                        <p class="w-100 m-3 text-center text-muted">No scans for this media.</p>
                    @endif
                </div>
            </div>
        </div>

        <hr>

        <div class="row">
            <h3>Dumps</h3>
            <div class="row mb-4">
                <form
                    action="{{ route('admin.games.releases.medias.dumps.store', [
                        'game' => $release->game,
                        'release' => $release,
                        'media' => $media,
                    ]) }}"
                    onsubmit="javascript:alert('Processing large dumps may take time, please be patient.')"
                    method="POST">
                    @csrf
                    <input type="file" name="file[]" multiple class="filepond"
                        data-filepond-button="[data-upload-dump='{{ $media->getKey() }}']">
                    <button class="btn btn-success" data-upload-dump="{{ $media->getKey() }}" disabled>
                        Upload all files
                    </button>
                    <span class="ms-2 text-muted">
                        The following dump formats are accepted: <strong>{{ join(', ', \App\Models\Dump::FORMATS) }}</strong>.
                        You can also upload a ZIP containing one or more dumps.
                        <i class="fas fa-exclamation-triangle"></i> Only dumps are supported, not ZIPs with individual files.
                    </span>
                </form>
            </div>
            @if ($media->dumps->isNotEmpty())
                <div class="col">
                    <table class="table table-sm table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Format</th>
                                <th>Date added</th>
                                <th>By</th>
                                <th>Size</th>
                                <th>Notes</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($media->dumps as $dump)
                                <tr>
                                    <td>
                                        {{ $dump->format }}
                                        @if ($dump->track_picture_url)
                                            <a class="lightbox-link ms-2" href="{{ $dump->track_picture_url }}" target="_blank">
                                                <img src="{{ $dump->track_picture_url }}" style="height: 2rem;" class="border border-secondary" alt="Track analysis picture">
                                            </a>
                                        @endif
                                    </td>
                                    <td>{{ $dump->date->toDayDateTimeString() }}</td>
                                    <td>{{ Helper::user($dump->user) }}</td>
                                    <td>{{ Helper::fileSize($dump->size) }}</td>
                                    <td>
                                        <form method="POST"
                                            action="{{ route('admin.games.releases.medias.dumps.update', [
                                                'game' => $release->game,
                                                'release' => $release,
                                                'media' => $media,
                                                'dump' => $dump,
                                            ]) }}">
                                            @csrf
                                            @method('PUT')

                                            <div class="input-group">
                                                <textarea class="form-control" name="info">{{ $dump->info }}</textarea>
                                                <button class="btn btn-outline-success">Update</button>
                                            </div>
                                        </form>
                                    </td>
                                    <td class="ps-3">
                                        <a href="{{ $dump->download_url }}" download="{{ $dump->download_filename }}"
                                            class="d-inline-block">
                                            <i class="fas fa-download"></i>
                                        </a>
                                        <form method="POST" class="d-inline"
                                            action="{{ route('admin.games.releases.medias.dumps.destroy', [
                                                'game' => $release->game,
                                                'release' => $release,
                                                'media' => $media,
                                                'dump' => $dump,
                                            ]) }}"
                                            onsubmit="javascript:return confirm('This item will be permanently deleted')">
                                            @csrf
                                            @method('DELETE')

                                            <button title="Delete dump" class="btn">
                                                <i class="fas fa-trash fa-fw text-danger" aria-hidden="true"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        <tbody>
                    </table>
                </div>
            @else
                <p class="w-100 m-3 text-center text-muted">No dumps for this media.</p>
            @endif
        </div>
    </div>
</div>
