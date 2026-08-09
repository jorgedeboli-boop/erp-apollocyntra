<?php
/**
 * PDF factura Bizkaia TicketBAI (completa, simplificada y rectificativas).
 * Misma maqueta fiscal: TBAI + QR. La simplificada omite datos de cliente.
 *
 * ?tipo=simplificada | rectificativa | rectificativa_simplificada
 *
 * Si se define FACTURA_MPDF_BUFFER_MODE como true antes de incluir este archivo,
 * se omite la comprobación GET y al final se devuelve el PDF en binario (return)
 * en lugar de enviarlo al navegador.
 */

$factura_mpdf_buffer = defined('FACTURA_MPDF_BUFFER_MODE') && FACTURA_MPDF_BUFFER_MODE === true;

if (!$factura_mpdf_buffer && $_SERVER['REQUEST_METHOD'] !== 'GET') {
    header('Location: ../../dashboard.php');
    exit;
}

require_once __DIR__ . '/../../include/session.php';
require_once __DIR__ . '/../../include/functions.php';

$tipo_get = isset($_GET['tipo']) ? strtolower(trim((string) $_GET['tipo'])) : '';
$es_simplificada = ($tipo_get === 'simplificada');
$es_rectificativa = ($tipo_get === 'rectificativa' || $tipo_get === 'rectificativa_simplificada');
$es_rectificativa_simplificada = ($tipo_get === 'rectificativa_simplificada');

$id_factura = isset($_GET['id_factura']) ? (int) $_GET['id_factura'] : 0;
if ($id_factura <= 0) {
    if ($factura_mpdf_buffer) {
        return null;
    }
    header('Location: ../../dashboard.php');
    exit;
}

$conexion = conectar_bd();
require_once __DIR__ . '/../../vendor/autoload.php';

$origen_simplificada = isset($_GET['origen']) ? strtolower(trim((string) $_GET['origen'])) : '';
if ($origen_simplificada === 'unificada') {
    $origen_simplificada = 'facturas';
}
$tabla_items_articulos = 'facturas_rel_articulos';
$tabla_items_renovaciones = 'facturas_rel_renovaciones';
$factura_original_display = '';
$motivo_rectificado = '';

