<?php
/**
 * Genera factura (completa o simplificada) al cerrar una venta a plazos.
 * Misma lógica de régimen/Fiskaly que insertar_venta.php.
 */

if (!function_exists('venta_rel_tipo_iva_valido')) {
    function venta_rel_tipo_iva_valido($v)
    {
        $u = strtoupper(trim((string) $v));
        $ok = ['IVA', 'IPSI', 'IGIC', 'OTHER'];

        return in_array($u, $ok, true) ? $u : 'IVA';
    }
}

if (!function_exists('venta_rel_regimen_valido')) {
    function venta_rel_regimen_valido($v)
    {
        $u = strtoupper(preg_replace('/\s+/', '', (string) $v));
        if ($u === 'REBU') {
            return 'REBU';
        }
        if ($u === 'INVERSION') {
            return 'INVERSION';
        }

        return 'GENERAL';
    }
}

function gfv_lineas_factura_desde_venta(mysqli $conexion, int $id_venta, int $id_sucursal): array
{
    $filasItems = [];
    $stmtLines = mysqli_prepare(
        $conexion,
        'SELECT sku_articulo, descripcion_articulo_rel, precio_venta,
                coste_articulo_venta, tipo_iva_articulo, system_codigo_regimen
         FROM rel_articulos_venta
         WHERE rel_id_venta = ? AND sucursal_venta = ?'
    );
    if (!$stmtLines) {
        return $filasItems;
    }
    mysqli_stmt_bind_param($stmtLines, 'ii', $id_venta, $id_sucursal);
    mysqli_stmt_execute($stmtLines);
    $resLines = mysqli_stmt_get_result($stmtLines);
    while ($rowL = mysqli_fetch_assoc($resLines)) {
        $p = (float) ($rowL['precio_venta'] ?? 0);
        $filasItems[] = [
            'id_rel_sucursal' => $id_sucursal,
            'rel_id_item' => (int) ($rowL['sku_articulo'] ?? 0),
            'descripcion_articulo_rel' => (string) ($rowL['descripcion_articulo_rel'] ?? ''),
            'precio_unitario' => $p,
            'precio_coste_articulo' => (float) ($rowL['coste_articulo_venta'] ?? 0),
            'tipo_iva_articulo' => (string) ($rowL['tipo_iva_articulo'] ?? 'IVA'),
            'system_codigo_regimen' => venta_rel_regimen_valido((string) ($rowL['system_codigo_regimen'] ?? '')),
            'cantidad' => 1,
            'total_linea' => $p,
        ];
    }
    mysqli_stmt_close($stmtLines);

    return $filasItems;
}

function gfv_tipo_factura_items_desde_venta(mysqli $conexion, int $id_venta, int $id_sucursal): string
{
    $tipo_factura_items = 'articulos';
    $stmtLines = mysqli_prepare(
        $conexion,
        'SELECT system_codigo_regimen FROM rel_articulos_venta WHERE rel_id_venta = ? AND sucursal_venta = ?'
    );
    if (!$stmtLines) {
        return $tipo_factura_items;
    }
    mysqli_stmt_bind_param($stmtLines, 'ii', $id_venta, $id_sucursal);
    mysqli_stmt_execute($stmtLines);
    $resLines = mysqli_stmt_get_result($stmtLines);
    while ($rowL = mysqli_fetch_assoc($resLines)) {
        $regimen = venta_rel_regimen_valido((string) ($rowL['system_codigo_regimen'] ?? 'GENERAL'));
        if ($regimen === 'REBU') {
            $tipo_factura_items = 'articulos';
        } elseif ($regimen === 'INVERSION') {
            $tipo_factura_items = 'oro_inversion';
        } else {
            $tipo_factura_items = 'articulos';
        }
    }
    mysqli_stmt_close($stmtLines);

    return $tipo_factura_items;
}

