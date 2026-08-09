<?php
require_once '../../../include/session.php';
require_once '../../../include/functions.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

if (!usuario_sesion_es_root()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Solo usuarios root pueden modificar la estructura de la tabla']);
    exit;
}

$tipos_permitidos = items_sections_tipos_columna_permitidos();

function validar_nombre_columna_items_sections($nombre) {
    return preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $nombre) === 1;
}

function formatear_default_sql($valor, $tipo) {
    $valor = trim($valor);
    if ($valor === '') {
        return '';
    }

    if (stripos($tipo, 'int') !== false || stripos($tipo, 'tinyint') !== false) {
        if (!preg_match('/^-?\d+$/', $valor)) {
            throw new Exception('El valor DEFAULT debe ser numérico para este tipo');
        }
        return ' DEFAULT ' . $valor;
    }

    if (stripos($tipo, 'enum') !== false) {
        $valor_esc = str_replace("'", "''", $valor);
        return " DEFAULT '" . $valor_esc . "'";
    }

    $valor_esc = str_replace("'", "''", $valor);
    return " DEFAULT '" . $valor_esc . "'";
}

try {
    $nombre_columna = trim($_POST['nombre_columna'] ?? '');
    $tipo_index = isset($_POST['tipo_index']) ? (int) $_POST['tipo_index'] : -1;
    $default_valor = trim($_POST['default_valor'] ?? '');
    $despues_columna = trim($_POST['despues_columna'] ?? '');

    if (!validar_nombre_columna_items_sections($nombre_columna)) {
        throw new Exception('Nombre de columna no válido');
    }

    if (!array_key_exists($tipo_index, $tipos_permitidos)) {
        throw new Exception('Tipo de columna no permitido');
    }

    $tipo_columna = $tipos_permitidos[$tipo_index];

    if (stripos($tipo_columna, 'DEFAULT') !== false && $default_valor !== '') {
        throw new Exception('El tipo seleccionado ya incluye DEFAULT; deje vacío el campo de valor por defecto');
    }

    $conexion = conectar_bd();
    if (!$conexion) {
        throw new Exception('Error de conexión a la base de datos');
    }

    $stmt_existe = mysqli_prepare($conexion, 'SHOW COLUMNS FROM itemsSections LIKE ?');
    if (!$stmt_existe) {
        throw new Exception('Error al comprobar columna: ' . mysqli_error($conexion));
    }
    mysqli_stmt_bind_param($stmt_existe, 's', $nombre_columna);
    mysqli_stmt_execute($stmt_existe);
    mysqli_stmt_store_result($stmt_existe);
    $existe = mysqli_stmt_num_rows($stmt_existe) > 0;
    mysqli_stmt_close($stmt_existe);

    if ($existe) {
        throw new Exception('La columna ya existe en la tabla');
    }

    $definicion = $tipo_columna;
    if ($default_valor !== '') {
        $definicion .= formatear_default_sql($default_valor, $tipo_columna);
    }

    $sql = 'ALTER TABLE itemsSections ADD COLUMN `' . $nombre_columna . '` ' . $definicion;

    if ($despues_columna !== '') {
        if (!validar_nombre_columna_items_sections($despues_columna)) {
            throw new Exception('Columna de referencia no válida');
        }

        $stmt_ref = mysqli_prepare($conexion, 'SHOW COLUMNS FROM itemsSections LIKE ?');
        if (!$stmt_ref) {
            throw new Exception('Error al comprobar columna de referencia: ' . mysqli_error($conexion));
        }
        mysqli_stmt_bind_param($stmt_ref, 's', $despues_columna);
        mysqli_stmt_execute($stmt_ref);
        mysqli_stmt_store_result($stmt_ref);
        $ref_existe = mysqli_stmt_num_rows($stmt_ref) > 0;
        mysqli_stmt_close($stmt_ref);

        if (!$ref_existe) {
            throw new Exception('La columna de referencia no existe');
        }

        $sql .= ' AFTER `' . $despues_columna . '`';
    }

    if (!mysqli_query($conexion, $sql)) {
        throw new Exception('Error al añadir columna: ' . mysqli_error($conexion));
    }

    mysqli_close($conexion);

    echo json_encode([
        'success' => true,
        'message' => 'Columna añadida correctamente',
    ]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
    ]);
}
