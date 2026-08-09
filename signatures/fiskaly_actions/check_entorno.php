<?php
require_once 'include/functions.php';

    // Obtener sucursal_venta por GET
    if($formato_factura == 'articulos'){
        $sucursal_venta = isset($_GET['sucursal_venta']) ? (int)$_GET['sucursal_venta'] : 0;
    }else{
        $sucursal_renovacion = isset($_GET['sucursal_renovacion']) ? (int)$_GET['sucursal_renovacion'] : 0;
    }

    
    $id_empresa = isset($_GET['id_empresa']) ? (int)$_GET['id_empresa'] : 0;

    $url_api_fiskaly = obtenerUrlApiFiskalyPorEmpresa($id_empresa);
    if ($url_api_fiskaly) {
    }else{
        if ($url_completa_origen) {
            header('Location: ' . $url_completa_origen.'&state=error&text_error='.urlencode('URL de la API de Fiskaly no encontrada'));
        } else {
            http_response_code(403);
            die('Error: URL de la API de Fiskaly no encontrada');
        }
    }
?>