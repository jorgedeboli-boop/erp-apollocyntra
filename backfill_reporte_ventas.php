<?php
/**
 * Rellena reporte_ventas faltantes a partir de ventas + rel_articulos_venta.
 *
 * Navegador (con sesión iniciada):
 *   /backfill_reporte_ventas.php
 *   /backfill_reporte_ventas.php?ejecutar=1
 *   /backfill_reporte_ventas.php?desde=2026-06-15&ejecutar=1
 *   /backfill_reporte_ventas.php?id_venta=123&ejecutar=1
 *   /backfill_reporte_ventas.php?id_sucursal=5&limite=100
 *
 * CLI: php -f backfill_reporte_ventas.php
 *   php backfill_reporte_ventas.php
 *   php backfill_reporte_ventas.php --ejecutar
 *
 * Por defecto solo lista (modo vista). Añade ejecutar=1 / --ejecutar para insertar.
 */

$esCli = (PHP_SAPI === 'cli');

if ($esCli) {
    $_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $_SERVER['REQUEST_URI'] = $_SERVER['REQUEST_URI'] ?? '/backfill_reporte_ventas.php';
    $_SERVER['REMOTE_ADDR'] = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $_SERVER['HTTP_USER_AGENT'] = $_SERVER['HTTP_USER_AGENT'] ?? 'cli-backfill-reporte-ventas';

    require_once __DIR__ . '/include/config.php';
    require_once __DIR__ . '/include/functions.php';

    $ejecutar = false;
    $fechaDesde = '2026-06-15';
    $idVentaFiltro = 0;
    $idSucursalFiltro = 0;
    $limite = 0;

    foreach (array_slice($argv, 1) as $arg) {
        if ($arg === '--ejecutar' || $arg === '--execute') {
            $ejecutar = true;
            continue;
        }
        if (preg_match('/^--desde=(\d{4}-\d{2}-\d{2})$/', $arg, $m)) {
            $fechaDesde = $m[1];
            continue;
        }
        if (preg_match('/^--id-venta=(\d+)$/', $arg, $m)) {
            $idVentaFiltro = (int) $m[1];
            continue;
        }
        if (preg_match('/^--id-sucursal=(\d+)$/', $arg, $m)) {
            $idSucursalFiltro = (int) $m[1];
            continue;
        }
        if (preg_match('/^--limite=(\d+)$/', $arg, $m)) {
            $limite = (int) $m[1];
            continue;
        }
        if ($arg === '--help' || $arg === '-h') {
            echo "Opciones CLI / navegador:\n";
            echo "  --ejecutar / ?ejecutar=1\n";
            echo "  --desde=YYYY-MM-DD / ?desde=\n";
            echo "  --id-venta=N / ?id_venta=\n";
            echo "  --id-sucursal=N / ?id_sucursal=\n";
            echo "  --limite=N / ?limite=\n";
            exit(0);
        }
        fwrite(STDERR, "Argumento no reconocido: {$arg}\n");
        exit(1);
    }
} else {
    require_once __DIR__ . '/include/session.php';

    if (empty($usuario_id) || (int) $usuario_id <= 0) {
        http_response_code(401);
        echo 'No autorizado.';
        exit;
    }

    header('Content-Type: text/html; charset=utf-8');

    $ejecutar = isset($_GET['ejecutar']) && (string) $_GET['ejecutar'] === '1';
    $fechaDesde = isset($_GET['desde']) ? trim((string) $_GET['desde']) : '2026-06-15';
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaDesde)) {
        $fechaDesde = '2026-06-15';
    }
    $idVentaFiltro = isset($_GET['id_venta']) ? (int) $_GET['id_venta'] : 0;
    $idSucursalFiltro = isset($_GET['id_sucursal']) ? (int) $_GET['id_sucursal'] : 0;
    $limite = isset($_GET['limite']) ? (int) $_GET['limite'] : 0;
}

function brv_linea($msg)
{
    global $esCli;
    if ($esCli) {
        echo $msg . PHP_EOL;
        return;
    }
    echo htmlspecialchars((string) $msg, ENT_QUOTES, 'UTF-8') . "\n";
}

