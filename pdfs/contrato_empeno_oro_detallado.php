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

// Calcular diferencia de recompra
$difrecompra = $rsItem['precio_recompra'] - $rsItem['precio_compra'];
$logo_tienda = $rsItem['logotipo_sucursal'];
$empresa_id = $rsItem['empresa_id'];

// Obtener sello de sucursal
$id_sello = $rsItem['sello_sucursal'];
$sello_image = $rsItem['sello_image'];
$sello_sucursal = generaSello($id_sucursal, $conexion);

// Contar adelantos de capital
$query_adelantos = "SELECT COUNT(id_adelanto_capital) AS TOTALADELANTOS 
                    FROM adelantos_capital 
                    WHERE id_lote_adelanto = ? AND sucursal_adelanto = ?";
$stmt_adelantos = mysqli_prepare($conexion, $query_adelantos);
mysqli_stmt_bind_param($stmt_adelantos, 'ii', $id_lote, $id_sucursal);
mysqli_stmt_execute($stmt_adelantos);
$result_adelantos = mysqli_stmt_get_result($stmt_adelantos);
$rsItemadelantos = mysqli_fetch_assoc($result_adelantos);
$TOTALADELANTOS = $rsItemadelantos['TOTALADELANTOS'];
mysqli_stmt_close($stmt_adelantos);

// Obtener capital antiguo (primer adelanto)
$precio_compra_inicial = $rsItem['precio_compra']; // Por defecto
if ($TOTALADELANTOS > 0) {
    $query_adelanto_inicial = "SELECT capital_antiguo 
                               FROM adelantos_capital 
                               WHERE id_lote_adelanto = ? AND sucursal_adelanto = ? 
                               ORDER BY id_adelanto_capital ASC 
                               LIMIT 1";
    $stmt_adelanto_inicial = mysqli_prepare($conexion, $query_adelanto_inicial);
    mysqli_stmt_bind_param($stmt_adelanto_inicial, 'ii', $id_lote, $id_sucursal);
    mysqli_stmt_execute($stmt_adelanto_inicial);
    $result_adelanto_inicial = mysqli_stmt_get_result($stmt_adelanto_inicial);
    $rsItemadelantose = mysqli_fetch_assoc($result_adelanto_inicial);
    if ($rsItemadelantose) {
        $precio_compra_inicial = $rsItemadelantose['capital_antiguo'];
    }
    mysqli_stmt_close($stmt_adelanto_inicial);
}

// Generar firmas
$signature_value = $rsItem['firma_cliente'];
$textSignature = $rsItem['nombre'] . " " . $rsItem['apellido'];
$signatureInsert_cliente = generateSignatureContratoFinal($signature_value, $textSignature);

$signature_value = $rsItem['firma_usuario'];
$textSignature = $rsItem['nombre_usuario'] . " " . $rsItem['apellido_usuario'];
$signatureInsert_empleado = generateSignatureContratoFinal($signature_value, $textSignature);

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
  'margin_top' => 50,
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
    margin-top: 20pt;
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
</style>
</head>
<body>
';

$html .= '
<!--mpdf
<htmlpageheader name="myheader" >
<table width="100%"><tr>
<td width="55%">
<img src="../photos/' . $logo_tienda . '" width="315" height="63">
</td>
<td width="45%" style="text-align: right;">
   <table width="100%" border="0" cellspacing="0" cellpadding="0">
      <tr>
         <td height="10"  style="vertical-align:middle; font-size:15px; font-weight: bold;">Contrato opción de compra</td>
      </tr>
      <tr>
        <td height="10" style="vertical-align:middle; ">Fecha: <span style="font-weight: bold; font-size: 11pt;">' . $sqldate . '</span></td>
      </tr>
      <tr>
        <td height="10" style="vertical-align:middle; ">Lote Nº <span style="font-weight: bold; font-size: 11pt;">' . $rsItem['id_lote'] . '</span>';

switch ($rsItem['estado_lote']) {
    case 'vencido':
        $html .= " - Vencido";
        break;
    case 'retirado':
        $html .= " - Retirado";
        break;
}

$html .= '</td>
      </tr>
      </table>
    </td>
</tr>
</table>

