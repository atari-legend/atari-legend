<div class="card mb-3 bg-light">
    <div class="card-body">

        <h2 class="card-title fs-4">
            @if (isset($link))
                {{ $link->website_name }}
            @else
                Create link
            @endif
        </h2>

        <form action="{{ isset($link) ? route('admin.links.links.update', $link) : route('admin.links.links.store') }}"
            method="post" enctype="multipart/form-data">
            @csrf
            @isset($link)
                @method('PUT')
            @endisset

            <div class="row">
                <div class="col-12 col-md-8">
                    <div class="mb-3">
                        <label for="name" class="form-label">Name</label>
                        <input type="text" required class="form-control @error('name') is-invalid @enderror"
                            name="name" id="name" value="{{ old('name', $link->website_name ?? '') }}">

                        @error('name')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="url" class="form-label">URL</label>
                        <input type="url" required class="form-control @error('url') is-invalid @enderror"
                            name="url" id="url" value="{{ old('url', $link->website_url ?? '') }}"
                            placeholder="https://...">

                        @error('url')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control @error('description') is-invalid @enderror"
                            name="description" id="description"
                            rows="4">{{ old('description', $link->description ?? '') }}</textarea>

                        @error('description')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Categories</label>
                        @php
                            $selectedCategories = old('categories', isset($link) ? $link->categories->pluck('website_category_id')->map(fn($id) => strval($id))->all() : []);
                        @endphp
                        <div class="row">
                            @foreach ($categories as $category)
                                <div class="col-12 col-sm-6 col-md-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox"
                                            name="categories[]"
                                            value="{{ $category->website_category_id }}"
                                            id="cat_{{ $category->website_category_id }}"
                                            @checked(in_array(strval($category->website_category_id), $selectedCategories))>
                                        <label class="form-check-label" for="cat_{{ $category->website_category_id }}">
                                            {{ $category->website_category_name }}
                                        </label>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        @error('categories')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="inactive" value="1"
                                id="inactive" @checked(old('inactive', ($link->inactive ?? false)))>
                            <label class="form-check-label" for="inactive">
                                Inactive
                            </label>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-4">
                    <div class="text-center mb-3">
                        <img class="p-1 border border-dark shadow-sm mw-100" style="max-height: 10rem"
                            src="{{ isset($link) && $link->file ? asset('storage/' . $link->path) : asset('images/image-placeholder.png') }}" alt="Website screenshot">

                        @if (isset($link) && $link->file)
                            <button class="btn btn-link" type="button"
                                onclick="if (confirm('The screenshot will be deleted')) document.getElementById('delete-image').submit();">
                                <i class="fas fa-trash-alt text-danger"></i>
                            </button>
                        @endif
                    </div>

                    <div class="mb-3">
                        <label for="image" class="form-label">Screenshot</label>
                        <input type="file" class="form-control @error('image') is-invalid @enderror" name="image" id="image" accept="image/*">
                        @if (isset($link) && $link->file)
                            <div class="form-text">Select a file to replace the current screenshot.</div>
                        @endif

                        @error('image')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>
                </div>
            </div>

            <button type="submit" name="stay" value="true" class="btn btn-success">
                <i class="fas fa-save fa-fw"></i> Save
            </button>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save fa-fw"></i> Save &amp; Close
            </button>
            <a href="{{ route('admin.links.links.index') }}" class="btn btn-link">Cancel</a>

        </form>

        @if (isset($link))
            <form action="{{ route('admin.links.links.image', $link) }}" method="post" id="delete-image">
                @csrf
                @method('DELETE')
            </form>
        @endif
    </div>
</div>
