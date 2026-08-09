<?php
require_once '../../../include/session.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit();
}

$id_usuario = isset($_POST['id_usuario']) ? (int) $_POST['id_usuario'] : 0;
$id_jerarquia = isset($_POST['id_jerarquia']) ? (int) $_POST['id_jerarquia'] : 0;
$element_id = isset($_POST['element_id']) ? (int) $_POST['element_id'] : 0;
$estado = isset($_POST['estado']) ? trim((string) $_POST['estado']) : '';

if ($id_usuario <= 0 || $id_jerarquia <= 0 || $element_id <= 0 || ($estado !== 'activo' && $estado !== 'no_activo')) {
    echo json_encode(['success' => false, 'error' => 'Datos incompletos']);
    exit();
}

require_once '../../../include/functions.php';

try {
    $conexion = conectar_bd();
    if (!$conexion) {
        throw new Exception('No se pudo conectar a la base de datos');
    }

    $stmtCheckJerarquia = mysqli_prepare(
        $conexion,
        'SELECT COUNT(*) AS total FROM elementsRelLevelsUsers WHERE rel_id_element = ? AND relIdUsersLevel = ?'
    );
    if (!$stmtCheckJerarquia) {
        throw new Exception('Error al preparar consulta de jerarquía: ' . mysqli_error($conexion));
    }

    mysqli_stmt_bind_param($stmtCheckJerarquia, 'ii', $element_id, $id_jerarquia);
    mysqli_stmt_execute($stmtCheckJerarquia);
    mysqli_stmt_store_result($stmtCheckJerarquia);
    mysqli_stmt_bind_result($stmtCheckJerarquia, $totalJerarquia);
    mysqli_stmt_fetch($stmtCheckJerarquia);
    mysqli_stmt_close($stmtCheckJerarquia);

    if ((int) $totalJerarquia > 0) {
        mysqli_close($conexion);
        echo json_encode([
            'success' => false,
            'error' => 'Este elemento pertenece a la jerarquía y no se puede modificar aquí',
        ]);
        exit();
    }

    if ($estado === 'activo') {
        $stmtCheck = mysqli_prepare(
            $conexion,
            'SELECT COUNT(*) AS total FROM elementsRelUsers WHERE rel_id_element = ? AND relIdUser = ?'
        );
        if (!$stmtCheck) {
            throw new Exception('Error al preparar consulta: ' . mysqli_error($conexion));
        }

        mysqli_stmt_bind_param($stmtCheck, 'ii', $element_id, $id_usuario);
        mysqli_stmt_execute($stmtCheck);
        mysqli_stmt_store_result($stmtCheck);
        mysqli_stmt_bind_result($stmtCheck, $total);
        mysqli_stmt_fetch($stmtCheck);
        mysqli_stmt_close($stmtCheck);

        if ((int) $total === 0) {
            $stmtInsert = mysqli_prepare(
                $conexion,
                'INSERT INTO elementsRelUsers (rel_id_element, relIdUser, relIdUsersLevel) VALUES (?, ?, ?)'
            );
            if (!$stmtInsert) {
                throw new Exception('Error al preparar inserción: ' . mysqli_error($conexion));
            }

            mysqli_stmt_bind_param($stmtInsert, 'iii', $element_id, $id_usuario, $id_jerarquia);
            if (!mysqli_stmt_execute($stmtInsert)) {
                $error = mysqli_stmt_error($stmtInsert);
                mysqli_stmt_close($stmtInsert);
                throw new Exception('Error al insertar permiso: ' . $error);
            }
            mysqli_stmt_close($stmtInsert);
        }
    } else {
        $stmtDelete = mysqli_prepare(
            $conexion,
            'DELETE FROM elementsRelUsers WHERE rel_id_element = ? AND relIdUser = ?'
        );
        if (!$stmtDelete) {
            throw new Exception('Error al preparar eliminación: ' . mysqli_error($conexion));
        }

        mysqli_stmt_bind_param($stmtDelete, 'ii', $element_id, $id_usuario);
        if (!mysqli_stmt_execute($stmtDelete)) {
            $error = mysqli_stmt_error($stmtDelete);
            mysqli_stmt_close($stmtDelete);
            throw new Exception('Error al eliminar permiso: ' . $error);
        }
        mysqli_stmt_close($stmtDelete);
    }

    mysqli_close($conexion);

    echo json_encode([
        'success' => true,
        'message' => $estado === 'activo' ? 'Elemento personalizado activado' : 'Elemento personalizado desactivado',
    ]);
} catch (Exception $e) {
    error_log('Error en usuarios/main/actualizar_permiso_elemento_usuario: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
