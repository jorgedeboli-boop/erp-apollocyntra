<?php
require_once '../../../include/session.php';
require_once '../../../include/functions.php';

// Conectar BD
$conexion = conectar_bd();

// Obtener estructura de la tabla gastos
$query = "DESCRIBE gastos";
$result = mysqli_query($conexion, $query);

echo "<h3>Estructura de la tabla gastos:</h3>";
echo "<table border='1'>";
echo "<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";

while ($row = mysqli_fetch_assoc($result)) {
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

// Probar consulta con un gasto existente
$query_test = "SELECT * FROM gastos LIMIT 1";
$result_test = mysqli_query($conexion, $query_test);

echo "<h3>Datos de ejemplo de un gasto:</h3>";
if ($result_test && mysqli_num_rows($result_test) > 0) {
    $gasto = mysqli_fetch_assoc($result_test);
    echo "<pre>";
    print_r($gasto);
    echo "</pre>";
} else {
    echo "No hay gastos en la tabla";
}

mysqli_close($conexion);
?>
