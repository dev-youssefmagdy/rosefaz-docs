@extends('layouts.app')

@section('title', 'تعديل طلب '.$contractRequest->ref_no)

@section('content')
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3 print:hidden">
        <h1 class="text-xl font-bold">تعديل طلب توثيق عقد <span dir="ltr">{{ $contractRequest->ref_no }}</span></h1>
        <a href="{{ route('requests.show', $contractRequest) }}" class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm hover:bg-gray-50">رجوع إلى الطلب</a>
    </div>

    <form id="request-form" class="rounded-xl bg-white shadow" novalidate>
        @include('requests._document', [
            'request' => $contractRequest,
            'mode' => 'edit',
            'branch' => $contractRequest->branch,
            'branches' => $branches,
        ])

        <div class="flex flex-wrap items-center justify-end gap-3 border-t border-gray-200 p-4 print:hidden">
            <button type="submit" class="rounded-md bg-rose-700 px-6 py-2 text-sm font-bold text-white hover:bg-rose-800 disabled:cursor-wait disabled:opacity-60">حفظ التعديلات</button>
        </div>
    </form>

    <form id="delete-form" action="{{ route('requests.destroy', $contractRequest) }}" method="POST" class="mt-4 print:hidden">
        @csrf
        @method('DELETE')
        <div class="flex items-center justify-end">
            <button type="submit" class="rounded-md border border-red-300 bg-white px-4 py-2 text-sm text-red-700 hover:bg-red-50">حذف الطلب</button>
        </div>
    </form>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    window.App.initRequestForm(document.getElementById('request-form'), {
        updateUrl: @json(route('requests.update', $contractRequest)),
        usersUrl: @json(route('lookups.users')),
        initialUserId: @json($contractRequest->user_id),
        branches: @json($branches->values()),
    });

    document.getElementById('delete-form').addEventListener('submit', (e) => {
        if (!window.confirm('هل أنت متأكد من حذف هذا الطلب؟ لا يمكن التراجع عن هذه العملية.')) {
            e.preventDefault();
        }
    });
});
</script>
@endpush
