<?php
declare(strict_types=1);

/**
 * Vars:
 *   string $name, string $label, array $options (['value' => 'Label']),
 *   string|null $value, string|null $hint, bool $required, string|null $placeholder
 */
$value = $value ?? old($name, '');
$error = errors_for($name);
$id = 'f_' . $name;
?>
<label class="block">
  <span class="block text-xs font-medium text-slate-300 mb-1.5"><?= e($label) ?><?= !empty($required) ? ' <span class="text-rose-400">*</span>' : '' ?></span>
  <select id="<?= e($id) ?>" name="<?= e($name) ?>" class="input <?= $error ? 'input-error' : '' ?>" <?= !empty($required) ? 'required' : '' ?>>
    <?php if (!empty($placeholder)): ?>
      <option value="" <?= $value === '' ? 'selected' : '' ?> disabled><?= e($placeholder) ?></option>
    <?php endif; ?>
    <?php foreach ($options as $optValue => $optLabel): ?>
      <option value="<?= e($optValue) ?>" <?= ((string)$optValue === (string)$value) ? 'selected' : '' ?>><?= e($optLabel) ?></option>
    <?php endforeach; ?>
  </select>
  <?php if ($error): ?>
    <p class="mt-1 text-xs text-rose-400"><?= e($error) ?></p>
  <?php elseif (!empty($hint)): ?>
    <p class="mt-1 text-xs text-slate-500"><?= e($hint) ?></p>
  <?php endif; ?>
</label>
