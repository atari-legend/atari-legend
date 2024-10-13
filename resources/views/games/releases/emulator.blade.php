@extends('layouts.app')
@section('title', 'Play Atari ST games in your browser')
@section('robots', 'follow,noindex')

@section('content')
    <h1 class="visually-hidden">Atari ST Emulator</h1>
    <div class="row">
    <div class="col-12 col-sm-6 col-lg-3 order-2 order-lg-1 lightbox-gallery">
            @include('games.releases.card_game')
            @include('games.card_releases', ['game' => $release->game, 'currentRelease' => $release])
        </div>
        <div class="col-12 col-lg-6 order-1 order-lg-2">
            <div class="pcejs pcejs-container">
                <div id="status" class="pcejs loading-status">Downloading...</div>
                <div class="pcejs">
                  <!-- canvas class="pcejs emulator-canvas" oncontextmenu="event.preventDefault()"></canvas -->
                  <canvas class="emscripten" width="640" height="400" id="canvas" oncontextmenu="event.preventDefault()" tabindex="-1"></canvas>
                </div>
              </div>
        </div>
        <button id="button-test">TEST</button>
        <div id="filter"></div>
        <div id="filterresultcount"></div>
        <div id="dbdate"></div>
        <div id="output">output</div>
        <div id="popup">popup</div>
        <div id="version">version</div>
        <div id="innerSelector"></div>
        <div class="col-12 col-sm-6 col-lg-3 order-3">
            @include('games.card_boxscan')
            @include('games.card_submit_info', ['game' => $release->game])
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        window.hatariDiskDownloadUrl = '{{ $dump->download_url }}';
    </script>

    <script defer src="{{ asset('js/emulator/jszip.min.js') }}"></script>
    <script defer src="{{ asset('js/emulator/emulator.js') }}"></script>
    <script defer src="{{ asset('js/emulator/hatari.js') }}"></script>

@endsection
