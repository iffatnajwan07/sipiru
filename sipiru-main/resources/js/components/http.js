const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content;

export async function getJson(url, params = {}) {
    const qs = new URLSearchParams(Object.entries(params).filter(([, v]) => v !== null && v !== undefined && v !== ''));
    const res = await fetch(`${url}?${qs}`, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
    if (!res.ok) throw new Error(`HTTP ${res.status}`);
    return res.json();
}

export async function postJson(url, body) {
    const res = await fetch(url, {
        method: 'POST',
        credentials: 'same-origin',
        headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf() },
        body: JSON.stringify(body),
    });
    const data = await res.json().catch(() => ({}));
    return { ok: res.ok, status: res.status, data };
}

export function debounce(fn, wait = 350) {
    let t;
    return (...args) => {
        clearTimeout(t);
        t = setTimeout(() => fn(...args), wait);
    };
}
