/* Mi Tecnica CRM — table filter enhancements (debounced search, auto-submit selects) */
(function () {
  'use strict';

  function debounce(fn, wait) {
    let t;
    return function (...args) {
      clearTimeout(t);
      t = setTimeout(() => fn.apply(this, args), wait);
    };
  }

  document.querySelectorAll('form[data-filters]').forEach((form) => {
    const submit = debounce(() => form.requestSubmit ? form.requestSubmit() : form.submit(), 500);
    form.querySelectorAll('input[type="search"]').forEach((input) => {
      input.addEventListener('input', submit);
    });
    form.querySelectorAll('select').forEach((sel) => {
      sel.addEventListener('change', () => form.requestSubmit ? form.requestSubmit() : form.submit());
    });
  });
})();
