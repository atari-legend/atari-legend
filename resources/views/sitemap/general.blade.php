{!! '<'.'?'.'xml version="1.0" encoding="UTF-8" ?>' !!}
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
	<url>
		<loc>{{ route('home.index') }}</loc>
		<changefreq>daily</changefreq>
	</url>
	<url>
		<loc>{{ route('news.index') }}</loc>
		<changefreq>daily</changefreq>
	</url>
@foreach($interviews as $interview)
	<url>
		<loc>{{ route('interviews.show', ['interview' => $interview]) }}</loc>
		<changefreq>monthly</changefreq>
	</url>
@endforeach
@foreach($reviews as $review)
	<url>
		<loc>{{ route('reviews.show', ['review' => $review]) }}</loc>
		<changefreq>monthly</changefreq>
	</url>
@endforeach
	<url>
		<loc>{{ route('links.index') }}</loc>
		<changefreq>monthly</changefreq>
	</url>
@foreach ($websiteCategories as $category)
@if ($category->websites->isNotEmpty())
	<url>
		<loc>{{ route('links.index', ['category' => $category]) }}</loc>
		<changefreq>monthly</changefreq>
	</url>
@endif
@endforeach
</urlset>
