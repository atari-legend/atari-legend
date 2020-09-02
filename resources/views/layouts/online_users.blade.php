@if (request()->attributes->has('onlineUsers'))
    <div class="container-fluid text-center bg-darklight m-0 px-0">
        <p class="my-1 pt-1">Currently {{ request()->attributes->get('onlineUsers')->count() }} registered users online</p>
        <ul class="list-inline my-0 p-1 bg-primary">
            @foreach (request()->attributes->get('onlineUsers') as $user)
                <li class="list-inline-item"><i class="far fa-user"></i> {{ $user->userid }}</li>
            @endforeach
        </ul>
    </div>
@endif

@if (request()->attributes->has('pastDayUsers'))
    <div class="container-fluid text-center bg-darklight mt-0 mb-3 px-0">
        <p class="my-1 pt-1">In the past 24h there were {{ request()->attributes->get('pastDayUsers')->count() }} registered users online</p>
        <ul class="list-inline my-0 p-1 bg-primary">
            @foreach (request()->attributes->get('pastDayUsers') as $user)
                <li class="list-inline-item"><i class="far fa-user"></i> {{ $user->userid }}</li>
            @endforeach
        </ul>
    </div>
@endif
