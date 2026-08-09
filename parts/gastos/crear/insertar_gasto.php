<?php
require_once '../../../include/session.php';
require_once '../../../include/functions.php';

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Método no permitido']);
        exit;
    }

    if (!usuario_autenticado()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Usuario no autenticado']);
        exit;
    }

    $descripcion = trim((string)($_POST['descripcion_gasto'] ?? ''));
    $fechaGasto = trim((string)($_POST['fecha_gasto'] ?? ''));
    $fechaFactura = trim((string)($_POST['fecha_factura_gasto'] ?? ''));
    $numeroFactura = trim((string)($_POST['numero_factura_proveedor'] ?? ''));

    $base = (float)str_replace(',', '.', (string)($_POST['base_impobile'] ?? '0'));
    $tipoIva = (int)($_POST['tipo_iva'] ?? 21);
    $irpf = (float)str_replace(',', '.', (string)($_POST['irpf'] ?? '0'));

    $empresaId = (int)($_POST['empresa_gasto'] ?? 0);
    $sucursalRaw = (string)($_POST['sucursal_gasto'] ?? '0');
    $sucursalId = ($sucursalRaw === 'no_es_sucursal') ? 0 : (int)$sucursalRaw;
    $proveedorId = (int)($_POST['proveedor_gasto'] ?? 0);
    $formaPagoId = (int)($_POST['forma_pago_gasto'] ?? 0);
    $tipoGastoId = (int)($_POST['tipo_de_gasto'] ?? 0);
    $estado = (string)($_POST['estado_gasto'] ?? 'pendiente');

    $fechaPagoRaw = trim((string)($_POST['fecha_pago_gasto'] ?? ''));
    $fechaPago = null;
    if ($fechaPagoRaw !== '') {
        $dtPago = DateTime::createFromFormat('Y-m-d\TH:i', $fechaPagoRaw);
        if ($dtPago) {
            $fechaPago = $dtPago->format('Y-m-d H:i:s');
        }
    }
    if ($fechaPago === null) {
        $fechaPago = date('Y-m-d H:i:s');
    }

    // Validaciones básicas
    if (
        $descripcion === '' ||
        $fechaGasto === '' ||
        $fechaFactura === '' ||
        $numeroFactura === '' ||
        $empresaId <= 0 ||
        $proveedorId <= 0 ||
        $formaPagoId <= 0 ||
        $tipoGastoId <= 0 ||
        $base <= 0
    ) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Faltan campos obligatorios o son inválidos']);
        exit;
    }

    $dtGasto = DateTime::createFromFormat('Y-m-d', $fechaGasto);
    $dtFactura = DateTime::createFromFormat('Y-m-d', $fechaFactura);
    if (
        !$dtGasto || $dtGasto->format('Y-m-d') !== $fechaGasto ||
        !$dtFactura || $dtFactura->format('Y-m-d') !== $fechaFactura
    ) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Fechas inválidas']);
        exit;
    }

    $estadosValidos = ['pendiente', 'pagado', 'cancelado'];
    if (!in_array($estado, $estadosValidos, true)) {
        $estado = 'pendiente';
    }

    if ($tipoIva < 0) $tipoIva = 0;

    $ivaTotal = round($base * ($tipoIva / 100), 2);
    $total = round($base + $ivaTotal - $irpf, 2);

    $usuarioId = (int)($_SESSION['usuario_id'] ?? 0);
    if ($usuarioId <= 0) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Sesión inválida']);
        exit;
    }

    $conexion = conectar_bd();
    if (!$conexion) {
        throw new Exception('Error de conexión a la base de datos');
    }

    $sql = "INSERT INTO gastos (
                sucursal_gasto,
                proveedor_gasto,
                fecha_gasto,
                fecha_pago_gasto,
                fecha_factura_gasto,
                usuario_creacion_gasto,
                base_impobile,
                iva_total,
                total_gasto,
                forma_pago_gasto,
                estado_gasto,
                tipo_de_gasto,
                usuario_pago_gasto,
                empresa_gasto,
                descripcion_gasto,
                numero_factura_proveedor,
                irpf,
                creado_desde,
                tipo_iva,
                origen_gasto_variable,
                rel_id_gasto_fijo,
                gasto_tipo
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Central', ?, 'manual', 0, 'empresa'
            )";

    $stmt = mysqli_prepare($conexion, $sql);
    if (!$stmt) {
        throw new Exception('Error preparando INSERT: ' . mysqli_error($conexion));
    }

    mysqli_stmt_bind_param(
        $stmt,
        'iisssidddisiiissdi',
        $sucursalId,
        $proveedorId,
        $fechaGasto,
        $fechaPago,
        $fechaFactura,
        $usuarioId,
        $base,
        $ivaTotal,
        $total,
        $formaPagoId,
        $estado,
        $tipoGastoId,
        $usuarioId,
        $empresaId,
        $descripcion,
        $numeroFactura,
        $irpf,
        $tipoIva
    );

    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception('Error insertando gasto: ' . mysqli_stmt_error($stmt));
    }

    $idNuevo = (int)mysqli_insert_id($conexion);
    mysqli_stmt_close($stmt);
    mysqli_close($conexion);

    echo json_encode([
        'success' => true,
        'id_gasto' => $idNuevo,
        'redirect' => 'gastos.php',
        'message' => 'Gasto creado correctamente'
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

