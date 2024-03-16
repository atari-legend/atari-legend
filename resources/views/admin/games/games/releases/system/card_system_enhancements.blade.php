<div class="card bg-light mb-3">
    <div class="card-body">
        <div class="row">
            <div class="col">
                <h3>System Enhancements</h3>

                <form class="mb-4 row row-cols-lg-auto"
                    action="{{ route('admin.games.releases.system-enhancement.store', ['game' => $game, 'release' => $release]) }}"
                    method="POST">
                    @csrf

                    <div class="col">
                        <label for="system" class="form-label">System</label>
                        <select class="form-select @error('system') is-invalid @enderror" id="system" name="system">
                            @foreach ($systems as $system)
                                <option value="{{ $system->id }}">
                                    {{ $system->name }}
                                </option>
                            @endforeach
                        </select>

                        @error('system')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>
                    <div class="col">
                        <label for="enhancement" class="form-label">Enhancement</label>
                        <select class="form-select @error('enhancement') is-invalid @enderror" id="enhancement"
                            name="enhancement">
                            <option value="">-</option>
                            @foreach ($systemEnhancements as $enhancement)
                                <option value="{{ $enhancement->id }}">
                                    {{ $enhancement->name }}
                                </option>
                            @endforeach
                        </select>

                        @error('enhancement')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="add">&nbsp;</label>
                        <button type="submit" class="btn btn-success w-100" id="add">Add enhancement</button>
                    </div>
                </form>


                @if ($release->systemEnhanced->isNotEmpty())
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>System</th>
                                <th>Enhancement</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($release->systemEnhanced as $enhancement)
                                <tr>
                                    <td>{{ $enhancement->system->name }}</td>
                                    <td>{{ $enhancement->enhancement?->name ?? '-' }}</td>
                                    <td>
                                        <form
                                            action="{{ route('admin.games.releases.system-enhancement.destroy', ['game' => $game, 'release' => $release, 'enhancement' => $enhancement]) }}"
                                            method="POST"
                                            onsubmit="javascript:return confirm('This item will be permanently deleted')">
                                            @csrf
                                            @method('DELETE')
                                            <button title="Delete enhancement" class="btn btn-sm">
                                                <i class="fas fa-trash fa-fw text-danger" aria-hidden="true"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <p class="text-center text-muted">No enhancement for this release.</p>
                @endif

            </div>
        </div>
    </div>
</div>
