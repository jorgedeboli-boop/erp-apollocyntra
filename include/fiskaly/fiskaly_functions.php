<?php

require_once __DIR__ . '/fiskaly_helpers.php';
require_once __DIR__ . '/FiskalyClient.php';
require_once __DIR__ . '/FiskalyInvoiceBuilder.php';

/**
 * Credenciales Fiskaly de una sucursal/empresa.
 *
 * @return array|null
 */
function fiskalyObtenerCredencialesSucursal($id_sucursal, $id_empresa)
{
    $id_sucursal = (int) $id_sucursal;
    $id_empresa = (int) $id_empresa;
    if ($id_sucursal <= 0 || $id_empresa <= 0) {
        return null;
    }

    $mysqli = obtenerConexionFiskalyPorEmpresa($id_empresa);
    if (!$mysqli || $mysqli->connect_errno) {
        return null;
    }

    $stmt = $mysqli->prepare(
        'SELECT rel_empresa, id_client_fisklaly, rel_firmante
         FROM datos_fiskaly_sucursales WHERE id_sucursal = ? LIMIT 1'
    );
    if (!$stmt) {
        return null;
    }
    $stmt->bind_param('i', $id_sucursal);
    $stmt->execute();
    $res = $stmt->get_result();
    $rowSuc = $res ? $res->fetch_assoc() : null;
    $stmt->close();

    if (!$rowSuc || !isset($rowSuc['rel_empresa'])) {
        return null;
    }

    $rel_empresa = (int) $rowSuc['rel_empresa'];
    $stmtEmp = $mysqli->prepare(
        'SELECT clave_api, secret_clave_api FROM datos_fiskaly_empresas WHERE rel_empresa = ? LIMIT 1'
    );
    if (!$stmtEmp) {
        return null;
    }
    $stmtEmp->bind_param('i', $rel_empresa);
    $stmtEmp->execute();
    $resEmp = $stmtEmp->get_result();
    $rowEmp = $resEmp ? $resEmp->fetch_assoc() : null;
    $stmtEmp->close();

    if (!$rowEmp || empty($rowEmp['clave_api']) || empty($rowEmp['secret_clave_api'])) {
        return null;
    }

    return array(
        'clave_api' => $rowEmp['clave_api'],
        'secret_clave_api' => $rowEmp['secret_clave_api'],
        'rel_empresa' => $rel_empresa,
        'id_client_fiskaly' => isset($rowSuc['id_client_fisklaly']) ? $rowSuc['id_client_fisklaly'] : null,
        'id_firmante' => isset($rowSuc['rel_firmante']) ? $rowSuc['rel_firmante'] : null,
    );
}

/**
 * Comprueba BD y URL Fiskaly según empresas.tipo_api (test | produccion).
 *
 * @return array{ok:bool,motivo:string,tipo_api:string}
 */
function fiskalyEvaluarEntornoPorTipoApi($id_empresa)
{
    $id_empresa = (int) $id_empresa;
    $tipo_api = obtenerTipoApiEmpresa($id_empresa);

    if ($tipo_api === false) {
        return array(
            'ok' => false,
            'motivo' => 'empresa sin tipo_api válido (debe ser test o produccion)',
            'tipo_api' => '',
        );
    }

    if ($tipo_api === 'test') {
        if (!obtenerConexionFiskalyPorEmpresa($id_empresa)) {
            return array(
                'ok' => false,
                'motivo' => 'BD Fiskaly test no disponible (mysqli_fiskalyapp_test)',
                'tipo_api' => 'test',
            );
        }
        if (!obtenerUrlApiFiskalyPorEmpresa($id_empresa)) {
            return array(
                'ok' => false,
                'motivo' => 'URL API Fiskaly test no configurada',
                'tipo_api' => 'test',
            );
        }

        return array('ok' => true, 'motivo' => 'ok', 'tipo_api' => 'test');
    }

    if (!environment_is_production()) {
        return array(
            'ok' => false,
            'motivo' => 'tipo_api produccion pero ENVIRONMENT no es production',
            'tipo_api' => 'produccion',
        );
    }

    if (!obtenerConexionFiskalyPorEmpresa($id_empresa)) {
        return array(
            'ok' => false,
            'motivo' => 'BD Fiskaly producción no disponible (mysqli_fiskalyapp_production)',
            'tipo_api' => 'produccion',
        );
    }

    if (!obtenerUrlApiFiskalyPorEmpresa($id_empresa)) {
        return array(
            'ok' => false,
            'motivo' => 'URL API Fiskaly producción no configurada',
            'tipo_api' => 'produccion',
        );
    }

    return array('ok' => true, 'motivo' => 'ok', 'tipo_api' => 'produccion');
}

/**
 * Evalúa si la sucursal/empresa debe emitir factura Fiskaly.
 *
 * @return array{activo:bool,motivo:string,regimen:string,tipo_api:string}
 */
function fiskalyEvaluarSucursalEmpresa($id_sucursal, $id_empresa)
{
    $id_sucursal = (int) $id_sucursal;
    $id_empresa = (int) $id_empresa;
    $regimen = '';
    $tipo_api = '';

    $evalBase = function ($activo, $motivo) use (&$regimen, &$tipo_api) {
        return array(
            'activo' => $activo,
            'motivo' => $motivo,
            'regimen' => $regimen,
            'tipo_api' => $tipo_api,
        );
    };

    if ($id_sucursal <= 0 || $id_empresa <= 0) {
        return $evalBase(false, 'id_sucursal o id_empresa inválido');
    }

    $factura_digital = obtenerEstadoFacturaDigitalEmpresa($id_empresa);
    if ($factura_digital !== 'true' && $factura_digital !== '1') {
        return $evalBase(false, 'empresa sin factura_digital activa');
    }

    $regimen = obtenerRegimenEmpresa($id_empresa);
    if (!fiskaly_regimen_activo($regimen)) {
        return $evalBase(false, 'régimen empresa no fiscal (' . $regimen . ')');
    }

    $conexion = conectar_bd();
    $stmt = mysqli_prepare(
        $conexion,
        'SELECT 1 FROM rel_regimen_sucursales WHERE rel_id_sucursal = ? LIMIT 1'
    );
    if (!$stmt) {
        mysqli_close($conexion);
        return $evalBase(false, 'error al consultar régimen sucursal');
    }
    mysqli_stmt_bind_param($stmt, 'i', $id_sucursal);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $tiene_regimen_sucursal = ($res && mysqli_fetch_assoc($res));
    mysqli_stmt_close($stmt);

    if (!$tiene_regimen_sucursal) {
        mysqli_close($conexion);
        return $evalBase(false, 'sucursal sin régimen fiscal (rel_regimen_sucursales)');
    }

    $stmtF = mysqli_prepare(
        $conexion,
        'SELECT fecha_inicio_factura_digital FROM empresas WHERE id_empresa = ? LIMIT 1'
    );
    if ($stmtF) {
        mysqli_stmt_bind_param($stmtF, 'i', $id_empresa);
        mysqli_stmt_execute($stmtF);
        $resF = mysqli_stmt_get_result($stmtF);
        $rowF = $resF ? mysqli_fetch_assoc($resF) : null;
        mysqli_stmt_close($stmtF);
        if ($rowF && !empty($rowF['fecha_inicio_factura_digital'])) {
            $fecha_inicio = (string) $rowF['fecha_inicio_factura_digital'];
            if ($fecha_inicio !== '0000-00-00' && $fecha_inicio > date('Y-m-d')) {
                mysqli_close($conexion);
                return $evalBase(false, 'fecha inicio factura digital futura (' . $fecha_inicio . ')');
            }
        }
    }
    mysqli_close($conexion);

    $entorno = fiskalyEvaluarEntornoPorTipoApi($id_empresa);
    $tipo_api = $entorno['tipo_api'];
    if (!$entorno['ok']) {
        return $evalBase(false, $entorno['motivo']);
    }

    $credenciales = fiskalyObtenerCredencialesSucursal($id_sucursal, $id_empresa);
    if (!$credenciales || empty($credenciales['id_client_fiskaly'])) {
        return $evalBase(false, 'sucursal sin id_client Fiskaly en datos_fiskaly_sucursales');
    }

    return $evalBase(true, 'ok');
}

/**
 * @return bool
 */
function debeGenerarFacturaFiskaly($id_sucursal, $id_empresa)
{
    $eval = fiskalyEvaluarSucursalEmpresa($id_sucursal, $id_empresa);

    return !empty($eval['activo']);
}

