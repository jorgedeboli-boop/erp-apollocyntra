<?php
require_once '../../../include/session.php';
require_once '../../../include/functions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'No autorizado'
    ]);
    exit;
}

if (!isset($_POST['id']) || $_POST['id'] === '') {
    echo json_encode([
        'success' => false,
        'error' => 'No se recibió el ID de autorización'
    ]);
    exit();
}

if (!isset($_POST['estado']) || $_POST['estado'] === '') {
    echo json_encode([
        'success' => false,
        'error' => 'No se recibió el estado'
    ]);
    exit();
}

$id = (int)$_POST['id'];
$estado = $_POST['estado'];

$estados_validos = ['autorizada', 'nousada'];
if (!in_array($estado, $estados_validos, true)) {
    echo json_encode([
        'success' => false,
        'error' => 'Estado no válido'
    ]);
    exit();
}

if ($estado === 'autorizada') {
    if (!isset($_POST['precio_nuevo']) || $_POST['precio_nuevo'] === '') {
        echo json_encode([
            'success' => false,
            'error' => 'Indique el precio autorizado'
        ]);
        exit();
    }
    $precio_nuevo = (float)$_POST['precio_nuevo'];
    if ($precio_nuevo < 0) {
        echo json_encode([
            'success' => false,
            'error' => 'Precio no válido'
        ]);
        exit();
    }
}

try {
    $conexion = conectar_bd();

    if (!$conexion) {
        throw new Exception('Error de conexión a la base de datos');
    }

    $qcheck = 'SELECT estado FROM autorizaciones_descuento_articulo_venta WHERE id = ? LIMIT 1';
    $stc = mysqli_prepare($conexion, $qcheck);
    if (!$stc) {
        throw new Exception('Error al preparar consulta: ' . mysqli_error($conexion));
    }
    mysqli_stmt_bind_param($stc, 'i', $id);
    mysqli_stmt_execute($stc);
    $rc = mysqli_stmt_get_result($stc);
    $cur = $rc ? mysqli_fetch_assoc($rc) : null;
    mysqli_stmt_close($stc);

    if (!$cur || $cur['estado'] !== 'pendiente') {
        mysqli_close($conexion);
        echo json_encode([
            'success' => false,
            'error' => 'Solo se puede actuar sobre solicitudes pendientes'
        ]);
        exit();
    }

    if ($estado === 'autorizada') {
        $query = 'UPDATE autorizaciones_descuento_articulo_venta
                  SET estado = ?, precio_nuevo = ?
                  WHERE id = ? AND estado = \'pendiente\'';
        $stmt = mysqli_prepare($conexion, $query);
        if (!$stmt) {
            throw new Exception('Error al preparar consulta: ' . mysqli_error($conexion));
        }
        mysqli_stmt_bind_param($stmt, 'sdi', $estado, $precio_nuevo, $id);
    } else {
        $query = 'UPDATE autorizaciones_descuento_articulo_venta
                  SET estado = ?
                  WHERE id = ? AND estado = \'pendiente\'';
        $stmt = mysqli_prepare($conexion, $query);
        if (!$stmt) {
            throw new Exception('Error al preparar consulta: ' . mysqli_error($conexion));
        }
        mysqli_stmt_bind_param($stmt, 'si', $estado, $id);
    }

    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception('Error al actualizar: ' . mysqli_stmt_error($stmt));
    }

    if (mysqli_affected_rows($conexion) === 0) {
        mysqli_stmt_close($stmt);
        mysqli_close($conexion);
        echo json_encode([
            'success' => false,
            'error' => 'No se pudo actualizar el registro'
        ]);
        exit();
    }

    mysqli_stmt_close($stmt);
    mysqli_close($conexion);

    if ($estado === 'autorizada') {
        $mensaje = 'La autorización de descuento ha quedado registrada con el precio indicado';
    } else {
        $mensaje = 'La solicitud ha sido marcada como no utilizada (rechazada)';
    }

    echo json_encode([
        'success' => true,
        'message' => $mensaje
    ]);
} catch (Exception $e) {
    if (isset($stmt)) {
        mysqli_stmt_close($stmt);
    }
    if (isset($conexion)) {
        mysqli_close($conexion);
    }

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