if ($es_rectificativa) {
    $tabla_cabecera = $es_rectificativa_simplificada
        ? 'facturas_rectificativas_simplificadas'
        : 'facturas_rectificativas';
    $tabla_items_articulos = $es_rectificativa_simplificada
        ? 'facturas_rectificativas_rel_articulos_simplificadas'
        : 'facturas_rectificativas_rel_articulos';
    $tabla_items_renovaciones = '';

    $queryFR = "SELECT
    FCTR.id_factura,
    FCTR.numero_factura,
    FCTR.total_factura,
    FCTR.id_sucursal AS sucursal_factura,
    FCTR.fecha_factura,
    FCTR.prefijo_factura,
    FCTR.tipo_pago_factura,
    FCTR.tipo_factura,
    FCTR.id_rel_factura_fiskaly,
    FCTR.factura_original,
    FCTR.motivo_rectificado,
    FCTR.prefijo_factura_original,
    CLT.id_cliente,
    CLT.nombre,
    CLT.apellido,
    CLT.tipo_identificacion,
    CLT.identificacion,
    CLT.telefono,
    SCR.nombre_sucursal,
    SCR.identificacion_tienda,
    SCR.numero_identificacion_tienda,
    SCR.direccion_tienda,
    SCR.poblacion_tienda,
    SCR.provincia_tienda,
    SCR.codigo_postal_tienda,
    SCR.email_tienda,
    SCR.telefono_tienda,
    SCR.empresa,
    SCR.logotipo_sucursal,
    SCR.sello_sucursal,
    SCR.sello_image,
    VTS.id AS identificador_venta,
    VTS.tipo_pago,
    EMPS.id_empresa,
    EMPS.nombre_empresa,
    EMPS.cif_empresa,
    EMPS.direccion_empresa,
    EMPS.poblacion_empresa,
    EMPS.provincia_empresa,
    EMPS.pais_empresa,
    EMPS.telefono_empresa,
    EMPS.codigo_postal_empresa,
    EMPS.email_empresa,
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
FROM {$tabla_cabecera} AS FCTR
LEFT JOIN clientes AS CLT ON CLT.id_cliente = FCTR.cliente_factura
LEFT JOIN sucursal AS SCR ON SCR.id_sucursal = FCTR.id_sucursal
LEFT JOIN ventas AS VTS ON VTS.id = FCTR.rel_id_venta
LEFT JOIN empresas AS EMPS ON EMPS.id_empresa = SCR.empresa_id
LEFT JOIN direcciones d ON d.rel_id_item = CLT.id_cliente AND d.type_direccion = 'clientes'
LEFT JOIN datos_clientes dc ON dc.rel_id_cliente = CLT.id_cliente
WHERE FCTR.id_factura = ?
LIMIT 1";
} elseif ($es_simplificada) {
    $preferencia = '';
    if ($origen_simplificada === 'facturas' || $origen_simplificada === 'historico') {
        $preferencia = $origen_simplificada;
    }
    $infoOrigen = facturaSimplificadaResolverOrigen($id_factura, $preferencia);
    if (!$infoOrigen) {
        mysqli_close($conexion);
        if ($factura_mpdf_buffer) {
            return null;
        }
        header('Location: ../dashboard.php');
        exit;
    }
    $tablasSimp = facturaSimplificadaTablasPorOrigen($infoOrigen['origen']);
    $tabla_items_articulos = $tablasSimp['articulos'];
    $tabla_items_renovaciones = $tablasSimp['renovaciones'];
    $tabla_cabecera = $tablasSimp['cabecera'];

    $queryFR = "SELECT
    FCTR.id_factura,
    FCTR.numero_factura,
    FCTR.total_factura,
    FCTR.id_sucursal AS sucursal_factura,
    FCTR.fecha_factura,
    FCTR.prefijo_factura,
    FCTR.tipo_pago_factura,
    FCTR.tipo_factura,
    FCTR.id_rel_factura_fiskaly,
    NULL AS id_cliente,
    '' AS nombre,
    '' AS apellido,
    '' AS tipo_identificacion,
    '' AS identificacion,
    '' AS telefono,
    SCR.nombre_sucursal,
    SCR.identificacion_tienda,
    SCR.numero_identificacion_tienda,
    SCR.direccion_tienda,
    SCR.poblacion_tienda,
    SCR.provincia_tienda,
    SCR.codigo_postal_tienda,
    SCR.email_tienda,
    SCR.telefono_tienda,
    SCR.empresa,
    SCR.logotipo_sucursal,
    SCR.sello_sucursal,
    SCR.sello_image,
    VTS.id AS identificador_venta,
    VTS.tipo_pago,
    EMPS.id_empresa,
    EMPS.nombre_empresa,
    EMPS.cif_empresa,
    EMPS.direccion_empresa,
    EMPS.poblacion_empresa,
    EMPS.provincia_empresa,
    EMPS.pais_empresa,
    EMPS.telefono_empresa,
    EMPS.codigo_postal_empresa,
    EMPS.email_empresa,
    NULL AS direccion_cliente,
    NULL AS provincia_cliente,
    NULL AS poblacion_cliente,
    NULL AS pais_cliente,
    NULL AS cp_cliente,
    NULL AS observaciones_direccion,
    NULL AS f_nacimiento,
    NULL AS movil_cliente,
    NULL AS email_cliente,
    NULL AS observaciones_cliente,
    NULL AS publicidad,
    NULL AS sexo,
    NULL AS f_vencimiento_dni,
    NULL AS firma_cliente
FROM {$tabla_cabecera} AS FCTR
LEFT JOIN sucursal AS SCR ON SCR.id_sucursal = FCTR.id_sucursal
LEFT JOIN ventas AS VTS ON VTS.id = FCTR.rel_id_venta
LEFT JOIN empresas AS EMPS ON EMPS.id_empresa = SCR.empresa_id
WHERE FCTR.id_factura = ?
LIMIT 1";
} else {
    $queryFR = "SELECT
    FCTR.id_factura,
    FCTR.numero_factura,
    FCTR.total_factura,
    FCTR.id_sucursal AS sucursal_factura,
    FCTR.fecha_factura,
    FCTR.prefijo_factura,
    FCTR.tipo_pago_factura,
    FCTR.tipo_factura,
    FCTR.id_rel_factura_fiskaly,
    CLT.id_cliente,
    CLT.nombre,
    CLT.apellido,
    CLT.tipo_identificacion,
    CLT.identificacion,
    CLT.telefono,
    SCR.nombre_sucursal,
    SCR.identificacion_tienda,
    SCR.numero_identificacion_tienda,
    SCR.direccion_tienda,
    SCR.poblacion_tienda,
    SCR.provincia_tienda,
    SCR.codigo_postal_tienda,
    SCR.email_tienda,
    SCR.telefono_tienda,
    SCR.empresa,
    SCR.logotipo_sucursal,
    SCR.sello_sucursal,
    SCR.sello_image,
    VTS.id AS identificador_venta,
    VTS.tipo_pago,
    EMPS.id_empresa,
    EMPS.nombre_empresa,
    EMPS.cif_empresa,
    EMPS.direccion_empresa,
    EMPS.poblacion_empresa,
    EMPS.provincia_empresa,
    EMPS.pais_empresa,
    EMPS.telefono_empresa,
    EMPS.codigo_postal_empresa,
    EMPS.email_empresa,
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
FROM facturas AS FCTR
LEFT JOIN clientes AS CLT ON CLT.id_cliente = FCTR.cliente_factura
LEFT JOIN sucursal AS SCR ON SCR.id_sucursal = FCTR.id_sucursal
LEFT JOIN ventas AS VTS ON VTS.id = FCTR.rel_id_venta
LEFT JOIN empresas AS EMPS ON EMPS.id_empresa = SCR.empresa_id
LEFT JOIN direcciones d ON d.rel_id_item = CLT.id_cliente AND d.type_direccion = 'clientes'
LEFT JOIN datos_clientes dc ON dc.rel_id_cliente = CLT.id_cliente
WHERE FCTR.id_factura = ?
LIMIT 1";
}

$stmtFR = mysqli_prepare($conexion, $queryFR);
if (!$stmtFR) {
    die('Error en la consulta: ' . mysqli_error($conexion));
}


mysqli_stmt_bind_param($stmtFR, 'i', $id_factura);
mysqli_stmt_execute($stmtFR);
$result = mysqli_stmt_get_result($stmtFR);
$rsItemFR = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmtFR);

if (!$rsItemFR) {
    if ($factura_mpdf_buffer) {
        return null;
    }
    header('Location: ../dashboard.php');
    exit;
}

