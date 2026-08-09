<?php
if ($origen_print != 'enviar_proforma') {
    require_once '../../include/session.php';
	require_once '../../include/functions.php';

    $id_proforma = isset($_GET['id_proforma']) ? (int) $_GET['id_proforma'] : 0;
    $id_sucursal = isset($suc) ? $suc : '';
}


$conexion = conectar_bd();
require_once __DIR__ . '/../../vendor/autoload.php';
$id_proforma = (int) $id_proforma;

$queryFR = "SELECT
PRF.fecha_envio,
PRF.fecha_proforma,
PRF.usuario_envia_proforma,
PRF.importe_proforma,
PRF.total_gramos_enviados,
PRF.observacion_proforma,
PRF.tipo_metal_proforma,
PRF.precio_gramo_proforma,
EMPR.id_empresa,
EMPR.nombre_empresa,
EMPR.cif_empresa,
EMPR.direccion_empresa,
EMPR.poblacion_empresa,
EMPR.provincia_empresa,
EMPR.pais_empresa,
EMPR.telefono_empresa,
EMPR.codigo_postal_empresa,
EMPR.email_empresa,
EMPR.logotipo_empresa,
PRO.nombre_proveedor,
PRO.cif_proveedor,
PRO.direccion_proveedor,
PRO.poblacion_proveedor,
PRO.provincia_proveedor,
PRO.pais_proveedor,
PRO.telefono_proveedor,
PRO.codigo_postal_proveedor,
PRO.email_proveedor,
FRP.nombre_forma_de_pago
FROM proformas AS PRF
LEFT JOIN empresas AS EMPR ON EMPR.id_empresa = PRF.empresa_proforma 
LEFT JOIN proveedores AS PRO ON PRO.id_proveedor = PRF.proveedor_proforma
LEFT JOIN formas_de_pago AS FRP ON FRP.id_forma_de_pago = PRF.forma_de_pago
WHERE PRF.id_proforma = ?
";

$stmtFR = mysqli_prepare($conexion, $queryFR);
$rsItemFR = null;
if ($stmtFR) {
    mysqli_stmt_bind_param($stmtFR, 'i', $id_proforma);
    mysqli_stmt_execute($stmtFR);
    $resFR = mysqli_stmt_get_result($stmtFR);
    if ($resFR) {
        $rsItemFR = mysqli_fetch_assoc($resFR);
        mysqli_free_result($resFR);
    }
    mysqli_stmt_close($stmtFR);
}

if (!is_array($rsItemFR)) {
    exit('No se encontró la proforma.');
}

$importe_proforma = 66;
$fecha_envio = $rsItemFR['fecha_envio'];
$fecha_envio_parset = strtotime($fecha_envio);
$anyo_envio = date("Y", $fecha_envio_parset);
$fecha_envio_final = date("d-m-Y", $fecha_envio_parset);

if (is_null($fecha_envio) || $fecha_envio === '' || $fecha_envio === '0000-00-00' || $fecha_envio === '0000-00-00 00:00:00') {
    $fecha_envio_final = "-------";
}
$currentyear = $anyo_envio;
$id_empresa = $rsItemFR['id_empresa'];
$nombre_empresa = $rsItemFR['nombre_empresa'];
$cif_empresa = $rsItemFR['cif_empresa'];
$direccion_empresa = $rsItemFR['direccion_empresa'];
$poblacion_empresa = $rsItemFR['poblacion_empresa'];
$provincia_empresa = $rsItemFR['provincia_empresa'];
$pais_empresa = $rsItemFR['pais_empresa'];
$telefono_empresa = $rsItemFR['telefono_empresa'];
$codigo_postal_empresa = $rsItemFR['codigo_postal_empresa'];
$email_empresa = $rsItemFR['email_empresa'];
$logotipoPdf = $rsItemFR['logotipo_empresa'];

$datos_footer_empresa = $nombre_empresa."  - CIF: ".$cif_empresa." - ".$direccion_empresa." -  ".$poblacion_empresa." - España";

$nombre_proveedor = $rsItemFR['nombre_proveedor'];
$cif_proveedor = $rsItemFR['cif_proveedor'];
$direccion_proveedor = $rsItemFR['direccion_proveedor'];
$poblacion_proveedor = $rsItemFR['poblacion_proveedor'];
$provincia_proveedor = $rsItemFR['provincia_proveedor'];
$pais_proveedor = $rsItemFR['pais_proveedor'];
$codigo_postal_proveedor = $rsItemFR['codigo_postal_proveedor'];
$email_proveedor = $rsItemFR['email_proveedor'];
$telefono_proveedor = $rsItemFR['telefono_proveedor'];

