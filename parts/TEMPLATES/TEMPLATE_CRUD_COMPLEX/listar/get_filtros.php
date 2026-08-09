<?php
require_once '../../../include/session.php';
require_once '../../../include/functions.php';

// Verificar que el usuario esté autenticado
if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

try {
    $conexion = conectar_bd();
    
    if (!$conexion) {
        throw new Exception('Error de conexión a la base de datos');
    }
    
    $filtros = [];
    
    // Obtener empresas
    $query_empresas = "SELECT id_empresa, nombre_empresa FROM empresas ORDER BY nombre_empresa";
    $result_empresas = mysqli_query($conexion, $query_empresas);
    $empresas = [];
    while ($row = mysqli_fetch_assoc($result_empresas)) {
        $empresas[] = ['id' => $row['id_empresa'], 'nombre' => $row['nombre_empresa']];
    }
    $filtros['empresas'] = $empresas;
    
    // Obtener sucursales
    $query_sucursales = "SELECT id_sucursal, nombre_sucursal FROM sucursal ORDER BY nombre_sucursal";
    $result_sucursales = mysqli_query($conexion, $query_sucursales);
    $sucursales = [];
    while ($row = mysqli_fetch_assoc($result_sucursales)) {
        $sucursales[] = ['id' => $row['id_sucursal'], 'nombre' => $row['nombre_sucursal']];
    }
    $filtros['sucursales'] = $sucursales;
    
    // Obtener proveedores
    $query_proveedores = "SELECT id_proveedor, nombre_proveedor FROM proveedores ORDER BY nombre_proveedor";
    $result_proveedores = mysqli_query($conexion, $query_proveedores);
    $proveedores = [];
    while ($row = mysqli_fetch_assoc($result_proveedores)) {
        $proveedores[] = ['id' => $row['id_proveedor'], 'nombre' => $row['nombre_proveedor']];
    }
    $filtros['proveedores'] = $proveedores;
    
    // Obtener tipos de gasto
    $query_tipos = "SELECT id_tipo_gasto, nombre_tipo_gasto FROM tipo_de_gasto ORDER BY nombre_tipo_gasto";
    $result_tipos = mysqli_query($conexion, $query_tipos);
    $tipos = [];
    while ($row = mysqli_fetch_assoc($result_tipos)) {
        $tipos[] = ['id' => $row['id_tipo_gasto'], 'nombre' => $row['nombre_tipo_gasto']];
    }
    $filtros['tipos_gasto'] = $tipos;
    
    // Obtener formas de pago
    $query_formas = "SELECT id_forma_de_pago, nombre_forma_de_pago FROM formas_de_pago ORDER BY nombre_forma_de_pago";
    $result_formas = mysqli_query($conexion, $query_formas);
    $formas = [];
    while ($row = mysqli_fetch_assoc($result_formas)) {
        $formas[] = ['id' => $row['id_forma_de_pago'], 'nombre' => $row['nombre_forma_de_pago']];
    }
    $filtros['formas_pago'] = $formas;
    
    // Estados de gasto
    $filtros['estados'] = [
        ['id' => 'pendiente', 'nombre' => 'Pendiente'],
        ['id' => 'pagado', 'nombre' => 'Pagado'],
        ['id' => 'cancelado', 'nombre' => 'Cancelado']
    ];
    
    mysqli_close($conexion);
    
    echo json_encode(['success' => true, 'filtros' => $filtros]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error interno del servidor: ' . $e->getMessage()]);
}
?>
