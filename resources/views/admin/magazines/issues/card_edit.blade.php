<div class="card mb-3 bg-light">
    <div class="card-body">

        <h2 class="card-title fs-4">
            {{ $magazine->name }}
            @if (isset($issue))
                #{{ $issue->issue }}
            @else
                - Create issue
            @endif
        </h2>

        <form
            action="{{ isset($issue) ? route('admin.magazines.issues.update', ['magazine' => $magazine, 'issue' => $issue]) : route('admin.magazines.issues.store', $magazine) }}"
            method="post" enctype="multipart/form-data">
            @csrf
            @isset($issue)
                @method('PUT')
            @endisset

            <div class="row">
                <div class="col-12 col-md-6">
                    <div class="mb-3">
                        <label for="issue" class="form-label">Issue Number</label>
                        <input type="number" required class="form-control @error('name') is-invalid @enderror"
                            name="issue" id="issue" value="{{ old('issue', $issue->issue ?? '') }}">

                        @error('issue')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="archiveorg_url" class="form-label">Archive.org URL</label>
                        <input type="text" class="form-control @error('archiveorg_url') is-invalid @enderror"
                            pattern="https://archive.org/details/[^/]+/"
                            placeholder="e.g. 'https://archive.org/details/.../'" name="archiveorg_url"
                            id="archiveorg_url" value="{{ old('archiveorg_url', $issue->archiveorg_url ?? '') }}">
                        <div class="form-text">
                            For example <code>https://archive.org/details/st-magazine-034/</code> or
                            <code>https://archive.org/details/Atari_ST_User_Issue_105_1994-10_Europress_GB/</code>. Must
                            start with <code>https://archive.org/details/</code> followed by the identifier and a
                            <strong>trailing slash</strong>.
                        </div>
                        @error('archiveorg_url')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="published" class="form-label">Published</label>
                        <input type="date" class="form-control @error('published') is-invalid @enderror"
                            name="published" id="published"
                            value="{{ old('published', isset($issue) ? $issue->published?->toDateString() ?? '' : '') }}">

                        @error('published')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="barcode" class="form-label">Barcode</label>
                        <input type="number" class="form-control @error('name') is-invalid @enderror" name="barcode"
                            id="barcode" value="{{ old('barcode', $issue->barcode ?? '') }}">

                        @error('issue')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>
                </div>
                <div class="col-12 col-md-6">
                    <div class="text-center">
                        <img class="p-1 border border-dark shadow-sm" style="max-height: 15rem"
                            src="{{ isset($issue) ? $issue->image ?? asset('images/image-placeholder.png') : asset('images/image-placeholder.png') }}"
                            alt="Issue cover" id="issue-cover">

                        @if (isset($issue?->image))
                            <button class="btn btn-link" type="button" id="delete-image-button"
                                onclick="if (confirm('The image will be deleted')) document.getElementById('delete-image').submit();">
                                <i class="fas fa-trash-alt text-danger"></i>
                            </button>
                        @endif
                    </div>

                    <div class="mb-3">
                        <label for="image" class="form-label">Image</label>
                        <input type="file" class="form-control @error('image') is-invalid @enderror" name="image" id="image">
                        @if (isset($issue) && $issue->image)
                            <div class="form-text">Select a file to replace the current image.</div>
                        @endif

                        @error('image')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror

                        <button class="btn btn-info mt-3" id="fetch-thumbnail" type="button">Fetch from Archive.org</button>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-success">Save</button>
            <a href="{{ route('admin.magazines.magazines.edit', $magazine) }}" class="btn btn-link">Cancel</a>

        </form>

        @if (isset($issue))
            <form action="{{ route('admin.magazines.issues.image.destroy', ['magazine' => $magazine, 'issue' => $issue]) }}" method="post" id="delete-image">
                @csrf
                @method('DELETE')
            </form>
        @endif
    </div>
</div>
