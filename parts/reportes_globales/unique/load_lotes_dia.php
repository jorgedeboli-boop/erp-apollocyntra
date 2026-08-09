<?php
require_once '../../../include/session.php';
require_once '../../../include/functions.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'No autorizado']);
    exit;
}

$id_sucursal = isset($_POST['id_sucursal']) ? (int) $_POST['id_sucursal'] : 0;
$fecha = isset($_POST['fecha']) ? trim((string) $_POST['fecha']) : '';

if ($id_sucursal <= 0) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Sucursal no válida']);
    exit;
}

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Fecha no válida']);
    exit;
}

function rg_fmt_gramos_lote($valor)
{
    return number_format((float) $valor, 2, ',', '.') . ' g';
}

function rg_fmt_fecha_lote($fecha)
{
    if ($fecha === null || $fecha === '' || substr((string) $fecha, 0, 10) === '0000-00-00') {
        return '—';
    }
    $ts = strtotime((string) $fecha);
    return $ts ? date('d/m/Y', $ts) : '—';
}

try {
    $conexion = conectar_bd();
    if (!$conexion) {
        throw new Exception('Error de conexión a la base de datos');
    }

    $sql = 'SELECT
                l.id_lote,
                l.identificador,
                l.fecha_compra,
                l.tipo_de_lote,
                l.compra_opcion,
                l.peso,
                l.peso_bruto,
                l.merma,
                l.fecha_vencimiento,
                l.cantidad_articulos,
                COALESCE(a.peso_articulos, 0) AS peso_articulos
            FROM lotes_joyeria l
            LEFT JOIN (
                SELECT
                    id_lote_articulos,
                    sucursal_articulo,
                    SUM(peso_articulo) AS peso_articulos
                FROM articulos_lotes
                WHERE sucursal_articulo = ?
                GROUP BY id_lote_articulos, sucursal_articulo
            ) a ON a.id_lote_articulos = l.id_lote
               AND a.sucursal_articulo = l.sucursal
            WHERE l.sucursal = ?
              AND DATE(l.fecha_compra) = ?
            ORDER BY l.id_lote ASC';

    $stmt = mysqli_prepare($conexion, $sql);
    if (!$stmt) {
        throw new Exception('Error preparando consulta: ' . mysqli_error($conexion));
    }

    mysqli_stmt_bind_param($stmt, 'iis', $id_sucursal, $id_sucursal, $fecha);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $lotes = [];
    while ($row = $result ? mysqli_fetch_assoc($result) : null) {
        $pesoNeto = round((float) ($row['peso'] ?? 0), 2);
        $pesoArticulos = round((float) ($row['peso_articulos'] ?? 0), 2);
        $esEmpeno = strtolower(trim((string) ($row['compra_opcion'] ?? ''))) === 'si';
        $coincide = abs($pesoNeto - $pesoArticulos) < 0.005;

        $lotes[] = [
            'id_lote' => (int) $row['id_lote'],
            'identificador' => (int) ($row['identificador'] ?? 0),
            'fecha_compra' => rg_fmt_fecha_lote($row['fecha_compra'] ?? null),
            'tipo_de_lote' => htmlspecialchars((string) ($row['tipo_de_lote'] ?? '—')),
            'empeno' => $esEmpeno ? 'Sí' : 'No',
            'es_empeno' => $esEmpeno,
            'peso_neto' => rg_fmt_gramos_lote($pesoNeto),
            'peso_bruto' => rg_fmt_gramos_lote($row['peso_bruto'] ?? 0),
            'merma' => rg_fmt_gramos_lote($row['merma'] ?? 0),
            'fecha_vencimiento' => $esEmpeno ? rg_fmt_fecha_lote($row['fecha_vencimiento'] ?? null) : '—',
            'cantidad_articulos' => (int) ($row['cantidad_articulos'] ?? 0),
            'peso_articulos' => rg_fmt_gramos_lote($pesoArticulos),
            'peso_coincide' => $coincide,
        ];
    }

    mysqli_stmt_close($stmt);
    mysqli_close($conexion);

    echo json_encode([
        'ok' => true,
        'total' => count($lotes),
        'lotes' => $lotes,
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'error' => $e->getMessage(),
    ]);
}
