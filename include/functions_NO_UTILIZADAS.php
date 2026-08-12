<?php

// Función para generar un select de tipo de identificación asd
function generarSelectTipoIdentificacion($tipo_seleccionado = '', $name = 'tipo_identificacion', $id = 'tipo_identificacion', $required = false, $formato = 'mayusculas', $id_cliente = 0) {
    $conexion = conectar_bd();
    
    if (!$conexion) {
        echo "<select class='form-select select2' id='{$id}' name='{$name}'" . ($required ? ' required' : '') . ">";
        echo "<option value=''>Error de conexión</option>";
        echo "</select>";
        return;
    }
    
    // Función interna para actualizar tipo_identificacion_id si está vacío
    function actualizarTipoIdentificacionId($conexion, $id_cliente) {
        if (!$id_cliente) return;
        
        // Obtener datos del cliente
        $query_cliente = "SELECT tipo_identificacion, tipo_identificacion_id FROM clientes WHERE id_cliente = ?";
        $stmt_cliente = mysqli_prepare($conexion, $query_cliente);
        if (!$stmt_cliente) return;
        
        mysqli_stmt_bind_param($stmt_cliente, 'i', $id_cliente);
        mysqli_stmt_execute($stmt_cliente);
        $result_cliente = mysqli_stmt_get_result($stmt_cliente);
        
        if ($result_cliente && mysqli_num_rows($result_cliente) > 0) {
            $cliente = mysqli_fetch_assoc($result_cliente);
            
            // Solo actualizar si tipo_identificacion_id está vacío/null y tipo_identificacion (string) no está vacío
            if (empty($cliente['tipo_identificacion_id']) && !empty($cliente['tipo_identificacion'])) {
                // Mapeo de strings a IDs
                $mapeo_tipos = [
                    'DNI' => 1,
                    'dni' => 1,
                    'NIE' => 2,
                    'nie' => 2,
                    'PASAPORTE' => 3,
                    'pasaporte' => 3,
                    'CIF' => 4,
                    'cif' => 4
                ];
                
                $tipo_string = $cliente['tipo_identificacion'];
                if (isset($mapeo_tipos[$tipo_string])) {
                    $tipo_id = $mapeo_tipos[$tipo_string];
                    
                    // Actualizar el tipo_identificacion_id en la tabla clientes
                    $query_update = "UPDATE clientes SET tipo_identificacion_id = ? WHERE id_cliente = ?";
                    $stmt_update = mysqli_prepare($conexion, $query_update);
                    if ($stmt_update) {
                        mysqli_stmt_bind_param($stmt_update, 'ii', $tipo_id, $id_cliente);
                        mysqli_stmt_execute($stmt_update);
                        mysqli_stmt_close($stmt_update);
                    }
                }
            }
        }
        mysqli_stmt_close($stmt_cliente);
    }
    
    // Actualizar tipo_identificacion_id si es necesario
    if ($id_cliente > 0) {
        actualizarTipoIdentificacionId($conexion, $id_cliente);
    }
    
    if ($formato == 'minusculas') {
        $tipos = [
            'dni' => 'DNI',
            'nie' => 'NIE', 
            'pasaporte' => 'PASAPORTE',
            'cif' => 'CIF'
        ];
    } else {
        $tipos = [
            'DNI' => 'DNI',
            'NIE' => 'NIE', 
            'PASAPORTE' => 'PASAPORTE',
            'CIF' => 'CIF'
        ];
    }
    
    echo "<select class='form-select select2' id='{$id}' name='{$name}'" . ($required ? ' required' : '') . ">";
    echo "<option value=''>Seleccionar tipo</option>";
    
    foreach ($tipos as $valor => $texto) {
        $selected = ($valor == $tipo_seleccionado) ? 'selected' : '';
        echo "<option value='{$valor}' {$selected}>" . htmlspecialchars($texto) . "</option>";
    }
    
    echo "</select>";
    
    mysqli_close($conexion);
}

