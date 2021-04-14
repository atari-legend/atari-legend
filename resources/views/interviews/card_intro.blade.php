<div class="card bg-dark mb-4">
    <div class="card-header text-center">
        <h2 class="text-uppercase d-none d-lg-block">{{ $interview->individual->ind_name }}</h2>
        <h2 class="text-uppercase d-lg-none">Introduction</h2>
    </div>



    <div class="card-body p-0">
        @if (isset($interview->individual->text->file))
           <img class="w-100" src="{{ asset('storage/images/individual_screenshots/'.$interview->individual->text->file) }}" alt="Picture of {{ $interview->individual->ind_name }}">
        @endif
        <p class="card-text p-2">
            {!! Helper::bbCode($interview->texts->first()->interview_intro) !!}
        </p>
    </div>
</div>
