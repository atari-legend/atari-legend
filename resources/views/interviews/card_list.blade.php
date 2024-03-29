<div class="card bg-dark mb-4">
    <div class="card-header text-center">
        <a class="float-end" href="{{ route('feeds.main') }}"><i class="fas fa-rss-square text-warning"></i></a>
        <h2 class="text-uppercase">Interviews</h2>
    </div>
    <div class="card-body p-2 mb-3">
        <p class="card-text">
            In this section we'll be talking to the VIP's of the industry. People
            that were in some way or another important in the history of the ST.
            We are anxious to fill this section with special conversations, preserving
            information about the long lost classics and the good ol' days. If you
            know of anybody, make sure to pass over the information to us. So for now,
            grab your VIP pass and enter the world of glitter and glamour! Enjoy
            the read. There are currently <strong>{{ $interviews->total() }}</strong>
            interviews in the AL database!
        </p>
    </div>

    <div class="card-body p-0 striped">
        @foreach ($interviews as $interview)
            <div class="p-2 lightbox-gallery">
                <div class="clearfix mb-2">
                    <h3 class="fs-5 text-audiowide">
                        <a href="{{ route('interviews.show', ['interview' => $interview]) }}">
                            {{ $interview->individual->ind_name }}
                        </a>
                    </h3>
                    <p class="card-subtitle text-muted">{{ $interview->texts->first()->interview_date->format('F j, Y') }} by {{ Helper::user($interview->user) }}</p>
                </div>

                <div class="clearfix">
                    @if ($interview->individual->text !== null && $interview->individual->text->file !== null)
                        <a class="lightbox-link" href="{{ $interview->individual->text->image_url }}">
                            <img class="col-4 col-sm-3 float-start mt-1 me-2 mb-1" src="{{ route('individuals.avatar', $interview->individual) }}" alt="Picture of {{ $interview->individual->ind_name }}">
                        </a>
                    @else
                        <img class="col-4 col-sm-3 float-start mt-1 me-2 mb-1" src="{{ asset('images/unknown.jpg') }}" alt="Placeholder image as there is no picture for the interviewee">
                    @endif

                    {!! Helper::bbCode(e($interview->texts->first()->interview_intro)) !!}<br>
                    <a class="d-block text-end mt-2" href="{{ route('interviews.show', ['interview' => $interview]) }}">
                        Read the interview of {{ $interview->individual->ind_name }} <i class="fas fa-chevron-right"></i>
                    </a>
                </div>
            </div>
        @endforeach

        {{ $interviews->links() }}
    </div>
</div>
