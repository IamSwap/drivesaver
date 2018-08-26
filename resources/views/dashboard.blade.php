@extends('layouts.app')

@section('content')
    <router-view :user="{{ auth()->user() }}"></router-view>
@endsection
