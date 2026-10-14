@extends('layouts.app')

@section('title', 'طلب جديد')

@section('content')
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3 print:hidden">
        <h1 class="text-xl font-bold">طلب توثيق عقد جديد</h1>
        <a href="{{ route('requests.index') }}" class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm hover:bg-gray-50">رجوع إلى الطلبات</a>
    </div>

    <form id="request-form" class="rounded-xl bg-white shadow" novalidate>
        @include('requests._document', ['request' => null, 'mode' => 'edit', 'branch' => $branch])

        <div class="flex flex-wrap items-center justify-between gap-3 border-t border-gray-200 p-4 print:hidden">
            <p class="text-sm text-gray-600">تنبيه: بعد إرسال الطلب لا يمكن تعديله أو حذفه.</p>
            <button type="submit" class="rounded-md bg-rose-700 px-6 py-2 text-sm font-bold text-white hover:bg-rose-800 disabled:cursor-wait disabled:opacity-60">إرسال الطلب</button>
        </div>
    </form>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    window.App.initRequestForm(document.getElementById('request-form'), {
        storeUrl: @json(route('requests.store')),
        usersUrl: @json(route('lookups.users')),
        authUserId: @json(auth()->id()),
    });
});
</script>
@endpush
