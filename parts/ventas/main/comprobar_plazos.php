<?php
/**
 * Tras cobrar un plazo: comprueba si la venta debe cerrarse (vendido) o generar la siguiente cuota.
 */
require_once __DIR__ . '/../../../include/session.php';
require_once __DIR__ . '/../../../include/functions.php';
require_once __DIR__ . '/venta_plazos_factura_lib.php';

header('Content-Type: application/json; charset=utf-8');

function cpv_resumen_badges($conexion, $id_venta, $precio, $numero_plazos)
{
    $resumen = [
        'estado_class' => 'secondary',
        'estado_texto' => '',
        'plazos_pagados' => 0,
        'plazos_pendientes' => 0,
        'total_pagado' => 0.0,
        'total_pendiente' => 0.0,
    ];

    $stmtE = mysqli_prepare($conexion, 'SELECT estado FROM ventas WHERE id = ? LIMIT 1');
    if ($stmtE) {
        mysqli_stmt_bind_param($stmtE, 'i', $id_venta);
        mysqli_stmt_execute($stmtE);
        $re = mysqli_stmt_get_result($stmtE);
        $rowE = $re ? mysqli_fetch_assoc($re) : null;
        mysqli_stmt_close($stmtE);
        if ($rowE) {
            $estRb = strtolower((string) ($rowE['estado'] ?? ''));
            $resumen['estado_texto'] = (string) ($rowE['estado'] ?? '');
            if ($estRb === 'vendido') {
                $resumen['estado_class'] = 'success';
            } elseif ($estRb === 'anulada' || $estRb === 'anulado') {
                $resumen['estado_class'] = 'danger';
            } elseif ($estRb === 'enfecha') {
                $resumen['estado_class'] = 'info';
                $resumen['estado_texto'] = 'en plazo';
            } elseif ($estRb === 'vencido') {
                $resumen['estado_class'] = 'warning';
                $resumen['estado_texto'] = 'vencida';
            }
        }
    }

    $stmtPg = mysqli_prepare(
        $conexion,
        "SELECT COUNT(id) AS c, COALESCE(SUM(importe), 0) AS total_pagado
         FROM ventas_plazos WHERE id_venta = ? AND estado = 'Pagado'"
    );
    if ($stmtPg) {
        mysqli_stmt_bind_param($stmtPg, 'i', $id_venta);
        mysqli_stmt_execute($stmtPg);
        $rpg = mysqli_stmt_get_result($stmtPg);
        $rowPg = $rpg ? mysqli_fetch_assoc($rpg) : null;
        mysqli_stmt_close($stmtPg);
        $resumen['plazos_pagados'] = (int) ($rowPg['c'] ?? 0);
        $resumen['total_pagado'] = round((float) ($rowPg['total_pagado'] ?? 0), 2);
        $resumen['plazos_pendientes'] = max(0, (int) $numero_plazos - $resumen['plazos_pagados']);
        $resumen['total_pendiente'] = round(max(0, (float) $precio - $resumen['total_pagado']), 2);
    }

    return $resumen;
}

/**
 * Cierra la venta a plazos (estado vendido) y actualiza artículos.
 *
 * @return bool true si se cerró la venta en esta llamada
 */
function cpv_cerrar_venta_plazos_completada(
    mysqli $conexion,
    int $id_venta,
    int $id_sucursal,
    int $id_venta_sucursal,
    string $nombre_sucursal,
    int $usuario_id
): bool {
    $stmtEst = mysqli_prepare($conexion, 'SELECT estado FROM ventas WHERE id = ? LIMIT 1');
    if (!$stmtEst) {
        throw new Exception(mysqli_error($conexion));
    }
    mysqli_stmt_bind_param($stmtEst, 'i', $id_venta);
    mysqli_stmt_execute($stmtEst);
    $re = mysqli_stmt_get_result($stmtEst);
    $rowEst = $re ? mysqli_fetch_assoc($re) : null;
    mysqli_stmt_close($stmtEst);
    $ya_vendida = strtolower((string) ($rowEst['estado'] ?? '')) === 'vendido';

    if (!$ya_vendida) {
        $stmtVend = mysqli_prepare(
            $conexion,
            "UPDATE ventas SET estado = 'vendido', fecha = NOW() WHERE id = ? LIMIT 1"
        );
        if (!$stmtVend) {
            throw new Exception(mysqli_error($conexion));
        }
        mysqli_stmt_bind_param($stmtVend, 'i', $id_venta);
        if (!mysqli_stmt_execute($stmtVend)) {
            mysqli_stmt_close($stmtVend);
            throw new Exception(mysqli_stmt_error($stmtVend));
        }
        mysqli_stmt_close($stmtVend);
    }

    venta_plazos_marcar_articulos_vendidos(
        $conexion,
        $id_venta,
        $id_sucursal,
        $id_venta_sucursal,
        $nombre_sucursal,
        $usuario_id
    );

    return !$ya_vendida;
}

