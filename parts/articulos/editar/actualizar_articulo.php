<?php
require_once '../../../include/session.php';

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(array('success' => false, 'error' => 'Método no permitido'));
        exit;
    }

    $conexion = conectar_bd();
    mysqli_begin_transaction($conexion);

    try {
        if (!isset($_POST['id_articulo']) || empty($_POST['id_articulo'])) {
            throw new Exception('ID de artículo es requerido');
        }

        $id_articulo = (int) $_POST['id_articulo'];

        $campos_obligatorios = array('precio_venta', 'descripcion', 'system_codigo_regimen');
        foreach ($campos_obligatorios as $campo) {
            if (!isset($_POST[$campo]) || trim($_POST[$campo]) === '') {
                throw new Exception("El campo '" . $campo . "' es obligatorio");
            }
        }

        $precio_venta = (float) $_POST['precio_venta'];
        $descripcion = trim($_POST['descripcion']);
        $system_codigo_regimen = trim($_POST['system_codigo_regimen']);
        $regimenes_validos = array('REBU', 'INVERSION', 'GENERAL');
        if (!in_array($system_codigo_regimen, $regimenes_validos, true)) {
            throw new Exception('Régimen fiscal no válido');
        }

        $precio_coste = isset($_POST['precio_coste']) ? (float) $_POST['precio_coste'] : 0;
        if (empty($precio_coste)) {
            $precioCosteParset = $precio_venta * 30 / 100;
            $precio_coste = number_format($precio_venta - $precioCosteParset, 2, '.', '');
        } else {
            $precio_coste = number_format($precio_coste, 2, '.', '');
        }

        $observaciones = isset($_POST['observaciones']) ? trim($_POST['observaciones']) : '';

        $query_check = 'SELECT sku, estado, precio, tipo_iva_articulo, empresa_id_rel FROM articulos WHERE sku = ? LIMIT 1';
        $stmt_check = mysqli_prepare($conexion, $query_check);
        mysqli_stmt_bind_param($stmt_check, 'i', $id_articulo);
        mysqli_stmt_execute($stmt_check);
        $result_check = mysqli_stmt_get_result($stmt_check);

        if (mysqli_num_rows($result_check) == 0) {
            mysqli_stmt_close($stmt_check);
            throw new Exception('El artículo no existe');
        }

        $articulo_actual = mysqli_fetch_assoc($result_check);
        $precio_anterior = (float) $articulo_actual['precio'];
        $tipo_iva_articulo = (string) ($articulo_actual['tipo_iva_articulo'] ?? 'IVA');
        $empresa_id_rel = (int) ($articulo_actual['empresa_id_rel'] ?? 0);
        mysqli_stmt_close($stmt_check);

        if (isset($_POST['tipo_iva_articulo']) && trim($_POST['tipo_iva_articulo']) !== '') {
            $tipo_iva_post = trim($_POST['tipo_iva_articulo']);
            $tipos_iva_validos = array('IVA', 'IPSI', 'IGIC', 'OTHER');
            if (in_array($tipo_iva_post, $tipos_iva_validos, true)) {
                $tipo_iva_articulo = $tipo_iva_post;
            }
        }

        $precio_venta_fmt = number_format($precio_venta, 2, '.', '');
        $precio_sin_iva = $precio_venta_fmt;
        $precio_coste_calculado = $precio_coste;

        $query_update = '
            UPDATE articulos SET
                descripcion = ?,
                precio = ?,
                precio_coste = ?,
                observaciones = ?,
                system_codigo_regimen = ?,
                tipo_iva_articulo = ?,
                precio_sin_iva = ?,
                precio_coste_calculado = ?,
                update_register = CURDATE()
            WHERE sku = ?
        ';

        $stmt_update = mysqli_prepare($conexion, $query_update);
        if (!$stmt_update) {
            throw new Exception('Error al preparar la actualización: ' . mysqli_error($conexion));
        }

        mysqli_stmt_bind_param(
            $stmt_update,
            'sddsssddi',
            $descripcion,
            $precio_venta_fmt,
            $precio_coste,
            $observaciones,
            $system_codigo_regimen,
            $tipo_iva_articulo,
            $precio_sin_iva,
            $precio_coste_calculado,
            $id_articulo
        );

        if (!mysqli_stmt_execute($stmt_update)) {
            throw new Exception('Error al actualizar artículo: ' . mysqli_stmt_error($stmt_update));
        }
        mysqli_stmt_close($stmt_update);

        $id_usuario = isset($_SESSION['usuario_id']) ? (int) $_SESSION['usuario_id'] : 0;
        $id_venta_traz = 0;
        $identificador_venta_traz = 0;
        $accion_trazabilidad_venta = 'editado';
        $comentarios_trazabilidad_venta = 'Artículo editado por el usuario ' . $id_usuario;
        $stmt_traz = mysqli_prepare(
            $conexion,
            'INSERT INTO trazabilidad_articulos_venta (
                id_venta, identificador_venta, fecha_accion, usuario_accion,
                accion_trazabilidad, comentarios_accion, rel_id_empresa, id_articulo
            ) VALUES (?, ?, NOW(), ?, ?, ?, ?, ?)'
        );
        if ($stmt_traz) {
            mysqli_stmt_bind_param(
                $stmt_traz,
                'iiissii',
                $id_venta_traz,
                $identificador_venta_traz,
                $id_usuario,
                $accion_trazabilidad_venta,
                $comentarios_trazabilidad_venta,
                $empresa_id_rel,
                $id_articulo
            );
            mysqli_stmt_execute($stmt_traz);
            mysqli_stmt_close($stmt_traz);
        }

        if ($precio_anterior != $precio_venta) {
            $estado_nuevo = 'noetiquetado_u';
            $estado_articulo_txt = 'No etiqeutado editado';
            $stmt_est = mysqli_prepare(
                $conexion,
                'SELECT texto_estado_articulo FROM estados_articulos WHERE var_estado_articulo = ? LIMIT 1'
            );
            if ($stmt_est) {
                mysqli_stmt_bind_param($stmt_est, 's', $estado_nuevo);
                mysqli_stmt_execute($stmt_est);
                $res_est = mysqli_stmt_get_result($stmt_est);
                $row_est = $res_est ? mysqli_fetch_assoc($res_est) : null;
                if (!empty($row_est['texto_estado_articulo'])) {
                    $estado_articulo_txt = (string) $row_est['texto_estado_articulo'];
                }
                mysqli_stmt_close($stmt_est);
            }

            $stmt_estado = mysqli_prepare(
                $conexion,
                'UPDATE articulos SET estado = ?, estado_articulo = ?, update_register = CURDATE() WHERE sku = ?'
            );
            if ($stmt_estado) {
                mysqli_stmt_bind_param($stmt_estado, 'ssi', $estado_nuevo, $estado_articulo_txt, $id_articulo);
                mysqli_stmt_execute($stmt_estado);
                mysqli_stmt_close($stmt_estado);
            }

            $accion_precio = 'cambio_precio';
            $comentarios_precio = 'Cambio de precio: de ' . number_format($precio_anterior, 2, ',', '.') . ' € a ' . number_format($precio_venta, 2, ',', '.') . ' € por el usuario ' . $id_usuario;
            $stmt_traz_p = mysqli_prepare(
                $conexion,
                'INSERT INTO trazabilidad_articulos_venta (
                    id_venta, identificador_venta, fecha_accion, usuario_accion,
                    accion_trazabilidad, comentarios_accion, rel_id_empresa, id_articulo
                ) VALUES (?, ?, NOW(), ?, ?, ?, ?, ?)'
            );
            if ($stmt_traz_p) {
                mysqli_stmt_bind_param(
                    $stmt_traz_p,
                    'iiissii',
                    $id_venta_traz,
                    $identificador_venta_traz,
                    $id_usuario,
                    $accion_precio,
                    $comentarios_precio,
                    $empresa_id_rel,
                    $id_articulo
                );
                mysqli_stmt_execute($stmt_traz_p);
                mysqli_stmt_close($stmt_traz_p);
            }

            $tipo_registro = 'update';
            $stmt_hist = mysqli_prepare(
                $conexion,
                'INSERT INTO historico_precio_articulos_venta (
                    rel_sku_historico, precio_anterior, precio_actual, actualizado_por,
                    rel_id_empresa, fecha_actualizacion, tipo_registro
                ) VALUES (?, ?, ?, ?, ?, CURDATE(), ?)'
            );
            if ($stmt_hist) {
                mysqli_stmt_bind_param(
                    $stmt_hist,
                    'iddiis',
                    $id_articulo,
                    $precio_anterior,
                    $precio_venta_fmt,
                    $id_usuario,
                    $empresa_id_rel,
                    $tipo_registro
                );
                mysqli_stmt_execute($stmt_hist);
                mysqli_stmt_close($stmt_hist);
            }
        }

        mysqli_commit($conexion);

        echo json_encode(array(
            'success' => true,
            'message' => 'Artículo actualizado correctamente',
            'id_articulo' => $id_articulo
        ));
    } catch (Exception $e) {
        mysqli_rollback($conexion);
        http_response_code(500);
        echo json_encode(array(
            'success' => false,
            'error' => $e->getMessage()
        ));
    }

    mysqli_close($conexion);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(array(
        'success' => false,
        'error' => $e->getMessage()
    ));
}
