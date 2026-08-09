<?php
/**
 * Polling: estados/filas actualizadas + detección de autorizaciones pendientes nuevas (id mayor).
 */
require_once __DIR__ . '/../../include/session.php';
require_once __DIR__ . '/../../include/functions.php';

if (!function_exists('renderComprobanteAutorizacion')) {
    require_once __DIR__ . '/autorizaciones_comprobante_helper.php';
}

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

$tipo = isset($_POST['tipo']) ? trim((string) $_POST['tipo']) : '';
$idsRaw = isset($_POST['ids']) ? $_POST['ids'] : '[]';
$maxId = isset($_POST['max_id']) ? (int) $_POST['max_id'] : 0;

if (is_string($idsRaw)) {
    $ids = json_decode($idsRaw, true);
} else {
    $ids = $idsRaw;
}
if (!is_array($ids)) {
    $ids = [];
}
$ids = array_values(array_unique(array_filter(array_map('intval', $ids), function ($id) {
    return $id > 0;
})));

if ($tipo === '') {
    echo json_encode(['success' => false, 'error' => 'Tipo requerido']);
    exit;
}

$filtroSucursal = isset($_POST['filtro_sucursal']) ? trim((string) $_POST['filtro_sucursal']) : '';
$filtroEstado = isset($_POST['filtro_estado']) ? trim((string) $_POST['filtro_estado']) : '';
$filtroEstadoSms = isset($_POST['filtro_estado_sms']) ? trim((string) $_POST['filtro_estado_sms']) : '';
$filtroEstadoAutorizado = isset($_POST['filtro_estado_autorizado']) ? trim((string) $_POST['filtro_estado_autorizado']) : '';

try {
    $conexion = conectar_bd();
    if (!$conexion) {
        throw new Exception('Error de conexión a la base de datos');
    }

    $filas = [];
    if (!empty($ids)) {
        $filas = poll_obtener_filas_por_ids($conexion, $tipo, $ids);
    }

    $hayNuevas = poll_hay_pendientes_nuevas(
        $conexion,
        $tipo,
        $maxId,
        $filtroSucursal,
        $filtroEstado,
        $filtroEstadoSms,
        $filtroEstadoAutorizado
    );

    mysqli_close($conexion);

    echo json_encode([
        'success' => true,
        'filas' => $filas,
        'hay_nuevas' => $hayNuevas,
    ]);
} catch (Exception $e) {
    if (isset($conexion)) {
        mysqli_close($conexion);
    }
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
    ]);
}

/**
 * @return array<int, array<string, mixed>>
 */