$id_factura = $rsItemFR['id_factura'];
$numero_factura = $rsItemFR['numero_factura'];
$total_factura = (float) $rsItemFR['total_factura'];
$id_cliente = $rsItemFR['id_cliente'];
$nombre = $rsItemFR['nombre'];
$apellido = $rsItemFR['apellido'];
$nombrecomercialCustomer = $nombre." ".$apellido;
$addressFullInvoice = $rsItemFR['direccion_cliente'];
$provinceCustomerInvoice = $rsItemFR['provincia_cliente'];
$populationCustomerInvoice = $rsItemFR['poblacion_cliente'];
$postalcodeCustomerInvoice = $rsItemFR['cp_cliente'];
$tiponifCustomer = $rsItemFR['tipo_identificacion'];
$nifCustomer = $rsItemFR['identificacion'];
$telefono = $rsItemFR['telefono'];
$nombre_sucursal = $rsItemFR['nombre_sucursal'];
$tiponifCompany = $rsItemFR['identificacion_tienda'];
$nifCompany = $rsItemFR['numero_identificacion_tienda'];
$addressAddress = $rsItemFR['direccion_tienda'];
$populationAddress = $rsItemFR['poblacion_tienda'];
$provinceAddress = $rsItemFR['provincia_tienda'];
$postalcodeAddress = $rsItemFR['codigo_postal_tienda'];
$appEmail = $rsItemFR['email_tienda'];
$appPhone  = $rsItemFR['telefono_tienda'];
$nameCompany  = $rsItemFR['empresa'];
$countryCustomerInvoice = $rsItemFR['pais_cliente'];
$sucursal_factura = $rsItemFR['sucursal_factura'];
$logotipoPdf = $rsItemFR['logotipo_sucursal'];
$tipo_pago_factura = isset($rsItemFR['tipo_pago_factura']) ? trim((string) $rsItemFR['tipo_pago_factura']) : '';
$tipo_pago_venta = isset($rsItemFR['tipo_pago']) ? trim((string) $rsItemFR['tipo_pago']) : '';
$tipo_pago = $tipo_pago_factura !== '' ? $tipo_pago_factura : $tipo_pago_venta;
$fecha_factura = $rsItemFR['fecha_factura'];
$fecha_factura_final = date('d-m-Y', strtotime($fecha_factura));
$fecha_factura_parset = strtotime($fecha_factura);
$anyo_factura = date('Y', $fecha_factura_parset);

$tipo_factura = isset($rsItemFR['tipo_factura']) ? trim((string) $rsItemFR['tipo_factura']) : '';
$es_renovaciones = ($tipo_factura === 'renovaciones');
$texto_facturas = obtenerTextoLegalFactura($tipo_factura);
$texto_facturas_pie = $texto_facturas;

$prefijo_factura = $rsItemFR['prefijo_factura'];
if ($es_rectificativa) {
    $prefijo_factura_original = isset($rsItemFR['prefijo_factura_original'])
        ? trim((string) $rsItemFR['prefijo_factura_original'])
        : '';
    $factura_original_num = isset($rsItemFR['factura_original'])
        ? trim((string) $rsItemFR['factura_original'])
        : '';
    $factura_original_display = trim($prefijo_factura_original . '-' . $factura_original_num, '-');
    $motivo_rectificado = isset($rsItemFR['motivo_rectificado'])
        ? trim((string) $rsItemFR['motivo_rectificado'])
        : '';
}

$direccion_tienda = $rsItemFR['direccion_tienda'];
$poblacion_tienda = $rsItemFR['poblacion_tienda'];
$provincia_tienda = $rsItemFR['provincia_tienda'];
$codigo_postal_tienda = $rsItemFR['codigo_postal_tienda'];
$telefono_tienda = $rsItemFR['telefono_tienda'];
$email_tienda = $rsItemFR['email_tienda'];

$id_sello = $rsItemFR['sello_sucursal'];
$sello_image = $rsItemFR['sello_image'];

$nombre_empresa = $rsItemFR['nombre_empresa'];
$cif_empresa = $rsItemFR['cif_empresa'];
$direccion_empresa = $rsItemFR['direccion_empresa'];
$poblacion_empresa = $rsItemFR['poblacion_empresa'];
$provincia_empresa = $rsItemFR['provincia_empresa'];
$pais_empresa = $rsItemFR['pais_empresa'];
$telefono_empresa = $rsItemFR['telefono_empresa'];
$codigo_postal_empresa = $rsItemFR['codigo_postal_empresa'];
$email_empresa = isset($rsItemFR['email_empresa']) ? $rsItemFR['email_empresa'] : '';

$datos_footer_empresa = $nombre_empresa . '  - CIF: ' . $cif_empresa . ' - ' . $direccion_empresa . ' -  ' . $poblacion_empresa . ' - España';

$emailCustomer = trim((string) ($rsItemFR['email_datos_cliente'] ?? $rsItemFR['email_cliente'] ?? ''));
$telefonoCustomer = trim((string) ($telefono ?? ''));
$movilCustomer = trim((string) ($rsItemFR['movil_datos_cliente'] ?? $rsItemFR['movil_cliente'] ?? ''));

$identificador_venta = (int) ($rsItemFR['identificador_venta'] ?? 0);
$id_empresa = (int) ($rsItemFR['id_empresa'] ?? 0);
$id_rel_factura_fiskaly = (int) ($rsItemFR['id_rel_factura_fiskaly'] ?? 0);

$fiskalyQr = fiskalyObtenerDatosQrImpresion(
    $id_empresa,
    $id_rel_factura_fiskaly,
    $identificador_venta,
    (int) $sucursal_factura,
    (int) $numero_factura
);
$id_qr = $fiskalyQr['tbai'];
$imagen_codigo_qr = $fiskalyQr['imagen_codigo_qr'];
$url_validacion_tbai = $fiskalyQr['url_validacion'];
$qr_img_html = fiskalyHtmlImagenQr($imagen_codigo_qr);

// VAR ORIGINALES DEL DOCUMENTO
if ($es_rectificativa) {
    $titulodocumento = $es_rectificativa_simplificada
        ? 'Factura rectificativa simplificada'
        : 'Factura rectificativa';
} else {
    $titulodocumento = $es_simplificada ? 'Factura simplificada' : 'Factura';
}
$appColorBrand = '#555555';

$numero_factura_impresion = trim($prefijo_factura . '-' . $numero_factura);
$invoiceNumber = $numero_factura_impresion;
$hoy = date('d/m/Y');
$currentyear = date('Y');

function parsearPrecioReturn($priceNumber)
{
    return number_format((float) $priceNumber, 2, ',', '.');
}

