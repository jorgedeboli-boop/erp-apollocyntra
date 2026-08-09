<?php
/**
 * Debug temporal para ver la renderización de DataTables
 */

require_once '../../../include/session.php';
require_once '../../../include/functions.php';

echo "<h2>Debug de Renderización de DataTables</h2>";

try {
    // Conectar BD
    $conexion = conectar_bd();
    
    // Consulta con JOINs para obtener privilegio y sucursal
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
            u.sucursal_usuario,
            COALESCE(p.nombre_privilegio, 'Sin privilegio') as nombre_privilegio,
            COALESCE(s.nombre_sucursal, 'Sin sucursal') as nombre_sucursal
        FROM usuarios u
        LEFT JOIN privilegios_usuarios p ON u.privilegio_usuario = p.id_privilegios
        LEFT JOIN sucursal s ON u.sucursal_usuario = s.id_sucursal
        ORDER BY u.id_usuario ASC
        LIMIT 2
    ";
    
    echo "<p><strong>Query:</strong> <code>" . htmlspecialchars($query) . "</code></p>";
    
    $result = mysqli_query($conexion, $query);
    
    if (!$result) {
        echo "<p>❌ Error en consulta: " . mysqli_error($conexion) . "</p>";
        exit;
    }
    
    echo "<p>✅ Consulta ejecutada correctamente</p>";
    
    // Simular la estructura que enviamos
    while ($row = mysqli_fetch_assoc($result)) {
        echo "<hr>";
        echo "<h4>Usuario ID: " . $row['id_usuario'] . "</h4>";
        
        $data_structure = [
            $row['id_usuario'], // ID
            [
                'full_name' => $row['nombre_usuario'] . ' ' . $row['apellido_usuario'],
                'username' => $row['usuario']
            ], // Nombre completo + username
            $row['ultimo_acceso'] && $row['ultimo_acceso'] !== '0000-00-00 00:00:00' ? date('d/m/Y H:i', strtotime($row['ultimo_acceso'])) : '---------', // Última conexión
            $row['nombre_privilegio'], // Jerarquía (privilegio)
            $row['nombre_sucursal'], // Sucursal
            $row['estado_usuario'] === 'true' ? 'Habilitado' : 'Sin acceso', // Estado formateado
            [
                'id' => $row['id_usuario'],
                'full_name' => $row['nombre_usuario'] . ' ' . $row['apellido_usuario'],
                'email' => $row['email'],
                'role' => $row['nombre_privilegio'],
                'status' => $row['estado_usuario']
            ]
        ];
        
        echo "<p><strong>Estructura completa:</strong></p>";
        echo "<pre>" . print_r($data_structure, true) . "</pre>";
        
        echo "<p><strong>Columna 0 (ID):</strong> " . $data_structure[0] . "</p>";
        echo "<p><strong>Columna 1 (User):</strong></p>";
        echo "<pre>" . print_r($data_structure[1], true) . "</pre>";
        echo "<p><strong>Columna 2 (Última conexión):</strong> " . $data_structure[2] . "</p>";
        echo "<p><strong>Columna 3 (Jerarquía):</strong> " . $data_structure[3] . "</p>";
        echo "<p><strong>Columna 4 (Sucursal):</strong> " . $data_structure[4] . "</p>";
        echo "<p><strong>Columna 5 (Estado):</strong> " . $data_structure[5] . "</p>";
        echo "<p><strong>Columna 6 (Actions):</strong></p>";
        echo "<pre>" . print_r($data_structure[6], true) . "</pre>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
}

mysqli_close($conexion);
?>
