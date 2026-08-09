<?php
/**
 * Lista migraciones activas desde la tabla migraciones.
 */

require_once __DIR__ . '/../../../include/session.php';
require_once __DIR__ . '/../../../include/functions.php';

header('Content-Type: application/json; charset=utf-8');

$conexion = conectar_bd();
if (!$conexion) {
    echo json_encode(array('success' => false, 'message' => 'No se pudo conectar a la base de datos.'));
    exit;
}

$chk = mysqli_query($conexion, "SHOW TABLES LIKE 'migraciones'");
if (!$chk || mysqli_num_rows($chk) === 0) {
    if ($chk) {
        mysqli_free_result($chk);
    }
    mysqli_close($conexion);
    echo json_encode(array(
        'success' => false,
        'message' => 'La tabla migraciones no existe. Créala en la base de datos antes de continuar.',
        'items' => array()
    ));
    exit;
}
mysqli_free_result($chk);

$query = "SELECT
    id_migracion,
    codigo_migracion,
    nombre_migracion,
    descripcion_migracion,
    script_migracion,
    estado_migracion,
    mensaje_resultado,
    registros_procesados,
    registros_total,
    ejecutado_por,
    fecha_ejecucion,
    fecha_ultimo_intento,
    orden_visual
FROM migraciones
WHERE activa = 'true'
ORDER BY orden_visual ASC, id_migracion ASC";

$result = mysqli_query($conexion, $query);
if (!$result) {
    $err = mysqli_error($conexion);
    mysqli_close($conexion);
    echo json_encode(array('success' => false, 'message' => $err ? $err : 'Error al consultar migraciones.'));
    exit;
}

$items = array();
while ($row = mysqli_fetch_assoc($result)) {
    $items[] = array(
        'id_migracion' => (int) $row['id_migracion'],
        'codigo_migracion' => $row['codigo_migracion'],
        'nombre_migracion' => $row['nombre_migracion'],
        'descripcion_migracion' => $row['descripcion_migracion'],
        'script_migracion' => $row['script_migracion'],
        'estado_migracion' => $row['estado_migracion'],
        'mensaje_resultado' => $row['mensaje_resultado'],
        'registros_procesados' => (int) $row['registros_procesados'],
        'registros_total' => (int) $row['registros_total'],
        'fecha_ejecucion' => $row['fecha_ejecucion'],
        'fecha_ultimo_intento' => $row['fecha_ultimo_intento']
    );
}
mysqli_free_result($result);
mysqli_close($conexion);

echo json_encode(array(
    'success' => true,
    'items' => $items
));
