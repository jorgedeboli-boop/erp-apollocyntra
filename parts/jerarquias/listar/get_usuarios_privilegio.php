<?php
require_once '../../../include/session.php';
require_once '../../../include/functions.php';

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Petición inválida']);
    exit;
}

$id_privilegio = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id_privilegio <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'ID de privilegio inválido']);
    exit;
}

try {
    $conexion = conectar_bd();
    if (!$conexion) {
        throw new Exception('Error de conexión a la base de datos');
    }

    $queryPrivilegio = 'SELECT id_privilegios, nombre_privilegio FROM privilegios_usuarios WHERE id_privilegios = ? LIMIT 1';
    $stmtPrivilegio = mysqli_prepare($conexion, $queryPrivilegio);
    if (!$stmtPrivilegio) {
        throw new Exception('Error en la consulta: ' . mysqli_error($conexion));
    }
    mysqli_stmt_bind_param($stmtPrivilegio, 'i', $id_privilegio);
    mysqli_stmt_execute($stmtPrivilegio);
    $resPrivilegio = mysqli_stmt_get_result($stmtPrivilegio);
    $privilegio = $resPrivilegio ? mysqli_fetch_assoc($resPrivilegio) : null;
    if ($resPrivilegio) {
        mysqli_free_result($resPrivilegio);
    }
    mysqli_stmt_close($stmtPrivilegio);

    if (!$privilegio) {
        mysqli_close($conexion);
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Privilegio no encontrado']);
        exit;
    }

    $queryUsuarios = "
        SELECT
            u.id_usuario,
            u.usuario,
            u.estado_usuario
        FROM usuarios u
        WHERE u.privilegio_usuario = ?
          AND COALESCE(u.usuario_root, 'false') <> 'true'
        ORDER BY u.usuario ASC
    ";
    $stmtUsuarios = mysqli_prepare($conexion, $queryUsuarios);
    if (!$stmtUsuarios) {
        throw new Exception('Error en la consulta: ' . mysqli_error($conexion));
    }
    mysqli_stmt_bind_param($stmtUsuarios, 'i', $id_privilegio);
    mysqli_stmt_execute($stmtUsuarios);
    $resUsuarios = mysqli_stmt_get_result($stmtUsuarios);

    $usuarios = [];
    if ($resUsuarios) {
        while ($fila = mysqli_fetch_assoc($resUsuarios)) {
            $estado = strtolower(trim((string) ($fila['estado_usuario'] ?? '')));
            $usuarios[] = [
                'id_usuario' => (int) ($fila['id_usuario'] ?? 0),
                'usuario' => (string) ($fila['usuario'] ?? ''),
                'estado_usuario' => $estado === 'true' ? 'Habilitado' : 'Deshabilitado',
            ];
        }
        mysqli_free_result($resUsuarios);
    }
    mysqli_stmt_close($stmtUsuarios);
    mysqli_close($conexion);

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => true,
        'privilegio' => [
            'id_privilegios' => (int) $privilegio['id_privilegios'],
            'nombre_privilegio' => (string) $privilegio['nombre_privilegio'],
        ],
        'usuarios' => $usuarios,
        'total' => count($usuarios),
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    if (isset($conexion) && $conexion) {
        mysqli_close($conexion);
    }
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error del servidor: ' . $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
}