function gfv_contexto_fiskaly(int $id_sucursal, int $rel_id_empresa, string $contexto_log): array
{
    $fiskaly_eval = fiskalyEvaluarSucursalEmpresa($id_sucursal, $rel_id_empresa);
    if (!$fiskaly_eval['activo']) {
        insertErrorLog(
            $contexto_log . ': Fiskaly omitido (sucursal ' . $id_sucursal . ', empresa ' . $rel_id_empresa . ', tipo_api ' . ($fiskaly_eval['tipo_api'] !== '' ? $fiskaly_eval['tipo_api'] : 'n/a') . '): ' . $fiskaly_eval['motivo']
        );
    }
    $regimen_empresa = $fiskaly_eval['regimen'] !== ''
        ? $fiskaly_eval['regimen']
        : obtenerRegimenEmpresa($rel_id_empresa);
    if (!in_array($regimen_empresa, ['false', 'General', 'Verifactu', 'TicketBAIBizkaia', 'TicketBAIAlava', 'TicketBAIGipuzkua'], true)) {
        $regimen_empresa = 'General';
    }

    return [
        'regimen' => $regimen_empresa,
        'generar_fiskaly' => !empty($fiskaly_eval['activo']),
    ];
}

function gfv_generar_factura_completa(
    mysqli $conexion,
    int $id_venta,
    int $id_sucursal,
    int $id_cliente,
    float $total_venta,
    string $tipo_pago,
    int $usuario_id,
    string $tipo_factura_items = 'articulos'
): int {
    $stmtPref = mysqli_prepare(
        $conexion,
        'SELECT empresa_id, TRIM(COALESCE(inicio_facturas, "")) AS pref FROM sucursal WHERE id_sucursal = ? LIMIT 1'
    );
    if (!$stmtPref) {
        throw new Exception(mysqli_error($conexion));
    }
    mysqli_stmt_bind_param($stmtPref, 'i', $id_sucursal);
    mysqli_stmt_execute($stmtPref);
    $rp = mysqli_stmt_get_result($stmtPref);
    $rowP = $rp ? mysqli_fetch_assoc($rp) : null;
    mysqli_stmt_close($stmtPref);

    $rel_id_empresa_fact = $rowP ? (int) ($rowP['empresa_id'] ?? 0) : 0;
    $prefijo_f = facturaConstruirPrefijo($id_sucursal, false, $tipo_factura_items);
    $ctxFiskaly = gfv_contexto_fiskaly($id_sucursal, $rel_id_empresa_fact, 'venta_plazos factura completa');
    $numero_sig = (int) obtenerNumeroFactura($id_sucursal, $tipo_factura_items);

    $id_factura = crearFactura([
        'id_sucursal' => $id_sucursal,
        'numero_factura' => $numero_sig,
        'cliente_factura' => $id_cliente,
        'facturado_por' => $usuario_id,
        'estado_factura' => 'pagada',
        'tipo_pago_factura' => $tipo_pago,
        'total_factura' => $total_venta,
        'rel_id_venta' => $id_venta,
        'prefijo_factura' => $prefijo_f,
        'tipo_factura' => $tipo_factura_items,
        'rel_id_empresa' => $rel_id_empresa_fact,
        'factura_regimen' => $ctxFiskaly['regimen'],
    ]);

    if ($id_factura <= 0) {
        throw new Exception('No se pudo crear la factura');
    }

    $filasItems = gfv_lineas_factura_desde_venta($conexion, $id_venta, $id_sucursal);
    foreach ($filasItems as &$fi) {
        $fi['rel_id_factura'] = $id_factura;
    }
    unset($fi);
    if (count($filasItems) > 0) {
        insertarItemsFactura($filasItems);
    }

    if ($ctxFiskaly['generar_fiskaly']) {
        try {
            $id_factura_fiskaly = crearFacturaFiskaly([
                'id_sucursal' => $id_sucursal,
                'numero_factura' => $numero_sig,
                'cliente_factura' => $id_cliente,
                'facturado_por' => $usuario_id,
                'estado_factura' => 'pagada',
                'tipo_pago_factura' => $tipo_pago,
                'total_factura' => $total_venta,
                'rel_id_venta' => $id_venta,
                'prefijo_factura' => $prefijo_f,
                'tipo_factura' => $tipo_factura_items,
                'rel_id_empresa' => $rel_id_empresa_fact,
                'factura_regimen' => $ctxFiskaly['regimen'],
            ]);

            fiskalyVincularFacturaTpv($id_factura, $id_factura_fiskaly);

            $filasItemsFiskaly = gfv_lineas_factura_desde_venta($conexion, $id_venta, $id_sucursal);
            foreach ($filasItemsFiskaly as &$fiF) {
                $fiF['rel_factura_id_fiskaly'] = $id_factura_fiskaly;
                $fiF['rel_id_factura'] = $id_factura;
                $fiF['rel_id_empresa'] = $rel_id_empresa_fact;
            }
            unset($fiF);
            if (count($filasItemsFiskaly) > 0) {
                insertarItemsFacturaFiskaly($filasItemsFiskaly);
            }

            try {
                enviarFacturaFiskaly($id_factura_fiskaly, $rel_id_empresa_fact, $id_sucursal);
            } catch (Throwable $exFiskaly) {
                insertErrorLog('venta_plazos: envío Fiskaly no completado: ' . $exFiskaly->getMessage());
            }
        } catch (Throwable $exFiskalyCache) {
            insertErrorLog('venta_plazos: factura Fiskaly no creada: ' . $exFiskalyCache->getMessage());
        }
    }

    try {
        generarPdfFactura($id_factura, 'factura', 'sucursal', $id_sucursal);
    } catch (Throwable $exPdf) {
        insertErrorLog('venta_plazos: PDF factura completa no generado: ' . $exPdf->getMessage());
    }

    return $id_factura;
}

