<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>@yield('title', 'Atari ST games, reviews, interviews, news and more') | Atari Legend</title>

    <link rel="stylesheet" href="{{ mix('css/app.css') }}">

    <script type="application/ld+json">
        {
            "@context": "http://schema.org",
            "@type": "Organization",
            "url": "{{ URL::to('/') }}",
            "name": "Atari Legend",
            "logo": "{{ asset('images/card/class.png') }}",
            "sameAs": [
                "https://www.facebook.com/atarilegend",
                "https://twitter.com/AtariLegend"
            ]
        }
    </script>

    @if (config('al.analytics.id'))
        <!-- Global site tag (gtag.js) - Google Analytics -->
        <script async src="//www.googletagmanager.com/gtag/js?id={{ config('al.analytics.id') }}"></script>
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('js', new Date());

            gtag('config', '{{ config('al.analytics.id') }}');
        </script>
    @endif

    <link href="{{ route('feed') }}" type="application/atom+xml" rel="alternate" title="Latest News, Reviews and Interviews Atom Feed" />

    {{-- Installation as mobile app --}}
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">

    {{-- IOS app icons --}}
    <link rel="apple-touch-icon" sizes="57x57" href="{{ asset('images/icons/icon-57x57.png') }}">
    <link rel="apple-touch-icon" sizes="72x72" href="{{ asset('images/icons/icon-72x72.png') }}">
    <link rel="apple-touch-icon" sizes="114x114" href="{{ asset('images/icons/icon-114x114.png') }}">
    <link rel="apple-touch-icon" sizes="144x144" href="{{ asset('images/icons/icon-144x144.png') }}">

    {{-- Social media metadata --}}
    <meta property="og:title" content="Atari Legend: Legends Never Die">
    <meta property="og:description" content="Nostalgia trippin' down the Atari ST memory lane">
    <meta property="og:image" content="{{ asset('images/AL.jpg') }}">
    <meta property="og:url" content="{{ URL::to('/') }}">
    <meta property="og:site_name" content="Atari Legend">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:image:alt" content="Atari Legend">

    <meta name="description" content="@yield('description', 'Information, reviews and comments about Atari ST games, interviews of famous Atari ST game developers, contribute missing information to the database.')">
    <meta name="robots" content="@yield('robots', 'follow,index')">

    <link rel="icon" href="{{ asset('images/favicon.png') }}">

</head>

<body>

    @include('layouts.header')

    @include('layouts.nav')

    <div class="container-fluid">
        @include('layouts.alert')

        @yield('content')
    </div>

    @include('layouts.online_users')

    @include('layouts.footer')

    @yield('scripts')

    <script src="{{ mix('js/app.js') }}"></script>
</body>

</html>