$mpdfone = new \Mpdf\Mpdf([
    'mode' => 'utf-8',
    'format' => 'A4',
    'margin_left' => 5,
    'margin_right' => 5,
    'margin_top' => 30,
    'margin_bottom' => 21,
    'margin_header' => 5,
    'margin_footer' => 5,
]);

$mpdfone->SetProtection(['print']);
$mpdfone->SetTitle('Factura ' . $nombre_empresa);
$mpdfone->SetAuthor($nombre_empresa);
$mpdfone->SetWatermarkText('Enviada');
$mpdfone->showWatermarkText = false;
$mpdfone->watermark_font = 'DejaVuSansCondensed';
$mpdfone->watermarkTextAlpha = 0.1;
$mpdfone->SetDisplayMode('fullpage');

$headerTabla = '
    <table width="100%" style="margin-top:20px;">
        <tr>
            <td width="50%">' .
                (!empty($logotipoPdf)
                    ? '<img src="../../photos/' . htmlspecialchars($logotipoPdf, ENT_QUOTES, 'UTF-8') . '" width="300" height="auto" alt="">'
                    : '&nbsp;') . '
            </td>
            <td width="50%" style="text-align: right; vertical-align: bottom;">
               <p class="textoheader">' . htmlspecialchars($nombre_empresa, ENT_QUOTES, 'UTF-8') . '</p>
               <p class="textoheader">CIF: ' . htmlspecialchars($cif_empresa, ENT_QUOTES, 'UTF-8') . '</p>
               <p class="textoheader">' . htmlspecialchars($direccion_tienda . ' - ' . $poblacion_tienda, ENT_QUOTES, 'UTF-8') . '</p>
               <p class="textoheader">' . htmlspecialchars($provincia_tienda . ' - España - (' . $codigo_postal_tienda . ')', ENT_QUOTES, 'UTF-8') . '</p>
               <p class="textoheader">' . htmlspecialchars($email_tienda . ' - ' . $telefono_tienda, ENT_QUOTES, 'UTF-8') . '</p>
            </td>
        </tr>
    </table>
    ';
$mpdfone->SetHTMLHeader($headerTabla);



