<div class="card mb-3 bg-light">
    <div class="card-body">

        <h2 class="card-title fs-4">
            @if (isset($company))
                {{ $company->pub_dev_name }}
            @else
                Create new company
            @endif
        </h2>

        <form action="{{ isset($company) ? route('admin.games.companies.update', $company) : route('admin.games.companies.store') }}" method="post"
            enctype="multipart/form-data">
            @csrf
            @isset($company)
                @method('PUT')
            @endisset

            <div class="row">
                <div class="col-12 col-md-6">
                    <div class="mb-3">
                        <label for="name" class="form-label">Name</label>
                        <input type="text" required class="form-control @error('name') is-invalid @enderror" name="name"
                            id="name" value="{{ old('name', $company->pub_dev_name ?? '') }}">

                        @error('name')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>
                </div>
                <div class="col-12 col-md-6">
                    <div class="text-center">
                        <img class="p-1 border border-dark shadow-sm" style="max-height: 7rem"
                            src="{{ isset($company) ? $company->logo ?? asset('images/unknown.jpg') : asset('images/unknown.jpg') }}" alt="Company logo">

                        @if (isset($company?->logo))
                            <button class="btn btn-link" type="button"
                                onclick="if (confirm('The logo will be deleted')) document.getElementById('delete-logo').submit();">
                                <i class="fas fa-trash-alt text-danger"></i>
                            </button>
                        @endif
                    </div>

                    <div class="mb-3">
                        <label for="logo" class="form-label">Logo</label>
                        <input type="file" class="form-control @error('logo') is-invalid @enderror" name="logo">
                        @if (isset($company) && $company->logo)
                            <div class="form-text">Select a file to replace the current logo.</div>
                        @endif

                        @error('logo')
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
                    rows="5">{{ old('profile', $company->text?->pub_dev_profile ?? '') }}</textarea>
            </div>

            <button type="submit" class="btn btn-success">Save</button>
            <a href="{{ route('admin.games.companies.index') }}" class="btn btn-link">Cancel</a>

        </form>

        @if (isset($company))
            <form action="{{ route('admin.games.companies.logo.destroy', $company) }}" method="post" id="delete-logo">
                @csrf
                @method('DELETE')
            </form>
        @endif
    </div>
</div>
