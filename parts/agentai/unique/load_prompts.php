<?php
require_once __DIR__ . '/../../../include/session.php';
require_once __DIR__ . '/../../../include/functions.php';

header('Content-Type: application/json; charset=utf-8');

$grupo = isset($_GET['grupo']) ? trim((string) $_GET['grupo']) : '';
$grupo = preg_replace('/[^a-z0-9_]/i', '', $grupo);
if ($grupo === '') {
    echo json_encode(array('success' => false, 'message' => 'Grupo no indicado'));
    exit;
}

$conexion = conectar_bd();
if (!$conexion) {
    echo json_encode(array('success' => false, 'message' => 'Sin conexión BD'));
    exit;
}

require_once __DIR__ . '/../ia_agent_config.php';
ia_agent_ensure_columna_disparadores($conexion);

$tieneDisp = false;
$chk = @mysqli_query($conexion, "SHOW COLUMNS FROM ia_agent_prompts LIKE 'disparadores'");
if ($chk && mysqli_num_rows($chk) > 0) {
    $tieneDisp = true;
}
if ($chk) {
    mysqli_free_result($chk);
}

$cols = $tieneDisp
    ? 'p.id_prompt, p.id_grupo, p.codigo, p.titulo, p.contenido, p.disparadores, p.orden, p.activo, p.fecha_actualizacion, p.actualizado_por'
    : 'p.id_prompt, p.id_grupo, p.codigo, p.titulo, p.contenido, p.orden, p.activo, p.fecha_actualizacion, p.actualizado_por';

$stmt = mysqli_prepare(
    $conexion,
    "SELECT {$cols}
     FROM ia_agent_prompts p
     INNER JOIN ia_agent_grupos g ON g.id_grupo = p.id_grupo
     WHERE g.codigo = ?
     ORDER BY p.orden ASC, p.id_prompt ASC"
);
if (!$stmt) {
    echo json_encode(array('success' => false, 'message' => 'Error SQL: ' . mysqli_error($conexion)));
    mysqli_close($conexion);
    exit;
}

mysqli_stmt_bind_param($stmt, 's', $grupo);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$prompts = array();
if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        $prompts[] = $row;
    }
}
mysqli_stmt_close($stmt);
mysqli_close($conexion);

echo json_encode(array(
    'success' => true,
    'grupo'   => $grupo,
    'prompts' => $prompts,
), JSON_UNESCAPED_UNICODE);
