@extends('layouts.guest')

@section('content')
    <h1 class="text-xl font-bold text-center mb-6">تسجيل الدخول</h1>
    <form method="POST" action="{{ route('login.attempt') }}" class="space-y-4">
        @csrf
        <div>
            <label for="email" class="block text-sm font-medium mb-1">البريد الإلكتروني</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus
                   class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-rose-500 focus:outline-none">
            @error('email')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="password" class="block text-sm font-medium mb-1">كلمة المرور</label>
            <input id="password" name="password" type="password" required
                   class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-rose-500 focus:outline-none">
            @error('password')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
        </div>
        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" name="remember" value="1"> تذكرني
        </label>
        <button type="submit" class="w-full rounded-md bg-rose-700 text-white py-2 font-medium hover:bg-rose-800">دخول</button>
    </form>
@endsection
