<?php
/**
 * Procesar renovaciones del lote
 * Este archivo es incluido desde insertar_lote.php
 * Las variables $conexion, $id_sucursal, $id_lote, $precio_compra, $precio_recompra, $fecha_vencimiento ya están disponibles
 */

// Validar que las variables necesarias estén disponibles
if (!isset($conexion) || !isset($id_sucursal) || !isset($id_lote) || !isset($precio_compra) || !isset($precio_recompra) || !isset($fecha_vencimiento)) {
    error_log("Error en procesar_renovaciones: Variables requeridas no disponibles");
    return;
}

// Calcular importe de renovación
$importe_renovacion = $precio_recompra - $precio_compra;

// Validar que el ID de sucursal sea un entero para seguridad en el nombre de tabla
$id_sucursal = (int) $id_sucursal;
$id_lote = (int) $id_lote;
$importe_renovacion = floatval($importe_renovacion);
$estado_historico = 'enfecha';

// Construir nombre de tabla de forma segura
$nombre_tabla = "historico_renovaciones_" . $id_sucursal;

// Insertar registro en histórico de renovaciones usando prepared statements
$query = "INSERT INTO `$nombre_tabla` (
    importe_renovacion,
    lote,
    proximo_vencimiento,
    estado_historico,
    fecha_insert
) VALUES (?, ?, ?, ?, NOW())";

$stmt = mysqli_prepare($conexion, $query);

if (!$stmt) {
    error_log("Error al preparar consulta en procesar_renovaciones: " . mysqli_error($conexion));
    return;
}

mysqli_stmt_bind_param($stmt, 'diss', 
    $importe_renovacion,
    $id_lote,
    $fecha_vencimiento,
    $estado_historico
);

if (!mysqli_stmt_execute($stmt)) {
    error_log("Error al ejecutar consulta en procesar_renovaciones: " . mysqli_stmt_error($stmt));
    mysqli_stmt_close($stmt);
    return;
}


mysqli_stmt_close($stmt);
?>