$html = '
<html>
<head>
<meta charset="UTF-8" />
	<style>
			html {
				background: #ffffff;
			}
			body {
				background: #ffffff;
				font-family: chelvetica;
				font-size: 9pt;
				width:900px;
				margin:0 auto;
				color:#555555;
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
			.textoheader{
				font-size:12px;
				height: 31.3pt;
				color:#555555;
			}
			.textofooter{
				text-align:left;
				width: 350px;
				float:left;
				display: block;
				line-height: 24px;
				background-color: red;
			}
			.paginacionfooter{
				background-color: #EEEEEE;
				width: 20px;
				text-align:right;
				float: right;
				display: block;
				line-height: 24px;
			}
			.titulodocumento{
				color:#555555;
				font-size: 36px;
				text-align: left;
				display: block;
				width: 350px;
				float: right;
				border-bottom: 2px solid '.$appColorBrand.';
				line-height: 44px;
				margin:0px;
			}
			.texto-a{
				color: '.$appColorBrand.';
				font-style: italic;
				font-size: 15px;
				font-weight: normal;
				text-align: right;
				line-height: 29px;
				margin-right:2px;
			}
			.texto-b{
				color: #555555;
				font-style: normal;
				font-size: 15px;
				font-weight: normal;
				line-height: 29px;
			}
			.adestino{
				background: '.$appColorBrand.';
				color: #ffffff;
				font-style: normal;
				font-size: 27px;
				font-weight: normal;
				text-align: right;
				line-height: 20px;
				display: block;
				width: 60px;
				padding: 0px 3px 5px 0px;
				height: 20px;
				margin: 0px;
				float: left;
			}
			.destinatariodata{
				float:left;
				width:390px;
				height:auto;
				text-align:left;
			}
			.texto-c{
				color: #555555;
				font-style: normal;
				font-size: 14px;
				font-weight: normal;
				line-height: 25px;
			}
			.nombrecliente{
				color: #555555;
				font-style: normal;
				font-size: 16px;
				font-weight: bold;
				line-height: 20px;
			}
			.totaltopleft{
				background: '.$appColorBrand.';
				color: #ffffff;
				font-style: normal;
				font-size: 27px;
				font-weight: bold;
				text-align: right;
				line-height: 58px;
				display: block;
				width: 174px;
				padding: 0px 13px 5px 0px;
				height: 54px;
				margin: 0px;
				float: left;
			}
			.totaltopright{
				background: #EDEDEE;
				color: '.$appColorBrand.';
				font-style: normal;
				letter-spacing:-1px;
				font-size: 22px;
				font-weight: bold;
				text-align: center;
				line-height: 58px;
				display: block;
				width: 143px;
				padding: 0px 3px 5px 0px;
				height: 54px;
				margin: 0px;
				float: right;
			}
			.itemnumbernav{
				background: '.$appColorBrand.';
				color: #ffffff;
				font-style: normal;
				font-size: 15px;
				font-weight: normal;
				text-align: center;
				line-height: 30px;
				display: block;
				width: 70px;
				padding: 0px 0px 0px 0px;
				height: 28px;
				margin: 0px;
				float: left;
			}
			.itemdescripcionnav{
				background: '.$appColorBrand.';
				color: #ffffff;
				font-style: normal;
				font-size: 15px;
				font-weight: normal;
				text-align: center;
				line-height: 30px;
				display: block;
				width: 248px;
				padding: 0px 0px 0px 0px;
				height: 28px;
				margin: 0px 0px 0px 6px;
				float: left;
			}
			.itemunidadesnav{
				background: '.$appColorBrand.';
				color: #ffffff;
				font-style: normal;
				font-size: 15px;
				font-weight: normal;
				text-align: center;
				line-height: 30px;
				display: block;
				width: 80px;
				padding: 0px 0px 0px 0px;
				height: 28px;
				margin: 0px 0px 0px 6px;
				float: left;
			}
			.itempreciounitarionav{
				background: '.$appColorBrand.';
				color: #ffffff;
				font-style: normal;
				font-size: 15px;
				font-weight: normal;
				text-align: center;
				line-height: 30px;
				display: block;
				width: 90px;
				padding: 0px 0px 0px 0px;
				height: 28px;
				margin: 0px 0px 0px 6px;
				float: left;
			}
			.itemivanav{
				background: '.$appColorBrand.';
				color: #ffffff;
				font-style: normal;
				font-size: 15px;
				font-weight: normal;
				text-align: center;
				line-height: 30px;
				display: block;
				width: 91px;
				padding: 0px 0px 0px 0px;
				height: 28px;
				margin: 0px 0px 0px 6px;
				float: left;
			}
			.itemtotalnav{
				background: '.$appColorBrand.';
				color: #ffffff;
				font-style: normal;
				font-size: 13px;
				font-weight: normal;
				text-align: center;
				line-height: 30px;
				display: block;
				width: 146px;
				padding: 0px 0px 0px 0px;
				height: 28px;
				margin: 0px 0px 0px 6px;
				float: left;
			}
			.itemsinvoice{
				font-style: normal;
				font-size: 13px;
				font-weight: normal;
				text-align: left;
				display: block;
				width: 100%;
				padding: 0px 5px;
				height: 30px;
				margin: 0px 0px 5px 0px;
			}
			.pitemnumbernav{
				font-style: normal;
				font-size: 13px;
				font-weight: normal;
				text-align: center;
				line-height: 30px;
				display: block;
				width: 60px;
				padding: 0px 0px 0px 0px;
				height: 23px;
				margin: 0px;
				float: left;
			}
			.pitemdescripcionnav{
				font-style: normal;
				font-size: 12px;
				font-weight: normal;
				text-align: left;
				line-height: 20px;
				display: block;
				width: 242px;
				padding: 0px 0px 0px 0px;
				height: 23px;
				margin: 0px 0px 0px 14px;
				float: left;
			}
			.pitemunidadesnav{
				font-style: normal;
				font-size: 13px;
				font-weight: normal;
				text-align: center;
				line-height: 30px;
				display: block;
				width: 70px;
				padding: 0px 0px 0px 0px;
				height: 23px;
				margin: 0px 0px 0px 14px;
				float: left;
			}
			.pitempreciounitarionav{
				font-style: normal;
				font-size: 13px;
				font-weight: normal;
				text-align: center;
				line-height: 30px;
				display: block;
				width: 81px;
				padding: 0px 0px 0px 0px;
				height: 23px;
				margin: 0px 0px 0px 14px;
				float: left;
			}
			.pitemivanav{
				font-style: normal;
				font-size: 13px;
				font-weight: normal;
				text-align: center;
				line-height: 30px;
				display: block;
				width: 100px;
				padding: 0px 0px 0px 0px;
				height: 23px;
				margin: 0px 0px 0px 4px;
				float: left;
			}
			.pitemtotalnav{
				font-style: normal;
				font-size: 13px;
				font-weight: normal;
				text-align: center;
				line-height: 30px;
				display: block;
				width: 126px;
				padding: 0px 0px 0px 0px;
				height: 23px;
				margin: 0px 0px 0px 14px;
				float: left;
			}
			.textototalbottomleft{
				background: #EDEDEE;
				color:#555555;
				font-style: normal;
				font-size: 13px;
				font-weight: normal;
				text-align: right;
				line-height: 21px;
				display: block;
				width: 160px;
				padding: 13px 10px;
				margin: 0px;
				float: left;
			}
			.textototalbottomright{
				background: #EDEDEE;
				color:#555555;
				font-style: normal;
				letter-spacing:0px;
				font-size: 13px;
				font-weight: normal;
				text-align: right;
				line-height: 21px;
				display: block;
				width: 126px;
				padding: 13px 10px;
				margin: 0px;
				float: right;
			}
			.totalbottomleft{
				background: '.$appColorBrand.';
				color: #ffffff;
				font-style: normal;
				font-size: 22px;
				font-weight: bold;
				text-align: right;
				line-height: 26px;
				display: block;
				width: 160px;
				padding: 10px;
				margin: 0px;
				float: left;
			}
			.totalbottomright{
				background: #EDEDEE;
				color:'.$appColorBrand.';
				font-style: normal;
				letter-spacing:-1px;
				font-size: 22px;
				font-weight: bold;
				text-align: right;
				line-height: 26px;
				display: block;
				width: 126px;
				padding: 10px;
				margin: 0px;
				float: right;
			}
			.classhr{
				color:#EDEDEE;
				height: 2px;
			}
            #sello {
        width: 180px;
        height: 180px;
        border: none;
        border-radius: 50%;
        text-align: center;
        display: block;
        font-family: "Helvetica Neue",Helvetica,Arial,sans-serif;
        transform: rotate(-15deg);
        margin-top: -30px;
        opacity: 1;
        margin-left: 0px;
        margin-bottom: 0px;
    }
   #ordago {
        width: 100%;
        height: 65px;
        margin: 2px auto 19px;
        display: block;
        position: relative;
        float: none;
        image-rendering: optimizequality;
    }
    #ordago_sinlogo {
        width: 165px;
        height: 32px;
        margin: 2px auto 19px;
        display: block;
        position: relative;
        float: none;
    }
    #ordago img{
        width: 100%;
        image-rendering: optimizequality;
    }
    .spans_sellos {
        display: block;
        font-size: 10px;
        line-height: 13px;
        min-height: auto !important;
    }
    .spans_sellos_sinlogo {
        display: block;
        font-size: 13px;
        line-height: 18px;
        min-height: auto !important;
    }
    #nombre_empresa {
        letter-spacing: -1px;
        font-size: 14.5px;
        margin: 5px 0px auto;
        line-height: normal;
        min-height: auto;
    }
    #nombre_empresa.spans_sellos_sinlogo {
        font-weight: bold;
        letter-spacing: -1px;
        font-size: 15px;
        margin: 5px 0px auto;
        line-height: 22px;
        min-height: auto;
    }
		</style>
