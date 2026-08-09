<?php
require_once '../../../include/session.php';

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Método no permitido']);
        exit;
    }

    $idGastoFijo = isset($_POST['id_gasto_fijo']) ? (int)$_POST['id_gasto_fijo'] : 0;
    if ($idGastoFijo <= 0) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'ID inválido']);
        exit;
    }

    $descripcion = trim((string)($_POST['descripcion_gasto_fijo'] ?? ''));
    $periodo = trim((string)($_POST['periodo_gasto_fijo'] ?? ''));
    $fechaInicio = trim((string)($_POST['fecha_inicio_gasto_fijo'] ?? ''));
    $total = (float)str_replace(',', '.', (string)($_POST['total_gasto_fijo'] ?? '0'));

    $sucursalRaw = (string)($_POST['sucursal_gasto_fijo'] ?? '0');
    $sucursalId = ($sucursalRaw === 'no_es_sucursal') ? 0 : (int)$sucursalRaw;
    $proveedorId = (int)($_POST['proveedor_gasto_fijo'] ?? 0);
    $formaPagoId = (int)($_POST['forma_pago_gasto_fijo'] ?? 0);
    $tipoGastoId = (int)($_POST['tipo_de_gasto_fijo'] ?? 0);

    if ($descripcion === '' || $periodo === '' || $fechaInicio === '' || $total <= 0 || $proveedorId <= 0 || $formaPagoId <= 0 || $tipoGastoId <= 0) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Faltan campos obligatorios o son inválidos']);
        exit;
    }

    $dt = DateTime::createFromFormat('Y-m-d', $fechaInicio);
    if (!$dt || $dt->format('Y-m-d') !== $fechaInicio) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Fecha de inicio inválida']);
        exit;
    }

    $conexion = conectar_bd();
    if (!$conexion) {
        throw new Exception('Error de conexión');
    }

    $sql = "UPDATE gastos_fijos
            SET descripcion_gasto_fijo = ?,
                periodo_gasto_fijo = ?,
                fecha_inicio_gasto_fijo = ?,
                total_gasto_fijo = ?,
                sucursal_gasto_fijo = ?,
                proveedor_gasto_fijo = ?,
                forma_pago_gasto_fijo = ?,
                tipo_de_gasto_fijo = ?
            WHERE id_gasto_fijo = ?
            LIMIT 1";

    $stmt = mysqli_prepare($conexion, $sql);
    if (!$stmt) {
        throw new Exception('Error preparando UPDATE: ' . mysqli_error($conexion));
    }

    mysqli_stmt_bind_param(
        $stmt,
        'sssdiiiii',
        $descripcion,
        $periodo,
        $fechaInicio,
        $total,
        $sucursalId,
        $proveedorId,
        $formaPagoId,
        $tipoGastoId,
        $idGastoFijo
    );

    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception('Error actualizando gasto fijo: ' . mysqli_stmt_error($stmt));
    }

    mysqli_stmt_close($stmt);
    mysqli_close($conexion);

    echo json_encode([
        'success' => true,
        'message' => 'Gasto fijo actualizado correctamente'
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

