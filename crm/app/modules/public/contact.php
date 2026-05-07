<?php
declare(strict_types=1);

/**
 * Public contact / purchase request page.
 * - Rendered without authentication.
 * - On POST, forwards to the API `/public/contact-requests` endpoint which
 *   automatically creates the institution (and a trial subscription if a plan
 *   is chosen) in the CRM.
 */

$submitted = false;
$submittedData = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $body = [
        'institution_name'  => trim((string)($_POST['institution_name'] ?? '')),
        'contact_name'      => trim((string)($_POST['contact_name'] ?? '')),
        'contact_last_name' => trim((string)($_POST['contact_last_name'] ?? '')) ?: null,
        'contact_email'     => trim((string)($_POST['contact_email'] ?? '')),
        'contact_phone'    => trim((string)($_POST['contact_phone'] ?? '')) ?: null,
        'address'          => trim((string)($_POST['address'] ?? '')) ?: null,
        'plan_code'        => trim((string)($_POST['plan_code'] ?? '')) ?: null,
        'notes'            => trim((string)($_POST['notes'] ?? '')) ?: null,
    ];

    try {
        $res = api_post('/public/contact-requests', ['auth' => false, 'body' => $body]);
        $submittedData = $res['data'] ?? null;
        $submitted = true;
        old_clear();
    } catch (ApiClientException $e) {
        old_set($body);
        if ($e->details && is_array($e->details)) errors_set($e->details);
        flash_set('error', $e->getMessage() ?: 'No pudimos registrar tu solicitud. Intentá nuevamente.');
    }
}

$plans = [];
try {
    $plans = api_get('/public/plans', ['auth' => false])['data'] ?? [];
} catch (Throwable) { /* seguimos sin opciones, el form igual funciona */ }

ob_start();
?>

<?php if ($submitted && $submittedData): ?>
  <section class="max-w-2xl mx-auto text-center py-8">
    <div class="inline-flex items-center justify-center h-16 w-16 rounded-2xl bg-emerald-500/15 border border-emerald-500/30 mb-6">
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-8 w-8 text-emerald-300"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
    </div>
    <h1 class="text-2xl font-semibold text-white">¡Listo! Recibimos tu solicitud.</h1>
    <p class="mt-3 text-slate-400">Tu institución <strong class="text-slate-200"><?= e($submittedData['institution']['name']) ?></strong> quedó registrada con código <span class="font-mono text-slate-200"><?= e($submittedData['institution']['public_code']) ?></span>.</p>
    <?php if (!empty($submittedData['plan_selected'])): ?>
      <p class="mt-2 text-slate-400">Te contactaremos en las próximas 24 hs para activar el plan <strong class="text-slate-200"><?= e($submittedData['plan_selected']['name']) ?></strong>.</p>
    <?php else: ?>
      <p class="mt-2 text-slate-400">Te contactaremos en las próximas 24 hs para avanzar con el alta.</p>
    <?php endif; ?>
    <a href="/contact" class="btn-ghost mt-6 inline-flex">Enviar otra solicitud</a>
  </section>
