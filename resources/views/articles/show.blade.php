@extends('layouts.app')

@section('content')
    <div class="row">
        <div class="col-3 pl-0">
            @include('articles.card_intro')
            @include('articles.card_author')

        </div>
        <div class="col-6">
            @include('articles.card_article')
        </div>
        <div class="col-3 pr-0">
            @include('articles.card_comments')
            @include('articles.card_latest_articles')
        </div>
    </div>
@endsection
