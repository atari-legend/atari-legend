<div class="card mb-3 bg-light">
    <div class="card-body">

        <h2 class="card-title fs-4">Nicknames</h2>

        <div class="mb-3">
            @foreach ($individual->nicknames as $nick)
                <form action="{{ route('admin.games.individuals.nickname.destroy', ['individual' => $individual, 'nickname' => $nick]) }}" method="post">
                    @csrf
                    @method('DELETE')
                    <a class="me-1" href="javascript:;"
                        onclick="javascript:if (confirm('The nickname will be deleted')) this.parentElement.submit();">
                        <i class="fas fa-trash-alt text-danger"></i></a>

                    {{ $nick->ind_name }}
                    @if ($nickDuplicates[$nick->ind_id]->count())
                        <span class="text-warning ms-2">
                            <i class="fa-solid fa-triangle-exclamation"></i>
                            Possible nick {{ Str::plural('duplicate', $nickDuplicates[$nick->ind_id]) }} with:
                        </span>
                        @foreach ($nickDuplicates[$nick->ind_id] as $duplicate)
                            <a href="{{ route('admin.games.individuals.edit', $duplicate) }}">
                                {{ $duplicate->ind_name}}
                                @if ($duplicate->aka_list->count())({{ $duplicate->aka_list->join(', ') }})@endif
                            </a>@if (!$loop->last),@endif
                        @endforeach
                    @endif

                </form>
            @endforeach

            <form action="{{ route('admin.games.individuals.nickname.store', $individual) }}" method="post">
                @csrf

                <div class="form-text mt-2">Add a new nickname</div>
                <div class="input-group">
                    <input type="text" required class="form-control @error('nickname') is-invalid @enderror"
                        name="nickname" id="nickname" value="{{ old('nickname') }}">
                    <button class="btn btn-success" type="submit">Add nickname</button>

                    @error('nickname')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>
            </form>
        </div>


    </div>
</div>
