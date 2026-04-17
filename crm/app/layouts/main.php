<?php
declare(strict_types=1);

/**
 * Main authenticated layout.
 * Variables expected:
 *   - string $title
 *   - string $content (rendered HTML)
 *   - string|null $activeRoute (optional)
 */

$appConfig = require dirname(__DIR__, 2) . '/config/app.php';
$user = auth_user();
$flashes = flash_pull();
?>
<!doctype html>
<html lang="es" class="h-full">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
  <title><?= e($title ?? $appConfig['name']) ?> — <?= e($appConfig['brand']['short']) ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= asset('css/output.css') ?>">
</head>
<body class="h-full bg-ink text-slate-100 font-sans antialiased">
  <div class="min-h-screen flex">
    <?php include dirname(__DIR__) . '/components/sidebar.php'; ?>

    <div class="flex-1 flex flex-col min-w-0">
      <?php include dirname(__DIR__) . '/components/topbar.php'; ?>

      <main class="flex-1 px-6 lg:px-10 py-8 overflow-x-hidden">
        <?php include dirname(__DIR__) . '/components/alert.php'; ?>
        <?= $content ?? '' ?>
      </main>

      <footer class="px-6 lg:px-10 py-4 text-xs text-slate-500 border-t border-slate-800/60">
        © <?= date('Y') ?> <?= e($appConfig['brand']['short']) ?> · <?= e($appConfig['brand']['product']) ?>
      </footer>
    </div>
  </div>

  <div id="toast-root" class="fixed bottom-6 right-6 z-50 flex flex-col gap-2"></div>

  <script src="<?= asset('js/app.js') ?>" defer></script>
  <script src="<?= asset('js/api.js') ?>" defer></script>
  <script src="<?= asset('js/toast.js') ?>" defer></script>
  <script src="<?= asset('js/table-filters.js') ?>" defer></script>
  <?php if (!empty($extraScripts) && is_array($extraScripts)): foreach ($extraScripts as $s): ?>
    <script src="<?= e($s) ?>" defer></script>
  <?php endforeach; endif; ?>
</body>
</html>
