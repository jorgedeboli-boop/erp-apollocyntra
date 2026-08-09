<?php
$conexion = mysql_connect("localhost", "root", "0rO2022*") or trigger_error(mysql_error(),E_USER_ERROR); 
mysql_select_db("oroefectivo", $conexion);
//mysql_query ("SET NAMES 'utf8'");
?>