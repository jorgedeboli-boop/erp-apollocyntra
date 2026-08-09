<?php
/**
 * Prueba simple para verificar qué tablas existen
 */

require_once '../../../include/session.php';
require_once '../../../include/functions.php';

echo "<h2>Verificación de Tablas</h2>";

try {
    // Conectar BD
    $conexion = conectar_bd();
    
    // Verificar qué tablas existen
    echo "<h3>Tablas disponibles:</h3>";
    $result = mysqli_query($conexion, "SHOW TABLES");
    
    if ($result) {
        echo "<ul>";
        while ($row = mysqli_fetch_array($result)) {
            echo "<li>" . $row[0] . "</li>";
        }
        echo "</ul>";
    }
    
    // Verificar estructura de tabla usuarios
    echo "<h3>Estructura de tabla 'usuarios':</h3>";
    $result = mysqli_query($conexion, "DESCRIBE usuarios");
    
    if ($result) {
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th></tr>";
        while ($row = mysqli_fetch_assoc($result)) {
            echo "<tr>";
            echo "<td>" . $row['Field'] . "</td>";
            echo "<td>" . $row['Type'] . "</td>";
            echo "<td>" . $row['Null'] . "</td>";
            echo "<td>" . $row['Key'] . "</td>";
            echo "<td>" . $row['Default'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Verificar si existe tabla sucursal
    echo "<h3>¿Existe tabla 'sucursal'?</h3>";
    $result = mysqli_query($conexion, "SHOW TABLES LIKE 'sucursal'");
    
    if (mysqli_num_rows($result) > 0) {
        echo "<p>✅ Sí existe la tabla 'sucursal'</p>";
        
        // Verificar estructura
        echo "<h4>Estructura de tabla 'sucursal':</h4>";
        $result = mysqli_query($conexion, "DESCRIBE sucursal");
        
        if ($result) {
            echo "<table border='1' style='border-collapse: collapse;'>";
            echo "<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th></tr>";
            while ($row = mysqli_fetch_assoc($result)) {
                echo "<tr>";
                echo "<td>" . $row['Field'] . "</td>";
                echo "<td>" . $row['Type'] . "</td>";
                echo "<td>" . $row['Null'] . "</td>";
                echo "<td>" . $row['Key'] . "</td>";
                echo "<td>" . $row['Default'] . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    } else {
        echo "<p>❌ NO existe la tabla 'sucursal'</p>";
        echo "<p>Creando tabla de prueba...</p>";
        
        // Crear tabla de prueba
        $create_query = "
            CREATE TABLE IF NOT EXISTS `sucursal` (
                `id_sucursal` int(2) NOT NULL AUTO_INCREMENT,
                `nombre_sucursal` varchar(100) NOT NULL,
                `direccion_sucursal` text,
                PRIMARY KEY (`id_sucursal`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8
        ";
        
        if (mysqli_query($conexion, $create_query)) {
            echo "<p>✅ Tabla 'sucursal' creada exitosamente</p>";
            
            // Insertar datos de prueba
            $insert_query = "
                INSERT INTO `sucursal` (`id_sucursal`, `nombre_sucursal`, `direccion_sucursal`) VALUES
                (1, 'Sucursal Centro', 'Calle Principal 123'),
                (2, 'Sucursal Norte', 'Avenida Norte 456'),
                (3, 'Sucursal Sur', 'Calle Sur 789')
            ";
            
            if (mysqli_query($conexion, $insert_query)) {
                echo "<p>✅ Datos de prueba insertados en 'sucursal'</p>";
            } else {
                echo "<p>❌ Error insertando datos: " . mysqli_error($conexion) . "</p>";
            }
        } else {
            echo "<p>❌ Error creando tabla: " . mysqli_error($conexion) . "</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
}

mysqli_close($conexion);
?>
