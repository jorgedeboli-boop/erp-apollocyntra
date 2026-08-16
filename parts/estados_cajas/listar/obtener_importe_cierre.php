<?php
require_once '../../../include/session.php';
require_once '../../../include/functions.php';

// Verificar que el usuario esté autenticado
if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'No autorizado'
    ]);
    exit;
}

if (!isset($_GET['id_tabla']) || empty($_GET['id_tabla'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'ID de caja no proporcionado'
    ]);
    exit;
}

$idTabla = intval($_GET['id_tabla']);

try {
    $conexion = conectar_bd();
    
    if (!$conexion) {
        throw new Exception('Error de conexión a la base de datos');
    }
    
    $tableName = "movimientos_de_caja_" . $idTabla;
    
    // Verificar si la tabla existe
    $tableCheck = mysqli_query($conexion, "SHOW TABLES LIKE '$tableName'");
    
    if (mysqli_num_rows($tableCheck) > 0) {
        // Primero buscar el último cierre del día actual (por ID descendente)
        $queryCierre = "SELECT salida 
                       FROM $tableName 
                       WHERE cierre_caja = 'true' 
                       AND fecha_apunte = CURDATE() 
                       ORDER BY id_movimientos DESC 
                       LIMIT 1";
        $resultCierre = mysqli_query($conexion, $queryCierre);
        
        if ($resultCierre && mysqli_num_rows($resultCierre) > 0) {
            // Hay cierre del día actual
            $rowCierre = mysqli_fetch_assoc($resultCierre);
            $importe = floatval($rowCierre['salida']);
            
            mysqli_close($conexion);
            
            echo json_encode([
                'success' => true,
                'importe' => $importe
            ]);
        } else {
            // No hay cierre del día actual, buscar el último cierre por ID (cualquier fecha)
            $queryCierreGeneral = "SELECT salida 
                                  FROM $tableName 
                                  WHERE cierre_caja = 'true' 
                                  ORDER BY id_movimientos DESC 
                                  LIMIT 1";
            $resultCierreGeneral = mysqli_query($conexion, $queryCierreGeneral);
            
            if ($resultCierreGeneral && mysqli_num_rows($resultCierreGeneral) > 0) {
                $rowCierreGeneral = mysqli_fetch_assoc($resultCierreGeneral);
                $importe = floatval($rowCierreGeneral['salida']);
                
                mysqli_close($conexion);
                
                echo json_encode([
                    'success' => true,
                    'importe' => $importe
                ]);
            } else {
                // No hay ningún cierre
                mysqli_close($conexion);
                
                echo json_encode([
                    'success' => true,
                    'importe' => 0
                ]);
            }
        }
    } else {
        mysqli_close($conexion);
        
        echo json_encode([
            'success' => true,
            'importe' => 0
        ]);
    }
    
} catch (Exception $e) {
    if (isset($conexion)) {
        mysqli_close($conexion);
    }
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>

