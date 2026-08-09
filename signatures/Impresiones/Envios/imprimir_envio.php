<?php
/**
 * Documento de envío: PDF apaisado (mPDF) o vista HTML con diálogo de impresión (autoprint=1).
 * GET: id_envio (obligatorio), sucursal_remitente (opcional; debe coincidir con el envío si se envía).
 * GET: autoprint=1 — misma información en HTML y se abre el diálogo de impresión del navegador.
 * Sin autoprint: PDF en línea (mPDF).
 */

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    header('HTTP/1.1 405 Method Not Allowed');
    exit;
}

require_once __DIR__ . '/../../include/session.php';
require_once __DIR__ . '/../../include/functions.php';

$id_envio = isset($_GET['id_envio']) ? (int) $_GET['id_envio'] : 0;
$sucursal_get = isset($_GET['sucursal_remitente']) ? (int) $_GET['sucursal_remitente'] : 0;
$autoprint = isset($_GET['autoprint']) && (string) $_GET['autoprint'] === '1';

if ($id_envio <= 0) {
    header('Location: ../../dashboard.php');
    exit;
}

$conexion = conectar_bd();
if (!$conexion || !($conexion instanceof mysqli)) {
    http_response_code(500);
    echo 'Error de conexión';
    exit;
}

mysqli_set_charset($conexion, 'utf8mb4');

$query_envio = "
    SELECT
        e.*,
        s.nombre_sucursal
    FROM envios e
    INNER JOIN sucursal s ON s.id_sucursal = e.sucursal_remitente
    WHERE e.id_envio = ?
    LIMIT 1
";
$stmt_e = mysqli_prepare($conexion, $query_envio);
if (!$stmt_e) {
    mysqli_close($conexion);
    http_response_code(500);
    echo 'Error en consulta';
    exit;
}
mysqli_stmt_bind_param($stmt_e, 'i', $id_envio);
mysqli_stmt_execute($stmt_e);
$rsItem = mysqli_stmt_fetch_assoc_compat($stmt_e);
mysqli_stmt_close($stmt_e);

if (!$rsItem) {
    mysqli_close($conexion);
    header('Location: ../../dashboard.php');
    exit;
}

$sucursal_remitente = (int) $rsItem['sucursal_remitente'];
if ($sucursal_get > 0 && $sucursal_get !== $sucursal_remitente) {
    mysqli_close($conexion);
    http_response_code(403);
    echo 'Sucursal no coincide con el envío';
    exit;
}

if (
    isset($_SESSION['sucursal_section'], $_SESSION['usuario_sucursal'], $_SESSION['usuario_root'])
    && $_SESSION['sucursal_section'] === 'true'
    && $_SESSION['usuario_root'] !== 'true'
    && (int) $_SESSION['usuario_sucursal'] !== $sucursal_remitente
) {
    mysqli_close($conexion);
    http_response_code(403);
    echo 'No autorizado';
    exit;
}

$tabla_lotes = 'lotes_' . $sucursal_remitente;

/**
 * @param mixed $val
 */
function imprimir_envio_fmt_fecha($val): string
{
    if ($val === null || $val === '' || $val === '0000-00-00' || $val === '0000-00-00 00:00:00') {
        return '-----';
    }
    $ts = strtotime((string) $val);

    return $ts ? date('d-m-Y', $ts) : '-----';
}

/**
 * @param mixed $estado
 */
function imprimir_envio_etiqueta_estado($estado): string
{
    $e = (string) $estado;
    $map = [
        'pendiente_enviar' => 'Pendiente enviar',
        'pendiente_envio' => 'Pendiente envío',
        'enviado_central' => 'Enviado central',
        'recibido_central' => 'Recibido central',
        'devuelto_central' => 'Devuelto central',
        'recibido_sucursal' => 'Recibido sucursal',
        'envio_cancelado' => 'Envío cancelado',
        'envio_rechazado' => 'Envío rechazado',
        'envio_auditado' => 'Envío auditado',
        'auditando_envio' => 'Auditando envío',
        'false' => '-----',
    ];

    return $map[$e] ?? $e;
}

