@if ($interviews->isNotEmpty())
    <div class="card bg-dark mb-4">
        <div class="card-header text-center">
            <h2 class="text-uppercase">Interviews</h2>
        </div>
        <div class="striped">
            @php
                $interview = $interviews->random();
            @endphp
            @include('interviews.partial_card', ['interview' => $interview])
        </div>
    </div>
@endif