<table width="100%" style="font-family: Arial; " cellpadding="5">
<tr>
<td width="30%" style=""><span style="font-size: 9pt;  font-family: arial; background-color: #EEEEEE; padding:5px;">&nbsp;El comprador&nbsp;</span><br /><br />' . $rsItem['empresa'] . '<br />' . $rsItem['identificacion_tienda'] . '  ' . $rsItem['numero_identificacion_tienda'] . '<br />' . $rsItem['direccion_tienda'] . '<br /></td>
<td width="30%" ><span style="font-size: 9pt;  font-family: arial; padding:5px;">&nbsp;&nbsp;</span><br /><br />' . $rsItem['poblacion_tienda'] . ', ' . $rsItem['codigo_postal_tienda'] . ', ' . $rsItem['provincia_tienda'] . '<br />Tel.: ' . $rsItem['telefono_tienda'] . '<br />Móvil.: ' . $rsItem['movil_tienda'] . '</td>
<td width="10%">&nbsp;</td>
<td width="40%" style=""><span style="font-size: 9pt;  font-family: arial; background-color: #EEEEEE; padding:5px;">&nbsp;El vendedor&nbsp;</span><br /><br />
' . $rsItem['nombre'] . ' ' . $rsItem['apellido'] . '<br />' . $rsItem['tipo_identificacion'] . ' ' . $rsItem['identificacion'] . '<br />' . $rsItem['direccion'] . '</td>
<td width="30%" style=""><span style="font-size: 9pt;  font-family: arial;  padding:5px;">&nbsp;&nbsp;</span><br /><br />' . $rsItem['c_poblacion'] . ', ' . $rsItem['codigo_postal'] . ', ' . $rsItem['c_provincia'] . '
<br />Tel.: ' . $rsItem['telefono'] . ' <br />Móvil.: ' . $rsItem['movil'] . ' 
</td>
</tr>
<tr>
<td colspan="5"><span style="font-size: 9pt;  font-family: arial;">Datos de la venta. El vendedor reconoce que los artículos que se detallan a continuación son de su legítima propiedad.</span></td>
</tr>
</table>

</htmlpageheader>

<sethtmlpageheader name="myheader" value="on" show-this-page="1"  />
<htmlpagefooter name="footer">
<table class="items" width="100%" style="font-size: 9pt; border-collapse: collapse;" cellpadding="4">
<tr>
  <td align="center" width="33%">El comprador:<div style="width: 110px;">' . $sello_sucursal . '</div></td>
  <td align="center" width="33%">El vendedor:<br>' . $signatureInsert_cliente . '</td>
  <td width="33%">Lote retirado:</td>
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
<table class="items" width="100%" style=" font-size: 11px; border-collapse: collapse;" cellpadding="8">
<thead>
<tr>
  <td width="5%">Nº</td>
  <td width="5%">Us.</td>
  <td width="65%" align="left">Descricpión de los artículos</td>
  <td width="25%">Inscripciones</td>
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
    $html .= '<tr>
    <td height="10" align="center">' . $row['id_articulo_lote'] . '</td>
    <td align="center">' . $row['unidades'] . '</td>
    <td>' . $row['descripcion_articulo'] . ' (' . $row['tipo_de_articulo'] . ' ' . $row['ley'] . ')</td>
    <td>' . $row['inscripciones'] . '</td>
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

$interestotales = $rsItem['precio_recompra'] - $rsItem['precio_compra'];

$html .= '
<!-- SUMATORIA -->
<table class="items" width="100%" style=" font-size: 9pt; border-collapse: collapse; margin-bottom:15px;" cellpadding="8">
<tr>
<td width="4%" height="20" align="center" style="background-color: #EEEEEE;">Total</td>
<td width="4%" align="center" style="background-color: #EEEEEE;">' . $smatoria . '</td>
<td width="65%">&nbsp;</td>
<td width="25%">&nbsp;</td>
</tr>
</table>
<!-- END SUMATORIA -->

<table class="items" width="100%" style="font-size: 9pt; border-collapse: collapse;" cellpadding="5">
<thead>
<tr>
  <td width="15%">Gramos</td>
  <td width="15%">Importe</td>
  <td width="25%">Vencimiento</td>
  <td width="15%">Importe recompra</td>
</tr>
</thead>
<tbody>
<!-- ITEMS HERE -->
<tr>
<td align="center">' . $rsItem['peso'] . ' grs</td>
<td align="center">' . $rsItem['precio_compra'] . ' €</td>
<td align="center">' . $sqldatef . '</td>
<td align="center">' . $rsItem['precio_recompra'] . ' €</td>
</tr>
<!-- END ITEMS HERE -->
<tr>
<td class="blanktotal" colspan="3"></td>
</tr>
</tbody>
</table>
';