/**
 * Datos de cliente denormalizados para la cache Fiskaly.
 *
 * @return array
 */
function fiskalyObtenerDatosClienteFactura($id_cliente)
{
    $id_cliente = (int) $id_cliente;
    if ($id_cliente <= 0) {
        throw new Exception('fiskalyObtenerDatosClienteFactura: id_cliente inválido');
    }

    $conexion = conectar_bd();
    $sql = 'SELECT c.id_cliente, c.nombre, c.apellido, c.tipo_identificacion, c.tipo_identificacion_id,
                   c.identificacion, c.nacionalidad,
                   ti.parameter_kisfaly,
                   d.direccion, d.codigo_postal
            FROM clientes c
            LEFT JOIN tipo_identificacion ti ON ti.id_tipo_identificacion = c.tipo_identificacion_id
            LEFT JOIN direcciones d ON d.rel_id_item = c.id_cliente AND d.type_direccion = \'clientes\'
            WHERE c.id_cliente = ?
            LIMIT 1';
    $stmt = mysqli_prepare($conexion, $sql);
    if (!$stmt) {
        mysqli_close($conexion);
        throw new Exception('fiskalyObtenerDatosClienteFactura: error al preparar consulta');
    }
    mysqli_stmt_bind_param($stmt, 'i', $id_cliente);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $row = $res ? mysqli_fetch_assoc($res) : null;
    mysqli_stmt_close($stmt);
    mysqli_close($conexion);

    if (!$row) {
        throw new Exception('fiskalyObtenerDatosClienteFactura: cliente no encontrado');
    }

    $nombre = trim((string) (isset($row['nombre']) ? $row['nombre'] : '') . ' ' . (string) (isset($row['apellido']) ? $row['apellido'] : ''));
    $tipo = fiskaly_resolver_tipo_identificacion_cliente(
        isset($row['tipo_identificacion']) ? $row['tipo_identificacion'] : '',
        isset($row['parameter_kisfaly']) ? $row['parameter_kisfaly'] : ''
    );
    $direccion = trim((string) (isset($row['direccion']) ? $row['direccion'] : ''));
    $cp = trim((string) (isset($row['codigo_postal']) ? $row['codigo_postal'] : ''));

    if ($nombre === '') {
        throw new Exception('fiskalyObtenerDatosClienteFactura: falta nombre del cliente');
    }
    if (trim((string) (isset($row['identificacion']) ? $row['identificacion'] : '')) === '') {
        throw new Exception('fiskalyObtenerDatosClienteFactura: falta identificación fiscal del cliente');
    }
    if ($direccion === '') {
        $direccion = '-';
    }
    if ($cp === '') {
        $cp = '00000';
    }

    return array(
        'rel_cliente_id' => $id_cliente,
        'nombre_cliente' => $nombre,
        'tipo_identificacion_cliente' => $tipo,
        'identificacion_fiscal_cliente' => trim((string) $row['identificacion']),
        'direccion_cliente' => $direccion,
        'codigo_postal_cliente' => $cp,
        'country_code' => fiskaly_country_code_cliente($tipo, isset($row['nacionalidad']) ? $row['nacionalidad'] : ''),
    );
}

/**
 * Texto legal de factura según formato (configuracion_general vía obtenerTextoLegalFactura).
 */
function fiskalyObtenerTextoFacturaEmpresa($id_empresa, $formato_factura)
{
    unset($id_empresa);
    $formato = strtolower(trim((string) $formato_factura));
    if (!in_array($formato, array('articulos', 'oro_inversion', 'renovaciones'), true)) {
        $formato = 'articulos';
    }

    return trim(obtenerTextoLegalFactura($formato));
}

/**
 * Inserta cabecera en facturas_fiskaly_cache.
 *
 * @param array $datos
 * @return int id_factura cache
 */
function crearFacturaFiskaly(array $datos)
{
    if (!array_key_exists('id_sucursal', $datos) || !array_key_exists('rel_id_empresa', $datos)) {
        throw new Exception('crearFacturaFiskaly: faltan id_sucursal o rel_id_empresa');
    }
    if (!array_key_exists('numero_factura', $datos) || !array_key_exists('cliente_factura', $datos)) {
        throw new Exception('crearFacturaFiskaly: faltan numero_factura o cliente_factura');
    }

    $id_sucursal = (int) $datos['id_sucursal'];
    $id_empresa = (int) $datos['rel_id_empresa'];
    $id_cliente = (int) $datos['cliente_factura'];
    $numero_factura = (int) $datos['numero_factura'];
    $facturado_por = (int) (isset($datos['facturado_por']) ? $datos['facturado_por'] : 0);
    $estado_factura = (string) (isset($datos['estado_factura']) ? $datos['estado_factura'] : 'pagada');
    $tipo_pago = fiskaly_mapear_tipo_pago_factura(isset($datos['tipo_pago_factura']) ? $datos['tipo_pago_factura'] : '');
    $total_factura = (float) (isset($datos['total_factura']) ? $datos['total_factura'] : 0);
    $rel_id_venta = (int) (isset($datos['rel_id_venta']) ? $datos['rel_id_venta'] : 0);
    $prefijo = substr((string) (isset($datos['prefijo_factura']) ? $datos['prefijo_factura'] : ''), 0, 10);
    $formato = (string) (isset($datos['tipo_factura']) ? $datos['tipo_factura'] : 'articulos');
    if (!in_array($formato, array('articulos', 'oro_inversion', 'renovaciones'), true)) {
        $formato = 'articulos';
    }

    $mysqli = obtenerConexionFiskalyPorEmpresa($id_empresa);
    if (!$mysqli || $mysqli->connect_errno) {
        throw new Exception('crearFacturaFiskaly: sin conexión BD Fiskaly');
    }

    $cliente = fiskalyObtenerDatosClienteFactura($id_cliente);
    $texto_facturas = fiskalyObtenerTextoFacturaEmpresa($id_empresa, $formato);
    if ($texto_facturas === '') {
        throw new Exception('crearFacturaFiskaly: falta texto_facturas en la empresa');
    }

    $estado_cache = 'pendiente';
    $fecha_anulacion = '0000-00-00 00:00:00';
    $rel_id_lote = 0;
    $rel_id_renovacion = 0;
    $tipo_factura_master = 'COMPLETE';
    if (!empty($datos['tipo_factura_master'])) {
        $tm = strtoupper(trim((string) $datos['tipo_factura_master']));
        if (in_array($tm, array('SIMPLIFIED', 'COMPLETE', 'CORRECTING'), true)) {
            $tipo_factura_master = $tm;
        }
    }

    $sql = 'INSERT INTO facturas_fiskaly_cache (
        id_sucursal, numero_factura, cliente_factura, facturado_por,
        estado_factura, tipo_pago_factura, total_factura,
        fecha_factura, hora_factura,
        formato_factura, rel_id_lote, rel_id_renovacion, rel_id_venta,
        fecha_anulacion, prefijo_factura,
        rel_cliente_id, nombre_cliente, tipo_identificacion_cliente,
        identificacion_fiscal_cliente, direccion_cliente, codigo_postal_cliente,
        country_code, texto_facturas, estado_cache, tipo_factura
    ) VALUES (
        ?, ?, ?, ?,
        ?, ?, ?,
        CURDATE(), CURTIME(),
        ?, ?, ?, ?,
        ?, ?,
        ?, ?, ?,
        ?, ?, ?,
        ?, ?, ?, ?
    )';

    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        throw new Exception('crearFacturaFiskaly: error al preparar INSERT: ' . $mysqli->error);
    }

    $rel_cliente_id = (int) $cliente['rel_cliente_id'];
    $nombre_cliente = (string) $cliente['nombre_cliente'];
    $tipo_identificacion_cliente = (string) $cliente['tipo_identificacion_cliente'];
    $identificacion_fiscal_cliente = (string) $cliente['identificacion_fiscal_cliente'];
    $direccion_cliente = (string) $cliente['direccion_cliente'];
    $codigo_postal_cliente = (string) $cliente['codigo_postal_cliente'];
    $country_code = (string) $cliente['country_code'];

    $stmt->bind_param(
        'iiiissdsiiississsssssss',
        $id_sucursal,
        $numero_factura,
        $id_cliente,
        $facturado_por,
        $estado_factura,
        $tipo_pago,
        $total_factura,
        $formato,
        $rel_id_lote,
        $rel_id_renovacion,
        $rel_id_venta,
        $fecha_anulacion,
        $prefijo,
        $rel_cliente_id,
        $nombre_cliente,
        $tipo_identificacion_cliente,
        $identificacion_fiscal_cliente,
        $direccion_cliente,
        $codigo_postal_cliente,
        $country_code,
        $texto_facturas,
        $estado_cache,
        $tipo_factura_master
    );

    if (!$stmt->execute()) {
        $err = $stmt->error;
        $stmt->close();
        throw new Exception('crearFacturaFiskaly: error al insertar: ' . $err);
    }

    $id = (int) $mysqli->insert_id;
    $stmt->close();

    if ($id <= 0) {
        throw new Exception('crearFacturaFiskaly: no se obtuvo id_factura');
    }

    return $id;
}

