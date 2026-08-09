<?php
/**
 * Debug temporal para ver qué datos recibimos
 */

require_once '../../../include/session.php';
require_once '../../../include/functions.php';

echo "<h2>Debug de Datos de Usuarios</h2>";

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
        LIMIT 3
    ";
    
    echo "<p><strong>Query:</strong> <code>" . htmlspecialchars($query) . "</code></p>";
    
    $result = mysqli_query($conexion, $query);
    
    if (!$result) {
        echo "<p>❌ Error en consulta: " . mysqli_error($conexion) . "</p>";
        exit;
    }
    
    echo "<p>✅ Consulta ejecutada correctamente</p>";
    
    // Mostrar estructura de datos
    echo "<h3>Estructura de datos que enviamos:</h3>";
    
    while ($row = mysqli_fetch_assoc($result)) {
        echo "<hr>";
        echo "<h4>Usuario ID: " . $row['id_usuario'] . "</h4>";
        
        echo "<p><strong>Datos de la BD:</strong></p>";
        echo "<ul>";
        echo "<li><strong>usuario:</strong> '" . $row['usuario'] . "'</li>";
        echo "<li><strong>nombre_usuario:</strong> '" . $row['nombre_usuario'] . "'</li>";
        echo "<li><strong>apellido_usuario:</strong> '" . $row['apellido_usuario'] . "'</li>";
        echo "<li><strong>email:</strong> '" . $row['email'] . "'</li>";
        echo "<li><strong>estado_usuario:</strong> '" . $row['estado_usuario'] . "'</li>";
        echo "<li><strong>privilegio_usuario:</strong> '" . $row['privilegio_usuario'] . "'</li>";
        echo "<li><strong>ultimo_acceso:</strong> '" . $row['ultimo_acceso'] . "'</li>";
        echo "<li><strong>sucursal_usuario:</strong> '" . $row['sucursal_usuario'] . "'</li>";
        echo "<li><strong>nombre_privilegio:</strong> '" . $row['nombre_privilegio'] . "'</li>";
        echo "<li><strong>nombre_sucursal:</strong> '" . $row['nombre_sucursal'] . "'</li>";
        echo "</ul>";
        
        // Simular la estructura que enviamos
        $data_structure = [
            '', // Control
            $row['id_usuario'], // ID
            [
                'full_name' => $row['nombre_usuario'] . ' ' . $row['apellido_usuario'],
                'username' => $row['usuario'] // Username de la tabla usuarios
            ], // Nombre completo + username
            $row['ultimo_acceso'] ? date('d/m/Y H:i', strtotime($row['ultimo_acceso'])) : 'Nunca', // Última conexión
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
        
        echo "<p><strong>Estructura que enviamos:</strong></p>";
        echo "<pre>" . print_r($data_structure, true) . "</pre>";
        
        echo "<p><strong>Columna 2 (User):</strong></p>";
        echo "<pre>" . print_r($data_structure[2], true) . "</pre>";
        
        echo "<p><strong>Username específico:</strong> '" . $data_structure[2]['username'] . "'</p>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
}

mysqli_close($conexion);
?>
