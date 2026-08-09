<?php
require_once '../include/session.php';
require_once '../include/functions.php';
$conexion = conectar_bd();
require_once '../vendor/autoload.php';

 // Verificar que sea una petición GET
 if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
  // AQUI REDIRIJE AL DASHBOARD
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
    u.nombre_usuario,
    u.apellido_usuario,
    u.firma_usuario,
    d.direccion AS direccion_cliente,
    d.c_provincia AS provincia_cliente,
    d.c_poblacion AS poblacion_cliente,
    d.c_pais AS pais_cliente,
    d.codigo_postal AS cp_cliente,
    d.observaciones_direccion,
    dc.f_nacimiento,
    dc.movil AS movil_cliente,
    dc.email AS email_cliente,
    dc.observaciones AS observaciones_cliente,
    dc.publicidad,
    dc.sexo,
    dc.f_vencimiento AS f_vencimiento_dni,
    dc.firma_cliente
FROM lotes_$id_sucursal l
LEFT JOIN clientes c ON l.cliente = c.id_cliente 
LEFT JOIN sucursal s ON l.sucursal = s.id_sucursal 
LEFT JOIN usuarios u ON u.id_usuario = l.comprado_por
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

$datef = $rsItem['fecha_vencimiento'];
$sqldatef = date('d-m-Y', strtotime($datef));

$date_nacimiento = $rsItem['f_nacimiento'];
$fecha_nacimiento = date('d-m-Y', strtotime($date_nacimiento));

$logo_tienda = $rsItem['logotipo_sucursal'];
$empresa_id = $rsItem['empresa_id'];

// Obtener sello de sucursal
$id_sello = $rsItem['sello_sucursal'];
$sello_image = $rsItem['sello_image'];
$sello_sucursal = generaSello($id_sucursal, $conexion);

// Generar firmas
$signature_value = $rsItem['firma_cliente'];
$textSignature = $rsItem['nombre'] . " " . $rsItem['apellido'];
$signatureInsert_cliente = generateSignatureContratoFinal($signature_value, $textSignature);

$signature_value = $rsItem['firma_usuario'];
$textSignature = $rsItem['nombre_usuario'] . " " . $rsItem['apellido_usuario'];
$signatureInsert_empleado = generateSignatureContratoFinal($signature_value, $textSignature);

$tipo_identificacion_id = isset($rsItem['tipo_identificacion_id']) ? (int) $rsItem['tipo_identificacion_id'] : 0;
if ($tipo_identificacion_id > 0) {
    $texto_tipo_identificacion = obtenerTextoTipoIdentificacion($conexion, $tipo_identificacion_id);
} else {
    $texto_tipo_identificacion = isset($rsItem['tipo_identificacion']) ? trim((string) $rsItem['tipo_identificacion']) : '';
}
$tipo_identificacion_cliente = strtoupper($texto_tipo_identificacion) . ': ';

// Obtener datos de la empresa
$query_empresa = "SELECT nombre_empresa, cif_empresa FROM empresas WHERE id_empresa = ?";
$stmt_empresa = mysqli_prepare($conexion, $query_empresa);
mysqli_stmt_bind_param($stmt_empresa, 'i', $empresa_id);
mysqli_stmt_execute($stmt_empresa);
$result_empresa = mysqli_stmt_get_result($stmt_empresa);
$rsItemww = mysqli_fetch_assoc($result_empresa);
$nombre_empresa = $rsItemww['nombre_empresa'];
$cif_empresa = $rsItemww['cif_empresa'];
mysqli_stmt_close($stmt_empresa);

// Variables para el reemplazo en textos legales
$direccion_empresa = $rsItem['direccion_tienda'] . " " . $rsItem['poblacion_tienda'] . " " . $rsItem['provincia_tienda'] . " " . $rsItem['codigo_postal_tienda'];
$correo_electronico_empresa = $rsItem['email_tienda'];

