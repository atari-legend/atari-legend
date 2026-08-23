{{-- Two bombs: a bus error, where the access could not be completed because
     nothing answered. The service is there, it just cannot respond yet. --}}
@extends('errors.bombs', ['bombs' => 2, 'message' => __('Service Unavailable')])

@section('title', __('Service Unavailable'))
@section('code', '503')
