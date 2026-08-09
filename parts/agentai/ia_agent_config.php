<?php
/**
 * Carga de prompts del agente IA desde BD (ia_agent_grupos / ia_agent_prompts).
 * Fallback a los textos embebidos en parts/agentai/ajax_ia_chat.php / ia_contexto_bd_extra.php.
 */

if (!function_exists('ia_agent_db')) {
    /**
     * @return mysqli|false
     */
    function ia_agent_db() {
        if (!function_exists('conectar_bd')) {
            return false;
        }
        return conectar_bd();
    }
}

/**
 * Obtiene un prompt activo por grupo.codigo + prompt.codigo.
 *
 * @return string|null null si no existe o tablas no disponibles
 */
function ia_agent_prompt_obtener($grupo_codigo, $prompt_codigo) {
    static $cache = array();
    $key = strtolower(trim((string) $grupo_codigo) . '/' . trim((string) $prompt_codigo));
    if (array_key_exists($key, $cache)) {
        return $cache[$key];
    }

    $cache[$key] = null;
    $conexion = ia_agent_db();
    if (!$conexion) {
        return null;
    }

    $sql = "SELECT p.contenido
            FROM ia_agent_prompts p
            INNER JOIN ia_agent_grupos g ON g.id_grupo = p.id_grupo
            WHERE g.codigo = ?
              AND p.codigo = ?
              AND g.activo = 'true'
              AND p.activo = 'true'
            LIMIT 1";
    $stmt = mysqli_prepare($conexion, $sql);
    if (!$stmt) {
        mysqli_close($conexion);
        return null;
    }

    $g = (string) $grupo_codigo;
    $p = (string) $prompt_codigo;
    mysqli_stmt_bind_param($stmt, 'ss', $g, $p);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $row = $res ? mysqli_fetch_assoc($res) : null;
    mysqli_stmt_close($stmt);
    mysqli_close($conexion);

    if ($row && isset($row['contenido']) && trim((string) $row['contenido']) !== '') {
        $cache[$key] = (string) $row['contenido'];
    }
    return $cache[$key];
}

/**
 * Concatena todos los prompts activos de un grupo (orden ASC), separados por doble salto.
 */
function ia_agent_grupo_contenido($grupo_codigo) {
    static $cache = array();
    $key = strtolower(trim((string) $grupo_codigo));
    if (array_key_exists($key, $cache)) {
        return $cache[$key];
    }

    $cache[$key] = '';
    $conexion = ia_agent_db();
    if (!$conexion) {
        return '';
    }

    $sql = "SELECT p.contenido
            FROM ia_agent_prompts p
            INNER JOIN ia_agent_grupos g ON g.id_grupo = p.id_grupo
            WHERE g.codigo = ?
              AND g.activo = 'true'
              AND p.activo = 'true'
            ORDER BY p.orden ASC, p.id_prompt ASC";
    $stmt = mysqli_prepare($conexion, $sql);
    if (!$stmt) {
        mysqli_close($conexion);
        return '';
    }

    $g = (string) $grupo_codigo;
    mysqli_stmt_bind_param($stmt, 's', $g);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $partes = array();
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
            $txt = trim((string) ($row['contenido'] ?? ''));
            if ($txt !== '') {
                $partes[] = $txt;
            }
        }
    }
    mysqli_stmt_close($stmt);
    mysqli_close($conexion);

    $cache[$key] = implode("\n\n", $partes);
    return $cache[$key];
}

/**
 * Sustituye placeholders {{CABECERAS}} y {{NOMBRE_USUARIO}} en un texto de prompt.
 */
