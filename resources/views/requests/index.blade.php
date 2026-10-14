@extends('layouts.app')

@section('title', auth()->user()->isGeneralManager() ? 'الطلبات' : 'طلباتي')

@section('content')
    @php
        $input = 'w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-rose-700 focus:outline-none';
        $isGm = auth()->user()->isGeneralManager();
    @endphp
    <div class="bg-white rounded-xl shadow p-6">
        <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
            <h1 class="text-xl font-bold">{{ $isGm ? 'طلبات توثيق العقود' : 'طلبات الفرع' }}</h1>
            <div class="flex flex-wrap items-center gap-2">
                @if ($isGm)
                    <select id="filter-branch" class="{{ $input }} w-48">
                        <option value="">كل الفروع</option>
                        @foreach ($branches as $b)
                            <option value="{{ $b->id }}">{{ $b->name }} ({{ $b->number }})</option>
                        @endforeach
                    </select>
                @endif
                <input id="search" type="search" placeholder="بحث بالمرجع أو الاسم أو الهوية أو الجوال" class="{{ $input }} w-72">
                @can('create', App\Models\ContractRequest::class)
                    <a href="{{ route('requests.create') }}" class="rounded-md bg-rose-700 px-4 py-2 text-sm text-white hover:bg-rose-800">طلب جديد</a>
                @endcan
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-start">
                <thead class="bg-gray-50 text-gray-600">
                    <tr>
                        <th class="p-2 text-start">رقم المرجع</th>
                        <th class="p-2 text-start">الاسم</th>
                        <th class="p-2 text-start">الفرع</th>
                        <th class="p-2 text-start">تاريخ الطلب</th>
                        <th class="p-2 text-start">الملاحظات</th>
                    </tr>
                </thead>
                <tbody id="rows">
                    <tr><td colspan="6" class="p-4 text-center text-gray-500">جاري التحميل…</td></tr>
                </tbody>
            </table>
        </div>
        <div class="mt-4 flex flex-wrap items-center justify-between gap-2">
            <div id="pagination" class="flex flex-wrap gap-1"></div>
            <span id="total" class="text-xs text-gray-500"></span>
        </div>
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const { api, toast, debounce, renderPagination, el, emptyRow } = window.App;
    const $ = (s) => document.querySelector(s);
    const listUrl = @json(route('requests.list'));
    const state = { q: '', branch: '', page: 1 };
    const fmtDate = (iso) => iso ? iso.split('-').reverse().join('/') : '';

    async function load() {
        let res;
        try {
            const params = new URLSearchParams({ q: state.q, branch_id: state.branch, page: state.page });
            res = await api(`${listUrl}?${params}`);
        } catch (e) {
            toast(e.message || 'تعذر تحميل الطلبات.', 'error');
            return;
        }
        const rows = $('#rows');
        if (!res.data.length) emptyRow(rows, 5, 'لا توجد طلبات.');
        else rows.replaceChildren(...res.data.map((r) => el('tr', { class: `border-t cursor-pointer hover:bg-rose-50 ${r.is_new ? 'font-bold' : ''}`, dataset: { url: r.url } }, [
            el('td', { class: 'p-2 whitespace-nowrap' }, [
                el('a', { href: r.url, class: 'text-rose-700 hover:underline', dir: 'ltr', text: r.ref_no }),
                r.is_new ? el('span', { class: 'ms-2 rounded-full bg-red-600 px-2 py-0.5 text-xs font-normal text-white', text: 'جديد' }) : null,
            ]),
            el('td', { class: 'p-2', text: r.full_name }),
            el('td', { class: 'p-2', text: r.branch ? r.branch.name : '' }),
            el('td', { class: 'p-2 whitespace-nowrap', dir: 'ltr', text: fmtDate(r.request_date) }),
            el('td', { class: 'p-2', text: r.notes_count }),
        ])));
        $('#total').textContent = `الإجمالي: ${res.meta.total}`;
        renderPagination($('#pagination'), res.meta, (p) => { state.page = p; load(); });
    }

    $('#rows').addEventListener('click', (e) => {
        const tr = e.target.closest('tr[data-url]');
        if (tr && !e.target.closest('a')) window.location.assign(tr.dataset.url);
    });
    $('#search').addEventListener('input', debounce((e) => { state.q = e.target.value.trim(); state.page = 1; load(); }));
    $('#filter-branch')?.addEventListener('change', (e) => { state.branch = e.target.value; state.page = 1; load(); });

    load();
});
</script>
@endpush
