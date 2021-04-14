<div class="card bg-dark mb-4">
    <div class="card-header text-center">
        <h2 class="text-uppercase"><a href="{{ route('interviews.index') }}">Who is it?</a></h2>
    </div>
    @include('interviews.partial_card', ['interview' => $interview])
</div>
