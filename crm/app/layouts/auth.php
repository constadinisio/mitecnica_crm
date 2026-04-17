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
  <title><?= e($title ?? 'Ingresar') ?> — <?= e($appConfig['brand']['short']) ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= asset('css/output.css') ?>">
</head>
<body class="h-full bg-ink text-slate-100 font-sans antialiased">
  <div class="min-h-screen flex">
    <aside class="hidden lg:flex lg:w-1/2 relative overflow-hidden bg-gradient-to-br from-slate-950 via-indigo-950 to-slate-900">
      <div class="absolute inset-0 bg-[radial-gradient(circle_at_20%_20%,rgba(59,130,246,0.18),transparent_60%),radial-gradient(circle_at_80%_70%,rgba(99,102,241,0.18),transparent_60%)]"></div>
      <div class="relative z-10 p-14 flex flex-col justify-between text-slate-200 w-full">
        <div class="flex items-center gap-3">
          <span class="h-10 w-10 rounded-xl bg-brand-500 grid place-items-center font-bold text-white text-lg">MT</span>
          <div>
            <div class="text-xl font-semibold"><?= e($appConfig['brand']['short']) ?></div>
            <div class="text-xs text-slate-400"><?= e($appConfig['brand']['product']) ?></div>
          </div>
        </div>
        <div>
          <h2 class="text-3xl font-semibold leading-tight text-white">Backoffice central para la gestión<br>de instituciones técnicas.</h2>
          <p class="mt-4 text-slate-400 max-w-md">Administrá clientes, planes, dominios, estado comercial y técnico desde un único panel profesional.</p>
        </div>
        <div class="text-xs text-slate-500">© <?= date('Y') ?> <?= e($appConfig['brand']['short']) ?></div>
      </div>
    </aside>
    <section class="flex-1 flex items-center justify-center px-6 py-12">
      <div class="w-full max-w-md">
        <?php include dirname(__DIR__) . '/components/alert.php'; ?>
        <?= $content ?? '' ?>
      </div>
    </section>
  </div>

  <div id="toast-root" class="fixed bottom-6 right-6 z-50 flex flex-col gap-2"></div>
  <script src="<?= asset('js/app.js') ?>" defer></script>
  <script src="<?= asset('js/toast.js') ?>" defer></script>
</body>
</html>
