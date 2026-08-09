<?php
/**
 * Fragmento UI: modal QR para foto desde móvil (mismo flujo que camera-qr.js).
 * POST: tipo, id, id_sucursal
 * Respuesta JSON: modal_html, modal_id, qr_container_id, title, refresh_hint
 */
require_once __DIR__ . '/../../include/session.php';

header('Content-Type: application/json; charset=utf-8');

$tipos_ok = [
    'cliente', 'lote', 'gasto', 'gasto_prueba', 'renovacion', 'adelanto', 'articulo', 'venta',
    'articulo_venta', 'adelanto_venta', 'plazo_venta', 'autorizar_gasto', 'ia_chat', 'documento_ocr', 'factura_ocr', 'traspaso',
];

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Método no permitido']);
        exit;
    }

    $tipo = isset($_POST['tipo']) ? trim((string) $_POST['tipo']) : '';
    $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
    $id_sucursal = isset($_POST['id_sucursal']) ? (int) $_POST['id_sucursal'] : 0;

    if (!in_array($tipo, $tipos_ok, true)) {
        throw new InvalidArgumentException('tipo no válido');
    }
    if ($id <= 0) {
        throw new InvalidArgumentException('id no válido');
    }
    if ($id_sucursal <= 0) {
        throw new InvalidArgumentException('id_sucursal no válido');
    }

    $slug = preg_replace('/[^a-z0-9]/i', '_', $tipo);
    $suffix = substr(str_replace('.', '', uniqid('', true)), -8);
    $modal_id = 'modalCamDp_' . $slug . '_' . $id . '_' . $suffix;
    $qr_container_id = 'qrcode_camdp_' . $slug . '_' . $id . '_' . $suffix;

    $titles = [
        'cliente' => 'Hacer foto desde móvil',
        'lote' => 'Foto del lote desde móvil',
        'gasto' => 'Documento del gasto desde móvil',
        'gasto_prueba' => 'Documento del gasto de prueba desde móvil',
        'renovacion' => 'Comprobante de pago desde móvil',
        'adelanto' => 'Comprobante de adelanto desde móvil',
        'articulo' => 'Foto del artículo desde móvil',
        'venta' => 'Comprobante desde móvil',
        'articulo_venta' => 'Foto del ticket desde móvil',
        'adelanto_venta' => 'Comprobante de adelanto (venta) desde móvil',
        'plazo_venta' => 'Comprobante de plazo desde móvil',
        'autorizar_gasto' => 'Documento desde móvil',
        'ia_chat' => 'Foto para el asistente IA (móvil)',
        'documento_ocr' => 'Foto del documento desde móvil',
        'factura_ocr' => 'Foto de factura desde móvil',
        'traspaso' => 'Documento del traspaso desde móvil',
    ];
    $title = $titles[$tipo] ?? 'Escanear código QR';

    // Importante: este modal QR se usa como "overlay" y NO debe cerrar el modal que esté debajo.
    // Por eso se fuerza backdrop false (sin overlay) y focus false (no roba el foco).
    $modal_html = '<div class="modal fade" id="' . htmlspecialchars($modal_id, ENT_QUOTES, 'UTF-8') . '" tabindex="-1" aria-hidden="true" data-bs-backdrop="false" data-bs-focus="false">'
        . '<div class="modal-dialog modal-dialog-centered">'
        . '<div class="modal-content">'
        . '<div class="modal-header">'
        . '<h5 class="modal-title">' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</h5>'
        . '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>'
        . '</div>'
        . '<div class="modal-body text-center py-5">'
        . '<p class="mb-4">Escanee el código QR con su móvil</p>'
        . '<div class="d-flex justify-content-center"><div id="' . htmlspecialchars($qr_container_id, ENT_QUOTES, 'UTF-8') . '"></div></div>'
        . '</div>'
        . '<div class="modal-footer">'
        . '<button type="button" class="btn btn-primary camdp-btn-refresh-qr"><i class="icon-base ri ri-refresh-line me-2"></i>Generar nuevo QR</button>'
        . '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>'
        . '<button class="btn btn-primary d-none" id="btnSubiendoFotos" type="button"><span class="spinner-border me-1" role="status" aria-hidden="true"></span>Subiéndose fotos..</button>'
        . '</div>'
        . '</div></div></div>';

    echo json_encode([
        'success' => true,
        'tipo' => $tipo,
        'id' => $id,
        'id_sucursal' => $id_sucursal,
        'modal_id' => $modal_id,
        'qr_container_id' => $qr_container_id,
        'title' => $title,
        'modal_html' => $modal_html,
    ]);
} catch (InvalidArgumentException $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
