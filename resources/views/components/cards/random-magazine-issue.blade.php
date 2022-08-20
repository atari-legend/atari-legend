@if ($issue)
    <div class="card bg-dark mb-4">
        <div class="card-header text-center">
            <h2 class="text-uppercase">Random magazine</h2>
        </div>
        <div class="card-body p-0 striped">
            <figure>
                <img src="{{ $issue->image }}" class="img-fluid bg-black" alt="Cover for {{ $issue->label }}">
                <figcaption class="py-2 px-3">
                    <div class="figcaption-caret"><i class="fas fa-angle-up fa-2x"></i></div>
                    <div class="figcaption-title">
                        <a href="{{ route('magazines.show', ['magazine' => $issue->magazine, 'page' => $issue->magazine_page_number]) }}#magazine-issue-{{ $issue->id }}">
                            {{ $issue->magazine->name }} {{ $issue->issue }}
                        </a>
                    </div>
                    <div class="figcaption-subtitle">{{ $issue->published?->format('F Y') ?? '' }}</div>
                </figcaption>
            </figure>
        </div>
    </div>
@endif
