<?php
declare(strict_types=1);

require_guest();

$token = (string)($_GET['token'] ?? $_POST['token'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $pw  = (string)($_POST['new_password'] ?? '');
    $pw2 = (string)($_POST['new_password_confirm'] ?? '');
    $errors = [];
    if (strlen($pw) < 8)  $errors[] = ['field' => 'new_password', 'message' => 'Mínimo 8 caracteres'];
    if ($pw !== $pw2)     $errors[] = ['field' => 'new_password_confirm', 'message' => 'Las contraseñas no coinciden'];
    if (!$token)          $errors[] = ['field' => 'token', 'message' => 'Token inválido'];

    if (!$errors) {
        try {
            api_post('/auth/reset-password', ['auth' => false, 'body' => ['token' => $token, 'new_password' => $pw]]);
            flash_set('success', 'Contraseña actualizada. Ingresá con tus nuevas credenciales.');
            redirect('/login');
        } catch (ApiClientException $e) {
            flash_set('error', $e->getMessage() ?: 'No se pudo actualizar la contraseña');
        } catch (Throwable $e) {
            flash_set('error', 'Ocurrió un error inesperado');
        }
    } else {
        errors_set($errors);
        flash_set('error', 'Revisá los campos del formulario.');
    }
    redirect('/reset-password?token=' . urlencode($token));
}

ob_start();
?>
<div class="card p-8">
  <h1 class="text-2xl font-semibold text-white">Definir nueva contraseña</h1>
  <p class="mt-1 text-sm text-slate-400">Elegí una contraseña de al menos 8 caracteres.</p>

  <form method="post" action="/reset-password" class="mt-6 space-y-4" novalidate>
    <?= csrf_field() ?>
    <input type="hidden" name="token" value="<?= e($token) ?>">
    <?php
      $name = 'new_password'; $label = 'Nueva contraseña'; $type = 'password'; $required = true;
      $autocomplete = 'new-password'; $placeholder = '••••••••'; $value = '';
      include dirname(__DIR__, 2) . '/components/form_input.php';
    ?>
    <?php
      $name = 'new_password_confirm'; $label = 'Repetir contraseña'; $type = 'password'; $required = true;
      $autocomplete = 'new-password'; $placeholder = '••••••••'; $value = '';
      include dirname(__DIR__, 2) . '/components/form_input.php';
    ?>
    <button type="submit" class="btn-primary w-full h-11">Actualizar contraseña</button>
  </form>
</div>
<?php
$content = ob_get_clean();
$title = 'Restablecer contraseña';
include dirname(__DIR__, 2) . '/layouts/auth.php';
