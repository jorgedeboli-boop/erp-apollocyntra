<?php

/**
 * Funciones auxiliares del cron del TPV.
 */

/**
 * Registra la conexión MySQL disponible para las tareas del cron.
 *
 * @param mysqli $conexion
 * @return void
 */
function cron_establecer_conexion($conexion)
{
    $GLOBALS['cron_conexion'] = $conexion;
}

/**
 * Obtiene la conexión MySQL del cron.
 *
 * @return mysqli|null
 */
function cron_obtener_conexion()
{
    return isset($GLOBALS['cron_conexion']) ? $GLOBALS['cron_conexion'] : null;
}

/**
 * Registra la conexión web (PrestaShop / Goyac) para las tareas del cron.
 *
 * @param mysqli $conexionWeb
 * @return void
 */
function cron_establecer_conexion_web($conexionWeb)
{
    $GLOBALS['cron_conexion_web'] = $conexionWeb;
}

/**
 * Obtiene la conexión web del cron.
 *
 * @return mysqli|null
 */
function cron_obtener_conexion_web()
{
    return isset($GLOBALS['cron_conexion_web']) ? $GLOBALS['cron_conexion_web'] : null;
}

/**
 * Activa o desactiva el modo solo vista (sin escritura en BD).
 *
 * @param bool $only_view
 * @return void
 */
function cron_establecer_only_view($only_view)
{
    $GLOBALS['cron_only_view'] = (bool) $only_view;
}

/**
 * Indica si el cron está en modo solo vista.
 *
 * @return bool
 */
function cron_solo_vista()
{
    return !empty($GLOBALS['cron_only_view']);
}

/**
 * Inserta un registro en control_cron.
 *
 * @param string $descripcion_cron
 * @param int|string $id_sucursal_cron
 * @param string $tipo_de_operacion
 * @param string $usuario_cron
 * @return bool
 */
function insert_global_cron($descripcion_cron, $id_sucursal_cron, $tipo_de_operacion, $usuario_cron = '')
{
    if (cron_solo_vista()) {
        return true;
    }

    $conexion = cron_obtener_conexion();
    if (!$conexion) {
        return false;
    }

    if ($usuario_cron === '' || $usuario_cron === null) {
        $usuario_cron = '1';
    }

    $descripcion_cron = (string) $descripcion_cron;
    $id_sucursal_cron = (string) $id_sucursal_cron;
    $tipo_de_operacion = (string) $tipo_de_operacion;
    $usuario_cron = (string) $usuario_cron;

    $sql = "INSERT INTO control_cron (
        fecha_cron,
        hora_cron,
        descripcion_cron,
        sucursal_cron,
        tipo_de_operacion,
        usuario_cron
    ) VALUES (NOW(), NOW(), ?, ?, ?, ?)";

    $stmt = mysqli_prepare($conexion, $sql);
    if (!$stmt) {
        return false;
    }

    mysqli_stmt_bind_param(
        $stmt,
        'ssss',
        $descripcion_cron,
        $id_sucursal_cron,
        $tipo_de_operacion,
        $usuario_cron
    );

    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    return (bool) $ok;
}

/**
 * Registra trazabilidad de un lote desde el cron.
 *
 * @param int $id_lote
 * @param int $usuario_accion
 * @param string $accion_trazabilidad
 * @param string $comentarios_accion
 * @param int $sucursal_accion
 * @return bool
 */
function registrar_trazabilidad_lote_cron($id_lote, $usuario_accion, $accion_trazabilidad, $comentarios_accion, $sucursal_accion)
{
    if (cron_solo_vista()) {
        return true;
    }

    $conexion = cron_obtener_conexion();
    if (!$conexion) {
        return false;
    }

    $id_lote = (int) $id_lote;
    $usuario_accion = (int) $usuario_accion;
    $accion_trazabilidad = (string) $accion_trazabilidad;
    $comentarios_accion = (string) $comentarios_accion;
    $sucursal_accion = (int) $sucursal_accion;

    $sql = "INSERT INTO trazabilidad_lotes (
        id_lote,
        fecha_accion,
        usuario_accion,
        accion_trazabilidad,
        comentarios_accion,
        sucursal_accion
    ) VALUES (?, NOW(), ?, ?, ?, ?)";

    $stmt = mysqli_prepare($conexion, $sql);
    if (!$stmt) {
        error_log('Error al preparar consulta de trazabilidad cron: ' . mysqli_error($conexion));
        return false;
    }

    mysqli_stmt_bind_param(
        $stmt,
        'iissi',
        $id_lote,
        $usuario_accion,
        $accion_trazabilidad,
        $comentarios_accion,
        $sucursal_accion
    );

    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    return (bool) $ok;
}