$conexion = conectar_bd();

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Método no permitido']);
        exit;
    }

    $id_venta = isset($_POST['id_venta']) ? (int) $_POST['id_venta'] : 0;
    if ($id_venta <= 0) {
        throw new Exception('id_venta no válido');
    }

    if (!$conexion) {
        throw new Exception('Sin conexión a la base de datos');
    }

    $stmtV = mysqli_prepare(
        $conexion,
        'SELECT v.id, v.precio, v.numero_plazos, v.venta_plazos, v.estado, v.id_sucursal, v.id_venta_sucursal,
                s.nombre_sucursal
         FROM ventas v
         LEFT JOIN sucursal s ON v.id_sucursal = s.id_sucursal
         WHERE v.id = ? LIMIT 1'
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

    $precio = (float) ($venta['precio'] ?? 0);
    $numero_plazos = (int) ($venta['numero_plazos'] ?? 0);
    $id_sucursal_venta = (int) ($venta['id_sucursal'] ?? 0);
    $id_venta_sucursal = (int) ($venta['id_venta_sucursal'] ?? 0);
    $nombre_sucursal = trim((string) ($venta['nombre_sucursal'] ?? ''));
    $accion = 'sin_cambios';
    $message = 'Sin cambios en la venta';
    $tiene_factura = false;

    $stmtSum = mysqli_prepare(
        $conexion,
        "SELECT COALESCE(SUM(importe), 0) AS total_pagado FROM ventas_plazos WHERE id_venta = ? AND estado = 'Pagado'"
    );
    if (!$stmtSum) {
        throw new Exception(mysqli_error($conexion));
    }
    mysqli_stmt_bind_param($stmtSum, 'i', $id_venta);
    mysqli_stmt_execute($stmtSum);
    $rs = mysqli_stmt_get_result($stmtSum);
    $rowSum = $rs ? mysqli_fetch_assoc($rs) : null;
    mysqli_stmt_close($stmtSum);
    $total_pagado = round((float) ($rowSum['total_pagado'] ?? 0), 2);

    // 1) Total pagado igual al precio (±0,10 €) → venta vendida
    if ($precio > 0 && abs($total_pagado - $precio) <= 0.10) {
        if (cpv_cerrar_venta_plazos_completada(
            $conexion,
            $id_venta,
            $id_sucursal_venta,
            $id_venta_sucursal,
            $nombre_sucursal,
            (int) $usuario_id
        )) {
            $accion = 'vendido';
            $message = 'Venta cerrada: todos los plazos cobrados';
        }
        $tiene_factura = venta_plazos_tiene_factura_generada($conexion, $id_venta, $id_sucursal_venta);
    } elseif ($total_pagado < $precio) {
        // 2) Total pagado menor que precio venta
        $stmtCnt = mysqli_prepare(
            $conexion,
            'SELECT COUNT(id) AS c FROM ventas_plazos WHERE id_venta = ?'
        );
        if (!$stmtCnt) {
            throw new Exception(mysqli_error($conexion));
        }
        mysqli_stmt_bind_param($stmtCnt, 'i', $id_venta);
        mysqli_stmt_execute($stmtCnt);
        $rc = mysqli_stmt_get_result($stmtCnt);
        $rowCnt = $rc ? mysqli_fetch_assoc($rc) : null;
        mysqli_stmt_close($stmtCnt);
        $cantidad_plazos = (int) ($rowCnt['c'] ?? 0);

        if ($cantidad_plazos !== $numero_plazos) {
            // 3) No coincide cantidad de plazos con numero_plazos de la venta
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
            $pendientes = (int) ($rowPend['c'] ?? 0);

            if ($pendientes === 0) {
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
                $no_pagados = (int) ($rowNoPag['c'] ?? 0);

                if ($no_pagados === 0 && $cantidad_plazos < $numero_plazos) {
                    $stmtUlt = mysqli_prepare(
                        $conexion,
                        "SELECT fecha_vencimiento, importe FROM ventas_plazos
                         WHERE id_venta = ? AND estado = 'Pagado'
                         ORDER BY id DESC LIMIT 1"
                    );
                    if (!$stmtUlt) {
                        throw new Exception(mysqli_error($conexion));
                    }
                    mysqli_stmt_bind_param($stmtUlt, 'i', $id_venta);
                    mysqli_stmt_execute($stmtUlt);
                    $ru = mysqli_stmt_get_result($stmtUlt);
                    $ultimo = $ru ? mysqli_fetch_assoc($ru) : null;
                    mysqli_stmt_close($stmtUlt);
                    if (!$ultimo) {
                        throw new Exception('No se encontró el último plazo pagado');
                    }

                    $base = trim((string) ($ultimo['fecha_vencimiento'] ?? ''));
                    if ($base === '' || substr($base, 0, 10) === '0000-00-00') {
                        $base = date('Y-m-d');
                    } else {
                        $base = substr($base, 0, 10);
                    }
                    $tProximo = strtotime($base . ' +1 month');
                    if ($tProximo === false) {
                        throw new Exception('No se pudo calcular el próximo vencimiento');
                    }
                    $fecha_siguiente = date('Y-m-d H:i:s', $tProximo);

                    // Si el plazo que se inserta es el último, el importe es el resto hasta el precio de venta
                    $es_ultimo_plazo = ($cantidad_plazos + 1) >= $numero_plazos;
                    if ($es_ultimo_plazo) {
                        $importe_siguiente = round($precio - $total_pagado, 2);
                    } else {
                        $importe_siguiente = round((float) ($ultimo['importe'] ?? 0), 2);
                    }

                    if ($importe_siguiente <= 0) {
                        throw new Exception('Importe del siguiente plazo no válido');
                    }

                    $stmtIns = mysqli_prepare(
                        $conexion,
                        'INSERT INTO ventas_plazos (id_venta, estado, fecha_vencimiento, importe) VALUES (?, ?, ?, ?)'
                    );
                    if (!$stmtIns) {
                        throw new Exception(mysqli_error($conexion));
                    }
                    $estPend = 'Pendiente';
                    mysqli_stmt_bind_param($stmtIns, 'issd', $id_venta, $estPend, $fecha_siguiente, $importe_siguiente);
                    if (!mysqli_stmt_execute($stmtIns)) {
                        mysqli_stmt_close($stmtIns);
                        throw new Exception(mysqli_stmt_error($stmtIns));
                    }
                    mysqli_stmt_close($stmtIns);
                    $accion = 'plazo_creado';
                    $message = 'Siguiente cuota pendiente generada';
                }
            }
        } else {
            // Misma cantidad de plazos que numero_plazos: cerrar si ya están todos pagados
            $stmtPag = mysqli_prepare(
                $conexion,
                "SELECT COUNT(id) AS c FROM ventas_plazos WHERE id_venta = ? AND estado = 'Pagado'"
            );
            if (!$stmtPag) {
                throw new Exception(mysqli_error($conexion));
            }
            mysqli_stmt_bind_param($stmtPag, 'i', $id_venta);
            mysqli_stmt_execute($stmtPag);
            $rpag = mysqli_stmt_get_result($stmtPag);
            $rowPag = $rpag ? mysqli_fetch_assoc($rpag) : null;
            mysqli_stmt_close($stmtPag);
            $plazos_pagados = (int) ($rowPag['c'] ?? 0);

            if ($numero_plazos > 0 && $plazos_pagados >= $numero_plazos) {
                if (cpv_cerrar_venta_plazos_completada(
                    $conexion,
                    $id_venta,
                    $id_sucursal_venta,
                    $id_venta_sucursal,
                    $nombre_sucursal,
                    (int) $usuario_id
                )) {
                    $accion = 'vendido';
                    $message = 'Venta cerrada: todos los plazos cobrados';
                }
                $tiene_factura = venta_plazos_tiene_factura_generada($conexion, $id_venta, $id_sucursal_venta);
            }
        }
    }

    $resumen_badges = cpv_resumen_badges($conexion, $id_venta, $precio, $numero_plazos);
    mysqli_close($conexion);

    echo json_encode([
        'success' => true,
        'accion' => $accion,
        'message' => $message,
        'resumen_badges' => $resumen_badges,
        'precio_venta' => $precio,
        'tiene_factura' => $tiene_factura,
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    if ($conexion) {
        mysqli_close($conexion);
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
