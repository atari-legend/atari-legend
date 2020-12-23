@if ($crumbs !== [])
<nav aria-label="breadcrumb" style="--bs-breadcrumb-divider: '›';">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.home.index') }}"><i class="fas fa-home"></i></a></li>
        @foreach ($crumbs as $crumb)
            @if (!$loop->last)
                <li class="breadcrumb-item"><a href="{{ $crumb->route }}">{{ $crumb->label }}</a></li>
            @else
                <li class="breadcrumb-item active" aria-current="page">{{ $crumb->label }}</li>
            @endif
        @endforeach
    </ol>
</nav>
@else
    <div class="alert alert-warning" role="alert">
        <i class="fas fa-exclamation-triangle"></i> No breadcrumb defined for this page. Please provide a <code>$breadcrumb</code> variable
        in your controller.
    </div>
@endif