/**
 * Registra un evento en tareas_cron.
 *
 * @param string $descripcion_evento
 * @return bool
 */
function registrar_tareas_cron($descripcion_evento)
{
    if (cron_solo_vista()) {
        return true;
    }

    $conexion = cron_obtener_conexion();
    if (!$conexion) {
        return false;
    }

    $descripcion_evento = (string) $descripcion_evento;

    $sql = "INSERT INTO tareas_cron (descripcion_evento, fecha) VALUES (?, NOW())";

    $stmt = mysqli_prepare($conexion, $sql);
    if (!$stmt) {
        return false;
    }

    mysqli_stmt_bind_param($stmt, 's', $descripcion_evento);

    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    return (bool) $ok;
}

/**
 * Contexto inicial para un paso del informe diario.
 *
 * @param string $nombrePaso
 * @return array{conexion:mysqli,fecha:string}|null
 */
function cron_informe_contexto($nombrePaso)
{
    $conexion = cron_obtener_conexion();
    if (!$conexion) {
        cron_linea('ERROR ' . $nombrePaso . ': sin conexion a base de datos.');
        return null;
    }

    global $fecha_informe_today;
    if (!isset($fecha_informe_today) || $fecha_informe_today === '') {
        $fecha_informe_today = date('Y-m-d');
    }

    cron_linea('>> Paso: ' . $nombrePaso . ' | fecha=' . $fecha_informe_today);

    return array(
        'conexion' => $conexion,
        'fecha' => $fecha_informe_today,
    );
}

/**
 * @param mysqli $conexion
 * @param string $fechaInforme
 * @return mysqli_stmt|null
 */
function cron_informe_stmt_abiertos($conexion, $fechaInforme)
{
    $sql = "SELECT id_informe, sucursal_informe, fecha_informe
            FROM informe_diario
            WHERE fecha_informe = ?
              AND estado_informe = 'abierto'
            ORDER BY id_informe ASC";
    $stmt = mysqli_prepare($conexion, $sql);
    if (!$stmt) {
        return null;
    }

    mysqli_stmt_bind_param($stmt, 's', $fechaInforme);
    if (!mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        return null;
    }

    return $stmt;
}

/**
 * @param int $idInforme
 * @param int $sucursalInforme
 * @return void
 */
function cron_informe_tarea_generado($idInforme, $sucursalInforme)
{
    registrar_tareas_cron(
        'Genero informe Nº ' . (int) $idInforme . ' de la Sucursal ' . (int) $sucursalInforme
    );
}

/**
 * Contexto inicial para un paso del informe actual (tiempo real).
 *
 * @param string $nombrePaso
 * @return array{conexion:mysqli,fecha:string}|null
 */
function cron_informe_actual_contexto($nombrePaso)
{
    $conexion = cron_obtener_conexion();
    if (!$conexion) {
        cron_linea('ERROR ' . $nombrePaso . ': sin conexion a base de datos.');
        return null;
    }

    global $fecha_informe_actual_today;
    if (!isset($fecha_informe_actual_today) || $fecha_informe_actual_today === '') {
        $fecha_informe_actual_today = date('Y-m-d');
    }

    cron_linea('>> Paso: ' . $nombrePaso . ' | fecha=' . $fecha_informe_actual_today);

    return array(
        'conexion' => $conexion,
        'fecha' => $fecha_informe_actual_today,
    );
}

/**
 * @param mysqli $conexion
 * @param string $fechaInforme
 * @return mysqli_stmt|null
 */
function cron_informe_actual_stmt_abiertos($conexion, $fechaInforme)
{
    $sql = "SELECT id_informe, sucursal_informe, fecha_informe
            FROM informe_actual
            WHERE fecha_informe = ?
              AND estado_informe = 'abierto'
            ORDER BY id_informe ASC";
    $stmt = mysqli_prepare($conexion, $sql);
    if (!$stmt) {
        return null;
    }

    mysqli_stmt_bind_param($stmt, 's', $fechaInforme);
    if (!mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        return null;
    }

    return $stmt;
}