<?php else: ?>
  <section class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    <div class="lg:col-span-1 space-y-6">
      <div>
        <h1 class="text-3xl font-semibold text-white leading-tight">Contanos sobre tu institución</h1>
        <p class="mt-4 text-slate-400">Completá el formulario y el equipo de Mi Tecnica te contacta en menos de 24 hs para armar una propuesta a medida.</p>
      </div>
      <div class="space-y-3 text-sm text-slate-400">
        <div class="flex items-start gap-3">
          <span class="mt-0.5 h-6 w-6 rounded-lg bg-brand-500/10 text-brand-300 border border-brand-500/20 grid place-items-center text-xs">✓</span>
          <div>Asistencias, calificaciones, boletines y campus virtual integrados.</div>
        </div>
        <div class="flex items-start gap-3">
          <span class="mt-0.5 h-6 w-6 rounded-lg bg-brand-500/10 text-brand-300 border border-brand-500/20 grid place-items-center text-xs">✓</span>
          <div>Trial de 14 días sin cargo cuando activamos tu institución.</div>
        </div>
        <div class="flex items-start gap-3">
          <span class="mt-0.5 h-6 w-6 rounded-lg bg-brand-500/10 text-brand-300 border border-brand-500/20 grid place-items-center text-xs">✓</span>
          <div>Soporte en español y acompañamiento durante toda la implementación.</div>
        </div>
      </div>
      <?php if (!empty($plans)): ?>
        <div class="card p-4 text-xs">
          <div class="text-slate-400 mb-2 uppercase tracking-wider">Planes disponibles</div>
          <ul class="space-y-1">
            <?php foreach ($plans as $p): ?>
              <li class="flex items-center justify-between text-slate-300">
                <span><?= e($p['name']) ?></span>
                <span class="tabular-nums text-slate-400"><?= e(format_money($p['price_amount'], $p['currency_code'])) ?> · <?= e(frequency_label($p['billing_frequency'])) ?></span>
              </li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>
    </div>

    <div class="lg:col-span-2">
      <form method="post" action="/contact" class="card p-6 space-y-5" novalidate>
        <?= csrf_field() ?>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <?php
            $name = 'institution_name'; $label = 'Nombre de la institución'; $type = 'text'; $required = true;
            $placeholder = 'Escuela Técnica N°...'; $value = old('institution_name');
            include dirname(__DIR__, 2) . '/components/form_input.php';
          ?>
          <?php
            $name = 'contact_name'; $label = 'Tu nombre'; $type = 'text'; $required = true;
            $placeholder = 'María José'; $value = old('contact_name');
            include dirname(__DIR__, 2) . '/components/form_input.php';
          ?>
          <?php
            $name = 'contact_last_name'; $label = 'Tu apellido'; $type = 'text'; $required = false;
            $placeholder = 'García López'; $value = old('contact_last_name');
            include dirname(__DIR__, 2) . '/components/form_input.php';
          ?>
          <?php
            $name = 'contact_email'; $label = 'Email de contacto'; $type = 'email'; $required = true;
            $placeholder = 'responsable@institucion.edu.ar'; $value = old('contact_email');
            include dirname(__DIR__, 2) . '/components/form_input.php';
          ?>
          <?php
            $name = 'contact_phone'; $label = 'Teléfono (opcional)'; $type = 'text'; $required = false;
            $placeholder = '+54 11 ...'; $value = old('contact_phone');
            include dirname(__DIR__, 2) . '/components/form_input.php';
          ?>
          <div class="md:col-span-2">
            <?php
              $name = 'address'; $label = 'Dirección / ciudad'; $type = 'text'; $required = false;
              $placeholder = 'Calle 123, Ciudad, Provincia'; $value = old('address');
              include dirname(__DIR__, 2) . '/components/form_input.php';
            ?>
          </div>
          <div class="md:col-span-2">
            <?php
              $planOptions = ['' => '— No sé todavía —'];
              foreach ($plans as $p) $planOptions[$p['code']] = $p['name'] . ' · ' . format_money($p['price_amount'], $p['currency_code']) . ' / ' . strtolower(frequency_label($p['billing_frequency']));
              $name = 'plan_code'; $label = 'Plan de interés'; $required = false;
              $options = $planOptions; $value = old_raw('plan_code', '');
              include dirname(__DIR__, 2) . '/components/form_select.php';
            ?>
          </div>
          <div class="md:col-span-2">
            <?php
              $name = 'notes'; $label = 'Contanos más (opcional)'; $required = false;
              $rows = 4; $value = old('notes');
              include dirname(__DIR__, 2) . '/components/form_textarea.php';
            ?>
          </div>
        </div>
        <div class="flex items-center justify-between gap-3 pt-2">
          <p class="text-xs text-slate-500">Al enviar, aceptás que te contactemos por el email informado.</p>
          <button type="submit" class="btn-primary h-11 px-6">Enviar solicitud</button>
        </div>
      </form>
    </div>
  </section>
<?php endif; ?>

<?php
$content = ob_get_clean();
$title = 'Solicitud de demo y compra — Mi Tecnica';
include dirname(__DIR__, 2) . '/layouts/public.php';
