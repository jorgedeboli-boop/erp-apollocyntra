<?php
require_once __DIR__ . '/../../../include/session.php';
require_once __DIR__ . '/../../../include/functions.php';
require_once __DIR__ . '/../ia_agent_config.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(array('success' => false, 'message' => 'Método no permitido'));
    exit;
}

$id_prompt = isset($_POST['id_prompt']) ? (int) $_POST['id_prompt'] : 0;
$grupo_codigo = isset($_POST['grupo_codigo']) ? preg_replace('/[^a-z0-9_]/i', '', (string) $_POST['grupo_codigo']) : '';
$codigo = isset($_POST['codigo']) ? strtolower(preg_replace('/[^a-z0-9_]/i', '', (string) $_POST['codigo'])) : '';
$titulo = isset($_POST['titulo']) ? trim((string) $_POST['titulo']) : '';
$contenido = isset($_POST['contenido']) ? (string) $_POST['contenido'] : '';
$disparadores = isset($_POST['disparadores']) ? trim((string) $_POST['disparadores']) : '';
$orden = isset($_POST['orden']) ? (int) $_POST['orden'] : 0;
$activo = (isset($_POST['activo']) && $_POST['activo'] === 'false') ? 'false' : 'true';
$usuario_id = isset($_SESSION['usuario_id']) ? (int) $_SESSION['usuario_id'] : 0;

if ($grupo_codigo === '' || $codigo === '' || $titulo === '') {
    echo json_encode(array('success' => false, 'message' => 'Grupo, código y título son obligatorios'));
    exit;
}

if ($grupo_codigo !== 'flujos') {
    $disparadores = '';
}

$conexion = conectar_bd();
if (!$conexion) {
    echo json_encode(array('success' => false, 'message' => 'Sin conexión BD'));
    exit;
}

ia_agent_ensure_columna_disparadores($conexion);

$stmtG = mysqli_prepare($conexion, 'SELECT id_grupo FROM ia_agent_grupos WHERE codigo = ? LIMIT 1');
if (!$stmtG) {
    echo json_encode(array('success' => false, 'message' => mysqli_error($conexion)));
    mysqli_close($conexion);
    exit;
}
mysqli_stmt_bind_param($stmtG, 's', $grupo_codigo);
mysqli_stmt_execute($stmtG);
$resG = mysqli_stmt_get_result($stmtG);
$rowG = $resG ? mysqli_fetch_assoc($resG) : null;
mysqli_stmt_close($stmtG);

if (!$rowG) {
    mysqli_close($conexion);
    echo json_encode(array('success' => false, 'message' => 'Grupo no encontrado'));
    exit;
}
$id_grupo = (int) $rowG['id_grupo'];
$ahora = date('Y-m-d H:i:s');

$tieneDisp = false;
$chk = @mysqli_query($conexion, "SHOW COLUMNS FROM ia_agent_prompts LIKE 'disparadores'");
if ($chk && mysqli_num_rows($chk) > 0) {
    $tieneDisp = true;
}
if ($chk) {
    mysqli_free_result($chk);
}

if ($id_prompt > 0) {
    if ($tieneDisp) {
        $stmt = mysqli_prepare(
            $conexion,
            "UPDATE ia_agent_prompts
             SET titulo = ?, contenido = ?, disparadores = ?, orden = ?, activo = ?, fecha_actualizacion = ?, actualizado_por = ?
             WHERE id_prompt = ? AND id_grupo = ?"
        );
        if (!$stmt) {
            echo json_encode(array('success' => false, 'message' => mysqli_error($conexion)));
            mysqli_close($conexion);
            exit;
        }
        mysqli_stmt_bind_param(
            $stmt,
            'sssissiii',
            $titulo,
            $contenido,
            $disparadores,
            $orden,
            $activo,
            $ahora,
            $usuario_id,
            $id_prompt,
            $id_grupo
        );
    } else {
        $stmt = mysqli_prepare(
            $conexion,
            "UPDATE ia_agent_prompts
             SET titulo = ?, contenido = ?, orden = ?, activo = ?, fecha_actualizacion = ?, actualizado_por = ?
             WHERE id_prompt = ? AND id_grupo = ?"
        );
        if (!$stmt) {
            echo json_encode(array('success' => false, 'message' => mysqli_error($conexion)));
            mysqli_close($conexion);
            exit;
        }
        mysqli_stmt_bind_param($stmt, 'ssissiii', $titulo, $contenido, $orden, $activo, $ahora, $usuario_id, $id_prompt, $id_grupo);
    }
    $ok = mysqli_stmt_execute($stmt);
    $err = mysqli_stmt_error($stmt);
    mysqli_stmt_close($stmt);
    mysqli_close($conexion);
    if (!$ok) {
        echo json_encode(array('success' => false, 'message' => $err ?: 'Error al actualizar'));
        exit;
    }
    echo json_encode(array('success' => true, 'message' => 'Prompt actualizado', 'id_prompt' => $id_prompt), JSON_UNESCAPED_UNICODE);
    exit;
}

if ($tieneDisp) {
    $stmt = mysqli_prepare(
        $conexion,
        "INSERT INTO ia_agent_prompts
            (id_grupo, codigo, titulo, contenido, disparadores, orden, activo, fecha_actualizacion, actualizado_por)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );
    if (!$stmt) {
        echo json_encode(array('success' => false, 'message' => mysqli_error($conexion)));
        mysqli_close($conexion);
        exit;
    }
    mysqli_stmt_bind_param(
        $stmt,
        'issssissi',
        $id_grupo,
        $codigo,
        $titulo,
        $contenido,
        $disparadores,
        $orden,
        $activo,
        $ahora,
        $usuario_id
    );
} else {
    $stmt = mysqli_prepare(
        $conexion,
        "INSERT INTO ia_agent_prompts
            (id_grupo, codigo, titulo, contenido, orden, activo, fecha_actualizacion, actualizado_por)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
    );
    if (!$stmt) {
        echo json_encode(array('success' => false, 'message' => mysqli_error($conexion)));
        mysqli_close($conexion);
        exit;
    }
    mysqli_stmt_bind_param($stmt, 'isssissi', $id_grupo, $codigo, $titulo, $contenido, $orden, $activo, $ahora, $usuario_id);
}

$ok = mysqli_stmt_execute($stmt);
$err = mysqli_stmt_error($stmt);
$newId = (int) mysqli_insert_id($conexion);
mysqli_stmt_close($stmt);
mysqli_close($conexion);

if (!$ok) {
    $msg = $err ?: 'Error al insertar';
    if (stripos($msg, 'Duplicate') !== false) {
        $msg = 'Ya existe un prompt con ese código en el grupo';
    }
    echo json_encode(array('success' => false, 'message' => $msg));
    exit;
}

echo json_encode(array('success' => true, 'message' => 'Prompt creado', 'id_prompt' => $newId), JSON_UNESCAPED_UNICODE);
