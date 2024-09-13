@extends('layouts.app')
@section('title', 'Play Atari ST games in your browser')
@section('robots', 'follow,noindex')

@section('content')
    <h1 class="visually-hidden">Atari ST Emulator</h1>
    <div class="row">
        <div class="col-12 col-sm-6 col-lg-3 order-2 order-lg-1">
            <x-cards.latest-comments />
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
        <div id="filter"></div>
        <div id="filterresultcount"></div>
        <div id="dbdate"></div>
        <div id="output"></div>
        <div id="popup"></div>
        <div id="version"></div>
        <div id="innerSelector"></div>
        <div class="col col-sm-6 col-lg-3 order-3">
            <x-cards.top-games />
        </div>
    </div>
@endsection

@section('scripts')
    
    <script src="{{ asset('js/emulator/hatari.js') }}"></script>   
    
    <script src="{{ asset('js/emulator/emulator.js') }}" type="module"></script>
@endsection