/**
 * Inserta cabecera simplificada en facturas_fiskaly_cache (sin datos de cliente).
 *
 * @param array $datos
 * @return int
 */
function crearFacturaFiskalySimplificada(array $datos)
{
    if (!array_key_exists('id_sucursal', $datos) || !array_key_exists('rel_id_empresa', $datos)) {
        throw new Exception('crearFacturaFiskalySimplificada: faltan id_sucursal o rel_id_empresa');
    }
    if (!array_key_exists('numero_factura', $datos)) {
        throw new Exception('crearFacturaFiskalySimplificada: falta numero_factura');
    }

    $id_sucursal = (int) $datos['id_sucursal'];
    $id_empresa = (int) $datos['rel_id_empresa'];
    $id_cliente = 0;
    $numero_factura = (int) $datos['numero_factura'];
    $facturado_por = (int) (isset($datos['facturado_por']) ? $datos['facturado_por'] : 0);
    $estado_factura = (string) (isset($datos['estado_factura']) ? $datos['estado_factura'] : 'pagada');
    $tipo_pago = fiskaly_mapear_tipo_pago_factura(isset($datos['tipo_pago_factura']) ? $datos['tipo_pago_factura'] : '');
    $total_factura = (float) (isset($datos['total_factura']) ? $datos['total_factura'] : 0);
    $rel_id_venta = (int) (isset($datos['rel_id_venta']) ? $datos['rel_id_venta'] : 0);
    $prefijo = substr((string) (isset($datos['prefijo_factura']) ? $datos['prefijo_factura'] : ''), 0, 10);
    $formato = (string) (isset($datos['tipo_factura']) ? $datos['tipo_factura'] : 'articulos');
    if (!in_array($formato, array('articulos', 'oro_inversion', 'renovaciones'), true)) {
        $formato = 'articulos';
    }

    $mysqli = obtenerConexionFiskalyPorEmpresa($id_empresa);
    if (!$mysqli || $mysqli->connect_errno) {
        throw new Exception('crearFacturaFiskalySimplificada: sin conexión BD Fiskaly');
    }

    $texto_facturas = fiskalyObtenerTextoFacturaEmpresa($id_empresa, $formato);
    if ($texto_facturas === '') {
        throw new Exception('crearFacturaFiskalySimplificada: falta texto_facturas en la empresa');
    }

    $estado_cache = 'pendiente';
    $fecha_anulacion = '0000-00-00 00:00:00';
    $rel_id_lote = (int) (isset($datos['rel_id_lote']) ? $datos['rel_id_lote'] : 0);
    $rel_id_renovacion = (int) (isset($datos['rel_id_renovacion']) ? $datos['rel_id_renovacion'] : 0);
    $tipo_factura_master = 'SIMPLIFIED';
    if (!empty($datos['tipo_factura_master'])) {
        $tm = strtoupper(trim((string) $datos['tipo_factura_master']));
        if (in_array($tm, array('SIMPLIFIED', 'COMPLETE', 'CORRECTING'), true)) {
            $tipo_factura_master = $tm;
        }
    }
    $rel_cliente_id = 0;
    $nombre_cliente = '-';
    $tipo_identificacion_cliente = '-';
    $identificacion_fiscal_cliente = '-';
    $direccion_cliente = '-';
    $codigo_postal_cliente = '00000';
    $country_code = 'ES';

    $sql = 'INSERT INTO facturas_fiskaly_cache (
        id_sucursal, numero_factura, cliente_factura, facturado_por,
        estado_factura, tipo_pago_factura, total_factura,
        fecha_factura, hora_factura,
        formato_factura, rel_id_lote, rel_id_renovacion, rel_id_venta,
        fecha_anulacion, prefijo_factura,
        rel_cliente_id, nombre_cliente, tipo_identificacion_cliente,
        identificacion_fiscal_cliente, direccion_cliente, codigo_postal_cliente,
        country_code, texto_facturas, estado_cache, tipo_factura
    ) VALUES (
        ?, ?, ?, ?,
        ?, ?, ?,
        CURDATE(), CURTIME(),
        ?, ?, ?, ?,
        ?, ?,
        ?, ?, ?,
        ?, ?, ?,
        ?, ?, ?, ?
    )';

    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        throw new Exception('crearFacturaFiskalySimplificada: error al preparar INSERT: ' . $mysqli->error);
    }

    $stmt->bind_param(
        'iiiissdsiiississsssssss',
        $id_sucursal,
        $numero_factura,
        $id_cliente,
        $facturado_por,
        $estado_factura,
        $tipo_pago,
        $total_factura,
        $formato,
        $rel_id_lote,
        $rel_id_renovacion,
        $rel_id_venta,
        $fecha_anulacion,
        $prefijo,
        $rel_cliente_id,
        $nombre_cliente,
        $tipo_identificacion_cliente,
        $identificacion_fiscal_cliente,
        $direccion_cliente,
        $codigo_postal_cliente,
        $country_code,
        $texto_facturas,
        $estado_cache,
        $tipo_factura_master
    );

    if (!$stmt->execute()) {
        $err = $stmt->error;
        $stmt->close();
        throw new Exception('crearFacturaFiskalySimplificada: error al insertar: ' . $err);
    }

    $id = (int) $mysqli->insert_id;
    $stmt->close();

    if ($id <= 0) {
        throw new Exception('crearFacturaFiskalySimplificada: no se obtuvo id_factura');
    }

    return $id;
}

/**
 * Inserta líneas en facturas_fiskaly_rel_articulos_cache.
 *
 * @param array $items
 * @return int|int[]
 */