/**
 * @param int $idInforme
 * @param int $sucursalInforme
 * @return void
 */
function cron_informe_actual_tarea_generado($idInforme, $sucursalInforme)
{
    registrar_tareas_cron(
        'Actualizo informe actual Nº ' . (int) $idInforme . ' de la Sucursal ' . (int) $sucursalInforme
    );
}

/**
 * Resuelve el año del listado semanal (fallback al año actual).
 *
 * @return string
 */
function cron_informe_semanal_resolver_anyo()
{
    global $anyo_listado;

    if (!isset($anyo_listado) || $anyo_listado === '' || $anyo_listado === null) {
        $anyo_listado = date('Y');
    }

    return (string) $anyo_listado;
}

/**
 * Contexto inicial para un paso del informe semanal.
 *
 * @param string $nombrePaso
 * @return array{conexion:mysqli,anyo:string}|null
 */
function cron_informe_semanal_contexto($nombrePaso)
{
    $conexion = cron_obtener_conexion();
    if (!$conexion) {
        cron_linea('ERROR ' . $nombrePaso . ': sin conexion a base de datos.');
        return null;
    }

    $anyoListado = cron_informe_semanal_resolver_anyo();
    cron_linea('>> Paso: ' . $nombrePaso . ' | anyo=' . $anyoListado);

    return array(
        'conexion' => $conexion,
        'anyo' => $anyoListado,
    );
}

/**
 * @param mysqli $conexion
 * @param string $anyoListado
 * @return mysqli_stmt|null
 */
function cron_informe_semanal_stmt_abiertos($conexion, $anyoListado)
{
    $sql = "SELECT id_informe, sucursal_informe, numero_semana, year_informe, numero_proforma
            FROM informe_semanal
            WHERE estado_informe = 'abierto'
              AND year_informe = ?
            ORDER BY id_informe ASC";
    $stmt = mysqli_prepare($conexion, $sql);
    if (!$stmt) {
        return null;
    }

    mysqli_stmt_bind_param($stmt, 's', $anyoListado);
    if (!mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        return null;
    }

    return $stmt;
}

/**
 * @param int $idInforme
 * @param int $sucursalInforme
 * @return void
 */
function cron_informe_semanal_tarea_generado($idInforme, $sucursalInforme)
{
    registrar_tareas_cron(
        'Genero informe semanal Nº ' . (int) $idInforme . ' de la Sucursal ' . (int) $sucursalInforme
    );
}

/**
 * Suma una columna de informe_diario para una semana/sucursal.
 *
 * @param mysqli $conexion
 * @param int $numeroSemana
 * @param string $yearInforme
 * @param int $sucursalInforme
 * @param string $columna
 * @return float
 */
