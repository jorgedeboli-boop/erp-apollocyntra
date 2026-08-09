<?php
/**
 * Recalcula bloques de informe_diario desde tablas origen (misma lógica que CRON/informe_diario.php).
 *
 * Uso:
 *   actualizarInformeDiario('venta', '2026-06-15', 3);
 *   actualizarInformeDiario('todos', '2026-06-15', 3);
 *   sincronizarInformeDiarioLote([...]); // desde edición/cambio de estado de lote
 *
 * Si no existe fila para fecha+sucursal, no crea informe (devuelve false).
 */

if (!function_exists('sincronizarInformeDiarioLote')) {
    /**
     * Actualiza informe_diario afectado por cambios en un lote.
     * Recalcula la fecha de compra (y fechas de estado asociadas) cuando la edición
     * es posterior al día de compra (hoy > fecha_compra) o cuando ya existe informe.
     *
     * Claves esperadas en $lote:
     * - id_sucursal (int)
     * - fecha_compra (Y-m-d)
     * - tipo_de_lote / tipo_de_lote_anterior (oro|plata)
     * - compra_opcion / compra_opcion_anterior (si|no)
     * - estado_lote / estado_lote_anterior
     * - fecha_vencimiento, fecha_perdido, fecha_retirado, fecha_intervenido (opc)
     * - sync_caja_hoy, sync_transferencia_hoy (bool)
     *
     * @param array<string, mixed> $lote
     * @return bool true si se intentó al menos un recalculo
     */
    function sincronizarInformeDiarioLote(array $lote)
    {
        if (!function_exists('actualizarInformeDiario')) {
            return false;
        }

        $idSucursal = isset($lote['id_sucursal']) ? (int) $lote['id_sucursal'] : 0;
        if ($idSucursal <= 0) {
            return false;
        }

        $hoy = date('Y-m-d');
        $fechaCompra = aid_normalizar_fecha_informe(isset($lote['fecha_compra']) ? $lote['fecha_compra'] : '');
        if ($fechaCompra === '') {
            return false;
        }

        $tipoActual = strtolower(trim((string) (isset($lote['tipo_de_lote']) ? $lote['tipo_de_lote'] : 'oro')));
        $tipoAnterior = strtolower(trim((string) (isset($lote['tipo_de_lote_anterior']) ? $lote['tipo_de_lote_anterior'] : $tipoActual)));
        if ($tipoActual !== 'plata') {
            $tipoActual = 'oro';
        }
        if ($tipoAnterior !== 'plata') {
            $tipoAnterior = 'oro';
        }

        $compraActual = strtolower(trim((string) (isset($lote['compra_opcion']) ? $lote['compra_opcion'] : 'no')));
        $compraAnterior = strtolower(trim((string) (isset($lote['compra_opcion_anterior']) ? $lote['compra_opcion_anterior'] : $compraActual)));
        $compraActual = ($compraActual === 'si') ? 'si' : 'no';
        $compraAnterior = ($compraAnterior === 'si') ? 'si' : 'no';

        $estadoActual = strtolower(trim((string) (isset($lote['estado_lote']) ? $lote['estado_lote'] : '')));
        $estadoAnterior = strtolower(trim((string) (isset($lote['estado_lote_anterior']) ? $lote['estado_lote_anterior'] : $estadoActual)));

        $metales = array_unique(array($tipoActual, $tipoAnterior));
        $intentado = false;

        // Compras / empeños del día de compra (aunque el cron ya haya corrido)
        $esCompra = ($compraActual === 'no' || $estadoActual === 'compra' || $compraAnterior === 'no' || $estadoAnterior === 'compra');
        $esEmpeno = ($compraActual === 'si' || $compraAnterior === 'si');

        if ($esCompra) {
            foreach ($metales as $metal) {
                actualizarInformeDiario(($metal === 'plata') ? 'compras_plata' : 'compras_oro', $fechaCompra, $idSucursal);
                $intentado = true;
            }
        }

        if ($esEmpeno) {
            actualizarInformeDiario('empenos_global', $fechaCompra, $idSucursal);
            foreach ($metales as $metal) {
                actualizarInformeDiario(($metal === 'plata') ? 'empenos_plata' : 'empenos_oro', $fechaCompra, $idSucursal);
            }
            $intentado = true;
        }

        // Estados con fecha propia
        $mapaEstadoFecha = array(
            'vencido' => array(
                'tipo' => 'empenos_vencidos',
                'fecha' => aid_normalizar_fecha_informe(isset($lote['fecha_vencimiento']) ? $lote['fecha_vencimiento'] : ''),
            ),
            'perdido' => array(
                'tipo' => 'empenos_perdidos',
                'fecha' => aid_normalizar_fecha_informe(isset($lote['fecha_perdido']) ? $lote['fecha_perdido'] : ''),
            ),
            'retirado' => array(
                'tipo' => 'empenos_retirados',
                'fecha' => aid_normalizar_fecha_informe(isset($lote['fecha_retirado']) ? $lote['fecha_retirado'] : ''),
            ),
            'intervenido' => array(
                'tipo' => 'intervenidos',
                'fecha' => aid_normalizar_fecha_informe(isset($lote['fecha_intervenido']) ? $lote['fecha_intervenido'] : ''),
            ),
        );

        foreach (array($estadoAnterior, $estadoActual) as $est) {
            if (!isset($mapaEstadoFecha[$est])) {
                continue;
            }
            $fechaEst = $mapaEstadoFecha[$est]['fecha'];
            if ($fechaEst === '') {
                $fechaEst = $hoy;
            }
            actualizarInformeDiario($mapaEstadoFecha[$est]['tipo'], $fechaEst, $idSucursal);
            $intentado = true;
        }

        if (!empty($lote['sync_caja_hoy'])) {
            actualizarInformeDiario('caja', $hoy, $idSucursal);
            $intentado = true;
        }
        if (!empty($lote['sync_transferencia_hoy'])) {
            actualizarInformeDiario('operaciones_transferencia', $hoy, $idSucursal);
            $intentado = true;
        }

        return $intentado;
    }
}

