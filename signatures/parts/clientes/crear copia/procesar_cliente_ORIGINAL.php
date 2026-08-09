<?php
/**
 * Archivo para procesar la creación de nuevos clientes
 * Inserta en las tablas: clientes y datos_clientes
 * Compatible con PHP 7.0
 */

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
            'nacionalidad_id', 'f_nacimiento', 'telefono', 'sucursal', 'f_vencimiento',
            'direccion', 'c_provincia', 'c_poblacion', 'codigo_postal', 'sexo'
        );
        
        foreach ($campos_obligatorios as $campo) {
            if (!isset($_POST[$campo]) || trim($_POST[$campo]) === '') {
                throw new Exception("El campo '" . $campo . "' es obligatorio");
            }
        }
        
        // Sanitizar datos
        $nombre = trim($_POST['nombre']);
        $apellido = trim($_POST['apellido']);
        $tipo_identificacion = trim($_POST['tipo_identificacion']);
        $identificacion = trim($_POST['identificacion']);
        $nacionalidad_id = (int)$_POST['nacionalidad_id'];
        $f_nacimiento = $_POST['f_nacimiento'];
        $telefono = trim($_POST['telefono']);
        $sucursal = (int)$_POST['sucursal'];
        $f_vencimiento = $_POST['f_vencimiento'];
        $direccion = trim($_POST['direccion']);
        $c_provincia = trim($_POST['c_provincia']);
        $c_poblacion = trim($_POST['c_poblacion']);
        $codigo_postal = trim($_POST['codigo_postal']);
        $email = isset($_POST['email']) ? trim($_POST['email']) : '';
        $observaciones = isset($_POST['observaciones']) ? trim($_POST['observaciones']) : '';
        $sexo = trim($_POST['sexo']);
        
        // Verificar que la identificación no exista ya
        $query_check = "SELECT id_cliente FROM clientes WHERE tipo_identificacion = ? AND identificacion = ?";
        $stmt_check = mysqli_prepare($conexion, $query_check);
        mysqli_stmt_bind_param($stmt_check, 'ss', $tipo_identificacion, $identificacion);
        mysqli_stmt_execute($stmt_check);
        $result_check = mysqli_stmt_get_result($stmt_check);
        
        if (mysqli_num_rows($result_check) > 0) {
            throw new Exception("Ya existe un cliente con esta identificación");
        }
        mysqli_stmt_close($stmt_check);
        
        // INSERT 1: Tabla clientes
        $query_clientes = "
            INSERT INTO clientes (
                nombre, apellido, tipo_identificacion, identificacion, 
                nacionalidad_id, f_nacimiento, telefono, sucursal, 
                f_alta, f_vencimiento
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, CURDATE(), ?)
        ";
        
        $stmt_clientes = mysqli_prepare($conexion, $query_clientes);
        mysqli_stmt_bind_param($stmt_clientes, 'ssssissss', 
            $nombre, $apellido, $tipo_identificacion, $identificacion,
            $nacionalidad_id, $f_nacimiento, $telefono, $sucursal, $f_vencimiento
        );
        
        if (!mysqli_stmt_execute($stmt_clientes)) {
            throw new Exception("Error al insertar en clientes: " . mysqli_stmt_error($stmt_clientes));
        }
        
        $id_cliente = mysqli_insert_id($conexion);
        mysqli_stmt_close($stmt_clientes);
        
        // INSERT 2: Tabla datos_clientes
        $query_datos = "
            INSERT INTO datos_clientes (
                rel_id_cliente, direccion, c_provincia, c_poblacion, 
                codigo_postal, email, observaciones, sexo
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ";
        
        $stmt_datos = mysqli_prepare($conexion, $query_datos);
        mysqli_stmt_bind_param($stmt_datos, 'isssssss', 
            $id_cliente, $direccion, $c_provincia, $c_poblacion,
            $codigo_postal, $email, $observaciones, $sexo
        );
        
        if (!mysqli_stmt_execute($stmt_datos)) {
            throw new Exception("Error al insertar en datos_clientes: " . mysqli_stmt_error($stmt_datos));
        }
        
        mysqli_stmt_close($stmt_datos);
        
        // Confirmar transacción
        mysqli_commit($conexion);
        
        // Respuesta de éxito
        echo json_encode(array(
            'success' => true,
            'message' => "Cliente '" . $nombre . " " . $apellido . "' creado exitosamente",
            'id_cliente' => $id_cliente,
            'redirect' => 'clientes.php'
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
