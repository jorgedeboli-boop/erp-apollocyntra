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
    $id_sucursal = isset($_POST['id_sucursal']) ? (int)$_POST['id_sucursal'] : 0;
    $nombre_sucursal = isset($_POST['nombre_sucursal']) ? trim($_POST['nombre_sucursal']) : '';
    $rel_empresa = isset($_POST['rel_empresa']) ? (int)$_POST['rel_empresa'] : 0;
    $rel_firmante = isset($_POST['rel_firmante']) ? trim($_POST['rel_firmante']) : '';
    $id_client_fisklaly = isset($_POST['id_client_fisklaly']) ? trim($_POST['id_client_fisklaly']) : '';
    
    // Validar datos
    if (!$id_sucursal) {
        throw new Exception('ID de sucursal no válido');
    }
    if (!$nombre_sucursal) {
        throw new Exception('Nombre de sucursal no válido');
    }
    if (!$rel_empresa) {
        throw new Exception('ID de empresa no válido');
    }
    if (!$rel_firmante) {
        throw new Exception('ID de firmante no válido');
    }
    if (!$id_client_fisklaly) {
        throw new Exception('ID de cliente Fiskaly no válido');
    }
    
    // Verificar que existe la conexión a la base de datos Fiskaly
    $mysqli_fiskalyapp = obtenerConexionFiskalyPorEmpresa($rel_empresa);
    
    // Verificar si ya existe un registro para este cliente y sucursal
    $query_check = "SELECT COUNT(*) as total FROM datos_fiskaly_sucursales WHERE id_client_fisklaly = ? AND id_sucursal = ?";
    $stmt_check = $mysqli_fiskalyapp->prepare($query_check);
    if (!$stmt_check) {
        $error_msg = $mysqli_fiskalyapp->error;
        throw new Exception('Error al preparar la consulta de verificación: ' . ($error_msg ? $error_msg : 'Error desconocido'));
    }
    
    $stmt_check->bind_param('si', $id_client_fisklaly, $id_sucursal);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    $row_check = $result_check->fetch_assoc();
    $stmt_check->close();
    
    if ($row_check['total'] > 0) {
        throw new Exception('Ya existe un registro para este cliente y sucursal');
    }
    
    // Insertar los datos
    $query_insert = "
        INSERT INTO datos_fiskaly_sucursales 
        (id_sucursal, nombre_sucursal, rel_empresa, rel_firmante, id_client_fisklaly) 
        VALUES (?, ?, ?, ?, ?)
    ";
    
    $stmt_insert = $mysqli_fiskalyapp->prepare($query_insert);
    if (!$stmt_insert) {
        $error_msg = $mysqli_fiskalyapp->error;
        throw new Exception('Error al preparar la consulta de inserción: ' . ($error_msg ? $error_msg : 'Error desconocido'));
    }
    
    $stmt_insert->bind_param('issss', $id_sucursal, $nombre_sucursal, $rel_empresa, $rel_firmante, $id_client_fisklaly);
    
    if (!$stmt_insert->execute()) {
        throw new Exception('Error al insertar los datos: ' . $stmt_insert->error);
    }
    
    $stmt_insert->close();
    
    echo json_encode([
        'success' => true,
        'message' => 'Cliente guardado en la base de datos correctamente'
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
