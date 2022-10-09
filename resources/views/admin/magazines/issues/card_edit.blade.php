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
                    <div class="mb-3 row row-cols-2">
                        <div class="col">
                            <label for="issue" class="form-label">Issue Number</label>
                            <input type="number" class="form-control @error('issue') is-invalid @enderror"
                                name="issue" id="issue" value="{{ old('issue', $issue->issue ?? '') }}">

                            @error('issue')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>
                        <div class="col">
                            <label for="label" class="form-label">Label</label>
                            <input type="text" class="form-control @error('label') is-invalid @enderror"
                                placeholder="e.g. 'Volume 1 Issue 1'"
                                name="label" id="label" value="{{ old('label', $issue->label ?? '') }}">
                            <div class="form-text">
                                Leave blank for regular issues with a number.
                            </div>
                            @error('label')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="archiveorg_url" class="form-label">Archive.org URL</label>
                        <input type="url" class="form-control @error('archiveorg_url') is-invalid @enderror"
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
                        <label for="alternate_url" class="form-label">Alternate URL</label>
                        <input type="url" class="form-control @error('alternate_url') is-invalid @enderror"
                            placeholder="e.g. 'https://www.example.org/mags/st-format-01.pdf'" name="alternate_url"
                            id="alternate_url" value="{{ old('alternate_url', $issue->alternate_url ?? '') }}">
                        <div class="form-text">
                            If the issue is not available on archive.org and you cannot upload it there, indicate
                            another URL to read the magazine. Preferably a direct link to a PDF.
                        </div>
                        @error('alternate_url')
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
                        <div class="form-text">
                                Use 1st of month if the specific day is unknown.
                        </div>
                        @error('published')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>
                    <div class="mb-3 row row-cols-2">
                        <div class="col">
                            <label for="page_count" class="form-label">Page count</label>
                            <input type="number" class="form-control @error('page_count') is-invalid @enderror"
                                name="page_count" id="page_count"
                                value="{{ old('page_count', isset($issue) ? $issue->page_count ?? '' : '') }}">
                            @error('page_count')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>
                        <div class="col">
                            <label for="circulation" class="form-label">Copies sold</label>
                            <input type="number" class="form-control @error('circulation') is-invalid @enderror"
                                name="circulation" id="circulation"
                                value="{{ old('circulation', isset($issue) ? $issue->circulation ?? '' : '') }}">
                            @error('circulation')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-6">
                    <div class="text-center">
                        <img class="p-1 border border-dark shadow-sm bg-black" style="max-height: 15rem"
                            src="{{ isset($issue) ? $issue->image : asset('images/no-cover.svg') }}"
                            alt="Issue cover" id="issue-cover">

                            <button class="btn btn-link" type="button" id="destroy-image-button">
                                <i class="fas fa-trash-alt text-danger"></i>
                            </button>
                    </div>

                    <input type="hidden" id="useArchiveOrgCover" name="useArchiveOrgCover" value="">
                    <input type="hidden" id="destroyImage" name="destroyImage" value="">
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


            <button type="submit" class="btn btn-success" name="stay" value="true">Save</button>
            <button type="submit" class="btn btn-primary">Save & Close</button>
            <a href="{{ route('admin.magazines.magazines.edit', $magazine) }}" class="btn btn-link">Cancel</a>

        </form>

    </div>
</div>
