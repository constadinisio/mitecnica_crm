<?php
declare(strict_types=1);

/**
 * Vars:
 *   string $name, string $label, string $value (prefilled), string|null $type='text',
 *   string|null $placeholder, string|null $icon (raw SVG), string|null $hint, bool $required
 */
$type = $type ?? 'text';
$value = $value ?? old($name, '');
$error = errors_for($name);
$id = 'f_' . $name;
?>
<label class="block">
  <span class="block text-xs font-medium text-slate-300 mb-1.5"><?= e($label) ?><?= !empty($required) ? ' <span class="text-rose-400">*</span>' : '' ?></span>
  <div class="relative">
    <?php if (!empty($icon)): ?>
      <span class="absolute inset-y-0 left-3 flex items-center text-slate-500"><?= $icon ?></span>
    <?php endif; ?>
    <input id="<?= e($id) ?>" name="<?= e($name) ?>" type="<?= e($type) ?>"
           value="<?= is_string($value) ? $value : e($value) ?>"
           placeholder="<?= e($placeholder ?? '') ?>"
           <?= !empty($required) ? 'required' : '' ?>
           <?= !empty($autocomplete) ? 'autocomplete="' . e($autocomplete) . '"' : '' ?>
           class="input <?= !empty($icon) ? 'pl-9' : '' ?> <?= $error ? 'input-error' : '' ?>">
  </div>
  <?php if ($error): ?>
    <p class="mt-1 text-xs text-rose-400"><?= e($error) ?></p>
  <?php elseif (!empty($hint)): ?>
    <p class="mt-1 text-xs text-slate-500"><?= e($hint) ?></p>
  <?php endif; ?>
</label>
