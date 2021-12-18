<div class="card mb-3 bg-light">
    <div class="card-body">

        <h2 class="card-title fs-4">{{ $individual->ind_name }}</h2>

        <form action="{{ route('admin.games.individuals.update', $individual) }}" method="post"
            enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="row">
                <div class="col-12 col-md-6">
                    <div class="mb-3">
                        <label for="name" class="form-label">Name</label>
                        <input type="text" required class="form-control @error('name') is-invalid @enderror" name="name"
                            id="name" value="{{ old('name', $individual->ind_name) }}">

                        @error('name')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control @error('email') is-invalid @enderror" name="email"
                            id="email" value="{{ old('email', $individual->text->ind_email) }}">

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
                            src="{{ $individual->avatar ?? asset('images/unknown.jpg') }}" alt="Individual avatar">

                        <button class="btn btn-link" type="button"
                            onclick="document.getElementById('delete-avatar').submit();">
                            <i class="fas fa-trash-alt text-danger"></i>
                        </button>
                    </div>

                    <div class="mb-3">
                        <label for="avatar" class="form-label">Avatar</label>
                        <input type="file" class="form-control @error('avatar') is-invalid @enderror" name="avatar">
                        @if ($individual->avatar)
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
                <textarea class="form-control" id="profile" name="profile"
                    rows="5">{{ old('profile', $individual->text->ind_profile) }}</textarea>
            </div>

            <button type="submit" class="btn btn-success">Save</button>
            <a href="{{ route('admin.games.individuals.index') }}" class="btn btn-link">Cancel</a>

        </form>

        <form action="{{ route('admin.games.individuals.avatar', $individual) }}" method="post" id="delete-avatar"
            onsubmit="javascript:return confirm('The avatar will be deleted')">
            @csrf
            @method('DELETE')
        </form>
    </div>
</div>
