<div class="card mb-3 bg-light">
    <div class="card-body">

        <h2 class="card-title fs-4">
            @if (isset($news))
                {{ $news->news_headline }}
            @else
                Create news
            @endif
        </h2>

        <form action="{{ isset($news) ? route('admin.news.news.update', $news) : route('admin.news.news.store') }}" method="post"
            enctype="multipart/form-data">
            @csrf
            @isset($news)
                @method('PUT')
            @endisset

            <div class="row">
                <div class="col-12 col-md-6">
                    <div class="mb-3">
                        <label for="headline" class="form-label">Headline</label>
                        <input type="text" required class="form-control @error('headline') is-invalid @enderror" name="headline"
                            id="headline" value="{{ old('headline', $news->news_headline ?? '') }}">

                        @error('headline')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="author" class="form-label">Author</label>
                        <input class="autocomplete form-control @error('author') is-invalid @enderror"
                            name="author_name" id="author_name" type="search" required
                            data-autocomplete-endpoint="{{ route('admin.ajax.users') }}"
                            data-autocomplete-key="userid" data-autocomplete-id="user_id"
                            data-autocomplete-companion="author" value="{{ old('author_name', isset($news) ? $news->user?->userid : Auth::user()->userid ) }}"
                            placeholder="Type a user name..." autocomplete="off">
                        <input type="hidden" name="author" value="{{ old('author', isset($news) ? $news->user?->user_id : Auth::user()->user_id) }}">

                        @error('author')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="date" class="form-label">Date</label>
                        <input type="date" required class="form-control @error('date') is-invalid @enderror" name="date"
                            id="date" value="{{ old('date', isset($news)  ? $news->news_date?->toDateString() : \Carbon\Carbon::now()->toDateString() ) }}">

                        @error('date')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>
                </div>
                <div class="col-12 col-md-6">
                    <div class="text-center">
                        <img class="p-1 border border-dark shadow-sm" style="max-height: 7rem"
                            src="{{ isset($news) ? $news->news_image ?? asset('images/image-placeholder.png') : asset('images/image-placeholder.png') }}" alt="News image">

                        @if (isset($news?->news_image))
                            <button class="btn btn-link" type="button"
                                onclick="if (confirm('The image will be deleted')) document.getElementById('delete-image').submit();">
                                <i class="fas fa-trash-alt text-danger"></i>
                            </button>
                        @endif
                    </div>

                    <div class="mb-3">
                        <label for="image" class="form-label">Image</label>
                        <input type="file" class="form-control @error('image') is-invalid @enderror" name="image">
                        @if (isset($news) && $news->news_image)
                            <div class="form-text">Select a file to replace the current image.</div>
                        @endif

                        @error('image')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>

                </div>
            </div>

            <div class="mb-3">
                <label for="text" class="form-label">Text</label>
                <textarea class="form-control sceditor" id="text" name="text" required
                    {{-- Legacy CPANEL was inserting <br /> for new lines, so we replace them with actual newlines --}}
                    rows="10">{{ old('text', isset($news) ? Str::replace('<br />', "\n", $news->news_text) : '') }}</textarea>
            </div>

            <button type="submit" class="btn btn-success">Save</button>
            <a href="{{ route('admin.news.news.index') }}" class="btn btn-link">Cancel</a>

        </form>

        @if (isset($news))
            <form action="{{ route('admin.news.news.image', $news) }}" method="post" id="delete-image">
                @csrf
                @method('DELETE')
            </form>
        @endif
    </div>
</div>