</head>
<body>
';

$html .= '
<div style="width: 100%; height: 55px; padding-top: 5px;"><h1 class="titulodocumento">'.$titulodocumento.'</h1></div>
';

if ($es_simplificada) {
    $html .= '
<div style="width: 100%; height: 105px; margin-bottom: 5px;">
	<div style="width: 100%; height: 100px; float: left">
		<div style="width: 145px; height: 80px; float: left; padding: 5px 0px; ">
			<p class="texto-a" style="text-align: left;">Número factura: </p>
			<p class="texto-a" style="text-align: left;">Fecha emisión: </p>
            <p class="texto-a" style="text-align: left;">Forma de pago: </p>
		</div>
		<div style="width: 195px; height: 80px; float: left; padding: 5px 0px; ">
			<p class="texto-b" style="text-align: left;">'.$numero_factura_impresion.'</p>
			<p class="texto-b" style="text-align: left;">'.$fecha_factura_final.'</p>
            <p class="texto-b" style="text-align: left; text-transform: capitalize;">'.$tipo_pago.'</p>
		</div>
        <div style="width:340px; height:50px; float: right; margin-top: 50px;">
        	<h1 class="totaltopleft">TOTAL:</h1>
			<h1 class="totaltopright">'.parsearPrecioReturn($total_factura).' €</h1>
        </div>
	</div>
</div>
';
} else {
    $html .= '
<div style="width: 100%; height: 165px;">
	<div style="width: 400px; height: 155px; float: left">
';
    if (!empty($id_cliente)) {
        $html .= '
        <div class="destinatariodata">
        	<p class="nombrecliente">'.$nombrecomercialCustomer.'</p>
			<p class="texto-c" style="text-transform: uppercase;">'.htmlspecialchars(trim($tiponifCustomer . ' ' . $nifCustomer), ENT_QUOTES, 'UTF-8').'</p>
			<p class="texto-c">'.$addressFullInvoice.' - '.$populationCustomerInvoice.'</p>
            <p class="texto-c">'.$provinceCustomerInvoice.' - '. $countryCustomerInvoice.' - ('.$postalcodeCustomerInvoice.')</p>
            <p class="texto-c">'.$emailCustomer.'</p>
            <p class="texto-c">'.$telefonoCustomer.' '.$movilCustomer.'</p>
        </div>
';
    }
    $html .= '
    </div>
	<div style="width: 340px; height: 100px; float: right">
		<div style="width: 160px; height: 80px; float: left; padding: 5px 0px; ">
			<p class="texto-a" style="text-align: left;">'.$titulodocumento.' Nº: </p>
			<p class="texto-a" style="text-align: left;">Fecha emisión: </p>
            <p class="texto-a" style="text-align: left;">Forma de pago: </p>
            ' . ($es_rectificativa ? '<p class="texto-a" style="text-align: left;">Rectifica factura Nº: </p>' : '') . '
		</div>
		<div style="width: 170px; height: 80px; float: left; padding: 5px 0px; ">
			<p class="texto-b" style="text-align: left;">'.$numero_factura_impresion.'</p>
			<p class="texto-b" style="text-align: left;">'.$fecha_factura_final.'</p>
            <p class="texto-b" style="text-align: left; text-transform: capitalize;">'.$tipo_pago.'</p>
            ' . ($es_rectificativa
                ? '<p class="texto-b" style="text-align: left;">' . htmlspecialchars($factura_original_display, ENT_QUOTES, 'UTF-8') . '</p>'
                : '') . '
		</div>
        <div style="width:100%; height:60px; float: left;">
        	<h1 class="totaltopleft">TOTAL:</h1><h1 class="totaltopright">'.parsearPrecioReturn($total_factura).' €</h1>
        </div>
	</div>
</div>
';
}
if ($es_rectificativa && $motivo_rectificado !== '') {
    $html .= '<div style="width: 100%; margin-bottom: 8px;"><p class="texto-c">Motivo rectificado: '
        . htmlspecialchars($motivo_rectificado, ENT_QUOTES, 'UTF-8') . '</p></div>';
}

$html .= '
<div style="width: 100%; height: 30px; margin-bottom: 5px;">
	<h1 class="itemnumbernav">' . ($es_renovaciones ? 'N#' : 'SKU') . '</h1>
    <h1 class="itemdescripcionnav">Descripción</h1>
    <h1 class="itemunidadesnav">Cantidad</h1>
    <h1 class="itempreciounitarionav">Precio</h1>
	<h1 class="itemivanav">Impuesto</h1>
    <h1 class="itemtotalnav" style="float: right;">Total</h1>
</div>
';



$suma_base_imponible = 0.0;
$suma_iva_21 = 0.0;
$lineas_impresas = 0;

