<?php
require_once '../../../include/session.php';
require_once '../../../include/functions.php';

header('Content-Type: application/json; charset=utf-8');

$conexion = conectar_bd();

if (!$conexion) {
    http_response_code(500);
    echo json_encode(['error' => 'Error de conexión']);
    exit;
}

mysqli_set_charset($conexion, 'utf8mb4');

$query = "
    SELECT
        s.id_sucursal,
        s.nombre_sucursal,
        s.direccion_tienda,
        s.poblacion_tienda,
        s.provincia_tienda,
        s.codigo_postal_tienda,
        s.lat,
        s.lng,
        CASE
            WHEN EXISTS (
                SELECT 1
                FROM usersConexions uc
                WHERE uc.sucursalIdUserConexion = s.id_sucursal
                  AND uc.state_connection = 'true'
            ) THEN 'true'
            ELSE 'false'
        END AS sucursal_conectada
    FROM sucursal s
    WHERE s.estado_tienda = 'habilitada'
    ORDER BY s.nombre_sucursal ASC
";

$resultado = mysqli_query($conexion, $query);

if (!$resultado) {
    mysqli_close($conexion);
    http_response_code(500);
    echo json_encode(['error' => 'Error en la consulta']);
    exit;
}

$sucursales = [];

while ($fila = mysqli_fetch_assoc($resultado)) {
    $sucursales[] = $fila;
}

mysqli_free_result($resultado);
mysqli_close($conexion);

echo json_encode($sucursales, JSON_UNESCAPED_UNICODE);