if (!function_exists('aid_normalizar_fecha_informe')) {
    function aid_normalizar_fecha_informe($fecha)
    {
        $fecha = trim((string) $fecha);
        if ($fecha === '' || $fecha === '0000-00-00' || strpos($fecha, '0000-00-00') === 0) {
            return '';
        }
        if (preg_match('/^(\d{4}-\d{2}-\d{2})/', $fecha, $m)) {
            return $m[1];
        }
        $ts = strtotime($fecha);
        if ($ts === false) {
            return '';
        }

        return date('Y-m-d', $ts);
    }
}

if (!function_exists('actualizarInformeDiario')) {
    function actualizarInformeDiario($tipo_dato, $fecha_dato, $sucursal_dato)
    {
        $fecha_dato = trim((string) $fecha_dato);
        $sucursal_dato = (int) $sucursal_dato;
        $tipo = strtolower(trim((string) $tipo_dato));
        $tipo = str_replace([' ', '-'], '_', $tipo);

        $aliases = array(
            'ventas' => 'venta',
            'venta_a_plazos' => 'venta_plazos',
            'ventas_plazos' => 'venta_plazos',
            'compras' => 'compras_oro',
            'compra_oro' => 'compras_oro',
            'compra_plata' => 'compras_plata',
            'empenos' => 'empenos_global',
            'empenyos' => 'empenos_global',
            'empenyos_global' => 'empenos_global',
            'empenos_oro' => 'empenos_oro',
            'empenyos_oro' => 'empenos_oro',
            'empenos_plata' => 'empenos_plata',
            'empenyos_plata' => 'empenos_plata',
            'empenos_retirados' => 'empenos_retirados',
            'empenyos_retirados' => 'empenos_retirados',
            'empenos_vencidos' => 'empenos_vencidos',
            'empenyos_vencidos' => 'empenos_vencidos',
            'empenos_perdidos' => 'empenos_perdidos',
            'empenyos_perdidos' => 'empenos_perdidos',
            'empenos_renovaciones' => 'empenos_renovaciones',
            'empenyos_renovaciones' => 'empenos_renovaciones',
            'renovaciones' => 'empenos_renovaciones',
            'caja' => 'caja',
            'cajaoperaciones_tarjetas' => 'operaciones_tarjeta',
            'operaciones_tarjetas' => 'operaciones_tarjeta',
            'operaciones_transferencias' => 'operaciones_transferencia',
            'operaciones_trasnferencia' => 'operaciones_transferencia',
            'forma_pago' => 'ventas_forma_pago',
            'ventas_forma_de_pago' => 'ventas_forma_pago',
            'lotes_intervenidos' => 'intervenidos',
            'contratos_intervenidos' => 'intervenidos',
            'ajustes' => 'ajustes_lotes',
            'ajustes_de_lotes' => 'ajustes_lotes',
            'stock' => 'stock_valorizado',
            'all' => 'todos',
        );
        if (isset($aliases[$tipo])) {
            $tipo = $aliases[$tipo];
        }

        if ($sucursal_dato <= 0 || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_dato)) {
            return false;
        }

        $conexion = conectar_bd();
        if (!$conexion) {
            return false;
        }

        $idInforme = aid_obtener_id_informe($conexion, $fecha_dato, $sucursal_dato);
        if ($idInforme <= 0) {
            mysqli_close($conexion);
            return false;
        }

        $ok = true;
        if ($tipo === 'todos') {
            $bloques = array(
                'compras_oro', 'compras_plata', 'empenos_global', 'empenos_oro', 'empenos_plata',
                'empenos_retirados', 'empenos_vencidos', 'empenos_perdidos', 'empenos_renovaciones',
                'intervenidos', 'caja', 'ajustes_lotes', 'operaciones_tarjeta', 'operaciones_transferencia',
                'operaciones_bizum', 'stock_valorizado', 'venta', 'venta_plazos', 'venta_web',
                'ventas_forma_pago', 'devoluciones', 'gastos', 'precio_oro',
            );
            foreach ($bloques as $bloque) {
                if (!aid_recalcular_bloque($conexion, $bloque, $idInforme, $fecha_dato, $sucursal_dato)) {
                    $ok = false;
                }
            }
        } else {
            $ok = aid_recalcular_bloque($conexion, $tipo, $idInforme, $fecha_dato, $sucursal_dato);
        }

        if ($ok) {
            aid_marcar_ultima_actualizacion($conexion, $idInforme);
        }

        mysqli_close($conexion);
        return $ok;
    }
}

if (!function_exists('aid_marcar_ultima_actualizacion')) {
    function aid_marcar_ultima_actualizacion(mysqli $conexion, $idInforme)
    {
        $idInforme = (int) $idInforme;
        if ($idInforme <= 0) {
            return false;
        }

        $stmt = mysqli_prepare(
            $conexion,
            'UPDATE informe_diario SET ultima_actualizacion = NOW() WHERE id_informe = ? LIMIT 1'
        );
        if (!$stmt) {
            return false;
        }
        mysqli_stmt_bind_param($stmt, 'i', $idInforme);
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        return (bool) $ok;
    }
}

