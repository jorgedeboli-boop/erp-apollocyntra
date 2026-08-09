<?php

/**
 * Informe diario MANUAL con fecha.
 *
 * curl:
 *   curl "https://DOMINIO/parts/cron_manual_informes/informe_diario.php?fecha=2026-07-20"
 *
 * CLI:
 *   php parts/cron_manual_informes/informe_diario.php --fecha=2026-07-20
 */

require_once __DIR__ . '/_bootstrap.php';

cron_iniciar_salida('Informe diario MANUAL');
cron_linea($esCli ? 'Entorno: CLI (manual)' : 'Entorno: HTTP/cURL (manual)');
cron_linea('>> Inicio: informe_diario (manual)');

$conexion = null;

try {
    $fecha_informe_today = cron_manual_resolver_fecha();
    cron_linea('Fecha manual: ' . $fecha_informe_today);

    $conexion = cron_manual_preparar_entorno('/parts/cron_manual_informes/informe_diario.php');
    cron_linea('OK: conexión a base de datos establecida.');

    $pasosInforme = array(
        'calculo_semana_numero.php',
        'generar-informe.php',
        'informes-compras-oro.php',
        'informes-compras-plata.php',
        'informes-empenyos-global.php',
        'informes-empenyos-oro.php',
        'informes-empenyos-plata.php',
        'informes-empenyos-retirados.php',
        'informes-empenyos-vencidos.php',
        'informes-empenyos-perdidos.php',
        'informes-empenyos-renovaciones.php',
        'informes-lotes-intervenidos.php',
        'informes-caja.php',
        'informes-ajustes_de_lotes.php',
        'informes-operaciones-tarjetas.php',
        'informes-operaciones-transferencias.php',
        'informes-operaciones-bizum.php',
        'informes-stock-valorizado.php',
        'informes-ventas.php',
        'informes-ventas-plazos.php',
        'informes-ventas-web.php',
        'informes_ventas_forma_pago.php',
        'informes-devoluciones.php',
        'informes-gastos.php',
        'informes-precio-oro.php',
        'informes-calculo-totales.php',
        'finalizar-informe.php',
    );

    foreach ($pasosInforme as $archivo) {
        $origen = cron_manual_paso_es_local($archivo) ? 'manual' : 'CRON';
        cron_linea('  - Cargando (' . $origen . ') ' . $archivo);
        require cron_manual_ruta_paso($archivo);
    }

    cron_linea('>> Fin: informe_diario (manual) | fecha=' . $fecha_informe_today);
} catch (Exception $e) {
    cron_linea('ERROR: ' . $e->getMessage());
    cron_cerrar_salida();
    exit(1);
} catch (Error $e) {
    cron_linea('ERROR: ' . $e->getMessage());
    cron_cerrar_salida();
    exit(1);
}

cron_cerrar_salida();
