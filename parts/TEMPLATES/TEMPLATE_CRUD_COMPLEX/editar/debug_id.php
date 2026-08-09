<?php
require_once '../../../include/session.php';
require_once '../../../include/functions.php';

$id_gasto = isset($_GET['id']) ? (int)$_GET['id'] : 0;

echo "<h2>Debug del ID de gasto: $id_gasto</h2>";

$conexion = conectar_bd();

if (!$conexion) {
    echo "<p style='color: red;'>Error de conexión a la base de datos</p>";
    exit;
}

// 1. Verificar si existe el gasto con ID simple
echo "<h3>1. Verificación simple de existencia:</h3>";
$query_simple = "SELECT id_gasto FROM gastos WHERE id_gasto = ?";
$stmt_simple = mysqli_prepare($conexion, $query_simple);
mysqli_stmt_bind_param($stmt_simple, 'i', $id_gasto);
mysqli_stmt_execute($stmt_simple);
$result_simple = mysqli_stmt_get_result($stmt_simple);
$existe = mysqli_num_rows($result_simple) > 0;
mysqli_stmt_close($stmt_simple);

echo "<p>¿Existe gasto con ID $id_gasto? <strong>" . ($existe ? 'SÍ' : 'NO') . "</strong></p>";

// 2. Mostrar algunos IDs disponibles
echo "<h3>2. IDs de gastos disponibles (últimos 10):</h3>";
$query_ids = "SELECT id_gasto FROM gastos ORDER BY id_gasto DESC LIMIT 10";
$result_ids = mysqli_query($conexion, $query_ids);
$ids = [];
while ($row = mysqli_fetch_assoc($result_ids)) {
    $ids[] = $row['id_gasto'];
}
echo "<p>IDs: " . implode(', ', $ids) . "</p>";

// 3. Verificar estructura de la tabla
echo "<h3>3. Estructura de la tabla gastos:</h3>";
$query_structure = "DESCRIBE gastos";
$result_structure = mysqli_query($conexion, $query_structure);

echo "<table border='1' style='border-collapse: collapse;'>";
echo "<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";

while ($row = mysqli_fetch_assoc($result_structure)) {
    echo "<tr>";
    echo "<td>" . $row['Field'] . "</td>";
    echo "<td>" . $row['Type'] . "</td>";
    echo "<td>" . $row['Null'] . "</td>";
    echo "<td>" . $row['Key'] . "</td>";
    echo "<td>" . $row['Default'] . "</td>";
    echo "<td>" . $row['Extra'] . "</td>";
    echo "</tr>";
}
echo "</table>";

// 4. Si existe, mostrar los datos
if ($existe) {
    echo "<h3>4. Datos del gasto $id_gasto:</h3>";
    $query_data = "SELECT * FROM gastos WHERE id_gasto = ?";
    $stmt_data = mysqli_prepare($conexion, $query_data);
    mysqli_stmt_bind_param($stmt_data, 'i', $id_gasto);
    mysqli_stmt_execute($stmt_data);
    $result_data = mysqli_stmt_get_result($stmt_data);
    $gasto_data = mysqli_fetch_assoc($result_data);
    mysqli_stmt_close($stmt_data);
    
    echo "<pre>";
    print_r($gasto_data);
    echo "</pre>";
}

// 5. Probar la consulta completa con JOINs
echo "<h3>5. Prueba de consulta completa con JOINs:</h3>";
$query_completa = "
    SELECT 
        g.id_gasto,
        g.fecha_gasto,
        g.descripcion_gasto,
        g.total_gasto,
        g.estado_gasto,
        g.empresa_gasto,
        g.sucursal_gasto,
        g.proveedor_gasto,
        g.tipo_de_gasto,
        g.forma_pago_gasto,
        g.numero_factura_proveedor,
        g.observaciones_gasto,
        g.usuario_gasto,
        g.fecha_creacion_gasto,
        e.nombre_empresa,
        s.nombre_sucursal,
        p.nombre_proveedor,
        tg.nombre_tipo_gasto,
        fp.nombre_forma_de_pago
    FROM gastos g
    LEFT JOIN empresas e ON g.empresa_gasto = e.id_empresa
    LEFT JOIN sucursal s ON g.sucursal_gasto = s.id_sucursal
    LEFT JOIN proveedores p ON g.proveedor_gasto = p.id_proveedor
    LEFT JOIN tipo_de_gasto tg ON g.tipo_de_gasto = tg.id_tipo_gasto
    LEFT JOIN formas_de_pago fp ON g.forma_pago_gasto = fp.id_forma_de_pago
    WHERE g.id_gasto = ?
";

$stmt_completa = mysqli_prepare($conexion, $query_completa);
mysqli_stmt_bind_param($stmt_completa, 'i', $id_gasto);
mysqli_stmt_execute($stmt_completa);
$result_completa = mysqli_stmt_get_result($stmt_completa);
$num_filas = mysqli_num_rows($result_completa);
mysqli_stmt_close($stmt_completa);

echo "<p>Número de filas con consulta completa: <strong>$num_filas</strong></p>";

if ($num_filas > 0) {
    echo "<p style='color: green;'>✓ La consulta completa funciona correctamente</p>";
} else {
    echo "<p style='color: red;'>✗ La consulta completa no devuelve resultados</p>";
}

mysqli_close($conexion);
?>
