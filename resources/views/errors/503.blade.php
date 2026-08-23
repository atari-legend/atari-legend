{{-- Two bombs: a bus error, where the access could not be completed because
     nothing answered. The service is there, it just cannot respond yet. --}}
@extends('errors.bombs', ['bombs' => 2])

@section('title', __('Service Unavailable'))
@section('code', '503')
@section('message', __('Service Unavailable'))
