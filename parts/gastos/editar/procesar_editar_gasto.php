<?php
require_once '../../../include/session.php';
require_once '../../../include/functions.php';

header('Content-Type: application/json');

// Verificar que sea una petición POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

// Verificar que el usuario esté autenticado
if (!usuario_autenticado()) {
    echo json_encode(['success' => false, 'error' => 'Usuario no autenticado']);
    exit;
}

try {
    // Obtener datos del formulario
    $id_gasto = isset($_POST['id_gasto']) ? (int)$_POST['id_gasto'] : 0;
    $fecha_gasto = $_POST['fecha_gasto'] ?? '';
    $descripcion_gasto = $_POST['descripcion_gasto'] ?? '';
    $total_gasto = $_POST['total_gasto'] ?? 0;
    $estado_gasto = $_POST['estado_gasto'] ?? '';
    $empresa_gasto = isset($_POST['empresa_gasto']) ? (int)$_POST['empresa_gasto'] : 0;
    $sucursal_gasto = 0;
    $proveedor_gasto = isset($_POST['proveedor_gasto']) ? (int)$_POST['proveedor_gasto'] : 0;
    $tipo_de_gasto = isset($_POST['tipo_de_gasto']) ? (int)$_POST['tipo_de_gasto'] : 0;
    $forma_pago_gasto = isset($_POST['forma_pago_gasto']) ? (int)$_POST['forma_pago_gasto'] : 0;
    $numero_factura_proveedor = $_POST['numero_factura_proveedor'] ?? '';
    $observaciones_gasto = $_POST['observaciones_gasto'] ?? '';
    
    // Validar datos requeridos
    if (!$id_gasto) {
        throw new Exception('ID de gasto no válido');
    }
    
    if (empty($fecha_gasto)) {
        throw new Exception('La fecha del gasto es requerida');
    }
    
    if (empty($descripcion_gasto)) {
        throw new Exception('La descripción del gasto es requerida');
    }
    
    if ($total_gasto <= 0) {
        throw new Exception('El total del gasto debe ser mayor a 0');
    }
    
    if (!$empresa_gasto) {
        throw new Exception('Debe seleccionar una empresa');
    }
    
    if (!$proveedor_gasto) {
        throw new Exception('Debe seleccionar un proveedor');
    }
    
    if (!$tipo_de_gasto) {
        throw new Exception('Debe seleccionar un tipo de gasto');
    }
    
    if (!$forma_pago_gasto) {
        throw new Exception('Debe seleccionar una forma de pago');
    }
    
    // Conectar a la base de datos
    $conexion = conectar_bd();
    if (!$conexion) {
        throw new Exception('Error de conexión a la base de datos');
    }
    
    // Verificar que el gasto existe
    $query_verificar = "SELECT id_gasto FROM gastos WHERE id_gasto = ?";
    $stmt_verificar = mysqli_prepare($conexion, $query_verificar);
    mysqli_stmt_bind_param($stmt_verificar, 'i', $id_gasto);
    mysqli_stmt_execute($stmt_verificar);
    $result_verificar = mysqli_stmt_get_result($stmt_verificar);
    
    if (mysqli_num_rows($result_verificar) == 0) {
        mysqli_stmt_close($stmt_verificar);
        mysqli_close($conexion);
        throw new Exception('Gasto no encontrado');
    }
    mysqli_stmt_close($stmt_verificar);
    
    // Actualizar el gasto
    $query_update = "
        UPDATE gastos SET 
            fecha_gasto = ?,
            descripcion_gasto = ?,
            total_gasto = ?,
            estado_gasto = ?,
            empresa_gasto = ?,
            sucursal_gasto = ?,
            proveedor_gasto = ?,
            tipo_de_gasto = ?,
            forma_pago_gasto = ?,
            numero_factura_proveedor = ?,
            observaciones_gasto = ?,
            usuario_gasto = ?,
            fecha_modificacion_gasto = NOW()
        WHERE id_gasto = ?
    ";
    
    $stmt_update = mysqli_prepare($conexion, $query_update);
    if (!$stmt_update) {
        throw new Exception('Error al preparar la consulta de actualización: ' . mysqli_error($conexion));
    }
    
    mysqli_stmt_bind_param(
        $stmt_update,
        'ssdsiiiiisssi',
        $fecha_gasto,
        $descripcion_gasto,
        $total_gasto,
        $estado_gasto,
        $empresa_gasto,
        $sucursal_gasto,
        $proveedor_gasto,
        $tipo_de_gasto,
        $forma_pago_gasto,
        $numero_factura_proveedor,
        $observaciones_gasto,
        $_SESSION['usuario_id'],
        $id_gasto
    );
    
    if (!mysqli_stmt_execute($stmt_update)) {
        throw new Exception('Error al actualizar el gasto: ' . mysqli_stmt_error($stmt_update));
    }
    
    mysqli_stmt_close($stmt_update);
    
    // Registrar la acción en auditoría
    registrar_accion_usuario(
        $_SESSION['usuario_id'],
        2, // ID de acción de edición
        "Gasto #{$id_gasto} actualizado - Total: €{$total_gasto}",
        $_SESSION['usuario_sucursal'],
        0, // relItemAction
        "editar_gasto.php?id={$id_gasto}"
    );
    
    mysqli_close($conexion);
    
    // Respuesta de éxito
    echo json_encode([
        'success' => true,
        'message' => 'Gasto actualizado correctamente',
        'redirect' => 'gastos.php'
    ]);
    
} catch (Exception $e) {
    // Log del error
    error_log("Error al editar gasto: " . $e->getMessage());
    
    // Respuesta de error
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>