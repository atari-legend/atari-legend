@extends('admin.layouts.admin')
@section('title', isset($category) ? $category->website_category_name : 'Create category')

@section('content')
    <div class="row">
        <div class="col">
            @include('admin.links.categories.card_edit')
        </div>
    </div>
@endsection