function cron_informe_semanal_sum_diario($conexion, $numeroSemana, $yearInforme, $sucursalInforme, $columna)
{
    static $columnasPermitidas = array(
        'ajustes_de_lotes',
        'total_caja_entradas',
        'total_caja_salidas',
        'total_operaciones_tarjeta',
        'total_operaciones_trasnferencia_entrada',
        'total_operaciones_trasnferencia_salida',
        'total_operaciones_bizum',
        'total_lotes_compra_oro',
        'total_euros_lotes_compra_oro',
        'total_gramos_compra_oro',
        'total_lotes_compra_plata',
        'total_euros_lotes_compra_plata',
        'total_gramos_compra_plata',
        'total_lotes_empenios',
        'total_euros_lotes_empenios',
        'total_gramos_empenios',
        'total_lotes_empenios_oro',
        'total_euros_lotes_empenios_oro',
        'total_gramos_empenios_oro',
        'total_lotes_empenios_plata',
        'total_euros_lotes_empenios_plata',
        'total_gramos_empenios_plata',
        'total_empenyos_retirados',
        'total_euros_empenyos_retirados',
        'total_gramos_empenios_retirados',
        'total_empenyos_vencidos',
        'total_euros_empenyos_vencidos',
        'total_gramos_empenios_vencidos',
        'total_empenyos_perdidos',
        'total_euros_empenios_perdidos',
        'total_gramos_empenyos_perdidos',
        'total_renovaciones',
        'total_euros_renovaciones',
        'total_contratos_intervenidos',
        'total_euros_contratos_intervenidos',
        'total_gramos_contratos_intervenidos',
        'total_ventas',
        'total_euros_ventas',
        'total_gramos_ventas',
        'total_coste_art_venta',
        'total_ventas_plazo',
        'total_ventas_plazo_euro',
        'ventas_web',
        'total_euros_ventas_web',
        'ventas_contado',
        'ventas_contado_euros',
        'ventas_tarjeta',
        'ventas_tarjeta_euros',
        'ventas_transferencia',
        'ventas_transferencia_euros',
        'ventas_bizum',
        'ventas_bizum_euros',
        'total_devoluciones',
        'total_euros_devoluciones',
    );

    if (!in_array($columna, $columnasPermitidas, true)) {
        return 0.0;
    }

    $sql = 'SELECT SUM(' . $columna . ') AS total
            FROM informe_diario
            WHERE semana_numero = ?
              AND year_rel = ?
              AND sucursal_informe = ?';
    $stmt = mysqli_prepare($conexion, $sql);
    if (!$stmt) {
        return 0.0;
    }

    mysqli_stmt_bind_param($stmt, 'isi', $numeroSemana, $yearInforme, $sucursalInforme);
    if (!mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        return 0.0;
    }

    $fila = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    return (float) (isset($fila['total']) ? $fila['total'] : 0);
}

/**
 * Recorre informes semanales abiertos del año en curso.
 *
 * @param string $nombrePaso
 * @param callable $callback
 * @return void
 */
function cron_informe_semanal_recorrer_abiertos($nombrePaso, $callback)
{
    $ctx = cron_informe_semanal_contexto($nombrePaso);
    if (!$ctx) {
        return;
    }

    $stmt = cron_informe_semanal_stmt_abiertos($ctx['conexion'], $ctx['anyo']);
    if (!$stmt) {
        cron_linea('ERROR ' . $nombrePaso . ' preparando consulta de informes.');
        return;
    }

    $resultado = mysqli_stmt_get_result($stmt);
    while ($informe = $resultado ? mysqli_fetch_assoc($resultado) : false) {
        $callback($informe, $ctx['conexion']);
    }

    mysqli_stmt_close($stmt);
}

/**
 * Resuelve el año del listado mensual (fallback al año actual).
 *
 * @return string
 */
function cron_informe_mensual_resolver_anyo()
{
    global $anyo_listado;

    if (!isset($anyo_listado) || $anyo_listado === '' || $anyo_listado === null) {
        $anyo_listado = date('Y');
    }

    return (string) $anyo_listado;
}

/**
 * Contexto inicial para un paso del informe mensual.
 *
 * @param string $nombrePaso
 * @return array{conexion:mysqli,anyo:string}|null
 */
function cron_informe_mensual_contexto($nombrePaso)
{
    $conexion = cron_obtener_conexion();
    if (!$conexion) {
        cron_linea('ERROR ' . $nombrePaso . ': sin conexion a base de datos.');
        return null;
    }

    $anyoListado = cron_informe_mensual_resolver_anyo();
    cron_linea('>> Paso: ' . $nombrePaso . ' | anyo=' . $anyoListado);

    return array(
        'conexion' => $conexion,
        'anyo' => $anyoListado,
    );
}

/**
 * @param mysqli $conexion
 * @param string $anyoListado
 * @return mysqli_stmt|null
 */
function cron_informe_mensual_stmt_abiertos($conexion, $anyoListado)
{
    $sql = "SELECT id_informe, sucursal_informe, numero_mes, year_informe, fecha_desde, fecha_hasta
            FROM informe_mensual
            WHERE estado_informe = 'abierto'
              AND year_informe = ?
            ORDER BY id_informe ASC";
    $stmt = mysqli_prepare($conexion, $sql);
    if (!$stmt) {
        return null;
    }

    mysqli_stmt_bind_param($stmt, 's', $anyoListado);
    if (!mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        return null;
    }

    return $stmt;
}

/**
 * @param int $idInforme
 * @param int $sucursalInforme
 * @return void
 */
