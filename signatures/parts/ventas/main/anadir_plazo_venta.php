<?php
/**
 * Valida y prepara la venta para generar el siguiente plazo vía comprobar_plazos.php
 * (fecha_vencimiento del primer plazo pagado = fecha_creado + 1 mes → comprobar inserta +1 mes = +2 desde creado).
 */
require_once __DIR__ . '/../../../include/session.php';
require_once __DIR__ . '/../../../include/functions.php';

header('Content-Type: application/json; charset=utf-8');

$conexion = conectar_bd();

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Método no permitido']);
        exit;
    }

    if (!$conexion) {
        throw new Exception('Sin conexión');
    }

    $id_venta = isset($_POST['id_venta']) ? (int) $_POST['id_venta'] : 0;

    if ($id_venta <= 0) {
        throw new Exception('Datos no válidos');
    }

    $item_modulo = basename(dirname(__DIR__));
    if (!usuario_puede_acceder_crud_tipo($usuario_privilegio_id, crud_id_listar_modulo($item_modulo), 'editar')) {
        throw new Exception('No tiene permisos para esta acción');
    }

    $stmtV = mysqli_prepare(
        $conexion,
        'SELECT id, estado, venta_plazos, precio, numero_plazos, id_sucursal FROM ventas WHERE id = ? LIMIT 1'
    );
    if (!$stmtV) {
        throw new Exception(mysqli_error($conexion));
    }
    mysqli_stmt_bind_param($stmtV, 'i', $id_venta);
    mysqli_stmt_execute($stmtV);
    $rv = mysqli_stmt_get_result($stmtV);
    $venta = $rv ? mysqli_fetch_assoc($rv) : null;
    mysqli_stmt_close($stmtV);

    if (!$venta || strtolower((string) ($venta['venta_plazos'] ?? '')) !== 'si') {
        throw new Exception('Venta no encontrada o no es a plazos');
    }

    $estVenta = strtolower((string) ($venta['estado'] ?? ''));
    if (!in_array($estVenta, ['enfecha', 'vencido'], true)) {
        throw new Exception('La venta no admite añadir plazos en su estado actual');
    }

    $id_sucursal = (int) ($venta['id_sucursal'] ?? 0);
    $numero_plazos = (int) ($venta['numero_plazos'] ?? 0);

    if (venta_plazos_tiene_factura_generada($conexion, $id_venta, $id_sucursal)) {
        throw new Exception('No se pueden añadir plazos: la venta ya tiene factura generada');
    }

    $stmtCnt = mysqli_prepare($conexion, 'SELECT COUNT(id) AS c FROM ventas_plazos WHERE id_venta = ?');
    if (!$stmtCnt) {
        throw new Exception(mysqli_error($conexion));
    }
    mysqli_stmt_bind_param($stmtCnt, 'i', $id_venta);
    mysqli_stmt_execute($stmtCnt);
    $rc = mysqli_stmt_get_result($stmtCnt);
    $rowCnt = $rc ? mysqli_fetch_assoc($rc) : null;
    mysqli_stmt_close($stmtCnt);
    $cantidad_plazos = (int) ($rowCnt['c'] ?? 0);

    if ($cantidad_plazos !== 1) {
        throw new Exception('Solo se puede añadir plazos cuando existe únicamente el primer plazo');
    }

    if ($numero_plazos > 0 && $cantidad_plazos >= $numero_plazos) {
        throw new Exception('La venta ya tiene el número máximo de plazos');
    }

    $stmtPend = mysqli_prepare(
        $conexion,
        "SELECT COUNT(id) AS c FROM ventas_plazos WHERE id_venta = ? AND estado = 'Pendiente'"
    );
    if (!$stmtPend) {
        throw new Exception(mysqli_error($conexion));
    }
    mysqli_stmt_bind_param($stmtPend, 'i', $id_venta);
    mysqli_stmt_execute($stmtPend);
    $rp = mysqli_stmt_get_result($stmtPend);
    $rowPend = $rp ? mysqli_fetch_assoc($rp) : null;
    mysqli_stmt_close($stmtPend);
    if ((int) ($rowPend['c'] ?? 0) > 0) {
        throw new Exception('Ya existe un plazo pendiente');
    }

    $stmtNoPag = mysqli_prepare(
        $conexion,
        "SELECT COUNT(id) AS c FROM ventas_plazos WHERE id_venta = ? AND estado <> 'Pagado'"
    );
    if (!$stmtNoPag) {
        throw new Exception(mysqli_error($conexion));
    }
    mysqli_stmt_bind_param($stmtNoPag, 'i', $id_venta);
    mysqli_stmt_execute($stmtNoPag);
    $rnp = mysqli_stmt_get_result($stmtNoPag);
    $rowNoPag = $rnp ? mysqli_fetch_assoc($rnp) : null;
    mysqli_stmt_close($stmtNoPag);
    if ((int) ($rowNoPag['c'] ?? 0) > 0) {
        throw new Exception('Existen plazos no cobrados; no se puede añadir otro');
    }

    $stmtPrim = mysqli_prepare(
        $conexion,
        "SELECT id, fecha_creado, fecha_cobrado, fecha_vencimiento
         FROM ventas_plazos
         WHERE id_venta = ? AND estado = 'Pagado'
         ORDER BY id ASC
         LIMIT 1"
    );
    if (!$stmtPrim) {
        throw new Exception(mysqli_error($conexion));
    }
    mysqli_stmt_bind_param($stmtPrim, 'i', $id_venta);
    mysqli_stmt_execute($stmtPrim);
    $rprim = mysqli_stmt_get_result($stmtPrim);
    $primero = $rprim ? mysqli_fetch_assoc($rprim) : null;
    mysqli_stmt_close($stmtPrim);

    if (!$primero) {
        throw new Exception('No se encontró el primer plazo pagado');
    }

    $base = trim((string) ($primero['fecha_creado'] ?? ''));
    if ($base === '' || substr($base, 0, 10) === '0000-00-00') {
        $base = trim((string) ($primero['fecha_cobrado'] ?? ''));
    }
    if ($base === '' || substr($base, 0, 10) === '0000-00-00') {
        $base = date('Y-m-d');
    } else {
        $base = substr($base, 0, 10);
    }

    $tBase = strtotime($base . ' +1 month');
    if ($tBase === false) {
        throw new Exception('No se pudo calcular la fecha base del plazo');
    }
    $fecha_venc_primero = date('Y-m-d H:i:s', $tBase);
    $id_primer_plazo = (int) ($primero['id'] ?? 0);

    $stmtUp = mysqli_prepare(
        $conexion,
        "UPDATE ventas_plazos SET fecha_vencimiento = ? WHERE id = ? AND id_venta = ? AND estado = 'Pagado'"
    );
    if (!$stmtUp) {
        throw new Exception(mysqli_error($conexion));
    }
    mysqli_stmt_bind_param($stmtUp, 'sii', $fecha_venc_primero, $id_primer_plazo, $id_venta);
    mysqli_stmt_execute($stmtUp);
    mysqli_stmt_close($stmtUp);

    mysqli_close($conexion);

    echo json_encode([
        'success' => true,
        'message' => 'Listo para generar el siguiente plazo',
        'usar_comprobar_plazos' => true,
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    if ($conexion) {
        mysqli_close($conexion);
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
