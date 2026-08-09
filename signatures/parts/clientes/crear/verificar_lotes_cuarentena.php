<?php
require_once '../../../include/session.php';

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
$tabla_cuarentena = "lotes_cuarentena_" . $id_sucursal;

// Consultar lotes en cuarentena con estado 'arribado'
$query = "SELECT id_lote_cuarentena FROM " . $tabla_cuarentena . " WHERE estado_lote = 'arribado'";
$result = mysqli_query($conexion, $query);

if (!$result) {
    mysqli_close($conexion);
    echo json_encode(['success' => false, 'error' => 'La tabla de cuarentena no existe para esta sucursal o error en consulta']);
    exit();
}

$numero = mysqli_num_rows($result);
$lotes_disponibles = array();

if ($numero > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $lotes_disponibles[] = $row['id_lote_cuarentena'];
    }
}

mysqli_close($conexion);

echo json_encode([
    'success' => true,
    'hay_lotes' => $numero > 0,
    'numero_lotes' => $numero,
    'lotes_disponibles' => implode(', ', $lotes_disponibles)
]);
?>

