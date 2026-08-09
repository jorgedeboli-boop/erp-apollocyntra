<?php
require_once '../../../include/session.php';

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Método no permitido']);
        exit;
    }

    $descripcion = trim((string)($_POST['descripcion_gasto_fijo'] ?? ''));
    $periodo = trim((string)($_POST['periodo_gasto_fijo'] ?? 'mensual'));
    $fechaInicio = trim((string)($_POST['fecha_inicio_gasto_fijo'] ?? ''));
    $total = (float)str_replace(',', '.', (string)($_POST['total_gasto_fijo'] ?? '0'));

    $empresaId = (int)($_POST['empresa_gasto_fijo'] ?? 0);
    $sucursalRaw = (string)($_POST['sucursal_gasto_fijo'] ?? '0');
    $sucursalId = ($sucursalRaw === 'no_es_sucursal') ? 0 : (int)$sucursalRaw;
    $proveedorId = (int)($_POST['proveedor_gasto_fijo'] ?? 0);
    $formaPagoId = (int)($_POST['forma_pago_gasto_fijo'] ?? 0);
    $tipoGastoId = (int)($_POST['tipo_de_gasto_fijo'] ?? 0);
    $gastoTipo = trim((string)($_POST['gasto_tipo'] ?? ''));

    if ($descripcion === '' || $fechaInicio === '' || $total <= 0 || $empresaId <= 0 || $proveedorId <= 0 || $formaPagoId <= 0 || $tipoGastoId <= 0) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Faltan campos obligatorios o son inválidos']);
        exit;
    }

    // Validación básica de fecha (YYYY-MM-DD)
    $dt = DateTime::createFromFormat('Y-m-d', $fechaInicio);
    if (!$dt || $dt->format('Y-m-d') !== $fechaInicio) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Fecha de inicio inválida']);
        exit;
    }

    $conexion = conectar_bd();
    if (!$conexion) {
        throw new Exception('Error de conexión a la base de datos');
    }

    $sql = "INSERT INTO gastos_fijos (
                descripcion_gasto_fijo,
                periodo_gasto_fijo,
                fecha_inicio_gasto_fijo,
                total_gasto_fijo,
                tipo_de_gasto_fijo,
                proveedor_gasto_fijo,
                sucursal_gasto_fijo,
                forma_pago_gasto_fijo,
                empresa_gasto_fijo,
                fecha_alta_gasto_fijo,
                gasto_tipo,
                estado_gasto_fijo
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, 'true'
            )";

    $stmt = mysqli_prepare($conexion, $sql);
    if (!$stmt) {
        throw new Exception('Error preparando INSERT: ' . mysqli_error($conexion));
    }

    mysqli_stmt_bind_param(
        $stmt,
        'sssdiiiiis',
        $descripcion,
        $periodo,
        $fechaInicio,
        $total,
        $tipoGastoId,
        $proveedorId,
        $sucursalId,
        $formaPagoId,
        $empresaId,
        $gastoTipo
    );

    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception('Error insertando gasto fijo: ' . mysqli_stmt_error($stmt));
    }

    $idNuevo = (int)mysqli_insert_id($conexion);

    mysqli_stmt_close($stmt);
    mysqli_close($conexion);

    echo json_encode([
        'success' => true,
        'id_gasto_fijo' => $idNuevo,
        'redirect' => 'gastos_fijos.php?categoria=gastos&page=gastos_fijos&btn=list',
        'message' => 'Gasto fijo creado correctamente'
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

