<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'تسجيل الدخول') - مجموعة روز فان الفندقية</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100 text-gray-900 font-sans antialiased min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md bg-white rounded-xl shadow p-8">
        <div class="flex justify-center mb-6">
            <img src="{{ asset('logo.png') }}" alt="روز فان" class="h-20 w-auto">
        </div>
        @yield('content')
    </div>
    <div id="toast-container" class="fixed bottom-4 start-4 z-50 flex flex-col gap-2"></div>
    @stack('scripts')
</body>
</html>
