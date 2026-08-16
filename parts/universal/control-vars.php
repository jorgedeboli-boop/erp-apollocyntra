<?php
$conexion = conectar_bd();

$query = "SELECT * FROM itemsSections WHERE url_item = ?";
$stmt = mysqli_prepare($conexion, $query);
mysqli_stmt_bind_param($stmt, "s", $uri_actual_limpia);
mysqli_stmt_execute($stmt);
$resultitemsSections = mysqli_stmt_get_result($stmt);    
$itemsSections = mysqli_fetch_assoc($resultitemsSections);

if (isset($_SESSION['auditoria_section']) && $_SESSION['auditoria_section'] === 'true') {
    $dashboard_redirect_url = 'dashboard_auditorias.php';
} elseif (isset($_SESSION['recepcion_lotes_section']) && $_SESSION['recepcion_lotes_section'] === 'true') {
    $dashboard_redirect_url = 'dashboard_recepcion_lotes.php';
} else {
    $dashboard_redirect_url = 'dashboard.php';
}

if (mysqli_num_rows($resultitemsSections) == 0) {
    header('Location: ' . $dashboard_redirect_url . '?error=not control vars');
    exit();
}else{

    $id_type_Item = $itemsSections['id_type_Item'];
    $typ_item = $itemsSections['typ_item'];
    $type = $itemsSections['typ_item'];
    $id_item = $itemsSections['id_type_Item'];
    $itemname = $itemsSections['itemName'];
    $itemnameText = $itemsSections['itemnameText'];
    $url_completa = APP_URL.'/'.$itemname.'/'.$type.'/content.php';
    $columnas_section = ['central_section', 'recepcion_lotes_section', 'auditoria_section'];
    $nombres_section = [
        'central_section' => 'central',
        'recepcion_lotes_section' => 'recepción de lotes',
        'auditoria_section' => 'auditoría',
    ];

    $section_activa_item = null;
    foreach ($columnas_section as $columna) {
        if (($itemsSections[$columna] ?? 'false') === 'true') {
            $section_activa_item = $columna;
            break;
        }
    }
    if ($section_activa_item === null) {
        $section_activa_item = 'central_section';
    }

    $_SESSION['section_activa_item'] = $section_activa_item;

    $usuario_sections = [
        'central_section' => ($_SESSION['central_section'] ?? 'false') === 'true' ? 'true' : 'false',
        'recepcion_lotes_section' => ($_SESSION['recepcion_lotes_section'] ?? 'false') === 'true' ? 'true' : 'false',
        'auditoria_section' => ($_SESSION['auditoria_section'] ?? 'false') === 'true' ? 'true' : 'false',
    ];

    $section_activa_usuario = null;
    foreach ($columnas_section as $columna) {
        if ($usuario_sections[$columna] === 'true') {
            $section_activa_usuario = $columna;
            break;
        }
    }
    if ($section_activa_usuario === null) {
        $section_activa_usuario = 'central_section';
    }

    // typ_item=main que son listados sin ficha (?id= no requerido)
    $uris_main_sin_id = ['auditoria_empeno_vencido'];
    $main_requiere_id = !in_array($uri_actual_limpia, $uris_main_sin_id, true);

    if ($section_activa_item !== $section_activa_usuario) {
        $nombre_section_item = $nombres_section[$section_activa_item] ?? $section_activa_item;
        $id_action_user = '38';
        $texto_action_user = "$usuario intenta acceder a la seccion $itemname que es tipo $nombre_section_item";
        registrar_accion_usuario($usuario_id, $id_action_user, $texto_action_user, $usuario_sucursal, $id_type_Item, $url_completa);
        header('Location: ' . $dashboard_redirect_url . '?error=no tienes acceso a esta seccion');
        exit();
    }

    $_SESSION['relItemAction'] = $id_type_Item;

    // Ítem de menú a marcar como activo: en CRUD, editar/crear/main comparten fhater_item con el listar
    $menu_active_id_type_item = (int) $id_type_Item;
    $fh_item_menu = (int) ($itemsSections['fhater_item'] ?? 0);
    if ($fh_item_menu > 0 && in_array($typ_item, ['editar', 'crear', 'main'], true)) {
        $menu_active_id_type_item = $fh_item_menu;
    }

    // Privilegios CRUD del módulo actual (listar, main, editar, crear, delete)
    $id_padre_listar = crud_id_padre_listar($itemsSections);
    $_SESSION['id_padre_listar'] = $id_padre_listar;
    $puede_acceder_main = usuario_puede_acceder_crud_tipo($usuario_privilegio_id, $id_padre_listar, 'main');
    $puede_acceder_editar = usuario_puede_acceder_crud_tipo($usuario_privilegio_id, $id_padre_listar, 'editar');
    $puede_acceder_crear = usuario_puede_acceder_crud_tipo($usuario_privilegio_id, $id_padre_listar, 'crear');
    $puede_acceder_borrar = usuario_puede_acceder_crud_tipo($usuario_privilegio_id, $id_padre_listar, 'delete');
    $puede_acceder_edit = usuario_puede_acceder_permiso_accion($usuario_privilegio_id, $id_padre_listar, 'edit');
    $puede_acceder_fotos_cliente_edit = usuario_puede_acceder_fotos_cliente_edit($usuario_privilegio_id);
    $puede_acceder_fotos_lote_edit = usuario_puede_acceder_fotos_lote_edit($usuario_privilegio_id);
    $puede_acceder_renovar_empeno = usuario_puede_acceder_permiso_accion_por_nombre($usuario_privilegio_id, $id_padre_listar, 'renovar_empeno');
    $puede_acceder_renovar_empeno_eliminar = usuario_puede_acceder_permiso_accion_por_nombre($usuario_privilegio_id, $id_padre_listar, 'renovar_empeno_eliminar');
    $puede_acceder_renovar_empeno_recuperar = usuario_puede_acceder_permiso_accion_por_nombre($usuario_privilegio_id, $id_padre_listar, 'renovar_empeno_recuperar');    

    if($usuario_root == "true"){

        if($typ_item == "main" ){
            $id_action_user = "30";
            $texto_action_user = $main_requiere_id
                ? "Root accede a la ficha de ".$itemname
                : "Root accede a la lista de ".$itemname;
            registrar_accion_usuario($usuario_id, $id_action_user, $texto_action_user, $usuario_sucursal, $id_type_Item, $url_completa);
            if ($main_requiere_id) {
                $id = verificarVariable('id', 'login.php');
            }
        }elseif($typ_item == "listar"){
            $id_action_user = "28";
            $texto_action_user = "Root accede a la lista de ".$itemname;
            registrar_accion_usuario($usuario_id, $id_action_user, $texto_action_user, $usuario_sucursal, $id_type_Item, $url_completa);
        }elseif($typ_item == "unique"){
            $id_action_user = "30";
            $texto_action_user = "Root accede a la página ".$itemname;
            registrar_accion_usuario($usuario_id, $id_action_user, $texto_action_user, $usuario_sucursal, $id_type_Item, $url_completa);
        }elseif($typ_item == "editar"){
            $id_action_user = "26";
            $texto_action_user = "Root accede a la edicion de ".$itemname;
            registrar_accion_usuario($usuario_id, $id_action_user, $texto_action_user, $usuario_sucursal, $id_type_Item, $url_completa);
            $id = verificarVariable('id', 'login.php');
        }elseif($typ_item == "crear"){
            $id_action_user = "25";
            $texto_action_user = "Root accede a la creacion de ".$itemname;
            registrar_accion_usuario($usuario_id, $id_action_user, $texto_action_user, $usuario_sucursal, $id_type_Item, $url_completa);
        }elseif($typ_item == "blank_page"){
            $id_action_user = "30";
            $texto_action_user = "Root accede a la pagina de ".$itemname;
            registrar_accion_usuario($usuario_id, $id_action_user, $texto_action_user, $usuario_sucursal, $id_type_Item, $url_completa);
        }

        

    }else{

        if(usuario_puede_acceder_item($usuario_privilegio_id, $id_type_Item)){

            if($typ_item == "main" ){
                $id_action_user = "30";
                $texto_action_user = $main_requiere_id
                    ? "$usuario accede a la ficha de ".$itemname
                    : "$usuario accede a la lista de ".$itemname;
                registrar_accion_usuario($usuario_id, $id_action_user, $texto_action_user, $usuario_sucursal, $id_type_Item, $url_completa);
                if ($main_requiere_id) {
                    $id = verificarVariable('id', 'login.php');
                }
            }elseif($typ_item == "listar"){
                $id_action_user = "28";
                $texto_action_user = "$usuario accede a la lista de ".$itemname;
                registrar_accion_usuario($usuario_id, $id_action_user, $texto_action_user, $usuario_sucursal, $id_type_Item, $url_completa);
            }elseif($typ_item == "unique"){
                $id_action_user = "30";
                $texto_action_user = "$usuario accede a la página ".$itemname;
                registrar_accion_usuario($usuario_id, $id_action_user, $texto_action_user, $usuario_sucursal, $id_type_Item, $url_completa);
            }elseif($typ_item == "editar"){
                $id_action_user = "26";
                $texto_action_user = "$usuario accede a la edicion de ".$itemname;
                registrar_accion_usuario($usuario_id, $id_action_user, $texto_action_user, $usuario_sucursal, $id_type_Item, $url_completa);
                $id = verificarVariable('id', 'login.php');
            }elseif($typ_item == "crear"){
                $id_action_user = "25";
                $texto_action_user = "$usuario accede a la creacion de ".$itemname;
                registrar_accion_usuario($usuario_id, $id_action_user, $texto_action_user, $usuario_sucursal, $id_type_Item, $url_completa);
            }
            elseif($typ_item == "blank_page"){
                $id_action_user = "30";
                $texto_action_user = "Root accede a la pagina de ".$itemname;
                registrar_accion_usuario($usuario_id, $id_action_user, $texto_action_user, $usuario_sucursal, $id_type_Item, $url_completa);
            }

        }else{

            $id_action_user = "29";
            $texto_action_user = "Usuario $usuario no tiene acceso a ".$itemname;
            registrar_accion_usuario($usuario_id, $id_action_user, $texto_action_user, $usuario_sucursal, $id_type_Item, $url_completa);
            header('Location: ' . $dashboard_redirect_url . '?error=no tienes acceso a este item');
            exit();
            
        }

    }

}



