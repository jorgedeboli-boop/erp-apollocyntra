<?php
/**
 * Prueba directa de la consulta SQL sin DataTables
 */

require_once '../../../include/session.php';
require_once '../../../include/functions.php';

echo "<h2>Prueba Directa de Consulta SQL</h2>";

try {
    // Conectar a la base de datos
    $conexion = conectar_bd();
    echo "<p>✅ Conexión exitosa</p>";
    
    // Verificar si la tabla usuarios existe
    $result = mysqli_query($conexion, "SHOW TABLES LIKE 'usuarios'");
    if (mysqli_num_rows($result) > 0) {
        echo "<p>✅ Tabla 'usuarios' existe</p>";
    } else {
        echo "<p>❌ Tabla 'usuarios' NO existe</p>";
        exit;
    }
    
    // Contar usuarios totales
    $result = mysqli_query($conexion, "SELECT COUNT(*) as total FROM usuarios");
    $total = mysqli_fetch_assoc($result)['total'];
    echo "<p>📊 Total de usuarios: $total</p>";
    
    // Mostrar estructura de la tabla usuarios
    echo "<h3>Estructura de la tabla usuarios:</h3>";
    $result = mysqli_query($conexion, "DESCRIBE usuarios");
    echo "<table border='1'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    while ($row = mysqli_fetch_assoc($result)) {
        echo "<tr>";
        echo "<td>{$row['Field']}</td>";
        echo "<td>{$row['Type']}</td>";
        echo "<td>{$row['Null']}</td>";
        echo "<td>{$row['Key']}</td>";
        echo "<td>{$row['Default']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Probar la consulta simple
    echo "<h3>Prueba de consulta simple:</h3>";
    $query = "SELECT * FROM usuarios ORDER BY id_usuario ASC LIMIT 5";
    echo "<p>Query: <code>$query</code></p>";
    
    $result = mysqli_query($conexion, $query);
    
    if (!$result) {
        echo "<p>❌ Error en consulta: " . mysqli_error($conexion) . "</p>";
    } else {
        echo "<p>✅ Consulta ejecutada correctamente</p>";
        
        if (mysqli_num_rows($result) > 0) {
            echo "<table border='1'>";
            echo "<tr><th>ID</th><th>Usuario</th><th>Nombre</th><th>Apellido</th><th>Email</th><th>Estado</th><th>Privilegio</th></tr>";
            while ($row = mysqli_fetch_assoc($result)) {
                echo "<tr>";
                echo "<td>{$row['id_usuario']}</td>";
                echo "<td>{$row['usuario']}</td>";
                echo "<td>{$row['nombre_usuario']}</td>";
                echo "<td>{$row['apellido_usuario']}</td>";
                echo "<td>{$row['email']}</td>";
                echo "<td>{$row['estado_usuario']}</td>";
                echo "<td>{$row['privilegio_usuario']}</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p>❌ No hay usuarios en la base de datos</p>";
        }
    }
    
    // Verificar permisos del usuario actual
    echo "<h3>Permisos del usuario actual:</h3>";
    echo "<p>Usuario ID: " . $_SESSION['usuario_id'] . "</p>";
    echo "<p>Puede acceder a usuarios: " . (puede_acceder_a('usuarios') ? 'SÍ' : 'NO') . "</p>";
    
    // Probar la función puede_acceder_a
    echo "<h3>Prueba de función puede_acceder_a:</h3>";
    echo "<p>puede_acceder_a('usuarios'): " . (puede_acceder_a('usuarios') ? 'true' : 'false') . "</p>";
    echo "<p>puede_acceder_a('reportes'): " . (puede_acceder_a('reportes') ? 'true' : 'false') . "</p>";
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
}

mysqli_close($conexion);
?>
