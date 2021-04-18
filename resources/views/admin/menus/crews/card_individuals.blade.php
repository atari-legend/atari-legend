
<div class="card mb-3 bg-light">
    <div class="card-body">
        <h2 class="card-title fs-4">Members</h2>

        <form action="{{ route('admin.menus.crews.addIndividual', $crew) }}" method="post">
            @csrf
            <div class="row mb-3">
                <label for="individual_name" class="form-label">Add member</label>
                <div class="col-9">
                    <input class="autocomplete form-control @error('individual') is-invalid @enderror"
                        name="individual_name" id="individual_name" type="search" required
                        data-autocomplete-endpoint="{{ route('ajax.individuals') }}"
                        data-autocomplete-key="ind_name" data-autocomplete-id="ind_id"
                        data-autocomplete-companion="individual" value="{{ old('individual_name') }}"
                        placeholder="Type an individual name..." autocomplete="off">
                    <input type="hidden" name="individual" value="{{ old('individual') }}">
                    <span class="form-text">Add & edit individuals in the <a href="{{ config('al.legacy.base_url').'/admin/individuals/individuals_main.php' }}">Legacy CPANEL</a>.</span>

                    @error('individual')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>
                <div class="col-3">
                    <button class="btn btn-success w-100" type="submit">Add member</button>
                </div>
            </div>
        </form>

        @if ($crew->individuals->isNotEmpty())
            <table class="table table-striped table-hover table-sm">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($crew->individuals as $individual)
                        <tr>
                            <td>
                                <a href="{{ config('al.legacy.base_url').'/admin/individuals/individuals_edit.php?ind_id='.$individual->ind_id }}">{{ $individual->ind_name }}</a>
                                @if ($individual->nicknames->isNotEmpty())
                                    <span class="text-muted ms-1">(aka. {{ $individual->nick_list->join(', ') }})</span>
                                @endif
                            </td>
                            <td>
                                <form action="{{ route('admin.menus.crews.removeIndividual', ['crew' => $crew, 'individual' => $individual]) }}"
                                    method="POST"
                                    onsubmit="javascript:return confirm('This item will be permanently deleted')">
                                    @csrf
                                    @method('DELETE')
                                    <button title="Delete individual '{{ $individual->ind_name }}'" class="btn btn-sm">
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
