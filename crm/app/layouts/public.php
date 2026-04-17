<?php
declare(strict_types=1);

$appConfig = require dirname(__DIR__, 2) . '/config/app.php';
$flashes = flash_pull();
?>
<!doctype html>
<html lang="es" class="h-full">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
  <title><?= e($title ?? 'Mi Tecnica') ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= asset('css/output.css') ?>">
</head>
<body class="h-full bg-ink text-slate-100 font-sans antialiased">
  <header class="border-b border-slate-800/60">
    <div class="max-w-6xl mx-auto px-6 py-4 flex items-center justify-between">
      <a href="/contact" class="flex items-center gap-3">
        <span class="h-9 w-9 rounded-xl bg-brand-500 grid place-items-center text-white font-bold">MT</span>
        <div>
          <div class="text-sm font-semibold text-white leading-tight">Mi Tecnica</div>
          <div class="text-[11px] text-slate-400">Gestión integral para escuelas técnicas</div>
        </div>
      </a>
      <a href="/login" class="text-xs text-slate-400 hover:text-white">Acceso interno</a>
    </div>
  </header>
  <main class="max-w-6xl mx-auto px-6 py-10">
    <?php include dirname(__DIR__) . '/components/alert.php'; ?>
    <?= $content ?? '' ?>
  </main>
  <footer class="border-t border-slate-800/60 mt-16">
    <div class="max-w-6xl mx-auto px-6 py-6 text-xs text-slate-500">
      © <?= date('Y') ?> Mi Tecnica · Soluciones tecnológicas para instituciones educativas.
    </div>
  </footer>

  <div id="toast-root" class="fixed bottom-6 right-6 z-50 flex flex-col gap-2"></div>
  <script src="<?= asset('js/app.js') ?>" defer></script>
  <script src="<?= asset('js/toast.js') ?>" defer></script>
</body>
</html>