if (!function_exists('aid_obtener_id_informe')) {
    function aid_obtener_id_informe(mysqli $conexion, $fecha, $sucursal)
    {
        $stmt = mysqli_prepare(
            $conexion,
            'SELECT id_informe FROM informe_diario
             WHERE fecha_informe = ? AND sucursal_informe = ?
             ORDER BY id_informe DESC LIMIT 1'
        );
        if (!$stmt) {
            return 0;
        }
        mysqli_stmt_bind_param($stmt, 'si', $fecha, $sucursal);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $row = $res ? mysqli_fetch_assoc($res) : null;
        mysqli_stmt_close($stmt);

        return $row ? (int) $row['id_informe'] : 0;
    }
}

if (!function_exists('aid_tabla_caja_sucursal')) {
    function aid_tabla_caja_sucursal($idSucursal)
    {
        $idSucursal = (int) $idSucursal;
        if ($idSucursal <= 0) {
            return false;
        }
        $tabla = 'movimientos_de_caja_' . $idSucursal;
        if (!preg_match('/^movimientos_de_caja_\d+$/', $tabla)) {
            return false;
        }

        return $tabla;
    }
}

if (!function_exists('aid_exec_update')) {
    /**
     * @param array<int, mixed> $params
     */
    function aid_exec_update(mysqli $conexion, $sql, $types, array $params)
    {
        $stmt = mysqli_prepare($conexion, $sql);
        if (!$stmt) {
            return false;
        }
        $bind = array($stmt, $types);
        foreach ($params as $k => $v) {
            $bind[] = &$params[$k];
        }
        call_user_func_array('mysqli_stmt_bind_param', $bind);
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        return (bool) $ok;
    }
}

if (!function_exists('aid_fetch_assoc')) {
    /**
     * @param array<int, mixed> $params
     * @return array<string, mixed>|null
     */
    function aid_fetch_assoc(mysqli $conexion, $sql, $types, array $params)
    {
        $stmt = mysqli_prepare($conexion, $sql);
        if (!$stmt) {
            return null;
        }
        if ($types !== '') {
            $bind = array($stmt, $types);
            foreach ($params as $k => $v) {
                $bind[] = &$params[$k];
            }
            call_user_func_array('mysqli_stmt_bind_param', $bind);
        }
        if (!mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            return null;
        }
        $res = mysqli_stmt_get_result($stmt);
        $row = $res ? mysqli_fetch_assoc($res) : null;
        if ($res) {
            mysqli_free_result($res);
        }
        mysqli_stmt_close($stmt);

        return $row ?: array();
    }
}

if (!function_exists('aid_recalcular_bloque')) {
    function aid_recalcular_bloque(mysqli $conexion, $tipo, $idInforme, $fecha, $sucursal)
    {
        $idInforme = (int) $idInforme;
        $sucursal = (int) $sucursal;
        $fecha = (string) $fecha;

        switch ($tipo) {
            case 'compras_oro':
                return aid_bloque_compras_metal($conexion, $idInforme, $fecha, $sucursal, 'oro');
            case 'compras_plata':
                return aid_bloque_compras_metal($conexion, $idInforme, $fecha, $sucursal, 'plata');
            case 'empenos_global':
                return aid_bloque_empenos_global($conexion, $idInforme, $fecha, $sucursal);
            case 'empenos_oro':
                return aid_bloque_empenos_metal($conexion, $idInforme, $fecha, $sucursal, 'oro');
            case 'empenos_plata':
                return aid_bloque_empenos_metal($conexion, $idInforme, $fecha, $sucursal, 'plata');
            case 'empenos_retirados':
                return aid_bloque_empenos_retirados($conexion, $idInforme, $fecha, $sucursal);
            case 'empenos_vencidos':
                return aid_bloque_empenos_estado_fecha(
                    $conexion, $idInforme, $fecha, $sucursal, 'vencido', 'fecha_vencimiento',
                    'total_empenyos_vencidos', 'total_euros_empenyos_vencidos', 'total_gramos_empenios_vencidos'
                );
            case 'empenos_perdidos':
                return aid_bloque_empenos_estado_fecha(
                    $conexion, $idInforme, $fecha, $sucursal, 'perdido', 'fecha_perdido',
                    'total_empenyos_perdidos', 'total_euros_empenios_perdidos', 'total_gramos_empenyos_perdidos'
                );
            case 'empenos_renovaciones':
                return aid_bloque_renovaciones($conexion, $idInforme, $fecha, $sucursal);
            case 'intervenidos':
                return aid_bloque_intervenidos($conexion, $idInforme, $fecha, $sucursal);
            case 'caja':
                return aid_bloque_caja($conexion, $idInforme, $fecha, $sucursal);
            case 'ajustes_lotes':
                return aid_bloque_ajustes_lotes($conexion, $idInforme, $fecha, $sucursal);
            case 'operaciones_tarjeta':
                return aid_bloque_operaciones_tarjeta($conexion, $idInforme, $fecha, $sucursal);
            case 'operaciones_transferencia':
                return aid_bloque_operaciones_transferencia($conexion, $idInforme, $fecha, $sucursal);
            case 'operaciones_bizum':
                return aid_bloque_operaciones_bizum($conexion, $idInforme, $fecha, $sucursal);
            case 'stock_valorizado':
                return aid_bloque_stock_valorizado($conexion, $idInforme, $sucursal);
            case 'venta':
                return aid_bloque_ventas($conexion, $idInforme, $fecha, $sucursal);
            case 'venta_plazos':
                return aid_bloque_ventas_plazos($conexion, $idInforme, $fecha, $sucursal);
            case 'venta_web':
                return aid_bloque_ventas_web($conexion, $idInforme, $fecha, $sucursal);
            case 'ventas_forma_pago':
                return aid_bloque_ventas_forma_pago($conexion, $idInforme, $fecha, $sucursal);
            case 'devoluciones':
                return aid_bloque_devoluciones($conexion, $idInforme, $fecha, $sucursal);
            case 'gastos':
                return aid_bloque_gastos($conexion, $idInforme, $fecha, $sucursal);
            case 'precio_oro':
                return aid_bloque_precio_oro($conexion, $idInforme);
            default:
                return false;
        }
    }
}

