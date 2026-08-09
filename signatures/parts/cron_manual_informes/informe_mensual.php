<?php

/**
 * Informe mensual MANUAL con fecha (debe ser fecha_mes_desde).
 *
 * curl:
 *   curl "https://DOMINIO/parts/cron_manual_informes/informe_mensual.php?fecha=2026-07-01"
 *
 * CLI:
 *   php parts/cron_manual_informes/informe_mensual.php --fecha=2026-07-01
 */

require_once __DIR__ . '/_bootstrap.php';

cron_iniciar_salida('Informe mensual MANUAL');
cron_linea($esCli ? 'Entorno: CLI (manual)' : 'Entorno: HTTP/cURL (manual)');
cron_linea('>> Inicio: informe_mensual (manual)');

$conexion = null;

try {
    $fecha_informe_today = cron_manual_resolver_fecha();
    cron_linea('Fecha manual: ' . $fecha_informe_today);
    cron_linea('Nota: solo genera informe si esa fecha es inicio de mes (fecha_mes_desde).');

    $conexion = cron_manual_preparar_entorno('/parts/cron_manual_informes/informe_mensual.php');
    cron_linea('OK: conexión a base de datos establecida.');

    $pasosInforme = array(
        'consultar-mes-inicio.php',
        'generar-informe_mensual.php',
        'informes-mensual-caja.php',
        'informes-mensual-ajustes_de_lotes.php',
        'informes-mensual-operaciones-tarjetas.php',
        'informes-mensual-operaciones-transferencias.php',
        'informes-mensual-operaciones-bizum.php',
        'informes-mensual-compras-oro.php',
        'informes-mensual-compras-plata.php',
        'informes-mensual-empenyos-global.php',
        'informes-mensual-empenyos-oro.php',
        'informes-mensual-empenyos-plata.php',
        'informes-mensual-empenyos-retirados.php',
        'informes-mensual-empenyos-vencidos.php',
        'informes-mensual-empenyos-perdidos.php',
        'informes-mensual-empenyos-renovaciones.php',
        'informes-mensual-beneficios-empenyos.php',
        'informes-mensual-lotes-intervenidos.php',
        'informes-mensual-stock-valorizado.php',
        'informes-mensual-beneficio-oro-fundido.php',
        'informes-mensual-beneficio-plata-fundido.php',
        'informes-mensual-beneficio-fundido-total.php',
        'informes-mensual-calculo-coste-articulos-venta.php',
        'informes-mensual-beneficio-articulos-venta.php',
        'informes-mensual-ventas.php',
        'informes-mensual-ventas-plazos.php',
        'informes-mensual-ventas-web.php',
        'informes-mensual-ventas-forma-pago.php',
        'informes-mensual-devoluciones.php',
        'informes-mensual-gasto.php',
        'informe-mensual-calcular-beneficio.php',
        'informe-mensual-calcular-ranking-tiendas.php',
        'finalizar-informe-mensual.php',
    );

    foreach ($pasosInforme as $archivo) {
        $origen = cron_manual_paso_es_local($archivo) ? 'manual' : 'CRON';
        cron_linea('  - Cargando (' . $origen . ') ' . $archivo);
        require cron_manual_ruta_paso($archivo);
    }

    cron_linea('>> Fin: informe_mensual (manual) | fecha=' . $fecha_informe_today);
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
