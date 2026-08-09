<?php
require_once '../../../include/session.php';
require_once '../../../include/functions.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

$anio = isset($_GET['anio']) ? (int) $_GET['anio'] : (int) date('Y');

try {
    $conexion = conectar_bd();
    if (!$conexion) {
        throw new Exception('Error de conexión a la base de datos');
    }

    $stmt = mysqli_prepare(
        $conexion,
        "SELECT numero_semana, fecha_semana_desde, fecha_semana_hasta
         FROM listado_numero_semanas
         WHERE anyo_listado = ?
         ORDER BY numero_semana ASC"
    );

    if (!$stmt) {
        throw new Exception('Error al preparar consulta de semanas');
    }

    mysqli_stmt_bind_param($stmt, 'i', $anio);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $semanas = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $semanas[] = [
            'numero_semana' => (int) $row['numero_semana'],
            'fecha_semana_desde' => $row['fecha_semana_desde'],
            'fecha_semana_hasta' => $row['fecha_semana_hasta']
        ];
    }

    mysqli_stmt_close($stmt);
    mysqli_close($conexion);

    echo json_encode([
        'success' => true,
        'semanas' => $semanas,
        'semana_actual' => obtener_numero_semana()
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
