<div class="card bg-dark mb-4">
    <div class="card-header text-center">
        <a class="float-end" href="{{ route('feeds.main') }}"><i class="fas fa-rss-square text-warning"></i></a>
        <h2 class="text-uppercase">Latest News</h2>
    </div>

    <div class="card-body p-0">
        @foreach ($news as $new)
            <div class="p-2 bg-darklight clearfix">
                <h3 class="card-title fs-5 text-audiowide mb-0">
                    {{ $new->news_headline }}
                    @contributor
                        <a href="{{ route('admin.news.news.edit', $new) }}">
                            <small><i class="fas fa-pencil-alt text-contributor"></i></small>
                        </a>
                    @endcontributor
                </h3>
                <p class="text-muted my-1">{{ $new->news_date->format('F j, Y') }} by {{ Helper::user($new->user) }}</p>
            </div>
            <div class="p-2 clearfix mb-4 ">
                <p class="card-text pt-2">
                    @if (isset($new->image))
                        <img class="col-4 col-sm-3 float-start mt-1 me-2 mb-1" src="{{ asset('storage/images/news_images/'.$new->image->news_image_id.'.'.$new->image->news_image_ext) }}" alt="News illustration image">
                    @endif
                    {!! Helper::bbCode(stripslashes(nl2br(e($new->news_text), false))) !!}
                </p>
            </div>
        @endforeach

        {{ $news->links() }}
    </div>
</div>
