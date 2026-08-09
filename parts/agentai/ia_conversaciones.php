<?php
/**
 * Conversaciones del chat IA (UI + vínculo con historial Claude).
 * Compatible PHP 7.0.
 */

if (!defined('IA_HISTORIAL_DIR')) {
    define('IA_HISTORIAL_DIR', __DIR__ . '/historiales/');
}

function ia_conv_path($usuario_id) {
    return IA_HISTORIAL_DIR . 'conversaciones_' . (int) $usuario_id . '.json';
}

function ia_conv_ahora() {
    return date('Y-m-d H:i:s');
}

function ia_conv_nuevo_id() {
    return uniqid('c', true);
}

function ia_conv_titulo_desde_html($html) {
    $texto = trim(html_entity_decode(strip_tags((string) $html), ENT_QUOTES, 'UTF-8'));
    $texto = preg_replace('/\s+/u', ' ', $texto);
    if ($texto === '') {
        return 'Nueva conversación';
    }
    // Preferir primer mensaje de usuario si está marcado
    if (preg_match('/ia-msg-user[\s\S]*?ia-msg-burbuja[^>]*>([\s\S]*?)<\/div>/u', (string) $html, $m)) {
        $u = trim(html_entity_decode(strip_tags($m[1]), ENT_QUOTES, 'UTF-8'));
        $u = preg_replace('/\s+/u', ' ', $u);
        if ($u !== '') {
            $texto = $u;
        }
    }
    if (function_exists('mb_substr')) {
        return mb_strlen($texto, 'UTF-8') > 48 ? mb_substr($texto, 0, 48, 'UTF-8') . '…' : $texto;
    }
    return strlen($texto) > 48 ? substr($texto, 0, 48) . '…' : $texto;
}

function ia_conv_tiene_mensajes_reales($html) {
    $html = (string) $html;
    if ($html === '') {
        return false;
    }
    // Hay al menos un mensaje de usuario
    return (strpos($html, 'ia-msg-user') !== false);
}

function ia_conv_leer_store($usuario_id) {
    $path = ia_conv_path($usuario_id);
    $vacio = array(
        'activa_id' => '',
        'conversaciones' => array(),
    );
    if (!file_exists($path)) {
        return $vacio;
    }
    $json = file_get_contents($path);
    $data = json_decode($json, true);
    if (!is_array($data)) {
        return $vacio;
    }
    if (!isset($data['conversaciones']) || !is_array($data['conversaciones'])) {
        $data['conversaciones'] = array();
    }
    if (!isset($data['activa_id'])) {
        $data['activa_id'] = '';
    }
    return $data;
}