function insertarItemsFacturaFiskaly(array $items)
{
    if (isset($items['rel_factura_id_fiskaly'])) {
        $items = array($items);
    }
    if (!is_array($items) || $items === array()) {
        throw new Exception('insertarItemsFacturaFiskaly: no hay líneas');
    }

    $defaults = array(
        'rel_factura_id_fiskaly' => 0,
        'id_rel_sucursal' => 0,
        'id_sucursal' => 0,
        'sucursal_venta' => 0,
        'rel_id_item' => 0,
        'id_rel_articulo' => 0,
        'descripcion_articulo_rel' => '',
        'precio_unitario' => 0.0,
        'total_linea' => 0.0,
        'precio_coste_articulo' => 0.0,
        'tipo_iva_articulo' => 'IVA',
        'system_codigo_regimen' => 'GENERAL',
        'rel_id_factura' => 0,
        'id_rel_factura' => 0,
        'rel_id_empresa' => 0,
    );

    $id_empresa = 0;
    foreach ($items as $fila) {
        if (is_array($fila) && !empty($fila['rel_id_empresa'])) {
            $id_empresa = (int) $fila['rel_id_empresa'];
            break;
        }
    }
    if ($id_empresa <= 0 && is_array($items[0]) && isset($items[0]['rel_factura_id_fiskaly'])) {
        throw new Exception('insertarItemsFacturaFiskaly: falta rel_id_empresa');
    }

    $mysqli = obtenerConexionFiskalyPorEmpresa($id_empresa);
    if (!$mysqli || $mysqli->connect_errno) {
        throw new Exception('insertarItemsFacturaFiskaly: sin conexión BD Fiskaly');
    }

    $sql = 'INSERT INTO facturas_fiskaly_rel_articulos_cache (
        id_rel_sucursal, id_rel_factura, id_rel_articulo, fecha_factura,
        precio_rel_articulo, precio_coste_articulo, descripcion_articulo_rel,
        rel_factura_id_fiskaly, tipo_iva_articulo, system_codigo_regimen,
        beneficio_articulo, tax_base, precio_venta_sin_iva
    ) VALUES (?, ?, ?, NOW(), ?, ?, ?, ?, ?, ?, ?, ?, ?)';

    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        throw new Exception('insertarItemsFacturaFiskaly: error al preparar INSERT: ' . $mysqli->error);
    }

    $ids = array();

    foreach ($items as $fila) {
        if (!is_array($fila) || empty($fila['rel_factura_id_fiskaly'])) {
            $stmt->close();
            throw new Exception('insertarItemsFacturaFiskaly: falta rel_factura_id_fiskaly');
        }

        $d = array_merge($defaults, $fila);
        $id_rel_sucursal = (int) ($d['id_rel_sucursal'] ?: $d['id_sucursal'] ?: $d['sucursal_venta']);
        $id_rel_factura = (int) ($d['rel_id_factura'] ?: $d['id_rel_factura']);
        $id_rel_articulo = (int) ($d['id_rel_articulo'] ?: $d['rel_id_item']);
        $rel_factura_id_fiskaly = (int) $d['rel_factura_id_fiskaly'];
        if ($id_rel_factura <= 0) {
            $stmt->close();
            throw new Exception('insertarItemsFacturaFiskaly: falta rel_id_factura (id factura TPV)');
        }
        $precio = (float) ($d['total_linea'] ?: $d['precio_unitario']);
        $coste = (float) $d['precio_coste_articulo'];
        $desc = trim((string) $d['descripcion_articulo_rel']);
        if ($desc === '') {
            $desc = 'Artículo #' . $id_rel_articulo;
        }

        $regimen = fiskaly_normalizar_regimen_enum($d['system_codigo_regimen']);
        $fiscal = fiskaly_calcular_linea_fiscal($precio, $regimen, $d['tipo_iva_articulo']);
        if ($regimen === 'REBU' && isset($fiscal['beneficio_articulo'], $fiscal['precio_coste_articulo'])) {
            $beneficio = (float) $fiscal['beneficio_articulo'];
            $coste = (float) $fiscal['precio_coste_articulo'];
            fiskalyActualizarPrecioCosteCalculadoArticulo($id_rel_articulo, $coste);
        } else {
            $beneficio = round($precio - $coste, 2);
        }

        $tipo_iva = strtoupper(trim((string) $d['tipo_iva_articulo']));
        if (!in_array($tipo_iva, array('IVA', 'IPSI', 'IGIC', 'OTHER'), true)) {
            $tipo_iva = 'IVA';
        }
        $tax_base = (float) $fiscal['tax_base'];
        $precio_sin_iva = (float) $fiscal['precio_sin_iva'];

        $stmt->bind_param(
            'iiiddsissddd',
            $id_rel_sucursal,
            $id_rel_factura,
            $id_rel_articulo,
            $precio,
            $coste,
            $desc,
            $rel_factura_id_fiskaly,
            $tipo_iva,
            $regimen,
            $beneficio,
            $tax_base,
            $precio_sin_iva
        );

        if (!$stmt->execute()) {
            $err = $stmt->error;
            $stmt->close();
            throw new Exception('insertarItemsFacturaFiskaly: error al insertar: ' . $err);
        }

        if (!empty($fiscal['formato_factura'])) {
            fiskalyActualizarFormatoFacturaCache(
                $rel_factura_id_fiskaly,
                $id_empresa,
                $fiscal['formato_factura']
            );
        }

        $ids[] = (int) $mysqli->insert_id;
    }

    $stmt->close();

    return count($ids) === 1 ? $ids[0] : $ids;
}

/**
 * Inserta líneas de renovación en facturas_fiskaly_rel_renovaciones_cache.
 * IVA 21 % incluido (régimen GENERAL).
 *
 * @param array $items
 * @return int|int[]
 */
function insertarItemsRenovacionesFacturaFiskaly(array $items)
{
    if (isset($items['rel_factura_id_fiskaly'])) {
        $items = array($items);
    }
    if (!is_array($items) || $items === array()) {
        throw new Exception('insertarItemsRenovacionesFacturaFiskaly: no hay líneas');
    }

    $defaults = array(
        'rel_factura_id_fiskaly' => 0,
        'id_rel_sucursal' => 0,
        'id_sucursal' => 0,
        'sucursal_venta' => 0,
        'rel_id_item' => 0,
        'id_rel_renovacion' => 0,
        'descripcion_renovacion' => '',
        'precio_rel_renovacion' => 0.0,
        'total_linea' => 0.0,
        'rel_id_factura' => 0,
        'id_rel_factura' => 0,
        'rel_id_empresa' => 0,
    );

    $id_empresa = 0;
    foreach ($items as $fila) {
        if (is_array($fila) && !empty($fila['rel_id_empresa'])) {
            $id_empresa = (int) $fila['rel_id_empresa'];
            break;
        }
    }
    if ($id_empresa <= 0) {
        throw new Exception('insertarItemsRenovacionesFacturaFiskaly: falta rel_id_empresa');
    }

    $mysqli = obtenerConexionFiskalyPorEmpresa($id_empresa);
    if (!$mysqli || $mysqli->connect_errno) {
        throw new Exception('insertarItemsRenovacionesFacturaFiskaly: sin conexión BD Fiskaly');
    }

    $sql = 'INSERT INTO facturas_fiskaly_rel_renovaciones_cache (
        id_rel_sucursal, id_rel_factura, id_rel_renovacion, fecha_factura,
        precio_rel_renovacion, descripcion_renovacion_rel,
        rel_factura_id_fiskaly, tipo_iva_articulo, system_codigo_regimen,
        tax_base, precio_venta_sin_iva
    ) VALUES (?, ?, ?, NOW(), ?, ?, ?, ?, ?, ?, ?)';

    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        throw new Exception('insertarItemsRenovacionesFacturaFiskaly: error al preparar INSERT: ' . $mysqli->error);
    }

    $ids = array();
    $regimen = 'GENERAL';
    $tipo_iva = 'IVA';

    foreach ($items as $fila) {
        if (!is_array($fila) || empty($fila['rel_factura_id_fiskaly'])) {
            $stmt->close();
            throw new Exception('insertarItemsRenovacionesFacturaFiskaly: falta rel_factura_id_fiskaly');
        }

        $d = array_merge($defaults, $fila);
        $id_rel_sucursal = (int) ($d['id_rel_sucursal'] ?: $d['id_sucursal'] ?: $d['sucursal_venta']);
        $id_rel_factura = (int) ($d['rel_id_factura'] ?: $d['id_rel_factura']);
        $id_rel_renovacion = (int) ($d['id_rel_renovacion'] ?: $d['rel_id_item']);
        $rel_factura_id_fiskaly = (int) $d['rel_factura_id_fiskaly'];
        if ($id_rel_factura <= 0 || $id_rel_renovacion <= 0) {
            $stmt->close();
            throw new Exception('insertarItemsRenovacionesFacturaFiskaly: falta rel_id_factura o id_rel_renovacion');
        }

        $precio = (float) ($d['precio_rel_renovacion'] ?: $d['total_linea']);
        if ($precio <= 0.0) {
            $stmt->close();
            throw new Exception('insertarItemsRenovacionesFacturaFiskaly: precio_rel_renovacion debe ser > 0');
        }

        $desc = trim((string) $d['descripcion_renovacion']);
        if ($desc === '') {
            $desc = 'Renovación #' . $id_rel_renovacion;
        }

        $precio_sin_iva = round($precio / 1.21, 2);
        $tax_base = 0.0;

        $stmt->bind_param(
            'iiidsissdd',
            $id_rel_sucursal,
            $id_rel_factura,
            $id_rel_renovacion,
            $precio,
            $desc,
            $rel_factura_id_fiskaly,
            $tipo_iva,
            $regimen,
            $tax_base,
            $precio_sin_iva
        );

        if (!$stmt->execute()) {
            $err = $stmt->error;
            $stmt->close();
            throw new Exception('insertarItemsRenovacionesFacturaFiskaly: error al insertar: ' . $err);
        }

        $ids[] = (int) $mysqli->insert_id;
    }

    $stmt->close();

    return count($ids) === 1 ? $ids[0] : $ids;
}

/**
 * Cache Fiskaly + envío API para factura simplificada de renovaciones (post-TPV).
 *
 * @param array $datos
 * @return array|null null si Fiskaly no está activo
 */
