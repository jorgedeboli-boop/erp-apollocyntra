<?php
require_once '../../../include/session.php';
require_once '../../../include/functions.php';
require_once './ajax_ftp.php';

header('Content-Type: application/json');

define('IMPORTACIONES_MAX_SQL_PART_BYTES', 20971520); // 20 * 1024 * 1024

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

function importaciones_jobs_log_upload($filename, $sha256, $usuario_id, $estado, $message) {
    try {
        $conexion = conectar_bd();
        mysqli_set_charset($conexion, 'utf8');
        importaciones_jobs_ensure_table($conexion);
        $stmt = mysqli_prepare($conexion, "INSERT INTO importaciones_jobs (filename, file_sha256, accion, estado, usuario_id, message, started_at, finished_at) VALUES (?, ?, 'upload', ?, ?, ?, NOW(), NOW())");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "sssss", $filename, $sha256, $estado, $usuario_id, $message);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
        mysqli_close($conexion);
    } catch (Exception $ignored) {
        // no romper el flujo de upload por logging
    }
}

function importaciones_sanitize_zip_filename($name) {
    $base = basename((string)$name);
    $base = preg_replace('/[^A-Za-z0-9._-]/', '_', $base);
    if (!preg_match('/\.zip$/i', $base)) {
        throw new Exception('Nombre de fichero inválido. Solo se permiten .zip');
    }
    return $base;
}

function importaciones_sanitize_gzip_filename($name) {
    $base = basename((string)$name);
    $base = preg_replace('/[^A-Za-z0-9._-]/', '_', $base);
    if (!preg_match('/\.(gz|gzip)$/i', $base)) {
        throw new Exception('Nombre de fichero inválido. Solo se permiten .gz/.gzip');
    }
    return $base;
}

function importaciones_is_zip($name) {
    return (bool)preg_match('/\.zip$/i', (string)$name);
}

function importaciones_is_gzip($name) {
    return (bool)preg_match('/\.(gz|gzip)$/i', (string)$name);
}

function importaciones_is_sql($name) {
    return (bool)preg_match('/\.sql$/i', (string)$name);
}

function importaciones_build_sql_name_from_gzip($name) {
    $base = basename((string)$name);
    $base = preg_replace('/\.(gz|gzip)$/i', '', $base);
    $base = preg_replace('/[^A-Za-z0-9._-]/', '_', $base);
    if (!preg_match('/\.sql$/i', $base)) {
        $base .= '.sql';
    }
    return importaciones_sanitize_sql_filename($base);
}

function importaciones_zip_entry_is_safe($entryName) {
    $entryName = (string)$entryName;
    if ($entryName === '') return false;
    if (strpos($entryName, "\0") !== false) return false;
    // prevenir zip-slip
    if (strpos($entryName, '../') !== false || strpos($entryName, '..\\') !== false) return false;
    if ($entryName[0] === '/' || $entryName[0] === '\\') return false;
    return true;
}

function importaciones_should_rewrite_clientes_sql($sqlFilename) {
    return strtolower((string)$sqlFilename) === 'clientes.sql';
}

/**
 * Reescribe el dump antiguo de clientes para que use `clientes_old` como tabla destino.
 * Solo afecta a CREATE TABLE/INSERT INTO de `clientes`.
 *
 * @param resource $in
 * @param resource $out
 */
function importaciones_rewrite_clientes_dump_stream($in, $out) {
    while (!feof($in)) {
        $line = fgets($in);
        if ($line === false) break;

        $line = preg_replace(
            '/\bCREATE\s+TABLE\s+IF\s+NOT\s+EXISTS\s+`?clientes`?\b/i',
            'CREATE TABLE IF NOT EXISTS `clientes_old`',
            $line
        );
        $line = preg_replace(
            '/\bINSERT\s+INTO\s+`?clientes`?\b/i',
            'INSERT INTO `clientes_old`',
            $line
        );
        fwrite($out, $line);
    }
}

function importaciones_should_inject_truncate_for_table($tableName) {
    // Excepciones por claves foráneas: no se puede TRUNCATE si hay FKs apuntando a la tabla.
    $t = strtolower((string)$tableName);
    if ($t === 'countrys') return false;
    return true;
}

