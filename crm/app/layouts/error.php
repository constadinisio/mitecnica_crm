<?php
declare(strict_types=1);

$appConfig = require dirname(__DIR__, 2) . '/config/app.php';
$code = $code ?? 404;
$title = $title ?? 'Error';
$message = $message ?? 'Ocurrió un problema.';
?>
<!doctype html>
<html lang="es" class="h-full">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= e($code) ?> — <?= e($title) ?></title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= asset('css/output.css') ?>">
</head>
<body class="h-full bg-ink text-slate-100 font-sans antialiased">
  <div class="min-h-screen flex items-center justify-center px-6">
    <div class="max-w-md text-center">
      <div class="inline-flex items-center justify-center h-16 w-16 rounded-2xl bg-slate-800/70 border border-slate-700 mb-6">
        <span class="text-2xl font-bold text-brand-400"><?= e($code) ?></span>
      </div>
      <h1 class="text-2xl font-semibold text-white"><?= e($title) ?></h1>
      <p class="mt-2 text-slate-400"><?= e($message) ?></p>
      <a href="/dashboard" class="btn-primary mt-6 inline-flex">Volver al inicio</a>
    </div>
  </div>
</body>
</html>
