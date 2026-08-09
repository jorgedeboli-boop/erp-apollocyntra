<?php
/**
 * JSON de cuotas ventas_plazos — DataTable AJAX.
 */
require_once __DIR__ . '/../../../include/session.php';
require_once __DIR__ . '/../../../include/functions.php';

header('Content-Type: application/json; charset=utf-8');

function vfp_fmt_fecha($valor)
{
    $v = trim((string) $valor);
    if ($v === '' || substr($v, 0, 10) === '0000-00-00') {
        return '—';
    }
    $t = strtotime($v);

    return $t ? date('d/m/Y', $t) : '—';
}   

function vfp_fmt_fecha_hora($valor)
{
    $v = trim((string) $valor);
    if ($v === '' || substr($v, 0, 10) === '0000-00-00') {
        return '—';
    }
    $t = strtotime($v);

    return $t ? date('d/m/Y H:i', $t) : '—';
}

function vfp_badge_estado($estado)
{
    $e = (string) $estado;
    if ($e === 'Pagado') {
        return '<div class="badge bg-success rounded-pill lh-xs badget-estados-tablas">Pagado</div>';
    }
    if ($e === 'Pendiente') {
        return '<div class="badge bg-label-primary rounded-pill lh-xs badget-estados-tablas">Pendiente</div>';
    }
    if ($e === 'Vencido') {
        return '<div class="badge bg-label-warning rounded-pill lh-xs badget-estados-tablas">Vencido</div>';
    }
    if ($e === 'Anulado') {
        return '<div class="badge bg-label-danger rounded-pill lh-xs badget-estados-tablas">VENTA ANULADA</div>';
    }
    $txt = htmlspecialchars($e !== '' ? $e : '—', ENT_QUOTES, 'UTF-8');

    return '<div class="badge bg-label-secondary rounded-pill lh-xs badget-estados-tablas">' . $txt . '</div>';
}