if (!function_exists('aid_bloque_compras_metal')) {
    function aid_bloque_compras_metal(mysqli $conexion, $idInforme, $fecha, $sucursal, $metal)
    {
        $compraOpcion = 'no';
        $estadoLote = 'compra';
        $row = aid_fetch_assoc(
            $conexion,
            'SELECT COUNT(identificador) AS TOTALLOTES,
                    COALESCE(SUM(precio_compra), 0) AS TOTALPRECIOCOMPRA,
                    COALESCE(SUM(peso), 0) AS TOTALGRAMOS
             FROM lotes_joyeria
             WHERE compra_opcion = ? AND estado_lote = ? AND fecha_compra = ?
               AND tipo_de_lote = ? AND sucursal = ?',
            'ssssi',
            array($compraOpcion, $estadoLote, $fecha, $metal, $sucursal)
        );
        $totalLotes = (int) ($row['TOTALLOTES'] ?? 0);
        $totalPrecio = round((float) ($row['TOTALPRECIOCOMPRA'] ?? 0), 2);
        $totalGramos = round((float) ($row['TOTALGRAMOS'] ?? 0), 2);
        $media = $totalGramos > 0 ? round($totalPrecio / $totalGramos, 2) : 0.0;

        if ($metal === 'plata') {
            $sql = 'UPDATE informe_diario SET total_lotes_compra_plata = ?, total_gramos_compra_plata = ?,
                    total_euros_lotes_compra_plata = ?, media_pagado_plata_compra = ? WHERE id_informe = ?';
        } else {
            $sql = 'UPDATE informe_diario SET total_lotes_compra_oro = ?, total_gramos_compra_oro = ?,
                    total_euros_lotes_compra_oro = ?, media_pagado_oro_compra = ? WHERE id_informe = ?';
        }

        return aid_exec_update($conexion, $sql, 'idddi', array($totalLotes, $totalGramos, $totalPrecio, $media, $idInforme));
    }
}

if (!function_exists('aid_bloque_empenos_global')) {
    function aid_bloque_empenos_global(mysqli $conexion, $idInforme, $fecha, $sucursal)
    {
        $row = aid_fetch_assoc(
            $conexion,
            "SELECT COUNT(identificador) AS TOTALLOTES,
                    COALESCE(SUM(precio_compra), 0) AS TOTALPRECIOCOMPRA,
                    COALESCE(SUM(peso), 0) AS TOTALGRAMOS
             FROM lotes_joyeria
             WHERE compra_opcion = 'si' AND estado_lote = 'enfecha'
               AND fecha_compra = ? AND sucursal = ?",
            'si',
            array($fecha, $sucursal)
        );
        $totalLotes = (int) ($row['TOTALLOTES'] ?? 0);
        $totalPrecio = round((float) ($row['TOTALPRECIOCOMPRA'] ?? 0), 2);
        $totalGramos = round((float) ($row['TOTALGRAMOS'] ?? 0), 2);
        $media = $totalGramos > 0 ? round($totalPrecio / $totalGramos, 2) : 0.0;

        return aid_exec_update(
            $conexion,
            'UPDATE informe_diario SET total_lotes_empenios = ?, total_gramos_empenios = ?,
             total_euros_lotes_empenios = ?, media_pagado_empenyo = ? WHERE id_informe = ?',
            'idddi',
            array($totalLotes, $totalGramos, $totalPrecio, $media, $idInforme)
        );
    }
}

if (!function_exists('aid_bloque_empenos_metal')) {
    function aid_bloque_empenos_metal(mysqli $conexion, $idInforme, $fecha, $sucursal, $metal)
    {
        $row = aid_fetch_assoc(
            $conexion,
            "SELECT COUNT(identificador) AS TOTALLOTES,
                    COALESCE(SUM(precio_compra), 0) AS TOTALPRECIOCOMPRA,
                    COALESCE(SUM(peso), 0) AS TOTALGRAMOS
             FROM lotes_joyeria
             WHERE compra_opcion = 'si' AND estado_lote = 'enfecha'
               AND fecha_compra = ? AND tipo_de_lote = ? AND sucursal = ?",
            'ssi',
            array($fecha, $metal, $sucursal)
        );
        $totalLotes = (int) ($row['TOTALLOTES'] ?? 0);
        $totalPrecio = round((float) ($row['TOTALPRECIOCOMPRA'] ?? 0), 2);
        $totalGramos = round((float) ($row['TOTALGRAMOS'] ?? 0), 2);
        $media = $totalGramos > 0 ? round($totalPrecio / $totalGramos, 2) : 0.0;

        if ($metal === 'plata') {
            $sql = 'UPDATE informe_diario SET total_lotes_empenios_plata = ?, total_gramos_empenios_plata = ?,
                    total_euros_lotes_empenios_plata = ?, media_pagado_plata_empenyo = ? WHERE id_informe = ?';
        } else {
            $sql = 'UPDATE informe_diario SET total_lotes_empenios_oro = ?, total_gramos_empenios_oro = ?,
                    total_euros_lotes_empenios_oro = ?, media_pagado_oro_empenyo = ? WHERE id_informe = ?';
        }

        return aid_exec_update($conexion, $sql, 'idddi', array($totalLotes, $totalGramos, $totalPrecio, $media, $idInforme));
    }
}

