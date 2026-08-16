<?php
require("../../session_file.php");

?>
<?php
require_once("../../conexion.php");
require_once __DIR__ . '/../../include/config.php';
require_once __DIR__ . '/../../include/functions.php';

$id_factura = $_GET["id_factura"];
$id_sucursal = $suc;

$queryFR = "SELECT
FCTR.id_factura,
FCTR.numero_factura,
FCTR.total_factura,
FCTR.id_sucursal AS sucursal_factura,
FCTR.fecha_factura,
FCTR.prefijo_factura,
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
EMPS.nombre_empresa AS nombre_sucursal,
'CIF' AS identificacion_tienda,
EMPS.cif_empresa AS numero_identificacion_tienda,
EMPS.direccion_empresa AS direccion_tienda,
EMPS.poblacion_empresa AS poblacion_tienda,
EMPS.provincia_empresa AS provincia_tienda,
EMPS.codigo_postal_empresa AS codigo_postal_tienda,
EMPS.email_empresa AS email_tienda,
EMPS.telefono_empresa AS telefono_tienda,
EMPS.nombre_empresa AS empresa,
EMPS.logotipo_empresa AS logotipo_sucursal,
NULL AS sello_sucursal,
NULL AS sello_image,
VTS.tipo_pago,
EMPS.nombre_empresa,
EMPS.cif_empresa,
EMPS.direccion_empresa,
EMPS.poblacion_empresa,
EMPS.provincia_empresa,
EMPS.pais_empresa,
EMPS.telefono_empresa,
EMPS.codigo_postal_empresa
FROM facturas AS FCTR
LEFT JOIN clientes AS CLT ON CLT.id_cliente = FCTR.cliente_factura 
LEFT JOIN ventas AS VTS ON VTS.id = FCTR.rel_id_venta
LEFT JOIN empresas AS EMPS ON EMPS.id_empresa = COALESCE(NULLIF(FCTR.rel_id_empresa, 0), NULLIF(VTS.rel_id_empresa, 0))
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
$sucursal_factura = $rsItemFR['sucursal_factura'];
$logotipoPdf = $rsItemFR['logotipo_sucursal'];
$tipo_pago = $rsItemFR['tipo_pago'];
$fecha_factura = $rsItemFR['fecha_factura'];
$fecha_factura_final = date('d-m-Y',strtotime($fecha_factura));
$texto_facturas = obtenerTextoLegalFactura('articulos');
$fecha_factura_parset = strtotime($fecha_factura);
$anyo_factura = date("Y", $fecha_factura_parset);

$prefijo_factura = $rsItemFR['prefijo_factura'];


$direccion_tienda = $rsItemFR['direccion_tienda'];
$poblacion_tienda = $rsItemFR['poblacion_tienda'];
$provincia_tienda = $rsItemFR['provincia_tienda'];
$codigo_postal_tienda = $rsItemFR['codigo_postal_tienda'];
$telefono_tienda = $rsItemFR['telefono_tienda'];

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
$email_empresa = $rsItemFR['email_empresa'];

$datos_footer_empresa = $nombre_empresa."  - CIF: ".$cif_empresa." - ".$direccion_empresa." -  ".$poblacion_empresa." - España";

$direccion_tienda = $rsItemFR['direccion_tienda'];
$poblacion_tienda = $rsItemFR['poblacion_tienda'];
$provincia_tienda = $rsItemFR['provincia_tienda'];
$codigo_postal_tienda = $rsItemFR['codigo_postal_tienda'];
$telefono_tienda = $rsItemFR['telefono_tienda'];
$email_tienda = $rsItemFR['email_tienda'];

// VAR ORIGINALES DEL DOCUMENTO
$titulodocumento = "Factura";
$appColorBrand = "#555555";

$invoiceNumber = $NumberItemInvoice;
$hoy =  date("d/m/Y");
$currentyear = date ("Y");
/*
$logotipoPdf = "../logotipoPdf.jpg";

if($sucursal_factura == 56 ){
    $logotipoPdf = "../logo_pdf_URIA.jpg";
}
*/

function parsearPrecioReturn($priceNumber){
	$varLocal = "ES_es";
	setlocale(LC_MONETARY,"".$varLocal.".UTF-8");
	return money_format("%.2n", $priceNumber);
}


// get_logotipo_sucursal($idsucursal, $conexion);

