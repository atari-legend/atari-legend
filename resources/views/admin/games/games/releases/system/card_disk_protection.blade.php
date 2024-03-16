<div class="card bg-light mb-3">
    <div class="card-body">
        <div class="row">
            <div class="col">
                <h3>Disk protection</h3>

                <form class="mb-4 row row-cols-lg-auto"
                    action="{{ route('admin.games.releases.system-disk-protection.store', ['game' => $game, 'release' => $release]) }}"
                    method="POST">
                    @csrf

                    <div class="col">
                        <label for="disk_protection" class="form-label">Disk protection</label>
                        <select class="form-select @error('disk_protection') is-invalid @enderror" id="disk_protection" name="disk_protection">
                            @foreach ($diskProtections as $protection)
                                <option value="{{ $protection->id }}">
                                    {{ $protection->name }}
                                </option>
                            @endforeach
                        </select>

                        @error('disk_protection')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>
                    <div class="col">
                        <label for="disk_protection_notes" class="form-label">Notes</label>
                        <input type="text" name="disk_protection_notes" id="disk_protection_notes" class="form-control @error('disk_protection_notes') is-invalid @enderror">

                        @error('disk_protection_notes')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="add">&nbsp;</label>
                        <button type="submit" class="btn btn-success w-100" id="add">Add disk protection</button>
                    </div>
                </form>


                @if ($release->diskProtections->isNotEmpty())
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Protection</th>
                                <th>Notes</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($release->diskProtections as $protection)
                                <tr>
                                    <td>{{ $protection->name }}</td>
                                    <td>{{ $protection->pivot->notes ?? '-' }}</td>
                                    <td>
                                        <form
                                            action="{{ route('admin.games.releases.system-disk-protection.destroy', ['game' => $game, 'release' => $release, 'protection' => $protection]) }}"
                                            method="POST"
                                            onsubmit="javascript:return confirm('This item will be permanently deleted')">
                                            @csrf
                                            @method('DELETE')
                                            <button title="Delete protection '{{ $protection->name }}'" class="btn btn-sm">
                                                <i class="fas fa-trash fa-fw text-danger" aria-hidden="true"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <p class="text-center text-muted">No copy protection for this release.</p>
                @endif

            </div>
        </div>
    </div>
</div>