function cron_informe_mensual_tarea_generado($idInforme, $sucursalInforme)
{
    registrar_tareas_cron(
        'Genero informe mensual Nº ' . (int) $idInforme . ' de la Sucursal ' . (int) $sucursalInforme
    );
}

/**
 * Suma una columna de informe_semanal para un mes/sucursal.
 *
 * @param mysqli $conexion
 * @param int $numeroMes
 * @param string $yearInforme
 * @param int $sucursalInforme
 * @param string $columna
 * @return float
 */
function cron_informe_mensual_sum_semanal($conexion, $numeroMes, $yearInforme, $sucursalInforme, $columna)
{
    static $columnasPermitidas = array(
        'ajustes_de_lotes',
        'total_caja_entradas',
        'total_caja_salidas',
        'total_operaciones_tarjeta',
        'total_operaciones_trasnferencia_entrada',
        'total_operaciones_trasnferencia_salida',
        'total_operaciones_bizum',
        'total_lotes_compra_oro',
        'total_euros_lotes_compra_oro',
        'total_gramos_compra_oro',
        'total_lotes_compra_plata',
        'total_euros_lotes_compra_plata',
        'total_gramos_compra_plata',
        'total_lotes_empenios',
        'total_euros_lotes_empenios',
        'total_gramos_empenios',
        'total_lotes_empenios_oro',
        'total_euros_lotes_empenios_oro',
        'total_gramos_empenios_oro',
        'total_lotes_empenios_plata',
        'total_euros_lotes_empenios_plata',
        'total_gramos_empenios_plata',
        'total_empenyos_retirados',
        'total_euros_empenyos_retirados',
        'total_gramos_empenios_retirados',
        'total_empenyos_vencidos',
        'total_euros_empenyos_vencidos',
        'total_gramos_empenios_vencidos',
        'total_empenyos_perdidos',
        'total_euros_empenios_perdidos',
        'total_gramos_empenyos_perdidos',
        'total_renovaciones',
        'total_euros_renovaciones',
        'total_contratos_intervenidos',
        'total_euros_contratos_intervenidos',
        'total_gramos_contratos_intervenidos',
        'beneficio_fundicion_oro',
        'beneficio_fundicion_plata',
        'total_articulos_enviado_fundicion',
        'total_gramos_enviado_fundicion',
        'importe_cobrado_funcidion',
        'beneficio_fundicion',
        'total_articulos_vendidos',
        'total_coste_art_venta',
        'total_beneficio_ventas',
        'total_ventas',
        'total_euros_ventas',
        'total_gramos_ventas',
        'total_ventas_plazo',
        'total_ventas_plazo_euro',
        'ventas_web',
        'total_euros_ventas_web',
        'ventas_contado',
        'ventas_contado_euros',
        'ventas_tarjeta',
        'ventas_tarjeta_euros',
        'ventas_transferencia',
        'ventas_transferencia_euros',
        'ventas_bizum',
        'ventas_bizum_euros',
        'total_devoluciones',
        'total_euros_devoluciones',
    );

    if (!in_array($columna, $columnasPermitidas, true)) {
        return 0.0;
    }

    $sql = 'SELECT SUM(' . $columna . ') AS total
            FROM informe_semanal
            WHERE mes_semana = ?
              AND year_informe = ?
              AND sucursal_informe = ?';
    $stmt = mysqli_prepare($conexion, $sql);
    if (!$stmt) {
        return 0.0;
    }

    mysqli_stmt_bind_param($stmt, 'isi', $numeroMes, $yearInforme, $sucursalInforme);
    if (!mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        return 0.0;
    }

    $fila = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    return (float) (isset($fila['total']) ? $fila['total'] : 0);
}

/**
 * Recorre informes mensuales abiertos del año en curso.
 *
 * @param string $nombrePaso
 * @param callable $callback
 * @return void
 */
function cron_informe_mensual_recorrer_abiertos($nombrePaso, $callback)
{
    $ctx = cron_informe_mensual_contexto($nombrePaso);
    if (!$ctx) {
        return;
    }

    $stmt = cron_informe_mensual_stmt_abiertos($ctx['conexion'], $ctx['anyo']);
    if (!$stmt) {
        cron_linea('ERROR ' . $nombrePaso . ' preparando consulta de informes.');
        return;
    }

    $resultado = mysqli_stmt_get_result($stmt);
    while ($informe = $resultado ? mysqli_fetch_assoc($resultado) : false) {
        $callback($informe, $ctx['conexion']);
    }

    mysqli_stmt_close($stmt);
}

