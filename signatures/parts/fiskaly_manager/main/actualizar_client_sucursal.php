<?php
require_once '../../../include/session.php';

header('Content-Type: application/json');

try {
    // Verificar que sea una petición POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Método no permitido']);
        exit;
    }
    
    // Obtener datos del formulario
    $id_client_fisklaly = isset($_POST['id_client_fisklaly']) ? trim($_POST['id_client_fisklaly']) : '';
    $id_sucursal = isset($_POST['id_sucursal']) ? (int)$_POST['id_sucursal'] : 0;
    $nombre_sucursal = isset($_POST['nombre_sucursal']) ? trim($_POST['nombre_sucursal']) : '';
    $rel_empresa = isset($_POST['rel_empresa']) ? (int)$_POST['rel_empresa'] : 0;
    
    // Validar datos
    if (!$id_client_fisklaly) {
        throw new Exception('ID de cliente Fiskaly no válido');
    }
    
    if (!$id_sucursal) {
        throw new Exception('ID de sucursal no válido');
    }
    
    if (!$nombre_sucursal) {
        throw new Exception('Nombre de sucursal no válido');
    }
    
    if (!$rel_empresa) {
        throw new Exception('ID de empresa no válido');
    }
    
    // Verificar que existe la conexión a la base de datos Fiskaly
    $mysqli_fiskalyapp = obtenerConexionFiskalyPorEmpresa($rel_empresa);
    
    // Verificar si existe un registro para este cliente
    $query_check = "SELECT COUNT(*) as total FROM datos_fiskaly_sucursales WHERE id_client_fisklaly = ?";
    $stmt_check = $mysqli_fiskalyapp->prepare($query_check);
    if (!$stmt_check) {
        $error_msg = $mysqli_fiskalyapp->error;
        throw new Exception('Error al preparar la consulta de verificación: ' . ($error_msg ? $error_msg : 'Error desconocido'));
    }
    
    $stmt_check->bind_param('s', $id_client_fisklaly);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    $row_check = $result_check->fetch_assoc();
    $stmt_check->close();
    
    if ($row_check['total'] > 0) {
        // Actualizar el registro existente
        $query_update = "
            UPDATE datos_fiskaly_sucursales 
            SET id_sucursal = ?, 
                nombre_sucursal = ?, 
                rel_empresa = ?
            WHERE id_client_fisklaly = ?
        ";
        
        $stmt_update = $mysqli_fiskalyapp->prepare($query_update);
        if (!$stmt_update) {
            $error_msg = $mysqli_fiskalyapp->error;
            throw new Exception('Error al preparar la consulta de actualización: ' . ($error_msg ? $error_msg : 'Error desconocido'));
        }
        
        $stmt_update->bind_param('isis', $id_sucursal, $nombre_sucursal, $rel_empresa, $id_client_fisklaly);
        
        if (!$stmt_update->execute()) {
            throw new Exception('Error al actualizar los datos: ' . $stmt_update->error);
        }
        
        $stmt_update->close();
    } else {
        throw new Exception('No se encontró un registro para este cliente');
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Cliente actualizado en la base de datos correctamente'
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