function gfv_generar_factura_simplificada(
    mysqli $conexion,
    int $id_venta,
    int $id_sucursal,
    float $total_venta,
    string $tipo_pago,
    int $usuario_id,
    string $tipo_factura_items = 'articulos'
): int {
    $stmtPref = mysqli_prepare(
        $conexion,
        'SELECT empresa_id, TRIM(COALESCE(prefijo_factura_simplificada, "")) AS pref FROM sucursal WHERE id_sucursal = ? LIMIT 1'
    );
    if (!$stmtPref) {
        throw new Exception(mysqli_error($conexion));
    }
    mysqli_stmt_bind_param($stmtPref, 'i', $id_sucursal);
    mysqli_stmt_execute($stmtPref);
    $rp = mysqli_stmt_get_result($stmtPref);
    $rowP = $rp ? mysqli_fetch_assoc($rp) : null;
    mysqli_stmt_close($stmtPref);

    $rel_id_empresa_fact = $rowP ? (int) ($rowP['empresa_id'] ?? 0) : 0;
    $prefijo_f = facturaConstruirPrefijo($id_sucursal, true, $tipo_factura_items);
    $ctxFiskaly = gfv_contexto_fiskaly($id_sucursal, $rel_id_empresa_fact, 'venta_plazos factura simplificada');
    $numero_sig = (int) obtenerNumeroFactura($id_sucursal, $tipo_factura_items);

    $id_factura = crearFacturaSimplificada([
        'id_sucursal' => $id_sucursal,
        'numero_factura' => $numero_sig,
        'facturado_por' => $usuario_id,
        'estado_factura' => 'pagada',
        'tipo_pago_factura' => $tipo_pago,
        'total_factura' => $total_venta,
        'rel_id_venta' => $id_venta,
        'prefijo_factura' => $prefijo_f,
        'tipo_factura' => $tipo_factura_items,
        'rel_id_empresa' => $rel_id_empresa_fact,
        'factura_regimen' => $ctxFiskaly['regimen'],
    ]);

    if ($id_factura <= 0) {
        throw new Exception('No se pudo crear la factura simplificada');
    }

    $filasItems = gfv_lineas_factura_desde_venta($conexion, $id_venta, $id_sucursal);
    foreach ($filasItems as &$fi) {
        $fi['rel_id_factura'] = $id_factura;
    }
    unset($fi);
    if (count($filasItems) > 0) {
        insertarItemsFacturaSimplificada($filasItems);
    }

    if ($ctxFiskaly['generar_fiskaly']) {
        try {
            $id_factura_fiskaly = crearFacturaFiskalySimplificada([
                'id_sucursal' => $id_sucursal,
                'numero_factura' => $numero_sig,
                'facturado_por' => $usuario_id,
                'estado_factura' => 'pagada',
                'tipo_pago_factura' => $tipo_pago,
                'total_factura' => $total_venta,
                'rel_id_venta' => $id_venta,
                'prefijo_factura' => $prefijo_f,
                'tipo_factura' => $tipo_factura_items,
                'rel_id_empresa' => $rel_id_empresa_fact,
                'factura_regimen' => $ctxFiskaly['regimen'],
            ]);

            fiskalyVincularFacturaSimplificadaTpv($id_factura, $id_factura_fiskaly);

            $filasItemsFiskaly = gfv_lineas_factura_desde_venta($conexion, $id_venta, $id_sucursal);
            foreach ($filasItemsFiskaly as &$fiF) {
                $fiF['rel_factura_id_fiskaly'] = $id_factura_fiskaly;
                $fiF['rel_id_factura'] = $id_factura;
                $fiF['rel_id_empresa'] = $rel_id_empresa_fact;
            }
            unset($fiF);
            if (count($filasItemsFiskaly) > 0) {
                insertarItemsFacturaFiskaly($filasItemsFiskaly);
            }

            try {
                enviarFacturaFiskaly($id_factura_fiskaly, $rel_id_empresa_fact, $id_sucursal);
            } catch (Throwable $exFiskaly) {
                insertErrorLog('venta_plazos: envío Fiskaly simplificada no completado: ' . $exFiskaly->getMessage());
            }
        } catch (Throwable $exFiskalyCache) {
            insertErrorLog('venta_plazos: factura Fiskaly simplificada no creada: ' . $exFiskalyCache->getMessage());
        }
    }

    try {
        generarPdfFactura($id_factura, 'factura', 'sucursal', $id_sucursal);
    } catch (Throwable $exPdf) {
        insertErrorLog('venta_plazos: PDF factura simplificada no generado: ' . $exPdf->getMessage());
    }

    return $id_factura;
}

