<?php
/**
 * Archivo para procesar la actualización de Languages existentes
 * Actualiza la tabla: Languages
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
            'id_lang', 'cod_LP', 'description', 'rel_id_country'
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
        $id_lang = (int)$_POST['id_lang'];
        $cod_LP = trim($_POST['cod_LP']);
        $description = trim($_POST['description']);
        $rel_id_country = (int)$_POST['rel_id_country'];
        $stateLang = isset($_POST['stateLang']) ? 'true' : 'false';
        
        // Validar formato del código
        if (strlen($cod_LP) < 2 || strlen($cod_LP) > 5) {
            echo json_encode(array(
                'success' => false,
                'error' => 'El código debe tener entre 2 y 5 caracteres'
            ));
            exit;
        }
        
        // Preparar la consulta SQL de actualización
        $sql = "UPDATE Languages SET 
            cod_LP = ?,
            description = ?,
            rel_id_country = ?,
            stateLang = ?
            WHERE id_lang = ?";
        
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("ssisi", 
            $cod_LP,
            $description,
            $rel_id_country,
            $stateLang,
            $id_lang
        );
        
        if ($stmt->execute()) {
            // Commit de la transacción
            mysqli_commit($conexion);
            
            // Respuesta exitosa
            echo json_encode(array(
                'success' => true,
                'message' => 'Language actualizado exitosamente',
                'id_lang' => $id_lang,
                'redirect' => 'Languages.php'
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
