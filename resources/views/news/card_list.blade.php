<div class="card bg-dark mb-4">
    <div class="card-header text-center">
        <a class="float-right" href="{{ route('feed') }}"><i class="fas fa-rss-square text-warning"></i></a>
        <h2 class="text-uppercase">Latest News</h2>
    </div>

    <div class="card-body p-0">
        @foreach ($news as $new)
            <div class="p-2 bg-darklight clearfix">
                <p class="card-subtitle text-muted mt-1 float-right">{{ date('F j, Y', $new->news_date) }} by {{ Helper::user($new->user) }}</p>
                <h3 class="card-title text-h5 text-audiowide">{{ $new->news_headline }}</h3>
            </div>
            <div class="p-2 clearfix">
                <p class="card-text">
                    @if (isset($new->image))
                        <img class="col-3 float-left mt-2 mr-2 mb-2" src="{{ asset('storage/images/news_images/'.$new->image->news_image_id.'.'.$new->image->news_image_ext) }}" alt="News illustration image">
                    @endif
                    {!! Helper::bbCode(nl2br($new->news_text)) !!}
                </p>
            </div>
        @endforeach

        {{ $news->links() }}
    </div>
</div>
