<?php
/**
 * Búsqueda de servicios del catálogo para líneas de presupuesto (por empresa).
 */
require_once __DIR__ . '/../../../include/session.php';
require_once __DIR__ . '/../../../include/functions.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $q = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
    $rel_id_empresa = isset($_GET['rel_id_empresa']) ? (int)$_GET['rel_id_empresa'] : 0;

    if ($rel_id_empresa <= 0) {
        throw new Exception('Empresa no indicada');
    }
    if ($q === '') {
        throw new Exception('Indique código, nombre o ID de servicio');
    }

    $conexion = conectar_bd();

    function servicio_precio_linea(array $row)
    {
        $tf = $row['tipo_facturacion'] ?? 'por_hora';
        $pf = (float)($row['precio_fijo'] ?? 0);
        $ph = (float)($row['precio_hora'] ?? 0);
        if ($tf === 'precio_fijo' || $tf === 'por_sesion') {
            return $pf > 0 ? $pf : $ph;
        }
        return $ph > 0 ? $ph : $pf;
    }

    $row = null;

    if (ctype_digit($q)) {
        $id = (int)$q;
        $stmt = mysqli_prepare(
            $conexion,
            'SELECT * FROM servicios WHERE id = ? AND rel_id_empresa = ? AND activo = 1 LIMIT 1'
        );
        mysqli_stmt_bind_param($stmt, 'ii', $id, $rel_id_empresa);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($res);
        mysqli_stmt_close($stmt);
    }

    if (!$row) {
        $stmt = mysqli_prepare(
            $conexion,
            'SELECT * FROM servicios WHERE codigo = ? AND rel_id_empresa = ? AND activo = 1 LIMIT 1'
        );
        mysqli_stmt_bind_param($stmt, 'si', $q, $rel_id_empresa);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($res);
        mysqli_stmt_close($stmt);
    }

    if (!$row && strlen($q) >= 3) {
        $like = '%' . $q . '%';
        $stmt = mysqli_prepare(
            $conexion,
            'SELECT * FROM servicios WHERE rel_id_empresa = ? AND activo = 1
             AND (codigo LIKE ? OR nombre LIKE ?)
             ORDER BY id ASC LIMIT 1'
        );
        mysqli_stmt_bind_param($stmt, 'iss', $rel_id_empresa, $like, $like);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($res);
        mysqli_stmt_close($stmt);
    }

    mysqli_close($conexion);

    if ($row) {
        $precio = servicio_precio_linea($row);
        $out = [
            'id' => (int)$row['id'],
            'codigo' => (string)$row['codigo'],
            'nombre' => (string)$row['nombre'],
            'descripcion' => (string)$row['descripcion'],
            'tipo_facturacion' => (string)$row['tipo_facturacion'],
            'precio' => round($precio, 2),
            'tipo' => 'servicio',
            'peso' => 0,
        ];
        echo json_encode([
            'success' => true,
            'encontrado' => true,
            'servicio' => $out,
        ]);
        return;
    }

    echo json_encode([
        'success' => true,
        'encontrado' => false,
        'message' => 'Servicio no encontrado para esta empresa',
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
