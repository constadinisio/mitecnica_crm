<?php
declare(strict_types=1);

/**
 * Vars: string $name, string $label, string|null $value, int $rows, string|null $hint, bool $required
 */
$value = $value ?? old($name, '');
$rows = $rows ?? 4;
$error = errors_for($name);
$id = 'f_' . $name;
?>
<label class="block">
  <span class="block text-xs font-medium text-slate-300 mb-1.5"><?= e($label) ?><?= !empty($required) ? ' <span class="text-rose-400">*</span>' : '' ?></span>
  <textarea id="<?= e($id) ?>" name="<?= e($name) ?>" rows="<?= (int)$rows ?>" class="input <?= $error ? 'input-error' : '' ?>" <?= !empty($required) ? 'required' : '' ?> placeholder="<?= e($placeholder ?? '') ?>"><?= is_string($value) ? $value : e($value) ?></textarea>
  <?php if ($error): ?>
    <p class="mt-1 text-xs text-rose-400"><?= e($error) ?></p>
  <?php elseif (!empty($hint)): ?>
    <p class="mt-1 text-xs text-slate-500"><?= e($hint) ?></p>
  <?php endif; ?>
</label>
