<?php
/**
 * Archivo para procesar la actualización de empresas existentes
 * Actualiza la tabla: empresas
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
            'id_empresa', 'nombre_empresa', 'cif_empresa', 'direccion_empresa',
            'poblacion_empresa', 'provincia_empresa', 'telefono_empresa',
            'codigo_postal_empresa', 'pais_empresa', 'email_empresa'
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
        $id_empresa = (int)$_POST['id_empresa'];
        $nombre_empresa = trim($_POST['nombre_empresa']);
        $cif_empresa = trim($_POST['cif_empresa']);
        $direccion_empresa = trim($_POST['direccion_empresa']);
        $poblacion_empresa = trim($_POST['poblacion_empresa']);
        $provincia_empresa = trim($_POST['provincia_empresa']);
        $telefono_empresa = preg_replace('/[^0-9]/', '', $_POST['telefono_empresa']);
        $codigo_postal_empresa = trim($_POST['codigo_postal_empresa']);
        $pais_empresa = trim($_POST['pais_empresa']);
        $email_empresa = trim($_POST['email_empresa']);
        $webempresa = isset($_POST['webempresa']) ? trim($_POST['webempresa']) : '';
        $rel_id_provincia = isset($_POST['rel_id_provincia']) ? (int) $_POST['rel_id_provincia'] : 0;
        $rel_id_poblacion = isset($_POST['rel_id_poblacion']) ? (int) $_POST['rel_id_poblacion'] : 0;
        $rel_id_pais = isset($_POST['rel_id_pais']) ? (int) $_POST['rel_id_pais'] : 0;
        
        // Validar formato de email
        if (!filter_var($email_empresa, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(array(
                'success' => false,
                'error' => 'El formato del email no es válido'
            ));
            exit;
        }
        
        // Preparar la consulta SQL de actualización
        $sql = "UPDATE empresas SET 
            nombre_empresa = ?,
            cif_empresa = ?,
            direccion_empresa = ?,
            poblacion_empresa = ?,
            provincia_empresa = ?,
            telefono_empresa = ?,
            codigo_postal_empresa = ?,
            pais_empresa = ?,
            email_empresa = ?,
            webempresa = ?,
            rel_id_provincia = ?,
            rel_id_poblacion = ?,
            rel_id_pais = ?
            WHERE id_empresa = ?";
        
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("ssssssssssiiii",
            $nombre_empresa,
            $cif_empresa,
            $direccion_empresa,
            $poblacion_empresa,
            $provincia_empresa,
            $telefono_empresa,
            $codigo_postal_empresa,
            $pais_empresa,
            $email_empresa,
            $webempresa,
            $rel_id_provincia,
            $rel_id_poblacion,
            $rel_id_pais,
            $id_empresa
        );
        
        if ($stmt->execute()) {
            // Commit de la transacción
            mysqli_commit($conexion);
            
            // Respuesta exitosa
            echo json_encode(array(
                'success' => true,
                'message' => 'Empresa actualizada exitosamente',
                'id_empresa' => $id_empresa,
                'redirect' => 'empresa.php?id=' . $id_empresa
            ));
        } else {
            throw new Exception('Error al ejecutar la actualización: ' . $stmt->error);
        }
        
        $stmt->close();

        $texto_action_user = "$usuario actualizó la empresa Nº '$id_empresa'";
        $id_action_user = "33";
        $relItemAction = $_SESSION['relItemAction'];
        registrar_accion_usuario($usuario_id, $id_action_user, $texto_action_user, $usuario_sucursal, $relItemAction);
        $_SESSION['relItemAction'] = "false";
        
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
