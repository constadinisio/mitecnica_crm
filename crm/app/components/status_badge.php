<?php
declare(strict_types=1);

/**
 * Status badge component.
 * Expected vars (set by caller before include):
 *   - string|null $status              : status key (drives label + color)
 *   - string|null $statusOverrideLabel : optional explicit label override
 *
 * Uses scoped locals prefixed with `__sb_` to avoid PHP `include` scope
 * accidentally picking up leftover `$label` variables from the outer scope.
 */
$__sb_status = $status ?? null;
$__sb_label  = $statusOverrideLabel ?? status_label($__sb_status);
$__sb_class  = status_class($__sb_status);
?>
<span class="inline-flex items-center gap-1.5 px-2 py-0.5 text-xs font-medium rounded-full <?= e($__sb_class) ?>">
  <span class="h-1.5 w-1.5 rounded-full bg-current opacity-90"></span>
  <?= e($__sb_label) ?>
</span>
<?php unset($__sb_status, $__sb_label, $__sb_class); if (isset($statusOverrideLabel)) unset($statusOverrideLabel); ?>
