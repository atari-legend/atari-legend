<div class="card mb-3 bg-light">
    <div class="card-body">
        <h2 class="card-title fs-4">Genealogy</h2>

        <form action="{{ route('admin.menus.crews.addSubCrew', $crew) }}" method="post">
            @csrf
            <div class="row mb-3">
                <label for="subcrew_name" class="form-label">Add sub-crew</label>
                <div class="input-group">
                    <input class="autocomplete form-control @error('subcrew') is-invalid @enderror"
                        name="subcrew_name" id="subcrew_name" type="search" required
                        data-autocomplete-endpoint="{{ route('ajax.crews') }}"
                        data-autocomplete-key="crew_name" data-autocomplete-id="crew_id"
                        data-autocomplete-companion="subcrew" value="{{ old('subcrew_name') }}"
                        placeholder="Type a crew name..." autocomplete="off">
                    <input type="hidden" name="subcrew" value="{{ old('subcrew') }}">

                    <button class="btn btn-success" type="submit">Add sub-crew</button>

                    @error('subcrew')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>
            </div>
        </form>

        @if ($crew->subCrews->isNotEmpty())
            <table class="table table-striped table-hover table-sm">
                <thead>
                    <tr>
                        <th>Sub-crews</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($crew->subCrews as $subCrew)
                        <tr>
                            <td>
                                <a href="{{ route('admin.menus.crews.edit', $subCrew) }}">
                                    {{ $subCrew->crew_name }}
                                </a>
                            </td>
                            <td>
                                <form action="{{ route('admin.menus.crews.removeSubCrew', ['crew' => $crew, 'subCrew' => $subCrew]) }}"
                                    method="POST"
                                    onsubmit="javascript:return confirm('This item will be permanently deleted')">
                                    @csrf
                                    @method('DELETE')
                                    <button title="Delete sub-crew '{{ $subCrew->crew_name }}'" class="btn btn-sm">
                                        <i class="fas fa-trash fa-fw text-danger" aria-hidden="true"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        @if ($crew->parentCrews->isNotEmpty())
            <table class="table table-striped table-hover table-sm">
                <thead>
                    <tr>
                        <th>Part of</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($crew->parentCrews as $parentCrew)
                        <tr>
                            <td>
                                <a href="{{ route('admin.menus.crews.edit', $parentCrew) }}">
                                    {{ $parentCrew->crew_name }}
                                </a>
                            </td>
                            <td>
                                <form action="{{ route('admin.menus.crews.removeParentCrew', ['crew' => $crew, 'parentCrew' => $parentCrew]) }}"
                                    method="POST"
                                    onsubmit="javascript:return confirm('This item will be permanently deleted')">
                                    @csrf
                                    @method('DELETE')
                                    <button title="Delete parent crew '{{ $parentCrew->crew_name }}'" class="btn btn-sm">
                                        <i class="fas fa-trash fa-fw text-danger" aria-hidden="true"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

    </div>
</div>
