<?php
/**
 * Envía la factura por email con el PDF adjunto (multipart).
 */

require_once '../../../include/session.php';
require_once '../../../include/functions.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$id_factura = isset($_POST['id_factura']) ? (int)$_POST['id_factura'] : 0;
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
if (!$id_factura || !$email) {
    echo json_encode(['success' => false, 'message' => 'Faltan id_factura o email']);
    exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Correo no válido']);
    exit;
}

try {
    $conexion = conectar_bd();
    $stmtF = mysqli_prepare(
        $conexion,
        'SELECT f.id_factura, f.numero_factura, f.prefijo_factura, f.id_sucursal, e.nombre_empresa
         FROM facturas f
         LEFT JOIN empresas e ON f.rel_id_empresa = e.id_empresa
         WHERE f.id_factura = ?
         LIMIT 1'
    );
    if (!$stmtF) {
        throw new Exception(mysqli_error($conexion));
    }
    mysqli_stmt_bind_param($stmtF, 'i', $id_factura);
    mysqli_stmt_execute($stmtF);
    $resF = mysqli_stmt_get_result($stmtF);
    $row = $resF ? mysqli_fetch_assoc($resF) : null;
    mysqli_stmt_close($stmtF);
    mysqli_close($conexion);
    if (!$row) {
        echo json_encode(['success' => false, 'message' => 'Factura no encontrada']);
        exit;
    }

    $id_sucursal = (int) ($row['id_sucursal'] ?? 0);
    if ($id_sucursal <= 0) {
        throw new Exception('Sucursal de la factura no válida');
    }

    $numero = ($row['prefijo_factura'] ? $row['prefijo_factura'] . '-' : '') . $row['numero_factura'];
    $pdfBinary = generarPdfFacturaBinario($id_factura, 'factura', 'sucursal', $id_sucursal);

    $nombreFichero = 'factura_' . preg_replace('/[^a-zA-Z0-9._-]+/', '_', $numero) . '.pdf';

    $emailFrom = obtener_direccion_mail_from();
    $nombreEmpresa = preg_replace("/[\r\n]/", '', trim((string) ($row['nombre_empresa'] ?? '')));

    $asunto = 'Factura ' . $numero . ' - ' . $nombreEmpresa;
    $mensaje = "Estimado/a,\n\nAdjuntamos la factura " . $numero . " en formato PDF.\n\nSaludos.";

    $boundary = 'b_' . md5((string) time());

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
        echo json_encode(['success' => true, 'message' => 'Factura enviada a ' . $email]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No se pudo enviar el correo. Compruebe la configuración de mail en el servidor.']);
    }
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
