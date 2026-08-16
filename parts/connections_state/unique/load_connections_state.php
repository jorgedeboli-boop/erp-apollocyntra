<?php
/**
 * Conexiones activas agrupadas por sucursal.
 */

ob_clean();

require_once '../../../include/session.php';
require_once '../../../include/functions.php';

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Método no permitido']);
        exit;
    }

    $conexion = conectar_bd();
    if (!$conexion) {
        throw new Exception('Error de conexión');
    }

    $checkTable = mysqli_query($conexion, "SHOW TABLES LIKE 'usersConexions'");
    if (!$checkTable || mysqli_num_rows($checkTable) === 0) {
        mysqli_close($conexion);
        echo json_encode([
            'success' => true,
            'total_conexiones' => 0,
            'total_usuarios' => 0,
            'total_sucursales' => 0,
            'sucursales' => [],
        ]);
        exit;
    }

    $query = "
        SELECT
            uc.idUserConexion,
            uc.dateConexion,
            uc.userId,
            uc.ipNumberUser,
            uc.userAgent,
            uc.locationLat,
            uc.locationLong,
            uc.tokensessioncontrol,
            u.sucursal_usuario,
            s.nombre_sucursal,
            u.nombre_usuario,
            u.apellido_usuario,
            u.usuario
        FROM usersConexions uc
        LEFT JOIN usuarios u ON uc.userId = u.id_usuario
        LEFT JOIN sucursal s ON u.sucursal_usuario = s.id_sucursal
        WHERE uc.state_connection = 'true'
        ORDER BY s.nombre_sucursal ASC, uc.dateConexion DESC
    ";

    $result = mysqli_query($conexion, $query);
    if (!$result) {
        throw new Exception('Error al consultar conexiones: ' . mysqli_error($conexion));
    }

    $sucursalesMap = [];
    $usuariosUnicos = [];
    $totalConexiones = 0;

    while ($row = mysqli_fetch_assoc($result)) {
        $totalConexiones++;

        $idSucursal = (int) ($row['sucursal_usuario'] ?? 0);
        $nombreSucursal = trim((string) ($row['nombre_sucursal'] ?? ''));
        if ($nombreSucursal === '') {
            $nombreSucursal = $idSucursal > 0 ? 'Sucursal #' . $idSucursal : 'Sin sucursal';
        }

        $nombreUsuario = trim(($row['nombre_usuario'] ?? '') . ' ' . ($row['apellido_usuario'] ?? ''));
        if ($nombreUsuario === '') {
            $nombreUsuario = trim((string) ($row['usuario'] ?? ''));
        }
        if ($nombreUsuario === '') {
            $nombreUsuario = 'Usuario #' . (int) ($row['userId'] ?? 0);
        }

        $userId = (int) ($row['userId'] ?? 0);
        if ($userId > 0) {
            $usuariosUnicos[$userId] = true;
        }

        $ubicacion = '';
        if (!empty($row['locationLat']) && !empty($row['locationLong'])) {
            $ubicacion = $row['locationLat'] . ', ' . $row['locationLong'];
        }

        $fechaTs = strtotime((string) ($row['dateConexion'] ?? ''));
        $fechaFormateada = $fechaTs ? date('d/m/Y H:i', $fechaTs) : '—';

        if (!isset($sucursalesMap[$idSucursal])) {
            $sucursalesMap[$idSucursal] = [
                'id_sucursal' => $idSucursal,
                'nombre_sucursal' => $nombreSucursal,
                'conexiones' => [],
            ];
        }

        $sucursalesMap[$idSucursal]['conexiones'][] = [
            'id_user_conexion' => (int) ($row['idUserConexion'] ?? 0),
            'id_usuario' => $userId,
            'nombre_usuario' => $nombreUsuario,
            'login_usuario' => trim((string) ($row['usuario'] ?? '')),
            'fecha_conexion' => $fechaFormateada,
            'ip' => trim((string) ($row['ipNumberUser'] ?? '')) !== '' ? (string) $row['ipNumberUser'] : 'N/A',
            'user_agent' => trim((string) ($row['userAgent'] ?? '')) !== '' ? (string) $row['userAgent'] : 'N/A',
            'ubicacion' => $ubicacion !== '' ? $ubicacion : 'N/A',
            'token' => trim((string) ($row['tokensessioncontrol'] ?? '')) !== '' ? (string) $row['tokensessioncontrol'] : 'N/A',
        ];
    }

    mysqli_free_result($result);
    mysqli_close($conexion);

    $sucursales = array_values($sucursalesMap);
    usort($sucursales, function ($a, $b) {
        return strcasecmp($a['nombre_sucursal'], $b['nombre_sucursal']);
    });

    echo json_encode([
        'success' => true,
        'total_conexiones' => $totalConexiones,
        'total_usuarios' => count($usuariosUnicos),
        'total_sucursales' => count($sucursales),
        'sucursales' => $sucursales,
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'total_conexiones' => 0,
        'total_usuarios' => 0,
        'total_sucursales' => 0,
        'sucursales' => [],
    ], JSON_UNESCAPED_UNICODE);
}
