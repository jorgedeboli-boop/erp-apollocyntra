<?php
/**
 * API para consultar, crear y actualizar registros en elementsDomLevels.
 * Solo usuarios root.
 */

require_once __DIR__ . '/../../include/session.php';
require_once __DIR__ . '/../../include/functions.php';

header('Content-Type: application/json; charset=utf-8');

function elements_dom_levels_responder($payload, $status = 200)
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

function elements_dom_levels_obtener_rel_id_type_item()
{
    if (isset($_POST['rel_id_type_Item'])) {
        $id = (int) $_POST['rel_id_type_Item'];
        if ($id > 0) {
            return $id;
        }
    }

    if (isset($_SESSION['relItemAction']) && (int) $_SESSION['relItemAction'] > 0) {
        return (int) $_SESSION['relItemAction'];
    }

    throw new Exception('No se pudo determinar el id_type_Item de la página actual');
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        elements_dom_levels_responder(['success' => false, 'message' => 'Método no permitido'], 405);
    }

    if (!isset($_SESSION['usuario_autenticado']) || $_SESSION['usuario_autenticado'] !== true) {
        elements_dom_levels_responder(['success' => false, 'message' => 'No autorizado'], 401);
    }

    if (!isset($usuario_root) || $usuario_root !== 'true') {
        elements_dom_levels_responder(['success' => false, 'message' => 'Acceso restringido a usuarios root'], 403);
    }

    $action = isset($_POST['action']) ? trim((string) $_POST['action']) : '';

    $conexion = conectar_bd();
    if (!$conexion) {
        throw new Exception('Sin conexión a la base de datos');
    }

    if ($action === 'get') {
        $id_dom_element = isset($_POST['id_dom_element']) ? trim((string) $_POST['id_dom_element']) : '';
        $rel_id_type_item = elements_dom_levels_obtener_rel_id_type_item();

        if ($id_dom_element === '' || strlen($id_dom_element) > 28) {
            throw new Exception('ID DOM no válido');
        }

        $stmt = mysqli_prepare(
            $conexion,
            'SELECT id_element, id_dom_element, rel_id_type_Item, state_element_rel, name_text_element
             FROM elementsDomLevels
             WHERE id_dom_element = ? AND rel_id_type_Item = ?
             LIMIT 1'
        );
        if (!$stmt) {
            throw new Exception('Error al preparar consulta');
        }

        mysqli_stmt_bind_param($stmt, 'si', $id_dom_element, $rel_id_type_item);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = $result ? mysqli_fetch_assoc($result) : null;
        mysqli_stmt_close($stmt);
        mysqli_close($conexion);

        if (!$row) {
            elements_dom_levels_responder([
                'success' => true,
                'exists' => false,
                'id_dom_element' => $id_dom_element,
                'rel_id_type_Item' => $rel_id_type_item,
            ]);
        }

        elements_dom_levels_responder([
            'success' => true,
            'exists' => true,
            'id_element' => (int) $row['id_element'],
            'id_dom_element' => (string) $row['id_dom_element'],
            'rel_id_type_Item' => (int) $row['rel_id_type_Item'],
            'state_element_rel' => (string) $row['state_element_rel'],
            'name_text_element' => (string) ($row['name_text_element'] ?? ''),
        ]);
    }

    if ($action === 'create') {
        $id_dom_element = isset($_POST['id_dom_element']) ? trim((string) $_POST['id_dom_element']) : '';
        $name_text_element = isset($_POST['name_text_element']) ? trim((string) $_POST['name_text_element']) : '';
        $rel_id_type_item = elements_dom_levels_obtener_rel_id_type_item();

        if ($id_dom_element === '' || strlen($id_dom_element) > 28) {
            throw new Exception('ID DOM no válido');
        }
        if ($name_text_element === '') {
            throw new Exception('El nombre del elemento es obligatorio');
        }
        if (strlen($name_text_element) > 124) {
            throw new Exception('El nombre del elemento no puede superar 124 caracteres');
        }

        $stmtCheck = mysqli_prepare(
            $conexion,
            'SELECT id_element, state_element_rel, name_text_element
             FROM elementsDomLevels
             WHERE id_dom_element = ? AND rel_id_type_Item = ?
             LIMIT 1'
        );
        if (!$stmtCheck) {
            throw new Exception('Error al preparar consulta');
        }
        mysqli_stmt_bind_param($stmtCheck, 'si', $id_dom_element, $rel_id_type_item);
        mysqli_stmt_execute($stmtCheck);
        $resultCheck = mysqli_stmt_get_result($stmtCheck);
        $existing = $resultCheck ? mysqli_fetch_assoc($resultCheck) : null;
        mysqli_stmt_close($stmtCheck);

        if ($existing) {
            mysqli_close($conexion);
            elements_dom_levels_responder([
                'success' => true,
                'exists' => true,
                'id_element' => (int) $existing['id_element'],
                'id_dom_element' => $id_dom_element,
                'rel_id_type_Item' => $rel_id_type_item,
                'state_element_rel' => (string) $existing['state_element_rel'],
                'name_text_element' => (string) ($existing['name_text_element'] ?? ''),
                'message' => 'El elemento ya existe',
            ]);
        }

        $state = 'false';
        $stmt = mysqli_prepare(
            $conexion,
            'INSERT INTO elementsDomLevels (id_dom_element, rel_id_type_Item, state_element_rel, name_text_element) VALUES (?, ?, ?, ?)'
        );
        if (!$stmt) {
            throw new Exception('Error al preparar inserción');
        }
        mysqli_stmt_bind_param($stmt, 'siss', $id_dom_element, $rel_id_type_item, $state, $name_text_element);
        if (!mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            throw new Exception('No se pudo crear el elemento');
        }
        $id_element = (int) mysqli_insert_id($conexion);
        mysqli_stmt_close($stmt);
        mysqli_close($conexion);

        elements_dom_levels_responder([
            'success' => true,
            'exists' => true,
            'created' => true,
            'id_element' => $id_element,
            'id_dom_element' => $id_dom_element,
            'rel_id_type_Item' => $rel_id_type_item,
            'state_element_rel' => $state,
            'name_text_element' => $name_text_element,
            'message' => 'Elemento creado correctamente',
        ]);
    }

    if ($action === 'update') {
        $id_element = isset($_POST['id_element']) ? (int) $_POST['id_element'] : 0;
        $state = isset($_POST['state_element_rel']) ? trim((string) $_POST['state_element_rel']) : '';
        $rel_id_type_item = elements_dom_levels_obtener_rel_id_type_item();

        if ($id_element <= 0) {
            throw new Exception('ID de elemento no válido');
        }
        if (!in_array($state, ['true', 'false'], true)) {
            throw new Exception('Estado no válido');
        }

        $stmt = mysqli_prepare(
            $conexion,
            'UPDATE elementsDomLevels SET state_element_rel = ? WHERE id_element = ? AND rel_id_type_Item = ?'
        );
        if (!$stmt) {
            throw new Exception('Error al preparar actualización');
        }
        mysqli_stmt_bind_param($stmt, 'sii', $state, $id_element, $rel_id_type_item);
        if (!mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            throw new Exception('No se pudo actualizar el elemento');
        }
        $affected = mysqli_stmt_affected_rows($stmt);
        mysqli_stmt_close($stmt);
        mysqli_close($conexion);

        if ($affected < 1) {
            throw new Exception('No se encontró el elemento para este ítem de página');
        }

        elements_dom_levels_responder([
            'success' => true,
            'id_element' => $id_element,
            'rel_id_type_Item' => $rel_id_type_item,
            'state_element_rel' => $state,
            'message' => 'Estado actualizado',
        ]);
    }

    mysqli_close($conexion);
    throw new Exception('Acción no válida');
} catch (Exception $e) {
    if (isset($conexion) && $conexion) {
        mysqli_close($conexion);
    }
    elements_dom_levels_responder(['success' => false, 'message' => $e->getMessage()], 400);
}
