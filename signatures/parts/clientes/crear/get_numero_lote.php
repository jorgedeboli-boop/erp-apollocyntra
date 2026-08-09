<?php
session_start();
require_once '../../../include/functions.php';

header('Content-Type: application/json');

// Verificar que venga el ID de sucursal
if (!isset($_POST['id_sucursal']) || empty($_POST['id_sucursal'])) {
    echo json_encode(['success' => false, 'error' => 'ID de sucursal no proporcionado']);
    exit();
}

$id_sucursal = intval($_POST['id_sucursal']);
$conexion = conectar_bd();

if (!$conexion) {
    echo json_encode(['success' => false, 'error' => 'Error de conexión a la base de datos']);
    exit();
}

// Obtener el nombre de la tabla dinámica
$tabla_lotes = "lotes_" . $id_sucursal;

// Obtener el último id_lote
$query = "SELECT MAX(id_lote) as ultimo_id FROM " . $tabla_lotes;
$resultado = mysqli_query($conexion, $query);

if (!$resultado) {
    mysqli_close($conexion);
    echo json_encode(['success' => false, 'error' => 'La tabla de lotes no existe para esta sucursal o error en consulta']);
    exit();
}

$row = mysqli_fetch_assoc($resultado);
$ultimo_id = $row['ultimo_id'] ?? 0;
$siguiente_id = $ultimo_id + 1;

mysqli_close($conexion);

echo json_encode([
    'success' => true,
    'numero_lote' => $siguiente_id
]);
?>

