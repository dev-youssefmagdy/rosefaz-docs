@extends('layouts.app')

@section('title', 'الموظفون')

@section('content')
    @php
        $input = 'w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-rose-700 focus:outline-none';
        $err = 'mt-1 text-xs text-red-600 hidden';
    @endphp
    <div class="bg-white rounded-xl shadow p-6">
        <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
            <h1 class="text-xl font-bold">الموظفون</h1>
            <div class="flex flex-wrap items-center gap-2">
                <select id="filter-branch" class="{{ $input }} w-48">
                    <option value="">كل الفروع (عرض الكل)</option>
                    <option value="all">موظفو كل الفروع فقط</option>
                    @foreach ($branches as $b)
                        <option value="{{ $b->id }}">{{ $b->name }}</option>
                    @endforeach
                </select>
                <input id="search" type="search" placeholder="بحث بالاسم" class="{{ $input }} w-56">
                <button id="add-btn" type="button" class="rounded-md bg-rose-700 px-4 py-2 text-sm text-white hover:bg-rose-800">إضافة موظف</button>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-start">
                <thead class="bg-gray-50 text-gray-600">
                    <tr>
                        <th class="p-2 text-start">الاسم</th>
                        <th class="p-2 text-start">الفرع</th>
                        <th class="p-2 text-start">الحالة</th>
                        <th class="p-2 text-start">إجراءات</th>
                    </tr>
                </thead>
                <tbody id="rows"></tbody>
            </table>
        </div>
        <div id="pagination" class="mt-4 flex flex-wrap gap-1"></div>
    </div>

    <div id="emp-modal" class="fixed inset-0 z-40 hidden items-center justify-center bg-black/40 p-4">
        <form id="emp-form" class="w-full max-w-md rounded-xl bg-white p-6 shadow-xl space-y-4" novalidate>
            <h2 id="emp-title" class="text-lg font-bold"></h2>
            <div>
                <label class="block mb-1 text-sm">الاسم</label>
                <input name="name" class="{{ $input }}">
                <p data-error-for="name" class="{{ $err }}"></p>
            </div>
            <div>
                <label class="block mb-1 text-sm">الفرع</label>
                <select name="branch_id" class="{{ $input }}">
                    <option value="">كل الفروع</option>
                    @foreach ($branches as $b)
                        <option value="{{ $b->id }}">{{ $b->name }}</option>
                    @endforeach
                </select>
                <p data-error-for="branch_id" class="{{ $err }}"></p>
            </div>
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" checked> فعّال</label>
            <div class="flex justify-end gap-2">
                <button type="button" data-close class="rounded-md border border-gray-300 px-4 py-2 text-sm">إلغاء</button>
                <button type="submit" class="rounded-md bg-rose-700 px-4 py-2 text-sm text-white">حفظ</button>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const { api, toast, showErrors, clearErrors, debounce, renderPagination, el, emptyRow } = window.App;
    const $ = (s) => document.querySelector(s);
    const base = @json(url('/employees'));
    const state = { q: '', branch: '', page: 1, items: {}, editing: null };
    const btn = 'rounded border border-gray-300 px-2 py-1 text-xs hover:bg-gray-50';
    const action = (act, id, text) => el('button', { type: 'button', class: btn, dataset: { act, id }, text });
    const open = (m) => { m.classList.remove('hidden'); m.classList.add('flex'); };
    const close = (m) => { m.classList.add('hidden'); m.classList.remove('flex'); };
    document.querySelectorAll('[data-close]').forEach((b) => b.addEventListener('click', () => close(b.closest('.fixed'))));
    const fail = (e, form) => {
        if (e.status === 422) { form ? showErrors(form, e.errors) : toast(Object.values(e.errors)[0]?.[0] ?? 'خطأ', 'error'); }
        else toast(e.message || 'حدث خطأ غير متوقع.', 'error');
    };

    async function load() {
        let res;
        try { res = await api(`${base}/list?q=${encodeURIComponent(state.q)}&branch_id=${encodeURIComponent(state.branch)}&page=${state.page}`); } catch (err) { fail(err); return; }
        state.items = {};
        const rows = $('#rows');
        if (!res.data.length) emptyRow(rows, 4, 'لا يوجد موظفون.');
        else rows.replaceChildren(...res.data.map((m) => {
            state.items[m.id] = m;
            return el('tr', { class: 'border-t' }, [
                el('td', { class: 'p-2', text: m.name }),
                el('td', { class: 'p-2', text: m.branch ? m.branch.name : 'كل الفروع' }),
                el('td', { class: 'p-2' }, [el('span', { class: `rounded-full px-2 py-0.5 text-xs ${m.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-200 text-gray-700'}`, text: m.is_active ? 'فعّال' : 'موقوف' })]),
                el('td', { class: 'p-2 flex gap-1' }, [action('edit', m.id, 'تعديل'), action('toggle', m.id, m.is_active ? 'إيقاف' : 'تفعيل')]),
            ]);
        }));
        renderPagination($('#pagination'), res.meta, (p) => { state.page = p; load(); });
    }

    $('#search').addEventListener('input', debounce((e) => { state.q = e.target.value.trim(); state.page = 1; load(); }));
    $('#filter-branch').addEventListener('change', (e) => { state.branch = e.target.value; state.page = 1; load(); });

    $('#add-btn').addEventListener('click', () => {
        const f = $('#emp-form');
        state.editing = null; f.reset(); clearErrors(f);
        f.is_active.checked = true;
        $('#emp-title').textContent = 'إضافة موظف';
        open($('#emp-modal'));
    });

    $('#rows').addEventListener('click', async (e) => {
        const el = e.target.closest('button[data-act]');
        if (!el) return;
        const m = state.items[el.dataset.id];
        if (el.dataset.act === 'edit') {
            const f = $('#emp-form');
            state.editing = m.id; f.reset(); clearErrors(f);
            f.name.value = m.name; f.branch_id.value = m.branch_id ?? ''; f.is_active.checked = m.is_active;
            $('#emp-title').textContent = 'تعديل موظف';
            open($('#emp-modal'));
        } else {
            try { const r = await api(`${base}/${m.id}/toggle`, { method: 'PATCH' }); toast(r.message); load(); } catch (err) { fail(err); }
        }
    });

    $('#emp-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        const f = e.target;
        const body = { name: f.name.value, branch_id: f.branch_id.value || null, is_active: f.is_active.checked };
        try {
            const r = await api(state.editing ? `${base}/${state.editing}` : base, { method: state.editing ? 'PUT' : 'POST', body });
            toast(r.message); close($('#emp-modal')); load();
        } catch (err) { fail(err, f); }
    });

    load();
});
</script>
@endpush
