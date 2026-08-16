<?php
/**
 * Archivo para obtener todos los grupos existentes en las tablas de movimientos
 */

require_once '../../../include/session.php';

header('Content-Type: application/json');

try {
    // Conectar BD
    $conexion = conectar_bd();
    
    $gruposUnicos = [];
    $resultTablas = mysqli_query($conexion, "SHOW TABLES LIKE 'movimientos_de_caja_%'");
    
    while ($resultTablas && ($rowTabla = mysqli_fetch_row($resultTablas))) {
        if (!preg_match('/^movimientos_de_caja_\d+$/', $rowTabla[0])) {
            continue;
        }
        $tableName = $rowTabla[0];
        
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

