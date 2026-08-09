<?php
require_once '../../../include/session.php';
require_once '../../../include/functions.php';

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'Método no permitido']);
        exit;
    }
    
    $id_empresa = isset($_POST['id_empresa']) ? (int)$_POST['id_empresa'] : 0;
    
    if (!$id_empresa) {
        echo json_encode(['success' => false, 'message' => 'ID de empresa no válido']);
        exit;
    }
    
    $conexion = conectar_bd();
    
    $query = "SELECT logotipo_empresa FROM empresas WHERE id_empresa = ?";
    $stmt = mysqli_prepare($conexion, $query);
    mysqli_stmt_bind_param($stmt, 'i', $id_empresa);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $logotipo_actual = $row['logotipo_empresa'];
        
        if (!empty($logotipo_actual)) {
            $ruta_archivo = '../../../photos/' . $logotipo_actual;
            if (file_exists($ruta_archivo)) {
                unlink($ruta_archivo);
            }
            
            $query_update = "UPDATE empresas SET logotipo_empresa = NULL WHERE id_empresa = ?";
            $stmt_update = mysqli_prepare($conexion, $query_update);
            mysqli_stmt_bind_param($stmt_update, 'i', $id_empresa);
            
            if (mysqli_stmt_execute($stmt_update)) {
                // Comentado temporalmente para debug
                // log_accion('Logotipo de empresa eliminado', 'empresas', $id_empresa);
                echo json_encode(['success' => true, 'message' => 'Logotipo eliminado correctamente']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error al actualizar la base de datos']);
            }
            
            mysqli_stmt_close($stmt_update);
        } else {
            echo json_encode(['success' => false, 'message' => 'Esta empresa no tiene logotipo configurado']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Empresa no encontrada']);
    }
    
    mysqli_stmt_close($stmt);
    mysqli_close($conexion);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