$html .= '
<!-- RENOVACIONES -->
<div style=" height:auto; margin-top: 0pt; page-break-before:always;">
Renovaciones
<table class="items" width="100%" style=" font-size: 8pt; border-collapse: collapse;" cellpadding="5">
<thead>
<tr>
  <td width="20%">Nº</td>
  <td width="20%">F. Renovación</td>
  <td width="20%">Importe</td>
  <td width="20%">F. vencimiento</td>
  <td width="20%">Estado</td>
</tr>
</thead>
<tbody>
';

// Obtener renovaciones
$query_renovaciones = "SELECT * FROM historico_renovaciones_$id_sucursal WHERE lote = ? ORDER BY id_renovaciones ASC";
$stmt_renovaciones = mysqli_prepare($conexion, $query_renovaciones);
mysqli_stmt_bind_param($stmt_renovaciones, 'i', $id_lote);
mysqli_stmt_execute($stmt_renovaciones);
$result_renovaciones = mysqli_stmt_get_result($stmt_renovaciones);

$i = 1;
while ($row = mysqli_fetch_assoc($result_renovaciones)) {
    $fecharenovacion = $row['fecha_renovacion'];
    $proximo_vencimiento = $row['proximo_vencimiento'];
    $estado_historico = $row['estado_historico'];
    
    if ($estado_historico == "enfecha") {
        $estado_historico = "Pendiente";
    }
    
    if ($proximo_vencimiento != '0000-00-00' && $proximo_vencimiento != null) {
        $proximo_vencimiento = date('d-m-Y', strtotime($proximo_vencimiento));
    } else {
        $proximo_vencimiento = '-----';
    }
    
    if ($fecharenovacion != '0000-00-00' && $fecharenovacion != null) {
        $fecharenovacion = date('d-m-Y', strtotime($fecharenovacion));
    } else {
        $fecharenovacion = '-----';
    }
    
    $importe_renovacionw = number_format($row['importe_renovacion'], 2, '.', ' ');
    
    $html .= '<tr>
    <td align="center">' . $i . '</td>
    <td align="center">' . $fecharenovacion . '</td>
    <td align="center">' . $importe_renovacionw . ' €</td>
    <td align="center">' . $proximo_vencimiento . '</td>
    <td align="center">' . $estado_historico . '</td>
    </tr>';
    $i++;
}
mysqli_stmt_close($stmt_renovaciones);

$html .= '
</tbody>
</table>
</div>
<!-- END RENOVACIONES -->
';

// Sección de adelantos de capital
if ($TOTALADELANTOS > 0) {
    $html .= '
    <!-- ADELANTO CAPITAL -->
    <div style=" height:auto; margin-top: 0pt;  page-break-before:always;">
    Adelantos de capital <small>(capital inicial: ' . $precio_compra_inicial . ')</small>
    <table class="items" width="100%" style=" font-size: 8pt; border-collapse: collapse;" cellpadding="5">
    <thead>
    <tr>
      <td>Nº</td>
      <td>Importe</td>
      <td>F. Adelanto</td>
      <td>Capital</td>
      <td>Precio recompra</td>
      <td>Importe renovacion</td>
    </tr>
    </thead>
    <tbody>
    ';
    
    $query_adelantos_detalle = "SELECT * FROM adelantos_capital WHERE id_lote_adelanto = ? AND sucursal_adelanto = ? ORDER BY id_adelanto_capital ASC";
    $stmt_adelantos_detalle = mysqli_prepare($conexion, $query_adelantos_detalle);
    mysqli_stmt_bind_param($stmt_adelantos_detalle, 'ii', $id_lote, $id_sucursal);
    mysqli_stmt_execute($stmt_adelantos_detalle);
    $result_adelantos_detalle = mysqli_stmt_get_result($stmt_adelantos_detalle);
    
    $is = 1;
    while ($rowa = mysqli_fetch_assoc($result_adelantos_detalle)) {
        $importe_adelanto = $rowa['importe_adelanto'];
        $fecha_adelanto = $rowa['fecha_adelanto'];
        $nuevo_capital = $rowa['nuevo_capital'];
        $nuevo_precio_recompra = $rowa['nuevo_precio_recompra'];
        $importe_renovacion = $nuevo_precio_recompra - $nuevo_capital;
        
        $importe_renovacion = number_format($importe_renovacion, 2, '.', ' ');
        
        if ($fecha_adelanto != '0000-00-00' && $fecha_adelanto != null) {
            $fecha_adelanto = date('d-m-Y', strtotime($fecha_adelanto));
        } else {
            $fecha_adelanto = '-----';
        }
        
        $html .= '<tr>
        <td align="center">' . $is . '</td>
        <td align="center">' . $importe_adelanto . ' €</td>
        <td align="center">' . $fecha_adelanto . '</td>
        <td align="center">' . $nuevo_capital . ' €</td>
        <td align="center">' . $nuevo_precio_recompra . ' €</td>
        <td align="center">' . $importe_renovacion . ' €</td>
        </tr>';
        $is++;
    }
    mysqli_stmt_close($stmt_adelantos_detalle);
    
    $html .= '
    </tbody>
    </table>
    </div>
    <!-- END ADELANTO CAPITAL -->
    ';
}

