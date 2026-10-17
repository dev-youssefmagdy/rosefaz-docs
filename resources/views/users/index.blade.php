@extends('layouts.app')

@section('title', 'المستخدمون')

@section('content')
    @php
        $input = 'w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-rose-700 focus:outline-none';
        $err = 'mt-1 text-xs text-red-600 hidden';
    @endphp
    <div class="bg-white rounded-xl shadow p-6">
        <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
            <h1 class="text-xl font-bold">المستخدمون</h1>
            <div class="flex flex-wrap items-center gap-2">
                <select id="filter-role" class="{{ $input }} w-36">
                    <option value="">كل الأدوار</option>
                    @foreach ($roles as $r)
                        <option value="{{ $r->value }}">{{ $r->label() }}</option>
                    @endforeach
                </select>
                <select id="filter-branch" class="{{ $input }} w-44">
                    <option value="">كل الفروع</option>
                    @foreach ($branches as $b)
                        <option value="{{ $b->id }}">{{ $b->name }}</option>
                    @endforeach
                </select>
                <select id="filter-status" class="{{ $input }} w-32">
                    <option value="">كل الحالات</option>
                    <option value="active">فعّال</option>
                    <option value="inactive">موقوف</option>
                </select>
                <input id="search" type="search" placeholder="بحث بالاسم أو البريد" class="{{ $input }} w-56">
                <button id="add-btn" type="button" class="rounded-md bg-rose-700 px-4 py-2 text-sm text-white hover:bg-rose-800">إضافة مستخدم</button>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-start">
                <thead class="bg-gray-50 text-gray-600">
                    <tr>
                        <th class="p-2 text-start">الاسم</th>
                        <th class="p-2 text-start">البريد الإلكتروني</th>
                        <th class="p-2 text-start">الدور</th>
                        <th class="p-2 text-start">الفرع</th>
                        <th class="p-2 text-start">الطلبات</th>
                        <th class="p-2 text-start">الحالة</th>
                        <th class="p-2 text-start">إجراءات</th>
                    </tr>
                </thead>
                <tbody id="rows"></tbody>
            </table>
        </div>
        <div id="pagination" class="mt-4 flex flex-wrap gap-1"></div>
    </div>

    <div id="user-modal" class="fixed inset-0 z-40 hidden items-center justify-center bg-black/40 p-4">
        <form id="user-form" class="w-full max-w-md rounded-xl bg-white p-6 shadow-xl space-y-4" novalidate>
            <h2 id="user-title" class="text-lg font-bold"></h2>
            <div>
                <label class="block mb-1 text-sm">الاسم</label>
                <input name="name" class="{{ $input }}">
                <p data-error-for="name" class="{{ $err }}"></p>
            </div>
            <div>
                <label class="block mb-1 text-sm">البريد الإلكتروني</label>
                <input name="email" type="email" dir="ltr" class="{{ $input }}">
                <p data-error-for="email" class="{{ $err }}"></p>
            </div>
            <div>
                <label class="block mb-1 text-sm">الدور</label>
                <select name="role" class="{{ $input }}">
                    @foreach ($roles as $r)
                        <option value="{{ $r->value }}">{{ $r->label() }}</option>
                    @endforeach
                </select>
                <p data-error-for="role" class="{{ $err }}"></p>
            </div>
            <div id="branch-wrap">
                <label class="block mb-1 text-sm">الفروع <span class="text-gray-400 text-xs">(يمكن اختيار أكثر من فرع)</span></label>
                <select name="branch_ids[]" multiple class="{{ $input }}">
                    @foreach ($branches as $b)
                        <option value="{{ $b->id }}">{{ $b->name }}</option>
                    @endforeach
                </select>
                <p data-error-for="branch_ids" class="{{ $err }}"></p>
            </div>
            <div>
                <label class="block mb-1 text-sm">كلمة المرور</label>
                <div class="flex gap-2">
                    <input name="password" type="text" dir="ltr" autocomplete="new-password" class="{{ $input }}">
                    <button type="button" id="gen-pass" class="shrink-0 rounded-md border border-gray-300 px-3 text-xs hover:bg-gray-50">توليد</button>
                </div>
                <p data-error-for="password" class="{{ $err }}"></p>
            </div>
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" checked> فعّال</label>
            <p data-error-for="user" class="{{ $err }}"></p>
            <div class="flex justify-end gap-2">
                <button type="button" data-close class="rounded-md border border-gray-300 px-4 py-2 text-sm">إلغاء</button>
                <button type="submit" class="rounded-md bg-rose-700 px-4 py-2 text-sm text-white">حفظ</button>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
