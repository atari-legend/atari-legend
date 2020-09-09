<div class="card card-ellipsis bg-dark mb-4">
    <div class="card-header text-center">
        <h2 class="text-uppercase">Latest Interviews</h2>
    </div>

    <div class="striped">
        @foreach ($interviews as $interview)
            <div class="card-body p-2">
                <h5 class="card-title">
                    <a href="{{ route('interviews.show', ['interview' => $interview]) }}">{{ $interview->individual->ind_name }}</a>
                </h5>
                <h6 class="card-subtitle text-muted mb-2">{{ date('F j, Y', $interview->texts->first()->interview_date) }} by {{ Helper::user($interview->user) }}</h6>
                <p class="card-text ellipsis">
                    {!! Helper::bbCode($interview->texts->first()->interview_intro) !!}
                </p>
                <a class="d-block text-right" href="{{ route('interviews.show', ['interview' => $interview]) }}">
                    <i class="fas fa-chevron-right"></i>
                </a>
            </div>
        @endforeach
    </div>
</div>
