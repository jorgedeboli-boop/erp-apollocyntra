<?php
require_once '../../../include/session.php';
require_once '../../../include/functions.php';
require_once './ajax_ftp.php';

header('Content-Type: application/json');

define('IMPORTACIONES_EXECUTE_CHUNK_SIZE', 25);

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

function importaciones_jobs_create_running($conexion, $filename, $sha256, $usuario_id) {
    importaciones_jobs_ensure_table($conexion);
    $stmt = mysqli_prepare($conexion, "INSERT INTO importaciones_jobs (filename, file_sha256, accion, estado, usuario_id, started_at) VALUES (?, ?, 'execute_sql', 'running', ?, NOW())");
    if (!$stmt) throw new Exception('Error creando job: ' . mysqli_error($conexion));
    mysqli_stmt_bind_param($stmt, "sss", $filename, $sha256, $usuario_id);
    if (!mysqli_stmt_execute($stmt)) {
        $err = mysqli_stmt_error($stmt);
        mysqli_stmt_close($stmt);
        throw new Exception('Error creando job: ' . $err);
    }
    $id = mysqli_insert_id($conexion);
    mysqli_stmt_close($stmt);
    return $id;
}

function importaciones_jobs_get($conexion, $job_id) {
    importaciones_jobs_ensure_table($conexion);
    $job_id = (int)$job_id;
    if ($job_id <= 0) return null;
    $stmt = mysqli_prepare($conexion, "SELECT id, filename, file_sha256, accion, estado, meta_json FROM importaciones_jobs WHERE id = ? LIMIT 1");
    if (!$stmt) {
        throw new Exception('Error leyendo job: ' . mysqli_error($conexion));
    }
    mysqli_stmt_bind_param($stmt, "i", $job_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($res);
    mysqli_stmt_close($stmt);
    return $row ?: null;
}

function importaciones_jobs_finish($conexion, $job_id, $estado, $message, $meta_json) {
    importaciones_jobs_ensure_table($conexion);
    $stmt = mysqli_prepare($conexion, "UPDATE importaciones_jobs SET estado = ?, message = ?, meta_json = ?, finished_at = NOW() WHERE id = ?");
    if (!$stmt) throw new Exception('Error actualizando job: ' . mysqli_error($conexion));
    mysqli_stmt_bind_param($stmt, "sssi", $estado, $message, $meta_json, $job_id);
    if (!mysqli_stmt_execute($stmt)) {
        $err = mysqli_stmt_error($stmt);
        mysqli_stmt_close($stmt);
        throw new Exception('Error actualizando job: ' . $err);
    }
    mysqli_stmt_close($stmt);
}

function importaciones_parse_sql_statements($sql) {
    $statements = [];
    $delimiter = ';';
    $buffer = '';
    $sawDelimiterDirective = false;

    $lines = preg_split("/\r\n|\n|\r/", (string)$sql);
    foreach ($lines as $line) {
        // quitar BOM y espacios
        $trim = trim($line);

        // Algunos dumps antiguos dejan una línea con '//' (o prefijan la siguiente sentencia con '// ').
        // Eso rompe si el parser no está en delimiter '//'. Lo tratamos como separador/ruido.
        if ($trim === '//') {
            continue;
        }
        if (strpos($trim, '//') === 0 && $trim !== '//') {
            // Si es '// DROP TRIGGER...' o similar, quitamos el prefijo.
            $line = ltrim(substr($line, strpos($line, '//') + 2));
            $trim = trim($line);
        }

        if ($trim === '' || strpos($trim, '-- ') === 0 || $trim === '--' || strpos($trim, '#') === 0) {
            // comentarios / líneas vacías
            continue;
        }

        if (stripos($trim, 'DELIMITER ') === 0) {
            $parts = preg_split('/\s+/', $trim);
            $delimiter = $parts[1] ?? ';';
            $sawDelimiterDirective = true;
            continue;
        }

        // Auto-detección para dumps que usan // pero no declaran DELIMITER.
        // Si vemos cualquier línea terminando en //, asumimos delimiter // para poder trocear bien.
        if (!$sawDelimiterDirective && $delimiter === ';') {
            if (substr($trim, -2) === '//') {
                $delimiter = '//';
            }
        }

        $buffer .= $line . "\n";

        // comprobar fin de sentencia con delimiter actual
        $bufTrimRight = rtrim($buffer);
        if ($delimiter !== '' && substr($bufTrimRight, -strlen($delimiter)) === $delimiter) {
            $stmt = substr($bufTrimRight, 0, -strlen($delimiter));
            $stmt = trim($stmt);
            if ($stmt !== '') {
                $statements[] = $stmt;
            }
            $buffer = '';
            continue;
        }

        // Caso especial: dumps sin DELIMITER que acaban en delimiter '//' pero dejan
        // sentencias normales (CREATE TABLE, DROP TRIGGER, etc.) terminadas en ';'.
        // Si ya estamos en delimiter '//' y el buffer termina en ';' y NO parece un cuerpo de trigger,
        // cortamos también por ';' para no pegar varias sentencias en una.
        if ($delimiter === '//' && substr($bufTrimRight, -1) === ';') {
            $bufTrimLeft = ltrim($buffer);
            $isTriggerBody = (stripos($bufTrimLeft, 'CREATE TRIGGER') === 0);
            if (!$isTriggerBody) {
                $stmt = substr($bufTrimRight, 0, -1);
                $stmt = trim($stmt);
                if ($stmt !== '') {
                    $statements[] = $stmt;
                }
                $buffer = '';
            }
        }
    }

    $rest = trim($buffer);
    if ($rest !== '') {
        $statements[] = $rest;
    }

    return $statements;
}

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

    $job_id_in = isset($_POST['job_id']) ? (int)$_POST['job_id'] : 0;
    $offset = isset($_POST['offset']) ? (int)$_POST['offset'] : 0;
    if ($offset < 0) $offset = 0;
    $execMode = isset($_POST['exec_mode']) ? (string)$_POST['exec_mode'] : 'all';
    if (!in_array($execMode, ['all', 'no_triggers', 'only_triggers'], true)) {
        throw new Exception('exec_mode inválido.');
    }

    // Descargar desde FTP a memoria
    $conn = importaciones_ftp_connect();
    $remotePath = '/migration/' . $filename;

    $temp = fopen('php://temp', 'w+');
    if (!$temp) {
        ftp_close($conn);
        throw new Exception('No se pudo crear buffer temporal.');
    }

    if (!@ftp_fget($conn, $temp, $remotePath, FTP_BINARY)) {
        fclose($temp);
        ftp_close($conn);
        throw new Exception('No se pudo descargar el fichero desde FTP.');
    }
    ftp_close($conn);

    rewind($temp);
    $sql = stream_get_contents($temp);
    fclose($temp);

    if (!$sql || trim($sql) === '') {
        throw new Exception('El fichero SQL está vacío.');
    }

    $sha256 = hash('sha256', (string)$sql);

    $stmts = importaciones_parse_sql_statements($sql);
    if (count($stmts) === 0) {
        throw new Exception('No se encontraron sentencias SQL ejecutables.');
    }

    $conexion = conectar_bd();
    mysqli_set_charset($conexion, 'utf8');

    $usuario_id_local = isset($GLOBALS['usuario_id']) ? (string)$GLOBALS['usuario_id'] : '';
    $job_id = 0;
    if ($job_id_in > 0) {
        $job = importaciones_jobs_get($conexion, $job_id_in);
        if (!$job) {
            throw new Exception('Job no encontrado.');
        }
        if (($job['accion'] ?? '') !== 'execute_sql') {
            throw new Exception('Job inválido.');
        }
        if (($job['filename'] ?? '') !== $filename) {
            throw new Exception('Job no coincide con filename.');
        }
        $job_id = $job_id_in;
    } else {
        $job_id = importaciones_jobs_create_running($conexion, $filename, $sha256, $usuario_id_local);
        $offset = 0;
        // guardar meta inicial
        try {
            importaciones_jobs_finish($conexion, $job_id, 'running', 'Iniciado', json_encode([
                'total_statements' => count($stmts),
                'offset' => 0
            ], JSON_UNESCAPED_UNICODE));
        } catch (Exception $ignored) {}
    }

    $total = count($stmts);
    $chunk = IMPORTACIONES_EXECUTE_CHUNK_SIZE;
    $end = min($total, $offset + $chunk);

    $ok = 0;
    for ($i = $offset; $i < $end; $i++) {
        $stmt = $stmts[$i];
        $stmtTrim = ltrim($stmt);
        $isTriggerStmt = (stripos($stmtTrim, 'CREATE TRIGGER') === 0) || (stripos($stmtTrim, 'DROP TRIGGER') === 0);

        if ($execMode === 'no_triggers' && $isTriggerStmt) {
            continue;
        }
        if ($execMode === 'only_triggers' && !$isTriggerStmt) {
            continue;
        }
        if (!mysqli_query($conexion, $stmt)) {
            $err = mysqli_error($conexion);
            try {
                importaciones_jobs_finish($conexion, $job_id, 'error', 'Error ejecutando SQL: ' . $err, json_encode([
                    'total_statements' => $total,
                    'offset' => $i,
                    'statements_ok' => ($offset + $ok)
                ], JSON_UNESCAPED_UNICODE));
            } catch (Exception $ignored) {}
            mysqli_close($conexion);
            throw new Exception('Error ejecutando SQL: ' . $err);
        }
        $ok++;
    }

    $newOffset = $end;
    $done = ($newOffset >= $total);

    if ($done) {
        importaciones_jobs_finish($conexion, $job_id, 'success', 'OK. Sentencias ejecutadas: ' . $total, json_encode([
            'total_statements' => $total,
            'offset' => $total,
            'statements_ok' => $total
        ], JSON_UNESCAPED_UNICODE));
    } else {
        // actualizar meta_json y mantener estado running
        try {
            importaciones_jobs_finish($conexion, $job_id, 'running', 'En progreso', json_encode([
                'total_statements' => $total,
                'offset' => $newOffset,
                'statements_ok' => $newOffset
            ], JSON_UNESCAPED_UNICODE));
        } catch (Exception $ignored) {}
    }
    mysqli_close($conexion);

    echo json_encode([
        'success' => true,
        'done' => $done,
        'job_id' => $job_id,
        'offset' => $newOffset,
        'total' => $total,
        'message' => $done
            ? ('Importación completada. Sentencias ejecutadas: ' . $total)
            : ('Ejecutadas ' . $newOffset . ' / ' . $total . ' sentencias…')
    ]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

?>

