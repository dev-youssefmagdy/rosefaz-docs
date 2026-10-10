const LOCALE = 'en-US-u-ca-islamic-umalqura';
const parts = new Intl.DateTimeFormat(LOCALE, { day: 'numeric', month: 'numeric', year: 'numeric', timeZone: 'UTC' });
const monthName = new Intl.DateTimeFormat('ar-SA-u-ca-islamic-umalqura-nu-latn', { month: 'long', timeZone: 'UTC' });
const DAY_MS = 86400000;
const WEEKDAYS = ['أحد', 'اثنين', 'ثلاثاء', 'أربعاء', 'خميس', 'جمعة', 'سبت'];

const pad = (n) => String(n).padStart(2, '0');
const toIso = (d) => d.toISOString().slice(0, 10);
const fromIso = (s) => (/^\d{4}-\d{2}-\d{2}$/.test(s || '') ? new Date(`${s}T00:00:00Z`) : null);

function hijriParts(date) {
    const p = Object.fromEntries(parts.formatToParts(date).map((x) => [x.type, x.value]));
    return { y: Number(String(p.year).replace(/\D/g, '')), m: Number(p.month), d: Number(p.day) };
}

// Gregorian Date (UTC) of the 1st of a Hijri month.
function firstOfMonth(y, m) {
    let date = new Date(Date.UTC(2000, 0, 1) + Math.round(((y - 1420) * 12 + (m - 1)) * 29.530588 * DAY_MS));
    for (let i = 0; i < 90; i++) {
        const h = hijriParts(date);
        const diff = (h.y - y) * 12 + (h.m - m);
        if (diff === 0) return new Date(date.getTime() - (h.d - 1) * DAY_MS);
        date = new Date(date.getTime() - Math.sign(diff) * DAY_MS * (Math.abs(diff) > 1 ? 28 : 1));
    }
    return null;
}

function monthDays(y, m) {
    const start = firstOfMonth(y, m);
    const days = [];
    for (let i = 0; i < 30; i++) {
        const d = new Date(start.getTime() + i * DAY_MS);
        const h = hijriParts(d);
        if (h.y !== y || h.m !== m) break;
        days.push(d);
    }
    return { start, days };
}

let openPopup = null;
function closePopup() {
    openPopup?.remove();
    openPopup = null;
}
document.addEventListener('keydown', (e) => e.key === 'Escape' && closePopup());
document.addEventListener('mousedown', (e) => {
    if (openPopup && !openPopup.contains(e.target) && !e.target.closest('[data-hijri-trigger]')) closePopup();
});

const tag = (name, cls, text) => {
    const n = document.createElement(name);
    if (cls) n.className = cls;
    if (text !== undefined) n.textContent = text;
    return n;
};

