<div class="card bg-dark mb-4">
    <div class="card-header text-center">
        <h2 class="text-uppercase">In Loving Memory</h2>
    </div>
    <div class="card-body p-0">
        <figure>
            <img class="w-100" src="{{ asset('images/class_al/Andreas3.jpg') }}" alt="Picture of Andreas Wahlin">
            <figcaption class="py-2 px-3">
                <div class="figcaption-caret"><i class="fas fa-angle-up fa-2x"></i></div>
                <div class="figcaption-title"><a href="{{ route('about.andreas') }}">Andreas Wahlin</a></div>
                <div class="figcaption-subtitle mb-2"><strong>1980 - 2007</strong></div>
            </figcaption>
        </figure>
        <p class="card-text p-2">
            Gone but not forgotten…
        </p>
        <a class="d-block p-2 text-end" href="{{ route('about.andreas') }}">
            Learn more about Andreas <i class="fas fa-chevron-right"></i>
        </a>
    </div>
</div>
