<?php

/**
 * Utilidades fiscales para integración Fiskaly SIGN ES (PHP servidor).
 */

function fiskaly_format_decimal($valor)
{
    return number_format((float) $valor, 2, '.', '');
}

/**
 * Texto legal de factura acotado para la API SIGN ES (Utf8Text500 = máx. 500 bytes UTF-8).
 */
function fiskaly_texto_factura_api($texto, $maxBytes = 500)
{
    $texto = trim(str_replace(array("\r\n", "\r", "\n"), ' ', (string) $texto));
    $texto = preg_replace('/\s+/u', ' ', $texto);
    if ($texto === '') {
        return '';
    }
    $maxBytes = max(1, (int) $maxBytes);
    if (strlen($texto) <= $maxBytes) {
        return $texto;
    }
    if (function_exists('mb_strcut')) {
        return rtrim((string) mb_strcut($texto, 0, $maxBytes, 'UTF-8'));
    }

    return rtrim(substr($texto, 0, $maxBytes));
}

/**
 * Límite de bytes del campo text según régimen fiscal de la empresa.
 */
function fiskaly_max_bytes_texto_por_regimen($regimen_empresa)
{
    $regimen = (string) $regimen_empresa;
    if (strpos($regimen, 'TicketBAI') !== false) {
        return 250;
    }

    return 500;
}

function fiskaly_normalizar_tipo_identificacion($tipo)
{
    $t = strtolower(trim((string) $tipo));
    if ($t === 'dni') {
        return 'dni';
    }
    if ($t === 'nie') {
        return 'nie';
    }
    if ($t === 'passport' || strpos($t, 'pasap') !== false) {
        return 'passport';
    }
    if ($t === 'cif' || $t === 'nif' || $t === 'tax_number') {
        return 'tax_number';
    }
    if ($t === 'other') {
        return 'other';
    }

    return $t !== '' ? $t : 'tax_number';
}

/**
 * Resuelve el tipo Fiskaly del cliente.
 * Preferencia: clientes.tipo_identificacion (texto).
 * Si está vacío: tipo_identificacion.parameter_kisfaly vía tipo_identificacion_id
 * (enum DNI|NIE|CIF|PASSPORT|OTHER).
 *
 * @param string $tipo_identificacion
 * @param string $parameter_kisfaly
 */
function fiskaly_resolver_tipo_identificacion_cliente($tipo_identificacion, $parameter_kisfaly = '')
{
    $tipo = trim((string) $tipo_identificacion);
    if ($tipo !== '') {
        return fiskaly_normalizar_tipo_identificacion($tipo);
    }

    $param = trim((string) $parameter_kisfaly);
    if ($param !== '') {
        return fiskaly_normalizar_tipo_identificacion($param);
    }

    return 'tax_number';
}

function fiskaly_es_cliente_nacional($tipo_identificacion)
{
    $t = fiskaly_normalizar_tipo_identificacion($tipo_identificacion);

    return $t === 'dni' || $t === 'nie';
}

function fiskaly_country_code_cliente($tipo_identificacion, $nacionalidad)
{
    if (fiskaly_es_cliente_nacional($tipo_identificacion)) {
        return 'ES';
    }
    $nac = strtolower(trim((string) $nacionalidad));
    if ($nac !== '' && (strpos($nac, 'espa') !== false || $nac === 'spain')) {
        return 'ES';
    }

    return 'ES';
}

function fiskaly_mapear_regimen_item($system_codigo_regimen)
{
    $type = fiskaly_normalizar_regimen_enum($system_codigo_regimen);
    if ($type === 'REBU') {
        return 'ANTIQUES';
    }
    if ($type === 'INVERSION') {
        return 'INVESTMENT_GOLD';
    }

    return 'REGULAR';
}

/**
 * Valores permitidos en facturas_fiskaly_rel_articulos_cache.system_codigo_regimen.
 */
function fiskaly_normalizar_regimen_enum($regimen)
{
    $u = strtoupper(preg_replace('/\s+/', '', (string) $regimen));
    if (in_array($u, array('REBU', 'INVERSION', 'GENERAL'), true)) {
        return $u;
    }

    return 'GENERAL';
}

/**
 * Porcentaje de beneficio REBU (tabla porcentaje_beneficio).
 */
function fiskaly_obtener_porcentaje_beneficio()
{
    static $porcentaje = null;
    if ($porcentaje !== null) {
        return $porcentaje;
    }

    $porcentaje = 30.0;
    if (!function_exists('conectar_bd')) {
        return $porcentaje;
    }

    $conexion = conectar_bd();
    if (!$conexion) {
        return $porcentaje;
    }

    $res = mysqli_query($conexion, 'SELECT porcentaje_beneficio FROM porcentaje_beneficio ORDER BY id_porcentaje_beneficio ASC LIMIT 1');
    if ($res && ($row = mysqli_fetch_assoc($res))) {
        $porcentaje = (float) $row['porcentaje_beneficio'];
    }
    mysqli_close($conexion);

    return $porcentaje;
}

