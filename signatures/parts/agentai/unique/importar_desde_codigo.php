<?php
/**
 * Importa/actualiza prompts por defecto desde el código actual del chat IA.
 */
define('IA_AGENT_SOLO_DEFINICIONES', true);

require_once __DIR__ . '/../../../include/session.php';
require_once __DIR__ . '/../../../include/functions.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(array('success' => false, 'message' => 'Método no permitido'));
    exit;
}

// Carga constantes/funciones del chat sin ejecutar el router
require_once __DIR__ . '/../ajax_ia_chat.php';

$usuario_id = isset($_SESSION['usuario_id']) ? (int) $_SESSION['usuario_id'] : 0;
$ahora = date('Y-m-d H:i:s');

$grupos = array(
    array(
        'codigo' => 'identidad',
        'nombre' => 'Identidad',
        'descripcion' => 'Quién es el asistente y tono general',
        'orden' => 10,
    ),
    array(
        'codigo' => 'esquema',
        'nombre' => 'Esquema BD',
        'descripcion' => 'Tablas, relaciones y contexto de negocio para SQL',
        'orden' => 20,
    ),
    array(
        'codigo' => 'reglas_sql',
        'nombre' => 'Reglas SQL',
        'descripcion' => 'Instrucciones al generar SELECT',
        'orden' => 30,
    ),
    array(
        'codigo' => 'interpretar',
        'nombre' => 'Interpretar resultados',
        'descripcion' => 'Cómo redactar la respuesta tras ejecutar el SQL',
        'orden' => 40,
    ),
    array(
        'codigo' => 'informativa',
        'nombre' => 'Capacidades',
        'descripcion' => 'Respuestas cuando preguntan qué puede consultar',
        'orden' => 50,
    ),
    array(
        'codigo' => 'flujos',
        'nombre' => 'Flujos especiales',
        'descripcion' => 'Gracias, adjuntos, precio oro, etc.',
        'orden' => 60,
    ),
);

$contexto = defined('IA_CONTEXTO_BD') ? (string) IA_CONTEXTO_BD : '';
if ($contexto !== '' && function_exists('ia_contexto_bd_etiquetas_anexo')) {
    $anexo = ia_contexto_bd_etiquetas_anexo();
    if ($anexo !== '' && strpos($contexto, $anexo) !== false) {
        $contexto = str_replace($anexo, "\n{{CABECERAS}}", $contexto);
    }
}

$prompts = array(
    array(
        'grupo' => 'identidad',
        'codigo' => 'base',
        'titulo' => 'Identidad del asistente',
        'orden' => 10,
        'contenido' => "Eres un asistente de análisis de datos para una joyería española.\nTienes acceso a una base de datos MariaDB 10.1.\nResponde en español, claro y conciso.",
    ),
    array(
        'grupo' => 'esquema',
        'codigo' => 'principal',
        'titulo' => 'Contexto y esquema BD (principal)',
        'orden' => 10,
        'contenido' => $contexto !== '' ? $contexto : 'Esquema no disponible en código.',
    ),
    array(
        'grupo' => 'reglas_sql',
        'codigo' => 'generar_select',
        'titulo' => 'Instrucciones al generar SELECT',
        'orden' => 10,
        'contenido' => "Tu tarea: generar SOLO consultas SELECT para MariaDB 10.1.\n"
            . "Devuelve únicamente el SQL (un SELECT). Sin markdown, sin explicaciones, sin punto y coma final.\n"
            . "Evita JOINs innecesarios: si el SELECT/WHERE solo usa una tabla, no joins otras por si acaso.",
    ),
    array(
        'grupo' => 'interpretar',
        'codigo' => 'instrucciones',
        'titulo' => 'Instrucciones al interpretar resultados',
        'orden' => 10,
        'contenido' => "Eres un asistente de análisis de datos para una joyería española.\n"
            . "Responde en español de forma clara y concisa:\n"
            . "- Si es un total o conteo, dilo con el número formateado.\n"
            . "- Usa formatos legibles: euros con €, fechas en DD/MM/YYYY.\n"
            . "- Si no hay resultados, explica brevemente por qué podría ser.\n"
            . "- PROHIBIDO: escribir SQL, SELECT, FROM, JOIN, nombres de tablas, consultas técnicas o bloques de código.\n"
            . "- No listes tablas ni digas que puedes consultar «otras tablas» de la BD.\n"
            . "- No escribas tablas en formato markdown (líneas con |).\n"
            . "- Máximo 3-4 líneas.",
    ),
    array(
        'grupo' => 'informativa',
        'codigo' => 'instrucciones',
        'titulo' => 'Instrucciones respuesta de capacidades',
        'orden' => 10,
        'contenido' => "El usuario pregunta por TUS CAPACIDADES o el ALCANCE de datos; NO ejecutes una consulta ahora.\n"
            . "Responde en español, breve (máximo 6 líneas), claro y amable.\n"
            . "Solo puedes obtener datos mediante SELECT sobre las tablas del contexto.\n"
            . "NO cites ni listes nombres de tablas que no aparezcan en el contexto (bloques TABLA:).\n"
            . "NO digas que tienes acceso a toda la base de datos ni inventes tablas fuera del contexto.\n"
            . "Si preguntan por «otras tablas», explica que solo trabajas con el esquema del contexto.\n"
            . "No generes SQL ni bloques de código.",
    ),
    array(
        'grupo' => 'flujos',
        'codigo' => 'gracias',
        'titulo' => 'Respuesta a agradecimientos',
        'orden' => 10,
        'contenido' => 'De nada, {{NOMBRE_USUARIO}}. Aquí estoy para lo que necesites. Dios te bendiga',
        'disparadores' => "gracias\nmuchas gracias\nmil gracias\nok gracias\nvale gracias\nthank you\nthanks\nty\nte agradezco\nfantastico\nfantástico\nperfecto\nexcelente\ngenial",
    ),
);
$conexion = conectar_bd();
if (!$conexion) {
    echo json_encode(array('success' => false, 'message' => 'Sin conexión BD'));
    exit;
}