$nombre_sucursal = (string) ($rsItem['nombre_sucursal'] ?? '');
$fecha_envio = imprimir_envio_fmt_fecha($rsItem['fecha_envio'] ?? null);
$cantidad_lotes = (int) ($rsItem['cantidad_lotes'] ?? 0);
$estado_envio = imprimir_envio_etiqueta_estado($rsItem['estado_envio'] ?? '');
$cantidad_articulos = (int) ($rsItem['cantidad_articulos'] ?? 0);
$enviado_por = (int) ($rsItem['enviado_por'] ?? 0);
$recibido_por = (int) ($rsItem['recibido_por'] ?? 0);

$observaciones_envio = (string) ($rsItem['observaciones_envio'] ?? '');

$total_renovaciones = $rsItem['total_renovaciones'] ?? '';
$total_retiradas = $rsItem['total_retiradas'] ?? '';
$cantidad_renovaciones = (int) ($rsItem['cantidad_renovaciones'] ?? 0);
$cantidad_retiradas = (int) ($rsItem['cantidad_retiradas'] ?? 0);
$semana_numero = $rsItem['semana_numero'] ?? '';
$fecha_compra_desde = imprimir_envio_fmt_fecha($rsItem['fecha_compra_desde'] ?? null);
$fecha_compra_hasta = imprimir_envio_fmt_fecha($rsItem['fecha_compra_hasta'] ?? null);

$fecha_semanas_desde = $rsItem['fecha_semanas_desde'] ?? null;
$fecha_semanas_hasta = $rsItem['fecha_semanas_hasta'] ?? null;
$fecha_desde_parset = imprimir_envio_fmt_fecha($fecha_semanas_desde);
$fecha_hasta_parset = imprimir_envio_fmt_fecha($fecha_semanas_hasta);

$semanas_enviadas = $rsItem['semanas_enviadas'] ?? '';
$solo_una_semana = (string) ($rsItem['solo_una_semana'] ?? 'true');

$cantidad_compras = (int) ($rsItem['cantidad_compras'] ?? 0);
$cantidad_empenios = (int) ($rsItem['cantidad_empenios'] ?? 0);

$nombre_usuario_recibido = '-----';
$nombre_usuario = '-----';
$ids_u = array_unique(
    array_filter([(int) $enviado_por, (int) $recibido_por], static function ($id) {
        return $id > 0;
    })
);
if ($ids_u !== []) {
    $in_list = implode(',', $ids_u);
    $sql_u = "SELECT id_usuario, usuario FROM usuarios WHERE id_usuario IN ($in_list)";
    $res_u = mysqli_query($conexion, $sql_u);
    if ($res_u) {
        $map_u = [];
        while ($row_u = mysqli_fetch_assoc($res_u)) {
            $map_u[(int) $row_u['id_usuario']] = (string) $row_u['usuario'];
        }
        mysqli_free_result($res_u);
        if (isset($map_u[$enviado_por])) {
            $nombre_usuario = $map_u[$enviado_por];
        }
        if (isset($map_u[$recibido_por])) {
            $nombre_usuario_recibido = $map_u[$recibido_por];
        }
    }
}

$total_lotes_oro = 0;
$total_lotes_plata = 0;
$peso_oro_parset = 0.0;
$sumar_oro_bruto_parset = 0.0;
$cantidad_articulos_oro_parset = 0;
$precio_compra_total_oro = 0.0;
$peso_plata_parset = 0.0;
$sumar_plata_bruto_parset = 0.0;
$cantidad_articulos_plata_parset = 0;
$precio_compra_total_plata = 0.0;

