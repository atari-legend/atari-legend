@extends('errors.bombs', ['bombs' => 8, 'message' => __($exception->getMessage() ?: 'Forbidden')])

@section('title', __('Forbidden'))
@section('code', '403')
