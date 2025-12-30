@extends('admin.layouts.admin')
@section('title', 'Reviews Submissions')

@section('content')
    <div class="row">
        <div class="col">
            @include('admin.reviews.submissions.card_list')
        </div>
    </div>
@endsection
