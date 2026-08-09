<?php
/**
 * Lógica compartida: camera/api/consultar_token.php y parts/*/procesar_consultar_token.php
 */
require_once __DIR__ . '/camera_qr_multifoto.php';

/**
 * @param array $data token, id_token, last_foto_count opcional
 * @return array
 */
function camera_consultar_token_execute(array $data)
{
    if (empty($data['token'])) {
        throw new InvalidArgumentException('Token no proporcionado');
    }
    if (empty($data['id_token'])) {
        throw new InvalidArgumentException('ID de token no proporcionado');
    }

    $id_token = (int) $data['id_token'];
    $token = trim((string) $data['token']);
    $last_foto_count = isset($data['last_foto_count']) ? (int) $data['last_foto_count'] : 0;

    $conexion = conectar_bd();

    // mysqli_query + escape: funciona sin mysqlnd y evita fallos de bind_result en PHP antiguos.
    $esc = mysqli_real_escape_string($conexion, $token);
    $sql = 'SELECT * FROM tokens_actions WHERE id_token = ' . (int) $id_token
        . " AND token_string = '" . $esc . "' LIMIT 1";
    $res = mysqli_query($conexion, $sql);
    if (!$res) {
        throw new RuntimeException('Error al consultar token: ' . mysqli_error($conexion));
    }
    $row = mysqli_fetch_assoc($res);
    mysqli_free_result($res);

    if (!$row) {
        mysqli_close($conexion);
        return array(
            'success' => true,
            'utilizado' => false,
            'mensaje' => 'Token no encontrado o no coincide',
        );
    }

    $tipo = isset($row['type_item']) ? (string) $row['type_item'] : '';

    if (camera_qr_es_multifoto($tipo)) {
        camera_token_ensure_foto_count_column($conexion);
        $count = isset($row['foto_subidas_count']) ? (int) $row['foto_subidas_count'] : 0;

        if ($count > $last_foto_count) {
            $token_data = array(
                'id_token' => $id_token,
                'id_item' => $row['id_item'],
                'type_item' => $row['type_item'],
                'fecha_token' => $row['fecha_token'],
            );
            mysqli_close($conexion);

            return array(
                'success' => true,
                'utilizado' => true,
                'multifoto_solo_refresco' => true,
                'foto_subidas_count' => $count,
                'mensaje' => 'Nueva foto en sesión',
                'token_data' => $token_data,
            );
        }

        mysqli_close($conexion);
        return array(
            'success' => true,
            'utilizado' => false,
            'foto_subidas_count' => $count,
            'mensaje' => 'Sin fotos nuevas en esta sesión',
        );
    }

    $state_false = ($row['state_token'] === 'false' || $row['state_token'] === false
        || (string) $row['state_token'] === '0');

    if ($state_false) {
        $token_data = array(
            'id_token' => $id_token,
            'id_item' => $row['id_item'],
            'type_item' => $row['type_item'],
            'fecha_token' => $row['fecha_token'],
        );

        $query_delete = 'DELETE FROM tokens_actions WHERE id_token = ?';
        $stmt_delete = mysqli_prepare($conexion, $query_delete);
        mysqli_stmt_bind_param($stmt_delete, 'i', $id_token);
        mysqli_stmt_execute($stmt_delete);
        mysqli_stmt_close($stmt_delete);
        mysqli_close($conexion);

        return array(
            'success' => true,
            'utilizado' => true,
            'mensaje' => 'El token ya fue utilizado',
            'token_data' => $token_data,
        );
    }

    mysqli_close($conexion);
    return array(
        'success' => true,
        'utilizado' => false,
        'mensaje' => 'El token aún no ha sido utilizado',
    );
}
