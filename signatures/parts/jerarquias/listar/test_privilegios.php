<?php
// Archivo de prueba para verificar la consulta de privilegios
require_once '../../../include/session.php';
require_once '../../../include/functions.php';

echo "<h2>Prueba de consulta de privilegios</h2>";

try {
    // Conectar a la base de datos
    $conexion = conectar_bd();
    
    if (!$conexion) {
        echo "<p style='color: red;'>Error: No se pudo conectar a la base de datos</p>";
        exit;
    }
    
    echo "<p style='color: green;'>✓ Conexión exitosa a la base de datos</p>";
    
    // Verificar que la tabla existe
    $query_check = "SHOW TABLES LIKE 'privilegios_usuarios'";
    $result_check = mysqli_query($conexion, $query_check);
    
    if (mysqli_num_rows($result_check) == 0) {
        echo "<p style='color: red;'>Error: La tabla 'privilegios_usuarios' no existe</p>";
        exit;
    }
    
    echo "<p style='color: green;'>✓ Tabla 'privilegios_usuarios' encontrada</p>";
    
    // Mostrar estructura de la tabla
    echo "<h3>Estructura de la tabla:</h3>";
    $query_structure = "DESCRIBE privilegios_usuarios";
    $result_structure = mysqli_query($conexion, $query_structure);
    
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Llave</th><th>Por defecto</th><th>Extra</th></tr>";
    
    while ($row = mysqli_fetch_assoc($result_structure)) {
        echo "<tr>";
        echo "<td>{$row['Field']}</td>";
        echo "<td>{$row['Type']}</td>";
        echo "<td>{$row['Null']}</td>";
        echo "<td>{$row['Key']}</td>";
        echo "<td>{$row['Default']}</td>";
        echo "<td>{$row['Extra']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Ejecutar la consulta principal
    echo "<h3>Resultados de la consulta:</h3>";
    $query = "SELECT id_privilegios, nombre_privilegio FROM privilegios_usuarios ORDER BY nombre_privilegio ASC";
    $resultado = mysqli_query($conexion, $query);
    
    if (!$resultado) {
        echo "<p style='color: red;'>Error en la consulta: " . mysqli_error($conexion) . "</p>";
        exit;
    }
    
    echo "<p style='color: green;'>✓ Consulta ejecutada correctamente</p>";
    
    // Mostrar resultados
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>ID</th><th>Nombre del Privilegio</th></tr>";
    
    $total = 0;
    while ($fila = mysqli_fetch_assoc($resultado)) {
        echo "<tr>";
        echo "<td>{$fila['id_privilegios']}</td>";
        echo "<td>{$fila['nombre_privilegio']}</td>";
        echo "</tr>";
        $total++;
    }
    echo "</table>";
    
    echo "<p><strong>Total de privilegios encontrados: {$total}</strong></p>";
    
    // Cerrar conexión
    mysqli_close($conexion);
    echo "<p style='color: green;'>✓ Conexión cerrada</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>
