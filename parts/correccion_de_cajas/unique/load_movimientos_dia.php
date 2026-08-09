<?php
require_once '../../../include/session.php';
require_once '../../../include/functions.php';
require_once __DIR__ . '/correccion_cajas_helper.php';

header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado'], JSON_UNESCAPED_UNICODE);
    exit;
}

$idSucursal = isset($_GET['id_sucursal']) ? (int) $_GET['id_sucursal'] : 0;
$fecha = isset($_GET['fecha']) ? trim($_GET['fecha']) : '';

if ($idSucursal <= 0 || $fecha === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Parámetros inválidos'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $conexion = conectar_bd();
    if (!$conexion) {
        throw new Exception('Error de conexión a la base de datos');
    }

    $tabla = correccion_cajas_tabla_movimientos($idSucursal);
    if (!correccion_cajas_tabla_existe($conexion, $tabla)) {
        throw new Exception('No existe tabla de movimientos para esta sucursal');
    }

    $query = "SELECT id_movimientos, fecha_apunte, hora_de_apunte, grupos, concepto, entrada, salida, cierre_caja
              FROM `{$tabla}`
              WHERE fecha_apunte = ?
              ORDER BY id_movimientos ASC";
    $stmt = mysqli_prepare($conexion, $query);
    if (!$stmt) {
        throw new Exception('Error al consultar movimientos');
    }
    mysqli_stmt_bind_param($stmt, 's', $fecha);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $movimientos = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $esApertura = correccion_cajas_es_apertura($row['grupos']);
        $esCierre = correccion_cajas_es_cierre_registro($row['cierre_caja']);
        $movimientos[] = [
            'id_movimientos' => (int) $row['id_movimientos'],
            'fecha_apunte' => $row['fecha_apunte'],
            'hora_de_apunte' => $row['hora_de_apunte'],
            'grupos' => $row['grupos'],
            'concepto' => $row['concepto'],
            'entrada' => (float) $row['entrada'],
            'salida' => (float) $row['salida'],
            'cierre_caja' => $row['cierre_caja'],
            'es_apertura' => $esApertura,
            'es_cierre' => $esCierre,
            'es_arrastrable' => $esApertura || $esCierre,
        ];
    }
    mysqli_stmt_close($stmt);

    $analisis = correccion_cajas_analizar_dia($conexion, $tabla, $fecha);
    $totales = correccion_cajas_calcular_total_dia($conexion, $tabla, $fecha);
    $importeAperturaSugerido = correccion_cajas_obtener_importe_cierre_dia_anterior($conexion, $tabla, $fecha);
    $importeCierreSugerido = !empty($analisis['importe_cierre_esperado'])
        ? (float) $analisis['importe_cierre_esperado']
        : $totales['total'];

    mysqli_close($conexion);

    echo json_encode([
        'success' => true,
        'movimientos' => $movimientos,
        'totales' => $totales,
        'sugeridos' => [
            'apertura' => $importeAperturaSugerido,
            'cierre' => $importeCierreSugerido,
        ],
        'falta_apertura' => !empty($analisis['falta_apertura']),
        'falta_cierre' => !empty($analisis['falta_cierre']),
        'apertura_id_erroneo' => !empty($analisis['apertura_id_erroneo']),
        'cierre_id_erroneo' => !empty($analisis['cierre_id_erroneo']),
        'cierre_no_coincide' => !empty($analisis['cierre_no_coincide']),
        'id_cierre' => $analisis['id_cierre'],
        'importe_cierre' => $analisis['importe_cierre'],
        'importe_cierre_esperado' => $analisis['importe_cierre_esperado'],
        'conflicto' => correccion_cajas_construir_mensaje_conflicto($analisis),
        'min_id' => $analisis['min_id'],
        'max_id' => $analisis['max_id'],
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
}
