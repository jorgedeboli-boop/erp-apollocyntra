<?php
/**
 * Vista imprimible de presupuesto (plantilla tipo documents/invoice.php).
 */
require_once __DIR__ . '/../include/session.php';
require_once __DIR__ . '/../include/functions.php';
require_once __DIR__ . '/../include/presupuesto_documento.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header('HTTP/1.0 400 Bad Request');
    echo 'ID no válido';
    exit;
}

$conexion = conectar_bd();
$data = presupuesto_obtener_datos_documento($conexion, $id);
mysqli_close($conexion);

if (!$data) {
    header('HTTP/1.0 404 Not Found');
    echo 'Presupuesto no encontrado';
    exit;
}

$body = presupuesto_invoice_body_inner($data);
?>
<!doctype html>
<html lang="es" class="layout-wide" dir="ltr" data-assets-path="../assets/" data-bs-theme="light">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <meta name="robots" content="noindex, nofollow" />
    <title>Presupuesto <?php echo htmlspecialchars($data['presupuesto']['numero'] ?? '', ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="icon" type="image/x-icon" href="../assets/img/icons/app/favicon.ico" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&amp;display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="../assets/vendor/fonts/iconify-icons.css" />
    <link rel="stylesheet" href="../assets/vendor/libs/node-waves/node-waves.css" />
    <link rel="stylesheet" href="../assets/vendor/css/core.css" />
    <link rel="stylesheet" href="../assets/css/demo.css" />
    <link rel="stylesheet" href="../assets/vendor/css/pages/app-invoice-print.css" />
    <script src="../assets/vendor/js/helpers.js"></script>
    <script src="../assets/js/config.js"></script>
  </head>
  <body>
    <?php echo $body; ?>
    <script src="../assets/vendor/libs/jquery/jquery.js"></script>
    <script src="../assets/vendor/js/bootstrap.js"></script>
    <script src="../assets/js/app-invoice-print.js"></script>
  </body>
</html>
