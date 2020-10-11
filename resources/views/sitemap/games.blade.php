{!! '<'.'?'.'xml version="1.0" encoding="UTF-8" ?>' !!}
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
@foreach ($games as $game)
	<url>
		<loc>{{ route('games.show', ['game' => $game]) }}</loc>
		<changefreq>weekly</changefreq>
	</url>
@endforeach
</urlset>
