<?php
/**
 * Prueba de envío con mail() únicamente (sin PHPMailer ni SMTP en PHP).
 * El servidor debe tener configurado el correo saliente (p. ej. Dinahosting).
 */
require_once __DIR__ . '/include/session.php';

$from = obtener_direccion_mail_from();

$resultado = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = isset($_POST['email']) ? trim((string)$_POST['email']) : '';
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $resultado = ['ok' => false, 'texto' => 'Indique un correo válido.'];
    } else {
        $asunto = 'Prueba correo TPV — ' . (defined('APP_NAME') ? APP_NAME : 'TPV');
        $cuerpo = "Mensaje de prueba (mail() nativo).\n\n";
        $cuerpo .= 'Fecha: ' . date('Y-m-d H:i:s') . "\n";
        $cuerpo .= 'From: ' . $from . "\n";

        $headers = [];
        $headers[] = 'From: ' . $from;
        $headers[] = 'Content-Type: text/plain; charset=UTF-8';

        if (@mail($email, $asunto, $cuerpo, implode("\r\n", $headers))) {
            $resultado = [
                'ok' => true,
                'texto' => 'mail() devolvió true. Revise la bandeja (y spam): ' . $email
            ];
        } else {
            $resultado = [
                'ok' => false,
                'texto' => 'mail() devolvió false. Revise el envío en el panel del hosting y los logs de PHP.'
            ];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Prueba mail()</title>
  <style>
    body { font-family: system-ui, sans-serif; max-width: 32rem; margin: 2rem auto; padding: 0 1rem; }
    .ok { color: #080; }
    .fail { color: #c00; }
    label { display: block; margin-bottom: 0.5rem; }
    input[type="email"] { width: 100%; max-width: 22rem; padding: 0.4rem; }
    button { margin-top: 0.75rem; padding: 0.5rem 1rem; }
    code { background: #f4f4f4; padding: 0.1rem 0.35rem; }
  </style>
</head>
<body>
  <h1>Prueba de correo (solo <code>mail()</code>)</h1>
  <p>Remitente <code>From</code> (<code>MAIL_FROM_ADDRESS</code> / <code>obtener_direccion_mail_from()</code>):</p>
  <p><strong><?php echo htmlspecialchars($from, ENT_QUOTES, 'UTF-8'); ?></strong></p>

  <?php if ($resultado !== null): ?>
    <p class="<?php echo $resultado['ok'] ? 'ok' : 'fail'; ?>">
      <?php echo htmlspecialchars($resultado['texto'], ENT_QUOTES, 'UTF-8'); ?>
    </p>
  <?php endif; ?>

  <form method="post" action="">
    <label for="email">Correo destino</label>
    <input type="email" id="email" name="email" required
           value="<?php echo isset($_POST['email']) ? htmlspecialchars((string)$_POST['email'], ENT_QUOTES, 'UTF-8') : ''; ?>"
           autocomplete="email" />
    <div><button type="submit">Enviar prueba</button></div>
  </form>

  <p><small>Requiere sesión. Borre este archivo cuando termine. SMTP del hosting no se configura aquí; solo PHP <code>mail()</code>.</small></p>
</body>
</html>
