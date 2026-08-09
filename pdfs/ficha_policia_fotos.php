<?php
require_once '../include/session.php';
require_once '../include/functions.php';
$conexion = conectar_bd();
require_once '../vendor/autoload.php';

// Verificar que sea una petición GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    header('Location: ../dashboard.php');
    exit;
}

$id_lote = isset($_GET['id_lote']) ? (int)$_GET['id_lote'] : 0;
$id_sucursal = isset($_GET['id_sucursal']) ? (int)$_GET['id_sucursal'] : 0;

if (!$id_lote) {
    header('Location: ../dashboard.php');
    exit;
}

if (!$id_sucursal) {
    header('Location: ../dashboard.php');
    exit;
}

// CONSULTA PARA OBTENER LOS DATOS DEL LOTE, EL CLIENTE Y LA SUCURSAL
$query = "SELECT 
    l.*,
    c.*,
    s.*,
    d.direccion AS direccion_cliente,
    d.c_provincia AS provincia_cliente,
    d.c_poblacion AS poblacion_cliente,
    d.codigo_postal AS cp_cliente,
    dc.f_nacimiento,
    dc.movil AS movil_cliente
FROM lotes_$id_sucursal l
LEFT JOIN clientes c ON l.cliente = c.id_cliente 
LEFT JOIN sucursal s ON l.sucursal = s.id_sucursal 
LEFT JOIN direcciones d ON d.rel_id_item = c.id_cliente AND d.type_direccion = 'clientes'
LEFT JOIN datos_clientes dc ON dc.rel_id_cliente = c.id_cliente
WHERE l.id_lote = ?
LIMIT 1";

$stmt = mysqli_prepare($conexion, $query);
if (!$stmt) {
    die('Error en la consulta: ' . mysqli_error($conexion));
}

mysqli_stmt_bind_param($stmt, 'i', $id_lote);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$rsItem = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$rsItem) {
    header('Location: ../dashboard.php');
    exit;
}

// Formatear fechas
$date = $rsItem['fecha_compra'];
$sqldate = date('d-m-Y', strtotime($date));
$cliente = $rsItem['id_cliente'];

$logo_tienda = $rsItem['logotipo_sucursal'];
$nombre_suc = $rsItem['nombre_sucursal'];

// Inicializar MPDF (formato horizontal A4-L)
$mpdf = new \Mpdf\Mpdf([
    'mode' => 'utf-8',
    'format' => 'A4-L',
    'default_font_size' => 0,
    'default_font' => '',
    'margin_left' => 10,
    'margin_right' => 10,
    'margin_top' => 25,
    'margin_bottom' => 5,
    'margin_header' => 5,
    'margin_footer' => 1,
]);

$html = '
<html>
<head>
<meta charset="UTF-8" />
<style>
body {
    font-family: helvetica;
    font-size: 9pt;
    width: 900px;
    margin: 0 auto;
    color: #666666;
}
p {
    margin: 0pt;
}
td {
    vertical-align: top;
}
.items td {
}
table thead td {
    background-color: #EEEEEE;
    text-align: center;
}
.items td.blanktotal {
    background-color: none;
    border: none;
}
.items td.totals {
    text-align: right;
}
</style>
</head>
<body>
';

$html .= '
<!--mpdf
<htmlpageheader name="myheader" >
<table width="100%"><tr>
<td width="55%">
<img src="../photos/' . $logo_tienda . '" width="250" height="auto">
</td>
<td width="45%" style="text-align: right;">
   <table width="100%" border="0" cellspacing="0" cellpadding="0">
      <tr>
        <td height="10" style="vertical-align:middle; ">Hoja resumen de operación para la policía ' . $nombre_suc . '</td>
      </tr>
      <tr>
        <td height="10" style="vertical-align:middle; ">Fecha según libro de registro: <span style="font-weight: bold; font-size: 11pt;">' . $sqldate . '</span></td>
      </tr>
      <tr>
        <td height="10" style="vertical-align:middle; ">Operación Nº según libro de registro: <span style="font-weight: bold; font-size: 11pt;">' . $rsItem['id_lote'] . '</span></td>
      </tr>
    </table>
    </td>
</tr>
</table>
</htmlpageheader>

<sethtmlpageheader name="myheader" value="on" show-this-page="1"  />

mpdf-->
';

// OBTENER FOTOS DEL LOTE
$query_fotos_lote = "SELECT * FROM fotos_app_$id_sucursal WHERE id_lote = ? ORDER BY id_foto DESC LIMIT 1";
$stmt_fotos_lote = mysqli_prepare($conexion, $query_fotos_lote);
mysqli_stmt_bind_param($stmt_fotos_lote, 'i', $id_lote);
mysqli_stmt_execute($stmt_fotos_lote);
$result_fotos_lote = mysqli_stmt_get_result($stmt_fotos_lote);
$foto_lote = mysqli_fetch_assoc($result_fotos_lote);
mysqli_stmt_close($stmt_fotos_lote);

$html .= '
<table class="items" width="100%" style="margin-top:5px;font-size: 10pt; border-collapse: collapse;" cellpadding="2">
<tr>
<td width="720px" style="text-align: center; ">';

if ($foto_lote) {
    $html .= '<img src="../photos/' . $foto_lote['nombre_foto'] . '" style="width:730px; height:547px; " />';
}

$html .= '</td>
<td width="305px" style="text-align: center; ">
';

// OBTENER FOTOS DEL CLIENTE (DNI)
$query_fotos_cliente = "SELECT * FROM fotos_app_$id_sucursal WHERE id_cliente = ? LIMIT 2";
$stmt_fotos_cliente = mysqli_prepare($conexion, $query_fotos_cliente);
mysqli_stmt_bind_param($stmt_fotos_cliente, 'i', $cliente);
mysqli_stmt_execute($stmt_fotos_cliente);
$result_fotos_cliente = mysqli_stmt_get_result($stmt_fotos_cliente);

while ($row = mysqli_fetch_assoc($result_fotos_cliente)) {
    $html .= '<img src="../photos/' . $row['nombre_foto'] . '" style="margin-bottom:15px; width:305px; max-height:230px;" />';
}
mysqli_stmt_close($stmt_fotos_cliente);

$html .= '
  <br /><br /><br />
  <br /><br /><br />
  </td>
</tr>
</table>
<table class="items" width="100%" style="margin-top:5px;font-size: 10pt; border-collapse: collapse;" cellpadding="2">
<tr>
<td width="100%" align="left" style="font-size: 11pt;  font-family: helvetica; text-align:center; font-weight:bold;"><span>' . $rsItem['nombre'] . ' ' . $rsItem['apellido'] . ' con ' . strtoupper($rsItem['tipo_identificacion']) . ': ' . $rsItem['identificacion'] . '</span></td>
</tr>
</table>
</body>
</html>
';

// Cerrar conexión
mysqli_close($conexion);

// Generar PDF
$mpdf->WriteHTML($html);
$mpdf->OutputHttpDownload('ficha_policia_fotos_' . $rsItem['id_lote'] . '.pdf');
exit;
?>