switch($sucursal_factura){
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
			$texto_legal = ' (De acuerdo con los derechos que le confiere la normativa vigente y aplicable en protección de datos podrá
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
			$texto_legal = ' (De acuerdo con los derechos que le confiere la normativa vigente y aplicable en protección de datos podrá
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
			$texto_legal = ' (De acuerdo con los derechos que le confiere la normativa vigente y aplicable en protección de datos podrá
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
			$texto_legal = ' (De acuerdo con los derechos que le confiere la normativa vigente y aplicable en protección de datos podrá
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








    $query_SELL = " SELECT * FROM sellos WHERE id_sello = $id_factura ";
    $Item_SELL = mysql_query($query_SELL, $conexion);
    mysql_query ("SET NAMES 'utf8'");
    $rsItem_SELL = mysql_fetch_assoc($Item_SELL);

    $imagen_logotipo = $Item_SELL['imagen_logotipo'];
    $sello_logotipo = $Item_SELL['sello_logotipo'];
    
    if( $sello_logotipo == "true" ){
        
        $sello = '
        <div id="sello" style="width: 180px; margin-top: -15px; transform: rotate(-15deg);">
            <span class="spans_sellos" id="ordago">
                <img style="width: 180px;" src="'.$url.'/photos/'.$imagen_logotipo.'">
            </span><br>
            <span style="display: block; font-size: 10px; line-height: 13px; min-height: auto !important;" class="spans_sellos" id="nombre_empresa">'.$nombre_empresa.'</span>
            <span style="display: block; font-size: 10px; line-height: 13px; min-height: auto !important;"  class="spans_sellos" id="cif_empresa">CIF: '.$cif_empresa.'</span>
            <span style="display: block; font-size: 10px; line-height: 13px; min-height: auto !important;"  class="spans_sellos" id="direccion_tienda">'.$direccion_tienda.'</span>
            <span style="display: block; font-size: 10px; line-height: 13px; min-height: auto !important;"  class="spans_sellos" id="datos_varios">
                <span id="codigo_postal_tienda">'.$codigo_postal_tienda.' </span>
                <span id="poblacion_tienda"> '.$poblacion_tienda.' </span>
                <span id="provincia_tienda"> ('.$provincia_tienda.')</span>
            </span>
        </div>
        ';

    }else{
        
        $sello = '
        <div id="sello" style="width: 180px;">
            <span class="spans_sellos_sinlogo" id="ordago_sinlogo"></span>
            <span class="spans_sellos_sinlogo" id="nombre_empresa">'.$nombre_empresa.'</span>
            <span class="spans_sellos_sinlogo" id="cif_empresa">CIF: '.$cif_empresa.'</span>
            <span class="spans_sellos_sinlogo" id="direccion_tienda">'.$direccion_tienda.'</span>
            <span class="spans_sellos_sinlogo" id="datos_varios">
                <span id="codigo_postal_tienda">'.$codigo_postal_tienda.' </span>
                <span id="poblacion_tienda"> '.$poblacion_tienda.' </span>
                <span id="provincia_tienda"> ('.$provincia_tienda.')</span>
            </span>
        </div>';
        
    }








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

if(empty($logotipoPdf)){
    $mpdfone->SetHTMLHeader('
    <table width="100%" style="margin-top:20px;">
        <tr>
            <td width="50%">
                <img src="../../photos/'.$logotipoPdf.'" width="300" height="auto">
            </td>
            <td width="50%" style="text-align: right; vertical-align: bottom;">
               <p class="textoheader">'.$nombre_empresa.'</p>
               <p class="textoheader">CIF: '.$cif_empresa.'</p>
               <p class="textoheader">'. $direccion_tienda.' - '.$poblacion_tienda.'</p>
               <p class="textoheader">'. $provincia_tienda.' - España - ('.$codigo_postal_tienda.') </p>
               <p class="textoheader">'.$email_tienda.' - '.$telefono_tienda.'</p>
            </td>
        </tr>
    </table>
    ');
}else{
    $mpdfone->SetHTMLHeader('
    <table width="100%" style="margin-top:20px;">
        <tr>
            <td width="50%">
                <img src="../../photos/'.$logotipoPdf.'" width="300" height="auto">
            </td>
            <td width="50%" style="text-align: right; vertical-align: bottom;">
               <p class="textoheader">'.$nombre_empresa.'</p>
               <p class="textoheader">CIF: '.$cif_empresa.'</p>
               <p class="textoheader">'. $direccion_tienda.' - '.$poblacion_tienda.'</p>
               <p class="textoheader">'. $provincia_tienda.' - España - ('.$codigo_postal_tienda.') </p>
               <p class="textoheader">'.$email_tienda.' - '.$telefono_tienda.'</p>
            </td>
        </tr>
    </table>
    ');
}



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
<div style="width: 100%; height: 165px;">

	<div style="width: 400px; height: 155px; float: left">
';
if(empty($id_cliente)){
    
}else{
$html .= '
        <div class="destinatariodata">
        
        	<p class="nombrecliente">'.$nombrecomercialCustomer.'</p>
		
			<p class="texto-c" style="text-transform: uppercase;">CIF / NIF / NIE: '.$nifCustomer.'</p>
		
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
	
		<div style="width: 130px; height: 80px; float: left; padding: 5px 0px; ">
		
			<p class="texto-a" style="text-align: left;">Número '.$titulodocumento.': </p>
		
			<p class="texto-a" style="text-align: left;">Fecha emisión: </p>
            
            <p class="texto-a" style="text-align: left;">Forma de pago: </p>
		
		</div>
	
		<div style="width: 200px; height: 80px; float: left; padding: 5px 0px; ">
			
			<p class="texto-b" style="text-align: left;">'.$prefijo_factura.'-'.$anyo_factura.'-'.$numero_factura.'</p>
		
			<p class="texto-b" style="text-align: left;">'.$fecha_factura_final.'</p>
            
            <p class="texto-b" style="text-align: left; text-transform: capitalize;">'.$tipo_pago.'</p>

			
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
    <h1 class="itemtotalnav" style="float: right;">Total</h1>
</div>
';



$queryFRA="
SELECT 
id_rel_fac_art, 
id_rel_sucursal, 
id_rel_factura, 
id_rel_articulo,
precio_rel_articulo,
precio,
descripcion
FROM facturas_rel_articulos
LEFT JOIN articulos_venta ON facturas_rel_articulos.id_rel_articulo = articulos_venta.id
WHERE id_rel_factura = $id_factura
ORDER BY id_rel_fac_art ASC 
";
$ItemFRA = mysql_query($queryFRA, $conexion);
mysql_query ("SET NAMES 'utf8'");
while ($row = mysql_fetch_assoc($ItemFRA)){
    
            $id_rel_articulo = $row["id_rel_articulo"]; 
			$descripcion_articulo = $row["descripcion"]; 
			$precio_rel_articulo = $row["precio_rel_articulo"]; 
			$html .='
			  <div class="itemsinvoice">
					<p class="pitemnumbernav">'.$id_rel_articulo.'</p>
					<p class="pitemdescripcionnav">'.$descripcion_articulo.'</p>
					<p class="pitemunidadesnav">1</p>
					<p class="pitempreciounitarionav">'.parsearPrecioReturn($precio_rel_articulo).' €</p>
					<p class="pitemtotalnav" style="float: right;">'.parsearPrecioReturn($precio_rel_articulo).' €</p>
				</div>
			';
    
}

$html .='
<div style="width: 330px; position: absolute; bottom: 74px; left: 88px; ">
        <img src="'.$url.'/photos/'.$sello_image.'" width="188"  >
    </div>
        
</body>
</html>
';

$mpdfone->SetHTMLFooter('
<div class="footer"  style="padding-top: 20px; width: 100%;">
<hr class="classhr">
<div style=" width: 100%;">

    <div style="width: 330px; float: right;">
        <!--<div style="width: 330px; height: 65px; margin-top: 5px; float: right;  ">
            <h1 class="textototalbottomleft" style="background: #EDEDEE; color: '.$appColorBrand.';">Total neto:<br></h1> 
            <h1 class="textototalbottomright" style="background: #EDEDEE; color: '.$appColorBrand.';">'.parsearPrecioReturn($total_factura).' €</h1>
        </div>-->
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
				float: right;">'.parsearPrecioReturn($total_factura).' €</h1>
        </div>
    </div>
    
</div>

	<hr class="classhr">
    <div style="width: 100%; text-align: left; font-style: italic; margin:5px 0 0 0; color:#000000; font-size:7pt; ">'.$texto_facturas.'</div>
    <p style="text-align: left; font-style: normal; margin:10px 0 0 0; color:#000000; font-size:7pt; ">'.$datos_footer_empresa.'</p>
</div>
'
);

$mpdfone->WriteHTML($html);
//$mpdfone->Output(''.$titulodocumento.'-Nº'.$invoiceNumber.'.pdf','D');
//$mpdfone->Output('../pdfs/outputinvoice/'.$titulodocumento.'-'.$currentyear.'-'.$invoiceNumber.'.pdf','F');
$mpdfone->Output();

?>