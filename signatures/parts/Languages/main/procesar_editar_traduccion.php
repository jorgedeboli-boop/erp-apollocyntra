<?php
// Verificar que se ejecute solo por AJAX
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    exit('Acceso denegado');
}

// Incluir archivos necesarios
require_once '../../../include/session.php';
require_once '../../../include/functions.php';

// Verificar sesión
if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

// Verificar método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

// Obtener y validar datos
$id_translations = isset($_POST['id_translations']) ? (int)$_POST['id_translations'] : 0;
$entry_translate = isset($_POST['entry_translate']) ? trim($_POST['entry_translate']) : '';
$exit_translate = isset($_POST['exit_translate']) ? trim($_POST['exit_translate']) : '';

// Validaciones
if ($id_translations <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID de traducción inválido']);
    exit;
}

if (empty($entry_translate)) {
    echo json_encode(['success' => false, 'message' => 'El campo Entrada es obligatorio']);
    exit;
}

if (empty($exit_translate)) {
    echo json_encode(['success' => false, 'message' => 'El campo Traducción es obligatorio']);
    exit;
}

// Sanitizar datos
$entry_translate = htmlspecialchars($entry_translate, ENT_QUOTES, 'UTF-8');
$exit_translate = htmlspecialchars($exit_translate, ENT_QUOTES, 'UTF-8');

// Establecer conexión
$conexion = conectar_bd();
if (!$conexion) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error de conexión a la base de datos']);
    exit;
}

try {
    // Iniciar transacción
    mysqli_autocommit($conexion, false);
    
    // Verificar que la traducción existe
    $checkQuery = "SELECT id_translations FROM Translations WHERE id_translations = ?";
    $checkStmt = mysqli_prepare($conexion, $checkQuery);
    mysqli_stmt_bind_param($checkStmt, 'i', $id_translations);
    mysqli_stmt_execute($checkStmt);
    $checkResult = mysqli_stmt_get_result($checkStmt);
    
    if (mysqli_num_rows($checkResult) === 0) {
        mysqli_stmt_close($checkStmt);
        mysqli_rollback($conexion);
        mysqli_close($conexion);
        echo json_encode(['success' => false, 'message' => 'Traducción no encontrada']);
        exit;
    }
    
    mysqli_stmt_close($checkStmt);
    
    // Actualizar la traducción
    $updateQuery = "UPDATE Translations SET entry_translate = ?, exit_translate = ? WHERE id_translations = ?";
    $updateStmt = mysqli_prepare($conexion, $updateQuery);
    
    if (!$updateStmt) {
        throw new Exception('Error en la consulta de actualización');
    }
    
    mysqli_stmt_bind_param($updateStmt, 'ssi', $entry_translate, $exit_translate, $id_translations);
    
    if (!mysqli_stmt_execute($updateStmt)) {
        throw new Exception('Error al ejecutar la actualización');
    }
    
    // Confirmar transacción
    mysqli_commit($conexion);
    mysqli_stmt_close($updateStmt);
    mysqli_close($conexion);
    
    // Log de la acción
    error_log("Traducción actualizada - ID: {$id_translations} por usuario: {$_SESSION['usuario_id']}");
    
    echo json_encode([
        'success' => true,
        'message' => 'Traducción actualizada correctamente'
    ]);
    
} catch (Exception $e) {
    // Rollback en caso de error
    if (isset($conexion)) {
        mysqli_rollback($conexion);
        mysqli_close($conexion);
    }
    
    error_log("Error al actualizar traducción: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error interno del servidor'
    ]);
}
?>
