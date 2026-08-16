<?php
require_once '../../../include/session.php';
require_once '../../../include/functions.php';

header('Content-Type: application/json');

try {
    $sku = isset($_GET['sku']) ? (int) $_GET['sku'] : 0;
    if ($sku <= 0) {
        throw new Exception('Debe proporcionar el SKU del artículo');
    }

    $rel_id_empresa = obtener_rel_id_empresa_sesion();
    if ($rel_id_empresa <= 0) {
        throw new Exception('El usuario no tiene empresa asignada');
    }

    $conexion = conectar_bd();

    $query = "SELECT
                a.sku AS id,
                a.sku,
                a.descripcion,
                a.precio,
                a.estado,
                a.categoria_articulo AS tipo,
                0 AS peso
              FROM articulos a
              WHERE a.sku = ?
                AND a.empresa_id_rel = ?
                AND a.estado IN ('enventa', 'en_venta')
              LIMIT 1";

    $stmt = mysqli_prepare($conexion, $query);
    if (!$stmt) {
        throw new Exception('Error en preparación de consulta: ' . mysqli_error($conexion));
    }

    mysqli_stmt_bind_param($stmt, 'ii', $sku, $rel_id_empresa);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $articulo = $result ? mysqli_fetch_assoc($result) : null;
    mysqli_stmt_close($stmt);
    mysqli_close($conexion);

    if ($articulo) {
        $articulo['peso'] = 0;
        $articulo['tipo'] = (string) ($articulo['tipo'] ?? '');
        echo json_encode([
            'success' => true,
            'encontrado' => true,
            'articulo' => $articulo,
        ]);
        return;
    }

    echo json_encode([
        'success' => true,
        'encontrado' => false,
        'message' => 'Artículo no encontrado o no está disponible para la venta',
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
    ]);
}