$nombre_forma_de_pago = $rsItemFR['nombre_forma_de_pago'];
$observacion_proforma = $rsItemFR['observacion_proforma'];

// VAR ORIGINALES DEL DOCUMENTO
$titulodocumento = "Proforma";
$appColorBrand = "#555555";

function generarCodigo6() {
    // 3 números aleatorios
    $numeros = '';
    for ($i = 0; $i < 3; $i++) {
        $numeros .= rand(0, 9);
    }
    
    // 3 letras aleatorias
    $letras = '';
    $alfabeto = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    for ($i = 0; $i < 3; $i++) {
        $letras .= $alfabeto[rand(0, 25)];
    }
    
    // Combinar y mezclar
    $codigo = $numeros . $letras;
    $codigo = str_shuffle($codigo);
    
    return $codigo;
}


$code_generate = generarCodigo6();
    
// AL PDF SE LE DEBE AGREGAR EL AÑO DE LA PROFORMA Y EL ID DE LA EMPRESA, PARA QUE NO SE REPITAN CON OTRAS DE OTRAS EMRPESAS DEL HOLDING
$name_file_parset = $titulodocumento.'-'.$nombre_empresa.'-'.$currentyear.'-'.$id_proforma.'-'.$code_generate.'.pdf';



function limpiarString($texto) {
    if (is_null($texto) || $texto === '') return '';
    
    $texto = strval($texto);
    
    // Eliminar espacios
    $texto = str_replace(' ', '', $texto);
    
    // Convertir ñ/Ñ a n/N
    $texto = str_replace('ñ', 'n', $texto);
    $texto = str_replace('Ñ', 'N', $texto);
    
    return $texto;
}

$name_file = limpiarString($name_file_parset);

function parsearPrecioReturn($priceNumber){
	$varLocal = "ES_es";
	setlocale(LC_MONETARY,"".$varLocal.".UTF-8");
	return money_format("%.2n", $priceNumber);
}

$texto_iva_proforma = "No aplicado I.V.A. en la factura por inversión del sujeto pasivo según artículo 84 de la ley de I.V.A.";


$numerocuenta = '';
$relFormaPagoPdf = obtener_rel_forma_pago_proforma($id_proforma, $conexion);
if ($relFormaPagoPdf && trim((string) ($relFormaPagoPdf['numero_forma_pago'] ?? '')) !== '') {
    $numerocuenta = trim((string) $relFormaPagoPdf['numero_forma_pago']);
} else {
$query = 'SELECT numerocuenta FROM cuentas_banco_empresas WHERE empresa_cuenta_id = ? AND por_defecto = \'true\'';
$id_empresa_int = (int) $id_empresa;
$stmtCuenta = mysqli_prepare($conexion, $query);
$rsItem = null;
if ($stmtCuenta) {
    mysqli_stmt_bind_param($stmtCuenta, 'i', $id_empresa_int);
    mysqli_stmt_execute($stmtCuenta);
    $resCuenta = mysqli_stmt_get_result($stmtCuenta);
    if ($resCuenta) {
        $rsItem = mysqli_fetch_assoc($resCuenta);
        mysqli_free_result($resCuenta);
    }
    mysqli_stmt_close($stmtCuenta);
}
$numerocuenta = is_array($rsItem) && isset($rsItem['numerocuenta']) ? $rsItem['numerocuenta'] : '';
}

$sello = '
<div id="sello" style="width: 180px;">
    <span class="spans_sellos_sinlogo" id="ordago_sinlogo"></span>
    <span class="spans_sellos_sinlogo" id="nombre_empresa">'.$nombre_empresa.'</span>
    <span class="spans_sellos_sinlogo" id="cif_empresa">CIF: '.$cif_empresa.'</span>
    <span class="spans_sellos_sinlogo" id="direccion_tienda">'.$direccion_empresa.'</span>
    <span class="spans_sellos_sinlogo" id="datos_varios">
        <span id="codigo_postal_tienda">'.$codigo_postal_empresa.' </span>
        <span id="poblacion_tienda"> '.$poblacion_empresa.' </span>
        <span id="provincia_tienda"> ('.$provincia_empresa.')</span>
    </span>
</div>';


