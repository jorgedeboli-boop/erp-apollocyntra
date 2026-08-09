<?php
/**
 * Archivo para servir el logotipo de la gasto
 * Compatible con PHP 7.0
 */

require_once '../../../include/session.php';
require_once '../../../include/functions.php';

// Verificar que sea una petición GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    exit;
}

// Obtener el ID de la gasto
$id_gasto = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$id_gasto) {
    http_response_code(400);
    exit;
}

try {
    // Conectar a la base de datos
    $conexion = conectar_bd();
    
    // Consulta para obtener el logotipo de la gasto
    $query = "SELECT logotipo_gasto FROM gastos WHERE id_gasto = ?";
    $stmt = mysqli_prepare($conexion, $query);
    mysqli_stmt_bind_param($stmt, 'i', $id_gasto);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $logotipo_gasto = $row['logotipo_gasto'];
        
        if (!empty($logotipo_gasto)) {
            // Construir la ruta completa al archivo
            $ruta_archivo = '../../../photos/' . $logotipo_gasto;
            
            // Verificar que el archivo existe
            if (file_exists($ruta_archivo)) {
                // Obtener información del archivo
                $tipo_mime = mime_content_type($ruta_archivo);
                $tamaño_archivo = filesize($ruta_archivo);
                
                // Verificar que es una imagen válida
                $tipos_permitidos = array(
                    'image/jpeg',
                    'image/jpg', 
                    'image/png',
                    'image/gif',
                    'image/webp'
                );
                
                if (in_array($tipo_mime, $tipos_permitidos)) {
                    // Configurar headers para la imagen
                    header('Content-Type: ' . $tipo_mime);
                    header('Content-Length: ' . $tamaño_archivo);
                    header('Cache-Control: no-cache, no-store, must-revalidate'); // Sin cache
                    header('Pragma: no-cache');
                    header('Expires: 0');
                    
                    // Servir el archivo
                    readfile($ruta_archivo);
                    exit;
                } else {
                    // Tipo de archivo no permitido
                    http_response_code(415);
                    exit;
                }
            } else {
                // Archivo no encontrado
                http_response_code(404);
                exit;
            }
        } else {
            // No hay logotipo configurado
            http_response_code(404);
            exit;
        }
    } else {
        // Empresa no encontrada
        http_response_code(404);
        exit;
    }
    
} catch (Exception $e) {
    error_log("Error en get_logotipo.php: " . $e->getMessage());
    http_response_code(500);
    exit;
} finally {
    if (isset($conexion)) {
        mysqli_close($conexion);
    }
}
?>
