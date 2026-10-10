@extends('layouts.app')

@section('title', 'الرئيسية')

@section('content')
    <div class="bg-white rounded-xl shadow p-6">
        <h1 class="text-xl font-bold mb-2">مرحبًا، {{ auth()->user()->name }}</h1>
        <p class="text-gray-600">الدور: {{ auth()->user()->role->label() }}</p>
    </div>
@endsection
