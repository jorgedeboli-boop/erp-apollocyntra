<?php
    
$identificador_deposito = $iditem;
$titulodocumento = $typeitem;
$appColorBrand = "#4a8bc2";

$query = "SELECT * FROM depositos LEFT JOIN clientes ON depositos.cliente_deposito = clientes.id_cliente WHERE depositos.sucursal_deposito LIKE '".$sucursal."' AND identificador = '".$identificador_deposito."'";

mysql_query ("SET NAMES 'utf8'");
$Item = mysql_query($query, $conexion);
$rsItem = mysql_fetch_assoc($Item);

$id_document=$rsItem['id_deposito'];
$numerocontrato=$rsItem['id_deposito'];

$nombreapellidocliente=$rsItem['nombre']." ".$rsItem['apellido'];
$direccioncliente=$rsItem['direccion'];
$codigospotalcliente=$rsItem['codigo_postal'];
$ciudadcliente=$rsItem['poblacion'];
$provinciacliente=$rsItem['provincia'];
$telefonocliente=$rsItem['telefono'];
$dnicliente=$rsItem['identificacion'];
$contratopol = $rsItem['idpol'];



$date= $rsItem['fecha_deposito'];
$fechacontrato=date('d-m-Y',strtotime($date));


$date= $rsItem['fecha_vencimiento_deposito'];
$datevencimiento=date('d-m-Y',strtotime($date));

$preciocompra = $rsItem['precio_deposito'];



//Consulto sucursal
$querys = "SELECT * FROM sucursales WHERE id_sucursal= $sucursal ";
mysql_query ("SET NAMES 'utf8'");
$Ietem = mysql_query($querys, $conexion);
$rseItem = mysql_fetch_assoc($Ietem);

$nombreempresa=$rseItem['empresa'];
$nameCompany=$rseItem['empresa'];
$addressAddress=$rseItem['direccion_tienda'];
$postalcodeAddress=$rseItem['codigo_postal_tienda'];
$populationAddress=$rseItem['poblacion_tienda'];
$provinceAddress=$rseItem['provincia_tienda'];
$countryAddress = "España";
$appPhone=$rseItem['telefono_tienda'];
$appEmail=$rseItem['email_tienda'];
$nifCompany=$rseItem['numero_identificacion_tienda'];
$appWeb=$rseItem['webempresa'];
$logotipoPdf = "photos/1001/logotipos/".$rseItem['logotipo_sucursal'];
$tiponifCompany = "CIF";





//LLAMO AL PORCENTAJE BONOTIENDA
$queryporc = "SELECT * FROM porcentaje_deposito_vendedor WHERE sucursal_porcentaje_vendedor = '".$sucursal."' ";
$Itemporc = mysql_query($queryporc, $conexion);
mysql_query ("SET NAMES 'utf8'");
$rsItemporc = mysql_fetch_assoc($Itemporc);
$pdep=$rsItemporc['porcentaje_deposito_vendedor'];
$meses=$rsItemporc['meses'];

//LLAMO AL PORCENTAJE BONOTIENDA
$query = "SELECT valor_dias_vencimiento_depositos FROM dias_vencimiento_depositos WHERE sucursal_dias_vencimiento_depositos = $sucursal ";
$Item = mysql_query($query, $conexion);
mysql_query ("SET NAMES 'utf8'");
$rsItem = mysql_fetch_assoc($Item);
$valor_dias_vencimiento_depositos=$rsItem['valor_dias_vencimiento_depositos'];

// RECOJO LA FIRMA DEL CLIENTE
$statesignature = 'true';
if ($statesignature=='true'){    
    $signature_value = signaturesManager("view", "", "", $identificador_deposito, "deposito");
    if(!empty($signature_value)){
        $textSignature = '';
        $signatureInsert = generateSignatureContrato( $signature_value, $textSignature );
    } 
}

// RECOJO LA FIRMA DEL EMPLEADO
if ($statesignature=='true'){    
    $signature_value_user = signaturesManager("view", "", "", $id_usuario, "user");
    if(!empty($signature_value_user)){
        $textSignature_user = '';
        $signatureInsert_user = generateSignatureContrato( $signature_value_user, $textSignature_user );
    } 
}


