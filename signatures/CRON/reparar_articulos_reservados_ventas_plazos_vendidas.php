<?php

/**
 * Repara ventas a plazos ya cerradas (estado vendido) cuyos artículos siguen en reservado.
 *
 * Uso (desde la raíz del proyecto):
 *   php CRON/reparar_articulos_reservados_ventas_plazos_vendidas.php
 *   php CRON/reparar_articulos_reservados_ventas_plazos_vendidas.php --ejecutar
 *   php CRON/reparar_articulos_reservados_ventas_plazos_vendidas.php --ejecutar --id-venta=123
 *   php CRON/reparar_articulos_reservados_ventas_plazos_vendidas.php --ejecutar --id-sucursal=5
 *
 * Por defecto solo lista (modo vista). Añade --ejecutar para aplicar cambios.
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "Ejecutar solo por CLI.\n");
    exit(1);
}

$ejecutar = false;
$idVentaFiltro = 0;
$idSucursalFiltro = 0;

foreach (array_slice($argv, 1) as $arg) {
    if ($arg === '--ejecutar' || $arg === '--execute') {
        $ejecutar = true;
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
    if ($arg === '--help' || $arg === '-h') {
        echo "Opciones:\n";
        echo "  --ejecutar          Aplica la reparación (sin esto, solo vista previa)\n";
        echo "  --id-venta=N        Solo una venta concreta\n";
        echo "  --id-sucursal=N     Solo una sucursal\n";
        exit(0);
    }
    fwrite(STDERR, "Argumento no reconocido: {$arg}\n");
    exit(1);
}

$_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'] ?? 'localhost';
$_SERVER['REQUEST_URI'] = $_SERVER['REQUEST_URI'] ?? '/CRON/reparar_articulos_reservados_ventas_plazos_vendidas.php';
$_SERVER['REMOTE_ADDR'] = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
$_SERVER['HTTP_USER_AGENT'] = $_SERVER['HTTP_USER_AGENT'] ?? 'cli-reparar-ventas-plazos';

require_once __DIR__ . '/../include/config.php';
require_once __DIR__ . '/../include/functions.php';
require_once __DIR__ . '/../parts/ventas/main/venta_plazos_factura_lib.php';

$usuarioReparacion = 1;

function rar_linea(string $msg): void
{
    echo $msg . PHP_EOL;
}

/**
 * @return array<int, array<string, mixed>>
 */