/**
 * Cálculo REBU (misma lógica legacy con porcentaje_beneficio).
 *
 * @return array{
 *   precio_venta:float,
 *   beneficio_articulo:float,
 *   iva_del_beneficio:float,
 *   beneficio_sin_iva:float,
 *   precio_coste_articulo:float,
 *   precio_venta_sin_iva:float
 * }
 */
function fiskaly_calcular_rebu_importes($precio_venta, $porcentaje_beneficio = null)
{
    if ($porcentaje_beneficio === null) {
        $porcentaje_beneficio = fiskaly_obtener_porcentaje_beneficio();
    }

    $precio_venta_parset = number_format((float) $precio_venta, 2, '.', '');

    $beneficio_articulo = (float) $precio_venta_parset * (float) $porcentaje_beneficio / 100;
    $beneficio_articulo = number_format($beneficio_articulo, 2, '.', '');

    $iva_del_beneficio = (float) $beneficio_articulo * 21 / 100;
    $iva_del_beneficio = number_format($iva_del_beneficio, 2, '.', '');

    $beneficio_sin_iva = (float) $beneficio_articulo - (float) $iva_del_beneficio;
    $beneficio_sin_iva = number_format($beneficio_sin_iva, 2, '.', '');

    $precio_coste_articulo = (float) $precio_venta_parset - (float) $beneficio_articulo;
    $precio_coste_articulo = number_format($precio_coste_articulo, 2, '.', '');

    $precio_venta_sin_iva = (float) $precio_coste_articulo + (float) $beneficio_sin_iva;
    $precio_venta_sin_iva = number_format($precio_venta_sin_iva, 2, '.', '');

    return array(
        'precio_venta' => (float) $precio_venta_parset,
        'beneficio_articulo' => (float) $beneficio_articulo,
        'iva_del_beneficio' => (float) $iva_del_beneficio,
        'beneficio_sin_iva' => (float) $beneficio_sin_iva,
        'precio_coste_articulo' => (float) $precio_coste_articulo,
        'precio_venta_sin_iva' => (float) $precio_venta_sin_iva,
    );
}

/**
 * Actualiza precio_coste_calculado en articulos_venta (legacy REBU).
 */
function fiskalyActualizarPrecioCosteCalculadoArticulo($id_articulo, $precio_coste_calculado)
{
    $id_articulo = (int) $id_articulo;
    if ($id_articulo <= 0 || !function_exists('conectar_bd')) {
        return false;
    }

    $conexion = conectar_bd();
    if (!$conexion) {
        return false;
    }

    $stmt = mysqli_prepare(
        $conexion,
        'UPDATE articulos_venta SET precio_coste_calculado = ? WHERE id = ?'
    );
    if (!$stmt) {
        mysqli_close($conexion);
        return false;
    }

    $coste = (float) $precio_coste_calculado;
    mysqli_stmt_bind_param($stmt, 'di', $coste, $id_articulo);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    mysqli_close($conexion);

    return (bool) $ok;
}

/**
 * formato_factura de cache según system_codigo_regimen de la línea.
 */
function fiskaly_formato_factura_por_regimen($system_codigo_regimen)
{
    $regimen = strtoupper(trim((string) $system_codigo_regimen));
    if ($regimen === 'INVERSION') {
        return 'oro_inversion';
    }

    return 'articulos';
}

/**
 * Actualiza formato_factura en facturas_fiskaly_cache (legacy: por régimen de línea).
 */
function fiskalyActualizarFormatoFacturaCache($id_factura_fiskaly, $id_empresa, $formato_factura)
{
    $id_factura_fiskaly = (int) $id_factura_fiskaly;
    $id_empresa = (int) $id_empresa;
    $formato = strtolower(trim((string) $formato_factura));
    if (!in_array($formato, array('articulos', 'oro_inversion', 'renovaciones'), true)) {
        $formato = 'articulos';
    }
    if ($id_factura_fiskaly <= 0 || $id_empresa <= 0) {
        return false;
    }

    $mysqli = obtenerConexionFiskalyPorEmpresa($id_empresa);
    if (!$mysqli || $mysqli->connect_errno) {
        return false;
    }

    $stmt = $mysqli->prepare(
        'UPDATE facturas_fiskaly_cache SET formato_factura = ? WHERE id_factura = ?'
    );
    if (!$stmt) {
        return false;
    }

    $stmt->bind_param('si', $formato, $id_factura_fiskaly);
    $ok = $stmt->execute();
    $stmt->close();

    return (bool) $ok;
}

