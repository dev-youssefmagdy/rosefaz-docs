export async function api(url, { method = 'GET', body = null } = {}) {
    const headers = {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
    };
    const options = { method, headers, credentials: 'same-origin' };

    if (body instanceof FormData) {
        options.body = body;
    } else if (body !== null && body !== undefined) {
        headers['Content-Type'] = 'application/json';
        options.body = JSON.stringify(body);
    }

    const response = await fetch(url, options);
    let data = null;
    try {
        data = await response.json();
    } catch {
        data = null;
    }

    if (!response.ok) {
        throw { status: response.status, errors: data?.errors ?? {}, message: data?.message ?? null };
    }

    return data;
}
