<?php

/**
 * Helpers para procesar facturas descargadas por FTP (lógica del repositorio OCR).
 */

/**
 * @return array<int, string>
 */
function cron_ftp_extensiones_factura_permitidas()
{
    return array('pdf', 'jpg', 'jpeg', 'png', 'gif', 'xls', 'xlsx');
}

/**
 * @param string $nombreArchivo
 * @return bool
 */
function cron_ftp_es_factura_permitida($nombreArchivo)
{
    $extension = strtolower(pathinfo($nombreArchivo, PATHINFO_EXTENSION));
    return in_array($extension, cron_ftp_extensiones_factura_permitidas(), true);
}

/**
 * @return string
 */
function cron_ftp_registro_procesados_path()
{
    $dir = __DIR__ . '/data';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    return $dir . '/ftp_facturas_procesadas.json';
}

/**
 * @return array<string, mixed>
 */
function cron_ftp_cargar_registro_procesados()
{
    $path = cron_ftp_registro_procesados_path();
    if (!is_file($path)) {
        return array('files' => array());
    }

    $json = file_get_contents($path);
    if ($json === false || $json === '') {
        return array('files' => array());
    }

    $data = json_decode($json, true);
    if (!is_array($data) || !isset($data['files']) || !is_array($data['files'])) {
        return array('files' => array());
    }

    return $data;
}

/**
 * @param array<string, mixed> $data
 * @return void
 */
function cron_ftp_guardar_registro_procesados(array $data)
{
    $path = cron_ftp_registro_procesados_path();
    file_put_contents(
        $path,
        json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
        LOCK_EX
    );
}

/**
 * @param string $nombreFtp
 * @param int $size
 * @return string
 */
function cron_ftp_clave_registro($nombreFtp, $size)
{
    return strtolower(trim($nombreFtp)) . '|' . (int) $size;
}

/**
 * @param string $nombreFtp
 * @param int $size
 * @return bool
 */
function cron_ftp_ya_procesado($nombreFtp, $size)
{
    $data = cron_ftp_cargar_registro_procesados();
    $clave = cron_ftp_clave_registro($nombreFtp, $size);
    return isset($data['files'][$clave]);
}

/**
 * @param string $nombreFtp
 * @param int $size
 * @param array<string, mixed> $info
 * @return void
 */
function cron_ftp_marcar_procesado($nombreFtp, $size, array $info)
{
    $data = cron_ftp_cargar_registro_procesados();
    $clave = cron_ftp_clave_registro($nombreFtp, $size);
    $data['files'][$clave] = array_merge(
        array(
            'nombre_ftp' => $nombreFtp,
            'size' => (int) $size,
            'processed_at' => date('Y-m-d H:i:s'),
        ),
        $info
    );
    cron_ftp_guardar_registro_procesados($data);
}

/**
 * Guarda una factura local (descargada por FTP) en photos/.
 *
 * @param string $rutaLocal
 * @param string $nombreOriginal
 * @return string|null
 */
function cron_guardar_archivo_factura_desde_ruta($rutaLocal, $nombreOriginal)
{
    if (!is_file($rutaLocal)) {
        return null;
    }

    $tipos_permitidos = array(
        'image/jpeg', 'image/jpg', 'image/png', 'image/gif',
        'application/pdf',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    );

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $tipo_mime = finfo_file($finfo, $rutaLocal);
    finfo_close($finfo);

    if (!in_array($tipo_mime, $tipos_permitidos, true)) {
        throw new Exception('Tipo de archivo no permitido: ' . $nombreOriginal);
    }

    $size = filesize($rutaLocal);
    if ($size === false || $size > 10 * 1024 * 1024) {
        throw new Exception('Archivo demasiado grande (máx. 10 MB): ' . $nombreOriginal);
    }

    $extension = strtolower(pathinfo($nombreOriginal, PATHINFO_EXTENSION));
    $directorio = dirname(__DIR__) . '/photos/';
    if (!is_dir($directorio) && !mkdir($directorio, 0755, true)) {
        throw new Exception('No se pudo crear el directorio de fotos');
    }

    if ($tipo_mime === 'application/pdf') {
        $nombre_archivo = generarNombrePDF($nombreOriginal, $extension);
        $ruta = $directorio . $nombre_archivo;
        if (!copy($rutaLocal, $ruta)) {
            throw new Exception('Error al guardar el PDF');
        }
        return $nombre_archivo;
    }

    if (in_array($tipo_mime, array('application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'), true)) {
        $nombre_archivo = generarNombreUnico() . '.' . ($extension ?: 'xlsx');
        $ruta = $directorio . $nombre_archivo;
        if (!copy($rutaLocal, $ruta)) {
            throw new Exception('Error al guardar el Excel');
        }
        return $nombre_archivo;
    }

    do {
        $nombre_archivo = generarNombreUnico() . '.' . $extension;
        $ruta = $directorio . $nombre_archivo;
    } while (file_exists($ruta));

    $imagen = procesarYRedimensionarImagen($rutaLocal, $extension);
    if (!$imagen) {
        throw new Exception('Error al procesar la imagen');
    }
    if (!file_put_contents($ruta, $imagen)) {
        throw new Exception('Error al guardar la imagen');
    }

    return $nombre_archivo;
}

