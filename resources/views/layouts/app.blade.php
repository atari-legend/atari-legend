<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>@yield('title', 'Atari ST games, reviews, interviews, news and more') | Atari Legend</title>

    <link rel="stylesheet" href="{{ mix('css/app.css') }}">

    <link rel="canonical" href="{{ url()->current() }}">

    <script type="application/ld+json">
        @isset($jsonLd)
            {!! $jsonLd->json() !!}
        @else
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
        @endif
    </script>

    @if (config('al.analytics.matomo.id'))
        <!-- Matomo -->
        <script>
        var _paq = window._paq = window._paq || [];
        /* tracker methods like "setCustomDimension" should be called before "trackPageView" */
        @auth
            _paq.push(['setUserId', '{{ Auth::user()->userid }}']);
        @endauth
        _paq.push(['trackPageView']);
        _paq.push(['enableLinkTracking']);
        (function() {
            var u="//matomo.atarilegend.com/";
            _paq.push(['setTrackerUrl', u+'matomo.php']);
            _paq.push(['setSiteId', '{{ config('al.analytics.matomo.id') }}']);
            var d=document, g=d.createElement('script'), s=d.getElementsByTagName('script')[0];
            g.type='text/javascript'; g.async=true; g.src=u+'matomo.js'; s.parentNode.insertBefore(g,s);
        })();
        </script>
        <!-- End Matomo Code -->
    @endif

    @include('feed::links')

    {{-- Installation as mobile app --}}
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">

    {{-- IOS app icons --}}
    <link rel="apple-touch-icon" sizes="57x57" href="{{ asset('images/icons/icon-57x57.png') }}">
    <link rel="apple-touch-icon" sizes="72x72" href="{{ asset('images/icons/icon-72x72.png') }}">
    <link rel="apple-touch-icon" sizes="114x114" href="{{ asset('images/icons/icon-114x114.png') }}">
    <link rel="apple-touch-icon" sizes="144x144" href="{{ asset('images/icons/icon-144x144.png') }}">

    {{-- Social media metadata --}}
    <meta property="og:title" content="@yield('title', 'Atari Legend: Legends Never Die')">
    <meta property="og:description" content="@yield('description', 'Nostalgia trippin\' down the Atari ST memory lane')">
    <meta property="og:image" content="@yield('image', asset('images/AL.webp'))">
    <meta property="og:url" content="{{ URL::current() }}">
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

    <div class="container-xxxl">
        @include('layouts.alert')

        @yield('content')
    </div>

    @include('layouts.online_users')

    @include('layouts.footer')

    @yield('scripts')

    <script src="{{ mix('js/app.js') }}"></script>
</body>

</html>
