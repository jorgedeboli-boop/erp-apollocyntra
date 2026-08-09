<?php
require("../session_file.php");
?>
<?php 
require("../conexion.php");
include_once '../includes/functions.php';

$typeitem = $_POST["typeitem"];
$iditem = $_POST["iditem"];
$email = $_POST["email_destino"];

$dia = date("d.m.Y");
$hora = date("H:i:s");
$currentyear = date("Y");
$titulodocumento = $typeitem;
$appColorBrand = "#666666";
$number_aleatorio = rand(1,9999);
$dominio = $_SERVER['SERVER_NAME'];
$licencia_acceso = $_SESSION['licencia'];
$logotipoPdf_parset = "../photos/";
$url_logo_email_parset = "http:/".$dominio."/photos/";
$foto_dni_cliente = "../photos/";
$statesignature = 'true';

//Consulto sucursal
$querys = "SELECT * FROM sucursales WHERE id_sucursal= $sucursal ";
mysql_query ("SET NAMES 'utf8'");
$Ietem = mysql_query($querys, $conexion);
$rseItem = mysql_fetch_assoc($Ietem);
$empresa_id = $rseItem['empresa_id'];
$logotipoPdf = $logotipoPdf_parset.$rseItem['logotipo_sucursal'];
$url_logo_email = $url_logo_email_parset.$rseItem['logotipo_sucursal'];

$queryss = "SELECT * FROM empresas WHERE id_empresa = $empresa_id ";
mysql_query ("SET NAMES 'utf8'");
$Ietems = mysql_query($queryss, $conexion);
$rseItems = mysql_fetch_assoc($Ietems);
$nombreempresa = $rseItems['nombre_empresa'];
$appName = $rseItems['nombre_empresa'];
$nameCompany=$rseItems['nombre_empresa'];
$direcciontienda=$rseItems['direccion_empresa'];
$codigospotaltienda=$rseItems['codigo_postal_empresa'];
$ciudadtienda=$rseItems['poblacion_empresa'];
$provinciatienda=$rseItems['provincia_tienda'];
$countryAddress = "España";
$telefonotienda=$rseItems['telefono_empresa'];
$email_app = $rseItems['email_empresa'];
$appEmail=$rseItems['email_empresa'];
$appSuportEmail = $rseItems['email_empresa'];
$ciftienda=$rseItems['cif_empresa'];
$appWeb=$rseItems['webempresa'];
$tiponifCompany = "CIF";


if($typeitem=="deposito"){
    
    include_once 'generate_pdf_deposito.php';
    
}elseif($typeitem=="compra"){
    
    include_once 'generate_pdf_compra.php';
    
}elseif($typeitem=="empeno"){
    
    include_once 'generate_pdf_empeno.php';
    
}elseif($typeitem=="lote"){
    
    include_once 'generate_pdf_lote.php';
    
}elseif($typeitem=="empenolote"){
    
    include_once 'generate_pdf_empeno_lote.php';
    
}
include_once 'send_mail_document.php';

$error_msg = 'ok';
    
if($error_msg=='ok'){
	$hoy=date("Y-m-d");
	header('Content-Type: application/json');
	$datos = array(
		'status' => 'ok'
	);	
	echo json_encode($datos, JSON_FORCE_OBJECT);
}else{
	header('Content-Type: application/json');
	$datos = array(
        'status' => 'ko'
	);
	echo json_encode($datos, JSON_FORCE_OBJECT);
}
    
?>