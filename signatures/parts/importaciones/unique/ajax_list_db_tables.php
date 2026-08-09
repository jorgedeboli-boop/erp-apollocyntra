<?php
require_once '../../../include/session.php';
require_once '../../../include/functions.php';

header('Content-Type: application/json');

try {
    $conexion = conectar_bd();
    $db = defined('DB_NAME') ? DB_NAME : '';
    if ($db === '') {
        throw new Exception('DB_NAME no definido.');
    }

    $sql = "SELECT TABLE_NAME 
            FROM INFORMATION_SCHEMA.TABLES 
            WHERE TABLE_SCHEMA = ?
            ORDER BY TABLE_NAME ASC";
    $stmt = mysqli_prepare($conexion, $sql);
    if (!$stmt) {
        throw new Exception('Error preparando consulta.');
    }
    mysqli_stmt_bind_param($stmt, 's', $db);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);

    $tables = [];
    while ($row = mysqli_fetch_assoc($res)) {
        $tables[] = $row['TABLE_NAME'];
    }
    mysqli_stmt_close($stmt);
    mysqli_close($conexion);

    echo json_encode(['success' => true, 'tables' => $tables]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

?>

