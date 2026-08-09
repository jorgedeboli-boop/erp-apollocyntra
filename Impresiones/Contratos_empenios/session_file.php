<?php 
include_once '../../includes/db_connect.php';
include_once '../../includes/functions.php';
 
sec_session_start();
include_once '../../conexion.php';

if (login_check($mysqli) == true) : else :
header("Location: ../index.php"); 
endif; 

/*session_start();
if($_SESSION["logeado"] != "SI"){ 
$error = "Necesita estar registrado:";
header("location: ../login.php?error=$error");
exit;
}*/
?>