function fiskalyIntegrarFacturaSimplificadaRenovaciones(array $datos)
{
    $contexto = trim((string) (isset($datos['contexto_log']) ? $datos['contexto_log'] : 'renovaciones'));
    $id_sucursal = (int) (isset($datos['id_sucursal']) ? $datos['id_sucursal'] : 0);
    $rel_id_empresa = (int) (isset($datos['rel_id_empresa']) ? $datos['rel_id_empresa'] : 0);
    $id_factura_simplificada = (int) (isset($datos['id_factura_simplificada']) ? $datos['id_factura_simplificada'] : 0);

    if ($id_sucursal <= 0 || $rel_id_empresa <= 0 || $id_factura_simplificada <= 0) {
        insertErrorLog($contexto . ': Fiskaly renovaciones omitido (parámetros inválidos)');

        return null;
    }

    $fiskaly_eval = fiskalyEvaluarSucursalEmpresa($id_sucursal, $rel_id_empresa);
    if (empty($fiskaly_eval['activo'])) {
        insertErrorLog(
            $contexto . ': Fiskaly omitido (sucursal ' . $id_sucursal . ', empresa ' . $rel_id_empresa
            . ', tipo_api ' . ($fiskaly_eval['tipo_api'] !== '' ? $fiskaly_eval['tipo_api'] : 'n/a') . '): '
            . $fiskaly_eval['motivo']
        );

        return null;
    }

    $regimen_empresa = $fiskaly_eval['regimen'] !== ''
        ? $fiskaly_eval['regimen']
        : obtenerRegimenEmpresa($rel_id_empresa);

    try {
        $id_factura_fiskaly = crearFacturaFiskalySimplificada(
            array(
                'id_sucursal' => $id_sucursal,
                'numero_factura' => (int) (isset($datos['numero_factura']) ? $datos['numero_factura'] : 0),
                'facturado_por' => (int) (isset($datos['facturado_por']) ? $datos['facturado_por'] : 0),
                'estado_factura' => (string) (isset($datos['estado_factura']) ? $datos['estado_factura'] : 'pagada'),
                'tipo_pago_factura' => isset($datos['tipo_pago_factura']) ? $datos['tipo_pago_factura'] : '',
                'total_factura' => (float) (isset($datos['total_factura']) ? $datos['total_factura'] : 0),
                'rel_id_venta' => 0,
                'prefijo_factura' => isset($datos['prefijo_factura']) ? $datos['prefijo_factura'] : '',
                'tipo_factura' => 'renovaciones',
                'rel_id_lote' => (int) (isset($datos['rel_id_lote']) ? $datos['rel_id_lote'] : 0),
                'rel_id_renovacion' => (int) (isset($datos['rel_id_renovacion']) ? $datos['rel_id_renovacion'] : 0),
                'rel_id_empresa' => $rel_id_empresa,
                'factura_regimen' => $regimen_empresa,
            )
        );

        fiskalyVincularFacturaSimplificadaTpv($id_factura_simplificada, $id_factura_fiskaly);

        insertarItemsRenovacionesFacturaFiskaly(
            array(
                'rel_factura_id_fiskaly' => $id_factura_fiskaly,
                'rel_id_factura' => $id_factura_simplificada,
                'id_rel_sucursal' => $id_sucursal,
                'id_rel_renovacion' => (int) (isset($datos['rel_id_renovacion']) ? $datos['rel_id_renovacion'] : 0),
                'descripcion_renovacion' => isset($datos['descripcion_renovacion']) ? $datos['descripcion_renovacion'] : '',
                'precio_rel_renovacion' => (float) (isset($datos['precio_rel_renovacion']) ? $datos['precio_rel_renovacion'] : 0),
                'rel_id_empresa' => $rel_id_empresa,
            )
        );

        try {
            return enviarFacturaFiskaly($id_factura_fiskaly, $rel_id_empresa, $id_sucursal);
        } catch (Throwable $exFiskaly) {
            insertErrorLog($contexto . ': envío Fiskaly simplificada renovaciones no completado: ' . $exFiskaly->getMessage());

            return array(
                'success' => false,
                'estado_cache' => 'error',
                'message' => $exFiskaly->getMessage(),
            );
        }
    } catch (Throwable $exCache) {
        insertErrorLog($contexto . ': factura Fiskaly simplificada renovaciones no creada: ' . $exCache->getMessage());

        return array(
            'success' => false,
            'estado_cache' => 'error',
            'message' => $exCache->getMessage(),
        );
    }
}

/**
 * Enlaza factura TPV con registro Fiskaly cache.
 */
function fiskalyVincularFacturaTpv($id_factura_tpv, $id_factura_fiskaly)
{
    $id_factura_tpv = (int) $id_factura_tpv;
    $id_factura_fiskaly = (int) $id_factura_fiskaly;
    if ($id_factura_tpv <= 0 || $id_factura_fiskaly <= 0) {
        return false;
    }

    $conexion = conectar_bd();
    $stmt = mysqli_prepare(
        $conexion,
        'UPDATE facturas SET id_rel_factura_fiskaly = ? WHERE id_factura = ? LIMIT 1'
    );
    if (!$stmt) {
        mysqli_close($conexion);
        return false;
    }
    mysqli_stmt_bind_param($stmt, 'ii', $id_factura_fiskaly, $id_factura_tpv);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    mysqli_close($conexion);

    return (bool) $ok;
}

/**
 * Enlaza factura rectificativa TPV con registro Fiskaly cache.
 *
 * @param bool $simplificada true → facturas_rectificativas_simplificadas
 */
function fiskalyVincularFacturaRectificativaTpv($id_factura_rectificativa_tpv, $id_factura_fiskaly, $simplificada = false)
{
    $id_factura_rectificativa_tpv = (int) $id_factura_rectificativa_tpv;
    $id_factura_fiskaly = (int) $id_factura_fiskaly;
    if ($id_factura_rectificativa_tpv <= 0 || $id_factura_fiskaly <= 0) {
        return false;
    }

    $tabla = $simplificada ? 'facturas_rectificativas_simplificadas' : 'facturas_rectificativas';
    $conexion = conectar_bd();
    $stmt = mysqli_prepare(
        $conexion,
        "UPDATE {$tabla} SET id_rel_factura_fiskaly = ? WHERE id_factura = ? LIMIT 1"
    );
    if (!$stmt) {
        mysqli_close($conexion);
        return false;
    }
    mysqli_stmt_bind_param($stmt, 'ii', $id_factura_fiskaly, $id_factura_rectificativa_tpv);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    mysqli_close($conexion);

    return (bool) $ok;
}

/**
 * Enlaza factura simplificada TPV con registro Fiskaly cache.
 */
function fiskalyVincularFacturaSimplificadaTpv($id_factura_simplificada_tpv, $id_factura_fiskaly)
{
    // Las simplificadas nuevas viven en `facturas`; reutilizamos el vínculo de factura completa.
    return fiskalyVincularFacturaTpv($id_factura_simplificada_tpv, $id_factura_fiskaly);
}

/**
 * Lee factura + líneas de la cache Fiskaly.
 *
 * @return array{factura:array,articulos:array}
 */
function fiskalyLeerFacturaCache($id_factura_fiskaly, $id_empresa)
{
    $id_factura_fiskaly = (int) $id_factura_fiskaly;
    $id_empresa = (int) $id_empresa;
    $mysqli = obtenerConexionFiskalyPorEmpresa($id_empresa);
    if (!$mysqli || $mysqli->connect_errno) {
        throw new Exception('fiskalyLeerFacturaCache: sin conexión BD Fiskaly');
    }

    $stmt = $mysqli->prepare('SELECT * FROM facturas_fiskaly_cache WHERE id_factura = ? LIMIT 1');
    if (!$stmt) {
        throw new Exception('fiskalyLeerFacturaCache: error al preparar SELECT factura');
    }
    $stmt->bind_param('i', $id_factura_fiskaly);
    $stmt->execute();
    $res = $stmt->get_result();
    $factura = $res ? $res->fetch_assoc() : null;
    $stmt->close();

    if (!$factura) {
        throw new Exception('fiskalyLeerFacturaCache: factura no encontrada');
    }

    $formato = isset($factura['formato_factura']) ? strtolower(trim((string) $factura['formato_factura'])) : 'articulos';
    if ($formato === 'renovaciones') {
        $stmtA = $mysqli->prepare(
            'SELECT descripcion_renovacion_rel AS descripcion_articulo_rel,
                    precio_rel_renovacion AS precio_rel_articulo,
                    precio_venta_sin_iva, tax_base,
                    tipo_iva_articulo, system_codigo_regimen
             FROM facturas_fiskaly_rel_renovaciones_cache
             WHERE rel_factura_id_fiskaly = ?'
        );
    } else {
        $stmtA = $mysqli->prepare(
            'SELECT descripcion_articulo_rel, precio_rel_articulo, precio_venta_sin_iva, tax_base,
                    tipo_iva_articulo, system_codigo_regimen
             FROM facturas_fiskaly_rel_articulos_cache
             WHERE rel_factura_id_fiskaly = ?'
        );
    }
    if (!$stmtA) {
        throw new Exception('fiskalyLeerFacturaCache: error al preparar SELECT líneas');
    }
    $stmtA->bind_param('i', $id_factura_fiskaly);
    $stmtA->execute();
    $resA = $stmtA->get_result();
    $articulos = array();
    if ($resA) {
        while ($row = $resA->fetch_assoc()) {
            $articulos[] = $row;
        }
    }
    $stmtA->close();

    return array(
        'factura' => $factura,
        'articulos' => $articulos,
    );
}

