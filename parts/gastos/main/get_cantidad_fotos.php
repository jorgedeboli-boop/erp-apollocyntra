<?php
/**
 * Cantidad de documentos en fotos_gastos (polling QR móvil).
 */
require_once '../../../include/session.php';
require_once '../../../include/functions.php';

header('Content-Type: application/json');

try {
    $tipo = isset($_GET['tipo']) ? $_GET['tipo'] : '';
    $id_item = isset($_GET['id_item']) ? (int) $_GET['id_item'] : 0;

    if ($tipo !== 'gasto' || !$id_item) {
        throw new Exception('Parámetros insuficientes');
    }

    $conexion = conectar_bd();

    $query = 'SELECT COUNT(*) as cantidad FROM fotos_gastos WHERE id_gasto = ?';
    $stmt = mysqli_prepare($conexion, $query);
    mysqli_stmt_bind_param($stmt, 'i', $id_item);
    mysqli_stmt_execute($stmt);
    $resultado = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($resultado);
    $cantidad = (int) $row['cantidad'];
    mysqli_stmt_close($stmt);
    mysqli_close($conexion);

    echo json_encode(array(
        'success' => true,
        'cantidad' => $cantidad,
        'tipo' => $tipo,
    ));
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(array(
        'success' => false,
        'error' => $e->getMessage(),
    ));
}
