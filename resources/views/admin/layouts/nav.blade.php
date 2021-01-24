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
                        <li><a>Add</a></li>
                        <li><a>Edit</a></li>
                        <li><a class="@activeroute('admin.games.issues')" href="{{ route('admin.games.issues') }}">Issues</a></li>
                        <li><a>Configuration</a></li>
                        <li><a>Series</a></li>
                        <li><a>Submissions</a></li>
                        <li><a>Individuals</a></li>
                        <li><a>Companies</a></li>
                        <li><a>Company Logos</a></li>
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
                        <li><a>Add</a></li>
                        <li><a>Edit</a></li>
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
                        <li><a>Add</a></li>
                        <li><a>Edit</a></li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed p-1 py-2 text-muted border-0 rounded-0" type="button" data-bs-toggle="collapse" data-bs-target="#news" aria-expanded="false" aria-controls="news">
                    <i class="far fa-newspaper fa-fw me-1"></i> News
                </button>
            </h2>
            <div id="news" class="accordion-collapse collapse border-0">
                <div class="accordion-body">
                    <ul class="list-unstyled mb-2">
                        <li><a>Add</a></li>
                        <li><a>Edit</a></li>
                        <li><a>Approve</a></li>
                        <li><a>Images</a></li>
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
                        <li><a>Add</a></li>
                        <li><a>Edit</a></li>
                        <li><a>Categories</a></li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed p-1 py-2 text-muted border-0 rounded-0" type="button" data-bs-toggle="collapse" data-bs-target="#articles" aria-expanded="false" aria-controls="articles">
                    <i class="far fa-file-alt fa-fw me-1"></i> Articles
                </button>
            </h2>
            <div id="articles" class="accordion-collapse collapse border-0">
                <div class="accordion-body">
                    <ul class="list-unstyled mb-2">
                        <li><a>Add</a></li>
                        <li><a>Edit</a></li>
                        <li><a>Types</a></li>
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
                    </ul>
                </div>
            </div>
        </div>

        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed p-1 py-2 text-muted border-0 rounded-0" type="button" data-bs-toggle="collapse" data-bs-target="#users" aria-expanded="false" aria-controls="users">
                    <i class="fas fa-user-friends fa-fw me-1"></i> Users
                </button>
            </h2>
            <div id="users" class="accordion-collapse collapse border-0">
                <div class="accordion-body">
                    <ul class="list-unstyled mb-2">
                        <li><a>Edit</a></li>
                        <li><a>Comments</a></li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed p-1 py-2 text-muted border-0 rounded-0" type="button" data-bs-toggle="collapse" data-bs-target="#other" aria-expanded="false" aria-controls="other">
                    <i class="fas fa-tools fa-fw me-1"></i> Other
                </button>
            </h2>
            <div id="other" class="accordion-collapse collapse border-0">
                <div class="accordion-body">
                    <ul class="list-unstyled mb-2">
                        <li><a>Statistics</a></li>
                        <li><a>Changelog</a></li>
                        <li><a>Did You Know</a></li>
                        <li><a>Trivia Quotes</a></li>
                        <li><a>Spotlight</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

</nav>
