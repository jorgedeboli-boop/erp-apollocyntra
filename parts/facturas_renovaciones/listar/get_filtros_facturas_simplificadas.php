<?php
/**
 * Opciones para filtros del listado de facturas simplificadas.
 */

require_once '../../../include/session.php';
require_once '../../../include/functions.php';

header('Content-Type: application/json; charset=utf-8');

if (!function_exists('usuario_autenticado') || !usuario_autenticado()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

try {
    $conexion = conectar_bd();

    $empresas = [];
    $resEmp = mysqli_query($conexion, 'SELECT id_empresa, nombre_empresa FROM empresas ORDER BY nombre_empresa ASC');
    if ($resEmp) {
        while ($row = mysqli_fetch_assoc($resEmp)) {
            $empresas[] = [
                'id_empresa' => (int) $row['id_empresa'],
                'nombre_empresa' => $row['nombre_empresa'],
            ];
        }
        mysqli_free_result($resEmp);
    }

    $tipos_pago = [];
    $resTp = mysqli_query(
        $conexion,
        "SELECT DISTINCT tipo_pago_factura AS t
         FROM facturas_simplificadas
         WHERE tipo_pago_factura IS NOT NULL AND TRIM(tipo_pago_factura) <> ''
         ORDER BY tipo_pago_factura ASC"
    );
    if ($resTp) {
        while ($row = mysqli_fetch_assoc($resTp)) {
            $tipos_pago[] = $row['t'];
        }
        mysqli_free_result($resTp);
    }

    mysqli_close($conexion);

    echo json_encode([
        'success' => true,
        'empresas' => $empresas,
        'tipos_pago' => $tipos_pago,
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
