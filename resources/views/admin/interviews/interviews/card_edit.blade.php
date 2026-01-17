<div class="card mb-3 bg-light">
    <div class="card-body">

        <h2 class="card-title fs-4">
            @if (isset($interview))
                {{ $interview->individual->ind_name }}
            @else
                Create interview
            @endif
        </h2>

        <form
            action="{{ isset($interview) ? route('admin.interviews.interviews.update', $interview) : route('admin.interviews.interviews.store') }}"
            method="post">
            @csrf
            @isset($interview)
                @method('PUT')
            @endisset

            @if(!isset($interview))
                <div class="row mb-3">
                    <div class="col-12">
                        <label for="individual" class="form-label">Individual</label>
                        <input class="autocomplete form-control @error('individual') is-invalid @enderror"
                            name="individual_name" id="individual_name" type="search" required
                            data-autocomplete-endpoint="{{ route('ajax.individuals') }}"
                            data-autocomplete-key="ind_name" data-autocomplete-id="ind_id"
                            data-autocomplete-companion="individual"
                            value="{{ old('individual_name') }}"
                            placeholder="Type an individual name..." autocomplete="off">
                        <input type="hidden" name="individual" value="{{ old('individual') }}">

                        @error('individual')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>
                </div>
            @endif

            <div class="row">
                <div class="col-12 col-md-6">
                    <div class="mb-3">
                        <label for="author" class="form-label">Author</label>
                        <input class="autocomplete form-control @error('author') is-invalid @enderror"
                            name="author_name" id="author_name" type="search" required
                            data-autocomplete-endpoint="{{ route('admin.ajax.users') }}"
                            data-autocomplete-key="userid" data-autocomplete-id="user_id"
                            data-autocomplete-companion="author"
                            value="{{ old('author_name', isset($interview) ? $interview->user?->userid : Auth::user()->userid) }}"
                            placeholder="Type a user name..." autocomplete="off">
                        <input type="hidden" name="author"
                            value="{{ old('author', isset($interview) ? $interview->user?->user_id : Auth::user()->user_id) }}">

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
                            value="{{ old('date', isset($interview) ? $interview->texts->first()?->interview_date?->toDateString() : \Carbon\Carbon::now()->toDateString()) }}">

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
                                name="draft" id="draft" @checked(old('draft', isset($interview) ? $interview->draft : false)) value="true">
                            <label class="form-check-label" for="draft">If enabled, the interview will not appear on the
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
                        <label for="intro" class="form-label">Introduction</label>
                        <textarea class="form-control sceditor @error('intro') is-invalid @enderror" id="intro" name="intro"
                            rows="5">{{ old('intro', isset($interview) ? Str::replace('<br />', "\n", $interview->texts->first()?->interview_intro) : '') }}</textarea>

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
                        <label for="chapters" class="form-label">Chapters</label>
                        <textarea class="form-control sceditor @error('chapters') is-invalid @enderror" id="chapters" name="chapters"
                            rows="5">{{ old('chapters', isset($interview) ? Str::replace('<br />', "\n", $interview->texts->first()?->interview_chapters) : '') }}</textarea>
                        <div class="form-text">
                            Use <code>[hotspotUrl=#1]Chapter title[/hotspotUrl]</code> to create links to sections in the interview text.
                        </div>

                        @error('chapters')
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
                        <label for="text" class="form-label">Interview Text</label>
                        <textarea class="form-control sceditor @error('text') is-invalid @enderror" id="text" name="text" required
                            rows="30">{{ old('text', isset($interview) ? Str::replace('<br />', "\n", $interview->texts->first()?->interview_text) : '') }}</textarea>
                        <div class="form-text">
                            Use <code>[hotspot=1]Question text[/hotspot]</code> to mark sections that correspond to chapter links.
                        </div>

                        @error('text')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-success" name="stay" value="true">Save</button>
            <button type="submit" class="btn btn-primary">Save & Close</button>
            <a href="{{ route('admin.interviews.interviews.index') }}" class="btn btn-link">Cancel</a>

        </form>

    </div>
</div>
