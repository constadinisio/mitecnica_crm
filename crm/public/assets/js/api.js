/* Thin helper for browser-side fetches to the PHP frontend.
 * The CRM frontend primarily uses server-side rendering; this module is kept
 * minimal and available for progressive enhancements without extra dependencies.
 */
(function (global) {
  'use strict';

  function csrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.getAttribute('content') : null;
  }

  async function request(method, url, body) {
    const headers = { 'Accept': 'application/json' };
    const opts = { method: method.toUpperCase(), headers, credentials: 'same-origin' };
    if (body && typeof body === 'object' && !(body instanceof FormData)) {
      headers['Content-Type'] = 'application/json';
      opts.body = JSON.stringify(body);
    } else if (body) {
      opts.body = body;
    }
    const token = csrfToken();
    if (token && method !== 'GET') headers['X-CSRF-Token'] = token;

    const res = await fetch(url, opts);
    const contentType = res.headers.get('Content-Type') || '';
    let data = null;
    if (contentType.includes('application/json')) {
      data = await res.json().catch(() => null);
    }
    if (!res.ok) {
      const message = data?.errors?.[0]?.message || res.statusText || 'Error';
      const err = new Error(message);
      err.status = res.status;
      err.data = data;
      throw err;
    }
    return data;
  }

  global.api = {
    get:    (url)       => request('GET', url),
    post:   (url, body) => request('POST', url, body),
    put:    (url, body) => request('PUT', url, body),
    patch:  (url, body) => request('PATCH', url, body),
    del:    (url)       => request('DELETE', url),
  };
})(window);
