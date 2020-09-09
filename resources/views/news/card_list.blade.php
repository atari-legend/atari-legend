<div class="card bg-dark mb-4">
    <div class="card-header text-center">
        <h2 class="text-uppercase">Latest News</h2>
    </div>

    <div class="card-body p-0">
        @foreach ($news as $new)
            <div class="p-2 bg-darklight clearfix">
                <h6 class="card-subtitle text-muted mt-1 float-right">{{ date('F j, Y', $new->news_date) }} by {{ Helper::user($new->user) }}</h6>
                <h4 class="card-title">{{ $new->news_headline }}</h4>
            </div>
            <div class="p-2 clearfix">
                <p class="card-text">
                    @if (isset($new->image))
                        <img class="col-3 float-left mt-2 mr-2 mb-2" src="{{ asset('storage/images/news_images/'.$new->image->news_image_id.'.'.$new->image->news_image_ext) }}">
                    @endif
                    {!! Helper::bbCode(nl2br($new->news_text)) !!}
                </p>
            </div>
        @endforeach

        {{ $news->links() }}
    </div>
</div>
