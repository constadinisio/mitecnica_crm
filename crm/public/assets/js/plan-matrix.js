/* Plan × Modules matrix interactions. Each "plan column" has a hidden form
 * with all module checkboxes; the visible cells mirror the hidden form via
 * data-attributes. The plan's "Guardar" button submits that form.
 */
(function () {
  'use strict';

  function updatePlanCount(planId) {
    const cells = document.querySelectorAll(`[data-matrix-cell][data-plan-id="${planId}"]`);
    let count = 0;
    cells.forEach((c) => { if (c.checked) count += 1; });
    const label = document.querySelector(`[data-plan-count="${planId}"]`);
    if (label) label.textContent = count;
  }

  function mirrorToHiddenForm(planId, moduleId, checked) {
    const hidden = document.querySelector(`[data-plan-form-cell="${planId}-${moduleId}"]`);
    if (hidden) hidden.checked = checked;
  }

  document.querySelectorAll('[data-matrix-cell]').forEach((cell) => {
    cell.addEventListener('change', () => {
      const planId = cell.getAttribute('data-plan-id');
      const moduleId = cell.getAttribute('data-module-id');
      mirrorToHiddenForm(planId, moduleId, cell.checked);
      updatePlanCount(planId);
    });
  });

  document.querySelectorAll('[data-plan-save]').forEach((btn) => {
    btn.addEventListener('click', () => {
      const planId = btn.getAttribute('data-plan-save');
      const form = document.getElementById(`plan-form-${planId}`);
      if (form) form.submit();
    });
  });
})();
