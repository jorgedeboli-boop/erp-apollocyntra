<?php
/**
 * Archivo para obtener el detalle de un arqueo
 */

require_once '../../../include/session.php';

header('Content-Type: application/json');

try {
    // Verificar que se reciban los parámetros necesarios
    if (!isset($_POST['id_fecha_cierre']) || !isset($_POST['id_sucursal'])) {
        throw new Exception("Parámetros incompletos");
    }
    
    $idFechaCierre = (int)$_POST['id_fecha_cierre'];
    $idSucursal = (int)$_POST['id_sucursal'];
    
    // Conectar BD
    $conexion = conectar_bd();
    
    // Nombre de la tabla
    $tableName = "cierre_caja_$idSucursal";
    
    // Verificar si la tabla existe
    $checkTable = mysqli_query($conexion, "SHOW TABLES LIKE '$tableName'");
    if (mysqli_num_rows($checkTable) == 0) {
        throw new Exception("Tabla de cierres no encontrada");
    }
    
    // Obtener los datos del arqueo
    $query = "SELECT * FROM $tableName WHERE id_fecha_cierre = ?";
    
    $stmt = mysqli_prepare($conexion, $query);
    mysqli_stmt_bind_param($stmt, 'i', $idFechaCierre);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($row = mysqli_fetch_assoc($result)) {
        // Preparar la respuesta con todos los datos del arqueo
        echo json_encode([
            'success' => true,
            'arqueo' => [
                'id_fecha_cierre' => $row['id_fecha_cierre'],
                'fecha_cierre' => $row['fecha_cierre'],
                'caja' => floatval($row['caja']),
                'efectivo' => floatval($row['efectivo']),
                'diferencia' => floatval($row['diferencia']),
                // Billetes
                'b500' => intval($row['b500']),
                'b200' => intval($row['b200']),
                'b100' => intval($row['b100']),
                'b50' => intval($row['b50']),
                'b20' => intval($row['b20']),
                'b10' => intval($row['b10']),
                'b5' => intval($row['b5']),
                // Monedas
                'm2' => intval($row['m2']),
                'm1' => floatval($row['m1']),
                // Totales billetes
                't500' => intval($row['t500']),
                't200' => intval($row['t200']),
                't100' => intval($row['t100']),
                't50' => intval($row['t50']),
                't20' => intval($row['t20']),
                't10' => intval($row['t10']),
                't5' => intval($row['t5']),
                't2' => intval($row['t2']),
                't1' => floatval($row['t1']),
                // Céntimos
                '50cent' => intval($row['50cent']),
                '20cent' => intval($row['20cent']),
                '10cent' => intval($row['10cent']),
                '5cent' => intval($row['5cent']),
                '2cent' => intval($row['2cent']),
                '1cent' => intval($row['1cent']),
                // Totales céntimos
                't50cent' => floatval($row['t50cent']),
                't20cent' => floatval($row['t20cent']),
                't10cent' => floatval($row['t10cent']),
                't5cent' => floatval($row['t5cent']),
                't2cent' => floatval($row['t2cent']),
                't1cent' => floatval($row['t1cent'])
            ]
        ]);
    } else {
        throw new Exception("Arqueo no encontrado");
    }
    
    mysqli_stmt_close($stmt);
    mysqli_close($conexion);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>

