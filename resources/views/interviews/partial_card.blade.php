<div class="card-body p-0">
    @if (isset($interview->individual->text->file))
    <img class="w-100" src="{{ asset('storage/images/individual_screenshots/'.$interview->individual->text->file) }}">
    @endif
    <div class="p-2">
        <p class="card-text">
            {!! Helper::bbCode(Helper::extractTag($interview->texts()->first()->interview_intro, "frontpage")) !!}
        </p>
        <h6 class="card-subtitle text-muted">{{ date('F j, Y', $interview->texts()->first()->interview_date) }} by {{ Helper::user($interview->user) }}</h6>
        <a class="d-block text-right" href="{{ route('interviews.show', ['interview' => $interview->interview_id]) }}">
            More <i class="fas fa-chevron-right"></i>
        </a>
    </div>
</div>
