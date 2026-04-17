<?php
declare(strict_types=1);
$q = $query ?? [];
$statuses = ['' => 'Todos los estados', 'trial' => 'Trial', 'active' => 'Activa', 'suspended' => 'Suspendida', 'expired' => 'Expirada', 'canceled' => 'Cancelada'];
$renewal = ['' => 'Todas las renovaciones', 'manual' => 'Manual', 'automatic' => 'Automática'];
?>
<form method="get" action="/subscriptions" class="card p-4 flex flex-col md:flex-row md:items-center gap-3" data-filters>
  <div class="flex-1 relative">
    <span class="absolute inset-y-0 left-3 flex items-center text-slate-500">
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" /></svg>
    </span>
    <input type="search" name="search" placeholder="Buscar institución o plan..." value="<?= e($q['search'] ?? '') ?>" class="input pl-9 h-10">
  </div>
  <select name="status" class="input h-10 md:w-48">
    <?php foreach ($statuses as $v => $l): ?>
      <option value="<?= e($v) ?>" <?= (($q['status'] ?? '') === $v) ? 'selected' : '' ?>><?= e($l) ?></option>
    <?php endforeach; ?>
  </select>
  <select name="renewal_mode" class="input h-10 md:w-52">
    <?php foreach ($renewal as $v => $l): ?>
      <option value="<?= e($v) ?>" <?= (($q['renewal_mode'] ?? '') === $v) ? 'selected' : '' ?>><?= e($l) ?></option>
    <?php endforeach; ?>
  </select>
  <div class="flex gap-2">
    <button class="btn-primary h-10 px-5">Aplicar</button>
    <a href="/subscriptions" class="btn-ghost h-10 px-5">Limpiar</a>
  </div>
</form>
