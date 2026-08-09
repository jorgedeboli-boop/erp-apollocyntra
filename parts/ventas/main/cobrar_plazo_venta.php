<?php
/**
 * Registra cobro de una cuota ventas_plazos: marca la cuota como Pagada,
 * guarda comprobante_plazo desde fotos_app_adelantos_cache y registra el movimiento de caja.
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

    $id_venta = isset($_POST['id_venta']) ? (int) $_POST['id_venta'] : 0;
    $id_plazo = isset($_POST['id_plazo']) ? (int) $_POST['id_plazo'] : 0;
    $forma_de_pago = isset($_POST['forma_de_pago']) ? trim((string) $_POST['forma_de_pago']) : '';
    $importe_post = isset($_POST['importe_plazo']) ? (float) $_POST['importe_plazo'] : 0.0;
    $id_foto_cache = isset($_POST['id_foto_cache_plazo_venta']) ? (int) $_POST['id_foto_cache_plazo_venta'] : 0;
    $comprobante_plazo = isset($_POST['comprobante_plazo']) ? trim((string) $_POST['comprobante_plazo']) : '';
    $foto_camara = isset($_POST['foto_camara']) ? trim((string) $_POST['foto_camara']) : '';
    $comprobante_archivo = isset($_FILES['comprobante_cobrar_plazo_venta_archivo']) ? $_FILES['comprobante_cobrar_plazo_venta_archivo'] : null;

    if ($id_venta <= 0 || $id_plazo <= 0) {
        throw new Exception('Datos de venta o cuota no válidos');
    }

    $formas = ['efectivo', 'tarjeta', 'transferencia', 'bizum'];
    if (!in_array($forma_de_pago, $formas, true)) {
        throw new Exception('Forma de pago no válida');
    }
    if ($forma_de_pago === 'efectivo'){ $forma_de_pago = 'contado';  }
    $cant_contado = 0.0;
    $cant_tarjeta = 0.0;
    $cant_transferencia = 0.0;
    $cant_bizum = 0.0;
    if ($forma_de_pago === 'combinado') {
        $c = $cobro['combinado'];
        $cant_contado = (float) ($c['contado'] ?? 0);
        $cant_tarjeta = (float) ($c['tarjeta'] ?? 0);
        $cant_bizum = (float) ($c['bizum'] ?? 0);
        $cant_transferencia = (float) ($c['transferencia'] ?? 0);
    } elseif ($forma_de_pago === 'contado') {
        $cant_contado = $importe_post;
    } elseif ($forma_de_pago === 'tarjeta') {
        $cant_tarjeta = $importe_post;
    } elseif ($forma_de_pago === 'bizum') {
        $cant_bizum = $importe_post;
    } else {
        $cant_transferencia = $importe_post;
    }


    if (!$conexion) {
        throw new Exception('Sin conexión a la base de datos');
    }

    $stmtV = mysqli_prepare(
        $conexion,
        'SELECT v.id, v.id_sucursal, v.id_venta_sucursal, v.precio, v.numero_plazos, v.estado, v.venta_plazos
         FROM ventas v
         WHERE v.id = ?
         LIMIT 1'
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

    $id_sucursal = (int) ($venta['id_sucursal'] ?? 0);
    $id_venta_sucursal = (int) ($venta['id_venta_sucursal'] ?? 0);

    $stmtPl = mysqli_prepare(
        $conexion,
        'SELECT vp.id, vp.id_venta, vp.estado, vp.importe,
                (SELECT COUNT(*) FROM ventas_plazos v2 WHERE v2.id_venta = vp.id_venta AND v2.id <= vp.id) AS numero_cuota
         FROM ventas_plazos vp
         WHERE vp.id = ? AND vp.id_venta = ?
         LIMIT 1'
    );
    if (!$stmtPl) {
        throw new Exception(mysqli_error($conexion));
    }
    mysqli_stmt_bind_param($stmtPl, 'ii', $id_plazo, $id_venta);
    mysqli_stmt_execute($stmtPl);
    $rp = mysqli_stmt_get_result($stmtPl);
    $plazo = $rp ? mysqli_fetch_assoc($rp) : null;
    mysqli_stmt_close($stmtPl);
    if (!$plazo) {
        throw new Exception('Cuota no encontrada');
    }

    $numero_cuota = max(1, (int) ($plazo['numero_cuota'] ?? 0));

    $estado = (string) ($plazo['estado'] ?? '');
    if ($estado !== 'Pendiente' && $estado !== 'Vencido') {
        throw new Exception('Esta cuota no admite cobro');
    }

    if ($estado === 'Pendiente') {
        $stmtVen = mysqli_prepare(
            $conexion,
            "SELECT COUNT(*) AS c FROM ventas_plazos WHERE id_venta = ? AND estado = 'Vencido'"
        );
        if ($stmtVen) {
            mysqli_stmt_bind_param($stmtVen, 'i', $id_venta);
            mysqli_stmt_execute($stmtVen);
            $rven = mysqli_stmt_get_result($stmtVen);
            $rowVen = $rven ? mysqli_fetch_assoc($rven) : null;
            mysqli_stmt_close($stmtVen);
            if ((int) ($rowVen['c'] ?? 0) > 0) {
                throw new Exception('Debe cobrar primero las cuotas vencidas');
            }
        }
    }

    $importe_db = (float) ($plazo['importe'] ?? 0);
    if ($importe_db <= 0) {
        throw new Exception('Importe de cuota no válido');
    }
    if (abs($importe_post - $importe_db) > 0.10) {
        throw new Exception('El importe no coincide con la cuota');
    }

    $metodo_pago = $forma_de_pago;

    $accion_historico = 'Cobro plazo venta Nº ' . $numero_cuota . ' de la venta Nº ' . $id_venta_sucursal . ' metodo de pago ' . $metodo_pago;
    $origen = 'Central';
    insertAccionPlazoVenta($id_sucursal, $id_plazo, $id_venta, $usuario_id, $accion_historico, $origen);

    if ($forma_de_pago !== 'contado') {
        if ($comprobante_plazo === '' && $foto_camara === 'true') {
            if ($id_foto_cache > 0) {
                $stmtF = mysqli_prepare(
                    $conexion,
                    'SELECT nombre_foto FROM fotos_app_adelantos_cache WHERE id_foto = ? AND id_sucursal = ? AND id_venta = ? AND id_plazo_venta = ? LIMIT 1'
                );
                if (!$stmtF) {
                    throw new Exception(mysqli_error($conexion));
                }
                mysqli_stmt_bind_param($stmtF, 'iiii', $id_foto_cache, $id_sucursal, $id_venta, $id_plazo);
                mysqli_stmt_execute($stmtF);
                $rf = mysqli_stmt_get_result($stmtF);
                $rowF = $rf ? mysqli_fetch_assoc($rf) : null;
                mysqli_stmt_close($stmtF);
                $comprobante_plazo = isset($rowF['nombre_foto']) ? trim((string) $rowF['nombre_foto']) : '';
            }

            if ($comprobante_plazo === '') {
                $stmtF = mysqli_prepare(
                    $conexion,
                    'SELECT nombre_foto FROM fotos_app_adelantos_cache
                     WHERE id_sucursal = ? AND id_venta = ? AND id_plazo_venta = ? AND nombre_foto <> ""
                     ORDER BY id_foto DESC LIMIT 1'
                );
                if (!$stmtF) {
                    throw new Exception(mysqli_error($conexion));
                }
                mysqli_stmt_bind_param($stmtF, 'iii', $id_sucursal, $id_venta, $id_plazo);
                mysqli_stmt_execute($stmtF);
                $rf = mysqli_stmt_get_result($stmtF);
                $rowF = $rf ? mysqli_fetch_assoc($rf) : null;
                mysqli_stmt_close($stmtF);
                $comprobante_plazo = isset($rowF['nombre_foto']) ? trim((string) $rowF['nombre_foto']) : '';
            }
        } elseif ($comprobante_plazo === '' && $comprobante_archivo && ($comprobante_archivo['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
            if ($comprobante_archivo['size'] > 5 * 1024 * 1024) {
                throw new Exception('El archivo es demasiado grande. Máximo 5MB');
            }
            $extension = strtolower(pathinfo($comprobante_archivo['name'], PATHINFO_EXTENSION));
            if (!in_array($extension, ['pdf', 'jpg', 'jpeg', 'png'], true)) {
                throw new Exception('Formato de archivo no permitido');
            }
            $comprobante_plazo = generarNombreUnico() . '.' . $extension;
            $ruta_completa = __DIR__ . '/../../../photos/' . $comprobante_plazo;
            if ($extension === 'pdf') {
                if (!move_uploaded_file($comprobante_archivo['tmp_name'], $ruta_completa)) {
                    throw new Exception('Error al guardar el PDF');
                }
            } else {
                if (!extension_loaded('gd')) {
                    throw new Exception('GD no disponible para procesar imágenes');
                }
                $imagen_procesada = procesarYRedimensionarImagen($comprobante_archivo['tmp_name'], $extension);
                if (!$imagen_procesada) {
                    throw new Exception('Error al procesar la imagen');
                }
                if (!file_put_contents($ruta_completa, $imagen_procesada)) {
                    throw new Exception('Error al guardar la imagen');
                }
            }
        }

        if ($comprobante_plazo === '') {
            throw new Exception('Debe adjuntar el comprobante de pago');
        }
    }

    $stmtU = mysqli_prepare(
        $conexion,
        "UPDATE ventas_plazos SET estado = 'Pagado', fecha_cobrado = NOW(), metodo_pago = ?, comprobante_plazo = ?, cantidad_contado = ?,  cantidad_transferencia = ?, cantidad_bizum = ?, cantidad_tarjeta = ?
         WHERE id = ? AND id_venta = ? AND estado IN ('Pendiente','Vencido')"
    );
    if (!$stmtU) {
        throw new Exception(mysqli_error($conexion));
    }
    mysqli_stmt_bind_param($stmtU, 'ssddddii', $metodo_pago, $comprobante_plazo, $cant_contado, $cant_transferencia, $cant_bizum, $cant_tarjeta , $id_plazo, $id_venta);
    mysqli_stmt_execute($stmtU);
    $af = mysqli_stmt_affected_rows($stmtU);
    mysqli_stmt_close($stmtU);
    if ($af !== 1) {
        throw new Exception('No se pudo actualizar la cuota (¿ya estaba cobrada?)');
    }

    if ($comprobante_plazo !== '') {
        $stmtDel = mysqli_prepare(
            $conexion,
            'DELETE FROM fotos_app_adelantos_cache WHERE id_sucursal = ? AND id_venta = ? AND id_plazo_venta = ?'
        );
        if (!$stmtDel) {
            throw new Exception(mysqli_error($conexion));
        }
        mysqli_stmt_bind_param($stmtDel, 'iii', $id_sucursal, $id_venta, $id_plazo);
        if (!mysqli_stmt_execute($stmtDel)) {
            mysqli_stmt_close($stmtDel);
            throw new Exception(mysqli_stmt_error($stmtDel));
        }
        mysqli_stmt_close($stmtDel);
    }

    $concepto = 'Cobro plazo venta Nº ' . $id_venta_sucursal . ' (Cuota Nº' . $numero_cuota . ')';
    $grupos_caja = 'Ventas a plazos';
    $uidMov = (int) $usuario_id;
    $entrada = $importe_db;
    $salida = 0;

    if ($forma_de_pago === 'contado') {
        insertar_movimiento_caja($grupos_caja, $concepto, $entrada, $salida, $uidMov, $id_sucursal);
    } elseif ($forma_de_pago === 'transferencia') {
        insertar_movimiento_transferencia($id_sucursal, 0, $id_venta_sucursal, $concepto, $entrada, $salida, $uidMov, $grupos_caja);
    } elseif ($forma_de_pago === 'tarjeta') {
        insertar_movimiento_tarjeta($id_sucursal, 0, $id_venta_sucursal, $concepto, $entrada, $uidMov, $grupos_caja);
    } elseif ($forma_de_pago === 'bizum') {
        insertar_movimiento_bizum($id_sucursal, 0, $id_venta_sucursal, $concepto, $entrada, $uidMov, $grupos_caja);
    }

    $resumen_badges = [
        'estado_class' => 'secondary',
        'estado_texto' => '',
        'plazos_pagados' => 0,
        'plazos_pendientes' => 0,
        'total_pagado' => 0.0,
        'total_pendiente' => 0.0,
    ];
    $estRb = strtolower((string) ($venta['estado'] ?? ''));
    $resumen_badges['estado_texto'] = (string) ($venta['estado'] ?? '');
    if ($estRb === 'vendido') {
        $resumen_badges['estado_class'] = 'success';
    } elseif ($estRb === 'anulada' || $estRb === 'anulado') {
        $resumen_badges['estado_class'] = 'danger';
    } elseif ($estRb === 'enfecha') {
        $resumen_badges['estado_class'] = 'info';
        $resumen_badges['estado_texto'] = 'en plazo';
    } elseif ($estRb === 'vencido') {
        $resumen_badges['estado_class'] = 'warning';
        $resumen_badges['estado_texto'] = 'vencida';
    }
    $precioRb = (float) ($venta['precio'] ?? 0);
    $numPlazosRb = (int) ($venta['numero_plazos'] ?? 0);
    $stmtPgRb = mysqli_prepare(
        $conexion,
        "SELECT COUNT(id) AS c, COALESCE(SUM(importe), 0) AS total_pagado
         FROM ventas_plazos WHERE id_venta = ? AND estado = 'Pagado'"
    );
    if ($stmtPgRb) {
        mysqli_stmt_bind_param($stmtPgRb, 'i', $id_venta);
        mysqli_stmt_execute($stmtPgRb);
        $rpgRb = mysqli_stmt_get_result($stmtPgRb);
        $rowPgRb = $rpgRb ? mysqli_fetch_assoc($rpgRb) : null;
        mysqli_stmt_close($stmtPgRb);
        $resumen_badges['plazos_pagados'] = (int) ($rowPgRb['c'] ?? 0);
        $resumen_badges['total_pagado'] = round((float) ($rowPgRb['total_pagado'] ?? 0), 2);
        $resumen_badges['plazos_pendientes'] = max(0, $numPlazosRb - $resumen_badges['plazos_pagados']);
        $resumen_badges['total_pendiente'] = round(max(0, $precioRb - $resumen_badges['total_pagado']), 2);
    }

    mysqli_close($conexion);

    echo json_encode([
        'success' => true,
        'message' => 'Plazo cobrado correctamente',
        'resumen_badges' => $resumen_badges,
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    if ($conexion) {
        mysqli_close($conexion);
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
