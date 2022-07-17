@extends('admin.layouts.admin')
@section('title', "Magazine {$magazine->name} : ".(isset($issue) ? $issue->issue : 'Create issue'))

@section('content')
    <div class="row">
        <div class="col">
            @include('admin.magazines.issues.card_edit')
        </div>
    </div>
    @isset($issue)
        <div class="row">
            <div class="col">
                @include('admin.magazines.issues.card_index')
            </div>
        </div>
    @endif
@endsection