/**
 * Genera factura automática al cerrar venta a plazos (misma regla que insertar_venta).
 *
 * @return array{generada: bool, tipo_factura: string, id_factura: int, id_factura_simplificada: int}
 */
function venta_plazos_generar_factura_automatica(
    mysqli $conexion,
    int $id_venta,
    int $id_sucursal,
    int $usuario_id
): array {
    $resultado = [
        'generada' => false,
        'tipo_factura' => '',
        'id_factura' => 0,
        'id_factura_simplificada' => 0,
    ];

    if ($usuario_id <= 0 || venta_plazos_tiene_factura_generada($conexion, $id_venta, $id_sucursal)) {
        return $resultado;
    }

    $stmtV = mysqli_prepare(
        $conexion,
        'SELECT id, precio, venta_plazos, estado, id_sucursal, cliente, tipo_pago FROM ventas WHERE id = ? LIMIT 1'
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
        return $resultado;
    }
    if (strtolower((string) ($venta['estado'] ?? '')) !== 'vendido') {
        return $resultado;
    }

    $precio = (float) ($venta['precio'] ?? 0);
    if ($precio <= 0) {
        return $resultado;
    }

    $id_cliente = (int) ($venta['cliente'] ?? 0);
    $tipo_pago = trim((string) ($venta['tipo_pago'] ?? ''));
    if ($tipo_pago === '') {
        $tipo_pago = 'contado';
    }
    $tipo_factura_items = gfv_tipo_factura_items_desde_venta($conexion, $id_venta, $id_sucursal);
    $max_simplificada = obtenerMaximoTotalFacturaSimplificada();

    if ($precio > $max_simplificada) {
        if ($id_cliente <= 0) {
            throw new Exception('La factura completa requiere cliente en la venta');
        }
        $resultado['tipo_factura'] = 'completa';
        $resultado['id_factura'] = gfv_generar_factura_completa(
            $conexion,
            $id_venta,
            $id_sucursal,
            $id_cliente,
            $precio,
            $tipo_pago,
            $usuario_id,
            $tipo_factura_items
        );
        $resultado['generada'] = $resultado['id_factura'] > 0;
    } else {
        $resultado['tipo_factura'] = 'simplificada';
        $resultado['id_factura_simplificada'] = gfv_generar_factura_simplificada(
            $conexion,
            $id_venta,
            $id_sucursal,
            $precio,
            $tipo_pago,
            $usuario_id,
            $tipo_factura_items
        );
        $resultado['generada'] = $resultado['id_factura_simplificada'] > 0;
    }

    return $resultado;
}

/**
 * Marca artículos reservado → vendido al cerrar/reparar una venta a plazos.
 *
 * @return int Número de artículos actualizados en articulos_venta
 */