function openFor(anchor, input) {
    closePopup();
    const min = fromIso(input.min);
    const max = fromIso(input.max);
    const selected = fromIso(input.value);
    const today = new Date(`${toIso(new Date())}T00:00:00Z`);
    const view = hijriParts(selected || max || today);
    const nowYear = hijriParts(today).y;
    const yearFrom = min ? hijriParts(min).y : 1300;
    const yearTo = Math.max(max ? hijriParts(max).y : nowYear + 10, view.y);

    const pop = tag('div', 'fixed z-50 w-72 rounded-lg border border-gray-300 bg-white p-3 shadow-xl text-sm');
    pop.dir = 'rtl';
    pop.setAttribute('role', 'dialog');
    pop.setAttribute('aria-label', 'التقويم الهجري');

    const head = tag('div', 'mb-2 flex items-center gap-1');
    const next = tag('button', 'rounded px-2 py-1 hover:bg-gray-100', '›');
    const prev = tag('button', 'rounded px-2 py-1 hover:bg-gray-100', '‹');
    [next, prev].forEach((b) => (b.type = 'button'));
    const monthSel = tag('select', 'flex-1 rounded border border-gray-300 px-1 py-1');
    for (let m = 1; m <= 12; m++) monthSel.add(new Option(monthName.format(firstOfMonth(1446, m)), m));
    const yearSel = tag('select', 'w-20 rounded border border-gray-300 px-1 py-1');
    for (let y = yearTo; y >= yearFrom; y--) yearSel.add(new Option(y, y));
    head.append(next, monthSel, yearSel, prev);

    const grid = tag('div', 'grid grid-cols-7 gap-1 text-center');
    pop.append(head, grid);

    const foot = tag('div', 'mt-2 flex justify-between');
    const todayBtn = tag('button', 'text-blue-700 hover:underline', 'اليوم');
    const clearBtn = tag('button', 'text-gray-600 hover:underline', 'مسح');
    [todayBtn, clearBtn].forEach((b) => (b.type = 'button'));
    foot.append(todayBtn, clearBtn);
    pop.append(foot);

    const commit = (iso) => {
        input.value = iso;
        input.dispatchEvent(new Event('input', { bubbles: true }));
        input.dispatchEvent(new Event('change', { bubbles: true }));
        closePopup();
    };
    const inRange = (d) => (!min || d >= min) && (!max || d <= max);

    const render = () => {
        monthSel.value = view.m;
        yearSel.value = view.y;
        grid.replaceChildren(...WEEKDAYS.map((w) => tag('div', 'text-xs text-gray-500', w)));
        const { start, days } = monthDays(view.y, view.m);
        for (let i = 0; i < start.getUTCDay(); i++) grid.append(tag('div'));
        days.forEach((d, i) => {
            const btn = tag('button', 'rounded py-1', String(i + 1));
            btn.type = 'button';
            const isSel = selected && toIso(d) === toIso(selected);
            btn.className += isSel ? ' bg-blue-700 text-white' : ' hover:bg-yellow-100';
            if (toIso(d) === toIso(today) && !isSel) btn.className += ' ring-1 ring-blue-400';
            if (!inRange(d)) {
                btn.disabled = true;
                btn.className += ' text-gray-300 cursor-not-allowed';
            }
            btn.title = toIso(d);
            btn.addEventListener('click', () => commit(toIso(d)));
            grid.append(btn);
        });
    };
    const step = (delta) => {
        view.m += delta;
        if (view.m > 12) { view.m = 1; view.y++; }
        if (view.m < 1) { view.m = 12; view.y--; }
        view.y = Math.min(Math.max(view.y, yearFrom), yearTo);
        render();
    };
    next.addEventListener('click', () => step(-1));
    prev.addEventListener('click', () => step(1));
    monthSel.addEventListener('change', () => { view.m = Number(monthSel.value); render(); });
    yearSel.addEventListener('change', () => { view.y = Number(yearSel.value); render(); });
    todayBtn.addEventListener('click', () => (inRange(today) ? commit(toIso(today)) : null));
    clearBtn.addEventListener('click', () => commit(''));

    render();
    document.body.append(pop);
    openPopup = pop;
    const r = anchor.getBoundingClientRect();
    const left = Math.min(Math.max(8, r.left + r.width / 2 - 144), window.innerWidth - 296);
    const below = r.bottom + 4 + pop.offsetHeight < window.innerHeight;
    pop.style.left = `${left}px`;
    pop.style.top = `${below ? r.bottom + 4 : Math.max(8, r.top - pop.offsetHeight - 4)}px`;
}

// Turns the read-only Hijri output into a clickable picker bound to the Gregorian input.
export function attachHijriPicker(out, input, render) {
    out.dataset.hijriTrigger = '1';
    out.tabIndex = 0;
    out.setAttribute('role', 'button');
    out.className += ' cursor-pointer rounded bg-yellow-200 px-2 py-1 ring-1 ring-gray-400 hover:bg-yellow-300';
    out.title = 'اختر التاريخ الهجري';
    const open = () => openFor(out, input);
    out.addEventListener('click', open);
    out.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); open(); }
    });
    return () => {
        render();
        if (!out.textContent) out.textContent = 'اختر التاريخ الهجري';
    };
}
