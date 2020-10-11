{!! '<'.'?'.'xml version="1.0" encoding="UTF-8" ?>' !!}
<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
	<sitemap>
		<loc>{{ route('sitemap.general') }}</loc>
	</sitemap>
	<sitemap>
		<loc>{{ route('sitemap.games', ['letter' => '0-9']) }}</loc>
	</sitemap>
@foreach (range('a', 'z') as $letter)
	<sitemap>
		<loc>{{ route('sitemap.games', ['letter' => $letter]) }}</loc>
	</sitemap>
@endforeach
</sitemapindex>
