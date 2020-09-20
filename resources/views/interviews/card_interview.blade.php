<div class="card bg-dark mb-4">
    <div class="card-header text-center">
        <h2 class="text-uppercase">Interview</h2>
    </div>

    <div class="card-body p-2 bg-darklight">
        <h5>Written by {{ Helper::user($interview->user) }}</h5>
        <span class="text-muted">{{ date('F j, Y', $interview->review_date) }}</span>
    </div>
    <div class="card-body p-2 bg-darklight">
        <div class="row g-0">
            <div class="col-9">
                @if (isset($interview->texts()->first()->interview_chapters))
                    <p class="card-text">{!! Helper::bbCode(nl2br($interview->texts()->first()->interview_chapters)) !!}</p>
                @endif
                <p class="card-text">{!! Helper::bbCode(nl2br($interview->texts()->first()->interview_text)) !!}</p>
            </div>
            <div class="col-3 pl-2 text-center text-muted">
                @foreach ($interview->screenshots as $screenshot)
                    <div class="bg-dark">
                        <img class="w-100 mb-2" src="{{ asset('storage/images/interview_screenshots/'.$screenshot->screenshot->file) }}" alt="{{ $screenshot->comment->comment_text }}">
                        <p class="pb-5 mb-0">{{ $screenshot->comment->comment_text }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