$sql_agg = "
    SELECT
        tipo_de_lote,
        COUNT(*) AS n_lotes,
        SUM(IFNULL(cantidad_articulos, 0)) AS sum_art,
        SUM(IFNULL(peso_bruto, 0)) AS sum_bruto,
        SUM(IFNULL(peso, 0)) AS sum_peso,
        SUM(IFNULL(precio_compra, 0)) AS sum_precio
    FROM `{$tabla_lotes}`
    WHERE envio_numero = ?
    GROUP BY tipo_de_lote
";
$stmt_agg = mysqli_prepare($conexion, $sql_agg);
if ($stmt_agg) {
    mysqli_stmt_bind_param($stmt_agg, 'i', $id_envio);
    mysqli_stmt_execute($stmt_agg);
    $res_agg = mysqli_stmt_get_result($stmt_agg);
    if ($res_agg) {
        while ($agg = mysqli_fetch_assoc($res_agg)) {
            $tipo = strtolower((string) ($agg['tipo_de_lote'] ?? ''));
            $n = (int) ($agg['n_lotes'] ?? 0);
            $art = (int) ($agg['sum_art'] ?? 0);
            $bruto = (float) ($agg['sum_bruto'] ?? 0);
            $peso = (float) ($agg['sum_peso'] ?? 0);
            $precio = (float) ($agg['sum_precio'] ?? 0);
            if ($tipo === 'oro') {
                $total_lotes_oro = $n;
                $cantidad_articulos_oro_parset = $art;
                $sumar_oro_bruto_parset = $bruto;
                $peso_oro_parset = $peso;
                $precio_compra_total_oro = $precio;
            } elseif ($tipo === 'plata') {
                $total_lotes_plata = $n;
                $cantidad_articulos_plata_parset = $art;
                $sumar_plata_bruto_parset = $bruto;
                $peso_plata_parset = $peso;
                $precio_compra_total_plata = $precio;
            }
        }
        mysqli_free_result($res_agg);
    }
    mysqli_stmt_close($stmt_agg);
}

$filas_lotes = [];
$sql_lotes = "
    SELECT
        id_lote,
        tipo_de_lote,
        compra_opcion,
        fecha_compra,
        fecha_liberado,
        fecha_perdido,
        cantidad_articulos,
        peso_bruto,
        peso,
        precio_compra,
        semana_numero
    FROM `{$tabla_lotes}`
    WHERE envio_numero = ?
    ORDER BY tipo_de_lote ASC, id_lote ASC
";
$stmt_l = mysqli_prepare($conexion, $sql_lotes);
if ($stmt_l) {
    mysqli_stmt_bind_param($stmt_l, 'i', $id_envio);
    mysqli_stmt_execute($stmt_l);
    $res_l = mysqli_stmt_get_result($stmt_l);
    if ($res_l) {
        while ($l = mysqli_fetch_assoc($res_l)) {
            $filas_lotes[] = $l;
        }
        mysqli_free_result($res_l);
    }
    mysqli_stmt_close($stmt_l);
}

mysqli_close($conexion);

$autoload = __DIR__ . '/../../vendor/autoload.php';
if (!is_readable($autoload)) {
    http_response_code(500);
    echo 'Dependencias PDF no instaladas (vendor/autoload.php).';
    exit;
}
require_once $autoload;

$h = static function (string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
};

$titulo_semana = $solo_una_semana === 'false'
    ? 'Semana principal Nº ' . $h((string) $semana_numero)
    : 'Semana Nº ' . $h((string) $semana_numero);

