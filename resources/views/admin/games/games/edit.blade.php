@extends('admin.layouts.admin')

@section('content')
    @isset($game)
        <div class="row">
            <div class="col">
                @include('admin.games.games.nav')
            </div>
        </div>
    @endif
    <div>
        <div class="col">
            @include('admin.games.games.card_edit')
        </div>
    </div>
@endsection