function poll_obtener_filas_por_ids($conexion, $tipo, array $ids)
{
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $types = str_repeat('i', count($ids));
    $out = [];

    switch ($tipo) {
        case 'gastos':
            $sql = "SELECT ag.id, s.nombre_sucursal, ag.estado, ag.codigo, ag.fecha, u.nombre_usuario,
                           ag.grupo, ag.concepto, ag.salida, ag.fecha_uso, ag.id_apunte, ag.imagen
                    FROM autorizaciones_gastos ag
                    LEFT JOIN sucursal s ON ag.sucursal = s.id_sucursal
                    LEFT JOIN usuarios u ON CAST(ag.usuario AS UNSIGNED) = u.id_usuario
                    WHERE ag.id IN ($placeholders)";
            break;
        case 'ventas':
            $sql = "SELECT apv.id, s.nombre_sucursal, apv.estado, apv.fecha, u.nombre_usuario, apv.id_articulo,
                           apv.intereses_originales, apv.intereses_nuevos, apv.precio_original, apv.precio_nuevo, apv.sucursal
                    FROM autorizaciones_porcentajes_ventas apv
                    LEFT JOIN sucursal s ON apv.sucursal = s.id_sucursal
                    LEFT JOIN usuarios u ON CAST(apv.usuario AS UNSIGNED) = u.id_usuario
                    WHERE apv.id IN ($placeholders)";
            break;
        case 'empenos':
            $sql = "SELECT ap.id_autorizacion, s.nombre_sucursal, ap.estado_autorizacion, ap.fecha_autorizacion,
                           u.nombre_usuario, ap.lote_autorizacion, ap.intereses_originales, ap.intereses_lote, ap.sucursal_autorizacion
                    FROM autorizaciones_porcentajes ap
                    LEFT JOIN sucursal s ON ap.sucursal_autorizacion = s.id_sucursal
                    LEFT JOIN usuarios u ON CAST(ap.usuario AS UNSIGNED) = u.id_usuario
                    WHERE ap.id_autorizacion IN ($placeholders)";
            break;
        case 'devoluciones':
            $sql = "SELECT ad.id_autorizacion, ad.codigo_autorizacion, s.nombre_sucursal, ad.estado_autorizacion,
                           ad.fecha_autorizacion, u.nombre_usuario, ad.sku_articulo_devolucion, ad.venta_id, ad.rel_id_devolucion
                    FROM autorizaciones_devoluciones ad
                    LEFT JOIN sucursal s ON ad.sucursal_autorizacion = s.id_sucursal
                    LEFT JOIN usuarios u ON CAST(ad.usuario_autorizacion AS UNSIGNED) = u.id_usuario
                    WHERE ad.id_autorizacion IN ($placeholders)";
            break;
        case 'descuentos':
            $sql = "SELECT ad.id, s.nombre_sucursal, u.nombre_usuario, ad.codigo, ad.id_articulo, ad.descripcion,
                           ad.estado, ad.fecha, ad.precio_original, ad.precio_nuevo, ad.sucursal
                    FROM autorizaciones_descuento_articulo_venta ad
                    LEFT JOIN sucursal s ON ad.sucursal = s.id_sucursal
                    LEFT JOIN usuarios u ON CAST(ad.usuario AS UNSIGNED) = u.id_usuario
                    WHERE ad.id IN ($placeholders)";
            break;
        case 'firmas':
            $sql = "SELECT s.id_signature, suc.nombre_sucursal, s.auth_no_signature, suc.codigo_firmas, s.createDate,
                           u.nombre_usuario, s.typeItem, s.ItemId, s.recibe_euros
                    FROM Signatures s
                    LEFT JOIN sucursal suc ON s.sucursalSignature = suc.id_sucursal
                    LEFT JOIN usuarios u ON s.userCreate = u.id_usuario
                    WHERE s.id_signature IN ($placeholders)";
            break;
        case 'sms':
            $sql = "SELECT sms.id_sms, s.nombre_sucursal, sms.codigo_sms, sms.estado_sms, sms.estado_codigo,
                           sms.fecha_sms, u.usuario AS nombre_usuario,
                           CONCAT(c.nombre, ' ', c.apellido) AS nombre_cliente,
                           sms.rel_item_sms, sms.movil_sms, sms.type_item_sms, sms.mensaje_sms,
                           sms.autorizado_central, sms.estado_autorizado, sms.surusal_sms
                    FROM sms_send sms
                    LEFT JOIN sucursal s ON sms.surusal_sms = s.id_sucursal
                    LEFT JOIN usuarios u ON sms.usuario_sms = u.id_usuario
                    LEFT JOIN clientes c ON sms.cliente_sms = c.id_cliente
                    WHERE sms.id_sms IN ($placeholders) AND NOT sms.type_item_sms = 'vencimiento'";
            break;
        default:
            throw new Exception('Tipo de autorización no válido');
    }

    $stmt = mysqli_prepare($conexion, $sql);
    if (!$stmt) {
        throw new Exception('Error al preparar consulta de filas');
    }
    mysqli_stmt_bind_param($stmt, $types, ...$ids);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    while ($row = mysqli_fetch_assoc($result)) {
        $out[(int) poll_row_id($tipo, $row)] = poll_format_row_datatable($tipo, $row);
    }
    mysqli_stmt_close($stmt);

    return $out;
}

function poll_row_id($tipo, array $row)
{
    switch ($tipo) {
        case 'empenos':
        case 'devoluciones':
            return $row['id_autorizacion'];
        case 'firmas':
            return $row['id_signature'];
        case 'sms':
            return $row['id_sms'];
        default:
            return $row['id'];
    }
}

/**
 * Formato de fila igual que load_list (array DataTables).
 *
 * @return array<int, mixed>
 */
