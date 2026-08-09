<?php
require_once '../../../include/session.php';
require_once '../../../include/functions.php';

$id_gasto = isset($_GET['id']) ? (int)$_GET['id'] : 0;

echo "<h2>Debug Simple para Gasto ID: $id_gasto</h2>";

if (!$id_gasto) {
    echo "<p style='color: red;'>Error: ID de gasto no válido</p>";
    exit;
}

try {
    $conexion = conectar_bd();
    
    // 1. Verificar si existe el gasto
    $query_simple = "SELECT id_gasto FROM gastos WHERE id_gasto = ?";
    $stmt_simple = mysqli_prepare($conexion, $query_simple);
    mysqli_stmt_bind_param($stmt_simple, 'i', $id_gasto);
    mysqli_stmt_execute($stmt_simple);
    $result_simple = mysqli_stmt_get_result($stmt_simple);
    $existe = mysqli_num_rows($result_simple) > 0;
    mysqli_stmt_close($stmt_simple);
    
    echo "<p><strong>1. ¿Existe gasto con ID $id_gasto?</strong> " . ($existe ? 'SÍ' : 'NO') . "</p>";
    
    if (!$existe) {
        // Mostrar algunos IDs disponibles
        $query_ids = "SELECT id_gasto FROM gastos ORDER BY id_gasto DESC LIMIT 5";
        $result_ids = mysqli_query($conexion, $query_ids);
        $ids = [];
        while ($row = mysqli_fetch_assoc($result_ids)) {
            $ids[] = $row['id_gasto'];
        }
        echo "<p><strong>Últimos 5 IDs disponibles:</strong> " . implode(', ', $ids) . "</p>";
    } else {
        // 2. Probar la consulta completa
        $query_completa = "
            SELECT 
                g.id_gasto,
                g.fecha_gasto,
                g.descripcion_gasto,
                g.total_gasto,
                g.estado_gasto,
                g.empresa_gasto,
                g.sucursal_gasto,
                g.proveedor_gasto,
                g.tipo_de_gasto,
                g.forma_pago_gasto,
                g.numero_factura_proveedor,
                g.observaciones_gasto,
                e.nombre_empresa,
                s.nombre_sucursal,
                p.nombre_proveedor,
                tg.nombre_tipo_gasto,
                fp.nombre_forma_de_pago
            FROM gastos g
            LEFT JOIN empresas e ON g.empresa_gasto = e.id_empresa
            LEFT JOIN sucursal s ON g.sucursal_gasto = s.id_sucursal
            LEFT JOIN proveedores p ON g.proveedor_gasto = p.id_proveedor
            LEFT JOIN tipo_de_gasto tg ON g.tipo_de_gasto = tg.id_tipo_gasto
            LEFT JOIN formas_de_pago fp ON g.forma_pago_gasto = fp.id_forma_de_pago
            WHERE g.id_gasto = ?
        ";
        
        $stmt_completa = mysqli_prepare($conexion, $query_completa);
        mysqli_stmt_bind_param($stmt_completa, 'i', $id_gasto);
        mysqli_stmt_execute($stmt_completa);
        $result_completa = mysqli_stmt_get_result($stmt_completa);
        
        if ($result_completa && mysqli_num_rows($result_completa) > 0) {
            $gasto = mysqli_fetch_assoc($result_completa);
            echo "<p><strong>2. Consulta completa exitosa:</strong></p>";
            echo "<pre>" . print_r($gasto, true) . "</pre>";
        } else {
            echo "<p style='color: red;'><strong>2. Error en consulta completa:</strong> " . mysqli_error($conexion) . "</p>";
        }
        mysqli_stmt_close($stmt_completa);
    }
    
    mysqli_close($conexion);
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>
