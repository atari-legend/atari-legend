<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Atari ST games, reviews, interviews, news and more | Atari Legend</title>

    <link rel="stylesheet" href="{{ mix('css/app.css') }}">
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
