<?php
declare(strict_types=1);

/**
 * Borra registros antiguos de `test_cron` en tandas.
 * Equivalente al legacy: DELETE ... ORDER BY id_test_cron ASC LIMIT 500
 */

function cron_borrar_registros_web(mysqli $conexion, int $limit = 500): array
{
    $resumen = [
        'skipped_no_table' => 0,
        'borrados' => 0,
        'errores' => 0,
    ];

    @mysqli_set_charset($conexion, 'utf8');

    $limit = max(1, min(5000, $limit));

    // Verificar existencia de tabla
    $stExists = mysqli_prepare(
        $conexion,
        "SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'test_cron'"
    );
    if ($stExists) {
        mysqli_stmt_execute($stExists);
        $r = mysqli_stmt_get_result($stExists);
        $existe = $r && mysqli_num_rows($r) > 0;
        mysqli_stmt_close($stExists);
        if (!$existe) {
            $resumen['skipped_no_table'] = 1;
            return $resumen;
        }
    }

    // Nota: LIMIT no puede parametrizarse en MySQLi de forma portable; se fuerza como int.
    $sql = "DELETE FROM test_cron ORDER BY id_test_cron ASC LIMIT {$limit}";
    $ok = mysqli_query($conexion, $sql);
    if (!$ok) {
        $resumen['errores']++;
        fwrite(STDERR, "[borrar_registros_web] Error DELETE test_cron: " . mysqli_error($conexion) . "\n");
        return $resumen;
    }

    $resumen['borrados'] = (int)mysqli_affected_rows($conexion);
    return $resumen;
}

