<?php
require_once '../../../include/session.php';
require_once '../../../include/functions.php';

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
            'nombre', 'apellido', 'tipo_identificacion', 'identificacion', 
            'nacionalidad', 'f_nacimiento', 'telefono', 'f_vencimiento', 'sexo'
        );
        
        foreach ($campos_obligatorios as $campo) {
            if (!isset($_POST[$campo]) || trim($_POST[$campo]) === '') {
                throw new Exception("El campo '" . $campo . "' es obligatorio");
            }
        }
        
        // Sanitizar datos
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
        // Verificar que la identificación no exista ya
        $query_check = "SELECT id_cliente FROM clientes WHERE identificacion = ?";
        $stmt_check = mysqli_prepare($conexion, $query_check);
        mysqli_stmt_bind_param($stmt_check, 's',  $identificacion);
        mysqli_stmt_execute($stmt_check);
        $result_check = mysqli_stmt_get_result($stmt_check);
        
        if (mysqli_num_rows($result_check) > 0) {
            throw new Exception("Ya existe un cliente con esta identificación");
        }
        mysqli_stmt_close($stmt_check);

        // INSERT 1: Tabla clientes
        $query_clientes = "
            INSERT INTO clientes (
                nombre, apellido, tipo_identificacion, tipo_identificacion_id, identificacion, nacionalidad, nacionalidad_id, telefono, creado_por, f_alta
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, CURDATE())
        ";
        
        $stmt_clientes = mysqli_prepare($conexion, $query_clientes);
        mysqli_stmt_bind_param($stmt_clientes, 'ssissisii', $nombre, $apellido, $tipo_identificacion_texto, $tipo_identificacion, $identificacion, $nacionalidad_texto, $nacionalidad_id, $telefono, $usuario_id);
        
        if (!mysqli_stmt_execute($stmt_clientes)) {
            throw new Exception("Error al insertar en clientes: " . mysqli_stmt_error($stmt_clientes));
        }
        
        $id_cliente = mysqli_insert_id($conexion);
        mysqli_stmt_close($stmt_clientes);
        
        // INSERT 2: Tabla datos_clientes
        $query_datos = "
            INSERT INTO datos_clientes (
                rel_id_cliente, email, observaciones, sexo, f_nacimiento, f_vencimiento
            ) VALUES (?, ?, ?, ?, ?, ?)
        ";
        
        $stmt_datos = mysqli_prepare($conexion, $query_datos);
        mysqli_stmt_bind_param($stmt_datos, 'isssss', 
            $id_cliente, $email, $observaciones, $sexo, $f_nacimiento, $f_vencimiento
        );
        
        if (!mysqli_stmt_execute($stmt_datos)) {
            throw new Exception("Error al insertar en datos_clientes: " . mysqli_stmt_error($stmt_datos));
        }

        // INSERTO LA DIRECCION LLAMANDO A LA FUNCION INSERTAR DIRECCION
        $id_type_direccion = $id_cliente;
        $type_direccion = 'clientes';
        require_once '../../../parts/universal/direcciones/insertar_direccion.php';        
        
        mysqli_stmt_close($stmt_datos);
        
        // Confirmar transacción
        mysqli_commit($conexion);

        // REGISTRAR ACCION DEL USUARIO
        
        $texto_action_user = "$usuario creó el cliente Nº '$id_cliente'";
        $id_action_user = "34";
        $relItemAction = $_SESSION['relItemAction'];
        registrar_accion_usuario($usuario_id, $id_action_user, $texto_action_user, $id_sucursal, $relItemAction);
        $_SESSION['relItemAction'] = "false";
                
        // Respuesta de éxito
        echo json_encode(array(
            'success' => true,
            'message' => "Cliente '" . $nombre . " " . $apellido . "' creado exitosamente",
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