function venta_plazos_marcar_articulos_vendidos(
    mysqli $conexion,
    int $id_venta,
    int $id_sucursal,
    int $id_venta_sucursal,
    string $nombre_sucursal,
    int $usuario_id,
    string $comentario_trazabilidad = 'Venta a plazos completada'
): int {
    $stmtVenta = mysqli_prepare(
        $conexion,
        'SELECT venta_plazos, numero_plazos, tipo_pago,
                cantidad_contado, cantidad_tarjeta, cantidad_transferencia, cantidad_bizum,
                comprado_por, DATE(fecha) AS fecha_venta
         FROM ventas WHERE id = ? LIMIT 1'
    );
    if (!$stmtVenta) {
        throw new Exception(mysqli_error($conexion));
    }
    mysqli_stmt_bind_param($stmtVenta, 'i', $id_venta);
    mysqli_stmt_execute($stmtVenta);
    $resVenta = mysqli_stmt_get_result($stmtVenta);
    $ventaCab = $resVenta ? mysqli_fetch_assoc($resVenta) : null;
    mysqli_stmt_close($stmtVenta);

    $venta_plazos_db = (string) ($ventaCab['venta_plazos'] ?? 'si');
    $numero_plazos_db = (int) ($ventaCab['numero_plazos'] ?? 0);
    $tipo_pago_db = (string) ($ventaCab['tipo_pago'] ?? 'contado');
    $cant_contado = (float) ($ventaCab['cantidad_contado'] ?? 0);
    $cant_tarjeta = (float) ($ventaCab['cantidad_tarjeta'] ?? 0);
    $cant_transferencia = (float) ($ventaCab['cantidad_transferencia'] ?? 0);
    $cant_bizum = (float) ($ventaCab['cantidad_bizum'] ?? 0);
    $usuario_reporte = (int) ($ventaCab['comprado_por'] ?? 0);
    if ($usuario_reporte <= 0) {
        $usuario_reporte = (int) $usuario_id;
    }
    $fecha_venta_reporte = (string) ($ventaCab['fecha_venta'] ?? date('Y-m-d'));
    if ($fecha_venta_reporte === '' || $fecha_venta_reporte === '0000-00-00') {
        $fecha_venta_reporte = date('Y-m-d');
    }

    $stmtRel = mysqli_prepare(
        $conexion,
        'SELECT r.sku_articulo, r.precio_venta, r.descripcion_articulo_rel, r.coste_articulo_venta, r.venta_web,
                av.peso, av.articulo_web, av.tipo, av.descripcion AS descripcion_av
         FROM rel_articulos_venta r
         LEFT JOIN articulos_venta av ON av.id = r.sku_articulo
         WHERE r.rel_id_venta = ? AND r.sucursal_venta = ?'
    );
    if (!$stmtRel) {
        throw new Exception(mysqli_error($conexion));
    }
    mysqli_stmt_bind_param($stmtRel, 'ii', $id_venta, $id_sucursal);
    mysqli_stmt_execute($stmtRel);
    $resRel = mysqli_stmt_get_result($stmtRel);
    if (!$resRel) {
        mysqli_stmt_close($stmtRel);
        throw new Exception(mysqli_error($conexion));
    }

    $numero_semana = numeroSemanaActual();
    if ($numero_semana === null) {
        $numero_semana = 0;
    }
    $yearPack = numeroSemanaActualConAnyo();
    $year_rel = (is_array($yearPack) && isset($yearPack['anyo_listado']))
        ? (int) $yearPack['anyo_listado']
        : (int) date('Y');

    $estado_articulo_final = 'vendido';
    $uid = (int) $usuario_id;
    $actualizados = 0;

    while ($linea = mysqli_fetch_assoc($resRel)) {
        $id_art = (int) ($linea['sku_articulo'] ?? 0);
        $precio_linea = (float) ($linea['precio_venta'] ?? 0);
        if ($id_art <= 0) {
            continue;
        }

        $stmtArt = mysqli_prepare(
            $conexion,
            "UPDATE articulos_venta SET
                estado = 'vendido',
                fecha_vendido = CURDATE(),
                hora_vendido = CURTIME(),
                precio = ?,
                last_id_venta = ?,
                id_venta_sucursal = ?,
                nombre_sucursal_venta = ?,
                update_register = CURDATE()
             WHERE id = ? AND id_sucursal_destino = ? AND estado = 'reservado'"
        );
        if (!$stmtArt) {
            mysqli_stmt_close($stmtRel);
            throw new Exception(mysqli_error($conexion));
        }
        mysqli_stmt_bind_param(
            $stmtArt,
            'diisii',
            $precio_linea,
            $id_venta,
            $id_venta_sucursal,
            $nombre_sucursal,
            $id_art,
            $id_sucursal
        );
        if (!mysqli_stmt_execute($stmtArt)) {
            mysqli_stmt_close($stmtArt);
            mysqli_stmt_close($stmtRel);
            throw new Exception(mysqli_stmt_error($stmtArt));
        }
        $af = mysqli_stmt_affected_rows($stmtArt);
        mysqli_stmt_close($stmtArt);
        if ($af > 0) {
            $actualizados++;
        }

        $stmtRest = mysqli_prepare(
            $conexion,
            "UPDATE rel_articulos_estados SET
                estado_articulo = ?,
                rel_id_sucursal_venta = ?,
                precio_venta = ?,
                fecha_venta = CURDATE(),
                rel_id_venta = ?,
                rel_numero_semana_venta = ?,
                year_rel = ?
             WHERE rel_id_articulo_venta = ?"
        );
        if (!$stmtRest) {
            mysqli_stmt_close($stmtRel);
            throw new Exception(mysqli_error($conexion));
        }
        mysqli_stmt_bind_param(
            $stmtRest,
            'sidiiii',
            $estado_articulo_final,
            $id_sucursal,
            $precio_linea,
            $id_venta,
            $numero_semana,
            $year_rel,
            $id_art
        );
        if (!mysqli_stmt_execute($stmtRest)) {
            mysqli_stmt_close($stmtRest);
            mysqli_stmt_close($stmtRel);
            throw new Exception(mysqli_stmt_error($stmtRest));
        }
        mysqli_stmt_close($stmtRest);

        if ($uid > 0 && function_exists('trazabilidad_articulos_venta')) {
            try {
                trazabilidad_articulos_venta(
                    $id_venta,
                    $uid,
                    $estado_articulo_final,
                    $comentario_trazabilidad,
                    $id_sucursal,
                    $id_art,
                    $id_venta_sucursal
                );
            } catch (Throwable $e) {
                insertErrorLog('trazabilidad_articulos_venta venta_plazos_marcar_vendidos: ' . $e->getMessage());
            }
        }

        if (function_exists('insert_reporte_ventas')) {
            try {
                $stmtExiste = mysqli_prepare(
                    $conexion,
                    'SELECT id_reporte_ventas FROM reporte_ventas
                     WHERE id_articulo = ? AND identificador_venta = ? LIMIT 1'
                );
                $yaExiste = false;
                if ($stmtExiste) {
                    mysqli_stmt_bind_param($stmtExiste, 'ii', $id_art, $id_venta);
                    mysqli_stmt_execute($stmtExiste);
                    $resEx = mysqli_stmt_get_result($stmtExiste);
                    $yaExiste = ($resEx && mysqli_fetch_assoc($resEx)) ? true : false;
                    if ($resEx) {
                        mysqli_free_result($resEx);
                    }
                    mysqli_stmt_close($stmtExiste);
                }

                if (!$yaExiste) {
                    $desc_rel = trim((string) ($linea['descripcion_articulo_rel'] ?? ''));
                    if ($desc_rel === '') {
                        $desc_rel = trim((string) ($linea['descripcion_av'] ?? ''));
                    }
                    if ($desc_rel === '') {
                        $desc_rel = 'Artículo #' . $id_art;
                    }
                    $coste = (float) ($linea['coste_articulo_venta'] ?? 0);
                    $peso = is_numeric($linea['peso'] ?? null) ? (float) $linea['peso'] : 0.0;
                    $articulo_web = (string) ($linea['articulo_web'] ?? '');
                    if ($articulo_web === '') {
                        $articulo_web = ((string) ($linea['venta_web'] ?? 'false') === 'true') ? 'true' : 'false';
                    }
                    $tipo_metal = (string) ($linea['tipo'] ?? 'oro');

                    insert_reporte_ventas(
                        $id_art,
                        $id_sucursal,
                        $desc_rel,
                        $id_venta_sucursal,
                        $id_venta,
                        $precio_linea,
                        $peso,
                        $articulo_web,
                        $tipo_metal,
                        $venta_plazos_db,
                        $numero_plazos_db,
                        $tipo_pago_db,
                        $cant_contado,
                        $cant_tarjeta,
                        $cant_transferencia,
                        $cant_bizum,
                        $usuario_reporte,
                        $fecha_venta_reporte,
                        $coste
                    );
                }
            } catch (Throwable $e) {
                insertErrorLog('insert_reporte_ventas venta_plazos_marcar_vendidos: ' . $e->getMessage());
            }
        }
    }
    mysqli_stmt_close($stmtRel);

    return $actualizados;
}
