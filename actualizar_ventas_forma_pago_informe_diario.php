<?php
/**
 * Script manual (navegador): rellena en informe_diario las columnas de ventas
 * por forma de pago desde ventas (fecha >= 2024-12-30, estado = vendido).
 *
 * URL: /actualizar_ventas_forma_pago_informe_diario.php
 */

require_once __DIR__ . '/include/session.php';
require_once __DIR__ . '/include/functions.php';

$esRoot = (isset($usuario_root) && $usuario_root === 'true');
$esSuperAdmin = (isset($usuario_super_administrador) && $usuario_super_administrador === 'true');
if (!$esRoot && !$esSuperAdmin) {
    http_response_code(403);
    exit('Acceso denegado. Solo usuario root o super administrador.');
}

set_time_limit(0);

$fechaDesde = '2026-07-03';
$fechaDesdeDateTime = $fechaDesde . ' 00:00:00';

header('Content-Type: text/html; charset=utf-8');
echo '<!DOCTYPE html><html lang="es"><head><meta charset="utf-8">';
echo '<title>Actualizar ventas forma pago informe_diario</title></head><body>';
echo '<h1>Actualizar ventas por forma de pago en informe_diario</h1>';
echo '<pre style="font-family:monospace;font-size:13px;line-height:1.45;">';

echo "Session OK\n";
echo "Conectando...\n";

$conexion = conectar_bd();
if (!$conexion) {
    echo "ERROR conexión\n</pre></body></html>";
    exit;
}

echo "Conexión OK\n";
echo "Fecha desde: {$fechaDesde}\n";
echo "Agrupando ventas por sucursal + día...\n\n";

$sql = "SELECT
            id_sucursal,
            DATE(fecha) AS fecha_venta,
            SUM(CASE
                  WHEN tipo_pago = 'contado'
                    OR (tipo_pago = 'combinado' AND cantidad_contado > 0)
                  THEN 1 ELSE 0
                END) AS ventas_contado,
            SUM(COALESCE(cantidad_contado, 0)) AS ventas_contado_euros,
            SUM(CASE
                  WHEN tipo_pago = 'tarjeta'
                    OR (tipo_pago = 'combinado' AND cantidad_tarjeta > 0)
                  THEN 1 ELSE 0
                END) AS ventas_tarjeta,
            SUM(COALESCE(cantidad_tarjeta, 0)) AS ventas_tarjeta_euros,
            SUM(CASE
                  WHEN tipo_pago = 'transferencia'
                    OR (tipo_pago = 'combinado' AND cantidad_transferencia > 0)
                  THEN 1 ELSE 0
                END) AS ventas_transferencia,
            SUM(COALESCE(cantidad_transferencia, 0)) AS ventas_transferencia_euros,
            SUM(CASE
                  WHEN tipo_pago = 'bizum'
                    OR (tipo_pago = 'combinado' AND cantidad_bizum > 0)
                  THEN 1 ELSE 0
                END) AS ventas_bizum,
            SUM(COALESCE(cantidad_bizum, 0)) AS ventas_bizum_euros
        FROM ventas
        WHERE estado = 'vendido'
          AND fecha >= '{$fechaDesdeDateTime}'
        GROUP BY id_sucursal, DATE(fecha)
        ORDER BY DATE(fecha) ASC, id_sucursal ASC";

$result = mysqli_query($conexion, $sql);
if (!$result) {
    echo 'ERROR SELECT: ' . mysqli_error($conexion) . "\n";
    echo '</pre></body></html>';
    exit;
}

$sqlUpdate = "UPDATE informe_diario
              SET ventas_contado = ?,
                  ventas_contado_euros = ?,
                  ventas_tarjeta = ?,
                  ventas_tarjeta_euros = ?,
                  ventas_transferencia = ?,
                  ventas_transferencia_euros = ?,
                  ventas_bizum = ?,
                  ventas_bizum_euros = ?
              WHERE sucursal_informe = ?
                AND fecha_informe = ?";
