<div class="card bg-dark mb-4">
    <div class="card-body p-0">
        @isset ($triviaQuote)
            <div class="carousel slide carousel-fade" data-bs-ride="carousel">
                <div class="carousel-inner">
                    @foreach ($triviaImages->random(10) as $image)
                        <div class="carousel-item {{ $loop->first ? 'active' : '' }}">
                            <img class="d-block w-100" src="{{ asset('images/'.$image) }}" alt="Large carousel image">

                            <div class="carousel-bg">
                                <h2 class="text-uppercase">{{ $triviaQuote->trivia_quote }}</h2>
                                <h3 class="text-primary mt-5">Your number 1 Atari ST resource on the net!</h3>
                                <p>
                                    Atari Legend is a living and breathing webproject, designed by sceners.
                                    We like to involve as many people as possible to make it fresh and up
                                    to date. We offer a nostalgic trip down the Atari ST memory lane,
                                    focussing on exciting content.
                                    Details on all the classics, in-depth reviews, interviews with the
                                    creators of yesterday’s gems and much more.
                                </p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endisset
    </div>
</div>
