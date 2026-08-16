<?php
/**
 * JSON adelantos_capital_venta — DataTable AJAX (ficha venta a plazos).
 */
require_once __DIR__ . '/../../../include/session.php';
require_once __DIR__ . '/../../../include/functions.php';

header('Content-Type: application/json; charset=utf-8');

function acvf_fmt_fecha_hora($valor)
{
    $v = trim((string) $valor);
    if ($v === '' || substr($v, 0, 10) === '0000-00-00') {
        return '—';
    }
    $t = strtotime($v);

    return $t ? date('d/m/Y H:i', $t) : '—';
}

function acvf_fmt_euro($n)
{
    return number_format((float) $n, 2, ',', '.') . ' €';
}

function acvf_badge_forma($forma)
{
    $m = strtolower(trim((string) $forma));
    if ($m === 'tarjeta') {
        return '<div class="badge bg-success rounded-pill lh-xs badget-estados-tablas">Tarjeta</div>';
    }
    if ($m === 'efectivo' || $m === 'contado') {
        return '<div class="badge bg-label-info rounded-pill lh-xs badget-estados-tablas">Efectivo</div>';
    }
    if ($m === 'bizum') {
        return '<div class="badge bg-label-info rounded-pill lh-xs badget-estados-tablas">Bizum</div>';
    }
    if ($m === 'transferencia') {
        return '<div class="badge bg-label-info rounded-pill lh-xs badget-estados-tablas">Transferencia</div>';
    }
    if ($m === 'combinado') {
        return '<div class="badge bg-label-info rounded-pill lh-xs badget-estados-tablas">Combinado</div>';
    }
    if ($m === 'pendiente') {
        return '<div class="badge bg-label-secondary rounded-pill lh-xs badget-estados-tablas">Pendiente</div>';
    }
    $txt = htmlspecialchars($forma !== '' ? $forma : '—', ENT_QUOTES, 'UTF-8');

    return '<div class="badge bg-label-secondary rounded-pill lh-xs badget-estados-tablas">' . $txt . '</div>';
}

function acvf_html_comprobante_pago($nombre_foto, $forma_de_pago)
{
    $fn = trim((string) $nombre_foto);
    $forma = strtolower(trim((string) $forma_de_pago));
    if ($fn === '' || $forma === 'efectivo' || $forma === 'pendiente' || $forma === '') {
        return '----';
    }
    $base = basename($fn);
    if ($base === '' || !preg_match('/^[a-zA-Z0-9_\-\.]+$/', $base)) {
        return '----';
    }
    $js = htmlspecialchars($base, ENT_QUOTES, 'UTF-8');

    return '<button type="button" class="btn btn-xs btn-xs-tablas btn-success waves-effect waves-light" onclick="ampliarComprobanteAdelantoVentaFicha(\''
        . $js
        . '\')">Ver comprobante</button>';
}

$id_venta = isset($_POST['id_venta']) ? (int) $_POST['id_venta'] : 0;
if ($id_venta <= 0) {
    echo json_encode(['success' => false, 'message' => 'id_venta no válido', 'data' => []]);
    exit;
}

$conexion = conectar_bd();
if (!$conexion) {
    echo json_encode(['success' => false, 'message' => 'Sin conexión', 'data' => []]);
    exit;
}

$sql = 'SELECT a.id_adelanto_capital, a.fecha_adelanto, a.importe_adelanto, a.capital_antiguo, '
    . 'a.importe_plazo_antiguo, a.nuevo_capital, a.nuevo_importe_plazo, a.forma_de_pago, a.nombre_foto '
    . 'FROM adelantos_capital_venta a '
    . 'WHERE a.id_venta_adelanto = ? '
    . 'ORDER BY a.fecha_adelanto ASC, a.id_adelanto_capital ASC';

$stmt = mysqli_prepare($conexion, $sql);
if (!$stmt) {
    mysqli_close($conexion);
    echo json_encode([
        'success' => false,
        'message' => 'Consulta no disponible: ' . mysqli_error($conexion),
        'data' => [],
    ]);
    exit;
}

mysqli_stmt_bind_param($stmt, 'i', $id_venta);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);

$rows = [];
if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        $rows[] = [
            'num' => (int) ($row['id_adelanto_capital'] ?? 0),
            'fecha_adelanto' => acvf_fmt_fecha_hora($row['fecha_adelanto'] ?? ''),
            'fecha_adelanto_raw' => (string) ($row['fecha_adelanto'] ?? ''),
            'importe_adelanto' => (float) ($row['importe_adelanto'] ?? 0),
            'importe_adelanto_fmt' => acvf_fmt_euro($row['importe_adelanto'] ?? 0),
            'capital_antiguo' => (float) ($row['capital_antiguo'] ?? 0),
            'capital_antiguo_fmt' => acvf_fmt_euro($row['capital_antiguo'] ?? 0),
            'importe_plazo_antiguo' => (float) ($row['importe_plazo_antiguo'] ?? 0),
            'importe_plazo_antiguo_fmt' => acvf_fmt_euro($row['importe_plazo_antiguo'] ?? 0),
            'nuevo_capital' => (float) ($row['nuevo_capital'] ?? 0),
            'nuevo_capital_fmt' => acvf_fmt_euro($row['nuevo_capital'] ?? 0),
            'nuevo_importe_plazo' => (float) ($row['nuevo_importe_plazo'] ?? 0),
            'nuevo_importe_plazo_fmt' => acvf_fmt_euro($row['nuevo_importe_plazo'] ?? 0),
            'forma_de_pago' => (string) ($row['forma_de_pago'] ?? ''),
            'forma_badge' => acvf_badge_forma($row['forma_de_pago'] ?? ''),
            'comprobante_pago' => acvf_html_comprobante_pago($row['nombre_foto'] ?? '', $row['forma_de_pago'] ?? ''),
            'comprobante_cliente' => '----',
        ];
    }
}

mysqli_stmt_close($stmt);
mysqli_close($conexion);

echo json_encode(['success' => true, 'data' => $rows], JSON_UNESCAPED_UNICODE);
