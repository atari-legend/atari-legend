<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Atari ST games, reviews, interviews, news and more | Atari Legend</title>

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

    @if (env('GOOGLE_ANALYTICS_ID'))
        <!-- Global site tag (gtag.js) - Google Analytics -->
        <script async src="//www.googletagmanager.com/gtag/js?id={{ env('GOOGLE_ANALYTICS_ID') }}"></script>
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('js', new Date());

            gtag('config', '{{ env('GOOGLE_ANALYTICS_ID') }}');
        </script>
    @endif
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

    <script src="{{ mix('js/app.js') }}"></script>
</body>

</html>
