import { api } from './http';
import { toast, showErrors, clearErrors } from './ui';
import { attachHijriPicker } from './hijri-picker';

const hijriFormatter = new Intl.DateTimeFormat('en-US-u-ca-islamic-umalqura', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    timeZone: 'UTC',
});

// 'YYYY-MM-DD' -> 'dd/MM/yyyy' (Umm al-Qura, latin digits); '' when invalid.
export function toHijri(isoDate) {
    if (!/^\d{4}-\d{2}-\d{2}$/.test(isoDate || '')) return '';
    const date = new Date(`${isoDate}T00:00:00Z`);
    if (Number.isNaN(date.getTime())) return '';
    const parts = Object.fromEntries(hijriFormatter.formatToParts(date).map((p) => [p.type, p.value]));
    return `${parts.day}/${parts.month}/${String(parts.year).replace(/\D/g, '')}`;
}

const money = (n) => n.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

function bindDigitBoxes(form) {
    form.querySelectorAll('[data-digits]').forEach((wrap) => {
        const input = wrap.querySelector('input');
        const boxes = wrap.querySelectorAll('[data-box]');
        const render = () => {
            const clean = input.value.replace(/\D/g, '').slice(0, boxes.length);
            if (clean !== input.value) input.value = clean;
            const focused = document.activeElement === input;
            boxes.forEach((box, i) => {
                box.textContent = clean[i] ?? '';
                box.classList.toggle('!bg-yellow-100', focused && i === Math.min(clean.length, boxes.length - 1));
            });
        };
        ['input', 'focus', 'blur'].forEach((ev) => input.addEventListener(ev, render));
        // keep caret at the end so typing/backspace always edits the last digit
        input.addEventListener('keyup', () => input.setSelectionRange(input.value.length, input.value.length));
        render();
    });
}

function bindHijri(form) {
    form.querySelectorAll('[data-hijri-for]').forEach((out) => {
        const input = form.elements[out.dataset.hijriFor];
        if (!input) return;
        const base = () => { out.textContent = toHijri(input.value); };
        const render = attachHijriPicker(out, input, base);
        input.addEventListener('input', render);
        input.addEventListener('change', render);
        render();
    });
}

function bindRemaining(form) {
    const out = form.querySelector('[data-remaining]');
    const rent = form.elements.annual_rent;
    const paid = form.elements.amount_paid;
    if (!out || !rent || !paid) return;
    const render = () => {
        if (rent.value === '') { out.textContent = ''; return; }
        const remaining = (Math.round((Number(rent.value) || 0) * 100) - Math.round((Number(paid.value) || 0) * 100)) / 100;
        out.textContent = money(remaining);
        out.classList.toggle('text-red-600', remaining < 0);
    };
    rent.addEventListener('input', render);
    paid.addEventListener('input', render);
    render();
}

async function loadEmployees(select, url) {
    try {
        const employees = await api(url);
        select.innerHTML = '<option value="">— اختر الموظف —</option>';
        employees.forEach((e) => select.add(new Option(e.name, e.id)));
    } catch {
        select.innerHTML = '<option value="">تعذر تحميل الموظفين</option>';
        toast('تعذر تحميل قائمة الموظفين.', 'error');
    }
}

function payload(form) {
    const el = form.elements;
    const bill = form.querySelector('input[name="needs_electricity_bill"]:checked');
    return {
        full_name: el.full_name.value,
        id_number: el.id_number.value,
        birth_date: el.birth_date.value,
        mobile: el.mobile.value,
        needs_electricity_bill: bill ? bill.value === '1' : null,
        apartment_no: el.apartment_no.value,
        annual_rent: el.annual_rent.value,
        insurance_amount: el.insurance_amount.value,
        authentication_amount: el.authentication_amount.value,
        rental_type: el.rental_type.value,
        contract_duration_months: el.contract_duration_months.value,
        rental_date: el.rental_date.value,
        amount_paid: el.amount_paid.value,
        start_date: el.start_date.value,
        employee_id: el.employee_id.value,
    };
}

export function initRequestForm(form, { storeUrl, employeesUrl }) {
    bindDigitBoxes(form);
    bindHijri(form);
    bindRemaining(form);
    loadEmployees(form.elements.employee_id, employeesUrl);

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        if (!window.confirm('بعد الإرسال لا يمكن التعديل. هل تريد إرسال الطلب؟')) return;

        const submit = form.querySelector('[type="submit"]');
        submit?.setAttribute('disabled', 'disabled');
        clearErrors(form);
        try {
            const res = await api(storeUrl, { method: 'POST', body: payload(form) });
            toast(res.message || 'تم إرسال الطلب بنجاح.');
            window.location.assign(res.url);
        } catch (err) {
            submit?.removeAttribute('disabled');
            if (err.status === 422) {
                showErrors(form, err.errors);
                toast('يرجى تصحيح الأخطاء الموضحة في النموذج.', 'error');
                form.querySelector('[data-error-for]:not(.hidden)')?.closest('td')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
            } else if (err.status === 403) {
                toast('غير مصرح لك بإنشاء طلب.', 'error');
            } else {
                toast(err.message || 'حدث خطأ غير متوقع.', 'error');
            }
        }
    });
}
