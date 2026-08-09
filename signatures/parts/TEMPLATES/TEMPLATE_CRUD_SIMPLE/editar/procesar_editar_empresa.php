<?php
/**
 * Archivo para procesar la actualización de proveedores existentes
 * Actualiza la tabla: proveedores
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
            'id_proveedor', 'nombre_proveedor', 'cif_proveedor', 'direccion_proveedor', 
            'poblacion_proveedor', 'provincia_proveedor', 'telefono_proveedor', 
            'codigo_postal_proveedor', 'pais_proveedor', 'email_proveedor', 
            'moneda_proveedor', 'forma_pago_proveedor'
        );
       
        foreach ($campos_obligatorios as $campo) {
            if (!isset($_POST[$campo]) || trim($_POST[$campo]) === '') {
                echo json_encode(array(
                    'success' => false,
                    'error' => "El campo '" . $campo . "' es obligatorio",
                    'campo_faltante' => $campo,
                    'valor_recibido' => isset($_POST[$campo]) ? $_POST[$campo] : 'NO_ENVIADO'
                ));
                exit;
            }
        }
        
        // Sanitizar datos
        $id_proveedor = (int)$_POST['id_proveedor'];
        $nombre_proveedor = trim($_POST['nombre_proveedor']);
        $cif_proveedor = trim($_POST['cif_proveedor']);
        $direccion_proveedor = trim($_POST['direccion_proveedor']);
        $poblacion_proveedor = trim($_POST['poblacion_proveedor']);
        $provincia_proveedor = trim($_POST['provincia_proveedor']);
        $telefono_proveedor = preg_replace('/[^0-9]/', '', $_POST['telefono_proveedor']);
        $codigo_postal_proveedor = trim($_POST['codigo_postal_proveedor']);
        $pais_proveedor = trim($_POST['pais_proveedor']);
        $email_proveedor = trim($_POST['email_proveedor']);
        $moneda_proveedor = trim($_POST['moneda_proveedor']);
        $forma_pago_proveedor = trim($_POST['forma_pago_proveedor']);
        
        // Procesar checkboxes de fundición
        $fundicion = isset($_POST['fundicion']) ? 'true' : 'false';
        $fundicion_multi_kilates = isset($_POST['fundicion_multi_kilates']) ? 'true' : 'false';
        
        // Validar formato de email
        if (!filter_var($email_proveedor, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(array(
                'success' => false,
                'error' => 'El formato del email no es válido'
            ));
            exit;
        }
        
        // Preparar la consulta SQL de actualización
        $sql = "UPDATE proveedores SET 
            nombre_proveedor = ?,
            cif_proveedor = ?,
            direccion_proveedor = ?,
            poblacion_proveedor = ?,
            provincia_proveedor = ?,
            telefono_proveedor = ?,
            codigo_postal_proveedor = ?,
            pais_proveedor = ?,
            email_proveedor = ?,
            moneda_proveedor = ?,
            forma_pago_proveedor = ?,
            fundicion = ?,
            fundicion_multi_kilates = ?
            WHERE id_proveedor = ?";
        
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("sssssssssssssi", 
            $nombre_proveedor,
            $cif_proveedor,
            $direccion_proveedor,
            $poblacion_proveedor,
            $provincia_proveedor,
            $telefono_proveedor,
            $codigo_postal_proveedor,
            $pais_proveedor,
            $email_proveedor,
            $moneda_proveedor,
            $forma_pago_proveedor,
            $fundicion,
            $fundicion_multi_kilates,
            $id_proveedor
        );
        
        if ($stmt->execute()) {
            // Commit de la transacción
            mysqli_commit($conexion);
            
            // Respuesta exitosa
            echo json_encode(array(
                'success' => true,
                'message' => 'Proveedor actualizado exitosamente',
                'id_proveedor' => $id_proveedor,
                'redirect' => 'proveedores.php'
            ));
        } else {
            throw new Exception('Error al ejecutar la actualización: ' . $stmt->error);
        }
        
        $stmt->close();
        
    } catch (Exception $e) {
        // Rollback de la transacción en caso de error
        mysqli_rollback($conexion);
        
        // Respuesta de error
        http_response_code(400);
        echo json_encode(array(
            'success' => false,
            'error' => $e->getMessage()
        ));
    }
    
    mysqli_close($conexion);
    
} catch (Exception $e) {
    // Error del sistema
    http_response_code(500);
    echo json_encode(array(
        'success' => false,
        'error' => 'Error interno del servidor: ' . $e->getMessage()
    ));
} catch (Error $e) {
    // Error fatal
    http_response_code(500);
    echo json_encode(array(
        'success' => false,
        'error' => 'Error fatal del sistema'
    ));
}
?>
