<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>@yield('title', 'Administration') | Atari Legend</title>

    <link rel="stylesheet" href="{{ mix('css/admin.css') }}">

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

    <link rel="icon" href="{{ asset('images/favicon.png') }}">

</head>

<body>

    @include('admin.layouts.navbar')

    <div class="container-fluid ps-0">

        <div class="admin-layout d-md-grid">
            <aside id="aside" class="collapse d-md-block bg-light p-2">
                @include('admin.layouts.profile')
                <hr>
                @include('admin.layouts.nav')
            </aside>
            <main class="pt-3">
                @yield('breadcrumbs')
                <hr>
                @yield('content')
            </main>
        </div>
    </div>

    <script src="{{ mix('js/app.js') }}"></script>
</body>

</html>
