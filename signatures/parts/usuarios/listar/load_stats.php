<?php
/**
 * Archivo para cargar estadísticas de usuarios via AJAX
 * Maneja diferentes tipos de consultas para las tarjetas superiores
 */

// Asegurar que no haya salida antes del JSON
ob_clean();

require_once '../../../include/session.php';
require_once '../../../include/functions.php';

header('Content-Type: application/json');

try {
    // Verificar que se haya enviado el tipo de consulta
    if (!isset($_POST['tipo'])) {
        throw new Exception("Tipo de consulta no especificado");
    }
    
    $tipo = $_POST['tipo'];
    $conexion = conectar_bd();
    $total = 0;
    
    switch ($tipo) {
        case 'usuarios_conectados':
            // Total de usuarios conectados
            // Tabla: usersConexions, agrupado por userId y último estado
            try {
                // Primero verificar si la tabla existe
                $check_table = mysqli_query($conexion, "SHOW TABLES LIKE 'usersConexions'");
                if (mysqli_num_rows($check_table) == 0) {
                    // Si la tabla no existe, devolver 0
                    $total = 0;
                    break;
                }
                
                // Si la tabla existe, usar la consulta compleja
                $query = "
                    SELECT COUNT(DISTINCT uc.userId) as total 
                    FROM usersConexions uc
                    INNER JOIN (
                        SELECT userId, MAX(idUserConexion) as max_id
                        FROM usersConexions
                        GROUP BY userId
                    ) latest ON uc.userId = latest.userId AND uc.idUserConexion = latest.max_id
                    WHERE uc.state_connection = 'true'
                ";
            } catch (Exception $e) {
                // Si hay error, usar consulta simple
                $query = "SELECT 0 as total";
            }
            break;
            
        case 'total_usuarios':
            // Total de usuarios en la tabla usuarios
            $query = "SELECT COUNT(*) as total FROM usuarios";
            break;
            
        case 'usuarios_habilitados':
            // Usuarios con estado_usuario = 'true'
            $query = "SELECT COUNT(*) as total FROM usuarios WHERE estado_usuario = 'true'";
            break;
            
        case 'usuarios_bloqueados':
            // Usuarios con estado_usuario = 'false'
            $query = "SELECT COUNT(*) as total FROM usuarios WHERE estado_usuario = 'false'";
            break;
            
        default:
            throw new Exception("Tipo de consulta no válido: " . $tipo);
    }
    
    // Ejecutar consulta
    $result = mysqli_query($conexion, $query);
    
    if (!$result) {
        throw new Exception("Error en consulta: " . mysqli_error($conexion));
    }
    
    // Obtener resultado
    $row = mysqli_fetch_assoc($result);
    $total = (int)$row['total'];
    
    // Respuesta exitosa
    echo json_encode([
        'success' => true,
        'total' => $total,
        'tipo' => $tipo
    ]);
    
} catch (Exception $e) {
    // Respuesta de error
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'tipo' => isset($tipo) ? $tipo : 'desconocido'
    ]);
}

mysqli_close($conexion);
?>
