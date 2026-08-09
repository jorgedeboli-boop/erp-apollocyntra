<?php
require_once '../../../include/session.php';
require_once '../../../include/functions.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Método no permitido']);
        exit;
    }

    $id_sello = isset($_POST['id_sello']) ? (int) $_POST['id_sello'] : 0;
    if ($id_sello <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID de sello no válido']);
        exit;
    }

    if (!isset($_FILES['logotipo']) || $_FILES['logotipo']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'No se ha subido ningún archivo o ha ocurrido un error']);
        exit;
    }

    $archivo = $_FILES['logotipo'];
    $tipos_permitidos = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    $tipo_archivo = mime_content_type($archivo['tmp_name']);

    if (!in_array($tipo_archivo, $tipos_permitidos, true)) {
        echo json_encode(['success' => false, 'message' => 'Solo se permiten archivos de imagen (JPG, PNG, GIF)']);
        exit;
    }

    if ($archivo['size'] > 5 * 1024 * 1024) {
        echo json_encode(['success' => false, 'message' => 'El archivo es demasiado grande. Máximo 5MB']);
        exit;
    }

    $conexion = conectar_bd();

    $stmtCheck = mysqli_prepare($conexion, 'SELECT id_sello, imagen_logotipo FROM sellos WHERE id_sello = ? LIMIT 1');
    mysqli_stmt_bind_param($stmtCheck, 'i', $id_sello);
    mysqli_stmt_execute($stmtCheck);
    $resultCheck = mysqli_stmt_get_result($stmtCheck);

    if (!$resultCheck || mysqli_num_rows($resultCheck) === 0) {
        mysqli_stmt_close($stmtCheck);
        mysqli_close($conexion);
        echo json_encode(['success' => false, 'message' => 'Sello no encontrado']);
        exit;
    }

    $rowActual = mysqli_fetch_assoc($resultCheck);
    $logotipoAnterior = $rowActual['imagen_logotipo'] ?? '';
    mysqli_stmt_close($stmtCheck);

    $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
    if ($extension === '') {
        $map = ['image/jpeg' => 'jpg', 'image/jpg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif'];
        $extension = $map[$tipo_archivo] ?? 'jpg';
    }

    $nombre_archivo = 'logotipo_sello_' . $id_sello . '_' . time() . '.' . $extension;
    $ruta_destino = '../../../photos/' . $nombre_archivo;

    if (!empty($logotipoAnterior)) {
        $rutaAnterior = '../../../photos/' . $logotipoAnterior;
        if (file_exists($rutaAnterior)) {
            unlink($rutaAnterior);
        }
    }

    if (!move_uploaded_file($archivo['tmp_name'], $ruta_destino)) {
        mysqli_close($conexion);
        throw new Exception('Error al mover el archivo subido');
    }

    $stmtUpdate = mysqli_prepare($conexion, 'UPDATE sellos SET imagen_logotipo = ? WHERE id_sello = ?');
    mysqli_stmt_bind_param($stmtUpdate, 'si', $nombre_archivo, $id_sello);

    if (!mysqli_stmt_execute($stmtUpdate)) {
        unlink($ruta_destino);
        mysqli_stmt_close($stmtUpdate);
        mysqli_close($conexion);
        throw new Exception('Error al actualizar la base de datos');
    }

    mysqli_stmt_close($stmtUpdate);
    mysqli_close($conexion);

    echo json_encode([
        'success' => true,
        'message' => 'Logotipo subido correctamente',
        'nombre_archivo' => $nombre_archivo,
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error interno del servidor: ' . $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
}
