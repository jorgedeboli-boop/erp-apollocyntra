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
    $rel_empresa = isset($_POST['rel_empresa']) ? (int)$_POST['rel_empresa'] : 0;
    $clave_api = isset($_POST['clave_api']) ? trim($_POST['clave_api']) : '';
    $secret_clave_api = isset($_POST['secret_clave_api']) ? trim($_POST['secret_clave_api']) : '';
    $id_organization_fisklaly = isset($_POST['id_organization_fisklaly']) ? trim($_POST['id_organization_fisklaly']) : '';
    
    // Validar datos
    if (!$rel_empresa) {
        throw new Exception('ID de empresa no válido');
    }
    
    if (empty($clave_api) || empty($secret_clave_api) || empty($id_organization_fisklaly)) {
        throw new Exception('Todos los campos son obligatorios');
    }
    
    // Verificar que existe la conexión a la base de datos Fiskaly
    $mysqli_fiskalyapp = obtenerConexionFiskalyPorEmpresa($rel_empresa);
    
    // Verificar si ya existe un registro para esta empresa
    $query_check = "SELECT COUNT(*) as total FROM datos_fiskaly_empresas WHERE rel_empresa = ? ";
    $stmt_check = $mysqli_fiskalyapp->prepare($query_check);
    if (!$stmt_check) {
        $error_msg = $mysqli_fiskalyapp->error;
        throw new Exception('Error al preparar la consulta de verificación: ' . ($error_msg ? $error_msg : 'Error desconocido'));
    }
    
    $stmt_check->bind_param('i', $rel_empresa);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    $row_check = $result_check->fetch_assoc();
    $stmt_check->close();
    
    if ($row_check['total'] > 0) {
        throw new Exception('Ya existe una organización Fiskaly para esta empresa');
    }
    
    // Insertar los datos
    $query_insert = "
        INSERT INTO datos_fiskaly_empresas 
        (rel_empresa, clave_api, secret_clave_api, id_organization_fisklaly) 
        VALUES (?, ?, ?, ?)
    ";
    
    $stmt_insert = $mysqli_fiskalyapp->prepare($query_insert);
    if (!$stmt_insert) {
        $error_msg = $mysqli_fiskalyapp->error;
        throw new Exception('Error al preparar la consulta de inserción: ' . ($error_msg ? $error_msg : 'Error desconocido'));
    }
    
    $stmt_insert->bind_param('isss', $rel_empresa, $clave_api, $secret_clave_api, $id_organization_fisklaly);
    
    if (!$stmt_insert->execute()) {
        throw new Exception('Error al insertar los datos: ' . $stmt_insert->error);
    }
    
    $stmt_insert->close();
    
    echo json_encode([
        'success' => true,
        'message' => 'Organización Fiskaly creada correctamente'
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>