if (!function_exists('aid_bloque_empenos_retirados')) {
    function aid_bloque_empenos_retirados(mysqli $conexion, $idInforme, $fecha, $sucursal)
    {
        $rowLotes = aid_fetch_assoc(
            $conexion,
            "SELECT COUNT(identificador) AS TOTALLOTES, COALESCE(SUM(peso), 0) AS TOTALGRAMOS
             FROM lotes_joyeria
             WHERE compra_opcion = 'si' AND estado_lote = 'retirado'
               AND fecha_retirado = ? AND sucursal = ?",
            'si',
            array($fecha, $sucursal)
        );
        $totalLotes = (int) ($rowLotes['TOTALLOTES'] ?? 0);
        $totalGramos = round((float) ($rowLotes['TOTALGRAMOS'] ?? 0), 2);

        $rowImp = aid_fetch_assoc(
            $conexion,
            "SELECT COALESCE(SUM(importe_renovacion), 0) AS TOTALPRECIOCOMPRA
             FROM historico_renovaciones_gobal
             WHERE estado_historico = 'Retirado' AND fecha_insert = ? AND sucursal_id = ?",
            'si',
            array($fecha, $sucursal)
        );
        $totalPrecio = round((float) ($rowImp['TOTALPRECIOCOMPRA'] ?? 0), 2);

        return aid_exec_update(
            $conexion,
            'UPDATE informe_diario SET total_empenyos_retirados = ?, total_gramos_empenios_retirados = ?,
             total_euros_empenyos_retirados = ? WHERE id_informe = ?',
            'iddi',
            array($totalLotes, $totalGramos, $totalPrecio, $idInforme)
        );
    }
}

if (!function_exists('aid_bloque_empenos_estado_fecha')) {
    function aid_bloque_empenos_estado_fecha(
        mysqli $conexion,
        $idInforme,
        $fecha,
        $sucursal,
        $estadoLote,
        $colFecha,
        $colCount,
        $colEuros,
        $colGramos
    ) {
        $allowedFecha = array('fecha_vencimiento' => 1, 'fecha_perdido' => 1);
        $allowedCols = array(
            'total_empenyos_vencidos' => 1,
            'total_euros_empenyos_vencidos' => 1,
            'total_gramos_empenios_vencidos' => 1,
            'total_empenyos_perdidos' => 1,
            'total_euros_empenios_perdidos' => 1,
            'total_gramos_empenyos_perdidos' => 1,
        );
        if (!isset($allowedFecha[$colFecha]) || !isset($allowedCols[$colCount])
            || !isset($allowedCols[$colEuros]) || !isset($allowedCols[$colGramos])) {
            return false;
        }

        $row = aid_fetch_assoc(
            $conexion,
            "SELECT COUNT(identificador) AS TOTALLOTES,
                    COALESCE(SUM(precio_compra), 0) AS TOTALPRECIOCOMPRA,
                    COALESCE(SUM(peso), 0) AS TOTALGRAMOS
             FROM lotes_joyeria
             WHERE compra_opcion = 'si' AND estado_lote = ?
               AND {$colFecha} = ? AND sucursal = ?",
            'ssi',
            array($estadoLote, $fecha, $sucursal)
        );
        $totalLotes = (int) ($row['TOTALLOTES'] ?? 0);
        $totalPrecio = round((float) ($row['TOTALPRECIOCOMPRA'] ?? 0), 2);
        $totalGramos = round((float) ($row['TOTALGRAMOS'] ?? 0), 2);

        // perdidos: order count, gramos, euros | vencidos: count, euros, gramos
        if ($colEuros === 'total_euros_empenios_perdidos') {
            $sql = "UPDATE informe_diario SET {$colCount} = ?, {$colGramos} = ?, {$colEuros} = ? WHERE id_informe = ?";
            return aid_exec_update($conexion, $sql, 'iddi', array($totalLotes, $totalGramos, $totalPrecio, $idInforme));
        }

        $sql = "UPDATE informe_diario SET {$colCount} = ?, {$colEuros} = ?, {$colGramos} = ? WHERE id_informe = ?";
        return aid_exec_update($conexion, $sql, 'iddi', array($totalLotes, $totalPrecio, $totalGramos, $idInforme));
    }
}

if (!function_exists('aid_bloque_renovaciones')) {
    function aid_bloque_renovaciones(mysqli $conexion, $idInforme, $fecha, $sucursal)
    {
        $row = aid_fetch_assoc(
            $conexion,
            "SELECT COUNT(id_renovaciones) AS TOTAL_RENOVACIONES,
                    COALESCE(SUM(importe_renovacion), 0) AS TOTALRENOVACION
             FROM historico_renovaciones_gobal
             WHERE estado_historico = 'Renovado' AND fecha_renovacion = ? AND sucursal_id = ?",
            'si',
            array($fecha, $sucursal)
        );
        $total = (int) ($row['TOTAL_RENOVACIONES'] ?? 0);
        $euros = round((float) ($row['TOTALRENOVACION'] ?? 0), 2);

        return aid_exec_update(
            $conexion,
            'UPDATE informe_diario SET total_renovaciones = ?, total_euros_renovaciones = ? WHERE id_informe = ?',
            'idi',
            array($total, $euros, $idInforme)
        );
    }
}

