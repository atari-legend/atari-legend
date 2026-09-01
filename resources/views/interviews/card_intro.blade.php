<div class="card bg-dark mb-4">
    <div class="card-header text-center">
        <h2 class="text-uppercase d-none d-lg-block">{{ $interview->individual->name }}</h2>
        <h2 class="text-uppercase d-lg-none">Introduction</h2>
    </div>



    <div class="card-body p-0">
        @if (isset($interview->individual->file))
           <img class="w-100" src="{{ route('individuals.avatar', $interview->individual) }}" alt="Picture of {{ $interview->individual->name }}">
        @endif
        <p class="card-text p-2">
            {!! Helper::bbCode(e($interview->interview_intro)) !!}
        </p>
    </div>
</div>
