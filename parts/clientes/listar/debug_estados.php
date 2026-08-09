<?php
/**
 * Archivo de debug para verificar estados de clientes
 */

require_once '../../../include/session.php';
require_once '../../../include/functions.php';

header('Content-Type: text/html; charset=utf-8');

try {
    $conexion = conectar_bd();
    
    // Consulta para ver todos los estados únicos
    $query = "SELECT DISTINCT estado, COUNT(*) as total FROM clientes GROUP BY estado ORDER BY estado";
    $result = mysqli_query($conexion, $query);
    
    if (!$result) {
        throw new Exception("Error en consulta: " . mysqli_error($conexion));
    }
    
    echo "<h2>Estados únicos de clientes en la base de datos:</h2>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Estado</th><th>Total Clientes</th></tr>";
    
    while ($row = mysqli_fetch_assoc($result)) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['estado']) . "</td>";
        echo "<td>" . $row['total'] . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    // Consulta para ver algunos ejemplos de cada estado
    echo "<h2>Ejemplos de clientes por estado:</h2>";
    
    $estados = ['habilitado', 'deshabilitado'];
    foreach ($estados as $estado) {
        $query_ejemplos = "SELECT id_cliente, nombre, apellido, estado FROM clientes WHERE estado = ? LIMIT 3";
        $stmt = mysqli_prepare($conexion, $query_ejemplos);
        mysqli_stmt_bind_param($stmt, 's', $estado);
        mysqli_stmt_execute($stmt);
        $result_ejemplos = mysqli_stmt_get_result($stmt);
        
        echo "<h3>Estado: '$estado'</h3>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Nombre</th><th>Apellido</th><th>Estado</th></tr>";
        
        while ($row = mysqli_fetch_assoc($result_ejemplos)) {
            echo "<tr>";
            echo "<td>" . $row['id_cliente'] . "</td>";
            echo "<td>" . htmlspecialchars($row['nombre']) . "</td>";
            echo "<td>" . htmlspecialchars($row['apellido']) . "</td>";
            echo "<td>" . htmlspecialchars($row['estado']) . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
        mysqli_stmt_close($stmt);
    }
    
    mysqli_close($conexion);
    
} catch (Exception $e) {
    echo "<h2>Error:</h2>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