$rows_detalle = '';
foreach ($filas_lotes as $idx => $lotes) {
    $bg = (($idx + 1) % 2 === 0) ? 'background:#f3f3f3;' : '';
    $compra_opcion = (string) ($lotes['compra_opcion'] ?? '');
    if ($compra_opcion === 'si') {
        $compra_opcion_txt = 'Empeño';
        $fecha_perdido = imprimir_envio_fmt_fecha($lotes['fecha_perdido'] ?? null);
    } else {
        $compra_opcion_txt = 'Compra';
        $fecha_perdido = '-----';
    }
    $fecha_compra = imprimir_envio_fmt_fecha($lotes['fecha_compra'] ?? null);
    $fecha_liberado = imprimir_envio_fmt_fecha($lotes['fecha_liberado'] ?? null);
    $cantidad_art = (int) ($lotes['cantidad_articulos'] ?? 0);
    $peso_bruto_oro = $lotes['peso_bruto'] ?? '';
    $peso_oro = $lotes['peso'] ?? '';
    $precio_compra = $lotes['precio_compra'] ?? '';
    $semana_num = $lotes['semana_numero'] ?? '';
    $id_lote = (int) ($lotes['id_lote'] ?? 0);
    $tipo_de_lote = $h((string) ($lotes['tipo_de_lote'] ?? ''));

    $rows_detalle .= '<tr style="' . $bg . 'text-align:center;">'
        . '<td class="tbl-lote-cell">' . $id_lote . '</td>'
        . '<td class="tbl-lote-cell">' . $h($compra_opcion_txt) . '</td>'
        . '<td class="tbl-lote-cell">' . $tipo_de_lote . '</td>'
        . '<td class="tbl-lote-cell">' . $h($fecha_compra) . '</td>'
        . '<td class="tbl-lote-cell">' . $h($fecha_liberado) . '</td>'
        . '<td class="tbl-lote-cell">' . $h($fecha_perdido) . '</td>'
        . '<td class="tbl-lote-cell">' . $cantidad_art . ' u.</td>'
        . '<td class="tbl-lote-cell">' . $h((string) $peso_bruto_oro) . ' grs.</td>'
        . '<td class="tbl-lote-cell">' . $h((string) $peso_oro) . ' grs.</td>'
        . '<td class="tbl-lote-cell">' . $h((string) $precio_compra) . ' €</td>'
        . '<td class="tbl-lote-cell">' . $h((string) $semana_num) . '</td>'
        . '</tr>';
}

