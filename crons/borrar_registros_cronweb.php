<?
//OBTENGO LA SUCURSAL
$query = "DELETE FROM test_cron WHERE hora_insert < DATE_SUB(NOW(), INTERVAL 7 DAY);";
$Item = mysql_query($query, $conexion);

$query = "DELETE FROM tareas_cron WHERE fecha < DATE_SUB(NOW(), INTERVAL 7 DAY);";
$Item = mysql_query($query, $conexion);
?>