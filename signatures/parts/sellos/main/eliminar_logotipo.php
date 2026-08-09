<?php
require_once '../../../include/session.php';
require_once '../../../include/functions.php';

header('Content-Type: application/json; charset=utf-8');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'Método no permitido']);
        exit;
    }

    $id_sello = isset($_POST['id_sello']) ? (int) $_POST['id_sello'] : 0;
    if ($id_sello <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID de sello no válido']);
        exit;
    }

    $conexion = conectar_bd();
    $stmt = mysqli_prepare($conexion, 'SELECT imagen_logotipo FROM sellos WHERE id_sello = ? LIMIT 1');
    mysqli_stmt_bind_param($stmt, 'i', $id_sello);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $logotipo_actual = $row['imagen_logotipo'] ?? '';

        if (!empty($logotipo_actual)) {
            $ruta_archivo = '../../../photos/' . $logotipo_actual;
            if (file_exists($ruta_archivo)) {
                unlink($ruta_archivo);
            }

            $stmtUpdate = mysqli_prepare($conexion, 'UPDATE sellos SET imagen_logotipo = NULL WHERE id_sello = ?');
            mysqli_stmt_bind_param($stmtUpdate, 'i', $id_sello);

            if (mysqli_stmt_execute($stmtUpdate)) {
                echo json_encode(['success' => true, 'message' => 'Logotipo eliminado correctamente'], JSON_UNESCAPED_UNICODE);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error al actualizar la base de datos'], JSON_UNESCAPED_UNICODE);
            }
            mysqli_stmt_close($stmtUpdate);
        } else {
            echo json_encode(['success' => false, 'message' => 'Este sello no tiene logotipo configurado'], JSON_UNESCAPED_UNICODE);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Sello no encontrado'], JSON_UNESCAPED_UNICODE);
    }

    mysqli_stmt_close($stmt);
    mysqli_close($conexion);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
