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
        $campos_obligatorios = array(
            'precio_venta', 'descripcion', 'system_codigo_regimen', 'tipo_iva_articulo'
        );

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

        $tipo_iva_articulo = trim($_POST['tipo_iva_articulo']);
        $tipos_iva_validos = array('IVA', 'IPSI', 'IGIC', 'OTHER');
        if (!in_array($tipo_iva_articulo, $tipos_iva_validos, true)) {
            throw new Exception('Tipo de IVA no válido');
        }

        $precio_coste = isset($_POST['precio_coste']) ? (float) $_POST['precio_coste'] : 0;
        if (empty($precio_coste)) {
            $precioCosteParset = $precio_venta * 30 / 100;
            $precio_coste = number_format($precio_venta - $precioCosteParset, 2, '.', '');
        } else {
            $precio_coste = number_format($precio_coste, 2, '.', '');
        }

        $observaciones = isset($_POST['observaciones']) ? trim($_POST['observaciones']) : '';
        $id_usuario = isset($_SESSION['usuario_id']) ? (int) $_SESSION['usuario_id'] : 0;
        if (!$id_usuario) {
            throw new Exception('No se pudo determinar el usuario');
        }

        $empresa_id_rel = defined('APP_ID') ? (int) APP_ID : 0;
        if ($empresa_id_rel <= 0) {
            $rsEmp = mysqli_query($conexion, 'SELECT id_empresa FROM empresas ORDER BY id_empresa ASC LIMIT 1');
            $rowEmp = $rsEmp ? mysqli_fetch_assoc($rsEmp) : null;
            $empresa_id_rel = (int) ($rowEmp['id_empresa'] ?? 0);
        }
        if ($empresa_id_rel <= 0) {
            throw new Exception('No se pudo determinar la empresa');
        }

        $estado = 'noetiquetado_c';
        $estado_articulo = 'No etiquetado creado';
        $stmt_est = mysqli_prepare(
            $conexion,
            'SELECT texto_estado_articulo FROM estados_articulos WHERE var_estado_articulo = ? LIMIT 1'
        );
        if ($stmt_est) {
            mysqli_stmt_bind_param($stmt_est, 's', $estado);
            mysqli_stmt_execute($stmt_est);
            $res_est = mysqli_stmt_get_result($stmt_est);
            $row_est = $res_est ? mysqli_fetch_assoc($res_est) : null;
            if (!empty($row_est['texto_estado_articulo'])) {
                $estado_articulo = (string) $row_est['texto_estado_articulo'];
            }
            mysqli_stmt_close($stmt_est);
        }

        $categoria_articulo = 0;
        $precio_venta_fmt = number_format($precio_venta, 2, '.', '');
        $precio_sin_iva = $precio_venta_fmt;
        $precio_coste_calculado = $precio_coste;

        $query_insert = '
            INSERT INTO articulos (
                empresa_id_rel,
                descripcion,
                precio,
                estado,
                observaciones,
                precio_coste,
                creado_por,
                fecha_alta,
                update_register,
                estado_articulo,
                categoria_articulo,
                tipo_iva_articulo,
                system_codigo_regimen,
                precio_sin_iva,
                precio_coste_calculado
            ) VALUES (?, ?, ?, ?, ?, ?, ?, CURDATE(), CURDATE(), ?, ?, ?, ?, ?, ?)
        ';

        $stmt_insert = mysqli_prepare($conexion, $query_insert);
        if (!$stmt_insert) {
            throw new Exception('Error al preparar el alta del artículo: ' . mysqli_error($conexion));
        }

        mysqli_stmt_bind_param(
            $stmt_insert,
            'isdsssisissdd',
            $empresa_id_rel,
            $descripcion,
            $precio_venta_fmt,
            $estado,
            $observaciones,
            $precio_coste,
            $id_usuario,
            $estado_articulo,
            $categoria_articulo,
            $tipo_iva_articulo,
            $system_codigo_regimen,
            $precio_sin_iva,
            $precio_coste_calculado
        );

        if (!mysqli_stmt_execute($stmt_insert)) {
            throw new Exception('Error al insertar artículo: ' . mysqli_stmt_error($stmt_insert));
        }

        $last_id = (int) mysqli_insert_id($conexion);
        mysqli_stmt_close($stmt_insert);

        $id_venta_traz = 0;
        $identificador_venta_traz = 0;
        $accion_trazabilidad_venta = 'creado';
        $comentarios_trazabilidad_venta = 'Artículo creado por el usuario ' . $id_usuario;
        $stmt_traz = mysqli_prepare(
            $conexion,
            'INSERT INTO trazabilidad_articulos_venta (
                id_venta,
                identificador_venta,
                fecha_accion,
                usuario_accion,
                accion_trazabilidad,
                comentarios_accion,
                rel_id_empresa,
                id_articulo
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
                $last_id
            );
            mysqli_stmt_execute($stmt_traz);
            mysqli_stmt_close($stmt_traz);
        }

        $precio_anterior = 0;
        $tipo_registro = 'create';
        $stmt_hist = mysqli_prepare(
            $conexion,
            'INSERT INTO historico_precio_articulos_venta (
                rel_sku_historico,
                precio_anterior,
                precio_actual,
                actualizado_por,
                rel_id_empresa,
                fecha_actualizacion,
                tipo_registro
            ) VALUES (?, ?, ?, ?, ?, CURDATE(), ?)'
        );
        if ($stmt_hist) {
            mysqli_stmt_bind_param(
                $stmt_hist,
                'iddiis',
                $last_id,
                $precio_anterior,
                $precio_venta_fmt,
                $id_usuario,
                $empresa_id_rel,
                $tipo_registro
            );
            mysqli_stmt_execute($stmt_hist);
            mysqli_stmt_close($stmt_hist);
        }

        mysqli_commit($conexion);

        echo json_encode(array(
            'success' => true,
            'message' => 'Artículo creado correctamente',
            'id_articulo' => $last_id
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
