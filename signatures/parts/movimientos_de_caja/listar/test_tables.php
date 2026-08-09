<?php
require_once '../../../include/session.php';

header('Content-Type: application/json');

try {
    $conexion = conectar_bd();
    
    // Obtener todas las sucursales
    $querySucursales = "SELECT id_sucursal, nombre_sucursal FROM sucursal ORDER BY nombre_sucursal";
    $resultSucursales = mysqli_query($conexion, $querySucursales);
    
    $diagnostico = [
        'sucursales' => [],
        'tablas' => []
    ];
    
    while ($row = mysqli_fetch_assoc($resultSucursales)) {
        $idSucursal = $row['id_sucursal'];
        $nombreSucursal = $row['nombre_sucursal'];
        $tableName = "movimientos_de_caja_$idSucursal";
        
        $diagnostico['sucursales'][] = [
            'id' => $idSucursal,
            'nombre' => $nombreSucursal,
            'tabla_esperada' => $tableName
        ];
        
        // Verificar si la tabla existe
        $checkTable = mysqli_query($conexion, "SHOW TABLES LIKE '$tableName'");
        $existe = mysqli_num_rows($checkTable) > 0;
        
        $info = [
            'tabla' => $tableName,
            'existe' => $existe,
            'total_registros' => 0,
            'ejemplo_datos' => []
        ];
        
        if ($existe) {
            // Contar registros
            $queryCount = "SELECT COUNT(*) as total FROM $tableName";
            $resultCount = mysqli_query($conexion, $queryCount);
            if ($resultCount) {
                $rowCount = mysqli_fetch_assoc($resultCount);
                $info['total_registros'] = (int)$rowCount['total'];
            }
            
            // Obtener 3 registros de ejemplo
            $queryEjemplo = "SELECT id_movimientos, fecha_apunte, grupos, concepto, salida, entrada, usuario FROM $tableName LIMIT 3";
            $resultEjemplo = mysqli_query($conexion, $queryEjemplo);
            if ($resultEjemplo) {
                while ($rowEjemplo = mysqli_fetch_assoc($resultEjemplo)) {
                    $info['ejemplo_datos'][] = $rowEjemplo;
                }
            }
        }
        
        $diagnostico['tablas'][] = $info;
    }
    
    // Mostrar todas las tablas de la base de datos que contienen "movimientos_de_caja"
    $queryAllTables = "SHOW TABLES LIKE 'movimientos_de_caja%'";
    $resultAllTables = mysqli_query($conexion, $queryAllTables);
    $diagnostico['todas_tablas_movimientos'] = [];
    
    while ($row = mysqli_fetch_array($resultAllTables)) {
        $diagnostico['todas_tablas_movimientos'][] = $row[0];
    }
    
    echo json_encode($diagnostico, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode([
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ], JSON_PRETTY_PRINT);
} finally {
    if (isset($conexion)) {
        mysqli_close($conexion);
    }
}
?>

