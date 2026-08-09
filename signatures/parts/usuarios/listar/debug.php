<?php
/**
 * Archivo de debug para verificar la conexión y datos
 */

require_once '../../../include/session.php';
require_once '../../../include/functions.php';

echo "<h2>Debug de Usuarios</h2>";

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
    
    // Verificar si la tabla privilegios_usuarios existe
    $result = mysqli_query($conexion, "SHOW TABLES LIKE 'privilegios_usuarios'");
    if (mysqli_num_rows($result) > 0) {
        echo "<p>✅ Tabla 'privilegios_usuarios' existe</p>";
    } else {
        echo "<p>❌ Tabla 'privilegios_usuarios' NO existe</p>";
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
    
    // Mostrar algunos usuarios de ejemplo
    echo "<h3>Usuarios de ejemplo:</h3>";
    $result = mysqli_query($conexion, "
        SELECT 
            u.id_usuario,
            u.usuario,
            u.nombre_usuario,
            u.apellido_usuario,
            u.email,
            u.estado_usuario,
            p.nombre_privilegio
        FROM usuarios u
        LEFT JOIN privilegios_usuarios p ON u.privilegio_usuario = p.id_privilegios
        LIMIT 5
    ");
    
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
            echo "<td>{$row['nombre_privilegio']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>❌ No hay usuarios en la base de datos</p>";
    }
    
    // Verificar permisos del usuario actual
    echo "<h3>Permisos del usuario actual:</h3>";
    echo "<p>Usuario ID: " . $_SESSION['usuario_id'] . "</p>";
    echo "<p>Puede acceder a usuarios: " . (puede_acceder_a('usuarios') ? 'SÍ' : 'NO') . "</p>";
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
}

mysqli_close($conexion);
?>
