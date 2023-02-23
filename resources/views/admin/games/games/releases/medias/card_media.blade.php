<div class="card mb-3">
    <div class="card-body">
        <h3>Info</h3>
        <div class="row">
            <form class="col row">
                <div class="col">
                    <label for="type-{{ $media->getKey() }}" class="visually-hidden">Type</label>
                    <select id="type-{{ $media->getKey() }}"
                        class="form-select @error('type') is-invald @endif" name="type">
                        <option value="" @if (old('type', $media->type?->getKey()) === null) selected @endif>-</option>
                        @foreach ($mediaTypes as $type)
                            <option value="{{ $type->getKey() }}" @if (old('type', $media->type?->getKey()) === $type->getKey()) selected @endif>{{ $type->name }}</option>
                        @endforeach
                    </select>
                    <div class="form-text">Media type</div>

                    @error('type')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>
                <div class="col">
                        <label for="label-{{ $media->getKey() }}" class="visually-hidden">Label</label>
                        <input id="label-{{ $media->getKey() }}" name="label" id="label"
                            class="form-control @error('label') is-invald @endif" placeholder="e.g. 'Disk A'"
                        value="{{ old('label', $media->label) }}">
                    <div class="form-text">Media label</div>

                    @error('label')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>
                <div class="col">
                        <button class="btn btn-success">Save</button>
                </div>
            </form>
            <div class="col-2 text-end">
                <button class="btn btn-danger ms-2">Delete media</button>
            </div>
        </div>
        <hr>
        <h3>Scans</h3>
        <div class="row row-cols-4">
            @if ($media->scans->isNotEmpty())
                @foreach ($media->scans as $scan)
                    <div class="col text-center">
                        <img class="img-fluid border" src="{{ asset('storage/images/media_scans/' . $scan->file) }}">
                        <small class="text-muted">{{ $scan->type?->name }}</small>
                    </div>
                @endforeach
            @else
                <p class="w-100 m-3 text-center text-muted">No scans for this media.</p>
            @endif
        </div>
        <hr>
        <h3>Dumps</h3>
        <div class="row">
            @if ($media->dumps->isNotEmpty())
                <div class="col">
                    <table class="table table-sm table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Format</th>
                                <th>Date added</th>
                                <th>By</th>
                                <th>Notes</th>
                                <th>Size</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($media->dumps as $dump)
                                <tr>
                                    <td>{{ $dump->format }}</td>
                                    <td>{{ $dump->date->toDayDateTimeString() }}</td>
                                    <td>{{ Helper::user($dump->user) }}</td>
                                    <td>{{ $dump->info }}</td>
                                    <td>{{ Helper::fileSize($dump->size) }}</td>
                                    <td>
                                        <a href="{{ asset('storage/zips/games/' . $dump->id . '.zip') }}"
                                            download="{{ $dump->download_filename }}" class="d-inline-block">
                                            <i class="fas fa-download"></i>
                                        </a>
                                        <form method="POST" class="d-inline"
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