/**
 * Procesa una factura descargada por FTP con la misma lógica del repositorio.
 *
 * @param mysqli $conexion
 * @param string $rutaLocal
 * @param string $nombreOriginal
 * @param int $usuarioId
 * @param int $sucursalId
 * @return array<string, mixed>
 */
function cron_procesar_factura_ftp($conexion, $rutaLocal, $nombreOriginal, $usuarioId, $sucursalId)
{
    $mimeArchivo = gemini_detectar_mime_factura($rutaLocal);
    $datosOcr = leer_factura_proveedor_archivos(array(
        array(
            'ruta' => $rutaLocal,
            'mime' => $mimeArchivo,
            'etiqueta' => 'Factura FTP cron',
        ),
    ));

    if ($datosOcr === false) {
        $mensaje = gemini_ultimo_error();
        if ($mensaje === '') {
            $mensaje = 'No se pudo leer la factura';
        }
        return array(
            'success' => false,
            'auto_guardado' => false,
            'estado_digitalizado' => 'noprocesado',
            'message' => $mensaje,
        );
    }

    $resuelto = ocr_resolver_entidades_desde_datos($conexion, $datosOcr);

    $empresaId = !empty($resuelto['empresa']['id_empresa']) ? (int) $resuelto['empresa']['id_empresa'] : 0;
    $proveedorId = !empty($resuelto['proveedor']['id_proveedor']) ? (int) $resuelto['proveedor']['id_proveedor'] : 0;

    $lineas = isset($datosOcr['lineas']) && is_array($datosOcr['lineas']) ? $datosOcr['lineas'] : array();
    $tipoGasto = !empty($resuelto['tipo_gasto']) ? $resuelto['tipo_gasto'] : ocr_buscar_tipo_gasto($conexion, $datosOcr['concepto'] ?? '', $lineas);
    $tipoGastoId = $tipoGasto ? (int) ($tipoGasto['id_tipo_gasto'] ?? 0) : 0;

    $formaPagoProveedor = isset($resuelto['proveedor']['forma_pago_proveedor']) ? $resuelto['proveedor']['forma_pago_proveedor'] : '';
    $formaPago = !empty($resuelto['forma_pago']) ? $resuelto['forma_pago'] : ocr_buscar_forma_pago($conexion, $datosOcr['forma_pago'] ?? '', $formaPagoProveedor);
    $formaPagoId = $formaPago ? (int) ($formaPago['id_forma_de_pago'] ?? 0) : 0;

    $extrasFaltantes = array();
    if (!ocr_validar_totales_factura($datosOcr)) {
        $extrasFaltantes[] = 'Totales incoherentes';
    }

    $numeroFactura = trim((string) ($datosOcr['numero_factura'] ?? ''));
    $fechaFactura = trim((string) ($datosOcr['fecha_factura'] ?? ''));
    if ($numeroFactura === '') {
        $extrasFaltantes[] = 'Número de factura';
    }
    if ($fechaFactura === '') {
        $extrasFaltantes[] = 'Fecha de factura';
        $fechaFactura = date('Y-m-d');
    }

    $faltantes = ocr_listar_faltantes_gasto($empresaId, $proveedorId, $tipoGastoId, $formaPagoId, $extrasFaltantes);
    $esCompleto = empty($faltantes);
    $resultadoDigitalizadoJson = ocr_resultado_json_desde_datos($datosOcr);

    if ($empresaId <= 0 && $proveedorId <= 0) {
        $nombreFoto = cron_guardar_archivo_factura_desde_ruta($rutaLocal, $nombreOriginal);
        $errorDig = ocr_describir_faltantes(array_merge($faltantes, array('Empresa o proveedor')));
        $idDigitalizacion = 0;

        if ($nombreFoto) {
            $idDigitalizacion = ocr_guardar_digitalizacion_agente(
                $conexion,
                $nombreFoto,
                $sucursalId,
                0,
                0,
                $usuarioId,
                0,
                $resultadoDigitalizadoJson,
                'noprocesado',
                $errorDig !== '' ? $errorDig : 'Sin empresa ni proveedor identificados',
                'gasto'
            );
        }

        return array(
            'success' => false,
            'auto_guardado' => false,
            'estado_digitalizado' => 'noprocesado',
            'id_digitalizacion' => $idDigitalizacion,
            'message' => 'Debe identificarse al menos la empresa o el proveedor',
        );
    }

    $base = ocr_parse_numero($datosOcr['base_imponible'] ?? '0');
    $tipoIva = ocr_parse_tipo_iva($datosOcr['tipo_iva'] ?? '21');
    $totalOcr = ocr_parse_numero($datosOcr['total'] ?? '0');
    if ($base <= 0 && $totalOcr > 0) {
        $base = round($totalOcr / (1 + ($tipoIva / 100)), 2);
    }
    if ($base <= 0 && $totalOcr <= 0) {
        $base = 0.01;
        $totalOcr = round($base * (1 + ($tipoIva / 100)), 2);
    }

    $fechaGasto = $fechaFactura;
    $concepto = trim((string) ($datosOcr['concepto'] ?? ''));
    $proveedorNombre = trim((string) ($datosOcr['proveedor_nombre'] ?? ''));
    $descripcion = $concepto !== '' ? $concepto : ($proveedorNombre !== '' ? $proveedorNombre : 'Gasto desde FTP OCR');

    $dtGasto = DateTime::createFromFormat('Y-m-d', $fechaGasto);
    if (!$dtGasto || $dtGasto->format('Y-m-d') !== $fechaGasto) {
        $fechaGasto = date('Y-m-d');
        $fechaFactura = $fechaGasto;
    }

    $irpf = 0.0;
    $ivaTotal = round($base * ($tipoIva / 100), 2);
    $total = round($base + $ivaTotal - $irpf, 2);
    $estado = 'pagado';
    $fechaPago = date('Y-m-d H:i:s');
    $estadoDigitalizado = $esCompleto ? 'procesado' : 'procesado_faltantes';
    $errorDigitalizado = $esCompleto ? '' : ocr_describir_faltantes($faltantes);

    $sql = "INSERT INTO gastos_pruebas (
                sucursal_gasto,
                proveedor_gasto,
                fecha_gasto,
                fecha_pago_gasto,
                fecha_factura_gasto,
                usuario_creacion_gasto,
                base_impobile,
                iva_total,
                total_gasto,
                forma_pago_gasto,
                estado_gasto,
                tipo_de_gasto,
                usuario_pago_gasto,
                empresa_gasto,
                descripcion_gasto,
                numero_factura_proveedor,
                irpf,
                creado_desde,
                tipo_iva,
                origen_gasto_variable,
                rel_id_gasto_fijo,
                gasto_tipo
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Central', ?, 'ocr', 0, 'empresa'
            )";

    $stmt = mysqli_prepare($conexion, $sql);
    if (!$stmt) {
        throw new Exception('Error preparando INSERT: ' . mysqli_error($conexion));
    }

    mysqli_stmt_bind_param(
        $stmt,
        'iisssidddisiiissdi',
        $sucursalId,
        $proveedorId,
        $fechaGasto,
        $fechaPago,
        $fechaFactura,
        $usuarioId,
        $base,
        $ivaTotal,
        $total,
        $formaPagoId,
        $estado,
        $tipoGastoId,
        $usuarioId,
        $empresaId,
        $descripcion,
        $numeroFactura,
        $irpf,
        $tipoIva
    );

    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception('Error insertando gasto: ' . mysqli_stmt_error($stmt));
    }

    $idGasto = (int) mysqli_insert_id($conexion);
    mysqli_stmt_close($stmt);

    $nombreFoto = cron_guardar_archivo_factura_desde_ruta($rutaLocal, $nombreOriginal);
    $idDigitalizacion = 0;

    if ($nombreFoto) {
        $idFoto = camera_insertar_foto_gasto_prueba(
            $conexion,
            $idGasto,
            $empresaId > 0 ? $empresaId : 0,
            $sucursalId,
            $nombreFoto,
            $usuarioId,
            'automatico'
        );
        $idDigitalizacion = ocr_guardar_digitalizacion_agente(
            $conexion,
            $nombreFoto,
            $sucursalId,
            $idGasto,
            $empresaId > 0 ? $empresaId : 0,
            $usuarioId,
            $idFoto,
            $resultadoDigitalizadoJson,
            $estadoDigitalizado,
            $errorDigitalizado,
            'gasto'
        );
    }

    $mensaje = $esCompleto
        ? 'Gasto guardado automáticamente (Nº ' . $numeroFactura . ')'
        : 'Gasto guardado con datos pendientes';

    return array(
        'success' => true,
        'auto_guardado' => true,
        'procesado_faltantes' => !$esCompleto,
        'estado_digitalizado' => $estadoDigitalizado,
        'error_digitalizado' => $errorDigitalizado,
        'id_gasto' => $idGasto,
        'id_digitalizacion' => $idDigitalizacion,
        'numero_factura' => $numeroFactura,
        'message' => $mensaje,
    );
}

/**
 * Mueve un archivo remoto a una subcarpeta del FTP (procesados/errores).
 *
 * @param resource|FTP\Connection $ftp
 * @param string $nombreArchivo
 * @param string $carpetaDestino
 * @return bool
 */
function cron_ftp_mover_a_carpeta($ftp, $nombreArchivo, $carpetaDestino)
{
    ftp_crear_directorio($ftp, $carpetaDestino);

    $destino = rtrim($carpetaDestino, '/') . '/' . basename($nombreArchivo);
    if (@ftp_size($ftp, $destino) !== -1) {
        $info = pathinfo($nombreArchivo);
        $base = $info['filename'] ?? 'archivo';
        $ext = isset($info['extension']) ? '.' . $info['extension'] : '';
        $destino = rtrim($carpetaDestino, '/') . '/' . $base . '_' . date('Ymd_His') . $ext;
    }

    return ftp_mover_archivo($ftp, $nombreArchivo, $destino);
}