include("../MPDF54/mpdf.php");
$mpdfone=new mPDF('win-1252','A4','','',5,5,30,21,5,5); 
$mpdfone->useOnlyCoreFonts = true;    // false is default
$mpdfone->SetProtection(array('print'));
$mpdfone->SetTitle("Deposito ".$nameCompany."");
$mpdfone->SetAuthor("".$nameCompany."");
$mpdfone->SetWatermarkText("Enviada");
$mpdfone->showWatermarkText = false;
$mpdfone->watermark_font = 'DejaVuSansCondensed';
$mpdfone->watermarkTextAlpha = 0.1;
$mpdfone->SetDisplayMode('fullpage');
$mpdfone->hyphenate = true;
$mpdfone->SHYlang = 'es';
$mpdfone->SetHTMLHeader('
<table width="100%" style="margin-top:20px;">
    <tr>
        <td width="50%">
        	<img src="'.$logotipoPdf.'" width="300" height="60">
        </td>
        <td width="50%" style="text-align: right; vertical-align: bottom;">
           <p class="textoheader">'.$nameCompany.'</p>
		   <p class="textoheader">'.$tiponifCompany.': '.$nifCompany.'</p>
		   <p class="textoheader">'. $addressAddress.' - '.$populationAddress.'</p>
		   <p class="textoheader">'. $provinceAddress.' - '. $countryAddress.' - ('.$postalcodeAddress.') </p>
		   <p class="textoheader">'.$appEmail.' - '.$appPhone.'</p>
        </td>
    </tr>
</table>
');
$mpdfone->SetHTMLFooter('
<div class="footer"  style="padding-top: 20px;">
	<hr class="classhr">
	<p class="textofooter">'.$appWeb.' - '.$titulodocumento.' Nº '.$id_document.'</p>
 	<h3 class="paginacionfooter">Página Nº {PAGENO}</h3>
</div>
'
);

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
				font-size:13px;
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
				font-size: 19px;
				font-weight: bold;
				line-height: 29px;
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
				font-size: 13px;
				font-weight: normal;
				text-align: left;
				line-height: 30px;
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
		</style>
</head>
<body>
<div style="width: 100%; height: 55px; padding-top: 5px;"><h1 class="titulodocumento">'.$titulodocumento.'</h1></div>
<div style="width: 100%; height: 165px;">

	<div style="width: 400px; height: 155px; float: left">
    	
        <div class="destinatariodata">
        
        	<p class="nombrecliente">'.$nombrecomercialCustomer.'</p>
		
			<p class="texto-c">'.$tiponifCustomer.': '.$nifCustomer.'</p>
		
			<p class="texto-c">'.$addressFullInvoice.' - '.$populationCustomerInvoice.'</p>
            
            <p class="texto-c">'.$provinceCustomerInvoice.' - '. $countryCustomerInvoice.' - ('.$postalcodeCustomerInvoice.')</p>
            
            <p class="texto-c">'.$emailCustomer.'</p>
            
            <p class="texto-c">'.$telefonoCustomer.' '.$movilCustomer.'</p>
            
        </div>
    </div>
	
	<div style="width: 340px; height: 155px; float: right">
	
		<div style="width: 130px; height: 80px; float: left; padding: 5px 0px; ">
		
			<p class="texto-a">Número '.$titulodocumento.': </p>
		
			<p class="texto-a">Fecha emisión: </p>
		
			<p class="texto-a">Número cliente: </p>
		
		</div>
	
		<div style="width: 200px; height: 80px; float: left; padding: 5px 0px; ">
			
			<p class="texto-b"> '.$id_document.'</p>
		
			<p class="texto-b"> '.$hoy.'</p>
		
			<p class="texto-b"> '.$numberItemCustomer.'</p>
			
		</div>
        
        <div style="width:100%; height:65px; float: left;">
        	<h1 class="totaltopleft">TOTAL:</h1><h1 class="totaltopright">'.parsearPrecioReturn($FinalTotalOrderIvaIncl).'</h1>
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

$query="
SELECT 
idRelProInv, 
relSku, 
relTitleProduct, 
relDescriptionProduct,
relUnitys,
relTypeUnityMeasure,
relPvp,
relIVA
FROM relProductsInvoices 
WHERE companyIdRel =  $companyIdUser AND idRelInvoice = $lastIdInvoice
ORDER BY relSku DESC 
";
if ($result = mysqli_query($mysqli, $query)) {
		$cont=0; 
		while ($row = mysqli_fetch_array($result)) {
			$cont++; 
			$relSku = $row["relSku"]; 
			$titleModule = $row["relTitleProduct"]; 
			$relPvp = $row["relPvp"]; 
			$relUnitys = $row["relUnitys"]; 
			$relIVA = $row["relIVA"];
			valorImpuesto($relIVA);
			$finalPrice = $relPvp*$relUnitys;
			$html .='
			  <div class="itemsinvoice">
					<p class="pitemnumbernav">'.$relSku.'</p>
					<p class="pitemdescripcionnav">'.$titleModule.'</p>
					<p class="pitemunidadesnav">'.$relUnitys.'</p>
					<p class="pitempreciounitarionav">'.parsearPrecioReturn($relPvp).'</p>
					<p class="pitemivanav">'.$nombre_impuesto.'</p>
					<p class="pitemtotalnav" style="float: right;">'.parsearPrecioReturn($finalPrice).'</p>
				</div>
			';
		
		}
mysqli_free_result($result); 
}
$html .='
<hr class="classhr">
<div style="width: 330px;  float: right;">
	<div style="width: 330px; height: 65px; margin-top: 5px; float: right;  ">
		<h1 class="textototalbottomleft">Total neto:<br>';
		$query="
		SELECT 
		relProductsInvoices.relIVA,
		SUM(relProductsInvoices.totalRelIva) AS totalIvaSum,
		impuestos.id_impuesto,
		impuestos.nombre_impuesto,
		impuestos.valor_impuesto
		FROM relProductsInvoices 
		LEFT JOIN impuestos ON impuestos.id_impuesto = relProductsInvoices.relIVA
		WHERE relProductsInvoices.companyIdRel =  $companyIdUser AND relProductsInvoices.idRelInvoice = $lastIdInvoice
		GROUP BY impuestos.id_impuesto
		";
		if ($result = mysqli_query($mysqli, $query)) {
				while ($rowa = mysqli_fetch_array($result)) {
					$nombre_impuesto = $rowa["nombre_impuesto"]; 
					$totalIvaSum = $rowa["totalIvaSum"]; 
					$html .='Base imponible '.$nombre_impuesto.': ';
					$html .='<br>';
					$html .='Total '.$nombre_impuesto.': ';
					$html .='<br>';
				}
		mysqli_free_result($result); 
		} 
		mysqli_close($link);
		$html .='</h1>';
		$html .='<h1 class="textototalbottomright">'.parsearPrecioReturn($counTotal).'<br>';
		// TOTAL IMPUESTOS
		$query="
		SELECT 
		relProductsInvoices.relIVA,
		SUM(relProductsInvoices.totalRelIva) AS totalIvaSum,
		SUM(relProductsInvoices.totalRelPvp) AS totalRelPvpSum,
		impuestos.id_impuesto,
		impuestos.nombre_impuesto,
		impuestos.valor_impuesto
		FROM relProductsInvoices 
		LEFT JOIN impuestos ON impuestos.id_impuesto = relProductsInvoices.relIVA
		WHERE relProductsInvoices.companyIdRel =  $companyIdUser AND relProductsInvoices.idRelInvoice = $lastIdInvoice
		GROUP BY impuestos.id_impuesto
		";
		if ($result = mysqli_query($mysqli, $query)) {
				while ($rowa = mysqli_fetch_array($result)) {
					$nombre_impuesto = $rowa["nombre_impuesto"]; 
					$totalIvaSum = $rowa["totalIvaSum"]; 
					$totalRelPvpSum = $rowa["totalRelPvpSum"]; 
					$html .=''.parsearPrecioReturn($totalRelPvpSum).'<br>';
					$html .=''.parsearPrecioReturn($totalIvaSum).'<br>';
				}
		mysqli_free_result($result); 
		} 
		mysqli_close($link);
		$html .='</h1>
	</div>
	<div style="width: 330px; height: 65px; margin-top: 5px; float: right;  ">
		<h1 class="totalbottomleft">TOTAL:</h1>
		<h1 class="totalbottomright">'.parsearPrecioReturn($FinalTotalOrderIvaIncl).'</h1>
	</div>
</div>
<div style="width: 400px;  height: 162px; float: left; background-color: white; margin-top:5px; padding:10px;">
	'.$formadepagoid.'
	'.$metodo_de_pago.'
	'.$numero_de_cuenta_company.'
	'.$comentariosOrder.'
</div>
';
$html .='
</body>
</html>
';
$mpdfone->WriteHTML($html);
//$mpdfone->Output(''.$titulodocumento.' Nº '.$invoiceNumber.'.pdf','D');
$mpdfone->Output('../pdfs/docs/'.$titulodocumento.'-'.$currentyear.'-'.$invoiceNumber.'.pdf','F');
//$mpdfone->Output();
?>