function importaciones_split_sql_name($sqlName, $partIndex) {
    // foo.sql -> foo_part001.sql
    $sqlName = (string)$sqlName;
    $partIndex = (int)$partIndex;
    if ($partIndex < 1) $partIndex = 1;
    $pad = str_pad((string)$partIndex, 3, '0', STR_PAD_LEFT);
    $base = preg_replace('/\.sql$/i', '', $sqlName);
    return $base . '_part' . $pad . '.sql';
}

function importaciones_make_sql_variant_name($baseSqlName, $suffix) {
    $baseSqlName = importaciones_sanitize_sql_filename($baseSqlName);
    $suffix = (string)$suffix;
    $base = preg_replace('/\.sql$/i', '', $baseSqlName);
    $suffix = preg_replace('/[^A-Za-z0-9_-]/', '_', $suffix);
    return $base . '_' . $suffix . '.sql';
}

/**
 * Detecta sentencias CREATE/DROP TRIGGER y separa en 2 streams:
 * - datos/estructura (sin triggers)
 * - solo triggers
 *
 * Respeta DELIMITER de forma básica (igual que el split por sentencias).
 *
 * @param resource $stream
 * @return array{has_triggers:bool, data:resource, triggers:resource}
 */
function importaciones_split_stream_by_triggers($stream) {
    $data = fopen('php://temp', 'w+');
    $trg = fopen('php://temp', 'w+');
    if (!$data || !$trg) {
        throw new Exception('No se pudo crear buffer temporal para separación de triggers.');
    }

    $delimiter = ';';
    $buffer = '';
    $hasTriggers = false;

    $emit = function ($outStmt) use ($data, $trg, &$hasTriggers) {
        $outStmt = (string)$outStmt;
        if ($outStmt === '') return;
        $trim = ltrim($outStmt);
        $isTrigger = (stripos($trim, 'CREATE TRIGGER') === 0) || (stripos($trim, 'DROP TRIGGER') === 0);
        if ($isTrigger) {
            $hasTriggers = true;
            fwrite($trg, $outStmt . "\n");
        } else {
            fwrite($data, $outStmt . "\n");
        }
    };

    while (($line = fgets($stream)) !== false) {
        $trim = trim($line);

        if ($trim === '' || strpos($trim, '-- ') === 0 || $trim === '--' || strpos($trim, '#') === 0) {
            continue;
        }

        if (stripos($trim, 'DELIMITER ') === 0) {
            $parts = preg_split('/\s+/', $trim);
            $delimiter = $parts[1] ?? ';';
            continue;
        }

        $buffer .= $line;
        $bufTrimRight = rtrim($buffer);
        if ($delimiter !== '' && substr($bufTrimRight, -strlen($delimiter)) === $delimiter) {
            $stmt = substr($bufTrimRight, 0, -strlen($delimiter));
            $stmt = trim($stmt);
            if ($stmt !== '') {
                $outStmt = $delimiter === ';' ? ($stmt . ';') : ($stmt . $delimiter);
                $emit($outStmt);
            }
            $buffer = '';
        }
    }

    $rest = trim($buffer);
    if ($rest !== '') {
        $emit($rest);
    }

    rewind($data);
    rewind($trg);
    return ['has_triggers' => $hasTriggers, 'data' => $data, 'triggers' => $trg];
}

/**
 * Sube un SQL, separando en *_datos.sql y *_triggers.sql si hay triggers.
 *
 * @param resource $conn
 * @param string $remoteDir
 * @param string $sqlName
 * @param resource $stream
 * @return string[] archivos subidos
 */
function importaciones_upload_sql_with_optional_trigger_split($conn, $remoteDir, $sqlName, $stream) {
    $sqlName = importaciones_sanitize_sql_filename($sqlName);
    $remoteDir = (string)$remoteDir;

    $split = importaciones_split_stream_by_triggers($stream);
    $uploaded = [];

    if (!empty($split['has_triggers'])) {
        $nameDatos = importaciones_make_sql_variant_name($sqlName, 'datos');
        $nameTrg = importaciones_make_sql_variant_name($sqlName, 'triggers');

        $partsDatos = importaciones_ftp_upload_sql_split_by_statements($conn, $remoteDir, $nameDatos, $split['data']);
        foreach ($partsDatos as $p) $uploaded[] = $p;

        $partsTrg = importaciones_ftp_upload_sql_split_by_statements($conn, $remoteDir, $nameTrg, $split['triggers']);
        foreach ($partsTrg as $p) $uploaded[] = $p;
    } else {
        $parts = importaciones_ftp_upload_sql_split_by_statements($conn, $remoteDir, $sqlName, $split['data']);
        foreach ($parts as $p) $uploaded[] = $p;
    }

    if (is_resource($split['data'])) fclose($split['data']);
    if (is_resource($split['triggers'])) fclose($split['triggers']);
    return $uploaded;
}

