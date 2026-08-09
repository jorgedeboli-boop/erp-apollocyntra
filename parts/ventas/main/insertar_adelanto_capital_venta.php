<?php
/**
 * Registra adelanto de capital en venta a plazos: actualiza importes de cuotas pendientes,
 * inserta en adelantos_capital_venta y registra movimientos de caja (adelanto + gastos).
 */
require_once __DIR__ . '/../../../include/session.php';
require_once __DIR__ . '/../../../include/functions.php';

header('Content-Type: application/json; charset=utf-8');

$conexion = conectar_bd();
$txActiva = false;

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
    $adelanto_cliente = isset($_POST['adelanto_cliente']) ? (float) $_POST['adelanto_cliente'] : 0.0;
    $forma_de_pago = isset($_POST['forma_de_pago']) ? trim((string) $_POST['forma_de_pago']) : '';
    $comprobante = isset($_FILES['comprobante_adelanto_venta_archivo']) ? $_FILES['comprobante_adelanto_venta_archivo'] : null;
    $foto_camara = isset($_POST['foto_camara']) ? trim((string) $_POST['foto_camara']) : '';
    $id_foto_cache = isset($_POST['id_foto_cache_adelanto_venta']) ? (int) $_POST['id_foto_cache_adelanto_venta'] : 0;

    if ($id_venta <= 0) {
        throw new Exception('ID de venta no válido');
    }
    if ($adelanto_cliente <= 0) {
        throw new Exception('El adelanto debe ser mayor a 0');
    }

    $formas = ['efectivo', 'tarjeta', 'transferencia', 'bizum'];
    if (!in_array($forma_de_pago, $formas, true)) {
        throw new Exception('Forma de pago no válida');
    }

    $stmtV = mysqli_prepare(
        $conexion,
        'SELECT id, estado, venta_plazos, numero_plazos, precio, cliente, id_venta_sucursal, id_sucursal
         FROM ventas WHERE id = ? LIMIT 1'
    );
    if (!$stmtV) {
        throw new Exception(mysqli_error($conexion));
    }
    mysqli_stmt_bind_param($stmtV, 'i', $id_venta);
    mysqli_stmt_execute($stmtV);
    $rv = mysqli_stmt_get_result($stmtV);
    $venta = $rv ? mysqli_fetch_assoc($rv) : null;
    mysqli_stmt_close($stmtV);
    if (!$venta) {
        throw new Exception('Venta no encontrada');
    }

    if (strtolower((string) ($venta['venta_plazos'] ?? '')) !== 'si') {
        throw new Exception('La venta no es a plazos');
    }

    $est = strtolower((string) ($venta['estado'] ?? ''));
    if (!in_array($est, ['enfecha', 'vencido'], true)) {
        throw new Exception('Estado de venta no admite adelanto de capital');
    }

    $numero_plazos = (int) ($venta['numero_plazos'] ?? 0);
    $id_cliente = (int) ($venta['cliente'] ?? 0);
    $id_sucursal = (int) ($venta['id_sucursal'] ?? 0);
    $id_venta_sucursal = (int) ($venta['id_venta_sucursal'] ?? 0);

    if ($numero_plazos <= 0 || $id_sucursal <= 0) {
        throw new Exception('Datos de venta incompletos');
    }

    $stmtS = mysqli_prepare(
        $conexion,
        'SELECT porcentaje_gastos_adelantos FROM sucursal WHERE id_sucursal = ? LIMIT 1'
    );
    if (!$stmtS) {
        throw new Exception(mysqli_error($conexion));
    }
    mysqli_stmt_bind_param($stmtS, 'i', $id_sucursal);
    mysqli_stmt_execute($stmtS);
    $rs = mysqli_stmt_get_result($stmtS);
    $rowS = $rs ? mysqli_fetch_assoc($rs) : null;
    mysqli_stmt_close($stmtS);
    if (!$rowS) {
        throw new Exception('Sucursal no encontrada');
    }
    $porcentaje_gastos = (float) ($rowS['porcentaje_gastos_adelantos'] ?? 0);

    $stmtPg = mysqli_prepare(
        $conexion,
        "SELECT COUNT(id) AS c, COALESCE(SUM(importe), 0) AS total_pagado
         FROM ventas_plazos WHERE id_venta = ? AND estado = 'Pagado'"
    );
    mysqli_stmt_bind_param($stmtPg, 'i', $id_venta);
    mysqli_stmt_execute($stmtPg);
    $rpg = mysqli_stmt_get_result($stmtPg);
    $rowPg = $rpg ? mysqli_fetch_assoc($rpg) : null;
    mysqli_stmt_close($stmtPg);
    $plazos_pagados = (int) ($rowPg['c'] ?? 0);
    $capital_actual = (float) ($rowPg['total_pagado'] ?? 0);
    $precio_venta = (float) ($venta['precio'] ?? 0);
    $total_pendiente = max(0, $precio_venta - $capital_actual);

    $stmtUlt = mysqli_prepare(
        $conexion,
        'SELECT importe FROM ventas_plazos WHERE id_venta = ? ORDER BY id DESC LIMIT 1'
    );
    mysqli_stmt_bind_param($stmtUlt, 'i', $id_venta);
    mysqli_stmt_execute($stmtUlt);
    $rult = mysqli_stmt_get_result($stmtUlt);
    $rowUlt = $rult ? mysqli_fetch_assoc($rult) : null;
    mysqli_stmt_close($stmtUlt);
    $importe_plazo_antiguo = (float) ($rowUlt['importe'] ?? 0);

    $stmtVen = mysqli_prepare(
        $conexion,
        "SELECT COUNT(id) AS c FROM ventas_plazos WHERE id_venta = ? AND estado = 'Vencido'"
    );
    mysqli_stmt_bind_param($stmtVen, 'i', $id_venta);
    mysqli_stmt_execute($stmtVen);
    $rven = mysqli_stmt_get_result($stmtVen);
    $rowVen = $rven ? mysqli_fetch_assoc($rven) : null;
    mysqli_stmt_close($stmtVen);
    $plazos_vencidos = (int) ($rowVen['c'] ?? 0);
    if ($plazos_vencidos > 0) {
        throw new Exception('No se puede adelantar capital con plazos vencidos');
    }

    $stmtPend = mysqli_prepare(
        $conexion,
        "SELECT COUNT(id) AS c FROM ventas_plazos WHERE id_venta = ? AND estado = 'Pendiente'"
    );
    mysqli_stmt_bind_param($stmtPend, 'i', $id_venta);
    mysqli_stmt_execute($stmtPend);
    $rpend = mysqli_stmt_get_result($stmtPend);
    $rowPend = $rpend ? mysqli_fetch_assoc($rpend) : null;
    mysqli_stmt_close($stmtPend);
    $plazos_pendientes = (int) ($rowPend['c'] ?? 0);

    if ($plazos_pendientes <= 0) {
        throw new Exception('No quedan plazos pendientes');
    }
    if ($total_pendiente <= 0) {
        throw new Exception('No hay capital pendiente');
    }
    if ($adelanto_cliente - 0.005 > $total_pendiente) {
        throw new Exception('El adelanto no puede superar el capital pendiente');
    }

    $nuevo_capital = round($capital_actual + $adelanto_cliente, 2);
    $resto_plazos = round($precio_venta - $nuevo_capital, 2);
    $nuevo_importe_plazo = round($resto_plazos / $plazos_pendientes, 2);
    if ($nuevo_importe_plazo < 0) {
        throw new Exception('El nuevo importe por plazo no es válido');
    }

    $gastos_adelantos = round($adelanto_cliente * $porcentaje_gastos / 100, 2);

    $nombre_archivo = '';
    if ($foto_camara === 'true') {
        if ($forma_de_pago !== 'efectivo') {
            if ($id_foto_cache <= 0) {
                throw new Exception('Falta el ID de la foto desde móvil');
            }
            $stmtF = mysqli_prepare(
                $conexion,
                'SELECT nombre_foto FROM fotos_app_adelantos_cache WHERE id_foto = ? AND id_sucursal = ? AND id_venta = ? LIMIT 1'
            );
            if (!$stmtF) {
                throw new Exception(mysqli_error($conexion));
            }
            mysqli_stmt_bind_param($stmtF, 'iii', $id_foto_cache, $id_sucursal, $id_venta);
            mysqli_stmt_execute($stmtF);
            $rf = mysqli_stmt_get_result($stmtF);
            $rowF = $rf ? mysqli_fetch_assoc($rf) : null;
            mysqli_stmt_close($stmtF);
            $nombre_archivo = isset($rowF['nombre_foto']) ? trim((string) $rowF['nombre_foto']) : '';
            if ($nombre_archivo === '') {
                throw new Exception('Todavía no se ha subido la foto desde el móvil');
            }
        }
    } else {
        if ($forma_de_pago !== 'efectivo') {
            if (!$comprobante || ($comprobante['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                throw new Exception('Debe adjuntar el comprobante de pago');
            }
            if ($comprobante['size'] > 5 * 1024 * 1024) {
                throw new Exception('El archivo es demasiado grande. Máximo 5MB');
            }
            $extension = strtolower(pathinfo($comprobante['name'], PATHINFO_EXTENSION));
            if (!in_array($extension, ['pdf', 'jpg', 'jpeg', 'png'], true)) {
                throw new Exception('Formato de archivo no permitido');
            }
            $nombre_archivo = generarNombreUnico() . '.' . $extension;
            $ruta_completa = __DIR__ . '/../../../photos/' . $nombre_archivo;
            if ($extension === 'pdf') {
                if (!move_uploaded_file($comprobante['tmp_name'], $ruta_completa)) {
                    throw new Exception('Error al guardar el PDF');
                }
            } else {
                if (!extension_loaded('gd')) {
                    throw new Exception('GD no disponible para procesar imágenes');
                }
                $imagen_procesada = procesarYRedimensionarImagen($comprobante['tmp_name'], $extension);
                if (!$imagen_procesada) {
                    throw new Exception('Error al procesar la imagen');
                }
                if (!file_put_contents($ruta_completa, $imagen_procesada)) {
                    throw new Exception('Error al guardar la imagen');
                }
            }
        }
    }

    $stmtCntAd = mysqli_prepare(
        $conexion,
        'SELECT COUNT(id_adelanto_capital) AS c FROM adelantos_capital_venta WHERE id_venta_adelanto = ? AND sucursal_adelanto = ?'
    );
    mysqli_stmt_bind_param($stmtCntAd, 'ii', $id_venta, $id_sucursal);
    mysqli_stmt_execute($stmtCntAd);
    $rca = mysqli_stmt_get_result($stmtCntAd);
    $rowCa = $rca ? mysqli_fetch_assoc($rca) : null;
    mysqli_stmt_close($stmtCntAd);
    $adelanto_numero = (int) ($rowCa['c'] ?? 0) + 1;

    $metodo_db = $forma_de_pago === 'efectivo' ? 'efectivo' : $forma_de_pago;
    $uid = (int) $usuario_id;

    mysqli_begin_transaction($conexion);
    $txActiva = true;

    $stmtUp = mysqli_prepare(
        $conexion,
        "UPDATE ventas_plazos SET importe = ? WHERE id_venta = ? AND estado = 'Pendiente'"
    );
    if (!$stmtUp) {
        throw new Exception(mysqli_error($conexion));
    }
    mysqli_stmt_bind_param($stmtUp, 'di', $nuevo_importe_plazo, $id_venta);
    if (!mysqli_stmt_execute($stmtUp)) {
        mysqli_stmt_close($stmtUp);
        throw new Exception(mysqli_stmt_error($stmtUp));
    }
    mysqli_stmt_close($stmtUp);

    $stmtIns = mysqli_prepare(
        $conexion,
        'INSERT INTO adelantos_capital_venta (
            id_venta_adelanto,
            sucursal_adelanto,
            cliente_adelanto,
            usuario_adelanto,
            fecha_adelanto,
            importe_adelanto,
            capital_antiguo,
            importe_plazo_antiguo,
            nuevo_capital,
            forma_de_pago,
            nombre_foto,
            nuevo_importe_plazo
        ) VALUES (?, ?, ?, ?, NOW(), ?, ?, ?, ?, ?, ?, ?)'
    );
    if (!$stmtIns) {
        throw new Exception(mysqli_error($conexion));
    }
    mysqli_stmt_bind_param(
        $stmtIns,
        'iiiiddddssd',
        $id_venta,
        $id_sucursal,
        $id_cliente,
        $uid,
        $adelanto_cliente,
        $capital_actual,
        $importe_plazo_antiguo,
        $nuevo_capital,
        $metodo_db,
        $nombre_archivo,
        $nuevo_importe_plazo
    );
    if (!mysqli_stmt_execute($stmtIns)) {
        mysqli_stmt_close($stmtIns);
        throw new Exception(mysqli_stmt_error($stmtIns));
    }
    $id_adelanto_insertado = (int) mysqli_insert_id($conexion);
    mysqli_stmt_close($stmtIns);

    // Si la foto viene desde móvil, vincular el cache con el adelanto real
    if ($foto_camara === 'true' && $forma_de_pago !== 'efectivo' && $id_foto_cache > 0 && $id_adelanto_insertado > 0) {
        $stmtLink = mysqli_prepare(
            $conexion,
            'UPDATE fotos_app_adelantos_cache SET id_adelanto_venta = ? WHERE id_foto = ? AND id_sucursal = ? AND id_venta = ?'
        );
        if ($stmtLink) {
            mysqli_stmt_bind_param($stmtLink, 'iiii', $id_adelanto_insertado, $id_foto_cache, $id_sucursal, $id_venta);
            mysqli_stmt_execute($stmtLink);
            mysqli_stmt_close($stmtLink);
        }
    }

    if (!mysqli_commit($conexion)) {
        mysqli_rollback($conexion);
        throw new Exception('No se pudo confirmar la operación');
    }
    $txActiva = false;

    $grupos = 'Adelanto capital Ventas a Plazos';
    $texto_adelanto = 'Adelanto de capital Nº ' . $adelanto_numero . ' de la venta Nº ' . $id_venta_sucursal;
    $texto_gastos = 'Gastos del adelanto de capital Nº ' . $adelanto_numero . ' de la venta Nº ' . $id_venta_sucursal;

    if ($forma_de_pago === 'efectivo') {
        insertar_movimiento_caja($grupos, $texto_adelanto, $adelanto_cliente, 0, $uid, $id_sucursal);
        if ($gastos_adelantos > 0.005) {
            insertar_movimiento_caja($grupos, $texto_gastos, $gastos_adelantos, 0, $uid, $id_sucursal);
        }
    } elseif ($forma_de_pago === 'transferencia') {
        insertar_movimiento_transferencia($id_sucursal, 0, $id_venta_sucursal, $texto_adelanto, $adelanto_cliente, 0, $uid, $grupos);
        if ($gastos_adelantos > 0.005) {
            insertar_movimiento_transferencia($id_sucursal, 0, $id_venta_sucursal, $texto_gastos, $gastos_adelantos, 0, $uid, $grupos);
        }
    } elseif ($forma_de_pago === 'tarjeta') {
        insertar_movimiento_tarjeta($id_sucursal, 0, $id_venta_sucursal, $texto_adelanto, $adelanto_cliente, $uid, $grupos);
        if ($gastos_adelantos > 0.005) {
            insertar_movimiento_tarjeta($id_sucursal, 0, $id_venta_sucursal, $texto_gastos, $gastos_adelantos, $uid, $grupos);
        }
    } elseif ($forma_de_pago === 'bizum') {
        insertar_movimiento_bizum($id_sucursal, 0, $id_venta_sucursal, $texto_adelanto, $adelanto_cliente, $uid, $grupos);
        if ($gastos_adelantos > 0.005) {
            insertar_movimiento_bizum($id_sucursal, 0, $id_venta_sucursal, $texto_gastos, $gastos_adelantos, $uid, $grupos);
        }
    }

    mysqli_close($conexion);

    echo json_encode([
        'success' => true,
        'message' => 'Adelanto de capital registrado correctamente',
        'nuevo_importe_plazo' => $nuevo_importe_plazo,
        'nuevo_capital' => $nuevo_capital,
        'gastos_adelantos' => $gastos_adelantos,
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    if ($txActiva && $conexion) {
        @mysqli_rollback($conexion);
    }
    if ($conexion) {
        mysqli_close($conexion);
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
