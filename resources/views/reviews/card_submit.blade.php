<div class="card bg-dark mb-4">
    <div class="card-header text-center">
        <h2 class="text-uppercase">{{ $game->game_name }}</h2>
    </div>
    <div class="card-body p-2 bg-darklight">
        @auth
            <form method="post" action="{{ route('reviews.submit', ['game' => $game]) }}">
                @csrf
                <input type="hidden" name="game" value="{{ $game->game_id }}">

                <ul class="nav nav-pills mb-2">
                    <li class="nav-item">
                        <a class="nav-link active" data-bs-toggle="tab" href="#edit">Edit</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#preview">Preview</a>
                    </li>
                </ul>

                <div class="tab-content pt-2">
                    <div class="tab-pane fade show active" id="edit" role="tabpanel">
                        <fieldset>
                            <legend>Review text</legend>
                            <div class="mb-2">
                                <button type="button" class="btn btn-primary me-2" data-bbcode-target="#text" data-bbcode-tag="b">B</button>
                                <button type="button" class="btn btn-primary me-2" data-bbcode-target="#text" data-bbcode-tag="u">U</button>
                                <button type="button" class="btn btn-primary me-2" data-bbcode-target="#text" data-bbcode-tag="i">I</button>
                                <button type="button" class="btn btn-primary me-2" data-bbcode-target="#text" data-bbcode-tag='url="http://example.org/"'>URL</button>
                            </div>
                            <div class="mb-4">
                                <textarea class="form-control" rows="20" id="text" name="text" placeholder="Your review here..." required></textarea>
                            </div>
                        </fieldset>

                        <fieldset>
                            <legend>Score</legend>
                                <div class="row mb-3">
                                    <div class="col">
                                        <label for="graphics" class="form-label">Graphics</label>
                                        <input type="number" min="0" max="10" class="form-control previewable" id="graphics" name="graphics" required placeholder="From 0 (worse) to 10 (best)">
                                    </div>
                                    <div class="col">
                                        <label for="sound" class="form-label">Sound</label>
                                        <input type="number" min="0" max="10" class="form-control previewable" id="sound" name="sound" required placeholder="From 0 (worse) to 10 (best)">
                                    </div>
                                </div>
                                <div class="row mb-4">
                                    <div class="col">
                                        <label for="gameplay" class="form-label">Gameplay</label>
                                        <input type="number" min="0" max="10" class="form-control previewable" id="gameplay" name="gameplay" required placeholder="From 0 (worse) to 10 (best)">
                                    </div>
                                    <div class="col">
                                        <label for="overall" class="form-label">Overall</label>
                                        <input type="number" min="0" max="10" class="form-control previewable" id="overall" name="overall" required placeholder="From 0 (worse) to 10 (best)">
                                    </div>
                                </div>
                        </fieldset>

                        @if ($game->screenshots->isNotEmpty())
                            <fieldset class="lightbox-gallery">
                                <legend>Screenshots</legend>
                                @foreach ($game->screenshots->sortBy('screenshot_id') as $screenshot)
                                    <div class="row mb-3">
                                        <div class="col-2">
                                            <a class="lightbox-link" href="{{ $screenshot->getUrl('game') }}">
                                                <img class="w-100" src="{{ $screenshot->getUrl('game') }}" alt="Game screenshot">
                                            </a>
                                        </div>
                                        <div class="col-10 d-flex">
                                            <input type="text" class="form-control align-self-center previewable" id="screenshot-comment-{{ $screenshot->screenshot_id }}" name="screenshot[]" placeholder="Comment for this screenshot">
                                        </div>
                                    </div>
                                @endforeach
                            </fieldset>
                        @endif

                        <div class="text-center">
                            <button type="submit" class="btn btn-primary mt-3">Submit</button>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="preview" role="tabpanel">

                        <h5>Written by {{ Helper::user(Auth::user()) }}</h5>
                        <span class="text-muted">{{ date('F j, Y') }}</span>


                        <div class="row g-0">

                            <div class="col-9">
                                <div id="preview-text" class="mt-3 mb-3"></div>

                                <hr>
                                <h5>Score</h5>

                                <ul class="list-unstyled ps-2">
                                    <li>Graphics: <span id="preview-graphics"></span></li>
                                    <li>Sound: <span id="preview-sound"></span></li>
                                    <li>Gameplay: <span id="preview-gameplay"></span></li>
                                    <li>Overall: <span id="preview-overall"></span></li>
                                </ul>
                            </div>

                            <div class="col-3 ps-2 text-center text-muted lightbox-gallery">
                                @foreach ($game->screenshots->sortBy('screenshot_id') as $screenshot)
                                    <div class="bg-dark p-2">
                                        <a class="lightbox-link" href="{{ $screenshot->getUrl('game') }}">
                                            <img class="w-100 mb-2" src="{{ $screenshot->getUrl('game') }}">
                                        </a>
                                        <p class="pb-5 mb-0" id="preview-screenshot-comment-{{ $screenshot->screenshot_id }}"></p>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                    </div>
                </div>
            </form>
        @endauth
    </div>
</div>
