@extends('layouts.app')

@section('content')
    <div class="row">
        <div class="col-3 pl-0">
            @include('interviews.card_intro')
            @include('interviews.card_profile')
            @include('interviews.card_credits')

        </div>
        <div class="col-6">
            @include('interviews.card_interview')
        </div>
        <div class="col-3 pr-0">
            @include('interviews.card_comments')
            @include('interviews.card_latest_interviews')
        </div>
    </div>
@endsection
