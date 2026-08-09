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
    $accion = isset($_GET['accion']) ? (string)$_GET['accion'] : '';
    $estado = isset($_GET['estado']) ? (string)$_GET['estado'] : '';
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
    if ($limit <= 0) $limit = 50;
    if ($limit > 200) $limit = 200;

    $conexion = conectar_bd();
    mysqli_set_charset($conexion, 'utf8');
    importaciones_jobs_ensure_table($conexion);

    $where = [];
    $params = [];
    $types = '';

    $allowedAccion = ['upload', 'execute_sql', 'import_data'];
    if ($accion !== '' && in_array($accion, $allowedAccion, true)) {
        $where[] = "accion = ?";
        $types .= 's';
        $params[] = $accion;
    }

    $allowedEstado = ['pending', 'running', 'success', 'error'];
    if ($estado !== '' && in_array($estado, $allowedEstado, true)) {
        $where[] = "estado = ?";
        $types .= 's';
        $params[] = $estado;
    }

    $sql = "SELECT id, filename, file_sha256, accion, estado, usuario_id, message, created_at, started_at, finished_at
            FROM importaciones_jobs";
    if (count($where) > 0) {
        $sql .= " WHERE " . implode(" AND ", $where);
    }
    $sql .= " ORDER BY id DESC LIMIT " . (int)$limit;

    $stmt = mysqli_prepare($conexion, $sql);
    if (!$stmt) {
        throw new Exception('Error preparando consulta: ' . mysqli_error($conexion));
    }
    if ($types !== '') {
        $bind = [];
        $bind[] = $types;
        for ($i = 0; $i < count($params); $i++) {
            $bind[] = &$params[$i];
        }
        call_user_func_array('mysqli_stmt_bind_param', array_merge([$stmt], $bind));
    }
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);

    $rows = [];
    while ($r = mysqli_fetch_assoc($res)) {
        $rows[] = $r;
    }

    mysqli_stmt_close($stmt);
    mysqli_close($conexion);

    echo json_encode(['success' => true, 'rows' => $rows]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

?>