/**
 * Calcula base, IVA y precio sin IVA de una línea (misma lógica que factura_pdf_archivo.php).
 *
 * @return array{
 *   precio_con_iva:float,
 *   precio_sin_iva:float,
 *   tax_base:float,
 *   iva:float,
 *   linea_con_iva_21:bool,
 *   beneficio_articulo?:float,
 *   precio_coste_articulo?:float
 * }
 */
function fiskaly_calcular_linea_fiscal($precio_con_iva, $system_codigo_regimen, $tipo_iva_articulo)
{
    $system_codigo_regimen = strtoupper(trim((string) $system_codigo_regimen));
    $tipo_iva_articulo = strtoupper(trim((string) $tipo_iva_articulo));
    if ($tipo_iva_articulo === '') {
        $tipo_iva_articulo = 'IVA';
    }

    if ($system_codigo_regimen === 'REBU') {
        $rebu = fiskaly_calcular_rebu_importes($precio_con_iva);

        return array(
            'precio_con_iva' => $rebu['precio_venta'],
            'precio_sin_iva' => $rebu['precio_venta_sin_iva'],
            'tax_base' => $rebu['beneficio_articulo'],
            'iva' => $rebu['iva_del_beneficio'],
            'linea_con_iva_21' => false,
            'beneficio_articulo' => $rebu['beneficio_articulo'],
            'beneficio_sin_iva' => $rebu['beneficio_sin_iva'],
            'precio_coste_articulo' => $rebu['precio_coste_articulo'],
            'formato_factura' => 'articulos',
        );
    }

    if ($system_codigo_regimen === 'INVERSION') {
        $precio_venta_parset = number_format((float) $precio_con_iva, 2, '.', '');

        return array(
            'precio_con_iva' => (float) $precio_venta_parset,
            'precio_sin_iva' => (float) $precio_venta_parset,
            'tax_base' => (float) $precio_venta_parset,
            'iva' => 0.0,
            'linea_con_iva_21' => false,
            'formato_factura' => 'oro_inversion',
        );
    }

    $precio_con_iva = round((float) $precio_con_iva, 2);

    $linea_con_iva_21 = in_array($system_codigo_regimen, array('GENERAL', 'INVERSION'), true)
        && $tipo_iva_articulo === 'IVA';

    if ($linea_con_iva_21) {
        $tax_base = round($precio_con_iva / 1.21, 2);
        $iva = round($precio_con_iva - $tax_base, 2);
        $precio_sin_iva = $tax_base;
    } else {
        $tax_base = $precio_con_iva;
        $iva = 0.0;
        $precio_sin_iva = $precio_con_iva;
    }

    return array(
        'precio_con_iva' => $precio_con_iva,
        'precio_sin_iva' => $precio_sin_iva,
        'tax_base' => $tax_base,
        'iva' => $iva,
        'linea_con_iva_21' => $linea_con_iva_21,
        'formato_factura' => 'articulos',
    );
}

function fiskaly_mapear_tipo_pago_factura($tipo_raw)
{
    $tipo_raw = trim((string) $tipo_raw);
    $tipo_map = array(
        'transferencia' => 'transf. banco',
    );
    $tipo_key = strtolower($tipo_raw);
    $tipo_pago = isset($tipo_map[$tipo_key]) ? $tipo_map[$tipo_key] : $tipo_raw;

    return substr($tipo_pago, 0, 13);
}

function fiskaly_estado_cache_desde_respuesta($invoice_state, $registration_state)
{
    if ($invoice_state === 'ISSUED' && $registration_state === 'REGISTERED') {
        return 'aceptada';
    }
    if ($invoice_state === 'ISSUED' && $registration_state === 'PENDING') {
        return 'pendiente';
    }
    if ($invoice_state === 'ISSUED' && $registration_state === 'CANCELLED') {
        return 'rechazada';
    }

    return 'rechazada';
}

function fiskaly_articulos_cache_a_payload($filas)
{
    $articulos = array();
    if (!is_array($filas)) {
        return $articulos;
    }

    foreach ($filas as $row) {
        $system_codigo_regimen = isset($row['system_codigo_regimen']) ? $row['system_codigo_regimen'] : 'GENERAL';
        $item_system_type = fiskaly_mapear_regimen_item($system_codigo_regimen);

        $articulos[] = array(
            'text' => isset($row['descripcion_articulo_rel']) ? $row['descripcion_articulo_rel'] : '',
            'quantity' => 1,
            'unit_amount' => isset($row['precio_venta_sin_iva']) ? $row['precio_venta_sin_iva'] : 0,
            'full_amount' => isset($row['precio_rel_articulo']) ? $row['precio_rel_articulo'] : 0,
            'tax_base' => isset($row['tax_base']) ? $row['tax_base'] : 0,
            'item_system_type' => $item_system_type,
            'item_category_type' => 'VAT',
            'system_codigo_regimen' => $system_codigo_regimen,
            'tipo_iva_articulo' => isset($row['tipo_iva_articulo']) ? $row['tipo_iva_articulo'] : 'IVA',
        );
    }

    return $articulos;
}

