<?php
/**
 * Cliente mínimo para la API de Claude (Anthropic).
 */

if (!defined('IA_CLAUDE_MODEL')) {
    $ia_model_env = getenv('CLAUDE_MODEL');
    if ($ia_model_env === false || $ia_model_env === '') {
        $ia_model_env = getenv('IA_CLAUDE_MODEL');
    }
    if ($ia_model_env !== false && trim($ia_model_env) !== '') {
        define('IA_CLAUDE_MODEL', trim($ia_model_env));
    } else {
        // claude-sonnet-4-20250514 está retirado; sustituto recomendado por Anthropic
        define('IA_CLAUDE_MODEL', 'claude-sonnet-4-6');
    }
}

if (!defined('IA_API_KEY')) {
    require_once __DIR__ . '/config.php';
    $ia_key_env = '';
    if (defined('ANTHROPIC_API_KEY') && ANTHROPIC_API_KEY !== '') {
        $ia_key_env = ANTHROPIC_API_KEY;
    }
    if ($ia_key_env === '') {
        $ia_key_env = getenv('ANTHROPIC_API_KEY');
        if ($ia_key_env === false || $ia_key_env === '') {
            $ia_key_env = getenv('IA_API_KEY');
        }
    }
    if ($ia_key_env !== false && $ia_key_env !== '') {
        define('IA_API_KEY', $ia_key_env);
    } else {
        define('IA_API_KEY', '');
    }
}

/** @var string */
$GLOBALS['ia_claude_ultimo_error'] = '';

function ia_claude_ultimo_error()
{
    return isset($GLOBALS['ia_claude_ultimo_error']) ? (string) $GLOBALS['ia_claude_ultimo_error'] : '';
}

function ia_claude_registrar_error($mensaje)
{
    $GLOBALS['ia_claude_ultimo_error'] = (string) $mensaje;
}

/**
 * Garantiza alternancia user/assistant exigida por Anthropic.
 *
 * @param array $messages
 * @return array
 */
function ia_claude_normalizar_mensajes($messages)
{
    $out = array();
    foreach ($messages as $msg) {
        if (!is_array($msg) || !isset($msg['role'], $msg['content'])) {
            continue;
        }
        $role = (string) $msg['role'];
        $content = trim((string) $msg['content']);
        if ($content === '' || ($role !== 'user' && $role !== 'assistant')) {
            continue;
        }
        if (empty($out)) {
            if ($role !== 'user') {
                continue;
            }
            $out[] = array('role' => 'user', 'content' => $content);
            continue;
        }
        $last_role = $out[count($out) - 1]['role'];
        if ($last_role === $role) {
            $out[count($out) - 1]['content'] .= "\n\n" . $content;
        } else {
            $out[] = array('role' => $role, 'content' => $content);
        }
    }
    return $out;
}

/**
 * @param array       $messages
 * @param int         $max_tokens
 * @param int         $timeout_segundos
 * @param string|null $system
 * @return string|false
 */
function ia_llamar_claude($messages, $max_tokens, $timeout_segundos = 60, $system = null)
{
    ia_claude_registrar_error('');

    if (!function_exists('curl_init')) {
        ia_claude_registrar_error('cURL no está disponible en el servidor.');
        return false;
    }

    if (!defined('IA_API_KEY') || IA_API_KEY === '') {
        ia_claude_registrar_error('No hay clave de API de Claude configurada.');
        return false;
    }

    $data = array(
        'model'      => IA_CLAUDE_MODEL,
        'max_tokens' => (int) $max_tokens,
        'messages'   => array(),
    );

    if ($system !== null && trim((string) $system) !== '') {
        $data['system'] = (string) $system;
    }

    $messages = ia_claude_normalizar_mensajes($messages);
    if (empty($messages)) {
        ia_claude_registrar_error('No hay mensajes válidos para enviar a Claude.');
        return false;
    }
    $data['messages'] = $messages;

    $ch = curl_init('https://api.anthropic.com/v1/messages');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'x-api-key: ' . IA_API_KEY,
        'anthropic-version: 2023-06-01',
    ));
    curl_setopt($ch, CURLOPT_TIMEOUT, (int) $timeout_segundos);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);

    $response = curl_exec($ch);
    $curl_errno = curl_errno($ch);
    $curl_error = curl_error($ch);
    $http_code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if ($curl_errno !== 0) {
        ia_claude_registrar_error('Error de conexión con Claude: ' . $curl_error);
        return false;
    }

    if ($response === false || $response === '') {
        ia_claude_registrar_error('Claude devolvió una respuesta vacía (HTTP ' . $http_code . ').');
        return false;
    }

    $json = json_decode($response, true);
    if (!is_array($json)) {
        ia_claude_registrar_error('Respuesta de Claude no válida (HTTP ' . $http_code . ').');
        return false;
    }

    if (isset($json['error']['message']) && $json['error']['message'] !== '') {
        ia_claude_registrar_error('Claude API: ' . $json['error']['message']);
        return false;
    }

    if ($http_code >= 400) {
        $msg = isset($json['error']['message']) ? $json['error']['message'] : ('HTTP ' . $http_code);
        ia_claude_registrar_error('Claude API: ' . $msg);
        return false;
    }

    if (isset($json['content'][0]['text'])) {
        return trim($json['content'][0]['text']);
    }

    ia_claude_registrar_error('Claude no devolvió texto en la respuesta.');
    return false;
}
