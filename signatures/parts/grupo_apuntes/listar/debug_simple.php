<?php
echo "Paso 1: Script iniciado<br>";

try {
    echo "Paso 2: Intentando incluir config.php<br>";
    require_once '../../../../include/config.php';
    echo "✅ config.php incluido correctamente<br>";
    
    echo "Paso 3: Verificando conexión a BD<br>";
    if (isset($conn) && $conn) {
        echo "✅ Conexión a BD exitosa<br>";
        
        echo "Paso 4: Verificando si la tabla existe<br>";
        $result = $conn->query("SHOW TABLES LIKE 'grupos_movimientos'");
        if ($result && $result->num_rows > 0) {
            echo "✅ Tabla grupos_movimientos existe<br>";
            
            echo "Paso 5: Contando registros<br>";
            $count_result = $conn->query("SELECT COUNT(*) as total FROM grupos_movimientos");
            if ($count_result) {
                $count = $count_result->fetch_assoc()['total'];
                echo "✅ Total de registros: $count<br>";
                
                if ($count > 0) {
                    echo "Paso 6: Probando consulta simple<br>";
                    $test_result = $conn->query("SELECT * FROM grupos_movimientos LIMIT 1");
                    if ($test_result) {
                        $row = $test_result->fetch_assoc();
                        echo "✅ Consulta exitosa. Primer registro: " . json_encode($row) . "<br>";
                    } else {
                        echo "❌ Error en consulta: " . $conn->error . "<br>";
                    }
                }
            } else {
                echo "❌ Error contando registros: " . $conn->error . "<br>";
            }
        } else {
            echo "❌ Tabla grupos_movimientos NO existe<br>";
        }
    } else {
        echo "❌ No hay conexión a BD<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Excepción capturada: " . $e->getMessage() . "<br>";
    echo "Archivo: " . $e->getFile() . "<br>";
    echo "Línea: " . $e->getLine() . "<br>";
} catch (Error $e) {
    echo "❌ Error fatal: " . $e->getMessage() . "<br>";
    echo "Archivo: " . $e->getFile() . "<br>";
    echo "Línea: " . $e->getLine() . "<br>";
}

echo "Paso 7: Script finalizado<br>";
?>