$query_pca = 'SELECT SUM(gramos_item_proforma) AS TOTALGRAMOSPROFORMA, SUM(fino_item_proforma) AS TOTALFINOPROFORMA, SUM(importe_item_proforma) AS TOTALIMPORTEPROFORMA FROM items_proforma WHERE rel_proforma_id = ?';
$stmtTot = mysqli_prepare($conexion, $query_pca);
$rsItem_pcs = null;
if ($stmtTot) {
    mysqli_stmt_bind_param($stmtTot, 'i', $id_proforma);
    mysqli_stmt_execute($stmtTot);
    $resTot = mysqli_stmt_get_result($stmtTot);
    if ($resTot) {
        $rsItem_pcs = mysqli_fetch_assoc($resTot);
        mysqli_free_result($resTot);
    }
    mysqli_stmt_close($stmtTot);
}
$TOTALGRAMOSPROFORMA = is_array($rsItem_pcs) && isset($rsItem_pcs['TOTALGRAMOSPROFORMA']) ? $rsItem_pcs['TOTALGRAMOSPROFORMA'] : '';
$TOTALFINOPROFORMA = is_array($rsItem_pcs) && isset($rsItem_pcs['TOTALFINOPROFORMA']) ? $rsItem_pcs['TOTALFINOPROFORMA'] : '';
$TOTALIMPORTEPROFORMA = is_array($rsItem_pcs) && isset($rsItem_pcs['TOTALIMPORTEPROFORMA']) ? $rsItem_pcs['TOTALIMPORTEPROFORMA'] : '';

$logotipoPdf = isset($logotipoPdf) ? trim((string) $logotipoPdf) : '';
$dirPhotos = __DIR__ . '/../../photos';
$pathLogoFisico = $logotipoPdf === '' ? '' : $dirPhotos . '/' . ltrim(str_replace(['..', "\0"], '', $logotipoPdf), '/');
$tieneLogoFisico = $pathLogoFisico !== '' && is_file($pathLogoFisico);
$logoPlaceholderBase64 = 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEAYABgAAD/2wBDAAMCAgMCAgMDAwMEAwMEBQgFBQQEBQoHBwYIDAkKCwsNDhcODg0SGhEUFBUWGhwgJC0pLTU0NTY3O0BCQkNEZGVmZ2hpanN0dXZ3eHp7fP/bAEMBDA0NDQ0QEA4QGRIVGhQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUGBgYGBgYGBgY/8AAEQgAIAEsAwEiAAIRAQMRAf/EAB0AAAEFAQEAAAAAAAAAAAAAAgUAAQMGCAcECf/xABGEAACAQMCAwQFCwgBAwIABwAAAQIDBBEFIQYSMUETIlFhBxQycYGR8BUjMzRCcpKhscHR4RUWFySyJCU1U3OCw/EkJWRzoqPC/8QAGQEAAgMBAAAAAAAAAAAAAAAAAQIAAwQF/8QALBEAAgIBAwMEAgIDAQEAAAAAAAECESESMQRBEyJRYXEDMoEUFCNCkbHB/9oADAMBAAIRAxEAPwB9lZzRkVlZzQH//2Q==';

if (!$tieneLogoFisico) {
    $dirTmp = __DIR__ . '/../../temp_proformas';
    if (!is_dir($dirTmp)) {
        @mkdir($dirTmp, 0755, true);
    }
    $rutaJpg = $dirTmp . '/proforma_emp_' . (int) $id_empresa . '_' . (int) $id_proforma . '_ph.jpg';
    proforma_generar_jpg_empresa($rutaJpg, $nombre_empresa, 600, 120);
    if (!is_file($rutaJpg)) {
        $rutaJpg = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'proforma_emp_' . (int) $id_proforma . '_' . getmypid() . '.jpg';
        proforma_generar_jpg_empresa($rutaJpg, $nombre_empresa, 600, 120);
    }
    if (is_file($rutaJpg) && ($rutaAbs = realpath($rutaJpg))) {
        if ($origen_print == 'enviar_proforma') {
            $logo_final = str_replace('\\', '/', $rutaAbs);
        } else {
            $dirProyecto = realpath(__DIR__ . '/../..');
            $logo_final = ($dirProyecto && strpos($rutaAbs, $dirProyecto) === 0)
                ? '../../temp_proformas/' . basename($rutaAbs)
                : str_replace('\\', '/', $rutaAbs);
        }
    } else {
        $logo_final = $logoPlaceholderBase64;
    }
} elseif ($origen_print == 'enviar_proforma') {
    // mPDF en envío por correo: ruta absoluta (el CWD del AJAX no es Impresiones/Proformas)
    $logo_final = str_replace('\\', '/', realpath($pathLogoFisico) ?: $pathLogoFisico);
} else {
    $logo_final = '../../photos/' . $logotipoPdf;
}

// mPDF vía Composer (mismo patrón que pdfs/contrato_compra.php)
$mpdfone = new \Mpdf\Mpdf([
    'mode' => 'utf-8',
    'format' => 'A4',
    'default_font_size' => 0,
    'default_font' => '',
    'margin_left' => 5,
    'margin_right' => 5,
    'margin_top' => 30,
    'margin_bottom' => 21,
    'margin_header' => 5,
    'margin_footer' => 5,
]);