/**
 * Bloque system de línea para factura simplificada Fiskaly (SIGN ES).
 * REGULAR: category { type: VAT } sin rate.
 * REBU/ANTIQUES: tax_base = margen de beneficio + category { type: VAT }.
 *
 * @param array $articulo
 * @return array
 */
function fiskaly_build_item_system_simplificada(array $articulo)
{
    $regimen = strtoupper(trim((string) (isset($articulo['system_codigo_regimen']) ? $articulo['system_codigo_regimen'] : 'GENERAL')));
    $tipo_iva = strtoupper(trim((string) (isset($articulo['tipo_iva_articulo']) ? $articulo['tipo_iva_articulo'] : 'IVA')));
    if ($tipo_iva === '') {
        $tipo_iva = 'IVA';
    }

    $item_type = fiskaly_mapear_regimen_item($regimen);

    if ($item_type === 'INVESTMENT_GOLD') {
        return array(
            'type' => 'INVESTMENT_GOLD',
            'category' => array(
                'type' => 'NO_VAT',
                'cause' => 'TAXABLE_EXEMPT_6',
            ),
        );
    }

    if ($regimen === 'REBU') {
        return array(
            'type' => 'ANTIQUES',
            'tax_base' => fiskaly_format_decimal(isset($articulo['tax_base']) ? $articulo['tax_base'] : 0),
            'category' => array(
                'type' => 'VAT',
            ),
        );
    }

    $system_type = 'REGULAR';
    if ($tipo_iva === 'IGIC') {
        $system_type = 'OTHER_TAX_IGIC';
    } elseif ($tipo_iva === 'IPSI') {
        $system_type = 'OTHER_TAX_IPSI';
    }

    return array(
        'type' => $system_type,
        'category' => array(
            'type' => 'VAT',
        ),
    );
}

/**
 * ¿El régimen de empresa activa envío Fiskaly?
 */
function fiskaly_regimen_activo($regimen_empresa)
{
    return in_array(
        (string) $regimen_empresa,
        array('General', 'Verifactu', 'TicketBAIBizkaia', 'TicketBAIAlava', 'TicketBAIGipuzkua'),
        true
    );
}

/**
 * ¿La factura TPV fue enviada y vinculada en Fiskaly (facturas_fiskaly_cache)?
 */
function fiskalyEsFacturaFiskaly($factura_regimen, $id_rel_factura_fiskaly)
{
    return fiskalyFacturaVinculadaEnCache($id_rel_factura_fiskaly);
}

/**
 * ¿Esta factura concreta tiene registro en facturas_fiskaly_cache?
 */
function fiskalyFacturaVinculadaEnCache($id_rel_factura_fiskaly)
{
    return (int) $id_rel_factura_fiskaly > 0;
}

/**
 * URL de impresión para factura simplificada de renovaciones.
 * Solo apunta a plantilla Fiskaly si la factura fue enviada (id_rel_factura_fiskaly > 0).
 *
 * @param string $origen_simplificada ''|facturas|historico
 */
function fiskalyUrlImpresionFacturaRenovaciones($id_factura, $factura_regimen, $id_rel_factura_fiskaly, $origen_simplificada = '')
{
    $id_factura = (int) $id_factura;
    if ($id_factura <= 0) {
        return '';
    }

    $urlClasica = 'Impresiones/Facturas/factura_simplificada_renovaciones.php?id_factura=' . $id_factura;
    $origen = strtolower(trim((string) $origen_simplificada));
    if ($origen === 'unificada') {
        $origen = 'facturas';
    }
    if ($origen === 'facturas' || $origen === 'historico') {
        $urlClasica .= '&origen=' . rawurlencode($origen);
    }
    if (!fiskalyFacturaVinculadaEnCache($id_rel_factura_fiskaly)) {
        return $urlClasica;
    }

    return fiskalyUrlImpresionFactura(
        $id_factura,
        $factura_regimen,
        $id_rel_factura_fiskaly,
        true,
        'renovaciones',
        $origen
    );
}

/**
 * Plantilla de impresión según régimen fiscal.
 */
