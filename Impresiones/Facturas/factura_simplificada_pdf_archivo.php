<?php
/**
 * PDF factura simplificada (tabla facturas_simplificadas, líneas facturas_simplificadas_rel_articulos).
 * Sin datos de cliente en el documento.
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

$id_factura = isset($_GET['id_factura']) ? (int) $_GET['id_factura'] : 0;
if ($id_factura <= 0) {
    if ($factura_mpdf_buffer) {
        return null;
    }
    header('Location: ../../dashboard.php');
    exit;
}

if (!$factura_mpdf_buffer) {
    fiskalyRedirigirImpresionDesdeGet($id_factura, 'facturas_simplificadas', true);
}

if ($factura_mpdf_buffer && $id_factura > 0) {
    $conexionFiskaly = conectar_bd();
    if ($conexionFiskaly) {
        $stmtFsk = mysqli_prepare(
            $conexionFiskaly,
            'SELECT factura_regimen, id_rel_factura_fiskaly FROM facturas_simplificadas WHERE id_factura = ? LIMIT 1'
        );
        if ($stmtFsk) {
            mysqli_stmt_bind_param($stmtFsk, 'i', $id_factura);
            mysqli_stmt_execute($stmtFsk);
            $resFsk = mysqli_stmt_get_result($stmtFsk);
            $rowFsk = $resFsk ? mysqli_fetch_assoc($resFsk) : null;
            mysqli_stmt_close($stmtFsk);
            if ($rowFsk && fiskalyEsFacturaFiskaly(
                (string) ($rowFsk['factura_regimen'] ?? 'false'),
                (int) ($rowFsk['id_rel_factura_fiskaly'] ?? 0)
            ) && fiskalyScriptImpresionFactura((string) ($rowFsk['factura_regimen'] ?? 'false'), true) === 'factura_Bizkaia_tbai.php') {
                mysqli_close($conexionFiskaly);
                $_GET['tipo'] = 'simplificada';

                return require __DIR__ . '/factura_Bizkaia_tbai.php';
            }
        }
        mysqli_close($conexionFiskaly);
    }
}

$conexion = conectar_bd();
require_once __DIR__ . '/../../vendor/autoload.php';

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
    FCTR.factura_regimen,
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
    NULL AS firma_cliente,
    NULL AS email_datos_cliente,
    NULL AS movil_datos_cliente
FROM facturas_simplificadas AS FCTR
LEFT JOIN sucursal AS SCR ON SCR.id_sucursal = FCTR.id_sucursal
LEFT JOIN ventas AS VTS ON VTS.id = FCTR.rel_id_venta
LEFT JOIN empresas AS EMPS ON EMPS.id_empresa = SCR.empresa_id
WHERE FCTR.id_factura = ?
LIMIT 1";

$stmtFR = mysqli_prepare($conexion, $queryFR);
if (!$stmtFR) {
    if ($factura_mpdf_buffer) {
        return null;
    }
    die('Error en la consulta: ' . mysqli_error($conexion));
}

mysqli_stmt_bind_param($stmtFR, 'i', $id_factura);
mysqli_stmt_execute($stmtFR);
$rsItemFR = mysqli_stmt_fetch_assoc_compat($stmtFR);
mysqli_stmt_close($stmtFR);

if (!$rsItemFR) {
    if ($factura_mpdf_buffer) {
        return null;
    }
    header('Location: ../../dashboard.php');
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
$texto_facturas = obtenerTextoLegalFactura($tipo_factura);

$prefijo_factura = $rsItemFR['prefijo_factura'];

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

$emailCustomer = trim((string) ($rsItemFR['email_datos_cliente'] ?? ''));
$telefonoCustomer = trim((string) ($telefono ?? ''));
$movilCustomer = trim((string) ($rsItemFR['movil_datos_cliente'] ?? ''));

$identificador_venta = (int) ($rsItemFR['identificador_venta'] ?? 0);
$id_empresa = (int) ($rsItemFR['id_empresa'] ?? 0);
$id_rel_factura_fiskaly = (int) ($rsItemFR['id_rel_factura_fiskaly'] ?? 0);
$factura_regimen = (string) ($rsItemFR['factura_regimen'] ?? 'false');
$fiskalyQr = fiskalyObtenerDatosQrImpresion(
    $id_empresa,
    $id_rel_factura_fiskaly,
    $identificador_venta,
    (int) $sucursal_factura,
    (int) $numero_factura
);
$altQr = ($factura_regimen === 'Verifactu' || $factura_regimen === 'General') ? 'QR Verifactu' : 'QR TicketBAI';
$html_fiskaly_qr = fiskalyHtmlBloqueQrImpresion(
    $fiskalyQr['tbai'],
    fiskalyHtmlImagenQr($fiskalyQr['imagen_codigo_qr'], $altQr),
    $factura_regimen
);

// VAR ORIGINALES DEL DOCUMENTO
$titulodocumento = 'Factura simplificada';
$appColorBrand = '#555555';

$invoiceNumber = trim($prefijo_factura . ' ' . $numero_factura . '/' . $anyo_factura);
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
$mpdfone->SetTitle('Factura simplificada ' . $nombre_empresa);
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
				font-size: 40px;
				text-align: left;
				display: block;
				width: 100%;
				float: left;
				border-bottom: 2px solid '.$appColorBrand.';
				line-height: 44px;
				margin:0px;
			}
			.texto-a{
				color: '.$appColorBrand.';
				font-style: italic;
				font-size: 17px;
				font-weight: normal;
				text-align: right;
				line-height: 29px;
				margin-right:5px;
			}
			.texto-b{
				color: #555555;
				font-style: normal;
				font-size: 17px;
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
				line-height: 45px;
				display: block;
				width: 174px;
				padding: 0px 13px 5px 0px;
				height: 45px;
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
				line-height: 45px;
				display: block;
				width: 143px;
				padding: 0px 3px 5px 0px;
				height: 45px;
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
<div style="width: 100%; height: 105px; margin-bottom: 5px;">
	
	<div style="width: 100%; height: 100px; float: left">
	
		<div style="width: 140px; height: 80px; float: left; padding: 5px 0px; ">
		
			<p class="texto-a" style="text-align: left;">Número factura: </p>
		
			<p class="texto-a" style="text-align: left;">Fecha emisión: </p>
            
            <p class="texto-a" style="text-align: left;">Forma de pago: </p>
		
		</div>
	
		<div style="width: 220px; height: 80px; float: left; padding: 5px 0px; ">
			
			<p class="texto-b" style="text-align: left;">'.$prefijo_factura.' '.$numero_factura.'/'.$anyo_factura.'</p>
		
			<p class="texto-b" style="text-align: left;">'.$fecha_factura_final.'</p>
            
            <p class="texto-b" style="text-align: left; text-transform: capitalize;">'.$tipo_pago.'</p>

			
		</div>
        
        <div style="width:340px; height:50px; float: right; margin-top: 50px;">
        	<h1 class="totaltopleft">TOTAL:</h1>
			<h1 class="totaltopright">'.parsearPrecioReturn($total_factura).' €</h1>
        </div>
		
	</div>
	
</div>

<div style="width: 100%; height: 30px; margin-bottom: 5px;">
	<h1 class="itemnumbernav">SKU</h1>
    <h1 class="itemdescripcionnav">Descripción</h1>
    <h1 class="itemunidadesnav">Cantidad</h1>
    <h1 class="itempreciounitarionav">Precio</h1>
	<h1 class="itemivanav">Impuesto</h1>
    <h1 class="itemtotalnav" style="float: right;">Total</h1>
</div>
';



$queryFRA = '';
if ($tipo_factura === 'renovaciones') {
    $queryFRA = '
    SELECT
        frr.id_rel_fac_art,
        frr.id_rel_sucursal,
        frr.id_rel_factura,
        frr.id_rel_renovacion,
        frr.precio_rel_renovacion,
        frr.descripcion_renovacion,
        frr.precio_venta_sin_iva
    FROM facturas_simplificadas_rel_renovaciones frr
    WHERE frr.id_rel_factura = ?
    ORDER BY frr.id_rel_fac_art ASC
    ';
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
    FROM facturas_simplificadas_rel_articulos fra
    LEFT JOIN articulos_venta av ON fra.id_rel_articulo = av.id
    WHERE fra.id_rel_factura = ?
    ORDER BY fra.id_rel_fac_art ASC
    ';
}
$suma_base_imponible = 0.0;
$suma_iva_21 = 0.0;
$stmtFRA = mysqli_prepare($conexion, $queryFRA);
if ($stmtFRA) {
    mysqli_stmt_bind_param($stmtFRA, 'i', $id_factura);
    mysqli_stmt_execute($stmtFRA);
    $fraRows = mysqli_stmt_fetch_all_assoc_compat($stmtFRA);
    mysqli_stmt_close($stmtFRA);
    foreach ($fraRows as $row) {
            if ($tipo_factura === 'renovaciones') {
                $id_rel_renovacion = (int) ($row['id_rel_renovacion'] ?? 0);
                $descripcion_renovacion = (string) ($row['descripcion_renovacion'] ?? '');
                $precio_rel_renovacion = (float) ($row['precio_rel_renovacion'] ?? 0);
                $precio_venta_sin_iva = (float) ($row['precio_venta_sin_iva'] ?? 0);

                if ($precio_venta_sin_iva <= 0.0 && $precio_rel_renovacion > 0.0) {
                    $precio_venta_sin_iva = $precio_rel_renovacion / 1.21;
                }

                $base_linea = $precio_venta_sin_iva;
                $iva_linea = $precio_rel_renovacion - $base_linea;

                $suma_base_imponible += $base_linea;
                $suma_iva_21 += $iva_linea;

                $html .= '
                  <div class="itemsinvoice">
                        <p class="pitemnumbernav">' . $id_rel_renovacion . '</p>
                        <p class="pitemdescripcionnav">' . htmlspecialchars($descripcion_renovacion, ENT_QUOTES, 'UTF-8') . '</p>
                        <p class="pitemunidadesnav">1</p>
                        <p class="pitempreciounitarionav">' . parsearPrecioReturn($base_linea) . ' €</p>
                        <p class="pitemivanav">21%</p>
                        <p class="pitemtotalnav" style="float: right;">' . parsearPrecioReturn($precio_rel_renovacion) . ' €</p>
                    </div>
                ';
            } else {
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
    }
}
$suma_base_imponible = round($suma_base_imponible, 2);
$suma_iva_21 = round($suma_iva_21, 2);
if ($suma_base_imponible < 0.00001 && $suma_iva_21 < 0.00001 && $total_factura > 0) {
    $suma_base_imponible = round($total_factura / 1.21, 2);
    $suma_iva_21 = round($total_factura - $suma_base_imponible, 2);
}
$html_base_imponible = parsearPrecioReturn($suma_base_imponible);
$html_iva_21_total = parsearPrecioReturn($suma_iva_21);
$html_total_factura_ttc = parsearPrecioReturn($total_factura);

$html_footer_fila_iva = '';
if (round($suma_iva_21, 2) > 0.0) {
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
    <div style="width: 100%; text-align: left; font-style: italic; margin:5px 0 0 0; color:#000000; font-size:7pt; ">'.$texto_facturas.'</div>
    '.$html_fiskaly_qr.'
    <p style="text-align: left; font-style: normal; margin:10px 0 0 0; color:#000000; font-size:7pt; ">'.$datos_footer_empresa.'</p>
</div>
'
);

$mpdfone->WriteHTML($html);
mysqli_close($conexion);
if ($factura_mpdf_buffer) {
    return $mpdfone->OutputBinaryData();
}
$mpdfone->OutputHttpDownload('factura_simplificada_' . $id_factura . '.pdf');
exit;