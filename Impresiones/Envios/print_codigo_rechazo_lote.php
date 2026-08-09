<?php
require("../../session_file.php");
?>
<?php
require_once("../../conexion.php");
$lote_decline = $_GET["lote_decline"];
$sucursal_remitente = $_GET["sucursal_remitente"];
$id_envio = $_GET["id_envio"];
$codigo_envio = $_GET["codigo_envio"];
$nombre_sucursal = $_GET["nombre_sucursal"];
?>
<html>
<head>
<meta charset="UTF-8" />
<style>
body {font-family: helvetica, arial;
    font-size: 7pt;
	 width:900px;
	 margin:0 auto;
	 color:#000000;
	 text-transform: uppercase; 
}
p{ 
margin: 0pt;
line-height:auto;
font-size:12px;
text-transform: uppercase; 
}
td { vertical-align: top; }
.items td {
}
.table {
	border-collapse:collapse;
}
.tablearticulo{
	margin:0 0 20px 0;
}
.table .td{
	padding:2px 5px;
	border: 1px solid #333333;
	border-spacing:0px;
	height:35px;
}
table thead td {
	text-align: center;
}
.items td.blanktotal {
    background-color: none;
    border: none;
}
.items td.totals {
    text-align: right;
}
h2{
	text-align:center;
	font-size:13px;
}
h3{
	text-decoration:none;
	font-size:10px;
	margin-top: 10px;
}
smallbr{
font-size: 1px; 
line-height: 0; 
}
.checkseleccionado{
	background-color:#0a090a;
	-webkit-border-top-left-radius:3px;
	-moz-border-radius-topleft:3px;
	border-top-left-radius:3px;
	-webkit-border-top-right-radius:3px;
	-moz-border-radius-topright:3px;
	border-top-right-radius:3px;
	-webkit-border-bottom-right-radius:3px;
	-moz-border-radius-bottomright:3px;
	border-bottom-right-radius:3px;
	-webkit-border-bottom-left-radius:3px;
	-moz-border-radius-bottomleft:3px;
	border-bottom-left-radius:3px;
	text-indent:0px;
	display:inline-block;
	color:#ffffff;
	font-family:Verdana;
	font-size:11px;
	font-weight:bold;
	font-style:normal;
	height:16px;
	line-height:16px;
	width:16px;
	text-decoration:none;
	text-align:center;
}
.checknoseleccionado {
	background-color:#ffffff;
	-webkit-border-top-left-radius:3px;
	-moz-border-radius-topleft:3px;
	border-top-left-radius:3px;
	-webkit-border-top-right-radius:3px;
	-moz-border-radius-topright:3px;
	border-top-right-radius:3px;
	-webkit-border-bottom-right-radius:3px;
	-moz-border-radius-bottomright:3px;
	border-bottom-right-radius:3px;
	-webkit-border-bottom-left-radius:3px;
	-moz-border-radius-bottomleft:3px;
	border-bottom-left-radius:3px;
	text-indent:0px;
	border:1px solid #a3a3a3;
	display:inline-block;
	color:#ffffff;
	font-family:Verdana;
	font-size:11px;
	font-weight:bold;
	font-style:normal;
	height:14px;
	line-height:14px;
	width:14px;
	text-decoration:none;
	text-align:center;
}
</style>

<script type="text/javascript"> 
    function PrintWindow() { 
    window.print(); 
    CheckWindowState(); 
    } 

    function CheckWindowState() { 
    if(document.readyState=="complete") { 
    window.location.replace("../../ver_envio_central.php?categoria=envios&sucursal_remitente=<? echo $sucursal_remitente; ?>&page=envios&id_envio=<? echo $id_envio; ?>");
    } else { 
    setTimeout("CheckWindowState()", 10) 
    } 
    } 
    PrintWindow();
</script>
<style type="text/css" media="print">
@page {
    size: auto;   /* auto is the initial value */
    margin-top: 0;  /* this affects the margin in the printer settings */
    margin-bottom:0;
    text-align: center;
}
body{
    margin-top: 50px;
    text-align: center;
}
    h1{
        font-size: 45px;
    }
</style>
</head>
<body>
<br><br><br>
<div style="border: 1px dashed grey; text-align: center; padding: 0px;">
    <h1 style="font-size: 39px;">DEVOLUCIÓN DE LOTES RECHAZADOS</h1>
    <h1 style="font-size: 39px;">SUCURSAL DESTINATARIA: <? ECHO $nombre_sucursal; ?></h1>
    <h1 style="font-size: 39px;">LOTE Nº <? echo $lote_decline; ?> / ENVÍO Nº<? echo $id_envio; ?></h1>
    <h1 style="font-size: 39px;">CÓDIGO DE APERTURA DE LOTE <span style="font-size: 80px; background: grey; color: white; display: block; padding: 3px;"><? echo $codigo_envio; ?></span></h1>
</div>
</body>
</html>