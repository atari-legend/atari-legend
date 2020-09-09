@foreach (session()->all() as $key => $value)
    @if (strpos($key, 'alert-') === 0 && $key !== 'alert-title')
        <div class="alert {{ $key }} alert-dismissible fade show" role="alert">
            @if (session()->has('alert-title'))
                <h4 class="alert-heading">{{ session()->get('alert-title') }}</h4>
            @endif
            {{ $value }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif
@endforeach
