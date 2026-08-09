<?php
/**
 * Crear cliente (POST). Lógica central del módulo modulo_cliente_form.
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
            'nombre', 'apellido', 'tipo_identificacion', 'identificacion',
            'nacionalidad', 'f_nacimiento', 'telefono', 'f_vencimiento', 'sexo',
        ];

        foreach ($campos_obligatorios as $campo) {
            if (!isset($_POST[$campo]) || trim((string) $_POST[$campo]) === '') {
                throw new Exception("El campo '" . $campo . "' es obligatorio");
            }
        }

        $nombre = trim($_POST['nombre']);
        $apellido = trim($_POST['apellido']);
        $identificacion = trim($_POST['identificacion']);
        $f_nacimiento = $_POST['f_nacimiento'];
        $telefono = trim($_POST['telefono']);
        $f_vencimiento = $_POST['f_vencimiento'];
        $email = isset($_POST['email']) ? trim($_POST['email']) : '';
        $observaciones = isset($_POST['observaciones']) ? trim($_POST['observaciones']) : '';
        $sexo = trim($_POST['sexo']);
        $nacionalidad_id = (int) $_POST['nacionalidad'];
        $tipo_identificacion = (int) $_POST['tipo_identificacion'];

        if (isset($_POST['sucursal_cliente']) && trim((string) $_POST['sucursal_cliente']) !== '') {
            $sucursal = (int) $_POST['sucursal_cliente'];
        } elseif (isset($_POST['sucursal']) && trim((string) $_POST['sucursal']) !== '') {
            $sucursal = (int) $_POST['sucursal'];
        } elseif (!empty($id_sucursal)) {
            $sucursal = (int) $id_sucursal;
        } else {
            throw new Exception("El campo 'sucursal' es obligatorio");
        }

        $nacionalidad_texto = obtenerTextoNacionalidad($conexion, $nacionalidad_id);
        $tipo_identificacion_texto = obtenerTextoTipoIdentificacion($conexion, $tipo_identificacion);

        $query_check = 'SELECT id_cliente FROM clientes WHERE identificacion = ?';
        $stmt_check = mysqli_prepare($conexion, $query_check);
        mysqli_stmt_bind_param($stmt_check, 's', $identificacion);
        mysqli_stmt_execute($stmt_check);
        $result_check = mysqli_stmt_get_result($stmt_check);

        if (mysqli_num_rows($result_check) > 0) {
            throw new Exception('Ya existe un cliente con esta identificación');
        }
        mysqli_stmt_close($stmt_check);

        $query_clientes = "
            INSERT INTO clientes (
                nombre, apellido, sucursal, tipo_identificacion, tipo_identificacion_id,
                identificacion, nacionalidad, nacionalidad_id, telefono, creado_por, f_alta
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURDATE())
        ";

        $stmt_clientes = mysqli_prepare($conexion, $query_clientes);
        mysqli_stmt_bind_param(
            $stmt_clientes,
            'ssisissisi',
            $nombre,
            $apellido,
            $sucursal,
            $tipo_identificacion_texto,
            $tipo_identificacion,
            $identificacion,
            $nacionalidad_texto,
            $nacionalidad_id,
            $telefono,
            $usuario_id
        );

        if (!mysqli_stmt_execute($stmt_clientes)) {
            throw new Exception('Error al insertar en clientes: ' . mysqli_stmt_error($stmt_clientes));
        }

        $id_cliente = mysqli_insert_id($conexion);
        mysqli_stmt_close($stmt_clientes);

        $query_datos = "
            INSERT INTO datos_clientes (
                rel_id_cliente, email, observaciones, sexo, f_nacimiento, f_vencimiento
            ) VALUES (?, ?, ?, ?, ?, ?)
        ";

        $stmt_datos = mysqli_prepare($conexion, $query_datos);
        mysqli_stmt_bind_param(
            $stmt_datos,
            'isssss',
            $id_cliente,
            $email,
            $observaciones,
            $sexo,
            $f_nacimiento,
            $f_vencimiento
        );

        if (!mysqli_stmt_execute($stmt_datos)) {
            throw new Exception('Error al insertar en datos_clientes: ' . mysqli_stmt_error($stmt_datos));
        }

        $id_type_direccion = $id_cliente;
        $type_direccion = 'clientes';
        require_once __DIR__ . '/../../universal/direcciones/insertar_direccion.php';

        mysqli_stmt_close($stmt_datos);
        mysqli_commit($conexion);

        $texto_action_user = "$usuario creó el cliente Nº '$id_cliente'";
        $id_action_user = '34';
        $relItemAction = $_SESSION['relItemAction'];
        registrar_accion_usuario($usuario_id, $id_action_user, $texto_action_user, $id_sucursal, $relItemAction);
        $_SESSION['relItemAction'] = 'false';

        $redirect = isset($_POST['redirect']) && trim((string) $_POST['redirect']) !== ''
            ? trim((string) $_POST['redirect'])
            : 'cliente.php?id=' . $id_cliente;

        echo json_encode([
            'success' => true,
            'message' => "Cliente '" . $nombre . ' ' . $apellido . "' creado exitosamente",
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
