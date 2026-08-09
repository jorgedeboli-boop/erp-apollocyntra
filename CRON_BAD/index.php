<?php
declare(strict_types=1);

/**
 * Modo prueba manual: solo ejecuta el paso activo en simulación (sin escrituras MySQL).
 * Cambiar CRON_PASO_ACTIVO para ir paso a paso.
 */
const CRON_MODO_MANUAL = true;
const CRON_PASO_ACTIVO = 'lotes_liberados';

$esCli = (PHP_SAPI === 'cli');

function cron_imprimir(string $mensaje): void
{
    global $esCli;
    if ($esCli) {
        echo $mensaje;
        return;
    }
    echo htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8');
}

function cron_imprimir_bloque(array $lineas): void
{
    global $esCli;
    if (!$esCli) {
        echo '<pre style="font-family:monospace;font-size:13px;line-height:1.45;">';
    }
    foreach ($lineas as $linea) {
        cron_imprimir($linea . "\n");
    }
    if (!$esCli) {
        echo '</pre>';
    }
}

if (!$esCli) {
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html lang="es"><head><meta charset="utf-8"><title>CRON manual</title></head><body>';
    echo '<h1>CRON — ejecución manual</h1>';
}

if (CRON_MODO_MANUAL) {
    cron_imprimir_bloque([
        '=== MODO MANUAL ACTIVO ===',
        'Paso activo: ' . CRON_PASO_ACTIVO,
        'Simulación: SÍ (no se actualiza MySQL)',
        str_repeat('-', 60),
    ]);
}

// Evitar ejecuciones simultáneas (cron solapado) salvo en modo manual.
$lockFile = __DIR__ . '/cron.lock';
$lockHandle = null;
if (!CRON_MODO_MANUAL) {
    $lockHandle = @fopen($lockFile, 'c');
    if (!$lockHandle) {
        fwrite(STDERR, "Error: no se pudo abrir el lockfile.\n");
        exit(1);
    }
    if (!flock($lockHandle, LOCK_EX | LOCK_NB)) {
        echo "SKIP: ya hay un cron en ejecución.\n";
        exit(0);
    }
}

// Este script está pensado para ejecutarse por cron (CLI), donde $_SERVER suele venir vacío.
$_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'] ?? 'localhost';
$_SERVER['REQUEST_URI'] = $_SERVER['REQUEST_URI'] ?? '/CRON/index.php';
$_SERVER['REMOTE_ADDR'] = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
$_SERVER['HTTP_USER_AGENT'] = $_SERVER['HTTP_USER_AGENT'] ?? 'cron';

require_once __DIR__ . '/../include/functions.php';

$conexion = conectar_bd();
if (!$conexion) {
    fwrite(STDERR, "Error: no se pudo conectar a la base de datos.\n");
    exit(1);
}

$salida = [];

if (!CRON_MODO_MANUAL || CRON_PASO_ACTIVO === 'lotes_liberados') {
    require_once __DIR__ . '/lotes_liberados.php';
    try {
        $soloSimulacion = CRON_MODO_MANUAL;
        $salida[] = '>> PASO: lotes_liberados';
        $salida[] = $soloSimulacion
            ? '   Modo: SIMULACIÓN (solo lectura, sin UPDATE/INSERT)'
            : '   Modo: PRODUCCIÓN';

        $res = cron_lotes_liberados($conexion, $soloSimulacion);

        $salida[] = sprintf(
            '   Resumen: sucursales=%d, lotes_a_liberar=%d, errores=%d',
            (int) $res['sucursales'],
            (int) $res['lotes_liberados'],
            (int) $res['errores']
        );

        if (!empty($res['detalle'])) {
            $salida[] = '   Detalle por sucursal:';
            foreach ($res['detalle'] as $bloque) {
                $salida[] = sprintf(
                    '   - Sucursal %d | tabla %s | dias_liberacion=%d | lotes=%d',
                    (int) $bloque['sucursal_id'],
                    (string) $bloque['tabla'],
                    (int) $bloque['dias_liberacion'],
                    count($bloque['lotes'])
                );
                foreach ($bloque['lotes'] as $lote) {
                    $salida[] = sprintf(
                        '       * Lote %d | compra=%s | dias_desde_compra=%d',
                        (int) $lote['id_lote'],
                        (string) $lote['fecha_compra'],
                        (int) $lote['dias_desde_compra']
                    );
                }
            }
        } else {
            $salida[] = '   No hay lotes pendientes de liberar con los criterios actuales.';
        }

        $salida[] = 'OK: lotes_liberados finalizado.';
    } catch (Throwable $e) {
        $salida[] = 'ERROR en lotes_liberados: ' . $e->getMessage();
        cron_imprimir_bloque($salida);
        mysqli_close($conexion);
        if (!$esCli) {
            echo '</body></html>';
        }
        exit(1);
    }
}

