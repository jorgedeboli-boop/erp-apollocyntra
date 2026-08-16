<?php
/**
 * Archivo para cargar la lista de usuarios via AJAX
 * Versión optimizada que funciona correctamente
 */

// Asegurar que no haya salida antes del JSON
ob_clean();

require_once '../../../include/session.php';
require_once '../../../include/functions.php';

header('Content-Type: application/json');

try {
    // Conectar BD
    $conexion = conectar_bd();

    $es_usuario_root = (isset($usuario_root) && $usuario_root === 'true');
    $es_usuario_super_administrador = (isset($usuario_super_administrador) && $usuario_super_administrador === 'true');

    $query = "
        SELECT 
            u.id_usuario, 
            u.usuario, 
            u.nombre_usuario, 
            u.apellido_usuario, 
            u.email, 
            u.estado_usuario, 
            u.privilegio_usuario,
            u.ultimo_acceso,
            COALESCE(p.nombre_privilegio, 'Sin privilegio') as nombre_privilegio,
            COALESCE(p.color_label_privilegio, 'secondary') as color_label_privilegio
        FROM usuarios u
        LEFT JOIN privilegios_usuarios p ON u.privilegio_usuario = p.id_privilegios
    ";

    $where = [];
    if (!$es_usuario_root) {
        $where[] = "u.usuario_root = 'false'";
    }
    if (!$es_usuario_super_administrador) {
        $where[] = "u.super_admin = 'false'";
    }
    if (!empty($where)) {
        $query .= ' WHERE ' . implode(' AND ', $where);
    }

    $query .= " ORDER BY u.id_usuario ASC";
    $result = mysqli_query($conexion, $query);
    
    if (!$result) {
        throw new Exception("Error en consulta: " . mysqli_error($conexion));
    }
    
    // Datos simples
    $data = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $estado_num = $row['estado_usuario'] === 'true' ? 2 : 3;
        
        $data[] = [
            $row['id_usuario'], // ID
            [
                'full_name' => $row['nombre_usuario'] . ' ' . $row['apellido_usuario'],
                'username' => $row['usuario'] // Username de la tabla usuarios
            ], // Nombre completo + username
            $row['ultimo_acceso'] && $row['ultimo_acceso'] !== '0000-00-00 00:00:00' ? date('d/m/Y H:i', strtotime($row['ultimo_acceso'])) : '---------', // Última conexión
            [
                'nombre' => $row['nombre_privilegio'],
                'color' => $row['color_label_privilegio']
            ], // Jerarquía (privilegio con color)
            $row['estado_usuario'] === 'true' ? 'Habilitado' : 'Sin acceso' // Estado formateado
        ];
    }
    
    // Respuesta
    $response = [
        'draw' => isset($_POST['draw']) ? (int)$_POST['draw'] : 1,
        'recordsTotal' => count($data),
        'recordsFiltered' => count($data),
        'data' => $data
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage(),
        'draw' => isset($_POST['draw']) ? (int)$_POST['draw'] : 1,
        'recordsTotal' => 0,
        'recordsFiltered' => 0,
        'data' => []
    ]);
}

mysqli_close($conexion);
?>
