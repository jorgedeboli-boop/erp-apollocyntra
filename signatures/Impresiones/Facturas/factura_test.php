<?php
require("../../session_file.php");

?>
<?php
require_once("../../conexion.php");

$id_factura = $_GET["id_factura"];
$id_sucursal = $suc;

$queryFR = "SELECT
FCTR.id_factura,
FCTR.numero_factura,
FCTR.total_factura,
CLT.id_cliente,
CLT.nombre,
CLT.apellido,
CLT.direccion,
CLT.c_provincia,
CLT.c_poblacion,
CLT.codigo_postal,
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
SCR.empresa
FROM facturas AS FCTR
LEFT JOIN clientes AS CLT ON CLT.id_cliente = FCTR.cliente_factura 
LEFT JOIN sucursal AS SCR ON SCR.id_sucursal = FCTR.id_sucursal 
WHERE FCTR.id_factura = $id_factura
";

$ItemFR = mysql_query($queryFR, $conexion);
mysql_query ("SET NAMES 'utf8'");
$rsItemFR = mysql_fetch_assoc($ItemFR);

$id_factura = $rsItemFR['id_factura'];
$numero_factura = $rsItemFR['numero_factura'];
$total_factura = $rsItemFR['total_factura'];
$id_cliente = $rsItemFR['id_cliente'];
$nombre = $rsItemFR['nombre'];
$apellido = $rsItemFR['apellido'];
$nombrecomercialCustomer = $nombre." ".$apellido;
$addressFullInvoice = $rsItemFR['direccion'];
$provinceCustomerInvoice = $rsItemFR['c_provincia'];
$populationCustomerInvoice = $rsItemFR['c_poblacion'];
$postalcodeCustomerInvoice = $rsItemFR['codigo_postal'];
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
$countryCustomerInvoice = "España";

// VAR ORIGINALES DEL DOCUMENTO
$titulodocumento = "Factura";
$appColorBrand = "#555555";

$invoiceNumber = $NumberItemInvoice;
$hoy =  date("d/m/Y");
$currentyear = date ("Y");
$logotipoPdf = "../logotipoPdf.jpg";

function parsearPrecioReturn($priceNumber){
	$varLocal = "ES_es";
	setlocale(LC_MONETARY,"".$varLocal.".UTF-8");
	return money_format("%.2n", $priceNumber);
}

