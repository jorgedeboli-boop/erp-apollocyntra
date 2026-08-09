<?php
require_once '../../../include/session.php';
require_once '../../../include/functions.php';

echo "1. Archivos incluidos correctamente<br>";

try {
    echo "2. Intentando conectar a la BD...<br>";
    $conexion = conectar_bd();
    
    if (!$conexion) {
        throw new Exception('Error de conexión a la base de datos');
    }
    
    echo "3. Conexión exitosa<br>";
    
    // Verificar si la tabla existe
    echo "4. Verificando si la tabla existe...<br>";
    $result = mysqli_query($conexion, "SHOW TABLES LIKE 'grupos_movimientos'");
    
    if (!$result) {
        throw new Exception("Error verificando tabla: " . mysqli_error($conexion));
    }
    
    if (mysqli_num_rows($result) == 0) {
        echo "❌ La tabla 'grupos_movimientos' NO existe<br>";
        echo "Necesitas crearla primero<br>";
    } else {
        echo "✅ La tabla 'grupos_movimientos' existe<br>";
        
        // Contar registros
        $count_result = mysqli_query($conexion, "SELECT COUNT(*) as total FROM grupos_movimientos");
        if ($count_result) {
            $count = mysqli_fetch_assoc($count_result)['total'];
            echo "Total de registros: $count<br>";
        }
        
        // Probar consulta simple
        echo "5. Probando consulta simple...<br>";
        $test_result = mysqli_query($conexion, "SELECT * FROM grupos_movimientos LIMIT 1");
        
        if (!$test_result) {
            throw new Exception("Error en consulta: " . mysqli_error($conexion));
        }
        
        echo "✅ Consulta exitosa<br>";
        
        if (mysqli_num_rows($test_result) > 0) {
            $row = mysqli_fetch_assoc($test_result);
            echo "Primer registro: ID=" . $row['id_grupo'] . ", Nombre=" . $row['nombre_grupo'] . ", Tipo=" . $row['tipo_grupo'] . "<br>";
        }
    }
    
    mysqli_close($conexion);
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}
?>
