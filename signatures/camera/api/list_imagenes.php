<?php
/**
 * Listado de imágenes para el visor centralizado (camera).
 * POST: tipo (ej. cliente), id (id numérico según tipo), id_sucursal opcional.
 */
require_once __DIR__ . '/../../include/session.php';
require_once __DIR__ . '/../lib/imagenes_catalogo.php';

header('Content-Type: application/json; charset=utf-8');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Método no permitido']);
        exit;
    }

    $tipo = isset($_POST['tipo']) ? trim((string) $_POST['tipo']) : '';
    $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
    $id_sucursal = isset($_POST['id_sucursal']) ? (int) $_POST['id_sucursal'] : 0;

    if ($tipo === '') {
        throw new InvalidArgumentException('Parámetro tipo requerido');
    }
    if ($id <= 0) {
        throw new InvalidArgumentException('Parámetro id no válido');
    }

    switch ($tipo) {
        case 'cliente':
            $imagenes = camera_catalog_imagenes_cliente($id);
            break;
        case 'lote':
            if ($id_sucursal <= 0) {
                throw new InvalidArgumentException('Parámetro id_sucursal requerido para lote');
            }
            $imagenes = camera_catalog_imagenes_lote($id, $id_sucursal);
            break;
        case 'gasto':
            $imagenes = camera_catalog_imagenes_gasto($id);
            break;
        case 'gasto_prueba':
            $imagenes = camera_catalog_imagenes_gasto($id, 'fotos_gastos_pruebas');
            break;
        case 'renovacion':
            if ($id_sucursal <= 0) {
                throw new InvalidArgumentException('Parámetro id_sucursal requerido para renovacion');
            }
            $imagenes = camera_catalog_imagenes_renovacion($id, $id_sucursal);
            break;
        case 'adelanto':
            // En lotes: id = id_foto_cache_adelanto (fotos_app_adelantos_cache)
            if ($id_sucursal > 0 && function_exists('camera_catalog_imagenes_adelanto_cache_sucursal')) {
                $imagenes = camera_catalog_imagenes_adelanto_cache_sucursal($id, $id_sucursal);
            } else {
                $imagenes = camera_catalog_imagenes_adelanto_cache($id);
            }
            break;
        case 'articulo':
            $imagenes = camera_catalog_imagenes_articulo($id);
            break;
        case 'venta':
            $imagenes = camera_catalog_imagenes_venta($id);
            break;
        case 'articulo_venta':
            $imagenes = camera_catalog_imagenes_articulo_venta_ticket($id);
            break;
        case 'adelanto_venta':
            // En ventas: id = id_foto cache (fotos_app_adelantos_cache)
            if ($id_sucursal > 0 && function_exists('camera_catalog_imagenes_adelanto_cache_sucursal')) {
                $imagenes = camera_catalog_imagenes_adelanto_cache_sucursal($id, $id_sucursal);
            } else {
                $imagenes = camera_catalog_imagenes_adelanto_cache($id);
            }
            break;
        case 'plazo_venta':
            // En ventas: id = id_foto cache (fotos_app_adelantos_cache)
            if ($id_sucursal > 0 && function_exists('camera_catalog_imagenes_adelanto_cache_sucursal')) {
                $imagenes = camera_catalog_imagenes_adelanto_cache_sucursal($id, $id_sucursal);
            } else {
                $imagenes = camera_catalog_imagenes_adelanto_cache($id);
            }
            break;
        case 'traspaso':
            $imagenes = camera_catalog_imagenes_traspaso($id);
            break;
        default:
            throw new InvalidArgumentException('Tipo de documentación no soportado aún: ' . $tipo);
    }

    echo json_encode([
        'success' => true,
        'imagenes' => $imagenes,
        'total' => count($imagenes),
    ]);
} catch (InvalidArgumentException $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
