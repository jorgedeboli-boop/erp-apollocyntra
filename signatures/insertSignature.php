<?php
require_once '../include/session.php';
require_once '../include/functions.php';

ob_start();
ob_clean();

header('Content-Type: application/json');

try {
    // Verificar que sea una petición POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }
    
    // Validar parámetros
    $parametros_faltantes = array();
    
    if (!isset($_POST['iditem']) || empty($_POST['iditem'])) {
        $parametros_faltantes[] = 'iditem';
    }
    if (!isset($_POST['typeitem']) || empty($_POST['typeitem'])) {
        $parametros_faltantes[] = 'typeitem';
    }
    if (!isset($_POST['recibe_euros'])) {
        $parametros_faltantes[] = 'recibe_euros';
    }
    if (!isset($_POST['sucursal_signature']) || empty($_POST['sucursal_signature'])) {
        $parametros_faltantes[] = 'sucursal_signature';
    }
    
    if (count($parametros_faltantes) > 0) {
        throw new Exception('Parámetros faltantes o vacíos: ' . implode(', ', $parametros_faltantes));
    }
    
    $iditem = (int)$_POST['iditem'];
    $typeitem = trim($_POST['typeitem']);
    $state_signature = 'false';
    $recibe_euros = trim($_POST['recibe_euros']);
    $sucursal_signature = (int)$_POST['sucursal_signature'];
    // Conectar BD
    $conexion = conectar_bd();
    
    // Usar variables de sesión por defecto
    // consulta de latabla sucursal para obtener el companyId
    $query_sucursal = "SELECT empresa_id FROM sucursal WHERE id_sucursal = ?";
    $stmt_sucursal = mysqli_prepare($conexion, $query_sucursal);
    mysqli_stmt_bind_param($stmt_sucursal, 'i', $sucursal_signature);
    mysqli_stmt_execute($stmt_sucursal);
    $result_sucursal = mysqli_stmt_get_result($stmt_sucursal);
    $sucursal_data = mysqli_fetch_assoc($result_sucursal);
    $companyId_signature = $sucursal_data['empresa_id'];
    if(!$companyId_signature){
        throw new Exception('Empresa no encontrada');
    }
    
    // Si es tipo "user", obtener datos específicos del usuario
    if ($typeitem == "user") {
        $query_user = "SELECT usuarios.sucursal_usuario, usuarios.empresa_id 
                       FROM usuarios 
                       LEFT JOIN sucursal ON sucursal.id_sucursal = usuarios.sucursal_usuario 
                       WHERE id_usuario = ?";
        
        $stmt_user = mysqli_prepare($conexion, $query_user);
        mysqli_stmt_bind_param($stmt_user, 'i', $iditem);
        mysqli_stmt_execute($stmt_user);
        $result_user = mysqli_stmt_get_result($stmt_user);
        
        if ($result_user && mysqli_num_rows($result_user) > 0) {
            $user_data = mysqli_fetch_assoc($result_user);
            $sucursal_signature = $user_data['sucursal_usuario'];
            $companyId_signature = $user_data['empresa_id'];
        }
        
        mysqli_stmt_close($stmt_user);
    }
    
    // Insertar firma en la tabla Signatures
    $query_insert = "INSERT INTO Signatures 
                     (ItemId, typeItem, companyId, userCreate, sucursalSignature, state_signature, recibe_euros, createDate) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
    
    $stmt_insert = mysqli_prepare($conexion, $query_insert);
    
    if (!$stmt_insert) {
        throw new Exception('Error al preparar consulta: ' . mysqli_error($conexion));
    }
    
    mysqli_stmt_bind_param($stmt_insert, 'issssss', 
        $iditem, 
        $typeitem, 
        $companyId_signature, 
        $usuario_id, 
        $sucursal_signature, 
        $state_signature, 
        $recibe_euros
    );
    
    if (!mysqli_stmt_execute($stmt_insert)) {
        throw new Exception('Error al insertar firma: ' . mysqli_stmt_error($stmt_insert));
    }
    
    $id_signature = mysqli_insert_id($conexion);
    
    mysqli_stmt_close($stmt_insert);
    mysqli_close($conexion);
    
    // Respuesta exitosa
    echo json_encode(array(
        'success' => true,
        'status' => 'ok',
        'id_signature' => $id_signature,
        'message' => 'Firma insertada correctamente'
    ));
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(array(
        'success' => false,
        'status' => 'error',
        'error_desc' => 'ko',
        'message' => $e->getMessage()
    ));
}
?>