<?php

/**
 * Informe semanal MANUAL con fecha (debe ser fecha_semana_desde).
 *
 * curl:
 *   curl "https://DOMINIO/parts/cron_manual_informes/informe_semanal.php?fecha=2026-07-20"
 *
 * CLI:
 *   php parts/cron_manual_informes/informe_semanal.php --fecha=2026-07-20
 */

require_once __DIR__ . '/_bootstrap.php';

cron_iniciar_salida('Informe semanal MANUAL');
cron_linea($esCli ? 'Entorno: CLI (manual)' : 'Entorno: HTTP/cURL (manual)');
cron_linea('>> Inicio: informe_semanal (manual)');

$conexion = null;

try {
    $fecha_informe_today = cron_manual_resolver_fecha();
    cron_linea('Fecha manual: ' . $fecha_informe_today);
    cron_linea('Nota: solo genera informe si esa fecha es inicio de semana (fecha_semana_desde).');

    $conexion = cron_manual_preparar_entorno('/parts/cron_manual_informes/informe_semanal.php');
    cron_linea('OK: conexión a base de datos establecida.');

    $pasosInforme = array(
        'consultar-semana-inicio.php',
        'generar-informe_semanal.php',
        'informes-semanal-caja.php',
        'informes-semanal-ajustes_de_lotes.php',
        'informes-semanal-operaciones-tarjetas.php',
        'informes-semanal-operaciones-transferencias.php',
        'informes-semanal-operaciones-bizum.php',
        'informes-semanal-compras-oro.php',
        'informes-semanal-compras-plata.php',
        'informes-semanal-empenyos-global.php',
        'informes-semanal-empenyos-oro.php',
        'informes-semanal-empenyos-plata.php',
        'informes-semanal-empenyos-retirados.php',
        'informes-semanal-empenyos-vencidos.php',
        'informes-semanal-empenyos-perdidos.php',
        'informes-semanal-empenyos-renovaciones.php',
        'informes-semanal-beneficios-empenyos.php',
        'informes-semanal-lotes-intervenidos.php',
        'informes-semanal-stock-valorizado.php',
        'informes-semanal-beneficio-oro-fundido.php',
        'informes-semanal-beneficio-plata-fundido.php',
        'informes-semanal-beneficio-fundido-total.php',
        'informes-semanal-calculo-coste-articulos-venta.php',
        'informes-semanal-beneficio-articulos-venta.php',
        'informes-semanal-ventas.php',
        'informes-semanal-ventas-plazos.php',
        'informes-semanal-ventas-web.php',
        'informes-semanal-ventas-forma-pago.php',
        'informes-semanal-devoluciones.php',
        'informe-semanal-calcular-beneficio.php',
        'informe-semanal-calcular-ranking-tiendas.php',
        'finalizar-informe-semanal.php',
    );

    foreach ($pasosInforme as $archivo) {
        $origen = cron_manual_paso_es_local($archivo) ? 'manual' : 'CRON';
        cron_linea('  - Cargando (' . $origen . ') ' . $archivo);
        require cron_manual_ruta_paso($archivo);
    }

    cron_linea('>> Fin: informe_semanal (manual) | fecha=' . $fecha_informe_today);
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