function ia_agent_aplicar_placeholders($texto) {
    $texto = (string) $texto;
    if ($texto === '') {
        return '';
    }
    if (strpos($texto, '{{CABECERAS}}') !== false && function_exists('ia_contexto_bd_etiquetas_anexo')) {
        $texto = str_replace('{{CABECERAS}}', ia_contexto_bd_etiquetas_anexo(), $texto);
    }
    if (strpos($texto, '{{NOMBRE_USUARIO}}') !== false) {
        $nombre = '';
        if (!empty($_SESSION['usuario_nombre_completo'])) {
            $nombre = trim((string) $_SESSION['usuario_nombre_completo']);
        } elseif (!empty($_SESSION['usuario_nombre'])) {
            $nombre = trim((string) $_SESSION['usuario_nombre']);
        }
        $texto = str_replace('{{NOMBRE_USUARIO}}', $nombre, $texto);
        $texto = preg_replace('/\s{2,}/u', ' ', $texto);
        $texto = preg_replace('/\s+,/u', ',', $texto);
    }
    return $texto;
}

/**
 * Contexto BD efectivo: BD si hay contenido en grupo esquema; si no, constante/archivo PHP.
 *
 * @param string $fallback_php Texto PHP actual (IA_CONTEXTO_BD o similar)
 */
function ia_agent_contexto_bd_efectivo($fallback_php) {
    $desde_bd = ia_agent_grupo_contenido('esquema');
    if ($desde_bd !== '') {
        return ia_agent_aplicar_placeholders($desde_bd);
    }
    return (string) $fallback_php;
}

/**
 * Prompt concreto con fallback.
 */
function ia_agent_prompt_o_fallback($grupo, $codigo, $fallback) {
    $desde_bd = ia_agent_prompt_obtener($grupo, $codigo);
    if ($desde_bd !== null && trim($desde_bd) !== '') {
        return ia_agent_aplicar_placeholders($desde_bd);
    }
    return (string) $fallback;
}

/**
 * Asegura columna disparadores en ia_agent_prompts (flujos especiales).
 */
function ia_agent_ensure_columna_disparadores($conexion = null) {
    static $ok = null;
    if ($ok === true) {
        return true;
    }
    if ($ok === false) {
        return false;
    }

    $cerrar = false;
    if (!$conexion) {
        $conexion = ia_agent_db();
        $cerrar = (bool) $conexion;
    }
    if (!$conexion) {
        $ok = false;
        return false;
    }

    $check = @mysqli_query($conexion, "SHOW COLUMNS FROM ia_agent_prompts LIKE 'disparadores'");
    if ($check && mysqli_num_rows($check) > 0) {
        mysqli_free_result($check);
        if ($cerrar) {
            mysqli_close($conexion);
        }
        $ok = true;
        return true;
    }
    if ($check) {
        mysqli_free_result($check);
    }

    $altered = @mysqli_query(
        $conexion,
        "ALTER TABLE ia_agent_prompts
         ADD COLUMN disparadores TEXT NULL COMMENT 'Frases que activan el flujo (una por línea)' AFTER contenido"
    );
    if ($cerrar) {
        mysqli_close($conexion);
    }
    $ok = (bool) $altered;
    return $ok;
}

/**
 * Normaliza texto de usuario/disparador para comparar.
 */
function ia_agent_normalizar_frase_flujo($texto) {
    $raw = preg_replace('/\s+/u', ' ', trim((string) $texto));
    $raw = preg_replace('/[!¡\.…,;:¿\?]+$/u', '', $raw);
    $raw = trim((string) $raw);
    if ($raw === '') {
        return '';
    }
    return function_exists('mb_strtolower') ? mb_strtolower($raw, 'UTF-8') : strtolower($raw);
}

/**
 * Parsea disparadores (una frase por línea; también admite comas).
 *
 * @return string[]
 */
function ia_agent_parsear_disparadores($texto) {
    $texto = str_replace(array("\r\n", "\r"), "\n", (string) $texto);
    $lineas = preg_split('/\n+/', $texto);
    $out = array();
    foreach ($lineas as $linea) {
        $linea = trim((string) $linea);
        if ($linea === '') {
            continue;
        }
        // Permitir varias frases en la misma línea separadas por |
        $partes = preg_split('/\s*\|\s*/', $linea);
        foreach ($partes as $parte) {
            $parte = trim((string) $parte);
            if ($parte === '') {
                continue;
            }
            // También aceptar listas separadas por coma si no parecen una sola frase larga
            if (strpos($parte, ',') !== false && preg_match('/^[^,]{1,40}(,\s*[^,]{1,40})+$/u', $parte)) {
                foreach (preg_split('/\s*,\s*/', $parte) as $sub) {
                    $sub = trim((string) $sub);
                    if ($sub !== '') {
                        $out[] = $sub;
                    }
                }
            } else {
                $out[] = $parte;
            }
        }
    }
    return array_values(array_unique($out));
}

