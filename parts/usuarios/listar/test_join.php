<?php
/**
 * Prueba del JOIN con privilegios_usuarios
 */

require_once '../../../include/session.php';
require_once '../../../include/functions.php';

echo "<h2>Prueba del JOIN con Privilegios</h2>";

try {
    // Conectar BD
    $conexion = conectar_bd();
    echo "<p>✅ Conexión exitosa</p>";
    
    // Verificar tablas
    $result = mysqli_query($conexion, "SHOW TABLES LIKE 'usuarios'");
    if (mysqli_num_rows($result) > 0) {
        echo "<p>✅ Tabla 'usuarios' existe</p>";
    } else {
        echo "<p>❌ Tabla 'usuarios' NO existe</p>";
        exit;
    }
    
    $result = mysqli_query($conexion, "SHOW TABLES LIKE 'privilegios_usuarios'");
    if (mysqli_num_rows($result) > 0) {
        echo "<p>✅ Tabla 'privilegios_usuarios' existe</p>";
    } else {
        echo "<p>❌ Tabla 'privilegios_usuarios' NO existe</p>";
        exit;
    }
    
    // Probar el JOIN
    echo "<h3>Probando JOINs:</h3>";
    $query = "
        SELECT 
            u.id_usuario, 
            u.nombre_usuario, 
            u.apellido_usuario, 
            u.email, 
            u.estado_usuario, 
            u.privilegio_usuario,
            u.ultimo_acceso,
            u.sucursal_usuario,
            COALESCE(p.nombre_privilegio, 'Sin privilegio') as nombre_privilegio,
            COALESCE(s.nombre_sucursal, 'Sin sucursal') as nombre_sucursal
        FROM usuarios u
        LEFT JOIN privilegios_usuarios p ON u.privilegio_usuario = p.id_privilegios
        LEFT JOIN sucursal s ON u.sucursal_usuario = s.id_sucursal
        ORDER BY u.id_usuario ASC
        LIMIT 5
    ";
    
    echo "<p>Query: <code>" . htmlspecialchars($query) . "</code></p>";
    
    $result = mysqli_query($conexion, $query);
    
    if (!$result) {
        echo "<p>❌ Error en consulta: " . mysqli_error($conexion) . "</p>";
        exit;
    }
    
    echo "<p>✅ JOIN ejecutado correctamente</p>";
    
    // Mostrar resultados
    echo "<h3>Resultados del JOIN:</h3>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background-color: #f8f9fa;'>";
    echo "<th style='padding: 8px; border: 1px solid #dee2e6;'>ID</th>";
    echo "<th style='padding: 8px; border: 1px solid #dee2e6;'>Nombre</th>";
    echo "<th style='padding: 8px; border: 1px solid #dee2e6;'>Último Acceso</th>";
    echo "<th style='padding: 8px; border: 1px solid #dee2e6;'>Jerarquía</th>";
    echo "<th style='padding: 8px; border: 1px solid #dee2e6;'>Sucursal</th>";
    echo "<th style='padding: 8px; border: 1px solid #dee2e6;'>Estado</th>";
    echo "</tr>";
    
    while ($row = mysqli_fetch_assoc($result)) {
        echo "<tr>";
        echo "<td style='padding: 8px; border: 1px solid #dee2e6;'>{$row['id_usuario']}</td>";
        echo "<td style='padding: 8px; border: 1px solid #dee2e6;'>{$row['nombre_usuario']} {$row['apellido_usuario']}<br><small style='color: #6c757d;'>{$row['usuario']}</small></td>";
        echo "<td style='padding: 8px; border: 1px solid #dee2e6;'>{$row['ultimo_acceso']}</td>";
        echo "<td style='padding: 8px; border: 1px solid #dee2e6;'><strong>{$row['nombre_privilegio']}</strong></td>";
        echo "<td style='padding: 8px; border: 1px solid #dee2e6;'><strong>{$row['nombre_sucursal']}</strong></td>";
        echo "<td style='padding: 8px; border: 1px solid #dee2e6;'>{$row['estado_usuario']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Verificar privilegios disponibles
    echo "<h3>Privilegios disponibles en la BD:</h3>";
    $privilegios_query = "SELECT * FROM privilegios_usuarios ORDER BY id_privilegios";
    $privilegios_result = mysqli_query($conexion, $privilegios_query);
    
    if ($privilegios_result) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background-color: #f8f9fa;'>";
        echo "<th style='padding: 8px; border: 1px solid #dee2e6;'>ID</th>";
        echo "<th style='padding: 8px; border: 1px solid #dee2e6;'>Nombre</th>";
        echo "<th style='padding: 8px; border: 1px solid #dee2e6;'>Descripción</th>";
        echo "</tr>";
        
        while ($row = mysqli_fetch_assoc($privilegios_result)) {
            echo "<tr>";
            echo "<td style='padding: 8px; border: 1px solid #dee2e6;'>{$row['id_privilegios']}</td>";
            echo "<td style='padding: 8px; border: 1px solid #dee2e6;'><strong>{$row['nombre_privilegio']}</strong></td>";
            echo "<td style='padding: 8px; border: 1px solid #dee2e6;'>{$row['descripcion_privilegio']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
}

mysqli_close($conexion);
?>
