<?php
/**
 * Debug para ver qué devuelve load_list.php
 */

require_once '../../../include/session.php';
require_once '../../../include/functions.php';

echo "<h2>Debug de load_list.php</h2>";

// Verificar permisos primero
echo "<h3>Verificando permisos:</h3>";
echo "<p>Usuario ID: " . $_SESSION['usuario_id'] . "</p>";
echo "<p>Puede acceder a usuarios: " . (puede_acceder_a('usuarios') ? 'SÍ' : 'NO') . "</p>";

if (!puede_acceder_a('usuarios')) {
    echo "<p>❌ No tiene permisos para ver usuarios</p>";
    exit;
}

echo "<p>✅ Tiene permisos para ver usuarios</p>";

// Verificar conexión a BD
echo "<h3>Verificando conexión a BD:</h3>";
try {
    $conexion = conectar_bd();
    echo "<p>✅ Conexión exitosa</p>";
} catch (Exception $e) {
    echo "<p>❌ Error de conexión: " . $e->getMessage() . "</p>";
    exit;
}

// Verificar si la tabla usuarios existe
echo "<h3>Verificando tabla usuarios:</h3>";
$result = mysqli_query($conexion, "SHOW TABLES LIKE 'usuarios'");
if (mysqli_num_rows($result) > 0) {
    echo "<p>✅ Tabla 'usuarios' existe</p>";
} else {
    echo "<p>❌ Tabla 'usuarios' NO existe</p>";
    exit;
}

// Contar usuarios
echo "<h3>Contando usuarios:</h3>";
$count_query = "SELECT COUNT(*) as total FROM usuarios";
$count_result = mysqli_query($conexion, $count_query);
$total_records = mysqli_fetch_assoc($count_result)['total'];
echo "<p>📊 Total de usuarios: $total_records</p>";

if ($total_records == 0) {
    echo "<p>❌ No hay usuarios en la base de datos</p>";
    exit;
}

// Probar la consulta principal
echo "<h3>Probando consulta principal:</h3>";
$query = "SELECT * FROM usuarios ORDER BY id_usuario ASC LIMIT 3";
echo "<p>Query: <code>$query</code></p>";

$result = mysqli_query($conexion, $query);

if (!$result) {
    echo "<p>❌ Error en consulta: " . mysqli_error($conexion) . "</p>";
    exit;
}

echo "<p>✅ Consulta ejecutada correctamente</p>";

// Mostrar datos de ejemplo
echo "<h3>Datos de ejemplo:</h3>";
echo "<table border='1'>";
echo "<tr><th>ID</th><th>Usuario</th><th>Nombre</th><th>Apellido</th><th>Email</th><th>Estado</th></tr>";
while ($row = mysqli_fetch_assoc($result)) {
    echo "<tr>";
    echo "<td>{$row['id_usuario']}</td>";
    echo "<td>{$row['usuario']}</td>";
    echo "<td>{$row['nombre_usuario']}</td>";
    echo "<td>{$row['apellido_usuario']}</td>";
    echo "<td>{$row['email']}</td>";
    echo "<td>{$row['estado_usuario']}</td>";
    echo "</tr>";
}
echo "</table>";

// Ahora probar la generación de datos para DataTables
echo "<h3>Generando datos para DataTables:</h3>";

// Resetear el resultado
mysqli_data_seek($result, 0);

$data = [];
while ($row = mysqli_fetch_assoc($result)) {
    $iniciales = strtoupper(substr($row['nombre_usuario'], 0, 1) . substr($row['apellido_usuario'], 0, 1));
    $estado_num = $row['estado_usuario'] === 'true' ? 2 : 3;
    
    $data[] = [
        '', // Columna de control (vacía)
        $row['id_usuario'],
        $row['nombre_usuario'] . ' ' . $row['apellido_usuario'],
        $row['email'],
        'Privilegio ' . $row['privilegio_usuario'],
        'Basic',
        $estado_num,
        [
            'id' => $row['id_usuario'],
            'full_name' => $row['nombre_usuario'] . ' ' . $row['apellido_usuario'],
            'email' => $row['email'],
            'role' => 'Privilegio ' . $row['privilegio_usuario'],
            'status' => $row['estado_usuario']
        ]
    ];
}

echo "<p>✅ Datos generados: " . count($data) . " usuarios</p>";

// Generar la respuesta JSON
$response = [
    'draw' => 1,
    'recordsTotal' => count($data),
    'recordsFiltered' => count($data),
    'data' => $data
];

echo "<h3>Respuesta JSON generada:</h3>";
echo "<pre>" . json_encode($response, JSON_PRETTY_PRINT) . "</pre>";

// Verificar si es JSON válido
$json_test = json_encode($response);
if ($json_test === false) {
    echo "<p>❌ Error generando JSON: " . json_last_error_msg() . "</p>";
} else {
    echo "<p>✅ JSON generado correctamente</p>";
}

mysqli_close($conexion);
?>
