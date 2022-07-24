@extends('admin.layouts.admin')
@section('title', "Magazine - ".(isset($magazine) ? $magazine->name : 'Create magazine'))

@section('content')
    <div class="row">
        <div class="col">
            @include('admin.magazines.magazines.card_edit')
        </div>
    </div>
    @isset($magazine)
        <div class="row">
            <div class="col">
                @include('admin.magazines.magazines.card_issues')
            </div>
        </div>
    @endif
@endsection
