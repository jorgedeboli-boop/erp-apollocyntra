<?php
require_once '../include/functions.php';

header('Content-Type: application/json');

try {
    // Obtener sucursal_parset por GET
    $sucursal_parset = isset($_GET['sucursal_parset']) ? (int)$_GET['sucursal_parset'] : 0;
    
    // Validar que se haya proporcionado sucursal_parset
    if (!$sucursal_parset) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'sucursal_parset es requerido'
        ]);
        exit;
    }

    $id_empresa = isset($_GET['id_empresa']) ? (int)$_GET['id_empresa'] : 0;
    
    // Validar que se haya proporcionado id_empresa
    if (empty($id_empresa)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'existe' => false,
            'message' => 'id_empresa es requerido'
        ]);
        exit;
    }

    $mysqli_fiskalyapp = obtenerConexionFiskalyPorEmpresa($id_empresa);
    
    // Verificar que existe la conexión a la base de datos Fiskaly
    if (!isset($mysqli_fiskalyapp)) {
        throw new Exception('Error de conexión a la base de datos Fiskaly: Variable $mysqli_fiskalyapp no está definida');
    }
    
    // Verificar que la conexión esté activa
    if ($mysqli_fiskalyapp->connect_errno) {
        throw new Exception('Error de conexión a la base de datos Fiskaly: ' . $mysqli_fiskalyapp->connect_error);
    }
    
    // Paso 1: Consultar datos_fiskaly_sucursales para obtener rel_empresa e id_client_fisklaly
    $query_sucursal = "SELECT rel_empresa, id_client_fisklaly, rel_firmante FROM datos_fiskaly_sucursales WHERE id_sucursal = ? LIMIT 1";
    $stmt_sucursal = $mysqli_fiskalyapp->prepare($query_sucursal);
    if (!$stmt_sucursal) {
        $error_msg = $mysqli_fiskalyapp->error;
        throw new Exception('Error al preparar la consulta de sucursal: ' . ($error_msg ? $error_msg : 'Error desconocido'));
    }
    
    $stmt_sucursal->bind_param('i', $sucursal_parset);
    $stmt_sucursal->execute();
    $result_sucursal = $stmt_sucursal->get_result();
    $row_sucursal = $result_sucursal->fetch_assoc();
    $stmt_sucursal->close();
    
    if (!$row_sucursal || !isset($row_sucursal['rel_empresa'])) {
        throw new Exception('No se encontró la sucursal en datos_fiskaly_sucursales');
    }
    
    $rel_empresa = (int)$row_sucursal['rel_empresa'];
    $id_client_fisklaly = isset($row_sucursal['id_client_fisklaly']) ? $row_sucursal['id_client_fisklaly'] : null;
    $rel_firmante = isset($row_sucursal['rel_firmante']) ? $row_sucursal['rel_firmante'] : null;
    
    // Paso 2: Consultar datos_fiskaly_empresas para obtener clave_api y secret_clave_api
    $query_empresa = "SELECT clave_api, secret_clave_api FROM datos_fiskaly_empresas WHERE rel_empresa = ? LIMIT 1";
    $stmt_empresa = $mysqli_fiskalyapp->prepare($query_empresa);
    if (!$stmt_empresa) {
        $error_msg = $mysqli_fiskalyapp->error;
        throw new Exception('Error al preparar la consulta de empresa: ' . ($error_msg ? $error_msg : 'Error desconocido'));
    }
    
    $stmt_empresa->bind_param('i', $rel_empresa);
    $stmt_empresa->execute();
    $result_empresa = $stmt_empresa->get_result();
    $row_empresa = $result_empresa->fetch_assoc();
    $stmt_empresa->close();
    
    if (!$row_empresa || !isset($row_empresa['clave_api']) || !isset($row_empresa['secret_clave_api'])) {
        throw new Exception('No se encontraron las credenciales para la empresa');
    }
    
    echo json_encode([
        'success' => true,
        'clave_api' => $row_empresa['clave_api'],
        'secret_clave_api' => $row_empresa['secret_clave_api'],
        'rel_empresa' => $rel_empresa,
        'id_client_fisklaly' => $id_client_fisklaly,
        'id_firmante' => $rel_firmante
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