function poll_format_row_datatable($tipo, array $row)
{
    switch ($tipo) {
        case 'gastos':
            $fechaUso = $row['fecha_uso'] && $row['fecha_uso'] !== '0000-00-00 00:00:00' ? $row['fecha_uso'] : null;
            return [
                (int) $row['id'],
                htmlspecialchars($row['nombre_sucursal'] ?? 'Sin sucursal'),
                $row['estado'],
                htmlspecialchars($row['codigo'] ?? '-'),
                $row['fecha'],
                htmlspecialchars($row['nombre_usuario'] ?? 'Sin usuario'),
                htmlspecialchars($row['grupo'] ?? '-'),
                htmlspecialchars($row['concepto'] ?? '-'),
                $row['salida'] ?? 0,
                $fechaUso,
                renderComprobanteAutorizacion($row['imagen'], $row['id_apunte'], $row['id']),
                [
                    'id_apunte' => $row['id_apunte'],
                    'imagen' => $row['imagen'],
                ],
            ];
        case 'ventas':
            return [
                (int) $row['id'],
                htmlspecialchars($row['nombre_sucursal'] ?? 'Sin sucursal'),
                $row['estado'],
                $row['fecha'],
                htmlspecialchars($row['nombre_usuario'] ?? 'Sin usuario'),
                $row['id_articulo'],
                $row['intereses_originales'] ?? 0,
                $row['intereses_nuevos'] ?? 0,
                $row['precio_original'] ?? 0,
                $row['precio_nuevo'] ?? 0,
                [
                    'id' => $row['id'],
                    'intereses_originales' => $row['intereses_originales'] ?? 0,
                    'intereses_nuevos' => $row['intereses_nuevos'] ?? 0,
                    'precio_original' => $row['precio_original'] ?? 0,
                    'precio_nuevo' => $row['precio_nuevo'] ?? 0,
                    'id_sucursal' => $row['sucursal'],
                ],
            ];
        case 'empenos':
            return [
                (int) $row['id_autorizacion'],
                htmlspecialchars($row['nombre_sucursal'] ?? 'Sin sucursal'),
                $row['estado_autorizacion'],
                $row['fecha_autorizacion'],
                htmlspecialchars($row['nombre_usuario'] ?? 'Sin usuario'),
                $row['lote_autorizacion'],
                $row['intereses_originales'],
                $row['intereses_lote'],
                [
                    'id_autorizacion' => $row['id_autorizacion'],
                    'intereses_originales' => $row['intereses_originales'],
                    'intereses_lote' => $row['intereses_lote'],
                    'id_sucursal' => $row['sucursal_autorizacion'],
                ],
            ];
        case 'devoluciones':
            return [
                (int) $row['id_autorizacion'],
                htmlspecialchars($row['codigo_autorizacion'] ?? '-'),
                htmlspecialchars($row['nombre_sucursal'] ?? 'Sin sucursal'),
                $row['estado_autorizacion'],
                $row['fecha_autorizacion'],
                htmlspecialchars($row['nombre_usuario'] ?? 'Sin usuario'),
                htmlspecialchars($row['sku_articulo_devolucion'] ?? '-'),
                $row['venta_id'],
                $row['rel_id_devolucion'],
            ];
        case 'descuentos':
            return [
                (int) $row['id'],
                htmlspecialchars($row['nombre_sucursal'] ?? 'Sin sucursal'),
                htmlspecialchars($row['nombre_usuario'] ?? 'Sin usuario'),
                htmlspecialchars($row['codigo'] ?? '-'),
                $row['id_articulo'],
                htmlspecialchars($row['descripcion'] ?? '-'),
                $row['estado'],
                $row['fecha'],
                $row['precio_original'] ?? 0,
                $row['precio_nuevo'] ?? 0,
                [
                    'id' => $row['id'],
                    'estado' => $row['estado'],
                    'precio_original' => $row['precio_original'] ?? 0,
                    'precio_nuevo' => $row['precio_nuevo'] ?? 0,
                    'id_sucursal' => $row['sucursal'],
                ],
            ];
        case 'firmas':
            $estado = $row['auth_no_signature'] === 'true' ? 'autorizada' : 'pendiente';
            return [
                (int) $row['id_signature'],
                htmlspecialchars($row['nombre_sucursal'] ?? 'Sin sucursal'),
                $estado,
                htmlspecialchars($row['codigo_firmas'] ?? '-'),
                $row['createDate'],
                htmlspecialchars($row['nombre_usuario'] ?? 'Sin usuario'),
                htmlspecialchars($row['typeItem'] ?? '-'),
                $row['ItemId'],
                $row['recibe_euros'] ?? 0,
            ];
        case 'sms':
            return [
                (int) $row['id_sms'],
                htmlspecialchars($row['nombre_sucursal'] ?? 'Sin sucursal'),
                $row['codigo_sms'],
                $row['estado_sms'],
                $row['estado_codigo'],
                $row['fecha_sms'],
                htmlspecialchars($row['nombre_usuario'] ?? 'Sin usuario'),
                htmlspecialchars($row['nombre_cliente'] ?? 'Sin cliente'),
                $row['rel_item_sms'],
                htmlspecialchars($row['movil_sms']),
                htmlspecialchars($row['type_item_sms']),
                htmlspecialchars($row['mensaje_sms']),
                [
                    'id_sms' => $row['id_sms'],
                    'rel_item_sms' => $row['rel_item_sms'],
                    'id_sucursal' => $row['surusal_sms'],
                    'autorizado_central' => $row['autorizado_central'],
                    'estado_autorizado' => $row['estado_autorizado'],
                ],
            ];
        default:
            return [];
    }
}