if (!function_exists('aid_bloque_intervenidos')) {
    function aid_bloque_intervenidos(mysqli $conexion, $idInforme, $fecha, $sucursal)
    {
        $row = aid_fetch_assoc(
            $conexion,
            "SELECT COUNT(identificador) AS TOTALLOTES,
                    COALESCE(SUM(precio_compra), 0) AS TOTALPRECIOCOMPRA,
                    COALESCE(SUM(peso), 0) AS TOTALGRAMOS
             FROM lotes_joyeria
             WHERE estado_lote = 'intervenido' AND fecha_intervenido = ? AND sucursal = ?",
            'si',
            array($fecha, $sucursal)
        );
        $totalLotes = (int) ($row['TOTALLOTES'] ?? 0);
        $totalPrecio = round((float) ($row['TOTALPRECIOCOMPRA'] ?? 0), 2);
        $totalGramos = round((float) ($row['TOTALGRAMOS'] ?? 0), 2);

        return aid_exec_update(
            $conexion,
            'UPDATE informe_diario SET total_contratos_intervenidos = ?, total_gramos_contratos_intervenidos = ?,
             total_euros_contratos_intervenidos = ? WHERE id_informe = ?',
            'iddi',
            array($totalLotes, $totalGramos, $totalPrecio, $idInforme)
        );
    }
}

if (!function_exists('aid_bloque_caja')) {
    function aid_bloque_caja(mysqli $conexion, $idInforme, $fecha, $sucursal)
    {
        $tabla = aid_tabla_caja_sucursal($sucursal);
        if ($tabla === false) {
            return false;
        }
        $row = aid_fetch_assoc(
            $conexion,
            "SELECT COALESCE(SUM(salida), 0) AS TOTALCAJASALIDAS,
                    COALESCE(SUM(entrada), 0) AS TOTALCAJAENTRADAS
             FROM `{$tabla}`
             WHERE fecha_apunte = ? AND cierre_caja = 'false' AND grupos != 'CAJA INICIO'",
            's',
            array($fecha)
        );
        $salidas = round((float) ($row['TOTALCAJASALIDAS'] ?? 0), 2);
        $entradas = round((float) ($row['TOTALCAJAENTRADAS'] ?? 0), 2);

        return aid_exec_update(
            $conexion,
            'UPDATE informe_diario SET total_caja_entradas = ?, total_caja_salidas = ? WHERE id_informe = ?',
            'ddi',
            array($entradas, $salidas, $idInforme)
        );
    }
}

if (!function_exists('aid_bloque_ajustes_lotes')) {
    function aid_bloque_ajustes_lotes(mysqli $conexion, $idInforme, $fecha, $sucursal)
    {
        $tabla = aid_tabla_caja_sucursal($sucursal);
        if ($tabla === false) {
            return false;
        }
        // En el cron histórico había "grupos == 'Abonados'" (inválido). Aquí se usa "=".
        $row = aid_fetch_assoc(
            $conexion,
            "SELECT COALESCE(SUM(salida), 0) AS TOTALCAJASALIDAS
             FROM `{$tabla}`
             WHERE fecha_apunte = ? AND cierre_caja = 'false' AND grupos = 'Abonados'",
            's',
            array($fecha)
        );
        $total = round((float) ($row['TOTALCAJASALIDAS'] ?? 0), 2);

        return aid_exec_update(
            $conexion,
            'UPDATE informe_diario SET ajustes_de_lotes = ? WHERE id_informe = ?',
            'di',
            array($total, $idInforme)
        );
    }
}

if (!function_exists('aid_bloque_operaciones_tarjeta')) {
    function aid_bloque_operaciones_tarjeta(mysqli $conexion, $idInforme, $fecha, $sucursal)
    {
        $row = aid_fetch_assoc(
            $conexion,
            'SELECT COALESCE(SUM(importe), 0) AS TOTAL FROM movimientos_tarjeta
             WHERE sucursal = ? AND DATE(fecha) = ?',
            'is',
            array($sucursal, $fecha)
        );
        $total = round((float) ($row['TOTAL'] ?? 0), 2);

        return aid_exec_update(
            $conexion,
            'UPDATE informe_diario SET total_operaciones_tarjeta = ? WHERE id_informe = ?',
            'di',
            array($total, $idInforme)
        );
    }
}

if (!function_exists('aid_bloque_operaciones_transferencia')) {
    function aid_bloque_operaciones_transferencia(mysqli $conexion, $idInforme, $fecha, $sucursal)
    {
        $rowE = aid_fetch_assoc(
            $conexion,
            'SELECT COALESCE(SUM(entrada), 0) AS TOTAL FROM movimientos_transferencia
             WHERE sucursal = ? AND DATE(fecha) = ?',
            'is',
            array($sucursal, $fecha)
        );
        $rowS = aid_fetch_assoc(
            $conexion,
            'SELECT COALESCE(SUM(salida), 0) AS TOTAL FROM movimientos_transferencia
             WHERE sucursal = ? AND DATE(fecha) = ?',
            'is',
            array($sucursal, $fecha)
        );
        $entrada = round((float) ($rowE['TOTAL'] ?? 0), 2);
        $salida = round((float) ($rowS['TOTAL'] ?? 0), 2);

        return aid_exec_update(
            $conexion,
            'UPDATE informe_diario SET total_operaciones_trasnferencia_entrada = ?,
             total_operaciones_trasnferencia_salida = ? WHERE id_informe = ?',
            'ddi',
            array($entrada, $salida, $idInforme)
        );
    }
}