function fiskalyScriptImpresionFactura($factura_regimen, $simplificada = false)
{
    $regimen = (string) $factura_regimen;
    if ($regimen === 'TicketBAIBizkaia') {
        return 'factura_Bizkaia_tbai.php';
    }
    if (strpos($regimen, 'TicketBAI') !== false) {
        return 'factura_ticket_bai.php';
    }

    return $simplificada ? 'factura_simplificada.php' : 'factura.php';
}

/**
 * URL relativa para imprimir una factura TPV según Fiskaly/régimen.
 *
 * @param string $formato_simplificada articulos|renovaciones (solo si $simplificada)
 * @param string $origen_simplificada ''|facturas|historico
 */
function fiskalyUrlImpresionFactura($id_factura, $factura_regimen, $id_rel_factura_fiskaly, $simplificada = false, $formato_simplificada = 'articulos', $origen_simplificada = '')
{
    $id_factura = (int) $id_factura;
    if ($id_factura <= 0) {
        return '';
    }

    if ($simplificada) {
        $scriptClasica = ($formato_simplificada === 'renovaciones')
            ? 'factura_simplificada_renovaciones.php'
            : 'factura_simplificada.php';
    } else {
        $scriptClasica = 'factura.php';
    }
    $urlClasica = 'Impresiones/Facturas/' . $scriptClasica . '?id_factura=' . $id_factura;
    $origen = strtolower(trim((string) $origen_simplificada));
    if ($origen === 'unificada') {
        $origen = 'facturas';
    }
    if ($simplificada && ($origen === 'facturas' || $origen === 'historico')) {
        $urlClasica .= '&origen=' . rawurlencode($origen);
    }

    if (!fiskalyFacturaVinculadaEnCache($id_rel_factura_fiskaly)) {
        return $urlClasica;
    }

    $script = fiskalyScriptImpresionFactura($factura_regimen, $simplificada);
    $url = 'Impresiones/Facturas/' . $script . '?id_factura=' . $id_factura;
    if ($simplificada && $script === 'factura_Bizkaia_tbai.php') {
        $url .= '&tipo=simplificada';
    }
    if ($simplificada && ($origen === 'facturas' || $origen === 'historico')) {
        $url .= '&origen=' . rawurlencode($origen);
    }

    return $url;
}

/**
 * URL de impresión para listados (completa o simplificada, incl. renovaciones).
 *
 * @param string $origen_simplificada ''|facturas|historico
 */
function fiskalyUrlImpresionFacturaListado(
    $id_factura,
    $factura_regimen,
    $id_rel_factura_fiskaly,
    $tipo_factura_db = 'articulos',
    $simplificada = false,
    $origen_simplificada = ''
) {
    $id_factura = (int) $id_factura;
    $tipo = strtolower(trim((string) $tipo_factura_db));
    if ($simplificada && $tipo === 'renovaciones') {
        return fiskalyUrlImpresionFacturaRenovaciones(
            $id_factura,
            $factura_regimen,
            $id_rel_factura_fiskaly,
            $origen_simplificada
        );
    }

    return fiskalyUrlImpresionFactura(
        $id_factura,
        $factura_regimen,
        $id_rel_factura_fiskaly,
        (bool) $simplificada,
        $tipo === 'renovaciones' ? 'renovaciones' : 'articulos',
        $origen_simplificada
    );
}

/**
 * Tipo para generarPdfFacturaBinario() según tipo_factura de BD (renovaciones vs resto).
 */
function tipoGeneracionPdfFacturaSimplificada($tipo_factura_db)
{
    return strtolower(trim((string) $tipo_factura_db)) === 'renovaciones'
        ? 'factura_simplificada_renovaciones'
        : 'factura_simplificada';
}

/**
 * URL de impresión para factura rectificativa (completa o simplificada).
 */
function fiskalyUrlImpresionFacturaRectificativa($id_factura, $factura_regimen, $id_rel_factura_fiskaly, $simplificada = false)
{
    $id_factura = (int) $id_factura;
    if ($id_factura <= 0) {
        return '';
    }

    $scriptClasica = $simplificada
        ? 'factura_rectificativa_simplificada_pdf_archivo.php'
        : 'factura_rectificativa.php';
    $urlClasica = 'Impresiones/Facturas/' . $scriptClasica . '?id_factura=' . $id_factura;

    if (!fiskalyFacturaVinculadaEnCache($id_rel_factura_fiskaly)) {
        return $urlClasica;
    }

    $script = fiskalyScriptImpresionFactura($factura_regimen, (bool) $simplificada);
    if ($script === 'factura_Bizkaia_tbai.php') {
        $tipo = $simplificada ? 'rectificativa_simplificada' : 'rectificativa';

        return 'Impresiones/Facturas/factura_Bizkaia_tbai.php?id_factura=' . $id_factura . '&tipo=' . $tipo;
    }

    return $urlClasica;
}

