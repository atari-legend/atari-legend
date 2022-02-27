<div class="card mb-3 bg-light">
    <div class="card-body">

        <h2 class="card-title fs-4">
            @if (isset($article))
                {{ $article->texts->first()->article_title }}
            @else
                Create article
            @endif
        </h2>

        <form
            action="{{ isset($article) ? route('admin.articles.articles.update', $article) : route('admin.articles.articles.store') }}"
            method="post">
            @csrf
            @isset($article)
                @method('PUT')
            @endisset

            <div class="row">
                <div class="col-12 col-md-6">
                    <div class="mb-3">
                        <label for="title" class="form-label">Title</label>
                        <input type="text" required class="form-control @error('title') is-invalid @enderror"
                            name="title" id="title"
                            value="{{ old('title', isset($article) ? $article->texts->first()->article_title : '') }}">

                        @error('title')
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
                            data-autocomplete-companion="author"
                            value="{{ old('author_name', isset($article) ? $article->user?->userid : Auth::user()->userid) }}"
                            placeholder="Type a user name..." autocomplete="off">
                        <input type="hidden" name="author"
                            value="{{ old('author', isset($article) ? $article->user?->user_id : Auth::user()->user_id) }}">

                        @error('author')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="date" class="form-label">Date</label>
                        <input type="date" required class="form-control @error('date') is-invalid @enderror" name="date"
                            id="date"
                            value="{{ old('date',isset($article) ? $article->texts->first()->article_date?->toDateString() : \Carbon\Carbon::now()->toDateString()) }}">

                        @error('date')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>
                </div>
                <div class="col-12 col-md-6">
                    <div class="mb-3">
                        <label for="type" class="form-label">Type</label>
                        <select class="form-select @error('type') is-invalid @enderror" name="type" id="type">
                            <option value="">-</option>
                            @foreach ($types as $t)
                                <option value="{{ $t->getKey() }}" @selected($t->getKey() == old('type',
                                    isset($article) ? $article->type?->getKey() : ''))>{{ $t->article_type }}
                                </option>
                            @endforeach
                        </select>

                        @error('type')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Draft</label>
                        <div class="form-check form-switch">
                            <input class="form-check-input @error('draft') is-invalid @enderror" type="checkbox" role="switch"
                                name="draft" id="draft" @checked(old('draft', isset($article) ? $article->draft : false)) value="true">
                            <label class="form-check-label" for="draft">If enabled, the article will not appear on the
                                main site</label>

                            @error('draft')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                            </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col">
                    <div class="mb-3">
                        <label for="intro" class="form-label">Intro</label>
                        <textarea class="form-control sceditor @error('intro') is-invalid @enderror" id="intro" name="intro" required
                            {{-- Legacy CPANEL was inserting <br /> for new lines, so we replace them with actual newlines --}}
                            rows="7">{{ old('intro', isset($article) ? Str::replace('<br />', "\n", $article->texts->first()->article_intro) : '') }}</textarea>

                        @error('intro')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col">
                    <div class="mb-3">
                        <label for="text" class="form-label">Text</label>
                        <textarea class="form-control sceditor @error('text') is-invalid @enderror" id="text" name="text" required
                            {{-- Legacy CPANEL was inserting <br /> for new lines, so we replace them with actual newlines --}}
                            rows="30">{{ old('text', isset($article) ? Str::replace('<br />', "\n", $article->texts->first()->article_text) : '') }}</textarea>

                        @error('intro')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror

                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-success">Save</button>
            <a href="{{ route('admin.articles.articles.index') }}" class="btn btn-link">Cancel</a>

        </form>

    </div>
</div>