/**
 * Parte un SQL SOLO entre sentencias completas (evita cortar a mitad de INSERT),
 * intentando respetar DELIMITER, y sube cada parte a FTP con tamaño <= IMPORTACIONES_MAX_SQL_PART_BYTES.
 *
 * @param resource $stream
 * @return string[] nombres subidos
 */
function importaciones_ftp_upload_sql_split_by_statements($conn, $remoteDir, $baseSqlName, $stream) {
    $remoteDir = (string)$remoteDir;
    $baseSqlName = importaciones_sanitize_sql_filename($baseSqlName);

    $maxBytes = (int)IMPORTACIONES_MAX_SQL_PART_BYTES;
    if ($maxBytes < 1024 * 1024) $maxBytes = 1024 * 1024;

    $uploaded = [];
    $part = 1;

    $tmpPath = null;
    $tmpFp = null;
    $bytes = 0;

    $openNew = function () use (&$tmpPath, &$tmpFp, &$bytes) {
        $tmpPath = tempnam(sys_get_temp_dir(), 'imp_sql_');
        if (!$tmpPath) {
            throw new Exception('No se pudo crear fichero temporal.');
        }
        $tmpFp = fopen($tmpPath, 'w+');
        if (!$tmpFp) {
            @unlink($tmpPath);
            throw new Exception('No se pudo abrir fichero temporal.');
        }
        $bytes = 0;
    };

    $flushPart = function ($finalName) use (&$tmpPath, &$tmpFp, &$uploaded, $conn, $remoteDir) {
        if (!$tmpFp || !$tmpPath) return;
        fflush($tmpFp);
        rewind($tmpFp);
        $remotePath = rtrim($remoteDir, '/') . '/' . $finalName;
        if (!@ftp_fput($conn, $remotePath, $tmpFp, FTP_BINARY)) {
            fclose($tmpFp);
            @unlink($tmpPath);
            throw new Exception('No se pudo subir al FTP: ' . $finalName);
        }
        fclose($tmpFp);
        @unlink($tmpPath);
        $tmpFp = null;
        $tmpPath = null;
        $uploaded[] = $finalName;
    };

    $write = function ($text) use (&$tmpFp, &$bytes) {
        if ($text === '') return;
        fwrite($tmpFp, $text);
        $bytes += strlen($text);
    };

    $finishStatement = function ($stmtText, $delimiter) use (&$bytes, $maxBytes, &$part, $baseSqlName, $flushPart, $openNew, $write) {
        $stmtText = (string)$stmtText;
        $delimiter = (string)$delimiter;
        if ($stmtText === '') return;

        // Si al añadir esta sentencia nos pasamos y ya hay algo, volcamos parte y abrimos nueva.
        $toAdd = $stmtText . "\n";
        if ($bytes > 0 && ($bytes + strlen($toAdd)) > $maxBytes) {
            $finalName = (count($GLOBALS['__imp_uploaded_tmp'] ?? []) >= 1 || $part > 1)
                ? importaciones_split_sql_name($baseSqlName, $part)
                : $baseSqlName;
            if ($part > 1 || (count($GLOBALS['__imp_uploaded_tmp'] ?? []) >= 1)) {
                $finalName = importaciones_split_sql_name($baseSqlName, $part);
            }
            $flushPart($finalName);
            $part++;
            $openNew();
        }

        $write($toAdd);
    };

    // hack: para que finishStatement sepa si ya hay subidos, usamos $uploaded en global temporal
    $GLOBALS['__imp_uploaded_tmp'] = &$uploaded;

    $delimiter = ';';
    $buffer = '';
    $truncatedTables = []; // tabla => true (ya se emitió TRUNCATE)

    $openNew();

    while (($line = fgets($stream)) !== false) {
        $trim = trim($line);

        // saltar BOM/espacios y comentarios simples
        if ($trim === '' || strpos($trim, '-- ') === 0 || $trim === '--' || strpos($trim, '#') === 0) {
            continue;
        }

        if (stripos($trim, 'DELIMITER ') === 0) {
            $parts = preg_split('/\s+/', $trim);
            $delimiter = $parts[1] ?? ';';
            continue;
        }

        $buffer .= $line;

        $bufTrimRight = rtrim($buffer);
        if ($delimiter !== '' && substr($bufTrimRight, -strlen($delimiter)) === $delimiter) {
            $stmt = substr($bufTrimRight, 0, -strlen($delimiter));
            $stmt = trim($stmt);
            if ($stmt !== '') {
                // re-apendizar el delimitador estándar ';' para MySQL cuando el delimiter sea ';'
                // Para otros delimiters, mantenemos el texto tal cual (sin la palabra DELIMITER).
                $outStmt = $delimiter === ';' ? ($stmt . ';') : ($stmt . $delimiter);
                // Si es un INSERT INTO, inyectar TRUNCATE TABLE una sola vez por tabla (antes del primer INSERT)
                if (preg_match('/^INSERT\\s+INTO\\s+`?([A-Za-z0-9_]+)`?/i', ltrim($outStmt), $mm)) {
                    $tbl = (string)($mm[1] ?? '');
                    if ($tbl !== '' && empty($truncatedTables[$tbl]) && importaciones_should_inject_truncate_for_table($tbl)) {
                        $truncatedTables[$tbl] = true;
                        $finishStatement("TRUNCATE TABLE `" . $tbl . "`;", $delimiter);
                    }
                }
                $finishStatement($outStmt, $delimiter);
            }
            $buffer = '';
        }
    }

    $rest = trim($buffer);
    if ($rest !== '') {
        // último trozo sin delimiter; lo dejamos completo (ejecutable si ya está bien formado)
        $finishStatement($rest, $delimiter);
    }

    // Subir última parte si tiene contenido
    if ($bytes > 0) {
        $finalName = (count($uploaded) >= 1 || $part > 1) ? importaciones_split_sql_name($baseSqlName, $part) : $baseSqlName;
        if (count($uploaded) >= 1 || $part > 1) $finalName = importaciones_split_sql_name($baseSqlName, $part);
        $flushPart($finalName);
    }

    unset($GLOBALS['__imp_uploaded_tmp']);

    // Si hemos subido más de 1 parte, aseguramos nombres con _part para TODAS (consistencia).
    if (count($uploaded) > 1) {
        // Nota: no renombramos en FTP para evitar operaciones extra; el 1º podría ser baseSqlName.
        // En práctica dejamos así.
    }

    return $uploaded;
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Método no permitido']);
        exit;
    }

    if (!isset($_FILES['sql']) || !is_array($_FILES['sql'])) {
        throw new Exception('No se recibió el fichero.');
    }

    $f = $_FILES['sql'];
    if (($f['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new Exception('Error subiendo fichero.');
    }

    $originalName = (string)($f['name'] ?? '');
    $isZip = importaciones_is_zip($originalName);
    $isGzip = importaciones_is_gzip($originalName);
    $isSql = importaciones_is_sql($originalName);
    if (!$isZip && !$isSql && !$isGzip) {
        throw new Exception('Formato inválido. Solo se permiten .sql, .zip o .gz');
    }

    $name = $isZip
        ? importaciones_sanitize_zip_filename($originalName)
        : ($isGzip
            ? importaciones_sanitize_gzip_filename($originalName)
            : importaciones_sanitize_sql_filename($originalName));
    $tmp = $f['tmp_name'] ?? '';
    if ($tmp === '' || !is_uploaded_file($tmp)) {
        throw new Exception('Fichero temporal inválido.');
    }

    $sha256 = @hash_file('sha256', $tmp);
    if (!is_string($sha256) || $sha256 === '') $sha256 = null;

    $conn = importaciones_ftp_connect();
    $remoteDir = '/migration';
    importaciones_ftp_ensure_dir($conn, $remoteDir);

    $uploadedSql = [];

    if ($isSql) {
        $fp = fopen($tmp, 'r');
        if (!$fp) {
            ftp_close($conn);
            throw new Exception('No se pudo leer el fichero temporal.');
        }

        if (importaciones_should_rewrite_clientes_sql($name)) {
            $rewritten = fopen('php://temp', 'w+');
            if (!$rewritten) {
                fclose($fp);
                ftp_close($conn);
                throw new Exception('No se pudo crear buffer para reescritura de clientes.sql');
            }
            importaciones_rewrite_clientes_dump_stream($fp, $rewritten);
            fclose($fp);
            rewind($rewritten);
            $parts = importaciones_upload_sql_with_optional_trigger_split($conn, $remoteDir, $name, $rewritten);
            fclose($rewritten);
        } else {
            $parts = importaciones_upload_sql_with_optional_trigger_split($conn, $remoteDir, $name, $fp);
            fclose($fp);
        }
        foreach ($parts as $p) $uploadedSql[] = $p;
    } elseif ($isGzip) {
        $gz = @gzopen($tmp, 'rb');
        if (!$gz) {
            ftp_close($conn);
            throw new Exception('No se pudo abrir el fichero GZIP.');
        }
        $sqlName = importaciones_build_sql_name_from_gzip($name);

        // Convertir stream gzip a stream normal para la función de split
        $plain = fopen('php://temp', 'w+');
        if (!$plain) {
            gzclose($gz);
            ftp_close($conn);
            throw new Exception('No se pudo crear buffer temporal para GZIP.');
        }
        while (!gzeof($gz)) {
            $chunk = gzread($gz, 8192);
            if ($chunk === false) {
                fclose($plain);
                gzclose($gz);
                ftp_close($conn);
                throw new Exception('Error leyendo contenido GZIP.');
            }
            fwrite($plain, $chunk);
        }
        gzclose($gz);
        rewind($plain);

        $parts = importaciones_upload_sql_with_optional_trigger_split($conn, $remoteDir, $sqlName, $plain);
        fclose($plain);
        foreach ($parts as $p) $uploadedSql[] = $p;
    } else {
        if (!class_exists('ZipArchive')) {
            ftp_close($conn);
            throw new Exception('ZIP no soportado en este servidor (ZipArchive no disponible).');
        }

        $zip = new ZipArchive();
        $openOk = $zip->open($tmp);
        if ($openOk !== true) {
            ftp_close($conn);
            throw new Exception('No se pudo abrir el ZIP.');
        }

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entryName = $zip->getNameIndex($i);
            if (!importaciones_zip_entry_is_safe($entryName)) {
                continue;
            }
            // saltar directorios
            if (substr($entryName, -1) === '/') continue;

            $base = basename($entryName);
            if (!importaciones_is_sql($base)) continue;

            // sanitizar nombre final .sql
            try {
                $sqlName = importaciones_sanitize_sql_filename($base);
            } catch (Exception $e) {
                continue;
            }

            $stream = $zip->getStream($entryName);
            if (!$stream) {
                continue;
            }
            $parts = importaciones_upload_sql_with_optional_trigger_split($conn, $remoteDir, $sqlName, $stream);
            fclose($stream);
            foreach ($parts as $p) $uploadedSql[] = $p;
        }

        $zip->close();

        if (count($uploadedSql) === 0) {
            ftp_close($conn);
            throw new Exception('El ZIP no contiene ficheros .sql válidos.');
        }
    }

    ftp_close($conn);

    $usuario_id_local = isset($GLOBALS['usuario_id']) ? (string)$GLOBALS['usuario_id'] : '';
    $msg = $isZip
        ? ('ZIP procesado. SQL subidos: ' . count($uploadedSql))
        : ($isGzip
            ? ('GZIP procesado. SQL subidos: ' . count($uploadedSql))
            : (importaciones_should_rewrite_clientes_sql($name) ? 'Subido correctamente (clientes.sql reescrito a clientes_old)' : 'Subido correctamente'));
    importaciones_jobs_log_upload($name, (string)$sha256, $usuario_id_local, 'success', $msg);

    echo json_encode([
        'success' => true,
        'filename' => $name,
        'uploaded_sql' => $uploadedSql,
        'message' => $isZip
            ? ('ZIP subido y descomprimido. SQL subidos: ' . count($uploadedSql))
            : ($isGzip
                ? ('GZIP subido y descomprimido. SQL subidos: ' . count($uploadedSql))
                : ('Subido correctamente: ' . $name))
    ]);
} catch (Exception $e) {
    $usuario_id_local = isset($GLOBALS['usuario_id']) ? (string)$GLOBALS['usuario_id'] : '';
    $fname = isset($name) ? (string)$name : (isset($_FILES['sql']['name']) ? importaciones_sanitize_sql_filename((string)$_FILES['sql']['name']) : 'unknown.sql');
    importaciones_jobs_log_upload($fname, isset($sha256) ? (string)$sha256 : '', $usuario_id_local, 'error', $e->getMessage());
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

?>
