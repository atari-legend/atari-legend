<div class="card bg-light mb-3">
    <div class="card-body">
        <div class="row">
            <div class="col">
                <h3>Memory Enhancements</h3>

                <form class="mb-4 row row-cols-lg-auto"
                    action="{{ route('admin.games.releases.system-memory-enhancement.store', ['game' => $game, 'release' => $release]) }}"
                    method="POST">
                    @csrf

                    <div class="col">
                        <label for="memory" class="form-label">Memory</label>
                        <select class="form-select @error('memory') is-invalid @enderror" id="memory" name="memory">
                            @foreach ($memories as $memory)
                                <option value="{{ $memory->id }}">
                                    {{ $memory->name }}
                                </option>
                            @endforeach
                        </select>

                        @error('memory')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>
                    <div class="col">
                        <label for="memory_enhancement" class="form-label">Enhancement</label>
                        <select class="form-select @error('memory_enhancement') is-invalid @enderror" id="memory_enhancement"
                            name="memory_enhancement">
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
                        <button type="submit" class="btn btn-success w-100" id="add">Add memory
                            enhancement</button>
                    </div>
                </form>

                @if ($release->memoryEnhanced->isNotEmpty())
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Memory</th>
                                <th>Enhancement</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($release->memoryEnhanced as $enhancement)
                                <tr>
                                    <td>{{ $enhancement->memory->name }}</td>
                                    <td>{{ $enhancement->enhancement?->name ?? '-' }}</td>
                                    <td>
                                        <form
                                            action="{{ route('admin.games.releases.system-memory-enhancement.destroy', ['game' => $game, 'release' => $release, 'enhancement' => $enhancement]) }}"
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
                    <p class="text-center text-muted">No memory enhancement for this release.</p>
                @endif

            </div>
        </div>
    </div>
</div>