if (!function_exists('aid_bloque_operaciones_bizum')) {
    function aid_bloque_operaciones_bizum(mysqli $conexion, $idInforme, $fecha, $sucursal)
    {
        $row = aid_fetch_assoc(
            $conexion,
            'SELECT COALESCE(SUM(importe), 0) AS TOTAL FROM movimientos_bizum
             WHERE sucursal = ? AND DATE(fecha) = ?',
            'is',
            array($sucursal, $fecha)
        );
        $total = round((float) ($row['TOTAL'] ?? 0), 2);

        return aid_exec_update(
            $conexion,
            'UPDATE informe_diario SET total_operaciones_bizum = ? WHERE id_informe = ?',
            'di',
            array($total, $idInforme)
        );
    }
}

if (!function_exists('aid_bloque_stock_valorizado')) {
    function aid_bloque_stock_valorizado(mysqli $conexion, $idInforme, $sucursal)
    {
        $row = aid_fetch_assoc(
            $conexion,
            "SELECT COALESCE(SUM(precio), 0) AS TOTALSTOCKVALORIZADO,
                    COALESCE(SUM(precio_coste), 0) AS TOTALSTOCKCOSTE,
                    COUNT(id) AS TOTALSTOCKUNIDADES
             FROM articulos_venta
             WHERE id_sucursal_destino = ? AND estado = 'enventa'",
            'i',
            array($sucursal)
        );
        $valorizado = round((float) ($row['TOTALSTOCKVALORIZADO'] ?? 0), 2);
        $coste = round((float) ($row['TOTALSTOCKCOSTE'] ?? 0), 2);
        $unidades = (int) ($row['TOTALSTOCKUNIDADES'] ?? 0);

        return aid_exec_update(
            $conexion,
            'UPDATE informe_diario SET coste_stock_valorizado = ?, stock_articulos = ?,
             stock_valorizado_eruo = ? WHERE id_informe = ?',
            'didi',
            array($coste, $unidades, $valorizado, $idInforme)
        );
    }
}

if (!function_exists('aid_bloque_ventas')) {
    function aid_bloque_ventas(mysqli $conexion, $idInforme, $fecha, $sucursal)
    {
        $rowV = aid_fetch_assoc(
            $conexion,
            "SELECT COALESCE(SUM(precio), 0) AS TOTALEUROSVENTAS, COUNT(id) AS TOTALVENTAS
             FROM ventas
             WHERE id_sucursal = ? AND estado = 'vendido' AND DATE(fecha) = ?",
            'is',
            array($sucursal, $fecha)
        );
        $totalVentas = (int) ($rowV['TOTALVENTAS'] ?? 0);
        $totalEuros = round((float) ($rowV['TOTALEUROSVENTAS'] ?? 0), 2);
        $media = $totalVentas > 0 ? round($totalEuros / $totalVentas, 2) : 0.0;

        $rowA = aid_fetch_assoc(
            $conexion,
            "SELECT COALESCE(SUM(peso), 0) AS TOTALGRAMOSVENTAS,
                    COALESCE(SUM(precio_coste), 0) AS TOTALCOSTEARTICULOSVENDIDOS
             FROM articulos_venta
             WHERE id_sucursal_destino = ? AND estado = 'vendido' AND DATE(fecha_vendido) = ?",
            'is',
            array($sucursal, $fecha)
        );
        $totalGramos = round((float) ($rowA['TOTALGRAMOSVENTAS'] ?? 0), 2);
        $totalCoste = round((float) ($rowA['TOTALCOSTEARTICULOSVENDIDOS'] ?? 0), 2);
        $beneficio = round($totalEuros - $totalCoste, 2);

        return aid_exec_update(
            $conexion,
            'UPDATE informe_diario SET total_beneficio_ventas = ?, total_coste_art_venta = ?,
             total_gramos_ventas = ?, total_ventas = ?, total_euros_ventas = ?, total_media_ventas = ?
             WHERE id_informe = ?',
            'dddiddi',
            array($beneficio, $totalCoste, $totalGramos, $totalVentas, $totalEuros, $media, $idInforme)
        );
    }
}

if (!function_exists('aid_bloque_ventas_plazos')) {
    function aid_bloque_ventas_plazos(mysqli $conexion, $idInforme, $fecha, $sucursal)
    {
        $row = aid_fetch_assoc(
            $conexion,
            "SELECT COALESCE(SUM(precio), 0) AS TOTALEUROSVENTAS, COUNT(id) AS TOTALVENTAS
             FROM ventas
             WHERE id_sucursal = ? AND venta_plazos = 'si' AND DATE(fecha) = ?",
            'is',
            array($sucursal, $fecha)
        );
        $totalVentas = (int) ($row['TOTALVENTAS'] ?? 0);
        $totalEuros = round((float) ($row['TOTALEUROSVENTAS'] ?? 0), 2);

        return aid_exec_update(
            $conexion,
            'UPDATE informe_diario SET total_ventas_plazo = ?, total_ventas_plazo_euro = ? WHERE id_informe = ?',
            'idi',
            array($totalVentas, $totalEuros, $idInforme)
        );
    }
}

if (!function_exists('aid_bloque_ventas_web')) {
    function aid_bloque_ventas_web(mysqli $conexion, $idInforme, $fecha, $sucursal)
    {
        // Filtra por sucursal (el cron histórico no lo hacía).
        $row = aid_fetch_assoc(
            $conexion,
            "SELECT COALESCE(SUM(precio), 0) AS TOTALEUROSVENTAS, COUNT(id) AS TOTALVENTAS
             FROM ventas
             WHERE id_sucursal = ? AND estado = 'vendido' AND venta_web = 'true' AND DATE(fecha) = ?",
            'is',
            array($sucursal, $fecha)
        );
        $totalVentas = (int) ($row['TOTALVENTAS'] ?? 0);
        $totalEuros = round((float) ($row['TOTALEUROSVENTAS'] ?? 0), 2);
        $media = $totalVentas > 0 ? round($totalEuros / $totalVentas, 2) : 0.0;

        return aid_exec_update(
            $conexion,
            'UPDATE informe_diario SET ventas_web = ?, total_euros_ventas_web = ?,
             total_media_ventas_web = ? WHERE id_informe = ?',
            'iddi',
            array($totalVentas, $totalEuros, $media, $idInforme)
        );
    }
}

