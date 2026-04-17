/* Mi Tecnica CRM — base interactions */
(function () {
  'use strict';

  function openModal(id) {
    const el = document.getElementById(id);
    if (!el) return;
    el.classList.remove('hidden');
    document.body.classList.add('overflow-hidden');
  }

  function closeModal(el) {
    if (!el) return;
    el.classList.add('hidden');
    document.body.classList.remove('overflow-hidden');
  }

  document.addEventListener('click', (event) => {
    const opener = event.target.closest('[data-modal-open]');
    if (opener) {
      event.preventDefault();
      openModal(opener.getAttribute('data-modal-open'));
    }
    const dismiss = event.target.closest('[data-modal-dismiss]');
    if (dismiss) {
      const modal = dismiss.closest('[data-modal]');
      closeModal(modal);
    }
  });

  document.addEventListener('keydown', (event) => {
    if (event.key !== 'Escape') return;
    document.querySelectorAll('[data-modal]:not(.hidden)').forEach(closeModal);
  });

  // Auto-dismiss flashes after a moment (the server renders them once).
  setTimeout(() => {
    document.querySelectorAll('[data-flash-auto-dismiss]').forEach((el) => {
      el.style.opacity = '0';
      setTimeout(() => el.remove(), 400);
    });
  }, 6000);

  // Simple keyboard shortcut: "/" focuses the global search.
  document.addEventListener('keydown', (event) => {
    if (event.key === '/' && !/input|textarea|select/i.test(event.target.tagName)) {
      const input = document.querySelector('[data-global-search]');
      if (input) {
        event.preventDefault();
        input.focus();
      }
    }
  });
})();
