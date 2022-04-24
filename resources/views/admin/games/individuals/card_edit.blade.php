<div class="card mb-3 bg-light">
    <div class="card-body">

        <h2 class="card-title fs-4">
            @if (isset($individual))
                {{ $individual->ind_name }}
            @else
                Create new individual
            @endif
        </h2>

        <form action="{{ isset($individual) ? route('admin.games.individuals.update', $individual) : route('admin.games.individuals.store') }}" method="post"
            enctype="multipart/form-data">
            @csrf
            @isset($individual)
                @method('PUT')
            @endisset

            <div class="row">
                <div class="col-12 col-md-6">
                    <div class="mb-3">
                        <label for="name" class="form-label">Name</label>
                        <input type="text" required class="form-control @error('name') is-invalid @enderror" name="name"
                            id="name" value="{{ old('name', $individual->ind_name ?? '') }}">

                        @error('name')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror

                        @if (isset($duplicates) && $duplicates->count())
                            <div class="mt-2">
                                <span class="text-warning">
                                    <i class="fa-solid fa-triangle-exclamation"></i>
                                    Possible {{ Str::plural('duplicate', $duplicates) }} with:
                                </span>
                                @foreach ($duplicates as $duplicate)
                                    <a href="{{ route('admin.games.individuals.edit', $duplicate) }}">
                                        {{ $duplicate->ind_name}}
                                        @if ($duplicate->aka_list->count())({{ $duplicate->aka_list->join(', ') }})@endif
                                    </a>@if (!$loop->last),@endif
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control @error('email') is-invalid @enderror" name="email"
                            id="email" value="{{ old('email', $individual->text?->ind_email ?? '') }}">

                        @error('email')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>
                </div>
                <div class="col-12 col-md-6">
                    <div class="text-center">
                        <img class="p-1 border border-dark shadow-sm" style="max-height: 7rem"
                            src="{{ isset($individual) ? $individual->avatar ?? asset('images/unknown.jpg') : asset('images/unknown.jpg') }}" alt="Individual avatar">

                        @if (isset($individual?->avatar))
                            <button class="btn btn-link" type="button"
                                onclick="if (confirm('The avatar will be deleted')) document.getElementById('delete-avatar').submit();">
                                <i class="fas fa-trash-alt text-danger"></i>
                            </button>
                        @endif
                    </div>

                    <div class="mb-3">
                        <label for="avatar" class="form-label">Avatar</label>
                        <input type="file" class="form-control @error('avatar') is-invalid @enderror" name="avatar">
                        @if (isset($individual) && $individual->avatar)
                            <div class="form-text">Select a file to replace the current avatar.</div>
                        @endif

                        @error('avatar')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>

                </div>
            </div>

            <div class="mb-3">
                <label for="profile" class="form-label">Profile</label>
                <textarea class="form-control sceditor" id="profile" name="profile"
                    rows="10">{{ old('profile', $individual->text?->ind_profile ?? '') }}</textarea>
            </div>

            <button type="submit" class="btn btn-success">Save</button>
            <a href="{{ route('admin.games.individuals.index') }}" class="btn btn-link">Cancel</a>

        </form>

        @if (isset($individual))
            <form action="{{ route('admin.games.individuals.avatar.destroy', $individual) }}" method="post" id="delete-avatar">
                @csrf
                @method('DELETE')
            </form>
        @endif
    </div>
</div>
