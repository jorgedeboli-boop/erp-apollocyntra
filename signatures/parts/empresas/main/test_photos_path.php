<?php
require_once '../../../include/session.php';
require_once '../../../include/functions.php';

// Headers para JSON
header('Content-Type: application/json');

try {
    // Verificar permisos
    if (!puede_acceder_a('empresas')) {
        echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
        exit;
    }
    
    $id_empresa = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    if (!$id_empresa) {
        echo json_encode(['success' => false, 'message' => 'ID de empresa no válido']);
        exit;
    }
    
    $conexion = conectar_bd();
    
    // Obtener logotipo actual de la empresa
    $query = "SELECT logotipo_empresa FROM empresas WHERE id_empresa = ?";
    $stmt = mysqli_prepare($conexion, $query);
    mysqli_stmt_bind_param($stmt, 'i', $id_empresa);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $logotipo_actual = null;
    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $logotipo_actual = $row['logotipo_empresa'];
    }
    
    mysqli_stmt_close($stmt);
    mysqli_close($conexion);
    
    // Verificar ruta de photos
    $ruta_photos = '../../../photos/';
    $ruta_absoluta = realpath($ruta_photos);
    
    // Listar archivos en photos
    $archivos = [];
    if (is_dir($ruta_photos)) {
        $files = scandir($ruta_photos);
        foreach ($files as $file) {
            if ($file != '.' && $file != '..' && strpos($file, 'logotipo_empresa_' . $id_empresa) === 0) {
                $archivos[] = [
                    'nombre' => $file,
                    'ruta_completa' => $ruta_photos . $file,
                    'existe' => file_exists($ruta_photos . $file),
                    'tamaño' => file_exists($ruta_photos . $file) ? filesize($ruta_photos . $file) : 0
                ];
            }
        }
    }
    
    echo json_encode([
        'success' => true,
        'id_empresa' => $id_empresa,
        'logotipo_actual_bd' => $logotipo_actual,
        'ruta_photos' => $ruta_photos,
        'ruta_absoluta' => $ruta_absoluta,
        'ruta_existe' => is_dir($ruta_photos),
        'archivos_encontrados' => $archivos,
        'total_archivos' => count($archivos)
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