// Función interna para actualizar nacionalidad_id si está vacío
function actualizarNacionalidadId($conexion, $id_cliente) {
    if (!$id_cliente) return;
    
    // Obtener datos del cliente
    $query_cliente = "SELECT nacionalidad, nacionalidad_id FROM clientes WHERE id_cliente = ?";
    $stmt_cliente = mysqli_prepare($conexion, $query_cliente);
    if (!$stmt_cliente) return;
    
    mysqli_stmt_bind_param($stmt_cliente, 'i', $id_cliente);
    mysqli_stmt_execute($stmt_cliente);
    $result_cliente = mysqli_stmt_get_result($stmt_cliente);
    
    if ($result_cliente && mysqli_num_rows($result_cliente) > 0) {
        $cliente = mysqli_fetch_assoc($result_cliente);
        
        // Solo actualizar si nacionalidad_id está vacío/null y nacionalidad (string) no está vacío
        if (empty($cliente['nacionalidad_id']) && !empty($cliente['nacionalidad'])) {
            // Buscar la nacionalidad por nombre
            $query_nacionalidad = "SELECT id FROM nacionalidades WHERE nombre_nacionalidad = ?";
            $stmt_nacionalidad = mysqli_prepare($conexion, $query_nacionalidad);
            if ($stmt_nacionalidad) {
                mysqli_stmt_bind_param($stmt_nacionalidad, 's', $cliente['nacionalidad']);
                mysqli_stmt_execute($stmt_nacionalidad);
                $result_nacionalidad = mysqli_stmt_get_result($stmt_nacionalidad);
                
                if ($result_nacionalidad && mysqli_num_rows($result_nacionalidad) > 0) {
                    $nacionalidad_data = mysqli_fetch_assoc($result_nacionalidad);
                    $nacionalidad_id = $nacionalidad_data['id'];
                    
                    // Actualizar el nacionalidad_id en la tabla clientes
                    $query_update = "UPDATE clientes SET nacionalidad_id = ? WHERE id_cliente = ?";
                    $stmt_update = mysqli_prepare($conexion, $query_update);
                    if ($stmt_update) {
                        mysqli_stmt_bind_param($stmt_update, 'ii', $nacionalidad_id, $id_cliente);
                        mysqli_stmt_execute($stmt_update);
                        mysqli_stmt_close($stmt_update);
                    }
                }
                mysqli_stmt_close($stmt_nacionalidad);
            }
        }
    }
    mysqli_stmt_close($stmt_cliente);
}

// Actualizar nacionalidad_id si es necesario
if ($id_cliente > 0) {
    actualizarNacionalidadId($conexion, $id_cliente);
}

/**
 * Función para obtener el texto de un país por ID
 */
function obtenerTextoPais($conexion, $id) {
    $query = "SELECT name_spanish FROM countrys WHERE id_country = ?";
    $stmt = mysqli_prepare($conexion, $query);
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    return $row ? $row['name_spanish'] : '';
}

/**
 * Función para obtener el texto de una provincia por ID
 */
function obtenerTextoProvincia($conexion, $id) {
    $query = "SELECT nombreProvince FROM provincias WHERE id_province = ?";
    $stmt = mysqli_prepare($conexion, $query);
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    return $row ? $row['nombreProvince'] : '';
}

/**
 * Función para obtener el texto de una población por ID
 */
function obtenerTextoPoblacion($conexion, $id) {
    $query = "SELECT poblacion FROM poblacion WHERE idpoblacion = ?";
    $stmt = mysqli_prepare($conexion, $query);
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    return $row ? $row['poblacion'] : '';
}

/**
 * Función para obtener el texto de una nacionalidad por ID
 */
function obtenerTextoNacionalidad($conexion, $id) {
    $query = "SELECT name_spanish FROM countrys WHERE id_country = ?";
    $stmt = mysqli_prepare($conexion, $query);
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    return $row ? $row['name_spanish'] : '';
}

/**
 * Función para obtener el texto de una tipo de identificación por ID
 */
function obtenerTextoTipoIdentificacion($conexion, $id) {
    $query = "SELECT nombre_identificacion FROM tipo_identificacion WHERE id_tipo_identificacion = ?";
    $stmt = mysqli_prepare($conexion, $query);
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    return $row ? $row['nombre_identificacion'] : '';
}

/**
 * Función helper para verificar si el usuario puede acceder a una funcionalidad
 */
function puede_acceder_a($funcionalidad) {
    global $usuario_es_admin, $usuario_privilegio_id;
    
    // Los administradores pueden acceder a todo
    if ($usuario_es_admin) {
        return true;
    }
    
    // Definir permisos por funcionalidad
    $permisos = [
        'usuarios' => [1, 2, 3], // Admin, Operador, Encargado
        'reportes' => [1, 2, 3, 4], // Admin, Operador, Encargado, Operador Central
        'contabilidad' => [1, 5], // Admin, Contables
        'auditoria' => [1, 8, 10], // Admin, Auditoria, Auditoria cajas
        'gastos' => [1, 9], // Admin, Gastos
        'lotes' => [1, 6, 7], // Admin, Recepcion lotes, Control lotes
    ];
    
    if (isset($permisos[$funcionalidad])) {
        return in_array($usuario_privilegio_id, $permisos[$funcionalidad]);
    }
    
    return false;
}
