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
            'id_cliente', 'nombre', 'apellido', 'tipo_identificacion', 'identificacion', 
            'nacionalidad', 'f_nacimiento', 'telefono', 'sucursal', 'f_vencimiento',
            'direccion', 'pais', 'c_provincia', 'c_poblacion', 'codigo_postal', 'sexo'
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
        $tipo_identificacion = trim($_POST['tipo_identificacion']);
        $identificacion = trim($_POST['identificacion']);
        $id_nacionalidad = (int)$_POST['nacionalidad']; // ID de la nacionalidad del Select2
        $f_nacimiento = $_POST['f_nacimiento'];
        $telefono = trim($_POST['telefono']);
        $sucursal = (int)$_POST['sucursal'];
        $f_vencimiento = $_POST['f_vencimiento'];
        $direccion = trim($_POST['direccion']);
        $rel_id_pais = (int)$_POST['pais']; // ID del país
        $rel_id_provincia = (int)$_POST['c_provincia']; // ID de la provincia
        $rel_id_poblacion = (int)$_POST['c_poblacion']; // ID de la población
        $codigo_postal = trim($_POST['codigo_postal']);
        $email = isset($_POST['email']) ? trim($_POST['email']) : '';
        $observaciones = isset($_POST['observaciones']) ? trim($_POST['observaciones']) : '';
        $sexo = trim($_POST['sexo']);
        
        // Obtener nombres de texto de provincia y población
        $c_provincia = '';
        $c_poblacion = '';
        
        if ($rel_id_provincia > 0) {
            $query_prov = "SELECT nombreProvince FROM provincias WHERE id_province = ?";
            $stmt_prov = mysqli_prepare($conexion, $query_prov);
            mysqli_stmt_bind_param($stmt_prov, 'i', $rel_id_provincia);
            mysqli_stmt_execute($stmt_prov);
            $result_prov = mysqli_stmt_get_result($stmt_prov);
            if ($row_prov = mysqli_fetch_assoc($result_prov)) {
                $c_provincia = $row_prov['nombreProvince'];
            }
            mysqli_stmt_close($stmt_prov);
        }
        
        if ($rel_id_poblacion > 0) {
            $query_pob = "SELECT poblacion FROM poblacion WHERE idpoblacion = ?";
            $stmt_pob = mysqli_prepare($conexion, $query_pob);
            mysqli_stmt_bind_param($stmt_pob, 'i', $rel_id_poblacion);
            mysqli_stmt_execute($stmt_pob);
            $result_pob = mysqli_stmt_get_result($stmt_pob);
            if ($row_pob = mysqli_fetch_assoc($result_pob)) {
                $c_poblacion = $row_pob['poblacion'];
            }
            mysqli_stmt_close($stmt_pob);
        }
        
        // Obtener el nombre de la nacionalidad a partir del ID
        if (!empty($id_nacionalidad) && $id_nacionalidad > 0) {
            $query_nacionalidad = "SELECT nombre_nacionalidad FROM nacionalidades WHERE id = ?";
            $stmt_nacionalidad = mysqli_prepare($conexion, $query_nacionalidad);
            mysqli_stmt_bind_param($stmt_nacionalidad, 'i', $id_nacionalidad);
            mysqli_stmt_execute($stmt_nacionalidad);
            $result_nacionalidad = mysqli_stmt_get_result($stmt_nacionalidad);
            
            if (mysqli_num_rows($result_nacionalidad) === 0) {
                throw new Exception("Nacionalidad no válida");
            }
            
            $row_nacionalidad = mysqli_fetch_assoc($result_nacionalidad);
            $nombre_nacionalidad = $row_nacionalidad['nombre_nacionalidad'];
            mysqli_stmt_close($stmt_nacionalidad);
        } else {
            // Si no se seleccionó nacionalidad, usar valores por defecto
            $nombre_nacionalidad = '';
            $id_nacionalidad = null;
        }
        
        // Obtener el ID del tipo de identificación
        $tipo_identificacion_id = 0;
        if (!empty($tipo_identificacion)) {
            $query_tipo_id = "SELECT id FROM tipo_identificacion WHERE tipo = ? LIMIT 1";
            $stmt_tipo_id = mysqli_prepare($conexion, $query_tipo_id);
            mysqli_stmt_bind_param($stmt_tipo_id, 's', $tipo_identificacion);
            mysqli_stmt_execute($stmt_tipo_id);
            $result_tipo_id = mysqli_stmt_get_result($stmt_tipo_id);
            if ($row_tipo_id = mysqli_fetch_assoc($result_tipo_id)) {
                $tipo_identificacion_id = $row_tipo_id['id'];
            }
            mysqli_stmt_close($stmt_tipo_id);
        }
        
        // Verificar que la identificación no exista ya en otro cliente
        $query_check = "SELECT id_cliente FROM clientes WHERE tipo_identificacion = ? AND identificacion = ? AND id_cliente != ?";
        $stmt_check = mysqli_prepare($conexion, $query_check);
        mysqli_stmt_bind_param($stmt_check, 'ssi', $tipo_identificacion, $identificacion, $id_cliente);
        mysqli_stmt_execute($stmt_check);
        $result_check = mysqli_stmt_get_result($stmt_check);
        
        if (mysqli_num_rows($result_check) > 0) {
            throw new Exception("Ya existe otro cliente con esta identificación");
        }
        mysqli_stmt_close($stmt_check);
        
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
                telefono = ?,
                sucursal = ?
            WHERE id_cliente = ?
        ";
        
        $stmt_clientes = mysqli_prepare($conexion, $query_clientes);
        mysqli_stmt_bind_param($stmt_clientes, 'ssisssissi', 
            $nombre, $apellido, $tipo_identificacion, $tipo_identificacion_id, $identificacion,
            $nombre_nacionalidad, $id_nacionalidad, $telefono, $sucursal, $id_cliente
        );
        
        if (!mysqli_stmt_execute($stmt_clientes)) {
            throw new Exception("Error al actualizar en clientes: " . mysqli_stmt_error($stmt_clientes));
        }
        
        mysqli_stmt_close($stmt_clientes);
        
        // UPDATE 2: Tabla datos_clientes
        $query_datos = "
            UPDATE datos_clientes SET
                direccion = ?,
                c_provincia = ?,
                c_poblacion = ?,
                codigo_postal = ?,
                email = ?,
                observaciones = ?,
                sexo = ?,
                id_nacionalidad_rel = ?,
                f_nacimiento = ?,
                f_vencimiento = ?,
                rel_id_pais = ?,
                rel_id_provincia = ?,
                rel_id_poblacion = ?
            WHERE rel_id_cliente = ?
        ";
        
        $stmt_datos = mysqli_prepare($conexion, $query_datos);
        // Usar 0 si id_nacionalidad es null para evitar problemas con bind_param
        $id_nacionalidad_param = $id_nacionalidad ?? 0;
        
        mysqli_stmt_bind_param($stmt_datos, 'ssssssssssiiis', 
            $direccion, $c_provincia, $c_poblacion,
            $codigo_postal, $email, $observaciones, $sexo, $id_nacionalidad_param, 
            $f_nacimiento, $f_vencimiento, $rel_id_pais, $rel_id_provincia, $rel_id_poblacion, $id_cliente
        );
        
        if (!mysqli_stmt_execute($stmt_datos)) {
            throw new Exception("Error al actualizar en datos_clientes: " . mysqli_stmt_error($stmt_datos));
        }
        
        mysqli_stmt_close($stmt_datos);
        
        // Confirmar transacción
        mysqli_commit($conexion);
        
        // Respuesta de éxito
        echo json_encode(array(
            'success' => true,
            'message' => "Cliente '" . $nombre . " " . $apellido . "' actualizado exitosamente",
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
