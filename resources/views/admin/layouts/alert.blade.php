@foreach (session()->all() as $key => $value)
    @if (Str::startsWith($key, 'alert-'))
        @php
            $type = explode('-', $key)[1];
            $icon = [
                'success' => 'fa-check',
                'warning' => 'fa-exclamation-triangle',
                'danger'  => 'fa-times',
            ][$type];
        @endphp
        <div class="position-fixed top-0 end-0 p-3" style="z-index: 5;">
            <div class="toast bg-{{ $type }} text-white" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="3000">
                <div class="d-flex">
                    <div class="toast-body"><i class="fas fa-fw {{ $icon }}"></i> {{ $value }}</div>
                    <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
        </div>
    @endif
@endforeach
