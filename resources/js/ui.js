export function toast(message, type = 'success') {
    const container = document.getElementById('toast-container');
    if (!container) return;
    const el = document.createElement('div');
    el.className = `rounded-md px-4 py-2 text-sm text-white shadow-lg ${type === 'error' ? 'bg-red-600' : 'bg-green-600'}`;
    el.textContent = message;
    container.appendChild(el);
    setTimeout(() => el.remove(), 4000);
}

export function clearErrors(form) {
    form.querySelectorAll('[data-error-for]').forEach((el) => {
        el.textContent = '';
        el.classList.add('hidden');
    });
    form.parentElement?.querySelector('.js-form-error-alert')?.remove();
}

export function showErrors(form, errors = {}) {
    clearErrors(form);
    const unmatched = [];
    Object.entries(errors).forEach(([field, messages]) => {
        const el = form.querySelector(`[data-error-for="${field}"]`);
        if (el) {
            el.textContent = Array.isArray(messages) ? messages[0] : messages;
            el.classList.remove('hidden');
        } else {
            unmatched.push(Array.isArray(messages) ? messages[0] : messages);
        }
    });
    if (unmatched.length > 0) {
        const alert = document.createElement('div');
        alert.className = 'js-form-error-alert rounded-md bg-red-50 border border-red-300 p-4 mb-4 text-sm text-red-700';
        alert.setAttribute('role', 'alert');
        alert.innerHTML = unmatched.map((m) => `<p>${m}</p>`).join('');
        form.insertAdjacentElement('beforebegin', alert);
    }
}

export function debounce(fn, delay = 300) {
    let timer;
    return (...args) => {
        clearTimeout(timer);
        timer = setTimeout(() => fn(...args), delay);
    };
}

// meta: { current_page, last_page }; onPage(page) is called on click.
export function renderPagination(container, meta, onPage) {
    container.innerHTML = '';
    if (!meta || meta.last_page <= 1) return;
    const make = (label, page, { active = false, disabled = false } = {}) => {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.textContent = label;
        btn.disabled = disabled;
        btn.className = `px-3 py-1 rounded border text-sm ${active ? 'bg-rose-700 text-white border-rose-700' : 'bg-white border-gray-300 hover:bg-gray-50'} ${disabled ? 'opacity-50 cursor-not-allowed' : ''}`;
        if (!disabled && !active) btn.addEventListener('click', () => onPage(page));
        container.appendChild(btn);
    };
    make('السابق', meta.current_page - 1, { disabled: meta.current_page <= 1 });
    for (let p = 1; p <= meta.last_page; p++) {
        if (p === 1 || p === meta.last_page || Math.abs(p - meta.current_page) <= 2) {
            make(String(p), p, { active: p === meta.current_page });
        }
    }
    make('التالي', meta.current_page + 1, { disabled: meta.current_page >= meta.last_page });
}

// Build a DOM element safely: text goes through textContent, never innerHTML.
// el('td', { class: 'p-2', text: name, dir: 'ltr', dataset: { id: 1 } }, [child, ...])
export function el(tag, { text, class: className, dataset, ...attrs } = {}, children = []) {
    const node = document.createElement(tag);
    if (className) node.className = className;
    if (text !== undefined && text !== null) node.textContent = String(text);
    Object.entries(dataset ?? {}).forEach(([k, v]) => { node.dataset[k] = v; });
    Object.entries(attrs).forEach(([k, v]) => { if (v !== undefined && v !== null && v !== false) node.setAttribute(k, v === true ? '' : v); });
    children.filter(Boolean).forEach((c) => node.append(c));
    return node;
}

// Replace a tbody's content with a single "empty" row.
export function emptyRow(tbody, colspan, message) {
    tbody.replaceChildren(el('tr', {}, [el('td', { colspan, class: 'p-4 text-center text-gray-500', text: message })]));
}