function ia_conv_guardar_store($usuario_id, $store) {
    if (!is_dir(IA_HISTORIAL_DIR)) {
        mkdir(IA_HISTORIAL_DIR, 0755, true);
    }
    // Limitar a 40 conversaciones (las más recientes primero)
    if (isset($store['conversaciones']) && is_array($store['conversaciones']) && count($store['conversaciones']) > 40) {
        $store['conversaciones'] = array_slice($store['conversaciones'], 0, 40);
    }
    file_put_contents(
        ia_conv_path($usuario_id),
        json_encode($store, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
    );
}

function ia_conv_buscar(&$store, $id) {
    if ($id === '' || empty($store['conversaciones'])) {
        return null;
    }
    foreach ($store['conversaciones'] as $i => $c) {
        if (isset($c['id']) && $c['id'] === $id) {
            return $i;
        }
    }
    return null;
}

function ia_conv_crear_vacia($titulo = 'Nueva conversación') {
    $ahora = ia_conv_ahora();
    return array(
        'id' => ia_conv_nuevo_id(),
        'titulo' => $titulo,
        'creada' => $ahora,
        'actualizada' => $ahora,
        'html' => '',
        'historial_claude' => array(),
    );
}

/**
 * Asegura que exista una conversación activa.
 * @return array store actualizado
 */
function ia_conv_asegurar_activa($usuario_id) {
    $store = ia_conv_leer_store($usuario_id);
    $idx = ia_conv_buscar($store, isset($store['activa_id']) ? $store['activa_id'] : '');
    if ($idx === null) {
        $nueva = ia_conv_crear_vacia();
        array_unshift($store['conversaciones'], $nueva);
        $store['activa_id'] = $nueva['id'];
        ia_conv_guardar_store($usuario_id, $store);
    }
    return $store;
}

function ia_conv_obtener_activa($usuario_id) {
    $store = ia_conv_asegurar_activa($usuario_id);
    $idx = ia_conv_buscar($store, $store['activa_id']);
    if ($idx === null) {
        return null;
    }
    return $store['conversaciones'][$idx];
}

function ia_conv_listar_resumen($usuario_id) {
    $store = ia_conv_leer_store($usuario_id);
    $lista = array();
    foreach ($store['conversaciones'] as $c) {
        $lista[] = array(
            'id' => isset($c['id']) ? $c['id'] : '',
            'titulo' => isset($c['titulo']) ? $c['titulo'] : 'Conversación',
            'actualizada' => isset($c['actualizada']) ? $c['actualizada'] : '',
            'activa' => (isset($store['activa_id']) && isset($c['id']) && $store['activa_id'] === $c['id']),
        );
    }
    return array(
        'activa_id' => isset($store['activa_id']) ? $store['activa_id'] : '',
        'conversaciones' => $lista,
    );
}

/**
 * Guarda el HTML de la UI en la conversación activa.
 * Opcionalmente sincroniza historial Claude desde el fichero clásico.
 */
function ia_conv_guardar_activa_html($usuario_id, $html, $titulo = '') {
    $store = ia_conv_asegurar_activa($usuario_id);
    $idx = ia_conv_buscar($store, $store['activa_id']);
    if ($idx === null) {
        return false;
    }
    $html = (string) $html;
    // Quitar loaders al guardar
    $html = preg_replace('/<div class="ia-msg ia-msg-bot"[^>]*>\s*<div class="ia-loading">[\s\S]*?<\/div>\s*<\/div>/u', '', $html);

    $store['conversaciones'][$idx]['html'] = $html;
    $store['conversaciones'][$idx]['actualizada'] = ia_conv_ahora();
    if ($titulo !== '') {
        $store['conversaciones'][$idx]['titulo'] = $titulo;
    } elseif (ia_conv_tiene_mensajes_reales($html)) {
        $store['conversaciones'][$idx]['titulo'] = ia_conv_titulo_desde_html($html);
    }

    // Snapshot del historial Claude actual
    if (function_exists('ia_historial_leer')) {
        $store['conversaciones'][$idx]['historial_claude'] = ia_historial_leer($usuario_id);
    }

    // Mover activa al principio
    $activa = $store['conversaciones'][$idx];
    unset($store['conversaciones'][$idx]);
    $store['conversaciones'] = array_values($store['conversaciones']);
    array_unshift($store['conversaciones'], $activa);
    $store['activa_id'] = $activa['id'];

    ia_conv_guardar_store($usuario_id, $store);
    return $activa['id'];
}

/**
 * Archiva la activa (si tiene mensajes) y crea una nueva vacía.
 */
function ia_conv_nueva($usuario_id, $html_actual = '') {
    $store = ia_conv_asegurar_activa($usuario_id);
    $idx = ia_conv_buscar($store, $store['activa_id']);

    if ($idx !== null) {
        if ($html_actual !== '') {
            $store['conversaciones'][$idx]['html'] = $html_actual;
            if (ia_conv_tiene_mensajes_reales($html_actual)) {
                $store['conversaciones'][$idx]['titulo'] = ia_conv_titulo_desde_html($html_actual);
            }
        }
        $store['conversaciones'][$idx]['actualizada'] = ia_conv_ahora();
        if (function_exists('ia_historial_leer')) {
            $store['conversaciones'][$idx]['historial_claude'] = ia_historial_leer($usuario_id);
        }
        // Si la activa no tenía mensajes reales, reutilizarla vacía
        if (!ia_conv_tiene_mensajes_reales(isset($store['conversaciones'][$idx]['html']) ? $store['conversaciones'][$idx]['html'] : '')) {
            $store['conversaciones'][$idx]['html'] = '';
            $store['conversaciones'][$idx]['titulo'] = 'Nueva conversación';
            $store['conversaciones'][$idx]['historial_claude'] = array();
            $store['conversaciones'][$idx]['actualizada'] = ia_conv_ahora();
            ia_conv_guardar_store($usuario_id, $store);
            if (function_exists('ia_historial_limpiar')) {
                ia_historial_limpiar($usuario_id);
            }
            return $store['conversaciones'][$idx];
        }
    }

    $nueva = ia_conv_crear_vacia();
    array_unshift($store['conversaciones'], $nueva);
    $store['activa_id'] = $nueva['id'];
    ia_conv_guardar_store($usuario_id, $store);

    if (function_exists('ia_historial_limpiar')) {
        ia_historial_limpiar($usuario_id);
    }

    return $nueva;
}

function ia_conv_cargar($usuario_id, $conv_id) {
    $store = ia_conv_leer_store($usuario_id);
    $idx = ia_conv_buscar($store, $conv_id);
    if ($idx === null) {
        return false;
    }
    $store['activa_id'] = $conv_id;
    $activa = $store['conversaciones'][$idx];
    unset($store['conversaciones'][$idx]);
    $store['conversaciones'] = array_values($store['conversaciones']);
    array_unshift($store['conversaciones'], $activa);
    ia_conv_guardar_store($usuario_id, $store);

    // Restaurar historial Claude de esa conversación
    if (function_exists('ia_historial_guardar')) {
        $hist = isset($activa['historial_claude']) && is_array($activa['historial_claude'])
            ? $activa['historial_claude']
            : array();
        ia_historial_guardar($usuario_id, $hist);
    }

    return $activa;
}

function ia_conv_handler_ajax($usuario_id) {
    $accion = isset($_REQUEST['accion']) ? (string) $_REQUEST['accion'] : '';
    header('Content-Type: application/json; charset=utf-8');

    if ($usuario_id <= 0) {
        echo json_encode(array('ok' => false, 'error' => 'No autenticado'));
        exit;
    }

    switch ($accion) {
        case 'conv_obtener_activa':
            $c = ia_conv_obtener_activa($usuario_id);
            echo json_encode(array(
                'ok' => true,
                'conversacion' => $c,
                'lista' => ia_conv_listar_resumen($usuario_id),
            ), JSON_UNESCAPED_UNICODE);
            exit;

        case 'conv_guardar':
            $html = isset($_POST['html']) ? (string) $_POST['html'] : '';
            $titulo = isset($_POST['titulo']) ? trim((string) $_POST['titulo']) : '';
            $id = ia_conv_guardar_activa_html($usuario_id, $html, $titulo);
            echo json_encode(array(
                'ok' => (bool) $id,
                'activa_id' => $id,
                'lista' => ia_conv_listar_resumen($usuario_id),
            ), JSON_UNESCAPED_UNICODE);
            exit;

        case 'conv_nueva':
            $html = isset($_POST['html']) ? (string) $_POST['html'] : '';
            $nueva = ia_conv_nueva($usuario_id, $html);
            echo json_encode(array(
                'ok' => true,
                'conversacion' => $nueva,
                'lista' => ia_conv_listar_resumen($usuario_id),
            ), JSON_UNESCAPED_UNICODE);
            exit;

        case 'conv_listar':
            echo json_encode(array(
                'ok' => true,
                'lista' => ia_conv_listar_resumen($usuario_id),
            ), JSON_UNESCAPED_UNICODE);
            exit;

        case 'conv_cargar':
            $id = isset($_POST['id']) ? (string) $_POST['id'] : '';
            $c = ia_conv_cargar($usuario_id, $id);
            if ($c === false) {
                echo json_encode(array('ok' => false, 'error' => 'Conversación no encontrada'));
                exit;
            }
            echo json_encode(array(
                'ok' => true,
                'conversacion' => $c,
                'lista' => ia_conv_listar_resumen($usuario_id),
            ), JSON_UNESCAPED_UNICODE);
            exit;

        default:
            echo json_encode(array('ok' => false, 'error' => 'Acción desconocida'));
            exit;
    }
}
