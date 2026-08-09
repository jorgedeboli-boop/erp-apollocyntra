<?php
/**
 * Envía la factura simplificada por email con el PDF adjunto (Fiskaly o clásica).
 */

require_once '../../../include/session.php';
require_once '../../../include/functions.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$id_factura = isset($_POST['id_factura']) ? (int) $_POST['id_factura'] : 0;
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
    $info = facturaSimplificadaResolverOrigen($id_factura, '');
    if (!$info) {
        echo json_encode(['success' => false, 'message' => 'Factura simplificada no encontrada']);
        exit;
    }

    $id_sucursal = (int) ($info['id_sucursal'] ?? 0);
    if ($id_sucursal <= 0) {
        throw new Exception('Sucursal de la factura no válida');
    }

    $nombreEmpresa = '';
    $conexion = conectar_bd();
    if ($conexion && (int) $info['rel_id_empresa'] > 0) {
        $idEmp = (int) $info['rel_id_empresa'];
        $stmtE = mysqli_prepare($conexion, 'SELECT nombre_empresa FROM empresas WHERE id_empresa = ? LIMIT 1');
        if ($stmtE) {
            mysqli_stmt_bind_param($stmtE, 'i', $idEmp);
            mysqli_stmt_execute($stmtE);
            $resE = mysqli_stmt_get_result($stmtE);
            $rowE = $resE ? mysqli_fetch_assoc($resE) : null;
            mysqli_stmt_close($stmtE);
            $nombreEmpresa = (string) ($rowE['nombre_empresa'] ?? '');
        }
    }
    if ($conexion) {
        mysqli_close($conexion);
    }

    $numero = ($info['prefijo_factura'] ? $info['prefijo_factura'] . '-' : '') . $info['numero_factura'];
    $tipoPdf = tipoGeneracionPdfFacturaSimplificada($info['tipo_factura'] ?? 'articulos');
    $pdfBinary = generarPdfFacturaBinario($id_factura, $tipoPdf, 'sucursal', $id_sucursal);

    $nombreFichero = 'factura_simplificada_' . preg_replace('/[^a-zA-Z0-9._-]+/', '_', $numero) . '.pdf';

    $emailFrom = obtener_direccion_mail_from();
    $nombreEmpresa = preg_replace("/[\r\n]/", '', trim($nombreEmpresa));

    $asunto = 'Factura simplificada ' . $numero . ' - ' . $nombreEmpresa;
    $mensaje = "Estimado/a,\n\nAdjuntamos la factura simplificada " . $numero . " en formato PDF.\n\nSaludos.";

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
        echo json_encode(['success' => true, 'message' => 'Factura simplificada enviada a ' . $email]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No se pudo enviar el correo. Compruebe la configuración de mail en el servidor.']);
    }
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
