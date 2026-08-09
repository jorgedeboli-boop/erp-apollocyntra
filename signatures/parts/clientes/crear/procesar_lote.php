<?php
// Este archivo es incluido desde insertar_lote.php
// La variable $conexion ya está disponible desde insertar_lote.php
// La variable $resultado_cliente también está disponible

try {
    // Validar y obtener datos del POST (todos obligatorios)
    if (!isset($_POST['id_lote']) || empty($_POST['id_lote'])) {
        throw new Exception("El ID de lote es obligatorio");
    }
    $id_lote = intval($_POST['id_lote']);
    
    if (!isset($_POST['sucursal_lote']) || empty($_POST['sucursal_lote'])) {
        throw new Exception("La sucursal es obligatoria");
    }
    $id_sucursal = intval($_POST['sucursal_lote']);
    
    $id_cliente_procesado = $resultado_cliente['id_cliente_procesado'];
    
    // Validar y obtener variables del formulario de datos del lote (todas obligatorias)
    if (!isset($_POST['tipo_lote']) || empty($_POST['tipo_lote'])) {
        throw new Exception("El tipo de lote es obligatorio");
    }
    $tipo_lote = $_POST['tipo_lote'];
    
    if (!isset($_POST['cantidad_articulos']) || $_POST['cantidad_articulos'] === '' || intval($_POST['cantidad_articulos']) <= 0) {
        throw new Exception("La cantidad de artículos es obligatoria y debe ser mayor a 0");
    }
    $cantidad_articulos = intval($_POST['cantidad_articulos']);
    
    if (!isset($_POST['peso_neto']) || $_POST['peso_neto'] === '' || floatval($_POST['peso_neto']) <= 0) {
        throw new Exception("El peso neto es obligatorio y debe ser mayor a 0");
    }
    $peso_neto = floatval($_POST['peso_neto']);
    
    if (!isset($_POST['peso_bruto']) || $_POST['peso_bruto'] === '' || floatval($_POST['peso_bruto']) <= 0) {
        throw new Exception("El peso bruto es obligatorio y debe ser mayor a 0");
    }
    $peso_bruto = floatval($_POST['peso_bruto']);
    
    if (!isset($_POST['precio_compra']) || $_POST['precio_compra'] === '' || floatval($_POST['precio_compra']) <= 0) {
        throw new Exception("El precio de compra es obligatorio y debe ser mayor a 0");
    }
    $precio_compra = floatval($_POST['precio_compra']);
    
    if (!isset($_POST['merma'])) {
        throw new Exception("La merma es obligatoria");
    }
    $merma = floatval($_POST['merma']);


    if (!isset($_POST['porcentaje_recompra']) || $_POST['porcentaje_recompra'] === '' || floatval($_POST['porcentaje_recompra']) <= 0) {
        throw new Exception("El precio de compra es obligatorio y debe ser mayor a 0");
    }
    $intereses_lote = floatval($_POST['porcentaje_recompra']);


    if (!isset($_POST['precio_recompra']) || $_POST['precio_recompra'] === '' || floatval($_POST['precio_recompra']) <= 0) {
        throw new Exception("El precio de compra es obligatorio y debe ser mayor a 0");
    }
    $precio_recompra = floatval($_POST['precio_recompra']);
    
    
    if (!isset($_POST['opcion_compra']) || empty($_POST['opcion_compra'])) {
        throw new Exception("La opción de compra es obligatoria");
    }
    $opcion_compra = $_POST['opcion_compra'];

    // GENERO EL ESTADO
    if($opcion_compra == 'si') {
        $estado_lote = 'enfecha';
    } else {
        $estado_lote = 'compra';
    }
    
    if (!isset($_POST['fecha_vencimiento_hidden'])) {
        throw new Exception("La fecha de vencimiento es obligatoria");
    }
    $fecha_vencimiento = !empty($_POST['fecha_vencimiento_hidden']) && $_POST['fecha_vencimiento_hidden'] !== 'null' ? $_POST['fecha_vencimiento_hidden'] : null;
    
    if (!isset($_POST['fecha_liberacion']) || empty($_POST['fecha_liberacion']) || $_POST['fecha_liberacion'] === 'null') {
        throw new Exception("La fecha de liberación es obligatoria");
    }
    $fecha_liberacion = $_POST['fecha_liberacion'];
    
    if (!isset($_POST['metodo_pago']) || empty($_POST['metodo_pago'])) {
        throw new Exception("El método de pago es obligatorio");
    }
    $metodo_pago = $_POST['metodo_pago'];


    // GENERO EL NUMERO DE SEMANA
    $numero_semana = obtener_numero_semana();
    if (!$numero_semana) {
        throw new Exception("Error al obtener el número de semana");
    }
    $liberado = "no";
    $auditado = "no";
    // Nombre de la tabla dinámica
    $tabla_lotes = "lotes_" . $id_sucursal;
    
    // Insertar lote en lotes_$id_sucursal
    $query = "INSERT INTO " . $tabla_lotes . " (
        id_lote,
        sucursal,
        cliente,
        tipo_de_lote,
        peso,
        peso_bruto,
        precio_compra,
        merma,
        cantidad_articulos,
        compra_opcion,
        fecha_vencimiento,
        fecha_liberacion,
        metodo_pago,
        fecha_compra,
        comprado_por,
        fecha_hora,
        estado_lote,
        intereses_lote,
        precio_recompra,
        semana_numero,
        anyo_semana,
        liberado,
        auditado
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURDATE(), ?, NOW(), ?, ?, ?, ?, YEAR(CURDATE()), ?, ? )";
    
    $stmt = mysqli_prepare($conexion, $query);
    
    if (!$stmt) {
        throw new Exception("Error al preparar consulta de lote: " . mysqli_error($conexion));
    }
    
    // Convertir fecha_vencimiento a null si está vacía (puede ser NULL en la tabla)
    $fecha_vencimiento_sql = (!empty($fecha_vencimiento) && $fecha_vencimiento !== 'null') ? $fecha_vencimiento : null;
    
    mysqli_stmt_bind_param($stmt, 'iiisdddisssssisddsss',
        $id_lote,
        $id_sucursal,
        $id_cliente_procesado,
        $tipo_lote,
        $peso_neto,
        $peso_bruto,
        $precio_compra,
        $merma,
        $cantidad_articulos,
        $opcion_compra,
        $fecha_vencimiento_sql,
        $fecha_liberacion,
        $metodo_pago,
        $usuario_id,
        $estado_lote,
        $intereses_lote,
        $precio_recompra,
        $numero_semana,
        $liberado,
        $auditado
    );
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Error al insertar lote: " . mysqli_stmt_error($stmt));
    }
    
    mysqli_stmt_close($stmt);

    $texto_action_user = "$usuario creó el lote Nº '$id_lote' de la sucursal '$id_sucursal'";
    $id_action_user = "34";
    $relItemAction = $_SESSION['relItemAction'];
    registrar_accion_usuario($usuario_id, $id_action_user, $texto_action_user, $id_sucursal, $relItemAction);
    $_SESSION['relItemAction'] = "false";
    
    // Guardar resultado en variable para insertar_lote.php
    $resultado_lote = [
        'success' => true,
        'id_lote' => $id_lote,
        'id_sucursal' => $id_sucursal,
        'opcion_compra' => $opcion_compra,
        'precio_compra' => $precio_compra,
        'precio_recompra' => $precio_recompra,
        'fecha_vencimiento' => $fecha_vencimiento_sql,
        'metodo_pago' => $metodo_pago,
        'cantidad_articulos' => $cantidad_articulos,
        'peso_neto' => $peso_neto,
        'peso_bruto' => $peso_bruto,
        'merma' => $merma,
        'intereses_lote' => $intereses_lote,
        'tipo_lote' => $tipo_lote,
        'fecha_liberacion' => $fecha_liberacion
    ];
    
} catch (Exception $e) {
    // Guardar error en variable
    $resultado_lote = [
        'success' => false,
        'error' => $e->getMessage()
    ];
}

