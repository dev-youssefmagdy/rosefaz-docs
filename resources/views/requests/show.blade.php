@extends('layouts.app')

@section('title', 'طلب '.$contractRequest->ref_no)

@section('content')
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3 print:hidden">
        <h1 class="text-xl font-bold">طلب توثيق عقد <span dir="ltr">{{ $contractRequest->ref_no }}</span></h1>
        <div class="flex gap-2">
            <a href="{{ route('requests.index') }}" class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm hover:bg-gray-50">رجوع إلى الطلبات</a>
            <button type="button" onclick="window.print()" class="rounded-md bg-rose-700 px-4 py-2 text-sm font-bold text-white hover:bg-rose-800">طباعة</button>
        </div>
    </div>

    <div class="rounded-xl bg-white shadow print:rounded-none print:shadow-none">
        @include('requests._document', ['request' => $contractRequest, 'mode' => 'view'])
    </div>

    <section id="notes-panel" class="mt-6 rounded-xl bg-white p-6 shadow print:hidden">
        <h2 class="mb-4 text-lg font-bold">سجل الملاحظات</h2>

        @if ($canAddNote)
            <form id="note-form" class="mb-6" novalidate>
                <textarea name="body" rows="3" maxlength="2000" placeholder="اكتب ملاحظة…" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-rose-700 focus:outline-none"></textarea>
                <p data-error-for="body" class="mt-1 hidden text-xs text-red-700"></p>
                <div class="mt-2 flex items-center justify-between gap-3">
                    <span class="text-xs text-gray-500">الملاحظات لا يمكن تعديلها أو حذفها بعد الإضافة.</span>
                    <button type="submit" class="rounded-md bg-rose-700 px-5 py-2 text-sm font-bold text-white hover:bg-rose-800 disabled:cursor-wait disabled:opacity-60">إضافة ملاحظة</button>
                </div>
            </form>
        @endif

        <ol id="notes-list" class="space-y-4 border-s-2 border-rose-200 ps-4">
            <li id="notes-empty" class="text-sm text-gray-500">جاري التحميل…</li>
        </ol>
    </section>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', async () => {
    const { api, toast, showErrors, clearErrors, el } = window.App;
    const list = document.getElementById('notes-list');
    const form = document.getElementById('note-form');
    const notesUrl = @json(route('requests.notes.index', $contractRequest));

    function item(n) {
        return el('li', { class: 'relative' }, [
            el('span', { class: 'absolute -start-[1.4rem] top-1.5 h-2.5 w-2.5 rounded-full bg-rose-700' }),
            el('div', { class: 'flex flex-wrap items-center gap-x-3 text-xs text-gray-500' }, [
                el('span', { class: 'text-sm font-bold text-gray-900', text: n.author }),
                el('span', { text: [n.role, n.branch].filter(Boolean).join(' - ') }),
                el('span', { dir: 'ltr', text: n.created_at }),
            ]),
            el('p', { class: 'mt-1 whitespace-pre-line text-sm', text: n.body }),
        ]);
    }

    function setEmpty(show) {
        document.getElementById('notes-empty')?.remove();
        if (show) list.replaceChildren(el('li', { id: 'notes-empty', class: 'text-sm text-gray-500', text: 'لا توجد ملاحظات.' }));
    }

    try {
        const res = await api(notesUrl);
        if (res.data.length) { setEmpty(false); res.data.forEach((n) => list.appendChild(item(n))); } else setEmpty(true);
    } catch (e) {
        toast(e.message || 'تعذر تحميل الملاحظات.', 'error');
    }

    form?.addEventListener('submit', async (e) => {
        e.preventDefault();
        clearErrors(form);
        const btn = form.querySelector('button[type="submit"]');
        btn.disabled = true;
        try {
            const res = await api(notesUrl, { method: 'POST', body: { body: form.body.value } });
            setEmpty(false);
            list.prepend(item(res.data));
            form.reset();
            toast(res.message);
        } catch (err) {
            if (err.status === 422) showErrors(form, err.errors);
            else toast(err.message || 'تعذر إضافة الملاحظة.', 'error');
        } finally {
            btn.disabled = false;
        }
    });
});
</script>
@endpush
