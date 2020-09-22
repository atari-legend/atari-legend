<nav class="navbar navbar-expand-md navbar-light bg-primary mb-4 p-0">
    <div class="container-fluid pr-0">
        <a class="navbar-brand" href="{{ route('home.index') }}"><i class="fas fa-home"></i></a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbar">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbar">
            <ul class="navbar-nav navbar-main mr-auto">
                <li class="nav-item p-0">
                    <a class="text-dark nav-link py-3 px-2 px-lg-3 {{ Request::routeIs('news.*') ? 'active' : '' }}" href="{{ route('news.index') }}">News</a>
                </li>
                <li class="nav-item p-0">
                    <a class="text-dark nav-link py-3 px-2 px-lg-3 {{ Request::routeIs('games.*') ? 'active' : '' }}" href="{{ route('games.index') }}">Games</a>
                </li>
                <li class="nav-item p-0">
                    <a class="text-dark nav-link py-3 px-2 px-lg-3 {{ Request::routeIs('reviews.*') ? 'active' : '' }}" href="{{ route('reviews.index') }}">Reviews</a>
                </li>
                <li class="nav-item p-0">
                    <a class="text-dark nav-link py-3 px-2 px-lg-3 {{ Request::routeIs('interviews.*') ? 'active' : '' }}" href="{{ route('interviews.index') }}">Interviews</a>
                </li>
                <li class="nav-item p-0">
                    <a class="text-dark nav-link py-3 px-2 px-lg-3 {{ Request::routeIs('articles.*') ? 'active' : '' }}" href="{{ route('articles.index') }}">Articles</a>
                </li>
                <li class="nav-item p-0">
                    <a class="text-dark nav-link py-3 px-2 px-lg-3 {{ Request::routeIs('links.*') ? 'active' : '' }}" href="{{ route('links.index') }}">Links</a>
                </li>
                <li class="nav-item p-0">
                    <a class="text-dark nav-link py-3 px-2 px-lg-3 {{ Request::routeIs('about.*') ? 'active' : '' }}" href="{{ route('about.index') }}">About</a>
                </li>
            </ul>
            <form class="d-flex search" method="get" action="{{ route('games.search') }}">
                <input class="form-control bg-black" name="title" type="search" placeholder="Search" aria-label="Search" required>
                <button class="btn" type="submit"><i class="fas fa-search"></i></button>
            </form>
            <ul class="navbar-nav navbar-profile">
                @auth
                <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                    @csrf
                </form>
                <li class="nav-item dropdown p-0">
                    <a class="text-dark nav-link dropdown-toggle py-3 px-2 px-lg-3" href="#" id="user-menu" role="button" data-toggle="dropdown" aria-expanded="false">
                        @if (Auth::user()->avatar_ext)
                            <img class="rounded-circle mr-1" height="30" src="{{ asset('storage/images/user_avatars/'.Auth::user()->user_id.'.'.Auth::user()->avatar_ext) }}" alt="User avatar">
                        @endif
                        {{ Auth::user()->userid }}
                    </a>
                    <ul class="dropdown-menu dropdown-menu-right" aria-labelledby="user-menu">
                        <li><a class="dropdown-item" href="#">Profile</a></li>
                        <li>
                            <a class="dropdown-item"
                                href="{{ route('logout') }}"
                                onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                Log out
                            </a>
                        </li>
                    </ul>
                </li>
                @endauth
                @guest
                    @if (Route::has('register'))
                    <li class="nav-item p-0">
                        <a class="text-dark nav-link py-3 {{ Request::routeIs('register') ? 'active' : '' }}" href="{{ route('register') }}" title="Register">
                            <i class="fas fa-user-plus"></i>
                        </a>
                    </li>
                    @endif
                    <li class="nav-item p-0">
                        <a class="text-dark nav-link py-3 {{ Request::routeIs('login') ? 'active' : '' }}" href="{{ route('login') }}" title="Log in">
                            <i class="fas fa-sign-in-alt"></i>
                        </a>
                    </li>
                @endguest
            </ul>
        </div>
    </div>
</nav>
