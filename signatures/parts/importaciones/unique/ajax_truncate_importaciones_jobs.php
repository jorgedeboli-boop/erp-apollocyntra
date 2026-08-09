<?php
require_once '../../../include/session.php';
require_once '../../../include/functions.php';

header('Content-Type: application/json');

function importaciones_jobs_ensure_table($conexion) {
    $sql = "
        CREATE TABLE IF NOT EXISTS `importaciones_jobs` (
            `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `filename` VARCHAR(255) NOT NULL,
            `file_sha256` CHAR(64) NULL,
            `accion` ENUM('upload','execute_sql','import_data') NOT NULL,
            `estado` ENUM('pending','running','success','error') NOT NULL DEFAULT 'pending',
            `usuario_id` VARCHAR(64) NULL,
            `message` TEXT NULL,
            `meta_json` LONGTEXT NULL,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `started_at` DATETIME NULL,
            `finished_at` DATETIME NULL,
            PRIMARY KEY (`id`),
            KEY `idx_estado_accion` (`estado`, `accion`),
            KEY `idx_filename` (`filename`),
            KEY `idx_created_at` (`created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";
    if (!mysqli_query($conexion, $sql)) {
        throw new Exception('No se pudo asegurar tabla importaciones_jobs: ' . mysqli_error($conexion));
    }
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Método no permitido']);
        exit;
    }

    $conexion = conectar_bd();
    mysqli_set_charset($conexion, 'utf8');
    importaciones_jobs_ensure_table($conexion);

    if (!mysqli_query($conexion, "TRUNCATE TABLE importaciones_jobs")) {
        throw new Exception('No se pudo vaciar importaciones_jobs: ' . mysqli_error($conexion));
    }

    mysqli_close($conexion);

    echo json_encode([
        'success' => true,
        'message' => 'Historial vaciado correctamente.'
    ]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

?>
