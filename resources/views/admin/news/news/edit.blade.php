@extends('admin.layouts.admin')
@section('title', "News - ".(isset($news) ? $news->news_headline : 'Create news'))

@section('content')
    <div class="row">
        <div class="col">
            @include('admin.news.news.card_edit')
        </div>
    </div>
@endsection