$stmtUpdate = mysqli_prepare($conexion, $sqlUpdate);
if (!$stmtUpdate) {
    echo 'ERROR preparando UPDATE: ' . mysqli_error($conexion) . "\n";
    mysqli_free_result($result);
    mysqli_close($conexion);
    echo '</pre></body></html>';
    exit;
}

$sqlExiste = 'SELECT id_informe
              FROM informe_diario
              WHERE sucursal_informe = ?
                AND fecha_informe = ?
              LIMIT 1';
$stmtExiste = mysqli_prepare($conexion, $sqlExiste);
if (!$stmtExiste) {
    echo 'ERROR preparando SELECT informe: ' . mysqli_error($conexion) . "\n";
    mysqli_stmt_close($stmtUpdate);
    mysqli_free_result($result);
    mysqli_close($conexion);
    echo '</pre></body></html>';
    exit;
}

$grupos = 0;
$actualizados = 0;
$sinInforme = 0;
$errores = 0;

while ($row = mysqli_fetch_assoc($result)) {
    $grupos++;
    $sucursal = (int) $row['id_sucursal'];
    $fecha = (string) $row['fecha_venta'];

    $ventasContado = (int) $row['ventas_contado'];
    $ventasContadoEuros = round((float) $row['ventas_contado_euros'], 2);
    $ventasTarjeta = (int) $row['ventas_tarjeta'];
    $ventasTarjetaEuros = round((float) $row['ventas_tarjeta_euros'], 2);
    $ventasTransferencia = (int) $row['ventas_transferencia'];
    $ventasTransferenciaEuros = round((float) $row['ventas_transferencia_euros'], 2);
    $ventasBizum = (int) $row['ventas_bizum'];
    $ventasBizumEuros = round((float) $row['ventas_bizum_euros'], 2);

    mysqli_stmt_bind_param($stmtExiste, 'is', $sucursal, $fecha);
    mysqli_stmt_execute($stmtExiste);
    $resExiste = mysqli_stmt_get_result($stmtExiste);
    $informe = $resExiste ? mysqli_fetch_assoc($resExiste) : null;

    if (!$informe) {
        $sinInforme++;
        echo 'SKIP sin informe | sucursal=' . $sucursal . ' | fecha=' . $fecha . "\n";
        continue;
    }

    mysqli_stmt_bind_param(
        $stmtUpdate,
        'ididididis',
        $ventasContado,
        $ventasContadoEuros,
        $ventasTarjeta,
        $ventasTarjetaEuros,
        $ventasTransferencia,
        $ventasTransferenciaEuros,
        $ventasBizum,
        $ventasBizumEuros,
        $sucursal,
        $fecha
    );

    if (!mysqli_stmt_execute($stmtUpdate)) {
        $errores++;
        echo 'ERROR update sucursal=' . $sucursal . ' fecha=' . $fecha . ': ' . mysqli_stmt_error($stmtUpdate) . "\n";
        continue;
    }

    $actualizados++;
    echo 'OK id_informe=' . (int) $informe['id_informe']
        . ' | sucursal=' . $sucursal
        . ' | fecha=' . $fecha
        . ' | contado=' . $ventasContado . '/' . $ventasContadoEuros
        . ' | tarjeta=' . $ventasTarjeta . '/' . $ventasTarjetaEuros
        . ' | transferencia=' . $ventasTransferencia . '/' . $ventasTransferenciaEuros
        . ' | bizum=' . $ventasBizum . '/' . $ventasBizumEuros
        . "\n";
}

mysqli_stmt_close($stmtExiste);
mysqli_stmt_close($stmtUpdate);
mysqli_free_result($result);
mysqli_close($conexion);

echo "\n--- Resumen ---\n";
echo "Grupos ventas (sucursal+día): {$grupos}\n";
echo "Informes actualizados: {$actualizados}\n";
echo "Sin informe coincidente: {$sinInforme}\n";
echo "Errores: {$errores}\n";
echo "Fin.\n";
echo '</pre></body></html>';
