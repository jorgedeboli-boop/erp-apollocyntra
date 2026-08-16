<?php
/**
 * Reenvía a Fiskaly una factura rectificativa del listado
 * (completa o simplificada) cuando ya existe vínculo en cache.
 *
 * POST: id_factura, simplificada=0|1
 */
require_once '../../../include/session.php';
require_once '../../../include/functions.php';

header('Content-Type: application/json');

try {
    if (!function_exists('usuario_autenticado') || !usuario_autenticado()) {
        throw new Exception('No autorizado');
    }

    $id_factura = isset($_POST['id_factura']) ? (int) $_POST['id_factura'] : 0;
    $simplificada = !empty($_POST['simplificada']);

    if ($id_factura <= 0) {
        throw new Exception('Parámetros inválidos');
    }

    $tablaRect = $simplificada ? 'facturas_rectificativas_simplificadas' : 'facturas_rectificativas';
    $conexion = conectar_bd();
    if (!$conexion) {
        throw new Exception('Sin conexión BD');
    }

    $stmt = mysqli_prepare(
        $conexion,
        "SELECT f.id_factura, f.id_sucursal, f.id_rel_factura_fiskaly, f.rel_id_factura, f.rel_id_empresa
         FROM {$tablaRect} f
         WHERE f.id_factura = ?
         LIMIT 1"
    );
    if (!$stmt) {
        throw new Exception(mysqli_error($conexion));
    }
    mysqli_stmt_bind_param($stmt, 'i', $id_factura);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $row = $res ? mysqli_fetch_assoc($res) : null;
    mysqli_stmt_close($stmt);

    if (!$row) {
        mysqli_close($conexion);
        throw new Exception('Rectificativa no encontrada');
    }

    $id_sucursal = (int) ($row['id_sucursal'] ?? 0);
    $id_fiskaly = (int) ($row['id_rel_factura_fiskaly'] ?? 0);
    $id_original_tpv = (int) ($row['rel_id_factura'] ?? 0);
    $id_empresa = (int) ($row['rel_id_empresa'] ?? 0);

    if ($id_sucursal <= 0) {
        mysqli_close($conexion);
        throw new Exception('La rectificativa no tiene sucursal');
    }
    if ($id_fiskaly <= 0) {
        mysqli_close($conexion);
        throw new Exception('La rectificativa no tiene vínculo Fiskaly. No se puede reenviar.');
    }
    if ($id_original_tpv <= 0) {
        mysqli_close($conexion);
        throw new Exception('La rectificativa no referencia factura original');
    }
    if ($id_empresa <= 0) {
        mysqli_close($conexion);
        throw new Exception('No se pudo determinar la empresa Fiskaly');
    }

    $tablaOrig = $simplificada ? 'facturas_simplificadas' : 'facturas';
    $stmtO = mysqli_prepare(
        $conexion,
        "SELECT id_rel_factura_fiskaly, rel_id_empresa FROM {$tablaOrig}
         WHERE id_factura = ? AND id_sucursal = ? LIMIT 1"
    );
    if (!$stmtO && $simplificada) {
        $stmtO = mysqli_prepare(
            $conexion,
            'SELECT id_rel_factura_fiskaly, rel_id_empresa FROM facturas
             WHERE id_factura = ? AND id_sucursal = ? LIMIT 1'
        );
    }
    if (!$stmtO) {
        mysqli_close($conexion);
        throw new Exception('No se pudo leer la factura original');
    }
    mysqli_stmt_bind_param($stmtO, 'ii', $id_original_tpv, $id_sucursal);
    mysqli_stmt_execute($stmtO);
    $resO = mysqli_stmt_get_result($stmtO);
    $rowO = $resO ? mysqli_fetch_assoc($resO) : null;
    mysqli_stmt_close($stmtO);
    mysqli_close($conexion);

    if (!$rowO) {
        throw new Exception('Factura original no encontrada');
    }

    $id_fiskaly_original = (int) ($rowO['id_rel_factura_fiskaly'] ?? 0);
    if ($id_fiskaly_original <= 0) {
        throw new Exception('La original no tiene vínculo Fiskaly');
    }

    $cacheOriginal = fiskalyLeerFacturaCache($id_fiskaly_original, $id_empresa);
    $uuidOriginal = trim((string) ($cacheOriginal['factura']['invoice_id_fiskaly'] ?? ''));
    if ($uuidOriginal === '') {
        throw new Exception('La factura Fiskaly original no tiene invoice_id_fiskaly');
    }

    $tipoOriginal = strtoupper(trim((string) ($cacheOriginal['factura']['tipo_factura'] ?? 'SIMPLIFIED')));
    $correction_data_type = ($tipoOriginal === 'COMPLETE') ? 'COMPLETE' : 'SIMPLIFIED';

    $envio = fiskalyReenviarFacturaRectificativaCache(
        $id_fiskaly,
        $id_empresa,
        $id_sucursal,
        $uuidOriginal,
        $correction_data_type
    );

    echo json_encode([
        'success' => !empty($envio['success']),
        'message' => !empty($envio['success'])
            ? 'Rectificativa reenviada a Fiskaly (' . ($envio['estado_cache'] ?? '') . ')'
            : ('Reenvío incompleto: ' . ($envio['estado_cache'] ?? 'error')),
        'estado_cache' => isset($envio['estado_cache']) ? $envio['estado_cache'] : null,
        'fiskaly' => $envio,
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'message' => $e->getMessage(),
    ]);
}