<style>
    /* Fit Select2 into the existing Tailwind input style */
    .select2-container--default .select2-selection--multiple {
        border-color: #d1d5db;
        border-radius: 0.375rem;
        min-height: 2.5rem;
        padding: 0.125rem 0.375rem;
    }
    .select2-container--default.select2-container--focus .select2-selection--multiple {
        border-color: #be123c;
        outline: none;
        box-shadow: none;
    }
    .select2-container--default .select2-selection--multiple .select2-selection__choice {
        background-color: #be123c;
        color: #fff;
        border: none;
        border-radius: 0.25rem;
        padding: 0.1rem 0.5rem;
        margin: 0.15rem 0.15rem;
    }
    .select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
        color: rgba(255,255,255,0.8);
        margin-left: 0.25rem;
        margin-right: 0;
    }
    .select2-container--default .select2-selection--multiple .select2-selection__choice__remove:hover { color: #fff; }
    .select2-container { width: 100% !important; }
    .select2-dropdown { border-color: #d1d5db; border-radius: 0.375rem; font-size: 0.875rem; }
    .select2-results__option--highlighted { background-color: #be123c !important; }
    .select2-search--dropdown .select2-search__field { border-color: #d1d5db; border-radius: 0.25rem; padding: 0.25rem 0.5rem; }
</style>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const { api, toast, showErrors, clearErrors, debounce, renderPagination, el, emptyRow } = window.App;
    const qs = (s) => document.querySelector(s);
    const base = @json(url('/users'));
    const state = { q: '', role: '', branch: '', status: '', page: 1, items: {}, editing: null };
    const btn = 'rounded border border-gray-300 px-2 py-1 text-xs hover:bg-gray-50';
    const action = (act, id, text, extra = '') => el('button', { type: 'button', class: `${btn} ${extra}`, dataset: { act, id }, text });
    const open = (m) => { m.classList.remove('hidden'); m.classList.add('flex'); };
    const close = (m) => { m.classList.add('hidden'); m.classList.remove('flex'); };
    document.querySelectorAll('[data-close]').forEach((b) => b.addEventListener('click', () => close(b.closest('.fixed'))));
    const fail = (e, form) => {
        if (e.status === 422) {
            if (form) showErrors(form, e.errors); else toast(Object.values(e.errors)[0]?.[0] ?? 'خطأ', 'error');
        } else toast(e.message || 'حدث خطأ غير متوقع.', 'error');
    };

    // Select2 on the branches multi-select
    const $branchSel = jQuery('select[name="branch_ids[]"]');
    $branchSel.select2({
        dir: 'rtl',
        placeholder: '— اختر الفروع —',
        allowClear: true,
        width: '100%',
        dropdownParent: jQuery('#user-modal'),
        language: {
            noResults: () => 'لا توجد نتائج',
            searching: () => 'جارٍ البحث…',
        },
    });

    const syncBranch = () => {
        qs('#branch-wrap').classList.toggle('hidden', qs('#user-form').role.value !== 'branch_manager');
    };
    qs('#user-form').role.addEventListener('change', syncBranch);

    qs('#gen-pass').addEventListener('click', () => {
        const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789';
        const bytes = crypto.getRandomValues(new Uint32Array(12));
        qs('#user-form').password.value = Array.from(bytes, (b) => chars[b % chars.length]).join('');
    });

    async function load() {
        const params = new URLSearchParams({ q: state.q, role: state.role, branch_id: state.branch, status: state.status, page: state.page });
        let res;
        try { res = await api(`${base}/list?${params}`); } catch (err) { fail(err); return; }
        state.items = {};
        const rows = qs('#rows');
        if (!res.data.length) emptyRow(rows, 7, 'لا يوجد مستخدمون.');
        else rows.replaceChildren(...res.data.map((u) => {
            state.items[u.id] = u;
            const actions = [action('edit', u.id, 'تعديل')];
            if (!u.is_self) {
                actions.push(action('toggle', u.id, u.is_active ? 'إيقاف' : 'تفعيل'));
                if (!u.requests_count) actions.push(action('delete', u.id, 'حذف', 'text-red-700'));
            }
            return el('tr', { class: 'border-t' }, [
                el('td', { class: 'p-2', text: u.is_self ? `${u.name} (أنت)` : u.name }),
                el('td', { class: 'p-2', dir: 'ltr', text: u.email }),
                el('td', { class: 'p-2', text: u.role_label }),
                el('td', { class: 'p-2', text: u.branch ? u.branch.map((b) => b.name).join('، ') : '—' }),
                el('td', { class: 'p-2', text: u.requests_count }),
                el('td', { class: 'p-2' }, [el('span', { class: `rounded-full px-2 py-0.5 text-xs ${u.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-200 text-gray-700'}`, text: u.is_active ? 'فعّال' : 'موقوف' })]),
                el('td', { class: 'p-2 flex gap-1' }, actions),
            ]);
        }));
        renderPagination(qs('#pagination'), res.meta, (p) => { state.page = p; load(); });
    }

    const refilter = (key) => (e) => { state[key] = e.target.value.trim(); state.page = 1; load(); };
    qs('#search').addEventListener('input', debounce(refilter('q')));
    qs('#filter-role').addEventListener('change', refilter('role'));
    qs('#filter-branch').addEventListener('change', refilter('branch'));
    qs('#filter-status').addEventListener('change', refilter('status'));

    qs('#add-btn').addEventListener('click', () => {
        const f = qs('#user-form');
        state.editing = null; f.reset(); clearErrors(f);
        $branchSel.val([]).trigger('change');
        f.is_active.checked = true;
        f.password.placeholder = 'كلمة المرور (8 أحرف على الأقل)';
        qs('#user-title').textContent = 'إضافة مستخدم';
        syncBranch();
        open(qs('#user-modal'));
    });

    qs('#rows').addEventListener('click', async (e) => {
        const b = e.target.closest('button[data-act]');
        if (!b) return;
        const u = state.items[b.dataset.id];
        if (b.dataset.act === 'edit') {
            const f = qs('#user-form');
            state.editing = u.id; f.reset(); clearErrors(f);
            f.name.value = u.name; f.email.value = u.email; f.role.value = u.role;
            $branchSel.val(u.branch_ids ?? []).trigger('change');
            f.is_active.checked = u.is_active; f.password.value = '';
            f.password.placeholder = 'كلمة مرور جديدة (اختياري)';
            f.role.disabled = u.is_self; f.is_active.disabled = u.is_self;
            qs('#user-title').textContent = 'تعديل مستخدم';
            syncBranch();
            open(qs('#user-modal'));
        } else if (b.dataset.act === 'toggle') {
            try { toast((await api(`${base}/${u.id}/toggle`, { method: 'PATCH' })).message); load(); } catch (err) { fail(err); }
        } else if (window.confirm(`حذف المستخدم ${u.name}؟`)) {
            try { toast((await api(`${base}/${u.id}`, { method: 'DELETE' })).message); load(); } catch (err) { fail(err); }
        }
    });

    qs('#user-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        const f = e.target;
        clearErrors(f);
        const self = state.editing && state.items[state.editing].is_self;
        const branchIds = Array.from(f['branch_ids[]'].selectedOptions).map((o) => parseInt(o.value, 10));
        const body = {
            name: f.name.value, email: f.email.value, password: f.password.value,
            role: self ? state.items[state.editing].role : f.role.value,
            branch_ids: branchIds.length ? branchIds : null,
            is_active: self ? true : f.is_active.checked,
        };
        try {
            const r = await api(state.editing ? `${base}/${state.editing}` : base, { method: state.editing ? 'PUT' : 'POST', body });
            toast(r.message); close(qs('#user-modal')); load();
        } catch (err) { fail(err, f); }
    });

    load();
});
</script>
@endpush