/**
 * Script PHP y parámetros GET para generarPdfFactura() según Fiskaly/régimen.
 *
 * @return array{script:string,get:array<string,string>}
 */
function fiskalyResolverGeneracionPdf($tipo_factura, $factura_regimen, $id_rel_factura_fiskaly)
{
    $mapDefault = array(
        'factura' => 'factura_pdf_archivo.php',
        'factura_simplificada' => 'factura_simplificada_pdf_archivo.php',
        'factura_simplificada_renovaciones' => 'factura_simplificada_renovaciones_pdf_archivo.php',
        'factura_rectificativa' => 'factura_rectificativa_pdf_archivo.php',
        'factura_rectificativa_simplificada' => 'factura_rectificativa_simplificada_pdf_archivo.php',
    );

    $tipo_factura = strtolower(trim((string) $tipo_factura));
    $default = isset($mapDefault[$tipo_factura]) ? $mapDefault[$tipo_factura] : 'factura_pdf_archivo.php';

    if (!fiskalyFacturaVinculadaEnCache($id_rel_factura_fiskaly)) {
        return array('script' => $default, 'get' => array());
    }

    $esRectificativa = in_array(
        $tipo_factura,
        array('factura_rectificativa', 'factura_rectificativa_simplificada'),
        true
    );
    $esNormal = in_array(
        $tipo_factura,
        array('factura', 'factura_simplificada', 'factura_simplificada_renovaciones'),
        true
    );

    if (!$esRectificativa && !$esNormal) {
        return array('script' => $default, 'get' => array());
    }

    if ($esRectificativa) {
        $simplificadaRect = ($tipo_factura === 'factura_rectificativa_simplificada');
        $scriptWeb = fiskalyScriptImpresionFactura($factura_regimen, $simplificadaRect);
        if ($scriptWeb === 'factura_Bizkaia_tbai.php') {
            return array(
                'script' => 'factura_Bizkaia_tbai.php',
                'get' => array(
                    'tipo' => $simplificadaRect ? 'rectificativa_simplificada' : 'rectificativa',
                ),
            );
        }

        // Verifactu / General: plantilla _pdf_archivo con QR en pie.
        return array('script' => $default, 'get' => array());
    }

    $simplificada = in_array($tipo_factura, array('factura_simplificada', 'factura_simplificada_renovaciones'), true);
    $scriptWeb = fiskalyScriptImpresionFactura($factura_regimen, $simplificada);

    if ($scriptWeb === 'factura_Bizkaia_tbai.php') {
        $get = array();
        if ($simplificada) {
            $get['tipo'] = 'simplificada';
        }

        return array('script' => 'factura_Bizkaia_tbai.php', 'get' => $get);
    }

    // General / Verifactu: plantillas _pdf_archivo estándar.
    if ($scriptWeb === 'factura.php' || $scriptWeb === 'factura_simplificada.php') {
        return array('script' => $default, 'get' => array());
    }

    // Otras provincias TicketBAI: factura_ticket_bai.php legacy sin modo buffer.
    return array('script' => $default, 'get' => array());
}

/**
 * TBai, QR y URL de validación desde facturas_fiskaly_cache para PDF.
 *
 * @return array{tbai:string,imagen_codigo_qr:string,url_validacion:string}
 */
function fiskalyObtenerDatosQrImpresion($id_empresa, $id_rel_factura_fiskaly, $identificador_venta, $id_sucursal, $numero_factura)
{
    $vacío = array(
        'tbai' => '',
        'imagen_codigo_qr' => '',
        'url_validacion' => '',
    );

    $id_empresa = (int) $id_empresa;
    if ($id_empresa <= 0) {
        return $vacío;
    }

    $mysqli = obtenerConexionFiskalyPorEmpresa($id_empresa);
    if (!$mysqli || $mysqli->connect_errno) {
        return $vacío;
    }

    $row = null;
    $id_rel_factura_fiskaly = (int) $id_rel_factura_fiskaly;
    if ($id_rel_factura_fiskaly > 0) {
        $stmt = $mysqli->prepare(
            'SELECT tbai, imagen_codigo_qr, url_validacion
             FROM facturas_fiskaly_cache WHERE id_factura = ? LIMIT 1'
        );
        if ($stmt) {
            $stmt->bind_param('i', $id_rel_factura_fiskaly);
            $stmt->execute();
            $res = $stmt->get_result();
            $row = $res ? $res->fetch_assoc() : null;
            $stmt->close();
        }
    } else {
        $identificador_venta = (int) $identificador_venta;
        $id_sucursal = (int) $id_sucursal;
        $numero_factura = (int) $numero_factura;
        if ($identificador_venta > 0 && $id_sucursal > 0 && $numero_factura > 0) {
            $stmt = $mysqli->prepare(
                'SELECT tbai, imagen_codigo_qr, url_validacion
                 FROM facturas_fiskaly_cache
                 WHERE rel_id_venta = ? AND id_sucursal = ? AND numero_factura = ?
                 LIMIT 1'
            );
            if ($stmt) {
                $stmt->bind_param('iii', $identificador_venta, $id_sucursal, $numero_factura);
                $stmt->execute();
                $res = $stmt->get_result();
                $row = $res ? $res->fetch_assoc() : null;
                $stmt->close();
            }
        }
    }

    if (!$row) {
        return $vacío;
    }

    return array(
        'tbai' => trim((string) ($row['tbai'] ?? '')),
        'imagen_codigo_qr' => trim((string) ($row['imagen_codigo_qr'] ?? '')),
        'url_validacion' => trim((string) ($row['url_validacion'] ?? '')),
    );
}

