@extends('admin.layouts.admin')
@section('title', "Interview - ".(isset($interview) ? $interview->individual->ind_name : 'Create interview'))

@section('content')
    <div class="row">
        <div class="col">
            @include('admin.interviews.interviews.card_edit')
        </div>
    </div>
@endsection
