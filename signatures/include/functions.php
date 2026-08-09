<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/ftp.php';

/**
 * Función para conectar a la base de datos
 */
function conectar_bd() {
    $conexion = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if (!$conexion) {
        die('Error de conexión: ' . mysqli_connect_error());
    }
    
    mysqli_set_charset($conexion, 'utf8');
    return $conexion;
}

/**
 * Inserta un mensaje en la tabla `error_log` de la base de datos.
 * Esquema esperado: columnas `mensaje` (TEXT o VARCHAR) y `fecha` (DATETIME; se rellena con NOW()).
 * Si tu tabla usa otros nombres de columna, adapta la consulta SQL aquí.
 *
 * @param string $message Texto del error
 * @return bool true si el INSERT se ejecutó correctamente
 */
function insertErrorLog($message)
{
    $msg = (string) $message;
    if (function_exists('mb_strlen') && mb_strlen($msg, 'UTF-8') > 65000) {
        $msg = mb_substr($msg, 0, 65000, 'UTF-8');
    } elseif (strlen($msg) > 65000) {
        $msg = substr($msg, 0, 65000);
    }

    $conexion = conectar_bd();
    if (!$conexion) {
        return false;
    }

    $sql = 'INSERT INTO `error_logs` (`texto_error`, `fecha_hora`) VALUES (?, NOW())';
    $stmt = mysqli_prepare($conexion, $sql);
    if (!$stmt) {
        mysqli_close($conexion);
        return false;
    }

    mysqli_stmt_bind_param($stmt, 's', $msg);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    mysqli_close($conexion);

    return (bool) $ok;
}

/**
 * Devuelve el régimen/región de la empresa (columna `region_regimen`).
 */
function obtenerRegimenEmpresa($id_empresa)
{
    $conexion = conectar_bd();

    $id = (int) $id_empresa;
    $stmt = mysqli_prepare($conexion, 'SELECT region_regimen FROM empresas WHERE id_empresa = ? LIMIT 1');
    if (!$stmt) {
        $err = mysqli_error($conexion);
        mysqli_close($conexion);
        throw new Exception('obtenerRegimenEmpresa: error al preparar consulta: ' . $err);
    }

    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $row = $res ? mysqli_fetch_assoc($res) : null;
    mysqli_stmt_close($stmt);
    mysqli_close($conexion);

    return (string) ($row['region_regimen'] ?? '');
}

function obtenerEstadoFacturaDigitalEmpresa($id_empresa)
{
    $conexion = conectar_bd();

    $id = (int) $id_empresa;
    $stmt = mysqli_prepare($conexion, 'SELECT factura_digital FROM empresas WHERE id_empresa = ? LIMIT 1');
    if (!$stmt) {
        $err = mysqli_error($conexion);
        mysqli_close($conexion);
        throw new Exception('obtenerEstadoFacturaDigitalEmpresa: error al preparar consulta: ' . $err);
    }

    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $row = $res ? mysqli_fetch_assoc($res) : null;
    mysqli_stmt_close($stmt);
    mysqli_close($conexion);

    return (string) ($row['factura_digital'] ?? '');
}

/**
 * Una fila asociativa desde un mysqli_stmt ya ejecutado (funciona sin mysqlnd).
 */
function mysqli_stmt_fetch_assoc_compat(mysqli_stmt $stmt)
{
    if (function_exists('mysqli_stmt_get_result')) {
        $res = @mysqli_stmt_get_result($stmt);
        if ($res instanceof mysqli_result) {
            $row = mysqli_fetch_assoc($res);
            mysqli_free_result($res);
            return $row ?: null;
        }
    }
    mysqli_stmt_store_result($stmt);
    $meta = mysqli_stmt_result_metadata($stmt);
    if (!$meta) {
        return null;
    }
    $row = [];
    $bind = [];
    while ($field = $meta->fetch_field()) {
        $name = $field->name;
        $row[$name] = null;
        $bind[] = &$row[$name];
    }
    if (!call_user_func_array([$stmt, 'bind_result'], $bind)) {
        return null;
    }
    if (!mysqli_stmt_fetch($stmt)) {
        return null;
    }
    $out = [];
    foreach ($row as $k => $v) {
        $out[$k] = $v;
    }
    return $out;
}

/**
 * Todas las filas como array asociativo (funciona sin mysqlnd).
 */
function mysqli_stmt_fetch_all_assoc_compat(mysqli_stmt $stmt)
{
    $out = [];
    if (function_exists('mysqli_stmt_get_result')) {
        $res = @mysqli_stmt_get_result($stmt);
        if ($res instanceof mysqli_result) {
            while ($row = mysqli_fetch_assoc($res)) {
                $out[] = $row;
            }
            mysqli_free_result($res);
            return $out;
        }
    }
    mysqli_stmt_store_result($stmt);
    $meta = mysqli_stmt_result_metadata($stmt);
    if (!$meta) {
        return [];
    }
    $row = [];
    $bind = [];
    while ($field = $meta->fetch_field()) {
        $name = $field->name;
        $row[$name] = null;
        $bind[] = &$row[$name];
    }
    if (!call_user_func_array([$stmt, 'bind_result'], $bind)) {
        return [];
    }
    while (mysqli_stmt_fetch($stmt)) {
        $copy = [];
        foreach ($row as $k => $v) {
            $copy[$k] = $v;
        }
        $out[] = $copy;
    }
    return $out;
}

/**
 * Genera el PDF de una factura (copias en Impresiones/Facturas/*_pdf_archivo.php).
 * Sucursal: `invoices/sucursales/{id}/{año}/{facturas|facturas_simplificadas|facturas_rectificativas}/`.
 * Empresa: `invoices/empresa/{id}/`.
 *
 * @param int    $id_factura        PK en la tabla correspondiente al tipo
 * @param string $tipo_factura      factura | factura_simplificada | factura_simplificada_renovaciones | factura_rectificativa
 * @param string $origen_factura    sucursal | empresa (filtra por id_sucursal o rel_id_empresa)
 * @param int    $id_origen_factura id_sucursal o id_empresa
 * @return string Ruta absoluta del .pdf guardado
 */
function generarPdfFacturaBinario($id_factura, $tipo_factura, $origen_factura, $id_origen_factura)
{
    $id_factura = (int) $id_factura;
    $id_origen_factura = (int) $id_origen_factura;
    if ($id_factura <= 0 || $id_origen_factura <= 0) {
        throw new Exception('generarPdfFacturaBinario: id_factura e id_origen deben ser mayores que 0');
    }

    $tipo_factura = strtolower(trim((string) $tipo_factura));
    $origen_factura = strtolower(trim((string) $origen_factura));

    $mapScript = [
        'factura' => 'factura_pdf_archivo.php',
        'factura_simplificada' => 'factura_simplificada_pdf_archivo.php',
        'factura_simplificada_renovaciones' => 'factura_simplificada_renovaciones_pdf_archivo.php',
        'factura_rectificativa' => 'factura_rectificativa_pdf_archivo.php',
        'factura_rectificativa_simplificada' => 'factura_rectificativa_simplificada_pdf_archivo.php',
    ];
    $tablaFact = [
        'factura' => 'facturas',
        'factura_simplificada' => 'facturas_simplificadas',
        'factura_simplificada_renovaciones' => 'facturas_simplificadas',
        'factura_rectificativa' => 'facturas_rectificativas',
        'factura_rectificativa_simplificada' => 'facturas_rectificativas_simplificadas',
    ];
    if (!isset($mapScript[$tipo_factura]) || !isset($tablaFact[$tipo_factura])) {
        throw new Exception('generarPdfFacturaBinario: tipo_factura no válido');
    }

    if ($origen_factura !== 'sucursal' && $origen_factura !== 'empresa') {
        throw new Exception('generarPdfFacturaBinario: origen_factura debe ser sucursal o empresa');
    }

    $tabla = $tablaFact[$tipo_factura];
    $conexion = conectar_bd();
    $ok = false;
    $factura_regimen = 'false';
    $id_rel_factura_fiskaly = 0;
    $origen_simplificada_get = '';
    $esTipoSimplificada = in_array(
        $tipo_factura,
        ['factura_simplificada', 'factura_simplificada_renovaciones'],
        true
    );

    $intentarTabla = static function ($conexion, $tablaBuscar, $id_factura, $origen_factura, $id_origen_factura, $exigeSimplificada) {
        if ($tablaBuscar === 'facturas_simplificadas') {
            if ($origen_factura === 'sucursal') {
                $sql = 'SELECT factura_regimen, id_rel_factura_fiskaly, tipo_factura
                        FROM facturas_simplificadas WHERE id_factura = ? AND id_sucursal = ? LIMIT 1';
            } else {
                $sql = 'SELECT factura_regimen, id_rel_factura_fiskaly, tipo_factura
                        FROM facturas_simplificadas WHERE id_factura = ? AND rel_id_empresa = ? LIMIT 1';
            }
        } elseif ($tablaBuscar === 'facturas') {
            $extra = $exigeSimplificada ? " AND factura_simplificada = 'true'" : '';
            if ($origen_factura === 'sucursal') {
                $sql = "SELECT factura_regimen, id_rel_factura_fiskaly, tipo_factura, factura_simplificada
                        FROM facturas WHERE id_factura = ? AND id_sucursal = ?{$extra} LIMIT 1";
            } else {
                $sql = "SELECT factura_regimen, id_rel_factura_fiskaly, tipo_factura, factura_simplificada
                        FROM facturas WHERE id_factura = ? AND rel_id_empresa = ?{$extra} LIMIT 1";
            }
        } else {
            if ($origen_factura === 'sucursal') {
                $sql = "SELECT factura_regimen, id_rel_factura_fiskaly FROM `{$tablaBuscar}` WHERE id_factura = ? AND id_sucursal = ? LIMIT 1";
            } else {
                $sql = "SELECT factura_regimen, id_rel_factura_fiskaly FROM `{$tablaBuscar}` WHERE id_factura = ? AND rel_id_empresa = ? LIMIT 1";
            }
        }
        $st = mysqli_prepare($conexion, $sql);
        if (!$st) {
            return null;
        }
        mysqli_stmt_bind_param($st, 'ii', $id_factura, $id_origen_factura);
        mysqli_stmt_execute($st);
        $res = mysqli_stmt_get_result($st);
        $row = $res ? mysqli_fetch_assoc($res) : null;
        mysqli_stmt_close($st);

        return $row ?: null;
    };

    $rowFact = $intentarTabla($conexion, $tabla, $id_factura, $origen_factura, $id_origen_factura, false);
    if ($rowFact) {
        $ok = true;
        if ($esTipoSimplificada) {
            $origen_simplificada_get = ($tabla === 'facturas') ? 'facturas' : 'historico';
        }
        if ($tipo_factura === 'factura'
            && isset($rowFact['factura_simplificada'])
            && (string) $rowFact['factura_simplificada'] === 'true'
        ) {
            $tipoDb = strtolower(trim((string) ($rowFact['tipo_factura'] ?? 'articulos')));
            $tipo_factura = ($tipoDb === 'renovaciones')
                ? 'factura_simplificada_renovaciones'
                : 'factura_simplificada';
            $origen_simplificada_get = 'facturas';
            $esTipoSimplificada = true;
        }
    } elseif ($esTipoSimplificada) {
        // Nuevas simplificadas viven en `facturas`
        $rowFact = $intentarTabla($conexion, 'facturas', $id_factura, $origen_factura, $id_origen_factura, true);
        if ($rowFact) {
            $ok = true;
            $origen_simplificada_get = 'facturas';
        }
    }

    if ($ok && $rowFact) {
        $factura_regimen = (string) ($rowFact['factura_regimen'] ?? 'false');
        $id_rel_factura_fiskaly = (int) ($rowFact['id_rel_factura_fiskaly'] ?? 0);
    }
    mysqli_close($conexion);

    if (!$ok) {
        throw new Exception('generarPdfFacturaBinario: la factura no existe o no coincide con el origen indicado');
    }

    $pdfFiskaly = fiskalyResolverGeneracionPdf($tipo_factura, $factura_regimen, $id_rel_factura_fiskaly);
    $scriptName = $pdfFiskaly['script'];

    $facturaDir = realpath(dirname(__DIR__) . '/Impresiones/Facturas');
    if ($facturaDir === false || !is_dir($facturaDir)) {
        throw new Exception('generarPdfFacturaBinario: no se encuentra Impresiones/Facturas');
    }

    $scriptFile = $facturaDir . '/' . $scriptName;
    if (!is_readable($scriptFile)) {
        throw new Exception('generarPdfFacturaBinario: no se encuentra el generador: ' . basename($scriptFile));
    }

    $tipoFacturaGeneracion = $tipo_factura;
    $prevCwd = getcwd();
    $pdfBinary = null;
    try {
        chdir($facturaDir);
        $getParams = array_merge(['id_factura' => $id_factura], $pdfFiskaly['get']);
        if ($origen_simplificada_get !== '') {
            $getParams['origen'] = $origen_simplificada_get;
        }
        $_GET = $getParams;
        if (!defined('FACTURA_MPDF_BUFFER_MODE')) {
            define('FACTURA_MPDF_BUFFER_MODE', true);
        }
        ob_start();
        $pdfBinary = require $scriptFile;
        $basura = ob_get_clean();
        if (is_string($basura) && $basura !== '') {
            insertErrorLog('generarPdfFacturaBinario: salida capturada al generar PDF (' . strlen($basura) . ' bytes)');
        }
    } finally {
        $tipo_factura = $tipoFacturaGeneracion;
        if ($prevCwd !== false) {
            @chdir($prevCwd);
        }
    }

    if (!is_string($pdfBinary) || $pdfBinary === '') {
        throw new Exception('generarPdfFacturaBinario: no se pudo generar el PDF');
    }

    return $pdfBinary;
}

function generarPdfFactura($id_factura, $tipo_factura, $origen_factura, $id_origen_factura)
{
    $id_factura = (int) $id_factura;
    $id_origen_factura = (int) $id_origen_factura;
    if ($id_factura <= 0 || $id_origen_factura <= 0) {
        throw new Exception('generarPdfFactura: id_factura e id_origen deben ser mayores que 0');
    }

    $tipo_factura = strtolower(trim((string) $tipo_factura));
    $origen_factura = strtolower(trim((string) $origen_factura));

    $mapScript = [
        'factura' => 'factura_pdf_archivo.php',
        'factura_simplificada' => 'factura_simplificada_pdf_archivo.php',
        'factura_simplificada_renovaciones' => 'factura_simplificada_renovaciones_pdf_archivo.php',
        'factura_rectificativa' => 'factura_rectificativa_pdf_archivo.php',
        'factura_rectificativa_simplificada' => 'factura_rectificativa_simplificada_pdf_archivo.php',
    ];
    if (!isset($mapScript[$tipo_factura])) {
        throw new Exception('generarPdfFactura: tipo_factura no válido');
    }

    if ($origen_factura !== 'sucursal' && $origen_factura !== 'empresa') {
        throw new Exception('generarPdfFactura: origen_factura debe ser sucursal o empresa');
    }

    $pdfBinary = generarPdfFacturaBinario($id_factura, $tipo_factura, $origen_factura, $id_origen_factura);

    $root = dirname(__DIR__);
    $anio = date('Y');
    $mapSubcarpetaTipo = [
        'factura' => 'facturas',
        'factura_simplificada' => 'facturas_simplificadas',
        'factura_simplificada_renovaciones' => 'facturas_simplificadas',
        'factura_rectificativa' => 'facturas_rectificativas',
        'factura_rectificativa_simplificada' => 'facturas_rectificativas_simplificadas',
    ];
    $subcarpeta = $mapSubcarpetaTipo[$tipo_factura];

    if ($origen_factura === 'sucursal') {
        if (!existenCarpetasFacturasSucursal($id_origen_factura)) {
            crearCarpetaFacturasSucursal($id_origen_factura);
        }
        $destDir = $root . DIRECTORY_SEPARATOR . 'invoices' . DIRECTORY_SEPARATOR . 'sucursales'
            . DIRECTORY_SEPARATOR . $id_origen_factura . DIRECTORY_SEPARATOR . $anio . DIRECTORY_SEPARATOR . $subcarpeta;
    } else {
        $destDir = $root . DIRECTORY_SEPARATOR . 'invoices' . DIRECTORY_SEPARATOR . 'empresa'
            . DIRECTORY_SEPARATOR . $id_origen_factura;
    }

    if (!is_dir($destDir) && !@mkdir($destDir, 0755, true)) {
        throw new Exception('generarPdfFactura: no se pudo crear el directorio: ' . $destDir);
    }

    if ($tipo_factura === 'factura') {
        $suffix = 'factura';
    } elseif ($tipo_factura === 'factura_simplificada') {
        $suffix = 'simplificada';
    } elseif ($tipo_factura === 'factura_simplificada_renovaciones') {
        $suffix = 'simplificada_renovaciones';
    } elseif ($tipo_factura === 'factura_rectificativa_simplificada') {
        $suffix = 'rectificativa_simplificada';
    } elseif ($tipo_factura === 'factura_rectificativa') {
        $suffix = 'rectificativa';
    }
    $destFile = $destDir . '/factura_' . $id_factura . '_' . $suffix . '.pdf';
    if (file_put_contents($destFile, $pdfBinary) === false) {
        throw new Exception('generarPdfFactura: error al escribir el archivo');
    }

    return realpath($destFile) ?: $destFile;
}

/**
 * Crea la jerarquía `invoices/sucursales/{id_sucursal}/{año actual}/facturas`, `facturas_simplificadas`, `facturas_rectificativas`, `facturas_rectificativas_simplificadas`.
 *
 * @param int $id_sucursal Si no es positivo, no hace nada
 * @throws Exception Si falla la creación de algún directorio
 */
function crearCarpetaFacturasSucursal($id_sucursal)
{
    $id_sucursal = (int) $id_sucursal;
    if ($id_sucursal <= 0) {
        return;
    }

    $root_proyecto = dirname(__DIR__);
    $anio_actual = date('Y');
    $base_sucursal = $root_proyecto . DIRECTORY_SEPARATOR . 'invoices' . DIRECTORY_SEPARATOR . 'sucursales'
        . DIRECTORY_SEPARATOR . $id_sucursal . DIRECTORY_SEPARATOR . $anio_actual;
    $subcarpetas = array('facturas', 'facturas_simplificadas', 'facturas_rectificativas', 'facturas_rectificativas_simplificadas');
    foreach ($subcarpetas as $sub) {
        $ruta = $base_sucursal . DIRECTORY_SEPARATOR . $sub;
        if (!is_dir($ruta) && !mkdir($ruta, 0755, true)) {
            throw new Exception('No se pudo crear la carpeta: ' . $ruta);
        }
    }
}

/**
 * Comprueba si existen las carpetas de facturas de la sucursal para el año actual
 * (`invoices/sucursales/{id}/{año}/facturas`, `facturas_simplificadas`, `facturas_rectificativas`, `facturas_rectificativas_simplificadas`).
 *
 * @param int $id_sucursal
 * @return bool true solo si las tres subcarpetas existen y son directorios
 */
function existenCarpetasFacturasSucursal($id_sucursal)
{
    $id_sucursal = (int) $id_sucursal;
    if ($id_sucursal <= 0) {
        return false;
    }

    $root_proyecto = dirname(__DIR__);
    $anio_actual = date('Y');
    $base_sucursal = $root_proyecto . DIRECTORY_SEPARATOR . 'invoices' . DIRECTORY_SEPARATOR . 'sucursales'
        . DIRECTORY_SEPARATOR . $id_sucursal . DIRECTORY_SEPARATOR . $anio_actual;
    $subcarpetas = array('facturas', 'facturas_simplificadas', 'facturas_rectificativas', 'facturas_rectificativas_simplificadas');
    foreach ($subcarpetas as $sub) {
        $ruta = $base_sucursal . DIRECTORY_SEPARATOR . $sub;
        if (!is_dir($ruta)) {
            return false;
        }
    }

    return true;
}


/**
 * Construye el prefijo de factura según sucursal, si es simplificada y el tipo.
 * Renovaciones: prefijo base + "GC" (Guardia y Custodia). Longitud máx. 10.
 *
 * @param int|string $id_sucursal
 * @param bool|string $simplificada true/'true' → prefijo_factura_simplificada; si no → inicio_facturas
 * @param string $tipo_factura articulos|oro_inversion|renovaciones
 */
function facturaConstruirPrefijo($id_sucursal, $simplificada = false, $tipo_factura = 'articulos')
{
    $id_sucursal = (int) $id_sucursal;
    $es_simplificada = ($simplificada === true || $simplificada === 'true' || $simplificada === 1 || $simplificada === '1');
    $tipo = strtolower(trim((string) $tipo_factura));
    if (!in_array($tipo, ['articulos', 'oro_inversion', 'renovaciones'], true)) {
        $tipo = 'articulos';
    }

    $conexion = conectar_bd();
    $stmt = mysqli_prepare(
        $conexion,
        'SELECT TRIM(COALESCE(inicio_facturas, "")) AS pref_normal,
                TRIM(COALESCE(prefijo_factura_simplificada, "")) AS pref_simplificada
         FROM sucursal WHERE id_sucursal = ? LIMIT 1'
    );
    if (!$stmt) {
        $err = mysqli_error($conexion);
        mysqli_close($conexion);
        throw new Exception('facturaConstruirPrefijo: ' . $err);
    }
    mysqli_stmt_bind_param($stmt, 'i', $id_sucursal);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $row = $res ? mysqli_fetch_assoc($res) : null;
    mysqli_stmt_close($stmt);
    mysqli_close($conexion);

    $base = $es_simplificada
        ? (string) ($row['pref_simplificada'] ?? '')
        : (string) ($row['pref_normal'] ?? '');
    $base = substr(trim($base), 0, 8);

    if ($tipo === 'renovaciones') {
        return substr($base . 'GC', 0, 10);
    }

    return substr($base, 0, 10);
}

/**
 * Prefijo de factura rectificativa según tipo:
 * - renovaciones → prefijo base + RGC (ej. gytRGC / gytsRGC)
 * - articulos / oro_inversion completa → inicio_facturas + R (ej. gytR)
 * - articulos / oro_inversion simplificada → prefijo_factura_simplificada + SR (ej. gytsSR)
 *
 * @param int|string $id_sucursal
 * @param bool|string $simplificada
 * @param string $tipo_factura articulos|oro_inversion|renovaciones
 */
function facturaConstruirPrefijoRectificativa($id_sucursal, $simplificada = false, $tipo_factura = 'articulos')
{
    $id_sucursal = (int) $id_sucursal;
    $es_simplificada = ($simplificada === true || $simplificada === 'true' || $simplificada === 1 || $simplificada === '1');
    $tipo = strtolower(trim((string) $tipo_factura));
    if (!in_array($tipo, ['articulos', 'oro_inversion', 'renovaciones'], true)) {
        $tipo = 'articulos';
    }

    $conexion = conectar_bd();
    $stmt = mysqli_prepare(
        $conexion,
        'SELECT TRIM(COALESCE(inicio_facturas, "")) AS pref_normal,
                TRIM(COALESCE(prefijo_factura_simplificada, "")) AS pref_simplificada
         FROM sucursal WHERE id_sucursal = ? LIMIT 1'
    );
    if (!$stmt) {
        $err = mysqli_error($conexion);
        mysqli_close($conexion);
        throw new Exception('facturaConstruirPrefijoRectificativa: ' . $err);
    }
    mysqli_stmt_bind_param($stmt, 'i', $id_sucursal);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $row = $res ? mysqli_fetch_assoc($res) : null;
    mysqli_stmt_close($stmt);
    mysqli_close($conexion);

    if ($tipo === 'renovaciones') {
        $base = $es_simplificada
            ? (string) ($row['pref_simplificada'] ?? '')
            : (string) ($row['pref_normal'] ?? '');
        $base = substr(trim($base), 0, 7);

        return substr($base . 'RGC', 0, 10);
    }

    // Artículos / oro inversión: R (completa) o SR (simplificada)
    if ($es_simplificada) {
        $base = substr(trim((string) ($row['pref_simplificada'] ?? '')), 0, 8);

        return substr($base . 'SR', 0, 10);
    }

    $base = substr(trim((string) ($row['pref_normal'] ?? '')), 0, 9);

    return substr($base . 'R', 0, 10);
}

/**
 * Siguiente número de factura por sucursal.
 * Series:
 * - articulos / oro_inversion: correlativa conjunta (completa + simplificada) en `facturas`
 * - renovaciones: serie independiente; primera factura del sistema nuevo = YYYY00001
 *
 * @param int|string $sucursal_consulta
 * @param string $tipo_factura articulos|oro_inversion|renovaciones
 */
function obtenerNumeroFactura($sucursal_consulta, $tipo_factura = 'articulos') {
    $conexion = conectar_bd();

    $sucursal_id = (int)$sucursal_consulta;
    $ano_hoy = date('Y');
    $tipo = strtolower(trim((string) $tipo_factura));
    if (!in_array($tipo, ['articulos', 'oro_inversion', 'renovaciones'], true)) {
        $tipo = 'articulos';
    }
    
    // 1) Leer configuración de la sucursal
    $stmt_sucu = mysqli_prepare(
        $conexion,
        "SELECT reiniciar_numero_factura FROM sucursal WHERE id_sucursal = ? LIMIT 1"
    );
    
    if (!$stmt_sucu) {
        $err = mysqli_error($conexion);
        mysqli_close($conexion);
        throw new Exception("Error al preparar consulta de sucursal: " . $err);
    }
    
    mysqli_stmt_bind_param($stmt_sucu, "i", $sucursal_id);
    mysqli_stmt_execute($stmt_sucu);
    $res_sucu = mysqli_stmt_get_result($stmt_sucu);
    $row_sucu = $res_sucu ? mysqli_fetch_assoc($res_sucu) : null;
    mysqli_stmt_close($stmt_sucu);
    
    $reiniciar_raw = $row_sucu['reiniciar_numero_factura'] ?? 'false';
    $reiniciar_numero_factura = (strtolower((string)$reiniciar_raw) === 'true' || (string)$reiniciar_raw === '1');

    // Renovaciones: serie propia; si no hay ninguna aún en `facturas`, arrancar YYYY00001
    if ($tipo === 'renovaciones') {
        $stmt_fact = mysqli_prepare(
            $conexion,
            "SELECT fecha_factura, numero_factura
             FROM facturas
             WHERE id_sucursal = ? AND tipo_factura = 'renovaciones'
             ORDER BY id_factura DESC
             LIMIT 1"
        );
        if (!$stmt_fact) {
            $err = mysqli_error($conexion);
            mysqli_close($conexion);
            throw new Exception("Error al preparar consulta de facturas renovaciones: " . $err);
        }
        mysqli_stmt_bind_param($stmt_fact, "i", $sucursal_id);
        mysqli_stmt_execute($stmt_fact);
        $res_fact = mysqli_stmt_get_result($stmt_fact);
        $row_fact = $res_fact ? mysqli_fetch_assoc($res_fact) : null;
        mysqli_stmt_close($stmt_fact);

        if (!$row_fact) {
            mysqli_close($conexion);
            return (string) ($ano_hoy . '00001');
        }

        $numero_factura_actual = (string) ($row_fact['numero_factura'] ?? '0');
        $fecha_factura = (string) ($row_fact['fecha_factura'] ?? '');
        $ano_fecha = $fecha_factura ? date('Y', strtotime($fecha_factura)) : $ano_hoy;
        $numero_siguiente = (string) ((int) $numero_factura_actual + 1);
        if ($reiniciar_numero_factura && (int) $ano_hoy > (int) $ano_fecha) {
            $numero_siguiente = $ano_hoy . '00001';
        }
        mysqli_close($conexion);
        return (string) $numero_siguiente;
    }
    
    // 2) Serie articulos + oro_inversion (completa y simplificada unificadas)
    $stmt_fact = mysqli_prepare(
        $conexion,
        "SELECT fecha_factura, numero_factura
         FROM facturas
         WHERE id_sucursal = ?
           AND tipo_factura IN ('articulos', 'oro_inversion')
         ORDER BY id_factura DESC
         LIMIT 1"
    );
    
    if (!$stmt_fact) {
        $err = mysqli_error($conexion);
        mysqli_close($conexion);
        throw new Exception("Error al preparar consulta de facturas: " . $err);
    }
    
    mysqli_stmt_bind_param($stmt_fact, "i", $sucursal_id);
    mysqli_stmt_execute($stmt_fact);
    $res_fact = mysqli_stmt_get_result($stmt_fact);
    $row_fact = $res_fact ? mysqli_fetch_assoc($res_fact) : null;
    mysqli_stmt_close($stmt_fact);
    
    // Si no hay facturas aún, arrancar según configuración
    if (!$row_fact) {
        $numero = $reiniciar_numero_factura ? ($ano_hoy . "00001") : "1";
        mysqli_close($conexion);
        return (string)$numero;
    }
    
    $numero_factura_actual = (string)($row_fact['numero_factura'] ?? '0');
    $fecha_factura = (string)($row_fact['fecha_factura'] ?? '');
    $ano_fecha = $fecha_factura ? date('Y', strtotime($fecha_factura)) : $ano_hoy;
    
    $numero_siguiente = (string)((int)$numero_factura_actual + 1);
    
    if ($reiniciar_numero_factura && (int)$ano_hoy > (int)$ano_fecha) {
        $numero_siguiente = $ano_hoy . "00001";
    }

    mysqli_close($conexion);

    return (string)$numero_siguiente;
}

/**
 * Siguiente número de factura rectificativa en `facturas_rectificativas`.
 * Series:
 * - articulos / oro_inversion: correlativa conjunta
 * - renovaciones: serie independiente (primera = YYYY00001)
 *
 * @param int|string $sucursal_consulta
 * @param string $tipo_factura articulos|oro_inversion|renovaciones
 */
function obtenerNumeroFacturaRectificativa($sucursal_consulta, $tipo_factura = 'articulos') {
    $conexion = conectar_bd();

    $sucursal_id = (int)$sucursal_consulta;
    $ano_hoy = date('Y');
    $tipo = strtolower(trim((string) $tipo_factura));
    if (!in_array($tipo, ['articulos', 'oro_inversion', 'renovaciones'], true)) {
        $tipo = 'articulos';
    }

    $stmt_sucu = mysqli_prepare(
        $conexion,
        "SELECT reiniciar_numero_factura FROM sucursal WHERE id_sucursal = ? LIMIT 1"
    );

    if (!$stmt_sucu) {
        $err = mysqli_error($conexion);
        mysqli_close($conexion);
        throw new Exception("Error al preparar consulta de sucursal: " . $err);
    }

    mysqli_stmt_bind_param($stmt_sucu, "i", $sucursal_id);
    mysqli_stmt_execute($stmt_sucu);
    $res_sucu = mysqli_stmt_get_result($stmt_sucu);
    $row_sucu = $res_sucu ? mysqli_fetch_assoc($res_sucu) : null;
    mysqli_stmt_close($stmt_sucu);

    $reiniciar_raw = $row_sucu['reiniciar_numero_factura'] ?? 'false';
    $reiniciar_numero_factura = (strtolower((string)$reiniciar_raw) === 'true' || (string)$reiniciar_raw === '1');

    if ($tipo === 'renovaciones') {
        $stmt_fact = mysqli_prepare(
            $conexion,
            "SELECT fecha_factura, numero_factura
             FROM facturas_rectificativas
             WHERE id_sucursal = ? AND tipo_factura = 'renovaciones'
             ORDER BY id_factura DESC
             LIMIT 1"
        );
        if (!$stmt_fact) {
            $err = mysqli_error($conexion);
            mysqli_close($conexion);
            throw new Exception("Error al preparar consulta de facturas_rectificativas renovaciones: " . $err);
        }
        mysqli_stmt_bind_param($stmt_fact, "i", $sucursal_id);
        mysqli_stmt_execute($stmt_fact);
        $res_fact = mysqli_stmt_get_result($stmt_fact);
        $row_fact = $res_fact ? mysqli_fetch_assoc($res_fact) : null;
        mysqli_stmt_close($stmt_fact);

        if (!$row_fact) {
            mysqli_close($conexion);
            return (string) ($ano_hoy . '00001');
        }

        $numero_factura_actual = (string) ($row_fact['numero_factura'] ?? '0');
        $fecha_factura = (string) ($row_fact['fecha_factura'] ?? '');
        $ano_fecha = $fecha_factura ? date('Y', strtotime($fecha_factura)) : $ano_hoy;
        $numero_siguiente = (string) ((int) $numero_factura_actual + 1);
        if ($reiniciar_numero_factura && (int) $ano_hoy > (int) $ano_fecha) {
            $numero_siguiente = $ano_hoy . '00001';
        }
        mysqli_close($conexion);
        return (string) $numero_siguiente;
    }

    $stmt_fact = mysqli_prepare(
        $conexion,
        "SELECT fecha_factura, numero_factura
         FROM facturas_rectificativas
         WHERE id_sucursal = ?
           AND tipo_factura IN ('articulos', 'oro_inversion')
         ORDER BY id_factura DESC
         LIMIT 1"
    );

    if (!$stmt_fact) {
        $err = mysqli_error($conexion);
        mysqli_close($conexion);
        throw new Exception("Error al preparar consulta de facturas_rectificativas: " . $err);
    }

    mysqli_stmt_bind_param($stmt_fact, "i", $sucursal_id);
    mysqli_stmt_execute($stmt_fact);
    $res_fact = mysqli_stmt_get_result($stmt_fact);
    $row_fact = $res_fact ? mysqli_fetch_assoc($res_fact) : null;
    mysqli_stmt_close($stmt_fact);

    if (!$row_fact) {
        $numero = $reiniciar_numero_factura ? ($ano_hoy . "00001") : "1";
        mysqli_close($conexion);
        return (string)$numero;
    }

    $numero_factura_actual = (string)($row_fact['numero_factura'] ?? '0');
    $fecha_factura = (string)($row_fact['fecha_factura'] ?? '');
    $ano_fecha = $fecha_factura ? date('Y', strtotime($fecha_factura)) : $ano_hoy;

    $numero_siguiente = (string)((int)$numero_factura_actual + 1);

    if ($reiniciar_numero_factura && (int)$ano_hoy > (int)$ano_fecha) {
        $numero_siguiente = $ano_hoy . "00001";
    }

    mysqli_close($conexion);

    return (string)$numero_siguiente;
}

/**
 * Siguiente número de factura rectificativa histórica en `facturas_rectificativas_simplificadas`.
 * Misma lógica de series que obtenerNumeroFacturaRectificativa.
 *
 * @param int|string $sucursal_consulta
 * @param string $tipo_factura articulos|oro_inversion|renovaciones
 */
function obtenerNumeroFacturaRectificativaSimplificadas($sucursal_consulta, $tipo_factura = 'articulos') {
    $conexion = conectar_bd();

    $sucursal_id = (int)$sucursal_consulta;
    $ano_hoy = date('Y');
    $tipo = strtolower(trim((string) $tipo_factura));
    if (!in_array($tipo, ['articulos', 'oro_inversion', 'renovaciones'], true)) {
        $tipo = 'articulos';
    }

    $stmt_sucu = mysqli_prepare(
        $conexion,
        "SELECT reiniciar_numero_factura FROM sucursal WHERE id_sucursal = ? LIMIT 1"
    );

    if (!$stmt_sucu) {
        $err = mysqli_error($conexion);
        mysqli_close($conexion);
        throw new Exception("Error al preparar consulta de sucursal: " . $err);
    }

    mysqli_stmt_bind_param($stmt_sucu, "i", $sucursal_id);
    mysqli_stmt_execute($stmt_sucu);
    $res_sucu = mysqli_stmt_get_result($stmt_sucu);
    $row_sucu = $res_sucu ? mysqli_fetch_assoc($res_sucu) : null;
    mysqli_stmt_close($stmt_sucu);

    $reiniciar_raw = $row_sucu['reiniciar_numero_factura'] ?? 'false';
    $reiniciar_numero_factura = (strtolower((string)$reiniciar_raw) === 'true' || (string)$reiniciar_raw === '1');

    if ($tipo === 'renovaciones') {
        $stmt_fact = mysqli_prepare(
            $conexion,
            "SELECT fecha_factura, numero_factura
             FROM facturas_rectificativas_simplificadas
             WHERE id_sucursal = ? AND tipo_factura = 'renovaciones'
             ORDER BY id_factura DESC
             LIMIT 1"
        );
        if (!$stmt_fact) {
            $err = mysqli_error($conexion);
            mysqli_close($conexion);
            throw new Exception("Error al preparar consulta de facturas_rectificativas_simplificadas renovaciones: " . $err);
        }
        mysqli_stmt_bind_param($stmt_fact, "i", $sucursal_id);
        mysqli_stmt_execute($stmt_fact);
        $res_fact = mysqli_stmt_get_result($stmt_fact);
        $row_fact = $res_fact ? mysqli_fetch_assoc($res_fact) : null;
        mysqli_stmt_close($stmt_fact);

        if (!$row_fact) {
            mysqli_close($conexion);
            return (string) ($ano_hoy . '00001');
        }

        $numero_factura_actual = (string) ($row_fact['numero_factura'] ?? '0');
        $fecha_factura = (string) ($row_fact['fecha_factura'] ?? '');
        $ano_fecha = $fecha_factura ? date('Y', strtotime($fecha_factura)) : $ano_hoy;
        $numero_siguiente = (string) ((int) $numero_factura_actual + 1);
        if ($reiniciar_numero_factura && (int) $ano_hoy > (int) $ano_fecha) {
            $numero_siguiente = $ano_hoy . '00001';
        }
        mysqli_close($conexion);
        return (string) $numero_siguiente;
    }

    $stmt_fact = mysqli_prepare(
        $conexion,
        "SELECT fecha_factura, numero_factura
         FROM facturas_rectificativas_simplificadas
         WHERE id_sucursal = ?
           AND tipo_factura IN ('articulos', 'oro_inversion')
         ORDER BY id_factura DESC
         LIMIT 1"
    );

    if (!$stmt_fact) {
        $err = mysqli_error($conexion);
        mysqli_close($conexion);
        throw new Exception("Error al preparar consulta de facturas_rectificativas_simplificadas: " . $err);
    }

    mysqli_stmt_bind_param($stmt_fact, "i", $sucursal_id);
    mysqli_stmt_execute($stmt_fact);
    $res_fact = mysqli_stmt_get_result($stmt_fact);
    $row_fact = $res_fact ? mysqli_fetch_assoc($res_fact) : null;
    mysqli_stmt_close($stmt_fact);

    if (!$row_fact) {
        $numero = $reiniciar_numero_factura ? ($ano_hoy . "00001") : "1";
        mysqli_close($conexion);
        return (string)$numero;
    }

    $numero_factura_actual = (string)($row_fact['numero_factura'] ?? '0');
    $fecha_factura = (string)($row_fact['fecha_factura'] ?? '');
    $ano_fecha = $fecha_factura ? date('Y', strtotime($fecha_factura)) : $ano_hoy;

    $numero_siguiente = (string)((int)$numero_factura_actual + 1);

    if ($reiniciar_numero_factura && (int)$ano_hoy > (int)$ano_fecha) {
        $numero_siguiente = $ano_hoy . "00001";
    }

    mysqli_close($conexion);

    return (string)$numero_siguiente;
}

function crearFactura(array $datos) {
    if (!array_key_exists('id_sucursal', $datos)) {
        throw new Exception('crearFactura: falta id_sucursal');
    }
    if (!array_key_exists('numero_factura', $datos) || $datos['numero_factura'] === '' || $datos['numero_factura'] === null) {
        throw new Exception('crearFactura: falta numero_factura');
    }

    $estados_ok = ['nopagada', 'pagada', 'anulada'];
    $tipos_ok = ['articulos', 'renovaciones', 'oro_inversion'];
    $regimen_ok = ['false', 'Verifactu', 'TicketBAIBizkaia', 'TicketBAIAlava', 'TicketBAIGipuzkua', 'General'];
    $simplificada_ok = ['false', 'true'];

    $defaults = [
        'cliente_factura' => 0,
        'facturado_por' => 0,
        'estado_factura' => 'nopagada',
        'tipo_pago_factura' => '',
        'total_factura' => 0.0,
        'rel_id_venta' => 0,
        'fecha_anulacion' => '0000-00-00 00:00:00',
        'prefijo_factura' => '',
        'tipo_factura' => 'articulos',
        'factura_simplificada' => 'false',
        'rel_id_lote' => 0,
        'rel_id_renovacion' => 0,
        'factura_regimen' => 'false',
        'id_rel_factura_fiskaly' => 0,
        'rel_id_empresa' => 0,
    ];

    $d = array_merge($defaults, $datos);

    $id_sucursal = (int)$d['id_sucursal'];
    $numero_factura = (int)$d['numero_factura'];
    $cliente_factura = (int)$d['cliente_factura'];
    $facturado_por = (int)$d['facturado_por'];
    $estado_factura = (string)$d['estado_factura'];
    if (!in_array($estado_factura, $estados_ok, true)) {
        $estado_factura = 'nopagada';
    }
    // facturas.tipo_pago_factura es VARCHAR(13): "transferencia" no cabe; usar etiqueta equivalente.
    $tipo_raw = trim((string) $d['tipo_pago_factura']);
    $tipo_map = [
        'transferencia' => 'transf. banco',
    ];
    $tipo_key = strtolower($tipo_raw);
    $tipo_pago_factura = $tipo_map[$tipo_key] ?? $tipo_raw;
    $tipo_pago_factura = substr($tipo_pago_factura, 0, 13);
    $total_factura = (float)$d['total_factura'];
    $rel_id_venta = (int)$d['rel_id_venta'];
    $fecha_anulacion = (string)$d['fecha_anulacion'];
    if ($fecha_anulacion === '') {
        $fecha_anulacion = '0000-00-00 00:00:00';
    }
    $prefijo_factura = substr((string)$d['prefijo_factura'], 0, 10);
    $tipo_factura = (string)$d['tipo_factura'];
    if (!in_array($tipo_factura, $tipos_ok, true)) {
        $tipo_factura = 'articulos';
    }
    $factura_simplificada = strtolower(trim((string) $d['factura_simplificada']));
    if (!in_array($factura_simplificada, $simplificada_ok, true)) {
        $factura_simplificada = 'false';
    }
    if ($factura_simplificada === 'true') {
        $cliente_factura = 0;
    }
    $rel_id_lote = (int)$d['rel_id_lote'];
    $rel_id_renovacion = (int)$d['rel_id_renovacion'];
    $factura_regimen = (string)$d['factura_regimen'];
    if (!in_array($factura_regimen, $regimen_ok, true)) {
        $factura_regimen = 'false';
    }
    $id_rel_factura_fiskaly = (int)$d['id_rel_factura_fiskaly'];
    $rel_id_empresa = (int)$d['rel_id_empresa'];

    $conexion = conectar_bd();

    $sql = "INSERT INTO facturas (
        id_sucursal, numero_factura, cliente_factura, facturado_por,
        estado_factura, tipo_pago_factura, total_factura,
        fecha_factura, hora_factura,
        rel_id_venta, fecha_anulacion, prefijo_factura, tipo_factura, factura_simplificada,
        rel_id_lote, rel_id_renovacion, factura_regimen,
        id_rel_factura_fiskaly, rel_id_empresa
    ) VALUES (
        ?, ?, ?, ?,
        ?, ?, ?,
        CURDATE(), CURTIME(),
        ?, ?, ?, ?, ?,
        ?, ?, ?,
        ?, ?
    )";

    $stmt = mysqli_prepare($conexion, $sql);
    if (!$stmt) {
        $err = mysqli_error($conexion);
        mysqli_close($conexion);
        throw new Exception('crearFactura: error al preparar INSERT: ' . $err);
    }

    mysqli_stmt_bind_param(
        $stmt,
        'iiiissdissssiisii',
        $id_sucursal,
        $numero_factura,
        $cliente_factura,
        $facturado_por,
        $estado_factura,
        $tipo_pago_factura,
        $total_factura,
        $rel_id_venta,
        $fecha_anulacion,
        $prefijo_factura,
        $tipo_factura,
        $factura_simplificada,
        $rel_id_lote,
        $rel_id_renovacion,
        $factura_regimen,
        $id_rel_factura_fiskaly,
        $rel_id_empresa
    );

    $ok = mysqli_stmt_execute($stmt);
    if (!$ok) {
        $errIns = mysqli_stmt_error($stmt);
        mysqli_stmt_close($stmt);
        mysqli_close($conexion);
        throw new Exception('crearFactura: error al insertar: ' . $errIns);
    }

    $id_insertado = (int) mysqli_insert_id($conexion);
    mysqli_stmt_close($stmt);
    mysqli_close($conexion);

    return $id_insertado;
}

function crearFacturaRectificativa(array $datos) {
    if (!array_key_exists('id_sucursal', $datos)) {
        throw new Exception('crearFacturaRectificativa: falta id_sucursal');
    }
    if (!array_key_exists('numero_factura', $datos) || $datos['numero_factura'] === '' || $datos['numero_factura'] === null) {
        throw new Exception('crearFacturaRectificativa: falta numero_factura');
    }

    $estados_ok = ['nopagada', 'pagada', 'anulada'];
    $tipos_ok = ['articulos', 'renovaciones', 'oro_inversion'];
    $regimen_ok = ['false', 'Verifactu', 'TicketBAIBizkaia', 'TicketBAIAlava', 'TicketBAIGipuzkua', 'General'];

    $defaults = [
        'cliente_factura' => 0,
        'facturado_por' => 0,
        'estado_factura' => 'nopagada',
        'tipo_pago_factura' => '',
        'total_factura' => 0.0,
        'rel_id_venta' => 0,
        'fecha_anulacion' => '0000-00-00 00:00:00',
        'prefijo_factura' => '',
        'tipo_factura' => 'articulos',
        'rel_id_lote' => 0,
        'rel_id_renovacion' => 0,
        'factura_regimen' => 'false',
        'id_rel_factura_fiskaly' => 0,
        'rel_id_empresa' => 0,
        'rel_id_factura' => 0,
        'factura_original' => 0,
        'motivo_rectificado' => '',
        'fecha_factura_original' => '',
        'prefijo_factura_original' => '',
    ];

    $d = array_merge($defaults, $datos);

    $id_sucursal = (int)$d['id_sucursal'];
    $numero_factura = (int)$d['numero_factura'];
    $cliente_factura = (int)$d['cliente_factura'];
    $facturado_por = (int)$d['facturado_por'];
    $estado_factura = (string)$d['estado_factura'];
    if (!in_array($estado_factura, $estados_ok, true)) {
        $estado_factura = 'nopagada';
    }
    // facturas_rectificativas.tipo_pago_factura es VARCHAR(13): "transferencia" no cabe; usar etiqueta equivalente.
    $tipo_raw = trim((string) $d['tipo_pago_factura']);
    $tipo_map = [
        'transferencia' => 'transf. banco',
    ];
    $tipo_key = strtolower($tipo_raw);
    $tipo_pago_factura = $tipo_map[$tipo_key] ?? $tipo_raw;
    $tipo_pago_factura = substr($tipo_pago_factura, 0, 13);
    $total_factura = (float)$d['total_factura'];
    $rel_id_venta = (int)$d['rel_id_venta'];
    $fecha_anulacion = (string)$d['fecha_anulacion'];
    if ($fecha_anulacion === '') {
        $fecha_anulacion = '0000-00-00 00:00:00';
    }
    $prefijo_factura = substr((string)$d['prefijo_factura'], 0, 5);
    $tipo_factura = (string)$d['tipo_factura'];
    if (!in_array($tipo_factura, $tipos_ok, true)) {
        $tipo_factura = 'articulos';
    }
    $rel_id_lote = (int)$d['rel_id_lote'];
    $rel_id_renovacion = (int)$d['rel_id_renovacion'];
    $factura_regimen = (string)$d['factura_regimen'];
    if (!in_array($factura_regimen, $regimen_ok, true)) {
        $factura_regimen = 'false';
    }
    $id_rel_factura_fiskaly = (int)$d['id_rel_factura_fiskaly'];
    $rel_id_empresa = (int)$d['rel_id_empresa'];
    $rel_id_factura = (int)$d['rel_id_factura'];
    $factura_original = (int)$d['factura_original'];
    $motivo_rectificado = (string)$d['motivo_rectificado'];
    $fecha_factura_original = (string)$d['fecha_factura_original'];
    $prefijo_factura_original = substr((string)$d['prefijo_factura_original'], 0, 5);

    $conexion = conectar_bd();

    $sql = "INSERT INTO facturas_rectificativas (
        id_sucursal, numero_factura, cliente_factura, facturado_por,
        estado_factura, tipo_pago_factura, total_factura,
        fecha_factura, hora_factura,
        rel_id_venta, fecha_anulacion, prefijo_factura, tipo_factura,
        rel_id_lote, rel_id_renovacion, factura_regimen,
        id_rel_factura_fiskaly, rel_id_empresa,
        rel_id_factura, factura_original, motivo_rectificado,
        fecha_factura_original, prefijo_factura_original
    ) VALUES (
        ?, ?, ?, ?,
        ?, ?, ?,
        CURDATE(), CURTIME(),
        ?, ?, ?, ?,
        ?, ?, ?,
        ?, ?,
        ?, ?, ?,
        ?, ?
    )";

    $stmt = mysqli_prepare($conexion, $sql);
    if (!$stmt) {
        $err = mysqli_error($conexion);
        mysqli_close($conexion);
        throw new Exception('crearFacturaRectificativa: error al preparar INSERT: ' . $err);
    }

    mysqli_stmt_bind_param(
        $stmt,
        'iiiissdisssiisiiiisss',
        $id_sucursal,
        $numero_factura,
        $cliente_factura,
        $facturado_por,
        $estado_factura,
        $tipo_pago_factura,
        $total_factura,
        $rel_id_venta,
        $fecha_anulacion,
        $prefijo_factura,
        $tipo_factura,
        $rel_id_lote,
        $rel_id_renovacion,
        $factura_regimen,
        $id_rel_factura_fiskaly,
        $rel_id_empresa,
        $rel_id_factura,
        $factura_original,
        $motivo_rectificado,
        $fecha_factura_original,
        $prefijo_factura_original
    );

    $ok = mysqli_stmt_execute($stmt);
    if (!$ok) {
        $errIns = mysqli_stmt_error($stmt);
        mysqli_stmt_close($stmt);
        mysqli_close($conexion);
        throw new Exception('crearFacturaRectificativa: error al insertar: ' . $errIns);
    }

    $id_insertado = (int) mysqli_insert_id($conexion);
    mysqli_stmt_close($stmt);
    mysqli_close($conexion);

    return $id_insertado;
}

function crearFacturaRectificativaSimplificadas(array $datos) {
    if (!array_key_exists('id_sucursal', $datos)) {
        throw new Exception('crearFacturaRectificativaSimplificadas: falta id_sucursal');
    }
    if (!array_key_exists('numero_factura', $datos) || $datos['numero_factura'] === '' || $datos['numero_factura'] === null) {
        throw new Exception('crearFacturaRectificativaSimplificadas: falta numero_factura');
    }

    $estados_ok = ['nopagada', 'pagada', 'anulada'];
    $tipos_ok = ['articulos', 'renovaciones', 'oro_inversion'];
    $regimen_ok = ['false', 'Verifactu', 'TicketBAIBizkaia', 'TicketBAIAlava', 'TicketBAIGipuzkua', 'General'];

    $defaults = [
        'cliente_factura' => 0,
        'facturado_por' => 0,
        'estado_factura' => 'nopagada',
        'tipo_pago_factura' => '',
        'total_factura' => 0.0,
        'rel_id_venta' => 0,
        'fecha_anulacion' => '0000-00-00 00:00:00',
        'prefijo_factura' => '',
        'tipo_factura' => 'articulos',
        'rel_id_lote' => 0,
        'rel_id_renovacion' => 0,
        'factura_regimen' => 'false',
        'id_rel_factura_fiskaly' => 0,
        'rel_id_empresa' => 0,
        'rel_id_factura' => 0,
        'factura_original' => 0,
        'motivo_rectificado' => '',
        'fecha_factura_original' => '',
        'prefijo_factura_original' => '',
    ];

    $d = array_merge($defaults, $datos);

    $id_sucursal = (int)$d['id_sucursal'];
    $numero_factura = (int)$d['numero_factura'];
    $cliente_factura = (int)$d['cliente_factura'];
    $facturado_por = (int)$d['facturado_por'];
    $estado_factura = (string)$d['estado_factura'];
    if (!in_array($estado_factura, $estados_ok, true)) {
        $estado_factura = 'nopagada';
    }
    // facturas_rectificativas_simplificadas.tipo_pago_factura es VARCHAR(13): "transferencia" no cabe; usar etiqueta equivalente.
    $tipo_raw = trim((string) $d['tipo_pago_factura']);
    $tipo_map = [
        'transferencia' => 'transf. banco',
    ];
    $tipo_key = strtolower($tipo_raw);
    $tipo_pago_factura = $tipo_map[$tipo_key] ?? $tipo_raw;
    $tipo_pago_factura = substr($tipo_pago_factura, 0, 13);
    $total_factura = (float)$d['total_factura'];
    $rel_id_venta = (int)$d['rel_id_venta'];
    $fecha_anulacion = (string)$d['fecha_anulacion'];
    if ($fecha_anulacion === '') {
        $fecha_anulacion = '0000-00-00 00:00:00';
    }
    $prefijo_factura = substr((string)$d['prefijo_factura'], 0, 5);
    $tipo_factura = (string)$d['tipo_factura'];
    if (!in_array($tipo_factura, $tipos_ok, true)) {
        $tipo_factura = 'articulos';
    }
    $rel_id_lote = (int)$d['rel_id_lote'];
    $rel_id_renovacion = (int)$d['rel_id_renovacion'];
    $factura_regimen = (string)$d['factura_regimen'];
    if (!in_array($factura_regimen, $regimen_ok, true)) {
        $factura_regimen = 'false';
    }
    $id_rel_factura_fiskaly = (int)$d['id_rel_factura_fiskaly'];
    $rel_id_empresa = (int)$d['rel_id_empresa'];
    $rel_id_factura = (int)$d['rel_id_factura'];
    $factura_original = (int)$d['factura_original'];
    $motivo_rectificado = (string)$d['motivo_rectificado'];
    $fecha_factura_original = (string)$d['fecha_factura_original'];
    $prefijo_factura_original = substr((string)$d['prefijo_factura_original'], 0, 5);

    $conexion = conectar_bd();

    $sql = "INSERT INTO facturas_rectificativas_simplificadas (
        id_sucursal, numero_factura, cliente_factura, facturado_por,
        estado_factura, tipo_pago_factura, total_factura,
        fecha_factura, hora_factura,
        rel_id_venta, fecha_anulacion, prefijo_factura, tipo_factura,
        rel_id_lote, rel_id_renovacion, factura_regimen,
        id_rel_factura_fiskaly, rel_id_empresa,
        rel_id_factura, factura_original, motivo_rectificado,
        fecha_factura_original, prefijo_factura_original
    ) VALUES (
        ?, ?, ?, ?,
        ?, ?, ?,
        CURDATE(), CURTIME(),
        ?, ?, ?, ?,
        ?, ?, ?,
        ?, ?,
        ?, ?, ?,
        ?, ?
    )";

    $stmt = mysqli_prepare($conexion, $sql);
    if (!$stmt) {
        $err = mysqli_error($conexion);
        mysqli_close($conexion);
        throw new Exception('crearFacturaRectificativaSimplificadas: error al preparar INSERT: ' . $err);
    }

    mysqli_stmt_bind_param(
        $stmt,
        'iiiissdisssiisiiiisss',
        $id_sucursal,
        $numero_factura,
        $cliente_factura,
        $facturado_por,
        $estado_factura,
        $tipo_pago_factura,
        $total_factura,
        $rel_id_venta,
        $fecha_anulacion,
        $prefijo_factura,
        $tipo_factura,
        $rel_id_lote,
        $rel_id_renovacion,
        $factura_regimen,
        $id_rel_factura_fiskaly,
        $rel_id_empresa,
        $rel_id_factura,
        $factura_original,
        $motivo_rectificado,
        $fecha_factura_original,
        $prefijo_factura_original
    );

    $ok = mysqli_stmt_execute($stmt);
    if (!$ok) {
        $errIns = mysqli_stmt_error($stmt);
        mysqli_stmt_close($stmt);
        mysqli_close($conexion);
        throw new Exception('crearFacturaRectificativaSimplificadas: error al insertar: ' . $errIns);
    }

    $id_insertado = (int) mysqli_insert_id($conexion);
    mysqli_stmt_close($stmt);
    mysqli_close($conexion);

    return $id_insertado;
}

/**
 * Compatibilidad: la numeración de simplificadas usa la misma serie que las normales
 * (articulos/oro_inversion) o la serie renovaciones según $tipo_factura.
 *
 * @param int|string $sucursal_consulta
 * @param string $tipo_factura
 */
function obtenerNumeroFacturaSimplificada($sucursal_consulta, $tipo_factura = 'articulos')
{
    return obtenerNumeroFactura($sucursal_consulta, $tipo_factura);
}

/**
 * Inserta factura simplificada en `facturas` con factura_simplificada = 'true'.
 * (Ya no escribe en facturas_simplificadas; el histórico anterior a hoy permanece allí.)
 */
function crearFacturaSimplificada(array $datos)
{
    $datos['cliente_factura'] = 0;
    $datos['factura_simplificada'] = 'true';

    return crearFactura($datos);
}

/**
 * Localiza una factura simplificada: unificada en `facturas` o histórico en `facturas_simplificadas`.
 *
 * @param int $id_factura
 * @param string $preferencia ''|facturas|historico — vacío: histórico primero (links antiguos), luego facturas
 * @return array{origen:string,factura_regimen:string,id_rel_factura_fiskaly:int,tipo_factura:string,id_sucursal:int,rel_id_empresa:int,prefijo_factura:string,numero_factura:int}|null
 */
function facturaSimplificadaResolverOrigen($id_factura, $preferencia = '')
{
    $id_factura = (int) $id_factura;
    if ($id_factura <= 0) {
        return null;
    }

    $preferencia = strtolower(trim((string) $preferencia));
    if ($preferencia === 'unificada') {
        $preferencia = 'facturas';
    }
    if ($preferencia !== 'facturas' && $preferencia !== 'historico') {
        $preferencia = '';
    }

    $conexion = conectar_bd();
    if (!$conexion) {
        return null;
    }

    $leerHistorico = static function ($conexion, $id_factura) {
        $stmt = mysqli_prepare(
            $conexion,
            'SELECT factura_regimen, id_rel_factura_fiskaly, tipo_factura, id_sucursal, rel_id_empresa,
                    prefijo_factura, numero_factura
             FROM facturas_simplificadas WHERE id_factura = ? LIMIT 1'
        );
        if (!$stmt) {
            return null;
        }
        mysqli_stmt_bind_param($stmt, 'i', $id_factura);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $row = $res ? mysqli_fetch_assoc($res) : null;
        mysqli_stmt_close($stmt);
        if (!$row) {
            return null;
        }

        return [
            'origen' => 'historico',
            'factura_regimen' => (string) ($row['factura_regimen'] ?? 'false'),
            'id_rel_factura_fiskaly' => (int) ($row['id_rel_factura_fiskaly'] ?? 0),
            'tipo_factura' => (string) ($row['tipo_factura'] ?? 'articulos'),
            'id_sucursal' => (int) ($row['id_sucursal'] ?? 0),
            'rel_id_empresa' => (int) ($row['rel_id_empresa'] ?? 0),
            'prefijo_factura' => (string) ($row['prefijo_factura'] ?? ''),
            'numero_factura' => (int) ($row['numero_factura'] ?? 0),
        ];
    };

    $leerUnificada = static function ($conexion, $id_factura) {
        $stmt = mysqli_prepare(
            $conexion,
            'SELECT factura_regimen, id_rel_factura_fiskaly, tipo_factura, id_sucursal, rel_id_empresa,
                    prefijo_factura, numero_factura
             FROM facturas
             WHERE id_factura = ? AND factura_simplificada = \'true\'
             LIMIT 1'
        );
        if (!$stmt) {
            return null;
        }
        mysqli_stmt_bind_param($stmt, 'i', $id_factura);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $row = $res ? mysqli_fetch_assoc($res) : null;
        mysqli_stmt_close($stmt);
        if (!$row) {
            return null;
        }

        return [
            'origen' => 'facturas',
            'factura_regimen' => (string) ($row['factura_regimen'] ?? 'false'),
            'id_rel_factura_fiskaly' => (int) ($row['id_rel_factura_fiskaly'] ?? 0),
            'tipo_factura' => (string) ($row['tipo_factura'] ?? 'articulos'),
            'id_sucursal' => (int) ($row['id_sucursal'] ?? 0),
            'rel_id_empresa' => (int) ($row['rel_id_empresa'] ?? 0),
            'prefijo_factura' => (string) ($row['prefijo_factura'] ?? ''),
            'numero_factura' => (int) ($row['numero_factura'] ?? 0),
        ];
    };

    $resultado = null;
    if ($preferencia === 'facturas') {
        $resultado = $leerUnificada($conexion, $id_factura);
    } elseif ($preferencia === 'historico') {
        $resultado = $leerHistorico($conexion, $id_factura);
    } else {
        $resultado = $leerHistorico($conexion, $id_factura);
        if ($resultado === null) {
            $resultado = $leerUnificada($conexion, $id_factura);
        }
    }

    mysqli_close($conexion);

    return $resultado;
}

/**
 * Tablas de líneas según origen de factura simplificada.
 *
 * @param string $origen facturas|historico
 * @return array{articulos:string,renovaciones:string,cabecera:string}
 */
function facturaSimplificadaTablasPorOrigen($origen)
{
    if ($origen === 'facturas') {
        return [
            'cabecera' => 'facturas',
            'articulos' => 'facturas_rel_articulos',
            'renovaciones' => 'facturas_rel_renovaciones',
        ];
    }

    return [
        'cabecera' => 'facturas_simplificadas',
        'articulos' => 'facturas_simplificadas_rel_articulos',
        'renovaciones' => 'facturas_simplificadas_rel_renovaciones',
    ];
}

/**
 * Líneas de factura (artículos). Las simplificadas nuevas usan `facturas_rel_articulos`.
 */
function insertarItemsFacturaSimplificada($items)
{
    return insertarItemsFactura($items);
}

/**
 * Líneas de renovaciones en `facturas_rel_renovaciones` (completa y simplificada unificadas).
 * IVA 21% incluido: `precio_venta_sin_iva = precio_rel_renovacion / 1.21`.
 * El histórico anterior a hoy sigue en `facturas_simplificadas_rel_renovaciones`.
 */
function insertarItemsRenovacionesFacturaSimplificada($items)
{
    if (isset($items['rel_id_factura'])) {
        $items = [$items];
    }
    if (!is_array($items) || $items === []) {
        throw new Exception('insertarItemsRenovacionesFacturaSimplificada: no hay líneas para insertar');
    }

    $defaults = [
        'rel_id_factura' => 0,
        'rel_id_item' => 0,
        'id_rel_renovacion' => 0,
        'id_rel_sucursal' => 0,
        'id_sucursal' => 0,
        'sucursal_venta' => 0,
        'descripcion_renovacion' => '',
        'precio_rel_renovacion' => 0.0,
        'total_linea' => 0.0,
    ];

    $conexion = conectar_bd();

    $sql = 'INSERT INTO facturas_rel_renovaciones (
        id_rel_sucursal,
        id_rel_factura,
        id_rel_renovacion,
        precio_rel_renovacion,
        descripcion_renovacion,
        precio_venta_sin_iva,
        fecha_factura
    ) VALUES (?, ?, ?, ?, ?, ?, NOW())';

    $stmt = mysqli_prepare($conexion, $sql);
    if (!$stmt) {
        $err = mysqli_error($conexion);
        mysqli_close($conexion);
        throw new Exception('insertarItemsRenovacionesFacturaSimplificada: error al preparar INSERT: ' . $err);
    }

    $ids = [];

    foreach ($items as $fila) {
        if (!is_array($fila) || !array_key_exists('rel_id_factura', $fila)) {
            mysqli_stmt_close($stmt);
            mysqli_close($conexion);
            throw new Exception('insertarItemsRenovacionesFacturaSimplificada: cada línea debe incluir rel_id_factura');
        }

        $d = array_merge($defaults, $fila);

        $id_rel_sucursal = (int) ($d['id_rel_sucursal'] ?: $d['id_sucursal'] ?: $d['sucursal_venta']);
        if ($id_rel_sucursal <= 0) {
            mysqli_stmt_close($stmt);
            mysqli_close($conexion);
            throw new Exception('insertarItemsRenovacionesFacturaSimplificada: falta id_rel_sucursal (o id_sucursal / sucursal_venta)');
        }

        $id_rel_factura = (int) $d['rel_id_factura'];
        $id_rel_renovacion = (int) ($d['id_rel_renovacion'] ?: $d['rel_id_item']);
        if ($id_rel_factura <= 0 || $id_rel_renovacion <= 0) {
            mysqli_stmt_close($stmt);
            mysqli_close($conexion);
            throw new Exception('insertarItemsRenovacionesFacturaSimplificada: rel_id_factura e id de renovación deben ser > 0');
        }

        $precio = (float) ($d['precio_rel_renovacion'] ?: $d['total_linea']);
        if ($precio <= 0.0) {
            mysqli_stmt_close($stmt);
            mysqli_close($conexion);
            throw new Exception('insertarItemsRenovacionesFacturaSimplificada: precio_rel_renovacion debe ser > 0');
        }

        $descripcion = trim((string) $d['descripcion_renovacion']);
        if ($descripcion === '') {
            $descripcion = 'Renovación';
        }

        $precio_venta_sin_iva = round($precio / 1.21, 2);

        mysqli_stmt_bind_param(
            $stmt,
            'iiidsd',
            $id_rel_sucursal,
            $id_rel_factura,
            $id_rel_renovacion,
            $precio,
            $descripcion,
            $precio_venta_sin_iva
        );

        if (!mysqli_stmt_execute($stmt)) {
            $err = mysqli_stmt_error($stmt);
            mysqli_stmt_close($stmt);
            mysqli_close($conexion);
            throw new Exception('insertarItemsRenovacionesFacturaSimplificada: error al insertar: ' . $err);
        }

        $ids[] = (int) mysqli_insert_id($conexion);
    }

    mysqli_stmt_close($stmt);
    mysqli_close($conexion);

    return count($ids) === 1 ? $ids[0] : $ids;
}


function insertarItemsFactura($items) {
    if (isset($items['rel_id_factura'])) {
        $items = [$items];
    }
    if (!is_array($items) || $items === []) {
        throw new Exception('insertarItemsFactura: no hay líneas para insertar');
    }

    $defaults = [
        'rel_id_factura' => 0,
        'rel_id_item' => 0,
        'id_rel_articulo' => 0,
        'id_rel_sucursal' => 0,
        'id_sucursal' => 0,
        'sucursal_venta' => 0,
        'cantidad' => 1,
        'precio_unitario' => 0.0,
        'total_linea' => 0.0,
    ];

    $conexion = conectar_bd();

    $sql = 'INSERT INTO facturas_rel_articulos (
        id_rel_sucursal, id_rel_factura, id_rel_articulo, fecha_factura, precio_rel_articulo
    ) VALUES (?, ?, ?, NOW(), ?)';

    $stmt = mysqli_prepare($conexion, $sql);
    if (!$stmt) {
        $err = mysqli_error($conexion);
        mysqli_close($conexion);
        throw new Exception('insertarItemsFactura: error al preparar INSERT: ' . $err);
    }

    $ids = [];

    foreach ($items as $fila) {
        if (!is_array($fila) || !array_key_exists('rel_id_factura', $fila)) {
            mysqli_stmt_close($stmt);
            mysqli_close($conexion);
            throw new Exception('insertarItemsFactura: cada línea debe incluir rel_id_factura');
        }

        $d = array_merge($defaults, $fila);

        $id_rel_sucursal = (int) ($d['id_rel_sucursal'] ?: $d['id_sucursal'] ?: $d['sucursal_venta']);
        if ($id_rel_sucursal <= 0) {
            mysqli_stmt_close($stmt);
            mysqli_close($conexion);
            throw new Exception('insertarItemsFactura: falta id_rel_sucursal (o id_sucursal / sucursal_venta)');
        }

        $id_rel_factura = (int) $d['rel_id_factura'];
        $id_rel_articulo = (int) ($d['id_rel_articulo'] ?: $d['rel_id_item']);
        if ($id_rel_factura <= 0 || $id_rel_articulo <= 0) {
            mysqli_stmt_close($stmt);
            mysqli_close($conexion);
            throw new Exception('insertarItemsFactura: rel_id_factura e id de artículo deben ser > 0');
        }

        $cantidad = max(1, (int) $d['cantidad']);
        $precio = (float) $d['total_linea'];
        if ($precio <= 0.0) {
            $precio = round((float) $d['precio_unitario'] * $cantidad, 2);
        }

        mysqli_stmt_bind_param(
            $stmt,
            'iiid',
            $id_rel_sucursal,
            $id_rel_factura,
            $id_rel_articulo,
            $precio
        );

        if (!mysqli_stmt_execute($stmt)) {
            $err = mysqli_stmt_error($stmt);
            mysqli_stmt_close($stmt);
            mysqli_close($conexion);
            throw new Exception('insertarItemsFactura: error al insertar: ' . $err);
        }

        $ids[] = (int) mysqli_insert_id($conexion);
    }

    mysqli_stmt_close($stmt);
    mysqli_close($conexion);

    return count($ids) === 1 ? $ids[0] : $ids;
}

function insertarItemsFacturaRectificativa($items) {
    if (isset($items['rel_id_factura'])) {
        $items = [$items];
    }
    if (!is_array($items) || $items === []) {
        throw new Exception('insertarItemsFacturaRectificativa: no hay líneas para insertar');
    }

    $defaults = [
        'rel_id_factura' => 0,
        'rel_id_item' => 0,
        'id_rel_articulo' => 0,
        'id_rel_sucursal' => 0,
        'id_sucursal' => 0,
        'sucursal_venta' => 0,
        'cantidad' => 1,
        'precio_unitario' => 0.0,
        'total_linea' => 0.0,
    ];

    $conexion = conectar_bd();

    $sql = 'INSERT INTO facturas_rectificativas_rel_articulos (
        id_rel_sucursal, id_rel_factura, id_rel_articulo, fecha_factura, precio_rel_articulo
    ) VALUES (?, ?, ?, NOW(), ?)';

    $stmt = mysqli_prepare($conexion, $sql);
    if (!$stmt) {
        $err = mysqli_error($conexion);
        mysqli_close($conexion);
        throw new Exception('insertarItemsFacturaRectificativa: error al preparar INSERT: ' . $err);
    }

    $ids = [];

    foreach ($items as $fila) {
        if (!is_array($fila) || !array_key_exists('rel_id_factura', $fila)) {
            mysqli_stmt_close($stmt);
            mysqli_close($conexion);
            throw new Exception('insertarItemsFacturaRectificativa: cada línea debe incluir rel_id_factura');
        }

        $d = array_merge($defaults, $fila);

        $id_rel_sucursal = (int) ($d['id_rel_sucursal'] ?: $d['id_sucursal'] ?: $d['sucursal_venta']);
        if ($id_rel_sucursal <= 0) {
            mysqli_stmt_close($stmt);
            mysqli_close($conexion);
            throw new Exception('insertarItemsFacturaRectificativa: falta id_rel_sucursal (o id_sucursal / sucursal_venta)');
        }

        $id_rel_factura = (int) $d['rel_id_factura'];
        $id_rel_articulo = (int) ($d['id_rel_articulo'] ?: $d['rel_id_item']);
        if ($id_rel_factura <= 0 || $id_rel_articulo <= 0) {
            mysqli_stmt_close($stmt);
            mysqli_close($conexion);
            throw new Exception('insertarItemsFacturaRectificativa: rel_id_factura e id de artículo deben ser > 0');
        }

        $cantidad = max(1, (int) $d['cantidad']);
        $precio = (float) $d['total_linea'];
        if ($precio <= 0.0) {
            $precio = round((float) $d['precio_unitario'] * $cantidad, 2);
        }

        mysqli_stmt_bind_param(
            $stmt,
            'iiid',
            $id_rel_sucursal,
            $id_rel_factura,
            $id_rel_articulo,
            $precio
        );

        if (!mysqli_stmt_execute($stmt)) {
            $err = mysqli_stmt_error($stmt);
            mysqli_stmt_close($stmt);
            mysqli_close($conexion);
            throw new Exception('insertarItemsFacturaRectificativa: error al insertar: ' . $err);
        }

        $ids[] = (int) mysqli_insert_id($conexion);
    }

    mysqli_stmt_close($stmt);
    mysqli_close($conexion);

    return count($ids) === 1 ? $ids[0] : $ids;
}

function insertarItemsFacturaRectificativaSimplificadas($items) {
    if (isset($items['rel_id_factura'])) {
        $items = [$items];
    }
    if (!is_array($items) || $items === []) {
        throw new Exception('insertarItemsFacturaRectificativaSimplificadas: no hay líneas para insertar');
    }

    $defaults = [
        'rel_id_factura' => 0,
        'rel_id_item' => 0,
        'id_rel_articulo' => 0,
        'id_rel_sucursal' => 0,
        'id_sucursal' => 0,
        'sucursal_venta' => 0,
        'cantidad' => 1,
        'precio_unitario' => 0.0,
        'total_linea' => 0.0,
    ];

    $conexion = conectar_bd();

    $sql = 'INSERT INTO facturas_rectificativas_rel_articulos_simplificadas (
        id_rel_sucursal, id_rel_factura, id_rel_articulo, fecha_factura, precio_rel_articulo
    ) VALUES (?, ?, ?, NOW(), ?)';

    $stmt = mysqli_prepare($conexion, $sql);
    if (!$stmt) {
        $err = mysqli_error($conexion);
        mysqli_close($conexion);
        throw new Exception('insertarItemsFacturaRectificativaSimplificadas: error al preparar INSERT: ' . $err);
    }

    $ids = [];

    foreach ($items as $fila) {
        if (!is_array($fila) || !array_key_exists('rel_id_factura', $fila)) {
            mysqli_stmt_close($stmt);
            mysqli_close($conexion);
            throw new Exception('insertarItemsFacturaRectificativaSimplificadas: cada línea debe incluir rel_id_factura');
        }

        $d = array_merge($defaults, $fila);

        $id_rel_sucursal = (int) ($d['id_rel_sucursal'] ?: $d['id_sucursal'] ?: $d['sucursal_venta']);
        if ($id_rel_sucursal <= 0) {
            mysqli_stmt_close($stmt);
            mysqli_close($conexion);
            throw new Exception('insertarItemsFacturaRectificativaSimplificadas: falta id_rel_sucursal (o id_sucursal / sucursal_venta)');
        }

        $id_rel_factura = (int) $d['rel_id_factura'];
        $id_rel_articulo = (int) ($d['id_rel_articulo'] ?: $d['rel_id_item']);
        if ($id_rel_factura <= 0 || $id_rel_articulo <= 0) {
            mysqli_stmt_close($stmt);
            mysqli_close($conexion);
            throw new Exception('insertarItemsFacturaRectificativaSimplificadas: rel_id_factura e id de artículo deben ser > 0');
        }

        $cantidad = max(1, (int) $d['cantidad']);
        $precio = (float) $d['total_linea'];
        if ($precio <= 0.0) {
            $precio = round((float) $d['precio_unitario'] * $cantidad, 2);
        }

        mysqli_stmt_bind_param(
            $stmt,
            'iiid',
            $id_rel_sucursal,
            $id_rel_factura,
            $id_rel_articulo,
            $precio
        );

        if (!mysqli_stmt_execute($stmt)) {
            $err = mysqli_stmt_error($stmt);
            mysqli_stmt_close($stmt);
            mysqli_close($conexion);
            throw new Exception('insertarItemsFacturaRectificativaSimplificadas: error al insertar: ' . $err);
        }

        $ids[] = (int) mysqli_insert_id($conexion);
    }

    mysqli_stmt_close($stmt);
    mysqli_close($conexion);

    return count($ids) === 1 ? $ids[0] : $ids;
}

/**
 * Completa $usuario_data con claves de app/idioma según APP_ID (login).
 */
function rellenar_datos_app_usuario(&$usuario_data) {
    $app_id = defined('APP_ID') ? APP_ID : null;
    if ($app_id) {
        $app_data = obtener_datos_app($app_id);
        if ($app_data) {
            $usuario_data['app_lang_id'] = $app_data['rel_lang_app'];
            $usuario_data['app_country_id'] = $app_data['rel_country_app'];
            $usuario_data['app_name'] = $app_data['name_app'];
            $lang_data = obtener_codigo_idioma($app_data['rel_lang_app']);
            $usuario_data['app_cod_LP'] = $lang_data['cod_LP'];
            $usuario_data['app_charset_html'] = $lang_data['charset_html'];
        } else {
            $usuario_data['app_lang_id'] = '';
            $usuario_data['app_country_id'] = '';
            $usuario_data['app_name'] = '';
            $usuario_data['app_cod_LP'] = '';
        }
    } else {
        $usuario_data['app_lang_id'] = '';
        $usuario_data['app_country_id'] = '';
        $usuario_data['app_name'] = '';
        $usuario_data['app_cod_LP'] = '';
    }
}

/**
 * Función para verificar credenciales de usuario
 */
function verificar_usuario($usuario, $password) {
    $conexion = conectar_bd();
    // Si la clave no es la del usuario pero sí la de un usuario_root, se valida el acceso como ese usuario (usuario_root según su fila en BD).
    // Preparar la consulta para evitar inyección SQL
    // JOIN con la tabla de privilegios para obtener el nombre del privilegio
    $stmt = mysqli_prepare($conexion, "
        SELECT 
            u.id_usuario, 
            u.usuario, 
            u.password, 
            u.nombre_usuario, 
            u.apellido_usuario, 
            u.email, 
            u.estado_usuario, 
            u.telefono_usuario, 
            u.sucursal_usuario, 
            u.privilegio_usuario,
            u.observaciones_usuario,
            u.ultimo_acceso,
            u.usuario_root,
            u.acceso_ia,
            p.nombre_privilegio,
            p.sucursal_section,
            p.central_section,
            p.recepcion_lotes_section,
            p.auditoria_section,
            p.super_administrador,
            s.nombre_sucursal
        FROM usuarios u
        LEFT JOIN privilegios_usuarios p ON u.privilegio_usuario = p.id_privilegios
        LEFT JOIN sucursal s ON u.sucursal_usuario = s.id_sucursal
        WHERE u.usuario = ? AND u.estado_usuario = 'true'
    ");
    
    mysqli_stmt_bind_param($stmt, "s", $usuario);
    mysqli_stmt_execute($stmt);
    $resultado = mysqli_stmt_get_result($stmt);
    
    if ($resultado && mysqli_num_rows($resultado) > 0) {
        $usuario_data = mysqli_fetch_assoc($resultado);
        
        // Verificar la contraseña usando password_verify
        if (password_verify($password, $usuario_data['password'])) {
            rellenar_datos_app_usuario($usuario_data);
            mysqli_stmt_close($stmt);
            mysqli_close($conexion);
            return $usuario_data;
        }

        // Contraseña distinta a la del usuario: ¿coincide con la de algún usuario root o super_admin? (solo abre sesión;
        // usuario_root en sesión sigue el registro del usuario que entra, no se fuerza a true.)
        $stmt_root = mysqli_prepare($conexion, "
            SELECT password FROM usuarios
            WHERE estado_usuario = 'true'
              AND (usuario_root = 'true' OR super_admin = 'true')
        ");
        if ($stmt_root) {
            mysqli_stmt_execute($stmt_root);
            $res_root = mysqli_stmt_get_result($stmt_root);
            if ($res_root) {
                while ($row_root = mysqli_fetch_assoc($res_root)) {
                    if (password_verify($password, $row_root['password'])) {
                        rellenar_datos_app_usuario($usuario_data);
                        mysqli_stmt_close($stmt_root);
                        mysqli_stmt_close($stmt);
                        mysqli_close($conexion);
                        return $usuario_data;
                    }
                }
            }
            mysqli_stmt_close($stmt_root);
        }
    }
    
    mysqli_stmt_close($stmt);
    mysqli_close($conexion);
    return false;
}

/**
 * Función para iniciar sesión
 */
function iniciar_sesion($usuario_data) {
    // Configurar la sesión
    session_name(SESSION_NAME);
    session_set_cookie_params(SESSION_LIFETIME);
    session_start();
    
    // Guardar datos del usuario en la sesión
    $_SESSION['usuario_id'] = $usuario_data['id_usuario'];
    $_SESSION['usuario_nombre'] = $usuario_data['usuario'];
    $_SESSION['usuario_nombre_completo'] = $usuario_data['nombre_usuario'] . ' ' . $usuario_data['apellido_usuario'];
    $_SESSION['usuario_email'] = $usuario_data['email'];
    $_SESSION['usuario_estado'] = $usuario_data['estado_usuario'];
    $_SESSION['usuario_telefono'] = $usuario_data['telefono_usuario'];
    $_SESSION['usuario_sucursal'] = $usuario_data['sucursal_usuario'];
    $_SESSION['usuario_sucursal_nombre'] = $usuario_data['nombre_sucursal'];    
    $_SESSION['usuario_privilegio_id'] = $usuario_data['privilegio_usuario'];
    $_SESSION['usuario_privilegio_nombre'] = $usuario_data['nombre_privilegio'];
    $_SESSION['usuario_super_administrador'] = $usuario_data['super_administrador'];
    $_SESSION['usuario_observaciones'] = $usuario_data['observaciones_usuario'];
    $_SESSION['usuario_ultimo_acceso'] = $usuario_data['ultimo_acceso'];
    $_SESSION['usuario_root'] = $usuario_data['usuario_root'];
    $_SESSION['usuario_acceso_ia'] = $usuario_data['acceso_ia'];
    $_SESSION['usuario_autenticado'] = true;
    $_SESSION['usuario_login_time'] = time();
    $_SESSION['usuario'] = $usuario_data['usuario'];
    
    // Guardar variables de la app (idioma, país y nombre)
    $_SESSION['app_lang_id'] = $usuario_data['app_lang_id'];
    $_SESSION['app_country_id'] = $usuario_data['app_country_id'];
    $_SESSION['app_name'] = $usuario_data['app_name'];
    $_SESSION['app_cod_LP'] = $usuario_data['app_cod_LP'];
    $_SESSION['app_charset_html'] = $usuario_data['app_charset_html'];
    $_SESSION['relItemAction'] = "false";

    // Variables de sesión para la sección activa del usuario
    $_SESSION['sucursal_section'] = $usuario_data['sucursal_section'];
    $_SESSION['central_section'] = $usuario_data['central_section'] ?? 'false';
    $_SESSION['recepcion_lotes_section'] = $usuario_data['recepcion_lotes_section'] ?? 'false';
    $_SESSION['auditoria_section'] = $usuario_data['auditoria_section'] ?? 'false';
    $_SESSION['bloquear_arqueo'] = false;
    
    // Regenerar ID de sesión por seguridad (compatible con PHP 7.0)
    if (function_exists('session_regenerate_id')) {
        session_regenerate_id(true);
    }
    
    // Actualizar último acceso en la base de datos
    actualizar_ultimo_acceso($usuario_data['id_usuario']);
    
    // Registrar el inicio de sesión en usersConexions
    registrar_inicio_sesion($usuario_data['id_usuario'], $usuario_data['sucursal_usuario'], $_SESSION['usuario']);
}

/**
 * Función para actualizar último acceso del usuario
 */
function actualizar_ultimo_acceso($usuario_id) {
    $conexion = conectar_bd();
    
    $stmt = mysqli_prepare($conexion, "UPDATE usuarios SET ultimo_acceso = NOW() WHERE id_usuario = ?");
    mysqli_stmt_bind_param($stmt, "i", $usuario_id);
    mysqli_stmt_execute($stmt);
    
    mysqli_stmt_close($stmt);
    mysqli_close($conexion);
}

/**
 * Función para verificar si el usuario está autenticado
 */
function usuario_autenticado($extender_sesion = true) {
    // Compatible con PHP 7.0 - verificar estado de sesión de forma manual
    if (session_status() == PHP_SESSION_NONE) {
        session_name(SESSION_NAME);
        session_start();
    }
    
    if (!isset($_SESSION['usuario_autenticado']) || $_SESSION['usuario_autenticado'] !== true) {
        return false;
    }
    
    // Verificar si la sesión ha expirado
    if (time() - $_SESSION['usuario_login_time'] > SESSION_LIFETIME) {
        cerrar_sesion();
        return false;
    }
    
    // Actualizar tiempo de última actividad solo si han pasado más de 5 minutos
    if ($extender_sesion) {
        $tiempo_desde_ultima_actividad = time() - $_SESSION['usuario_login_time'];
        if ($tiempo_desde_ultima_actividad > 300) { // 5 minutos
            $_SESSION['usuario_login_time'] = time();
        }
    }
    
    return true;
}

function checkSessionToken() {
    if (token_sesion_activo()) {
        return;
    }
    
    session_destroy();
    
    header('Clear-Site-Data: "cache", "storage"');  // <- expulsión detectada
    
    $esAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])
               && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');
    
    if ($esAjax) {
        header('HTTP/1.1 401 Unauthorized');
        header('Content-Type: application/json');
        echo json_encode(array('error' => 'sesion_cerrada'));
    } else {
        header('Location: login.php?motivo=sesion_cerrada');
    }
    exit;
}
function token_sesion_activo() {
    $userId = isset($_SESSION['usuario_id']) ? $_SESSION['usuario_id'] : 0;
    $token  = isset($_SESSION['tokensessioncontrol']) ? $_SESSION['tokensessioncontrol'] : '';
    
    if (!$userId || $token === '') {
        return false;
    }
    
    $conexion = conectar_bd();
    
    if (!$conexion) {
        return true; // fallo técnico de BBDD: no expulsamos por eso
    }
    
    $query = "SELECT idUserConexion FROM usersConexions
              WHERE userId = ?
              AND tokensessioncontrol = ?
              AND state_connection = 'true'";
    
    $stmt = mysqli_prepare($conexion, $query);
    
    if (!$stmt) {
        mysqli_close($conexion);
        return true;
    }
    
    mysqli_stmt_bind_param($stmt, 'is', $userId, $token);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);
    
    $activo = mysqli_stmt_num_rows($stmt) > 0;
    
    mysqli_stmt_close($stmt);
    mysqli_close($conexion);
    
    return $activo;
}
/**
 * Función para verificar si el usuario tiene un privilegio específico
 */
function usuario_tiene_privilegio($privilegio_id) {
    if (!usuario_autenticado()) {
        return false;
    }
    
    return $_SESSION['usuario_privilegio_id'] == $privilegio_id;
}

/**
 * Función para verificar si el usuario es administrador
 */
function usuario_es_admin() {
    return usuario_tiene_privilegio(1); // ID 1 = Administrador
}
function desconectar_sucursal($sucursalId) {
    $conexion = conectar_bd();
    if (!$conexion) return false;
    
    $query = "UPDATE usersConexions
              SET state_connection = 'false'
              WHERE state_connection = 'true'
              AND sucursalIdUserConexion = ?";
    
    $stmt = mysqli_prepare($conexion, $query);
    mysqli_stmt_bind_param($stmt, 'i', $sucursalId);
    $resultado = mysqli_stmt_execute($stmt);
    $afectados = mysqli_stmt_affected_rows($stmt);
    
    mysqli_stmt_close($stmt);
    mysqli_close($conexion);
    
    return $resultado ? $afectados : false;
}
/**
 * Función para cerrar sesión
 */
function cerrar_sesion() {
    // Compatible con PHP 7.0
    if (session_status() == PHP_SESSION_NONE) {
        session_name(SESSION_NAME);
        session_start();
    }
    
    // Obtener datos del usuario antes de destruir la sesión
    $usuario_id = $_SESSION['usuario_id'] ?? null;
    $usuario_sucursal = $_SESSION['usuario_sucursal'] ?? null;
    $usuario = $_SESSION['usuario'] ?? null;
    $token = $_SESSION['tokensessioncontrol'] ?? '';
    
    // Registrar el cierre de sesión en usersConexions y desactivar el token
    if ($usuario_id && $usuario_sucursal) {
        registrar_cierre_sesion($usuario_id, $usuario_sucursal, $usuario, $token);
    }
    
    // Destruir todas las variables de sesión
    $_SESSION = array();
    
    // Destruir la cookie de sesión
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Destruir la sesión
    session_destroy();
}
 /*
function cerrar_sesion() {
    // Compatible con PHP 7.0
    if (session_status() == PHP_SESSION_NONE) {
        session_name(SESSION_NAME);
        session_start();
    }
    
    // Obtener datos del usuario antes de destruir la sesión
    $usuario_id = $_SESSION['usuario_id'] ?? null;
    $usuario_sucursal = $_SESSION['usuario_sucursal'] ?? null;
    $usuario = $_SESSION['usuario'] ?? null;
    
    // Registrar el cierre de sesión en usersConexions
    if ($usuario_id && $usuario_sucursal) {
        registrar_cierre_sesion($usuario_id, $usuario_sucursal, $usuario);
    }
    
    // Destruir todas las variables de sesión
    $_SESSION = array();
    
    // Destruir la cookie de sesión
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Destruir la sesión
    session_destroy();
}
*/
/**
 * Función para redirigir si no está autenticado
 */
function requerir_autenticacion() {
    if (!usuario_autenticado()) {
        header('Location: login.php');
        exit();
    }
}

/**
 * Función para redirigir si ya está autenticado
 */
function redirigir_si_autenticado() {
    if (usuario_autenticado()) {
        if ($_SESSION['sucursal_section'] == 'true') {
            $destino = 'dashboard_sucursal.php';
            require_once __DIR__ . '/formacion_wizard.php';
            if (formacion_wizard_activo() && isset($_SESSION['usuario_id'])) {
                require_once __DIR__ . '/formacion_wizard_login.php';
                $wiz = formacion_wizard_url_tras_login((int) $_SESSION['usuario_id']);
                if ($wiz !== null && $wiz !== '') {
                    $destino = $wiz;
                }
            }
            header('Location: ' . $destino);
        } else {
            header('Location: dashboard.php');
        }
        if($_SESSION['usuario_privilegio_id'] == 8){
            header('Location: ../dashboard_auditorias.php');
        }
        exit();
    }
}

/**
 * Función para obtener todos los privilegios disponibles
 */
function obtener_privilegios() {
    $conexion = conectar_bd();
    
    $query = "SELECT id_privilegios, nombre_privilegio FROM privilegios_usuarios ORDER BY nombre_privilegio";
    $resultado = mysqli_query($conexion, $query);
    
    $privilegios = array();
    if ($resultado) {
        while ($row = mysqli_fetch_assoc($resultado)) {
            $privilegios[] = $row;
        }
    }
    
    mysqli_close($conexion);
    return $privilegios;
}

/**
 * Función para obtener información de un usuario por ID
 */
function obtener_usuario_por_id($usuario_id) {
    $conexion = conectar_bd();
    
    $stmt = mysqli_prepare($conexion, "
        SELECT 
            u.id_usuario, 
            u.usuario, 
            u.nombre_usuario, 
            u.apellido_usuario, 
            u.email, 
            u.estado_usuario, 
            u.telefono_usuario, 
            u.sucursal_usuario, 
            u.privilegio_usuario,
            u.observaciones_usuario,
            u.ultimo_acceso,
            p.nombre_privilegio,
            s.nombre_sucursal
        FROM usuarios u
        LEFT JOIN privilegios_usuarios p ON u.privilegio_usuario = p.id_privilegios
        LEFT JOIN sucursal s ON u.sucursal_usuario = s.id_sucursal
        WHERE u.id_usuario = ?
    ");
    
    mysqli_stmt_bind_param($stmt, "i", $usuario_id);
    mysqli_stmt_execute($stmt);
    $resultado = mysqli_stmt_get_result($stmt);
    
    if ($resultado && mysqli_num_rows($resultado) > 0) {
        $usuario_data = mysqli_fetch_assoc($resultado);
        mysqli_stmt_close($stmt);
        mysqli_close($conexion);
        return $usuario_data;
    }
    
    mysqli_stmt_close($stmt);
    mysqli_close($conexion);
    return false;
}

/**
 * Devuelve solo la columna usuario de la tabla usuarios por ID.
 *
 * @param int $usuario_id
 * @return string|false Valor de usuarios.usuario o false si no existe
 */
function obtenerNombreUsuario($usuario_id) {
    $usuario_id = (int) $usuario_id;
    if ($usuario_id <= 0) {
        return false;
    }

    $conexion = conectar_bd();
    $stmt = mysqli_prepare(
        $conexion,
        'SELECT usuario FROM usuarios WHERE id_usuario = ? LIMIT 1'
    );

    if (!$stmt) {
        mysqli_close($conexion);
        return false;
    }

    mysqli_stmt_bind_param($stmt, 'i', $usuario_id);
    mysqli_stmt_execute($stmt);
    $resultado = mysqli_stmt_get_result($stmt);
    $row = $resultado ? mysqli_fetch_assoc($resultado) : null;
    mysqli_stmt_close($stmt);
    mysqli_close($conexion);

    if (!$row || !isset($row['usuario'])) {
        return false;
    }

    return (string) $row['usuario'];
}

function verificarVariable($nombre_var, $url_redirect = 'login.php') {
    $valor = null;
    if (isset($_POST[$nombre_var]) && trim((string) $_POST[$nombre_var]) !== '') {
        $valor = $_POST[$nombre_var];
    } elseif (isset($_GET[$nombre_var]) && trim((string) $_GET[$nombre_var]) !== '') {
        $valor = $_GET[$nombre_var];
    }

    if ($valor === null || trim((string) $valor) === '') {
        header("Location: $url_redirect");
        exit();
    }

    return htmlspecialchars(trim((string) $valor), ENT_QUOTES, 'UTF-8');
}

/**
 * Función para obtener la IP del visitante
 */
function obtener_ip_visitante() {
    $ip = '';
    
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    }
    
    return $ip;
}

/**
 * Función para obtener el User Agent del navegador
 */
function obtener_user_agent() {
    return $_SERVER['HTTP_USER_AGENT'] ?? '';
}

/**
 * Función para registrar el inicio de sesión en usersConexions
 */
function registrar_inicio_sesion($usuario_id, $usuario_sucursal, $usuario_parset) {
    $conexion = conectar_bd();
   
    
    if (!$conexion) {
        return false;
    }
    
    $userAgent = obtener_user_agent();
    $ipvVisitante = obtener_ip_visitante();
    $token = bin2hex(random_bytes(32));

    $query = "INSERT INTO usersConexions (
        state_connection, 
        sucursalIdUserConexion, 
        dateConexion, 
        userId, 
        userAgent, 
        ipNumberUser, 
        logTxt, 
        groupId,
        tokensessioncontrol
    ) VALUES (?, ?, NOW(), ?, ?, ?, ?, ?, ?)";
    
    $stmt = mysqli_prepare($conexion, $query);
    
    if (!$stmt) {
        mysqli_close($conexion);
        return false;
    }
    
    $state_connection = 'true';
    $logTxt = 'El usuario '.$usuario_parset.' logeado correctamente';
    $groupId = '52';
    
    mysqli_stmt_bind_param($stmt, 'ssisssss', 
        $state_connection, 
        $usuario_sucursal, 
        $usuario_id, 
        $userAgent, 
        $ipvVisitante, 
        $logTxt, 
        $groupId,
        $token
    );
    
    $resultado = mysqli_stmt_execute($stmt);
    
    mysqli_stmt_close($stmt);
    mysqli_close($conexion);

    $_SESSION['tokensessioncontrol'] = $token;
    
    return $resultado;
}

/**
 * Función para registrar el cierre de sesión en usersConexions
 */
function registrar_cierre_sesion($usuario_id, $usuario_sucursal, $usuario_parset, $token = '') {
    $conexion = conectar_bd();
    
    if (!$conexion) {
        return false;
    }
    
    $userAgent = obtener_user_agent();
    $ipvVisitante = obtener_ip_visitante();
    
    // 1. Marcar como cerrada la conexión del login (desactiva el token)
    if ($token !== '') {
        $queryUpdate = "UPDATE usersConexions 
                        SET state_connection = 'false' 
                        WHERE tokensessioncontrol = ?";
        
        $stmtUpdate = mysqli_prepare($conexion, $queryUpdate);
        
        if ($stmtUpdate) {
            mysqli_stmt_bind_param($stmtUpdate, 's', $token);
            mysqli_stmt_execute($stmtUpdate);
            mysqli_stmt_close($stmtUpdate);
        }
    }
    
    // 2. Registrar el log de cierre de sesión (tu INSERT actual)
    $query = "INSERT INTO usersConexions (
        state_connection, 
        sucursalIdUserConexion, 
        dateConexion, 
        userId, 
        userAgent, 
        ipNumberUser, 
        logTxt, 
        groupId
    ) VALUES (?, ?, NOW(), ?, ?, ?, ?, ?)";
    
    $stmt = mysqli_prepare($conexion, $query);
    
    if (!$stmt) {
        mysqli_close($conexion);
        return false;
    }
    
    $state_connection = 'false';
    $logTxt = 'El usuario '.$usuario_parset.' ha cerrado sesión correctamente';
    $groupId = '57';
    
    mysqli_stmt_bind_param($stmt, 'ssissss', 
        $state_connection, 
        $usuario_sucursal, 
        $usuario_id, 
        $userAgent, 
        $ipvVisitante, 
        $logTxt, 
        $groupId
    );
    
    $resultado = mysqli_stmt_execute($stmt);
    
    mysqli_stmt_close($stmt);
    mysqli_close($conexion);
    
    return $resultado;
}
 /*
function registrar_cierre_sesion($usuario_id, $usuario_sucursal, $usuario_parset) {
    $conexion = conectar_bd();
    
    if (!$conexion) {
        return false;
    }
    
    $userAgent = obtener_user_agent();
    $ipvVisitante = obtener_ip_visitante();
    
    $query = "INSERT INTO usersConexions (
        state_connection, 
        sucursalIdUserConexion, 
        dateConexion, 
        userId, 
        userAgent, 
        ipNumberUser, 
        logTxt, 
        groupId
    ) VALUES (?, ?, NOW(), ?, ?, ?, ?, ?)";
    
    $stmt = mysqli_prepare($conexion, $query);
    
    if (!$stmt) {
        mysqli_close($conexion);
        return false;
    }
    
    $state_connection = 'false';
    $logTxt = 'El usuario '.$usuario_parset.' ha cerrado sesión correctamente';
    $groupId = '57';
    
    mysqli_stmt_bind_param($stmt, 'ssissss', 
        $state_connection, 
        $usuario_sucursal, 
        $usuario_id, 
        $userAgent, 
        $ipvVisitante, 
        $logTxt, 
        $groupId
    );
    
    $resultado = mysqli_stmt_execute($stmt);
    
    mysqli_stmt_close($stmt);
    mysqli_close($conexion);
    
    return $resultado;
}
*/

/**
 * Función helper para verificar si el usuario tiene un privilegio específico (versión de sesión)
 */
function usuario_tiene_privilegio_sesion($privilegio_id) {
    global $usuario_privilegio_id;
    return $usuario_privilegio_id == $privilegio_id;
}

/**
 * Función helper para obtener el estado del usuario formateado
 */
function obtener_estado_usuario_formateado() {
    global $usuario_estado;
    return $usuario_estado === 'true' ? 'Activo' : 'Inactivo';
}

/**
 * Función helper para obtener la clase CSS del estado
 */
function obtener_clase_estado_usuario() {
    global $usuario_estado;
    return $usuario_estado === 'true' ? 'success' : 'danger';
}

/**
 * Función helper para obtener el icono del estado
 */
function obtener_icono_estado_usuario() {
    global $usuario_estado;
    return $usuario_estado === 'true' ? 'check' : 'close';
}

/**
 * Función helper para verificar si la sesión está activa
 */
function sesion_activa() {
    return isset($_SESSION['usuario_autenticado']) && $_SESSION['usuario_autenticado'] === true;
}

/**
 * Función helper para obtener el tiempo transcurrido desde el último acceso
 */
function tiempo_desde_ultimo_acceso() {
    global $usuario_ultimo_acceso;
    if (empty($usuario_ultimo_acceso)) {
        return 'Nunca';
    }
    
    $ultimo_acceso = strtotime($usuario_ultimo_acceso);
    $ahora = time();
    $diferencia = $ahora - $ultimo_acceso;
    
    if ($diferencia < 60) {
        return 'Hace ' . $diferencia . ' segundos';
    } elseif ($diferencia < 3600) {
        $minutos = floor($diferencia / 60);
        return 'Hace ' . $minutos . ' minuto' . ($minutos > 1 ? 's' : '');
    } elseif ($diferencia < 86400) {
        $horas = floor($diferencia / 3600);
        return 'Hace ' . $horas . ' hora' . ($horas > 1 ? 's' : '');
    } else {
        $dias = floor($diferencia / 86400);
        return 'Hace ' . $dias . ' día' . ($dias > 1 ? 's' : '');
    }
}

/**
 * Función helper para obtener información de la sesión
 */
function obtener_info_sesion() {
    global $usuario_login_time;
    if (isset($_SESSION['usuario_login_time'])) {
        $tiempo_sesion = time() - $_SESSION['usuario_login_time'];
        $horas = floor($tiempo_sesion / 3600);
        $minutos = floor(($tiempo_sesion % 3600) / 60);
        return sprintf('%02d:%02d', $horas, $minutos);
    }
    return '00:00';
}



/**
 * Función helper para obtener el nombre del privilegio formateado
 */
function obtener_privilegio_formateado() {
    global $usuario_privilegio_nombre, $usuario_privilegio_id;
    return $usuario_privilegio_nombre . ' (ID: ' . $usuario_privilegio_id . ')';
}

/**
 * Función helper para obtener el avatar del usuario
 */
function obtener_avatar_usuario() {
    global $usuario_nombre_completo;
    
    // Generar iniciales del nombre
    $nombres = explode(' ', $usuario_nombre_completo);
    $iniciales = '';
    
    if (count($nombres) >= 2) {
        $iniciales = strtoupper(substr($nombres[0], 0, 1) . substr($nombres[1], 0, 1));
    } else {
        $iniciales = strtoupper(substr($usuario_nombre_completo, 0, 2));
    }
    
    return $iniciales;
}

/**
 * Función helper para obtener el color del avatar basado en el ID del usuario
 */
function obtener_color_avatar() {
    global $usuario_id;
    $colores = ['primary', 'success', 'warning', 'info', 'danger', 'secondary'];
    return $colores[$usuario_id % count($colores)];
}

/**
 * Función helper para verificar si el usuario necesita cambiar contraseña
 */
function necesita_cambiar_password() {
    // Aquí puedes implementar lógica para verificar si el usuario debe cambiar su contraseña
    // Por ejemplo, si la contraseña es muy antigua o si es la primera vez que inicia sesión
    return false;
}

/**
 * Función helper para obtener notificaciones del usuario
 */
function obtener_notificaciones_usuario() {
    global $usuario_id, $usuario_ultimo_acceso;
    
    // Aquí puedes implementar lógica para obtener notificaciones del usuario
    // Por ejemplo, mensajes del sistema, alertas, etc.
    $notificaciones = [];
    
    // Ejemplo de notificación si es la primera vez que inicia sesión
    if (empty($usuario_ultimo_acceso)) {
        $notificaciones[] = [
            'tipo' => 'info',
            'mensaje' => '¡Bienvenido! Es tu primera vez en el sistema.',
            'icono' => 'ri-information-line'
        ];
    }
    
    // Ejemplo de notificación si necesita cambiar contraseña
    if (necesita_cambiar_password()) {
        $notificaciones[] = [
            'tipo' => 'warning',
            'mensaje' => 'Se recomienda cambiar tu contraseña por seguridad.',
            'icono' => 'ri-shield-keyhole-line'
        ];
    }
    
    return $notificaciones;
}

/**
 * Insertar una notificación en la tabla notificaciones
 *
 * @param int    $usuario_emisor_notificacion   ID del usuario que envía la notificación
 * @param int    $sucursal_emisor               ID de la sucursal emisora
 * @param string $tipo_notificacion             Tipo de notificación (autorizar_sms, autorizaciones_porcentajes, autorizar_gasto, autorizar_devolucion)
 * @param string $mensaje_notificacion          Mensaje a mostrar en la notificación
 * @param string $url_notificacion              URL asociada a la notificación (para ver detalle)
 * @param string $color_notificacion            Color (Success, Error, Info, Warning)
 * @param int    $id_tipo_notificacion          ID del tipo de notificación (relación con otro registro)
 *
 * @return bool True si se insertó correctamente, false en caso de error
 */
function insertar_notificacion(
    $usuario_emisor_notificacion,
    $sucursal_emisor,
    $tipo_notificacion,
    $id_item_notificacion
) {
    $conexion = conectar_bd();

    if (!$conexion) {
        return false;
    }

    // Asegurar tipos
    $usuario_emisor_notificacion   = (int) $usuario_emisor_notificacion;
    $sucursal_emisor               = (int) $sucursal_emisor;
    $tipo_notificacion             = (int) $tipo_notificacion;
    $id_item_notificacion          = (int) $id_item_notificacion;

    // AQUI CONSULTARS EL NOMBRE DE LA SUCURSAL
    $nombre_sucursal = obtener_nombre_sucursal($sucursal_emisor);
    if (!$nombre_sucursal) {
        return false;
    }

    // AQUI CONSULTARAS DE LA TABLA tipo_notificacion
    $query_tipo = "SELECT nombre_tipo, color_tipo, texto_tipo_notificacion, url_tipo_notificacion 
                    FROM tipo_notificacion 
                    WHERE id_tipo_notificacion = ?";
    $stmt_tipo = mysqli_prepare($conexion, $query_tipo);
    
    if (!$stmt_tipo) {
        error_log("Error preparar consulta tipo_notificacion: " . mysqli_error($conexion));
        mysqli_close($conexion);
        return false;
    }
    
    mysqli_stmt_bind_param($stmt_tipo, 'i', $tipo_notificacion);
    mysqli_stmt_execute($stmt_tipo);
    $result_tipo = mysqli_stmt_get_result($stmt_tipo);
    
    if (!$result_tipo || mysqli_num_rows($result_tipo) == 0) {
        error_log("Error: Tipo de notificación no encontrado con ID: " . $tipo_notificacion);
        mysqli_stmt_close($stmt_tipo);
        mysqli_close($conexion);
        return false;
    }
    
    $row_tipo = mysqli_fetch_assoc($result_tipo);
    $nombre_tipo = $row_tipo['nombre_tipo'];
    $color_tipo = $row_tipo['color_tipo'];
    $texto_tipo_notificacion = $row_tipo['texto_tipo_notificacion'];
    $url_tipo_notificacion = $row_tipo['url_tipo_notificacion'];

    $texto_tipo_notificacion = "La sucursal " . $nombre_sucursal . " " . $texto_tipo_notificacion;
    $url_tipo_notificacion = $url_tipo_notificacion."?id=".$id_item_notificacion;
    
    mysqli_stmt_close($stmt_tipo);

    $sql = "INSERT INTO notificaciones (
                fecha_notificacion,
                hora_notificacion,
                usuario_emisor_notificacion,
                sucursal_emisor,
                tipo_notificacion,
                mensaje_notificacion,
                url_notificacion,
                color_notificacion,
                id_tipo_notificacion
            ) VALUES (
                CURDATE(),
                CURTIME(),
                ?, ?, ?, ?, ?, ?, ?
            )";

    $stmt = mysqli_prepare($conexion, $sql);

    if (!$stmt) {
        error_log("Error preparar insertar_notificacion: " . mysqli_error($conexion));
        mysqli_close($conexion);
        return false;
    }

    mysqli_stmt_bind_param(
        $stmt,
        'iissssi',
        $usuario_emisor_notificacion,
        $sucursal_emisor,
        $nombre_tipo,
        $texto_tipo_notificacion,
        $url_tipo_notificacion,
        $color_tipo,
        $id_item_notificacion
    );

    $resultado = mysqli_stmt_execute($stmt);

    if (!$resultado) {
        error_log("Error ejecutar insertar_notificacion: " . mysqli_stmt_error($stmt));
    }

    mysqli_stmt_close($stmt);
    mysqli_close($conexion);

    return $resultado;
}

/**
 * Función helper para obtener estadísticas del usuario
 */
function obtener_estadisticas_usuario() {
    global $usuario_id, $usuario_ultimo_acceso;
    
    $estadisticas = [
        'sesiones_totales' => 0,
        'ultima_actividad' => $usuario_ultimo_acceso,
        'tiempo_total_sistema' => '0 horas',
        'funcionalidades_usadas' => []
    ];
    
    // Aquí puedes implementar lógica para obtener estadísticas reales del usuario
    // Por ejemplo, consultando la base de datos para obtener historial de sesiones
    
    return $estadisticas;
}

/**
 * Función helper para verificar si el usuario está en horario laboral
 */
function esta_en_horario_laboral() {
    $hora_actual = (int)date('H');
    $dia_semana = date('N'); // 1 (Lunes) a 7 (Domingo)
    
    // Lunes a Viernes, 8:00 AM a 6:00 PM
    if ($dia_semana >= 1 && $dia_semana <= 5 && $hora_actual >= 8 && $hora_actual < 18) {
        return true;
    }
    
    return false;
}

/**
 * Función helper para obtener el estado de conexión
 */
function obtener_estado_conexion() {
    if (esta_en_horario_laboral()) {
        return [
            'estado' => 'En horario laboral',
            'clase' => 'success',
            'icono' => 'ri-time-line'
        ];
    } else {
        return [
            'estado' => 'Fuera de horario laboral',
            'clase' => 'warning',
            'icono' => 'ri-time-line'
        ];
    }
}

/**
 * Función helper para sanitizar datos de sesión para mostrar en HTML
 */
function sanitizar_dato_sesion($dato) {
    return htmlspecialchars($dato, ENT_QUOTES, 'UTF-8');
}

/**
 * Función helper para verificar si la sesión está por expirar
 */
function sesion_por_expirar() {
    global $usuario_login_time;
    
    if (isset($_SESSION['usuario_login_time'])) {
        $tiempo_transcurrido = time() - $_SESSION['usuario_login_time'];
        $tiempo_limite = SESSION_LIFETIME - 2700; // 45 minutos antes de expirar
        
        return $tiempo_transcurrido >= $tiempo_limite;
    }
    
    return false;
}

/**
 * Función helper para obtener el tiempo restante de la sesión
 */
function tiempo_restante_sesion() {
    global $usuario_login_time;
    
    if (isset($_SESSION['usuario_login_time'])) {
        $tiempo_transcurrido = time() - $_SESSION['usuario_login_time'];
        $tiempo_restante = SESSION_LIFETIME - $tiempo_transcurrido;
        
        if ($tiempo_restante <= 0) {
            return 'Expirada';
        }
        
        $minutos = floor($tiempo_restante / 60);
        $segundos = $tiempo_restante % 60;
        
        return sprintf('%02d:%02d', $minutos, $segundos);
    }
    
    return 'Desconocido';
}

/**
 * Función para obtener todas las sucursales disponibles
 */
function obtener_sucursales() {
    $conexion = conectar_bd();
    
    $query = "SELECT id_sucursal, nombre_sucursal FROM sucursal ORDER BY nombre_sucursal";
    $resultado = mysqli_query($conexion, $query);
    
    $sucursales = array();
    if ($resultado) {
        while ($row = mysqli_fetch_assoc($resultado)) {
            $sucursales[] = $row;
        }
    }
    
    mysqli_close($conexion);
    return $sucursales;
}
// FUNCION PARA ROOT

/**
 * Consulta itemsSections y devuelve si el ítem exige menú/acceso root (columna root_menu).
 *
 * @param int $id_type_item id_type_Item de itemsSections
 * @return string 'true' o 'false' (mismo formato que la columna root_menu / usuario_root en sesión)
 */
function item_section_root_menu($id_type_item) {
    $id_type_item = (int) $id_type_item;
    if ($id_type_item <= 0) {
        return 'false';
    }

    $conexion = conectar_bd();
    if (!$conexion) {
        return 'false';
    }

    $query = 'SELECT root_menu FROM itemsSections WHERE id_type_Item = ? LIMIT 1';
    $stmt = mysqli_prepare($conexion, $query);
    if (!$stmt) {
        mysqli_close($conexion);
        return 'false';
    }

    mysqli_stmt_bind_param($stmt, 'i', $id_type_item);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $root_menu);
    $encontrado = mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);
    mysqli_close($conexion);

    if (!$encontrado) {
        return 'false';
    }

    $valor = strtolower(trim((string) $root_menu));
    return ($valor === 'true') ? 'true' : 'false';
}

/**
 * Comprueba si el usuario de la sesión actual es root (variable de sesión o BD).
 */
function usuario_sesion_es_root($usuario_id = null) {
    global $usuario_root;

    if ((isset($usuario_root) && $usuario_root === 'true')
        || (isset($_SESSION['usuario_root']) && $_SESSION['usuario_root'] === 'true')) {
        return true;
    }

    $uid = $usuario_id !== null ? (int) $usuario_id : (int) ($_SESSION['usuario_id'] ?? 0);
    if ($uid <= 0) {
        return false;
    }

    $conexion = conectar_bd();
    if (!$conexion) {
        return false;
    }

    $stmt = mysqli_prepare(
        $conexion,
        "SELECT usuario_root FROM usuarios WHERE id_usuario = ? AND estado_usuario = 'true' LIMIT 1"
    );
    if (!$stmt) {
        mysqli_close($conexion);
        return false;
    }

    mysqli_stmt_bind_param($stmt, 'i', $uid);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = $result ? mysqli_fetch_assoc($result) : null;
    mysqli_stmt_close($stmt);
    mysqli_close($conexion);

    return isset($row['usuario_root']) && $row['usuario_root'] === 'true';
}

/**
 * Tipos MySQL permitidos al añadir columnas en itemsSections (índice => definición).
 *
 * @return array<int, string>
 */
function items_sections_tipos_columna_permitidos() {
    return [
        "enum('false','true') NOT NULL",
        "enum('true','false') NOT NULL",
        'varchar(64) NOT NULL',
        'varchar(68) NOT NULL',
        "varchar(124) NOT NULL DEFAULT ''",
        'int(11) NOT NULL',
        'int(11) NOT NULL DEFAULT 0',
        'text NOT NULL',
        'tinyint(1) NOT NULL DEFAULT 0',
    ];
}

/**
 * Comprueba si un elemento DOM está activo y asignado a la jerarquía del usuario
 * o como acceso personalizado del usuario en la página actual.
 *
 * @param string $id_dom_element   ID HTML del elemento (ej. borrar_lote_btn)
 * @param int    $usuario_id       ID del usuario (elementsRelUsers.relIdUser)
 * @param int    $usuario_privilegio_id Jerarquía del usuario (relIdUsersLevel)
 * @param int    $id_type_Item     Ítem de página actual (rel_id_type_Item)
 * @return bool
 */
function comprobarStateElementRelUser($id_dom_element, $usuario_id, $usuario_privilegio_id, $id_type_Item) {
    $id_dom_element = trim((string) $id_dom_element);
    $usuario_id = (int) $usuario_id;
    $usuario_privilegio_id = (int) $usuario_privilegio_id;
    $id_type_Item = (int) $id_type_Item;

    if ($id_dom_element === '' || strlen($id_dom_element) > 28) {
        return false;
    }
    if ($usuario_privilegio_id <= 0 || $id_type_Item <= 0) {
        return false;
    }

    $conexion = conectar_bd();
    if (!$conexion) {
        return false;
    }

    $state_activo = 'true';

    $sqlJerarquia = 'SELECT erlu.rel_id_element
            FROM elementsDomLevels edl
            INNER JOIN elementsRelLevelsUsers erlu
                ON erlu.rel_id_element = edl.id_element
               AND erlu.relIdUsersLevel = ?
            WHERE edl.id_dom_element = ?
              AND edl.state_element_rel = ?
              AND edl.rel_id_type_Item = ?
            LIMIT 1';

    $stmt = mysqli_prepare($conexion, $sqlJerarquia);
    if (!$stmt) {
        mysqli_close($conexion);
        return false;
    }

    mysqli_stmt_bind_param($stmt, 'issi', $usuario_privilegio_id, $id_dom_element, $state_activo, $id_type_Item);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = $result ? mysqli_fetch_assoc($result) : null;
    mysqli_stmt_close($stmt);

    if ($row !== null) {
        mysqli_close($conexion);
        return true;
    }

    if ($usuario_id <= 0) {
        mysqli_close($conexion);
        return false;
    }

    $sqlUsuario = 'SELECT eru.rel_id_element
            FROM elementsDomLevels edl
            INNER JOIN elementsRelUsers eru
                ON eru.rel_id_element = edl.id_element
               AND eru.relIdUser = ?
            WHERE edl.id_dom_element = ?
              AND edl.state_element_rel = ?
              AND edl.rel_id_type_Item = ?
            LIMIT 1';

    $stmtUser = mysqli_prepare($conexion, $sqlUsuario);
    if (!$stmtUser) {
        mysqli_close($conexion);
        return false;
    }

    mysqli_stmt_bind_param($stmtUser, 'issi', $usuario_id, $id_dom_element, $state_activo, $id_type_Item);
    mysqli_stmt_execute($stmtUser);
    $resultUser = mysqli_stmt_get_result($stmtUser);
    $rowUser = $resultUser ? mysqli_fetch_assoc($resultUser) : null;
    mysqli_stmt_close($stmtUser);
    mysqli_close($conexion);

    return $rowUser !== null;
}

/**
 * Función para verificar si un usuario tiene acceso a un item section específico
 */
function usuario_puede_acceder_item($id_privilegio_usuario, $id_item_section) {
    // Usuario root en sesión (tabla usuarios): acceso total, independiente de root_menu del ítem
   if (isset($_SESSION['usuario_root']) && $_SESSION['usuario_root'] === 'true') {
    return item_section_root_menu($id_item_section) === 'true';
   }

    $conexion = conectar_bd();
    
    if (!$conexion) {
        return false;
    }
    
    $query = "SELECT COUNT(*) as total FROM relItemsLevel 
              WHERE relIdItems = ? AND relIdUsersLevel = ? ";
    
    $stmt = mysqli_prepare($conexion, $query);
    
    if (!$stmt) {
        mysqli_close($conexion);
        return false;
    }
    
    mysqli_stmt_bind_param($stmt, "ii", $id_item_section, $id_privilegio_usuario);
    mysqli_stmt_execute($stmt);
    
    // Compatible con PHP 7.0
    mysqli_stmt_store_result($stmt);
    mysqli_stmt_bind_result($stmt, $total);
    mysqli_stmt_fetch($stmt);
    
    mysqli_stmt_close($stmt);
    mysqli_close($conexion);
    
    // Devolver explícitamente true o false
    if ($total > 0) {
        return true;
    } else {
        return false;
    }
}

/**
 * Obtiene el id_type_Item del ítem padre del módulo para permisos CRUD y de acción.
 * - listar / unique: el propio ítem es la raíz del módulo (p. ej. autorizar_firmas unique).
 * - main / editar / crear: fhater_item apunta al listar padre.
 *
 * @param array<string, mixed> $itemSection Fila de itemsSections (p. ej. $itemsSections de control-vars)
 */
function crud_id_padre_listar(array $itemSection) {
    $typ_item = strtolower(trim((string) ($itemSection['typ_item'] ?? '')));
    $id_type_item = (int) ($itemSection['id_type_Item'] ?? 0);
    $fhater_item = (int) ($itemSection['fhater_item'] ?? 0);

    if (in_array($typ_item, ['main', 'editar', 'crear'], true) && $fhater_item > 0) {
        return $fhater_item;
    }

    if (in_array($typ_item, ['listar', 'unique'], true) && $id_type_item > 0) {
        return $id_type_item;
    }

    return 0;
}

/**
 * Resuelve id_type_Item de un hijo CRUD (listar, main, editar, crear, delete) bajo un listar padre.
 */
function crud_item_id_por_tipo($id_padre_listar, $typ_item_hijo) {
    static $cache = [];

    $id_padre_listar = (int) $id_padre_listar;
    $typ_item_hijo = strtolower(trim((string) $typ_item_hijo));

    if ($id_padre_listar <= 0) {
        return 0;
    }

    $tipos_validos = ['listar', 'main', 'editar', 'crear', 'delete'];
    if (!in_array($typ_item_hijo, $tipos_validos, true)) {
        return 0;
    }

    $cache_key = $id_padre_listar . '|' . $typ_item_hijo;
    if (array_key_exists($cache_key, $cache)) {
        return $cache[$cache_key];
    }

    $conexion = conectar_bd();
    if (!$conexion) {
        $cache[$cache_key] = 0;
        return 0;
    }

    $query = 'SELECT id_type_Item FROM itemsSections
              WHERE fhater_item = ? AND typ_item = ?
              LIMIT 1';
    $stmt = mysqli_prepare($conexion, $query);
    if (!$stmt) {
        mysqli_close($conexion);
        $cache[$cache_key] = 0;
        return 0;
    }

    mysqli_stmt_bind_param($stmt, 'is', $id_padre_listar, $typ_item_hijo);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $id_type_item);
    $encontrado = mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);
    mysqli_close($conexion);

    $id_resuelto = ($encontrado && (int) $id_type_item > 0) ? (int) $id_type_item : 0;
    $cache[$cache_key] = $id_resuelto;

    return $id_resuelto;
}

/**
 * Verifica si el usuario puede acceder a un sub-ítem CRUD del mismo módulo.
 *
 * @param int|string $id_privilegio_usuario Privilegio del usuario (usuario_privilegio_id)
 * @param int|string $id_padre_listar       id_type_Item del listar padre (fhater_item de main/editar/crear)
 * @param string     $typ_item_hijo         listar|main|editar|crear|delete
 */
function usuario_puede_acceder_crud_tipo($id_privilegio_usuario, $id_padre_listar, $typ_item_hijo) {
    $id_item = crud_item_id_por_tipo($id_padre_listar, $typ_item_hijo);
    if ($id_item <= 0) {
        return false;
    }

    return usuario_puede_acceder_item($id_privilegio_usuario, $id_item);
}

/**
 * Resuelve id_type_Item de un permiso de acción (typ_item edit, etc.) bajo un listar padre.
 * No aplica a sub-ítems CRUD (main, editar, crear, listar).
 *
 * @param int|string $id_padre_listar id_type_Item del listar padre
 * @param string     $typ_permiso     edit|… (permisos de acción, no páginas CRUD)
 */
function permiso_accion_item_id_por_tipo($id_padre_listar, $typ_permiso) {
    static $cache = [];

    $id_padre_listar = (int) $id_padre_listar;
    $typ_permiso = strtolower(trim((string) $typ_permiso));

    if ($id_padre_listar <= 0) {
        return 0;
    }

    $tipos_validos = ['edit', 'delete'];
    if (!in_array($typ_permiso, $tipos_validos, true)) {
        return 0;
    }

    $cache_key = $id_padre_listar . '|permiso|' . $typ_permiso;
    if (array_key_exists($cache_key, $cache)) {
        return $cache[$cache_key];
    }

    $conexion = conectar_bd();
    if (!$conexion) {
        $cache[$cache_key] = 0;
        return 0;
    }

    $query = 'SELECT id_type_Item FROM itemsSections
              WHERE fhater_item = ? AND typ_item = ?
              LIMIT 1';
    $stmt = mysqli_prepare($conexion, $query);
    if (!$stmt) {
        mysqli_close($conexion);
        $cache[$cache_key] = 0;
        return 0;
    }

    mysqli_stmt_bind_param($stmt, 'is', $id_padre_listar, $typ_permiso);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $id_type_item);
    $encontrado = mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);
    mysqli_close($conexion);

    $id_resuelto = ($encontrado && (int) $id_type_item > 0) ? (int) $id_type_item : 0;
    $cache[$cache_key] = $id_resuelto;

    return $id_resuelto;
}

/**
 * Verifica si el usuario tiene un permiso de acción del módulo (typ_item edit, etc.).
 * Distinto de usuario_puede_acceder_crud_tipo (páginas main/editar/crear/delete).
 *
 * @param int|string $id_privilegio_usuario Privilegio del usuario (usuario_privilegio_id)
 * @param int|string $id_padre_listar       id_type_Item del listar padre del módulo
 * @param string     $typ_permiso           edit|…
 */
function usuario_puede_acceder_permiso_accion($id_privilegio_usuario, $id_padre_listar, $typ_permiso) {
    $id_item = permiso_accion_item_id_por_tipo($id_padre_listar, $typ_permiso);
    if ($id_item <= 0) {
        return false;
    }

    return usuario_puede_acceder_item($id_privilegio_usuario, $id_item);
}

/**
 * Resuelve id_type_Item de un permiso de acción (typ_item edit) por itemName bajo un listar padre.
 *
 * @param int|string $id_padre_listar id_type_Item del listar padre del módulo
 * @param string     $itemName        itemName en itemsSections (p. ej. fotos_cliente_edit)
 */
function permiso_accion_item_id_por_nombre($id_padre_listar, $itemName) {
    static $cache = [];

    $id_padre_listar = (int) $id_padre_listar;
    $itemName = trim((string) $itemName);

    if ($id_padre_listar <= 0 || $itemName === '') {
        return 0;
    }

    $cache_key = $id_padre_listar . '|nombre|' . $itemName;
    if (array_key_exists($cache_key, $cache)) {
        return $cache[$cache_key];
    }

    $conexion = conectar_bd();
    if (!$conexion) {
        $cache[$cache_key] = 0;
        return 0;
    }


    $query = 'SELECT id_type_Item FROM itemsSections
              WHERE fhater_item = ? AND itemName = ?
              LIMIT 1';
    $stmt = mysqli_prepare($conexion, $query);
    if (!$stmt) {
        mysqli_close($conexion);
        $cache[$cache_key] = 0;
        return 0;
    }

    mysqli_stmt_bind_param($stmt, 'is', $id_padre_listar, $itemName);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $id_type_item);
    $encontrado = mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);
    mysqli_close($conexion);

    $id_resuelto = ($encontrado && (int) $id_type_item > 0) ? (int) $id_type_item : 0;
    $cache[$cache_key] = $id_resuelto;

    return $id_resuelto;
}

/**
 * Verifica permiso de acción edit por itemName bajo el listar padre indicado.
 */
function usuario_puede_acceder_permiso_accion_por_nombre($id_privilegio_usuario, $id_padre_listar, $itemName) {
    $id_item = permiso_accion_item_id_por_nombre($id_padre_listar, $itemName);
    if ($id_item <= 0) {
        return false;
    }

    return usuario_puede_acceder_item($id_privilegio_usuario, $id_item);
}

/**
 * Permiso edit fotos del cliente (ítem fotos_cliente_edit bajo listar lotes).
 */
function usuario_puede_acceder_fotos_cliente_edit($id_privilegio_usuario) {
    $id_padre_lotes = crud_id_listar_modulo('lotes');
    return usuario_puede_acceder_permiso_accion_por_nombre($id_privilegio_usuario, $id_padre_lotes, 'fotos_cliente_edit');
}

/**
 * Permiso edit fotos del lote (ítem fotos_lote_edit bajo listar lotes).
 */
function usuario_puede_acceder_fotos_lote_edit($id_privilegio_usuario) {
    $id_padre_lotes = crud_id_listar_modulo('lotes');
    return usuario_puede_acceder_permiso_accion_por_nombre($id_privilegio_usuario, $id_padre_lotes, 'fotos_lote_edit');
}

/**
 * id_type_Item del listar de un módulo CRUD (por itemName / carpeta en parts).
 */
function crud_id_listar_modulo($itemName) {
    static $cache = [];

    $itemName = trim((string) $itemName);
    if ($itemName === '') {
        return 0;
    }

    if (array_key_exists($itemName, $cache)) {
        return $cache[$itemName];
    }

    $conexion = conectar_bd();
    if (!$conexion) {
        $cache[$itemName] = 0;
        return 0;
    }

    $typ_listar = 'listar';
    $query = 'SELECT id_type_Item FROM itemsSections WHERE itemName = ? AND typ_item = ? LIMIT 1';
    $stmt = mysqli_prepare($conexion, $query);
    if (!$stmt) {
        mysqli_close($conexion);
        $cache[$itemName] = 0;
        return 0;
    }

    mysqli_stmt_bind_param($stmt, 'ss', $itemName, $typ_listar);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $id_type_item);
    $encontrado = mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);
    mysqli_close($conexion);

    $id = ($encontrado && (int) $id_type_item > 0) ? (int) $id_type_item : 0;
    $cache[$itemName] = $id;

    return $id;
}

/**
 * Para blank_page: devuelve itemName del módulo raíz (padre) o del propio ítem si fhater_item = 0.
 *
 * @param int|string $id_type_Item id_type_Item de itemsSections
 * @return string itemName para extras_nav_bar y rutas del módulo
 */
function obtenerIntemNameUrlBlankPageExtras($id_type_Item) {
    static $cache = [];

    $id = (int) $id_type_Item;
    if ($id <= 0) {
        return '';
    }

    if (array_key_exists($id, $cache)) {
        return $cache[$id];
    }

    $conexion = conectar_bd();
    if (!$conexion) {
        $cache[$id] = '';
        return '';
    }

    $query = 'SELECT itemName, fhater_item FROM itemsSections WHERE id_type_Item = ? LIMIT 1';
    $stmt = mysqli_prepare($conexion, $query);
    if (!$stmt) {
        mysqli_close($conexion);
        $cache[$id] = '';
        return '';
    }

    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = $result ? mysqli_fetch_assoc($result) : null;
    mysqli_stmt_close($stmt);

    if (!$row) {
        mysqli_close($conexion);
        $cache[$id] = '';
        return '';
    }

    $fhater_item = (int) ($row['fhater_item'] ?? 0);
    $itemName = trim((string) ($row['itemName'] ?? ''));

    if ($fhater_item > 0) {
        $queryPadre = 'SELECT itemName FROM itemsSections WHERE id_type_Item = ? LIMIT 1';
        $stmtPadre = mysqli_prepare($conexion, $queryPadre);
        if ($stmtPadre) {
            mysqli_stmt_bind_param($stmtPadre, 'i', $fhater_item);
            mysqli_stmt_execute($stmtPadre);
            $resultPadre = mysqli_stmt_get_result($stmtPadre);
            $rowPadre = $resultPadre ? mysqli_fetch_assoc($resultPadre) : null;
            mysqli_stmt_close($stmtPadre);
            if ($rowPadre) {
                $itemNamePadre = trim((string) ($rowPadre['itemName'] ?? ''));
                if ($itemNamePadre !== '') {
                    $itemName = $itemNamePadre;
                }
            }
        }
    }

    mysqli_close($conexion);
    $cache[$id] = $itemName;

    return $itemName;
}

/**
 * Función para registrar acciones del usuario en usersActions PARA LOS QUE INTENTA ACCEDER A ITEMS DE OTRAS SUCURSALES
 */
function registrar_accion_usuario_not_access_id($texto_action_user, $relItemAction) {
    global $usuario_id;
    global $usuario_sucursal;
    global $url_completa;
    $id_action_user = 29;
    $conexion = conectar_bd();
    
    if (!$conexion) {
        return false;
    }
    
    $userAgent = obtener_user_agent();
    $ipvVisitante = obtener_ip_visitante();
    
    $query = "INSERT INTO usersActions (
        userId,
        relidlistActions, 
        logTxt, 
        sucursalIdUserAction, 
        ipNumberUser, 
        userAgent,
        relItemAction,
        urlAction,
        dateAction
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
    
    $stmt = mysqli_prepare($conexion, $query);
    
    if (!$stmt) {
        mysqli_close($conexion);
        return false;
    }
    
    mysqli_stmt_bind_param($stmt, 'iissssis', 
        $usuario_id, 
        $id_action_user, 
        $texto_action_user, 
        $usuario_sucursal, 
        $ipvVisitante, 
        $userAgent,
        $relItemAction,
        $url_completa
    );
    
    $resultado = mysqli_stmt_execute($stmt);
    
    mysqli_stmt_close($stmt);
    mysqli_close($conexion);
    
}

/**
 * Comprueba jerarquía y contraseña del usuario para autorizar una acción sobre un ítem.
 *
 * @param int $usuario_id ID del usuario en sesión
 * @param string $contrasena Contraseña introducida por el usuario
 * @param int $sucursal ID de sucursal asociada a la acción
 * @param int $id_item id_type_Item de itemsSections
 * @return array{estado: string, mensaje: string}
 */
function comprobar_contrasena_usuario_autorizado_action($usuario_id, $contrasena, $sucursal, $id_item) {
    $usuario_id = (int) $usuario_id;
    $sucursal = (int) $sucursal;
    $id_item = (int) $id_item;
    $contrasena = (string) $contrasena;

    if ($usuario_id < 1 || $sucursal < 1 || $id_item < 1) {
        return [
            'estado' => 'no autorizado',
            'mensaje' => 'Datos de autorización no válidos',
        ];
    }

    if ($contrasena === '') {
        return [
            'estado' => 'no autorizado',
            'mensaje' => 'Debe introducir su contraseña',
        ];
    }

    $conexion = conectar_bd();
    if (!$conexion) {
        return [
            'estado' => 'no autorizado',
            'mensaje' => 'Error de conexión a la base de datos',
        ];
    }

    $stmt = mysqli_prepare(
        $conexion,
        "SELECT u.usuario, u.password, u.privilegio_usuario
         FROM usuarios u
         WHERE u.id_usuario = ? AND u.estado_usuario = 'true'
         LIMIT 1"
    );

    if (!$stmt) {
        mysqli_close($conexion);
        return [
            'estado' => 'no autorizado',
            'mensaje' => 'No se pudo consultar el usuario',
        ];
    }

    mysqli_stmt_bind_param($stmt, 'i', $usuario_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $usuarioData = $result ? mysqli_fetch_assoc($result) : null;
    mysqli_stmt_close($stmt);
    mysqli_close($conexion);

    if (!$usuarioData) {
        return [
            'estado' => 'no autorizado',
            'mensaje' => 'Usuario no encontrado o inactivo',
        ];
    }

    $usuarioLogin = (string) ($usuarioData['usuario'] ?? '');
    $privilegioId = (int) ($usuarioData['privilegio_usuario'] ?? 0);
    $itemName = obtenerIntemNameUrlBlankPageExtras($id_item);
    $itemLabel = $itemName !== '' ? $itemName : ('item ' . $id_item);

    if (!usuario_puede_acceder_item($privilegioId, $id_item)) {
        $textoActionUser = $usuarioLogin . ' no tiene jerarquía para autorizar la acción en ' . $itemLabel;
        registrar_accion_usuario($usuario_id, 29, $textoActionUser, $sucursal, $id_item);

        return [
            'estado' => 'no autorizado',
            'mensaje' => 'No tiene permisos para realizar esta acción',
        ];
    }

    if (!password_verify($contrasena, (string) $usuarioData['password'])) {
        $textoActionUser = $usuarioLogin . ' introdujo una contraseña incorrecta al autorizar acción en ' . $itemLabel;
        registrar_accion_usuario($usuario_id, 29, $textoActionUser, $sucursal, $id_item);

        return [
            'estado' => 'no autorizado',
            'mensaje' => 'Contraseña incorrecta',
        ];
    }

    $textoActionUser = $usuarioLogin . ' autorizó la acción en ' . $itemLabel . ' mediante contraseña';
    registrar_accion_usuario($usuario_id, 33, $textoActionUser, $sucursal, $id_item);

    return [
        'estado' => 'autorizado',
        'mensaje' => 'Autorizado',
    ];
}

/**
 * Función para registrar acciones del usuario en usersActions
 */
function registrar_accion_usuario($usuario_id, $id_action_user, $texto_action_user, $usuario_sucursal, $relItemAction, $urlAction = null) {
    $conexion = conectar_bd();
    
    if (!$conexion) {
        return false;
    }
    
    if ($urlAction === null) {
        // Intentar HTTP_REFERER primero (página desde donde vino la petición)
        $urlAction = $_SERVER['HTTP_REFERER'] ?? $_SERVER['REQUEST_URI'] ?? '';
        
        // Limpiar URL: solo ruta, sin dominio ni query params
        if (!empty($urlAction)) {
            $parsed = parse_url($urlAction, PHP_URL_PATH);
            $urlAction = $parsed ?: $urlAction;
        }
    }
    
    $userAgent = obtener_user_agent();
    $ipvVisitante = obtener_ip_visitante();
    
    $query = "INSERT INTO usersActions (
        userId,
        relidlistActions, 
        logTxt, 
        sucursalIdUserAction, 
        ipNumberUser, 
        userAgent,
        relItemAction,
        urlAction,
        dateAction
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
    
    $stmt = mysqli_prepare($conexion, $query);
    
    if (!$stmt) {
        mysqli_close($conexion);
        return false;
    }
    
    mysqli_stmt_bind_param($stmt, 'iissssis', 
        $usuario_id, 
        $id_action_user, 
        $texto_action_user, 
        $usuario_sucursal, 
        $ipvVisitante, 
        $userAgent,
        $relItemAction,
        $urlAction
    );
    
    $resultado = mysqli_stmt_execute($stmt);
    
    mysqli_stmt_close($stmt);
    mysqli_close($conexion);
    
    return $resultado;
}

/**
 * Función para generar iniciales del usuario
 * @param string $nombre_completo Nombre completo del usuario
 * @param string $usuario Nombre de usuario del sistema
 * @return string Iniciales del usuario (2 caracteres)
 */
function generar_iniciales_usuario($nombre_completo, $usuario) {
    // Si tenemos nombre completo, usar las primeras letras de nombre y apellido
    if (!empty($nombre_completo)) {
        $partes = explode(' ', trim($nombre_completo));
        if (count($partes) >= 2) {
            $inicial_nombre = substr($partes[0], 0, 1);
            $inicial_apellido = substr($partes[1], 0, 1);
            return strtoupper($inicial_nombre . $inicial_apellido);
        } elseif (count($partes) == 1) {
            return strtoupper(substr($partes[0], 0, 2));
        }
    }
    
    // Si no hay nombre completo, usar las dos primeras letras del usuario
    if (!empty($usuario)) {
        return strtoupper(substr($usuario, 0, 2));
    }
    
    // Fallback: mostrar "U" de Usuario
    return 'U';
}

/**
 * Función para obtener todas las empresas
 * @return array Array con las empresas disponibles
 */
function obtener_empresas() {
    $conexion = conectar_bd();
    
    if (!$conexion) {
        return [];
    }
    
    $query = "SELECT id_empresa, nombre_empresa FROM empresas ORDER BY nombre_empresa ASC";
    $resultado = mysqli_query($conexion, $query);
    
    if (!$resultado) {
        mysqli_close($conexion);
        return [];
    }
    
    $empresas = [];
    while ($row = mysqli_fetch_assoc($resultado)) {
        $empresas[] = [
            'id_empresa' => $row['id_empresa'],
            'nombre_empresa' => $row['nombre_empresa']
        ];
    }
    
    mysqli_free_result($resultado);
    mysqli_close($conexion);
    
    return $empresas;
}

/**
 * Proveedores de fundición activos para proformas.
 * @return array
 */
function obtener_proveedores_fundicion() {
    $conexion = conectar_bd();

    if (!$conexion) {
        return array();
    }

    $query = "SELECT id_proveedor, nombre_proveedor FROM proveedores WHERE fundicion = 'true' ORDER BY nombre_proveedor ASC";
    $resultado = mysqli_query($conexion, $query);

    if (!$resultado) {
        mysqli_close($conexion);
        return array();
    }

    $proveedores = array();
    while ($row = mysqli_fetch_assoc($resultado)) {
        $proveedores[] = array(
            'id_proveedor' => $row['id_proveedor'],
            'nombre_proveedor' => $row['nombre_proveedor']
        );
    }

    mysqli_free_result($resultado);
    mysqli_close($conexion);

    return $proveedores;
}

/**
 * Datos de cabecera y totales de una proforma por id.
 *
 * @param int $id_proforma
 * @return array|null
 */
function obtener_datos_proforma_ficha($id_proforma) {
    $id_proforma = (int) $id_proforma;
    if ($id_proforma <= 0) {
        return null;
    }

    $conexion = conectar_bd();
    if (!$conexion) {
        return null;
    }

    $sqlPf = "
        SELECT PRF.proforma_numero,
            PRF.empresa_proforma,
            PRF.estado_proforma,
            PRF.tipo_metal_proforma,
            PRF.fecha_creacion,
            PRF.fecha_envio,
            PRF.usuario_genera_proforma,
            PRF.usuario_envia_proforma,
            PRF.rel_semana_numero,
            PRF.semanas_proforma,
            PRF.year_rel,
            PRF.total_gramos_proforma,
            PRF.total_fino,
            PRF.importe_proforma,
            PRF.precio_gramo_proforma,
            PRF.fecha_standby,
            PRF.proveedor_proforma,
            PRF.forma_de_pago,
            EMP.nombre_empresa,
            PRO.nombre_proveedor,
            PRO.email_proveedor,
            FDP.nombre_forma_de_pago,
            COALESCE(
                NULLIF(TRIM(CONCAT(COALESCE(U.nombre_usuario, ''), ' ', COALESCE(U.apellido_usuario, ''))), ''),
                U.usuario
            ) AS creador_display,
            COALESCE(
                NULLIF(TRIM(CONCAT(COALESCE(U_env.nombre_usuario, ''), ' ', COALESCE(U_env.apellido_usuario, ''))), ''),
                U_env.usuario
            ) AS enviador_display
        FROM proformas PRF
        LEFT JOIN usuarios U ON U.id_usuario = PRF.usuario_genera_proforma
        LEFT JOIN usuarios U_env ON U_env.id_usuario = PRF.usuario_envia_proforma
        LEFT JOIN empresas EMP ON EMP.id_empresa = PRF.empresa_proforma
        LEFT JOIN proveedores PRO ON PRO.id_proveedor = PRF.proveedor_proforma
        LEFT JOIN formas_de_pago FDP ON FDP.id_forma_de_pago = PRF.forma_de_pago
        WHERE PRF.id_proforma = ?
        LIMIT 1
    ";

    $q = mysqli_prepare($conexion, $sqlPf);
    if (!$q) {
        mysqli_close($conexion);
        return null;
    }

    mysqli_stmt_bind_param($q, 'i', $id_proforma);
    mysqli_stmt_execute($q);
    $rq = mysqli_stmt_get_result($q);
    $rp = ($rq && ($row = mysqli_fetch_assoc($rq))) ? $row : null;
    if ($rq) {
        mysqli_free_result($rq);
    }
    mysqli_stmt_close($q);

    if (!$rp) {
        mysqli_close($conexion);
        return null;
    }

    $proforma_numero = isset($rp['proforma_numero']) ? (string) $rp['proforma_numero'] : '';
    $empresa_proforma = isset($rp['empresa_proforma']) ? (int) $rp['empresa_proforma'] : 0;
    $proforma_estado = isset($rp['estado_proforma']) ? (string) $rp['estado_proforma'] : '';
    $tipo_metal = isset($rp['tipo_metal_proforma']) ? trim((string) $rp['tipo_metal_proforma']) : '';
    $pf_fecha_creacion_txt = '—';
    $fc = isset($rp['fecha_creacion']) ? $rp['fecha_creacion'] : '';
    if (!empty($fc) && $fc !== '0000-00-00 00:00:00') {
        $pf_fecha_creacion_txt = date('d/m/Y H:i', strtotime($fc));
    }
    $pf_fecha_envio_txt = '—';
    $fe = isset($rp['fecha_envio']) ? $rp['fecha_envio'] : '';
    if (!empty($fe) && $fe !== '0000-00-00' && $fe !== '0000-00-00 00:00:00') {
        $pf_fecha_envio_txt = date('d/m/Y H:i', strtotime($fe));
    }
    $pf_creada_por_txt = '—';
    $cd = isset($rp['creador_display']) ? trim((string) $rp['creador_display']) : '';
    if ($cd !== '') {
        $pf_creada_por_txt = $cd;
    }
    $nombre_empresa = isset($rp['nombre_empresa']) ? trim((string) $rp['nombre_empresa']) : '';
    $nombre_proveedor = isset($rp['nombre_proveedor']) ? trim((string) $rp['nombre_proveedor']) : '';
    $proveedor_proforma = isset($rp['proveedor_proforma']) ? (int) $rp['proveedor_proforma'] : 0;
    $forma_de_pago = isset($rp['forma_de_pago']) ? (int) $rp['forma_de_pago'] : 0;
    $id_cuenta_banco_proforma = 0;
    $numero_forma_pago_rel = '';

    $relFormaPago = obtener_rel_forma_pago_proforma($id_proforma, $conexion);
    if ($relFormaPago) {
        $numero_forma_pago_rel = isset($relFormaPago['numero_forma_pago']) ? trim((string) $relFormaPago['numero_forma_pago']) : '';
        if ($forma_de_pago <= 0 && isset($relFormaPago['forma_de_pago_id'])) {
            $forma_de_pago = (int) $relFormaPago['forma_de_pago_id'];
        }
        if ($numero_forma_pago_rel !== '' && $empresa_proforma > 0) {
            $id_cuenta_banco_proforma = resolver_id_cuenta_banco_empresa_por_numero(
                $empresa_proforma,
                $numero_forma_pago_rel,
                $conexion
            );
        }
    }

    $rel_semana_numero = isset($rp['rel_semana_numero']) ? (int) $rp['rel_semana_numero'] : 0;
    $year_rel_proforma = isset($rp['year_rel']) ? (int) $rp['year_rel'] : 0;
    $semanas_proforma_db = isset($rp['semanas_proforma']) ? trim((string) $rp['semanas_proforma']) : '';
    $semanas_proforma = '—';
    $linea_semana_cabecera = '—';
    if ($semanas_proforma_db !== '') {
        $semanas_proforma = $semanas_proforma_db;
        $linea_semana_cabecera = str_replace(',', ', ', $semanas_proforma_db);
    } elseif ($rel_semana_numero > 0 && $year_rel_proforma > 0) {
        $semanas_proforma = $rel_semana_numero . ' / ' . $year_rel_proforma;
        $linea_semana_cabecera = $rel_semana_numero . ' · ' . $year_rel_proforma;
    } elseif ($rel_semana_numero > 0) {
        $semanas_proforma = (string) $rel_semana_numero;
        $linea_semana_cabecera = (string) $rel_semana_numero;
    }

    $total_gramos = isset($rp['total_gramos_proforma']) ? (float) $rp['total_gramos_proforma'] : 0.0;
    $total_fino = isset($rp['total_fino']) ? (float) $rp['total_fino'] : 0.0;
    $importe = isset($rp['importe_proforma']) ? (float) $rp['importe_proforma'] : 0.0;
    $precio_gramo = isset($rp['precio_gramo_proforma']) ? (float) $rp['precio_gramo_proforma'] : 0.0;

    $precio_gramo_inicial = '';
    if (isset($rp['precio_gramo_proforma']) && $rp['precio_gramo_proforma'] !== null && $rp['precio_gramo_proforma'] !== '') {
        $precio_gramo_inicial = (string) (float) $rp['precio_gramo_proforma'];
    }

    $fecha_standby = isset($rp['fecha_standby']) && $rp['fecha_standby'] !== ''
        ? (string) $rp['fecha_standby']
        : null;
    $fecha_standby_fmt = formatear_fecha_dmY_desde_db($fecha_standby);

    $metal_badge_class = 'secondary';
    $mLower = mb_strtolower($tipo_metal, 'UTF-8');
    if ($mLower !== '' && strpos($mLower, 'oro') !== false) {
        $metal_badge_class = 'warning';
    } elseif ($mLower !== '' && strpos($mLower, 'plata') !== false) {
        $metal_badge_class = 'secondary';
    }

    $cantidad_articulos = 0;
    $stmtCnt = mysqli_prepare(
        $conexion,
        'SELECT COUNT(*) AS c FROM items_proforma WHERE rel_proforma_id = ?'
    );
    if ($stmtCnt) {
        mysqli_stmt_bind_param($stmtCnt, 'i', $id_proforma);
        mysqli_stmt_execute($stmtCnt);
        $rc = mysqli_stmt_get_result($stmtCnt);
        $rowc = $rc ? mysqli_fetch_assoc($rc) : null;
        if ($rc) {
            mysqli_free_result($rc);
        }
        mysqli_stmt_close($stmtCnt);
        $cantidad_articulos = isset($rowc['c']) ? (int) $rowc['c'] : 0;
    }

    $precio_adelanto_gramo_inicial = '';
    $chkPad = mysqli_query($conexion, "SHOW COLUMNS FROM proformas LIKE 'precio_adelanto_gramo'");
    if ($chkPad && mysqli_num_rows($chkPad) > 0) {
        mysqli_free_result($chkPad);
        $stPad = mysqli_prepare($conexion, 'SELECT precio_adelanto_gramo FROM proformas WHERE id_proforma = ? LIMIT 1');
        if ($stPad) {
            mysqli_stmt_bind_param($stPad, 'i', $id_proforma);
            mysqli_stmt_execute($stPad);
            $rPad = mysqli_stmt_get_result($stPad);
            if ($rPad) {
                $rwPad = mysqli_fetch_assoc($rPad);
                if ($rwPad && isset($rwPad['precio_adelanto_gramo']) && $rwPad['precio_adelanto_gramo'] !== null && $rwPad['precio_adelanto_gramo'] !== '') {
                    $precio_adelanto_gramo_inicial = (string) (float) $rwPad['precio_adelanto_gramo'];
                }
                mysqli_free_result($rPad);
            }
            mysqli_stmt_close($stPad);
        }
    } elseif ($chkPad) {
        mysqli_free_result($chkPad);
    }

    $nombre_forma_de_pago = isset($rp['nombre_forma_de_pago']) ? trim((string) $rp['nombre_forma_de_pago']) : '';
    if ($nombre_forma_de_pago === '' && $forma_de_pago > 0) {
        $stFp = mysqli_prepare($conexion, 'SELECT nombre_forma_de_pago FROM formas_de_pago WHERE id_forma_de_pago = ? LIMIT 1');
        if ($stFp) {
            mysqli_stmt_bind_param($stFp, 'i', $forma_de_pago);
            mysqli_stmt_execute($stFp);
            $rFp = mysqli_stmt_get_result($stFp);
            if ($rFp && ($rowFp = mysqli_fetch_assoc($rFp))) {
                $nombre_forma_de_pago = trim((string) $rowFp['nombre_forma_de_pago']);
            }
            if ($rFp) {
                mysqli_free_result($rFp);
            }
            mysqli_stmt_close($stFp);
        }
    }

    $enviada_por_txt = '—';
    $ed = isset($rp['enviador_display']) ? trim((string) $rp['enviador_display']) : '';
    if ($ed !== '') {
        $enviada_por_txt = $ed;
    }

    $enviada_a_txt = '—';
    $email_proveedor = isset($rp['email_proveedor']) ? trim((string) $rp['email_proveedor']) : '';
    if ($email_proveedor !== '') {
        $enviada_a_txt = $email_proveedor;
    } elseif ($nombre_proveedor !== '') {
        $enviada_a_txt = $nombre_proveedor;
    }

    $es_transferencia_proforma = ($forma_de_pago === 224);
    $cuenta_banco_resumen_txt = '—';
    if ($es_transferencia_proforma) {
        if ($id_cuenta_banco_proforma > 0 && $empresa_proforma > 0) {
            $rowCuenta = obtener_cuenta_banco_empresa_por_id($id_cuenta_banco_proforma, $empresa_proforma, $conexion);
            if ($rowCuenta) {
                $bancoCuenta = isset($rowCuenta['banco_cuenta']) ? trim((string) $rowCuenta['banco_cuenta']) : '';
                $numeroCuenta = isset($rowCuenta['numerocuenta']) ? trim((string) $rowCuenta['numerocuenta']) : '';
                if ($bancoCuenta !== '' && $numeroCuenta !== '') {
                    $cuenta_banco_resumen_txt = $bancoCuenta . ' · ' . $numeroCuenta;
                } elseif ($numeroCuenta !== '') {
                    $cuenta_banco_resumen_txt = $numeroCuenta;
                } elseif ($bancoCuenta !== '') {
                    $cuenta_banco_resumen_txt = $bancoCuenta;
                }
            }
        }
        if ($cuenta_banco_resumen_txt === '—' && $numero_forma_pago_rel !== '') {
            $cuenta_banco_resumen_txt = $numero_forma_pago_rel;
        }
    }

    mysqli_close($conexion);

    $estado_class = 'secondary';
    switch ($proforma_estado) {
        case 'pendiente':
            $estado_class = 'warning';
            break;
        case 'generada':
            $estado_class = 'success';
            break;
        case 'enviada':
            $estado_class = 'info';
            break;
        case 'editando':
            $estado_class = 'warning';
            break;
        case 'liquidada':
            $estado_class = 'success';
            break;
        case 'cancelada':
            $estado_class = 'danger';
            break;
    }

    return array(
        'id_proforma' => $id_proforma,
        'proforma_numero' => $proforma_numero,
        'empresa_proforma' => $empresa_proforma,
        'proveedor_proforma' => $proveedor_proforma,
        'forma_de_pago' => $forma_de_pago,
        'nombre_forma_de_pago' => $nombre_forma_de_pago,
        'es_transferencia_proforma' => $es_transferencia_proforma,
        'cuenta_banco_resumen_txt' => $cuenta_banco_resumen_txt,
        'enviada_por_txt' => $enviada_por_txt,
        'enviada_a_txt' => $enviada_a_txt,
        'id_cuenta_banco_proforma' => $id_cuenta_banco_proforma,
        'numero_forma_pago_rel' => $numero_forma_pago_rel,
        'estado_proforma' => $proforma_estado,
        'estado_class' => $estado_class,
        'tipo_metal_proforma' => $tipo_metal,
        'metal_badge_class' => $metal_badge_class,
        'fecha_creacion_txt' => $pf_fecha_creacion_txt,
        'fecha_envio_txt' => $pf_fecha_envio_txt,
        'creada_por_txt' => $pf_creada_por_txt,
        'nombre_empresa' => $nombre_empresa,
        'nombre_proveedor' => $nombre_proveedor,
        'rel_semana_numero' => $rel_semana_numero,
        'year_rel' => $year_rel_proforma,
        'semanas_proforma' => $semanas_proforma,
        'semanas_proforma_db' => $semanas_proforma_db,
        'linea_semana_cabecera' => $linea_semana_cabecera,
        'total_gramos' => $total_gramos,
        'total_gramos_txt' => number_format($total_gramos, 2, ',', '.'),
        'total_fino' => $total_fino,
        'total_fino_txt' => number_format($total_fino, 3, ',', '.'),
        'importe' => $importe,
        'importe_txt' => number_format($importe, 2, ',', '.') . ' €',
        'precio_gramo' => $precio_gramo,
        'precio_gramo_txt' => number_format($precio_gramo, 2, ',', '.') . ' €',
        'precio_gramo_inicial' => $precio_gramo_inicial,
        'fecha_standby' => $fecha_standby,
        'fecha_standby_fmt' => $fecha_standby_fmt,
        'precio_adelanto_gramo_inicial' => $precio_adelanto_gramo_inicial,
        'cantidad_articulos' => $cantidad_articulos,
    );
}

/**
 * Función para obtener usuarios activos
 * @return array Array con los usuarios activos disponibles
 */
function obtenerUsuarios() {
    $conexion = conectar_bd();
    
    if (!$conexion) {
        return [];
    }
    
    $query = "SELECT id_usuario, nombre_usuario, apellido_usuario FROM usuarios WHERE estado_usuario = 'true' ORDER BY nombre_usuario ASC, apellido_usuario ASC";
    $resultado = mysqli_query($conexion, $query);
    
    if (!$resultado) {
        mysqli_close($conexion);
        return [];
    }
    
    $usuarios = [];
    while ($row = mysqli_fetch_assoc($resultado)) {
        $usuarios[] = [
            'id_usuario' => $row['id_usuario'],
            'nombre_completo' => $row['nombre_usuario'] . ' ' . $row['apellido_usuario']
        ];
    }
    
    mysqli_free_result($resultado);
    mysqli_close($conexion);
    
    return $usuarios;
}

/**
 * Función para generar código de firmas aleatorio
 * @return string Código alfanumérico de 6 dígitos en mayúsculas
 */
function generarCodigoFirmas() {
    $caracteres = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $codigo = '';
    
    for ($i = 0; $i < 6; $i++) {
        $codigo .= $caracteres[rand(0, strlen($caracteres) - 1)];
    }
    
    return $codigo;
}

/**
 * Función para generar opciones de sellos en un select
 * @param int $sello_id ID del sello seleccionado (opcional)
 * @return void Imprime las opciones del select
 */
function generaSellos($sello_id = 0) {
    $conexion = conectar_bd();
    
    if (!$conexion) {
        echo "<option value='0'>Error de conexión</option>";
        return;
    }
    
    // Establecer charset UTF-8
    mysqli_set_charset($conexion, 'utf8');
    
    $query = "SELECT * FROM sellos ORDER BY nombre_sello ASC";
    $resultado = mysqli_query($conexion, $query);
    
    if (!$resultado) {
        echo "<option value='0'>Error en consulta</option>";
        mysqli_close($conexion);
        return;
    }
    
    echo "<option value='0'>Seleccione sello</option>";
    
    while ($registro = mysqli_fetch_assoc($resultado)) {
        echo "<option ";
        if ($registro['id_sello'] == $sello_id) {
            echo "selected='selected'";
        }
        echo " value='" . $registro['id_sello'] . "'>" . htmlspecialchars($registro['nombre_sello']) . "</option>";
    }
    
    mysqli_free_result($resultado);
    mysqli_close($conexion);
}

/**
 * Función para obtener el nombre de la empresa por ID
 * @param int $id_empresa ID de la empresa
 * @return string|false Nombre de la empresa o false si no existe
 */
function obtener_nombre_empresa($id_empresa) {
    $conexion = conectar_bd();
    
    if (!$conexion) {
        return false;
    }
    
    $query = "SELECT nombre_empresa FROM empresas WHERE id_empresa = ?";
    $stmt = mysqli_prepare($conexion, $query);
    
    if (!$stmt) {
        mysqli_close($conexion);
        return false;
    }
    
    mysqli_stmt_bind_param($stmt, 'i', $id_empresa);
    mysqli_stmt_execute($stmt);
    $resultado = mysqli_stmt_get_result($stmt);
    
    if ($resultado && mysqli_num_rows($resultado) > 0) {
        $row = mysqli_fetch_assoc($resultado);
        $nombre = $row['nombre_empresa'];
        mysqli_stmt_close($stmt);
        mysqli_close($conexion);
        return $nombre;
    }
    
    mysqli_stmt_close($stmt);
    mysqli_close($conexion);
    return false;
}

/**
 * Función para obtener el nombre de la sucursal por ID
 * @param int $id_sucursal ID de la sucursal
 * @return string|false Nombre de la sucursal o false si no existe
 */
/**
 * Genera un UUID v4 (versión 4)
 * @return string UUID v4
 */
function generarUUIDv4() {
    return sprintf(
        '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

function obtener_nombre_sucursal($id_sucursal) {
    $conexion = conectar_bd();
    
    if (!$conexion) {
        return false;
    }
    
    $query = "SELECT nombre_sucursal FROM sucursal WHERE id_sucursal = ?";
    $stmt = mysqli_prepare($conexion, $query);
    
    if (!$stmt) {
        mysqli_close($conexion);
        return false;
    }
    
    mysqli_stmt_bind_param($stmt, 'i', $id_sucursal);
    mysqli_stmt_execute($stmt);
    $resultado = mysqli_stmt_get_result($stmt);
    
    if ($resultado && mysqli_num_rows($resultado) > 0) {
        $row = mysqli_fetch_assoc($resultado);
        $nombre = $row['nombre_sucursal'];
        mysqli_stmt_close($stmt);
        mysqli_close($conexion);
        return $nombre;
    }
    
    mysqli_stmt_close($stmt);
    mysqli_close($conexion);
    return false;
}

/**
 * Función para obtener el nombre del proveedor por ID
 * @param int $id_proveedor ID del proveedor
 * @return string|false Nombre del proveedor o false si no existe
 */
function obtener_nombre_proveedor($id_proveedor) {
    $conexion = conectar_bd();
    
    if (!$conexion) {
        return false;
    }
    
    $query = "SELECT nombre_proveedor FROM proveedores WHERE id_proveedor = ?";
    $stmt = mysqli_prepare($conexion, $query);
    
    if (!$stmt) {
        mysqli_close($conexion);
        return false;
    }
    
    mysqli_stmt_bind_param($stmt, 'i', $id_proveedor);
    mysqli_stmt_execute($stmt);
    $resultado = mysqli_stmt_get_result($stmt);
    
    if ($resultado && mysqli_num_rows($resultado) > 0) {
        $row = mysqli_fetch_assoc($resultado);
        $nombre = $row['nombre_proveedor'];
        mysqli_stmt_close($stmt);
        mysqli_close($conexion);
        return $nombre;
    }
    
    mysqli_stmt_close($stmt);
    mysqli_close($conexion);
    return false;
}

/**
 * Función para obtener el nombre del tipo de gasto por ID
 * @param int $id_tipo_gasto ID del tipo de gasto
 * @return string|false Nombre del tipo de gasto o false si no existe
 */
function obtener_nombre_tipo_gasto($id_tipo_gasto) {
    $conexion = conectar_bd();
    
    if (!$conexion) {
        return false;
    }
    
    $query = "SELECT nombre_tipo_gasto FROM tipo_de_gasto WHERE id_tipo_gasto = ?";
    $stmt = mysqli_prepare($conexion, $query);
    
    if (!$stmt) {
        mysqli_close($conexion);
        return false;
    }
    
    mysqli_stmt_bind_param($stmt, 'i', $id_tipo_gasto);
    mysqli_stmt_execute($stmt);
    $resultado = mysqli_stmt_get_result($stmt);
    
    if ($resultado && mysqli_num_rows($resultado) > 0) {
        $row = mysqli_fetch_assoc($resultado);
        $nombre = $row['nombre_tipo_gasto'];
        mysqli_stmt_close($stmt);
        mysqli_close($conexion);
        return $nombre;
    }
    
    mysqli_stmt_close($stmt);
    mysqli_close($conexion);
    return false;
}

/**
 * Función para obtener el nombre de la forma de pago por ID
 * @param int $id_forma_de_pago ID de la forma de pago
 * @return string|false Nombre de la forma de pago o false si no existe
 */
function obtener_nombre_forma_de_pago($id_forma_de_pago) {
    $conexion = conectar_bd();
    
    if (!$conexion) {
        return false;
    }
    
    $query = "SELECT nombre_forma_de_pago FROM formas_de_pago WHERE id_forma_de_pago = ?";
    $stmt = mysqli_prepare($conexion, $query);
    
    if (!$stmt) {
        mysqli_close($conexion);
        return false;
    }
    
    mysqli_stmt_bind_param($stmt, 'i', $id_forma_de_pago);
    mysqli_stmt_execute($stmt);
    $resultado = mysqli_stmt_get_result($stmt);
    
    if ($resultado && mysqli_num_rows($resultado) > 0) {
        $row = mysqli_fetch_assoc($resultado);
        $nombre = $row['nombre_forma_de_pago'];
        mysqli_stmt_close($stmt);
        mysqli_close($conexion);
        return $nombre;
    }
    
    mysqli_stmt_close($stmt);
    mysqli_close($conexion);
    return false;
}

/**
 * Devuelve el valor “activo” de un registro en configuracion_general según typ_config.
 *
 * - text: texto_value
 * - boleano: true/false según boleano_value
 * - options: varchar_value (opción seleccionada; la lista está en options_value)
 * - integro: integro_value (int)
 * - decimal: decimal_value (float)
 * - varchar: varchar_value
 *
 * @param int $id_config id_config
 * @return string|int|float|bool|false Valor interpretado, o false si el id no es válido, no hay fila o falla la consulta
 */
function obtenerValueConfig($id_config) {
    $id_config = (int) $id_config;
    if ($id_config <= 0) {
        return false;
    }

    $conexion = conectar_bd();
    if (!$conexion) {
        return false;
    }

    $query = "SELECT typ_config, texto_value, boleano_value, options_value, integro_value, decimal_value, varchar_value
              FROM configuracion_general WHERE id_config = ? LIMIT 1";
    $stmt = mysqli_prepare($conexion, $query);
    if (!$stmt) {
        mysqli_close($conexion);
        return false;
    }

    mysqli_stmt_bind_param($stmt, 'i', $id_config);
    mysqli_stmt_execute($stmt);
    $resultado = mysqli_stmt_get_result($stmt);
    $row = ($resultado && mysqli_num_rows($resultado) > 0) ? mysqli_fetch_assoc($resultado) : null;
    mysqli_stmt_close($stmt);
    mysqli_close($conexion);

    if (!$row) {
        return false;
    }

    switch ($row['typ_config']) {
        case 'text':
            return $row['texto_value'];
        case 'boleano':
            return ($row['boleano_value'] === 'true');
        case 'options':
            return $row['varchar_value'];
        case 'integro':
            return (int) $row['integro_value'];
        case 'decimal':
            return (float) $row['decimal_value'];
        case 'varchar':
            return $row['varchar_value'];
        default:
            return false;
    }
}

/**
 * Texto legal del pie de factura según tipo (`configuracion_general`).
 *
 * id_config 4 = REBU (artículos), 5 = oro inversión, 9 = régimen general (renovaciones).
 *
 * @param string $tipo_factura articulos|oro_inversion|renovaciones|...
 * @return string
 */
function obtenerTextoLegalFactura($tipo_factura = '')
{
    $tipo_factura = trim((string) $tipo_factura);
    if ($tipo_factura === 'renovaciones') {
        $texto = obtenerValueConfig(9);
    } elseif ($tipo_factura === 'oro_inversion') {
        $texto = obtenerValueConfig(5);
    } else {
        $texto = obtenerValueConfig(4);
    }

    return ($texto !== false) ? (string) $texto : '';
}

/**
 * Tope legal/importe máximo configurado para factura simplificada (`configuracion_general.id_config = 1`, columna `decimal_value`).
 *
 * @return float 0.0 si no hay fila o el valor es nulo
 */
function obtenerMaximoTotalFacturaSimplificada()
{
    $conexion = conectar_bd();
    if (!$conexion) {
        return false;
    }

    $stmt = mysqli_prepare(
        $conexion,
        'SELECT decimal_value FROM configuracion_general WHERE id_config = 1 LIMIT 1'
    );
    if (!$stmt) {
        mysqli_close($conexion);
        return false;
    }

    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $row = $res ? mysqli_fetch_assoc($res) : null;
    mysqli_stmt_close($stmt);
    mysqli_close($conexion);

    if ($row === null || !array_key_exists('decimal_value', $row) || $row['decimal_value'] === null) {
        return false;
    }

    return (float) ($row['decimal_value'] ?? 0.0);
}

/**
 * Función para generar select de artículos lotes
 * @param int $id_articulo ID del artículo seleccionado (opcional)
 * @param int $sucursal_articulo ID de la sucursal (opcional)
 * @param string $name Nombre del select
 * @param string $id ID del select
 * @param bool $required Si es requerido
 */
function generarSelectArticulosLotes($id_articulo = 0, $sucursal_articulo = 0, $name = 'id_articulo', $id = 'id_articulo', $required = false) {
    $conexion = conectar_bd();
    
    if (!$conexion) {
        echo "<select class='form-select select2' id='{$id}' name='{$name}'" . ($required ? ' required' : '') . ">";
        echo "<option value=''>Error de conexión</option>";
        echo "</select>";
        return;
    }
    
    $query = "SELECT id_articulo, descripcion_articulo FROM articulos_lotes";
    $params = [];
    $types = "";
    
    if ($sucursal_articulo > 0) {
        $query .= " WHERE sucursal_articulo = ?";
        $params[] = $sucursal_articulo;
        $types .= "i";
    }
    
    $query .= " ORDER BY descripcion_articulo ASC";
    
    $stmt = mysqli_prepare($conexion, $query);
    if (!$stmt) {
        echo "<select class='form-select select2' id='{$id}' name='{$name}'" . ($required ? ' required' : '') . ">";
        echo "<option value=''>Error en consulta</option>";
        echo "</select>";
        mysqli_close($conexion);
        return;
    }
    
    if (!empty($params)) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    
    mysqli_stmt_execute($stmt);
    $resultado = mysqli_stmt_get_result($stmt);
    
    echo "<select class='form-select select2' id='{$id}' name='{$name}'" . ($required ? ' required' : '') . ">";
    echo "<option value=''>Seleccionar...</option>";
    
    while ($row = mysqli_fetch_assoc($resultado)) {
        $selected = ($row['id_articulo'] == $id_articulo) ? 'selected' : '';
        echo "<option value='{$row['id_articulo']}' {$selected}>" . htmlspecialchars($row['descripcion_articulo']) . "</option>";
    }
    
    echo "</select>";
    
    mysqli_stmt_close($stmt);
    mysqli_close($conexion);
}

/**
 * Función para generar select de clientes
 * @param int $id_cliente ID del cliente seleccionado (opcional)
 * @param int $sucursal ID de la sucursal (opcional)
 * @param string $name Nombre del select
 * @param string $id ID del select
 * @param bool $required Si es requerido
 */
function generarSelectClientes($id_cliente = 0, $sucursal = 0, $name = 'id_cliente', $id = 'id_cliente', $required = false) {
    $conexion = conectar_bd();
    
    if (!$conexion) {
        echo "<select class='form-select select2' id='{$id}' name='{$name}'" . ($required ? ' required' : '') . ">";
        echo "<option value=''>Error de conexión</option>";
        echo "</select>";
        return;
    }
    
    $query = "SELECT id_cliente, nombre, apellido FROM clientes";
    $params = [];
    $types = "";
    
    if ($sucursal > 0) {
        $query .= " WHERE sucursal = ?";
        $params[] = $sucursal;
        $types .= "i";
    }
    
    $query .= " ORDER BY nombre ASC, apellido ASC";
    
    $stmt = mysqli_prepare($conexion, $query);
    if (!$stmt) {
        echo "<select class='form-select select2' id='{$id}' name='{$name}'" . ($required ? ' required' : '') . ">";
        echo "<option value=''>Error en consulta</option>";
        echo "</select>";
        mysqli_close($conexion);
        return;
    }
    
    if (!empty($params)) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    
    mysqli_stmt_execute($stmt);
    $resultado = mysqli_stmt_get_result($stmt);
    
    echo "<select class='form-select select2' id='{$id}' name='{$name}'" . ($required ? ' required' : '') . ">";
    echo "<option value=''>Seleccionar...</option>";
    
    while ($row = mysqli_fetch_assoc($resultado)) {
        $selected = ($row['id_cliente'] == $id_cliente) ? 'selected' : '';
        $nombre_completo = trim($row['nombre'] . ' ' . $row['apellido']);
        echo "<option value='{$row['id_cliente']}' {$selected}>" . htmlspecialchars($nombre_completo) . "</option>";
    }
    
    echo "</select>";
    
    mysqli_stmt_close($stmt);
    mysqli_close($conexion);
}

/**
 * Función para generar select de usuarios
 * @param int $id_usuario ID del usuario seleccionado (opcional)
 * @param int $sucursal_usuario ID de la sucursal (opcional)
 * @param string $name Nombre del select
 * @param string $id ID del select
 * @param bool $required Si es requerido
 */
function generarSelectUsuarios($id_usuario = 0, $sucursal_usuario = 0, $name = 'id_usuario', $id = 'id_usuario', $required = false) {
    $conexion = conectar_bd();
    
    if (!$conexion) {
        echo "<select class='form-select select2' id='{$id}' name='{$name}'" . ($required ? ' required' : '') . ">";
        echo "<option value=''>Error de conexión</option>";
        echo "</select>";
        return;
    }
    
    $query = "SELECT id_usuario, usuario FROM usuarios";
    $params = [];
    $types = "";
    
    if ($sucursal_usuario > 0) {
        $query .= " WHERE sucursal_usuario = ?";
        $params[] = $sucursal_usuario;
        $types .= "i";
    }
    
    $query .= " ORDER BY usuario ASC";
    
    $stmt = mysqli_prepare($conexion, $query);
    if (!$stmt) {
        echo "<select class='form-select select2' id='{$id}' name='{$name}'" . ($required ? ' required' : '') . ">";
        echo "<option value=''>Error en consulta</option>";
        echo "</select>";
        mysqli_close($conexion);
        return;
    }
    
    if (!empty($params)) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    
    mysqli_stmt_execute($stmt);
    $resultado = mysqli_stmt_get_result($stmt);
    
    echo "<select class='form-select select2' id='{$id}' name='{$name}'" . ($required ? ' required' : '') . ">";
    echo "<option value=''>Seleccionar...</option>";
    
    while ($row = mysqli_fetch_assoc($resultado)) {
        $selected = ($row['id_usuario'] == $id_usuario) ? 'selected' : '';
        echo "<option value='{$row['id_usuario']}' {$selected}>" . htmlspecialchars($row['usuario']) . "</option>";
    }
    
    echo "</select>";
    
    mysqli_stmt_close($stmt);
    mysqli_close($conexion);
}

/**
 * Función para generar select de empresas
 * @param int $id_empresa ID de la empresa seleccionada (opcional)
 * @param string $name Nombre del select
 * @param string $id ID del select
 * @param bool $required Si es requerido
 */
function generarSelectEmpresas($id_empresa = 0, $name = 'id_empresa', $id = 'id_empresa', $required = false) {
    $conexion = conectar_bd();
    
    if (!$conexion) {
        echo "<select class='form-select select2' id='{$id}' name='{$name}'" . ($required ? ' required' : '') . ">";
        echo "<option value=''>Error de conexión</option>";
        echo "</select>";
        return;
    }
    
    $query = "SELECT id_empresa, nombre_empresa FROM empresas ORDER BY nombre_empresa ASC";
    $resultado = mysqli_query($conexion, $query);
    
    if (!$resultado) {
        echo "<select class='form-select select2' id='{$id}' name='{$name}'" . ($required ? ' required' : '') . ">";
        echo "<option value=''>Error en consulta</option>";
        echo "</select>";
        mysqli_close($conexion);
        return;
    }
    
    echo "<select class='form-select select2' id='{$id}' name='{$name}'" . ($required ? ' required' : '') . ">";
    echo "<option value=''>Seleccionar...</option>";
    
    while ($row = mysqli_fetch_assoc($resultado)) {
        $selected = ($row['id_empresa'] == $id_empresa) ? 'selected' : '';
        echo "<option value='{$row['id_empresa']}' {$selected}>" . htmlspecialchars($row['nombre_empresa']) . "</option>";
    }
    
    echo "</select>";
    
    mysqli_close($conexion);
}

/**
 * Función para generar select de impuestos
 * @param int $id_impuesto ID del impuesto seleccionado (opcional)
 * @param string $name Nombre del select
 * @param string $id ID del select
 * @param bool $required Si es requerido
 */
function generarSelectImpuestos($id_impuesto = 0, $name = 'id_impuesto', $id = 'id_impuesto', $required = false) {
    $conexion = conectar_bd();
    
    if (!$conexion) {
        echo "<select class='form-select select2' id='{$id}' name='{$name}'" . ($required ? ' required' : '') . ">";
        echo "<option value=''>Error de conexión</option>";
        echo "</select>";
        return;
    }
    
    $query = "SELECT id_impuesto, nombre_impuesto, valor_impuesto FROM impuestos ORDER BY nombre_impuesto ASC";
    $resultado = mysqli_query($conexion, $query);
    
    if (!$resultado) {
        echo "<select class='form-select select2' id='{$id}' name='{$name}'" . ($required ? ' required' : '') . ">";
        echo "<option value=''>Error en consulta</option>";
        echo "</select>";
        mysqli_close($conexion);
        return;
    }
    
    echo "<select class='form-select select2' id='{$id}' name='{$name}'" . ($required ? ' required' : '') . ">";
    echo "<option value=''>Seleccionar...</option>";
    
    while ($row = mysqli_fetch_assoc($resultado)) {
        $selected = ($row['id_impuesto'] == $id_impuesto) ? 'selected' : '';
        $texto = $row['nombre_impuesto'] . ' (' . $row['valor_impuesto'] . '%)';
        echo "<option value='{$row['id_impuesto']}' {$selected}>" . htmlspecialchars($texto) . "</option>";
    }
    
    echo "</select>";
    
    mysqli_close($conexion);
}

/**
 * Función para generar select de nacionalidades
 * @param int $id ID de la nacionalidad seleccionada (opcional)
 * @param string $name Nombre del select
 * @param string $id_select ID del select
 * @param bool $required Si es requerido
 * @param int $id_cliente ID del cliente (opcional)
 */
function generarSelectNacionalidades($id = 0, $required = false) {
    $conexion = conectar_bd();
    
    if (!$conexion) {
        echo "<select class='form-select select2' id='nacionalidad' name='nacionalidad'" . ($required ? ' required' : '') . ">";
        echo "<option value=''>Error de conexión</option>";
        echo "</select>";
        return;
    }
    
    
    
    $query = "SELECT id, nombre_nacionalidad FROM nacionalidades ORDER BY nombre_nacionalidad ASC";
    $resultado = mysqli_query($conexion, $query);
    
    if (!$resultado) {
        echo "<select class='form-select select2' id='nacionalidad' name='nacionalidad'" . ($required ? ' required' : '') . ">";
        echo "<option value=''>Error en consulta</option>";
        echo "</select>";
        mysqli_close($conexion);
        return;
    }
    
    echo "<select class='form-select select2' id='nacionalidad' name='nacionalidad'" . ($required ? ' required' : '') . ">";
    echo "<option value=''>Seleccionar...</option>";
    $selectedEspanola = ((int) $id === 54) ? 'selected' : '';
    echo "<option value='54' {$selectedEspanola}>Española</option>";
    
    while ($row = mysqli_fetch_assoc($resultado)) {
        if($row['id'] != 54) {
            $selected = ($row['id'] == $id) ? 'selected' : '';
            echo "<option value='{$row['id']}' {$selected}>" . htmlspecialchars($row['nombre_nacionalidad']) . "</option>";
        }
    }
    
    echo "</select>";
    
    mysqli_close($conexion);
}

/**
 * Función para generar select de sucursales
 * @param int $id_sucursal ID de la sucursal seleccionada (opcional)
 * @param string $name Nombre del select
 * @param string $id ID del select
 * @param bool $required Si es requerido
 */
function generarSelectSucursales($id_sucursal = 0, $name = 'id_sucursal', $id = 'id_sucursal', $required = false) {
    $conexion = conectar_bd();
    
    if (!$conexion) {
        echo "<select class='form-select select2' id='{$id}' name='{$name}'" . ($required ? ' required' : '') . ">";
        echo "<option value=''>Error de conexión</option>";
        echo "</select>";
        return;
    }
    
    $query = "SELECT id_sucursal, nombre_sucursal FROM sucursal ORDER BY nombre_sucursal ASC";
    $resultado = mysqli_query($conexion, $query);
    
    if (!$resultado) {
        echo "<select class='form-select select2' id='{$id}' name='{$name}'" . ($required ? ' required' : '') . ">";
        echo "<option value=''>Error en consulta</option>";
        echo "</select>";
        mysqli_close($conexion);
        return;
    }
    
    echo "<select class='form-select select2' id='{$id}' name='{$name}'" . ($required ? ' required' : '') . ">";
    echo "<option value=''>Seleccionar...</option>";
    
    while ($row = mysqli_fetch_assoc($resultado)) {
        $selected = ($row['id_sucursal'] == $id_sucursal) ? 'selected' : '';
        echo "<option value='{$row['id_sucursal']}' {$selected}>" . htmlspecialchars($row['nombre_sucursal']) . "</option>";
    }
    
    echo "</select>";
    
    mysqli_close($conexion);
}

function obtener_select_sucursales_habilitadas() {
    $conexion = conectar_bd();
    
    if (!$conexion) {
        echo "<option value=''>Error de conexión</option>";
        return;
    }
    
    $query = "SELECT id_sucursal, nombre_sucursal FROM sucursal WHERE estado_tienda = 'habilitada' ORDER BY nombre_sucursal ASC";
    $resultado = mysqli_query($conexion, $query);
    
    if (!$resultado) {
        echo "<option value=''>Error en consulta</option>";
        mysqli_close($conexion);
        return;
    }
    
    while ($row = mysqli_fetch_assoc($resultado)) {
        echo "<option value='{$row['id_sucursal']}'>" . htmlspecialchars($row['nombre_sucursal']) . "</option>";
    }
    
    mysqli_close($conexion);
}

/**
 * Opciones de sucursales habilitadas usando nombre_sucursal como value (filtros por nombre).
 */
function obtener_opciones_sucursales_habilitadas_por_nombre() {
    $conexion = conectar_bd();

    if (!$conexion) {
        echo "<option value=''>Error de conexión</option>";
        return;
    }

    $query = "SELECT nombre_sucursal FROM sucursal WHERE estado_tienda = 'habilitada' ORDER BY nombre_sucursal ASC";
    $resultado = mysqli_query($conexion, $query);

    if (!$resultado) {
        echo "<option value=''>Error en consulta</option>";
        mysqli_close($conexion);
        return;
    }

    while ($row = mysqli_fetch_assoc($resultado)) {
        $nombre = htmlspecialchars($row['nombre_sucursal'], ENT_QUOTES, 'UTF-8');
        echo "<option value=\"{$nombre}\">{$nombre}</option>";
    }

    mysqli_close($conexion);
}

/**
 * Función para generar select de proveedores
 * @param int $id_proveedor ID del proveedor seleccionado (opcional)
 * @param string $name Nombre del select
 * @param string $id ID del select
 * @param bool $required Si es requerido
 */
function generarSelectProveedores($id_proveedor = 0, $name = 'id_proveedor', $id = 'id_proveedor', $required = false) {
    $conexion = conectar_bd();
    
    if (!$conexion) {
        echo "<select class='form-select select2' id='{$id}' name='{$name}'" . ($required ? ' required' : '') . ">";
        echo "<option value=''>Error de conexión</option>";
        echo "</select>";
        return;
    }
    
    $query = "SELECT id_proveedor, nombre_proveedor FROM proveedores ORDER BY nombre_proveedor ASC";
    $resultado = mysqli_query($conexion, $query);
    
    if (!$resultado) {
        echo "<select class='form-select select2' id='{$id}' name='{$name}'" . ($required ? ' required' : '') . ">";
        echo "<option value=''>Error en consulta</option>";
        echo "</select>";
        mysqli_close($conexion);
        return;
    }
    
    echo "<select class='form-select select2' id='{$id}' name='{$name}'" . ($required ? ' required' : '') . ">";
    echo "<option value=''>Seleccionar...</option>";
    
    while ($row = mysqli_fetch_assoc($resultado)) {
        $selected = ($row['id_proveedor'] == $id_proveedor) ? 'selected' : '';
        echo "<option value='{$row['id_proveedor']}' {$selected}>" . htmlspecialchars($row['nombre_proveedor']) . "</option>";
    }
    
    echo "</select>";
    
    mysqli_close($conexion);
}

/**
 * Función para generar select de tipos de gasto
 * @param int $id_tipo_gasto ID del tipo de gasto seleccionado (opcional)
 * @param string $name Nombre del select
 * @param string $id ID del select
 * @param bool $required Si es requerido
 */
function generarSelectTiposGasto($id_tipo_gasto = 0, $name = 'id_tipo_gasto', $id = 'id_tipo_gasto', $required = false) {
    $conexion = conectar_bd();
    
    if (!$conexion) {
        echo "<select class='form-select select2' id='{$id}' name='{$name}'" . ($required ? ' required' : '') . ">";
        echo "<option value=''>Error de conexión</option>";
        echo "</select>";
        return;
    }
    
    $query = "SELECT id_tipo_gasto, nombre_tipo_gasto FROM tipo_de_gasto ORDER BY nombre_tipo_gasto ASC";
    $resultado = mysqli_query($conexion, $query);
    
    if (!$resultado) {
        echo "<select class='form-select select2' id='{$id}' name='{$name}'" . ($required ? ' required' : '') . ">";
        echo "<option value=''>Error en consulta</option>";
        echo "</select>";
        mysqli_close($conexion);
        return;
    }
    
    echo "<select class='form-select select2' id='{$id}' name='{$name}'" . ($required ? ' required' : '') . ">";
    echo "<option value=''>Seleccionar...</option>";
    
    while ($row = mysqli_fetch_assoc($resultado)) {
        $selected = ($row['id_tipo_gasto'] == $id_tipo_gasto) ? 'selected' : '';
        echo "<option value='{$row['id_tipo_gasto']}' {$selected}>" . htmlspecialchars($row['nombre_tipo_gasto']) . "</option>";
    }
    
    echo "</select>";
    
    mysqli_close($conexion);
}

/**
 * Función para generar select de formas de pago
 * @param int $id_forma_de_pago ID de la forma de pago seleccionada (opcional)
 * @param string $name Nombre del select
 * @param string $id ID del select
 * @param bool $required Si es requerido
 */
function generarSelectFormasPago($id_forma_de_pago = 0, $name = 'id_forma_de_pago', $id = 'id_forma_de_pago', $required = false) {
    $conexion = conectar_bd();
    
    if (!$conexion) {
        echo "<select class='form-select select2' id='{$id}' name='{$name}'" . ($required ? ' required' : '') . ">";
        echo "<option value=''>Error de conexión</option>";
        echo "</select>";
        return;
    }
    
    $query = "SELECT id_forma_de_pago, nombre_forma_de_pago FROM formas_de_pago ORDER BY nombre_forma_de_pago ASC";
    $resultado = mysqli_query($conexion, $query);
    
    if (!$resultado) {
        echo "<select class='form-select select2' id='{$id}' name='{$name}'" . ($required ? ' required' : '') . ">";
        echo "<option value=''>Error en consulta</option>";
        echo "</select>";
        mysqli_close($conexion);
        return;
    }
    
    echo "<select class='form-select select2' id='{$id}' name='{$name}'" . ($required ? ' required' : '') . ">";
    echo "<option value=''>Seleccionar...</option>";
    
    while ($row = mysqli_fetch_assoc($resultado)) {
        $selected = ($row['id_forma_de_pago'] == $id_forma_de_pago) ? 'selected' : '';
        echo "<option value='{$row['id_forma_de_pago']}' {$selected}>" . htmlspecialchars($row['nombre_forma_de_pago']) . "</option>";
    }
    
    echo "</select>";
    
    mysqli_close($conexion);
}

/**
 * Función para obtener el nombre del impuesto por ID
 * @param int $id_impuesto ID del impuesto
 * @return string|false Nombre del impuesto o false si no existe
 */
function obtener_nombre_impuesto($id_impuesto) {
    $conexion = conectar_bd();
    
    if (!$conexion) {
        return false;
    }
    
    $query = "SELECT nombre_impuesto FROM impuestos WHERE id_impuesto = ?";
    $stmt = mysqli_prepare($conexion, $query);
    
    if (!$stmt) {
        mysqli_close($conexion);
        return false;
    }
    
    mysqli_stmt_bind_param($stmt, 'i', $id_impuesto);
    mysqli_stmt_execute($stmt);
    $resultado = mysqli_stmt_get_result($stmt);
    
    if ($resultado && mysqli_num_rows($resultado) > 0) {
        $row = mysqli_fetch_assoc($resultado);
        $nombre = $row['nombre_impuesto'];
        mysqli_stmt_close($stmt);
        mysqli_close($conexion);
        return $nombre;
    }
    
    mysqli_stmt_close($stmt);
    mysqli_close($conexion);
    return false;
}

/**
 * Función para obtener el valor del impuesto por ID
 * @param int $id_impuesto ID del impuesto
 * @return float|false Valor del impuesto o false si no existe
 */
function obtener_valor_impuesto($id_impuesto) {
    $conexion = conectar_bd();
    
    if (!$conexion) {
        return false;
    }
    
    $query = "SELECT valor_impuesto FROM impuestos WHERE id_impuesto = ?";
    $stmt = mysqli_prepare($conexion, $query);
    
    if (!$stmt) {
        mysqli_close($conexion);
        return false;
    }
    
    mysqli_stmt_bind_param($stmt, 'i', $id_impuesto);
    mysqli_stmt_execute($stmt);
    $resultado = mysqli_stmt_get_result($stmt);
    
    if ($resultado && mysqli_num_rows($resultado) > 0) {
        $row = mysqli_fetch_assoc($resultado);
        $valor = $row['valor_impuesto'];
        mysqli_stmt_close($stmt);
        mysqli_close($conexion);
        return $valor;
    }
    
    mysqli_stmt_close($stmt);
    mysqli_close($conexion);
    return false;
}

/**
 * Función para obtener el nombre de la nacionalidad por ID
 * @param int $id ID de la nacionalidad
 * @return string|false Nombre de la nacionalidad o false si no existe
 */
function obtener_nombre_nacionalidad($id) {
    $conexion = conectar_bd();
    
    if (!$conexion) {
        return false;
    }
    
    $query = "SELECT nombre_nacionalidad FROM nacionalidades WHERE id = ?";
    $stmt = mysqli_prepare($conexion, $query);
    
    if (!$stmt) {
        mysqli_close($conexion);
        return false;
    }
    
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $resultado = mysqli_stmt_get_result($stmt);
    
    if ($resultado && mysqli_num_rows($resultado) > 0) {
        $row = mysqli_fetch_assoc($resultado);
        $nombre = $row['nombre_nacionalidad'];
        mysqli_stmt_close($stmt);
        mysqli_close($conexion);
        return $nombre;
    }
    
    mysqli_stmt_close($stmt);
    mysqli_close($conexion);
    return false;
}

/**
 * Función para generar select de tipos de identificación
 * @param string $tipo_seleccionado Tipo de identificación seleccionado (opcional)
 * @param string $name Nombre del select
 * @param string $id ID del select
 * @param bool $required Si es requerido
 * @param string $formato Formato de valores (mayusculas o minusculas)
 * @param int $id_cliente ID del cliente (opcional)
 */
function generarSelectTipoIdentificacion($tipo_identificacion = '', $app_country_id, $required = false, $select_id = 'tipo_identificacion') {
    $conexion = conectar_bd();
    $select_id = preg_replace('/[^a-zA-Z0-9_-]/', '', (string) $select_id);
    if ($select_id === '') {
        $select_id = 'tipo_identificacion';
    }
    $attr_required = $required ? ' required' : '';
    
    if (!$conexion) {
        echo "<select class='form-select select2' id='" . htmlspecialchars($select_id, ENT_QUOTES, 'UTF-8') . "' name='tipo_identificacion'" . $attr_required . ">";
        echo "<option value=''>Error de conexión</option>";
        echo "</select>";
        return;
    }
    
    $query = "SELECT id_tipo_identificacion, nombre_identificacion, texto_identificacion FROM tipo_identificacion WHERE country_id = ? AND state_tipo_identificacion = 'true' ORDER BY nombre_identificacion ASC";
    $stmt = mysqli_prepare($conexion, $query);
    mysqli_stmt_bind_param($stmt, 'i', $app_country_id);
    mysqli_stmt_execute($stmt);
    $resultado = mysqli_stmt_get_result($stmt);
    
    if (!$resultado) {
        echo "<select class='form-select select2' id='" . htmlspecialchars($select_id, ENT_QUOTES, 'UTF-8') . "' name='tipo_identificacion'" . $attr_required . ">";
        echo "<option value=''>Error en consulta</option>";
        echo "</select>";
        mysqli_close($conexion);
        return;
    }

    echo "<select class='form-select select2' id='" . htmlspecialchars($select_id, ENT_QUOTES, 'UTF-8') . "' name='tipo_identificacion'" . $attr_required . ">";
    echo "<option value=''>Seleccionar...</option>";
    
    while ($row = mysqli_fetch_assoc($resultado)) {
        $selected = ($row['id_tipo_identificacion'] == $tipo_identificacion) ? 'selected' : '';
        echo "<option value='{$row['id_tipo_identificacion']}' {$selected}>" . htmlspecialchars($row['texto_identificacion']) . "</option>";
    }
    
    echo "</select>";
    
    mysqli_close($conexion);
}

/**
 * Listar provincias de España en un select (misma estructura que generarSelectTipoIdentificacion)
 *
 * @param string|int $id_provincia Valor seleccionado (id_province)
 * @param string|int $app_country_id ID del país de la app (id_rel_country en provincias)
 * @param bool $required Si el campo es obligatorio
 */
function listarProvinciasSpain($id_provincia = '', $app_country_id = '', $required = false) {
    $conexion = conectar_bd();

    if (!$conexion) {
        echo "<select class='form-select select2' id='c_provincia' name='c_provincia'" . ($required ? ' required' : '') . ">";
        echo "<option value=''>Error de conexión</option>";
        echo "</select>";
        return;
    }

    $countryId = (int)$app_country_id;
    $query = "SELECT id_province, nombreProvince FROM provincias WHERE id_rel_country = ? ORDER BY nombreProvince ASC";
    $stmt = mysqli_prepare($conexion, $query);

    if (!$stmt) {
        echo "<select class='form-select select2' id='c_provincia' name='c_provincia'" . ($required ? ' required' : '') . ">";
        echo "<option value=''>Error en consulta</option>";
        echo "</select>";
        mysqli_close($conexion);
        return;
    }

    mysqli_stmt_bind_param($stmt, 'i', $countryId);
    mysqli_stmt_execute($stmt);
    $resultado = mysqli_stmt_get_result($stmt);

    if (!$resultado) {
        echo "<select class='form-select select2' id='c_provincia' name='c_provincia'" . ($required ? ' required' : '') . ">";
        echo "<option value=''>Error en consulta</option>";
        echo "</select>";
        mysqli_stmt_close($stmt);
        mysqli_close($conexion);
        return;
    }

    echo "<select class='form-select select2' id='c_provincia' name='c_provincia'" . ($required ? ' required' : '') . ">";
    echo "<option value=''>Seleccionar...</option>";

    while ($row = mysqli_fetch_assoc($resultado)) {
        $selected = ((string)$row['id_province'] === (string)$id_provincia) ? 'selected' : '';
        echo "<option value='{$row['id_province']}' {$selected}>" . htmlspecialchars($row['nombreProvince']) . "</option>";
    }

    echo "</select>";

    mysqli_stmt_close($stmt);
    mysqli_close($conexion);
}

/**
 * Función para verificar que la app esté activa en config_app
 * Si no existe o no está activa, redirige al login con error de suscripción
 * También obtiene el idioma y país de la app
 */
function verificar_app_activa() {
    // Obtener APP_ID de la configuración
    $app_id = defined('APP_ID') ? APP_ID : null;
    
    if (!$app_id) {
        // Si no hay APP_ID definido, no hacer nada
        return;
    }
    
    $conexion = conectar_bd();
    
    if (!$conexion) {
        // Si no hay conexión, no hacer nada (evitar errores en cascada)
        return;
    }
    
    // Verificar si la app existe y está activa, obtener también idioma, país y nombre
    $query = "SELECT state_app, rel_lang_app, rel_country_app, name_app FROM config_app WHERE id_config_app = ?";
    $stmt = mysqli_prepare($conexion, $query);
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'i', $app_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($result && mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_assoc($result);
            
            // Si la app no está activa, hacer logout y redirigir
            if ($row['state_app'] !== 'active') {
                // Destruir sesión
                session_destroy();
                
                // Redirigir al login con error de suscripción
                header('Location: login.php?subscriptionerror=1');
                exit;
            }
            
            // Guardar idioma, país y nombre en la sesión
            $_SESSION['app_lang_id'] = $row['rel_lang_app'];
            $_SESSION['app_country_id'] = $row['rel_country_app'];
            $_SESSION['app_name'] = $row['name_app'];
            
            // Obtener y guardar el código de idioma y charset
            $lang_data = obtener_codigo_idioma($row['rel_lang_app']);
            $_SESSION['app_cod_LP'] = $lang_data['cod_LP'];
            $_SESSION['app_charset_html'] = $lang_data['charset_html'];
            
        } else {
            // Si la app no existe, hacer logout y redirigir
            session_destroy();
            
            // Redirigir al login con error de suscripción
            header('Location: login.php?subscriptionerror=1');
            exit;
        }
        
        mysqli_stmt_close($stmt);
    }
    
    mysqli_close($conexion);
}

/**
 * Función para obtener el código de idioma (cod_LP) de la tabla Languages
 */
function obtener_codigo_idioma($lang_id) {
    if (!$lang_id) {
        return '';
    }
    
    $conexion = conectar_bd();
    
    if (!$conexion) {
        return '';
    }
    
    $query = "SELECT cod_LP, charset_html FROM Languages WHERE id_lang = ?";
    $stmt = mysqli_prepare($conexion, $query);
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'i', $lang_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($result && mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_assoc($result);
            $cod_LP = $row['cod_LP'];
            $charset_html = $row['charset_html'];
        } else {
            $cod_LP = '';
            $charset_html = '';
        }
        
        mysqli_stmt_close($stmt);
    } else {
        $cod_LP = '';
        $charset_html = '';
    }
    
    mysqli_close($conexion);
    
    return array(
        'cod_LP' => $cod_LP,
        'charset_html' => $charset_html
    );
}

/**
 * Función para obtener traducciones
 */
function t($entry_translate, $lang_id = null) {
    static $cache = array();
    
    // Si no se especifica idioma, usar el de la sesión
    if ($lang_id === null) {
        $lang_id = isset($_SESSION['app_lang_id']) ? $_SESSION['app_lang_id'] : 0;
    }
    
    // Si no hay idioma, devolver la clave original
    if (!$lang_id) {
        return $entry_translate;
    }
    
    // Crear clave de caché
    $cache_key = $lang_id . '_' . $entry_translate;
    
    // Si ya está en caché, devolverlo
    if (isset($cache[$cache_key])) {
        return $cache[$cache_key];
    }
    
    // Obtener traducción de la base de datos
    $conexion = conectar_bd();
    if (!$conexion) {
        return $entry_translate;
    }
    
    $query = "SELECT exit_translate FROM Translations WHERE entry_translate = ? AND idLang = ?";
    $stmt = mysqli_prepare($conexion, $query);
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'si', $entry_translate, $lang_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($result && mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_assoc($result);
            $translation = $row['exit_translate'];
        } else {
            $translation = $entry_translate; // Si no se encuentra, devolver la clave original
        }
        
        mysqli_stmt_close($stmt);
    } else {
        $translation = $entry_translate;
    }
    
    mysqli_close($conexion);
    
    // Guardar en caché
    $cache[$cache_key] = $translation;
    
    return $translation;
}

/**
 * Función para obtener los datos de la app desde config_app
 */
function obtener_datos_app($app_id) {
    if (!$app_id) {
        return false;
    }
    
    $conexion = conectar_bd();
    
    if (!$conexion) {
        return false;
    }
    
    $query = "SELECT rel_lang_app, rel_country_app, name_app FROM config_app WHERE id_config_app = ? AND state_app = 'active'";
    $stmt = mysqli_prepare($conexion, $query);
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'i', $app_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($result && mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);
            mysqli_close($conexion);
            return $row;
        }
        
        mysqli_stmt_close($stmt);
    }
    
    mysqli_close($conexion);
    return false;
}

/**
 * Genera un select de países
 * @param string $name Nombre del select
 * @param string $selectedValue Valor seleccionado
 * @param string $placeholder Texto del placeholder
 * @return string HTML del select
 */
function generarSelectPaises($name, $selectedValue = '', $placeholder = 'Selecciona país') {
   
    $conexion = conectar_bd();
    
    if (!$conexion) {
        return false;
    }
    
    $html = '<select name="' . $name . '" id="' . $name . '" class="form-select select2" required>';
    $html .= '<option value="">' . $placeholder . '</option>';
    
    try {
        $sql = "SELECT id_country, name_spanish FROM countrys WHERE state_country = 'true' ORDER BY name_spanish ASC";
        $result = $conexion->query($sql);
        
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $selected = ($selectedValue == $row['id_country']) ? 'selected' : '';
                $html .= '<option value="' . $row['id_country'] . '" ' . $selected . '>' . htmlspecialchars($row['name_spanish']) . '</option>';
            }
        }
    } catch (Exception $e) {
        error_log("Error al cargar países: " . $e->getMessage());
    }
    
    $html .= '</select>';
    return $html;
}

/**
 * Función para obtener el texto de un país por ID
 */
function obtenerTextoPais($conexion, $id) {
    $query = "SELECT name_spanish FROM countrys WHERE id_country = ?";
    $stmt = mysqli_prepare($conexion, $query);
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    return $row ? $row['name_spanish'] : '';
}
function obtenerTextoPaisNormalconexion( $id) {
    $conexion = conectar_bd();
    $query = "SELECT name_spanish FROM countrys WHERE id_country = ?";
    $stmt = mysqli_prepare($conexion, $query);
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    return $row ? $row['name_spanish'] : '';
}

/**
 * Función para obtener el texto de una provincia por ID
 */
function obtenerTextoProvincia($conexion, $id) {
    $query = "SELECT nombreProvince FROM provincias WHERE id_province = ?";
    $stmt = mysqli_prepare($conexion, $query);
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    return $row ? $row['nombreProvince'] : '';
}

/**
 * Obtiene region_itp_id de una empresa (0 si la columna no existe o no hay valor).
 */
function obtener_region_itp_id_empresa($conexion, $id_empresa) {
    $id_empresa = (int) $id_empresa;
    if ($id_empresa <= 0) {
        return 0;
    }

    $query = 'SELECT region_itp_id FROM empresas WHERE id_empresa = ? LIMIT 1';
    $stmt = mysqli_prepare($conexion, $query);
    if (!$stmt) {
        return 0;
    }

    mysqli_stmt_bind_param($stmt, 'i', $id_empresa);
    if (!mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        return 0;
    }

    $result = mysqli_stmt_get_result($stmt);
    $row = $result ? mysqli_fetch_assoc($result) : null;
    mysqli_stmt_close($stmt);

    return isset($row['region_itp_id']) ? (int) $row['region_itp_id'] : 0;
}

/**
 * Carga una empresa por ID de forma compatible (prepared statement o query directa).
 */
function cargar_empresa_por_id($conexion, $id_empresa) {
    $id_empresa = (int) $id_empresa;
    if ($id_empresa <= 0) {
        return null;
    }

    $query = 'SELECT * FROM empresas WHERE id_empresa = ? LIMIT 1';
    $stmt = mysqli_prepare($conexion, $query);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'i', $id_empresa);
        if (mysqli_stmt_execute($stmt) && function_exists('mysqli_stmt_get_result')) {
            $result = mysqli_stmt_get_result($stmt);
            if ($result && mysqli_num_rows($result) > 0) {
                $row = mysqli_fetch_assoc($result);
                mysqli_stmt_close($stmt);
                return $row;
            }
        }
        mysqli_stmt_close($stmt);
    }

    $result = mysqli_query($conexion, 'SELECT * FROM empresas WHERE id_empresa = ' . $id_empresa . ' LIMIT 1');
    if ($result && mysqli_num_rows($result) > 0) {
        return mysqli_fetch_assoc($result);
    }

    return null;
}

/**
 * Devuelve el texto formateado de región ITP para una empresa.
 */
function cargar_texto_region_itp_empresa($conexion, $id_empresa, $id_provincia = 0) {
    if (!function_exists('obtener_region_itp_datos')) {
        return '';
    }

    $id_provincia = (int) $id_provincia;
    if ($id_provincia <= 0) {
        return '';
    }

    $datos = obtener_region_itp_datos($conexion, $id_provincia, '');
    return !empty($datos['texto']) ? $datos['texto'] : '';
}

/**
 * Obtiene región ITP por id provincia ITP o nombre de provincia.
 * Falla en silencio si las tablas no existen.
 */
function obtener_region_itp_datos($conexion, $region_itp_id = 0, $provincia = '') {
    $vacio = array(
        'nombre_region_itp' => '',
        'impuesto_itp_compras' => null,
        'texto' => '',
    );

    $region_itp_id = (int) $region_itp_id;
    $provincia = trim((string) $provincia);

    if ($region_itp_id <= 0 && $provincia === '') {
        return $vacio;
    }

    if ($region_itp_id > 0) {
        $query = "
            SELECT
                ri.nombreRegion AS nombre_region_itp,
                ri.impuesto_itp_compras
            FROM provincias_system_itp psi
            LEFT JOIN region_itp ri ON ri.id_region = psi.id_rel_region_itp
            WHERE psi.id_province_itp = ?
            LIMIT 1
        ";
    } else {
        $query = "
            SELECT
                ri.nombreRegion AS nombre_region_itp,
                ri.impuesto_itp_compras
            FROM provincias_system_itp psi
            LEFT JOIN region_itp ri ON ri.id_region = psi.id_rel_region_itp
            WHERE TRIM(psi.nombreProvince_itp) = ?
            LIMIT 1
        ";
    }

    $stmt = mysqli_prepare($conexion, $query);
    if (!$stmt) {
        return $vacio;
    }

    if ($region_itp_id > 0) {
        mysqli_stmt_bind_param($stmt, 'i', $region_itp_id);
    } else {
        mysqli_stmt_bind_param($stmt, 's', $provincia);
    }

    if (!mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        return $vacio;
    }

    $result = mysqli_stmt_get_result($stmt);
    $row = $result ? mysqli_fetch_assoc($result) : null;
    mysqli_stmt_close($stmt);

    if (!$row || empty($row['nombre_region_itp']) || $row['impuesto_itp_compras'] === null || $row['impuesto_itp_compras'] === '') {
        return $vacio;
    }

    $nombre = $row['nombre_region_itp'];
    $impuesto = (int) $row['impuesto_itp_compras'];

    return array(
        'nombre_region_itp' => $nombre,
        'impuesto_itp_compras' => $impuesto,
        'texto' => $nombre . ' - ' . $impuesto . '%',
    );
}

function obtenerTextoProvinciaNormalconexion($id) {
    $conexion = conectar_bd();
    $query = "SELECT nombreProvince FROM provincias WHERE id_province = ?";
    $stmt = mysqli_prepare($conexion, $query);
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    return $row ? $row['nombreProvince'] : '';
}

/**
 * Función para obtener el texto de una población por ID
 */
function obtenerTextoPoblacion($conexion, $id) {
    $query = "SELECT poblacion FROM poblacion WHERE idpoblacion = ?";
    $stmt = mysqli_prepare($conexion, $query);
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    return $row ? $row['poblacion'] : '';
}
function obtenerTextoPoblacionNormalconexion($id) {
    $conexion = conectar_bd();
    $query = "SELECT poblacion FROM poblacion WHERE idpoblacion = ?";
    $stmt = mysqli_prepare($conexion, $query);
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    return $row ? $row['poblacion'] : '';
}
/**
 * Función para obtener el texto de una nacionalidad por ID
 */
function obtenerTextoNacionalidad($conexion, $id) {
    $query = "SELECT nombre_nacionalidad FROM nacionalidades WHERE id = ?";
    $stmt = mysqli_prepare($conexion, $query);
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    return $row ? $row['nombre_nacionalidad'] : '';
}

function obtenerTextoNacionalidadNormalconexion($id) {
    $conexion = conectar_bd();
    $query = "SELECT nombre_nacionalidad FROM nacionalidades WHERE id = ?";
    $stmt = mysqli_prepare($conexion, $query);
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    return $row ? $row['nombre_nacionalidad'] : '';
}

/**
 * Función para obtener el texto de una tipo de identificación por ID
 */
function obtenerTextoTipoIdentificacion($conexion, $id) {
    $query = "SELECT nombre_identificacion FROM tipo_identificacion WHERE id_tipo_identificacion = ?";
    $stmt = mysqli_prepare($conexion, $query);
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    return $row ? $row['nombre_identificacion'] : '';
}

/**
 * Obtener países
 */
function obtenerPaises($search = '', $page = 1) {
    $conexion = conectar_bd();
    
    $limit = 20;
    $offset = ($page - 1) * $limit;
    
    $sql = "SELECT id_country as id, name_spanish as text FROM countrys";
    
    if (!empty($search)) {
        $sql .= " WHERE name_spanish LIKE '%" . mysqli_real_escape_string($conexion, $search) . "%'";
    }
    
    $sql .= " ORDER BY name_spanish LIMIT $limit OFFSET $offset";
    
    $result = mysqli_query($conexion, $sql);
    
    if (!$result) {
        mysqli_close($conexion);
        throw new Exception('Error en consulta: ' . mysqli_error($conexion));
    }
    
    $paises = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $paises[] = $row;
    }
    
    // Obtener total para paginación
    $sqlTotal = "SELECT COUNT(*) as total FROM countrys";
    if (!empty($search)) {
        $sqlTotal .= " WHERE name_spanish LIKE '%" . mysqli_real_escape_string($conexion, $search) . "%'";
    }
    
    $resultTotal = mysqli_query($conexion, $sqlTotal);
    $total = mysqli_fetch_assoc($resultTotal)['total'];
    
    mysqli_close($conexion);
    
    return [
        'results' => $paises,
        'pagination' => [
            'more' => ($offset + $limit) < $total
        ]
    ];
}
function obtenerCodigoPais($id_pais)
{
    $conexion = conectar_bd();
    $query = "SELECT countrys.iso AS CODIGOPAIS FROM nacionalidades LEFT JOIN countrys ON countrys.id_country = nacionalidades.country_id_rel WHERE nacionalidades.id = ? ";
    $stmt = mysqli_prepare($conexion, $query);
    mysqli_stmt_bind_param($stmt, 'i', $id_pais);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    return $row ? $row['CODIGOPAIS'] : '';
 }
/**
 * Expande términos de búsqueda de provincia con nombres alternativos (p. ej. Biz/Bizka/Bizcaya → Vizcaya/Bizkaia).
 */
function expandirTerminosBusquedaProvincia($search) {
    $search = trim((string) $search);
    if ($search === '') {
        return [];
    }

    $terminos = [$search];
    $searchLower = mb_strtolower($search, 'UTF-8');
    $longitudMinimaPrefijo = 3;

    $gruposAliasProvincia = [
        [
            'patrones' => ['biz', 'bizcaya', 'bizka', 'bizkaia', 'bizkaya', 'viz', 'vizcaya', 'vizkaya'],
            'nombres' => ['Vizcaya', 'Bizkaia'],
        ],
    ];

    foreach ($gruposAliasProvincia as $grupo) {
        $coincide = false;

        foreach ($grupo['patrones'] as $patron) {
            if (mb_strpos($searchLower, $patron, 0, 'UTF-8') !== false) {
                $coincide = true;
                break;
            }

            if (
                mb_strlen($searchLower, 'UTF-8') >= $longitudMinimaPrefijo
                && mb_strpos($patron, $searchLower, 0, 'UTF-8') === 0
            ) {
                $coincide = true;
                break;
            }
        }

        if ($coincide) {
            $terminos = array_merge($terminos, $grupo['nombres']);
        }
    }

    return array_values(array_unique(array_filter($terminos)));
}

/**
 * Obtener provincias
 */
function obtenerProvincias($search = '', $page = 1, $idpais = '') {
    $conexion = conectar_bd();
    
    $limit = 20;
    $offset = ($page - 1) * $limit;
    
    $sql = "SELECT p.id_province as id, p.nombreProvince as text FROM provincias p";
    $whereConditions = [];
    
    if (!empty($idpais)) {
        $whereConditions[] = "p.id_rel_country = '" . mysqli_real_escape_string($conexion, $idpais) . "'";
    }
    
    if (!empty($search)) {
        $likeConditions = [];
        foreach (expandirTerminosBusquedaProvincia($search) as $termino) {
            $terminoEsc = mysqli_real_escape_string($conexion, $termino);
            $likeConditions[] = "LOWER(p.nombreProvince) LIKE LOWER('%" . $terminoEsc . "%')";
        }
        if (!empty($likeConditions)) {
            $whereConditions[] = '(' . implode(' OR ', $likeConditions) . ')';
        }
    }
    
    if (!empty($whereConditions)) {
        $sql .= " WHERE " . implode(" AND ", $whereConditions);
    }
    
    $sql .= " ORDER BY p.nombreProvince LIMIT $limit OFFSET $offset";
    
    $result = mysqli_query($conexion, $sql);
    
    if (!$result) {
        mysqli_close($conexion);
        throw new Exception('Error en consulta provincias: ' . mysqli_error($conexion));
    }
    
    $provincias = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $provincias[] = $row;
    }
    
    // Obtener total para paginación
    $sqlTotal = "SELECT COUNT(*) as total FROM provincias p";
    if (!empty($whereConditions)) {
        $sqlTotal .= " WHERE " . implode(" AND ", $whereConditions);
    }
    
    $resultTotal = mysqli_query($conexion, $sqlTotal);
    $total = mysqli_fetch_assoc($resultTotal)['total'];
    
    mysqli_close($conexion);
    
    return [
        'results' => $provincias,
        'pagination' => [
            'more' => ($offset + $limit) < $total
        ]
    ];
}

/**
 * Obtener poblaciones
 */
function obtenerPoblaciones($search = '', $page = 1, $idprovincia = '') {
    $conexion = conectar_bd();
    
    $limit = 20;
    $offset = ($page - 1) * $limit;
    
    $sql = "SELECT p.idpoblacion as id, p.poblacion as text FROM poblacion p";
    $whereConditions = [];
    
    if (!empty($idprovincia)) {
        $whereConditions[] = "p.idprovincia = '" . mysqli_real_escape_string($conexion, $idprovincia) . "'";
    }
    
    if (!empty($search)) {
        $whereConditions[] = "p.poblacion LIKE '%" . mysqli_real_escape_string($conexion, $search) . "%'";
    }
    
    if (!empty($whereConditions)) {
        $sql .= " WHERE " . implode(" AND ", $whereConditions);
    }
    
    $sql .= " ORDER BY p.poblacion LIMIT $limit OFFSET $offset";
    
    $result = mysqli_query($conexion, $sql);
    
    if (!$result) {
        mysqli_close($conexion);
        throw new Exception('Error en consulta poblaciones: ' . mysqli_error($conexion));
    }
    
    $poblaciones = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $poblaciones[] = $row;
    }
    
    // Obtener total para paginación
    $sqlTotal = "SELECT COUNT(*) as total FROM poblacion p";
    if (!empty($whereConditions)) {
        $sqlTotal .= " WHERE " . implode(" AND ", $whereConditions);
    }
    
    $resultTotal = mysqli_query($conexion, $sqlTotal);
    $total = mysqli_fetch_assoc($resultTotal)['total'];
    
    mysqli_close($conexion);
    
    return [
        'results' => $poblaciones,
        'pagination' => [
            'more' => ($offset + $limit) < $total
        ]
    ];
}

/**
 * Obtener detalle de población (código postal, provincia y país)
 */
function obtenerDetallePoblacion($idpoblacion) {
    $conexion = conectar_bd();
    
    $sql = "SELECT 
                p.idpoblacion,
                p.poblacion,
                p.postal as codigo_postal,
                p.idprovincia,
                prov.nombreProvince as provincia,
                prov.id_rel_country,
                c.name_spanish as pais
            FROM poblacion p
            LEFT JOIN provincias prov ON p.idprovincia = prov.id_province
            LEFT JOIN countrys c ON prov.id_rel_country = c.id_country
            WHERE p.idpoblacion = ?";
    
    $stmt = mysqli_prepare($conexion, $sql);
    mysqli_stmt_bind_param($stmt, 'i', $idpoblacion);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($row = mysqli_fetch_assoc($result)) {
        mysqli_close($conexion);
        return [
            'success' => true,
            'data' => $row
        ];
    } else {
        mysqli_close($conexion);
        return [
            'success' => false,
            'message' => 'Población no encontrada'
        ];
    }
}

// DIRECCIONES
function insertarDireccion($rel_id_item, $type_direccion, $direccion, $c_provincia, $c_poblacion, $c_pais, $codigo_postal, $rel_id_provincia = 0, $rel_id_pais = 0, $rel_id_poblacion = 0, $observaciones_direccion = '') {
    $conexion = conectar_bd();
    
    // Validar tipo de dirección
    $tipos_validos = ['clientes', 'proveedores', 'empresas', 'sucursales', 'usuarios', 'envios'];
    if (!in_array($type_direccion, $tipos_validos)) {
        error_log("Error insertarDireccion: Tipo de dirección inválido '$type_direccion'");
        mysqli_close($conexion);
        return false;
    }
    
    // Preparar la consulta
    $sql = "INSERT INTO direcciones (
                rel_id_item, 
                type_direccion, 
                direccion, 
                c_provincia, 
                c_poblacion, 
                c_pais, 
                codigo_postal, 
                observaciones_direccion, 
                rel_id_provincia, 
                rel_id_pais, 
                rel_id_poblacion
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = mysqli_prepare($conexion, $sql);
    
    if (!$stmt) {
        error_log("Error preparando consulta insertarDireccion: " . mysqli_error($conexion));
        mysqli_close($conexion);
        return false;
    }
    
    // Bind parameters
    mysqli_stmt_bind_param(
        $stmt, 
        "isssssssiii", 
        $rel_id_item,
        $type_direccion,
        $direccion,
        $c_provincia,
        $c_poblacion,
        $c_pais,
        $codigo_postal,
        $observaciones_direccion,
        $rel_id_provincia,
        $rel_id_pais,
        $rel_id_poblacion
    );
    
    // Ejecutar
    if (mysqli_stmt_execute($stmt)) {
        $id_insertado = mysqli_insert_id($conexion);
        mysqli_stmt_close($stmt);
        mysqli_close($conexion);
        return $id_insertado;
    } else {
        error_log("Error ejecutando insertarDireccion: " . mysqli_stmt_error($stmt));
        mysqli_stmt_close($stmt);
        mysqli_close($conexion);
        return false;
    }
}

// Variantes que reutilizan una conexión existente (evitan múltiples conectar_bd()).
function insertarDireccionConConexion($conexion, $rel_id_item, $type_direccion, $direccion, $c_provincia, $c_poblacion, $c_pais, $codigo_postal, $rel_id_provincia = 0, $rel_id_pais = 0, $rel_id_poblacion = 0, $observaciones_direccion = '') {
    // Validar tipo de dirección
    $tipos_validos = ['clientes', 'proveedores', 'empresas', 'sucursales', 'usuarios', 'envios'];
    if (!in_array($type_direccion, $tipos_validos)) {
        error_log("Error insertarDireccionConConexion: Tipo de dirección inválido '$type_direccion'");
        return false;
    }
    // $c_provincia, $c_poblacion, $c_pais,
    $sql = "INSERT INTO direcciones (
                rel_id_item, 
                type_direccion, 
                direccion, 
                c_provincia, 
                c_poblacion, 
                c_pais, 
                codigo_postal, 
                observaciones_direccion, 
                rel_id_provincia, 
                rel_id_pais, 
                rel_id_poblacion
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = mysqli_prepare($conexion, $sql);
    if (!$stmt) {
        error_log("Error preparando consulta insertarDireccionConConexion: " . mysqli_error($conexion));
        return false;
    }

    mysqli_stmt_bind_param(
        $stmt,
        "isssssssiii",
        $rel_id_item,
        $type_direccion,
        $direccion,
        $c_provincia,
        $c_poblacion,
        $c_pais,
        $codigo_postal,
        $observaciones_direccion,
        $rel_id_provincia,
        $rel_id_pais,
        $rel_id_poblacion
    );

    if (mysqli_stmt_execute($stmt)) {
        $id_insertado = mysqli_insert_id($conexion);
        mysqli_stmt_close($stmt);
        return $id_insertado;
    }

    error_log("Error ejecutando insertarDireccionConConexion: " . mysqli_stmt_error($stmt));
    mysqli_stmt_close($stmt);
    return false;
}

function actualizarDireccion($rel_id_item, $type_direccion, $direccion, $c_provincia, $c_poblacion, $c_pais, $codigo_postal = '', $rel_id_provincia = 0, $rel_id_pais = 0, $rel_id_poblacion = 0, $observaciones_direccion = '') {
    $conexion = conectar_bd();
    
    // Preparar la consulta
    $sql = "UPDATE direcciones SET
                direccion = ?, 
                c_provincia = ?, 
                c_poblacion = ?, 
                c_pais = ?, 
                codigo_postal = ?, 
                observaciones_direccion = ?, 
                rel_id_provincia = ?, 
                rel_id_pais = ?, 
                rel_id_poblacion = ?
            WHERE rel_id_item = ? AND type_direccion = ?";
    
    $stmt = mysqli_prepare($conexion, $sql);
    
    if (!$stmt) {
        error_log("Error preparando consulta actualizarDireccion: " . mysqli_error($conexion));
        mysqli_close($conexion);
        return false;
    }
    
    // Bind parameters
    mysqli_stmt_bind_param(
        $stmt, 
        "sssssssssis", 
        $direccion,
        $c_provincia,
        $c_poblacion,
        $c_pais,
        $codigo_postal,
        $observaciones_direccion,
        $rel_id_provincia,
        $rel_id_pais,
        $rel_id_poblacion,
        $rel_id_item,
        $type_direccion
    );
    
    // Ejecutar
    if (mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        mysqli_close($conexion);
        // Devolver true si se ejecutó correctamente, aunque no haya cambios
        return true;
    } else {
        error_log("Error ejecutando actualizar direccion: " . mysqli_stmt_error($stmt));
        mysqli_stmt_close($stmt);
        mysqli_close($conexion);
        return false;
    }
}

function actualizarDireccionConConexion($conexion, $rel_id_item, $type_direccion, $direccion, $c_provincia, $c_poblacion, $c_pais, $codigo_postal = '', $rel_id_provincia = 0, $rel_id_pais = 0, $rel_id_poblacion = 0, $observaciones_direccion = '') {
    $sql = "UPDATE direcciones SET
                direccion = ?, 
                c_provincia = ?, 
                c_poblacion = ?, 
                c_pais = ?, 
                codigo_postal = ?, 
                observaciones_direccion = ?, 
                rel_id_provincia = ?, 
                rel_id_pais = ?, 
                rel_id_poblacion = ?
            WHERE rel_id_item = ? AND type_direccion = ?";

    $stmt = mysqli_prepare($conexion, $sql);
    if (!$stmt) {
        error_log("Error preparando consulta actualizarDireccionConConexion: " . mysqli_error($conexion));
        return false;
    }

    mysqli_stmt_bind_param(
        $stmt,
        "sssssssssis",
        $direccion,
        $c_provincia,
        $c_poblacion,
        $c_pais,
        $codigo_postal,
        $observaciones_direccion,
        $rel_id_provincia,
        $rel_id_pais,
        $rel_id_poblacion,
        $rel_id_item,
        $type_direccion
    );

    if (mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        return true;
    }

    error_log("Error ejecutando actualizarDireccionConConexion: " . mysqli_stmt_error($stmt));
    mysqli_stmt_close($stmt);
    return false;
}

function insertarBinList($itemId, $userDelete, $descriptionBin, $rel_id_usuario_delete, $id_type_item_rel) {
    $conexion = conectar_bd();
    
    if (!$conexion) {
        error_log("Error insertarBinList: No se pudo conectar a la base de datos");
        return false;
    }
    

    $sql = "INSERT INTO BinList (
                itemId,
                userDelete, 
                descriptionBin,
                rel_id_usuario_delete,
                id_type_item_rel,
                dateDeleted, 
                hour_delete
            ) VALUES (?, ?, ?, ?, ?, CURDATE(), CURTIME())";
    
    $stmt = mysqli_prepare($conexion, $sql);
    
    if (!$stmt) {
        error_log("Error preparando consulta insertarBinList: " . mysqli_error($conexion));
        mysqli_close($conexion);
        return false;
    }
    
    mysqli_stmt_bind_param(
        $stmt, 
        "issii", 
        $itemId,
        $userDelete,
        $descriptionBin,
        $rel_id_usuario_delete,
        $id_type_item_rel
    );
    
    if (mysqli_stmt_execute($stmt)) {
        $id_insertado = mysqli_insert_id($conexion);
        mysqli_stmt_close($stmt);
        mysqli_close($conexion);
        return $id_insertado;
    } else {
        error_log("Error ejecutando insertarBinList: " . mysqli_stmt_error($stmt));
        mysqli_stmt_close($stmt);
        mysqli_close($conexion);
        return false;
    }
}

$test_function = function() {
    return "Hola Mundo";
};

function generaCategoriaspadre($tipo_joya)
    {
        $conexion = conectar_bd();
        
        // Preparar consulta con prepared statement
        $query = "SELECT id_categoria, nombre_categoria FROM categorias WHERE tipo_joya LIKE ? AND categoria_padre > 0 ORDER BY nombre_categoria ASC";
        $stmt = mysqli_prepare($conexion, $query);
        
        if (!$stmt) {
            echo "<select name='categoria_articulo' id='categoria_articulo'>";
            echo "<option value='0'>Error al cargar categorías</option>";
            echo "</select>";
            mysqli_close($conexion);
            return;
        }
        
        mysqli_stmt_bind_param($stmt, 's', $tipo_joya);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        echo "<select name='categoria_articulo' id='categoria_articulo' class='form-select'>";
        echo "<option value='0'>Seleccione categoría</option>";
        
        if ($result) {
            while($registro = mysqli_fetch_row($result)) {
                echo "<option value='" . htmlspecialchars($registro[0]) . "'>" . htmlspecialchars($registro[1]) . "</option>";
            }
        }
        
        echo "</select>";
        
        mysqli_stmt_close($stmt);
        mysqli_close($conexion);
    }

/**
 * Insertar registro de trazabilidad de artículo
 * Compatible con PHP 7.0
 * 
 * @param int $id_lote ID del lote
 * @param int $id_usuario ID del usuario que realiza la acción
 * @param string $accion_trazabilidad Tipo de acción realizada
 * @param string $comentarios Comentarios adicionales (opcional)
 * @param int $id_sucursal ID de la sucursal
 * @param int $id_articulo ID del artículo
 * @return bool True si se insertó correctamente, False en caso contrario
 * @throws Exception Si hay error en la consulta
 */
function insertar_trazabilidad_articulo($id_lote, $id_usuario, $accion_trazabilidad, $comentarios, $id_sucursal, $id_articulo, $id_envio = 0, $id_articulo_venta = 0) {
    $conexion = conectar_bd();
    
    // Fecha actual
    $fecha_accion = date('Y-m-d H:i:s');
    
    // Convertir id_envio vacío a 0
    if (empty($id_envio)) {
        $id_envio = 0;
    }
    $id_envio = (int)$id_envio;
    
    // Query de inserción
    $query = "INSERT INTO trazabilidad_articulos ( 
        id_lote,
        usuario_accion,
        fecha_accion,
        accion_trazabilidad,
        comentarios_accion,
        sucursal_accion,
        id_articulo,
        envio_id,
        id_articulo_venta
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = mysqli_prepare($conexion, $query);
    
    if (!$stmt) {
        mysqli_close($conexion);
        throw new Exception("Error al preparar consulta de trazabilidad: " . mysqli_error($conexion));
    }
    
    mysqli_stmt_bind_param(
        $stmt,
        'iisssiiii',
        $id_lote,               // i - integer
        $id_usuario,            // i - integer
        $fecha_accion,          // s - string (datetime)
        $accion_trazabilidad,   // s - string
        $comentarios,           // s - string
        $id_sucursal,           // i - integer
        $id_articulo,           // i - integer
        $id_envio,               // i - integer (puede ser NULL)
        $id_articulo_venta       // i - integer (puede ser NULL)
    );
    
    $resultado = mysqli_stmt_execute($stmt);
    
    if (!$resultado) {
        $error = mysqli_stmt_error($stmt);
        mysqli_stmt_close($stmt);
        mysqli_close($conexion);
        throw new Exception("Error al insertar trazabilidad: " . $error);
    }
    
    mysqli_stmt_close($stmt);
    mysqli_close($conexion);
    
    return true;
}

// AQUI LA FUNCION QUE COMPRUEBA SI LA SUCURSAL ENVIADA POR PARAMETRO TIENE LA FIRMA ACTIVA O INACTIVA
function verificarFirmaActiva($id_sucursal) {
    $conexion = conectar_bd();
    $query = "SELECT firma_digital FROM sucursal WHERE id_sucursal = ?";
    $stmt = mysqli_prepare($conexion, $query);
    if (!$stmt) {
        mysqli_close($conexion);
        return null;
    }
    mysqli_stmt_bind_param($stmt, 'i', $id_sucursal);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = $result ? mysqli_fetch_assoc($result) : null;
    $firma = $row['firma_digital'] ?? null;
    mysqli_stmt_close($stmt);
    mysqli_close($conexion);

    return $firma;
}

function registrar_trazabilidad_lote($id_lote, $usuario_accion, $accion_trazabilidad, $comentarios_accion, $sucursal_accion, $conexion = null) {
    $cerrar_conexion = false;

    if ($conexion === null) {
        $conexion = conectar_bd();
        $cerrar_conexion = true;
    }

    if (!$conexion) {
        error_log('Error de conexión en trazabilidad lote');
        return false;
    }

    $id_lote = (int) $id_lote;
    $usuario_accion = (int) $usuario_accion;
    $sucursal_accion = (int) $sucursal_accion;

    $query = "INSERT INTO trazabilidad_lotes 
              (id_lote, fecha_accion, usuario_accion, accion_trazabilidad, comentarios_accion, sucursal_accion) 
              VALUES (?, NOW(), ?, ?, ?, ?)";
    
    $stmt = mysqli_prepare($conexion, $query);
    
    if (!$stmt) {
        error_log('Error al preparar consulta de trazabilidad: ' . mysqli_error($conexion));
        if ($cerrar_conexion) {
            mysqli_close($conexion);
        }
        return false;
    }
    
    mysqli_stmt_bind_param($stmt, 'iissi', $id_lote, $usuario_accion, $accion_trazabilidad, $comentarios_accion, $sucursal_accion);
    
    $resultado = mysqli_stmt_execute($stmt);

    if (!$resultado) {
        error_log('Error al ejecutar trazabilidad lote: ' . mysqli_stmt_error($stmt));
    }
    
    mysqli_stmt_close($stmt);

    if ($cerrar_conexion) {
        mysqli_close($conexion);
    }
    
    return $resultado;
}

function registrar_trazabilidad_gasto($id_gasto, $usuario_accion, $accion_trazabilidad, $comentarios_accion, $sucursal_accion, $autorizado_por, $codigo_autorizacion) {
    $conexion = conectar_bd();
    
    $query = "INSERT INTO trazabilidad_gastos 
              (id_gasto, fecha_accion, usuario_accion, accion_trazabilidad, comentarios_accion, sucursal_accion, codigo_autorizacion, autorizado_por) 
              VALUES (?, NOW(), ?, ?, ?, ?, ?, ?)";
    
    $stmt = mysqli_prepare($conexion, $query);
    
    if (!$stmt) {
        error_log('Error al preparar consulta de trazabilidad de gasto: ' . mysqli_error($conexion));
        mysqli_close($conexion);
        return false;
    }
    
    mysqli_stmt_bind_param($stmt, 'iissisi', $id_gasto, $usuario_accion, $accion_trazabilidad, $comentarios_accion, $sucursal_accion, $codigo_autorizacion, $autorizado_por);
    
    $resultado = mysqli_stmt_execute($stmt);
    
    mysqli_stmt_close($stmt);
    mysqli_close($conexion);
    
    return $resultado;
}

/**
 * Copia un registro de `historico_renovaciones_{id_sucursal}` a `historico_renovaciones_gobal` con `sucursal_id`.
 *
 * @param int $id_renovacion PK en la tabla por sucursal
 * @param int $id_sucursal   Sufijo de tabla origen y valor de `sucursal_id` en destino
 * @return int id_renovaciones generado en la tabla global
 */
function insertarRenovacionEmpenoGlobal($id_renovacion, $id_sucursal)
{
    $id_renovacion = (int) $id_renovacion;
    $id_sucursal = (int) $id_sucursal;
    if ($id_renovacion <= 0 || $id_sucursal <= 0) {
        throw new Exception('insertarRenovacionEmpenoGlobal: id_renovacion e id_sucursal deben ser mayores que 0');
    }

    $conexion = conectar_bd();
    $tablaOrigen = 'historico_renovaciones_' . $id_sucursal;

    $sqlSel = "SELECT lote, fecha_renovacion, proximo_vencimiento, importe_renovacion, estado_historico,
                      fecha_insert, fecha_vencido, forma_de_pago, nombre_foto, fecha_perdido
               FROM `{$tablaOrigen}` WHERE id_renovaciones = ? LIMIT 1";
    $stmtSel = mysqli_prepare($conexion, $sqlSel);
    if (!$stmtSel) {
        $err = mysqli_error($conexion);
        mysqli_close($conexion);
        throw new Exception('insertarRenovacionEmpenoGlobal: error al preparar SELECT: ' . $err);
    }

    mysqli_stmt_bind_param($stmtSel, 'i', $id_renovacion);
    mysqli_stmt_execute($stmtSel);
    $res = mysqli_stmt_get_result($stmtSel);
    $row = $res ? mysqli_fetch_assoc($res) : null;
    mysqli_stmt_close($stmtSel);

    if (!$row) {
        mysqli_close($conexion);
        throw new Exception('insertarRenovacionEmpenoGlobal: no existe la renovación en la tabla de la sucursal');
    }

    $lote = (int) ($row['lote'] ?? 0);
    $fecha_renovacion = (string) ($row['fecha_renovacion'] ?? '');
    $proximo_vencimiento = (string) ($row['proximo_vencimiento'] ?? '');
    $importe_renovacion = (float) ($row['importe_renovacion'] ?? 0);
    $estado_historico = (string) ($row['estado_historico'] ?? '');
    $fecha_insert = (string) ($row['fecha_insert'] ?? '');
    $fecha_vencido = (string) ($row['fecha_vencido'] ?? '');
    $forma_de_pago = (string) ($row['forma_de_pago'] ?? 'pendiente');
    $nombre_foto = (string) ($row['nombre_foto'] ?? '');
    $fecha_perdido = (string) ($row['fecha_perdido'] ?? '');

    $sqlIns = 'INSERT INTO historico_renovaciones_gobal (
        id_renovaciones, lote, fecha_renovacion, proximo_vencimiento, importe_renovacion, estado_historico,
        fecha_insert, fecha_vencido, forma_de_pago, nombre_foto, fecha_perdido, sucursal_id
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE
        lote = VALUES(lote),
        fecha_renovacion = VALUES(fecha_renovacion),
        proximo_vencimiento = VALUES(proximo_vencimiento),
        importe_renovacion = VALUES(importe_renovacion),
        estado_historico = VALUES(estado_historico),
        fecha_insert = VALUES(fecha_insert),
        fecha_vencido = VALUES(fecha_vencido),
        forma_de_pago = VALUES(forma_de_pago),
        nombre_foto = VALUES(nombre_foto),
        fecha_perdido = VALUES(fecha_perdido),
        sucursal_id = VALUES(sucursal_id)';

    $stmtIns = mysqli_prepare($conexion, $sqlIns);
    if (!$stmtIns) {
        $err = mysqli_error($conexion);
        mysqli_close($conexion);
        throw new Exception('insertarRenovacionEmpenoGlobal: error al preparar INSERT: ' . $err);
    }

    mysqli_stmt_bind_param(
        $stmtIns,
        'iissdssssssi',
        $id_renovacion,
        $lote,
        $fecha_renovacion,
        $proximo_vencimiento,
        $importe_renovacion,
        $estado_historico,
        $fecha_insert,
        $fecha_vencido,
        $forma_de_pago,
        $nombre_foto,
        $fecha_perdido,
        $id_sucursal
    );

    if (!mysqli_stmt_execute($stmtIns)) {
        $errIns = mysqli_stmt_error($stmtIns);
        mysqli_stmt_close($stmtIns);
        mysqli_close($conexion);
        throw new Exception('insertarRenovacionEmpenoGlobal: error al insertar: ' . $errIns);
    }

    $idInsertado = (int) mysqli_insert_id($conexion);
    if ($idInsertado <= 0) {
        $idInsertado = $id_renovacion;
    }
    mysqli_stmt_close($stmtIns);
    mysqli_close($conexion);

    return $idInsertado;
}

/**
 * Sincroniza `historico_renovaciones_gobal` con la fila de `historico_renovaciones_{id_sucursal}`.
 * El PK `id_renovaciones` es el mismo que en la tabla por sucursal (véase `insertarRenovacionEmpenoGlobal`).
 *
 * @param int $id_renovacion id_renovaciones en la tabla `historico_renovaciones_{id_sucursal}`
 * @param int $id_sucursal   sufijo de tabla origen y filtro `sucursal_id` en global
 * @return int Filas afectadas (0 si no había fila global coincidente)
 */
function actualizarRenovacionEmpenoGlobal($id_renovacion, $id_sucursal)
{
    $id_renovacion = (int) $id_renovacion;
    $id_sucursal = (int) $id_sucursal;
    if ($id_renovacion <= 0 || $id_sucursal <= 0) {
        throw new Exception('actualizarRenovacionEmpenoGlobal: id_renovacion e id_sucursal deben ser mayores que 0');
    }

    $conexion = conectar_bd();
    $tablaOrigen = 'historico_renovaciones_' . $id_sucursal;

    $sqlSel = "SELECT lote, fecha_renovacion, proximo_vencimiento, importe_renovacion, estado_historico,
                      fecha_insert, fecha_vencido, forma_de_pago, nombre_foto, fecha_perdido
               FROM `{$tablaOrigen}` WHERE id_renovaciones = ? LIMIT 1";
    $stmtSel = mysqli_prepare($conexion, $sqlSel);
    if (!$stmtSel) {
        $err = mysqli_error($conexion);
        mysqli_close($conexion);
        throw new Exception('actualizarRenovacionEmpenoGlobal: error al preparar SELECT: ' . $err);
    }

    mysqli_stmt_bind_param($stmtSel, 'i', $id_renovacion);
    mysqli_stmt_execute($stmtSel);
    $res = mysqli_stmt_get_result($stmtSel);
    $row = $res ? mysqli_fetch_assoc($res) : null;
    mysqli_stmt_close($stmtSel);

    if (!$row) {
        mysqli_close($conexion);
        throw new Exception('actualizarRenovacionEmpenoGlobal: no existe la renovación en la tabla de la sucursal');
    }

    $lote = (int) ($row['lote'] ?? 0);
    $fecha_renovacion = (string) ($row['fecha_renovacion'] ?? '');
    $proximo_vencimiento = (string) ($row['proximo_vencimiento'] ?? '');
    $importe_renovacion = (float) ($row['importe_renovacion'] ?? 0);
    $estado_historico = (string) ($row['estado_historico'] ?? '');
    $fecha_insert = (string) ($row['fecha_insert'] ?? '');
    $fecha_vencido = (string) ($row['fecha_vencido'] ?? '');
    $forma_de_pago = (string) ($row['forma_de_pago'] ?? 'pendiente');
    $nombre_foto = (string) ($row['nombre_foto'] ?? '');
    $fecha_perdido = (string) ($row['fecha_perdido'] ?? '');

    $sqlUpd = 'UPDATE historico_renovaciones_gobal SET
        lote = ?, fecha_renovacion = ?, proximo_vencimiento = ?, importe_renovacion = ?, estado_historico = ?,
        fecha_insert = ?, fecha_vencido = ?, forma_de_pago = ?, nombre_foto = ?, fecha_perdido = ?, sucursal_id = ?
        WHERE id_renovaciones = ? AND sucursal_id = ?';

    $stmtUpd = mysqli_prepare($conexion, $sqlUpd);
    if (!$stmtUpd) {
        $err = mysqli_error($conexion);
        mysqli_close($conexion);
        throw new Exception('actualizarRenovacionEmpenoGlobal: error al preparar UPDATE: ' . $err);
    }

    mysqli_stmt_bind_param(
        $stmtUpd,
        'issdssssssiii',
        $lote,
        $fecha_renovacion,
        $proximo_vencimiento,
        $importe_renovacion,
        $estado_historico,
        $fecha_insert,
        $fecha_vencido,
        $forma_de_pago,
        $nombre_foto,
        $fecha_perdido,
        $id_sucursal,
        $id_renovacion,
        $id_sucursal
    );

    if (!mysqli_stmt_execute($stmtUpd)) {
        $errEx = mysqli_stmt_error($stmtUpd);
        mysqli_stmt_close($stmtUpd);
        mysqli_close($conexion);
        throw new Exception('actualizarRenovacionEmpenoGlobal: error al ejecutar UPDATE: ' . $errEx);
    }

    $afectadas = (int) mysqli_stmt_affected_rows($stmtUpd);
    mysqli_stmt_close($stmtUpd);
    mysqli_close($conexion);

    return $afectadas;
}

function insertAccionRenovacion($sucursalid, $idrenovaciones, $numerolote, $id_usuario, $conexion, $accion_historico){
    //INSERTO LA ACCION DEL HISTORICO DEL UPDATE
    $sql_insert = "INSERT INTO acciones_historico_renovaciones (
    sucursal,
    accion,
    origen,
    lote_accion,
    historico_id,
    fecha_accion,
    empleado
    )
    VALUES (?, ?, ?, ?, ?, NOW(), ?)";
    $stmt_insert = mysqli_prepare($conexion, $sql_insert);
    $accion_text = $accion_historico;
    $origen_text = "Central";
    mysqli_stmt_bind_param($stmt_insert, "issiii", $sucursalid, $accion_text, $origen_text, $numerolote, $idrenovaciones, $id_usuario);
    mysqli_stmt_execute($stmt_insert);
    mysqli_stmt_close($stmt_insert);
}

function insertAccionPlazoVenta($sucursalid, $idplazoventa, $id_venta, $id_usuario, $accion_historico, $origen_text){
    $conexion = conectar_bd();
    $sql_insert = "INSERT INTO acciones_historico_plazos_ventas (
    sucursal,
    accion,
    origen,
    venta_accion,
    historico_id,
    fecha_accion,
    empleado
    )
    VALUES (?, ?, ?, ?, ?, NOW(), ?)";
    $stmt_insert = mysqli_prepare($conexion, $sql_insert);
    $accion_text = $accion_historico;
    mysqli_stmt_bind_param($stmt_insert, "issiii", $sucursalid, $accion_text, $origen_text, $id_venta, $idplazoventa, $id_usuario);
    mysqli_stmt_execute($stmt_insert);
    mysqli_stmt_close($stmt_insert);
}

/**
 * @param string $v
 */
function venta_rel_tipo_iva_valido($v)
{
    $u = strtoupper(trim((string) $v));
    $ok = ['IVA', 'IPSI', 'IGIC', 'OTHER'];

    return in_array($u, $ok, true) ? $u : 'IVA';
}

/**
 * @param string $v
 */
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

/**
 * Indica si la venta a plazos ya tiene factura generada (todos los plazos pagados).
 */
function venta_plazos_tiene_factura_generada(mysqli $conexion, int $id_venta, int $id_sucursal): bool
{
    if ($id_venta <= 0 || $id_sucursal <= 0) {
        return false;
    }

    foreach (['facturas', 'facturas_simplificadas'] as $tabla) {
        $stmt = mysqli_prepare(
            $conexion,
            "SELECT id_factura FROM {$tabla} WHERE rel_id_venta = ? AND id_sucursal = ? LIMIT 1"
        );
        if (!$stmt) {
            continue;
        }
        mysqli_stmt_bind_param($stmt, 'ii', $id_venta, $id_sucursal);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $row = $res ? mysqli_fetch_assoc($res) : null;
        mysqli_stmt_close($stmt);
        if ($row && (int) ($row['id_factura'] ?? 0) > 0) {
            return true;
        }
    }

    return false;
}

/**
 * Indica si la venta del plazo ya tiene factura generada.
 */
function plazo_venta_tiene_factura_generada(mysqli $conexion, int $id_plazo): bool
{
    if ($id_plazo <= 0) {
        return false;
    }

    $stmt = mysqli_prepare(
        $conexion,
        'SELECT vp.id_venta, v.id_sucursal
         FROM ventas_plazos vp
         INNER JOIN ventas v ON v.id = vp.id_venta
         WHERE vp.id = ?
         LIMIT 1'
    );
    if (!$stmt) {
        return false;
    }
    mysqli_stmt_bind_param($stmt, 'i', $id_plazo);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $row = $res ? mysqli_fetch_assoc($res) : null;
    mysqli_stmt_close($stmt);

    return venta_plazos_tiene_factura_generada(
        $conexion,
        (int) ($row['id_venta'] ?? 0),
        (int) ($row['id_sucursal'] ?? 0)
    );
}

/**
 * Función para insertar movimientos de caja
 * @param string $grupos_caja - Grupo del movimiento
 * @param string $concepto_caja - Concepto del movimiento
 * @param float $total_entrada - Total de entrada
 * @param float $total_salida - Total de salida
 * @param int $usuario_id - ID del usuario
 * @param int $usuario_sucursal - ID de la sucursal
 * @return bool - True si se insertó correctamente, false en caso contrario
 * @throws Exception Si hay error en la consulta
 */
function insertar_movimiento_caja($grupos_caja, $concepto_caja, $total_entrada, $total_salida, $usuario_id, $usuario_sucursal) {
    $conexion = conectar_bd();
    
    $tabla_movimientos = "movimientos_de_caja_" . $usuario_sucursal;
    
    $query = "INSERT INTO " . $tabla_movimientos . " (
        grupos,
        concepto,
        entrada,
        salida,
        usuario,
        fecha_apunte,
        hora_de_apunte
    ) VALUES (?, ?, ?, ?, ?, CURDATE(), NOW())";
    
    $stmt = mysqli_prepare($conexion, $query);
    
    if (!$stmt) {
        mysqli_close($conexion);
        throw new Exception("Error al preparar consulta de movimiento de caja: " . mysqli_error($conexion));
        return false;
    }
    
    mysqli_stmt_bind_param($stmt, 'ssddi', $grupos_caja, $concepto_caja, $total_entrada, $total_salida, $usuario_id);
    
    $resultado = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    mysqli_close($conexion);
    
    if (!$resultado) {
        throw new Exception("Error al insertar movimiento de caja: " . mysqli_stmt_error($stmt));
    }
    
    return true;
}

/**
 * Función para insertar movimientos de transferencia
 * @param int $sucursal - ID de la sucursal
 * @param int|null $id_lote - ID del lote (puede ser NULL)
 * @param int|null $id_venta - ID de la venta (puede ser NULL)
 * @param string $descripcion - Descripción del movimiento
 * @param float $entrada - Total de entrada
 * @param float $salida - Total de salida
 * @param int $usuario - ID del usuario
 * @param string $grupos - Grupo del movimiento
 * @param string $fecha - Fecha del movimiento (formato: Y-m-d)
 * @return bool - True si se insertó correctamente
 * @throws Exception Si hay error en la consulta
 */
function insertar_movimiento_transferencia($sucursal, $id_lote, $id_venta, $descripcion, $entrada, $salida, $usuario, $grupos) {
    $conexion = conectar_bd();
    
    // Si id_lote, id_venta, entrada o salida vienen vacíos, establecer como 0
    $id_lote = empty($id_lote) ? 0 : (int)$id_lote;
    $id_venta = empty($id_venta) ? 0 : (int)$id_venta;
    $entrada = empty($entrada) ? 0 : (float)$entrada;
    $salida = empty($salida) ? 0 : (float)$salida;
    
    $query = "INSERT INTO movimientos_transferencia (
        sucursal,
        id_lote,
        id_venta,
        descripcion,
        entrada,
        salida,
        usuario,
        grupos,
        fecha
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
    
    $stmt = mysqli_prepare($conexion, $query);
    
    if (!$stmt) {
        mysqli_close($conexion);
        throw new Exception("Error al preparar consulta de movimiento de transferencia: " . mysqli_error($conexion));
    }
    
    mysqli_stmt_bind_param($stmt, 'iiisddis', $sucursal, $id_lote, $id_venta, $descripcion, $entrada, $salida, $usuario, $grupos);
    
    $resultado = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    mysqli_close($conexion);
    
    if (!$resultado) {
        throw new Exception("Error al insertar movimiento de transferencia");
    }
    
    return true;
}

/**
 * Función para insertar movimientos de tarjeta
 * @param int $sucursal - ID de la sucursal
 * @param int|null $id_lote - ID del lote (puede ser NULL)
 * @param string $descripcion - Descripción del movimiento
 * @param float $importe - Importe del movimiento
 * @param int $usuario - ID del usuario
 * @param string $grupos - Grupo del movimiento
 * @return bool - True si se insertó correctamente
 * @throws Exception Si hay error en la consulta
 */
function insertar_movimiento_tarjeta($sucursal, $id_lote, $id_venta, $descripcion, $importe, $usuario, $grupos, $salida = 0) {
    $conexion = conectar_bd();
    
    // Si id_lote o importe vienen vacíos, establecer como 0
    $id_lote = empty($id_lote) ? 0 : (int)$id_lote;
    $importe = empty($importe) ? 0 : (float)$importe;
    $id_venta = empty($id_venta) ? 0 : (int)$id_venta;
    $salida = empty($salida) ? 0 : (float) $salida;
    
    $query = "INSERT INTO movimientos_tarjeta (
        id_venta,
        sucursal,
        id_lote,
        descripcion,
        importe,
        usuario,
        grupos,
        salida,
        fecha
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
    
    $stmt = mysqli_prepare($conexion, $query);
    
    if (!$stmt) {
        mysqli_close($conexion);
        throw new Exception("Error al preparar consulta de movimiento de tarjeta: " . mysqli_error($conexion));
    }
    
    mysqli_stmt_bind_param($stmt, 'iiisdisd', $id_venta, $sucursal, $id_lote, $descripcion, $importe, $usuario, $grupos, $salida);
    
    $resultado = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    mysqli_close($conexion);
    
    if (!$resultado) {
        throw new Exception("Error al insertar movimiento de tarjeta");
    }
    
    return true;
}

//
function insertar_movimiento_bizum($sucursal, $id_lote, $id_venta, $descripcion, $importe, $usuario, $grupos, $salida = 0) {
    $conexion = conectar_bd();

    $salida = empty($salida) ? 0 : (float) $salida;
    
    $query = "INSERT INTO movimientos_bizum (
        sucursal,
        id_venta,
        id_lote,
        descripcion,
        importe,
        usuario,
        grupos,
        salida,
        fecha
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
    
    $stmt = mysqli_prepare($conexion, $query);
    
    if (!$stmt) {
        mysqli_close($conexion);
        throw new Exception("Error al preparar consulta de movimiento de bizum: " . mysqli_error($conexion));
    }
    
    mysqli_stmt_bind_param($stmt, 'iiisdisd', $sucursal, $id_venta, $id_lote, $descripcion, $importe, $usuario, $grupos, $salida);
    
    $resultado = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    mysqli_close($conexion);
    
    if (!$resultado) {
        throw new Exception("Error al insertar movimiento de bizum");
    }
    
    return true;
}

/**
 * Procesar y redimensionar imagen a un ancho máximo de 800px
 */
function procesarYRedimensionarImagen($ruta_temporal, $extension) {
    // Obtener información de la imagen
    $info_imagen = getimagesize($ruta_temporal);
    if (!$info_imagen) {
        return false;
    }
    
    $ancho_original = $info_imagen[0];
    $alto_original = $info_imagen[1];
    $tipo_imagen = $info_imagen[2];
    
    // Si la imagen ya es menor o igual a 800px de ancho, no redimensionar
    if ($ancho_original <= 1200) {
        return file_get_contents($ruta_temporal);
    }
    
    // Calcular nuevas dimensiones manteniendo proporción
    $ancho_nuevo = 1200;
    $alto_nuevo = round(($alto_original * $ancho_nuevo) / $ancho_original);
    
    // Crear imagen desde archivo temporal según el tipo
    $imagen_original = null;
    switch ($tipo_imagen) {
        case IMAGETYPE_JPEG:
            $imagen_original = imagecreatefromjpeg($ruta_temporal);
            break;
        case IMAGETYPE_PNG:
            $imagen_original = imagecreatefrompng($ruta_temporal);
            break;
        case IMAGETYPE_GIF:
            $imagen_original = imagecreatefromgif($ruta_temporal);
            break;
        default:
            return false;
    }
    
    if (!$imagen_original) {
        return false;
    }
    
    // Crear nueva imagen con dimensiones redimensionadas
    $imagen_nueva = imagecreatetruecolor($ancho_nuevo, $alto_nuevo);
    
    // Preservar transparencia para PNG y GIF
    if ($tipo_imagen == IMAGETYPE_PNG || $tipo_imagen == IMAGETYPE_GIF) {
        imagealphablending($imagen_nueva, false);
        imagesavealpha($imagen_nueva, true);
        $transparente = imagecolorallocatealpha($imagen_nueva, 255, 255, 255, 127);
        imagefilledrectangle($imagen_nueva, 0, 0, $ancho_nuevo, $alto_nuevo, $transparente);
    }
    
    // Redimensionar la imagen
    if (!imagecopyresampled($imagen_nueva, $imagen_original, 0, 0, 0, 0, $ancho_nuevo, $alto_nuevo, $ancho_original, $alto_original)) {
        imagedestroy($imagen_original);
        imagedestroy($imagen_nueva);
        return false;
    }
    
    // Capturar la imagen procesada en un buffer
    ob_start();
    switch ($extension) {
        case 'jpg':
        case 'jpeg':
            imagejpeg($imagen_nueva, null, 85); // Calidad 85%
            break;
        case 'png':
            imagepng($imagen_nueva, null, 6); // Compresión 6
            break;
        case 'gif':
            imagegif($imagen_nueva);
            break;
    }
    $imagen_procesada = ob_get_contents();
    ob_end_clean();
    
    // Liberar memoria
    imagedestroy($imagen_original);
    imagedestroy($imagen_nueva);
    
    return $imagen_procesada;
}

/**
 * Generar nombre único alfanumérico
 */
function generarNombreUnico($longitud = 12) {
    $caracteres = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $nombre = '';
    
    for ($i = 0; $i < $longitud; $i++) {
        $nombre .= $caracteres[rand(0, strlen($caracteres) - 1)];
    }
    
    return $nombre;
}

/**
 * Generar nombre único para PDFs, manteniendo parte del nombre original
 */
function generarNombrePDF($nombre_original, $extension) {
    $nombre_base = pathinfo($nombre_original, PATHINFO_FILENAME);
    $extension_segura = strtolower($extension);
    
    // Limpiar el nombre base: remover caracteres especiales y espacios
    $nombre_base = limpiarNombreArchivo($nombre_base);
    
    // Asegurarse de que el nombre base no exceda la longitud máxima
    $nombre_base = substr($nombre_base, 0, 30); // Máximo 30 caracteres
    
    // Generar identificador único
    $identificador = generarNombreUnico(8); // Identificador más corto
    
    // Combinar: nombre_limpio_identificador.extension
    $nombre_final = $nombre_base . '_' . $identificador . '.' . $extension_segura;
    
    // Verificar que no exista
    $ruta_completa = '../../../photos/' . $nombre_final;
    $contador = 1;
    
    while (file_exists($ruta_completa)) {
        $nombre_final = $nombre_base . '_' . $identificador . '_' . $contador . '.' . $extension_segura;
        $ruta_completa = '../../../photos/' . $nombre_final;
        $contador++;
    }
    
    return $nombre_final;
}

/**
 * Limpiar nombre de archivo para hacerlo seguro
 */
function limpiarNombreArchivo($nombre) {
    // Convertir a minúsculas
    $nombre = strtolower($nombre);
    
    // Reemplazar caracteres especiales y espacios
    $nombre = preg_replace('/[^a-z0-9_-]/', '_', $nombre);
    
    // Remover guiones bajos múltiples
    $nombre = preg_replace('/_+/', '_', $nombre);
    
    // Remover guiones bajos al inicio y final
    $nombre = trim($nombre, '_');
    
    // Si quedó vacío, usar un nombre por defecto
    if (empty($nombre)) {
        $nombre = 'documento';
    }
    
    return $nombre;
}

/**
 * Gira una imagen a horizontal si está en vertical
 * @param string $imagen_data - Datos de la imagen (string binario)
 * @return string|bool - Imagen rotada o false si hay error
 */
function giroHorizontal($imagen_data) {
    if (!$imagen_data) {
        return false;
    }
    
    // Crear imagen desde string
    $imagen = imagecreatefromstring($imagen_data);
    if (!$imagen) {
        return $imagen_data; // Si falla, devolver la imagen original
    }
    
    // Obtener dimensiones
    $ancho = imagesx($imagen);
    $alto = imagesy($imagen);
    
    // Si la imagen está en vertical (altura mayor que ancho), rotarla 90 grados
    if ($alto > $ancho) {
        $imagen_rotada = imagerotate($imagen, -90, 0);
        if (!$imagen_rotada) {
            imagedestroy($imagen);
            return $imagen_data; // Si falla la rotación, devolver original
        }
        
        // Destruir imagen original
        imagedestroy($imagen);
        $imagen = $imagen_rotada;
    }
    
    // Convertir a string binario (JPEG con calidad 90)
    ob_start();
    imagejpeg($imagen, null, 90);
    $imagen_output = ob_get_contents();
    ob_end_clean();
    
    // Destruir imagen
    imagedestroy($imagen);
    
    return $imagen_output ? $imagen_output : $imagen_data;
}

/**
 * Recorta el espacio en blanco de un SVG de firma ajustando el viewBox.
 *
 * @param string $svgXml
 * @return string
 */
function recortar_svg_firma_contrato($svgXml)
{
    if ($svgXml === '' || stripos($svgXml, '<svg') === false) {
        return $svgXml;
    }

    $puntos = array();

    if (preg_match_all('/\bd=["\']([^"\']+)["\']/i', $svgXml, $pathMatches)) {
        foreach ($pathMatches[1] as $d) {
            if (preg_match_all('/-?\d*\.?\d+(?:e[-+]?\d+)?/i', $d, $nums)) {
                $numeros = array_map('floatval', $nums[0]);
                for ($i = 0; $i < count($numeros) - 1; $i += 2) {
                    $puntos[] = array($numeros[$i], $numeros[$i + 1]);
                }
            }
        }
    }

    if (preg_match_all('/\bpoints=["\']([^"\']+)["\']/i', $svgXml, $polyMatches)) {
        foreach ($polyMatches[1] as $pts) {
            if (preg_match_all('/-?\d*\.?\d+(?:e[-+]?\d+)?/i', $pts, $nums)) {
                $numeros = array_map('floatval', $nums[0]);
                for ($i = 0; $i < count($numeros) - 1; $i += 2) {
                    $puntos[] = array($numeros[$i], $numeros[$i + 1]);
                }
            }
        }
    }

    if (empty($puntos)) {
        return $svgXml;
    }

    $minX = $minY = null;
    $maxX = $maxY = null;
    foreach ($puntos as $punto) {
        if ($minX === null || $punto[0] < $minX) {
            $minX = $punto[0];
        }
        if ($maxX === null || $punto[0] > $maxX) {
            $maxX = $punto[0];
        }
        if ($minY === null || $punto[1] < $minY) {
            $minY = $punto[1];
        }
        if ($maxY === null || $punto[1] > $maxY) {
            $maxY = $punto[1];
        }
    }

    $padding = 4;
    if (preg_match('/stroke-width=["\']([\d.]+)["\']/i', $svgXml, $strokeMatch)) {
        $padding = max($padding, (float) $strokeMatch[1]);
    }

    $minX -= $padding;
    $minY -= $padding;
    $maxX += $padding;
    $maxY += $padding;

    $anchoView = $maxX - $minX;
    $altoView = $maxY - $minY;
    if ($anchoView <= 0) {
        $anchoView = 1;
    }
    if ($altoView <= 0) {
        $altoView = 1;
    }

    $viewBox = sprintf('%F %F %F %F', $minX, $minY, $anchoView, $altoView);

    if (preg_match('/<svg([^>]*)>/i', $svgXml, $matchSvg)) {
        $attrs = $matchSvg[1];
        $attrs = preg_replace('/\s(width|height)=["\'][^"\']*["\']/i', '', $attrs);
        if (preg_match('/\bviewBox=["\'][^"\']*["\']/i', $attrs)) {
            $attrs = preg_replace('/\bviewBox=["\'][^"\']*["\']/i', 'viewBox="' . $viewBox . '"', $attrs);
        } else {
            $attrs .= ' viewBox="' . $viewBox . '"';
        }
        $attrs = trim($attrs);
        $svgXml = preg_replace('/<svg[^>]*>/i', '<svg ' . $attrs . '>', $svgXml, 1);
    }

    return $svgXml;
}

function generateSignatureContratoFinal( $encodeData, $textSignature ){
    if (!is_string($encodeData) || $encodeData === '' || strpos($encodeData, ',') === false) {
        return '';
    }

    $prefix = "signature_";
    $extencionFile = "svg";
    $file_name = uniqid($prefix) . "." . $extencionFile;
    $encodeData = substr($encodeData, strpos($encodeData, ',') + 1);
    $decodeData = base64_decode($encodeData);

    if ($decodeData === false || $decodeData === '') {
        return '';
    }

    if (stripos($decodeData, '<svg') !== false) {
        $decodeData = recortar_svg_firma_contrato($decodeData);
    }

    $handle = fopen($file_name, 'w');
    if ($handle === false) {
        return '';
    }
    fwrite($handle, $decodeData);
    fclose($handle);

    $imgStyle = 'max-width:350px; width:100%; height:auto; display:block; margin:0 auto;';
    $textBlock = trim((string) $textSignature) !== '' ? '<br>' . $textSignature : '';

    $signatureFinal = '
    <div style="width:100%; display:block; margin:0 auto; font-size:14px; font-weight:bold; text-align:center;">' . $textBlock . '
    <img src="' . $file_name . '" alt="" style="' . $imgStyle . '"/>
    </div>
    ';
    return $signatureFinal;
}

function generateSignatureContratosPdf($encodeData, $textSignature) {
    $signatureFinal = '
    <div style="width: 200px; display: block; margin: 0 auto; font-size:14px; font-weight:bold; text-align:center;">
        <br>' . htmlspecialchars($textSignature) . '
        <img src="' . htmlspecialchars($encodeData) . '" alt="Firma" style="width: 333px;"/>
    </div>
    ';
    
    return $signatureFinal;
}

function generaSello($idsucursal, $conexion){
    
    $query = "SELECT * FROM sucursal
              LEFT JOIN empresas ON sucursal.empresa_id = empresas.id_empresa
              LEFT JOIN sellos ON sucursal.sello_sucursal = sellos.id_sello
              WHERE id_sucursal = ?";
    
    $stmt = mysqli_prepare($conexion, $query);
    mysqli_stmt_bind_param($stmt, 'i', $idsucursal);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $rsItem = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    $imagen_logotipo = $rsItem['imagen_logotipo'];
    $nombre_empresa = $rsItem['nombre_empresa'];
    $cif_empresa = $rsItem['cif_empresa'];
    $direccion_tienda = $rsItem['direccion_tienda'];
    $codigo_postal_tienda = $rsItem['codigo_postal_tienda'];
    $poblacion_tienda = $rsItem['poblacion_tienda'];
    $provincia_tienda = $rsItem['provincia_tienda'];
    $sello_logotipo = $rsItem['sello_logotipo'];
    
    if( $sello_logotipo == "true" ){
        
        $sello = '
        <div id="sello" style="width: 180px; margin-top: -15px; transform: rotate(-15deg);">
            <span class="spans_sellos" id="ordago">
                <img style="width: 180px;" src="../photos/'.$imagen_logotipo.'">
            </span><br>
            <span style="display: block; font-size: 10px; line-height: 13px; min-height: auto !important;" class="spans_sellos" id="nombre_empresa">'.$nombre_empresa.'</span>
            <span style="display: block; font-size: 10px; line-height: 13px; min-height: auto !important;"  class="spans_sellos" id="cif_empresa">CIF: '.$cif_empresa.'</span>
            <span style="display: block; font-size: 10px; line-height: 13px; min-height: auto !important;"  class="spans_sellos" id="direccion_tienda">'.$direccion_tienda.'</span>
            <span style="display: block; font-size: 10px; line-height: 13px; min-height: auto !important;"  class="spans_sellos" id="datos_varios">
                <span id="codigo_postal_tienda">'.$codigo_postal_tienda.' </span>
                <span id="poblacion_tienda"> '.$poblacion_tienda.' </span>
                <span id="provincia_tienda"> ('.$provincia_tienda.')</span>
            </span>
        </div>
        ';

    }else{
        
        $sello = '
        <div id="sello" style="width: 180px;">
            <span class="spans_sellos_sinlogo" id="ordago_sinlogo"></span>
            <span class="spans_sellos_sinlogo" id="nombre_empresa">'.$nombre_empresa.'</span>
            <span class="spans_sellos_sinlogo" id="cif_empresa">CIF: '.$cif_empresa.'</span>
            <span class="spans_sellos_sinlogo" id="direccion_tienda">'.$direccion_tienda.'</span>
            <span class="spans_sellos_sinlogo" id="datos_varios">
                <span id="codigo_postal_tienda">'.$codigo_postal_tienda.' </span>
                <span id="poblacion_tienda"> '.$poblacion_tienda.' </span>
                <span id="provincia_tienda"> ('.$provincia_tienda.')</span>
            </span>
        </div>';
        
    }
    
    return $sello;

}

// AQUI CONSTRUIREMOS UNA FUNCION QUE CONTROLE SI LA CAJA ESTA CERRADA, DICHA FUNCION SE INVOCARA DESDE session.php y si es true se producira un logout.
function controlarNewSistemaCaja($sucursal_id) {
    $conexion = conectar_bd();
    $query = "SELECT * FROM sucursal WHERE id_sucursal = ? AND new_sitema_caja = 'true'";
    $stmt = mysqli_prepare($conexion, $query);
    mysqli_stmt_bind_param($stmt, 'i', $sucursal_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);    
    mysqli_close($conexion);
    if ($result && mysqli_num_rows($result) > 0) {
        return true;
    } else {
        return false;
    }
}

function controlarCajaCerrada($sucursal_id) {
    $conexion = conectar_bd();
    $query = "SELECT * FROM sucursal WHERE id_sucursal = ? AND caja_cerrada = 'true' ";
    $stmt = mysqli_prepare($conexion, $query);
    mysqli_stmt_bind_param($stmt, 'i', $sucursal_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);    
    mysqli_close($conexion);
    if ($result && mysqli_num_rows($result) > 0) {
        return true;
    } else {
        return false;
    }
}

/**
 * Comprueba si la fecha del último cierre de caja (CAJA FINAL) coincide con hoy.
 *
 * @param int|string $suc Id de sucursal (sufijo de movimientos_de_caja_{suc})
 * @return bool true si la fecha_apunte del último cierre es el día de hoy, false en caso contrario o sin registro
 */
function fechaCajaCerrada($suc) {
    $suc = (int) $suc;
    if ($suc < 1) {
        return false;
    }

    $conexion = conectar_bd();
    if (!$conexion) {
        return false;
    }

    $tabla = 'movimientos_de_caja_' . $suc;
    $query = "SELECT salida, fecha_apunte FROM {$tabla} WHERE cierre_caja = 'true' AND grupos = 'CAJA FINAL' ORDER BY id_movimientos DESC LIMIT 1";
    $result = mysqli_query($conexion, $query);

    if (!$result || mysqli_num_rows($result) === 0) {
        mysqli_close($conexion);
        return false;
    }

    $row = mysqli_fetch_assoc($result);
    mysqli_free_result($result);
    mysqli_close($conexion);

    $fecha_apunte_ultimo_cierre = isset($row['fecha_apunte']) ? $row['fecha_apunte'] : null;
    if ($fecha_apunte_ultimo_cierre === null || $fecha_apunte_ultimo_cierre === '' || $fecha_apunte_ultimo_cierre === '0000-00-00' || $fecha_apunte_ultimo_cierre === '0000-00-00 00:00:00') {
        return false;
    }

    $fechaUltimo = substr($fecha_apunte_ultimo_cierre, 0, 10);
    $hoy = date('Y-m-d');

    return $fechaUltimo === $hoy;
}

/**
 * Fecha (Y-m-d) del CAJA INICIO activo sin CAJA FINAL posterior, o null si no hay sesión pendiente.
 *
 * @param int|string $sucursal_id
 * @return string|null
 */
function obtener_fecha_apertura_caja_pendiente($sucursal_id) {
    $sucursal_id = (int) $sucursal_id;
    if ($sucursal_id < 1) {
        return null;
    }

    $conexion = conectar_bd();
    if (!$conexion) {
        return null;
    }

    $tabla = 'movimientos_de_caja_' . $sucursal_id;
    $check = mysqli_query($conexion, "SHOW TABLES LIKE '{$tabla}'");
    if (!$check || mysqli_num_rows($check) === 0) {
        mysqli_close($conexion);
        return null;
    }

    $query = "SELECT a.fecha_apunte
              FROM {$tabla} a
              WHERE a.grupos = 'CAJA INICIO'
              AND NOT EXISTS (
                  SELECT 1
                  FROM {$tabla} c
                  WHERE c.cierre_caja = 'true'
                  AND c.id_movimientos > a.id_movimientos
              )
              ORDER BY a.id_movimientos DESC
              LIMIT 1";
    $result = mysqli_query($conexion, $query);

    if (!$result || mysqli_num_rows($result) === 0) {
        mysqli_close($conexion);
        return null;
    }

    $row = mysqli_fetch_assoc($result);
    mysqli_free_result($result);
    mysqli_close($conexion);

    $fecha = isset($row['fecha_apunte']) ? substr((string) $row['fecha_apunte'], 0, 10) : null;
    if ($fecha === null || $fecha === '' || $fecha === '0000-00-00') {
        return null;
    }

    return $fecha;
}

/**
 * Indica si la sucursal debe realizar arqueo antes de continuar.
 * Solo aplica con caja abierta (caja_cerrada = false) y sesión de un día anterior sin cerrar.
 */
function requiere_arqueo_caja_sucursal($sucursal_id) {
    $sucursal_id = (int) $sucursal_id;
    if ($sucursal_id < 1 || !controlarNewSistemaCaja($sucursal_id)) {
        return false;
    }
    if (controlarCajaCerrada($sucursal_id)) {
        return false;
    }

    $fechaAperturaPendiente = obtener_fecha_apertura_caja_pendiente($sucursal_id);
    if ($fechaAperturaPendiente === null) {
        return false;
    }

    return $fechaAperturaPendiente < date('Y-m-d');
}

/**
 * Obtener el número de semana actual
 * 
 * @return int|false Número de semana actual o false si no se encuentra
 */
function obtener_numero_semana() {
    $conexion = conectar_bd();
    
    if (!$conexion) {
        return false;
    }
    
    // Establecer charset UTF-8
    mysqli_set_charset($conexion, 'utf8');
    
    $query = "SELECT numero_semana 
              FROM listado_numero_semanas 
              WHERE CURDATE() BETWEEN fecha_semana_desde AND fecha_semana_hasta 
              AND anyo_listado = YEAR(CURDATE())";
    
    $result = mysqli_query($conexion, $query);
    
    if (!$result) {
        error_log("Error en obtener_numero_semana: " . mysqli_error($conexion));
        mysqli_close($conexion);
        return false;
    }
    
    $row = mysqli_fetch_assoc($result);
    mysqli_free_result($result);
    mysqli_close($conexion);
    
    if ($row && isset($row['numero_semana'])) {
        return (int) $row['numero_semana'];
    }
    
    return false;
}

/**
 * Obtener el número de semana actual menos 3 semanas y su año
 * 
 * @return array|false Array con 'numero_semana', 'anyo_listado', 'semana_principal_desde' y 'semana_principal_hasta' de la semana - 3, o false si no se encuentra
 */
function obtener_numero_semana_menos_3() {
    $conexion = conectar_bd();
    
    if (!$conexion) {
        return false;
    }
    
    // Establecer charset UTF-8
    mysqli_set_charset($conexion, 'utf8');
    
    // Primero obtener la fecha de inicio de la semana actual
    $query_actual = "SELECT fecha_semana_desde 
                     FROM listado_numero_semanas 
                     WHERE CURDATE() BETWEEN fecha_semana_desde AND fecha_semana_hasta 
                     AND anyo_listado = YEAR(CURDATE())";
    
    $result_actual = mysqli_query($conexion, $query_actual);
    
    if (!$result_actual) {
        error_log("Error en obtener_numero_semana_menos_3 (consulta actual): " . mysqli_error($conexion));
        mysqli_close($conexion);
        return false;
    }
    
    $row_actual = mysqli_fetch_assoc($result_actual);
    mysqli_free_result($result_actual);
    
    if (!$row_actual || !isset($row_actual['fecha_semana_desde'])) {
        mysqli_close($conexion);
        return false;
    }
    
    // Calcular la fecha de 3 semanas antes
    $fecha_semana_actual = $row_actual['fecha_semana_desde'];
    $fecha_semana_menos_3 = date('Y-m-d', strtotime($fecha_semana_actual . ' -21 days'));
    
    // Buscar la semana correspondiente a esa fecha (incluyendo las fechas)
    $query_menos_3 = "SELECT numero_semana, anyo_listado, fecha_semana_desde, fecha_semana_hasta 
                      FROM listado_numero_semanas 
                      WHERE ? BETWEEN fecha_semana_desde AND fecha_semana_hasta";
    
    $stmt = mysqli_prepare($conexion, $query_menos_3);
    
    if (!$stmt) {
        error_log("Error en obtener_numero_semana_menos_3 (prepare): " . mysqli_error($conexion));
        mysqli_close($conexion);
        return false;
    }
    
    mysqli_stmt_bind_param($stmt, 's', $fecha_semana_menos_3);
    mysqli_stmt_execute($stmt);
    $result_menos_3 = mysqli_stmt_get_result($stmt);
    $row_menos_3 = mysqli_fetch_assoc($result_menos_3);
    mysqli_stmt_close($stmt);
    mysqli_close($conexion);
    
    if ($row_menos_3 && isset($row_menos_3['numero_semana']) && isset($row_menos_3['anyo_listado'])) {
        return [
            'numero_semana' => (int) $row_menos_3['numero_semana'],
            'anyo_listado' => (int) $row_menos_3['anyo_listado'],
            'semana_principal_desde' => date('d/m/Y', strtotime($row_menos_3['fecha_semana_desde'])),
            'semana_principal_hasta' => date('d/m/Y', strtotime($row_menos_3['fecha_semana_hasta']))
        ];
    }
    
    return false;
}

/**
 * Consultar si la sucursal tiene acceso a enviar SMS
 * 
 * @param int $sucursal_acceso ID de la sucursal
 * @return string|false Valor de sms_state ('true' o 'false') o false si no se encuentra
 */
function checkSMSsend($sucursal_acceso) {
    $conexion = conectar_bd();
    
    if (!$conexion) {
        return false;
    }
    
    // Asegurar que sea un entero
    $sucursal_acceso = (int) $sucursal_acceso;
    
    if ($sucursal_acceso <= 0) {
        mysqli_close($conexion);
        return false;
    }
    
    // CONTORLAR SI ESTA SUCURSAL TIENE ACCESO A CONTROL DE CODIGO CON SMS
    $sql = "SELECT sms_state FROM sucursal WHERE id_sucursal = ?";
    $stmt = mysqli_prepare($conexion, $sql);
    
    if (!$stmt) {
        error_log("Error preparar checkSMSsend: " . mysqli_error($conexion));
        mysqli_close($conexion);
        return false;
    }
    
    mysqli_stmt_bind_param($stmt, 'i', $sucursal_acceso);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (!$result) {
        error_log("Error ejecutar checkSMSsend: " . mysqli_stmt_error($stmt));
        mysqli_stmt_close($stmt);
        mysqli_close($conexion);
        return false;
    }
    
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    mysqli_close($conexion);
    
    if ($row && isset($row['sms_state'])) {
        return $row['sms_state'];
    }
    
    return false;
}

/**
 * Consultar si la sucursal tiene acceso a enviar SMS para empeños
 * 
 * @param int $sucursal_acceso ID de la sucursal
 * @return string|false Valor de sms_state_empeno ('true' o 'false') o false si no se encuentra
 */
function checkSMSsendEmpenyo($sucursal_acceso) {
    $conexion = conectar_bd();
    
    if (!$conexion) {
        return false;
    }
    
    // Asegurar que sea un entero
    $sucursal_acceso = (int) $sucursal_acceso;
    
    if ($sucursal_acceso <= 0) {
        mysqli_close($conexion);
        return false;
    }
    
    // CONTORLAR SI ESTA SUCURSAL TIENE ACCESO A CONTROL DE CODIGO CON SMS PARA EMPEÑOS
    $sql = "SELECT sms_state_empeno FROM sucursal WHERE id_sucursal = ?";
    $stmt = mysqli_prepare($conexion, $sql);
    
    if (!$stmt) {
        error_log("Error preparar checkSMSsendEmpenyo: " . mysqli_error($conexion));
        mysqli_close($conexion);
        return false;
    }
    
    mysqli_stmt_bind_param($stmt, 'i', $sucursal_acceso);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (!$result) {
        error_log("Error ejecutar checkSMSsendEmpenyo: " . mysqli_stmt_error($stmt));
        mysqli_stmt_close($stmt);
        mysqli_close($conexion);
        return false;
    }
    
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    mysqli_close($conexion);
    
    if ($row && isset($row['sms_state_empeno'])) {
        return $row['sms_state_empeno'];
    }
    
    return false;
}

/**
 * Consultar si la sucursal tiene acceso a enviar SMS según el tipo de pago
 * 
 * @param string $tipo_pago Tipo de pago ('sms_contado' o 'sms_otros_metodos_pago')
 * @param int $sucursal_acceso ID de la sucursal
 * @return string|false Valor de sms_state según tipo de pago ('true' o 'false') o false si no se encuentra
 */
function checkSMSsendTipoPago($tipo_pago, $sucursal_acceso) {
    $conexion = conectar_bd();
    
    if (!$conexion) {
        return false;
    }
    
    // Asegurar que sea un entero
    $sucursal_acceso = (int) $sucursal_acceso;
    
    if ($sucursal_acceso <= 0) {
        mysqli_close($conexion);
        return false;
    }
    
    // Validar tipo de pago
    if ($tipo_pago !== 'sms_contado' && $tipo_pago !== 'sms_otros_metodos_pago') {
        mysqli_close($conexion);
        return false;
    }
    
    // CONSULTAR SMS SEGÚN TIPO DE PAGO
    $sql = "SELECT sms_contado, sms_otros_metodos_pago FROM sucursal WHERE id_sucursal = ?";
    $stmt = mysqli_prepare($conexion, $sql);
    
    if (!$stmt) {
        error_log("Error preparar checkSMSsendTipoPago: " . mysqli_error($conexion));
        mysqli_close($conexion);
        return false;
    }
    
    mysqli_stmt_bind_param($stmt, 'i', $sucursal_acceso);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (!$result) {
        error_log("Error ejecutar checkSMSsendTipoPago: " . mysqli_stmt_error($stmt));
        mysqli_stmt_close($stmt);
        mysqli_close($conexion);
        return false;
    }
    
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    mysqli_close($conexion);
    
    if (!$row) {
        return false;
    }
    
    $active_code_autorization = false;
    
    if ($tipo_pago === 'sms_contado') {
        $active_code_autorization = $row['sms_contado'] ?? false;
    } elseif ($tipo_pago === 'sms_otros_metodos_pago') {
        $active_code_autorization = $row['sms_otros_metodos_pago'] ?? false;
    }
    
    return $active_code_autorization;
}

/**
 * Obtener el identificador de lotes_joyeria
 * 
 * @param int $id_lote ID del lote
 * @param int $id_sucursal ID de la sucursal
 * @return string|false Valor de la columna identificador o false si no se encuentra
 */
function obtenerIdLotesJoyeria($id_lote, $id_sucursal) {
    $conexion = conectar_bd();
    
    if (!$conexion) {
        return false;
    }
    
    // Asegurar que sean enteros
    $id_lote = (int) $id_lote;
    $id_sucursal = (int) $id_sucursal;
    
    if ($id_lote <= 0 || $id_sucursal <= 0) {
        mysqli_close($conexion);
        return false;
    }
    
    // Consultar identificador en lotes_joyeria
    $sql = "SELECT identificador FROM lotes_joyeria WHERE id_lote = ? AND sucursal = ?";
    $stmt = mysqli_prepare($conexion, $sql);
    
    if (!$stmt) {
        error_log("Error preparar obtenerIdLotesJoyeria: " . mysqli_error($conexion));
        mysqli_close($conexion);
        return false;
    }
    
    mysqli_stmt_bind_param($stmt, 'ii', $id_lote, $id_sucursal);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (!$result) {
        error_log("Error ejecutar obtenerIdLotesJoyeria: " . mysqli_stmt_error($stmt));
        mysqli_stmt_close($stmt);
        mysqli_close($conexion);
        return false;
    }
    
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    mysqli_close($conexion);
    
    if (!$row || !isset($row['identificador'])) {
        return false;
    }
    
    return $row['identificador'];
}

/**
 * Quita un artículo de venta de auditorías activas y ajusta totales en auditorias_tiendas.
 *
 * @throws Exception
 */
function quitar_articulo_venta_de_auditoria($conexion, $id_articulo)
{
    $id_articulo = (int) $id_articulo;
    if ($id_articulo <= 0) {
        return;
    }

    $stmt_aud = mysqli_prepare(
        $conexion,
        "SELECT id_rel_art_aud, rel_id_auditoria, estado_auditoria
         FROM rel_art_auditoria
         WHERE tipo_articulo = 'venta' AND rel_articulo = ?"
    );
    if (!$stmt_aud) {
        throw new Exception('Error al consultar auditoría: ' . mysqli_error($conexion));
    }

    mysqli_stmt_bind_param($stmt_aud, 'i', $id_articulo);
    mysqli_stmt_execute($stmt_aud);
    $result_aud = mysqli_stmt_get_result($stmt_aud);

    $filas_auditoria = [];
    while ($row_aud = mysqli_fetch_assoc($result_aud)) {
        $filas_auditoria[] = $row_aud;
    }
    mysqli_stmt_close($stmt_aud);

    foreach ($filas_auditoria as $fila_aud) {
        $id_rel_art_aud = (int) ($fila_aud['id_rel_art_aud'] ?? 0);
        $id_auditoria = (int) ($fila_aud['rel_id_auditoria'] ?? 0);
        $estado_auditoria = strtolower(trim((string) ($fila_aud['estado_auditoria'] ?? '')));

        if ($estado_auditoria === 'auditando') {
            $sql_up_aud = "UPDATE auditorias_tiendas
                           SET total_articulos_auditar = total_articulos_auditar - 1,
                               articulos_stock = articulos_stock - 1
                           WHERE id_auditoria = ?";
        } elseif ($estado_auditoria === 'existente') {
            $sql_up_aud = "UPDATE auditorias_tiendas
                           SET total_articulos_auditar = total_articulos_auditar - 1,
                               total_articulos_existentes = total_articulos_existentes - 1,
                               total_articulos_auditados = total_articulos_auditados - 1,
                               articulos_stock = articulos_stock - 1
                           WHERE id_auditoria = ?";
        } elseif ($estado_auditoria === 'faltante') {
            $sql_up_aud = "UPDATE auditorias_tiendas
                           SET total_articulos_auditar = total_articulos_auditar - 1,
                               total_articulos_faltantes = total_articulos_faltantes - 1,
                               total_articulos_auditados = total_articulos_auditados - 1,
                               articulos_stock = articulos_stock - 1
                           WHERE id_auditoria = ?";
        } else {
            $sql_up_aud = null;
        }

        if ($sql_up_aud !== null) {
            $stmt_up_aud = mysqli_prepare($conexion, $sql_up_aud);
            if (!$stmt_up_aud) {
                throw new Exception('Error al actualizar auditoría: ' . mysqli_error($conexion));
            }
            mysqli_stmt_bind_param($stmt_up_aud, 'i', $id_auditoria);
            if (!mysqli_stmt_execute($stmt_up_aud)) {
                $error = mysqli_stmt_error($stmt_up_aud);
                mysqli_stmt_close($stmt_up_aud);
                throw new Exception('Error al actualizar totales de auditoría: ' . $error);
            }
            mysqli_stmt_close($stmt_up_aud);
        }

        $stmt_del_aud = mysqli_prepare(
            $conexion,
            'DELETE FROM rel_art_auditoria WHERE id_rel_art_aud = ? LIMIT 1'
        );
        if (!$stmt_del_aud) {
            throw new Exception('Error al eliminar relación de auditoría: ' . mysqli_error($conexion));
        }
        mysqli_stmt_bind_param($stmt_del_aud, 'i', $id_rel_art_aud);
        if (!mysqli_stmt_execute($stmt_del_aud)) {
            $error = mysqli_stmt_error($stmt_del_aud);
            mysqli_stmt_close($stmt_del_aud);
            throw new Exception('Error al eliminar artículo de auditoría: ' . $error);
        }
        mysqli_stmt_close($stmt_del_aud);
    }
}

function trazabilidad_articulos_venta ( $id_venta, $usuario_accion, $accion_trazabilidad, $comentarios_accion, $sucursal_accion, $sku, $identificador_venta){
    $conexion = conectar_bd();
    
    if( $id_venta > 0){
        $query = "INSERT INTO trazabilidad_articulos_venta (
            id_venta,
            identificador_venta,
            fecha_accion,
            usuario_accion,
            accion_trazabilidad,
            comentarios_accion,
            sucursal_accion,
            id_articulo
        ) VALUES (?, ?, NOW(), ?, ?, ?, ?, ?)";
        
        $stmt = mysqli_prepare($conexion, $query);
        if (!$stmt) {
            error_log("Error al preparar consulta de trazabilidad_articulos_venta: " . mysqli_error($conexion));
            mysqli_close($conexion);
            return false;
        }
        
        mysqli_stmt_bind_param($stmt, 'issssii',
            $id_venta,              // i - integer
            $identificador_venta,    // s - string
            $usuario_accion,         // s - string
            $accion_trazabilidad,    // s - string
            $comentarios_accion,     // s - string
            $sucursal_accion,        // i - integer
            $sku             // i - integer
        );
        
        if (!mysqli_stmt_execute($stmt)) {
            error_log("Error al ejecutar consulta de trazabilidad_articulos_venta: " . mysqli_stmt_error($stmt));
            mysqli_stmt_close($stmt);
            mysqli_close($conexion);
            return false;
        }
        
        mysqli_stmt_close($stmt);
        
    }else{
        $query = "INSERT INTO trazabilidad_articulos_venta (
            fecha_accion,
            usuario_accion,
            accion_trazabilidad,
            comentarios_accion,
            sucursal_accion,
            id_articulo
        ) VALUES (NOW(), ?, ?, ?, ?, ?)";
        
        $stmt = mysqli_prepare($conexion, $query);
        if (!$stmt) {
            error_log("Error al preparar consulta de trazabilidad_articulos_venta: " . mysqli_error($conexion));
            mysqli_close($conexion);
            return false;
        }
        
        mysqli_stmt_bind_param($stmt, 'sssii',
            $usuario_accion,         // s - string
            $accion_trazabilidad,    // s - string
            $comentarios_accion,      // s - string
            $sucursal_accion,         // i - integer
            $sku              // i - integer
        );
        
        if (!mysqli_stmt_execute($stmt)) {
            error_log("Error al ejecutar consulta de trazabilidad_articulos_venta: " . mysqli_stmt_error($stmt));
            mysqli_stmt_close($stmt);
            mysqli_close($conexion);
            return false;
        }
        
        mysqli_stmt_close($stmt);
    }
    
    mysqli_close($conexion);
    return true;
}

function control_de_precios ($sku_historico, $precio_anterior, $precio_actual, $id_usuario, $id_sucursal_destino, $tipo_registro){
    $conexion = conectar_bd();
    
    $query = "INSERT INTO historico_precio_articulos_venta (
        rel_sku_historico,
        precio_anterior,
        precio_actual,
        actualizado_por,
        sucursal,
        fecha_actualizacion,
        tipo_registro
    ) VALUES (?, ?, ?, ?, ?, NOW(), ?)";
    
    $stmt = mysqli_prepare($conexion, $query);
    if (!$stmt) {
        error_log("Error al preparar consulta de control_de_precios: " . mysqli_error($conexion));
        mysqli_close($conexion);
        return false;
    }
    
    mysqli_stmt_bind_param($stmt, 'iddiis',
        $sku_historico,           // i - integer
        $precio_anterior,       // d - double
        $precio_actual,        // d - double
        $id_usuario,           // i - integer
        $id_sucursal_destino,  // i - integer
        $tipo_registro         // s - string
    );
    
    if (!mysqli_stmt_execute($stmt)) {
        error_log("Error al ejecutar consulta de control_de_precios: " . mysqli_stmt_error($stmt));
        mysqli_stmt_close($stmt);
        mysqli_close($conexion);
        return false;
    }
    
    mysqli_stmt_close($stmt);
    mysqli_close($conexion);
    return true;
}

function informeMetalFunction($id_envio){
    $conexion = conectar_bd();

    

   
    // PREPARO LAS VARIABLES PARA INSERTAR EL INFORME METAL
    $query_envio = "SELECT * FROM envios WHERE id_envio = ?";
    $stmt_envio = mysqli_prepare($conexion, $query_envio);
    if (!$stmt_envio) {
        error_log("Error al preparar consulta de envios: " . mysqli_error($conexion));
        mysqli_close($conexion);
        return false;
    }
    mysqli_stmt_bind_param($stmt_envio, 'i', $id_envio);
    mysqli_stmt_execute($stmt_envio);
    $result_envio = mysqli_stmt_get_result($stmt_envio);
    $rsItem_envio = mysqli_fetch_assoc($result_envio);
    mysqli_stmt_close($stmt_envio);
    
    if (!$rsItem_envio) {
        error_log("No se encontró el envío especificado: " . $id_envio);
        mysqli_close($conexion);
        return false;
    }
    
    $semanas_enviadas = $rsItem_envio['semanas_enviadas'] ?? '';
    $semana_numero = $rsItem_envio['semana_numero'] ?? '';
    $desde_fecha = $rsItem_envio['desde_fecha'] ?? '';
    $hasta_fecha = $rsItem_envio['hasta_fecha'] ?? '';
    $empresa_vende = $rsItem_envio['empresa_id_envio'] ?? 0;
    $empresa_informe = $rsItem_envio['empresa_id_envio'] ?? 0;
    $peso_bruto_plata_lotes = $rsItem_envio['peso_bruto_plata_lotes'] ?? 0;
    $peso_neto_plata_lotes = $rsItem_envio['peso_neto_plata_lotes'] ?? 0;
    $merma_plata = $rsItem_envio['merma_plata'] ?? 0;
    $sucursal_remitente = $rsItem_envio['sucursal_remitente'] ?? 0;
    $observaciones_envio = $rsItem_envio['observaciones_envio'] ?? '';

    if(!empty($semana_numero)){

        $sql_caca = "SELECT
        COUNT(RAES.rel_id_articulo) AS TOTALARTICULOS,
        SUM(RAES.peso_articulo) AS TOTAL_PESO_VENTA,
        SUM(AL.peso_bruto) AS TOTAL_PESO_BRUTO_VENTA,
        SUM(AL.merma) AS TOTAL_MERMA_VENTA
        FROM rel_articulos_estados AS RAES
        LEFT JOIN articulos_lotes AS AL
            ON AL.id_articulo = RAES.rel_id_articulo
            AND AL.sucursal_articulo = RAES.rel_id_sucursal
        WHERE RAES.articulo_auditado = 'true'
        AND RAES.estado_articulo = 'Stock'
        AND RAES.rel_id_sucursal = ?
        AND RAES.rel_id_envio = ?";
        $stmt_caca = mysqli_prepare($conexion, $sql_caca);
        if (!$stmt_caca) {
            error_log("Error al preparar consulta de totales venta: " . mysqli_error($conexion));
            mysqli_close($conexion);
            return false;
        }
        mysqli_stmt_bind_param($stmt_caca, 'ii', $sucursal_remitente, $id_envio);
        mysqli_stmt_execute($stmt_caca);
        $result_caca = mysqli_stmt_get_result($stmt_caca);
        $asdassda = mysqli_fetch_assoc($result_caca);
        mysqli_stmt_close($stmt_caca);
        $TOTALARTICULOS_VENTA = $asdassda['TOTALARTICULOS'] ?? 0;
        $TOTAL_PESO_VENTA = $asdassda['TOTAL_PESO_VENTA'] ?? 0;
        $TOTAL_PESO_BRUTO_VENTA = $asdassda['TOTAL_PESO_BRUTO_VENTA'] ?? 0;
        $TOTAL_MERMA_VENTA = $asdassda['TOTAL_MERMA_VENTA'] ?? 0;

        // CALCULO EL PESO DE LOS ARTICULOS DE COMPRA
        $sucursal_remitente_int = (int)$sucursal_remitente;
        $tabla_articulos = "articulos_" . $sucursal_remitente_int;
        $sql_art_COMP = "SELECT
        COUNT(RAES.rel_id_articulo) AS TOTALARTICULOS_ORO,
        SUM(RAES.peso_articulo) AS TOTAL_PESO_ORO,
        SUM(ARTSUC.merma) AS TOTAL_MERMA_ORO,
        SUM(ARTSUC.peso_bruto) AS TOTAL_PESO_BRUTO_ORO
        FROM rel_articulos_estados AS RAES
        LEFT JOIN " . $tabla_articulos . " AS ARTSUC ON ARTSUC.id_articulo = RAES.rel_id_articulo
        WHERE RAES.articulo_auditado = 'true'
        AND RAES.articulo_empeno = 'false'
        AND RAES.rel_id_sucursal = ?
        AND RAES.rel_id_envio = ?
        AND RAES.tipo_de_articulo = 'Oro'";
        $stmt_art_COMP = mysqli_prepare($conexion, $sql_art_COMP);
        if (!$stmt_art_COMP) {
            error_log("Error al preparar consulta de artículos compra: " . mysqli_error($conexion));
            mysqli_close($conexion);
            return false;
        }
        mysqli_stmt_bind_param($stmt_art_COMP, 'ii', $sucursal_remitente_int, $id_envio);
        mysqli_stmt_execute($stmt_art_COMP);
        $result_art_COMP = mysqli_stmt_get_result($stmt_art_COMP);
        $as_art_COMP = mysqli_fetch_assoc($result_art_COMP);
        mysqli_stmt_close($stmt_art_COMP);
        $TOTALARTICULOS_ORO = $as_art_COMP['TOTALARTICULOS_ORO'] ?? 0;
        $peso_neto_oro_lotes = $as_art_COMP['TOTAL_PESO_ORO'] ?? 0;
        $merma_oro = $as_art_COMP['TOTAL_MERMA_ORO'] ?? 0;
        $peso_bruto_oro_lotes = $as_art_COMP['TOTAL_PESO_BRUTO_ORO'] ?? 0;

        // CALCULO EL PESO DE LOS ARTICULOS DE EMPEÑOS
        $sql_art_emp = "SELECT
        COUNT(RAES.rel_id_articulo) AS TOTALARTICULOS_EMPENOS,
        SUM(RAES.peso_articulo) AS TOTAL_PESO_EMPENOS,
        SUM(ARTSUC.peso_bruto) AS TOTAL_PESO_BRUTO_EMPENOS
        FROM rel_articulos_estados AS RAES
        LEFT JOIN " . $tabla_articulos . " AS ARTSUC ON ARTSUC.id_articulo = RAES.rel_id_articulo
        WHERE RAES.articulo_auditado = 'true'
        AND RAES.articulo_empeno = 'true'
        AND RAES.rel_id_sucursal = ?
        AND RAES.rel_id_envio = ?
        AND RAES.tipo_de_articulo = 'Oro'";
        $stmt_art_emp = mysqli_prepare($conexion, $sql_art_emp);
        if (!$stmt_art_emp) {
            error_log("Error al preparar consulta de artículos empeños: " . mysqli_error($conexion));
            mysqli_close($conexion);
            return false;
        }
        mysqli_stmt_bind_param($stmt_art_emp, 'ii', $sucursal_remitente_int, $id_envio);
        mysqli_stmt_execute($stmt_art_emp);
        $result_art_emp = mysqli_stmt_get_result($stmt_art_emp);
        $as_art_emp = mysqli_fetch_assoc($result_art_emp);
        mysqli_stmt_close($stmt_art_emp);
        $TOTALARTICULOS_EMPENOS = $as_art_emp['TOTALARTICULOS_EMPENOS'] ?? 0;
        $TOTAL_PESO_EMPENOS = $as_art_emp['TOTAL_PESO_EMPENOS'] ?? 0;
        $TOTAL_PESO_BRUTO_EMPENOS = $as_art_emp['TOTAL_PESO_BRUTO_EMPENOS'] ?? 0;

        $usuario_informe_metal = $_SESSION['usuario_id'] ?? 0;
        $sucursal_informe = $sucursal_remitente;
        $empresa_informe_metal = $empresa_informe;
        $envio_informe_metal = $id_envio;
        $semanas_enviadas = $semanas_enviadas;
        $semana_informe_metal = $semana_numero;
        $fecha_desde = $desde_fecha;
        $fecha_hasta = $hasta_fecha;

        $peso_bruto_oro = $peso_bruto_oro_lotes;
        $peso_bruto_oro = $peso_bruto_oro - $TOTAL_PESO_BRUTO_VENTA;
        $peso_neto_oro = $peso_neto_oro_lotes;
        $peso_neto_oro = $peso_neto_oro - $TOTAL_PESO_VENTA;
        $merma_oro = $merma_oro - $TOTAL_MERMA_VENTA;
        $gramos_fundir_oro = $peso_neto_oro;

        $total_gramos_stock = $TOTAL_PESO_VENTA;
        $articulos_stock = $TOTALARTICULOS_VENTA;

        $peso_bruto_oro_empenos = $TOTAL_PESO_BRUTO_EMPENOS;
        $peso_neto_oro_empenos = $TOTAL_PESO_EMPENOS;
        $merma_oro_empenos = $TOTAL_PESO_BRUTO_EMPENOS - $TOTAL_PESO_EMPENOS;

        // SUMO LOS TOTALES DEL ORO
        $total_fundir_oro = $peso_neto_oro_empenos  + $gramos_fundir_oro;

        // LOS TOTALES DE LA PLATA
        $peso_bruto_plata = $peso_bruto_plata_lotes;
        $peso_neto_plata = $peso_neto_plata_lotes;
        $merma_plata = $merma_plata;
        $gramos_fundir_plata = $peso_neto_plata_lotes;

        $comentarios = $observaciones_envio;

        // CONSULTO SI EXISTE INFORME METAL
        $query_INFM = "SELECT id_informe FROM informe_metal WHERE envio_informe_metal = ?";
        $stmt_INFM = mysqli_prepare($conexion, $query_INFM);
        if (!$stmt_INFM) {
            error_log("Error al preparar consulta de informe metal: " . mysqli_error($conexion));
            mysqli_close($conexion);
            return false;
        }
        mysqli_stmt_bind_param($stmt_INFM, 'i', $id_envio);
        mysqli_stmt_execute($stmt_INFM);
        $result_INFM = mysqli_stmt_get_result($stmt_INFM);
        $rsItem_INFM = mysqli_fetch_assoc($result_INFM);
        mysqli_stmt_close($stmt_INFM);
        $id_informe = $rsItem_INFM['id_informe'] ?? null;

        if(empty($id_informe)){
            /*
            $descripcion_evento = 'informeMetalFunction disparada - id_envio: ' . (int) $id_envio.' - semanas_enviadas: ' . $semanas_enviadas.' - semana_numero: ' . $semana_numero.' - desde_fecha: ' . $desde_fecha.' - hasta_fecha: ' . $hasta_fecha.' - empresa_vende: ' . $empresa_vende.' - empresa_informe: ' . $empresa_informe.' - peso_bruto_plata_lotes: ' . $peso_bruto_plata_lotes.' - peso_neto_plata_lotes: ' . $peso_neto_plata_lotes.' - merma_plata: ' . $merma_plata.' - sucursal_remitente: ' . $sucursal_remitente.' - observaciones_envio: ' . $observaciones_envio.' - TOTALARTICULOS_VENTA: ' . $TOTALARTICULOS_VENTA.' - TOTAL_PESO_VENTA: ' . $TOTAL_PESO_VENTA.' - TOTALARTICULOS_ORO: ' . $TOTALARTICULOS_ORO.' - peso_neto_oro_lotes: ' . $peso_neto_oro_lotes.' - merma_oro: ' . $merma_oro.' - peso_bruto_oro_lotes: ' . $peso_bruto_oro_lotes.' - TOTALARTICULOS_EMPENOS: ' . $TOTALARTICULOS_EMPENOS.' - TOTAL_PESO_EMPENOS: ' . $TOTAL_PESO_EMPENOS.' - TOTAL_PESO_BRUTO_EMPENOS: ' . $TOTAL_PESO_BRUTO_EMPENOS.' - total_fundir_oro: ' . $total_fundir_oro.' - peso_bruto_plata: ' . $peso_bruto_plata.' - peso_neto_plata: ' . $peso_neto_plata.' - merma_plata: ' . $merma_plata.' - gramos_fundir_plata: ' . $gramos_fundir_plata.' - total_gramos_stock: ' . $total_gramos_stock.' - articulos_stock: ' . $articulos_stock.' - comentarios: ' . $comentarios.' - id_informe: ' . $id_informe.'no existe el informe metal';
        $query_test = "INSERT INTO test_tabla (fecha, descripcion_evento) VALUES (NOW(), ?)";
        $stmt_test = mysqli_prepare($conexion, $query_test);
        if ($stmt_test) {
            mysqli_stmt_bind_param($stmt_test, 's', $descripcion_evento);
            mysqli_stmt_execute($stmt_test);
            mysqli_stmt_close($stmt_test);
        }
    
        mysqli_close($conexion);
        return true;
*/
                // Query INSERT
                $query = "INSERT INTO informe_metal (
                    fecha_informe,
                    hora_informe_metal,
                    usuario_informe_metal,
                    sucursal_informe,
                    empresa_informe_metal,
                    envio_informe_metal,
                    semanas_enviadas,
                    fecha_desde_informe_metal,
                    fecha_hasta_informe_metal,
                    peso_bruto_oro,
                    peso_neto_oro,
                    merma_oro,
                    gramos_fundir_oro,
                    peso_bruto_oro_empenos,
                    peso_neto_oro_empenos,
                    merma_oro_empenos,
                    total_fundir_oro,
                    peso_bruto_plata,
                    peso_neto_plata,
                    merma_plata,
                    gramos_fundir_plata,
                    total_gramos_stock,
                    articulos_stock,
                    comentarios_fundicion_proforma,
                    semana_informe_metal
                ) VALUES (
                    NOW(),
                    NOW(),
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?
                )";

                $stmt_insert = mysqli_prepare($conexion, $query);
                if (!$stmt_insert) {
                    error_log("Error al preparar consulta INSERT informe_metal: " . mysqli_error($conexion));
                    mysqli_close($conexion);
                    return false;
                }
                
                mysqli_stmt_bind_param($stmt_insert, 'iiiisssdddddddddddddisi',
                    $usuario_informe_metal,      // i
                    $sucursal_informe,           // i
                    $empresa_informe_metal,
                    $envio_informe_metal,       // i
                    $semanas_enviadas,           // s
                    $fecha_desde,                // s
                    $fecha_hasta,                // s
                    $peso_bruto_oro,            // d
                    $peso_neto_oro,             // d
                    $merma_oro,                  // d
                    $gramos_fundir_oro,          // d
                    $peso_bruto_oro_empenos,     // d
                    $peso_neto_oro_empenos,      // d
                    $merma_oro_empenos,          // d
                    $total_fundir_oro,           // d
                    $peso_bruto_plata,           // d
                    $peso_neto_plata,            // d
                    $merma_plata,                // d
                    $gramos_fundir_plata,        // d
                    $total_gramos_stock,          // d
                    $articulos_stock,             // d
                    $comentarios,                 // s
                    $semana_informe_metal         // i
                );
                
                if (!mysqli_stmt_execute($stmt_insert)) {
                    error_log("Error al ejecutar consulta INSERT informe_metal: " . mysqli_stmt_error($stmt_insert));
                    mysqli_stmt_close($stmt_insert);
                    mysqli_close($conexion);
                    return false;
                }
                
                $id_insertado = mysqli_insert_id($conexion);
                mysqli_stmt_close($stmt_insert);


        }else{

            // SI EXISTE INFORME METAL LO ACTUALIZO
            $sql = "UPDATE informe_metal SET 
                peso_bruto_oro = ?,
                peso_neto_oro = ?,
                merma_oro = ?,
                gramos_fundir_oro = ?,
                peso_bruto_oro_empenos = ?,
                peso_neto_oro_empenos = ?,
                merma_oro_empenos = ?,
                total_fundir_oro = ?,
                peso_bruto_plata = ?,
                peso_neto_plata = ?,
                merma_plata = ?,
                gramos_fundir_plata = ?,
                total_gramos_stock = ?,
                articulos_stock = ?
            WHERE id_informe = ?";
            
            $stmt_update = mysqli_prepare($conexion, $sql);
            if (!$stmt_update) {
                error_log("Error al preparar consulta UPDATE informe_metal: " . mysqli_error($conexion));
                mysqli_close($conexion);
                return false;
            }
            
            mysqli_stmt_bind_param($stmt_update, 'dddddddddddddii',
                $peso_bruto_oro,
                $peso_neto_oro,
                $merma_oro,
                $gramos_fundir_oro,
                $peso_bruto_oro_empenos,
                $peso_neto_oro_empenos,
                $merma_oro_empenos,
                $total_fundir_oro,
                $peso_bruto_plata,
                $peso_neto_plata,
                $merma_plata,
                $gramos_fundir_plata,
                $total_gramos_stock,
                $articulos_stock,
                $id_informe                  // i
            );
            
            if (!mysqli_stmt_execute($stmt_update)) {
                error_log("Error al ejecutar consulta UPDATE informe_metal: " . mysqli_stmt_error($stmt_update));
                mysqli_stmt_close($stmt_update);
                mysqli_close($conexion);
                return false;
            }
            
            mysqli_stmt_close($stmt_update);

        } // SI EXISTE EL INFORME METAL LO ACTUALIZA
        
    } // SI HAY NUMERO DE SEMANA
    
    mysqli_close($conexion);
    return true;
    
}

// AQUI LA FUNCION QUE INSERTA INCIDENCIAS EN LA TABLA incidencias_articulos
function insertar_incidencia_articulo($id_articulo, $id_lote, $id_usuario, $accion_incidencia, $comentarios_incidencia, $sucursal_articulo, $total_diferencia_euros = '', $total_diferencia_gramos = '', $id_envio = 0, $codigo_envio = '') {
    $conexion = conectar_bd();
    if (!$conexion) {
        error_log("Error al conectar a la base de datos en insertar_incidencia_articulo");
        return false;
    }
    
    $query = "INSERT INTO incidencias_articulos (
        id_articulo,
        id_lote,
        fecha_incidencia,
        usuario_incidencia,
        accion_incidencia,
        comentarios_incidencia,
        sucursal_incidencia,
        total_diferencia_euros,
        total_diferencia_gramos,
        envio_id,
        codigo_envio_trazabilidad
    ) VALUES (?, ?, NOW(), ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = mysqli_prepare($conexion, $query);
    if (!$stmt) {
        error_log("Error al preparar la consulta en insertar_incidencia_articulo: " . mysqli_error($conexion));
        mysqli_close($conexion);
        return false;
    }
    
    mysqli_stmt_bind_param($stmt, 'iiissssssis', 
        $id_articulo, 
        $id_lote, 
        $id_usuario, 
        $accion_incidencia, 
        $comentarios_incidencia, 
        $sucursal_articulo, 
        $total_diferencia_euros, 
        $total_diferencia_gramos, 
        $id_envio, 
        $codigo_envio
    );
    
    if (!mysqli_stmt_execute($stmt)) {
        error_log("Error al ejecutar la consulta en insertar_incidencia_articulo: " . mysqli_stmt_error($stmt));
        mysqli_stmt_close($stmt);
        mysqli_close($conexion);
        return false;
    }
    
    mysqli_stmt_close($stmt);
    mysqli_close($conexion);
    return true;
}


/**
 * tipo_api de la empresa: 'test' | 'produccion' | false si no existe o es inválido.
 *
 * @param int $id_empresa
 * @return string|false
 */
function obtenerTipoApiEmpresa($id_empresa)
{
    if (!$id_empresa || $id_empresa <= 0) {
        return false;
    }

    $conexion = conectar_bd();
    $stmt = mysqli_prepare($conexion, 'SELECT tipo_api FROM empresas WHERE id_empresa = ? LIMIT 1');
    if (!$stmt) {
        mysqli_close($conexion);
        return false;
    }

    mysqli_stmt_bind_param($stmt, 'i', $id_empresa);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    mysqli_close($conexion);

    if (!$row || !isset($row['tipo_api'])) {
        return false;
    }

    $tipo_api = strtolower(trim((string) $row['tipo_api']));
    if ($tipo_api === 'test' || $tipo_api === 'produccion') {
        return $tipo_api;
    }

    return false;
}

/**
 * BD Fiskaly según empresas.tipo_api:
 * - test       → $mysqli_fiskalyapp_test (config.php)
 * - produccion → $mysqli_fiskalyapp_production (config.php, solo ENVIRONMENT=production)
 *
 * @param int $id_empresa
 * @return mysqli|false
 */
function obtenerConexionFiskalyPorEmpresa($id_empresa)
{
    global $mysqli_fiskalyapp_test;

    if (!$id_empresa || $id_empresa <= 0) {
        return false;
    }

    $tipo_api = obtenerTipoApiEmpresa($id_empresa);
    if ($tipo_api === false) {
        return false;
    }

    if ($tipo_api === 'test') {
        if (isset($mysqli_fiskalyapp_test) && $mysqli_fiskalyapp_test instanceof mysqli && !$mysqli_fiskalyapp_test->connect_errno) {
            return $mysqli_fiskalyapp_test;
        }
        return false;
    }

    if (!environment_is_production()) {
        return false;
    }

    $connProd = get_mysqli_fiskalyapp_production();
    if ($connProd instanceof mysqli) {
        return $connProd;
    }

    return false;
}

/**
 * URL API Fiskaly según empresas.tipo_api:
 * - test       → $url_api_fiskaly_test
 * - produccion → $url_api_fiskaly_production
 *
 * @param int $id_empresa
 * @return string|false
 */
function obtenerUrlApiFiskalyPorEmpresa($id_empresa)
{
    global $url_api_fiskaly_test, $url_api_fiskaly_production;

    if (!$id_empresa || $id_empresa <= 0) {
        return false;
    }

    $tipo_api = obtenerTipoApiEmpresa($id_empresa);
    if ($tipo_api === false) {
        return false;
    }

    if ($tipo_api === 'test') {
        if (isset($url_api_fiskaly_test) && !empty($url_api_fiskaly_test)) {
            return $url_api_fiskaly_test;
        }
        return false;
    }

    if (!environment_is_production()) {
        return false;
    }

    if (isset($url_api_fiskaly_production) && !empty($url_api_fiskaly_production)) {
        return $url_api_fiskaly_production;
    }

    return false;
}

// Función para validar DNI (DNI español)
function validarDNI($dni) {
    
    $dni = strtoupper($dni);
    
    // Formato: 8 números + letra
    if(!preg_match('/^[0-9]{8}[A-Z]$/', $dni)) {
        return false;
    }
    
    $numero = substr($dni, 0, 8);
    $letra = substr($dni, 8, 1);
    
    // Letras válidas para DNI
    $letras = 'TRWAGMYFPDXBNJZSQVHLCKE';
    $letra_correcta = $letras[$numero % 23];
    
    return ($letra == $letra_correcta);
}

// Función para validar NIE
function validarNIE($nie) {
    
    $nie = strtoupper($nie);
    
    // Formato: X/Y/Z + 7 números + letra
    if(!preg_match('/^[XYZ][0-9]{7}[A-Z]$/', $nie)) {
        return false;
    }
    
    // Reemplazar primera letra por número
    $nie_numero = str_replace(array('X', 'Y', 'Z'), array('0', '1', '2'), $nie);
    
    $numero = substr($nie_numero, 0, 8);
    $letra = substr($nie, 8, 1);
    
    // Letras válidas para NIE (igual que DNI)
    $letras = 'TRWAGMYFPDXBNJZSQVHLCKE';
    $letra_correcta = $letras[$numero % 23];
    
    return ($letra == $letra_correcta);
}

// Función para validar CIF
function validarCIF($cif) {
    
    $cif = strtoupper($cif);
    
    // Formato: letra + 7 números + dígito/letra
    if(!preg_match('/^[ABCDEFGHJNPQRSUVW][0-9]{7}[0-9A-J]$/', $cif)) {
        return false;
    }
    
    $letra_inicial = substr($cif, 0, 1);
    $numeros = substr($cif, 1, 7);
    $control = substr($cif, 8, 1);
    
    // Cálculo del dígito de control
    $suma = 0;
    
    for($i = 0; $i < 7; $i++) {
        $digito = $numeros[$i];
        
        if($i % 2 == 0) {
            // Posiciones impares (0,2,4,6) se multiplican por 2
            $temp = $digito * 2;
            $suma += floor($temp / 10) + ($temp % 10);
        } else {
            // Posiciones pares (1,3,5) se suman directamente
            $suma += $digito;
        }
    }
    
    $unidad = $suma % 10;
    $digito_control = ($unidad == 0) ? 0 : 10 - $unidad;
    
    // Letra de control para ciertos tipos de CIF
    $letras_control = 'JABCDEFGHI';
    $letra_control = $letras_control[$digito_control];
    
    // Algunos CIF usan letra, otros dígito
    return ($control == $digito_control || $control == $letra_control);
}

function validarPasaporteEspanol($pasaporte) {
    
    $pasaporte = strtoupper(trim($pasaporte));
    
    // Formato pasaporte español moderno (desde 2006): 3 letras + 6 números
    // Ejemplo: AAA123456
    if(preg_match('/^[A-Z]{3}[0-9]{6}$/', $pasaporte)) {
        return true;
    }
    
    // Formato pasaporte español antiguo: 1 o 2 letras + 6 o 7 números
    // Ejemplos: A1234567, AB123456
    if(preg_match('/^[A-Z]{1,2}[0-9]{6,7}$/', $pasaporte)) {
        return true;
    }
    
    return false;
}

function validarNumeroIdentificacion($identificacion, $tipo_identificacion){
    // Validar tipo de identificación y formato
    if(!empty($tipo_identificacion) && !empty($identificacion)) {
        
        switch($tipo_identificacion) {
            
            case '1':
                // Validar DNI (DNI español)
                if(!validarDNI($identificacion)) {
                    $error = "El DNI no es válido";
                }else{
                    $error = "valido";
                }
                break;
            
            case '2':
                // Validar NIE
                if(!validarNIE($identificacion)) {
                    $error = "El NIE no es válido";
                }else{
                    $error = "valido";
                }
                break;
            
            case '3':
                // Validar CIF
                if(!validarCIF($identificacion)) {
                    $error = "El CIF no es válido";
                }else{
                    $error = "valido";
                }
                break;
            
            case '4':
                 // Validar CIF
                 if(!validarPasaporteEspanol($identificacion)) {
                    $error = "El pasaporte Español no es válido";
                }else{
                    $error = "valido";
                }
                break;

            case '5':
                // Para pasaporte solo verificamos que no esté vacío y tenga al menos 5 caracteres
                if(strlen($identificacion) < 5) {
                    $error = "El pasaporte no es válido";
                }else{
                    $error = "valido";
                }
                break;
            
            default:
                $error = "Tipo de identificación no válido (debe ser: dni, nie, cif o pasaporte)";
                break;
        }
    }else{
        $error = "Falta tipo documento o indetificación!";
    }
    
    return $error;
}

/**
 * Verificar si el teléfono ya existe (solo validación de duplicados)
 * Busca si el teléfono contiene el número ingresado (no comparación exacta)
 */
function verificarTelefono($telefono, $id_cliente_excluir = null) {
    if (empty($telefono)) {
        return [
            'existe' => false,
            'message' => ''
        ];
    }

     // Conectar a la base de datos principal para consultar tipo_api
     $conexion = conectar_bd();
    
    
    // Usar LIKE para buscar si el teléfono contiene el número ingresado
    $telefonoBusqueda = '%' . $telefono . '%';
    
    // Si hay un id_cliente, excluirlo de la búsqueda (para no marcar como duplicado el mismo cliente)
    if ($id_cliente_excluir !== null && $id_cliente_excluir !== '') {
        $sql = "SELECT id_cliente, nombre, apellido FROM clientes WHERE telefono LIKE ? AND id_cliente != ?";
        $stmt = mysqli_prepare($conexion, $sql);
        $id_cliente_excluir_int = (int)$id_cliente_excluir;
        mysqli_stmt_bind_param($stmt, 'si', $telefonoBusqueda, $id_cliente_excluir_int);
    } else {
        $sql = "SELECT id_cliente, nombre, apellido FROM clientes WHERE telefono LIKE ?";
        $stmt = mysqli_prepare($conexion, $sql);
        mysqli_stmt_bind_param($stmt, 's', $telefonoBusqueda);
    }
    
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($row = mysqli_fetch_assoc($result)) {
        mysqli_stmt_close($stmt);
        return [
            'existe' => true,
            'message' => 'Este número de teléfono ya está registrado para el cliente: ' . $row['nombre'] . ' ' . $row['apellido'],
            'cliente' => $row
        ];
    } else {
        mysqli_stmt_close($stmt);
        return [
            'existe' => false,
            'message' => ''
        ];
    }
}

/**
 * Obtener información completa del cliente por id (clientes, datos_clientes y direcciones).
 *
 * @param int $id_cliente
 * @return array
 */
function obtenerInformacionCliente($id_cliente) {
    $id_cliente = (int) $id_cliente;
    if ($id_cliente <= 0) {
        return [
            'existe' => false,
            'message' => ''
        ];
    }

    $conexion = conectar_bd();
    $sql = 'SELECT * FROM clientes WHERE id_cliente = ? LIMIT 1';
    $stmt = mysqli_prepare($conexion, $sql);
    if (!$stmt) {
        return [
            'existe' => false,
            'message' => 'Error al preparar la consulta del cliente'
        ];
    }

    mysqli_stmt_bind_param($stmt, 'i', $id_cliente);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($row = mysqli_fetch_assoc($result)) {
        mysqli_stmt_close($stmt);

        $direccion = obtenerDireccionCliente($id_cliente);
        $datos_cliente = obtenerDatosCliente($id_cliente);

        return [
            'existe' => true,
            'message' => 'Cliente encontrado: ' . $row['nombre'] . ' ' . $row['apellido'],
            'cliente' => $row,
            'direccion' => $direccion,
            'datos_cliente' => $datos_cliente
        ];
    }

    mysqli_stmt_close($stmt);
    return [
        'existe' => false,
        'message' => ''
    ];
}

/**
 * Verificar si la identificación ya existe y devolver todos los datos del cliente
 */
function verificarIdentificacion($identificacion) {
    if (empty($identificacion)) {
        return [
            'existe' => false,
            'message' => ''
        ];
    }
     // Conectar a la base de datos principal para consultar tipo_api
     $conexion = conectar_bd();
    
    // Campos explícitos: asegura que `telefono` vaya siempre en el JSON (tabla clientes)
    $sql = "SELECT 
            id_cliente,
            tipo_identificacion_id,
            identificacion,
            nacionalidad_id,
            nombre,
            apellido,
            telefono,
            sucursal,
            f_alta,
            estado
        FROM clientes WHERE identificacion = ?";
    $stmt = mysqli_prepare($conexion, $sql);
    mysqli_stmt_bind_param($stmt, 's', $identificacion);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($row = mysqli_fetch_assoc($result)) {
        mysqli_stmt_close($stmt);
        
        // Obtener datos de dirección si existen
        $direccion = obtenerDireccionCliente($row['id_cliente']);

        $datos_cliente = obtenerDatosCliente($row['id_cliente']);

        $estado_cliente = $row['estado'];
        
        return [
            'existe' => true,
            'message' => 'Cliente encontrado: ' . $row['nombre'] . ' ' . $row['apellido'],
            'cliente' => $row,
            'direccion' => $direccion,
            'datos_cliente' => $datos_cliente,
            'estado_cliente' => $estado_cliente
        ];
    } else {
        mysqli_stmt_close($stmt);
        return [
            'existe' => false,
            'message' => ''
        ];
    }
}

/**
 * Obtener dirección del cliente
 */
function obtenerDireccionCliente($id_cliente) {
     // Conectar a la base de datos principal para consultar tipo_api
     $conexion = conectar_bd();
    
    $sql = "SELECT * FROM direcciones WHERE rel_id_item = ? AND type_direccion = 'clientes'";
    $stmt = mysqli_prepare($conexion, $sql);
    mysqli_stmt_bind_param($stmt, 'i', $id_cliente);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($row = mysqli_fetch_assoc($result)) {
        mysqli_stmt_close($stmt);
        return $row;
    } else {
        mysqli_stmt_close($stmt);
        return null;
    }
}

/**
 * Obtener datos adicionales del cliente desde datos_clientes
 */
function obtenerDatosCliente($id_cliente) {
     // Conectar a la base de datos principal para consultar tipo_api
     $conexion = conectar_bd();
    
    $sql = "SELECT 
                dc.id_datos_cliente,
                dc.rel_id_cliente,
                dc.f_nacimiento,
                dc.movil,
                dc.email,
                dc.observaciones,
                dc.publicidad,
                dc.sexo,
                dc.f_vencimiento,
                dc.firma_cliente
            FROM datos_clientes dc
            WHERE dc.rel_id_cliente = ?";
    
    $stmt = mysqli_prepare($conexion, $sql);
    mysqli_stmt_bind_param($stmt, 'i', $id_cliente);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($row = mysqli_fetch_assoc($result)) {
        mysqli_stmt_close($stmt);
        return $row;
    } else {
        mysqli_stmt_close($stmt);
        return null;
    }
}

/**
 * Resuelve el cliente de una venta: usa el existente o lo inserta si no existe.
 * Requiere conexión abierta (misma transacción que la venta).
 *
 * @param mysqli $conexion
 * @param array  $datos id_cliente, tipo_identificacion, identificacion, nombre, apellido, telefono, email, direccion, pais, provincia, poblacion, codigo_postal
 * @param int    $usuario_id
 * @param int    $sucursal
 * @return int id_cliente (0 si no hay datos de cliente)
 * @throws Exception
 */
function asegurarClienteParaVenta($conexion, array $datos, $usuario_id, $sucursal)
{
    $usuario_id = (int) $usuario_id;
    $sucursal = (int) $sucursal;
    $id_cliente = isset($datos['id_cliente']) ? (int) $datos['id_cliente'] : 0;

    $identificacion = isset($datos['identificacion']) ? trim((string) $datos['identificacion']) : '';
    $nombre = isset($datos['nombre']) ? trim((string) $datos['nombre']) : '';
    $apellido = isset($datos['apellido']) ? trim((string) $datos['apellido']) : '';
    $telefono = isset($datos['telefono']) ? trim((string) $datos['telefono']) : '';
    $email = isset($datos['email']) ? trim((string) $datos['email']) : '';
    $tipo_identificacion_id = isset($datos['tipo_identificacion']) ? (int) $datos['tipo_identificacion'] : 0;

    if ($id_cliente > 0) {
        $stmt = mysqli_prepare(
            $conexion,
            "SELECT id_cliente FROM clientes WHERE id_cliente = ? AND delete_state = 'false' LIMIT 1"
        );
        if (!$stmt) {
            throw new Exception('Error al comprobar el cliente: ' . mysqli_error($conexion));
        }
        mysqli_stmt_bind_param($stmt, 'i', $id_cliente);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $row = $res ? mysqli_fetch_assoc($res) : null;
        mysqli_stmt_close($stmt);
        if ($row) {
            return (int) $row['id_cliente'];
        }
        // id enviado no existe: se intenta resolver/crear por identificación más abajo
        $id_cliente = 0;
    }

    if ($identificacion === '') {
        return 0;
    }

    $stmt = mysqli_prepare(
        $conexion,
        "SELECT id_cliente FROM clientes WHERE identificacion = ? AND delete_state = 'false' LIMIT 1"
    );
    if (!$stmt) {
        throw new Exception('Error al buscar el cliente por identificación: ' . mysqli_error($conexion));
    }
    mysqli_stmt_bind_param($stmt, 's', $identificacion);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $row = $res ? mysqli_fetch_assoc($res) : null;
    mysqli_stmt_close($stmt);
    if ($row) {
        return (int) $row['id_cliente'];
    }

    if ($nombre === '' || $apellido === '' || $tipo_identificacion_id <= 0 || $telefono === '') {
        throw new Exception(
            'El cliente no existe y faltan datos mínimos para crearlo (tipo de identificación, identificación, nombre, apellidos y teléfono).'
        );
    }

    if ($sucursal <= 0) {
        throw new Exception('No se pudo determinar la sucursal para crear el cliente.');
    }

    $tipo_identificacion_texto = obtenerTextoTipoIdentificacion($conexion, $tipo_identificacion_id);
    if ($tipo_identificacion_texto === '' || $tipo_identificacion_texto === null) {
        $tipo_identificacion_texto = 'NIF';
    }
    $nacionalidad_texto = '';
    $nacionalidad_id = 0;

    $sqlCliente = "INSERT INTO clientes (
            nombre, apellido, sucursal, tipo_identificacion, tipo_identificacion_id,
            identificacion, nacionalidad, nacionalidad_id, telefono, creado_por, f_alta
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURDATE())";
    $stmtCliente = mysqli_prepare($conexion, $sqlCliente);
    if (!$stmtCliente) {
        throw new Exception('Error al preparar alta de cliente: ' . mysqli_error($conexion));
    }
    mysqli_stmt_bind_param(
        $stmtCliente,
        'ssisissisi',
        $nombre,
        $apellido,
        $sucursal,
        $tipo_identificacion_texto,
        $tipo_identificacion_id,
        $identificacion,
        $nacionalidad_texto,
        $nacionalidad_id,
        $telefono,
        $usuario_id
    );
    if (!mysqli_stmt_execute($stmtCliente)) {
        $err = mysqli_stmt_error($stmtCliente);
        mysqli_stmt_close($stmtCliente);
        throw new Exception('Error al insertar el cliente: ' . $err);
    }
    $nuevo_id = (int) mysqli_insert_id($conexion);
    mysqli_stmt_close($stmtCliente);

    if ($nuevo_id <= 0) {
        throw new Exception('No se obtuvo el id del cliente tras el INSERT.');
    }

    $observaciones = '';
    $sexo = '';
    $f_nacimiento = '0000-00-00';
    $f_vencimiento = '0000-00-00';
    $sqlDatos = "INSERT INTO datos_clientes (
            rel_id_cliente, email, observaciones, sexo, f_nacimiento, f_vencimiento
        ) VALUES (?, ?, ?, ?, ?, ?)";
    $stmtDatos = mysqli_prepare($conexion, $sqlDatos);
    if (!$stmtDatos) {
        throw new Exception('Error al preparar datos_clientes: ' . mysqli_error($conexion));
    }
    mysqli_stmt_bind_param(
        $stmtDatos,
        'isssss',
        $nuevo_id,
        $email,
        $observaciones,
        $sexo,
        $f_nacimiento,
        $f_vencimiento
    );
    if (!mysqli_stmt_execute($stmtDatos)) {
        $err = mysqli_stmt_error($stmtDatos);
        mysqli_stmt_close($stmtDatos);
        throw new Exception('Error al insertar datos_clientes: ' . $err);
    }
    mysqli_stmt_close($stmtDatos);

    $direccion = isset($datos['direccion']) ? trim((string) $datos['direccion']) : '';
    $codigo_postal = isset($datos['codigo_postal']) ? trim((string) $datos['codigo_postal']) : '';
    $pais_id = isset($datos['pais']) ? (int) $datos['pais'] : 0;
    $provincia_id = isset($datos['provincia']) ? (int) $datos['provincia'] : 0;
    $poblacion_id = isset($datos['poblacion']) ? (int) $datos['poblacion'] : 0;

    if ($direccion !== '' || $codigo_postal !== '' || $pais_id > 0 || $provincia_id > 0 || $poblacion_id > 0) {
        $nombre_pais = $pais_id > 0 ? (string) obtenerTextoPais($conexion, $pais_id) : '';
        $nombre_provincia = $provincia_id > 0 ? (string) obtenerTextoProvincia($conexion, $provincia_id) : '';
        $nombre_poblacion = $poblacion_id > 0 ? (string) obtenerTextoPoblacion($conexion, $poblacion_id) : '';
        $okDir = insertarDireccionConConexion(
            $conexion,
            $nuevo_id,
            'clientes',
            $direccion,
            $nombre_provincia,
            $nombre_poblacion,
            $nombre_pais,
            $codigo_postal,
            $provincia_id,
            $pais_id,
            $poblacion_id
        );
        if (!$okDir) {
            throw new Exception('Error al insertar la dirección del cliente.');
        }
    }

    if (function_exists('registrar_accion_usuario')) {
        global $usuario, $usuario_sucursal;
        $nombre_usuario = isset($usuario) ? (string) $usuario : '';
        $sucursal_accion = isset($usuario_sucursal) && (int) $usuario_sucursal > 0
            ? (int) $usuario_sucursal
            : $sucursal;
        $texto_action_user = $nombre_usuario . " creó el cliente Nº '" . $nuevo_id . "' (desde venta)";
        $relItemAction = isset($_SESSION['relItemAction']) ? $_SESSION['relItemAction'] : 'false';
        registrar_accion_usuario($usuario_id, '34', $texto_action_user, $sucursal_accion, $relItemAction);
        $_SESSION['relItemAction'] = 'false';
    }

    return $nuevo_id;
}

/**
 * Dirección From para mail(): usa MAIL_FROM_ADDRESS en config (dominio autorizado en SPF/DKIM).
 * No construir noreply@ desde HTTP_HOST si el host es un subdominio tipo tpv.dominio.app.
 */
function obtener_direccion_mail_from()
{

    if (defined('MAIL_FROM_ADDRESS') && MAIL_FROM_ADDRESS !== '') {
        return MAIL_FROM_ADDRESS;
    }
    $host = preg_replace('/^www\./i', '', (string)($_SERVER['HTTP_HOST'] ?? 'localhost'));
    $host = preg_replace('/^tpv\./i', '', $host);

    return 'noreply@' . $host;
}

/**
 * Nombre de remitente solo con caracteres ASCII imprimibles (RFC 5322 quoted-string).
 * Evita =?UTF-8?B?...?= y saltos de línea que sendmail/Exim y muchos hosts interpretan mal (solo queda visible «noreply»).
 */
function nombre_remitente_mail_ascii_seguro($nombreVisible)
{
    $nombreVisible = trim((string)$nombreVisible);
    $nombreVisible = preg_replace("/[\r\n\x00]/", ' ', $nombreVisible);
    if ($nombreVisible === '') {
        return '';
    }
    if (function_exists('iconv')) {
        $t = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $nombreVisible);
        if ($t !== false && $t !== '') {
            $nombreVisible = $t;
        }
    }
    $nombreVisible = preg_replace('/[^\x20-\x7E]/', '', $nombreVisible);

    return trim(preg_replace('/\s+/', ' ', $nombreVisible));
}


function formato_cabecera_mail_from_con_nombre($nombreVisible, $direccionCorreo)
{
    $direccionCorreo = trim((string)$direccionCorreo);
    if ($direccionCorreo === '') {
        return '';
    }
    $nombreVisible = trim((string)$nombreVisible);
    $nombreVisible = preg_replace("/[\r\n\x00]/", ' ', $nombreVisible);
    if ($nombreVisible === '') {
        return $direccionCorreo;
    }

    $ascii = nombre_remitente_mail_ascii_seguro($nombreVisible);
    if ($ascii !== '') {
        $q = str_replace(['\\', '"'], ['\\\\', '\\"'], $ascii);

        return '"' . $q . '" <' . $direccionCorreo . '>';
    }

    if (function_exists('mb_encode_mimeheader')) {
        $nom = mb_encode_mimeheader($nombreVisible, 'UTF-8', 'B', "\r\n");
        if ($nom === false || $nom === '') {
            $nom = '=?UTF-8?B?' . base64_encode($nombreVisible) . '?=';
        }
    } else {
        $nom = '=?UTF-8?B?' . base64_encode($nombreVisible) . '?=';
    }

    return trim($nom) . ' <' . $direccionCorreo . '>';
}

if (!function_exists('generar_clave')) {
    
    function generar_clave($longitud)
    {
        $longitud = (int)$longitud;
        if ($longitud <= 0) {
            $longitud = 5;
        }

        // Reemplazo moderno equivalente a eregi_replace (case-insensitive).
        $cadena = '/[^A-Z0-9]/i';
        $p1 = preg_replace($cadena, '', md5((string)mt_rand()));
        $p2 = preg_replace($cadena, '', md5((string)mt_rand()));
        $p3 = preg_replace($cadena, '', md5((string)mt_rand()));

        return strtoupper(substr($p1 . $p2 . $p3, 0, $longitud));
    }
}

/**
 * Crea la cabecera de control de etiquetado (tabla control_etiquetado).
 *
 * @param mysqli $conexion
 * @param int $usuario_id
 * @param int $sucursal_etiquetado
 * @param int $envio_etiquetado
 * @param string $tipo_control_etiquetado
 * @param int $total_etiquetas
 * @return int id_control_etiquetado o 0 si falla
 */
function crear_control_etiquetado($conexion, $usuario_id, $sucursal_etiquetado, $envio_etiquetado, $tipo_control_etiquetado, $total_etiquetas = 0)
{
    if (!$conexion || !($conexion instanceof mysqli)) {
        return 0;
    }

    $usuario_id = (int) $usuario_id;
    $sucursal_etiquetado = (int) $sucursal_etiquetado;
    $envio_etiquetado = (int) $envio_etiquetado;
    $tipo_control_etiquetado = (string) $tipo_control_etiquetado;
    $total_etiquetas = max(0, (int) $total_etiquetas);

    if ($usuario_id <= 0 || $sucursal_etiquetado <= 0) {
        return 0;
    }

    $sql = '
        INSERT INTO control_etiquetado (
            usuario_etiquetado,
            sucursal_etiquetado,
            envio_etiquetado,
            tipo_control_etiquetado,
            total_etiquetas,
            hora_etiquetado,
            fecha_etiquetado
        ) VALUES (?, ?, ?, ?, ?, NOW(), CURDATE())
    ';

    $stmt = mysqli_prepare($conexion, $sql);
    if (!$stmt) {
        return 0;
    }

    mysqli_stmt_bind_param($stmt, 'iiisi', $usuario_id, $sucursal_etiquetado, $envio_etiquetado, $tipo_control_etiquetado, $total_etiquetas);
    $ok = mysqli_stmt_execute($stmt);
    $id = $ok ? (int) mysqli_insert_id($conexion) : 0;
    mysqli_stmt_close($stmt);

    return $id;
}

/**
 * Cuenta las etiquetas que se imprimirán en etiquetas_articulos.php según el modo activo.
 *
 * @param mysqli $conexion
 * @param string $varios
 * @param int $por_sucursal
 * @param bool $envio_activo
 * @param int $id_envio
 * @param int $id_articulo_get
 * @return int
 */
function contar_etiquetas_articulos_a_imprimir($conexion, $varios, $por_sucursal, $envio_activo, $id_envio, $id_articulo_get)
{
    if (!$conexion || !($conexion instanceof mysqli)) {
        return 0;
    }

    if ($varios === 'true') {
        $sql = "
            SELECT COUNT(*) AS total
            FROM articulos_venta
            WHERE estado IN ('noetiquetado_c', 'noetiquetado_u')
        ";
        if ((int) $por_sucursal > 0) {
            $sql .= ' AND id_sucursal_destino = ?';
            $stmt = mysqli_prepare($conexion, $sql);
            if (!$stmt) {
                return 0;
            }
            mysqli_stmt_bind_param($stmt, 'i', $por_sucursal);
        } else {
            $stmt = mysqli_prepare($conexion, $sql);
            if (!$stmt) {
                return 0;
            }
        }
    } elseif ($envio_activo) {
        if ((int) $id_envio <= 0) {
            return 0;
        }
        $sql = "
            SELECT COUNT(DISTINCT t.id_articulo_venta) AS total
            FROM trazabilidad_articulos t
            INNER JOIN articulos_venta a ON a.id = t.id_articulo_venta
            WHERE t.envio_id = ? AND t.accion_trazabilidad = 'pasado_stock'
        ";
        $stmt = mysqli_prepare($conexion, $sql);
        if (!$stmt) {
            return 0;
        }
        mysqli_stmt_bind_param($stmt, 'i', $id_envio);
    } else {
        if ((int) $id_articulo_get <= 0) {
            return 0;
        }
        $sql = 'SELECT COUNT(*) AS total FROM articulos_venta WHERE id = ? LIMIT 1';
        $stmt = mysqli_prepare($conexion, $sql);
        if (!$stmt) {
            return 0;
        }
        mysqli_stmt_bind_param($stmt, 'i', $id_articulo_get);
    }

    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = $result ? mysqli_fetch_assoc($result) : null;
    if ($result) {
        mysqli_free_result($result);
    }
    mysqli_stmt_close($stmt);

    return (int) ($row['total'] ?? 0);
}

function insert_etiquetas_control_etiquetado($conexion, $rel_id_control_etiquetado, $rel_sku_etiquetado, $precio_sku, $descripcion_sku, $tipo_control_etiquetado = '')
{
    if (!$conexion || !($conexion instanceof mysqli)) {
        return false;
    }

    $rel_id_control_etiquetado = (int) $rel_id_control_etiquetado;
    $rel_sku_etiquetado = (int) $rel_sku_etiquetado;
    if ($rel_id_control_etiquetado <= 0 || $rel_sku_etiquetado <= 0) {
        return false;
    }

    $precio_val = is_numeric($precio_sku) ? (float) $precio_sku : 0.0;
    $descripcion_sku = (string) $descripcion_sku;
    $tipo_control_etiquetado = (string) $tipo_control_etiquetado;

    $sql = '
        INSERT INTO etiquetas_control_etiquetado (
            rel_id_control_etiquetado,
            rel_sku_etiquetado,
            precio_sku,
            descripcion_sku,
            tipo_control_etiquetado,
            fecha_control
        ) VALUES (?, ?, ?, ?, ?, NOW())
    ';

    $stmt = mysqli_prepare($conexion, $sql);
    if (!$stmt) {
        return false;
    }

    mysqli_stmt_bind_param(
        $stmt,
        'iidss',
        $rel_id_control_etiquetado,
        $rel_sku_etiquetado,
        $precio_val,
        $descripcion_sku,
        $tipo_control_etiquetado
    );

    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    return $ok;
}

/**
 * Semanas cerradas (fecha_semana_hasta &lt; hoy) desde listado_numero_semanas.
 *
 * @param int $anyo_filtro Año para filtrar el listado (0 = todos los años)
 * @return array<int,array{numero_semana:int,anyo_listado:int}>|false
 */
function obtener_listado_numero_semanas_cerradas($anyo_filtro = 0) {
    $conexion = conectar_bd();
    if (!$conexion) {
        return false;
    }

    mysqli_set_charset($conexion, 'utf8');

    $query = "SELECT numero_semana, anyo_listado
              FROM listado_numero_semanas
              WHERE fecha_semana_hasta < CURDATE()";
    $params = [];
    $types = '';

    if ((int) $anyo_filtro > 0) {
        $query .= " AND anyo_listado = ?";
        $params[] = (int) $anyo_filtro;
        $types .= 'i';
    }

    $query .= " ORDER BY id_numero_semana DESC";

    $stmt = mysqli_prepare($conexion, $query);
    if (!$stmt) {
        mysqli_close($conexion);
        return false;
    }

    if ($types !== '') {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }

    mysqli_stmt_execute($stmt);
    $resultado = mysqli_stmt_get_result($stmt);
    $semanas = [];

    if ($resultado) {
        while ($row = mysqli_fetch_assoc($resultado)) {
            $semanas[] = [
                'numero_semana' => (int) $row['numero_semana'],
                'anyo_listado' => (int) $row['anyo_listado'],
            ];
        }
        mysqli_free_result($resultado);
    }

    mysqli_stmt_close($stmt);
    mysqli_close($conexion);

    return $semanas;
}

/**
 * Imprime &lt;option&gt; de semanas cerradas (misma fuente que listado_semanas).
 *
 * @param int $numero_semana Número de semana preseleccionado (opcional)
 * @param int $anyo_filtro Año para filtrar el listado (0 = todos los años)
 * @param string $label_vacio Texto de la opción vacía
 * @param array<int,array{numero_semana:int,anyo_listado:int}>|false|null $semanas Filas precargadas o null para consultar
 */
function listado_semanas_imprimir_opciones($numero_semana = 0, $anyo_filtro = 0, $label_vacio = 'Seleccione número de semana', $semanas = null) {
    $labelVacioEsc = htmlspecialchars((string) $label_vacio, ENT_QUOTES, 'UTF-8');
    echo "<option value=''>{$labelVacioEsc}</option>";

    if ($semanas === null) {
        $semanas = obtener_listado_numero_semanas_cerradas($anyo_filtro);
    }

    if ($semanas === false) {
        echo "<option value=''>Error al cargar semanas</option>";
        return;
    }

    $numero_semana = (int) $numero_semana;

    foreach ($semanas as $row) {
        $numSem = (int) ($row['numero_semana'] ?? 0);
        $anyo = (int) ($row['anyo_listado'] ?? 0);
        if ($numSem <= 0) {
            continue;
        }
        $selected = ($numSem === $numero_semana) ? 'selected' : '';
        $texto = $numSem . ' el año ' . $anyo;
        echo "<option value='{$numSem}' {$selected} data-anyo='{$anyo}'>" . htmlspecialchars($texto, ENT_QUOTES, 'UTF-8') . "</option>";
    }
}

/**
 * Añade filtro de semana/año sobre envíos (tabla e).
 *
 * @param string $query_base
 * @param array<int|string> $params
 * @param string $types
 * @param string|int $filtro_semana
 * @param int $filtro_anyo_semana
 * @param bool $hayFiltroFecha
 * @param int $anioActual
 */
function envios_append_filtro_semana_sql(&$query_base, array &$params, &$types, $filtro_semana, $filtro_anyo_semana = 0, $hayFiltroFecha = false, $anioActual = 0) {
    if ($filtro_semana === '' || $filtro_semana === null) {
        return;
    }

    $query_base .= ' AND e.semana_numero = ?';
    $params[] = (int) $filtro_semana;
    $types .= 'i';

    $filtro_anyo_semana = (int) $filtro_anyo_semana;
    if ($filtro_anyo_semana > 0) {
        $query_base .= ' AND e.year_semana_numero = ?';
        $params[] = $filtro_anyo_semana;
        $types .= 'i';
        return;
    }

    if (!$hayFiltroFecha) {
        if ($anioActual <= 0) {
            $anioActual = (int) date('Y');
        }
        $query_base .= ' AND YEAR(e.fecha_envio) >= ?';
        $params[] = $anioActual - 2;
        $types .= 'i';
    }
}

/**
 * Genera un select con las semanas de listado_numero_semanas.
 *
 * @param int $numero_semana Número de semana preseleccionado (opcional)
 * @param int $anyo_filtro Año para filtrar el listado (0 = todos los años)
 * @param string $name Nombre del select
 * @param string $id ID del select
 * @param bool $required Si es requerido
 * @param string $select_class Clase extra del select (p. ej. select2-custom)
 */
function listado_semanas($numero_semana = 0, $anyo_filtro = 0, $name = 'listado_semanas', $id = 'listado_semanas', $required = false, $select_class = 'select2') {
    $classSelect = 'form-select ' . trim((string) $select_class);
    $semanas = obtener_listado_numero_semanas_cerradas($anyo_filtro);

    echo "<select class='{$classSelect}' id='{$id}' name='{$name}'" . ($required ? ' required' : '') . ">";

    if ($semanas === false) {
        echo "<option value=''>Error al cargar semanas</option>";
        echo "</select>";
        return;
    }

    listado_semanas_imprimir_opciones($numero_semana, $anyo_filtro, 'Seleccione número de semana', $semanas);
    echo "</select>";
}

/**
 * Select múltiple de semanas cerradas (fecha_semana_hasta &lt; hoy), orden id_numero_semana DESC.
 *
 * @param array $semanas_seleccionadas Números de semana preseleccionados
 * @param int $anyo_filtro Año opcional
 * @param string $name Nombre del select
 * @param string $id ID del select
 * @param bool $required Si es requerido
 * @param string $select_class Clase extra del select (p. ej. select2-custom)
 */
function listado_semanas_multiple($semanas_seleccionadas = array(), $anyo_filtro = 0, $name = 'semanas_numero[]', $id = 'semanas_numero', $required = false, $select_class = 'select2-custom') {
    $conexion = conectar_bd();
    $semanas_sel = array();
    foreach ((array) $semanas_seleccionadas as $semSel) {
        $n = (int) $semSel;
        if ($n > 0) {
            $semanas_sel[] = $n;
        }
    }
    $semanas_sel = array_values(array_unique($semanas_sel));
    $idEsc = htmlspecialchars((string) $id, ENT_QUOTES, 'UTF-8');
    $nameEsc = htmlspecialchars((string) $name, ENT_QUOTES, 'UTF-8');
    $classSelect = 'form-select select2 ' . trim((string) $select_class);

    if (!$conexion) {
        echo "<select class='{$classSelect}' id='{$idEsc}' name='{$nameEsc}' multiple" . ($required ? ' required' : '') . ">";
        echo "<option value=''>Error de conexión</option>";
        echo "</select>";
        return;
    }

    mysqli_set_charset($conexion, 'utf8');

    $query = "SELECT numero_semana, anyo_listado, fecha_semana_desde, fecha_semana_hasta
              FROM listado_numero_semanas
              WHERE fecha_semana_hasta < CURDATE()";
    $params = [];
    $types = '';

    if ((int) $anyo_filtro > 0) {
        $query .= " AND anyo_listado = ?";
        $params[] = (int) $anyo_filtro;
        $types .= 'i';
    }

    $query .= " ORDER BY id_numero_semana DESC";

    $stmt = mysqli_prepare($conexion, $query);
    if (!$stmt) {
        echo "<select class='{$classSelect}' id='{$idEsc}' name='{$nameEsc}' multiple" . ($required ? ' required' : '') . ">";
        echo "<option value=''>Error en consulta</option>";
        echo "</select>";
        mysqli_close($conexion);
        return;
    }

    if ($types !== '') {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }

    mysqli_stmt_execute($stmt);
    $resultado = mysqli_stmt_get_result($stmt);

    echo "<select class='{$classSelect}' id='{$idEsc}' name='{$nameEsc}' multiple" . ($required ? ' required' : '') . ">";

    while ($row = mysqli_fetch_assoc($resultado)) {
        $numSem = (int) $row['numero_semana'];
        $anyo = (int) $row['anyo_listado'];
        $fecha_semana_desde = formatear_fecha_dmY_desde_db($row['fecha_semana_desde'] ?? null);
        $fecha_semana_hasta = formatear_fecha_dmY_desde_db($row['fecha_semana_hasta'] ?? null);
        $selected = in_array($numSem, $semanas_sel, true) ? 'selected' : '';
        $texto = $numSem . ' del ' . $fecha_semana_desde . ' al ' . $fecha_semana_hasta;
        echo "<option value='{$numSem}' {$selected} data-anyo='{$anyo}'>" . htmlspecialchars($texto, ENT_QUOTES, 'UTF-8') . "</option>";
    }

    echo "</select>";

    mysqli_stmt_close($stmt);
    mysqli_close($conexion);
}

/**
 * Semanas disponibles en el picker de proforma: desde el pasado hasta hoy, orden descendente.
 *
 * @param int $anyo_filtro Año opcional (0 = todos)
 * @return array<int, array<string, int|string>>
 */
function obtener_semanas_disponibles_proforma_picker($anyo_filtro = 0) {
    $conexion = conectar_bd();
    if (!$conexion) {
        return array();
    }

    mysqli_set_charset($conexion, 'utf8');

    $query = "SELECT id_numero_semana, numero_semana, anyo_listado, fecha_semana_desde
              FROM listado_numero_semanas
              WHERE fecha_semana_desde <= CURDATE()";
    $params = array();
    $types = '';

    if ((int) $anyo_filtro > 0) {
        $query .= " AND anyo_listado = ?";
        $params[] = (int) $anyo_filtro;
        $types .= 'i';
    }

    $query .= " ORDER BY anyo_listado DESC, numero_semana DESC, fecha_semana_desde DESC, id_numero_semana DESC";

    $stmt = mysqli_prepare($conexion, $query);
    if (!$stmt) {
        mysqli_close($conexion);
        return array();
    }

    if ($types !== '') {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }

    mysqli_stmt_execute($stmt);
    $resultado = mysqli_stmt_get_result($stmt);
    $semanas = array();

    while ($row = mysqli_fetch_assoc($resultado)) {
        $semanas[] = array(
            'id_numero_semana' => (int) $row['id_numero_semana'],
            'numero_semana' => (int) $row['numero_semana'],
            'anyo_listado' => (int) $row['anyo_listado'],
        );
    }

    mysqli_stmt_close($stmt);
    mysqli_close($conexion);

    return $semanas;
}

/**
 * @param int $numero_semana
 * @param int $anyo_listado
 */
function obtener_id_listado_semana_proforma($numero_semana, $anyo_listado = 0) {
    $num = (int) $numero_semana;
    if ($num <= 0) {
        return 0;
    }

    $conexion = conectar_bd();
    if (!$conexion) {
        return 0;
    }

    mysqli_set_charset($conexion, 'utf8');

    if ((int) $anyo_listado > 0) {
        $stmt = mysqli_prepare(
            $conexion,
            'SELECT id_numero_semana FROM listado_numero_semanas WHERE numero_semana = ? AND anyo_listado = ? ORDER BY id_numero_semana DESC LIMIT 1'
        );
        if (!$stmt) {
            mysqli_close($conexion);
            return 0;
        }
        $anyo = (int) $anyo_listado;
        mysqli_stmt_bind_param($stmt, 'ii', $num, $anyo);
    } else {
        $stmt = mysqli_prepare(
            $conexion,
            'SELECT id_numero_semana FROM listado_numero_semanas WHERE numero_semana = ? ORDER BY id_numero_semana DESC LIMIT 1'
        );
        if (!$stmt) {
            mysqli_close($conexion);
            return 0;
        }
        mysqli_stmt_bind_param($stmt, 'i', $num);
    }

    mysqli_stmt_execute($stmt);
    $resultado = mysqli_stmt_get_result($stmt);
    $id = 0;
    if ($row = mysqli_fetch_assoc($resultado)) {
        $id = (int) $row['id_numero_semana'];
    }

    mysqli_stmt_close($stmt);
    mysqli_close($conexion);

    return $id;
}

function obtener_cuentas_banco_empresa_proforma($id_empresa) {
    $id_empresa = (int) $id_empresa;
    $resultado = array(
        'cuentas' => array(),
        'id_por_defecto' => 0,
    );

    if ($id_empresa <= 0) {
        return $resultado;
    }

    $conexion = conectar_bd();
    if (!$conexion) {
        return $resultado;
    }

    mysqli_set_charset($conexion, 'utf8');
    $stmt = mysqli_prepare(
        $conexion,
        'SELECT id_cuenta_banco, numerocuenta, banco_cuenta, por_defecto
         FROM cuentas_banco_empresas
         WHERE empresa_cuenta_id = ?
         ORDER BY por_defecto DESC, fecha_creacion DESC, id_cuenta_banco DESC'
    );
    if (!$stmt) {
        mysqli_close($conexion);
        return $resultado;
    }

    mysqli_stmt_bind_param($stmt, 'i', $id_empresa);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);

    while ($row = mysqli_fetch_assoc($res)) {
        $idCuenta = (int) $row['id_cuenta_banco'];
        $item = array(
            'id_cuenta_banco' => $idCuenta,
            'numerocuenta' => isset($row['numerocuenta']) ? (string) $row['numerocuenta'] : '',
            'banco_cuenta' => isset($row['banco_cuenta']) ? (string) $row['banco_cuenta'] : '',
            'por_defecto' => isset($row['por_defecto']) ? (string) $row['por_defecto'] : 'false',
        );
        $resultado['cuentas'][] = $item;
        if ($resultado['id_por_defecto'] <= 0 && $item['por_defecto'] === 'true') {
            $resultado['id_por_defecto'] = $idCuenta;
        }
    }

    if ($res) {
        mysqli_free_result($res);
    }
    mysqli_stmt_close($stmt);
    mysqli_close($conexion);

    return $resultado;
}

/**
 * @param int $id_proforma
 * @param mysqli|null $conexion
 * @return array|null
 */
function obtener_rel_forma_pago_proforma($id_proforma, $conexion = null) {
    $id_proforma = (int) $id_proforma;
    if ($id_proforma <= 0) {
        return null;
    }

    $cerrar = false;
    if (!$conexion) {
        $conexion = conectar_bd();
        $cerrar = true;
    }
    if (!$conexion) {
        return null;
    }

    mysqli_set_charset($conexion, 'utf8');
    $stmt = mysqli_prepare(
        $conexion,
        'SELECT id_rel_forma_pago, proforma_id, forma_de_pago_id, numero_forma_pago, fecha_rel, empresa_id_rel
         FROM rel_proformas_forma_pago
         WHERE proforma_id = ?
         ORDER BY id_rel_forma_pago DESC
         LIMIT 1'
    );
    if (!$stmt) {
        if ($cerrar) {
            mysqli_close($conexion);
        }
        return null;
    }

    mysqli_stmt_bind_param($stmt, 'i', $id_proforma);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $row = ($res && ($data = mysqli_fetch_assoc($res))) ? $data : null;
    if ($res) {
        mysqli_free_result($res);
    }
    mysqli_stmt_close($stmt);

    if ($cerrar) {
        mysqli_close($conexion);
    }

    return $row;
}

/**
 * @param int $id_empresa
 * @param string $numerocuenta
 * @param mysqli|null $conexion
 */
function resolver_id_cuenta_banco_empresa_por_numero($id_empresa, $numerocuenta, $conexion = null) {
    $id_empresa = (int) $id_empresa;
    $numerocuenta = trim((string) $numerocuenta);
    if ($id_empresa <= 0 || $numerocuenta === '') {
        return 0;
    }

    $cerrar = false;
    if (!$conexion) {
        $conexion = conectar_bd();
        $cerrar = true;
    }
    if (!$conexion) {
        return 0;
    }

    mysqli_set_charset($conexion, 'utf8');
    $stmt = mysqli_prepare(
        $conexion,
        'SELECT id_cuenta_banco
         FROM cuentas_banco_empresas
         WHERE empresa_cuenta_id = ? AND numerocuenta = ?
         ORDER BY por_defecto DESC, id_cuenta_banco DESC
         LIMIT 1'
    );
    if (!$stmt) {
        if ($cerrar) {
            mysqli_close($conexion);
        }
        return 0;
    }

    mysqli_stmt_bind_param($stmt, 'is', $id_empresa, $numerocuenta);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $id = 0;
    if ($res && ($row = mysqli_fetch_assoc($res))) {
        $id = (int) $row['id_cuenta_banco'];
        mysqli_free_result($res);
    }
    mysqli_stmt_close($stmt);

    if ($cerrar) {
        mysqli_close($conexion);
    }

    return $id;
}

/**
 * @param int $id_cuenta
 * @param int $id_empresa
 * @param mysqli|null $conexion
 * @return array|null
 */
function obtener_cuenta_banco_empresa_por_id($id_cuenta, $id_empresa, $conexion = null) {
    $id_cuenta = (int) $id_cuenta;
    $id_empresa = (int) $id_empresa;
    if ($id_cuenta <= 0 || $id_empresa <= 0) {
        return null;
    }

    $cerrar = false;
    if (!$conexion) {
        $conexion = conectar_bd();
        $cerrar = true;
    }
    if (!$conexion) {
        return null;
    }

    mysqli_set_charset($conexion, 'utf8');
    $stmt = mysqli_prepare(
        $conexion,
        'SELECT id_cuenta_banco, numerocuenta, banco_cuenta, por_defecto
         FROM cuentas_banco_empresas
         WHERE id_cuenta_banco = ? AND empresa_cuenta_id = ?
         LIMIT 1'
    );
    if (!$stmt) {
        if ($cerrar) {
            mysqli_close($conexion);
        }
        return null;
    }

    mysqli_stmt_bind_param($stmt, 'ii', $id_cuenta, $id_empresa);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $row = ($res && ($data = mysqli_fetch_assoc($res))) ? $data : null;
    if ($res) {
        mysqli_free_result($res);
    }
    mysqli_stmt_close($stmt);

    if ($cerrar) {
        mysqli_close($conexion);
    }

    return $row;
}

/**
 * @param mysqli $conexion
 * @param int $id_proforma
 * @param int $forma_de_pago_id
 * @param string $numero_forma_pago
 * @param int $empresa_id
 */
function guardar_rel_forma_pago_proforma($conexion, $id_proforma, $forma_de_pago_id, $numero_forma_pago, $empresa_id) {
    $id_proforma = (int) $id_proforma;
    $forma_de_pago_id = (int) $forma_de_pago_id;
    $empresa_id = (int) $empresa_id;
    $numero_forma_pago = trim((string) $numero_forma_pago);

    if ($id_proforma <= 0 || $forma_de_pago_id <= 0 || $empresa_id <= 0) {
        return false;
    }

    $rel = obtener_rel_forma_pago_proforma($id_proforma, $conexion);
    if ($rel) {
        $stmt = mysqli_prepare(
            $conexion,
            'UPDATE rel_proformas_forma_pago
             SET forma_de_pago_id = ?, numero_forma_pago = ?, empresa_id_rel = ?, fecha_rel = CURDATE()
             WHERE proforma_id = ?
             LIMIT 1'
        );
        if (!$stmt) {
            return false;
        }
        mysqli_stmt_bind_param($stmt, 'isii', $forma_de_pago_id, $numero_forma_pago, $empresa_id, $id_proforma);
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return $ok;
    }

    $stmt = mysqli_prepare(
        $conexion,
        'INSERT INTO rel_proformas_forma_pago (proforma_id, forma_de_pago_id, numero_forma_pago, fecha_rel, empresa_id_rel)
         VALUES (?, ?, ?, CURDATE(), ?)'
    );
    if (!$stmt) {
        return false;
    }
    mysqli_stmt_bind_param($stmt, 'iisi', $id_proforma, $forma_de_pago_id, $numero_forma_pago, $empresa_id);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    return $ok;
}

/**
 * @param mysqli $conexion
 * @param int $id_proforma
 */
function eliminar_rel_forma_pago_proforma($conexion, $id_proforma) {
    $id_proforma = (int) $id_proforma;
    if ($id_proforma <= 0) {
        return false;
    }

    $stmt = mysqli_prepare(
        $conexion,
        'DELETE FROM rel_proformas_forma_pago WHERE proforma_id = ?'
    );
    if (!$stmt) {
        return false;
    }
    mysqli_stmt_bind_param($stmt, 'i', $id_proforma);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    return $ok;
}

/**
 * Imprime <option> para picker de semanas (número y año), desde el pasado hasta la semana actual.
 *
 * @param int $anyo_filtro Año opcional
 * @param array $ids_excluir IDs o números de semana a omitir
 */
function imprimir_opciones_semanas_proforma_picker($anyo_filtro = 0, $ids_excluir = array()) {
    $excluirIds = array();
    foreach ((array) $ids_excluir as $valorEx) {
        $idSem = (int) $valorEx;
        if ($idSem > 0) {
            $excluirIds[$idSem] = true;
        }
    }

    $semanas = obtener_semanas_disponibles_proforma_picker($anyo_filtro);
    if (!$semanas) {
        echo "<option value=''>Sin semanas disponibles</option>";
        return;
    }

    foreach ($semanas as $row) {
        $idSem = (int) $row['id_numero_semana'];
        if (isset($excluirIds[$idSem])) {
            continue;
        }
        $numSem = (int) $row['numero_semana'];
        $anyo = (int) $row['anyo_listado'];
        $texto = $numSem . ' del ' . $anyo;
        echo "<option value='{$idSem}' data-numero='{$numSem}' data-anyo='{$anyo}'>" . htmlspecialchars($texto, ENT_QUOTES, 'UTF-8') . "</option>";
    }
}

/**
 * Etiqueta visible de semana para proformas: "25 del 2026".
 *
 * @param int $numero_semana
 * @param int $anyo_listado Año opcional; si no se indica, se busca el más reciente
 */
function etiqueta_semana_proforma_numero($numero_semana, $anyo_listado = 0) {
    $num = (int) $numero_semana;
    $anyo = (int) $anyo_listado;
    if ($num <= 0) {
        return '';
    }
    if ($anyo <= 0) {
        $anyo = obtener_anyo_listado_semana_proforma($num);
    }
    if ($anyo > 0) {
        return $num . ' del ' . $anyo;
    }
    return (string) $num;
}

/**
 * @param int $numero_semana
 */
function obtener_anyo_listado_semana_proforma($numero_semana) {
    $num = (int) $numero_semana;
    if ($num <= 0) {
        return 0;
    }

    $conexion = conectar_bd();
    if (!$conexion) {
        return 0;
    }

    mysqli_set_charset($conexion, 'utf8');
    $stmt = mysqli_prepare(
        $conexion,
        'SELECT anyo_listado FROM listado_numero_semanas WHERE numero_semana = ? ORDER BY id_numero_semana DESC LIMIT 1'
    );
    if (!$stmt) {
        mysqli_close($conexion);
        return 0;
    }

    mysqli_stmt_bind_param($stmt, 'i', $num);
    mysqli_stmt_execute($stmt);
    $resultado = mysqli_stmt_get_result($stmt);
    $anyo = 0;
    if ($row = mysqli_fetch_assoc($resultado)) {
        $anyo = (int) $row['anyo_listado'];
    }

    mysqli_stmt_close($stmt);
    mysqli_close($conexion);

    return $anyo;
}

function numeroSemanaConFecha($fecha_informe)
{
    $conexion = conectar_bd();
    if (!$conexion || !($conexion instanceof mysqli)) {
        return null;
    }

    $fecha_informe = (string) $fecha_informe;
    if ($fecha_informe === '') {
        mysqli_close($conexion);
        return null;
    }

    $sql = "SELECT numero_semana
            FROM listado_numero_semanas
            WHERE ? BETWEEN fecha_semana_desde AND fecha_semana_hasta
              AND anyo_listado = YEAR(CURDATE())
            LIMIT 1";

    $stmt = mysqli_prepare($conexion, $sql);
    if (!$stmt) {
        mysqli_close($conexion);
        return null;
    }

    mysqli_stmt_bind_param($stmt, 's', $fecha_informe);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $row = $res ? mysqli_fetch_assoc($res) : null;
    mysqli_stmt_close($stmt);

    $out = isset($row['numero_semana']) ? (int) $row['numero_semana'] : null;
    mysqli_close($conexion);
    return $out;
}

function numeroSemanaActual()
{
    $conexion = conectar_bd();
    if (!$conexion || !($conexion instanceof mysqli)) {
        return null;
    }

    $sql = "SELECT numero_semana
            FROM listado_numero_semanas
            WHERE CURDATE() BETWEEN fecha_semana_desde AND fecha_semana_hasta
              AND anyo_listado = YEAR(CURDATE())
            LIMIT 1";

    $res = mysqli_query($conexion, $sql);
    if (!$res) {
        mysqli_close($conexion);
        return null;
    }
    $row = mysqli_fetch_assoc($res);

    $out = isset($row['numero_semana']) ? (int) $row['numero_semana'] : null;
    mysqli_close($conexion);
    return $out;
}

function numeroSemanaActualConAnyo()
{
    $conexion = conectar_bd();
    if (!$conexion || !($conexion instanceof mysqli)) {
        return null;
    }

    $sql = "SELECT numero_semana, anyo_listado
            FROM listado_numero_semanas
            WHERE CURDATE() BETWEEN fecha_semana_desde AND fecha_semana_hasta
              AND anyo_listado = YEAR(CURDATE())
            LIMIT 1";

    $res = mysqli_query($conexion, $sql);
    if (!$res) {
        mysqli_close($conexion);
        return null;
    }
    $row = mysqli_fetch_assoc($res);

    if (!$row) {
        mysqli_close($conexion);
        return null;
    }

    $out = [
        'numero_semana' => isset($row['numero_semana']) ? (int) $row['numero_semana'] : null,
        'anyo_listado' => isset($row['anyo_listado']) ? (int) $row['anyo_listado'] : null,
    ];
    mysqli_close($conexion);
    return $out;
}

function numeroSemanaEnvio()
{
    $conexion = conectar_bd();
    if (!$conexion || !($conexion instanceof mysqli)) {
        return null;
    }

    // Semana actual
    $sqlActual = "SELECT id_numero_semana, fecha_semana_desde, fecha_semana_hasta, numero_semana, anyo_listado
                 FROM listado_numero_semanas
                 WHERE CURDATE() BETWEEN fecha_semana_desde AND fecha_semana_hasta
                   AND anyo_listado = YEAR(CURDATE())
                 LIMIT 1";

    $rActual = mysqli_query($conexion, $sqlActual);
    if (!$rActual) {
        mysqli_close($conexion);
        return null;
    }
    $actual = mysqli_fetch_assoc($rActual);
    if (!$actual) {
        mysqli_close($conexion);
        return null;
    }

    $numActual = (int) $actual['numero_semana'];
    $anyoActual = (int) $actual['anyo_listado'];

    // Queremos 3 semanas atrás (misma lógica que antes), pero sin hardcodear 50/51/52.
    $anyoEnvio = $anyoActual;
    $numEnvio = $numActual - 3;

    if ($numEnvio <= 0) {
        $anyoEnvio = $anyoActual - 1;

        $resMax = mysqli_query($conexion, "SELECT MAX(numero_semana) AS max_semana FROM listado_numero_semanas WHERE anyo_listado = " . (int) $anyoEnvio);
        $rowMax = $resMax ? mysqli_fetch_assoc($resMax) : null;
        $maxSemana = $rowMax && $rowMax['max_semana'] !== null ? (int) $rowMax['max_semana'] : 52;

        // Si estamos en semana 1 -> max-2, semana 2 -> max-1, semana 3 -> max
        $numEnvio = $maxSemana - (0 - $numEnvio); // numEnvio es negativo o 0
        if ($numEnvio < 1) {
            $numEnvio = 1;
        }
    }

    // Obtener el registro de la semana objetivo
    $sqlObj = "SELECT id_numero_semana, fecha_semana_desde, fecha_semana_hasta, numero_semana, anyo_listado
              FROM listado_numero_semanas
              WHERE numero_semana = ? AND anyo_listado = ?
              LIMIT 1";
    $stmt = mysqli_prepare($conexion, $sqlObj);
    if (!$stmt) {
        mysqli_close($conexion);
        return null;
    }
    mysqli_stmt_bind_param($stmt, 'ii', $numEnvio, $anyoEnvio);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $row = $res ? mysqli_fetch_assoc($res) : null;
    mysqli_stmt_close($stmt);

    $out = $row ?: null;
    mysqli_close($conexion);
    return $out;
}

/**
 * Genera un JPG (por defecto 600x120) con un texto centrado, útil como cabecera/logo en PDFs (requiere GD + JPEG).
 * Usa las fuentes DejaVu empaquetadas con mPDF (vendor).
 *
 * @param string $ruta_salida Ruta absoluta del .jpg a crear
 * @param string $texto       Texto a dibujar (p. ej. nombre de empresa)
 * @param int    $ancho
 * @param int    $alto
 * @return bool
 */
function proforma_generar_jpg_empresa($ruta_salida, $texto, $ancho = 600, $alto = 120) {
    if (!function_exists('imagecreatetruecolor') || !function_exists('imagejpeg')) {
        return false;
    }
    $texto = trim((string) $texto);
    if ($texto === '') {
        $texto = '—';
    }
    $im = imagecreatetruecolor($ancho, $alto);
    if ($im === false) {
        return false;
    }
    $blanco = imagecolorallocate($im, 255, 255, 255);
    $gris = imagecolorallocate($im, 40, 40, 40);
    imagefilledrectangle($im, 0, 0, $ancho, $alto, $blanco);
    $baseFont = __DIR__ . '/../vendor/mpdf/mpdf/ttfonts/DejaVuSansCondensed.ttf';
    $font = is_readable($baseFont) ? $baseFont : __DIR__ . '/../vendor/mpdf/mpdf/ttfonts/DejaVuSans.ttf';
    if (is_readable($font)) {
        $fontSize = 32;
        $maxW = $ancho - 40;
        $bbox = imagettfbbox($fontSize, 0, $font, $texto);
        $textW = $bbox ? abs($bbox[2] - $bbox[0]) : 0;
        while ($fontSize > 12 && $textW > $maxW) {
            $fontSize -= 2;
            $bbox = imagettfbbox($fontSize, 0, $font, $texto);
            $textW = $bbox ? abs($bbox[2] - $bbox[0]) : 0;
        }
        $bbox = imagettfbbox($fontSize, 0, $font, $texto);
        $textH = $bbox ? abs($bbox[7] - $bbox[1]) : 20;
        $textW = $bbox ? abs($bbox[2] - $bbox[0]) : 0;
        $x = (int) round(($ancho - $textW) / 2);
        $y = (int) round(($alto - $textH) / 2) + $textH;
        imagettftext($im, $fontSize, 0, $x, $y, $gris, $font, $texto);
    } else {
        $txt = function_exists('mb_substr') ? mb_substr($texto, 0, 45, 'UTF-8') : substr($texto, 0, 45);
        $txt2 = @iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $txt) ?: $txt;
        imagestring($im, 5, 20, (int) ($alto / 2 - 8), $txt2, $gris);
    }
    $ok = @imagejpeg($im, $ruta_salida, 92);
    imagedestroy($im);
    return (bool) $ok;
}

// FUNCION PAR OBTENER ID_EMPRESA CON ID_SUCURSAL
function obtenerIdEmpresa($id_sucursal) {
    $conexion = conectar_bd();
    if (!$conexion || !($conexion instanceof mysqli)) {
        return null;
    }
    $sql = "SELECT empresa_id FROM sucursal WHERE id_sucursal = ?";
    $stmt = mysqli_prepare($conexion, $sql);
    mysqli_stmt_bind_param($stmt, 'i', $id_sucursal);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = $result ? mysqli_fetch_assoc($result) : null;
    $id_empresa = isset($row['empresa_id']) ? (int) $row['empresa_id'] : null;
    mysqli_stmt_close($stmt);
    mysqli_close($conexion);
    return $id_empresa;
}

function calcular_datos_envio($id_sucursal) {
    $conexion = conectar_bd();
    $tabla_lotes = 'lotes_' . $id_sucursal;
    
    // Obtener semana menos 3 para filtrar (incluye fechas)
    $semana_menos_3 = obtener_numero_semana_menos_3();
    $numeroSemana_final = '';
    $anyo_listado = '';
    $semana_actual = '';
    $semana_principal_desde = '';
    $semana_principal_hasta = '';
    if ($semana_menos_3 !== false) {
        $numeroSemana_final = $semana_menos_3['numero_semana'];
        $anyo_listado = $semana_menos_3['anyo_listado'];
        $semana_actual = $numeroSemana_final;
        $semana_principal_desde = $semana_menos_3['semana_principal_desde'];
        $semana_principal_hasta = $semana_menos_3['semana_principal_hasta'];
    }
    
    // Consultar datos de los lotes pendientes
    $query_lotes = "SELECT 
        COUNT(*) as cantidad_lotes,
        SUM(CASE WHEN compra_opcion = 'no' THEN 1 ELSE 0 END) as cantidad_compras,
        SUM(CASE WHEN compra_opcion = 'si' AND fecha_perdido != '0000-00-00' AND fecha_perdido IS NOT NULL THEN 1 ELSE 0 END) as cantidad_empenios_perdidos,
        SUM(cantidad_articulos) as total_articulos,
        SUM(CASE WHEN tipo_de_lote LIKE '%oro%' THEN peso_bruto ELSE 0 END) as total_oro_bruto,
        SUM(CASE WHEN tipo_de_lote LIKE '%oro%' THEN peso ELSE 0 END) as total_oro_neto,
        SUM(CASE WHEN tipo_de_lote LIKE '%plata%' THEN peso_bruto ELSE 0 END) as total_plata_bruto,
        SUM(CASE WHEN tipo_de_lote LIKE '%plata%' THEN peso ELSE 0 END) as total_plata_neto
    FROM $tabla_lotes
    WHERE estado_envio = 'pendiente_enviar' AND devuelve_a_central = 'si'";
    
    $result_lotes = mysqli_query($conexion, $query_lotes);
    $datos_envio = mysqli_fetch_assoc($result_lotes);
    
    // Calcular semanas de compras
    $semanas_lotes = '----';
    if (!empty($numeroSemana_final) && !empty($anyo_listado)) {
        $query_semanas_compras = "SELECT GROUP_CONCAT(DISTINCT semana_numero ORDER BY semana_numero) as semanas 
                                   FROM $tabla_lotes 
                                   WHERE estado_envio = 'pendiente_enviar' 
                                   AND devuelve_a_central = 'si'
                                   AND compra_opcion = 'no' 
                                   AND semana_numero <= ? 
                                   AND anyo_semana = ?";
        $stmt_semanas_compras = mysqli_prepare($conexion, $query_semanas_compras);
        mysqli_stmt_bind_param($stmt_semanas_compras, 'ii', $numeroSemana_final, $anyo_listado);
        mysqli_stmt_execute($stmt_semanas_compras);
        $result_semanas_compras = mysqli_stmt_get_result($stmt_semanas_compras);
        $row_semanas_compras = mysqli_fetch_assoc($result_semanas_compras);
        if ($row_semanas_compras && !empty($row_semanas_compras['semanas'])) {
            $semanas_lotes = $row_semanas_compras['semanas'];
        }
        mysqli_stmt_close($stmt_semanas_compras);
    }
    
    // Calcular semanas de empeños perdidos
    $semanas_empenios_perdidos = '----';
    if (!empty($numeroSemana_final) && !empty($anyo_listado)) {
        $query_semanas_empenios = "SELECT GROUP_CONCAT(DISTINCT numero_semana_empenio_perdido ORDER BY numero_semana_empenio_perdido) as semanas_empenios 
                                    FROM $tabla_lotes 
                                    WHERE estado_envio = 'pendiente_enviar' 
                                    AND devuelve_a_central = 'si'
                                    AND compra_opcion = 'si' 
                                    AND numero_semana_empenio_perdido <= ? 
                                    AND year_empenio_perdido = ?";
        $stmt_semanas_empenios = mysqli_prepare($conexion, $query_semanas_empenios);
        mysqli_stmt_bind_param($stmt_semanas_empenios, 'ii', $numeroSemana_final, $anyo_listado);
        mysqli_stmt_execute($stmt_semanas_empenios);
        $result_semanas_empenios = mysqli_stmt_get_result($stmt_semanas_empenios);
        $row_semanas_empenios = mysqli_fetch_assoc($result_semanas_empenios);
        if ($row_semanas_empenios && !empty($row_semanas_empenios['semanas_empenios'])) {
            $semanas_empenios_perdidos = $row_semanas_empenios['semanas_empenios'];
        }
        mysqli_stmt_close($stmt_semanas_empenios);
    }
    
    // Calcular semanas desde > hasta usando las semanas calculadas
    $semanas_desde = '';
    $semanas_hasta = '';
    if (!empty($semanas_lotes) && $semanas_lotes != '----') {
        $semanas_array = explode(',', $semanas_lotes);
        $semanas_array = array_filter($semanas_array);
        if (!empty($semanas_array)) {
            // Obtener fechas de la semana mínima y máxima
            $query_fechas = "SELECT MIN(fecha_semana_desde) as fecha_desde, MAX(fecha_semana_hasta) as fecha_hasta 
                            FROM listado_numero_semanas 
                            WHERE numero_semana IN (" . implode(',', array_map('intval', $semanas_array)) . ")";
            $result_fechas = mysqli_query($conexion, $query_fechas);
            $row_fechas = mysqli_fetch_assoc($result_fechas);
            if ($row_fechas && $row_fechas['fecha_desde'] && $row_fechas['fecha_hasta']) {
                $semanas_desde = date('d/m/Y', strtotime($row_fechas['fecha_desde']));
                $semanas_hasta = date('d/m/Y', strtotime($row_fechas['fecha_hasta']));
            }
        }
    }

    // Una sola semana distinta en el envío (compras + empeños perdidos): "true" / "false"
    $semanas_unicas = array();
    if (!empty($semanas_lotes) && $semanas_lotes !== '----') {
        foreach (explode(',', $semanas_lotes) as $_s) {
            $_s = trim($_s);
            if ($_s !== '') {
                $semanas_unicas[(int) $_s] = true;
            }
        }
    }
    if (!empty($semanas_empenios_perdidos) && $semanas_empenios_perdidos !== '----') {
        foreach (explode(',', $semanas_empenios_perdidos) as $_s) {
            $_s = trim($_s);
            if ($_s !== '') {
                $semanas_unicas[(int) $_s] = true;
            }
        }
    }
    $solo_una_semana = count($semanas_unicas) <= 1 ? 'true' : 'false';

    // Totales por metal (lotes pendientes de envío)
    $total_lotes_oro = 0;
    $precio_compra_lotes_oro = 0.0;
    $merma_lotes_oro = 0.0;
    $cantidad_articulos_oro = 0;
    $total_lotes_plata = 0;
    $precio_compra_lotes_plata = 0.0;
    $merma_lotes_plata = 0.0;
    $cantidad_articulos_plata = 0;

    $q_oro = "SELECT COUNT(*) AS n, COALESCE(SUM(precio_compra), 0) AS precio, COALESCE(SUM(merma), 0) AS merma, COALESCE(SUM(cantidad_articulos), 0) AS arts
              FROM `{$tabla_lotes}`
              WHERE estado_envio = 'pendiente_enviar' AND devuelve_a_central = 'si' AND tipo_de_lote LIKE '%oro%'";
    $r_oro = mysqli_query($conexion, $q_oro);
    if ($r_oro && ($row_oro = mysqli_fetch_assoc($r_oro))) {
        $total_lotes_oro = (int) ($row_oro['n'] ?? 0);
        $precio_compra_lotes_oro = floatval($row_oro['precio'] ?? 0);
        $merma_lotes_oro = floatval($row_oro['merma'] ?? 0);
        $cantidad_articulos_oro = (int) ($row_oro['arts'] ?? 0);
    }

    $q_plata = "SELECT COUNT(*) AS n, COALESCE(SUM(precio_compra), 0) AS precio, COALESCE(SUM(merma), 0) AS merma, COALESCE(SUM(cantidad_articulos), 0) AS arts
                FROM `{$tabla_lotes}`
                WHERE estado_envio = 'pendiente_enviar' AND devuelve_a_central = 'si' AND tipo_de_lote LIKE '%plata%'";
    $r_plata = mysqli_query($conexion, $q_plata);
    if ($r_plata && ($row_plata = mysqli_fetch_assoc($r_plata))) {
        $total_lotes_plata = (int) ($row_plata['n'] ?? 0);
        $precio_compra_lotes_plata = floatval($row_plata['precio'] ?? 0);
        $merma_lotes_plata = floatval($row_plata['merma'] ?? 0);
        $cantidad_articulos_plata = (int) ($row_plata['arts'] ?? 0);
    }

    // Renovaciones y retirados en historico (rango semana principal en SQL Y-m-d)
    $total_renovaciones = 0;
    $importe_renovaciones = 0.0;
    $total_lotes_retirados = 0;
    $total_importe_retirados = 0.0;

    $fecha_semana_desde_sql = '';
    $fecha_semana_hasta_sql = '';
    if ($semana_principal_desde !== '' && $semana_principal_hasta !== '') {
        $dDesde = DateTime::createFromFormat('d/m/Y', $semana_principal_desde);
        $dHasta = DateTime::createFromFormat('d/m/Y', $semana_principal_hasta);
        if ($dDesde instanceof DateTime) {
            $fecha_semana_desde_sql = $dDesde->format('Y-m-d');
        }
        if ($dHasta instanceof DateTime) {
            $fecha_semana_hasta_sql = $dHasta->format('Y-m-d');
        }
    }

    if ($fecha_semana_desde_sql !== '' && $fecha_semana_hasta_sql !== '') {
        $tabla_hist = 'historico_renovaciones_' . (int) $id_sucursal;

        $sql_renov = "SELECT COUNT(id_renovaciones) AS total_renovaciones, COALESCE(SUM(importe_renovacion), 0) AS importe_renovaciones
                      FROM `{$tabla_hist}`
                      WHERE fecha_renovacion BETWEEN ? AND ?
                      AND estado_historico = 'Renovado'";
        if ($stmt_r = mysqli_prepare($conexion, $sql_renov)) {
            mysqli_stmt_bind_param($stmt_r, 'ss', $fecha_semana_desde_sql, $fecha_semana_hasta_sql);
            mysqli_stmt_execute($stmt_r);
            $res_r = mysqli_stmt_get_result($stmt_r);
            if ($res_r && ($rr = mysqli_fetch_assoc($res_r))) {
                $total_renovaciones = (int) ($rr['total_renovaciones'] ?? 0);
                $importe_renovaciones = floatval($rr['importe_renovaciones'] ?? 0);
            }
            mysqli_stmt_close($stmt_r);
        }

        // Importe retirados: si en su BD existe la columna importe_retiradas, sustitúyala en el SUM.
        $sql_ret = "SELECT COUNT(id_renovaciones) AS total_retirados, COALESCE(SUM(importe_renovacion), 0) AS importe_retirados
                    FROM `{$tabla_hist}`
                    WHERE fecha_renovacion BETWEEN ? AND ?
                    AND estado_historico = 'Retirado'";
        if ($stmt_t = mysqli_prepare($conexion, $sql_ret)) {
            mysqli_stmt_bind_param($stmt_t, 'ss', $fecha_semana_desde_sql, $fecha_semana_hasta_sql);
            mysqli_stmt_execute($stmt_t);
            $res_t = mysqli_stmt_get_result($stmt_t);
            if ($res_t && ($rt = mysqli_fetch_assoc($res_t))) {
                $total_lotes_retirados = (int) ($rt['total_retirados'] ?? 0);
                $total_importe_retirados = floatval($rt['importe_retirados'] ?? 0);
            }
            mysqli_stmt_close($stmt_t);
        }
    }

    // Formatear valores
    $cantidad_compras = $datos_envio['cantidad_compras'] ?? 0;
    $cantidad_empenios_perdidos = $datos_envio['cantidad_empenios_perdidos'] ?? 0;
    $cantidad_lotes = $cantidad_compras + $cantidad_empenios_perdidos;
    $total_articulos = $datos_envio['total_articulos'] ?? 0;
    $total_oro_bruto = floatval($datos_envio['total_oro_bruto'] ?? 0);
    $total_oro_neto = floatval($datos_envio['total_oro_neto'] ?? 0);
    $total_plata_bruto = floatval($datos_envio['total_plata_bruto'] ?? 0);
    $total_plata_neto = floatval($datos_envio['total_plata_neto'] ?? 0);
    
    mysqli_close($conexion);
    
    // Retornar array con todos los datos
    return [
        'semana_actual' => $semana_actual,
        'semana_principal_desde' => $semana_principal_desde,
        'semana_principal_hasta' => $semana_principal_hasta,
        'cantidad_lotes' => $cantidad_lotes,
        'cantidad_compras' => $cantidad_compras,
        'cantidad_empenios_perdidos' => $cantidad_empenios_perdidos,
        'total_articulos' => $total_articulos,
        'total_oro_bruto' => $total_oro_bruto,
        'total_oro_neto' => $total_oro_neto,
        'total_plata_bruto' => $total_plata_bruto,
        'total_plata_neto' => $total_plata_neto,
        'semanas_lotes' => $semanas_lotes,
        'semanas_empenios_perdidos' => $semanas_empenios_perdidos,
        'semanas_desde' => $semanas_desde,
        'semanas_hasta' => $semanas_hasta,
        'solo_una_semana' => $solo_una_semana,
        'total_lotes_oro' => $total_lotes_oro,
        'precio_compra_lotes_oro' => $precio_compra_lotes_oro,
        'merma_lotes_oro' => $merma_lotes_oro,
        'cantidad_articulos_oro' => $cantidad_articulos_oro,
        'total_lotes_plata' => $total_lotes_plata,
        'precio_compra_lotes_plata' => $precio_compra_lotes_plata,
        'merma_lotes_plata' => $merma_lotes_plata,
        'cantidad_articulos_plata' => $cantidad_articulos_plata,
        'total_renovaciones' => $total_renovaciones,
        'importe_renovaciones' => $importe_renovaciones,
        'total_lotes_retirados' => $total_lotes_retirados,
        'total_importe_retirados' => $total_importe_retirados,
        'year_semana_numero' => ($anyo_listado !== '' && $anyo_listado !== null) ? (int) $anyo_listado : 0
    ];
}

/**
 * Descuenta un lote de los totales agregados de un envío.
 * Pensado para reutilizarlo al quitar/devolver/cancelar un lote vinculado a un envío.
 *
 * Actualiza en envios:
 * - cantidad_lotes, cantidad_articulos
 * - cantidad_compras o cantidad_empenios
 * - merma_oro/plata, peso_bruto_*_lotes, peso_neto_*_lotes, total_abonado_*, media_*
 *
 * @param mysqli $conexion
 * @param int $id_envio
 * @param int $id_sucursal
 * @param int $id_lote
 * @return bool true si descontó o no había nada que hacer; false si error
 */
function descontar_lote_de_envio($conexion, $id_envio, $id_sucursal, $id_lote) {
    $id_envio = (int) $id_envio;
    $id_sucursal = (int) $id_sucursal;
    $id_lote = (int) $id_lote;

    if ($id_envio <= 0 || $id_sucursal <= 0 || $id_lote <= 0) {
        return true;
    }
    if (!$conexion || !($conexion instanceof mysqli)) {
        return false;
    }

    $tabla_lotes = 'lotes_' . $id_sucursal;

    $sql_lote = "SELECT tipo_de_lote, peso, peso_bruto, precio_compra, cantidad_articulos, compra_opcion, merma
                 FROM `{$tabla_lotes}` WHERE id_lote = ? LIMIT 1";
    $stmt_lote = mysqli_prepare($conexion, $sql_lote);
    if (!$stmt_lote) {
        error_log('descontar_lote_de_envio: preparar lote: ' . mysqli_error($conexion));
        return false;
    }
    mysqli_stmt_bind_param($stmt_lote, 'i', $id_lote);
    if (!mysqli_stmt_execute($stmt_lote)) {
        error_log('descontar_lote_de_envio: ejecutar lote: ' . mysqli_stmt_error($stmt_lote));
        mysqli_stmt_close($stmt_lote);
        return false;
    }
    $res_lote = mysqli_stmt_get_result($stmt_lote);
    $lote = $res_lote ? mysqli_fetch_assoc($res_lote) : null;
    mysqli_stmt_close($stmt_lote);

    if (!$lote) {
        return false;
    }

    $sql_envio = "SELECT cantidad_lotes, cantidad_articulos, cantidad_compras, cantidad_empenios,
                         merma_oro, merma_plata,
                         peso_bruto_oro_lotes, peso_bruto_plata_lotes,
                         peso_neto_oro_lotes, peso_neto_plata_lotes,
                         total_abonado_oro, total_abonado_plata,
                         media_oro, media_plata
                  FROM envios WHERE id_envio = ? AND sucursal_remitente = ? LIMIT 1";
    $stmt_envio = mysqli_prepare($conexion, $sql_envio);
    if (!$stmt_envio) {
        error_log('descontar_lote_de_envio: preparar envio: ' . mysqli_error($conexion));
        return false;
    }
    mysqli_stmt_bind_param($stmt_envio, 'ii', $id_envio, $id_sucursal);
    if (!mysqli_stmt_execute($stmt_envio)) {
        error_log('descontar_lote_de_envio: ejecutar envio: ' . mysqli_stmt_error($stmt_envio));
        mysqli_stmt_close($stmt_envio);
        return false;
    }
    $res_envio = mysqli_stmt_get_result($stmt_envio);
    $envio = $res_envio ? mysqli_fetch_assoc($res_envio) : null;
    mysqli_stmt_close($stmt_envio);

    if (!$envio) {
        return false;
    }

    $cantidad_articulos_lote = (int) ($lote['cantidad_articulos'] ?? 0);
    $peso_neto = floatval($lote['peso'] ?? 0);
    $peso_bruto = floatval($lote['peso_bruto'] ?? 0);
    $precio_compra = floatval($lote['precio_compra'] ?? 0);
    $merma = floatval($lote['merma'] ?? 0);
    $tipo_lote = strtolower((string) ($lote['tipo_de_lote'] ?? ''));
    $es_compra = (strtolower(trim((string) ($lote['compra_opcion'] ?? 'no'))) === 'no');
    $es_oro = (strpos($tipo_lote, 'oro') !== false);
    $es_plata = (strpos($tipo_lote, 'plata') !== false);

    $cantidad_lotes = max(0, (int) ($envio['cantidad_lotes'] ?? 0) - 1);
    $cantidad_articulos = max(0, (int) ($envio['cantidad_articulos'] ?? 0) - $cantidad_articulos_lote);
    $cantidad_compras = (int) ($envio['cantidad_compras'] ?? 0);
    $cantidad_empenios = (int) ($envio['cantidad_empenios'] ?? 0);
    if ($es_compra) {
        $cantidad_compras = max(0, $cantidad_compras - 1);
    } else {
        $cantidad_empenios = max(0, $cantidad_empenios - 1);
    }

    $merma_oro = floatval($envio['merma_oro'] ?? 0);
    $merma_plata = floatval($envio['merma_plata'] ?? 0);
    $peso_bruto_oro = floatval($envio['peso_bruto_oro_lotes'] ?? 0);
    $peso_bruto_plata = floatval($envio['peso_bruto_plata_lotes'] ?? 0);
    $peso_neto_oro = floatval($envio['peso_neto_oro_lotes'] ?? 0);
    $peso_neto_plata = floatval($envio['peso_neto_plata_lotes'] ?? 0);
    $total_abonado_oro = floatval($envio['total_abonado_oro'] ?? 0);
    $total_abonado_plata = floatval($envio['total_abonado_plata'] ?? 0);

    if ($es_oro) {
        $merma_oro = max(0, $merma_oro - $merma);
        $peso_bruto_oro = max(0, $peso_bruto_oro - $peso_bruto);
        $peso_neto_oro = max(0, $peso_neto_oro - $peso_neto);
        $total_abonado_oro = max(0, $total_abonado_oro - $precio_compra);
    }
    if ($es_plata) {
        $merma_plata = max(0, $merma_plata - $merma);
        $peso_bruto_plata = max(0, $peso_bruto_plata - $peso_bruto);
        $peso_neto_plata = max(0, $peso_neto_plata - $peso_neto);
        $total_abonado_plata = max(0, $total_abonado_plata - $precio_compra);
    }

    $media_oro = ($peso_neto_oro > 0) ? round($total_abonado_oro / $peso_neto_oro, 4) : 0.0;
    $media_plata = ($peso_neto_plata > 0) ? round($total_abonado_plata / $peso_neto_plata, 4) : 0.0;

    $sql_up_envio = 'UPDATE envios SET
            cantidad_lotes = ?,
            cantidad_articulos = ?,
            cantidad_compras = ?,
            cantidad_empenios = ?,
            merma_oro = ?,
            merma_plata = ?,
            peso_bruto_oro_lotes = ?,
            peso_bruto_plata_lotes = ?,
            peso_neto_oro_lotes = ?,
            peso_neto_plata_lotes = ?,
            total_abonado_oro = ?,
            total_abonado_plata = ?,
            media_oro = ?,
            media_plata = ?
        WHERE id_envio = ? AND sucursal_remitente = ?';
    $stmt_up_envio = mysqli_prepare($conexion, $sql_up_envio);
    if (!$stmt_up_envio) {
        error_log('descontar_lote_de_envio: preparar update: ' . mysqli_error($conexion));
        return false;
    }

    mysqli_stmt_bind_param(
        $stmt_up_envio,
        'iiiiddddddddddii',
        $cantidad_lotes,
        $cantidad_articulos,
        $cantidad_compras,
        $cantidad_empenios,
        $merma_oro,
        $merma_plata,
        $peso_bruto_oro,
        $peso_bruto_plata,
        $peso_neto_oro,
        $peso_neto_plata,
        $total_abonado_oro,
        $total_abonado_plata,
        $media_oro,
        $media_plata,
        $id_envio,
        $id_sucursal
    );

    if (!mysqli_stmt_execute($stmt_up_envio)) {
        error_log('descontar_lote_de_envio: ejecutar update: ' . mysqli_stmt_error($stmt_up_envio));
        mysqli_stmt_close($stmt_up_envio);
        return false;
    }
    mysqli_stmt_close($stmt_up_envio);

    return true;
}

function obtener_datos_precio_oro_navbar() {
    $sinDatos = [
        'id_precio_oro' => 0,
        'precio' => 0.0,
        'fecha_registro' => null,
        'fecha_registro_fmt' => '—',
        'vigencia_fmt' => '—',
        'precio_base' => 0.0,
    ];

    $conexion = conectar_bd();
    if (!$conexion) {
        return $sinDatos;
    }

    $idPrecioOro = 0;
    $precioBase = null;
    $fechaRegistro = null;
    $stmtPo = mysqli_prepare(
        $conexion,
        'SELECT id, precio_gramo_18k, fecha_registro FROM precios_oro ORDER BY id DESC LIMIT 1'
    );
    if ($stmtPo) {
        mysqli_stmt_execute($stmtPo);
        $resPo = mysqli_stmt_get_result($stmtPo);
        $rowPo = $resPo ? mysqli_fetch_assoc($resPo) : null;
        mysqli_stmt_close($stmtPo);
        if ($rowPo) {
            $idPrecioOro = isset($rowPo['id']) ? (int) $rowPo['id'] : 0;
            if (isset($rowPo['precio_gramo_18k']) && $rowPo['precio_gramo_18k'] !== '') {
                $precioBase = (float) $rowPo['precio_gramo_18k'];
            }
            if (isset($rowPo['fecha_registro'])) {
                $fechaRegistro = $rowPo['fecha_registro'];
            }
        }
    }

    $porcentaje = 0.0;
    $stmtCfg = mysqli_prepare(
        $conexion,
        'SELECT porcentaje_18k FROM precios_oro_configuracion ORDER BY id_config ASC LIMIT 1'
    );
    if ($stmtCfg) {
        mysqli_stmt_execute($stmtCfg);
        $resCfg = mysqli_stmt_get_result($stmtCfg);
        $rowCfg = $resCfg ? mysqli_fetch_assoc($resCfg) : null;
        mysqli_stmt_close($stmtCfg);
        if ($rowCfg && isset($rowCfg['porcentaje_18k']) && $rowCfg['porcentaje_18k'] !== '') {
            $porcentaje = (float) $rowCfg['porcentaje_18k'];
        }
    }

    mysqli_close($conexion);

    $fechaFmt = '—';
    $vigenciaFmt = '—';
    if ($fechaRegistro !== null && $fechaRegistro !== '') {
        $fechaTexto = trim((string) $fechaRegistro);
        if ($fechaTexto !== '' && substr($fechaTexto, 0, 10) !== '0000-00-00') {
            $timestamp = strtotime($fechaTexto);
            if ($timestamp) {
                $fechaFmt = date('d/m/Y H:i', $timestamp);
                $vigenciaFmt = 'Actualizado ' . date('d.m.Y', $timestamp);
            }
        }
    }

    if ($precioBase === null || $precioBase <= 0) {
        return [
            'id_precio_oro' => $idPrecioOro,
            'precio' => 0.0,
            'fecha_registro' => $fechaRegistro,
            'fecha_registro_fmt' => $fechaFmt,
            'vigencia_fmt' => $vigenciaFmt,
            'precio_base' => $precioBase,
        ];
    }

    if ($porcentaje < 0) {
        $porcentaje = 0.0;
    }
    if ($porcentaje > 100) {
        $porcentaje = 100.0;
    }

    return [
        'id_precio_oro' => $idPrecioOro,
        'precio' => (float) floor($precioBase * (1 - $porcentaje / 100)),
        'fecha_registro' => $fechaRegistro,
        'fecha_registro_fmt' => $fechaFmt,
        'vigencia_fmt' => $vigenciaFmt,
        'precio_base' => round($precioBase, 2),
    ];
}

function obtener_precio_oro_update() {
    $datos = obtener_datos_precio_oro_navbar();

    return $datos['precio'];
}

/**
 * Consulta precios del oro en la tabla precios_oro.
 *
 * @param string|array|null $fecha   Vacío = hoy; una fecha = ese día; dos fechas (array o "desde,hasta") = rango.
 * @param string|int|null   $kilates Vacío = 18k; "todos" = todos los kilatajes; un valor concreto (18, 24k…) = ese.
 * @return array{
 *   ok: bool,
 *   error?: string,
 *   fecha_desde?: string,
 *   fecha_hasta?: string|null,
 *   es_rango?: bool,
 *   kilates?: string,
 *   campo_precio?: string|null,
 *   registros?: array<int, array<string, mixed>>,
 *   total?: int
 * }
 */
function obtener_precio_oro($fecha = '', $kilates = '') {
    $camposKilates = [
        24 => 'precio_gramo_24k',
        22 => 'precio_gramo_22k',
        21 => 'precio_gramo_21k',
        20 => 'precio_gramo_20k',
        18 => 'precio_gramo_18k',
        16 => 'precio_gramo_16k',
        14 => 'precio_gramo_14k',
        10 => 'precio_gramo_10k',
    ];

    $normalizarFecha = static function ($valor) {
        $valor = trim((string) $valor);
        if ($valor === '' || substr($valor, 0, 10) === '0000-00-00') {
            return null;
        }

        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $valor, $m)) {
            if (checkdate((int) $m[2], (int) $m[3], (int) $m[1])) {
                return sprintf('%04d-%02d-%02d', (int) $m[1], (int) $m[2], (int) $m[3]);
            }
            return null;
        }

        if (preg_match('/^(\d{1,2})[\/\-.](\d{1,2})[\/\-.](\d{4})$/', $valor, $m)) {
            $d = (int) $m[1];
            $mo = (int) $m[2];
            $y = (int) $m[3];
            if (checkdate($mo, $d, $y)) {
                return sprintf('%04d-%02d-%02d', $y, $mo, $d);
            }
            return null;
        }

        $ts = strtotime($valor);
        if ($ts) {
            return date('Y-m-d', $ts);
        }

        return null;
    };

    $fechas = [];
    if (is_array($fecha)) {
        foreach ($fecha as $f) {
            $nf = $normalizarFecha($f);
            if ($nf !== null) {
                $fechas[] = $nf;
            }
        }
    } else {
        $fechaTexto = trim((string) ($fecha ?? ''));
        if ($fechaTexto !== '') {
            if (preg_match('/[,|;]+/', $fechaTexto)) {
                $partes = preg_split('/\s*[,|;]\s*/', $fechaTexto);
                foreach ($partes as $parte) {
                    $nf = $normalizarFecha($parte);
                    if ($nf !== null) {
                        $fechas[] = $nf;
                    }
                }
            } else {
                $nf = $normalizarFecha($fechaTexto);
                if ($nf === null) {
                    return [
                        'ok' => false,
                        'error' => 'Fecha no válida.',
                    ];
                }
                $fechas[] = $nf;
            }
        }
    }

    if (count($fechas) === 0) {
        $fechaDesde = date('Y-m-d');
        $fechaHasta = null;
        $esRango = false;
    } elseif (count($fechas) === 1) {
        $fechaDesde = $fechas[0];
        $fechaHasta = null;
        $esRango = false;
    } else {
        $fechaDesde = min($fechas[0], $fechas[1]);
        $fechaHasta = max($fechas[0], $fechas[1]);
        $esRango = true;
    }

    $kilatesTexto = strtolower(trim((string) ($kilates ?? '')));
    $todosKilates = false;
    $campoPrecio = 'precio_gramo_18k';
    $kilatesResuelto = '18';

    if ($kilatesTexto === '' || $kilatesTexto === '0') {
        $campoPrecio = 'precio_gramo_18k';
        $kilatesResuelto = '18';
    } elseif (in_array($kilatesTexto, ['todos', 'all', '*'], true)) {
        $todosKilates = true;
        $campoPrecio = null;
        $kilatesResuelto = 'todos';
    } else {
        $num = null;
        if (preg_match('/(\d{1,2})/', $kilatesTexto, $mKil)) {
            $num = (int) $mKil[1];
        }
        if ($num === null || !isset($camposKilates[$num])) {
            return [
                'ok' => false,
                'error' => 'Kilates no válidos. Usa 24, 22, 21, 20, 18, 16, 14, 10 o "todos".',
            ];
        }
        $campoPrecio = $camposKilates[$num];
        $kilatesResuelto = (string) $num;
    }

    $camposSelect = [
        'id',
        'fecha_registro',
        'metal',
        'currency',
        'precio_onza',
        'precio_apertura',
        'precio_max',
        'precio_min',
        'variacion',
        'variacion_pct',
        'timestamp_api',
    ];
    if ($todosKilates) {
        $camposSelect = array_merge($camposSelect, array_values($camposKilates));
    } else {
        $camposSelect[] = $campoPrecio;
    }
    $sqlCampos = implode(', ', $camposSelect);

    $conexion = conectar_bd();
    if (!$conexion) {
        return [
            'ok' => false,
            'error' => 'No se pudo conectar a la base de datos.',
        ];
    }

    if ($esRango) {
        $sql = "SELECT {$sqlCampos}
                FROM precios_oro
                WHERE DATE(fecha_registro) BETWEEN ? AND ?
                ORDER BY fecha_registro ASC, id ASC";
        $stmt = mysqli_prepare($conexion, $sql);
        if (!$stmt) {
            $err = mysqli_error($conexion);
            mysqli_close($conexion);
            return [
                'ok' => false,
                'error' => 'Error preparando consulta: ' . $err,
            ];
        }
        mysqli_stmt_bind_param($stmt, 'ss', $fechaDesde, $fechaHasta);
    } else {
        $sql = "SELECT {$sqlCampos}
                FROM precios_oro
                WHERE DATE(fecha_registro) = ?
                ORDER BY id DESC
                LIMIT 1";
        $stmt = mysqli_prepare($conexion, $sql);
        if (!$stmt) {
            $err = mysqli_error($conexion);
            mysqli_close($conexion);
            return [
                'ok' => false,
                'error' => 'Error preparando consulta: ' . $err,
            ];
        }
        mysqli_stmt_bind_param($stmt, 's', $fechaDesde);
    }

    if (!mysqli_stmt_execute($stmt)) {
        $err = mysqli_stmt_error($stmt);
        mysqli_stmt_close($stmt);
        mysqli_close($conexion);
        return [
            'ok' => false,
            'error' => 'Error ejecutando consulta: ' . $err,
        ];
    }

    $result = mysqli_stmt_get_result($stmt);
    $registros = [];
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $item = [
                'id' => isset($row['id']) ? (int) $row['id'] : 0,
                'fecha_registro' => $row['fecha_registro'] ?? null,
                'fecha' => isset($row['fecha_registro']) ? substr((string) $row['fecha_registro'], 0, 10) : null,
                'metal' => $row['metal'] ?? null,
                'currency' => $row['currency'] ?? null,
                'precio_onza' => isset($row['precio_onza']) ? (float) $row['precio_onza'] : null,
                'precio_apertura' => isset($row['precio_apertura']) ? (float) $row['precio_apertura'] : null,
                'precio_max' => isset($row['precio_max']) ? (float) $row['precio_max'] : null,
                'precio_min' => isset($row['precio_min']) ? (float) $row['precio_min'] : null,
                'variacion' => isset($row['variacion']) ? (float) $row['variacion'] : null,
                'variacion_pct' => isset($row['variacion_pct']) ? (float) $row['variacion_pct'] : null,
                'timestamp_api' => isset($row['timestamp_api']) ? (int) $row['timestamp_api'] : null,
            ];

            if ($todosKilates) {
                $precios = [];
                foreach ($camposKilates as $k => $campo) {
                    $precios[(string) $k] = isset($row[$campo]) ? (float) $row[$campo] : null;
                    $item[$campo] = isset($row[$campo]) ? (float) $row[$campo] : null;
                }
                $item['precios_kilates'] = $precios;
            } else {
                $precio = isset($row[$campoPrecio]) ? (float) $row[$campoPrecio] : null;
                $item['kilates'] = $kilatesResuelto;
                $item['campo_precio'] = $campoPrecio;
                $item['precio'] = $precio;
                $item[$campoPrecio] = $precio;
            }

            $registros[] = $item;
        }
    }

    mysqli_stmt_close($stmt);
    mysqli_close($conexion);

    return [
        'ok' => true,
        'fecha_desde' => $fechaDesde,
        'fecha_hasta' => $fechaHasta,
        'es_rango' => $esRango,
        'kilates' => $kilatesResuelto,
        'campo_precio' => $campoPrecio,
        'registros' => $registros,
        'total' => count($registros),
    ];
}

/**
 * Primera palabra del nombre de proveedor (para badge navbar).
 *
 * @param string $nombre
 * @return string
 */
function nombre_proveedor_primera_palabra($nombre) {
    $nombre = trim((string) $nombre);
    if ($nombre === '') {
        return '—';
    }
    $partes = preg_split('/\s+/u', $nombre, 2);

    return isset($partes[0]) && $partes[0] !== '' ? $partes[0] : '—';
}

/**
 * Formatea fecha de BD a DD/MM/YYYY para inputs del modal.
 *
 * @param string|null $fecha
 * @return string
 */
function formatear_fecha_dmY_desde_db($fecha) {
    if ($fecha === null || $fecha === '') {
        return '';
    }

    $fechaTexto = trim((string) $fecha);
    if ($fechaTexto === '' || substr($fechaTexto, 0, 10) === '0000-00-00') {
        return '';
    }

    $timestamp = strtotime($fechaTexto);
    if (!$timestamp) {
        return '';
    }

    return date('d/m/Y', $timestamp);
}

/**
 * Parsea DD/MM/YYYY a YYYY-MM-DD para guardar en BD.
 *
 * @param string $fecha
 * @return string|null
 */
function parse_fecha_dmY_a_ymd($fecha) {
    $fecha = trim((string) $fecha);
    if ($fecha === '') {
        return null;
    }

    $partes = explode('/', $fecha);
    if (count($partes) !== 3) {
        return null;
    }

    $d = (int) $partes[0];
    $m = (int) $partes[1];
    $y = (int) $partes[2];

    if ($y < 1000 || $m < 1 || $m > 12 || $d < 1 || $d > 31) {
        return null;
    }

    if (!checkdate($m, $d, $y)) {
        return null;
    }

    return sprintf('%04d-%02d-%02d', $y, $m, $d);
}

/**
 * Último precio oro proveedor a ley 0,725 (derivado de 24k si falta).
 *
 * @param int $id_proveedor
 * @return array{precio_gramo_proveedor: float, fecha_standby: string|null, precio_gramo_24k: float, precio_gramo_0725: float}
 */
function obtenerPrecioOroProveedor($id_proveedor) {
    $sinDatos = [
        'precio_gramo_proveedor' => 0.0,
        'fecha_standby' => null,
        'precio_gramo_24k' => 0.0,
        'precio_gramo_0725' => 0.0,
    ];

    $id_proveedor = (int) $id_proveedor;
    if ($id_proveedor <= 0) {
        return $sinDatos;
    }

    $conexion = conectar_bd();
    if (!$conexion) {
        return $sinDatos;
    }

    $stmt = mysqli_prepare(
        $conexion,
        'SELECT precio_gramo_24k, precio_gramo_0725, fecha_standby
         FROM precios_oro_proveedores
         WHERE proveedor_id = ?
         ORDER BY id DESC
         LIMIT 1'
    );

    if (!$stmt) {
        mysqli_close($conexion);
        return $sinDatos;
    }

    mysqli_stmt_bind_param($stmt, 'i', $id_proveedor);
    mysqli_stmt_execute($stmt);
    $resPrecio = mysqli_stmt_get_result($stmt);
    $rowPrecio = $resPrecio ? mysqli_fetch_assoc($resPrecio) : null;
    if ($resPrecio) {
        mysqli_free_result($resPrecio);
    }
    mysqli_stmt_close($stmt);
    mysqli_close($conexion);

    if (!$rowPrecio) {
        return $sinDatos;
    }

    $precio_gramo_24k = isset($rowPrecio['precio_gramo_24k']) && $rowPrecio['precio_gramo_24k'] !== ''
        ? (float) $rowPrecio['precio_gramo_24k']
        : 0.0;
    $precio_gramo_0725 = isset($rowPrecio['precio_gramo_0725']) && $rowPrecio['precio_gramo_0725'] !== ''
        ? (float) $rowPrecio['precio_gramo_0725']
        : 0.0;
    if ($precio_gramo_0725 <= 0 && $precio_gramo_24k > 0) {
        $precio_gramo_0725 = round($precio_gramo_24k * 0.725, 4);
    }
    $fecha_standby = isset($rowPrecio['fecha_standby']) && $rowPrecio['fecha_standby'] !== ''
        ? $rowPrecio['fecha_standby']
        : null;

    return [
        'precio_gramo_proveedor' => $precio_gramo_0725,
        'fecha_standby' => $fecha_standby,
        'precio_gramo_24k' => $precio_gramo_24k,
        'precio_gramo_0725' => $precio_gramo_0725,
    ];
}

/**
 * Proveedores de fundición con último precio oro 24k para el modal del navbar.
 *
 * @return array<int, array<string, mixed>>
 */
function obtener_proveedores_fundicion_modal_precio_oro() {
    $items = [];
    $conexion = conectar_bd();
    if (!$conexion) {
        return $items;
    }

    $proveedores = [];
    $resProv = mysqli_query(
        $conexion,
        "SELECT id_proveedor, nombre_proveedor
         FROM proveedores
         WHERE fundicion = 'true'
         ORDER BY nombre_proveedor ASC"
    );
    if ($resProv) {
        while ($rowProv = mysqli_fetch_assoc($resProv)) {
            $proveedores[] = $rowProv;
        }
        mysqli_free_result($resProv);
    }

    $stmtPrecio = mysqli_prepare(
        $conexion,
        'SELECT precio_gramo_24k, precio_gramo_0725, fecha_standby
         FROM precios_oro_proveedores
         WHERE proveedor_id = ?
         ORDER BY id DESC
         LIMIT 1'
    );

    foreach ($proveedores as $prov) {
        $idProveedor = isset($prov['id_proveedor']) ? (int) $prov['id_proveedor'] : 0;
        if ($idProveedor <= 0) {
            continue;
        }

        $precioFmt = '';
        $fechaStandbyFmt = '';

        if ($stmtPrecio) {
            mysqli_stmt_bind_param($stmtPrecio, 'i', $idProveedor);
            mysqli_stmt_execute($stmtPrecio);
            $resPrecio = mysqli_stmt_get_result($stmtPrecio);
            $rowPrecio = $resPrecio ? mysqli_fetch_assoc($resPrecio) : null;
            if ($resPrecio) {
                mysqli_free_result($resPrecio);
            }

            if ($rowPrecio) {
                $precio24k = isset($rowPrecio['precio_gramo_24k']) && $rowPrecio['precio_gramo_24k'] !== ''
                    ? (float) $rowPrecio['precio_gramo_24k']
                    : 0.0;
                $precio0725 = isset($rowPrecio['precio_gramo_0725']) && $rowPrecio['precio_gramo_0725'] !== ''
                    ? (float) $rowPrecio['precio_gramo_0725']
                    : 0.0;
                if ($precio0725 <= 0 && $precio24k > 0) {
                    $precio0725 = round($precio24k * 0.725, 4);
                }
                // El modal sigue editando 24k; se muestra el 24k actual.
                if ($precio24k > 0) {
                    $precioFmt = number_format($precio24k, 2, ',', '.');
                }
                $fechaStandbyFmt = formatear_fecha_dmY_desde_db($rowPrecio['fecha_standby'] ?? null);
            }
        }

        $items[] = [
            'id_proveedor' => $idProveedor,
            'nombre_proveedor' => isset($prov['nombre_proveedor']) ? (string) $prov['nombre_proveedor'] : '',
            'precio_fmt' => $precioFmt,
            'fecha_standby_fmt' => $fechaStandbyFmt,
        ];
    }

    if ($stmtPrecio) {
        mysqli_stmt_close($stmtPrecio);
    }
    mysqli_close($conexion);

    return $items;
}

/**
 * Precios oro 0,725 por proveedor de fundición para el badge del navbar.
 *
 * @return array{proveedores: array<int, array<string, mixed>>, ids_signature: string}
 */
function obtener_datos_precio_oro_proveedores_navbar() {
    $sinDatos = [
        'proveedores' => [],
        'ids_signature' => '',
    ];

    $conexion = conectar_bd();
    if (!$conexion) {
        return $sinDatos;
    }

    $proveedores = [];
    $resProv = mysqli_query(
        $conexion,
        "SELECT id_proveedor, nombre_proveedor
         FROM proveedores
         WHERE fundicion = 'true'
         ORDER BY nombre_proveedor ASC"
    );
    if ($resProv) {
        while ($rowProv = mysqli_fetch_assoc($resProv)) {
            $proveedores[] = $rowProv;
        }
        mysqli_free_result($resProv);
    }

    $items = [];
    $signatureParts = [];

    $stmtPrecio = mysqli_prepare(
        $conexion,
        'SELECT id, precio_gramo_24k, precio_gramo_0725, fecha_registro
         FROM precios_oro_proveedores
         WHERE proveedor_id = ?
         ORDER BY id DESC
         LIMIT 1'
    );

    foreach ($proveedores as $prov) {
        $idProveedor = isset($prov['id_proveedor']) ? (int) $prov['id_proveedor'] : 0;
        if ($idProveedor <= 0 || !$stmtPrecio) {
            continue;
        }

        mysqli_stmt_bind_param($stmtPrecio, 'i', $idProveedor);
        mysqli_stmt_execute($stmtPrecio);
        $resPrecio = mysqli_stmt_get_result($stmtPrecio);
        $rowPrecio = $resPrecio ? mysqli_fetch_assoc($resPrecio) : null;
        if ($resPrecio) {
            mysqli_free_result($resPrecio);
        }

        if (!$rowPrecio) {
            continue;
        }

        $idPrecio = isset($rowPrecio['id']) ? (int) $rowPrecio['id'] : 0;
        $precio24k = isset($rowPrecio['precio_gramo_24k']) && $rowPrecio['precio_gramo_24k'] !== ''
            ? (float) $rowPrecio['precio_gramo_24k']
            : 0.0;
        $precio0725 = isset($rowPrecio['precio_gramo_0725']) && $rowPrecio['precio_gramo_0725'] !== ''
            ? (float) $rowPrecio['precio_gramo_0725']
            : 0.0;
        if ($precio0725 <= 0 && $precio24k > 0) {
            $precio0725 = round($precio24k * 0.725, 4);
        }
        $fechaRegistro = isset($rowPrecio['fecha_registro']) ? $rowPrecio['fecha_registro'] : null;

        $fechaFmt = '—';
        if ($fechaRegistro !== null && $fechaRegistro !== '') {
            $fechaTexto = trim((string) $fechaRegistro);
            if ($fechaTexto !== '' && substr($fechaTexto, 0, 10) !== '0000-00-00') {
                $timestamp = strtotime($fechaTexto);
                if ($timestamp) {
                    $fechaFmt = date('d.m.Y', $timestamp);
                }
            }
        }

        $nombreCorto = nombre_proveedor_primera_palabra($prov['nombre_proveedor'] ?? '');
        $precioFmt = number_format($precio0725, 2, ',', '.');
        $nombreEsc = htmlspecialchars($nombreCorto, ENT_QUOTES, 'UTF-8');
        $precioEsc = htmlspecialchars($precioFmt, ENT_QUOTES, 'UTF-8');
        $fechaEsc = htmlspecialchars($fechaFmt, ENT_QUOTES, 'UTF-8');
        $textoPanel = $nombreEsc . ' <span id="precio_24k_proveedor">' . $precioEsc . '</span>€ ' . $fechaEsc;

        $items[] = [
            'id_proveedor' => $idProveedor,
            'id_precio' => $idPrecio,
            'nombre_corto' => $nombreCorto,
            'precio_gramo_24k' => $precio24k,
            'precio_gramo_0725' => $precio0725,
            'precio_fmt' => $precioFmt,
            'fecha_registro' => $fechaRegistro,
            'fecha_registro_fmt' => $fechaFmt,
            'texto_panel' => $textoPanel,
        ];
        $signatureParts[] = $idProveedor . '-' . $idPrecio;
    }

    if ($stmtPrecio) {
        mysqli_stmt_close($stmtPrecio);
    }
    mysqli_close($conexion);

    return [
        'proveedores' => $items,
        'ids_signature' => implode('|', $signatureParts),
    ];
}

/**
 * Empeños perdibles vencidos con cuotas vencidas >= valor_meses_perdidos_empenos (misma regla que el cron).
 *
 * @param int $id_sucursal
 * @return int
 */
function empenos_por_perder($id_sucursal)
{
    $id_sucursal = (int) $id_sucursal;
    if ($id_sucursal <= 0) {
        return 0;
    }

    $tabla_lotes = 'lotes_' . $id_sucursal;
    $tabla_historico = 'historico_renovaciones_' . $id_sucursal;

    if (!preg_match('/^lotes_\d+$/', $tabla_lotes) || !preg_match('/^historico_renovaciones_\d+$/', $tabla_historico)) {
        return 0;
    }

    $conexion = conectar_bd();
    if (!$conexion) {
        return 0;
    }

    $valor_meses = 0;
    $stmt_sucursal = mysqli_prepare(
        $conexion,
        'SELECT valor_meses_perdidos_empenos FROM sucursal WHERE id_sucursal = ? LIMIT 1'
    );

    if ($stmt_sucursal) {
        mysqli_stmt_bind_param($stmt_sucursal, 'i', $id_sucursal);
        mysqli_stmt_execute($stmt_sucursal);
        $fila_sucursal = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_sucursal));
        mysqli_stmt_close($stmt_sucursal);

        if ($fila_sucursal && isset($fila_sucursal['valor_meses_perdidos_empenos'])) {
            $valor_meses = (int) $fila_sucursal['valor_meses_perdidos_empenos'];
        }
    }

    if ($valor_meses <= 0) {
        mysqli_close($conexion);
        return 0;
    }

    $sql = "SELECT COUNT(*) AS total_lotes_por_perder
            FROM `{$tabla_lotes}` l
            INNER JOIN (
                SELECT lote, COUNT(id_renovaciones) AS total_vencidas
                FROM `{$tabla_historico}`
                WHERE estado_historico = 'Vencido'
                GROUP BY lote
            ) hv ON hv.lote = l.id_lote
            WHERE l.estado_lote = 'vencido'
              AND l.lote_perdible = 'true'
              AND hv.total_vencidas >= ?";

    $total_lotes_por_perder = 0;
    $stmt = mysqli_prepare($conexion, $sql);

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'i', $valor_meses);
        if (mysqli_stmt_execute($stmt)) {
            $fila = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
            $total_lotes_por_perder = $fila ? (int) $fila['total_lotes_por_perder'] : 0;
        }
        mysqli_stmt_close($stmt);
    }

    mysqli_close($conexion);

    return $total_lotes_por_perder;
}

/**
 * Lotes del último mes cuya cantidad de artículos descritos no coincide con cantidad_articulos.
 *
 * @param int $id_sucursal
 * @return int
 */
function lotes_sin_describir($id_sucursal)
{
    $id_sucursal = (int) $id_sucursal;
    if ($id_sucursal <= 0) {
        return 0;
    }

    $tabla_lotes = 'lotes_' . $id_sucursal;
    $tabla_articulos = 'articulos_' . $id_sucursal;

    if (!preg_match('/^lotes_\d+$/', $tabla_lotes) || !preg_match('/^articulos_\d+$/', $tabla_articulos)) {
        return 0;
    }

    $conexion = conectar_bd();
    if (!$conexion) {
        return 0;
    }

    $sql = "SELECT COUNT(l.id_lote) AS total_lotes_sin_describir
            FROM `{$tabla_lotes}` l
            LEFT JOIN (
                SELECT id_lote_articulos, COUNT(id_articulo) AS total_descritos
                FROM `{$tabla_articulos}`
                GROUP BY id_lote_articulos
            ) art ON art.id_lote_articulos = l.id_lote
            WHERE l.fecha_compra >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)
              AND COALESCE(art.total_descritos, 0) < l.cantidad_articulos";

    $total_lotes_sin_describir = 0;
    $resultado = mysqli_query($conexion, $sql);

    if ($resultado) {
        $fila = mysqli_fetch_assoc($resultado);
        $total_lotes_sin_describir = $fila ? (int) $fila['total_lotes_sin_describir'] : 0;
        mysqli_free_result($resultado);
    }

    mysqli_close($conexion);

    return $total_lotes_sin_describir;
}

/**
 * Detalle de lotes sin describir para el dashboard de sucursal.
 *
 * @param int $id_sucursal
 * @return array<int, array{id_lote: int, identificador: int, cantidad_articulos: int, sin_describir: int, fecha_compra: string}>
 */
function lotes_sin_describir_listado($id_sucursal)
{
    $id_sucursal = (int) $id_sucursal;
    if ($id_sucursal <= 0) {
        return [];
    }

    $tabla_lotes = 'lotes_' . $id_sucursal;
    $tabla_articulos = 'articulos_' . $id_sucursal;

    if (!preg_match('/^lotes_\d+$/', $tabla_lotes) || !preg_match('/^articulos_\d+$/', $tabla_articulos)) {
        return [];
    }

    $conexion = conectar_bd();
    if (!$conexion) {
        return [];
    }

    $sql = "SELECT
                l.id_lote,
                l.identificador,
                l.cantidad_articulos,
                l.fecha_compra,
                COALESCE(art.total_descritos, 0) AS articulos_descritos
            FROM `{$tabla_lotes}` l
            LEFT JOIN (
                SELECT id_lote_articulos, COUNT(id_articulo) AS total_descritos
                FROM `{$tabla_articulos}`
                GROUP BY id_lote_articulos
            ) art ON art.id_lote_articulos = l.id_lote
            WHERE l.fecha_compra >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)
              AND COALESCE(art.total_descritos, 0) < l.cantidad_articulos
            ORDER BY l.fecha_compra DESC, l.id_lote DESC";

    $lotes = [];
    $resultado = mysqli_query($conexion, $sql);

    if ($resultado) {
        while ($fila = mysqli_fetch_assoc($resultado)) {
            $cantidad_articulos = (int) ($fila['cantidad_articulos'] ?? 0);
            $articulos_descritos = (int) ($fila['articulos_descritos'] ?? 0);
            $sin_describir = max(0, $cantidad_articulos - $articulos_descritos);

            $lotes[] = [
                'id_lote' => (int) ($fila['id_lote'] ?? 0),
                'identificador' => (int) ($fila['identificador'] ?? 0),
                'cantidad_articulos' => $cantidad_articulos,
                'sin_describir' => $sin_describir,
                'fecha_compra' => (string) ($fila['fecha_compra'] ?? ''),
            ];
        }
        mysqli_free_result($resultado);
    }

    mysqli_close($conexion);

    return $lotes;
}

/**
 * Lotes y empeños de la sucursal con estado_envio = pendiente_enviar.
 *
 * @param int $id_sucursal
 * @return int
 */
function lotes_pendientes_enviar($id_sucursal)
{
    $id_sucursal = (int) $id_sucursal;
    if ($id_sucursal <= 0) {
        return 0;
    }

    $tabla_lotes = 'lotes_' . $id_sucursal;
    if (!preg_match('/^lotes_\d+$/', $tabla_lotes)) {
        return 0;
    }

    $conexion = conectar_bd();
    if (!$conexion) {
        return 0;
    }

    $sql = "SELECT COUNT(l.id_lote) AS total
            FROM `{$tabla_lotes}` l
            WHERE l.estado_envio = 'pendiente_enviar'";

    $total = 0;
    $resultado = mysqli_query($conexion, $sql);
    if ($resultado) {
        $fila = mysqli_fetch_assoc($resultado);
        $total = $fila ? (int) $fila['total'] : 0;
        mysqli_free_result($resultado);
    }

    mysqli_close($conexion);

    return $total;
}

/**
 * Detalle de lotes y empeños pendientes de enviar para el dashboard de sucursal.
 *
 * @param int $id_sucursal
 * @return array<int, array{id_lote: int, identificador: int, fecha_compra: string, peso: float, precio_compra: float, estado_envio: string}>
 */
function lotes_pendientes_enviar_listado($id_sucursal)
{
    $id_sucursal = (int) $id_sucursal;
    if ($id_sucursal <= 0) {
        return [];
    }

    $tabla_lotes = 'lotes_' . $id_sucursal;
    if (!preg_match('/^lotes_\d+$/', $tabla_lotes)) {
        return [];
    }

    $conexion = conectar_bd();
    if (!$conexion) {
        return [];
    }

    $sql = "SELECT
                l.id_lote,
                l.identificador,
                l.fecha_compra,
                l.peso,
                l.precio_compra,
                l.estado_envio
            FROM `{$tabla_lotes}` l
            WHERE l.estado_envio = 'pendiente_enviar'
            ORDER BY l.fecha_compra DESC, l.id_lote DESC";

    $lotes = [];
    $resultado = mysqli_query($conexion, $sql);

    if ($resultado) {
        while ($fila = mysqli_fetch_assoc($resultado)) {
            $lotes[] = [
                'id_lote' => (int) ($fila['id_lote'] ?? 0),
                'identificador' => (int) ($fila['identificador'] ?? 0),
                'fecha_compra' => (string) ($fila['fecha_compra'] ?? ''),
                'peso' => (float) ($fila['peso'] ?? 0),
                'precio_compra' => (float) ($fila['precio_compra'] ?? 0),
                'estado_envio' => (string) ($fila['estado_envio'] ?? ''),
            ];
        }
        mysqli_free_result($resultado);
    }

    mysqli_close($conexion);

    return $lotes;
}

/**
 * Inserta una fila en reporte_ventas (reporting desnormalizado por artículo vendido).
 *
 * @return int id_reporte_ventas insertado, o 0 si falla
 */
function insert_reporte_ventas(
    $id_articulo,
    $id_sucursal_venta,
    $descripcion_articulo,
    $id_venta_rel,
    $identificador_venta,
    $precio_articulo,
    $peso_articulo,
    $articulo_web,
    $tipo_metal_articulo,
    $venta_plazos,
    $numero_plazos,
    $tipo_pago,
    $cantidad_contado,
    $cantidad_tarjeta,
    $cantidad_transferencia,
    $cantidad_bizum,
    $usuario_venta,
    $fecha_venta,
    $coste_articulo_venta
) {
    $conexion = conectar_bd();
    if (!$conexion) {
        return 0;
    }

    $id_articulo = (int) $id_articulo;
    $id_sucursal_venta = (int) $id_sucursal_venta;
    $descripcion_articulo = (string) $descripcion_articulo;
    $id_venta_rel = (int) $id_venta_rel;
    $identificador_venta = (int) $identificador_venta;
    $precio_articulo = is_numeric($precio_articulo) ? (float) $precio_articulo : 0.0;
    $peso_articulo = is_numeric($peso_articulo) ? (float) $peso_articulo : 0.0;
    $articulo_web = (string) $articulo_web;
    $tipo_metal_articulo = strtolower(trim((string) $tipo_metal_articulo));
    if ($tipo_metal_articulo !== 'plata') {
        $tipo_metal_articulo = 'oro';
    }
    $venta_plazos = (strtolower(trim((string) $venta_plazos)) === 'si') ? 'si' : 'no';
    $numero_plazos = (int) $numero_plazos;
    $tipo_pago = strtolower(trim((string) $tipo_pago));
    $tipos_pago_ok = array('contado', 'bizum', 'combinado', 'transferencia', 'tarjeta');
    if (!in_array($tipo_pago, $tipos_pago_ok, true)) {
        $tipo_pago = 'contado';
    }
    $cantidad_contado = is_numeric($cantidad_contado) ? (float) $cantidad_contado : 0.0;
    $cantidad_tarjeta = is_numeric($cantidad_tarjeta) ? (float) $cantidad_tarjeta : 0.0;
    $cantidad_transferencia = is_numeric($cantidad_transferencia) ? (float) $cantidad_transferencia : 0.0;
    $cantidad_bizum = is_numeric($cantidad_bizum) ? (float) $cantidad_bizum : 0.0;
    $usuario_venta = (int) $usuario_venta;
    $fecha_venta = (string) $fecha_venta;
    $coste_articulo_venta = is_numeric($coste_articulo_venta) ? (float) $coste_articulo_venta : 0.0;

    $nombre_sucursal_venta = '';
    $stmtSuc = mysqli_prepare(
        $conexion,
        'SELECT nombre_sucursal FROM sucursal WHERE id_sucursal = ? LIMIT 1'
    );
    if ($stmtSuc) {
        mysqli_stmt_bind_param($stmtSuc, 'i', $id_sucursal_venta);
        if (mysqli_stmt_execute($stmtSuc)) {
            $resSuc = mysqli_stmt_get_result($stmtSuc);
            if ($resSuc) {
                $rowSuc = mysqli_fetch_assoc($resSuc);
                if ($rowSuc && isset($rowSuc['nombre_sucursal'])) {
                    $nombre_sucursal_venta = (string) $rowSuc['nombre_sucursal'];
                }
                mysqli_free_result($resSuc);
            }
        }
        mysqli_stmt_close($stmtSuc);
    }

    $sql = 'INSERT INTO reporte_ventas (
            id_articulo,
            id_sucursal_venta,
            nombre_sucursal_venta,
            descripcion_articulo,
            id_venta_rel,
            identificador_venta,
            precio_articulo,
            peso_articulo,
            articulo_web,
            tipo_metal_articulo,
            venta_plazos,
            numero_plazos,
            tipo_pago,
            cantidad_contado,
            cantidad_tarjeta,
            cantidad_transferencia,
            cantidad_bizum,
            fecha_venta,
            usuario_venta,
            coste_articulo_venta
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
        )';

    $stmt = mysqli_prepare($conexion, $sql);
    if (!$stmt) {
        mysqli_close($conexion);
        return 0;
    }

    mysqli_stmt_bind_param(
        $stmt,
        'iissiiddsssisddddsid',
        $id_articulo,
        $id_sucursal_venta,
        $nombre_sucursal_venta,
        $descripcion_articulo,
        $id_venta_rel,
        $identificador_venta,
        $precio_articulo,
        $peso_articulo,
        $articulo_web,
        $tipo_metal_articulo,
        $venta_plazos,
        $numero_plazos,
        $tipo_pago,
        $cantidad_contado,
        $cantidad_tarjeta,
        $cantidad_transferencia,
        $cantidad_bizum,
        $fecha_venta,
        $usuario_venta,
        $coste_articulo_venta
    );

    $ok = mysqli_stmt_execute($stmt);
    $id_insertado = $ok ? (int) mysqli_insert_id($conexion) : 0;
    mysqli_stmt_close($stmt);
    mysqli_close($conexion);

    return $id_insertado;
}

require_once __DIR__ . '/informe_diario_actualizar.php';

if (!defined('APP_NAME')) {
    $app_data = obtener_datos_app(defined('APP_ID') ? APP_ID : null);
    $app_name = ($app_data && !empty($app_data['name_app'])) ? $app_data['name_app'] : 'TPV Quinta Gracia';
    define('APP_NAME', $app_name);
}

require_once __DIR__ . '/fiskaly/fiskaly_functions.php';
?>