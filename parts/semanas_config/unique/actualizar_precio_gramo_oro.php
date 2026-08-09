<?php
require_once '../../../include/session.php';
require_once '../../../include/functions.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

$id_numero_semana = isset($_POST['id_numero_semana']) ? (int) $_POST['id_numero_semana'] : 0;
$campo = isset($_POST['campo']) ? trim($_POST['campo']) : 'precio_gramo_oro';

if (isset($_POST['valor'])) {
    $valor = str_replace(',', '.', trim($_POST['valor']));
} elseif ($campo === 'precio_gramo_oro' && isset($_POST['precio_gramo_oro'])) {
    $valor = str_replace(',', '.', trim($_POST['precio_gramo_oro']));
} else {
    $valor = null;
}

$campos_permitidos = ['precio_gramo_oro', 'precio_24_mercado'];

if ($id_numero_semana <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Semana no válida']);
    exit;
}

if (!in_array($campo, $campos_permitidos, true)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Campo no válido']);
    exit;
}

if ($valor === null || $valor === '' || !is_numeric($valor)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Precio no válido']);
    exit;
}

$valor = (float) $valor;

if ($valor < 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'El precio no puede ser negativo']);
    exit;
}

function formatear_precio_euro($valor)
{
    return number_format((float) $valor, 2, ',', '.') . ' €';
}

function formatear_media_porcentual($valor)
{
    return number_format((float) $valor, 2, ',', '.') . ' %';
}

function calcular_media_porcentual_diferencia($precioGramoOro, $precio24Mercado)
{
    $precioGramoOro = (float) $precioGramoOro;
    $precio24Mercado = (float) $precio24Mercado;

    if ($precioGramoOro <= 0 || $precio24Mercado <= 0) {
        return 0.0;
    }

    return round((($precioGramoOro - $precio24Mercado) / $precio24Mercado) * 100, 2);
}

try {
    $conexion = conectar_bd();
    if (!$conexion) {
        throw new Exception('Error de conexión a la base de datos');
    }

    $stmt_select = mysqli_prepare(
        $conexion,
        "SELECT precio_gramo_oro, precio_24_mercado
         FROM listado_numero_semanas
         WHERE id_numero_semana = ?
         LIMIT 1"
    );

    if (!$stmt_select) {
        throw new Exception('Error al consultar la semana');
    }

    mysqli_stmt_bind_param($stmt_select, 'i', $id_numero_semana);
    mysqli_stmt_execute($stmt_select);
    $result_select = mysqli_stmt_get_result($stmt_select);
    $row = mysqli_fetch_assoc($result_select);
    mysqli_stmt_close($stmt_select);

    if (!$row) {
        throw new Exception('Registro no encontrado');
    }

    $precio_gramo_oro = (float) $row['precio_gramo_oro'];
    $precio_24_mercado = (float) $row['precio_24_mercado'];

    if ($campo === 'precio_gramo_oro') {
        $precio_gramo_oro = $valor;
    } else {
        $precio_24_mercado = $valor;
    }

    $media_porcentual_diferencia = calcular_media_porcentual_diferencia($precio_gramo_oro, $precio_24_mercado);

    if ($campo === 'precio_gramo_oro') {
        $calculo_precio_gramo = $precio_gramo_oro > 0 ? 'manual' : 'false';
        $sql_update = "UPDATE listado_numero_semanas
                       SET precio_gramo_oro = ?, media_porcentual_diferencia = ?, calculo_precio_gramo = ?
                       WHERE id_numero_semana = ?
                       LIMIT 1";
    } else {
        $sql_update = "UPDATE listado_numero_semanas
                       SET precio_24_mercado = ?, media_porcentual_diferencia = ?
                       WHERE id_numero_semana = ?
                       LIMIT 1";
    }

    $stmt = mysqli_prepare($conexion, $sql_update);

    if (!$stmt) {
        throw new Exception('Error al preparar la actualización');
    }

    if ($campo === 'precio_gramo_oro') {
        mysqli_stmt_bind_param($stmt, 'ddsi', $valor, $media_porcentual_diferencia, $calculo_precio_gramo, $id_numero_semana);
    } else {
        mysqli_stmt_bind_param($stmt, 'ddi', $valor, $media_porcentual_diferencia, $id_numero_semana);
    }

    if (!mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        throw new Exception('No se pudo actualizar el precio');
    }

    mysqli_stmt_close($stmt);
    mysqli_close($conexion);

    $mensaje = $campo === 'precio_24_mercado'
        ? 'Precio 24 mercado actualizado'
        : 'Precio gramo oro actualizado';

    $response = [
        'success' => true,
        'message' => $mensaje,
        'campo' => $campo,
        'precio_gramo_oro' => $precio_gramo_oro,
        'precio_gramo_oro_formatted' => formatear_precio_euro($precio_gramo_oro),
        'precio_24_mercado' => $precio_24_mercado,
        'precio_24_mercado_formatted' => formatear_precio_euro($precio_24_mercado),
        'media_porcentual_diferencia' => $media_porcentual_diferencia,
        'media_porcentual_diferencia_formatted' => formatear_media_porcentual($media_porcentual_diferencia)
    ];

    if ($campo === 'precio_gramo_oro') {
        $response['calculo_precio_gramo'] = $calculo_precio_gramo;
    }

    echo json_encode($response);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
