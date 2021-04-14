@if ($interviews->isNotEmpty())
    <div class="card bg-dark mb-4">
        <div class="card-header text-center">
            <h2 class="text-uppercase">Interviews</h2>
        </div>
        <div class="striped">
            @foreach ($interviews as $interview)
                @include('interviews.partial_card', ['interview' => $interview])
            @endforeach
        </div>
    </div>
@endif