/**
 * Persiste respuesta SIGN ES en cache.
 *
 * @param array $fiskalyResponse
 * @return bool
 */
function actualizarEstadoFacturaFiskaly(array $fiskalyResponse, $id_factura_fiskaly, $id_empresa, $tipo_factura_master)
{
    $id_factura_fiskaly = (int) $id_factura_fiskaly;
    $id_empresa = (int) $id_empresa;
    if ($id_factura_fiskaly <= 0 || $id_empresa <= 0) {
        return false;
    }

    $mysqli = obtenerConexionFiskalyPorEmpresa($id_empresa);
    if (!$mysqli || $mysqli->connect_errno) {
        return false;
    }

    $content = isset($fiskalyResponse['content']) && is_array($fiskalyResponse['content']) ? $fiskalyResponse['content'] : array();
    $transmission = isset($content['transmission']) && is_array($content['transmission']) ? $content['transmission'] : array();
    $compliance = isset($content['compliance']) && is_array($content['compliance']) ? $content['compliance'] : array();
    $code = isset($compliance['code']) && is_array($compliance['code']) ? $compliance['code'] : array();
    $image = isset($code['image']) && is_array($code['image']) ? $code['image'] : array();

    $invoice_id = isset($content['id']) ? (string) $content['id'] : null;
    $invoice_state = isset($content['state']) ? (string) $content['state'] : null;
    $registration = isset($transmission['registration']) ? (string) $transmission['registration'] : null;
    $registration_csv = isset($transmission['registration_csv']) ? (string) $transmission['registration_csv'] : null;
    $cancellation = isset($transmission['cancellation']) ? (string) $transmission['cancellation'] : null;
    $url_validacion = isset($compliance['url']) ? trim((string) $compliance['url']) : '';
    $imagen_qr = isset($image['data']) ? (string) $image['data'] : null;

    // TicketBAI: compliance.tbai. Verifactu: compliance.text o VERI*FACTU si la URL es de AEAT.
    $tbai = isset($compliance['tbai']) ? trim((string) $compliance['tbai']) : '';
    if ($tbai === '' && isset($compliance['text'])) {
        $tbai = trim((string) $compliance['text']);
    }
    if ($tbai === '' && $url_validacion !== '') {
        $urlLower = strtolower($url_validacion);
        if (strpos($urlLower, 'validarqr') !== false
            || strpos($urlLower, 'agenciatributaria') !== false
            || strpos($urlLower, 'aeat.es') !== false
        ) {
            $tbai = 'VERI*FACTU';
        }
    }
    if ($tbai === '') {
        $tbai = null;
    }
    if ($url_validacion === '') {
        $url_validacion = null;
    }
    $estado_cache = fiskaly_estado_cache_desde_respuesta($invoice_state, $registration);
    $tipo_master = (string) $tipo_factura_master;

    $sql = 'UPDATE facturas_fiskaly_cache SET
        invoice_id_fiskaly = ?,
        InvoiceState = ?,
        SignedInvoiceRegistrationState = ?,
        registration_csv = ?,
        SignedInvoiceCancellationState = ?,
        tbai = ?,
        url_validacion = ?,
        imagen_codigo_qr = ?,
        estado_cache = ?,
        tipo_factura = ?
        WHERE id_factura = ?';

    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        return false;
    }

    $stmt->bind_param(
        'ssssssssssi',
        $invoice_id,
        $invoice_state,
        $registration,
        $registration_csv,
        $cancellation,
        $tbai,
        $url_validacion,
        $imagen_qr,
        $estado_cache,
        $tipo_master,
        $id_factura_fiskaly
    );
    $ok = $stmt->execute();
    $stmt->close();

    return (bool) $ok;
}

/**
 * Envía factura desde cache Fiskaly a SIGN ES.
 *
 * @param array $opciones corrected_invoice_id, correction_method, correction_code, correction_data_type
 * @return array
 */
function enviarFacturaFiskaly($id_factura_fiskaly, $id_empresa, $id_sucursal, array $opciones = array())
{
    $id_factura_fiskaly = (int) $id_factura_fiskaly;
    $id_empresa = (int) $id_empresa;
    $id_sucursal = (int) $id_sucursal;

    if ($id_factura_fiskaly <= 0 || $id_empresa <= 0 || $id_sucursal <= 0) {
        throw new Exception('enviarFacturaFiskaly: parámetros inválidos');
    }

    $urlApi = obtenerUrlApiFiskalyPorEmpresa($id_empresa);
    if (!$urlApi) {
        throw new Exception('enviarFacturaFiskaly: URL API Fiskaly no configurada');
    }

    $credenciales = fiskalyObtenerCredencialesSucursal($id_sucursal, $id_empresa);
    if (!$credenciales || empty($credenciales['id_client_fiskaly'])) {
        throw new Exception('enviarFacturaFiskaly: credenciales o id_client Fiskaly no configurados');
    }

    $cache = fiskalyLeerFacturaCache($id_factura_fiskaly, $id_empresa);
    $factura = $cache['factura'];
    $regimen_empresa = obtenerRegimenEmpresa($id_empresa);
    $factura['_fiskaly_texto_max_bytes'] = fiskaly_max_bytes_texto_por_regimen($regimen_empresa);

    if (!empty($opciones['corrected_invoice_id'])) {
        $factura['_fiskaly_corrected_invoice_id'] = (string) $opciones['corrected_invoice_id'];
    }
    if (!empty($opciones['correction_method'])) {
        $factura['_fiskaly_correction_method'] = (string) $opciones['correction_method'];
    }
    if (!empty($opciones['correction_code'])) {
        $factura['_fiskaly_correction_code'] = (string) $opciones['correction_code'];
    }
    if (!empty($opciones['correction_data_type'])) {
        $factura['_fiskaly_correction_data_type'] = (string) $opciones['correction_data_type'];
    }

    $articulosPayload = fiskaly_articulos_cache_a_payload($cache['articulos']);
    if ($articulosPayload === array()) {
        throw new Exception('enviarFacturaFiskaly: la factura no tiene líneas');
    }

    $jsonBody = FiskalyInvoiceBuilder::build($factura, $articulosPayload);
    $uuid = generarUUIDv4();
    $idClient = (string) $credenciales['id_client_fiskaly'];

    $mysqli = obtenerConexionFiskalyPorEmpresa($id_empresa);
    if ($mysqli && !$mysqli->connect_errno) {
        $stmtUp = $mysqli->prepare(
            'UPDATE facturas_fiskaly_cache SET client_id_fiskaly = ?, invoice_id_fiskaly = ? WHERE id_factura = ?'
        );
        if ($stmtUp) {
            $stmtUp->bind_param('ssi', $idClient, $uuid, $id_factura_fiskaly);
            $stmtUp->execute();
            $stmtUp->close();
        }
    }

    try {
        $client = new FiskalyClient($urlApi);
        $client->autenticar($credenciales['clave_api'], $credenciales['secret_clave_api']);
        $respuesta = $client->enviarFactura($idClient, $uuid, $jsonBody);

        $registration = null;
        if (isset($respuesta['content']['transmission']['registration'])) {
            $registration = $respuesta['content']['transmission']['registration'];
        }

        if ($registration === 'PENDING') {
            $invoiceId = isset($respuesta['content']['id']) ? $respuesta['content']['id'] : $uuid;
            $respuesta = $client->esperarRegistroFactura($idClient, $invoiceId, 20, 5);
        }

        $tipo_master = isset($factura['tipo_factura']) ? $factura['tipo_factura'] : 'COMPLETE';
        actualizarEstadoFacturaFiskaly($respuesta, $id_factura_fiskaly, $id_empresa, $tipo_master);

        $estado_cache = fiskaly_estado_cache_desde_respuesta(
            isset($respuesta['content']['state']) ? $respuesta['content']['state'] : null,
            isset($respuesta['content']['transmission']['registration']) ? $respuesta['content']['transmission']['registration'] : null
        );

        return array(
            'success' => $estado_cache === 'aceptada' || $estado_cache === 'pendiente',
            'estado_cache' => $estado_cache,
            'tbai' => isset($respuesta['content']['compliance']['tbai']) ? $respuesta['content']['compliance']['tbai'] : null,
            'invoice_id_fiskaly' => isset($respuesta['content']['id']) ? $respuesta['content']['id'] : $uuid,
            'respuesta' => $respuesta,
        );
    } catch (Throwable $exEnvio) {
        if ($mysqli && !$mysqli->connect_errno) {
            $estadoError = 'error';
            $stmtErr = $mysqli->prepare(
                'UPDATE facturas_fiskaly_cache SET estado_cache = ? WHERE id_factura = ?'
            );
            if ($stmtErr) {
                $stmtErr->bind_param('si', $estadoError, $id_factura_fiskaly);
                $stmtErr->execute();
                $stmtErr->close();
            }
        }
        throw $exEnvio;
    }
}

