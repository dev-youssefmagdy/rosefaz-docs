@extends('layouts.app')

@section('title', 'تغيير كلمة المرور')

@section('content')
    @php
        $input = 'w-full rounded-md border border-gray-300 px-3 py-2 focus:border-rose-500 focus:outline-none';
        $err = 'hidden text-sm text-red-600 mt-1';
    @endphp
    <div class="max-w-md mx-auto bg-white rounded-lg border border-gray-200 p-6">
        <h1 class="text-xl font-bold mb-1">تغيير كلمة المرور</h1>
        <p class="text-sm text-gray-500 mb-6">{{ auth()->user()->name }} — {{ auth()->user()->email }}</p>
        <form id="password-form" class="space-y-4" novalidate>
            <div>
                <label class="block text-sm font-medium mb-1" for="current_password">كلمة المرور الحالية</label>
                <input id="current_password" name="current_password" type="password" autocomplete="current-password" class="{{ $input }}">
                <p data-error-for="current_password" class="{{ $err }}"></p>
            </div>
            <div>
                <label class="block text-sm font-medium mb-1" for="password">كلمة المرور الجديدة</label>
                <input id="password" name="password" type="password" autocomplete="new-password" class="{{ $input }}">
                <p data-error-for="password" class="{{ $err }}"></p>
            </div>
            <div>
                <label class="block text-sm font-medium mb-1" for="password_confirmation">تأكيد كلمة المرور الجديدة</label>
                <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" class="{{ $input }}">
                <p data-error-for="password_confirmation" class="{{ $err }}"></p>
            </div>
            <button type="submit" class="w-full rounded-md bg-rose-700 text-white py-2 font-medium hover:bg-rose-800 disabled:opacity-50">حفظ</button>
        </form>
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const { api, toast, showErrors, clearErrors } = window.App;
    const form = document.getElementById('password-form');
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const btn = form.querySelector('[type="submit"]');
        btn.disabled = true;
        clearErrors(form);
        try {
            const res = await api(@json(route('account.password.update')), {
                method: 'PUT',
                body: Object.fromEntries(new FormData(form)),
            });
            form.reset();
            toast(res.message);
        } catch (err) {
            if (err.status === 422) showErrors(form, err.errors);
            else toast(err.message || 'حدث خطأ غير متوقع.', 'error');
        } finally {
            btn.disabled = false;
        }
    });
});
</script>
@endpush