if ($es_renovaciones) {
    $tabla_renovaciones = $tabla_items_renovaciones;
    if ($tabla_renovaciones !== '') {
        $queryFRA = '
    SELECT
        id_rel_fac_art,
        id_rel_renovacion,
        descripcion_renovacion,
        precio_rel_renovacion,
        precio_venta_sin_iva
    FROM ' . $tabla_renovaciones . '
    WHERE id_rel_factura = ?
    ORDER BY id_rel_fac_art ASC
    ';
        $stmtFRA = mysqli_prepare($conexion, $queryFRA);
        if ($stmtFRA) {
            mysqli_stmt_bind_param($stmtFRA, 'i', $id_factura);
            mysqli_stmt_execute($stmtFRA);
            $resFRA = mysqli_stmt_get_result($stmtFRA);
            if ($resFRA) {
                while ($row = mysqli_fetch_assoc($resFRA)) {
                    $id_rel_renovacion = (int) ($row['id_rel_renovacion'] ?? 0);
                    $descripcion_linea = (string) ($row['descripcion_renovacion'] ?? '');
                    $precio_rel_linea = (float) ($row['precio_rel_renovacion'] ?? 0);
                    $precio_sin_iva_linea = (float) ($row['precio_venta_sin_iva'] ?? 0);
                    if (abs($precio_sin_iva_linea) < 0.00001 && abs($precio_rel_linea) > 0.0) {
                        $precio_sin_iva_linea = $precio_rel_linea / 1.21;
                    }
                    $iva_linea = $precio_rel_linea - $precio_sin_iva_linea;
                    $suma_base_imponible += $precio_sin_iva_linea;
                    $suma_iva_21 += $iva_linea;
                    $lineas_impresas++;
                    $html .= '
			  <div class="itemsinvoice">
					<p class="pitemnumbernav">' . $id_rel_renovacion . '</p>
					<p class="pitemdescripcionnav">' . htmlspecialchars($descripcion_linea, ENT_QUOTES, 'UTF-8') . '</p>
					<p class="pitemunidadesnav">1</p>
					<p class="pitempreciounitarionav">' . parsearPrecioReturn($precio_sin_iva_linea) . ' €</p>
					<p class="pitemivanav">21%</p>
					<p class="pitemtotalnav">' . parsearPrecioReturn($precio_rel_linea) . ' €</p>
				</div>
			';
                }
            }
            mysqli_stmt_close($stmtFRA);
        }
    }
    if ($lineas_impresas === 0 && abs($total_factura) > 0.0) {
        $precio_rel_linea = $total_factura;
        $precio_sin_iva_linea = $total_factura / 1.21;
        $iva_linea = $precio_rel_linea - $precio_sin_iva_linea;
        $suma_base_imponible += $precio_sin_iva_linea;
        $suma_iva_21 += $iva_linea;
        $descripcion_linea = $motivo_rectificado !== ''
            ? $motivo_rectificado
            : 'Abono renovación';
        $html .= '
			  <div class="itemsinvoice">
					<p class="pitemnumbernav">—</p>
					<p class="pitemdescripcionnav">' . htmlspecialchars($descripcion_linea, ENT_QUOTES, 'UTF-8') . '</p>
					<p class="pitemunidadesnav">1</p>
					<p class="pitempreciounitarionav">' . parsearPrecioReturn($precio_sin_iva_linea) . ' €</p>
					<p class="pitemivanav">21%</p>
					<p class="pitemtotalnav">' . parsearPrecioReturn($precio_rel_linea) . ' €</p>
				</div>
			';
    }
} else {
    $queryFRA = '
SELECT
    fra.id_rel_fac_art,
    fra.id_rel_sucursal,
    fra.id_rel_factura,
    fra.id_rel_articulo,
    fra.precio_rel_articulo,
    av.precio,
    av.descripcion,
    av.system_codigo_regimen,
    av.tipo_iva_articulo
FROM ' . $tabla_items_articulos . ' fra
LEFT JOIN articulos_venta av ON fra.id_rel_articulo = av.id
WHERE fra.id_rel_factura = ?
ORDER BY fra.id_rel_fac_art ASC
';
    $stmtFRA = mysqli_prepare($conexion, $queryFRA);
    if ($stmtFRA) {
        mysqli_stmt_bind_param($stmtFRA, 'i', $id_factura);
        mysqli_stmt_execute($stmtFRA);
        $resFRA = mysqli_stmt_get_result($stmtFRA);
        if ($resFRA) {
            while ($row = mysqli_fetch_assoc($resFRA)) {
                $id_rel_articulo = (int) $row['id_rel_articulo'];
                $descripcion_articulo = (string) ($row['descripcion'] ?? '');
                $precio_rel_articulo = (float) ($row['precio_rel_articulo'] ?? 0);
                $system_codigo_regimen = strtoupper(trim((string) ($row['system_codigo_regimen'] ?? '')));
                $tipo_iva_articulo = strtoupper(trim((string) ($row['tipo_iva_articulo'] ?? '')));
                if ($tipo_iva_articulo === '') {
                    $tipo_iva_articulo = 'IVA';
                }
                // Precio en línea con IVA 21 % incluido solo si régimen GENERAL/INVERSION y tipo IVA peninsular IVA
                $linea_con_iva_21 = in_array($system_codigo_regimen, array('GENERAL', 'INVERSION'), true)
                    && $tipo_iva_articulo === 'IVA';
                if ($linea_con_iva_21) {
                    $base_linea = $precio_rel_articulo / 1.21;
                    $iva_linea = $precio_rel_articulo - $base_linea;
                } else {
                    $base_linea = $precio_rel_articulo;
                    $iva_linea = 0.0;
                }
                $suma_base_imponible += $base_linea;
                $suma_iva_21 += $iva_linea;
                $lineas_impresas++;
                if ($linea_con_iva_21) {
                    $pitemivanav = '21%';
                } elseif ($system_codigo_regimen === 'REBU') {
                    $pitemivanav = '---';
                } elseif ($tipo_iva_articulo !== '' && $tipo_iva_articulo !== 'IVA') {
                    $pitemivanav = $tipo_iva_articulo;
                } else {
                    $pitemivanav = '--';
                }
                $html .= '
			  <div class="itemsinvoice">
					<p class="pitemnumbernav">' . $id_rel_articulo . '</p>
					<p class="pitemdescripcionnav">' . htmlspecialchars($descripcion_articulo, ENT_QUOTES, 'UTF-8') . '</p>
					<p class="pitemunidadesnav">1</p>
					<p class="pitempreciounitarionav">' . parsearPrecioReturn($base_linea) . ' €</p>
					<p class="pitemivanav">' . htmlspecialchars($pitemivanav, ENT_QUOTES, 'UTF-8') . '</p>
					<p class="pitemtotalnav" style="float: right;">' . parsearPrecioReturn($precio_rel_articulo) . ' €</p>
				</div>
			';
            }
            mysqli_free_result($resFRA);
        }
        mysqli_stmt_close($stmtFRA);
    }
    if ($lineas_impresas === 0 && abs($total_factura) > 0.0) {
        $precio_rel_articulo = $total_factura;
        $base_linea = $total_factura / 1.21;
        $iva_linea = $precio_rel_articulo - $base_linea;
        $suma_base_imponible += $base_linea;
        $suma_iva_21 += $iva_linea;
        $descripcion_articulo = $motivo_rectificado !== ''
            ? $motivo_rectificado
            : 'Abono factura rectificativa';
        $html .= '
			  <div class="itemsinvoice">
					<p class="pitemnumbernav">—</p>
					<p class="pitemdescripcionnav">' . htmlspecialchars($descripcion_articulo, ENT_QUOTES, 'UTF-8') . '</p>
					<p class="pitemunidadesnav">1</p>
					<p class="pitempreciounitarionav">' . parsearPrecioReturn($base_linea) . ' €</p>
					<p class="pitemivanav">21%</p>
					<p class="pitemtotalnav" style="float: right;">' . parsearPrecioReturn($precio_rel_articulo) . ' €</p>
				</div>
			';
    }
}
$suma_base_imponible = round($suma_base_imponible, 2);
$suma_iva_21 = round($suma_iva_21, 2);
if (abs($suma_base_imponible) < 0.00001 && abs($suma_iva_21) < 0.00001 && abs($total_factura) > 0) {
    $suma_base_imponible = round($total_factura / 1.21, 2);
    $suma_iva_21 = round($total_factura - $suma_base_imponible, 2);
}
$html_base_imponible = parsearPrecioReturn($suma_base_imponible);
$html_iva_21_total = parsearPrecioReturn($suma_iva_21);
$html_total_factura_ttc = parsearPrecioReturn($total_factura);

