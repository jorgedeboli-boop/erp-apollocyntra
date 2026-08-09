<?php
/**
 * CLI only (SSH): rellena en informe_semanal las columnas de ventas por forma
 * de pago sumando desde informe_diario.
 *
 * Uso:
 *   php actualizar_ventas_forma_pago_informe_semanal.php
 *   php actualizar_ventas_forma_pago_informe_semanal.php --year=2025
 *   php actualizar_ventas_forma_pago_informe_semanal.php --year=2026
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    exit("Acceso denegado. Este script solo se puede ejecutar por SSH/CLI.\n");
}

set_time_limit(0);
ini_set('memory_limit', '512M');

require_once __DIR__ . '/include/functions.php';

$yearFiltro = null;
foreach ($argv as $arg) {
    if (strpos($arg, '--year=') === 0) {
        $yearFiltro = substr($arg, 7);
        if (!preg_match('/^\d{4}$/', $yearFiltro)) {
            fwrite(STDERR, "ERROR: --year debe ser un año de 4 dígitos (ej. --year=2025)\n");
            exit(1);
        }
    }
}

echo ">> Inicio: actualizar ventas forma pago informe_semanal\n";
echo 'Filtro año: ' . ($yearFiltro !== null ? $yearFiltro : 'todos') . "\n";

$conexion = conectar_bd();
if (!$conexion) {
    fwrite(STDERR, "ERROR: no se pudo conectar a la base de datos.\n");
    exit(1);
}

if (function_exists('mysqli_report')) {
    mysqli_report(MYSQLI_REPORT_OFF);
}

$sqlInformes = 'SELECT id_informe, sucursal_informe, numero_semana, year_informe
                FROM informe_semanal';
$params = [];
$types = '';

if ($yearFiltro !== null) {
    $sqlInformes .= ' WHERE year_informe = ?';
    $params[] = $yearFiltro;
    $types .= 's';
}

$sqlInformes .= ' ORDER BY year_informe ASC, numero_semana ASC, sucursal_informe ASC';

$stmtInformes = mysqli_prepare($conexion, $sqlInformes);
if (!$stmtInformes) {
    fwrite(STDERR, 'ERROR preparando SELECT informes: ' . mysqli_error($conexion) . "\n");
    mysqli_close($conexion);
    exit(1);
}

if ($types !== '') {
    mysqli_stmt_bind_param($stmtInformes, $types, ...$params);
}

if (!mysqli_stmt_execute($stmtInformes)) {
    fwrite(STDERR, 'ERROR ejecutando SELECT informes: ' . mysqli_stmt_error($stmtInformes) . "\n");
    mysqli_stmt_close($stmtInformes);
    mysqli_close($conexion);
    exit(1);
}

$resultadoInformes = mysqli_stmt_get_result($stmtInformes);
if (!$resultadoInformes) {
    fwrite(STDERR, "ERROR: sin resultado de informes semanales.\n");
    mysqli_stmt_close($stmtInformes);
    mysqli_close($conexion);
    exit(1);
}

$sqlSum = 'SELECT
                COALESCE(SUM(ventas_contado), 0) AS ventas_contado,
                COALESCE(SUM(ventas_contado_euros), 0) AS ventas_contado_euros,
                COALESCE(SUM(ventas_tarjeta), 0) AS ventas_tarjeta,
                COALESCE(SUM(ventas_tarjeta_euros), 0) AS ventas_tarjeta_euros,
                COALESCE(SUM(ventas_transferencia), 0) AS ventas_transferencia,
                COALESCE(SUM(ventas_transferencia_euros), 0) AS ventas_transferencia_euros,
                COALESCE(SUM(ventas_bizum), 0) AS ventas_bizum,
                COALESCE(SUM(ventas_bizum_euros), 0) AS ventas_bizum_euros
            FROM informe_diario
            WHERE semana_numero = ?
              AND year_rel = ?
              AND sucursal_informe = ?';
$stmtSum = mysqli_prepare($conexion, $sqlSum);
if (!$stmtSum) {
    fwrite(STDERR, 'ERROR preparando SUM diario: ' . mysqli_error($conexion) . "\n");
    mysqli_stmt_close($stmtInformes);
    mysqli_close($conexion);
    exit(1);
}

$sqlUpdate = 'UPDATE informe_semanal
              SET ventas_contado = ?,
                  ventas_contado_euros = ?,
                  ventas_tarjeta = ?,
                  ventas_tarjeta_euros = ?,
                  ventas_transferencia = ?,
                  ventas_transferencia_euros = ?,
                  ventas_bizum = ?,
                  ventas_bizum_euros = ?
              WHERE id_informe = ?';
$stmtUpdate = mysqli_prepare($conexion, $sqlUpdate);
if (!$stmtUpdate) {
    fwrite(STDERR, 'ERROR preparando UPDATE: ' . mysqli_error($conexion) . "\n");
    mysqli_stmt_close($stmtSum);
    mysqli_stmt_close($stmtInformes);
    mysqli_close($conexion);
    exit(1);
}

$total = 0;
$ok = 0;
$errores = 0;

while ($informe = mysqli_fetch_assoc($resultadoInformes)) {
    $total++;
    $idInforme = (int) $informe['id_informe'];
    $sucursalInforme = (int) $informe['sucursal_informe'];
    $numeroSemana = (int) $informe['numero_semana'];
    $yearInforme = (string) $informe['year_informe'];

    mysqli_stmt_bind_param($stmtSum, 'isi', $numeroSemana, $yearInforme, $sucursalInforme);
    if (!mysqli_stmt_execute($stmtSum)) {
        $errores++;
        echo "ERROR SUM informe {$idInforme}: " . mysqli_stmt_error($stmtSum) . "\n";
        continue;
    }

    $fila = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtSum));
    $ventasContado = (int) ($fila['ventas_contado'] ?? 0);
    $ventasContadoEuros = round((float) ($fila['ventas_contado_euros'] ?? 0), 2);
    $ventasTarjeta = (int) ($fila['ventas_tarjeta'] ?? 0);
    $ventasTarjetaEuros = round((float) ($fila['ventas_tarjeta_euros'] ?? 0), 2);
    $ventasTransferencia = (int) ($fila['ventas_transferencia'] ?? 0);
    $ventasTransferenciaEuros = round((float) ($fila['ventas_transferencia_euros'] ?? 0), 2);
    $ventasBizum = (int) ($fila['ventas_bizum'] ?? 0);
    $ventasBizumEuros = round((float) ($fila['ventas_bizum_euros'] ?? 0), 2);

    mysqli_stmt_bind_param(
        $stmtUpdate,
        'ididididi',
        $ventasContado,
        $ventasContadoEuros,
        $ventasTarjeta,
        $ventasTarjetaEuros,
        $ventasTransferencia,
        $ventasTransferenciaEuros,
        $ventasBizum,
        $ventasBizumEuros,
        $idInforme
    );

    if (!mysqli_stmt_execute($stmtUpdate)) {
        $errores++;
        echo "ERROR UPDATE informe {$idInforme}: " . mysqli_stmt_error($stmtUpdate) . "\n";
        continue;
    }

    $ok++;
    echo '  - Informe ' . $idInforme
        . ' | sem=' . $numeroSemana
        . ' | year=' . $yearInforme
        . ' | suc=' . $sucursalInforme
        . ' | contado=' . $ventasContado . '/' . $ventasContadoEuros
        . ' | tarjeta=' . $ventasTarjeta . '/' . $ventasTarjetaEuros
        . ' | transferencia=' . $ventasTransferencia . '/' . $ventasTransferenciaEuros
        . ' | bizum=' . $ventasBizum . '/' . $ventasBizumEuros
        . "\n";
}

mysqli_stmt_close($stmtUpdate);
mysqli_stmt_close($stmtSum);
mysqli_stmt_close($stmtInformes);
mysqli_close($conexion);

echo ">> Fin: procesados={$total} ok={$ok} errores={$errores}\n";
exit($errores > 0 ? 1 : 0);
