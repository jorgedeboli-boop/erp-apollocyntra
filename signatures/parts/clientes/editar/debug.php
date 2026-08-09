<?php
/**
 * Archivo de debug temporal para probar la carga de datos del cliente
 */

require_once '../../../include/session.php';
require_once '../../../include/functions.php';

// Verificar si se pasó un ID
$id_cliente = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$id_cliente) {
    echo "Error: No se proporcionó ID de cliente";
    exit;
}

echo "<h2>Debug - ID Cliente: $id_cliente</h2>";

// Conectar BD
$conexion = conectar_bd();

// Consulta para obtener datos del cliente
$query_cliente = "
    SELECT 
        c.id_cliente,
        c.nombre,
        c.apellido,
        c.tipo_identificacion,
        c.identificacion,
        c.nacionalidad,
        c.f_nacimiento,
        c.telefono,
        c.sucursal,
        c.f_alta,
        c.f_vencimiento
    FROM clientes c
    WHERE c.id_cliente = ?
";

$stmt_cliente = mysqli_prepare($conexion, $query_cliente);
mysqli_stmt_bind_param($stmt_cliente, 'i', $id_cliente);
mysqli_stmt_execute($stmt_cliente);
$result_cliente = mysqli_stmt_get_result($stmt_cliente);

if (!$result_cliente || mysqli_num_rows($result_cliente) === 0) {
    echo "<p style='color: red;'>Error: Cliente no encontrado</p>";
    exit;
}

$cliente = mysqli_fetch_assoc($result_cliente);
mysqli_stmt_close($stmt_cliente);

echo "<h3>Datos de la tabla 'clientes':</h3>";
echo "<pre>" . print_r($cliente, true) . "</pre>";

// Consulta para obtener datos adicionales del cliente
$query_datos = "
    SELECT 
        dc.direccion,
        dc.c_provincia,
        dc.c_poblacion,
        dc.codigo_postal,
        dc.email,
        dc.observaciones,
        dc.sexo
    FROM datos_clientes dc
    WHERE dc.rel_id_cliente = ?
";

$stmt_datos = mysqli_prepare($conexion, $query_datos);
mysqli_stmt_bind_param($stmt_datos, 'i', $id_cliente);
mysqli_stmt_execute($stmt_datos);
$result_datos = mysqli_stmt_get_result($stmt_datos);

$datos_cliente = null;
if ($result_datos && mysqli_num_rows($result_datos) > 0) {
    $datos_cliente = mysqli_fetch_assoc($result_datos);
    echo "<h3>Datos de la tabla 'datos_clientes':</h3>";
    echo "<pre>" . print_r($datos_cliente, true) . "</pre>";
} else {
    echo "<p style='color: orange;'>Advertencia: No se encontraron datos adicionales en 'datos_clientes'</p>";
}

mysqli_stmt_close($stmt_datos);

// Combinar datos
$cliente['datos_cliente'] = $datos_cliente;

echo "<h3>Datos combinados:</h3>";
echo "<pre>" . print_r($cliente, true) . "</pre>";

// Probar la función obtener_sucursales
echo "<h3>Prueba de obtener_sucursales():</h3>";
try {
    $sucursales = obtener_sucursales();
    echo "<pre>" . print_r($sucursales, true) . "</pre>";
} catch (Exception $e) {
    echo "<p style='color: red;'>Error en obtener_sucursales: " . $e->getMessage() . "</p>";
}

mysqli_close($conexion);
?>
