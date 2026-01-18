<div class="card bg-dark mb-4">
    <div class="card-header text-center">
        <h2 class="text-uppercase">
            <span class="text-uppercase d-lg-none">{{ $interview->individual->ind_name }}</span>
            <span class="text-uppercase d-none d-lg-inline">Interview</span>

            @contributor
                <a href="{{ route('admin.interviews.interviews.edit', $interview) }}">
                    <small><i class="fas fa-pencil-alt text-contributor"></i></small>
                </a>
            @endcontributor
        </h2>
    </div>

    <div class="card-body p-2 bg-darklight">
        <h3 class="fs-5 text-audiowide">Written by {{ Helper::user($interview->user) }}</h3>
        <span class="text-muted">{{ $interview->texts->first()->interview_date->format('F j, Y') }}</span>
    </div>
    <div class="card-body p-2 bg-darklight">
        @if (isset($interview->texts()->first()->interview_chapters))
            <p class="card-text">{!! Helper::bbCode(nl2br(e($interview->texts()->first()->interview_chapters), false)) !!}</p>
        @endif

        <div class="float-end col-5 col-sm-3 ps-2 text-center text-muted lightbox-gallery">
            @foreach ($interview->screenshots as $screenshot)
                <div class="bg-dark p-2">
                    <a class="lightbox-link" href="{{ $screenshot->getUrl('interview') }}" title="{{ $screenshot->pivot->comment->comment_text }}">
                        <img class="w-100 mb-2" src="{{ $screenshot->getUrl('interview') }}" alt="{{ $screenshot->pivot->comment->comment_text }}">
                    </a>
                    <p class="pb-5 mb-0">{{ $screenshot->pivot->comment->comment_text }}</p>
                </div>
            @endforeach
        </div>

        <p class="card-text">{!! Helper::bbCode(nl2br(e($interview->texts()->first()->interview_text), false)) !!}</p>
    </div>
</div>
