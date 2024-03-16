<div class="card bg-light mb-3">
    <div class="card-body">
        <div class="row">
            <div class="col">
                <h3>TOS Incompatibility</h3>

                <form class="mb-4 row row-cols-lg-auto"
                    action="{{ route('admin.games.releases.system-tos-incompatibility.store', ['game' => $game, 'release' => $release]) }}"
                    method="POST">
                    @csrf

                    <div class="col">
                        <label for="tos" class="form-label">TOS</label>
                        <select class="form-select @error('tos') is-invalid @enderror" id="tos" name="tos">
                            @foreach ($tosses as $tos)
                                <option value="{{ $tos->id }}">
                                    {{ $tos->name }}
                                </option>
                            @endforeach
                        </select>

                        @error('tos')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>
                    <div class="col">
                        <label for="language" class="form-label">Language</label>
                        <select class="form-select @error('language') is-invalid @enderror" id="language"
                            name="language">
                            <option value="">-</option>
                            @foreach ($languages as $language)
                                <option value="{{ $language->id }}">
                                    {{ $language->name }}
                                </option>
                            @endforeach
                        </select>

                        @error('language')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="add">&nbsp;</label>
                        <button type="submit" class="btn btn-success w-100" id="add">Add incompatibility</button>
                    </div>
                </form>


                @if ($release->tosIncompatibles->isNotEmpty())
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>TOS</th>
                                <th>Language</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($release->tosIncompatibles as $incompatibility)
                                <tr>
                                    <td>{{ $incompatibility->tos->name }}</td>
                                    <td>{{ $incompatibility->language?->name ?? '-' }}</td>
                                    <td>
                                        <form
                                            action="{{ route('admin.games.releases.system-tos-incompatibility.destroy', ['game' => $game, 'release' => $release, 'incompatibility' => $incompatibility]) }}"
                                            method="POST"
                                            onsubmit="javascript:return confirm('This item will be permanently deleted')">
                                            @csrf
                                            @method('DELETE')
                                            <button title="Delete incompatibility '{{ $incompatibility->tos->name }}'" class="btn btn-sm">
                                                <i class="fas fa-trash fa-fw text-danger" aria-hidden="true"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <p class="text-center text-muted">No TOS incompatibility for this release.</p>
                @endif

            </div>
        </div>
    </div>
</div>
