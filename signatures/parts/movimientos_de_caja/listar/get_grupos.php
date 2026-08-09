<?php
/**
 * Archivo para obtener todos los grupos existentes en las tablas de movimientos
 */

require_once '../../../include/session.php';

header('Content-Type: application/json');

try {
    // Conectar BD
    $conexion = conectar_bd();
    
    // Obtener todas las sucursales
    $querySucursales = "SELECT id_sucursal FROM sucursal ORDER BY id_sucursal";
    $resultSucursales = mysqli_query($conexion, $querySucursales);
    
    $gruposUnicos = [];
    
    // Recorrer todas las sucursales para obtener los grupos
    while ($row = mysqli_fetch_assoc($resultSucursales)) {
        $idSucursal = $row['id_sucursal'];
        $tableName = "movimientos_de_caja_$idSucursal";
        
        // Verificar si la tabla existe
        $checkTable = mysqli_query($conexion, "SHOW TABLES LIKE '$tableName'");
        if (mysqli_num_rows($checkTable) == 0) {
            continue;
        }
        
        // Obtener grupos únicos de esta tabla
        $queryGrupos = "SELECT DISTINCT grupos FROM $tableName WHERE grupos != '' AND grupos IS NOT NULL ORDER BY grupos";
        $resultGrupos = mysqli_query($conexion, $queryGrupos);
        
        if ($resultGrupos) {
            while ($rowGrupo = mysqli_fetch_assoc($resultGrupos)) {
                $grupo = trim($rowGrupo['grupos']);
                if (!empty($grupo) && !in_array($grupo, $gruposUnicos)) {
                    $gruposUnicos[] = $grupo;
                }
            }
        }
    }
    
    // Ordenar grupos alfabéticamente
    sort($gruposUnicos);
    
    mysqli_close($conexion);
    
    // Respuesta exitosa
    echo json_encode([
        'success' => true,
        'grupos' => $gruposUnicos
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>

