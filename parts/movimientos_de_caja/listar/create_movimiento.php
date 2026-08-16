<?php
/**
 * Archivo para crear un nuevo movimiento de caja
 */

require_once '../../../include/session.php';

header('Content-Type: application/json');

try {
    // Verificar que se reciban los parámetros necesarios
    $fechaApunte = trim($_POST['fecha_apunte']);
    $grupos = trim($_POST['grupos']);
    $concepto = trim($_POST['concepto']);
    $salida = floatval($_POST['salida']);
    $entrada = floatval($_POST['entrada']);
    
    // Validaciones
    if (empty($fechaApunte)) {
        throw new Exception("La fecha es requerida");
    }
    
    if (empty($grupos)) {
        throw new Exception("El grupo es requerido");
    }
    
    if (empty($concepto)) {
        throw new Exception("El concepto es requerido");
    }
    
    if ($salida === 0.0 && $entrada === 0.0) {
        throw new Exception("Debe ingresar un valor en Salida o Entrada");
    }
    
    // Conectar BD
    $conexion = conectar_bd();
    
    $tablasCaja = [];
    $resultTablas = mysqli_query($conexion, "SHOW TABLES LIKE 'movimientos_de_caja_%'");
    if ($resultTablas) {
        while ($rowTabla = mysqli_fetch_row($resultTablas)) {
            if (preg_match('/^movimientos_de_caja_(\d+)$/', $rowTabla[0], $m)) {
                $tablasCaja[] = [
                    'name' => $rowTabla[0],
                    'id' => (int) $m[1],
                ];
            }
        }
    }
    usort($tablasCaja, function ($a, $b) {
        return $a['id'] <=> $b['id'];
    });
    $tableName = $tablasCaja[0]['name'] ?? '';
    if ($tableName === '') {
        throw new Exception("Tabla de movimientos no encontrada");
    }
    
    // Obtener el ID del usuario de la sesión
    $usuario = isset($usuario_id) ? $usuario_id : 'Sistema';
    
    // Insertar el nuevo movimiento
    $query = "INSERT INTO $tableName (fecha_apunte, grupos, concepto, salida, entrada, usuario) 
              VALUES (?, ?, ?, ?, ?, ?)";
    
    $stmt = mysqli_prepare($conexion, $query);
    mysqli_stmt_bind_param($stmt, 'sssdds', $fechaApunte, $grupos, $concepto, $salida, $entrada, $usuario);
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Error al crear el movimiento: " . mysqli_error($conexion));
    }
    
    $nuevoId = mysqli_insert_id($conexion);
    
    mysqli_stmt_close($stmt);
    mysqli_close($conexion);
    
    // Respuesta exitosa
    echo json_encode([
        'success' => true,
        'message' => 'Movimiento creado correctamente',
        'id_movimiento' => $nuevoId
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>