/**
 * @return array<int, array<string, mixed>>
 */
function brv_buscar_ventas(mysqli $conexion, $fechaDesde, $idVentaFiltro, $idSucursalFiltro)
{
    $where = [
        'DATE(v.fecha) >= ?',
        "LOWER(COALESCE(v.estado, '')) = 'vendido'",
    ];
    $params = [$fechaDesde];
    $types = 's';

    if ($idVentaFiltro > 0) {
        $where[] = 'v.id = ?';
        $params[] = $idVentaFiltro;
        $types .= 'i';
    }
    if ($idSucursalFiltro > 0) {
        $where[] = 'v.id_sucursal = ?';
        $params[] = $idSucursalFiltro;
        $types .= 'i';
    }

    $sql = 'SELECT
                v.id,
                v.id_sucursal,
                v.id_venta_sucursal,
                v.comprado_por,
                v.venta_plazos,
                v.numero_plazos,
                v.tipo_pago,
                v.cantidad_contado,
                v.cantidad_tarjeta,
                v.cantidad_transferencia,
                v.cantidad_bizum,
                DATE(v.fecha) AS fecha_venta,
                v.estado
            FROM ventas v
            WHERE ' . implode(' AND ', $where) . '
            ORDER BY v.id ASC';

    $stmt = mysqli_prepare($conexion, $sql);
    if (!$stmt) {
        throw new Exception('Error al preparar ventas: ' . mysqli_error($conexion));
    }

    $bind = [$stmt, $types];
    foreach ($params as $k => $v) {
        $bind[] = &$params[$k];
    }
    call_user_func_array('mysqli_stmt_bind_param', $bind);

    if (!mysqli_stmt_execute($stmt)) {
        $err = mysqli_stmt_error($stmt);
        mysqli_stmt_close($stmt);
        throw new Exception('Error al listar ventas: ' . $err);
    }

    $res = mysqli_stmt_get_result($stmt);
    $rows = [];
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
            $rows[] = $row;
        }
        mysqli_free_result($res);
    }
    mysqli_stmt_close($stmt);

    return $rows;
}

/**
 * @return array<int, array<string, mixed>>
 */
function brv_articulos_venta(mysqli $conexion, $idVentaPk)
{
    $idVentaPk = (int) $idVentaPk;
    $sql = 'SELECT
                r.sku_articulo,
                r.descripcion_articulo_rel,
                r.precio_venta,
                r.coste_articulo_venta,
                r.venta_web,
                r.fecha_venta AS fecha_linea,
                av.peso,
                av.articulo_web,
                av.tipo,
                av.descripcion AS descripcion_av
            FROM rel_articulos_venta r
            LEFT JOIN articulos_venta av ON av.id = r.sku_articulo
            WHERE r.rel_id_venta = ?
              AND r.estado_rel_Articulo = "vendido"
            ORDER BY r.sku_articulo ASC';

    $stmt = mysqli_prepare($conexion, $sql);
    if (!$stmt) {
        throw new Exception('Error al preparar artículos: ' . mysqli_error($conexion));
    }
    mysqli_stmt_bind_param($stmt, 'i', $idVentaPk);
    if (!mysqli_stmt_execute($stmt)) {
        $err = mysqli_stmt_error($stmt);
        mysqli_stmt_close($stmt);
        throw new Exception('Error al listar artículos: ' . $err);
    }

    $res = mysqli_stmt_get_result($stmt);
    $rows = [];
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
            $rows[] = $row;
        }
        mysqli_free_result($res);
    }
    mysqli_stmt_close($stmt);

    return $rows;
}

function brv_ya_existe_reporte(mysqli $conexion, $idArticulo, $identificadorVenta)
{
    $idArticulo = (int) $idArticulo;
    $identificadorVenta = (int) $identificadorVenta;
    $stmt = mysqli_prepare(
        $conexion,
        'SELECT id_reporte_ventas FROM reporte_ventas
         WHERE id_articulo = ? AND identificador_venta = ?
         LIMIT 1'
    );
    if (!$stmt) {
        return false;
    }
    mysqli_stmt_bind_param($stmt, 'ii', $idArticulo, $identificadorVenta);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $existe = ($res && mysqli_fetch_assoc($res)) ? true : false;
    if ($res) {
        mysqli_free_result($res);
    }
    mysqli_stmt_close($stmt);

    return $existe;
}

