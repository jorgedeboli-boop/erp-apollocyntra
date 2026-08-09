<?php
/**
 * Script manual (navegador): recalcula informe_diario.total_gastos
 * desde gastos (fecha_gasto >= 2024-12-30).
 *
 * URL: /actualizar_total_gastos_informe_diario.php
 */

declare(strict_types=1);

require_once __DIR__ . '/include/functions.php';

header('Content-Type: text/html; charset=utf-8');

echo '<!DOCTYPE html><html lang="es"><head><meta charset="utf-8">';
echo '<title>Actualizar total_gastos informe_diario</title></head><body>';
echo '<h1>Actualizar total_gastos en informe_diario</h1>';
echo '<pre style="font-family:monospace;font-size:13px;line-height:1.45;">';

$fechaDesde = '2024-12-30';
$conexion = conectar_bd();

if (!$conexion) {
    echo "ERROR: no se pudo conectar a la base de datos.\n";
    echo '</pre></body></html>';
    exit(1);
}

echo 'Fecha desde: ' . $fechaDesde . "\n";
echo "Agrupando gastos por sucursal_gasto + fecha_gasto...\n\n";

$sql = 'SELECT sucursal_gasto,
               fecha_gasto,
               COALESCE(SUM(total_gasto), 0) AS total_gastos
        FROM gastos
        WHERE fecha_gasto >= ?
        GROUP BY sucursal_gasto, fecha_gasto
        ORDER BY fecha_gasto ASC, sucursal_gasto ASC';

$stmt = mysqli_prepare($conexion, $sql);
if (!$stmt) {
    echo 'ERROR preparando SELECT: ' . mysqli_error($conexion) . "\n";
    mysqli_close($conexion);
    echo '</pre></body></html>';
    exit(1);
}

mysqli_stmt_bind_param($stmt, 's', $fechaDesde);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$sqlUpdate = 'UPDATE informe_diario
              SET total_gastos = ?
              WHERE sucursal_informe = ?
                AND fecha_informe = ?';
$stmtUpdate = mysqli_prepare($conexion, $sqlUpdate);
if (!$stmtUpdate) {
    echo 'ERROR preparando UPDATE: ' . mysqli_error($conexion) . "\n";
    mysqli_stmt_close($stmt);
    mysqli_close($conexion);
    echo '</pre></body></html>';
    exit(1);
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
    mysqli_stmt_close($stmt);
    mysqli_close($conexion);
    echo '</pre></body></html>';
    exit(1);
}

$grupos = 0;
$actualizados = 0;
$sinInforme = 0;
$errores = 0;

while ($row = $result ? mysqli_fetch_assoc($result) : null) {
    $grupos++;
    $sucursal = (int) $row['sucursal_gasto'];
    $fecha = (string) $row['fecha_gasto'];
    $total = round((float) $row['total_gastos'], 2);

    mysqli_stmt_bind_param($stmtExiste, 'is', $sucursal, $fecha);
    mysqli_stmt_execute($stmtExiste);
    $resExiste = mysqli_stmt_get_result($stmtExiste);
    $informe = $resExiste ? mysqli_fetch_assoc($resExiste) : null;

    if (!$informe) {
        $sinInforme++;
        echo 'SKIP sin informe_diario | sucursal=' . $sucursal . ' | fecha=' . $fecha . ' | total=' . $total . "\n";
        continue;
    }

    mysqli_stmt_bind_param($stmtUpdate, 'dis', $total, $sucursal, $fecha);
    if (!mysqli_stmt_execute($stmtUpdate)) {
        $errores++;
        echo 'ERROR update sucursal=' . $sucursal . ' fecha=' . $fecha . ': ' . mysqli_stmt_error($stmtUpdate) . "\n";
        continue;
    }

    $actualizados++;
    echo 'OK  id_informe=' . (int) $informe['id_informe']
        . ' | sucursal=' . $sucursal
        . ' | fecha=' . $fecha
        . ' | total_gastos=' . $total . "\n";
}

mysqli_stmt_close($stmtExiste);
mysqli_stmt_close($stmtUpdate);
mysqli_stmt_close($stmt);
mysqli_close($conexion);

echo "\n--- Resumen ---\n";
echo 'Grupos procesados: ' . $grupos . "\n";
echo 'Informes actualizados: ' . $actualizados . "\n";
echo 'Sin informe coincidente: ' . $sinInforme . "\n";
echo 'Errores: ' . $errores . "\n";
echo "Fin.\n";
echo '</pre></body></html>';