/**
 * HTML <img> del QR Fiskaly para mPDF (data URI, sin archivo temporal).
 *
 * @param string $alt Texto alt (TicketBAI / Verifactu)
 */
function fiskalyHtmlImagenQr($imagen_codigo_qr, $alt = 'QR fiscal')
{
    $imagen_b64 = trim((string) $imagen_codigo_qr);
    if ($imagen_b64 === '') {
        return '';
    }

    if (strpos($imagen_b64, 'base64,') !== false) {
        $imagen_b64 = substr($imagen_b64, strrpos($imagen_b64, 'base64,') + 7);
    }
    $imagen_b64 = preg_replace('/\s+/', '', $imagen_b64);
    if ($imagen_b64 === '') {
        return '';
    }

    $imagen_binaria = base64_decode($imagen_b64, true);
    if ($imagen_binaria === false || $imagen_binaria === '') {
        $imagen_binaria = base64_decode($imagen_b64);
    }
    if ($imagen_binaria === false || $imagen_binaria === '') {
        return '';
    }

    $mime = 'image/png';
    if (strncmp($imagen_binaria, '<svg', 4) === 0 || strncmp($imagen_binaria, '<?xm', 4) === 0) {
        $mime = 'image/svg+xml';
    } elseif (strncmp($imagen_binaria, 'GIF8', 4) === 0) {
        $mime = 'image/gif';
    } elseif (strncmp($imagen_binaria, "\xFF\xD8\xFF", 3) === 0) {
        $mime = 'image/jpeg';
    }

    $dataUri = 'data:' . $mime . ';base64,' . base64_encode($imagen_binaria);
    $altSafe = htmlspecialchars(trim((string) $alt) !== '' ? (string) $alt : 'QR fiscal', ENT_QUOTES, 'UTF-8');

    return '<img src="' . $dataUri . '" width="30mm" height="30mm" alt="' . $altSafe . '">';
}

/**
 * Bloque HTML de cumplimiento fiscal (QR + leyenda) para pie de factura.
 * Verifactu: "QR tributario" + VERI*FACTU. TicketBAI: identificador TBAI.
 */
function fiskalyHtmlBloqueQrImpresion($id_qr, $qr_img_html, $factura_regimen = '')
{
    $id_qr = trim((string) $id_qr);
    $qr_img_html = (string) $qr_img_html;
    $regimen = (string) $factura_regimen;
    $esVerifactu = ($regimen === 'Verifactu' || $regimen === 'General');

    if ($id_qr === '' && $qr_img_html === '') {
        return '';
    }

    if ($esVerifactu && $id_qr === '' && $qr_img_html !== '') {
        $id_qr = 'VERI*FACTU';
    }

    $lineas = '';
    if ($esVerifactu) {
        $lineas .= '<p style="text-align: center; font-style: normal; margin:0 0 2px 0; color:#000000; font-size:7pt; font-weight:bold;">QR tributario</p>';
    }
    if ($id_qr !== '') {
        $lineas .= '<p style="text-align: center; font-style: normal; margin:0px 0 5px 0; color:#000000; font-size:7pt;">'
            . htmlspecialchars($id_qr, ENT_QUOTES, 'UTF-8') . '</p>';
    }

    return '
    <div style="width: 100%; text-align: center; font-style: italic; margin:15px 0 0 0; color:#000000; font-size:7pt;">
        ' . $lineas . $qr_img_html . '
    </div>';
}

/**
 * Territory Fiskaly sugerido según region_regimen de la empresa.
 */
function fiskaly_territory_sugerido_por_regimen($regimen_empresa)
{
    switch ((string) $regimen_empresa) {
        case 'TicketBAIAlava':
            return 'ARABA';
        case 'TicketBAIBizkaia':
            return 'BIZKAIA';
        case 'TicketBAIGipuzkua':
            return 'GIPUZKOA';
        case 'Verifactu':
        case 'General':
            return 'SPAIN_OTHER';
        default:
            return '';
    }
}

