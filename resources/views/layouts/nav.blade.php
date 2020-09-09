<nav class="navbar navbar-expand-md navbar-light bg-primary mb-4">
    <div class="container-fluid">
        <a class="navbar-brand" href="{{ route('home.index') }}"><i class="fas fa-home"></i></a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbar">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbar">
            <ul class="navbar-nav mr-auto">
                <li class="nav-item">
                    <a class="nav-link {{ Request::routeIs('news.*') ? 'active' : '' }}" href="{{ route('news.index') }}">News</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ Request::routeIs('games.*') ? 'active' : '' }}" href="{{ route('games.index') }}">Games</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ Request::routeIs('reviews.*') ? 'active' : '' }}" href="{{ route('reviews.index') }}">Reviews</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ Request::routeIs('interviews.*') ? 'active' : '' }}" href="{{ route('interviews.index') }}">Interviews</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ Request::routeIs('articles.*') ? 'active' : '' }}" href="{{ route('articles.index') }}">Articles</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ Request::routeIs('links.*') ? 'active' : '' }}" href="{{ route('links.index') }}">Links</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ Request::routeIs('about.*') ? 'active' : '' }}" href="{{ route('about.index') }}">About</a>
                </li>
            </ul>
            <form class="d-flex mr-3">
                <input class="form-control mr-2" type="search" placeholder="Search" aria-label="Search">
                <button class="btn btn-dark" type="submit"><i class="fas fa-search"></i></button>
            </form>
            <ul class="navbar-nav">
                @auth
                <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                    @csrf
                </form>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="user-menu" role="button" data-toggle="dropdown" aria-expanded="false">
                        @if (Auth::user()->avatar_ext)
                            <img class="rounded-circle mr-1" height="30" src="{{ asset('storage/images/user_avatars/'.Auth::user()->user_id.'.'.Auth::user()->avatar_ext) }}">
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
                    <li class="nav-item">
                        <a class="text-dark nav-link {{ Request::routeIs('login') ? 'active' : '' }}" href="{{ route('login') }}">Log-in</a>
                    </li>
                    @if (Route::has('register'))
                    <li class="nav-item">
                        <a class="text-dark nav-link {{ Request::routeIs('register') ? 'active' : '' }}" href="{{ route('register') }}">Register</a>
                    </li>
                    @endif
                @endguest
            </ul>
        </div>
    </div>
</nav>