/**
 * Inserta una acción en acciones_historico_renovaciones desde el cron.
 *
 * @param int $idSucursal
 * @param string $accion_historico_renovacion
 * @param int $id_lote
 * @param int $id_renovaciones
 * @return bool
 */
function insert_accion_historico_renovaciones($idSucursal, $accion_historico_renovacion, $id_lote, $id_renovaciones)
{
    if (cron_solo_vista()) {
        return true;
    }

    $conexion = cron_obtener_conexion();
    if (!$conexion) {
        return false;
    }

    $idSucursal = (int) $idSucursal;
    $accion_historico_renovacion = (string) $accion_historico_renovacion;
    $id_lote = (int) $id_lote;
    $id_renovaciones = (int) $id_renovaciones;

    $sql = "INSERT INTO acciones_historico_renovaciones (
        sucursal,
        accion,
        origen,
        lote_accion,
        historico_id,
        fecha_accion,
        empleado
    ) VALUES (?, ?, 'cron', ?, ?, NOW(), '1')";

    $stmt = mysqli_prepare($conexion, $sql);
    if (!$stmt) {
        return false;
    }

    mysqli_stmt_bind_param(
        $stmt,
        'isii',
        $idSucursal,
        $accion_historico_renovacion,
        $id_lote,
        $id_renovaciones
    );

    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    return (bool) $ok;
}

/**
 * Nombre seguro de la tabla lotes por sucursal: lotes_{id}.
 *
 * @param int $idSucursal
 * @return string|false
 */
function cron_tabla_lotes_sucursal($idSucursal)
{
    $idSucursal = (int) $idSucursal;
    if ($idSucursal <= 0) {
        return false;
    }

    $tabla = 'lotes_' . $idSucursal;

    if (!preg_match('/^lotes_\\d+$/', $tabla)) {
        return false;
    }

    return $tabla;
}

/**
 * Nombre seguro de la tabla historico_renovaciones por sucursal: historico_renovaciones_{id}.
 *
 * @param int $idSucursal
 * @return string|false
 */
function cron_tabla_historico_renovaciones_sucursal($idSucursal)
{
    $idSucursal = (int) $idSucursal;
    if ($idSucursal <= 0) {
        return false;
    }

    $tabla = 'historico_renovaciones_' . $idSucursal;

    if (!preg_match('/^historico_renovaciones_\\d+$/', $tabla)) {
        return false;
    }

    return $tabla;
}

/**
 * Nombre seguro de la tabla movimientos_de_caja por sucursal: movimientos_de_caja_{id}.
 *
 * @param int $idSucursal
 * @return string|false
 */
function cron_tabla_movimientos_caja_sucursal($idSucursal)
{
    $idSucursal = (int) $idSucursal;
    if ($idSucursal <= 0) {
        return false;
    }

    $tabla = 'movimientos_de_caja_' . $idSucursal;

    if (!preg_match('/^movimientos_de_caja_\\d+$/', $tabla)) {
        return false;
    }

    return $tabla;
}

/**
 * Conexion a la base de datos del servicio de envio SMS (matermedia).
 *
 * @return mysqli|null
 */
function cron_conectar_matermedia_sms()
{
    $conexion = @mysqli_connect(
        'mysql-5707.dinaserver.com',
        'sd3ref4df',
        'Soul@7891',
        'goldservicemater'
    );

    if (!$conexion) {
        return null;
    }

    mysqli_set_charset($conexion, 'utf8');

    return $conexion;
}

/**
 * Conexion Manager Quinta Gracia (conexion.php legacy: manager_main_222).
 *
 * @return mysqli|null
 */
function cron_conectar_manager_quinta_gracia()
{
    $conexion = @mysqli_connect(
        'vl24696.dinaserver.com',
        'I438diw23d',
        'Oelw#93id3o',
        'manager_main_222'
    );

    if (!$conexion) {
        return null;
    }

    mysqli_set_charset($conexion, 'utf8');

    return $conexion;
}