/*
if(empty($prefixInvoice)){ $prefix=""; }else{ $prefix=$prefixInvoice."/"; }
if($numberyearinvoicestate=='true'){
	$asignnumberitem = $prefix."$currentyear"."-".$invoiceNumber;
}else{
	$asignnumberitem = $prefix.$invoiceNumber;
}
$invoiceNumberPrint =$asignnumberitem;

$typeItemtaxesbank = "Customer";
//RECOGO LOS DATOS DETAXESBANK DEL CLIENTE
if ($stmtC = $mysqli->prepare("SELECT TXK.formadepagoid, TXK.idmethodsPayment, MTP.namemethodsPayment, FRM.nombreformadepago FROM taxesbank TXK LEFT JOIN methodsPayment MTP ON MTP.idmethodsPayment = TXK.idmethodsPayment LEFT JOIN formasdepago FRM ON FRM.idformadepago = TXK.formadepagoid WHERE TXK.item_id = ? AND TXK.companyIdTaxes = ? AND TXK.typeItem = ? ")) {
$stmtC->bind_param('iis', $idCustomerOrder, $companyIdUser, $typeItemtaxesbank);  
$stmtC->execute();    
$stmtC->store_result();
$stmtC->bind_result($formadepagoid, $idmethodsPayment, $namemethodsPayment, $nombreformadepago);
$stmtC->fetch();
}
$typeItemtaxesbankCompany = "Company";
//RECOGO LOS DATOS DETAXESBANK DEL CLIENTE
if ($stmtC = $mysqli->prepare("SELECT IBAN FROM taxesbank WHERE item_id = ? AND companyIdTaxes = ? AND typeItem = ? ")) {
$stmtC->bind_param('iis', $companyIdUser, $companyIdUser, $typeItemtaxesbankCompany);  
$stmtC->execute();    
$stmtC->store_result();
$stmtC->bind_result($IBANCompany);
$stmtC->fetch();
}
$formadepagoid = '<p style="line-height: 18px; font-size: 10px;"><strong>Forma de pago:</strong> '.$nombreformadepago.'</p>';
$metodo_de_pago = '<p style="line-height: 18px; font-size: 10px;"><strong>Método de pago:</strong> '.$namemethodsPayment.'</p>';
if($idmethodsPayment=='2'){
$numero_de_cuenta_company = '<p style="line-height: 18px; font-size: 10px;"><strong>Número de cuenta IBAN:</strong> '.$IBANCompany.'</p>';
}
if(!empty($commentsDocOrder)){
$comentariosOrder = '<p style="line-height: 18px; font-size: 10px; margin-top:10px;"><strong>Comentarios:</strong> '.$commentsDocOrder.'</p>';
}
// SUMO EL TOTAL LOS ITEMS
if ($stmtC = $mysqli->prepare("SELECT SUM(totalRelPvp) AS counTotal FROM relProductsInvoices WHERE idRelInvoice = ? AND companyIdRel = ? ")) {
$stmtC->bind_param('ss', $lastIdInvoice, $companyIdUser);  
$stmtC->execute();    
$stmtC->store_result();
$stmtC->bind_result($counTotal);
$stmtC->fetch();
}
// SUMO EL TOTAL DEL IVA LOS ITEMS
if ($stmtC = $mysqli->prepare("SELECT SUM(totalRelIva) AS counTotalIva FROM relProductsInvoices WHERE idRelInvoice = ? AND companyIdRel = ? ")) {
$stmtC->bind_param('ss', $lastIdInvoice, $companyIdUser);  
$stmtC->execute();    
$stmtC->store_result();
$stmtC->bind_result($counTotalIva);
$stmtC->fetch();
}

$FinalTotalOrderIvaIncl = $counTotal + $counTotalIva;
*/



include("../../MPDF54/mpdf.php");

$mpdfone=new mPDF('win-1252','A4','','',5,5,30,21,5,5); 

$mpdfone->useOnlyCoreFonts = true;    // false is default
$mpdfone->SetProtection(array('print'));
$mpdfone->SetTitle("Factura ".$nameCompany."");
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
	<p class="textofooter">'.$nameCompany.' - '.$titulodocumento.' Nº '.$numero_factura.'/'.$currentyear.'</p>
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
		</style>
</head>
<body>
';

$html .= '
<div style="width: 100%; height: 55px; padding-top: 5px;"><h1 class="titulodocumento">'.$titulodocumento.'</h1></div>
<div style="width: 100%; height: 165px;">

	<div style="width: 400px; height: 155px; float: left">
    	
        <div class="destinatariodata">
        
        	<p class="nombrecliente">'.$nombrecomercialCustomer.'</p>
		
			<p class="texto-c" style="text-transform: uppercase;">'.$tiponifCustomer.': '.$nifCustomer.'</p>
		
			<p class="texto-c">'.$addressFullInvoice.' - '.$populationCustomerInvoice.'</p>
            
            <p class="texto-c">'.$provinceCustomerInvoice.' - '. $countryCustomerInvoice.' - ('.$postalcodeCustomerInvoice.')</p>
            
            <p class="texto-c">'.$emailCustomer.'</p>
            
            <p class="texto-c">'.$telefonoCustomer.' '.$movilCustomer.'</p>
            
        </div>
    </div>
	
	<div style="width: 340px; height: 100px; float: right">
	
		<div style="width: 130px; height: 80px; float: left; padding: 5px 0px; ">
		
			<p class="texto-a" style="text-align: left;">Número '.$titulodocumento.': </p>
		
			<p class="texto-a" style="text-align: left;">Fecha emisión: </p>
		
		</div>
	
		<div style="width: 200px; height: 80px; float: left; padding: 5px 0px; ">
			
			<p class="texto-b" style="text-align: left;">'.$numero_factura.'/'.$currentyear.'</p>
		
			<p class="texto-b" style="text-align: left;">'.$hoy.'</p>
		
			
		</div>
        
        <div style="width:100%; height:60px; float: left;">
        	<h1 class="totaltopleft">TOTAL:</h1><h1 class="totaltopright">'.parsearPrecioReturn($total_factura).' €</h1>
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