/**
 * Integra factura rectificativa de renovación en Fiskaly (CORRECTING / DIFFERENCES).
 * Solo si la factura original tenía vínculo Fiskaly y la sucursal/empresa están activos.
 *
 * @return array|null
 */
function fiskalyIntegrarFacturaRectificativaRenovaciones(array $datos)
{
    $contexto = trim((string) (isset($datos['contexto_log']) ? $datos['contexto_log'] : 'rectificativa_renovaciones'));
    $id_sucursal = (int) (isset($datos['id_sucursal']) ? $datos['id_sucursal'] : 0);
    $rel_id_empresa = (int) (isset($datos['rel_id_empresa']) ? $datos['rel_id_empresa'] : 0);
    $id_factura_rectificativa = (int) (isset($datos['id_factura_rectificativa']) ? $datos['id_factura_rectificativa'] : 0);
    $id_factura_original_fiskaly = (int) (isset($datos['id_factura_original_fiskaly']) ? $datos['id_factura_original_fiskaly'] : 0);
    $simplificada_historico = !empty($datos['simplificada_historico']);

    if ($id_sucursal <= 0 || $rel_id_empresa <= 0 || $id_factura_rectificativa <= 0 || $id_factura_original_fiskaly <= 0) {
        insertErrorLog($contexto . ': Fiskaly CORRECTING omitido (parámetros inválidos)');

        return null;
    }

    $fiskaly_eval = fiskalyEvaluarSucursalEmpresa($id_sucursal, $rel_id_empresa);
    if (empty($fiskaly_eval['activo'])) {
        insertErrorLog(
            $contexto . ': Fiskaly CORRECTING omitido (sucursal ' . $id_sucursal . ', empresa ' . $rel_id_empresa
            . '): ' . $fiskaly_eval['motivo']
        );

        return null;
    }

    try {
        $cacheOriginal = fiskalyLeerFacturaCache($id_factura_original_fiskaly, $rel_id_empresa);
        $uuidOriginal = trim((string) (isset($cacheOriginal['factura']['invoice_id_fiskaly']) ? $cacheOriginal['factura']['invoice_id_fiskaly'] : ''));
        if ($uuidOriginal === '') {
            throw new Exception('La factura Fiskaly original no tiene invoice_id_fiskaly (UUID)');
        }

        $regimen_empresa = $fiskaly_eval['regimen'] !== ''
            ? $fiskaly_eval['regimen']
            : obtenerRegimenEmpresa($rel_id_empresa);

        $importeAbs = abs((float) (isset($datos['total_factura']) ? $datos['total_factura'] : 0));
        $id_factura_fiskaly = crearFacturaFiskalySimplificada(
            array(
                'id_sucursal' => $id_sucursal,
                'numero_factura' => (int) (isset($datos['numero_factura']) ? $datos['numero_factura'] : 0),
                'facturado_por' => (int) (isset($datos['facturado_por']) ? $datos['facturado_por'] : 0),
                'estado_factura' => 'pagada',
                'tipo_pago_factura' => isset($datos['tipo_pago_factura']) ? $datos['tipo_pago_factura'] : '',
                'total_factura' => $importeAbs,
                'rel_id_venta' => 0,
                'prefijo_factura' => isset($datos['prefijo_factura']) ? $datos['prefijo_factura'] : '',
                'tipo_factura' => 'renovaciones',
                'tipo_factura_master' => 'CORRECTING',
                'rel_id_lote' => (int) (isset($datos['rel_id_lote']) ? $datos['rel_id_lote'] : 0),
                'rel_id_renovacion' => (int) (isset($datos['rel_id_renovacion']) ? $datos['rel_id_renovacion'] : 0),
                'rel_id_empresa' => $rel_id_empresa,
                'factura_regimen' => $regimen_empresa,
            )
        );

        fiskalyVincularFacturaRectificativaTpv($id_factura_rectificativa, $id_factura_fiskaly, $simplificada_historico);

        $desc = isset($datos['descripcion_renovacion']) ? (string) $datos['descripcion_renovacion'] : '';
        if ($desc === '') {
            $desc = 'Rectificación renovación #' . (int) (isset($datos['rel_id_renovacion']) ? $datos['rel_id_renovacion'] : 0);
        }

        insertarItemsRenovacionesFacturaFiskaly(
            array(
                'rel_factura_id_fiskaly' => $id_factura_fiskaly,
                'rel_id_factura' => $id_factura_rectificativa,
                'id_rel_sucursal' => $id_sucursal,
                'id_rel_renovacion' => (int) (isset($datos['rel_id_renovacion']) ? $datos['rel_id_renovacion'] : 0),
                'descripcion_renovacion' => $desc,
                'precio_rel_renovacion' => $importeAbs,
                'rel_id_empresa' => $rel_id_empresa,
            )
        );

        try {
            $envio = enviarFacturaFiskaly(
                $id_factura_fiskaly,
                $rel_id_empresa,
                $id_sucursal,
                array(
                    'corrected_invoice_id' => $uuidOriginal,
                    'correction_method' => 'DIFFERENCES',
                    'correction_code' => 'CORRECTION_1',
                    'correction_data_type' => 'SIMPLIFIED',
                )
            );
            $envio['id_factura_fiskaly'] = $id_factura_fiskaly;

            return $envio;
        } catch (Throwable $exFiskaly) {
            insertErrorLog($contexto . ': envío Fiskaly CORRECTING renovaciones no completado: ' . $exFiskaly->getMessage());

            return array(
                'success' => false,
                'estado_cache' => 'error',
                'message' => $exFiskaly->getMessage(),
                'id_factura_fiskaly' => $id_factura_fiskaly,
            );
        }
    } catch (Throwable $exCache) {
        insertErrorLog($contexto . ': factura Fiskaly CORRECTING renovaciones no creada: ' . $exCache->getMessage());

        return array(
            'success' => false,
            'estado_cache' => 'error',
            'message' => $exCache->getMessage(),
        );
    }
}

/**
 * Integra factura rectificativa de devolución de artículos en Fiskaly (CORRECTING / DIFFERENCES).
 *
 * @param array $datos
 * @return array|null
 */
