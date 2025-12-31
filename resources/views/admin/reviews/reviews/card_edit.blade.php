<div class="card mb-3 bg-light">
    <div class="card-body">

        <h2 class="card-title fs-4">
            @if (isset($review))
                {{ $review->games[0]->game_name }}
            @else
                Create review
            @endif
        </h2>

        <form
            action="{{ isset($review) ? route('admin.reviews.reviews.update', $review) : route('admin.reviews.reviews.store') }}"
            method="post">
            @csrf
            @isset($review)
                @method('PUT')
            @endisset

            <div class="row">
                <div class="col-12 col-md-6">
                    <div class="mb-3">
                        <label for="author" class="form-label">Author</label>
                        <input class="autocomplete form-control @error('author') is-invalid @enderror"
                            name="author_name" id="author_name" type="search" required
                            data-autocomplete-endpoint="{{ route('admin.ajax.users') }}"
                            data-autocomplete-key="userid" data-autocomplete-id="user_id"
                            data-autocomplete-companion="author"
                            value="{{ old('author_name', isset($review) ? $review->user?->userid : Auth::user()->userid) }}"
                            placeholder="Type a user name..." autocomplete="off">
                        <input type="hidden" name="author"
                            value="{{ old('author', isset($review) ? $review->user?->user_id : Auth::user()->user_id) }}">

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
                            value="{{ old('date',isset($review) ? $review->review_date?->toDateString() : \Carbon\Carbon::now()->toDateString()) }}">

                        @error('date')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>
                </div>
                <div class="col-12 col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Draft</label>
                        <div class="form-check form-switch">
                            <input class="form-check-input @error('draft') is-invalid @enderror" type="checkbox" role="switch"
                                name="draft" id="draft" @checked(old('draft', isset($review) ? $review->draft : false)) value="true">
                            <label class="form-check-label" for="draft">If enabled, the review will not appear on the
                                main site</label>

                            @error('draft')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Submission</label>
                        <div class="form-check form-switch">
                            <input class="form-check-input @error('submission') is-invalid @enderror" type="checkbox" role="switch"
                                name="submission" id="submission" @checked(old('submission', isset($review) ? $review->review_edit : false)) value="true">
                            <label class="form-check-label" for="submission">If enabled, the review will be considered a user submission
                                and will not appear on the main site unless approved</label>

                            @error('submission')
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
                        <label for="text" class="form-label">Text</label>
                        <textarea class="form-control sceditor @error('text') is-invalid @enderror" id="text" name="text" required
                            {{-- Legacy CPANEL was inserting <br /> for new lines, so we replace them with actual newlines --}}
                            rows="30">{{ old('text', isset($review) ? Str::replace('<br />', "\n", $review->review_text) : '') }}</textarea>

                        @error('text')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror

                    </div>
                </div>
            </div>

            <fieldset>
                <legend>Score</legend>
                    <div class="row mb-3">
                        <div class="col">
                            <label for="graphics" class="form-label">Graphics</label>
                            <input type="number" value="{{ old('graphics', isset($review) ? $review->score?->review_graphics : 0) }}" min="0" max="10" class="form-control @error('graphics') is-invalid @enderror" id="graphics" name="graphics" required placeholder="From 0 (worse) to 10 (best)">
                            @error('graphics')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>
                        <div class="col">
                            <label for="sound" class="form-label">Sound</label>
                            <input type="number" value="{{ old('sound', isset($review) ? $review->score?->review_sound : 0) }}" min="0" max="10" class="form-control @error('sound') is-invalid @enderror" id="sound" name="sound" required placeholder="From 0 (worse) to 10 (best)">
                            @error('sound')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col">
                            <label for="gameplay" class="form-label">Gameplay</label>
                            <input type="number" value="{{ old('gameplay', isset($review) ? $review->score?->review_gameplay : 0) }}" min="0" max="10" class="form-control @error('gameplay') is-invalid @enderror" id="gameplay" name="gameplay" required placeholder="From 0 (worse) to 10 (best)">
                            @error('gameplay')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>
                        <div class="col">
                            <label for="overall" class="form-label">Overall</label>
                            <input type="number" value="{{ old('overall', isset($review) ? $review->score?->review_overall : 0) }}" min="0" max="10" class="form-control @error('overall') is-invalid @enderror" id="overall" name="overall" required placeholder="From 0 (worse) to 10 (best)">
                            @error('overall')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>
                    </div>
            </fieldset>

            @if ($review->games[0]->screenshots->isNotEmpty())
                <fieldset class="lightbox-gallery">
                    <legend>Screenshots</legend>
                    @foreach ($review->games[0]->screenshots->sortBy('screenshot_id') as $screenshot)
                        <div class="row mb-3">
                            <div class="col-2">
                                <img class="w-100" src="{{ $screenshot->getUrlRoute('game', $review->games[0]) }}" alt="Game screenshot">
                            </div>
                            <div class="col-10">
                                <input type="text" class="form-control @error('screenshot_comment_'.$screenshot->getKey()) is-invalid @enderror" id="screenshot-comment-{{ $screenshot->screenshot_id }}"
                                    value="{{ old('screenshot_comment_'.$screenshot->getKey(), isset($review) ? $review->getScreenshotComment($screenshot->getKey())?->pivot?->comment?->comment_text : '') }}"
                                    name="screenshot_comment_{{ $screenshot->getKey() }}" placeholder="Comment for this screenshot">

                                @error('screenshot_comment_'.$screenshot->getKey())
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>
                    @endforeach
                </fieldset>
            @endif

            <button type="submit" class="btn btn-success">Save</button>
            <a href="{{ route('admin.reviews.'.(old('submission', isset($review) ? $review->review_edit : false) ? 'submissions' : 'reviews').'.index') }}" class="btn btn-link">Cancel</a>

        </form>

    </div>
</div>