if (CRON_MODO_MANUAL) {
    $salida[] = str_repeat('-', 60);
    $salida[] = 'Resto de pasos del cron: BLOQUEADOS en modo manual.';
    $salida[] = 'Siguiente paso: cambiar CRON_PASO_ACTIVO en CRON/index.php cuando toque.';
    cron_imprimir_bloque($salida);
    mysqli_close($conexion);
    if (!$esCli) {
        echo '</body></html>';
    }
    exit(0);
}

/*
// AQUI LLAMAREMOS A OTROS ARCHIVOS PHP EN LA MISMA CARPETA CRON QUE HARAN DIFERENTES TAREAS
require_once __DIR__ . '/historico_empenos_vencidos.php';
try {
    $res2 = cron_historico_empenos_vencidos($conexion);
    echo "OK: historico_empenos_vencidos (sucursales={$res2['sucursales']}, validas={$res2['sucursales_validas']}, hist_inexistentes={$res2['tablas_historico_inexistentes']}, lotes_inexistentes={$res2['tablas_lotes_inexistentes']}, procesadas={$res2['renovaciones_vencidas_procesadas']}, errores={$res2['errores']})\n";
} catch (Throwable $e) {
    fwrite(STDERR, "Error en historico_empenos_vencidos: " . $e->getMessage() . "\n");
    mysqli_close($conexion);
    exit(1);
}

require_once __DIR__ . '/empenos_perdidos.php';
try {
    $res3 = cron_empenos_perdidos($conexion);
    echo "OK: empenos_perdidos (sucursales={$res3['sucursales']}, validas={$res3['sucursales_validas']}, lotes_inexistentes={$res3['tablas_lotes_inexistentes']}, hist_inexistentes={$res3['tablas_historico_inexistentes']}, evaluados={$res3['lotes_evaluados']}, perdidos={$res3['lotes_perdidos']}, errores={$res3['errores']})\n";
} catch (Throwable $e) {
    fwrite(STDERR, "Error en empenos_perdidos: " . $e->getMessage() . "\n");
    mysqli_close($conexion);
    exit(1);
}

require_once __DIR__ . '/empenos_perdidos_no_perdibles.php';
try {
    $res4 = cron_empenos_perdidos_no_perdibles($conexion);
    echo "OK: empenos_perdidos_no_perdibles (sucursales={$res4['sucursales']}, validas={$res4['sucursales_validas']}, lotes_inexistentes={$res4['tablas_lotes_inexistentes']}, hist_inexistentes={$res4['tablas_historico_inexistentes']}, control_inexistente={$res4['tabla_control_inexistente']}, evaluados={$res4['lotes_evaluados']}, marcados={$res4['lotes_marcados']}, errores={$res4['errores']})\n";
} catch (Throwable $e) {
    fwrite(STDERR, "Error en empenos_perdidos_no_perdibles: " . $e->getMessage() . "\n");
    mysqli_close($conexion);
    exit(1);
}

require_once __DIR__ . '/lotes_para_enviar.php';
try {
    $res5 = cron_lotes_para_enviar($conexion);
    if (!empty($res5['skipped_no_lunes'])) {
        echo "SKIP: lotes_para_enviar (no es lunes)\n";
    } else {
        echo "OK: lotes_para_enviar (sucursales={$res5['sucursales']}, validas={$res5['sucursales_validas']}, lotes_inexistentes={$res5['tablas_lotes_inexistentes']}, perdidos={$res5['perdidos_actualizados']}, liberados={$res5['liberados_actualizados']}, intervenidos={$res5['intervenidos_insertados']}, errores={$res5['errores']})\n";
    }
} catch (Throwable $e) {
    fwrite(STDERR, "Error en lotes_para_enviar: " . $e->getMessage() . "\n");
    mysqli_close($conexion);
    exit(1);
}

require_once __DIR__ . '/vencimiento_empeno_29_febrero.php';
try {
    $res6 = cron_vencimiento_empeno_29_febrero($conexion);
    if (!empty($res6['skipped_bisiesto'])) {
        echo "SKIP: vencimiento_empeno_29_febrero (año bisiesto)\n";
    } else {
        echo "OK: vencimiento_empeno_29_febrero (sucursales={$res6['sucursales']}, validas={$res6['sucursales_validas']}, hist_inexistentes={$res6['tablas_historico_inexistentes']}, lotes_inexistentes={$res6['tablas_lotes_inexistentes']}, upd_hist={$res6['actualizados_historico']}, upd_lotes={$res6['actualizados_lotes']}, errores={$res6['errores']})\n";
    }
} catch (Throwable $e) {
    fwrite(STDERR, "Error en vencimiento_empeno_29_febrero: " . $e->getMessage() . "\n");
    mysqli_close($conexion);
    exit(1);
}

require_once __DIR__ . '/apertura_de_caja.php';
try {
    $res7 = cron_apertura_de_caja($conexion);
    echo "OK: apertura_de_caja (sucursales={$res7['sucursales']}, validas={$res7['sucursales_validas']}, mov_inexistentes={$res7['tablas_movimientos_inexistentes']}, aperturas={$res7['aperturas']}, cierres={$res7['cierres']}, no_cerradas={$res7['cajas_no_cerradas']}, errores={$res7['errores']})\n";
} catch (Throwable $e) {
    fwrite(STDERR, "Error en apertura_de_caja: " . $e->getMessage() . "\n");
    mysqli_close($conexion);
    exit(1);
}

require_once __DIR__ . '/generar_gastos_variables.php';
try {
    $res8 = cron_generar_gastos_variables($conexion);
    echo "OK: generar_gastos_variables (gastos_fijos={$res8['gastos_fijos']}, creados={$res8['gastos_creados']}, existentes={$res8['gastos_existentes']}, errores={$res8['errores']})\n";
} catch (Throwable $e) {
    fwrite(STDERR, "Error en generar_gastos_variables: " . $e->getMessage() . "\n");
    mysqli_close($conexion);
    exit(1);
}

require_once __DIR__ . '/borrar_registros_web.php';
try {
    $res9 = cron_borrar_registros_web($conexion, 500);
    if (!empty($res9['skipped_no_table'])) {
        echo "SKIP: borrar_registros_web (no existe test_cron)\n";
    } else {
        echo "OK: borrar_registros_web (borrados={$res9['borrados']}, errores={$res9['errores']})\n";
    }
} catch (Throwable $e) {
    fwrite(STDERR, "Error en borrar_registros_web: " . $e->getMessage() . "\n");
    mysqli_close($conexion);
    exit(1);
}

require_once __DIR__ . '/revisar_plazos_vencidos.php';
try {
    $res10 = cron_revisar_plazos_vencidos($conexion);
    echo "OK: revisar_plazos_vencidos (procesados={$res10['plazos_procesados']}, nuevos_plazos={$res10['plazos_nuevos']}, errores={$res10['errores']})\n";
} catch (Throwable $e) {
    fwrite(STDERR, "Error en revisar_plazos_vencidos: " . $e->getMessage() . "\n");
    mysqli_close($conexion);
    exit(1);
}

$sql = "INSERT INTO tareas_cron (descripcion_evento, fecha) VALUES (?, NOW())";
$stmt = mysqli_prepare($conexion, $sql);
if (!$stmt) {
    fwrite(STDERR, "Error: no se pudo preparar el INSERT.\n");
    mysqli_close($conexion);
    exit(1);
}

$descripcion = 'Cron finalizado';
mysqli_stmt_bind_param($stmt, 's', $descripcion);
$ok = mysqli_stmt_execute($stmt);

if (!$ok) {
    fwrite(STDERR, "Error: no se pudo ejecutar el INSERT.\n");
    mysqli_stmt_close($stmt);
    mysqli_close($conexion);
    exit(1);
}

mysqli_stmt_close($stmt);
mysqli_close($conexion);

echo "OK: Insertado evento en tareas_cron.\n";
exit(0);
*/
