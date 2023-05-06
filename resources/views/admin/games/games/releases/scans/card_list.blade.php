<div class="card bg-light mb-3">
    <div class="card-body">
        <h2 class="card-title fs-4">{{ $release->boxscans->count() }} scans</h2>

        <div class="row row-cols-4 align-items-end">
            @forelse ($release->boxscans as $scan)
                <div class="col">
                    <form class="text-center mb-3"
                        action="{{ route('admin.games.releases.scans.destroy', ['game' => $release->game, 'release' => $release, 'scan' => $scan]) }}"
                        method="POST" onsubmit="javascript:return confirm('This scan will be deleted')">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-sm btn-danger"><i class="fas fa-trash fa-fw"></i> Delete</button>
                    </form>
                    <form class="text-center"
                        action="{{ route('admin.games.releases.scans.update', ['game' => $release->game, 'release' => $release, 'scan' => $scan]) }}"
                        method="POST">
                        @csrf
                        @method('PUT')

                        <img class="w-50 border border-dark mb-3" src="{{ $scan->url }}">

                        <select class="form-select mb-1" name="type">
                            @foreach (\App\Models\ReleaseScan::TYPES as $type)
                                <option value="{{ $type }}" @if ($scan->type === $type) selected @endif>
                                    {{ $type }}
                                </option>
                            @endforeach
                        </select>
                        <input class="form-control mb-1" placeholder="Notes (e.g. 'Publisher catalog')" value="{{ $scan->notes }}">
                        <button class="btn btn-success">Update</button>
                    </form>

                </div>

            @empty
                <p class="card-text text-center text-muted">
                    No scans for the release.
                </p>
            @endforelse
        </div>
    </div>
</div>
