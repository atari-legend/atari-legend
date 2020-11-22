<div class="text-center">
    <h1 class="fs-4 mb-3">AtariLegend</h1>
    @if (Auth::user()->avatar !== null)
        <img class="avatar rounded-circle border border-dark" src="{{ Auth::user()->avatar }}" alt="User avatar">
    @endif
    <div class="text-muted mb-2">{{ Auth::user()->userid }}</div>

    <div class="">
        <a href="{{ route('home.index') }}">Main site</a><br>
        <a href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
            Log out
        </a>

        <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
            @csrf
        </form>
    </div>
</div>
