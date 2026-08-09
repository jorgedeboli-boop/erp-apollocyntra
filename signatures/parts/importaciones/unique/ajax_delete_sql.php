<?php
require_once '../../../include/session.php';
require_once '../../../include/functions.php';
require_once './ajax_ftp.php';

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Método no permitido']);
        exit;
    }

    $filename = isset($_POST['filename']) ? importaciones_sanitize_sql_filename($_POST['filename']) : '';
    if ($filename === '') {
        throw new Exception('Falta filename.');
    }

    $conn = importaciones_ftp_connect();
    $remotePath = '/migration/' . $filename;

    if (!@ftp_delete($conn, $remotePath)) {
        ftp_close($conn);
        throw new Exception('No se pudo borrar el fichero del FTP.');
    }

    ftp_close($conn);

    echo json_encode([
        'success' => true,
        'message' => 'Fichero borrado correctamente: ' . $filename
    ]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

?>
