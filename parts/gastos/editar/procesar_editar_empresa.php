<?php
/**
 * Archivo para procesar la actualización de gastos existentes
 * Actualiza la tabla: gastos
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
            'id_gasto', 'nombre_gasto', 'cif_gasto', 'direccion_gasto', 
            'poblacion_gasto', 'provincia_gasto', 'telefono_gasto', 
            'codigo_postal_gasto', 'pais_gasto', 'email_gasto', 'texto_facturas'
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
        $id_gasto = (int)$_POST['id_gasto'];
        $nombre_gasto = trim($_POST['nombre_gasto']);
        $cif_gasto = trim($_POST['cif_gasto']);
        $direccion_gasto = trim($_POST['direccion_gasto']);
        $poblacion_gasto = trim($_POST['poblacion_gasto']);
        $provincia_gasto = trim($_POST['provincia_gasto']);
        $telefono_gasto = preg_replace('/[^0-9]/', '', $_POST['telefono_gasto']);
        $codigo_postal_gasto = trim($_POST['codigo_postal_gasto']);
        $pais_gasto = trim($_POST['pais_gasto']);
        $email_gasto = trim($_POST['email_gasto']);
        $texto_facturas = trim($_POST['texto_facturas']);
        $texto_contrato_empeno = isset($_POST['texto_contrato_empeno']) ? trim($_POST['texto_contrato_empeno']) : '';
        $texto_contrato_compra = isset($_POST['texto_contrato_compra']) ? trim($_POST['texto_contrato_compra']) : '';
        $webgasto = isset($_POST['webgasto']) ? trim($_POST['webgasto']) : '';
        
        // Validar formato de email
        if (!filter_var($email_gasto, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(array(
                'success' => false,
                'error' => 'El formato del email no es válido'
            ));
            exit;
        }
        
        // Preparar la consulta SQL de actualización
        $sql = "UPDATE gastos SET 
            nombre_gasto = ?,
            cif_gasto = ?,
            direccion_gasto = ?,
            poblacion_gasto = ?,
            provincia_gasto = ?,
            telefono_gasto = ?,
            codigo_postal_gasto = ?,
            pais_gasto = ?,
            email_gasto = ?,
            texto_facturas = ?,
            texto_contrato_empeno = ?,
            texto_contrato_compra = ?,
            webgasto = ?
            WHERE id_gasto = ?";
        
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("sssssssssssssi", 
            $nombre_gasto,
            $cif_gasto,
            $direccion_gasto,
            $poblacion_gasto,
            $provincia_gasto,
            $telefono_gasto,
            $codigo_postal_gasto,
            $pais_gasto,
            $email_gasto,
            $texto_facturas,
            $texto_contrato_empeno,
            $texto_contrato_compra,
            $webgasto,
            $id_gasto
        );
        
        if ($stmt->execute()) {
            // Commit de la transacción
            mysqli_commit($conexion);
            
            // Respuesta exitosa
            echo json_encode(array(
                'success' => true,
                'message' => 'Empresa actualizada exitosamente',
                'id_gasto' => $id_gasto,
                'redirect' => 'gastos.php'
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
