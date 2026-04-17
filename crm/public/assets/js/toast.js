/* Mi Tecnica CRM — tiny toast notification system */
(function (global) {
  'use strict';

  const STYLES = {
    success: 'bg-emerald-500/15 border-emerald-500/30 text-emerald-100',
    error:   'bg-rose-500/15 border-rose-500/30 text-rose-100',
    warn:    'bg-amber-500/15 border-amber-500/30 text-amber-100',
    info:    'bg-brand-500/15 border-brand-500/30 text-brand-100',
  };

  function toast(type, message, { duration = 4000 } = {}) {
    const root = document.getElementById('toast-root');
    if (!root) return;
    const cls = STYLES[type] || STYLES.info;
    const el = document.createElement('div');
    el.className = `pointer-events-auto max-w-sm rounded-xl border px-4 py-3 text-sm shadow-lg shadow-slate-950/30 transition-opacity ${cls}`;
    el.textContent = message;
    root.appendChild(el);
    setTimeout(() => {
      el.style.opacity = '0';
      setTimeout(() => el.remove(), 400);
    }, duration);
  }

  global.toast = {
    success: (m, o) => toast('success', m, o),
    error:   (m, o) => toast('error', m, o),
    warn:    (m, o) => toast('warn', m, o),
    info:    (m, o) => toast('info', m, o),
  };
})(window);