if (!$esCli) {
    echo '<!DOCTYPE html><html lang="es"><head><meta charset="utf-8">';
    echo '<title>Backfill reporte_ventas</title>';
    echo '<style>body{font-family:ui-monospace,Menlo,Consolas,monospace;background:#111;color:#eee;padding:16px;}';
    echo 'pre{white-space:pre-wrap;word-break:break-word;} a{color:#8cf;} .box{margin:12px 0;padding:12px;border:1px solid #333;border-radius:8px;}</style>';
    echo '</head><body>';
    echo '<h1>Backfill reporte_ventas</h1>';
    echo '<div class="box">';
    echo '<a href="?desde=' . rawurlencode($fechaDesde) . '">1) Vista previa</a> &nbsp;|&nbsp; ';
    echo '<a href="?desde=' . rawurlencode($fechaDesde) . '&ejecutar=1" onclick="return confirm(\'¿Insertar reportes faltantes?\');">2) Ejecutar inserción</a>';
    echo '</div><pre>';
    @ob_implicit_flush(true);
    if (function_exists('apache_setenv')) {
        @apache_setenv('no-gzip', '1');
    }
    @ini_set('zlib.output_compression', '0');
    @ini_set('output_buffering', '0');
}

try {
    $conexion = conectar_bd();
    if (!$conexion) {
        throw new Exception('Sin conexión a la base de datos.');
    }
    mysqli_set_charset($conexion, 'utf8');

    brv_linea('=== Backfill reporte_ventas ===');
    brv_linea('Modo: ' . ($ejecutar ? 'EJECUTAR (inserta)' : 'VISTA PREVIA (no inserta)'));
    brv_linea('Desde: ' . $fechaDesde);
    if ($idVentaFiltro > 0) {
        brv_linea('Filtro id_venta: ' . $idVentaFiltro);
    }
    if ($idSucursalFiltro > 0) {
        brv_linea('Filtro id_sucursal: ' . $idSucursalFiltro);
    }
    if ($limite > 0) {
        brv_linea('Límite líneas: ' . $limite);
    }
    brv_linea('');

    $ventas = brv_buscar_ventas($conexion, $fechaDesde, $idVentaFiltro, $idSucursalFiltro);
    brv_linea('Ventas encontradas: ' . count($ventas));

    $totalLineas = 0;
    $yaExisten = 0;
    $faltan = 0;
    $insertados = 0;
    $errores = 0;
    $sinArticulos = 0;

    foreach ($ventas as $venta) {
        $idVentaPk = (int) $venta['id'];
        $idVentaSucursal = (int) $venta['id_venta_sucursal'];
        $idSucursal = (int) $venta['id_sucursal'];
        $usuarioVenta = (int) ($venta['comprado_por'] ?? 0);
        $ventaPlazos = (string) ($venta['venta_plazos'] ?? 'no');
        $numeroPlazos = (int) ($venta['numero_plazos'] ?? 0);
        $tipoPago = (string) ($venta['tipo_pago'] ?? 'contado');
        $cantContado = (float) ($venta['cantidad_contado'] ?? 0);
        $cantTarjeta = (float) ($venta['cantidad_tarjeta'] ?? 0);
        $cantTransferencia = (float) ($venta['cantidad_transferencia'] ?? 0);
        $cantBizum = (float) ($venta['cantidad_bizum'] ?? 0);
        $fechaVenta = (string) ($venta['fecha_venta'] ?? $fechaDesde);

        $articulos = brv_articulos_venta($conexion, $idVentaPk);
        if (count($articulos) === 0) {
            $sinArticulos++;
            brv_linea("[SKIP] venta #{$idVentaPk} (suc {$idSucursal}/{$idVentaSucursal}): sin líneas en rel_articulos_venta");
            continue;
        }

        foreach ($articulos as $art) {
            if ($limite > 0 && ($faltan + $yaExisten) >= $limite) {
                break 2;
            }

            $totalLineas++;
            $idArticulo = (int) ($art['sku_articulo'] ?? 0);
            if ($idArticulo <= 0) {
                $errores++;
                brv_linea("[ERR] venta #{$idVentaPk}: sku_articulo inválido");
                continue;
            }

            if (brv_ya_existe_reporte($conexion, $idArticulo, $idVentaPk)) {
                $yaExisten++;
                continue;
            }

            $faltan++;
            $descripcion = trim((string) ($art['descripcion_articulo_rel'] ?? ''));
            if ($descripcion === '') {
                $descripcion = trim((string) ($art['descripcion_av'] ?? ''));
            }
            if ($descripcion === '') {
                $descripcion = 'Artículo #' . $idArticulo;
            }

            $precio = (float) ($art['precio_venta'] ?? 0);
            $coste = (float) ($art['coste_articulo_venta'] ?? 0);
            $peso = is_numeric($art['peso'] ?? null) ? (float) $art['peso'] : 0.0;
            $articuloWeb = (string) ($art['articulo_web'] ?? '');
            if ($articuloWeb === '') {
                $articuloWeb = ((string) ($art['venta_web'] ?? 'false') === 'true') ? 'true' : 'false';
            }
            $tipoMetal = (string) ($art['tipo'] ?? 'oro');
            $fechaLinea = (string) ($art['fecha_linea'] ?? '');
            if ($fechaLinea === '' || $fechaLinea === '0000-00-00') {
                $fechaLinea = $fechaVenta;
            }

            brv_linea(sprintf(
                '[FALTA] venta #%d (suc %d/%d) art #%d | %.2f € | %s | %s | %s',
                $idVentaPk,
                $idSucursal,
                $idVentaSucursal,
                $idArticulo,
                $precio,
                $fechaLinea,
                $tipoPago,
                $ventaPlazos === 'si' ? 'plazos' : 'normal'
            ));

            if (!$ejecutar) {
                continue;
            }

            try {
                $idNuevo = insert_reporte_ventas(
                    $idArticulo,
                    $idSucursal,
                    $descripcion,
                    $idVentaSucursal,
                    $idVentaPk,
                    $precio,
                    $peso,
                    $articuloWeb,
                    $tipoMetal,
                    $ventaPlazos,
                    $numeroPlazos,
                    $tipoPago,
                    $cantContado,
                    $cantTarjeta,
                    $cantTransferencia,
                    $cantBizum,
                    $usuarioVenta,
                    $fechaLinea,
                    $coste
                );
                if ($idNuevo > 0) {
                    $insertados++;
                    brv_linea("  -> insertado id_reporte_ventas={$idNuevo}");
                } else {
                    $errores++;
                    brv_linea('  -> ERROR: insert_reporte_ventas devolvió 0');
                }
            } catch (Throwable $e) {
                $errores++;
                brv_linea('  -> ERROR: ' . $e->getMessage());
            }
        }
    }

    brv_linea('');
    brv_linea('=== Resumen ===');
    brv_linea('Ventas revisadas: ' . count($ventas));
    brv_linea('Ventas sin artículos: ' . $sinArticulos);
    brv_linea('Líneas revisadas: ' . $totalLineas);
    brv_linea('Ya existían: ' . $yaExisten);
    brv_linea('Faltaban: ' . $faltan);
    brv_linea('Insertados: ' . $insertados);
    brv_linea('Errores: ' . $errores);
    if (!$ejecutar && $faltan > 0) {
        brv_linea('');
        brv_linea('Vista previa OK. Para insertar usa ?ejecutar=1');
    }

    mysqli_close($conexion);

    if (!$esCli) {
        echo '</pre></body></html>';
    }
    exit(($errores > 0) ? 2 : 0);
} catch (Throwable $e) {
    if ($esCli) {
        fwrite(STDERR, 'ERROR: ' . $e->getMessage() . PHP_EOL);
    } else {
        echo 'ERROR: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
        echo '</pre></body></html>';
    }
    exit(1);
}
