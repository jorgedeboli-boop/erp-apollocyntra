<?php
/**
 * Archivo para consultar sucursales
 * Muestra id_sucursal y nombre_sucursal de la tabla sucursal
 * Compatible con PHP 7.0
 */

// Incluir archivos necesarios para la conexión
require_once '../include/session.php';
require_once '../include/functions.php';

// Verificar que el usuario esté autenticado
if (!isset($_SESSION['usuario_id'])) {
    die('Usuario no autenticado');
}

try {
    // Conectar a la base de datos
    $conexion = conectar_bd();
    
    if (!$conexion) {
        throw new Exception("Error al conectar con la base de datos");
    }
    
    // Consulta para obtener sucursales
    $query = "SELECT id_sucursal, nombre_sucursal FROM sucursal ORDER BY id_sucursal ASC";
    
    $resultado = mysqli_query($conexion, $query);
    
    if (!$resultado) {
        throw new Exception("Error en la consulta: " . mysqli_error($conexion));
    }
    
    // Verificar si hay resultados
    if (mysqli_num_rows($resultado) == 0) {
        echo "<p>No se encontraron sucursales en la base de datos.</p>";
    } else {
        echo "<h2>Listado de Sucursales</h2>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<thead>";
        echo "<tr style='background-color: #f8f9fa;'>";
        echo "<th style='padding: 10px; text-align: left;'>ID Sucursal</th>";
        echo "<th style='padding: 10px; text-align: left;'>Nombre Sucursal</th>";
        echo "</tr>";
        echo "</thead>";
        echo "<tbody>";
        
        // Mostrar cada sucursal
        while ($fila = mysqli_fetch_assoc($resultado)) {

            // Crear nombre de la tabla de movimientos de caja
    $nombre_tabla_movimientos = "movimientos_de_caja_" . $fila['id_sucursal'];
    
    // Query para crear la tabla de movimientos de caja
    $query_crear_tabla = "
        CREATE TABLE IF NOT EXISTS `{$nombre_tabla_movimientos}` (
            `id_movimientos` int(9) NOT NULL AUTO_INCREMENT,
            `fecha_apunte` date NOT NULL,
            `grupos` varchar(128) NOT NULL,
            `concepto` text NOT NULL,
            `salida` decimal(12,2) NOT NULL,
            `entrada` decimal(12,2) NOT NULL,
            `acumulado` int(11) NOT NULL,
            `usuario` varchar(128) NOT NULL,
            `acumulado_diario` int(18) NOT NULL,
            `hora_de_apunte` time NOT NULL,
            `apunte_cierre_caja` decimal(7,2) NOT NULL,
            `cierre_caja` ENUM('false', 'true') NOT NULL,
            PRIMARY KEY (`id_movimientos`)
        ) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1
    ";
    
    // Ejecutar la creación de la tabla
    if (!mysqli_query($conexion, $query_crear_tabla)) {
        throw new Exception("Error al crear tabla de movimientos de caja: " . mysqli_error($conexion));
    }

            echo "<tr>";
            echo "<td style='padding: 10px; border: 1px solid #dee2e6;'>" . htmlspecialchars($fila['id_sucursal']) . "</td>";
            echo "<td style='padding: 10px; border: 1px solid #dee2e6;'>" . htmlspecialchars($fila['nombre_sucursal']) . "</td>";
            echo "</tr>";
        }
        
        echo "</tbody>";
        echo "</table>";
        
        // Mostrar total de sucursales
        $total_sucursales = mysqli_num_rows($resultado);
        echo "<p><strong>Total de sucursales: " . $total_sucursales . "</strong></p>";
    }
    
    // Cerrar conexión
    mysqli_close($conexion);
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    
    // Cerrar conexión si está abierta
    if (isset($conexion) && $conexion) {
        mysqli_close($conexion);
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consulta de Sucursales</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background-color: #f5f5f5;
        }
        h2 {
            color: #333;
            border-bottom: 2px solid #007bff;
            padding-bottom: 10px;
        }
        table {
            background-color: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            border-radius: 5px;
            overflow: hidden;
        }
        th {
            background-color: #007bff !important;
            color: white !important;
            font-weight: bold;
        }
        tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        tr:hover {
            background-color: #e9ecef;
        }
        p {
            margin: 20px 0;
            padding: 10px;
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            border-radius: 5px;
            color: #155724;
        }
        .error {
            background-color: #f8d7da !important;
            border-color: #f5c6cb !important;
            color: #721c24 !important;
        }
    </style>
</head>
<body>
    <h1>Generador de Datos - Consulta de Sucursales</h1>
    
    <div style="background-color: white; padding: 20px; border-radius: 5px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
        <?php
        // El contenido PHP ya está arriba, aquí solo mostramos la estructura HTML
        ?>
    </div>
    
    <div style="margin-top: 20px;">
        <a href="../index.php" style="color: #007bff; text-decoration: none;">← Volver al inicio</a>
    </div>
</body>
</html>