$mpdfone->SetProtection(['print']);
$mpdfone->SetTitle('Proforma ' . $nombre_empresa);
$mpdfone->SetAuthor($nombre_empresa);
$mpdfone->SetWatermarkText('Enviada');
$mpdfone->showWatermarkText = false;
$mpdfone->watermark_font = 'DejaVuSansCondensed';
$mpdfone->watermarkTextAlpha = 0.1;
$mpdfone->SetDisplayMode('fullpage');
$mpdfone->SHYlang = 'es';

$mpdfone->SetHTMLHeader('
<table width="100%" style="margin-top:20px;">
    <tr>
        <td width="50%">
        	<img src="'.$logo_final.'" width="300" height="auto">
        </td>
        <td width="50%" style="text-align: right; vertical-align: bottom;">
           <p class="textoheader">'.$nombre_empresa.'</p>
		   <p class="textoheader">CIF: '.$cif_empresa.'</p>
		   <p class="textoheader">'. $direccion_empresa.' - '.$poblacion_empresa.'</p>
		   <p class="textoheader">'. $provincia_empresa.' - España - ('.$codigo_postal_empresa.') </p>
		   <p class="textoheader">'.$email_empresa.' - '.$telefono_empresa.'</p>
        </td>
    </tr>
</table>
');

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
				font-size: 50px;
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
				font-size: 12px;
				font-weight: normal;
				text-align: left;
				display: block;
				width: 100%;
				padding: 0px 5px;
				height: 10px;
				margin: 0px 0px 0px 0px;
			}
			.pitemnumbernav{
				font-style: normal;
				font-size: 12px;
				font-weight: normal;
				text-align: right;
				line-height: 20px;
				display: block;
				width: 250px;
				padding: 0px 0px 0px 0px;
				height: 23px;
				margin: 0px;
				float: left;
			}
			.pitemdescripcionnav{
				font-style: normal;
				font-size: 13px;
				font-weight: normal;
				text-align: center;
				line-height: 20px;
				display: block;
				width: 70px;
				padding: 0px 0px 0px 0px;
				height: 23px;
				margin: 0px 0px 0px 0px;
				float: left;
			}
			.pitemunidadesnav{
				font-style: normal;
				font-size: 13px;
				font-weight: normal;
				text-align: center;
				line-height: 20px;
				display: block;
				width: 80px;
				padding: 0px 0px 0px 0px;
				height: 23px;
				margin: 0px 0px 0px 5px;
				float: left;
			}
			.pitempreciounitarionav{
				font-style: normal;
				font-size: 13px;
				font-weight: normal;
				text-align: center;
				line-height: 20px;
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
				line-height: 20px;
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
				line-height: 20px;
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
                height: auto;
                border: none;
                border-radius: 50%;
                text-align: center;
                display: block;
                font-family: "Helvetica Neue",Helvetica,Arial,sans-serif;
                transform: rotate(45deg);
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
            .observacionesproforma {
                font-size: 10px;
            }
            .datos_cierres {
                font-size: 12px;
                font-weight: bold;
            }
            .itemsinvoice_text_middle_sheet{
                position: absolute;
				bottom: 250px;
				left: 26px;
				font-style: normal;
				font-size: 17px;
				font-weight: normal;
				text-align: left;
				display: block;
				width: 500px;
				padding: 0px 5px;
				height: auto;
				margin: 0px 0px 0px 0px;
			}
			
			.itemsinvoice_footer{
                position: absolute; 
				bottom: 100px;
				left: 25px;
				font-style: normal;
				font-size: 17px;
				font-weight: bold;
				text-align: left;
				display: block;
				width: 100%;
				padding: 0px 5px;
				height: auto;
				margin: 0px 0px 0px 0px;
			}
            .pitemdescripcionnav_footer{
				font-style: normal;
				font-size: 17px;
				font-weight: bold;
				text-align: center;
				line-height: 20px;
				display: block;
				width: 70px;
				padding: 0px 0px 0px 0px;
				height: 23px;
				margin: 0px 0px 0px 0px;
				float: left;
			}
            .pitemunidadesnav_footer{
				font-style: normal;
				font-size: 17px;
				font-weight: bold;
				text-align: center;
				line-height: 20px;
				display: block;
				width: 80px;
				padding: 0px 0px 0px 0px;
				height: 23px;
				margin: 0px 0px 0px 5px;
				float: left;
			}
            .pitemtotalnav_footer{
				font-style: normal;
				font-size: 17px;
				font-weight: bold;
				text-align: center;
				line-height: 20px;
				display: block;
				width: 126px;
				padding: 0px 0px 0px 0px;
				height: 23px;
				margin: 0px 0px 0px 14px;
				float: left;
			}
		</style>
</head>
<body>
';

$html .= '
<div style="width: 100%; height: 55px; padding-top: 5px;"><h1 class="titulodocumento">'.$titulodocumento.'</h1></div>
<div style="width: 100%; height: 165px;">

	<div style="width: 400px; height: 155px; float: left; margin-left: 10px;">
';

$html .= '
        <div class="destinatariodata">
        
        	<p class="nombrecliente">'.$nombre_proveedor.'</p>
		
			<p class="texto-c" style="text-transform: uppercase;">CIF / NIF / NIE: '.$cif_proveedor.'</p>
		
			<p class="texto-c">'.$direccion_proveedor.' - '.$poblacion_proveedor.'</p>
            
            <p class="texto-c">'.$provincia_proveedor.' - '. $pais_proveedor.' - ('.$codigo_postal_proveedor.')</p>
            
            <p class="texto-c">'.$email_proveedor.'</p>
            
            <p class="texto-c">'.$telefono_proveedor.'</p>
            
        </div>
';

$html .= '
    </div>
	
	<div style="width: 340px; height: 100px; float: right">
	
		<div style="width: 130px; height: 80px; float: left; padding: 5px 0px; ">
		
			<p class="texto-a" style="text-align: left;">Número: </p>
		
			<p class="texto-a" style="text-align: left;">Fecha de envío: </p>
            		
		</div>
	
		<div style="width: 200px; height: 80px; float: left; padding: 5px 0px; ">
			
			<p class="texto-b" style="text-align: left;">'.$id_proforma.'</p>
		
			<p class="texto-b" style="text-align: left;">'.$fecha_envio_final.'</p>
            			
		</div>
        
        <div style="width:100%; height:60px; float: left;">
        	<h1 class="totaltopleft">TOTAL:</h1><h1 class="totaltopright">'.parsearPrecioReturn($TOTALIMPORTEPROFORMA).' €</h1>
        </div>
		
	</div>
	
</div>
';

$html .= '
<div style="width: 100%; height: 30px; margin-bottom: 5px;">
	<h1 class="itemdescripcionnav">Concepto</h1>
    <h1 class="itemnumbernav">Gramos</h1>
    <h1 class="itemunidadesnav">Fino</h1>
    <h1 class="itempreciounitarionav">Ley</h1>
	<h1 class="itemivanav">Precio</h1>
    <h1 class="itemtotalnav" style="float: right;">Importe</h1>
</div>
';

$precio_rel = $rsItemFR['precio_gramo_proforma'];
$fecha_proforma = isset($rsItemFR['fecha_proforma']) ? $rsItemFR['fecha_proforma'] : '';
$fecha_cierre = '-------';
if (!is_null($fecha_proforma) && $fecha_proforma !== '' && $fecha_proforma !== '0000-00-00' && $fecha_proforma !== '0000-00-00 00:00:00') {
    $fecha_cierre = date('d-m-Y', strtotime($fecha_proforma));
}

$html .= '
<div style="width: 100%; height: 10px; margin: 10px 0px 10px 10px; ">
    <span style="width: 150px; font-size: 15px; margin-right: 20px; display:block; font-weight: bold; ">'.$precio_rel.' €</span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span style="width: 150px; font-size: 14px; margin-left: 20px; display:block; ">Cierre '.$fecha_cierre.'</span>
</div>
';

$sql2 = 'SELECT * FROM items_proforma WHERE rel_proforma_id = ?';
$stmt2e = mysqli_prepare($conexion, $sql2);
if ($stmt2e) {
    mysqli_stmt_bind_param($stmt2e, 'i', $id_proforma);
    mysqli_stmt_execute($stmt2e);
    $result2 = mysqli_stmt_get_result($stmt2e);
    if ($result2) {
        $rows_items_proforma = array();
        $ids_sucursales_pdf = array();
        while ($lartc = mysqli_fetch_assoc($result2)) {
            $rows_items_proforma[] = $lartc;
            $id_sucursal_item = isset($lartc['sucursal_item']) ? (int) $lartc['sucursal_item'] : 0;
            if ($id_sucursal_item > 0) {
                $ids_sucursales_pdf[] = $id_sucursal_item;
            }
            $csv_sucursales = isset($lartc['sucursales_item']) ? trim((string) $lartc['sucursales_item']) : '';
            if ($csv_sucursales !== '') {
                foreach (explode(',', $csv_sucursales) as $part_sucursal) {
                    $sid = (int) trim($part_sucursal);
                    if ($sid > 0) {
                        $ids_sucursales_pdf[] = $sid;
                    }
                }
            }
        }
        mysqli_free_result($result2);

        $nombres_sucursales_pdf = array();
        $ids_sucursales_pdf = array_values(array_unique(array_filter($ids_sucursales_pdf)));
        if (!empty($ids_sucursales_pdf)) {
            $placeholders_suc = implode(',', array_fill(0, count($ids_sucursales_pdf), '?'));
            $types_suc = str_repeat('i', count($ids_sucursales_pdf));
            $sql_suc = 'SELECT id_sucursal, nombre_sucursal, nombre_corto FROM sucursal WHERE id_sucursal IN (' . $placeholders_suc . ')';
            $stmt_suc = mysqli_prepare($conexion, $sql_suc);
            if ($stmt_suc) {
                mysqli_stmt_bind_param($stmt_suc, $types_suc, ...$ids_sucursales_pdf);
                mysqli_stmt_execute($stmt_suc);
                $res_suc = mysqli_stmt_get_result($stmt_suc);
                if ($res_suc) {
                    while ($row_suc = mysqli_fetch_assoc($res_suc)) {
                        $sid = (int) $row_suc['id_sucursal'];
                        $nombre_sucursal = isset($row_suc['nombre_sucursal']) ? trim((string) $row_suc['nombre_sucursal']) : '';
                        $nombre_corto = isset($row_suc['nombre_corto']) ? trim((string) $row_suc['nombre_corto']) : '';
                        if (!empty($nombre_corto)) {
                            $nombre_sucursal = $nombre_corto;
                        }
                        if ($sid > 0 && $nombre_sucursal !== '') {
                            $nombres_sucursales_pdf[$sid] = $nombre_sucursal;
                        }
                    }
                    mysqli_free_result($res_suc);
                }
                mysqli_stmt_close($stmt_suc);
            }
        }

        foreach ($rows_items_proforma as $lartc) {
            $comentario_item = isset($lartc['comentario_item']) ? trim((string) $lartc['comentario_item']) : '';

            $ids_sucursales_item = array();
            $csv_sucursales = isset($lartc['sucursales_item']) ? trim((string) $lartc['sucursales_item']) : '';
            if ($csv_sucursales !== '') {
                foreach (explode(',', $csv_sucursales) as $part_sucursal) {
                    $sid = (int) trim($part_sucursal);
                    if ($sid > 0) {
                        $ids_sucursales_item[] = $sid;
                    }
                }
            }
            if (empty($ids_sucursales_item)) {
                $id_sucursal_item = isset($lartc['sucursal_item']) ? (int) $lartc['sucursal_item'] : 0;
                if ($id_sucursal_item > 0) {
                    $ids_sucursales_item[] = $id_sucursal_item;
                }
            }

            $nombres_sucursales_item = array();
            foreach ($ids_sucursales_item as $sid) {
                if (isset($nombres_sucursales_pdf[$sid])) {
                    $nombres_sucursales_item[] = $nombres_sucursales_pdf[$sid];
                }
            }

            $texto_primera_col = $comentario_item;
            if (!empty($nombres_sucursales_item)) {
                $lineas_sucursales = array();
                foreach ($nombres_sucursales_item as $nombre_sucursal_item) {
                    $lineas_sucursales[] = '<span style="font-size: 9px; line-height: 11px;">' . $nombre_sucursal_item . '</span>';
                }
                if ($texto_primera_col !== '') {
                    $texto_primera_col .= '<br />';
                }
                $texto_primera_col .= implode('<br />', $lineas_sucursales);
            }

            $attr_pitem = !empty($nombres_sucursales_item) ? ' style="height: auto;"' : '';

            $gramos_item_proforma = $lartc['gramos_item_proforma'];
            $fino_item_proforma = $lartc['fino_item_proforma'];
            $ley_proforma = $lartc['ley_proforma'];
            $precio = $lartc['precio'];
            $importe_item_proforma = $lartc['importe_item_proforma'];

            $html .= '
              <div class="itemsinvoice">
                    <p class="pitemnumbernav"'.$attr_pitem.'>'.$texto_primera_col.'</p>
                    <p class="pitemdescripcionnav">'.$gramos_item_proforma.'</p>
                    <p class="pitemunidadesnav">'.$fino_item_proforma.'</p>
                    <p class="pitempreciounitarionav">'.$ley_proforma.'</p>
                    <p class="pitemivanav">'.$precio.' €</p>
                    <p class="pitemtotalnav" style="float: right;">'.$importe_item_proforma.' €</p>
                </div>
            ';
        }
        mysqli_free_result($result2);
    }
    mysqli_stmt_close($stmt2e);
}

$texto_proforma = obtenerValueConfig(8);
$html .='
<div class="itemsinvoice_text_middle_sheet">
	<p class="observacionesproforma">'.$observacion_proforma.'</p>
	<p class="observacionesproforma">'.$texto_proforma.'</p>
</div>
';

$html .='
<style>
			.itemsinvoice_footer_primero{
                position: absolute;
				bottom: 130px;
				left: 0px;
				font-style: normal;
				font-size: 9px;
				font-weight: 500;
				text-align: left;
				display: block;
				width: 100%;
				padding: 0px 5px;
				height: 15px;
				margin: 0px 0px 0px 0px;
			}
			.pitemnumbernav_primero{
				font-style: normal;
				font-size: 9px;
				font-weight: normal;
				text-align: right;
				line-height: 20px;
				display: block;
				width: 240px;
				padding: 0px 0px 0px 0px;
				height: 15px;
				margin: 0px;
				float: left;
			}
			.pitemdescripcionnav_primero{
				font-style: normal;
				font-size: 9px;
				font-weight: normal;
				text-align: center;
				line-height: 20px;
				display: block;
				width: 80px;
				padding: 0px 0px 0px 0px;
				height: 15px;
				margin: 0px 0px 0px 0px;
				float: left;
			}
			.pitemdescripcionnav_footer_primero{
				font-style: normal;
				font-size: 9px;
				font-weight: 500;
				text-align: center;
				line-height: normal;
				display: block;
				width: 80px;
				padding: 0px 0px 0px 0px;
				height: 15px;
				margin: 0px 0px 0px 0px;
				float: left;
			}
			.pitemunidadesnav_footer_primero{
				font-style: normal;
				font-size: 9pxx;
				font-weight: 500;
				text-align: center;
				line-height: normal;
				display: block;
				width: 70px;
				padding: 0px 0px 0px 0px;
				height: 15px;
				margin: 0px 0px 0px 5px;
				float: left;
			}	

			.pitemtotalnav_footer_primero{
				font-style: normal;
				font-size: 9px;
				font-weight: 500;
				text-align: center;
				line-height: normal;
				display: block;
				width: 126px;
				padding: 0px 0px 0px 0px;
				height: 15px;
				margin: 0px 0px 0px 14px;
				float: left;
			}
			.pitemivanav_primero{
				font-style: normal;
				font-size: 9px;
				font-weight: 500;
				text-align: center;
				line-height: normal;
				display: block;
				width: 105px;
				padding: 0px 0px 0px 0px;
				height: 15px;
				margin: 0px 0px 0px 14px;
				float: left;
			}
</style>
<div class="itemsinvoice_footer_primero">
    <p class="pitemnumbernav_primero"></p>
    <p class="pitemdescripcionnav_footer_primero">TOTAL GRAMOS</p>
    <p class="pitemunidadesnav_footer_primero">TOTAL FINO</p>
    <p class="pitemdescripcionnav_primero"></p>
    <p class="pitemivanav_primero"></p>
    <p class="pitemtotalnav_footer_primero">IMPORTE TOTAL</p>
</div>
';

$html .='
<div class="itemsinvoice_footer">
    <p class="pitemnumbernav"></p>
    <p class="pitemdescripcionnav_footer">'.$TOTALGRAMOSPROFORMA.'</p>
    <p class="pitemunidadesnav_footer">'.$TOTALFINOPROFORMA.'</p>
    <p class="pitempreciounitarionav"></p>
    <p class="pitemivanav"></p>
    <p class="pitemtotalnav_footer" style="">'.$TOTALIMPORTEPROFORMA.' €</p>
</div>
';

$html .='
    <div style="position: absolute; bottom: 30px; right: 25px; font-size: 7px; ">'.$texto_iva_proforma.'</div>
    <div style="width: 330px; position: absolute; bottom: 90px; left: 20px; ">
        <div id="sello" style="width: 180px;">
            <span class="spans_sellos_sinlogo" id="ordago_sinlogo"></span>
            <span class="spans_sellos_sinlogo" id="nombre_empresa">'.$nombre_empresa.'</span>
            <span class="spans_sellos_sinlogo" id="cif_empresa">CIF: '.$cif_empresa.'</span>
            <span class="spans_sellos_sinlogo" id="direccion_tienda">'.$direccion_empresa.'</span>
            <span class="spans_sellos_sinlogo" id="datos_varios">
                <span id="codigo_postal_tienda">'.$codigo_postal_empresa.' </span>
                <span id="poblacion_tienda"> '.$poblacion_empresa.' </span>
                <span id="provincia_tienda"> ('.$provincia_empresa.')</span>
            </span>
        </div>
    </div>        
</body>
</html>
';



$mpdfone->SetHTMLFooter('
<div class="footer"  style="padding-top: 20px; width: 100%;">
<!--<hr class="classhr">
<div style=" width: 100%;">

    <div style="width: 330px; float: right;">
        <div style="width: 330px; height: 65px; margin-top: 5px; float: right;  ">
            <h1 class="textototalbottomleft" style="background: #EDEDEE; color: '.$appColorBrand.';">Total neto:<br></h1> 
            <h1 class="textototalbottomright" style="background: #EDEDEE; color: '.$appColorBrand.';">'.parsearPrecioReturn($importe_proforma).' €</h1>
        </div>
        <div style="width: 330px; height: 65px; margin-top: 5px; float: right;  ">
            <h1 class="totalbottomleft" style="color: '.$appColorBrand.';" >TOTAL:</h1>
            <h1 class="totalbottomright" style="background: red !important;
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
				float: right;">'.parsearPrecioReturn($importe_proforma).' €</h1>
        </div>
    </div>
    
</div>-->

	<hr class="classhr">
    <div style="width: 100%; text-align: left; font-style: italic; margin:5px 0 0 0; color:#000000; font-size:7pt; ">Forma de pago: '.$nombre_forma_de_pago.'</div>
    <p style="text-align: left; font-style: normal; margin:10px 0 0 0; color:#000000; font-size:7pt; ">IBAN: '.$numerocuenta.'</p>
</div>

'
);

$mpdfone->WriteHTML($html);

$html2 = '
    <pagebreak>
    <div style="width: 650px; display: block; margin: 140px auto;">
    <br><br><br><br>
    <h1 style="text-align: center; font-size: 30px; ">Pagos a cuenta</h1>
    <br><br>
    <p style="text-justify: inter-word; text-align: left; font-size: 15px;  line-height: 20px;">En pleno dominio de mercancía <strong> '.$TOTALFINOPROFORMA.' </strong> GRAMOS DE FINO DE ORO correspondiente desde este a momento a <strong> '.$nombre_proveedor.' </strong>, entregándonos mediante transferencia a cuenta la cantidad de <strong> '.$TOTALIMPORTEPROFORMA.' € </strong> liquidándose el mismo según la cotización que las partes acuerden dentro de los 20 días naturales siguientes, o en otro caso la correspondiente a la del último día laborable de los referidos 20 días naturales.</p>
    <br><br>
    <h3>La cuenta bancaria a la que debe realizar la transferencia es: </h3>
    <br>
    <h3 style="text-align: center; font-size: 20px; font-weight: bold;">'.$numerocuenta.'</h3>
    <br><br>
    <h3>Fecha '.$fecha_envio_final.' </h3>
    <br><br><br>
    <h3>Firmado P.P. </h3>
    <br>
    <h3>D.______________________</h3>
    <br>
    </div>
    <div style="width: 330px; position: absolute; bottom: 380px; right: 28px; ">
        <div id="sello" style="width: 180px;">
            <span class="spans_sellos_sinlogo" id="ordago_sinlogo"></span>
            <span class="spans_sellos_sinlogo" id="nombre_empresa">'.$nombre_empresa.'</span>
            <span class="spans_sellos_sinlogo" id="cif_empresa">CIF: '.$cif_empresa.'</span>
            <span class="spans_sellos_sinlogo" id="direccion_tienda">'.$direccion_empresa.'</span>
            <span class="spans_sellos_sinlogo" id="datos_varios">
                <span id="codigo_postal_tienda">'.$codigo_postal_empresa.' </span>
                <span id="poblacion_tienda"> '.$poblacion_empresa.' </span>
                <span id="provincia_tienda"> ('.$provincia_empresa.')</span>
            </span>
        </div>
    </div>     
    ';
$mpdfone->WriteHTML($html2);

if ($origen_print == 'enviar_proforma') {
    $mpdfone->Output(__DIR__ . '/../../temp_proformas/' . $name_file, \Mpdf\Output\Destination::FILE);
} else {
    $mpdfone->OutputHttpDownload($name_file);
}

//$mpdfone->Output(''.$titulodocumento.'-'.$currentyear.'-'.$id_proforma.'.pdf','D');
//$mpdfone->Output('../../temp_proformas/'.$titulodocumento.'-'.$currentyear.'-'.$id_proforma.'.pdf','F');
//$mpdfone->Output('../pdfs/docs/'.$titulodocumento.'-'.$currentyear.'-'.$id_document.'-'.$number_aleatorio.'.pdf','F');
//$mpdfone->Output();
?>