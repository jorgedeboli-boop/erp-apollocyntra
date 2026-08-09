<?php 
    require("../../conexion.php");

$id_autorizar_compra = $_POST['id_autorizar_compra'];

mysql_query("UPDATE sms_send SET
estado_autorizado = 'true'
WHERE id_sms = $id_autorizar_compra ",$conexion);


$error_msg = 'ok';

if($error_msg=='ok'){
    header('Content-Type: application/json');
    $datos = array(
        'statelogsms' => 'ok'
    );
    echo json_encode($datos, JSON_FORCE_OBJECT);
}else{
    header('Content-Type: application/json');
    $datos = array(
        'statelogsms' => $error_msg
    );
    echo json_encode($datos, JSON_FORCE_OBJECT);
}

?>