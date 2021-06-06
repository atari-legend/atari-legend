@extends('layouts.app')
@section('title', $article->texts->first()->article_title)

@if ($article->screenshots->isNotEmpty())
    @section('image', $article->screenshots->first()->getUrl('article'))
@endif

@section('content')
    <h1 class="visually-hidden">{{ $article->texts->first()->article_title }}</h1>
    <div class="row">
        <div class="col-12 col-sm-6 col-lg-3 order-2 order-lg-1">
            @include('articles.card_intro')
            @include('articles.card_author')

        </div>
        <div class="col-12 col-lg-6 order-1 order-lg-2">
            @include('articles.card_article')
        </div>
        <div class="col col-sm-6 col-lg-3 order-3">
            @include('articles.card_comments')
            @include('articles.card_latest_articles')
        </div>
    </div>
@endsection
