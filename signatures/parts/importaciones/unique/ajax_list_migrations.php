<?php
require_once '../../../include/session.php';
require_once '../../../include/functions.php';
require_once './ajax_ftp.php';

header('Content-Type: application/json');

try {
    $conn = importaciones_ftp_connect();
    $remoteDir = '/migration';
    importaciones_ftp_ensure_dir($conn, $remoteDir);

    $raw = @ftp_nlist($conn, $remoteDir);
    ftp_close($conn);

    $files = [];
    if (is_array($raw)) {
        foreach ($raw as $item) {
            $base = basename((string)$item);
            if ($base === '' || $base === '.' || $base === '..') continue;
            if (!preg_match('/\.sql$/i', $base)) continue;
            $files[] = $base;
        }
    }

    sort($files);

    echo json_encode(['success' => true, 'files' => $files]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

?>
