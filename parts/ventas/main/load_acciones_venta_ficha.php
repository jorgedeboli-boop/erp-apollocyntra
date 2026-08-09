<?php
/**
 * Botones de acción de la ficha de venta (adelanto, anular, imprimir).
 */
require_once __DIR__ . '/../../../include/session.php';
require_once __DIR__ . '/../../../include/functions.php';

header('Content-Type: application/json; charset=utf-8');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Método no permitido']);
        exit;
    }

    $id_venta = isset($_POST['id_venta']) ? (int) $_POST['id_venta'] : 0;
    if ($id_venta <= 0) {
        throw new Exception('ID de venta no válido');
    }

    $conexion = conectar_bd();
    if (!$conexion) {
        throw new Exception('Sin conexión');
    }

    $stmt = mysqli_prepare(
        $conexion,
        'SELECT id, estado, venta_plazos, id_sucursal FROM ventas WHERE id = ? LIMIT 1'
    );
    if (!$stmt) {
        throw new Exception(mysqli_error($conexion));
    }
    mysqli_stmt_bind_param($stmt, 'i', $id_venta);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $venta = $res ? mysqli_fetch_assoc($res) : null;
    mysqli_stmt_close($stmt);
    if (!$venta) {
        throw new Exception('Venta no encontrada');
    }

    $id_sucursal = (int) ($venta['id_sucursal'] ?? 0);
    $plazos_vencidos_count = 0;
    if (($venta['venta_plazos'] ?? '') === 'si') {
        $stmtVen = mysqli_prepare(
            $conexion,
            "SELECT COUNT(id) AS c FROM ventas_plazos WHERE id_venta = ? AND estado = 'Vencido'"
        );
        if ($stmtVen) {
            mysqli_stmt_bind_param($stmtVen, 'i', $id_venta);
            mysqli_stmt_execute($stmtVen);
            $rven = mysqli_stmt_get_result($stmtVen);
            $rowVen = $rven ? mysqli_fetch_assoc($rven) : null;
            mysqli_stmt_close($stmtVen);
            $plazos_vencidos_count = (int) ($rowVen['c'] ?? 0);
        }
    }

    $est_adel = strtolower((string) ($venta['estado'] ?? ''));
    $venta_plazos_activa_en_curso = (($venta['venta_plazos'] ?? '') === 'si')
        && in_array($est_adel, ['enfecha', 'vencido'], true);
    //$venta_permite_adelanto_capital = $venta_plazos_activa_en_curso && $plazos_vencidos_count === 0;
    $venta_permite_adelanto_capital = '';
    $venta_permite_anular_plazos = $venta_plazos_activa_en_curso;

    $id_factura = 0;
    $id_factura_simplificada = 0;
    $factura_regimen = 'false';
    $id_rel_factura_fiskaly = 0;
    $origen_simplificada_url = '';
    if ($id_sucursal > 0) {
        $stmtFr = mysqli_prepare(
            $conexion,
            'SELECT id_factura, factura_regimen, id_rel_factura_fiskaly, factura_simplificada
             FROM facturas WHERE rel_id_venta = ? AND id_sucursal = ? LIMIT 1'
        );
        if ($stmtFr) {
            mysqli_stmt_bind_param($stmtFr, 'ii', $id_venta, $id_sucursal);
            mysqli_stmt_execute($stmtFr);
            $resFr = mysqli_stmt_get_result($stmtFr);
            if ($resFr && ($rowFr = mysqli_fetch_assoc($resFr))) {
                $esSimp = (string) ($rowFr['factura_simplificada'] ?? 'false') === 'true';
                $factura_regimen = (string) ($rowFr['factura_regimen'] ?? 'false');
                $id_rel_factura_fiskaly = (int) ($rowFr['id_rel_factura_fiskaly'] ?? 0);
                if ($esSimp) {
                    $id_factura_simplificada = (int) ($rowFr['id_factura'] ?? 0);
                    $origen_simplificada_url = 'facturas';
                } else {
                    $id_factura = (int) ($rowFr['id_factura'] ?? 0);
                }
            }
            mysqli_stmt_close($stmtFr);
        }
        if ($id_factura <= 0 && $id_factura_simplificada <= 0) {
            $stmtFs = mysqli_prepare(
                $conexion,
                'SELECT id_factura, factura_regimen, id_rel_factura_fiskaly
                 FROM facturas_simplificadas WHERE rel_id_venta = ? AND id_sucursal = ? LIMIT 1'
            );
            if ($stmtFs) {
                mysqli_stmt_bind_param($stmtFs, 'ii', $id_venta, $id_sucursal);
                mysqli_stmt_execute($stmtFs);
                $resFs = mysqli_stmt_get_result($stmtFs);
                if ($resFs && ($rowFs = mysqli_fetch_assoc($resFs))) {
                    $id_factura_simplificada = (int) ($rowFs['id_factura'] ?? 0);
                    $factura_regimen = (string) ($rowFs['factura_regimen'] ?? 'false');
                    $id_rel_factura_fiskaly = (int) ($rowFs['id_rel_factura_fiskaly'] ?? 0);
                    $origen_simplificada_url = 'historico';
                }
                mysqli_stmt_close($stmtFs);
            }
        }
    }

    $url_imprimir_factura = fiskalyUrlImpresionFactura(
        $id_factura,
        $factura_regimen,
        $id_rel_factura_fiskaly,
        false
    );
    $url_imprimir_ticket = fiskalyUrlImpresionFactura(
        $id_factura_simplificada,
        $factura_regimen,
        $id_rel_factura_fiskaly,
        true,
        'articulos',
        $origen_simplificada_url
    );
    $es_factura_fiskaly = fiskalyFacturaVinculadaEnCache($id_rel_factura_fiskaly);

    mysqli_close($conexion);

    $item_modulo = basename(dirname(__DIR__));
    $puede_acceder_editar_venta = usuario_puede_acceder_crud_tipo(
        $usuario_privilegio_id,
        crud_id_listar_modulo($item_modulo),
        'editar'
    );

    $url_enviar_factura = ($item_modulo === 'ventas_sucursal')
        ? 'parts/facturas_sucursales/listar/enviar_factura.php'
        : 'parts/facturas/listar/enviar_factura.php';
    $url_enviar_factura_simplificada = ($item_modulo === 'ventas_sucursal')
        ? 'parts/facturas_simplificadas_sucursal/listar/enviar_factura_simplificada.php'
        : 'parts/facturas_simplificadas/listar/enviar_factura_simplificada.php';

    ob_start();
    if ($venta_permite_adelanto_capital) : ?>
        <button type="button" class="btn btn-info btn-xs waves-effect waves-light button-actions-datatable" style="border-radius: 7px !important;" onclick="abrirModalAdelantoCapitalVenta()">
            <span class="icon-base ri ri-progress-6-fill icon-20px me-1"></span>Adelantar capital
        </button>
    <?php endif;
    if ($venta_permite_anular_plazos && $puede_acceder_editar_venta) : ?>
        <button type="button" class="btn btn-danger btn-xs waves-effect waves-light button-actions-datatable" style="border-radius: 7px !important;" onclick="anularVentaPlazos()">
            <span class="icon-base ri ri-close-circle-line icon-20px me-1"></span>Anular venta a plazos
        </button>
    <?php endif;
    $venta_plazos_vendida_sin_factura = (($venta['venta_plazos'] ?? '') === 'si')
        && strtolower((string) ($venta['estado'] ?? '')) === 'vendido'
        && $id_factura <= 0
        && $id_factura_simplificada <= 0;
    if ($venta_plazos_vendida_sin_factura && $puede_acceder_editar_venta) : ?>
        <button type="button" class="btn btn-success btn-xs waves-effect waves-light button-actions-datatable" style="border-radius: 7px !important;" onclick="generarTicketVentaPlazosFicha()">
            <span class="icon-base ri ri-ticket-2-line icon-20px me-1"></span>Generar ticket
        </button>
    <?php endif;
    if ($id_factura > 0) : ?>
        <a href="<?php echo htmlspecialchars($url_imprimir_factura, ENT_QUOTES, 'UTF-8'); ?>" type="button" style="border-radius: 7px !important;" class="btn btn-primary btn-xs waves-effect waves-light button-actions-datatable" target="_blank" rel="noopener noreferrer"><i class="icon-base ri ri-printer-fill me-1"></i>Imprimir factura</a>
        <button type="button" style="border-radius: 7px !important;" class="btn btn-outline-primary btn-xs waves-effect waves-light button-actions-datatable" onclick="enviarFacturaEmailVentaFicha(this)" data-id-factura="<?php echo (int) $id_factura; ?>" data-url-envio="<?php echo htmlspecialchars($url_enviar_factura, ENT_QUOTES, 'UTF-8'); ?>" data-titulo-envio="Enviar factura"><i class="icon-base ri ri-mail-send-line me-1"></i>Enviar factura por email</button>
    <?php elseif ($id_factura_simplificada > 0) : ?>
        <a href="<?php echo htmlspecialchars($url_imprimir_ticket, ENT_QUOTES, 'UTF-8'); ?>" type="button" style="border-radius: 7px !important;" class="btn btn-primary btn-xs waves-effect waves-light button-actions-datatable" target="_blank" rel="noopener noreferrer"><i class="icon-base ri ri-printer-fill me-1"></i>Imprimir ticket</a>
        <button type="button" style="border-radius: 7px !important;" class="btn btn-outline-primary btn-xs waves-effect waves-light button-actions-datatable" onclick="enviarFacturaEmailVentaFicha(this)" data-id-factura="<?php echo (int) $id_factura_simplificada; ?>" data-url-envio="<?php echo htmlspecialchars($url_enviar_factura_simplificada, ENT_QUOTES, 'UTF-8'); ?>" data-titulo-envio="Enviar ticket"><i class="icon-base ri ri-mail-send-line me-1"></i>Enviar ticket por email</button>
    <?php endif;
    $html = ob_get_clean();

    echo json_encode([
        'success' => true,
        'html' => $html,
        'venta_permite_adelanto_capital' => $venta_permite_adelanto_capital,
        'venta_permite_anular_plazos' => $venta_permite_anular_plazos,
        'id_factura' => $id_factura,
        'id_factura_simplificada' => $id_factura_simplificada,
        'es_factura_fiskaly' => $es_factura_fiskaly,
        'factura_regimen' => $factura_regimen,
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
