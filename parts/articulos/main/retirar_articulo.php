<?php
require_once '../../../include/session.php';

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Método no permitido']);
        exit;
    }

    if (!isset($_POST['id_articulo']) || empty($_POST['id_articulo'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'ID de artículo es requerido']);
        exit;
    }

    $id_articulo = (int) $_POST['id_articulo'];
    $motivo_retirado = trim((string) ($_POST['motivo_retirado'] ?? ''));

    if ($motivo_retirado === '') {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'El comentario es obligatorio']);
        exit;
    }

    $conexion = conectar_bd();

    $query_check = "SELECT id, estado, id_sucursal_destino FROM articulos_venta WHERE id = ? LIMIT 1";
    $stmt_check = mysqli_prepare($conexion, $query_check);
    mysqli_stmt_bind_param($stmt_check, 'i', $id_articulo);
    mysqli_stmt_execute($stmt_check);
    $result_check = mysqli_stmt_get_result($stmt_check);

    if (mysqli_num_rows($result_check) === 0) {
        mysqli_stmt_close($stmt_check);
        mysqli_close($conexion);
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'El artículo no existe']);
        exit;
    }

    $articulo = mysqli_fetch_assoc($result_check);
    mysqli_stmt_close($stmt_check);

    $estados_validos = ['noetiquetado_c', 'noetiquetado_u', 'enventa'];
    $estado_actual = strtolower((string) $articulo['estado']);
    $id_sucursal = (int) $articulo['id_sucursal_destino'];

    if (!in_array($estado_actual, $estados_validos, true)) {
        mysqli_close($conexion);
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'El artículo no puede retirarse desde el estado actual']);
        exit;
    }

    mysqli_begin_transaction($conexion);

    try {
        $stmt_aud = mysqli_prepare(
            $conexion,
            "SELECT id_rel_art_aud, rel_id_auditoria, estado_auditoria
             FROM rel_art_auditoria
             WHERE tipo_articulo = 'venta' AND rel_articulo = ?"
        );
        if (!$stmt_aud) {
            throw new Exception('Error al consultar auditoría: ' . mysqli_error($conexion));
        }

        mysqli_stmt_bind_param($stmt_aud, 'i', $id_articulo);
        mysqli_stmt_execute($stmt_aud);
        $result_aud = mysqli_stmt_get_result($stmt_aud);

        $filas_auditoria = [];
        while ($row_aud = mysqli_fetch_assoc($result_aud)) {
            $filas_auditoria[] = $row_aud;
        }
        mysqli_stmt_close($stmt_aud);

        foreach ($filas_auditoria as $fila_aud) {
            $id_rel_art_aud = (int) $fila_aud['id_rel_art_aud'];
            $id_auditoria = (int) $fila_aud['rel_id_auditoria'];
            $estado_auditoria = strtolower(trim((string) ($fila_aud['estado_auditoria'] ?? '')));

            if ($estado_auditoria === 'auditando') {
                $sql_up_aud = "UPDATE auditorias_tiendas
                               SET total_articulos_auditar = total_articulos_auditar - 1,
                                   articulos_stock = articulos_stock - 1
                               WHERE id_auditoria = ?";
            } elseif ($estado_auditoria === 'existente') {
                $sql_up_aud = "UPDATE auditorias_tiendas
                               SET total_articulos_auditar = total_articulos_auditar - 1,
                                   total_articulos_existentes = total_articulos_existentes - 1,
                                   total_articulos_auditados = total_articulos_auditados - 1,
                                   articulos_stock = articulos_stock - 1
                               WHERE id_auditoria = ?";
            } elseif ($estado_auditoria === 'faltante') {
                $sql_up_aud = "UPDATE auditorias_tiendas
                               SET total_articulos_auditar = total_articulos_auditar - 1,
                                   total_articulos_faltantes = total_articulos_faltantes - 1,
                                   total_articulos_auditados = total_articulos_auditados - 1,
                                   articulos_stock = articulos_stock - 1
                               WHERE id_auditoria = ?";
            } else {
                $sql_up_aud = null;
            }

            if ($sql_up_aud !== null) {
                $stmt_up_aud = mysqli_prepare($conexion, $sql_up_aud);
                if (!$stmt_up_aud) {
                    throw new Exception('Error al actualizar auditoría: ' . mysqli_error($conexion));
                }
                mysqli_stmt_bind_param($stmt_up_aud, 'i', $id_auditoria);
                if (!mysqli_stmt_execute($stmt_up_aud)) {
                    $error = mysqli_stmt_error($stmt_up_aud);
                    mysqli_stmt_close($stmt_up_aud);
                    throw new Exception('Error al actualizar totales de auditoría: ' . $error);
                }
                mysqli_stmt_close($stmt_up_aud);
            }

            $stmt_del_aud = mysqli_prepare(
                $conexion,
                'DELETE FROM rel_art_auditoria WHERE id_rel_art_aud = ? LIMIT 1'
            );
            if (!$stmt_del_aud) {
                throw new Exception('Error al eliminar relación de auditoría: ' . mysqli_error($conexion));
            }
            mysqli_stmt_bind_param($stmt_del_aud, 'i', $id_rel_art_aud);
            if (!mysqli_stmt_execute($stmt_del_aud)) {
                $error = mysqli_stmt_error($stmt_del_aud);
                mysqli_stmt_close($stmt_del_aud);
                throw new Exception('Error al eliminar artículo de auditoría: ' . $error);
            }
            mysqli_stmt_close($stmt_del_aud);
        }

        $query_update = "UPDATE articulos_venta
                         SET estado = 'retirado',
                             estado_articulo = 'retirado',
                             fecha_retirado = NOW(),
                             motivo_retirado = ?,
                             update_register = CURDATE()
                         WHERE id = ?";
        $stmt_update = mysqli_prepare($conexion, $query_update);
        if (!$stmt_update) {
            throw new Exception('Error al preparar consulta: ' . mysqli_error($conexion));
        }

        mysqli_stmt_bind_param($stmt_update, 'si', $motivo_retirado, $id_articulo);
        if (!mysqli_stmt_execute($stmt_update)) {
            throw new Exception('Error al actualizar artículo: ' . mysqli_stmt_error($stmt_update));
        }
        mysqli_stmt_close($stmt_update);

        $query_update_rel = "
            UPDATE rel_articulos_estados SET
                estado_articulo = 'retirado'
            WHERE rel_id_articulo_venta = ? AND rel_id_sucursal_venta = ?
        ";
        $stmt_update_rel = mysqli_prepare($conexion, $query_update_rel);
        if (!$stmt_update_rel) {
            throw new Exception('Error al preparar actualización de rel_articulos_estados: ' . mysqli_error($conexion));
        }

        mysqli_stmt_bind_param($stmt_update_rel, 'ii', $id_articulo, $id_sucursal);
        if (!mysqli_stmt_execute($stmt_update_rel)) {
            mysqli_stmt_close($stmt_update_rel);
            throw new Exception('Error al actualizar rel_articulos_estados: ' . mysqli_stmt_error($stmt_update_rel));
        }
        mysqli_stmt_close($stmt_update_rel);

        $nombre_sucursal = (string) $id_sucursal;
        $stmt_suc = mysqli_prepare(
            $conexion,
            'SELECT nombre_sucursal FROM sucursal WHERE id_sucursal = ? LIMIT 1'
        );
        if ($stmt_suc) {
            mysqli_stmt_bind_param($stmt_suc, 'i', $id_sucursal);
            mysqli_stmt_execute($stmt_suc);
            $row_suc = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_suc));
            mysqli_stmt_close($stmt_suc);
            if (!empty($row_suc['nombre_sucursal'])) {
                $nombre_sucursal = (string) $row_suc['nombre_sucursal'];
            }
        }

        $id_usuario = (int) ($_SESSION['usuario_id'] ?? 0);
        $nombre_usuario = (string) $id_usuario;
        if ($id_usuario > 0) {
            $stmt_usr = mysqli_prepare(
                $conexion,
                'SELECT usuario FROM usuarios WHERE id_usuario = ? LIMIT 1'
            );
            if ($stmt_usr) {
                mysqli_stmt_bind_param($stmt_usr, 'i', $id_usuario);
                mysqli_stmt_execute($stmt_usr);
                $row_usr = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_usr));
                mysqli_stmt_close($stmt_usr);
                if (!empty($row_usr['usuario'])) {
                    $nombre_usuario = (string) $row_usr['usuario'];
                }
            }
        }

        $accion_trazabilidad = 'retirado';
        $comentarios_trazabilidad = 'Artículo retirado en la sucursal ' . $nombre_sucursal
            . ' por el usuario ' . $nombre_usuario
            . '. Motivo: ' . $motivo_retirado;

        try {
            trazabilidad_articulos_venta(
                0,
                $_SESSION['usuario_id'],
                $accion_trazabilidad,
                $comentarios_trazabilidad,
                $id_sucursal,
                $id_articulo,
                0
            );
        } catch (Exception $e) {
            error_log('Error al insertar trazabilidad retirado: ' . $e->getMessage());
        }

        mysqli_commit($conexion);

        echo json_encode([
            'success' => true,
            'message' => 'Artículo retirado correctamente',
        ]);
    } catch (Exception $e) {
        mysqli_rollback($conexion);
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage(),
        ]);
    }

    mysqli_close($conexion);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
    ]);
}