$queryFRA="
SELECT 
id_rel_fac_art, 
id_rel_sucursal, 
id_rel_factura, 
id_rel_articulo,
precio,
descripcion
FROM facturas_rel_articulos
LEFT JOIN articulos_venta ON facturas_rel_articulos.id_rel_articulo = articulos_venta.id
WHERE id_rel_factura = $id_factura AND id_rel_sucursal = $id_sucursal
ORDER BY id_rel_fac_art ASC 
";
$ItemFRA = mysql_query($queryFRA, $conexion);
mysql_query ("SET NAMES 'utf8'");
while ($row = mysql_fetch_assoc($ItemFRA)){
    
            $id_rel_articulo = $row["id_rel_articulo"]; 
			$descripcion_articulo = $row["descripcion"]; 
			$precio_rel_articulo = $row["precio"]; 
			$html .='
			  <div class="itemsinvoice">
					<p class="pitemnumbernav">'.$id_rel_articulo.'</p>
					<p class="pitemdescripcionnav">'.$descripcion_articulo.'</p>
					<p class="pitemunidadesnav">1</p>
					<p class="pitempreciounitarionav">'.parsearPrecioReturn($precio_rel_articulo).' €</p>
					<p class="pitemivanav">--</p>
					<p class="pitemtotalnav" style="float: right;">'.parsearPrecioReturn($precio_rel_articulo).' €</p>
				</div>
			';
    
}

$html .= '
<hr class="classhr">
<div style="width: 330px;  float: right;">
	<div style="width: 330px; height: 65px; margin-top: 5px; float: right;  ">
		<h1 class="textototalbottomleft">Total neto:<br>';
        
		$html .='</h1>';
		$html .='<h1 class="textototalbottomright">'.parsearPrecioReturn($total_factura).' €';
		
		$html .='</h1>
	</div>
    
';

$html .= '
	<div style="width: 330px; height: 65px; margin-top: 5px; float: right;  ">
		<h1 class="totalbottomleft">TOTAL:</h1>
		<h1 class="totalbottomright">'.parsearPrecioReturn($total_factura).' €</h1>
	</div>
