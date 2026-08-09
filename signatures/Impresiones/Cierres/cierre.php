<?php
if (!isset($origen_print) || $origen_print != 'enviar_cierre') {
    require_once '../../include/session.php';
    require_once '../../include/functions.php';

    $id_cierre = isset($_GET['id_cierre']) ? (int) $_GET['id_cierre'] : 0;
    $id_sucursal = isset($suc) ? $suc : '';
    $origen_print = '';
}


$conexion = conectar_bd();
require_once __DIR__ . '/../../vendor/autoload.php';
$id_cierre = (int) $id_cierre;

$queryFR = "SELECT
CF.id_cierre,
CF.cierre_numero,
CF.fecha_envio,
CF.fecha_cierre,
CF.fecha_creacion,
CF.fecha_standby,
CF.usuario_genera_cierre,
CF.tipo_metal_cierre,
CF.precio_gramo_cierre,
CF.estado_cierre,
CF.empresa_cierre_todas,
CF.empresa_cierre,
CF.total_gramos_cierre,
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
PRO.email_proveedor
FROM cierres_fundicion AS CF
LEFT JOIN empresas AS EMPR ON EMPR.id_empresa = CF.empresa_cierre
LEFT JOIN proveedores AS PRO ON PRO.id_proveedor = CF.proveedor_cierre
WHERE CF.id_cierre = ?
";

$stmtFR = mysqli_prepare($conexion, $queryFR);
$rsItemFR = null;
if ($stmtFR) {
    mysqli_stmt_bind_param($stmtFR, 'i', $id_cierre);
    mysqli_stmt_execute($stmtFR);
    $resFR = mysqli_stmt_get_result($stmtFR);
    if ($resFR) {
        $rsItemFR = mysqli_fetch_assoc($resFR);
        mysqli_free_result($resFR);
    }
    mysqli_stmt_close($stmtFR);
}

if (!is_array($rsItemFR)) {
    exit('No se encontró el cierre.');
}

$cierre_numero = isset($rsItemFR['cierre_numero']) ? (int) $rsItemFR['cierre_numero'] : 0;
$numero_documento = $cierre_numero > 0 ? $cierre_numero : $id_cierre;

$fecha_envio = $rsItemFR['fecha_envio'];
if (is_null($fecha_envio) || $fecha_envio === '' || $fecha_envio === '0000-00-00' || $fecha_envio === '0000-00-00 00:00:00') {
    $fecha_envio = $rsItemFR['fecha_cierre'];
}
if (is_null($fecha_envio) || $fecha_envio === '' || $fecha_envio === '0000-00-00' || $fecha_envio === '0000-00-00 00:00:00') {
    $fecha_envio = $rsItemFR['fecha_creacion'];
}
$fecha_envio_parset = $fecha_envio ? strtotime($fecha_envio) : false;
$anyo_envio = $fecha_envio_parset ? date("Y", $fecha_envio_parset) : date('Y');
$fecha_envio_final = $fecha_envio_parset ? date("d-m-Y", $fecha_envio_parset) : '-------';

$currentyear = $anyo_envio;
$id_empresa = isset($rsItemFR['id_empresa']) ? (int) $rsItemFR['id_empresa'] : 0;
$nombre_empresa = (string) ($rsItemFR['nombre_empresa'] ?? '');
$cif_empresa = (string) ($rsItemFR['cif_empresa'] ?? '');
$direccion_empresa = (string) ($rsItemFR['direccion_empresa'] ?? '');
$poblacion_empresa = (string) ($rsItemFR['poblacion_empresa'] ?? '');
$provincia_empresa = (string) ($rsItemFR['provincia_empresa'] ?? '');
$pais_empresa = (string) ($rsItemFR['pais_empresa'] ?? '');
$telefono_empresa = (string) ($rsItemFR['telefono_empresa'] ?? '');
$codigo_postal_empresa = (string) ($rsItemFR['codigo_postal_empresa'] ?? '');
$email_empresa = (string) ($rsItemFR['email_empresa'] ?? '');
$logotipoPdf = $rsItemFR['logotipo_empresa'] ?? '';

