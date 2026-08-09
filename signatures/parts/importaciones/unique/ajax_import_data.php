<?php
require_once '../../../include/session.php';
require_once '../../../include/functions.php';
require_once './ajax_ftp.php';

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

function importaciones_jobs_create_running_import($conexion, $filename, $sha256, $usuario_id, $meta) {
    importaciones_jobs_ensure_table($conexion);
    $metaJson = json_encode($meta, JSON_UNESCAPED_UNICODE);
    $stmt = mysqli_prepare($conexion, "INSERT INTO importaciones_jobs (filename, file_sha256, accion, estado, usuario_id, meta_json, started_at) VALUES (?, ?, 'import_data', 'running', ?, ?, NOW())");
    if (!$stmt) throw new Exception('Error creando job import_data: ' . mysqli_error($conexion));
    mysqli_stmt_bind_param($stmt, "ssss", $filename, $sha256, $usuario_id, $metaJson);
    if (!mysqli_stmt_execute($stmt)) {
        $err = mysqli_stmt_error($stmt);
        mysqli_stmt_close($stmt);
        throw new Exception('Error creando job import_data: ' . $err);
    }
    $id = mysqli_insert_id($conexion);
    mysqli_stmt_close($stmt);
    return $id;
}

function importaciones_jobs_finish($conexion, $job_id, $estado, $message, $meta) {
    importaciones_jobs_ensure_table($conexion);
    $metaJson = json_encode($meta, JSON_UNESCAPED_UNICODE);
    $stmt = mysqli_prepare($conexion, "UPDATE importaciones_jobs SET estado = ?, message = ?, meta_json = ?, finished_at = NOW() WHERE id = ?");
    if (!$stmt) throw new Exception('Error actualizando job import_data: ' . mysqli_error($conexion));
    mysqli_stmt_bind_param($stmt, "sssi", $estado, $message, $metaJson, $job_id);
    if (!mysqli_stmt_execute($stmt)) {
        $err = mysqli_stmt_error($stmt);
        mysqli_stmt_close($stmt);
        throw new Exception('Error actualizando job import_data: ' . $err);
    }
    mysqli_stmt_close($stmt);
}

function importaciones_get_unique_columns($conexion, $tableName) {
    $cols = [];
    $sql = "SHOW INDEX FROM `" . $tableName . "`";
    $res = mysqli_query($conexion, $sql);
    if (!$res) {
        throw new Exception('No se pudo leer índices de la tabla destino: ' . mysqli_error($conexion));
    }

    while ($row = mysqli_fetch_assoc($res)) {
        $keyName = isset($row['Key_name']) ? (string)$row['Key_name'] : '';
        $nonUnique = isset($row['Non_unique']) ? (int)$row['Non_unique'] : 1;
        $colName = isset($row['Column_name']) ? (string)$row['Column_name'] : '';
        if ($colName === '') continue;
        if ($keyName === 'PRIMARY' || $nonUnique === 0) {
            $cols[$colName] = true;
        }
    }
    mysqli_free_result($res);

    return array_keys($cols);
}

