<?php 
include_once '../includes/db_connect.php';
include_once '../includes/functions.php';
 
sec_session_start();
include_once '../conexion.php';

if (login_check($mysqli) == true) : else :
header("Location: ../index.php"); 
endif; 

/*session_start();
if($_SESSION["logeado"] != "SI"){ 
$error = "Necesita estar registrado:";
header("location: ../login.php?error=$error");
exit;
}*/
function generateSignatureContratoFinal( $encodeData, $textSignature ){
    $prefix = "signature_";
    $extencionFile = "svg";
    $file_name = uniqid($prefix).".".$extencionFile;
    $encodeData = substr($encodeData, strpos($encodeData, ',') + 1);
    $decodeData = base64_decode($encodeData);
    $handle = fopen($file_name, 'x+');
    fwrite($handle, $decodeData);
    fclose($handle);
    $signatureFinal = '
    <div style="width: 200px; display: block; margin: 0 auto; font-size:14px; font-weight:bold; text-align:center; "><br>'.$textSignature.'
    <img src="'.$file_name.'" alt="" style="width: 333px;"/>
    
    </div>
    ';
    return $signatureFinal;   
}
?>