function vfp_badge_metodo($metodo)
{
    $m = strtolower(trim((string) $metodo));
    if ($m === 'tarjeta') {
        return '<div class="badge bg-success rounded-pill lh-xs badget-estados-tablas">Tarjeta</div>';
    }
    if ($m === 'contado') {
        return '<div class="badge bg-label-info rounded-pill lh-xs badget-estados-tablas">Contado</div>';
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

    return '<div class="badge bg-label-secondary rounded-pill lh-xs badget-estados-tablas">-------</div>';
}

function vfp_plazo_vencimiento_anterior_hoy($fecha_vencimiento)
{
    $fecha_venc = trim((string) $fecha_vencimiento);
    if ($fecha_venc === '' || substr($fecha_venc, 0, 10) === '0000-00-00') {
        return false;
    }
    $tsVenc = strtotime(substr($fecha_venc, 0, 10));

    return $tsVenc !== false && $tsVenc < strtotime('today');
}

function vfp_html_menu_plazo($idplazo, $estPl, $venta_anulada, $estVenta, $puede_editar, $bloquear_menu, $es_primer_plazo, $fecha_vencimiento_raw, $posicion_plazo = 0, $numero_plazos = 0, $todos_plazos_pagados = false)
{
    if ($idplazo <= 0 || $venta_anulada || !$puede_editar || $bloquear_menu) {
        return '';
    }

    $estV = strtolower(trim((string) $estVenta));
    $mostrar_recuperar = ($estPl === 'Pagado') && !$es_primer_plazo && !$todos_plazos_pagados;
    $es_plazo_superior_contrato = ($numero_plazos > 0 && $posicion_plazo > $numero_plazos);
    $mostrar_eliminar = false;
    if (!$todos_plazos_pagados && in_array($estPl, ['Pendiente', 'Vencido'], true)) {
        if ($es_plazo_superior_contrato) {
            $mostrar_eliminar = true;
        } elseif (
            in_array($estV, ['enfecha', 'vencido'], true)
            && !$es_primer_plazo
            && !($estPl === 'Vencido' && vfp_plazo_vencimiento_anterior_hoy($fecha_vencimiento_raw))
        ) {
            $mostrar_eliminar = true;
        }
    }
    $mostrar_editar = in_array($estPl, ['Pagado', 'Pendiente', 'Vencido'], true);

    if (!$mostrar_recuperar && !$mostrar_eliminar && !$mostrar_editar) {
        return '';
    }

    $html = '<div class="dropdown dropend">'
        . '<button type="button" class="btn btn-sm btn-icon dropdown-toggle hide-arrow" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">'
        . '<i class="icon-base ri ri-more-2-line"></i>'
        . '</button>'
        . '<div class="dropdown-menu dropdown-menu-end">';

    if ($mostrar_editar) {
        $html .= '<a class="dropdown-item" href="javascript:void(0);" onclick="abrirModalEditarPlazoVenta(' . (int) $idplazo . ')">'
            . '<i class="icon-base ri ri-edit-line me-2"></i>Editar plazo'
            . '</a>';
    }
    if ($mostrar_recuperar) {
        if ($mostrar_editar) {
            $html .= '<div class="dropdown-divider"></div>';
        }
        $html .= '<a class="dropdown-item" href="javascript:void(0);" onclick="recuperarPlazoVenta(' . (int) $idplazo . ')">'
            . '<i class="icon-base ri ri-loop-left-fill me-2"></i>Recuperar plazo'
            . '</a>';
    }
    if ($mostrar_eliminar) {
        if ($mostrar_editar || $mostrar_recuperar) {
            $html .= '<div class="dropdown-divider"></div>';
        }
        $html .= '<a class="dropdown-item text-danger" href="javascript:void(0);" onclick="eliminarPlazoVenta(' . (int) $idplazo . ')">'
            . '<i class="icon-base ri ri-delete-bin-line me-2"></i>Eliminar plazo'
            . '</a>';
    }

    $html .= '</div></div>';

    return $html;
}

function vfp_html_accion_plazo($idplazo, $estPl, $plazoesvencidas, $importe, $venta_anulada = false, $id_primer_plazo_vencido = 0)
{
    if ($venta_anulada) {
        return '....';
    }
    if ($idplazo <= 0) {
        return '----';
    }
    $imp = json_encode((float) $importe, JSON_UNESCAPED_UNICODE);
    if ($estPl === 'Vencido') {
        if ($id_primer_plazo_vencido > 0 && (int) $idplazo === (int) $id_primer_plazo_vencido) {
            return '<button type="button" class="btn btn-xs btn-xs-tablas btn-warning waves-effect waves-light" '
                . 'onclick="abrirModalCobrarPlazoVenta(' . $imp . ', ' . (int) $idplazo . ')">Cobrar plazo</button>';
        }

        return '<a class="btn btn-xs btn-xs-tablas btn-secondary waves-effect waves-light" href="javascript:void(0)">Cobrar plazo</a>';
    }
    if ($estPl === 'Pendiente') {
        if ((int) $plazoesvencidas === 0) {
            return '<button type="button" class="btn btn-xs btn-xs-tablas btn-success waves-effect waves-light" '
                . 'onclick="abrirModalCobrarPlazoVenta(' . $imp . ', ' . (int) $idplazo . ')">Cobrar plazo</button>';
        }

        return '<a class="btn btn-xs btn-xs-tablas btn-secondary waves-effect waves-light" href="javascript:void(0)">Cobrar plazo</a>';
    }

    return '----';
}

function vfp_html_comprobante_plazo($nombre_foto)
{
    $fn = trim((string) $nombre_foto);
    if ($fn === '') {
        return '----';
    }
    $base = basename($fn);
    if ($base === '' || !preg_match('/^[a-zA-Z0-9_\-\.]+$/', $base)) {
        return '----';
    }
    $js = htmlspecialchars($base, ENT_QUOTES, 'UTF-8');

    return '<button type="button" class="btn btn-xs btn-xs-tablas btn-success waves-effect waves-light" onclick="ampliarComprobantePlazoVentaFicha(\''
        . $js
        . '\')">Ver comprobante</button>';
}

$id_venta = isset($_POST['id_venta']) ? (int) $_POST['id_venta'] : 0;
if ($id_venta <= 0) {
    echo json_encode(['success' => false, 'message' => 'id_venta no válido', 'plazos_vencidos' => 0, 'data' => []]);
    exit;
}

$conexion = conectar_bd();
if (!$conexion) {
    echo json_encode(['success' => false, 'message' => 'Sin conexión', 'plazos_vencidos' => 0, 'data' => []]);
    exit;
}

$venta_anulada = false;
$estVenta = '';
$id_sucursal_venta = 0;
$numero_plazos = 0;
$stmtS = mysqli_prepare($conexion, 'SELECT estado, id_sucursal, numero_plazos FROM ventas WHERE id = ? LIMIT 1');
if ($stmtS) {
    mysqli_stmt_bind_param($stmtS, 'i', $id_venta);
    mysqli_stmt_execute($stmtS);
    $rs = mysqli_stmt_get_result($stmtS);
    $rowS = $rs ? mysqli_fetch_assoc($rs) : null;
    mysqli_stmt_close($stmtS);
    if ($rowS) {
        $estVenta = strtolower(trim((string) ($rowS['estado'] ?? '')));
        $id_sucursal_venta = (int) ($rowS['id_sucursal'] ?? 0);
        $numero_plazos = (int) ($rowS['numero_plazos'] ?? 0);
        $venta_anulada = ($estVenta === 'anulado' || $estVenta === 'anulada');
    }
}

$bloquear_menu_plazos = $venta_anulada;

$puede_editar_plazos = false;
if (isset($usuario_privilegio_id)) {
    $item_modulo = 'ventas';
    $puede_editar_plazos = usuario_puede_acceder_crud_tipo(
        $usuario_privilegio_id,
        crud_id_listar_modulo($item_modulo),
        'editar'
    );
}

$plazos_raw = [];

$stmtP = mysqli_prepare(
    $conexion,
    'SELECT * FROM ventas_plazos WHERE id_venta = ? ORDER BY id ASC'
);
if ($stmtP) {
    mysqli_stmt_bind_param($stmtP, 'i', $id_venta);
    mysqli_stmt_execute($stmtP);
    $resP = mysqli_stmt_get_result($stmtP);
    if ($resP) {
        while ($pl = mysqli_fetch_assoc($resP)) {
            $plazos_raw[] = $pl;
        }
    }
    mysqli_stmt_close($stmtP);
}

$todos_plazos_pagados = !empty($plazos_raw);
foreach ($plazos_raw as $pl) {
    if ((string) ($pl['estado'] ?? '') !== 'Pagado') {
        $todos_plazos_pagados = false;
        break;
    }
}

$tiene_factura_venta = venta_plazos_tiene_factura_generada($conexion, $id_venta, $id_sucursal_venta);
if (
    !$todos_plazos_pagados
    && $estVenta === 'vendido'
    && $tiene_factura_venta
) {
    $bloquear_menu_plazos = true;
}

$plazos_vencidos = 0;
$id_primer_plazo_vencido = 0;
foreach ($plazos_raw as $pl) {
    if ((string) ($pl['estado'] ?? '') === 'Vencido') {
        $plazos_vencidos++;
        if ($id_primer_plazo_vencido <= 0) {
            $id_primer_plazo_vencido = (int) ($pl['id'] ?? 0);
        }
    }
}

$id_primer_plazo = 0;
if (!empty($plazos_raw)) {
    $id_primer_plazo = (int) ($plazos_raw[0]['id'] ?? 0);
}

$rows = [];
foreach ($plazos_raw as $idx => $pl) {
    $idp = (int) ($pl['id'] ?? 0);
    $estPl = (string) ($pl['estado'] ?? '');
    $es_primer_plazo = ($id_primer_plazo > 0 && $idp === $id_primer_plazo);
    $posicion_plazo = $idx + 1;
    $rawCreado = trim((string) ($pl['fecha_creado'] ?? ''));
    if ($rawCreado === '' || substr($rawCreado, 0, 10) === '0000-00-00') {
        $f_creado = '—';
    } else {
        $f_creado = strlen($rawCreado) > 10
            ? vfp_fmt_fecha_hora($rawCreado)
            : vfp_fmt_fecha($rawCreado);
    }
    $f_cobrado = $pl['fecha_cobrado'] ?? '';
    $txt_cobr = (trim((string) $f_cobrado) !== '' && substr((string) $f_cobrado, 0, 10) !== '0000-00-00')
        ? (strlen(trim((string) $f_cobrado)) > 10
            ? vfp_fmt_fecha_hora($f_cobrado)
            : vfp_fmt_fecha($f_cobrado))
        : '—';
    $rows[] = [
        'menu' => vfp_html_menu_plazo(
            $idp,
            $estPl,
            $venta_anulada,
            $estVenta,
            $puede_editar_plazos,
            $bloquear_menu_plazos,
            $es_primer_plazo,
            $pl['fecha_vencimiento'] ?? '',
            $posicion_plazo,
            $numero_plazos,
            $todos_plazos_pagados
        ),
        'id' => $idp,
        'fecha_creado' => $f_creado,
        'importe' => (float) ($pl['importe'] ?? 0),
        'importe_fmt' => number_format((float) ($pl['importe'] ?? 0), 2, ',', '.') . ' €',
        'fecha_cobrado' => $txt_cobr,
        'fecha_vencido' => vfp_fmt_fecha($pl['fecha_vencido'] ?? ''),
        'fecha_vencimiento' => vfp_fmt_fecha($pl['fecha_vencimiento'] ?? ''),
        'estado' => $estPl,
        'estado_badge' => vfp_badge_estado($estPl),
        'metodo_pago' => (string) ($pl['metodo_pago'] ?? ''),
        'metodo_badge' => vfp_badge_metodo($pl['metodo_pago'] ?? ''),
        'comprobante_nombre' => trim((string) ($pl['comprobante_plazo'] ?? '')),
        'comprobante_pago' => vfp_html_comprobante_plazo($pl['comprobante_plazo'] ?? ''),
        'acciones' => vfp_html_accion_plazo($idp, $estPl, $plazos_vencidos, (float) ($pl['importe'] ?? 0), $venta_anulada, $id_primer_plazo_vencido),
    ];
}
mysqli_close($conexion);

$total_plazos_historico = count($plazos_raw);
$mostrar_boton_anadir_plazo = $total_plazos_historico === 1
    && !$venta_anulada
    && !$bloquear_menu_plazos
    && $puede_editar_plazos
    && in_array($estVenta, ['enfecha', 'vencido'], true);

echo json_encode(
    [
        'success' => true,
        'plazos_vencidos' => $plazos_vencidos,
        'mostrar_boton_anadir_plazo' => $mostrar_boton_anadir_plazo,
        'data' => $rows,
    ],
    JSON_UNESCAPED_UNICODE
);
