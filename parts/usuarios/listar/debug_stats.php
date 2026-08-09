<?php
/**
 * Debug para load_stats.php
 */

require_once '../../../include/session.php';
require_once '../../../include/functions.php';

echo "<h2>Debug de load_stats.php</h2>";

try {
    echo "<p>✅ Permisos verificados correctamente</p>";
    
    // Conectar BD
    $conexion = conectar_bd();
    echo "<p>✅ Conexión a BD exitosa</p>";
    
    // Verificar si existe la tabla usersConexions
    echo "<h3>Verificando tabla usersConexions:</h3>";
    $result = mysqli_query($conexion, "SHOW TABLES LIKE 'usersConexions'");
    
    if (mysqli_num_rows($result) > 0) {
        echo "<p>✅ Tabla 'usersConexions' existe</p>";
        
        // Verificar estructura de la tabla
        echo "<h4>Estructura de usersConexions:</h4>";
        $result = mysqli_query($conexion, "DESCRIBE usersConexions");
        
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
        
        // Verificar datos de la tabla
        echo "<h4>Datos de usersConexions (primeros 5 registros):</h4>";
        $result = mysqli_query($conexion, "SELECT * FROM usersConexions LIMIT 5");
        
        if ($result && mysqli_num_rows($result) > 0) {
            echo "<table border='1' style='border-collapse: collapse;'>";
            echo "<tr>";
            $first_row = mysqli_fetch_assoc($result);
            foreach ($first_row as $key => $value) {
                echo "<th>" . $key . "</th>";
            }
            echo "</tr>";
            
            // Resetear el puntero
            mysqli_data_seek($result, 0);
            
            while ($row = mysqli_fetch_assoc($result)) {
                echo "<tr>";
                foreach ($row as $value) {
                    echo "<td>" . htmlspecialchars($value) . "</td>";
                }
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p>⚠️ La tabla usersConexions está vacía o no hay datos</p>";
        }
        
    } else {
        echo "<p>❌ Tabla 'usersConexions' NO existe</p>";
        echo "<p>Tablas disponibles:</p>";
        $result = mysqli_query($conexion, "SHOW TABLES");
        echo "<ul>";
        while ($row = mysqli_fetch_array($result)) {
            echo "<li>" . $row[0] . "</li>";
        }
        echo "</ul>";
    }
    
    // Probar la consulta paso a paso
    echo "<h3>Probando consulta paso a paso:</h3>";
    
    // Paso 1: Subconsulta
    echo "<h4>Paso 1: Subconsulta para obtener últimos registros</h4>";
    $subquery = "
        SELECT userId, MAX(idUserConexion) as max_id
        FROM usersConexions
        GROUP BY userId
    ";
    
    echo "<p>Subconsulta: <code>" . htmlspecialchars($subquery) . "</code></p>";
    
    $result = mysqli_query($conexion, $subquery);
    if ($result) {
        echo "<p>✅ Subconsulta ejecutada correctamente</p>";
        echo "<p>Registros encontrados: " . mysqli_num_rows($result) . "</p>";
    } else {
        echo "<p>❌ Error en subconsulta: " . mysqli_error($conexion) . "</p>";
    }
    
    // Paso 2: Consulta completa
    echo "<h4>Paso 2: Consulta completa</h4>";
    $full_query = "
        SELECT COUNT(DISTINCT uc.userId) as total 
        FROM usersConexions uc
        INNER JOIN (
            SELECT userId, MAX(idUserConexion) as max_id
            FROM usersConexions
            GROUP BY userId
        ) latest ON uc.userId = latest.userId AND uc.idUserConexion = latest.max_id
        WHERE uc.state_connection = 'true'
    ";
    
    echo "<p>Consulta completa: <code>" . htmlspecialchars($full_query) . "</code></p>";
    
    $result = mysqli_query($conexion, $full_query);
    if ($result) {
        echo "<p>✅ Consulta completa ejecutada correctamente</p>";
        $row = mysqli_fetch_assoc($result);
        echo "<p>Total de usuarios conectados: " . $row['total'] . "</p>";
    } else {
        echo "<p>❌ Error en consulta completa: " . mysqli_error($conexion) . "</p>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Error general: " . $e->getMessage() . "</p>";
    echo "<p>Stack trace:</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

mysqli_close($conexion);
?>