$datos_footer_empresa = $nombre_empresa."  - CIF: ".$cif_empresa." - ".$direccion_empresa." -  ".$poblacion_empresa." - España";

$nombre_proveedor = (string) ($rsItemFR['nombre_proveedor'] ?? '');
$cif_proveedor = (string) ($rsItemFR['cif_proveedor'] ?? '');
$direccion_proveedor = (string) ($rsItemFR['direccion_proveedor'] ?? '');
$poblacion_proveedor = (string) ($rsItemFR['poblacion_proveedor'] ?? '');
$provincia_proveedor = (string) ($rsItemFR['provincia_proveedor'] ?? '');
$pais_proveedor = (string) ($rsItemFR['pais_proveedor'] ?? '');
$codigo_postal_proveedor = (string) ($rsItemFR['codigo_postal_proveedor'] ?? '');
$email_proveedor = (string) ($rsItemFR['email_proveedor'] ?? '');
$telefono_proveedor = (string) ($rsItemFR['telefono_proveedor'] ?? '');

$nombre_forma_de_pago = '';
$observacion_proforma = '';
$tipo_metal_cierre = trim((string) ($rsItemFR['tipo_metal_cierre'] ?? 'Oro'));
$texto_metal_pagos = (stripos($tipo_metal_cierre, 'plata') !== false) ? 'PLATA' : 'ORO';

// VAR ORIGINALES DEL DOCUMENTO
$titulodocumento = "Cierre fundición";
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
$name_file_parset = $titulodocumento.'-'.$nombre_empresa.'-'.$currentyear.'-'.$id_cierre.'-'.$code_generate.'.pdf';



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
    return number_format((float) $priceNumber, 2, ',', '.');
}

$query_pca = 'SELECT
    SUM(COALESCE(gramos_item_cierre, gramos_item, 0)) AS TOTALGRAMOSPROFORMA,
    SUM(COALESCE(fino_item_cierre, fino_item, 0)) AS TOTALFINOPROFORMA,
    SUM(COALESCE(importe_item_cierre, importe, 0)) AS TOTALIMPORTEPROFORMA
FROM items_cierre_fundicion
WHERE rel_cierre_id = ?';
$stmtTot = mysqli_prepare($conexion, $query_pca);
$rsItem_pcs = null;
if ($stmtTot) {
    mysqli_stmt_bind_param($stmtTot, 'i', $id_cierre);
    mysqli_stmt_execute($stmtTot);
    $resTot = mysqli_stmt_get_result($stmtTot);
    if ($resTot) {
        $rsItem_pcs = mysqli_fetch_assoc($resTot);
        mysqli_free_result($resTot);
    }
    mysqli_stmt_close($stmtTot);
}
$TOTALGRAMOSPROFORMA = is_array($rsItem_pcs) && isset($rsItem_pcs['TOTALGRAMOSPROFORMA'])
    ? (float) $rsItem_pcs['TOTALGRAMOSPROFORMA']
    : 0.0;
$TOTALFINOPROFORMA = is_array($rsItem_pcs) && isset($rsItem_pcs['TOTALFINOPROFORMA'])
    ? (float) $rsItem_pcs['TOTALFINOPROFORMA']
    : 0.0;
$TOTALIMPORTEPROFORMA = is_array($rsItem_pcs) && isset($rsItem_pcs['TOTALIMPORTEPROFORMA'])
    ? (float) $rsItem_pcs['TOTALIMPORTEPROFORMA']
    : 0.0;
$TOTALGRAMOSPROFORMA_TXT = number_format($TOTALGRAMOSPROFORMA, 2, ',', '.');
$TOTALFINOPROFORMA_TXT = number_format($TOTALFINOPROFORMA, 3, ',', '.');
$TOTALIMPORTEPROFORMA_TXT = parsearPrecioReturn($TOTALIMPORTEPROFORMA);