$html_footer_fila_iva = '';
if (abs(round($suma_iva_21, 2)) > 0.0) {
    $html_footer_fila_iva = '
            <div style="width: 330px; height: 46px; margin-top: 0; float: right;">
                <h1 class="textototalbottomleft" style="background: #EDEDEE; color: ' . $appColorBrand . ';">IVA (21%):</h1>
                <h1 class="textototalbottomright" style="background: #EDEDEE; color: ' . $appColorBrand . ';">' . $html_iva_21_total . ' €</h1>
            </div>';
}

$sello_img_html = '';
if (!empty($sello_image)) {
    $sello_img_html = '<div style="width: 330px; position: absolute; bottom: 74px; left: 88px; ">
        <img src="../../photos/' . htmlspecialchars($sello_image, ENT_QUOTES, 'UTF-8') . '" width="188" alt="">
    </div>';
}

$html_ticketbai = '';
if ($id_qr !== '' || $qr_img_html !== '') {
    $html_ticketbai = '
    <div style="width: 100%; text-align: center; font-style: italic; margin:15px 0 0 0; color:#000000; font-size:7pt;">
        <p style="text-align: center; font-style: normal; margin:0px 0 5px 0; color:#000000; font-size:7pt;">'
            . htmlspecialchars($id_qr, ENT_QUOTES, 'UTF-8') . '</p>'
        . $qr_img_html . '
    </div>';
}

$html .= $sello_img_html . '
        
</body>
</html>
';

$mpdfone->SetHTMLFooter('
<div class="footer"  style="padding-top: 20px; width: 100%;">
<hr class="classhr">
<div style=" width: 100%;">

    <div style="width: 330px; float: right;">
        <div style="width: 330px; height: auto; margin-top: 5px; float: right;">
            <div style="width: 330px; height: 46px; margin-top: 0; float: right;">
                <h1 class="textototalbottomleft" style="background: #EDEDEE; color: '.$appColorBrand.';">Base imponible:</h1>
                <h1 class="textototalbottomright" style="background: #EDEDEE; color: '.$appColorBrand.';">'.$html_base_imponible.' €</h1>
            </div>
            '.$html_footer_fila_iva.'
            <div style="width: 330px; height: 65px; margin-top: 4px; float: right;">
                <h1 class="totalbottomleft" style="color: '.$appColorBrand.';" >TOTAL:</h1>
                <h1 class="totalbottomright" style="background: #EDEDEE !important;
				color:'.$appColorBrand.';
				font-style: normal;
				letter-spacing:-1px;
				font-size: 22px;
				font-weight: bold;
				text-align: right;
				line-height: 26px;
				display: block;
				width: 126px;
				padding: 10px;
				margin: 0px;
				float: right;">'.$html_total_factura_ttc.' €</h1>
            </div>
        </div>
    </div>
    
</div>

	<hr class="classhr">
    <div style="width: 100%; text-align: left; font-style: italic; margin:5px 0 0 0; color:#000000; font-size:7pt; ">'.$texto_facturas_pie.'</div>
    '.$html_ticketbai.'
    <p style="text-align: left; font-style: normal; margin:10px 0 0 0; color:#000000; font-size:7pt; ">'.$datos_footer_empresa.'</p>
</div>
'
);

$mpdfone->WriteHTML($html);
mysqli_close($conexion);
if ($factura_mpdf_buffer) {
    return $mpdfone->OutputBinaryData();
}
$mpdfone->OutputHttpDownload('factura_' . $id_factura . '.pdf');
exit;