function poll_hay_pendientes_nuevas($conexion, $tipo, $maxId, $filtroSucursal, $filtroEstado, $filtroEstadoSms, $filtroEstadoAutorizado)
{
    $params = [];
    $types = '';
    $where = '';

    switch ($tipo) {
        case 'gastos':
            $from = "FROM autorizaciones_gastos ag LEFT JOIN sucursal s ON ag.sucursal = s.id_sucursal WHERE 1=1";
            $where = " AND ag.id > ? AND ag.estado = 'pendiente'";
            if ($filtroEstado !== '' && $filtroEstado !== 'pendiente') {
                return false;
            }
            if ($filtroSucursal !== '') {
                $where .= " AND s.nombre_sucursal = ?";
            }
            break;
        case 'ventas':
            $from = "FROM autorizaciones_porcentajes_ventas apv LEFT JOIN sucursal s ON apv.sucursal = s.id_sucursal WHERE 1=1";
            $where = " AND apv.id > ? AND apv.estado = 'pendiente'";
            if ($filtroEstado !== '' && $filtroEstado !== 'pendiente') {
                return false;
            }
            if ($filtroSucursal !== '') {
                $where .= " AND s.nombre_sucursal = ?";
            }
            break;
        case 'empenos':
            $from = "FROM autorizaciones_porcentajes ap LEFT JOIN sucursal s ON ap.sucursal_autorizacion = s.id_sucursal WHERE 1=1";
            $where = " AND ap.id_autorizacion > ? AND ap.estado_autorizacion = 'pendiente'";
            if ($filtroEstado !== '' && $filtroEstado !== 'pendiente') {
                return false;
            }
            if ($filtroSucursal !== '') {
                $where .= " AND s.nombre_sucursal = ?";
            }
            break;
        case 'devoluciones':
            $from = "FROM autorizaciones_devoluciones ad LEFT JOIN sucursal s ON ad.sucursal_autorizacion = s.id_sucursal WHERE 1=1";
            $where = " AND ad.id_autorizacion > ? AND ad.estado_autorizacion = 'pendiente'";
            if ($filtroEstado !== '' && $filtroEstado !== 'pendiente') {
                return false;
            }
            if ($filtroSucursal !== '') {
                $where .= " AND s.nombre_sucursal = ?";
            }
            break;
        case 'descuentos':
            $from = "FROM autorizaciones_descuento_articulo_venta ad LEFT JOIN sucursal s ON ad.sucursal = s.id_sucursal WHERE 1=1";
            $where = " AND ad.id > ? AND ad.estado = 'pendiente'";
            if ($filtroEstado !== '' && $filtroEstado !== 'pendiente') {
                return false;
            }
            if ($filtroSucursal !== '') {
                $where .= " AND s.nombre_sucursal = ?";
            }
            break;
        case 'firmas':
            $from = "FROM Signatures s LEFT JOIN sucursal suc ON s.sucursalSignature = suc.id_sucursal WHERE 1=1";
            $where = " AND s.id_signature > ? AND s.auth_no_signature = 'false'";
            if ($filtroEstado !== '' && $filtroEstado !== 'pendiente') {
                return false;
            }
            if ($filtroSucursal !== '') {
                $where .= " AND suc.nombre_sucursal = ?";
            }
            break;
        case 'sms':
            $from = "FROM sms_send sms LEFT JOIN sucursal s ON sms.surusal_sms = s.id_sucursal
                     WHERE NOT sms.type_item_sms = 'vencimiento'";
            $where = " AND sms.id_sms > ? AND sms.estado_autorizado NOT IN ('true','cancelada')
                      AND ((sms.estado_sms = 'true' AND sms.estado_codigo = 'false') OR sms.autorizado_central = 'true')";
            if ($filtroEstadoAutorizado === 'true' || $filtroEstadoAutorizado === 'cancelada') {
                return false;
            }
            if ($filtroSucursal !== '') {
                $where .= " AND s.nombre_sucursal = ?";
            }
            if ($filtroEstadoSms !== '') {
                $where .= " AND sms.estado_sms = ?";
            }
            break;
        default:
            return false;
    }

    $params[] = $maxId;
    $types .= 'i';
    if ($filtroSucursal !== '') {
        $params[] = $filtroSucursal;
        $types .= 's';
    }
    if ($tipo === 'sms' && $filtroEstadoSms !== '') {
        $params[] = $filtroEstadoSms;
        $types .= 's';
    }

    $sql = "SELECT 1 " . $from . $where . " LIMIT 1";
    $stmt = mysqli_prepare($conexion, $sql);
    if (!$stmt) {
        return false;
    }
    if ($types !== '') {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $hay = $result && mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    return (bool) $hay;
}
