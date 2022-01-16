@extends('admin.layouts.admin')
@section('title', 'News submissions')

@section('content')
    <div class="row">
        <div class="col">
            @include('admin.news.submissions.card_list')
        </div>
    </div>
@endsection