/**
 * Redirige a la plantilla Fiskaly si la factura simplificada/completa lo requiere (solo HTTP).
 */
function fiskalyRedirigirImpresionDesdeGet($id_factura, $tabla, $simplificada = false)
{
    $id_factura = (int) $id_factura;
    $tabla = (string) $tabla;
    $tablas_ok = array(
        'facturas',
        'facturas_simplificadas',
        'facturas_rectificativas',
        'facturas_rectificativas_simplificadas',
    );
    if ($id_factura <= 0 || !in_array($tabla, $tablas_ok, true)) {
        return;
    }

    $origenGet = isset($_GET['origen']) ? strtolower(trim((string) $_GET['origen'])) : '';
    if ($origenGet === 'unificada') {
        $origenGet = 'facturas';
    }

    $factura_regimen = 'false';
    $id_rel_factura_fiskaly = 0;
    $encontrada = false;
    $esRectificativa = in_array(
        $tabla,
        array('facturas_rectificativas', 'facturas_rectificativas_simplificadas'),
        true
    );

    if ($esRectificativa) {
        $conexion = conectar_bd();
        if (!$conexion) {
            return;
        }
        $sql = "SELECT factura_regimen, id_rel_factura_fiskaly FROM `{$tabla}` WHERE id_factura = ? LIMIT 1";
        $stmt = mysqli_prepare($conexion, $sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'i', $id_factura);
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);
            $row = $res ? mysqli_fetch_assoc($res) : null;
            mysqli_stmt_close($stmt);
            if ($row) {
                $encontrada = true;
                $factura_regimen = (string) ($row['factura_regimen'] ?? 'false');
                $id_rel_factura_fiskaly = (int) ($row['id_rel_factura_fiskaly'] ?? 0);
            }
        }
        mysqli_close($conexion);
    } elseif ($simplificada) {
        $preferencia = '';
        if ($origenGet === 'facturas' || $origenGet === 'historico') {
            $preferencia = $origenGet;
        } elseif ($tabla === 'facturas') {
            $preferencia = 'facturas';
        } else {
            $preferencia = 'historico';
        }
        $info = facturaSimplificadaResolverOrigen($id_factura, $preferencia);
        if ($info) {
            $encontrada = true;
            $factura_regimen = $info['factura_regimen'];
            $id_rel_factura_fiskaly = $info['id_rel_factura_fiskaly'];
            $origenGet = $info['origen'] === 'facturas' ? 'facturas' : 'historico';
        } elseif ($preferencia !== '') {
            // Fallback al otro origen si el preferido no existe
            $info = facturaSimplificadaResolverOrigen($id_factura, '');
            if ($info) {
                $encontrada = true;
                $factura_regimen = $info['factura_regimen'];
                $id_rel_factura_fiskaly = $info['id_rel_factura_fiskaly'];
                $origenGet = $info['origen'] === 'facturas' ? 'facturas' : 'historico';
            }
        }
    } else {
        $conexion = conectar_bd();
        if (!$conexion) {
            return;
        }
        $sql = 'SELECT factura_regimen, id_rel_factura_fiskaly FROM facturas WHERE id_factura = ? LIMIT 1';
        $stmt = mysqli_prepare($conexion, $sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'i', $id_factura);
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);
            $row = $res ? mysqli_fetch_assoc($res) : null;
            mysqli_stmt_close($stmt);
            if ($row) {
                $encontrada = true;
                $factura_regimen = (string) ($row['factura_regimen'] ?? 'false');
                $id_rel_factura_fiskaly = (int) ($row['id_rel_factura_fiskaly'] ?? 0);
            }
        }
        mysqli_close($conexion);
    }

    if (!$encontrada || !fiskalyFacturaVinculadaEnCache($id_rel_factura_fiskaly)) {
        return;
    }

    $script = fiskalyScriptImpresionFactura($factura_regimen, $simplificada || $tabla === 'facturas_rectificativas_simplificadas');
    if ($script !== 'factura_Bizkaia_tbai.php') {
        return;
    }

    $url = 'factura_Bizkaia_tbai.php?id_factura=' . $id_factura;
    if ($esRectificativa) {
        $url .= '&tipo=' . ($tabla === 'facturas_rectificativas_simplificadas'
            ? 'rectificativa_simplificada'
            : 'rectificativa');
    } elseif ($simplificada) {
        $url .= '&tipo=simplificada';
        if ($origenGet === 'facturas' || $origenGet === 'historico') {
            $url .= '&origen=' . rawurlencode($origenGet);
        }
    }
    header('Location: ' . $url);
    exit;
}
