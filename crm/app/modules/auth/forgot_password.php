<?php
declare(strict_types=1);

require_guest();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $email = trim((string)($_POST['email'] ?? ''));
    $errors = [];
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = ['field' => 'email', 'message' => 'Ingresá un email válido'];
    }
    if (!$errors) {
        try {
            api_post('/auth/forgot-password', ['auth' => false, 'body' => ['email' => $email]]);
        } catch (Throwable) {
            // Intentionally ignore — we do not leak whether an account exists.
        }
        flash_set('success', 'Si el email existe, enviaremos instrucciones para recuperar la contraseña.');
        redirect('/login');
    }
    old_set(['email' => $email]);
    errors_set($errors);
    flash_set('error', 'Revisá los campos del formulario.');
    redirect('/forgot-password');
}

ob_start();
?>
<div class="card p-8">
  <h1 class="text-2xl font-semibold text-white">Recuperar contraseña</h1>
  <p class="mt-1 text-sm text-slate-400">Te enviaremos instrucciones al email informado.</p>

  <form method="post" action="/forgot-password" class="mt-6 space-y-4" novalidate>
    <?= csrf_field() ?>
    <?php
      $name = 'email'; $label = 'Email corporativo'; $type = 'email'; $required = true; $autocomplete = 'email';
      $placeholder = 'tu.nombre@mitecnica.ar';
      $icon = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" /></svg>';
      include dirname(__DIR__, 2) . '/components/form_input.php';
    ?>
    <button type="submit" class="btn-primary w-full h-11">Enviar instrucciones</button>
    <div class="text-center text-xs">
      <a href="/login" class="text-brand-300 hover:text-brand-200">← Volver al login</a>
    </div>
  </form>
</div>
<?php
$content = ob_get_clean();
$title = 'Recuperar contraseña';
include dirname(__DIR__, 2) . '/layouts/auth.php';
