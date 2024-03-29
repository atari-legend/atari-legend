<nav class="navbar navbar-expand-xl navbar-light bg-primary mt-2 mb-3 p-0">
    <div class="container-fluid">
        <a class="navbar-brand px-2" href="{{ route('home.index') }}"><i title="Home" class="fas fa-home"></i></a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbar" aria-label="Main menu">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbar">
            <ul class="navbar-nav navbar-main w-100">
                <li class="nav-item flex-fill text-center p-0">
                    <a class="text-dark nav-link py-3 px-2 {{ Request::routeIs('news.*') ? 'active' : '' }}" href="{{ route('news.index') }}">News</a>
                </li>
                <li class="nav-item flex-fill text-center p-0">
                    <a class="text-dark nav-link py-3 px-2 {{ Request::routeIs('games.*') ? 'active' : '' }}" href="{{ route('games.index') }}">Games</a>
                </li>
                <li class="nav-item flex-fill text-center p-0">
                    <a class="text-dark nav-link py-3 px-2 {{ Request::routeIs('menus.*') ? 'active' : '' }}" href="{{ route('menus.index') }}">Menus</a>
                </li>
                <li class="nav-item flex-fill text-center p-0">
                    <a class="text-dark nav-link py-3 px-2 {{ Request::routeIs('reviews.*') ? 'active' : '' }}" href="{{ route('reviews.index') }}">Reviews</a>
                </li>
                <li class="nav-item flex-fill text-center p-0">
                    <a class="text-dark nav-link py-3 px-2 {{ Request::routeIs('interviews.*') ? 'active' : '' }}" href="{{ route('interviews.index') }}">Interviews</a>
                </li>
                <li class="nav-item flex-fill text-center p-0">
                    <a class="text-dark nav-link py-3 px-2 {{ Request::routeIs('articles.*') ? 'active' : '' }}" href="{{ route('articles.index') }}">Articles</a>
                </li>
                <li class="nav-item flex-fill text-center p-0">
                    <a class="text-dark nav-link py-3 px-2 {{ Request::routeIs('links.*') ? 'active' : '' }}" href="{{ route('links.index') }}">Links</a>
                </li>
                <li class="nav-item flex-fill text-center p-0">
                    <a class="text-dark nav-link py-3 px-2 {{ Request::routeIs('magazines.*') ? 'active' : '' }}" href="{{ route('magazines.index') }}">Mags</a>
                </li>
                <li class="nav-item flex-fill text-center p-0">
                    <a class="text-dark nav-link py-3 px-2" href="https://www.atari-forum.com/">Forum</a>
                </li>
                <li class="nav-item flex-fill text-center p-0">
                    <a class="text-dark nav-link py-3 px-2 {{ Request::routeIs('about.*') ? 'active' : '' }}" href="{{ route('about.index') }}">About</a>
                </li>
            </ul>
            <form class="search d-flex d-xl-none d-xxl-flex ps-2 py-0    justify-content-center" method="get" action="{{ route('games.search') }}">
                <div class="position-relative">
                    <input class="autocomplete form-control bg-black" name="title" type="search"
                        data-autocomplete-endpoint="{{ route('ajax.games-and-software') }}"
                        data-autocomplete-key="name" data-autocomplete-follow-url="true"
                        placeholder="Search" aria-label="Search" autocomplete="off" required>
                </div>
                <button class="btn" type="submit"><i class="fas fa-search"></i></button>
            </form>
            <ul class="navbar-nav navbar-profile">
                @auth
                <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                    @csrf
                </form>
                <li class="nav-item dropdown ps-2 text-center">
                    <a class="text-dark nav-link dropdown-toggle py-3 @if (Auth::user()->avatar_ext) ps-0 @endif" href="#" id="user-menu" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        @if (Auth::user()->avatar_ext)
                            <img id="avatar" class="rounded-circle border border-dark me-1" src="{{ asset('storage/images/user_avatars/'.Auth::user()->user_id.'.'.Auth::user()->avatar_ext) }}" alt="User avatar">
                        @endif
                        {{ Auth::user()->userid }}
                    </a>

                    <ul class="dropdown-menu dropdown-menu-dark dropdown-menu-end" aria-labelledby="user-menu">
                        <li><a class="dropdown-item" href="{{ route('auth.profile') }}">Profile</a></li>
                        @contributor
                            <li>
                                <a class="dropdown-item text-contributor fw-bold" href="{{ config('al.legacy.base_url').'/admin/' }}">Legacy Control panel</a>
                            </li>
                            <li>
                                <a class="dropdown-item text-contributor fw-bold" href="{{ route('admin.home.index') }}">New Control panel</a>
                            </li>
                        @endcontributor
                        <li class="mt-3">
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
                    <li class="nav-item d-flex justify-content-center p-0">
                        @if (Route::has('register'))
                            <a class="text-dark nav-link py-2 px-2 ps-3 {{ Request::routeIs('register') ? 'active' : '' }}" href="{{ route('register') }}" title="Register">
                                <i class="fas fa-user-plus"></i>
                            </a>
                        @endif
                        <a class="text-dark nav-link py-2 px-2 {{ Request::routeIs('login') ? 'active' : '' }}" href="{{ route('login') }}" title="Log in">
                            <i class="fas fa-sign-in-alt"></i>
                        </a>
                    </li>
                @endguest
            </ul>
        </div>
    </div>
</nav>
