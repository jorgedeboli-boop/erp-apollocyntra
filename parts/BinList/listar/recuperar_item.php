<?php
require_once '../../../include/session.php';
require_once '../../../include/functions.php';

// Limpiar cualquier output previo
ob_start();
ob_clean();

header('Content-Type: application/json');

try {
    // Verificar que sea una petición POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(array('error' => 'Método no permitido'));
        exit;
    }
    
    // Verificar que el usuario esté autenticado
    if (!usuario_autenticado()) {
        http_response_code(401);
        echo json_encode(array('error' => 'No autorizado'));
        exit;
    }
    
    // Validar campos obligatorios
    if (!isset($_POST['idBinList']) || empty($_POST['idBinList'])) {
        throw new Exception("El ID del BinList es obligatorio");
    }
    
    if (!isset($_POST['itemId']) || empty($_POST['itemId'])) {
        throw new Exception("El ID del item es obligatorio");
    }
    
    if (!isset($_POST['idTypeItem']) || empty($_POST['idTypeItem'])) {
        throw new Exception("El tipo de item es obligatorio");
    }
    
    $idBinList = (int)$_POST['idBinList'];
    $itemId = (int)$_POST['itemId'];
    $idTypeItem = (int)$_POST['idTypeItem'];
    
    // Conectar BD
    $conexion = conectar_bd();
    
    // Comprobar si fhater_item = 0 en itemsSections
    $query_check = "SELECT fhater_item FROM itemsSections WHERE id_type_Item = ?";
    $stmt_check = mysqli_prepare($conexion, $query_check);
    
    if (!$stmt_check) {
        throw new Exception("Error preparando consulta itemsSections: " . mysqli_error($conexion));
    }
    
    mysqli_stmt_bind_param($stmt_check, 'i', $idTypeItem);
    mysqli_stmt_execute($stmt_check);
    $result_check = mysqli_stmt_get_result($stmt_check);
    
    if (!$result_check || mysqli_num_rows($result_check) == 0) {
        mysqli_stmt_close($stmt_check);
        mysqli_close($conexion);
        throw new Exception("No se encontró el tipo de item en itemsSections");
    }
    
    $item_section = mysqli_fetch_assoc($result_check);
    $fhater_item = (int)$item_section['fhater_item'];
    mysqli_stmt_close($stmt_check);
    
    // Si fhater_item !== 0, consultar tabla_mysql_name del padre
    $tabla_mysql_name = '';
    if ($fhater_item !== 0) {
        $query_parent = "SELECT tabla_mysql_name, itemnameText FROM itemsSections WHERE id_type_Item = ?";
        $stmt_parent = mysqli_prepare($conexion, $query_parent);
        
        if (!$stmt_parent) {
            mysqli_close($conexion);
            throw new Exception("Error preparando consulta parent itemsSections: " . mysqli_error($conexion));
        }
        
        mysqli_stmt_bind_param($stmt_parent, 'i', $fhater_item);
        mysqli_stmt_execute($stmt_parent);
        $result_parent = mysqli_stmt_get_result($stmt_parent);
        
        if ($result_parent && mysqli_num_rows($result_parent) > 0) {
            $parent_section = mysqli_fetch_assoc($result_parent);
            $tabla_mysql_name = $parent_section['tabla_mysql_name'];
            $itemName = $parent_section['itemnameText'];
        } else {
            mysqli_stmt_close($stmt_parent);
            mysqli_close($conexion);
            throw new Exception("No se encontró el itemSection padre con id: " . $fhater_item);
        }
        
        mysqli_stmt_close($stmt_parent);
    } else {
        // Si fhater_item = 0, usar tabla_mysql_name del mismo registro
        $query_tabla = "SELECT tabla_mysql_name, itemnameText FROM itemsSections WHERE id_type_Item = ?";
        $stmt_tabla = mysqli_prepare($conexion, $query_tabla);
        
        if (!$stmt_tabla) {
            mysqli_close($conexion);
            throw new Exception("Error preparando consulta tabla_mysql_name: " . mysqli_error($conexion));
        }
        
        mysqli_stmt_bind_param($stmt_tabla, 'i', $idTypeItem);
        mysqli_stmt_execute($stmt_tabla);
        $result_tabla = mysqli_stmt_get_result($stmt_tabla);
        
        if ($result_tabla && mysqli_num_rows($result_tabla) > 0) {
            $tabla_data = mysqli_fetch_assoc($result_tabla);
            $tabla_mysql_name = $tabla_data['tabla_mysql_name'];
            $itemName = $tabla_data['itemnameText'];
        } else {
            mysqli_stmt_close($stmt_tabla);
            mysqli_close($conexion);
            throw new Exception("No se pudo obtener tabla_mysql_name");
        }
        
        mysqli_stmt_close($stmt_tabla);
    }

    
    
    // Obtener el nombre de la columna PRIMARY KEY de la tabla
    $query_primary = "SELECT COLUMN_NAME 
                      FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
                      WHERE TABLE_SCHEMA = DATABASE() 
                      AND TABLE_NAME = ? 
                      AND CONSTRAINT_NAME = 'PRIMARY'
                      LIMIT 1";
    
    $stmt_primary = mysqli_prepare($conexion, $query_primary);
    
    if (!$stmt_primary) {
        mysqli_close($conexion);
        throw new Exception("Error preparando consulta PRIMARY KEY: " . mysqli_error($conexion));
    }
    
    mysqli_stmt_bind_param($stmt_primary, 's', $tabla_mysql_name);
    mysqli_stmt_execute($stmt_primary);
    $result_primary = mysqli_stmt_get_result($stmt_primary);
    
    if (!$result_primary || mysqli_num_rows($result_primary) == 0) {
        mysqli_stmt_close($stmt_primary);
        mysqli_close($conexion);
        throw new Exception("No se encontró PRIMARY KEY para la tabla: " . $tabla_mysql_name);
    }
    
    $primary_data = mysqli_fetch_assoc($result_primary);
    $primary_key_column = $primary_data['COLUMN_NAME'];
    mysqli_stmt_close($stmt_primary);
    
    // Buscar si existe el registro en la tabla
    $query_check_item = "SELECT $primary_key_column FROM $tabla_mysql_name WHERE $primary_key_column = ?";
    $stmt_check_item = mysqli_prepare($conexion, $query_check_item);
    
    if (!$stmt_check_item) {
        mysqli_close($conexion);
        throw new Exception("Error preparando consulta de verificación: " . mysqli_error($conexion));
    }
    
    mysqli_stmt_bind_param($stmt_check_item, 'i', $itemId);
    mysqli_stmt_execute($stmt_check_item);
    $result_check_item = mysqli_stmt_get_result($stmt_check_item);
    
    if (!$result_check_item || mysqli_num_rows($result_check_item) == 0) {
        mysqli_stmt_close($stmt_check_item);
        mysqli_close($conexion);
        throw new Exception("No se encontró el item #$itemId en la tabla $tabla_mysql_name");
    }
    
    mysqli_stmt_close($stmt_check_item);
    
    // Actualizar delete_state a 'false' en la tabla
    $query_update = "UPDATE $tabla_mysql_name SET delete_state = 'false' WHERE $primary_key_column = ?";
    $stmt_update = mysqli_prepare($conexion, $query_update);
    
    if (!$stmt_update) {
        mysqli_close($conexion);
        throw new Exception("Error preparando consulta de actualización: " . mysqli_error($conexion));
    }
    
    mysqli_stmt_bind_param($stmt_update, 'i', $itemId);
    
    if (!mysqli_stmt_execute($stmt_update)) {
        mysqli_stmt_close($stmt_update);
        mysqli_close($conexion);
        throw new Exception("Error al actualizar delete_state: " . mysqli_stmt_error($stmt_update));
    }
    
    mysqli_stmt_close($stmt_update);
    
    // Eliminar el registro de BinList
    $query_delete_bin = "DELETE FROM BinList WHERE idBinList = ?";
    $stmt_delete_bin = mysqli_prepare($conexion, $query_delete_bin);
    
    if (!$stmt_delete_bin) {
        mysqli_close($conexion);
        throw new Exception("Error preparando consulta de eliminación BinList: " . mysqli_error($conexion));
    }
    
    mysqli_stmt_bind_param($stmt_delete_bin, 'i', $idBinList);
    
    if (!mysqli_stmt_execute($stmt_delete_bin)) {
        mysqli_stmt_close($stmt_delete_bin);
        mysqli_close($conexion);
        throw new Exception("Error al eliminar de BinList: " . mysqli_stmt_error($stmt_delete_bin));
    }
    
    mysqli_stmt_close($stmt_delete_bin);
    
    // Obtener datos de sesión
    $usuario_id = $_SESSION['usuario_id'];
    $usuario = $_SESSION['usuario'];
    $usuario_sucursal = $_SESSION['usuario_sucursal'];
    // Registrar acción del usuario
    $texto_action_user = "$usuario recuperó el item Nº '$itemId' - $itemName";
    $id_action_user = 36;
    registrar_accion_usuario($usuario_id, $id_action_user, $texto_action_user, $usuario_sucursal, $idTypeItem);
    
    // Cerrar conexión
    mysqli_close($conexion);
    
    // Respuesta de éxito
    echo json_encode(array(
        'success' => true,
        'message' => "Item #$itemId recuperado exitosamente"
    ));
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(array(
        'success' => false,
        'error' => $e->getMessage()
    ));
}
?>

