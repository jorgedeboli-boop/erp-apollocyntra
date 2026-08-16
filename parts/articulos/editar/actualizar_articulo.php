<?php
require_once '../../../include/session.php';

header('Content-Type: application/json');

try {
    // Verificar que sea una petición POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(array('success' => false, 'error' => 'Método no permitido'));
        exit;
    }
    
    // Conectar BD
    $conexion = conectar_bd();
    
    // Iniciar transacción
    mysqli_begin_transaction($conexion);
    
    try {
        // Validar que se haya proporcionado el ID del artículo
        if (!isset($_POST['id_articulo']) || empty($_POST['id_articulo'])) {
            throw new Exception("ID de artículo es requerido");
        }
        
        $id_articulo = (int)$_POST['id_articulo'];
        
        // Validar campos obligatorios
        $campos_obligatorios = array(
            'precio_venta', 'descripcion', 'system_codigo_regimen'
        );
        
        foreach ($campos_obligatorios as $campo) {
            if (!isset($_POST[$campo]) || trim($_POST[$campo]) === '') {
                throw new Exception("El campo '" . $campo . "' es obligatorio");
            }
        }
        
        // Obtener datos del formulario
        $precio_venta = (float)$_POST['precio_venta'];
        $descripcion = trim($_POST['descripcion']);
        $system_codigo_regimen = trim($_POST['system_codigo_regimen']);
        
        $precio_coste = isset($_POST['precio_coste']) ? (float)$_POST['precio_coste'] : 0;
        if(empty($precio_coste)){
            $precioCosteParset = $precio_venta * 30 / 100;
            $precio_coste = number_format($precio_venta - $precioCosteParset, 2, '.', '');
        } else {
            $precio_coste = number_format($precio_coste, 2, '.', '');
        }
        
        $observaciones = isset($_POST['observaciones']) ? trim($_POST['observaciones']) : '';
        
        // Verificar que el artículo existe y obtener datos necesarios
        $query_check = "SELECT id, id_sucursal_destino, id_sucursal_origen, estado, precio, peso, tipo, id_lote_origen, ley, inscripciones, piedras FROM articulos_venta WHERE id = ? LIMIT 1";
        $stmt_check = mysqli_prepare($conexion, $query_check);
        mysqli_stmt_bind_param($stmt_check, 'i', $id_articulo);
        mysqli_stmt_execute($stmt_check);
        $result_check = mysqli_stmt_get_result($stmt_check);
        
        if (mysqli_num_rows($result_check) == 0) {
            mysqli_stmt_close($stmt_check);
            throw new Exception("El artículo no existe");
        }
        
        $articulo_actual = mysqli_fetch_assoc($result_check);
        $id_sucursal_destino = (int)$articulo_actual['id_sucursal_destino'];
        $id_sucursal_origen = (int)($articulo_actual['id_sucursal_origen'] ?? 0);
        $estado_articulo = $articulo_actual['estado'];
        $peso = (float) ($articulo_actual['peso'] ?? 0);
        $tipo_articulo = (string) ($articulo_actual['tipo'] ?? '');
        $id_lote_origen = (int) ($articulo_actual['id_lote_origen'] ?? 0);
        $ley = (string) ($articulo_actual['ley'] ?? '');
        $inscripciones = (string) ($articulo_actual['inscripciones'] ?? 'no');
        $piedras = (string) ($articulo_actual['piedras'] ?? 'no');
        $precio_gramo = ($peso > 0) ? number_format($precio_venta / $peso, 2, '.', '') : 0;
        $precio_anterior = (float)$articulo_actual['precio'];
        mysqli_stmt_close($stmt_check);
        
        // Obtener número de semana actual
        $query_semana = "SELECT numero_semana FROM listado_numero_semanas WHERE CURDATE() BETWEEN fecha_semana_desde AND fecha_semana_hasta AND anyo_listado = YEAR(CURDATE()) LIMIT 1";
        $result_semana = mysqli_query($conexion, $query_semana);
        $row_semana = mysqli_fetch_assoc($result_semana);
        $numeroSemana = isset($row_semana['numero_semana']) ? (int)$row_semana['numero_semana'] : 0;
        
        // Convertir tipo de artículo a formato correcto
        $tipo_de_articulo_formato = '';
        if ($tipo_articulo == 'oro') {
            $tipo_de_articulo_formato = 'Oro';
        } elseif ($tipo_articulo == 'plata') {
            $tipo_de_articulo_formato = 'Plata';
        } elseif ($tipo_articulo == 'acero') {
            $tipo_de_articulo_formato = 'Acero';
        } else {
            $tipo_de_articulo_formato = ucfirst($tipo_articulo);
        }
        
        $year_rel = date("Y");
        
        // UPDATE: Tabla articulos_venta
        $query_update = "
            UPDATE articulos_venta SET
                id_sucursal_origen = ?,
                id_lote_origen = ?,
                descripcion = ?,
                ley = ?,
                inscripciones = ?,
                tipo = ?,
                peso = ?,
                piedras = ?,
                precio = ?,
                precio_coste = ?,
                precio_gramo = ?,
                observaciones = ?,
                system_codigo_regimen = ?,
                update_register = NOW()
            WHERE id = ?
        ";
        
        $stmt_update = mysqli_prepare($conexion, $query_update);
        if (!$stmt_update) {
            throw new Exception("Error al preparar consulta articulos_venta: " . mysqli_error($conexion));
        }
        
        mysqli_stmt_bind_param(
            $stmt_update,
            'iisssssssssssi',
            $id_sucursal_origen,
            $id_lote_origen,
            $descripcion,
            $ley,
            $inscripciones,
            $tipo_articulo,
            $peso,
            $piedras,
            $precio_venta,
            $precio_coste,
            $precio_gramo,
            $observaciones,
            $system_codigo_regimen,
            $id_articulo
        );
        
        if (!mysqli_stmt_execute($stmt_update)) {
            throw new Exception("Error al actualizar artículo: " . mysqli_stmt_error($stmt_update));
        }
        
        mysqli_stmt_close($stmt_update);
        
        // UPDATE: Tabla rel_articulos_estados
        $query_update_rel = "
            UPDATE rel_articulos_estados SET
                rel_id_sucursal = ?,
                ley = ?,
                rel_id_lote = ?,
                tipo_de_articulo = ?,
                peso_articulo = ?,
                precio_coste_venta = ?,
                rel_id_sucursal_venta = ?,
                precio_venta = ?,
                tipo_iva_articulo = ?
            WHERE rel_id_articulo_venta = ?
        ";
        
        $stmt_update_rel = mysqli_prepare($conexion, $query_update_rel);
        if (!$stmt_update_rel) {
            throw new Exception("Error al preparar consulta rel_articulos_estados: " . mysqli_error($conexion));
        }
        
        mysqli_stmt_bind_param(
            $stmt_update_rel,
            'isissssisi',
            $id_sucursal_origen,         // i
            $ley,                         // s
            $id_lote_origen,              // i
            $tipo_de_articulo_formato,   // s
            $peso,                        // s (decimal como string)
            $precio_coste,                // s (decimal como string)
            $id_sucursal_destino,         // i
            $precio_venta,                // s (decimal como string)
            $system_codigo_regimen,           // s
            $id_articulo                  // i
        );
        
        if (!mysqli_stmt_execute($stmt_update_rel)) {
            throw new Exception("Error al actualizar relación: " . mysqli_stmt_error($stmt_update_rel));
        }
        
        mysqli_stmt_close($stmt_update_rel);


         // INSERT 3: Trazabilidad
         $accion_trazabilidad_venta = 'editado';
         $comentarios_trazabilidad_venta = "Artículo editado por el usuario " . $_SESSION['usuario_id'];
         
         try {
             trazabilidad_articulos_venta( 0, $_SESSION['usuario_id'], $accion_trazabilidad_venta, $comentarios_trazabilidad_venta, $id_sucursal_destino, $id_articulo, 0);
         } catch (Exception $e) {
             // Log del error de trazabilidad, pero no detener el proceso
             error_log("Error al insertar trazabilidad: " . $e->getMessage());
         }

         // Comparar precios y registrar cambio si es diferente
         if ($precio_anterior != $precio_venta) {

            // Actualizar estado del artículo a 'noetiquetado_u' y update_register en ambas tablas
            // UPDATE 1: Tabla articulos_venta
            $query_update_estado_venta = "
                UPDATE articulos_venta SET
                    estado = 'noetiquetado_u',
                    update_register = CURDATE()
                WHERE id = ?
            ";
            
            $stmt_update_estado_venta = mysqli_prepare($conexion, $query_update_estado_venta);
            if (!$stmt_update_estado_venta) {
                throw new Exception("Error al preparar consulta de actualización de estado en articulos_venta: " . mysqli_error($conexion));
            }
            
            mysqli_stmt_bind_param($stmt_update_estado_venta, 'i', $id_articulo);
            
            if (!mysqli_stmt_execute($stmt_update_estado_venta)) {
                mysqli_stmt_close($stmt_update_estado_venta);
                throw new Exception("Error al actualizar estado en articulos_venta: " . mysqli_stmt_error($stmt_update_estado_venta));
            }
            
            mysqli_stmt_close($stmt_update_estado_venta);
            
            // UPDATE 2: Tabla rel_articulos_estados
            $query_update_estado_rel = "
                UPDATE rel_articulos_estados SET
                    estado_articulo = 'noetiquetado_u'
                WHERE rel_id_articulo_venta = ?
            ";
            
            $stmt_update_estado_rel = mysqli_prepare($conexion, $query_update_estado_rel);
            if (!$stmt_update_estado_rel) {
                throw new Exception("Error al preparar consulta de actualización de estado en rel_articulos_estados: " . mysqli_error($conexion));
            }
            
            mysqli_stmt_bind_param($stmt_update_estado_rel, 'i', $id_articulo);
            
            if (!mysqli_stmt_execute($stmt_update_estado_rel)) {
                mysqli_stmt_close($stmt_update_estado_rel);
                throw new Exception("Error al actualizar estado en rel_articulos_estados: " . mysqli_stmt_error($stmt_update_estado_rel));
            }
            
            mysqli_stmt_close($stmt_update_estado_rel);

             $accion_trazabilidad_cambio_precio = 'cambio_precio';
             $comentarios_trazabilidad_cambio_precio = "Cambio de precio: de " . number_format($precio_anterior, 2, ',', '.') . " € a " . number_format($precio_venta, 2, ',', '.') . " € por el usuario " . $_SESSION['usuario_id'];
             
             try {
                 trazabilidad_articulos_venta( 0, $_SESSION['usuario_id'], $accion_trazabilidad_cambio_precio, $comentarios_trazabilidad_cambio_precio, $id_sucursal_destino, $id_articulo, 0);
             } catch (Exception $e) {
                 // Log del error de trazabilidad, pero no detener el proceso
                 error_log("Error al insertar trazabilidad de cambio de precio: " . $e->getMessage());
             }

            // INSERT 4: Control de precios
            control_de_precios($id_articulo, $precio_anterior, $precio_venta, $_SESSION['usuario_id'], $id_sucursal_destino, 'update');
         
         }
        
        // Confirmar transacción
        mysqli_commit($conexion);
        
        // Respuesta exitosa
        echo json_encode(array(
            'success' => true,
            'message' => 'Artículo actualizado correctamente',
            'id_articulo' => $id_articulo
        ));
        
    } catch (Exception $e) {
        // Rollback en caso de error
        mysqli_rollback($conexion);
        
        http_response_code(500);
        echo json_encode(array(
            'success' => false,
            'error' => $e->getMessage()
        ));
    }
    
    mysqli_close($conexion);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(array(
        'success' => false,
        'error' => $e->getMessage()
    ));
}
?>