$check = @mysqli_query($conexion, "SHOW TABLES LIKE 'ia_agent_grupos'");
if (!$check || mysqli_num_rows($check) === 0) {
    if ($check) {
        mysqli_free_result($check);
    }
    mysqli_close($conexion);
    echo json_encode(array(
        'success' => false,
        'message' => 'Crea primero las tablas ia_agent_grupos e ia_agent_prompts (ver install_ia_agent_tables.sql)',
    ));
    exit;
}
mysqli_free_result($check);

mysqli_begin_transaction($conexion);
try {
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

    $mapGrupoId = array();

    $stmtInsG = mysqli_prepare(
        $conexion,
        "INSERT INTO ia_agent_grupos (codigo, nombre, descripcion, orden, activo)
         VALUES (?, ?, ?, ?, 'true')
         ON DUPLICATE KEY UPDATE
           nombre = VALUES(nombre),
           descripcion = VALUES(descripcion),
           orden = VALUES(orden)"
    );
    if (!$stmtInsG) {
        throw new Exception(mysqli_error($conexion));
    }

    foreach ($grupos as $g) {
        $cod = $g['codigo'];
        $nom = $g['nombre'];
        $des = $g['descripcion'];
        $ord = (int) $g['orden'];
        mysqli_stmt_bind_param($stmtInsG, 'sssi', $cod, $nom, $des, $ord);
        if (!mysqli_stmt_execute($stmtInsG)) {
            throw new Exception(mysqli_stmt_error($stmtInsG));
        }
    }
    mysqli_stmt_close($stmtInsG);

    $resG = mysqli_query($conexion, 'SELECT id_grupo, codigo FROM ia_agent_grupos');
    if (!$resG) {
        throw new Exception(mysqli_error($conexion));
    }
    while ($row = mysqli_fetch_assoc($resG)) {
        $mapGrupoId[$row['codigo']] = (int) $row['id_grupo'];
    }
    mysqli_free_result($resG);

    if ($tieneDisp) {
        $stmtUpsert = mysqli_prepare(
            $conexion,
            "INSERT INTO ia_agent_prompts
                (id_grupo, codigo, titulo, contenido, disparadores, orden, activo, fecha_actualizacion, actualizado_por)
             VALUES (?, ?, ?, ?, ?, ?, 'true', ?, ?)
             ON DUPLICATE KEY UPDATE
               titulo = VALUES(titulo),
               contenido = VALUES(contenido),
               disparadores = VALUES(disparadores),
               orden = VALUES(orden),
               activo = 'true',
               fecha_actualizacion = VALUES(fecha_actualizacion),
               actualizado_por = VALUES(actualizado_por)"
        );
    } else {
        $stmtUpsert = mysqli_prepare(
            $conexion,
            "INSERT INTO ia_agent_prompts
                (id_grupo, codigo, titulo, contenido, orden, activo, fecha_actualizacion, actualizado_por)
             VALUES (?, ?, ?, ?, ?, 'true', ?, ?)
             ON DUPLICATE KEY UPDATE
               titulo = VALUES(titulo),
               contenido = VALUES(contenido),
               orden = VALUES(orden),
               activo = 'true',
               fecha_actualizacion = VALUES(fecha_actualizacion),
               actualizado_por = VALUES(actualizado_por)"
        );
    }
    if (!$stmtUpsert) {
        throw new Exception(mysqli_error($conexion));
    }

    $importados = 0;
    foreach ($prompts as $p) {
        if (!isset($mapGrupoId[$p['grupo']])) {
            continue;
        }
        $id_grupo = $mapGrupoId[$p['grupo']];
        $codigo = $p['codigo'];
        $titulo = $p['titulo'];
        $contenido = $p['contenido'];
        $orden = (int) $p['orden'];
        $disp = isset($p['disparadores']) ? (string) $p['disparadores'] : '';
        if ($tieneDisp) {
            mysqli_stmt_bind_param(
                $stmtUpsert,
                'issssisi',
                $id_grupo,
                $codigo,
                $titulo,
                $contenido,
                $disp,
                $orden,
                $ahora,
                $usuario_id
            );
        } else {
            mysqli_stmt_bind_param(
                $stmtUpsert,
                'isssisi',
                $id_grupo,
                $codigo,
                $titulo,
                $contenido,
                $orden,
                $ahora,
                $usuario_id
            );
        }
        if (!mysqli_stmt_execute($stmtUpsert)) {
            throw new Exception(mysqli_stmt_error($stmtUpsert));
        }
        $importados++;
    }
    mysqli_stmt_close($stmtUpsert);

    mysqli_commit($conexion);
    mysqli_close($conexion);

    echo json_encode(array(
        'success' => true,
        'message' => 'Importados/actualizados ' . $importados . ' prompts desde el código.',
        'reload'  => true,
    ), JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    mysqli_rollback($conexion);
    mysqli_close($conexion);
    echo json_encode(array('success' => false, 'message' => $e->getMessage()));
}