// mPDF vía Composer (mismo patrón que pdfs/contrato_compra.php)
$mpdfone = new \Mpdf\Mpdf([
    'mode' => 'utf-8',
    'format' => 'A4',
    'default_font_size' => 0,
    'default_font' => '',
    'margin_left' => 5,
    'margin_right' => 5,
    'margin_top' => 12,
    'margin_bottom' => 28,
    'margin_header' => 5,
    'margin_footer' => 5,
]);

$mpdfone->SetProtection(['print']);
$mpdfone->SetTitle('Cierre fundición');
$mpdfone->SetAuthor($nombre_empresa);
$mpdfone->SetWatermarkText('Enviada');
$mpdfone->showWatermarkText = false;
$mpdfone->watermark_font = 'DejaVuSansCondensed';
$mpdfone->watermarkTextAlpha = 0.1;
$mpdfone->SetDisplayMode('fullpage');
$mpdfone->SHYlang = 'es';

$mpdfone->SetHTMLHeader('');

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
				width: 520px;
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
                display: none;
			}
            .pitemdescripcionnav_footer{
				display: none;
			}
            .pitemunidadesnav_footer{
				display: none;
			}
            .pitemtotalnav_footer{
				display: none;
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
		
			<p class="texto-a" style="text-align: left;">Fecha cierre: </p>
            		
		</div>
	
		<div style="width: 200px; height: 80px; float: left; padding: 5px 0px; ">
			
			<p class="texto-b" style="text-align: left;">'.$numero_documento.'</p>
		
			<p class="texto-b" style="text-align: left;">'.$fecha_envio_final.'</p>
            			
		</div>
        
        <div style="width:100%; height:60px; float: left;">
        	<h1 class="totaltopleft">TOTAL:</h1><h1 class="totaltopright">'.$TOTALIMPORTEPROFORMA_TXT.' €</h1>
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

$precio_rel = isset($rsItemFR['precio_gramo_cierre']) ? $rsItemFR['precio_gramo_cierre'] : '';
$fecha_cierre_src = isset($rsItemFR['fecha_cierre']) ? $rsItemFR['fecha_cierre'] : '';
if (is_null($fecha_cierre_src) || $fecha_cierre_src === '' || $fecha_cierre_src === '0000-00-00' || $fecha_cierre_src === '0000-00-00 00:00:00') {
    $fecha_cierre_src = isset($rsItemFR['fecha_standby']) ? $rsItemFR['fecha_standby'] : '';
}
$fecha_cierre = '-------';
if (!is_null($fecha_cierre_src) && $fecha_cierre_src !== '' && $fecha_cierre_src !== '0000-00-00' && $fecha_cierre_src !== '0000-00-00 00:00:00') {
    $fecha_cierre = date('d-m-Y', strtotime($fecha_cierre_src));
}

$html .= '
<div style="width: 100%; height: 10px; margin: 10px 0px 10px 10px; ">
    <span style="width: 150px; font-size: 15px; margin-right: 20px; display:block; font-weight: bold; ">'.$precio_rel.' €</span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span style="width: 150px; font-size: 14px; margin-left: 20px; display:block; ">Cierre '.$fecha_cierre.'</span>
</div>
';

$sql2 = 'SELECT * FROM items_cierre_fundicion WHERE rel_cierre_id = ? ORDER BY id_item_cierre ASC';
$stmt2e = mysqli_prepare($conexion, $sql2);
if ($stmt2e) {
    mysqli_stmt_bind_param($stmt2e, 'i', $id_cierre);
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

            $gramos_item_proforma = isset($lartc['gramos_item_cierre']) && $lartc['gramos_item_cierre'] !== '' && $lartc['gramos_item_cierre'] !== null
                ? $lartc['gramos_item_cierre']
                : (isset($lartc['gramos_item']) ? $lartc['gramos_item'] : 0);
            $fino_item_proforma = isset($lartc['fino_item_cierre']) && $lartc['fino_item_cierre'] !== '' && $lartc['fino_item_cierre'] !== null
                ? $lartc['fino_item_cierre']
                : (isset($lartc['fino_item']) ? $lartc['fino_item'] : 0);
            if (isset($lartc['ley_cierre']) && $lartc['ley_cierre'] !== '' && $lartc['ley_cierre'] !== null) {
                $ley_proforma = $lartc['ley_cierre'];
            } elseif (isset($lartc['ley_final']) && $lartc['ley_final'] !== '' && $lartc['ley_final'] !== null) {
                $ley_proforma = $lartc['ley_final'];
            } else {
                $ley_proforma = isset($lartc['ley_item']) ? $lartc['ley_item'] : '';
            }
            $precio = isset($lartc['precio']) && $lartc['precio'] !== '' && $lartc['precio'] !== null
                ? $lartc['precio']
                : (isset($lartc['precio_gramo_item']) ? $lartc['precio_gramo_item'] : 0);
            $importe_item_proforma = isset($lartc['importe_item_cierre']) && $lartc['importe_item_cierre'] !== '' && $lartc['importe_item_cierre'] !== null
                ? $lartc['importe_item_cierre']
                : (isset($lartc['importe']) ? $lartc['importe'] : 0);

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







$html .='
</body>
</html>
';



$mpdfone->SetHTMLFooter('
<table width="100%" cellspacing="0" cellpadding="0" style="border-collapse: collapse; border-top: 1px solid #EDEDEE;">
    <tr>
        <td width="34%" style="text-align: center; vertical-align: bottom; padding: 4px 2px 0 2px;">
            <div style="font-size: 7pt; font-weight: normal; color: #777777; line-height: 10px; text-transform: uppercase;">TOTAL GRAMOS</div>
            <div style="font-size: 14pt; font-weight: bold; color: #555555; line-height: 20px; padding-top: 2px;">' . $TOTALGRAMOSPROFORMA_TXT . '</div>
        </td>
        <td width="33%" style="text-align: center; vertical-align: bottom; padding: 4px 2px 0 2px;">
            <div style="font-size: 7pt; font-weight: normal; color: #777777; line-height: 10px; text-transform: uppercase;">TOTAL FINO</div>
            <div style="font-size: 14pt; font-weight: bold; color: #555555; line-height: 20px; padding-top: 2px;">' . $TOTALFINOPROFORMA_TXT . '</div>
        </td>
        <td width="33%" style="text-align: center; vertical-align: bottom; padding: 4px 2px 0 2px;">
            <div style="font-size: 7pt; font-weight: normal; color: #777777; line-height: 10px; text-transform: uppercase;">IMPORTE TOTAL</div>
            <div style="font-size: 14pt; font-weight: bold; color: #555555; line-height: 20px; padding-top: 2px;">' . $TOTALIMPORTEPROFORMA_TXT . ' €</div>
        </td>
    </tr>
</table>
');

$mpdfone->WriteHTML($html);

if (isset($origen_print) && $origen_print == 'enviar_cierre') {
    $dirOut = __DIR__ . '/../../temp_cierres';
    if (!is_dir($dirOut)) {
        @mkdir($dirOut, 0755, true);
    }
    $mpdfone->Output($dirOut . '/' . $name_file, \Mpdf\Output\Destination::FILE);
} else {
    $mpdfone->OutputHttpDownload($name_file);
}

//$mpdfone->Output(''.$titulodocumento.'-'.$currentyear.'-'.$id_proforma.'.pdf','D');
//$mpdfone->Output('../../temp_cierres/'.$titulodocumento.'-'.$currentyear.'-'.$id_proforma.'.pdf','F');
//$mpdfone->Output('../pdfs/docs/'.$titulodocumento.'-'.$currentyear.'-'.$id_document.'-'.$number_aleatorio.'.pdf','F');
//$mpdfone->Output();
?>