/**
 * Disparadores por defecto del flujo "gracias" si el campo está vacío.
 *
 * @return string[]
 */
function ia_agent_disparadores_gracias_default() {
    return array(
        'gracias',
        'muchas gracias',
        'mil gracias',
        'ok gracias',
        'vale gracias',
        'muy gracias',
        'thank you',
        'thanks',
        'ty',
        'te agradezco',
        'fantastico',
        'fantástico',
        'perfecto',
        'excelente',
        'genial',
    );
}

/**
 * Lista flujos activos del grupo "flujos" (con caché estática por request).
 *
 * @return array<int, array{codigo:string,titulo:string,contenido:string,disparadores:string,orden:int}>
 */
function ia_agent_flujos_activos() {
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }
    $cache = array();

    $conexion = ia_agent_db();
    if (!$conexion) {
        return $cache;
    }

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
        ? 'p.codigo, p.titulo, p.contenido, p.disparadores, p.orden'
        : 'p.codigo, p.titulo, p.contenido, p.orden';

    $sql = "SELECT {$cols}
            FROM ia_agent_prompts p
            INNER JOIN ia_agent_grupos g ON g.id_grupo = p.id_grupo
            WHERE g.codigo = 'flujos'
              AND g.activo = 'true'
              AND p.activo = 'true'
            ORDER BY p.orden ASC, p.id_prompt ASC";
    $res = mysqli_query($conexion, $sql);
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
            $cache[] = array(
                'codigo' => (string) ($row['codigo'] ?? ''),
                'titulo' => (string) ($row['titulo'] ?? ''),
                'contenido' => (string) ($row['contenido'] ?? ''),
                'disparadores' => $tieneDisp ? (string) ($row['disparadores'] ?? '') : '',
                'orden' => (int) ($row['orden'] ?? 0),
            );
        }
        mysqli_free_result($res);
    }
    mysqli_close($conexion);

    return $cache;
}

/**
 * Si la pregunta coincide exactamente con un disparador de un flujo, devuelve la respuesta.
 * null = no hay match (seguir con SQL / otros handlers).
 *
 * @return string|null
 */
function ia_agent_flujo_respuesta_si_aplica($pregunta) {
    $raw = preg_replace('/\s+/u', ' ', trim((string) $pregunta));
    $raw_cmp = ia_agent_normalizar_frase_flujo($raw);
    if ($raw_cmp === '') {
        return null;
    }

    // "Gracias, dime el total…" → no es solo flujo
    if (function_exists('ia_pregunta_normalizar_entrada')) {
        $sin_prefijo = trim(ia_pregunta_normalizar_entrada($raw));
        $sin_cmp = ia_agent_normalizar_frase_flujo($sin_prefijo);
        if ($sin_cmp !== '' && $sin_cmp !== $raw_cmp) {
            return null;
        }
    }

    $flujos = ia_agent_flujos_activos();
    foreach ($flujos as $flujo) {
        $contenido = trim((string) ($flujo['contenido'] ?? ''));
        if ($contenido === '') {
            continue;
        }

        $triggers = ia_agent_parsear_disparadores($flujo['disparadores'] ?? '');
        if (empty($triggers) && strtolower((string) ($flujo['codigo'] ?? '')) === 'gracias') {
            $triggers = ia_agent_disparadores_gracias_default();
        }
        if (empty($triggers)) {
            continue;
        }

        foreach ($triggers as $trig) {
            if (ia_agent_normalizar_frase_flujo($trig) === $raw_cmp) {
                return ia_agent_aplicar_placeholders($contenido);
            }
        }
    }

    return null;
}