function rar_buscar_ventas_afectadas(mysqli $conexion, int $idVentaFiltro, int $idSucursalFiltro): array
{
    $where = [
        "LOWER(v.venta_plazos) = 'si'",
        "LOWER(v.estado) = 'vendido'",
        "av.estado = 'reservado'",
    ];
    $params = [];
    $types = '';

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

    $sql = '
        SELECT
            v.id AS id_venta,
            v.id_sucursal,
            v.id_venta_sucursal,
            IFNULL(s.nombre_sucursal, "") AS nombre_sucursal,
            COUNT(av.id) AS articulos_reservados,
            GROUP_CONCAT(av.id ORDER BY av.id SEPARATOR ",") AS ids_articulos
        FROM ventas v
        INNER JOIN rel_articulos_venta r
            ON r.rel_id_venta = v.id AND r.sucursal_venta = v.id_sucursal
        INNER JOIN articulos_venta av
            ON av.id = r.sku_articulo AND av.id_sucursal_destino = v.id_sucursal
        LEFT JOIN sucursal s ON s.id_sucursal = v.id_sucursal
        WHERE ' . implode(' AND ', $where) . '
        GROUP BY v.id, v.id_sucursal, v.id_venta_sucursal, s.nombre_sucursal
        ORDER BY v.id ASC';

    $stmt = mysqli_prepare($conexion, $sql);
    if (!$stmt) {
        throw new RuntimeException(mysqli_error($conexion));
    }
    if ($types !== '') {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    if (!mysqli_stmt_execute($stmt)) {
        $err = mysqli_stmt_error($stmt);
        mysqli_stmt_close($stmt);
        throw new RuntimeException($err);
    }
    $res = mysqli_stmt_get_result($stmt);
    $filas = [];
    while ($row = $res ? mysqli_fetch_assoc($res) : null) {
        if (!$row) {
            break;
        }
        $filas[] = $row;
    }
    mysqli_stmt_close($stmt);

    return $filas;
}

$conexion = conectar_bd();
if (!$conexion) {
    fwrite(STDERR, "No hay conexión a la base de datos.\n");
    exit(1);
}

try {
    rar_linea('=== Reparación artículos reservados en ventas a plazos vendidas ===');
    rar_linea('Modo: ' . ($ejecutar ? 'EJECUTAR' : 'SOLO VISTA (añade --ejecutar para aplicar)'));
    if ($idVentaFiltro > 0) {
        rar_linea('Filtro id_venta: ' . $idVentaFiltro);
    }
    if ($idSucursalFiltro > 0) {
        rar_linea('Filtro id_sucursal: ' . $idSucursalFiltro);
    }
    rar_linea('');

    $ventas = rar_buscar_ventas_afectadas($conexion, $idVentaFiltro, $idSucursalFiltro);
    if (count($ventas) === 0) {
        rar_linea('No hay ventas vendidas a plazos con artículos en reservado.');
        mysqli_close($conexion);
        exit(0);
    }

    $totalArticulos = 0;
    foreach ($ventas as $v) {
        $n = (int) ($v['articulos_reservados'] ?? 0);
        $totalArticulos += $n;
        rar_linea(sprintf(
            'Venta id=%d sucursal=%d nº=%d | %d artículo(s) reservado(s): %s',
            (int) $v['id_venta'],
            (int) $v['id_sucursal'],
            (int) $v['id_venta_sucursal'],
            $n,
            (string) ($v['ids_articulos'] ?? '')
        ));
    }
    rar_linea('');
    rar_linea(sprintf('Total: %d venta(s), %d artículo(s) pendientes de pasar a vendido.', count($ventas), $totalArticulos));

    if (!$ejecutar) {
        rar_linea('');
        rar_linea('Vista previa completada. Repite con --ejecutar para aplicar.');
        mysqli_close($conexion);
        exit(0);
    }

    rar_linea('');
    rar_linea('Aplicando reparación...');

    $ventasReparadas = 0;
    $articulosReparados = 0;
    $errores = 0;

    foreach ($ventas as $v) {
        $idVenta = (int) $v['id_venta'];
        $idSucursal = (int) $v['id_sucursal'];
        $idVentaSucursal = (int) $v['id_venta_sucursal'];
        $nombreSucursal = trim((string) ($v['nombre_sucursal'] ?? ''));

        try {
            $n = venta_plazos_marcar_articulos_vendidos(
                $conexion,
                $idVenta,
                $idSucursal,
                $idVentaSucursal,
                $nombreSucursal,
                $usuarioReparacion,
                'Reparación histórica venta a plazos'
            );
            if ($n > 0) {
                $ventasReparadas++;
                $articulosReparados += $n;
                rar_linea("  OK venta {$idVenta}: {$n} artículo(s) actualizado(s).");
            } else {
                rar_linea("  AVISO venta {$idVenta}: ningún artículo actualizado (¿ya reparada?).");
            }
        } catch (Throwable $e) {
            $errores++;
            rar_linea("  ERROR venta {$idVenta}: " . $e->getMessage());
            insertErrorLog('reparar_articulos_reservados_ventas_plazos: venta ' . $idVenta . ' - ' . $e->getMessage());
        }
    }

    rar_linea('');
    rar_linea(sprintf(
        'Fin: %d venta(s) reparada(s), %d artículo(s) pasados a vendido, %d error(es).',
        $ventasReparadas,
        $articulosReparados,
        $errores
    ));

    mysqli_close($conexion);
    exit($errores > 0 ? 2 : 0);
} catch (Throwable $e) {
    if ($conexion) {
        mysqli_close($conexion);
    }
    fwrite(STDERR, 'Error fatal: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}
