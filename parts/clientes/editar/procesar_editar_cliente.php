<?php
require_once '../../../include/session.php';
require_once '../../../include/functions.php';

ob_start();  // ← NUEVO: Capturar cualquier output de session.php o functions.php
ob_clean();

header('Content-Type: application/json');

try {
    // Verificar que sea una petición POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(array('error' => 'Método no permitido'));
        exit;
    }
    
    // Conectar BD
    $conexion = conectar_bd();
    
    // Iniciar transacción
    mysqli_begin_transaction($conexion);
    
    try {
        // Validar campos obligatorios
        $campos_obligatorios = array(
            'id_cliente','nombre', 'apellido', 'tipo_identificacion', 'identificacion', 
            'nacionalidad', 'f_nacimiento', 'telefono', 'f_vencimiento', 'sexo'
        );
        
        foreach ($campos_obligatorios as $campo) {
            if (!isset($_POST[$campo]) || trim($_POST[$campo]) === '') {
                throw new Exception("El campo '" . $campo . "' es obligatorio");
            }
        }
        
        // Sanitizar datos
        $id_cliente = (int)$_POST['id_cliente'];
        $nombre = trim($_POST['nombre']);
        $apellido = trim($_POST['apellido']);
        $identificacion = trim($_POST['identificacion']);
        $nacionalidad = trim($_POST['nacionalidad']);
        $f_nacimiento = $_POST['f_nacimiento'];
        $telefono = trim($_POST['telefono']);
        $f_vencimiento = $_POST['f_vencimiento'];
        $email = isset($_POST['email']) ? trim($_POST['email']) : '';
        $observaciones = isset($_POST['observaciones']) ? trim($_POST['observaciones']) : '';
        $sexo = trim($_POST['sexo']);
        $nacionalidad_id = (int)$_POST['nacionalidad'];
        $tipo_identificacion = (int)$_POST['tipo_identificacion'];
        $nacionalidad_texto = obtenerTextoNacionalidad($conexion, $nacionalidad_id);
        $tipo_identificacion_texto = obtenerTextoTipoIdentificacion($conexion, $tipo_identificacion);
        

        // UPDATE 1: Tabla clientes
        $query_clientes = "
            UPDATE clientes SET
                nombre = ?,
                apellido = ?,
                tipo_identificacion = ?,
                tipo_identificacion_id = ?,
                identificacion = ?,
                nacionalidad = ?,
                nacionalidad_id = ?,
                telefono = ?
            WHERE id_cliente = ?
        ";
            
        $stmt_clientes = mysqli_prepare($conexion, $query_clientes);
        mysqli_stmt_bind_param($stmt_clientes, 'sssissisi', $nombre, $apellido, $tipo_identificacion_texto, $tipo_identificacion, $identificacion, $nacionalidad_texto, $nacionalidad_id, $telefono, $id_cliente);
        
        if (!mysqli_stmt_execute($stmt_clientes)) {
            throw new Exception("Error al actualizar en clientes: " . mysqli_stmt_error($stmt_clientes));
        }
        
        mysqli_stmt_close($stmt_clientes);
        
        // UPDATE 2: Tabla datos_clientes
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
        mysqli_stmt_bind_param($stmt_datos, 'sssssi', 
            $email, $observaciones, $sexo, $f_nacimiento, $f_vencimiento, $id_cliente
        );
        
        if (!mysqli_stmt_execute($stmt_datos)) {
            throw new Exception("Error al actualizar en datos_clientes: " . mysqli_stmt_error($stmt_datos));
        }

        // Actualizo LA DIRECCION LLAMANDO A LA FUNCION INSERTAR DIRECCION
        $id_type_direccion = $id_cliente;
        $type_direccion = 'clientes';
        require_once '../../../parts/universal/direcciones/actualizar_direccion.php';        
        
        mysqli_stmt_close($stmt_datos);
        
        // Confirmar transacción
        mysqli_commit($conexion);

        $texto_action_user = "$usuario actualizó el cliente Nº '$id_cliente'";
        $id_action_user = "33";
        $relItemAction = $_SESSION['relItemAction'];
        registrar_accion_usuario($usuario_id, $id_action_user, $texto_action_user, $usuario_sucursal, $relItemAction);
        $_SESSION['relItemAction'] = "false";

        require_once 'procesar_editar_cliente_figueredo.php';
        
        // Respuesta de éxito
        echo json_encode(array(
            'success' => true,
            'message' => "Cliente '" . $nombre . " " . $apellido . "' actualizado exitosamente",
            'id_cliente' => $id_cliente,
            'redirect' => 'cliente.php?id=' . $id_cliente
        ));
        
    } catch (Exception $e) {
        // Revertir transacción en caso de error
        mysqli_rollback($conexion);
        throw $e;
    }
   
    
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(array(
        'success' => false,
        'error' => $e->getMessage()
    ));
} finally {
    if (isset($conexion)) {
        mysqli_close($conexion);
    }
}
    
?>
