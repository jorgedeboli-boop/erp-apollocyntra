<?php
// Este archivo es incluido desde insertar_lote.php
// La variable $conexion ya está disponible desde insertar_lote.php

// Obtener id_cliente del POST
$id_cliente = $_POST['id_cliente'] ?? 'false';

try {
    // Obtener datos del formulario
    $sucursal_lote = intval($_POST['sucursal_lote']);
    $tipo_identificacion = $_POST['tipo_identificacion'] ?? '';
    $identificacion = $_POST['identificacion'] ?? '';
    $nacionalidad = $_POST['nacionalidad'] ?? '';
    $nombre_nacionalidad = obtener_nombre_nacionalidad($nacionalidad);
    if ($nombre_nacionalidad === false) {
        throw new Exception("Error al obtener el nombre de la nacionalidad");
    }
    $nombre = $_POST['nombre'] ?? '';
    $apellido = $_POST['apellido'] ?? '';
    $f_nacimiento = $_POST['f_nacimiento'] ?? '';
    $sexo = $_POST['sexo'] ?? '';
    $f_vencimiento = $_POST['f_vencimiento'] ?? '';
    $telefono = $_POST['telefono'] ?? '';
    $email = $_POST['email'] ?? '';
    
    // Datos de dirección
    $direccion = $_POST['direccion'] ?? '';
    $pais = $_POST['pais'] ?? '';
    $nombre_pais = obtenerTextoPais($conexion, $pais);
    $c_provincia = $_POST['c_provincia'] ?? '';
    $nombre_provincia = obtenerTextoProvincia($conexion, $c_provincia);
    $c_poblacion = $_POST['c_poblacion'] ?? '';
    $nombre_poblacion = obtenerTextoPoblacion($conexion, $c_poblacion);
    $codigo_postal = $_POST['codigo_postal'] ?? '';
    
    if ($id_cliente === 'false') {
        // ===== INSERTAR NUEVO CLIENTE =====
        
        // 1. Insertar en tabla clientes
        $query_cliente = "INSERT INTO clientes (
            tipo_identificacion_id,
            identificacion,
            nacionalidad,
            nacionalidad_id,
            nombre,
            apellido,
            telefono,
            sucursal,
            creado_por,
            f_alta
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, CURDATE())";
        
        $stmt_cliente = mysqli_prepare($conexion, $query_cliente);
        if (!$stmt_cliente) {
            throw new Exception("Error al preparar consulta de cliente: " . mysqli_error($conexion));
        }
        
        mysqli_stmt_bind_param($stmt_cliente, 'issssssii', 
            $tipo_identificacion,
            $identificacion,
            $nombre_nacionalidad,
            $nacionalidad,
            $nombre,
            $apellido,
            $telefono,
            $sucursal_lote,
            $usuario_id
        );

        if (!mysqli_stmt_execute($stmt_cliente)) {
            throw new Exception("Error al insertar cliente: " . mysqli_stmt_error($stmt_cliente));
        }

        $nuevo_id_cliente = mysqli_insert_id($conexion);
        mysqli_stmt_close($stmt_cliente);

        $texto_action_user = "$usuario creó el cliente Nº '$nuevo_id_cliente'";
        $id_action_user = "34";
        $relItemAction = $_SESSION['relItemAction'];
        registrar_accion_usuario($usuario_id, $id_action_user, $texto_action_user, $usuario_sucursal, $relItemAction);
        
        // 2. Insertar en tabla direcciones
        $resultado_direccion = insertarDireccionConConexion(
            $conexion,
            $nuevo_id_cliente,
            'clientes',
            $direccion,
            $nombre_provincia, // c_provincia (texto)
            $nombre_poblacion, // c_poblacion (texto)
            $nombre_pais, // c_pais (texto)
            $codigo_postal,
            $c_provincia, // rel_id_provincia
            $pais, // rel_id_pais
            $c_poblacion  // rel_id_poblacion
        );
        
        if (!$resultado_direccion) {
            throw new Exception("Error al insertar dirección del cliente");
        }
        
        // 3. Insertar en tabla datos_clientes
        $query_datos = "INSERT INTO datos_clientes (
            rel_id_cliente,
            f_nacimiento,
            email,
            sexo,
            f_vencimiento
        ) VALUES (?, ?, ?, ?, ?)";
        
        $stmt_datos = mysqli_prepare($conexion, $query_datos);
        if (!$stmt_datos) {
            throw new Exception("Error al preparar consulta de datos_clientes: " . mysqli_error($conexion));
        }
        
        mysqli_stmt_bind_param($stmt_datos, 'issss',
            $nuevo_id_cliente,
            $f_nacimiento,
            $email,
            $sexo,
            $f_vencimiento
        );
        
        if (!mysqli_stmt_execute($stmt_datos)) {
            throw new Exception("Error al insertar datos_clientes: " . mysqli_stmt_error($stmt_datos));
        }
        
        mysqli_stmt_close($stmt_datos);
        
        $id_cliente_procesado = $nuevo_id_cliente;
        
    } else {
        // ===== ACTUALIZAR CLIENTE EXISTENTE =====
        
        $id_cliente_numerico = intval($id_cliente);
        
        // 1. Actualizar tabla clientes
        $query_update_cliente = "UPDATE clientes SET
            tipo_identificacion_id = ?,
            identificacion = ?,
            nacionalidad = ?,
            nacionalidad_id = ?,
            nombre = ?,
            apellido = ?,
            telefono = ?
        WHERE id_cliente = ?";
        
        $stmt_update_cliente = mysqli_prepare($conexion, $query_update_cliente);
        if (!$stmt_update_cliente) {
            throw new Exception("Error al preparar actualización de cliente: " . mysqli_error($conexion));
        }
        
        mysqli_stmt_bind_param($stmt_update_cliente, 'issssssi',
            $tipo_identificacion,
            $identificacion,
            $nombre_nacionalidad,
            $nacionalidad,
            $nombre,
            $apellido,
            $telefono,
            $id_cliente_numerico
        );
        
        if (!mysqli_stmt_execute($stmt_update_cliente)) {
            throw new Exception("Error al actualizar cliente: " . mysqli_stmt_error($stmt_update_cliente));
        }
        
        mysqli_stmt_close($stmt_update_cliente);
        
        // 2. Verificar si existe dirección del cliente
        $query_check_direccion = "SELECT id_direccion FROM direcciones WHERE rel_id_item = ? AND type_direccion = 'clientes' LIMIT 1";
        $stmt_check_dir = mysqli_prepare($conexion, $query_check_direccion);
        mysqli_stmt_bind_param($stmt_check_dir, 'i', $id_cliente_numerico);
        mysqli_stmt_execute($stmt_check_dir);
        $result_check_dir = mysqli_stmt_get_result($stmt_check_dir);
        
        if (mysqli_num_rows($result_check_dir) > 0) {
            // Dirección existe, actualizar
            mysqli_stmt_close($stmt_check_dir);
            
            $resultado_direccion = actualizarDireccionConConexion(
                $conexion,
                $id_cliente_numerico,
                'clientes',
                $direccion,
                $nombre_provincia, // c_provincia (texto)
                $nombre_poblacion, // c_poblacion (texto)
                $nombre_pais, // c_pais (texto)
                $codigo_postal,
                $c_provincia, // rel_id_provincia
                $pais, // rel_id_pais
                $c_poblacion  // rel_id_poblacion
            );
            
            if (!$resultado_direccion) {
                throw new Exception("Error al actualizar dirección del cliente");
            }
        } else {
            // Dirección NO existe, insertar
            mysqli_stmt_close($stmt_check_dir);
            
            $resultado_direccion = insertarDireccionConConexion(
                $conexion,
                $id_cliente_numerico,
                'clientes',
                $direccion,
                $nombre_provincia, // c_provincia (texto)
                $nombre_poblacion, // c_poblacion (texto)
                $nombre_pais, // c_pais (texto)
                $codigo_postal,
                $c_provincia, // rel_id_provincia
                $pais, // rel_id_pais
                $c_poblacion  // rel_id_poblacion
            );
            
            if (!$resultado_direccion) {
                throw new Exception("Error al insertar dirección del cliente");
            }
        }
        
        // 3. Verificar si existe datos_clientes del cliente
        $query_check_datos = "SELECT id_datos_cliente FROM datos_clientes WHERE rel_id_cliente = ? AND type_direccion = 'clientes' LIMIT 1";
        $stmt_check_datos = mysqli_prepare($conexion, $query_check_datos);
        mysqli_stmt_bind_param($stmt_check_datos, 'i', $id_cliente_numerico);
        mysqli_stmt_execute($stmt_check_datos);
        $result_check_datos = mysqli_stmt_get_result($stmt_check_datos);
        
        if (mysqli_num_rows($result_check_datos) > 0) {
            // Datos existen, actualizar
            mysqli_stmt_close($stmt_check_datos);
            
            $query_update_datos = "UPDATE datos_clientes SET
                f_nacimiento = ?,
                email = ?,
                sexo = ?,
                f_vencimiento = ?
            WHERE rel_id_cliente = ?";
            
            $stmt_update_datos = mysqli_prepare($conexion, $query_update_datos);
            if (!$stmt_update_datos) {
                throw new Exception("Error al preparar actualización de datos_clientes: " . mysqli_error($conexion));
            }
            
            mysqli_stmt_bind_param($stmt_update_datos, 'ssssi',
                $f_nacimiento,
                $email,
                $sexo,
                $f_vencimiento,
                $id_cliente_numerico
            );
            
            if (!mysqli_stmt_execute($stmt_update_datos)) {
                throw new Exception("Error al actualizar datos_clientes: " . mysqli_stmt_error($stmt_update_datos));
            }
            
            mysqli_stmt_close($stmt_update_datos);
        } else {
            // Datos NO existen, insertar
            mysqli_stmt_close($stmt_check_datos);
            
            $query_datos = "INSERT INTO datos_clientes (
                rel_id_cliente,
                f_nacimiento,
                email,
                sexo,
                f_vencimiento
            ) VALUES (?, ?, ?, ?, ?)";
            
            $stmt_datos = mysqli_prepare($conexion, $query_datos);
            if (!$stmt_datos) {
                throw new Exception("Error al preparar consulta de datos_clientes: " . mysqli_error($conexion));
            }
            
            mysqli_stmt_bind_param($stmt_datos, 'issss',
                $id_cliente_numerico,
                $f_nacimiento,
                $email,
                $sexo,
                $f_vencimiento
            );
            
            if (!mysqli_stmt_execute($stmt_datos)) {
                throw new Exception("Error al insertar datos_clientes: " . mysqli_stmt_error($stmt_datos));
            }
            
            mysqli_stmt_close($stmt_datos);
        }
        
        $id_cliente_procesado = $id_cliente_numerico;
    }
    
    // Guardar resultado en variables para que insertar_lote.php las use
    $resultado_cliente = [
        'success' => true,
        'id_cliente_procesado' => $id_cliente_procesado
    ];
    
} catch (Exception $e) {
    // Guardar error en variable
    $resultado_cliente = [
        'success' => false,
        'error' => $e->getMessage()
    ];
}

