<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>@yield('title', 'Administration') | Atari Legend</title>

    @vite(['resources/sass/admin/admin.scss'])

    @livewireStyles
    @rappasoftTableStyles
    @rappasoftTableThirdPartyStyles

    <link rel="icon" href="{{ asset('images/favicon.png') }}">

</head>

<body>

    @include('admin.layouts.navbar')

    <div class="container-fluid">

        <div class="admin-layout d-md-grid">
            <aside id="aside" class="collapse d-md-block bg-light p-2">
                @include('admin.layouts.profile')
                <hr>
                @include('admin.layouts.nav')
            </aside>
            <main class="pt-3">

                @include('admin.layouts.alert')

                <x-admin.breadcrumbs :crumbs="$breadcrumbs ?? []" />
                <hr>
                @yield('content')
            </main>
        </div>
    </div>

    @livewireScripts
    @rappasoftTableScripts
    @rappasoftTableThirdPartyScripts

    @yield('scripts')

    @vite(['resources/js/admin/app.js'])
</body>

</html>