</div>
<div style="width: 400px;  height: 162px; float: left; background-color: white; margin-top:5px; padding:10px;">
	<div style="text-align: left; font-style: italic; margin:5px 0 0 0; color:#000000; font-size:5pt; ">
    CLAUSULAS APLICABLES A LAS OPERACIONES DE COMPRA DE ORO.
    <br>
    <!-- OPERACIÓN SUJETA AL RÉGIMEN ESPECIAL DE BIENES USADOS SEGÚN ARTÍCULO 135 DE LA LEY 37/1992 DEL 28 DE DICIEMBRE DEL IVA. -->
    OPERACIÓN ACOGIDA AL RÉGIMEN ESPECIAL DE BIENES USADOS (REBU) DE LA LEY 37/1992 DEL IMPUESTO SOBRE EL VALOR AÑADIDO (IVA).
    <br>
    <strong>EN ESTE CONTRATO USTED ESTÁ COMPRANDO EL OBJETO REFERENCIADO.</strong>
    <br />
	';
	switch($sucursal){
		//ESTER JOYERIA
		case 51:
		case 3:
		case 41:
		case 4:
		case 25:
		case 24:
		case 23:
		case 16:
		case 14:
		case 17:
		case 18:
			$html .= ' (De acuerdo con los derechos que le confiere la normativa vigente y aplicable en protección de datos podrá
				ejercer los derechos de acceso, rectificación, limitación de tratamiento, supresión (“derecho al olvido”),
				portabilidad y oposición al tratamiento de sus datos de carácter personal así como la revocación del
				consentimiento prestado para el tratamiento de los mismos, dirigiendo su petición a la dirección C/Goya, 127 
				28009, MADRID o al correo electrónico esjollerias19@gmail.com. Podrá dirigirse a la Autoridad de
				Control competente para presentar la reclamación que considere oportuna.<br /><br />
				Si desea ampliar la información sobre los procedimientos y protocolos adoptados por ESTER JOYERIA 2019 SL,
				le informamos, que puede contactar con el Delegado de Protección de Datos, dirigiéndose por escrito a la
				dirección de correo dpd.cliente@conversia.es o al teléfono 902 877 192.) ';
			break;
		//AYALA RECICLADOS
		case 22:
		case 21:
		case 20:
		case 15:
			$html .= ' (De acuerdo con los derechos que le confiere la normativa vigente y aplicable en protección de datos podrá
				ejercer los derechos de acceso, rectificación, limitación de tratamiento, supresión (“derecho al olvido”),
				portabilidad y oposición al tratamiento de sus datos de carácter personal así como la revocación del
				consentimiento prestado para el tratamiento de los mismos, dirigiendo su petición a la dirección C/Pensamiento 27, 3º Esc. Izda. 
				28020, MADRID o al correo electrónico ayalareciclados16@hotmail.com. Podrá dirigirse a la Autoridad de
				Control competente para presentar la reclamación que considere oportuna.<br /><br />
				Si desea ampliar la información sobre los procedimientos y protocolos adoptados por AYALA RECICLADOS 16 SLU,
				le informamos, que puede contactar con el Delegado de Protección de Datos, dirigiéndose por escrito a la
				dirección de correo dpd.cliente@conversia.es o al teléfono 902 877 192.) ';
			break;
		//NANOPAC
		case 47:
			$html .= ' (De acuerdo con los derechos que le confiere la normativa vigente y aplicable en protección de datos podrá
					ejercer los derechos de acceso, rectificación, limitación de tratamiento, supresión (“derecho al olvido”),
					portabilidad y oposición al tratamiento de sus datos de carácter personal así como la revocación del
					consentimiento prestado para el tratamiento de los mismos, dirigiendo su petición a la dirección Avda. Brasil, 6 Planta 1 
					28020, MADRID o al correo electrónico nanopacjoyeriaydsl@gmail.com. Podrá dirigirse a la Autoridad de
					Control competente para presentar la reclamación que considere oportuna.<br /><br />
					Si desea ampliar la información sobre los procedimientos y protocolos adoptados por NANOPAC JOYERIA Y DISEÑO SL,
					le informamos, que puede contactar con el Delegado de Protección de Datos, dirigiéndose por escrito a la
					dirección de correo dpd.cliente@conversia.es o al teléfono 902 877 192.) ';
			break;
		//OPELIA
		case 50:
			$html .= ' (De acuerdo con los derechos que le confiere la normativa vigente y aplicable en protección de datos podrá
					ejercer los derechos de acceso, rectificación, limitación de tratamiento, supresión (“derecho al olvido”),
					portabilidad y oposición al tratamiento de sus datos de carácter personal así como la revocación del
					consentimiento prestado para el tratamiento de los mismos, dirigiendo su petición a la dirección C/Velázquez 53, 2º Izda 
					28001, MADRID o al correo electrónico opeliaservices@gmail.com. Podrá dirigirse a la Autoridad de
					Control competente para presentar la reclamación que considere oportuna.<br /><br />
					Si desea ampliar la información sobre los procedimientos y protocolos adoptados por OPELIA SERVICES SL,
					le informamos, que puede contactar con el Delegado de Protección de Datos, dirigiéndose por escrito a la
					dirección de correo dpd.cliente@conversia.es o al teléfono 902 877 192.) ';
			break;		
	}

$html .= '
  </div>
</div>
';

$html .='
</body>
</html>
';

$mpdfone->WriteHTML($html);
//$mpdfone->Output(''.$titulodocumento.'-Nº'.$invoiceNumber.'.pdf','D');
//$mpdfone->Output('../pdfs/outputinvoice/'.$titulodocumento.'-'.$currentyear.'-'.$invoiceNumber.'.pdf','F');
$mpdfone->Output();
?>