<?php
require_once '../../../include/session.php';
require_once '../../../include/functions.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

if (!usuario_sesion_es_root()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Solo usuarios root pueden ver la estructura de la tabla']);
    exit;
}

try {
    $conexion = conectar_bd();
    if (!$conexion) {
        throw new Exception('Error de conexión a la base de datos');
    }

    $resultado = mysqli_query($conexion, 'SHOW FULL COLUMNS FROM itemsSections');
    if (!$resultado) {
        throw new Exception('Error al obtener columnas: ' . mysqli_error($conexion));
    }

    $columnas = [];
    while ($fila = mysqli_fetch_assoc($resultado)) {
        $columnas[] = [
            'field' => $fila['Field'],
            'type' => $fila['Type'],
            'null' => $fila['Null'],
            'key' => $fila['Key'],
            'default' => $fila['Default'],
            'extra' => $fila['Extra'],
        ];
    }

    mysqli_close($conexion);

    echo json_encode([
        'success' => true,
        'columnas' => $columnas,
        'total' => count($columnas),
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
    ]);
}
