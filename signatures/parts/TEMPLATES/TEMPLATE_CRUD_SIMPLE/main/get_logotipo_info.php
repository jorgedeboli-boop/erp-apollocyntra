<?php
require_once '../../../include/session.php';
require_once '../../../include/functions.php';
// Headers para JSON
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

// Log de debug
//error_log("get_logotipo_info.php - Iniciando");


try {
    // Verificar que sea una petición GET
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(405);
        echo json_encode(array('success' => false, 'message' => 'Método no permitido'));
        exit;
    }
    
    // Obtener ID de la empresa
    $id_empresa = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    if (!$id_empresa) {
        echo json_encode(array('success' => false, 'message' => 'ID de empresa no válido'));
        exit;
    }
    
    // Conectar a la base de datos
    $conexion = conectar_bd();
    
    // Consulta para obtener el logotipo de la empresa
    $query = "SELECT logotipo_empresa FROM empresas WHERE id_empresa = ?";
    $stmt = mysqli_prepare($conexion, $query);
    mysqli_stmt_bind_param($stmt, 'i', $id_empresa);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $logotipo_empresa = $row['logotipo_empresa'];
        
        if (!empty($logotipo_empresa)) {
            // Verificar que el archivo existe físicamente
            $ruta_archivo = '../../../photos/' . $logotipo_empresa;
            
            // Debug: log de la consulta
            error_log("get_logotipo_info: empresa_id={$id_empresa}, archivo={$logotipo_empresa}, ruta={$ruta_archivo}, existe=" . (file_exists($ruta_archivo) ? 'SI' : 'NO'));
            
            if (file_exists($ruta_archivo)) {
                echo json_encode(array(
                    'success' => true,
                    'logotipo' => $logotipo_empresa,
                    'id_empresa' => $id_empresa
                ));
            } else {
                echo json_encode(array(
                    'success' => false,
                    'message' => 'Archivo de logotipo no encontrado en el servidor',
                    'logotipo_bd' => $logotipo_empresa
                ));
            }
        } else {
            echo json_encode(array(
                'success' => false,
                'message' => 'No hay logotipo configurado para esta empresa'
            ));
        }
    } else {
        echo json_encode(array(
            'success' => false,
            'message' => 'Empresa no encontrada'
        ));
    }
    
    mysqli_stmt_close($stmt);
    mysqli_close($conexion);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(array(
        'success' => false,
        'message' => 'Error interno del servidor: ' . $e->getMessage()
    ));
}
?>
