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
        // Validar campos obligatorios
        $campos_obligatorios = array(
            'precio_venta', 'descripcion', 'system_codigo_regimen', 'tipo_iva_articulo'
        );
        
        foreach ($campos_obligatorios as $campo) {
            if (!isset($_POST[$campo]) || trim($_POST[$campo]) === '') {
                throw new Exception("El campo '" . $campo . "' es obligatorio");
            }
        }
        
        // Obtener datos del formulario
        $precio_venta = (float)$_POST['precio_venta'];
        $peso = 0;
        $tipo_articulo = '';
        $descripcion = trim($_POST['descripcion']);
        $system_codigo_regimen = trim($_POST['system_codigo_regimen']);
        $regimenes_validos = array('REBU', 'INVERSION', 'GENERAL');
        if (!in_array($system_codigo_regimen, $regimenes_validos, true)) {
            throw new Exception('Régimen fiscal no válido');
        }
        $tipo_iva_articulo = trim($_POST['tipo_iva_articulo']);
        $tipos_iva_validos = array('IVA', 'IPSI', 'IGIC', 'OTHER');
        if (!in_array($tipo_iva_articulo, $tipos_iva_validos, true)) {
            throw new Exception('Tipo de IVA no válido');
        }
        $id_sucursal_destino = 0;
        $id_sucursal_origen = 0;
        $id_lote_origen = 0;
        
        
        $precio_coste = (float)$_POST['precio_coste'];
        if(empty($precio_coste)){
            $precioCosteParset = $precio_venta * 30 /100;
            $precio_coste = number_format($precio_venta - $precioCosteParset, 2, '.', '');
        }else{
            $precio_coste = number_format($precio_coste, 2, '.', '');
        }
        $precio_gramo = 0;
        $ley = '';
        $inscripciones = isset($_POST['inscripciones']) ? trim($_POST['inscripciones']) : 'no';
        $piedras = isset($_POST['piedras']) ? trim($_POST['piedras']) : 'no';
        $observaciones = isset($_POST['observaciones']) ? trim($_POST['observaciones']) : '';
        
        
        // Obtener ID de usuario
        $id_usuario = isset($_SESSION['usuario_id']) ? (int)$_SESSION['usuario_id'] : 0;
        if (!$id_usuario) {
            throw new Exception("No se pudo determinar el usuario");
        }
        
        $rel_id_empresa = 0;
        
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
        
        // Obtenemos id_articulo_sucursal que corresponda
      $sql_id_articulo = "SELECT MAX(id_articulo_sucursal) as max FROM articulos_venta WHERE id_sucursal_destino = ?";
      $stmt_id_articulo = mysqli_prepare($conexion, $sql_id_articulo);
      if (!$stmt_id_articulo) {
          echo json_encode(['success' => false, 'message' => 'Error al preparar la consulta de id_articulo_sucursal: ' . mysqli_error($conexion)]);
          exit;
      }
      mysqli_stmt_bind_param($stmt_id_articulo, 'i', $id_sucursal_destino);
      mysqli_stmt_execute($stmt_id_articulo);
      $result_id_articulo = mysqli_stmt_get_result($stmt_id_articulo);
      $rs_id_articulo = mysqli_fetch_assoc($result_id_articulo);
      mysqli_stmt_close($stmt_id_articulo);
      
      $id_articulo_sucursal = intval($rs_id_articulo['max'] ?? 0) + 1;
        
        // INSERT 1: Tabla articulos_venta
        $query_insert = "
            INSERT INTO articulos_venta (
                id_sucursal_origen,
                id_lote_origen,
                id_sucursal_destino,
                id_articulo_sucursal,
                descripcion,
                ley,
                inscripciones,
                tipo,
                peso,
                piedras,
                piedras_descripcion,
                kilate_piedras,
                precio,
                estado,
                observaciones,
                precio_coste,
                creado_por,
                fecha_alta,
                origen_articulo,
                update_register,
                system_codigo_regimen,
                tipo_iva_articulo
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, NOW(), ?, ?)
        ";
        
        $piedras_descripcion = ''; // Campo vacío por defecto
        $kilate_piedras = ''; // Campo vacío por defecto
        $estado = 'noetiquetado_c';
        $origen_articulo = 'central';
        
        $stmt_insert = mysqli_prepare($conexion, $query_insert);
        if (!$stmt_insert) {
            throw new Exception("Error al preparar consulta articulos_venta: " . mysqli_error($conexion));
        }
        
        // Tipos: 4×i ids, 4×s texto, peso d, 3×s, precio d, 2×s, precio_coste d, usuario i, 3×s (origen, régimen, tipo IVA)
        $bindTypesArticulo = 'iiii' . 'ssss' . 'd' . 'sss' . 'd' . 'ss' . 'd' . 'i' . 'sss';
        mysqli_stmt_bind_param(
            $stmt_insert,
            $bindTypesArticulo,
            $id_sucursal_origen,
            $id_lote_origen,
            $id_sucursal_destino,
            $id_articulo_sucursal,
            $descripcion,
            $ley,
            $inscripciones,
            $tipo_articulo,
            $peso,
            $piedras,
            $piedras_descripcion,
            $kilate_piedras,
            $precio_venta,
            $estado,
            $observaciones,
            $precio_coste,
            $id_usuario,
            $origen_articulo,
            $system_codigo_regimen,
            $tipo_iva_articulo
        );
        
        if (!mysqli_stmt_execute($stmt_insert)) {
            throw new Exception(
                'Error al insertar artículo: ' . mysqli_stmt_error($stmt_insert)
                . ' (errno ' . mysqli_stmt_errno($stmt_insert) . ')'
            );
        }
        
        $last_id = mysqli_insert_id($conexion);
        mysqli_stmt_close($stmt_insert);
        
        // INSERT 2: Tabla rel_articulos_estados
        $year_rel = date("Y");
        $query_rel = "
            INSERT INTO rel_articulos_estados (
                rel_id_articulo_venta,
                rel_id_sucursal,
                ley,
                rel_id_lote,
                tipo_de_articulo,
                peso_articulo,
                precio_coste_venta,
                estado_articulo,
                rel_id_empresa,
                rel_numero_semana,
                rel_id_sucursal_venta,
                precio_venta,
                year_rel,
                tipo_iva_articulo
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ? ,?, ?, ?, ?)
        ";
        
        $stmt_rel = mysqli_prepare($conexion, $query_rel);
        if (!$stmt_rel) {
            throw new Exception("Error al preparar consulta de relación rel_articulos_estados: " . mysqli_error($conexion));
        }
        
        mysqli_stmt_bind_param(
            $stmt_rel,
            'iisissssiiisis',
            $last_id,                    // i
            $id_sucursal_origen,         // i
            $ley,                        // s
            $id_lote_origen,             // i
            $tipo_de_articulo_formato,   // s
            $peso,                       // d
            $precio_coste,               // d
            $estado,            // s
            $rel_id_empresa,             // i
            $numeroSemana,               // i
            $id_sucursal_destino,        // i
            $precio_venta,               // d
            $year_rel,                   // i
            $tipo_iva_articulo           // s
        );
        
        if (!mysqli_stmt_execute($stmt_rel)) {
            throw new Exception("Error al insertar relación: " . mysqli_stmt_error($stmt_rel));
        }
        
        mysqli_stmt_close($stmt_rel);
        
        // INSERT 3: Trazabilidad
        $accion_trazabilidad_venta = 'creado';
        $comentarios_trazabilidad_venta = "Artículo creado por el usuario " . $_SESSION['usuario_id'];
        
        try {
            trazabilidad_articulos_venta( 0, $_SESSION['usuario_id'], $accion_trazabilidad_venta, $comentarios_trazabilidad_venta, $id_sucursal_destino, $last_id, 0);
        } catch (Exception $e) {
            // Log del error de trazabilidad, pero no detener el proceso
            error_log("Error al insertar trazabilidad: " . $e->getMessage());
        }
        
        // INSERT 4: Control de precios
        control_de_precios($last_id, 0, $precio_venta, $_SESSION['usuario_id'], $id_sucursal_destino, 'create');
        
        // Confirmar transacción
        mysqli_commit($conexion);
        
        // Respuesta exitosa
        echo json_encode(array(
            'success' => true,
            'message' => 'Artículo creado correctamente',
            'id_articulo' => $last_id
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