if (!function_exists('aid_bloque_ventas_forma_pago')) {
    function aid_bloque_ventas_forma_pago(mysqli $conexion, $idInforme, $fecha, $sucursal)
    {
        $row = aid_fetch_assoc(
            $conexion,
            "SELECT
                SUM(CASE WHEN tipo_pago = 'contado' OR (tipo_pago = 'combinado' AND cantidad_contado > 0) THEN 1 ELSE 0 END) AS ventas_contado,
                COALESCE(SUM(cantidad_contado), 0) AS ventas_contado_euros,
                SUM(CASE WHEN tipo_pago = 'tarjeta' OR (tipo_pago = 'combinado' AND cantidad_tarjeta > 0) THEN 1 ELSE 0 END) AS ventas_tarjeta,
                COALESCE(SUM(cantidad_tarjeta), 0) AS ventas_tarjeta_euros,
                SUM(CASE WHEN tipo_pago = 'transferencia' OR (tipo_pago = 'combinado' AND cantidad_transferencia > 0) THEN 1 ELSE 0 END) AS ventas_transferencia,
                COALESCE(SUM(cantidad_transferencia), 0) AS ventas_transferencia_euros,
                SUM(CASE WHEN tipo_pago = 'bizum' OR (tipo_pago = 'combinado' AND cantidad_bizum > 0) THEN 1 ELSE 0 END) AS ventas_bizum,
                COALESCE(SUM(cantidad_bizum), 0) AS ventas_bizum_euros
             FROM ventas
             WHERE id_sucursal = ? AND estado = 'vendido' AND DATE(fecha) = ?",
            'is',
            array($sucursal, $fecha)
        );

        return aid_exec_update(
            $conexion,
            'UPDATE informe_diario SET
                ventas_contado = ?, ventas_contado_euros = ?,
                ventas_tarjeta = ?, ventas_tarjeta_euros = ?,
                ventas_transferencia = ?, ventas_transferencia_euros = ?,
                ventas_bizum = ?, ventas_bizum_euros = ?
             WHERE id_informe = ?',
            'ididididi',
            array(
                (int) ($row['ventas_contado'] ?? 0),
                round((float) ($row['ventas_contado_euros'] ?? 0), 2),
                (int) ($row['ventas_tarjeta'] ?? 0),
                round((float) ($row['ventas_tarjeta_euros'] ?? 0), 2),
                (int) ($row['ventas_transferencia'] ?? 0),
                round((float) ($row['ventas_transferencia_euros'] ?? 0), 2),
                (int) ($row['ventas_bizum'] ?? 0),
                round((float) ($row['ventas_bizum_euros'] ?? 0), 2),
                $idInforme,
            )
        );
    }
}

if (!function_exists('aid_bloque_devoluciones')) {
    function aid_bloque_devoluciones(mysqli $conexion, $idInforme, $fecha, $sucursal)
    {
        $row = aid_fetch_assoc(
            $conexion,
            "SELECT COUNT(id) AS TOTALDEVOLUCIONES,
                    COALESCE(SUM(importe_devolucion), 0) AS TOTALEUROSDEVOLUCIONES
             FROM devoluciones
             WHERE sucursal_devolucion = ? AND estado_devolucion = 'hecha' AND fecha_devolucion = ?",
            'is',
            array($sucursal, $fecha)
        );
        $total = (int) ($row['TOTALDEVOLUCIONES'] ?? 0);
        $euros = round((float) ($row['TOTALEUROSDEVOLUCIONES'] ?? 0), 2);

        return aid_exec_update(
            $conexion,
            'UPDATE informe_diario SET total_devoluciones = ?, total_euros_devoluciones = ? WHERE id_informe = ?',
            'idi',
            array($total, $euros, $idInforme)
        );
    }
}

if (!function_exists('aid_bloque_gastos')) {
    function aid_bloque_gastos(mysqli $conexion, $idInforme, $fecha, $sucursal)
    {
        $row = aid_fetch_assoc(
            $conexion,
            'SELECT COALESCE(SUM(total_gasto), 0) AS TOTAL_GASTOS
             FROM gastos WHERE sucursal_gasto = ? AND fecha_gasto = ?',
            'is',
            array($sucursal, $fecha)
        );
        $total = round((float) ($row['TOTAL_GASTOS'] ?? 0), 2);

        return aid_exec_update(
            $conexion,
            'UPDATE informe_diario SET total_gastos = ? WHERE id_informe = ?',
            'di',
            array($total, $idInforme)
        );
    }
}

if (!function_exists('aid_bloque_precio_oro')) {
    function aid_bloque_precio_oro(mysqli $conexion, $idInforme)
    {
        $row = aid_fetch_assoc(
            $conexion,
            'SELECT precio_oro FROM precio_oro
             WHERE id_precio_oro = (SELECT MAX(id_precio_oro) FROM precio_oro) LIMIT 1',
            '',
            array()
        );
        $precio = round((float) ($row['precio_oro'] ?? 0), 2);

        return aid_exec_update(
            $conexion,
            'UPDATE informe_diario SET precio_oro = ? WHERE id_informe = ?',
            'di',
            array($precio, $idInforme)
        );
    }
}
