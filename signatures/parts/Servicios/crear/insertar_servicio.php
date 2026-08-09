<?php
require_once '../../../include/session.php';
require_once '../../../include/functions.php';

header('Content-Type: application/json');

$unidades = ['hora', 'media_hora', 'dia', 'sesion'];
$tipos_fact = ['por_hora', 'precio_fijo', 'por_sesion'];

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Método no permitido']);
        exit;
    }

    if (empty(trim($_POST['nombre'] ?? ''))) {
        throw new Exception('El nombre es obligatorio');
    }
    if (empty($_POST['rel_id_empresa'])) {
        throw new Exception('Debe seleccionar una empresa');
    }

    $rel_id_empresa = (int)$_POST['rel_id_empresa'];
    $codigo = isset($_POST['codigo']) ? trim($_POST['codigo']) : '';
    $nombre = trim($_POST['nombre']);
    $descripcion = isset($_POST['descripcion']) ? trim($_POST['descripcion']) : '';
    $id_categoria = isset($_POST['id_categoria']) ? (int)$_POST['id_categoria'] : 0;
    $activo = !empty($_POST['activo']) ? 1 : 0;

    $duracion_horas = isset($_POST['duracion_horas']) ? (float)$_POST['duracion_horas'] : 0;
    $duracion_minutos = isset($_POST['duracion_minutos']) ? (int)$_POST['duracion_minutos'] : 0;
    $unidad_tiempo = isset($_POST['unidad_tiempo']) ? trim($_POST['unidad_tiempo']) : 'hora';
    if (!in_array($unidad_tiempo, $unidades, true)) {
        $unidad_tiempo = 'hora';
    }

    $precio_hora = isset($_POST['precio_hora']) ? (float)$_POST['precio_hora'] : 0;
    $precio_coste_hora = isset($_POST['precio_coste_hora']) ? (float)$_POST['precio_coste_hora'] : 0;
    $precio_fijo = isset($_POST['precio_fijo']) ? (float)$_POST['precio_fijo'] : 0;
    $porcentaje_iva = isset($_POST['porcentaje_iva']) ? (float)$_POST['porcentaje_iva'] : 21;

    $tipo_facturacion = isset($_POST['tipo_facturacion']) ? trim($_POST['tipo_facturacion']) : 'por_hora';
    if (!in_array($tipo_facturacion, $tipos_fact, true)) {
        $tipo_facturacion = 'por_hora';
    }

    $minimo_horas = isset($_POST['minimo_horas']) ? (float)$_POST['minimo_horas'] : 0;
    $incremento_horas = isset($_POST['incremento_horas']) ? (float)$_POST['incremento_horas'] : 0.25;
    $notas = isset($_POST['notas']) ? trim($_POST['notas']) : '';

    $id_usuario = isset($_SESSION['usuario_id']) ? (int)$_SESSION['usuario_id'] : 0;
    if (!$id_usuario) {
        throw new Exception('No se pudo determinar el usuario');
    }

    $conexion = conectar_bd();

    $sql = "
        INSERT INTO servicios (
            rel_id_empresa, codigo, nombre, descripcion, id_categoria, activo,
            duracion_horas, duracion_minutos, unidad_tiempo,
            precio_hora, precio_coste_hora, precio_fijo, porcentaje_iva,
            tipo_facturacion, minimo_horas, incremento_horas, notas,
            id_usuario_creador, id_usuario_modificador
        ) VALUES (
            ?, ?, ?, ?, ?, ?,
            ?, ?, ?,
            ?, ?, ?, ?,
            ?, ?, ?, ?,
            ?, ?
        )
    ";

    $stmt = mysqli_prepare($conexion, $sql);
    if (!$stmt) {
        throw new Exception(mysqli_error($conexion));
    }

    mysqli_stmt_bind_param(
        $stmt,
        'isssiidisddddsddsii',
        $rel_id_empresa,
        $codigo,
        $nombre,
        $descripcion,
        $id_categoria,
        $activo,
        $duracion_horas,
        $duracion_minutos,
        $unidad_tiempo,
        $precio_hora,
        $precio_coste_hora,
        $precio_fijo,
        $porcentaje_iva,
        $tipo_facturacion,
        $minimo_horas,
        $incremento_horas,
        $notas,
        $id_usuario,
        $id_usuario
    );

    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception(mysqli_stmt_error($stmt));
    }

    $new_id = mysqli_insert_id($conexion);
    mysqli_stmt_close($stmt);
    mysqli_close($conexion);

    echo json_encode([
        'success' => true,
        'message' => 'Servicio creado correctamente',
        'id_servicio' => $new_id,
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
