<div class="card-body p-0">
    @isset ($interview)
        @if (isset($interview->individual->text->file))
            <figure>
                <img class="w-100" src="{{ asset('storage/images/individual_screenshots/'.$interview->individual->text->file) }}" alt="Picture of {{ $interview->individual->ind_name }}">
                <figcaption class="py-2 px-3">
                    <div class="figcaption-caret"><i class="fas fa-angle-up fa-2x"></i></div>
                    <div class="figcaption-title"><a href="{{ route('interviews.show', ['interview' => $interview->interview_id]) }}">{{ $interview->individual->ind_name }}</a></div>
                    <div class="figcaption-subtitle mb-2"><strong>Random interview</strong></div>
                </figcaption>
            </figure>
        @endif
        <div class="p-2">
            <p class="card-text">
                {!! Helper::bbCode(Helper::extractTag($interview->texts()->first()->interview_intro, "frontpage")) !!}
            </p>
            <p class="card-subtitle text-muted">{{ date('F j, Y', $interview->texts()->first()->interview_date) }} by {{ Helper::user($interview->user) }}</p>
            <a class="d-block text-end" href="{{ route('interviews.show', ['interview' => $interview->interview_id]) }}">
                More <i class="fas fa-chevron-right"></i>
            </a>
        </div>
    @endisset
</div>
