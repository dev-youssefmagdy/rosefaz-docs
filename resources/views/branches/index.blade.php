@extends('layouts.app')

@section('title', 'الفروع')

@section('content')
    @php
        $input = 'w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-rose-700 focus:outline-none';
        $err = 'mt-1 text-xs text-red-600 hidden';
    @endphp
    <div class="bg-white rounded-xl shadow p-6">
        <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
            <h1 class="text-xl font-bold">الفروع</h1>
            <div class="flex items-center gap-2">
                <input id="search" type="search" placeholder="بحث بالاسم أو الرقم" class="{{ $input }} w-64">
                <button id="add-btn" type="button" class="rounded-md bg-rose-700 px-4 py-2 text-sm text-white hover:bg-rose-800">إضافة فرع</button>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-start">
                <thead class="bg-gray-50 text-gray-600">
                    <tr>
                        <th class="p-2 text-start">رقم الفرع</th>
                        <th class="p-2 text-start">الاسم</th>
                        <th class="p-2 text-start">عدد المديرين</th>
                        <th class="p-2 text-start">الحالة</th>
                        <th class="p-2 text-start">إجراءات</th>
                    </tr>
                </thead>
                <tbody id="rows"></tbody>
            </table>
        </div>
        <div id="pagination" class="mt-4 flex flex-wrap gap-1"></div>
    </div>

    {{-- Branch modal --}}
    <div id="branch-modal" class="fixed inset-0 z-40 hidden items-center justify-center bg-black/40 p-4">
        <form id="branch-form" class="w-full max-w-md rounded-xl bg-white p-6 shadow-xl space-y-4" novalidate>
            <h2 id="branch-title" class="text-lg font-bold"></h2>
            <div>
                <label class="block mb-1 text-sm">الاسم</label>
                <input name="name" class="{{ $input }}">
                <p data-error-for="name" class="{{ $err }}"></p>
            </div>
            <div>
                <label class="block mb-1 text-sm">رقم الفرع</label>
                <input name="number" type="number" min="1" class="{{ $input }}">
                <p data-error-for="number" class="{{ $err }}"></p>
            </div>
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" checked> فعّال</label>
            <div class="flex justify-end gap-2">
                <button type="button" data-close class="rounded-md border border-gray-300 px-4 py-2 text-sm">إلغاء</button>
                <button type="submit" class="rounded-md bg-rose-700 px-4 py-2 text-sm text-white">حفظ</button>
            </div>
        </form>
    </div>

    {{-- Managers modal --}}
    <div id="mgr-modal" class="fixed inset-0 z-40 hidden items-center justify-center bg-black/40 p-4">
        <div class="w-full max-w-2xl rounded-xl bg-white p-6 shadow-xl space-y-4 max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between">
                <h2 id="mgr-title" class="text-lg font-bold"></h2>
                <button type="button" data-close class="text-sm text-gray-500">إغلاق</button>
            </div>
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-600"><tr><th class="p-2 text-start">الاسم</th><th class="p-2 text-start">البريد الإلكتروني</th><th class="p-2 text-start">إجراءات</th></tr></thead>
                <tbody id="mgr-rows"></tbody>
            </table>
            <form id="mgr-form" class="grid gap-3 sm:grid-cols-3 border-t pt-4" novalidate>
                <h3 id="mgr-form-title" class="sm:col-span-3 font-medium">إضافة مدير</h3>
                <div>
                    <input name="name" placeholder="الاسم" class="{{ $input }}">
                    <p data-error-for="name" class="{{ $err }}"></p>
                </div>
                <div>
                    <input name="email" type="email" placeholder="البريد الإلكتروني" class="{{ $input }}">
                    <p data-error-for="email" class="{{ $err }}"></p>
                </div>
                <div>
                    <input name="password" type="password" placeholder="كلمة المرور" class="{{ $input }}">
                    <p data-error-for="password" class="{{ $err }}"></p>
                </div>
                <p data-error-for="user" class="{{ $err }} sm:col-span-3"></p>
                <div class="sm:col-span-3 flex justify-end gap-2">
                    <button type="button" id="mgr-cancel" class="hidden rounded-md border border-gray-300 px-4 py-2 text-sm">إلغاء التعديل</button>
                    <button type="submit" class="rounded-md bg-rose-700 px-4 py-2 text-sm text-white">حفظ</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const { api, toast, showErrors, clearErrors, debounce, renderPagination, el, emptyRow } = window.App;
    const $ = (s) => document.querySelector(s);
    const base = @json(url('/branches'));
    const state = { q: '', page: 1, branches: {}, editing: null, mgrBranch: null, mgrEditing: null, mgrs: {} };
    const btn = 'rounded border border-gray-300 px-2 py-1 text-xs hover:bg-gray-50';
    const action = (act, id, text, extra = '') => el('button', { type: 'button', class: `${btn} ${extra}`, dataset: { act, id }, text });
    const badge = (active) => el('span', { class: `rounded-full px-2 py-0.5 text-xs ${active ? 'bg-green-100 text-green-800' : 'bg-gray-200 text-gray-700'}`, text: active ? 'فعّال' : 'موقوف' });
    const open = (m) => { m.classList.remove('hidden'); m.classList.add('flex'); };
    const close = (m) => { m.classList.add('hidden'); m.classList.remove('flex'); };
    document.querySelectorAll('[data-close]').forEach((b) => b.addEventListener('click', () => close(b.closest('.fixed'))));
    const fail = (e, form) => {
        if (e.status === 422) { form ? showErrors(form, e.errors) : toast(Object.values(e.errors)[0]?.[0] ?? 'خطأ', 'error'); }
        else toast(e.message || 'حدث خطأ غير متوقع.', 'error');
    };

    async function load() {
        let res;
        try { res = await api(`${base}/list?q=${encodeURIComponent(state.q)}&page=${state.page}`); } catch (err) { fail(err); return; }
        state.branches = {};
        const rows = $('#rows');
        if (!res.data.length) emptyRow(rows, 5, 'لا توجد فروع.');
        else rows.replaceChildren(...res.data.map((b) => {
            state.branches[b.id] = b;
            return el('tr', { class: 'border-t' }, [
                el('td', { class: 'p-2', text: b.number }),
                el('td', { class: 'p-2', text: b.name }),
                el('td', { class: 'p-2', text: b.managers_count }),
                el('td', { class: 'p-2' }, [badge(b.is_active)]),
                el('td', { class: 'p-2 flex gap-1' }, [
                    action('edit', b.id, 'تعديل'),
                    action('mgr', b.id, 'المديرون'),
                    action('toggle', b.id, b.is_active ? 'إيقاف' : 'تفعيل'),
                ]),
            ]);
        }));
        renderPagination($('#pagination'), res.meta, (p) => { state.page = p; load(); });
    }

    $('#search').addEventListener('input', debounce((e) => { state.q = e.target.value.trim(); state.page = 1; load(); }));

    $('#add-btn').addEventListener('click', () => {
        const f = $('#branch-form');
        state.editing = null; f.reset(); clearErrors(f);
        f.is_active.checked = true;
        $('#branch-title').textContent = 'إضافة فرع';
        open($('#branch-modal'));
    });

    $('#rows').addEventListener('click', async (e) => {
        const el = e.target.closest('button[data-act]');
        if (!el) return;
        const b = state.branches[el.dataset.id];
        if (el.dataset.act === 'edit') {
            const f = $('#branch-form');
            state.editing = b.id; f.reset(); clearErrors(f);
            f.name.value = b.name; f.number.value = b.number; f.is_active.checked = b.is_active;
            $('#branch-title').textContent = 'تعديل فرع';
            open($('#branch-modal'));
        } else if (el.dataset.act === 'toggle') {
            try { const r = await api(`${base}/${b.id}/toggle`, { method: 'PATCH' }); toast(r.message); load(); } catch (err) { fail(err); }
        } else {
            state.mgrBranch = b.id;
            $('#mgr-title').textContent = `مديرو ${b.name}`;
            resetMgrForm();
            open($('#mgr-modal'));
            loadMgrs();
        }
    });

    $('#branch-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        const f = e.target;
        const body = { name: f.name.value, number: f.number.value, is_active: f.is_active.checked };
        try {
            const r = await api(state.editing ? `${base}/${state.editing}` : base, { method: state.editing ? 'PUT' : 'POST', body });
            toast(r.message); close($('#branch-modal')); load();
        } catch (err) { fail(err, f); }
    });

    function resetMgrForm() {
        const f = $('#mgr-form');
        state.mgrEditing = null; f.reset(); clearErrors(f);
        f.password.placeholder = 'كلمة المرور';
        $('#mgr-form-title').textContent = 'إضافة مدير';
        $('#mgr-cancel').classList.add('hidden');
    }
    $('#mgr-cancel').addEventListener('click', resetMgrForm);

    async function loadMgrs() {
        let res;
        try { res = await api(`${base}/${state.mgrBranch}/managers`); } catch (err) { fail(err); return; }
        state.mgrs = {};
        const rows = $('#mgr-rows');
        if (!res.data.length) emptyRow(rows, 3, 'لا يوجد مديرون.');
        else rows.replaceChildren(...res.data.map((u) => {
            state.mgrs[u.id] = u;
            return el('tr', { class: 'border-t' }, [
                el('td', { class: 'p-2', text: u.name }),
                el('td', { class: 'p-2', dir: 'ltr', text: u.email }),
                el('td', { class: 'p-2 flex gap-1' }, [action('edit', u.id, 'تعديل'), action('del', u.id, 'حذف', 'text-red-700')]),
            ]);
        }));
    }

    $('#mgr-rows').addEventListener('click', async (e) => {
        const el = e.target.closest('button[data-act]');
        if (!el) return;
        const u = state.mgrs[el.dataset.id];
        if (el.dataset.act === 'edit') {
            const f = $('#mgr-form');
            clearErrors(f);
            state.mgrEditing = u.id;
            f.name.value = u.name; f.email.value = u.email; f.password.value = '';
            f.password.placeholder = 'كلمة مرور جديدة (اختياري)';
            $('#mgr-form-title').textContent = 'تعديل مدير';
            $('#mgr-cancel').classList.remove('hidden');
        } else if (confirm('هل تريد حذف هذا المدير؟')) {
            try { const r = await api(`${base}/${state.mgrBranch}/managers/${u.id}`, { method: 'DELETE' }); toast(r.message); loadMgrs(); load(); }
            catch (err) { fail(err); }
        }
    });

    $('#mgr-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        const f = e.target;
        const body = { name: f.name.value, email: f.email.value, password: f.password.value };
        const url = `${base}/${state.mgrBranch}/managers` + (state.mgrEditing ? `/${state.mgrEditing}` : '');
        try {
            const r = await api(url, { method: state.mgrEditing ? 'PUT' : 'POST', body });
            toast(r.message); resetMgrForm(); loadMgrs(); load();
        } catch (err) { fail(err, f); }
    });

    load();
});
</script>
@endpush
