<?
require("session_file.php");
//include ($_SERVER['DOCUMENT_ROOT']."/session_file.php");


$id_cliente = 23995;
$query = "SELECT firma_cliente FROM clientes WHERE id_cliente = '23995' ";
$Item = mysql_query($query, $conexion);
mysql_query ("SET NAMES 'utf8'");
$rsItem = mysql_fetch_assoc($Item);
$signature_value = $rsItem['firma_cliente'];

$textSignature = "jorge deboli";
$signatureInsert = generateSignatureContratoFinal( $signature_value, $textSignature );
echo $signatureInsert;