<?php 
require("../../conexion.php");


//////////////////////////////////////
//ahora inserto el lote///////////////
//////////////////////////////////////
$hora = date("Y-m-d H:i:s");

if( $_POST['movil_sms'] == "true"){
    mysql_query("INSERT INTO sms_send (
    movil_sms,
    mensaje_sms,
    cliente_sms,
    type_item_sms,
    estado_sms,
    usuario_sms,
    surusal_sms,
    fecha_sms
    )
    VALUES (
    '{$_POST['movil_sms']}',
    '{$_POST['mensaje_sms_manual']}',
    '{$_POST['id_cliente_sms']}',
    '{$_POST['type_item_sms']}',
    '{$_POST['estado_sms']}',
    '{$id_usuario}',
    '{$suc}',
    '{$hora}'
    )",$conexion);
    $id_sms = mysql_insert_id();
}else{
    
    mysql_query("INSERT INTO sms_send (
    movil_sms,
    mensaje_sms,
    cliente_sms,
    type_item_sms,
    estado_sms,
    usuario_sms,
    surusal_sms,
    fecha_sms
    )
    VALUES (
    '{$_POST['movil_sms']}',
    '{$_POST['mensaje_sms_manual']}',
    '{$_POST['id_cliente_sms']}',
    '{$_POST['type_item_sms']}',
    '{$_POST['estado_sms']}',
    '{$id_usuario}',
    '{$suc}',
    '{$hora}'
    )",$conexion);
    $id_sms = mysql_insert_id();
    
}


$error_msg = "ok";

if($error_msg=='ok'){
    header('Content-Type: application/json');
    $datos = array(
        'statelogsms' => 'ok',
        'id_sms' => $id_sms
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