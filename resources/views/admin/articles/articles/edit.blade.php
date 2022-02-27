@extends('admin.layouts.admin')
@section('title', "Article - ".(isset($article) ? $article->texts->first()->article_title : 'Create article'))

@section('content')
    <div class="row">
        <div class="col">
            @include('admin.articles.articles.card_edit')
        </div>
    </div>
    <div class="row">
        <div class="col">
            @include('admin.articles.articles.card_images')
        </div>
    </div>
@endsection
