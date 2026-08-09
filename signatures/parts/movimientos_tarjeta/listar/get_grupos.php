<?php
/**
 * Archivo para obtener todos los grupos existentes en movimientos_tarjeta
 */

require_once '../../../include/session.php';

header('Content-Type: application/json');

try {
    // Conectar BD
    $conexion = conectar_bd();
    
    // Obtener grupos únicos
    $queryGrupos = "SELECT DISTINCT grupos FROM movimientos_tarjeta WHERE grupos != '' AND grupos IS NOT NULL ORDER BY grupos";
    $resultGrupos = mysqli_query($conexion, $queryGrupos);
    
    $gruposUnicos = [];
    if ($resultGrupos) {
        while ($rowGrupo = mysqli_fetch_assoc($resultGrupos)) {
            $grupo = trim($rowGrupo['grupos']);
            if (!empty($grupo)) {
                $gruposUnicos[] = $grupo;
            }
        }
    }
    
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