$html = <<<HTML
<style>
body { font-family: DejaVu Sans, sans-serif; font-size: 9pt; color: #222; }
.hdr { background: #2d4154; color: #fff; text-align: center; padding: 8px 4px; font-size: 11pt; font-weight: bold; }
.hdr h2 { margin: 0; font-size: 11pt; line-height: 1.2; }
th.cell-hdr { background: #2d4154; color: #fff; padding: 6px 4px; font-size: 8.5pt; border: 1px solid #1a2833; }
hr.sep { border: none; border-top: 1px solid #666; margin: 16px 0 8px 0; }
h1.doc { text-align: center; color: #2d4154; font-size: 14pt; margin: 12px 0; }
.obs { margin-top: 14px; font-size: 10pt; }
table.grid { width: 100%; border-collapse: collapse; }
td.b { border: 1px solid #ccc; }
/* Tabla detalle lotes: anchos fijos para repartir bien el apaisado (evita columna Semana desproporcionada) */
table.table-lotes { width: 100%; border-collapse: collapse; table-layout: fixed; font-size: 7.5pt; }
table.table-lotes th.cell-hdr,
table.table-lotes td.tbl-lote-cell {
    border: 1px solid #ccc;
    padding: 3px 2px;
    vertical-align: middle;
    line-height: 1.15;
    word-wrap: break-word;
    overflow-wrap: anywhere;
}
table.table-lotes th.cell-hdr {
    font-size: 7pt;
    font-weight: bold;
    padding: 4px 2px;
}
</style>

<table class="grid" style="margin-bottom:6px;">
<tr>
<td class="hdr" style="width:33%;"><h2>Envío Nº {$h((string) $id_envio)}</h2></td>
<td class="hdr" style="width:34%;"><h2>Enviado el {$h($fecha_envio)}</h2></td>
<td class="hdr" style="width:33%;"><h2>Sucursal {$h($nombre_sucursal)}</h2></td>
</tr>
</table>

<table class="grid" style="margin-bottom:6px;">
<tr>
<td class="hdr" style="width:33%;"><h2>{$titulo_semana}</h2></td>
<td class="hdr" style="width:34%;"><h2>Desde {$h($fecha_compra_desde)}</h2></td>
<td class="hdr" style="width:33%;"><h2>Hasta {$h($fecha_compra_hasta)}</h2></td>
</tr>
</table>

<table class="grid" style="margin-bottom:6px;">
<tr>
<td class="hdr" style="width:33%;"><h2>Estado: {$h($estado_envio)}</h2></td>
<td class="hdr" style="width:34%;"><h2>Envía: {$h($nombre_usuario)}</h2></td>
<td class="hdr" style="width:33%;"><h2>Recibe: {$h($nombre_usuario_recibido)}</h2></td>
</tr>
</table>
HTML;

if ($solo_una_semana === 'false') {
    $html .= <<<HTML
<table class="grid" style="margin-bottom:6px;">
<tr>
<td class="hdr" style="width:33%;"><h2>Semanas enviadas: {$h((string) $semanas_enviadas)}</h2></td>
<td class="hdr" style="width:34%;"><h2>Desde: {$h($fecha_desde_parset)}</h2></td>
<td class="hdr" style="width:33%;"><h2>Hasta: {$h($fecha_hasta_parset)}</h2></td>
</tr>
</table>
HTML;
}

$html .= <<<HTML
<table class="grid" style="margin-bottom:6px;">
<tr>
<td class="hdr" style="width:25%;"><h2>{$cantidad_lotes} lotes</h2></td>
<td class="hdr" style="width:25%;"><h2>{$cantidad_compras} compras</h2></td>
<td class="hdr" style="width:25%;"><h2>{$cantidad_empenios} empeños</h2></td>
<td class="hdr" style="width:25%;"><h2>{$cantidad_articulos} artículos</h2></td>
</tr>
</table>

<table class="grid" style="margin-bottom:6px;">
<tr>
<td class="hdr" style="width:25%;"><h2>{$cantidad_renovaciones} renovaciones</h2></td>
<td class="hdr" style="width:25%;"><h2>{$h((string) $total_renovaciones)} € renovado</h2></td>
<td class="hdr" style="width:25%;"><h2>{$cantidad_retiradas} retirados</h2></td>
<td class="hdr" style="width:25%;"><h2>{$h((string) $total_retiradas)} € retirado</h2></td>
</tr>
</table>

<hr class="sep" />
<h1 class="doc">Detalle de lotes de envío Nº {$h((string) $id_envio)}</h1>

<table class="grid" style="margin-bottom:8px;">
<tr>
<td class="hdr" style="width:20%;"><strong>{$total_lotes_oro} lotes Oro<br />{$total_lotes_plata} lotes Plata</strong></td>
<td class="hdr" style="width:20%;"><strong>{$cantidad_articulos_oro_parset} artículos Oro<br />{$cantidad_articulos_plata_parset} artículos Plata</strong></td>
<td class="hdr" style="width:20%;"><strong>{$sumar_oro_bruto_parset} grs. bruto Oro<br />{$sumar_plata_bruto_parset} grs. bruto Plata</strong></td>
<td class="hdr" style="width:20%;"><strong>{$peso_oro_parset} grs. neto Oro<br />{$peso_plata_parset} grs. neto Plata</strong></td>
<td class="hdr" style="width:20%;"><strong>{$precio_compra_total_oro} € pagado Oro<br />{$precio_compra_total_plata} € pagado Plata</strong></td>
</tr>
</table>

<table class="grid table-lotes">
<colgroup>
  <col style="width:5%;" />
  <col style="width:8%;" />
  <col style="width:7%;" />
  <col style="width:10%;" />
  <col style="width:10%;" />
  <col style="width:10%;" />
  <col style="width:7%;" />
  <col style="width:12%;" />
  <col style="width:12%;" />
  <col style="width:15%;" />
  <col style="width:4%;" />
</colgroup>
<tr>
<th class="cell-hdr">Lote</th>
<th class="cell-hdr">Tipo</th>
<th class="cell-hdr">Metal</th>
<th class="cell-hdr">F. compra</th>
<th class="cell-hdr">F. liberado</th>
<th class="cell-hdr">F. perdido</th>
<th class="cell-hdr">Art.</th>
<th class="cell-hdr">P. bruto</th>
<th class="cell-hdr">P. neto</th>
<th class="cell-hdr">Precio €</th>
<th class="cell-hdr">Sem.</th>
</tr>
{$rows_detalle}
</table>

<p class="obs"><strong>Observaciones y/o detalles del envío:</strong> {$h($observaciones_envio)}</p>
HTML;

if ($autoprint) {
    $url_volver = 'envios_sucursal.php';
    if (!empty($_GET['volver'])) {
        $candidato_volver = trim((string) $_GET['volver']);
        if (
            $candidato_volver !== ''
            && strpos($candidato_volver, '..') === false
            && !preg_match('#^(?:https?:)?//#i', $candidato_volver)
            && preg_match('#^[a-zA-Z0-9_\-./?=&%]+$#', $candidato_volver)
        ) {
            $url_volver = $candidato_volver;
        }
    }

    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html lang="es"><head><meta charset="utf-8"><title>Envío ' . (int) $id_envio . '</title>';
    echo '<style>@page { size: A4 landscape; margin: 10mm; } @media print { body { -webkit-print-color-adjust: exact; print-color-adjust: exact; } }</style></head><body>';
    echo $html;
    ?>
<script>
(function () {
    if (window.__envioPrintDisparado) {
        return;
    }
    window.__envioPrintDisparado = true;

    var urlVolver = <?php echo json_encode($url_volver, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    var finalizado = false;

    function finalizarImpresion() {
        if (finalizado) {
            return;
        }
        finalizado = true;

        if (window.opener && !window.opener.closed) {
            try {
                window.opener.postMessage({ type: 'envio:printed' }, window.location.origin);
            } catch (e) {}
            try {
                window.opener.location.reload();
            } catch (e) {}
            try {
                window.close();
                return;
            } catch (e) {}
        }

        if (urlVolver) {
            window.location.replace(urlVolver);
            return;
        }
        if (window.history.length > 1) {
            window.history.back();
            return;
        }
        window.location.replace('../../envios_sucursal.php');
    }

    function iniciarImpresion() {
        window.addEventListener('afterprint', finalizarImpresion, { once: true });
        try {
            window.print();
        } catch (e) {}
        // print() bloquea hasta Imprimir/Cancelar; respaldo si afterprint no dispara
        setTimeout(finalizarImpresion, 400);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            setTimeout(iniciarImpresion, 300);
        });
    } else {
        setTimeout(iniciarImpresion, 300);
    }
})();
</script>
</body></html>
<?php
    exit;
}

try {
    $mpdf = new \Mpdf\Mpdf([
        'mode' => 'utf-8',
        'format' => 'A4-L',
        'margin_left' => 10,
        'margin_right' => 10,
        'margin_top' => 12,
        'margin_bottom' => 12,
        'margin_header' => 0,
        'margin_footer' => 0,
    ]);
    $mpdf->SetTitle('Envío ' . $id_envio);
    $mpdf->SetAuthor('Quinta Gracia');
    $mpdf->SetDisplayMode('fullpage');
    $mpdf->WriteHTML($html);
    $mpdf->Output('envio_' . $id_envio . '.pdf', \Mpdf\Output\Destination::INLINE);
} catch (Throwable $e) {
    error_log('imprimir_envio mPDF: ' . $e->getMessage());
    http_response_code(500);
    echo 'Error al generar PDF';
}