// Inicializar MPDF
// Crear instancia de mPDF
$mpdf = new \Mpdf\Mpdf([
  'mode' => 'utf-8',
  'format' => 'A4',
  'default_font_size' => 0,
  'default_font' => '',
  'margin_left' => 5,
  'margin_right' => 5,
  'margin_top' => 55,
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
    font-family: arial;
    font-size: 12pt;
    width: 900px;
    margin: 0 auto;
    color: #000000;
}

/* CSS para la última página */
@page ultima {
    margin-top: 10mm;
    margin-bottom: 10mm;
}

.ultima-pagina {
    page: ultima;
}

.titulo_texto_doc {
    font-size: 14pt;
    font-weight: bold;
    margin-bottom: 10pt;
    margin-top: 25pt;
}

.texto_doc {
    font-size: 11pt;
    text-align: justify;
    line-height: 1.4;
    margin-bottom: 15pt;
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
    .titulo_texto_doc{
        font-size: 8px;
        color: #696969;
        font-style: italic;
    }
    .texto_doc {
        font-size: 8px;
        color: #696969;
        text-transform: initial;
        font-style: italic;
        text-align: justify;
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
<img src="../photos/' . $logo_tienda . '" width="280" height="auto">
</td>
<td width="45%" style="text-align: right;">
   <table width="100%" border="0" cellspacing="0" cellpadding="0">
      <tr>
         <td height="10"  style="vertical-align:middle; font-size:15px; font-weight: bold;">Contrato de compra</td>
      </tr>
      <tr>
        <td height="10" style="vertical-align:middle; ">Fecha de compra: <span style="font-weight: bold; font-size: 11pt;">' . $sqldate . '</span></td>
      </tr>
      <tr>
        <td height="10" style="vertical-align:middle; ">Lote Nº <span style="font-weight: bold; font-size: 11pt;">' . $rsItem['id_lote'] . '</span></td>
      </tr>
      </table>
    </td>
</tr>
</table>

<table width="100%" style="font-family: Arial; margin-top: 5pt; " cellpadding="5">
<tr>
<td width="30%" style=""><span style="font-size: 9pt;  font-family: arial; background-color: #EEEEEE; padding:5px; margin-bottom: 10pt;">&nbsp;Datos del establecimiento&nbsp;</span><br /><br />' . $rsItem['empresa'] . '<br />' . $rsItem['identificacion_tienda'] . '  ' . $rsItem['numero_identificacion_tienda'] . '<br />' . $rsItem['direccion_tienda'] . '<br />' . $rsItem['poblacion_tienda'] . ', ' . $rsItem['codigo_postal_tienda'] . ', ' . $rsItem['provincia_tienda'] . '</td>
<td width="30%" ><span style="font-size: 9pt;  font-family: arial; padding:5px;">&nbsp;&nbsp;</span><br /><br />Correo electrónico: ' . $rsItem['email_tienda'] . '<br />Tel.: ' . $rsItem['telefono_tienda'] . '<br />Móvil.: ' . $rsItem['movil_tienda'] . '</td>
<td width="10%">&nbsp;</td>
<td width="40%" style=""><span style="font-size: 9pt;  font-family: arial; background-color: #EEEEEE; padding:5px; margin-bottom: 10pt;">&nbsp;Interesado&nbsp;</span><br /><br />
' . $rsItem['nombre'] . ' ' . $rsItem['apellido'] . '<br />' . $tipo_identificacion_cliente . ' ' . $rsItem['identificacion'] . '<br />Nacionalidad: ' . $rsItem['nacionalidad'] . '<br />Fecha de nacimiento: ' . $fecha_nacimiento . '</td>
<td width="30%" style=""><span style="font-size: 9pt;  font-family: arial;  padding:5px;">&nbsp;&nbsp;</span><br /><br />' . $rsItem['direccion_cliente'] . '<br />' . $rsItem['poblacion_cliente'] . ', ' . $rsItem['cp_cliente'] . ', ' . $rsItem['provincia_cliente'] . '
<br />Tel.: ' . $rsItem['telefono'] . ' <br />Sexo.: ' . $rsItem['sexo'] . ' 
</td>
</tr>
<tr>
<td colspan="5"><span style="font-size: 9pt;  font-family: arial; padding:5px;">Datos de la venta. El vendedor reconoce que los artículos que se detallan a continuación son de su legítima propiedad.</span></td>
</tr>
</table>

</htmlpageheader>

<sethtmlpageheader name="myheader" value="on" show-this-page="1" />
<htmlpagefooter name="footer">
<table class="items" width="100%" style="font-size: 9pt; border-collapse: collapse;" cellpadding="4">
<tr>
  <td align="center" width="33%">Datos del establecimiento:<div style="width: 110px;">' . $sello_sucursal . '</div></td>
  <td align="center" width="33%">Interesado:<br>' . $signatureInsert_cliente . '</td>
</tr>
</table>
';

$html .= '
</htmlpagefooter>
<sethtmlpagefooter name="footer" value="on" />

mpdf-->
';

$html .= '
<div style=" height:auto;">
<div style="font-size: 11px; margin-top: 25pt; line-height: 20px;">&nbsp;Objeto/s del presente contrato (individualizados)</div>
<table class="items" width="100%" style=" font-size: 11px; border-collapse: collapse;" cellpadding="8">
<thead>
<tr>
  <td>Nº</td>
  <td>Us.</td>
  <td align="left">Descricpión</td>
  <td>Peso neto / bruto</td>
  <td>Tipo metal</td>
  <td>Kilates</td>
  <td>Inscripciones</td>
  <td>Piedras</td>
  <td>Precio</td>
</tr>
</thead>
<tbody>
';

// Obtener artículos del lote
$query_articulos = "SELECT * FROM articulos_$id_sucursal WHERE id_lote_articulos = ?";
$stmt_articulos = mysqli_prepare($conexion, $query_articulos);
mysqli_stmt_bind_param($stmt_articulos, 'i', $id_lote);
mysqli_stmt_execute($stmt_articulos);
$result_articulos = mysqli_stmt_get_result($stmt_articulos);

while ($row = mysqli_fetch_assoc($result_articulos)) {
    $peso_neto_bruto = $row['peso_articulo'] . ' / ' . $row['peso_bruto'] . ' grs';
    $inscripciones = ($row['active_inscripciones'] == 'si') ? $row['inscripciones'] : 'No';
    $piedras = ($row['active_piedras'] == 'si') ? $row['descripcion_piedras'] : 'No';
    
    $html .= '<tr>
    <td height="10" align="center">' . $row['id_articulo_lote'] . '</td>
    <td align="center">' . $row['unidades'] . '</td>
    <td>' . $row['descripcion_articulo'] . '</td>
    <td align="center">' . $peso_neto_bruto . '</td>
    <td align="center">' . $row['tipo_de_articulo'] . '</td>
    <td align="center">' . $row['ley'] . '</td>
    <td align="center">' . $inscripciones . '</td>
    <td align="center">' . $piedras . '</td>
    <td align="center">' . $row['precio_compra_articulo'] . ' €</td>
    </tr>';
}
mysqli_stmt_close($stmt_articulos);

$html .= '
<!-- END LISTO ITEMS -->
</tbody>
</table>
</div>';

// Calcular sumatoria de unidades
$query_sum_unidades = "SELECT SUM(unidades) as total_unidades FROM articulos_$id_sucursal WHERE id_lote_articulos = ?";
$stmt_sum = mysqli_prepare($conexion, $query_sum_unidades);
mysqli_stmt_bind_param($stmt_sum, 'i', $id_lote);
mysqli_stmt_execute($stmt_sum);
$result_sum = mysqli_stmt_get_result($stmt_sum);
$row_sum = mysqli_fetch_assoc($result_sum);
$smatoria = $row_sum['total_unidades'];
mysqli_stmt_close($stmt_sum);

$html .= '
<table class="items" width="100%" style="margin-top: 10pt; font-size: 9pt; border-collapse: collapse;" cellpadding="5">
<thead>
<tr>
  <td width="15%">Precio total</td>
  <td width="15%">Peso total objetos</td>
  <td width="25%">Peso bruto objetos</td>
  <td width="25%">Total objetos</td>
</tr>
</thead>
<tbody>
<!-- ITEMS HERE -->
<tr>
<td align="center">' . $rsItem['precio_compra'] . ' €</td>
<td align="center">' . $rsItem['peso'] . ' grs</td>
<td align="center">' . $rsItem['peso_bruto'] . ' grs</td>
<td align="center">' . $smatoria . '</td>
</tr>
<!-- END ITEMS HERE -->
<tr>
<td class="blanktotal" colspan="3"></td>
</tr>
</tbody>
</table>
';

// ÚLTIMA PÁGINA CON CONTENIDO DINÁMICO
$html .= '
<div style="page-break-before: always; page-break-after: avoid;">
';

// Consulta para textos de la última página
$tipo_documento_ultima = 'compra';
$query_ultima = "SELECT titulo_text, content_text 
                 FROM textos_documentos 
                 WHERE (tipo_documento = ? OR tipo_documento = 'texto_legal_datos') 
                 AND state_texto_doc = 'true' 
                 ORDER BY id_texto_doc";
$stmt_ultima = mysqli_prepare($conexion, $query_ultima);
mysqli_stmt_bind_param($stmt_ultima, 's', $tipo_documento_ultima);
mysqli_stmt_execute($stmt_ultima);
$result_ultima = mysqli_stmt_get_result($stmt_ultima);

if (mysqli_num_rows($result_ultima) > 0) {
    while ($rsItem_ultima = mysqli_fetch_assoc($result_ultima)) {
        $titulo_text = $rsItem_ultima['titulo_text'];
        $content_text = $rsItem_ultima['content_text'];
        
        // Reemplazo de variables
        $buscar = array(
            '{direccion_empresa}',
            '{correo_electronico_empresa}',
            '{nombre_empresa}',
            '{telefono_empresa}',
            '{cif_empresa}',
            '{cliente_nombre}',
            '{lote_numero}'
        );
        
        $reemplazar = array(
            $direccion_empresa,
            $correo_electronico_empresa,
            $rsItem['empresa'],
            $rsItem['telefono_tienda'],
            $cif_empresa,
            $rsItem['nombre'] . ' ' . $rsItem['apellido'],
            $rsItem['id_lote']
        );
        
        $content_text_final = str_replace($buscar, $reemplazar, $content_text);
        
        $html .= '<strong class="titulo_texto_doc">' . $titulo_text . '</strong>';
        $html .= '<p class="texto_doc">' . $content_text_final . '</p>';
    }
} else {
    // Contenido por defecto si no hay textos en la base de datos
    $html .= '
    <div class="titulo_texto_doc">TÉRMINOS Y CONDICIONES</div>
    <div class="texto_doc">
        <p>Este contrato se rige por las condiciones generales de la empresa ' . $rsItem['empresa'] . '.</p>
        <br>
        <p>Para cualquier consulta o reclamación, puede dirigirse a:</p>
        <p><strong>Dirección:</strong> ' . $direccion_empresa . '</p>
        <p><strong>Teléfono:</strong> ' . $rsItem['telefono_tienda'] . '</p>
        <p><strong>Email:</strong> ' . $correo_electronico_empresa . '</p>
    </div>
    ';
}
mysqli_stmt_close($stmt_ultima);

$html .= '
</div>
<!-- END ÚLTIMA PÁGINA -->
</body>
</html>
';

// Cerrar conexión
mysqli_close($conexion);

// Generar PDF
$mpdf->WriteHTML($html);
$mpdf->OutputHttpDownload('contrato_compra_' . $rsItem['id_lote'] . '.pdf');
exit;
?>