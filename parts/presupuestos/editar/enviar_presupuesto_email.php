<?php
/**
 * Envía el presupuesto en PDF por correo (multipart).
 */
require_once __DIR__ . '/../../../include/session.php';
require_once __DIR__ . '/../../../include/functions.php';
require_once __DIR__ . '/../../../include/presupuesto_documento.php';
require_once __DIR__ . '/../../../vendor/autoload.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$id_presupuesto = isset($_POST['id_presupuesto']) ? (int)$_POST['id_presupuesto'] : 0;
$email = isset($_POST['email']) ? trim((string)$_POST['email']) : '';

if ($id_presupuesto <= 0 || $email === '') {
    echo json_encode(['success' => false, 'message' => 'Faltan datos']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Correo no válido']);
    exit;
}

try {
    $conexion = conectar_bd();
    $data = presupuesto_obtener_datos_documento($conexion, $id_presupuesto);
    mysqli_close($conexion);

    if (!$data) {
        echo json_encode(['success' => false, 'message' => 'Presupuesto no encontrado']);
        exit;
    }

    $html = presupuesto_invoice_html_mpdf($data);
    $mpdf = new \Mpdf\Mpdf(presupuesto_mpdf_config());
    $mpdf->WriteHTML($html);
    $pdfBinary = $mpdf->OutputBinaryData();

    $numero = (string)($data['presupuesto']['numero'] ?? $id_presupuesto);
    $nombreFichero = 'presupuesto_' . preg_replace('/[^a-zA-Z0-9._-]+/', '_', $numero) . '.pdf';

    $asunto = 'Presupuesto ' . $numero . ' — ' . (defined('APP_NAME') ? APP_NAME : 'TPV');
    $mensaje = "Adjuntamos el presupuesto " . $numero . " en formato PDF.\n\nSaludos.";

    $emailFrom = obtener_direccion_mail_from();
    $nombreEmpresa = trim((string)($data['empresa']['nombre_empresa'] ?? ''));
    if ($nombreEmpresa === '') {
        $nombreEmpresa = defined('APP_NAME') ? APP_NAME : 'TPV';
    }
    $nombreEmpresa = preg_replace("/[\r\n]/", '', $nombreEmpresa);
    $boundary = 'b_' . md5((string)time());

    $cabeceras = '';
    $cabeceras .= 'From: ' . $nombreEmpresa . ' <' . $emailFrom . '>' . "\r\n";
    $cabeceras .= 'MIME-Version: 1.0' . "\r\n";
    $cabeceras .= 'Content-Type: multipart/mixed; boundary="' . $boundary . '"' . "\r\n";

    $body = "--{$boundary}\r\n";
    $body .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $body .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
    $body .= $mensaje . "\r\n";
    $body .= "--{$boundary}\r\n";
    $body .= 'Content-Type: application/pdf; name="' . $nombreFichero . "\"\r\n";
    $body .= "Content-Transfer-Encoding: base64\r\n";
    $body .= 'Content-Disposition: attachment; filename="' . $nombreFichero . "\"\r\n\r\n";
    $body .= chunk_split(base64_encode($pdfBinary)) . "\r\n";
    $body .= "--{$boundary}--";

    $paramsCorreo = '-f' . $emailFrom;
    if (@mail($email, '=?UTF-8?B?' . base64_encode($asunto) . '?=', $body, $cabeceras, $paramsCorreo)) {
        echo json_encode(['success' => true, 'message' => 'Presupuesto enviado a ' . $email]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No se pudo enviar el correo. Compruebe la configuración del servidor.']);
    }
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
