<?php
/**
 * Consultar notificaciones pendientes para el usuario actual
 */

require_once '../../include/session.php';
require_once '../../include/functions.php';

header('Content-Type: application/json');

// Verificar que el usuario esté autenticado
if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error'   => 'No autorizado'
    ]);
    exit;
}

try {
    $conexion = conectar_bd();

    if (!$conexion) {
        throw new Exception('Error de conexión a la base de datos');
    }

    // Obtener notificaciones pendientes para el usuario receptor
    $query = "SELECT 
                id_notificacion,
                tipo_notificacion,
                mensaje_notificacion,
                url_notificacion,
                color_notificacion
              FROM notificaciones
              WHERE estado_notificacion = 'pendiente'
              ORDER BY fecha_notificacion DESC, hora_notificacion DESC
              LIMIT 10";

    $stmt = mysqli_prepare($conexion, $query);

    if (!$stmt) {
        throw new Exception('Error al preparar consulta: ' . mysqli_error($conexion));
    }

    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception('Error al ejecutar consulta: ' . mysqli_stmt_error($stmt));
    }

    $result = mysqli_stmt_get_result($stmt);

    $notificaciones = [];
    $ids = [];

    while ($row = mysqli_fetch_assoc($result)) {
        $notificaciones[] = [
            'id_notificacion'   => (int) $row['id_notificacion'],
            'tipo_notificacion' => $row['tipo_notificacion'],
            'mensaje_notificacion' => $row['mensaje_notificacion'],
            'url_notificacion'  => $row['url_notificacion'],
            'color_notificacion'=> $row['color_notificacion']
        ];
        $ids[] = (int) $row['id_notificacion'];
    }

    mysqli_stmt_close($stmt);

    // Marcar notificaciones como "mostrada" para no repetirlas
    if (!empty($ids)) {
        $updateQuery = "UPDATE notificaciones SET estado_notificacion = 'mostrada' WHERE id_notificacion = ?";
        $updateStmt = mysqli_prepare($conexion, $updateQuery);

        if ($updateStmt) {
            foreach ($ids as $id) {
                mysqli_stmt_bind_param($updateStmt, 'i', $id);
                mysqli_stmt_execute($updateStmt);
            }
            mysqli_stmt_close($updateStmt);
        }
    }
        

    mysqli_close($conexion);

    echo json_encode([
        'success'       => true,
        'notificaciones'=> $notificaciones
    ]);

} catch (Exception $e) {
    if (isset($stmt)) {
        mysqli_stmt_close($stmt);
    }
    if (isset($conexion)) {
        mysqli_close($conexion);
    }

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error'   => $e->getMessage()
    ]);
}


