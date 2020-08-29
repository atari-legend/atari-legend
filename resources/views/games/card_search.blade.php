<div class="card bg-dark mb-4">
    <div class="card-header text-center">
        <h2 class="text-uppercase">Games</h2>
    </div>
    <div class="card-body p-2">
        <p class="card-text">
            Welcome to the game section. The place to be for every Atari ST and retro
            gaming nutcase. Enjoy your stay! Search for a game by using any of the
            functionalities below. Combinations are allowed. There are currently {{ $gamesCount }}
            games in the database. For more statistics of the db, click
            <!-- FIXME --><a href="#">here</a>.
            if there is data missing and you are willing to contribute, please get in touch with
            the team.
        </p>
    </div>
    <div class="card-body p-2">
        <form method="get" action="games/search">
            <div class="row mb-3">
                <label for="titleAZ" class="col-sm-2 col-form-label">Title (A-Z)</label>
                <div class="col-sm-10">
                    <select class="form-select" id="titleAZ" name="titleAZ">
                        <option selected>-</option>
                        <option value="0-9">0-9</option>
                        @foreach (range('A', 'Z') as $letter)
                            <option value="{{ $letter }}">{{ $letter }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="row mb-3">
                <label for="title" class="col-sm-2 col-form-label">Title</label>
                <div class="col-sm-10">
                    <input type="text" class="form-control" id="title" name="title">
                </div>
            </div>
            <div class="row mb-3">
                <label for="publisher" class="col-sm-2 col-form-label">Publisher</label>
                <div class="col-sm-10">
                    <input type="text" class="form-control" id="publisher" name="publisher">
                </div>
            </div>
            <div class="row mb-3">
                <label for="developer" class="col-sm-2 col-form-label">Developer</label>
                <div class="col-sm-10">
                    <input type="text" class="form-control" id="developer" name="developer">
                </div>
            </div>
            <div class="row mb-3">
                <label for="year" class="col-sm-2 col-form-label">Release year</label>
                <div class="col-sm-10">
                    <input type="text" class="form-control" id="year" name="year">
                </div>
            </div>
            <div class="row mb-3">
                <label for="genre" class="col-sm-2 col-form-label">Genre</label>
                <div class="col-sm-10">
                    <input type="text" class="form-control" id="genre" name="genre">
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-sm-2 col-form-label"></div>
                <div class="col-sm-10">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="download">
                        <label class="form-check-label" for="download">
                            Download
                        </label>
                    </div>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-sm-2 col-form-label"></div>
                <div class="col-sm-10">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="screenshot" name="screenshot">
                        <label class="form-check-label" for="screenshot">
                            Screenshot
                        </label>
                    </div>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-sm-2 col-form-label"></div>
                <div class="col-sm-10">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="boxscan" name="boxscan">
                        <label class="form-check-label" for="boxscan">
                            Boxscan
                        </label>
                    </div>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-sm-2 col-form-label"></div>
                <div class="col-sm-10">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="review" name="review">
                        <label class="form-check-label" for="review">
                            Review
                        </label>
                    </div>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-sm-2 col-form-label"></div>
                <div class="col-sm-10">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="export">
                        <label class="form-check-label" for="export">
                            Export mode
                        </label>
                    </div>
                </div>
            </div>
            <div class="text-center">
                <button type="submit" class="btn btn-primary">Search</button>
            </div>
        </form>
    </div>
</div>
