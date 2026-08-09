<?php
require_once '../../../include/session.php';

header('Content-Type: application/json');

try {
    $empresaId = isset($_GET['empresa_id']) ? (int)$_GET['empresa_id'] : 0;

    $conexion = conectar_bd();
    if (!$conexion) {
        throw new Exception('Error de conexión');
    }

    if ($empresaId > 0) {
        $sql = "SELECT id_sucursal, nombre_sucursal FROM sucursal WHERE empresa_id = ? ORDER BY nombre_sucursal ASC";
        $stmt = mysqli_prepare($conexion, $sql);
        mysqli_stmt_bind_param($stmt, 'i', $empresaId);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
    } else {
        $sql = "SELECT id_sucursal, nombre_sucursal FROM sucursal ORDER BY nombre_sucursal ASC";
        $res = mysqli_query($conexion, $sql);
    }

    $sucursales = [];
    while ($res && ($row = mysqli_fetch_assoc($res))) {
        $sucursales[] = [
            'id_sucursal' => (int)$row['id_sucursal'],
            'nombre_sucursal' => $row['nombre_sucursal']
        ];
    }

    if (isset($stmt) && $stmt) {
        mysqli_stmt_close($stmt);
    }
    mysqli_close($conexion);

    echo json_encode(['success' => true, 'sucursales' => $sucursales]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

