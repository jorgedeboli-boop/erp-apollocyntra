<?php
/**
 * Desconecta al usuario actualizando la última conexión activa en usersConexions.
 */

require_once '../../../include/session.php';
require_once '../../../include/functions.php';

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Método no permitido']);
        exit;
    }

    if (!isset($_SESSION['usuario_autenticado']) || !$_SESSION['usuario_autenticado']) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Usuario no autenticado']);
        exit;
    }

    $es_usuario_root = (isset($usuario_root) && $usuario_root === 'true');
    $es_usuario_super_administrador = (isset($usuario_super_administrador) && $usuario_super_administrador === 'true');

    $id_usuario = isset($_POST['id_usuario']) ? (int) $_POST['id_usuario'] : 0;

    if ($id_usuario <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'ID de usuario no válido']);
        exit;
    }

    $conexion = conectar_bd();
    if (!$conexion) {
        throw new Exception('Error de conexión');
    }

    $query_usuario = "SELECT usuario, usuario_root, super_admin FROM usuarios WHERE id_usuario = ?";
    $stmt_usuario = mysqli_prepare($conexion, $query_usuario);
    mysqli_stmt_bind_param($stmt_usuario, 'i', $id_usuario);
    mysqli_stmt_execute($stmt_usuario);
    $result_usuario = mysqli_stmt_get_result($stmt_usuario);

    if (!$result_usuario || mysqli_num_rows($result_usuario) === 0) {
        mysqli_stmt_close($stmt_usuario);
        mysqli_close($conexion);
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Usuario no encontrado']);
        exit;
    }

    $usuario_objetivo = mysqli_fetch_assoc($result_usuario);
    mysqli_stmt_close($stmt_usuario);

    if (!$es_usuario_root && ($usuario_objetivo['usuario_root'] ?? 'false') === 'true') {
        mysqli_close($conexion);
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'No tienes permisos para desconectar este usuario']);
        exit;
    }
    if (!$es_usuario_root && !$es_usuario_super_administrador && ($usuario_objetivo['super_admin'] ?? 'false') === 'true') {
        mysqli_close($conexion);
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'No tienes permisos para desconectar este usuario']);
        exit;
    }

    $query_ultima = "
        SELECT uc.idUserConexion, uc.state_connection
        FROM usersConexions uc
        WHERE uc.userId = ?
        ORDER BY uc.idUserConexion DESC
        LIMIT 1
    ";
    $stmt_ultima = mysqli_prepare($conexion, $query_ultima);
    mysqli_stmt_bind_param($stmt_ultima, 'i', $id_usuario);
    mysqli_stmt_execute($stmt_ultima);
    $result_ultima = mysqli_stmt_get_result($stmt_ultima);

    if (!$result_ultima || mysqli_num_rows($result_ultima) === 0) {
        mysqli_stmt_close($stmt_ultima);
        mysqli_close($conexion);
        echo json_encode(['success' => false, 'error' => 'No se encontró ninguna conexión para este usuario']);
        exit;
    }

    $ultima_conexion = mysqli_fetch_assoc($result_ultima);
    mysqli_stmt_close($stmt_ultima);

    $id_user_conexion = (int) ($ultima_conexion['idUserConexion'] ?? 0);
    if ($id_user_conexion <= 0) {
        mysqli_close($conexion);
        echo json_encode(['success' => false, 'error' => 'Conexión no válida']);
        exit;
    }

    if (($ultima_conexion['state_connection'] ?? 'false') !== 'true') {
        mysqli_close($conexion);
        echo json_encode(['success' => false, 'error' => 'El usuario ya está desconectado']);
        exit;
    }

    $queryUpdate = "UPDATE usersConexions
                    SET state_connection = 'false'
                    WHERE userId = ?
                    AND state_connection = 'true'";
    $stmtUpdate = mysqli_prepare($conexion, $queryUpdate);
    if ($stmtUpdate) {
        mysqli_stmt_bind_param($stmtUpdate, 'i', $id_usuario);
        mysqli_stmt_execute($stmtUpdate);
        mysqli_stmt_close($stmtUpdate);
    }

    $state_connection = 'false';
    $usuario_parset = $usuario_objetivo['usuario'] ?? '';
    $userAgent = obtener_user_agent();
    $ipvVisitante = obtener_ip_visitante();
    $logTxt = 'Desde central se han cerrado todas las sesiones del usuario '.$usuario_parset;
    $groupId = '55';

    $query = "INSERT INTO usersConexions (
        state_connection, 
        dateConexion, 
        userId, 
        userAgent, 
        ipNumberUser, 
        logTxt, 
        groupId
    ) VALUES (?, NOW(), ?, ?, ?, ?, ?)";
    
    $stmt = mysqli_prepare($conexion, $query);
    
    if (!$stmt) {
        mysqli_close($conexion);
        return false;
    }
    
    mysqli_stmt_bind_param($stmt, 'sissss', 
        $state_connection, 
        $id_usuario, 
        $userAgent, 
        $ipvVisitante, 
        $logTxt, 
        $groupId
    );
    
    $resultado = mysqli_stmt_execute($stmt);
    
    mysqli_stmt_close($stmt);
    mysqli_close($conexion);

    echo json_encode([
        'success' => true,
        'message' => 'Usuario desconectado correctamente',
        'id_user_conexion' => $id_user_conexion,
        'estado_conexion' => 'false',
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
    ]);
}
