<?php
/**
 * Actualizar cliente (POST). Lógica central del módulo modulo_cliente_form.
 */
ob_start();
require_once __DIR__ . '/../../../include/session.php';
require_once __DIR__ . '/../../../include/functions.php';
ob_end_clean();

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Método no permitido'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $conexion = conectar_bd();
    mysqli_begin_transaction($conexion);

    try {
        $campos_obligatorios = [
            'id_cliente', 'nombre', 'apellido', 'tipo_identificacion', 'identificacion',
            'nacionalidad', 'f_nacimiento', 'telefono', 'f_vencimiento', 'sexo', 'sucursal',
        ];

        foreach ($campos_obligatorios as $campo) {
            if (!isset($_POST[$campo]) || trim((string) $_POST[$campo]) === '') {
                throw new Exception("El campo '" . $campo . "' es obligatorio");
            }
        }

        $id_cliente = (int) $_POST['id_cliente'];
        $nombre = trim($_POST['nombre']);
        $apellido = trim($_POST['apellido']);
        $identificacion = trim($_POST['identificacion']);
        $f_nacimiento = $_POST['f_nacimiento'];
        $telefono = trim($_POST['telefono']);
        $f_vencimiento = $_POST['f_vencimiento'];
        $sucursal = (int) $_POST['sucursal'];
        $email = isset($_POST['email']) ? trim($_POST['email']) : '';
        $observaciones = isset($_POST['observaciones']) ? trim($_POST['observaciones']) : '';
        $sexo = trim($_POST['sexo']);
        $nacionalidad_id = (int) $_POST['nacionalidad'];
        $tipo_identificacion = (int) $_POST['tipo_identificacion'];
        $nacionalidad_texto = obtenerTextoNacionalidad($conexion, $nacionalidad_id);
        $tipo_identificacion_texto = obtenerTextoTipoIdentificacion($conexion, $tipo_identificacion);

        $query_clientes = "
            UPDATE clientes SET
                nombre = ?,
                apellido = ?,
                tipo_identificacion = ?,
                tipo_identificacion_id = ?,
                identificacion = ?,
                nacionalidad = ?,
                nacionalidad_id = ?,
                telefono = ?,
                sucursal = ?
            WHERE id_cliente = ?
        ";

        $stmt_clientes = mysqli_prepare($conexion, $query_clientes);
        mysqli_stmt_bind_param(
            $stmt_clientes,
            'sssissisii',
            $nombre,
            $apellido,
            $tipo_identificacion_texto,
            $tipo_identificacion,
            $identificacion,
            $nacionalidad_texto,
            $nacionalidad_id,
            $telefono,
            $sucursal,
            $id_cliente
        );

        if (!mysqli_stmt_execute($stmt_clientes)) {
            throw new Exception('Error al actualizar en clientes: ' . mysqli_stmt_error($stmt_clientes));
        }
        mysqli_stmt_close($stmt_clientes);

        $query_datos = "
            UPDATE datos_clientes SET
                email = ?,
                observaciones = ?,
                sexo = ?,
                f_nacimiento = ?,
                f_vencimiento = ?
            WHERE rel_id_cliente = ?
        ";

        $stmt_datos = mysqli_prepare($conexion, $query_datos);
        mysqli_stmt_bind_param(
            $stmt_datos,
            'sssssi',
            $email,
            $observaciones,
            $sexo,
            $f_nacimiento,
            $f_vencimiento,
            $id_cliente
        );

        if (!mysqli_stmt_execute($stmt_datos)) {
            throw new Exception('Error al actualizar en datos_clientes: ' . mysqli_stmt_error($stmt_datos));
        }

        $id_type_direccion = $id_cliente;
        $type_direccion = 'clientes';
        require_once __DIR__ . '/../../universal/direcciones/actualizar_direccion.php';

        mysqli_stmt_close($stmt_datos);
        mysqli_commit($conexion);

        $texto_action_user = "$usuario actualizó el cliente Nº '$id_cliente'";
        $id_action_user = '33';
        $relItemAction = $_SESSION['relItemAction'];
        registrar_accion_usuario($usuario_id, $id_action_user, $texto_action_user, $usuario_sucursal, $relItemAction);
        $_SESSION['relItemAction'] = 'false';

        require_once __DIR__ . '/../../clientes/editar/procesar_editar_cliente_figueredo.php';

        $redirect = isset($_POST['redirect']) && trim((string) $_POST['redirect']) !== ''
            ? trim((string) $_POST['redirect'])
            : 'cliente.php?id=' . $id_cliente;

        echo json_encode([
            'success' => true,
            'message' => "Cliente '" . $nombre . ' ' . $apellido . "' actualizado exitosamente",
            'id_cliente' => $id_cliente,
            'redirect' => $redirect,
        ], JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        mysqli_rollback($conexion);
        throw $e;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
} finally {
    if (isset($conexion)) {
        mysqli_close($conexion);
    }
}
