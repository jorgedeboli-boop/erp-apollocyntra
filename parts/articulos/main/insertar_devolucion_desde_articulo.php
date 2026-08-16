<?php
/**
 * Registra la devolución desde la ficha del artículo (misma lógica que crear/insertar_devolucion)
 * y repone el artículo en tienda, caja y trazabilidad. Todo queda bajo parts/articulos_sucursal/main.
 */

require_once __DIR__ . '/../../../include/session.php';
require_once __DIR__ . '/../../../include/functions.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$id_articulo = isset($_POST['id_articulo']) ? (int) $_POST['id_articulo'] : 0;
$motivo = isset($_POST['motivo_devolucion']) ? trim($_POST['motivo_devolucion']) : '';
$id_autorizacion_req = isset($_POST['id_autorizacion']) ? (int) $_POST['id_autorizacion'] : 0;
$sucursal_devolucion = isset($_POST['sucursal_devolucion']) ? (int) $_POST['sucursal_devolucion'] : 0;

if (!$id_articulo || $motivo === '') {
    echo json_encode(['success' => false, 'message' => 'Faltan artículo o motivo de devolución.']);
    exit;
}

try {
    $conexion = conectar_bd();

    $stSucArt = mysqli_prepare($conexion, 'SELECT id_sucursal_destino FROM articulos_venta WHERE id = ? LIMIT 1');
    if (!$stSucArt) {
        throw new Exception('Error al comprobar sucursal del artículo: ' . mysqli_error($conexion));
    }
    mysqli_stmt_bind_param($stSucArt, 'i', $id_articulo);
    mysqli_stmt_execute($stSucArt);
    $resSucArt = mysqli_stmt_get_result($stSucArt);
    $rowSucArt = $resSucArt ? mysqli_fetch_assoc($resSucArt) : null;
    mysqli_stmt_close($stSucArt);
    if (!$rowSucArt) {
        mysqli_close($conexion);
        echo json_encode(['success' => false, 'message' => 'Artículo no encontrado.']);
        exit;
    }
    $suc_dest_art = isset($rowSucArt['id_sucursal_destino']) ? (int) $rowSucArt['id_sucursal_destino'] : 0;
    if ($sucursal_devolucion <= 0) {
        $sucursal_devolucion = $suc_dest_art;
    }

    if ($id_autorizacion_req > 0) {
        $sku_match = (string) $id_articulo;
        $stmt_auth = mysqli_prepare(
            $conexion,
            "SELECT id_autorizacion FROM autorizaciones_devoluciones
             WHERE id_autorizacion = ? AND estado_autorizacion = 'autorizada' AND sku_articulo_devolucion = ?
             LIMIT 1"
        );
        if (!$stmt_auth) {
            throw new Exception('Error al validar autorización: ' . mysqli_error($conexion));
        }
        mysqli_stmt_bind_param($stmt_auth, 'is', $id_autorizacion_req, $sku_match);
        mysqli_stmt_execute($stmt_auth);
        $res_auth = mysqli_stmt_get_result($stmt_auth);
        $ok_auth = $res_auth && mysqli_fetch_assoc($res_auth);
        mysqli_stmt_close($stmt_auth);
        if (!$ok_auth) {
            mysqli_close($conexion);
            echo json_encode(['success' => false, 'message' => 'Autorización no válida o no coincide con el artículo.']);
            exit;
        }
    }

    // Obtener la última venta asociada al artículo desde articulos_venta.last_id_venta
    $stmt_last = mysqli_prepare($conexion, 'SELECT last_id_venta FROM articulos_venta WHERE id = ? LIMIT 1');
    if (!$stmt_last) {
        throw new Exception('Error al preparar consulta last_id_venta: ' . mysqli_error($conexion));
    }
    mysqli_stmt_bind_param($stmt_last, 'i', $id_articulo);
    mysqli_stmt_execute($stmt_last);
    $res_last = mysqli_stmt_get_result($stmt_last);
    $row_last = $res_last ? mysqli_fetch_assoc($res_last) : null;
    mysqli_stmt_close($stmt_last);

    $last_id_venta = $row_last ? (int) ($row_last['last_id_venta'] ?? 0) : 0;
    if ($last_id_venta <= 0) {
        echo json_encode(['success' => false, 'message' => 'El artículo no tiene last_id_venta asociado.']);
        exit;
    }

    // Validar la venta por id (last_id_venta) y estado vendido
    $sql_venta = "SELECT
                    v.id,
                    v.cliente,
                    v.id_sucursal,
                    v.precio,
                    v.tipo_pago,
                    v.cantidad_contado,
                    v.cantidad_tarjeta,
                    v.cantidad_transferencia,
                    v.cantidad_bizum,
                    v.venta_web,
                    v.id_venta_sucursal,
                    f.id_factura AS id_factura,
                    f.factura_simplificada AS factura_simplificada,
                    fs.id_factura AS id_factura_simplificada,
                    f.numero_factura AS numero_factura,
                    fs.numero_factura AS numero_factura_simplificada,
                    f.fecha_factura AS fecha_factura,
                    fs.fecha_factura AS fecha_factura_simplificada,
                    f.prefijo_factura AS prefijo_factura,
                    fs.prefijo_factura AS prefijo_factura_simplificada,
                    f.factura_regimen AS factura_regimen,
                    fs.factura_regimen AS factura_regimen_simplificada,
                    f.tipo_factura AS tipo_factura,
                    fs.tipo_factura AS tipo_factura_simplificada,
                    f.rel_id_empresa AS rel_id_empresa,
                    fs.rel_id_empresa AS rel_id_empresa_simplificada,
                    f.cliente_factura AS cliente_factura,
                    fs.cliente_factura AS cliente_factura_simplificada,
                    f.id_rel_factura_fiskaly AS id_rel_factura_fiskaly,
                    fs.id_rel_factura_fiskaly AS id_rel_factura_fiskaly_simplificada
                  FROM ventas v
                  LEFT JOIN facturas f ON f.rel_id_venta = v.id
                  LEFT JOIN facturas_simplificadas fs ON fs.rel_id_venta = v.id AND f.id_factura IS NULL
                  WHERE v.id = ? AND v.estado = 'vendido'
                  LIMIT 1";
    $stmt_v = mysqli_prepare($conexion, $sql_venta);
    if (!$stmt_v) {
        throw new Exception('Error al preparar consulta de venta: ' . mysqli_error($conexion));
    }
    mysqli_stmt_bind_param($stmt_v, 'i', $last_id_venta);
    mysqli_stmt_execute($stmt_v);
    $res_v = mysqli_stmt_get_result($stmt_v);
    $venta = $res_v ? mysqli_fetch_assoc($res_v) : null;
    mysqli_stmt_close($stmt_v);

    if (!$venta) {
        echo json_encode(['success' => false, 'message' => 'No se encontró una venta para este artículo.']);
        exit;
    }

    $id_venta = (int) $venta['id'];
    $cliente = (int) $venta['cliente'];
    $sucursal = (int) $venta['id_sucursal'];
    $cantidad_contado = (float) $venta['cantidad_contado'];
    $cantidad_tarjeta = (float) $venta['cantidad_tarjeta'];
    $cantidad_transferencia = (float) $venta['cantidad_transferencia'];
    $cantidad_bizum = (float) $venta['cantidad_bizum'];
    // Importe de la devolución: precio del artículo (no el total del ticket/factura)
    $importe = 0.0;
    $stmtPrecio = mysqli_prepare(
        $conexion,
        'SELECT precio_venta FROM rel_articulos_venta WHERE sku_articulo = ? AND rel_id_venta = ? AND sucursal_venta = ? LIMIT 1'
    );
    if ($stmtPrecio) {
        mysqli_stmt_bind_param($stmtPrecio, 'iii', $id_articulo, $id_venta, $sucursal);
        mysqli_stmt_execute($stmtPrecio);
        $resPrecio = mysqli_stmt_get_result($stmtPrecio);
        $rowPrecio = $resPrecio ? mysqli_fetch_assoc($resPrecio) : null;
        mysqli_stmt_close($stmtPrecio);
        if ($rowPrecio && isset($rowPrecio['precio_venta'])) {
            $importe = (float) $rowPrecio['precio_venta'];
        }
    }
    if ($importe <= 0.0) {
        // Fallback: si no se encuentra la línea en rel_articulos_venta, usar el precio de ventas (compatibilidad).
        $importe = (float) $venta['precio'];
    }
    $forma_pago = $venta['tipo_pago'] ?: '';
    $devolucion_web = ($venta['venta_web'] === 'true') ? 'true' : 'false';
    
    

    $id_factura_generada = 0;
    $fiskaly_rect_result = null;
    $id_factura = (int) ($venta['id_factura'] ?? 0);
    $id_factura_simplificada = (int) ($venta['id_factura_simplificada'] ?? 0);
    // Unificada en `facturas` (completa o simplificada) o histórica en `facturas_simplificadas`.
    $es_simplificada_unificada = (
        $id_factura > 0
        && strtolower(trim((string) ($venta['factura_simplificada'] ?? ''))) === 'true'
    );
    $es_simplificada_historico = ($id_factura <= 0 && $id_factura_simplificada > 0);
    $es_simplificada = $es_simplificada_unificada || $es_simplificada_historico;
    $id_factura_orig = $id_factura > 0 ? $id_factura : $id_factura_simplificada;
    if ($id_factura_orig > 0) {
        // Histórico → facturas_rectificativas_simplificadas.
        // Unificada (simplificada o completa en `facturas`) → facturas_rectificativas.
        $stmtPref = mysqli_prepare(
            $conexion,
            'SELECT empresa_id FROM sucursal WHERE id_sucursal = ? LIMIT 1'
        );
        if (!$stmtPref) {
            throw new Exception('Error al leer sucursal para prefijo rectificativa: ' . mysqli_error($conexion));
        }
        mysqli_stmt_bind_param($stmtPref, 'i', $sucursal);
        mysqli_stmt_execute($stmtPref);
        $rp = mysqli_stmt_get_result($stmtPref);
        $rowP = $rp ? mysqli_fetch_assoc($rp) : null;
        mysqli_stmt_close($stmtPref);

        $rel_id_empresa_fact = $id_factura > 0
            ? (int) ($venta['rel_id_empresa'] ?? 0)
            : (int) ($venta['rel_id_empresa_simplificada'] ?? 0);
        if ($rel_id_empresa_fact <= 0 && $rowP) {
            $rel_id_empresa_fact = (int) ($rowP['empresa_id'] ?? 0);
        }

        $tipo_factura_v = $id_factura > 0
            ? (string) ($venta['tipo_factura'] ?? 'articulos')
            : (string) ($venta['tipo_factura_simplificada'] ?? 'articulos');
        $tipos_factura_ok = ['articulos', 'renovaciones', 'oro_inversion'];
        if (!in_array($tipo_factura_v, $tipos_factura_ok, true)) {
            $tipo_factura_v = 'articulos';
        }

        try {
            $prefijo_f = facturaConstruirPrefijoRectificativa($sucursal, $es_simplificada, $tipo_factura_v);
        } catch (Throwable $ePref) {
            $prefijo_f = '';
        }
        $prefijo_f = substr(trim((string) $prefijo_f), 0, 10);

        if ($es_simplificada_historico) {
            $numero_sig = (int) obtenerNumeroFacturaRectificativaSimplificadas($sucursal, $tipo_factura_v);
        } else {
            $numero_sig = (int) obtenerNumeroFacturaRectificativa($sucursal, $tipo_factura_v);
        }

        $id_cliente_fact = $id_factura > 0
            ? (int) ($venta['cliente_factura'] ?? 0)
            : (int) ($venta['cliente_factura_simplificada'] ?? 0);
        if ($id_cliente_fact <= 0) {
            $id_cliente_fact = $cliente;
        }

        $usuario_id_rect = isset($_SESSION['usuario_id']) ? (int) $_SESSION['usuario_id'] : 0;
        if ($usuario_id_rect <= 0) {
            $ru0 = mysqli_query($conexion, 'SELECT id_usuario FROM usuarios LIMIT 1');
            if ($ru0 && $row_u0 = mysqli_fetch_assoc($ru0)) {
                $usuario_id_rect = (int) $row_u0['id_usuario'];
            }
        }

        $numero_factura_orig = $id_factura > 0
            ? (int) ($venta['numero_factura'] ?? 0)
            : (int) ($venta['numero_factura_simplificada'] ?? 0);
        $fecha_factura_orig = $id_factura > 0
            ? (string) ($venta['fecha_factura'] ?? '')
            : (string) ($venta['fecha_factura_simplificada'] ?? '');
        $prefijo_factura_orig = $id_factura > 0
            ? substr((string) ($venta['prefijo_factura'] ?? ''), 0, 5)
            : substr((string) ($venta['prefijo_factura_simplificada'] ?? ''), 0, 5);
        $factura_regimen_v = $id_factura > 0
            ? (string) ($venta['factura_regimen'] ?? 'false')
            : (string) ($venta['factura_regimen_simplificada'] ?? 'false');
        $regimen_ok_rect = ['false', 'Verifactu', 'TicketBAIBizkaia', 'TicketBAIAlava', 'TicketBAIGipuzkua', 'General'];
        if (!in_array($factura_regimen_v, $regimen_ok_rect, true)) {
            $factura_regimen_v = 'false';
        }

        $total_abono = -abs($importe);

        $crearRectFunc = $es_simplificada_historico
            ? 'crearFacturaRectificativaSimplificadas'
            : 'crearFacturaRectificativa';
        $id_factura_generada = $crearRectFunc(
            [
                'id_sucursal' => $sucursal,
                'numero_factura' => $numero_sig,
                'cliente_factura' => $id_cliente_fact,
                'facturado_por' => $usuario_id_rect,
                'estado_factura' => 'pagada',
                'tipo_pago_factura' => $forma_pago,
                'total_factura' => $total_abono,
                'rel_id_venta' => $id_venta,
                'prefijo_factura' => $prefijo_f,
                'tipo_factura' => $tipo_factura_v,
                'rel_id_empresa' => $rel_id_empresa_fact,
                'factura_regimen' => $factura_regimen_v,
                'rel_id_factura' => $id_factura_orig,
                'factura_original' => $numero_factura_orig,
                'motivo_rectificado' => $motivo,
                'fecha_factura_original' => $fecha_factura_orig,
                'prefijo_factura_original' => $prefijo_factura_orig,
            ]
        );

        $insertarLineaRectFunc = $es_simplificada_historico
            ? 'insertarItemsFacturaRectificativaSimplificadas'
            : 'insertarItemsFacturaRectificativa';
        $insertarLineaRectFunc(
            [
                'rel_id_factura' => $id_factura_generada,
                'id_rel_articulo' => $id_articulo,
                'id_rel_sucursal' => $sucursal,
                'precio_unitario' => -abs($importe),
                'cantidad' => 1,
                'total_linea' => 0,
            ]
        );

        $id_fiskaly_original = $id_factura > 0
            ? (int) ($venta['id_rel_factura_fiskaly'] ?? 0)
            : (int) ($venta['id_rel_factura_fiskaly_simplificada'] ?? 0);
        if (
            $id_fiskaly_original > 0
            && $rel_id_empresa_fact > 0
            && $id_factura_generada > 0
            && function_exists('fiskalyIntegrarFacturaRectificativaArticulos')
        ) {
            $descArt = '';
            $costeArt = 0.0;
            $tipoIvaArt = 'IVA';
            $regimenArt = 'GENERAL';
            $stmtLineaArt = mysqli_prepare(
                $conexion,
                'SELECT descripcion_articulo_rel, coste_articulo_venta, tipo_iva_articulo, system_codigo_regimen
                 FROM rel_articulos_venta
                 WHERE sku_articulo = ? AND rel_id_venta = ? AND sucursal_venta = ?
                 LIMIT 1'
            );
            if ($stmtLineaArt) {
                mysqli_stmt_bind_param($stmtLineaArt, 'iii', $id_articulo, $id_venta, $sucursal);
                mysqli_stmt_execute($stmtLineaArt);
                $resLineaArt = mysqli_stmt_get_result($stmtLineaArt);
                $rowLineaArt = $resLineaArt ? mysqli_fetch_assoc($resLineaArt) : null;
                mysqli_stmt_close($stmtLineaArt);
                if ($rowLineaArt) {
                    $descArt = (string) ($rowLineaArt['descripcion_articulo_rel'] ?? '');
                    $costeArt = (float) ($rowLineaArt['coste_articulo_venta'] ?? 0);
                    $tipoIvaArt = (string) ($rowLineaArt['tipo_iva_articulo'] ?? 'IVA');
                    $regimenArt = (string) ($rowLineaArt['system_codigo_regimen'] ?? 'GENERAL');
                }
            }
            if ($descArt === '') {
                $descArt = 'Devolución: ' . $motivo;
            }

            $fiskaly_rect_result = fiskalyIntegrarFacturaRectificativaArticulos([
                'contexto_log' => 'rectificativa_devolucion_articulo',
                'id_sucursal' => $sucursal,
                'rel_id_empresa' => $rel_id_empresa_fact,
                'id_factura_rectificativa' => $id_factura_generada,
                'id_factura_original_fiskaly' => $id_fiskaly_original,
                'simplificada_historico' => $es_simplificada_historico,
                'numero_factura' => $numero_sig,
                'facturado_por' => $usuario_id_rect,
                'tipo_pago_factura' => $forma_pago,
                'total_factura' => abs($importe),
                'prefijo_factura' => $prefijo_f,
                'rel_id_venta' => $id_venta,
                'cliente_factura' => $id_cliente_fact,
                'id_rel_articulo' => $id_articulo,
                'descripcion_articulo_rel' => $descArt,
                'precio_coste_articulo' => $costeArt,
                'tipo_iva_articulo' => $tipoIvaArt,
                'system_codigo_regimen' => $regimenArt,
            ]);
        }
    }
    $tipo_factura_rectificativa = $es_simplificada ? 'simplificada' : 'factura';
    $sql = "INSERT INTO devoluciones (
                id_venta_original,
                articulo_devolucion,
                motivo_devolucion,
                cliente_devolucion,
                sucursal_devolucion,
                importe_devolucion,
                forma_de_pago_devolucion,
                devolucion_web,
                factura_rel_id,
                tipo_factura,
                fecha_devolucion
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
    $stmt = mysqli_prepare($conexion, $sql);
    if (!$stmt) {
        throw new Exception('Error al preparar inserción: ' . mysqli_error($conexion));
    }
    mysqli_stmt_bind_param(
        $stmt,
        'iisiiissis',
        $id_venta,
        $id_articulo,
        $motivo,
        $cliente,
        $sucursal,
        $importe,
        $forma_pago,
        $devolucion_web,
        $id_factura_generada,
        $tipo_factura_rectificativa
    );

    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception('Error al insertar devolución: ' . mysqli_stmt_error($stmt));
    }
    $id_devolucion = mysqli_insert_id($conexion);
    mysqli_stmt_close($stmt);

    // Marcar artículo como devuelto en rel_articulos_venta (para esta venta/sucursal)
    $stmt_up_rel_av = mysqli_prepare(
        $conexion,
        "UPDATE rel_articulos_venta
         SET estado_rel_Articulo = 'devuelto'
         WHERE sku_articulo = ? AND rel_id_venta = ? AND sucursal_venta = ?"
    );
    if ($stmt_up_rel_av) {
        mysqli_stmt_bind_param($stmt_up_rel_av, 'iii', $id_articulo, $id_venta, $sucursal);
        mysqli_stmt_execute($stmt_up_rel_av);
        mysqli_stmt_close($stmt_up_rel_av);
    }

    if ($id_autorizacion_req > 0 && $id_devolucion > 0) {
        $stmt_rel = mysqli_prepare(
            $conexion,
            "UPDATE autorizaciones_devoluciones SET rel_id_devolucion = ?, estado_autorizacion = 'usada' WHERE id_autorizacion = ?"
        );
        if ($stmt_rel) {
            mysqli_stmt_bind_param($stmt_rel, 'ii', $id_devolucion, $id_autorizacion_req);
            mysqli_stmt_execute($stmt_rel);
            mysqli_stmt_close($stmt_rel);
        }
    }

    $id_usuario = isset($_SESSION['usuario_id']) ? (int) $_SESSION['usuario_id'] : 0;
    if (!$id_usuario) {
        $ru = mysqli_query($conexion, "SELECT id_usuario FROM usuarios LIMIT 1");
        if ($ru && $row_u = mysqli_fetch_assoc($ru)) {
            $id_usuario = (int) $row_u['id_usuario'];
        }
    }

    $numero_venta_display = isset($venta['id_venta_sucursal']) && (string) $venta['id_venta_sucursal'] !== ''
        ? (string) $venta['id_venta_sucursal']
        : (string) $id_venta;

    $stmt_art = mysqli_prepare(
        $conexion,
        'SELECT id, estado, id_sucursal_destino, id_sucursal_origen FROM articulos_venta WHERE id = ? LIMIT 1'
    );
    if ($stmt_art) {
        mysqli_stmt_bind_param($stmt_art, 'i', $id_articulo);
        mysqli_stmt_execute($stmt_art);
        $res_art = mysqli_stmt_get_result($stmt_art);
        $fila_art = $res_art ? mysqli_fetch_assoc($res_art) : null;
        mysqli_stmt_close($stmt_art);

        if ($fila_art) {
            $estado_av = strtolower((string) ($fila_art['estado'] ?? ''));
            $id_destino = (int) ($fila_art['id_sucursal_destino'] ?? 0);
            $id_origen = (int) ($fila_art['id_sucursal_origen'] ?? 0);
            $es_vendido_web = ($estado_av === 'vendido_web');

            if ($es_vendido_web) {
                $stmt_up_av = mysqli_prepare(
                    $conexion,
                    "UPDATE articulos_venta SET
                        estado = 'enventa',
                        fecha_vendido = '0000-00-00',
                        id_sucursal_origen = ?,
                        id_sucursal_destino = ?,
                        update_register = CURDATE()
                     WHERE id = ?"
                );
                if ($stmt_up_av) {
                    mysqli_stmt_bind_param($stmt_up_av, 'iii', $id_destino, $id_origen, $id_articulo);
                    mysqli_stmt_execute($stmt_up_av);
                    mysqli_stmt_close($stmt_up_av);
                }
                $stmt_rel = mysqli_prepare(
                    $conexion,
                    "UPDATE rel_articulos_estados SET
                        estado_articulo = 'Stock',
                        rel_id_sucursal_venta = ?
                     WHERE rel_id_articulo_venta = ?"
                );
                if ($stmt_rel) {
                    mysqli_stmt_bind_param($stmt_rel, 'ii', $id_origen, $id_articulo);
                    mysqli_stmt_execute($stmt_rel);
                    mysqli_stmt_close($stmt_rel);
                }
            } elseif ($estado_av === 'vendido') {
                $stmt_up_av = mysqli_prepare(
                    $conexion,
                    "UPDATE articulos_venta SET
                        estado = 'enventa',
                        fecha_vendido = '0000-00-00',
                        update_register = CURDATE()
                     WHERE id = ?"
                );
                if ($stmt_up_av) {
                    mysqli_stmt_bind_param($stmt_up_av, 'i', $id_articulo);
                    mysqli_stmt_execute($stmt_up_av);
                    mysqli_stmt_close($stmt_up_av);
                }
                $stmt_rel = mysqli_prepare(
                    $conexion,
                    "UPDATE rel_articulos_estados SET
                        estado_articulo = 'Stock',
                        rel_id_sucursal_venta = 0
                     WHERE rel_id_articulo_venta = ?"
                );
                if ($stmt_rel) {
                    mysqli_stmt_bind_param($stmt_rel, 'i', $id_articulo);
                    mysqli_stmt_execute($stmt_rel);
                    mysqli_stmt_close($stmt_rel);
                }

                
            }

            $forma_norm = strtolower(trim((string) $forma_pago));
            $grupos_caja = "Devoluciones de articulos";
            $concepto_caja = 'Artículo SKU ' . $id_articulo . ' devuelto de la venta Nº ' . $numero_venta_display." incluido en la devolucion Nº ".$id_devolucion;
            if($forma_norm == 'combinado'){

                if($cantidad_contado > 0){
                    // INSERTO EL MOVIMIENTO EN SALIDA EN CAJA NORMAL
                    insertar_movimiento_caja($grupos_caja, $concepto_caja, 0, $cantidad_contado, $id_usuario, $sucursal);
                }
                if($cantidad_tarjeta > 0){
                    // INSERTO EL MOVIMIENTO EN IMPORTE EN CAJA TARJETA
                    insertar_movimiento_tarjeta($sucursal, 0, $id_venta, $concepto_caja, 0, $id_usuario, $grupos_caja, $cantidad_tarjeta);
                }
                if($cantidad_transferencia > 0){
                    // INSERTO EL MOVIMIENTO EN SALIDA EN CAJA TRANSFERENCIA
                    insertar_movimiento_transferencia($sucursal, 0, $id_venta, $concepto_caja, 0, $cantidad_transferencia, $id_usuario, $grupos_caja);
                }
                if($cantidad_bizum > 0){
                    // INSERTO EL MOVIMIENTO EN IMPORTE EN CAJA BIZUM
                    insertar_movimiento_bizum($sucursal, 0, $id_venta, $concepto_caja, 0, $id_usuario, $grupos_caja, $cantidad_bizum);
                }

            }elseif($forma_norm == 'bizum'){
                // INSERTO EL MOVIMIENTO EN IMPORTE EN CAJA BIZUM
                insertar_movimiento_bizum($sucursal, 0, $id_venta, $concepto_caja, 0, $id_usuario, $grupos_caja, $cantidad_bizum);
            }elseif($forma_norm == 'contado'){
                // INSERTO EL MOVIMIENTO EN SALIDA EN CAJA NORMAL
                insertar_movimiento_caja($grupos_caja, $concepto_caja, 0, $cantidad_contado, $id_usuario, $sucursal);
            }elseif($forma_norm == 'tarjeta'){
                // INSERTO EL MOVIMIENTO EN IMPORTE EN CAJA TARJETA
                insertar_movimiento_tarjeta($sucursal, 0, $id_venta, $concepto_caja, 0, $id_usuario, $grupos_caja, $cantidad_tarjeta);
            }elseif($forma_norm == 'transferencia'){
                    // INSERTO EL MOVIMIENTO EN SALIDA EN CAJA TRANSFERENCIA
                    insertar_movimiento_transferencia($sucursal, 0, $id_venta, $concepto_caja, 0, $cantidad_transferencia, $id_usuario, $grupos_caja);
            }else{
                // INSERTO EL MOVIMIENTO EN SALIDA EN CAJA NORMAL
            }

            $comentarios_traz = $concepto_caja;
            try {
                trazabilidad_articulos_venta(
                    0,
                    (string) $id_usuario,
                    'devuelto',
                    $comentarios_traz,
                    $id_destino > 0 ? $id_destino : $sucursal,
                    $id_articulo,
                    0
                );
            } catch (Throwable $e_tr) {
                error_log('trazabilidad devolución: ' . $e_tr->getMessage());
            }
        }
    }
    if ($id_factura_generada > 0) {
        $tipo_pdf_rect = $es_simplificada_historico
            ? 'factura_rectificativa_simplificada'
            : 'factura_rectificativa';
        try {
            generarPdfFactura($id_factura_generada, $tipo_pdf_rect, 'sucursal', $sucursal);
        } catch (Throwable $ePdf) {
            error_log('PDF rectificativa devolución: ' . $ePdf->getMessage());
        }
    }
    mysqli_close($conexion);

    $msgOk = 'Devolución creada correctamente.';
    if (is_array($fiskaly_rect_result)) {
        if (!empty($fiskaly_rect_result['success'])) {
            $msgOk .= ' Rectificativa enviada a Fiskaly.';
        } elseif (!empty($fiskaly_rect_result['message'])) {
            $msgOk .= ' (Fiskaly NO enviada: ' . $fiskaly_rect_result['message'] . ')';
        } else {
            $msgOk .= ' (Fiskaly NO enviada)';
        }
    }

    echo json_encode([
        'success' => true,
        'message' => $msgOk,
        'id_devolucion' => $id_devolucion,
        'estado_articulo' => 'enventa',
        'fiskaly' => $fiskaly_rect_result,
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

