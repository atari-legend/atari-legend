<nav>
    <a class="d-block py-1 text-decoration-none pl-1 @activeroute(admin.home.index)" href="{{ route('admin.home.index') }}">
        <i class="fas fa-home fa-fw"></i> Home
    </a>
    <div class="accordion">
        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed p-1 py-2 text-muted border-0 rounded-0" type="button" data-toggle="collapse" data-target="#games" aria-expanded="fakse" aria-controls="games">
                    <i class="fas fa-gamepad fa-fw mr-1"></i> Games
                </button>
            </h2>
            <div id="games" class="accordion-collapse collapse border-0">
                <div class="accordion-body">
                    <ul class="list-unstyled mb-2">
                        <li><a href="#">Editor</a></li>
                        <li><a href="#">Configuration</a></li>
                        <li><a href="#">Series</a></li>
                        <li><a href="#">Submission</a></li>
                        <li><a href="#">Individuals</a></li>
                        <li><a href="#">Companies</a></li>
                        <li><a href="#">Company Logos</a></li>
                        <li><a href="#">Interviews</a></li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed p-1 py-2 text-muted border-0 rounded-0" type="button" data-toggle="collapse" data-target="#reviews" aria-expanded="false" aria-controls="reviews">
                    <i class="fas fa-check-double fa-fw mr-1"></i> Reviews
                </button>
            </h2>
            <div id="reviews" class="accordion-collapse collapse border-0">
                <div class="accordion-body">
                    <ul class="list-unstyled mb-2">
                        <li><a href="#">Add</a></li>
                        <li><a href="#">Edit</a></li>
                        <li><a href="#">Submissions</a></li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed p-1 py-2 text-muted border-0 rounded-0" type="button" data-toggle="collapse" data-target="#interviews" aria-expanded="false" aria-controls="interviews">
                    <i class="fas fa-microphone fa-fw mr-1"></i> Interviews
                </button>
            </h2>
            <div id="interviews" class="accordion-collapse collapse border-0">
                <div class="accordion-body">
                    <ul class="list-unstyled mb-2">
                        <li><a href="#">Add</a></li>
                        <li><a href="#">Edit</a></li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed p-1 py-2 text-muted border-0 rounded-0" type="button" data-toggle="collapse" data-target="#news" aria-expanded="false" aria-controls="news">
                    <i class="far fa-newspaper fa-fw mr-1"></i> News
                </button>
            </h2>
            <div id="news" class="accordion-collapse collapse border-0">
                <div class="accordion-body">
                    <ul class="list-unstyled mb-2">
                        <li><a href="#">Add</a></li>
                        <li><a href="#">Edit</a></li>
                        <li><a href="#">Approve</a></li>
                        <li><a href="#">Images</a></li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed p-1 py-2 text-muted border-0 rounded-0" type="button" data-toggle="collapse" data-target="#links" aria-expanded="false" aria-controls="links">
                    <i class="fas fa-link fa-fw mr-1"></i> Links
                </button>
            </h2>
            <div id="links" class="accordion-collapse collapse border-0">
                <div class="accordion-body">
                    <ul class="list-unstyled mb-2">
                        <li><a href="#">Add</a></li>
                        <li><a href="#">Edit</a></li>
                        <li><a href="#">Categories</a></li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed p-1 py-2 text-muted border-0 rounded-0" type="button" data-toggle="collapse" data-target="#articles" aria-expanded="false" aria-controls="articles">
                    <i class="far fa-file-alt fa-fw mr-1"></i> Articles
                </button>
            </h2>
            <div id="links" class="accordion-collapse collapse border-0">
                <div class="accordion-body">
                    <ul class="list-unstyled mb-2">
                        <li><a href="#">Add</a></li>
                        <li><a href="#">Edit</a></li>
                        <li><a href="#">Types</a></li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed p-1 py-2 text-muted border-0 rounded-0" type="button" data-toggle="collapse" data-target="#users" aria-expanded="false" aria-controls="users">
                    <i class="fas fa-user-friends fa-fw mr-1"></i> Users
                </button>
            </h2>
            <div id="users" class="accordion-collapse collapse border-0">
                <div class="accordion-body">
                    <ul class="list-unstyled mb-2">
                        <li><a href="#">Edit</a></li>
                        <li><a href="#">Comments</a></li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed p-1 py-2 text-muted border-0 rounded-0" type="button" data-toggle="collapse" data-target="#other" aria-expanded="false" aria-controls="other">
                    <i class="fas fa-tools fa-fw mr-1"></i> Other
                </button>
            </h2>
            <div id="other" class="accordion-collapse collapse border-0">
                <div class="accordion-body">
                    <ul class="list-unstyled mb-2">
                        <li><a href="#">Statistics</a></li>
                        <li><a href="#">Changelog</a></li>
                        <li><a href="#">Did You Know</a></li>
                        <li><a href="#">Trivia Quotes</a></li>
                        <li><a href="#">Spotlight</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

</nav>