function fiskalyIntegrarFacturaRectificativaArticulos(array $datos)
{
    $contexto = trim((string) (isset($datos['contexto_log']) ? $datos['contexto_log'] : 'rectificativa_articulos'));
    $id_sucursal = (int) (isset($datos['id_sucursal']) ? $datos['id_sucursal'] : 0);
    $rel_id_empresa = (int) (isset($datos['rel_id_empresa']) ? $datos['rel_id_empresa'] : 0);
    $id_factura_rectificativa = (int) (isset($datos['id_factura_rectificativa']) ? $datos['id_factura_rectificativa'] : 0);
    $id_factura_original_fiskaly = (int) (isset($datos['id_factura_original_fiskaly']) ? $datos['id_factura_original_fiskaly'] : 0);
    $simplificada_historico = !empty($datos['simplificada_historico']);
    $id_cliente = (int) (isset($datos['cliente_factura']) ? $datos['cliente_factura'] : 0);
    $id_articulo = (int) (isset($datos['id_rel_articulo']) ? $datos['id_rel_articulo'] : 0);
    $rel_id_venta = (int) (isset($datos['rel_id_venta']) ? $datos['rel_id_venta'] : 0);

    if ($id_sucursal <= 0 || $rel_id_empresa <= 0 || $id_factura_rectificativa <= 0 || $id_factura_original_fiskaly <= 0) {
        insertErrorLog($contexto . ': Fiskaly CORRECTING omitido (parámetros inválidos)');

        return null;
    }

    $fiskaly_eval = fiskalyEvaluarSucursalEmpresa($id_sucursal, $rel_id_empresa);
    if (empty($fiskaly_eval['activo'])) {
        insertErrorLog(
            $contexto . ': Fiskaly CORRECTING omitido (sucursal ' . $id_sucursal . ', empresa ' . $rel_id_empresa
            . '): ' . $fiskaly_eval['motivo']
        );

        return null;
    }

    try {
        $cacheOriginal = fiskalyLeerFacturaCache($id_factura_original_fiskaly, $rel_id_empresa);
        $uuidOriginal = trim((string) (isset($cacheOriginal['factura']['invoice_id_fiskaly']) ? $cacheOriginal['factura']['invoice_id_fiskaly'] : ''));
        if ($uuidOriginal === '') {
            throw new Exception('La factura Fiskaly original no tiene invoice_id_fiskaly (UUID)');
        }

        $tipoOriginal = strtoupper(trim((string) (isset($cacheOriginal['factura']['tipo_factura']) ? $cacheOriginal['factura']['tipo_factura'] : 'SIMPLIFIED')));
        $esComplete = ($tipoOriginal === 'COMPLETE');
        $correction_data_type = $esComplete ? 'COMPLETE' : 'SIMPLIFIED';

        $regimen_empresa = $fiskaly_eval['regimen'] !== ''
            ? $fiskaly_eval['regimen']
            : obtenerRegimenEmpresa($rel_id_empresa);

        $importeAbs = abs((float) (isset($datos['total_factura']) ? $datos['total_factura'] : 0));
        $cabeceraFiskaly = array(
            'id_sucursal' => $id_sucursal,
            'numero_factura' => (int) (isset($datos['numero_factura']) ? $datos['numero_factura'] : 0),
            'facturado_por' => (int) (isset($datos['facturado_por']) ? $datos['facturado_por'] : 0),
            'estado_factura' => 'pagada',
            'tipo_pago_factura' => isset($datos['tipo_pago_factura']) ? $datos['tipo_pago_factura'] : '',
            'total_factura' => $importeAbs,
            'rel_id_venta' => $rel_id_venta,
            'prefijo_factura' => isset($datos['prefijo_factura']) ? $datos['prefijo_factura'] : '',
            'tipo_factura' => 'articulos',
            'tipo_factura_master' => 'CORRECTING',
            'rel_id_empresa' => $rel_id_empresa,
            'factura_regimen' => $regimen_empresa,
        );

        if ($esComplete) {
            if ($id_cliente <= 0) {
                $id_cliente = (int) (isset($cacheOriginal['factura']['cliente_factura']) ? $cacheOriginal['factura']['cliente_factura'] : 0);
            }
            if ($id_cliente <= 0) {
                throw new Exception('CORRECTING COMPLETE requiere cliente_factura');
            }
            $cabeceraFiskaly['cliente_factura'] = $id_cliente;
            $id_factura_fiskaly = crearFacturaFiskaly($cabeceraFiskaly);
        } else {
            $id_factura_fiskaly = crearFacturaFiskalySimplificada($cabeceraFiskaly);
        }

        fiskalyVincularFacturaRectificativaTpv($id_factura_rectificativa, $id_factura_fiskaly, $simplificada_historico);

        $desc = isset($datos['descripcion_articulo_rel']) ? trim((string) $datos['descripcion_articulo_rel']) : '';
        if ($desc === '') {
            $desc = 'Devolución artículo #' . $id_articulo;
        }

        insertarItemsFacturaFiskaly(
            array(
                'rel_factura_id_fiskaly' => $id_factura_fiskaly,
                'rel_id_factura' => $id_factura_rectificativa,
                'id_rel_sucursal' => $id_sucursal,
                'id_rel_articulo' => $id_articulo,
                'descripcion_articulo_rel' => $desc,
                'precio_unitario' => $importeAbs,
                'total_linea' => $importeAbs,
                'precio_coste_articulo' => (float) (isset($datos['precio_coste_articulo']) ? $datos['precio_coste_articulo'] : 0),
                'tipo_iva_articulo' => isset($datos['tipo_iva_articulo']) ? $datos['tipo_iva_articulo'] : 'IVA',
                'system_codigo_regimen' => isset($datos['system_codigo_regimen']) ? $datos['system_codigo_regimen'] : 'GENERAL',
                'rel_id_empresa' => $rel_id_empresa,
            )
        );

        try {
            $envio = enviarFacturaFiskaly(
                $id_factura_fiskaly,
                $rel_id_empresa,
                $id_sucursal,
                array(
                    'corrected_invoice_id' => $uuidOriginal,
                    'correction_method' => 'DIFFERENCES',
                    'correction_code' => 'CORRECTION_1',
                    'correction_data_type' => $correction_data_type,
                )
            );
            $envio['id_factura_fiskaly'] = $id_factura_fiskaly;

            return $envio;
        } catch (Throwable $exFiskaly) {
            insertErrorLog($contexto . ': envío Fiskaly CORRECTING artículos no completado: ' . $exFiskaly->getMessage());

            return array(
                'success' => false,
                'estado_cache' => 'error',
                'message' => $exFiskaly->getMessage(),
                'id_factura_fiskaly' => $id_factura_fiskaly,
            );
        }
    } catch (Throwable $exCache) {
        insertErrorLog($contexto . ': factura Fiskaly CORRECTING artículos no creada: ' . $exCache->getMessage());

        return array(
            'success' => false,
            'estado_cache' => 'error',
            'message' => $exCache->getMessage(),
        );
    }
}

/**
 * Reenvía a SIGN ES una factura CORRECTING ya creada en cache (p. ej. tras fallo de payload).
 *
 * @param string $correction_data_type SIMPLIFIED|COMPLETE
 * @return array
 */
function fiskalyReenviarFacturaRectificativaCache($id_factura_fiskaly, $id_empresa, $id_sucursal, $uuid_factura_original, $correction_data_type = 'SIMPLIFIED')
{
    $id_factura_fiskaly = (int) $id_factura_fiskaly;
    $id_empresa = (int) $id_empresa;
    $id_sucursal = (int) $id_sucursal;
    $uuid_factura_original = trim((string) $uuid_factura_original);
    $correction_data_type = strtoupper(trim((string) $correction_data_type));
    if ($correction_data_type !== 'COMPLETE') {
        $correction_data_type = 'SIMPLIFIED';
    }

    if ($id_factura_fiskaly <= 0 || $id_empresa <= 0 || $id_sucursal <= 0 || $uuid_factura_original === '') {
        throw new Exception('fiskalyReenviarFacturaRectificativaCache: parámetros inválidos');
    }

    return enviarFacturaFiskaly(
        $id_factura_fiskaly,
        $id_empresa,
        $id_sucursal,
        array(
            'corrected_invoice_id' => $uuid_factura_original,
            'correction_method' => 'DIFFERENCES',
            'correction_code' => 'CORRECTION_1',
            'correction_data_type' => $correction_data_type,
        )
    );
}

/**
 * Obtiene estado_cache de facturas Fiskaly agrupadas por empresa.
 *
 * @param array $idsPorEmpresa [id_empresa => [id_factura_fiskaly, ...], ...]
 * @return array [id_factura_fiskaly => estado_cache]
 */
function fiskalyObtenerEstadosCacheMapa(array $idsPorEmpresa)
{
    $mapa = array();

    foreach ($idsPorEmpresa as $id_empresa => $ids) {
        $id_empresa = (int) $id_empresa;
        if ($id_empresa <= 0) {
            continue;
        }

        $idsLimpios = array();
        foreach ((array) $ids as $idF) {
            $idF = (int) $idF;
            if ($idF > 0) {
                $idsLimpios[$idF] = $idF;
            }
        }
        if ($idsLimpios === array()) {
            continue;
        }

        $mysqli = obtenerConexionFiskalyPorEmpresa($id_empresa);
        if (!$mysqli || $mysqli->connect_errno) {
            continue;
        }

        $lista = implode(',', array_map('intval', array_values($idsLimpios)));
        $sql = 'SELECT id_factura, estado_cache FROM facturas_fiskaly_cache WHERE id_factura IN (' . $lista . ')';
        $res = $mysqli->query($sql);
        if (!$res) {
            continue;
        }
        while ($row = $res->fetch_assoc()) {
            $mapa[(int) $row['id_factura']] = trim((string) ($row['estado_cache'] ?? ''));
        }
        $res->free();
    }

    return $mapa;
}
