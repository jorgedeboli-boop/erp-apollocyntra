<?php
/**
 * Provincias de España para filtros del listado de clientes
 * Misma consulta que listarProvinciasSpain()
 */

require_once '../../../include/session.php';

header('Content-Type: application/json');

try {
    $conexion = conectar_bd();

    if (!$conexion) {
        throw new Exception('Error de conexión a la base de datos');
    }

    $countryId = isset($app_country_id) && $app_country_id !== '' ? (int)$app_country_id : 0;
    if ($countryId <= 0) {
        throw new Exception('País de la aplicación no configurado');
    }

    $query = "SELECT id_province, nombreProvince FROM provincias WHERE id_rel_country = ? ORDER BY nombreProvince ASC";
    $stmt = mysqli_prepare($conexion, $query);
    mysqli_stmt_bind_param($stmt, 'i', $countryId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $provincias = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $provincias[] = [
            'id' => (int)$row['id_province'],
            'nombre' => $row['nombreProvince']
        ];
    }

    mysqli_stmt_close($stmt);
    mysqli_close($conexion);

    echo json_encode([
        'success' => true,
        'provincias' => $provincias,
        'total' => count($provincias)
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
