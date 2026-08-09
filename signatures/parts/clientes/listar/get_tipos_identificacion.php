<?php
/**
 * Tipos de identificación para filtros del listado de clientes
 * Misma consulta que generarSelectTipoIdentificacion()
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

    $query = "SELECT id_tipo_identificacion, nombre_identificacion, texto_identificacion
              FROM tipo_identificacion
              WHERE country_id = ? AND state_tipo_identificacion = 'true'
              ORDER BY nombre_identificacion ASC";
    $stmt = mysqli_prepare($conexion, $query);
    mysqli_stmt_bind_param($stmt, 'i', $countryId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $tipos = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $tipos[] = [
            'id' => (int)$row['id_tipo_identificacion'],
            'nombre' => $row['nombre_identificacion'],
            'texto' => $row['texto_identificacion'],
            'filtro' => strtoupper($row['nombre_identificacion'])
        ];
    }

    mysqli_stmt_close($stmt);
    mysqli_close($conexion);

    echo json_encode([
        'success' => true,
        'tipos' => $tipos,
        'total' => count($tipos)
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