// ÚLTIMA PÁGINA CON CONTENIDO DINÁMICO
$html .= '
<div class="ultima-pagina" style="page-break-before: always; page-break-after: avoid;">
<table width="100%"><tr>
<td width="55%">
<img src="../photos/' . $logo_tienda . '" width="315" height="63">
</td>
<td width="45%" style="text-align: right;">
   <table width="100%" border="0" cellspacing="0" cellpadding="0">
      <tr>
         <td height="10"  style="vertical-align:middle; font-size:15px; font-weight: bold;">Contrato opción de compra</td>
      </tr>
      <tr>
        <td height="10" style="vertical-align:middle; ">Fecha: <span style="font-weight: bold; font-size: 11pt;">' . $sqldate . '</span></td>
      </tr>
      <tr>
        <td height="10" style="vertical-align:middle; ">Lote Nº <span style="font-weight: bold; font-size: 11pt;">' . $rsItem['id_lote'] . '</span>';

switch ($rsItem['estado_lote']) {
    case 'vencido':
        $html .= " - Vencido";
        break;
    case 'retirado':
        $html .= " - Retirado";
        break;
}

$html .= '</td>
      </tr>
      </table>
    </td>
</tr>
</table>
<table width="100%" style="font-family: Arial; " cellpadding="5">
<tr>
<td width="30%" style=""><span style="font-size: 9pt;  font-family: arial; background-color: #EEEEEE; padding:5px;">&nbsp;El comprador&nbsp;</span><br /><br />' . $rsItem['empresa'] . '<br />' . $rsItem['identificacion_tienda'] . '  ' . $rsItem['numero_identificacion_tienda'] . '<br />' . $rsItem['direccion_tienda'] . '<br /></td>
<td width="30%" ><span style="font-size: 9pt;  font-family: arial; padding:5px;">&nbsp;&nbsp;</span><br /><br />' . $rsItem['poblacion_tienda'] . ', ' . $rsItem['codigo_postal_tienda'] . ', ' . $rsItem['provincia_tienda'] . '<br />Tel.: ' . $rsItem['telefono_tienda'] . '<br />Móvil.: ' . $rsItem['movil_tienda'] . '</td>
<td width="10%">&nbsp;</td>
<td width="40%" style=""><span style="font-size: 9pt;  font-family: arial; background-color: #EEEEEE; padding:5px;">&nbsp;El vendedor&nbsp;</span><br /><br />
' . $rsItem['nombre'] . ' ' . $rsItem['apellido'] . '<br />' . $rsItem['tipo_identificacion'] . ' ' . $rsItem['identificacion'] . '<br />' . $rsItem['direccion'] . '</td>
<td width="30%" style=""><span style="font-size: 9pt;  font-family: arial;  padding:5px;">&nbsp;&nbsp;</span><br /><br />' . $rsItem['c_poblacion'] . ', ' . $rsItem['codigo_postal'] . ', ' . $rsItem['c_provincia'] . '
<br />Tel.: ' . $rsItem['telefono'] . ' <br />Móvil.: ' . $rsItem['movil'] . ' 
</td>
</tr>
</table>
<br>
<style>
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
';

// Consulta para textos de la última página
$tipo_documento_ultima = 'empenio';
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
<table class="items" width="100%" style="font-size: 9pt; border-collapse: collapse;" cellpadding="4">
<tr>
  <td align="center" width="33%">El comprador:<div style="width: 110px;">' . $sello_sucursal . '</div></td>
  <td align="center" width="33%">El vendedor:<br>' . $signatureInsert_cliente . '</td>
  <td width="33%">Lote retirado:</td>
</tr>
</table>
<!-- END ÚLTIMA PÁGINA -->
';

$html .= '
</body>
</html>
';

// Cerrar conexión
mysqli_close($conexion);

// Generar PDF
$mpdf->WriteHTML($html);
$mpdf->Output();
exit;
?>