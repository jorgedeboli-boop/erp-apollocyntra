<?php
/**
 * Duplicar Gasto - AJAX Handler
 */

// Incluir configuración de base de datos
require_once '../../../include/database.php';

// Configurar headers para JSON
header('Content-Type: application/json');

// Verificar que sea una petición POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

// Obtener datos del POST
$input = json_decode(file_get_contents('php://input'), true);
$gasto_id = isset($input['gasto_id']) ? intval($input['gasto_id']) : 0;

if ($gasto_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID de gasto inválido']);
    exit;
}

try {
    // Obtener los datos del gasto original
    $stmt = $pdo->prepare("
        SELECT 
            descripcion,
            fecha_gasto,
            empresa_id,
            sucursal_id,
            proveedor_id,
            tipo_gasto_id,
            total,
            estado,
            forma_pago_id,
            observaciones,
            usuario_id,
            fecha_creacion
        FROM gastos 
        WHERE id = ?
    ");
    
    $stmt->execute([$gasto_id]);
    $gasto_original = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$gasto_original) {
        echo json_encode(['success' => false, 'message' => 'Gasto no encontrado']);
        exit;
    }
    
    // Insertar el gasto duplicado
    $stmt = $pdo->prepare("
        INSERT INTO gastos (
            descripcion,
            fecha_gasto,
            empresa_id,
            sucursal_id,
            proveedor_id,
            tipo_gasto_id,
            total,
            estado,
            forma_pago_id,
            observaciones,
            usuario_id,
            fecha_creacion
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    
    $resultado = $stmt->execute([
        $gasto_original['descripcion'] . ' (Copia)',
        $gasto_original['fecha_gasto'],
        $gasto_original['empresa_id'],
        $gasto_original['sucursal_id'],
        $gasto_original['proveedor_id'],
        $gasto_original['tipo_gasto_id'],
        $gasto_original['total'],
        'pendiente', // Estado por defecto para gastos duplicados
        $gasto_original['forma_pago_id'],
        $gasto_original['observaciones'],
        $gasto_original['usuario_id']
    ]);
    
    if ($resultado) {
        $nuevo_gasto_id = $pdo->lastInsertId();
        
        // Log de auditoría
        $stmt_audit = $pdo->prepare("
            INSERT INTO auditoria (
                tabla,
                accion,
                registro_id,
                usuario_id,
                datos_anteriores,
                datos_nuevos,
                fecha_creacion
            ) VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $datos_anteriores = json_encode($gasto_original);
        $datos_nuevos = json_encode([
            'gasto_duplicado_id' => $nuevo_gasto_id,
            'gasto_original_id' => $gasto_id
        ]);
        
        $stmt_audit->execute([
            'gastos',
            'duplicar',
            $nuevo_gasto_id,
            $gasto_original['usuario_id'],
            $datos_anteriores,
            $datos_nuevos
        ]);
        
        echo json_encode([
            'success' => true, 
            'message' => 'Gasto duplicado correctamente',
            'nuevo_gasto_id' => $nuevo_gasto_id
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al duplicar el gasto']);
    }
    
} catch (PDOException $e) {
    error_log("Error al duplicar gasto: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error de base de datos']);
} catch (Exception $e) {
    error_log("Error general al duplicar gasto: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
}
?>
