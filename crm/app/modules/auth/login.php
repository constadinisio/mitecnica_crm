<?php
declare(strict_types=1);

require_guest();

$appConfig = require dirname(__DIR__, 3) . '/config/app.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $email = trim((string)($_POST['email'] ?? ''));
    $password = (string)($_POST['password'] ?? '');

    $errors = [];
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = ['field' => 'email', 'message' => 'Ingresá un email válido'];
    }
    if (strlen($password) < 1) {
        $errors[] = ['field' => 'password', 'message' => 'Ingresá tu contraseña'];
    }

    if ($errors) {
        old_set(['email' => $email]);
        errors_set($errors);
        flash_set('error', 'Revisá los campos del formulario.');
        redirect('/login');
    }

    $result = auth_login_api($email, $password);
    if ($result === true) {
        old_clear();
        flash_set('success', 'Bienvenido/a de vuelta.');
        redirect('/dashboard');
    }
    old_set(['email' => $email]);
    flash_set('error', is_string($result) ? $result : 'No se pudo iniciar sesión');
    redirect('/login');
}

ob_start();
?>
<div class="card p-8">
  <div class="flex items-center gap-3 mb-6 lg:hidden">
    <span class="h-10 w-10 rounded-xl bg-brand-500 grid place-items-center text-white font-bold">MT</span>
    <div>
      <div class="text-lg font-semibold text-white">Mi Tecnica</div>
      <div class="text-xs text-slate-400">CRM Central</div>
    </div>
  </div>
  <h1 class="text-2xl font-semibold text-white">Iniciar sesión</h1>
  <p class="mt-1 text-sm text-slate-400">Panel de administración — acceso interno</p>

  <form method="post" action="/login" class="mt-6 space-y-4" novalidate>
    <?= csrf_field() ?>
    <?php
      $name = 'email'; $label = 'Email corporativo'; $type = 'email'; $required = true; $autocomplete = 'email';
      $placeholder = 'tu.nombre@mitecnica.ar';
      $icon = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" /></svg>';
      include dirname(__DIR__, 2) . '/components/form_input.php';
    ?>
    <?php
      $name = 'password'; $label = 'Contraseña'; $type = 'password'; $required = true; $autocomplete = 'current-password';
      $placeholder = '••••••••'; $value = '';
      $icon = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" /></svg>';
      include dirname(__DIR__, 2) . '/components/form_input.php';
    ?>

    <div class="flex items-center justify-between text-xs">
      <label class="inline-flex items-center gap-2 text-slate-400">
        <input type="checkbox" name="remember" value="1" class="rounded border-slate-700 bg-slate-900 text-brand-500 focus:ring-brand-500/40"> Mantener sesión
      </label>
      <a href="/forgot-password" class="text-brand-300 hover:text-brand-200">Olvidé mi contraseña</a>
    </div>

    <button type="submit" class="btn-primary w-full h-11">Ingresar</button>

    <?php if (!empty($appConfig['google_oauth'])): ?>
      <div class="relative my-4">
        <div class="absolute inset-0 flex items-center"><span class="w-full border-t border-slate-800"></span></div>
        <div class="relative flex justify-center"><span class="bg-ink px-3 text-xs text-slate-500">o</span></div>
      </div>
      <a href="/auth/google" class="btn-secondary w-full h-11 inline-flex items-center justify-center gap-2">
        <svg viewBox="0 0 24 24" class="h-4 w-4"><path fill="#EA4335" d="M12 10.2v3.9h5.5c-.2 1.4-1.6 4.2-5.5 4.2-3.3 0-6-2.7-6-6.1s2.7-6.1 6-6.1c1.9 0 3.2.8 3.9 1.5l2.7-2.6C16.9 3.4 14.7 2.4 12 2.4 6.8 2.4 2.6 6.6 2.6 12s4.2 9.6 9.4 9.6c5.4 0 9-3.8 9-9.2 0-.6-.1-1.1-.2-1.6H12z"/></svg>
        Continuar con Google
      </a>
    <?php endif; ?>
  </form>
</div>
<p class="text-center text-xs text-slate-500 mt-4">Credencial de desarrollo: admin@mitecnica.local · Admin123!</p>
<?php
$content = ob_get_clean();
$title = 'Ingresar';
include dirname(__DIR__, 2) . '/layouts/auth.php';
