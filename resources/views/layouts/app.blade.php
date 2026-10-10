<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'طلبات توثيق العقود') - مجموعة روز فان الفندقية</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100 text-gray-900 font-sans antialiased min-h-screen">
    @php($user = auth()->user())
    <nav class="bg-white border-b border-gray-200 print:hidden">
        <div class="max-w-6xl mx-auto px-4 h-16 flex items-center justify-between gap-4">
            <div class="flex items-center gap-6">
                <a href="{{ route('home') }}" class="flex items-center gap-2">
                    <img src="{{ asset('logo.png') }}" alt="روز فان" class="h-10 w-auto">
                </a>
                @if ($user?->isGeneralManager())
                    <a href="{{ Route::has('requests.index') ? route('requests.index') : '#' }}" class="text-sm font-medium hover:text-rose-700">الطلبات</a>
                    <a href="{{ Route::has('branches.index') ? route('branches.index') : '#' }}" class="text-sm font-medium hover:text-rose-700">الفروع</a>
                    <a href="{{ Route::has('employees.index') ? route('employees.index') : '#' }}" class="text-sm font-medium hover:text-rose-700">الموظفون</a>
                    <a href="{{ route('users.index') }}" class="text-sm font-medium hover:text-rose-700">المستخدمون</a>
                @elseif ($user?->isBranchManager())
                    <a href="{{ Route::has('requests.index') ? route('requests.index') : '#' }}" class="text-sm font-medium hover:text-rose-700">طلباتي</a>
                    <a href="{{ Route::has('requests.create') ? route('requests.create') : '#' }}" class="text-sm font-medium hover:text-rose-700">طلب جديد</a>
                @endif
            </div>
            <div class="flex items-center gap-3 text-sm">
                <a href="{{ route('account.password') }}" class="hover:text-rose-700" title="تغيير كلمة المرور">{{ $user?->name }}</a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="rounded-md border border-gray-300 px-3 py-1.5 hover:bg-gray-50">تسجيل الخروج</button>
                </form>
            </div>
        </div>
    </nav>

    <main class="max-w-6xl mx-auto px-4 py-6">
        @yield('content')
    </main>

    <div id="toast-container" class="fixed bottom-4 start-4 z-50 flex flex-col gap-2 print:hidden"></div>
    @stack('scripts')
</body>
</html>