function importaciones_parse_insert_rows($sql) {
    // Muy básico: extrae INSERT INTO ... (cols) VALUES (...),(...);
    // Devuelve array de ['columns'=>[], 'rows'=>[[]]]
    $out = [];

    $pattern = '/INSERT\s+INTO\s+`?([A-Za-z0-9_]+)`?\s*\(([^)]+)\)\s*VALUES\s*(.+?);/is';
    if (!preg_match_all($pattern, $sql, $matches, PREG_SET_ORDER)) {
        return $out;
    }

    foreach ($matches as $m) {
        $colsRaw = $m[2];
        $valuesRaw = $m[3];
        $cols = array_map(function ($c) {
            $c = trim($c);
            $c = trim($c, "` \t\n\r\0\x0B");
            return $c;
        }, explode(',', $colsRaw));

        // separar grupos (...) del VALUES, respetando comillas simples
        $rows = [];
        $len = strlen($valuesRaw);
        $i = 0;
        while ($i < $len) {
            while ($i < $len && $valuesRaw[$i] !== '(') $i++;
            if ($i >= $len) break;
            $i++; // skip '('
            $inStr = false;
            $escape = false;
            $buf = '';
            $row = [];
            while ($i < $len) {
                $ch = $valuesRaw[$i];
                if ($inStr) {
                    if ($escape) {
                        $buf .= $ch;
                        $escape = false;
                    } else if ($ch === "\\\\") {
                        $buf .= $ch;
                        $escape = true;
                    } else if ($ch === "'") {
                        $buf .= $ch;
                        $inStr = false;
                    } else {
                        $buf .= $ch;
                    }
                } else {
                    if ($ch === "'") {
                        $buf .= $ch;
                        $inStr = true;
                    } else if ($ch === ',') {
                        $row[] = trim($buf);
                        $buf = '';
                    } else if ($ch === ')') {
                        $row[] = trim($buf);
                        $buf = '';
                        break;
                    } else {
                        $buf .= $ch;
                    }
                }
                $i++;
            }
            $rows[] = $row;
            while ($i < $len && $valuesRaw[$i] !== '(') $i++;
        }

        $out[] = ['columns' => $cols, 'rows' => $rows];
    }

    return $out;
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Método no permitido']);
        exit;
    }

    $filename = isset($_POST['filename']) ? importaciones_sanitize_sql_filename($_POST['filename']) : '';
    $destTable = isset($_POST['dest_table']) ? preg_replace('/[^A-Za-z0-9_]/', '', (string)$_POST['dest_table']) : '';
    $importMode = isset($_POST['import_mode']) ? (string)$_POST['import_mode'] : 'insert';
    $mapJson = isset($_POST['column_map']) ? (string)$_POST['column_map'] : '{}';

    if ($filename === '' || $destTable === '') {
        throw new Exception('Faltan parámetros.');
    }
    if (!in_array($importMode, ['insert', 'truncate_insert', 'upsert'], true)) {
        throw new Exception('Modo de importación inválido.');
    }

    $columnMap = json_decode($mapJson, true);
    if (!is_array($columnMap)) $columnMap = [];

    // Descargar SQL desde FTP
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
        throw new Exception('SQL vacío.');
    }

    $sha256 = hash('sha256', (string)$sql);

    $inserts = importaciones_parse_insert_rows($sql);
    if (count($inserts) === 0) {
        throw new Exception('No se encontraron INSERTs en el fichero.');
    }

    $conexion = conectar_bd();
    mysqli_set_charset($conexion, 'utf8');

    $usuario_id_local = isset($GLOBALS['usuario_id']) ? (string)$GLOBALS['usuario_id'] : '';
    $job_id = importaciones_jobs_create_running_import($conexion, $filename, $sha256, $usuario_id_local, [
        'dest_table' => $destTable,
        'column_map' => $columnMap,
        'import_mode' => $importMode
    ]);

    if ($importMode === 'truncate_insert') {
        if (!mysqli_query($conexion, "TRUNCATE TABLE `" . $destTable . "`")) {
            importaciones_jobs_finish($conexion, $job_id, 'error', 'Error vaciando tabla destino: ' . mysqli_error($conexion), [
                'inserted' => 0,
                'dest_table' => $destTable,
                'import_mode' => $importMode
            ]);
            throw new Exception('Error vaciando tabla destino: ' . mysqli_error($conexion));
        }
    }

    $uniqueCols = [];
    if ($importMode === 'upsert') {
        $uniqueCols = importaciones_get_unique_columns($conexion, $destTable);
        if (count($uniqueCols) === 0) {
            importaciones_jobs_finish($conexion, $job_id, 'error', 'La tabla destino no tiene clave primaria ni índice único para upsert.', [
                'inserted' => 0,
                'dest_table' => $destTable,
                'import_mode' => $importMode
            ]);
            throw new Exception('La tabla destino no tiene clave primaria ni índice único para upsert.');
        }
    }

    $inserted = 0;
    foreach ($inserts as $block) {
        $srcCols = $block['columns'];
        foreach ($block['rows'] as $rowValues) {
            if (count($rowValues) !== count($srcCols)) {
                continue;
            }

            $destCols = [];
            $params = [];
            $placeholders = [];
            $types = '';

            for ($i = 0; $i < count($srcCols); $i++) {
                $src = $srcCols[$i];
                $dst = isset($columnMap[$src]) ? (string)$columnMap[$src] : $src;
                $dst = preg_replace('/[^A-Za-z0-9_]/', '', $dst);
                if ($dst === '') continue;

                $rawVal = $rowValues[$i];
                // convertir NULL literal
                if (strcasecmp($rawVal, 'NULL') === 0) {
                    $val = null;
                } else {
                    // si viene con comillas simples, quitar comillas
                    $val = $rawVal;
                    $val = trim($val);
                    if (strlen($val) >= 2 && $val[0] === "'" && substr($val, -1) === "'") {
                        $val = substr($val, 1, -1);
                        $val = str_replace("\\'", "'", $val);
                        $val = str_replace('\\\\', '\\', $val);
                    }
                }

                $destCols[] = $dst;
                $placeholders[] = '?';
                $types .= 's';
                $params[] = ($val === null) ? null : (string)$val;
            }

            if (count($destCols) === 0) continue;

            $sqlIns = "INSERT INTO `" . $destTable . "` (`" . implode('`,`', $destCols) . "`) VALUES (" . implode(',', $placeholders) . ")";
            if ($importMode === 'upsert') {
                $updates = [];
                foreach ($destCols as $dc) {
                    if (in_array($dc, $uniqueCols, true)) {
                        continue;
                    }
                    $updates[] = "`" . $dc . "` = VALUES(`" . $dc . "`)";
                }
                if (count($updates) === 0) {
                    throw new Exception('No hay columnas actualizables para upsert tras excluir claves únicas/primarias.');
                }
                $sqlIns .= " ON DUPLICATE KEY UPDATE " . implode(', ', $updates);
            }
            $stmt = mysqli_prepare($conexion, $sqlIns);
            if (!$stmt) {
                throw new Exception('Error preparando INSERT en destino: ' . mysqli_error($conexion));
            }

            // bind dinámico
            $bind = [];
            $bind[] = $types;
            for ($b = 0; $b < count($params); $b++) {
                $bind[] = &$params[$b];
            }
            call_user_func_array('mysqli_stmt_bind_param', array_merge([$stmt], $bind));

            if (!mysqli_stmt_execute($stmt)) {
                $err = mysqli_stmt_error($stmt);
                mysqli_stmt_close($stmt);
                try {
                    importaciones_jobs_finish($conexion, $job_id, 'error', 'Error importando datos: ' . $err, ['inserted' => $inserted, 'dest_table' => $destTable, 'import_mode' => $importMode]);
                } catch (Exception $ignored) {}
                throw new Exception('Error importando datos: ' . $err);
            }
            mysqli_stmt_close($stmt);
            $inserted++;
        }
    }

    importaciones_jobs_finish($conexion, $job_id, 'success', 'OK. Filas procesadas: ' . $inserted, ['inserted' => $inserted, 'dest_table' => $destTable, 'import_mode' => $importMode]);
    mysqli_close($conexion);

    echo json_encode(['success' => true, 'message' => 'Datos importados (' . $importMode . '). Filas procesadas: ' . $inserted]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

?>

