<nav>
    <a class="d-block py-1 text-decoration-none ps-1 @activeroute('admin.home.index')" href="{{ route('admin.home.index') }}">
        <i class="fas fa-home fa-fw"></i> Home
    </a>
    <div class="accordion">
        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button @collapsedroute('admin.games.*') p-1 py-2 text-muted border-0 rounded-0" type="button" data-bs-toggle="collapse" data-bs-target="#games" aria-expanded="true" aria-controls="games">
                    <i class="fas fa-gamepad fa-fw me-1"></i> Games
                </button>
            </h2>
            <div id="games" class="accordion-collapse collapse @showroute('admin.games.*') border-0">
                <div class="accordion-body">
                    <ul class="list-unstyled mb-2">
                        <li>
                            <a class="@activeroute('admin.games.games.*')" href="{{ route('admin.games.games.index') }}">Games</a>
                            @isset ($game)
                                : <strong>{{ $game->game_name }}</strong>
                                @include('admin.games.games.nav')
                            @endif
                        </li>
                        <li><a class="@activeroute('admin.games.issues')" href="{{ route('admin.games.issues') }}">Issues</a></li>
                        <li><a class="@activeroute('admin.games.configuration.*')" href="{{ route('admin.games.configuration.index') }}">Configuration</a></li>
                        <li><a class="@activeroute('admin.games.series.*')" href="{{ route('admin.games.series.index') }}">Series</a></li>
                        <li><a class="@activeroute('admin.games.submissions.*')" href="{{ route('admin.games.submissions.index') }}">Submissions</a></li>
                        <li><a class="@activeroute('admin.games.individuals.*')" href="{{ route('admin.games.individuals.index') }}">Individuals</a></li>
                        <li><a class="@activeroute('admin.games.companies.*')" href="{{ route('admin.games.companies.index') }}">Companies</a></li>
                        <li><a class="@activeroute('admin.games.music')" href="{{ route('admin.games.music') }}">Music</a></li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed p-1 py-2 text-muted border-0 rounded-0" type="button" data-bs-toggle="collapse" data-bs-target="#reviews" aria-expanded="false" aria-controls="reviews">
                    <i class="fas fa-check-double fa-fw me-1"></i> Reviews
                </button>
            </h2>
            <div id="reviews" class="accordion-collapse collapse border-0">
                <div class="accordion-body">
                    <ul class="list-unstyled mb-2">
                        <li><a>Reviews</a></li>
                        <li><a>Submissions</a></li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed p-1 py-2 text-muted border-0 rounded-0" type="button" data-bs-toggle="collapse" data-bs-target="#interviews" aria-expanded="false" aria-controls="interviews">
                    <i class="fas fa-microphone fa-fw me-1"></i> Interviews
                </button>
            </h2>
            <div id="interviews" class="accordion-collapse collapse border-0">
                <div class="accordion-body">
                    <ul class="list-unstyled mb-2">
                        <li><a>Interviews</a></li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button @collapsedroute('admin.news.*') collapsed p-1 py-2 text-muted border-0 rounded-0" type="button" data-bs-toggle="collapse" data-bs-target="#news" aria-expanded="false" aria-controls="news">
                    <i class="far fa-newspaper fa-fw me-1"></i> News
                </button>
            </h2>
            <div id="news" class="accordion-collapse collapse @showroute('admin.news.*') border-0">
                <div class="accordion-body">
                    <ul class="list-unstyled mb-2">
                        <li><a class="@activeroute('admin.news.news.*')" href="{{ route('admin.news.news.index') }}">News</a></li>
                        <li><a class="@activeroute('admin.news.submissions.*')" href="{{ route('admin.news.submissions.index') }}">Submissions</a></li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed p-1 py-2 text-muted border-0 rounded-0" type="button" data-bs-toggle="collapse" data-bs-target="#links" aria-expanded="false" aria-controls="links">
                    <i class="fas fa-link fa-fw me-1"></i> Links
                </button>
            </h2>
            <div id="links" class="accordion-collapse collapse border-0">
                <div class="accordion-body">
                    <ul class="list-unstyled mb-2">
                        <li><a>Links</a></li>
                        <li><a>Categories</a></li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button @collapsedroute('admin.articles.*') p-1 py-2 text-muted border-0 rounded-0" type="button" data-bs-toggle="collapse" data-bs-target="#articles" aria-expanded="false" aria-controls="articles">
                    <i class="far fa-file-alt fa-fw me-1"></i> Articles
                </button>
            </h2>
            <div id="articles" class="accordion-collapse collapse @showroute('admin.articles.*') border-0">
                <div class="accordion-body">
                    <ul class="list-unstyled mb-2">
                        <li><a class="@activeroute('admin.articles.articles.*')" href="{{ route('admin.articles.articles.index') }}">Articles</a></li>
                        <li><a class="@activeroute('admin.articles.types.*')" href="{{ route('admin.articles.types.index') }}">Types</a></li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button @collapsedroute('admin.menus.*') p-1 py-2 text-muted border-0 rounded-0" type="button" data-bs-toggle="collapse" data-bs-target="#menus" aria-expanded="false" aria-controls="menus">
                    <i class="fas fa-skull-crossbones fa-fw me-1"></i> Menus
                </button>
            </h2>
            <div id="menus" class="accordion-collapse collapse @showroute('admin.menus.*') border-0">
                <div class="accordion-body">
                    <ul class="list-unstyled mb-2">
                        <li><a class="@activeroute('admin.menus.sets.*')" href="{{ route('admin.menus.sets.index') }}">Sets</a></li>
                        <li><a class="@activeroute('admin.menus.conditions.*')" href="{{ route('admin.menus.conditions.index') }}">Conditions</a></li>
                        <li><a class="@activeroute('admin.menus.content-types.*')" href="{{ route('admin.menus.content-types.index') }}">Content Types</a></li>
                        <li><a class="@activeroute('admin.menus.software.*')" href="{{ route('admin.menus.software.index') }}">Software</a></li>
                        <li><a class="@activeroute('admin.menus.crews.*')" href="{{ route('admin.menus.crews.index') }}">Crews</a></li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button @collapsedroute('admin.magazines.*') p-1 py-2 text-muted border-0 rounded-0" type="button" data-bs-toggle="collapse" data-bs-target="#magazines" aria-expanded="false" aria-controls="magazines">
                    <i class="fas fa-newspaper fa-fw me-1"></i> Magazines
                </button>
            </h2>
            <div id="magazines" class="accordion-collapse collapse @showroute('admin.magazines.*') border-0">
                <div class="accordion-body">
                    <ul class="list-unstyled mb-2">
                        <li><a class="@activeroute('admin.magazines.magazines.*')" href="{{ route('admin.magazines.magazines.index') }}">Magazines</a></li>
                        <li><a class="@activeroute('admin.magazines.index-types.*')" href="{{ route('admin.magazines.index-types.index') }}">Index types</a></li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button @collapsedroute('admin.users.*') p-1 py-2 text-muted border-0 rounded-0" type="button" data-bs-toggle="collapse" data-bs-target="#users" aria-expanded="false" aria-controls="users">
                    <i class="fas fa-user-friends fa-fw me-1"></i> Users
                </button>
            </h2>
            <div id="users" class="accordion-collapse collapse @showroute('admin.users.*') border-0">
                <div class="accordion-body">
                    <ul class="list-unstyled mb-2">
                        <li><a class="@activeroute('admin.users.users.*')" href="{{ route('admin.users.users.index') }}">Users</a></li>
                        <li><a class="@activeroute('admin.users.comments.*')" href="{{ route('admin.users.comments.index') }}">Comments</a></li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button @collapsedroute('admin.others.*') p-1 py-2 text-muted border-0 rounded-0" type="button" data-bs-toggle="collapse" data-bs-target="#other" aria-expanded="false" aria-controls="other">
                    <i class="fas fa-tools fa-fw me-1"></i> Other
                </button>
            </h2>
            <div id="other" class="accordion-collapse collapse @showroute('admin.others.*') border-0">
                <div class="accordion-body">
                    <ul class="list-unstyled mb-2">
                        <li><a>Statistics</a></li>
                        <li><a>Changelog</a></li>
                        <li><a class="@activeroute('admin.others.trivias.*')" href="{{ route('admin.others.trivias.index') }}">Did You Know?</a></li>
                        <li><a class="@activeroute('admin.others.quotes.*')" href="{{ route('admin.others.quotes.index') }}">Quotes</a></li>
                        <li><a class="@activeroute('admin.others.spotlights.*')" href="{{ route('admin.others.spotlights.index') }}">Spotlights</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

</nav>
