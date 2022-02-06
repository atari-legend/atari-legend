@extends('admin.layouts.admin')
@section('title', "Spotlight - ".(isset($spotlight) ? Str::words($spotlight->spotlight, 3) : 'Create spotlight'))

@section('content')
    <div class="row">
        <div class="col">
            @include('admin.others.spotlights.card_edit')
        </div>
    </div>
@endsection
