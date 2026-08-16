<?php
require_once '../../../include/session.php';
require_once '../../../include/functions.php';
require_once __DIR__ . '/correccion_cajas_helper.php';

header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado'], JSON_UNESCAPED_UNICODE);
    exit;
}

@set_time_limit(120);

$draw = isset($_POST['draw']) ? (int) $_POST['draw'] : 1;
$start = isset($_POST['start']) ? (int) $_POST['start'] : 0;
$length = isset($_POST['length']) ? (int) $_POST['length'] : 25;
$search = isset($_POST['search']['value']) ? trim($_POST['search']['value']) : '';
if ($start < 0) {
    $start = 0;
}
$exportAll = !empty($_POST['export_all']);
if ($exportAll) {
    $start = 0;
    $length = 50000;
} elseif ($length < 1 || $length > 500) {
    $length = 25;
}

try {
    $conexion = conectar_bd();
    if (!$conexion) {
        throw new Exception('Error de conexión a la base de datos');
    }

    $conflictos = [];
    foreach (correccion_cajas_listar_ids_tablas($conexion) as $idTabla) {
        $conflictosTabla = correccion_cajas_detectar_conflictos_tabla($conexion, $idTabla);
        if (!empty($conflictosTabla)) {
            $conflictos = array_merge($conflictos, $conflictosTabla);
        }
    }

    if ($search !== '') {
        $searchLower = function_exists('mb_strtolower')
            ? mb_strtolower($search, 'UTF-8')
            : strtolower($search);
        $conflictos = array_values(array_filter($conflictos, function ($item) use ($searchLower) {
            $texto = $item['id_tabla'] . ' ' . $item['fecha_texto'] . ' ' . $item['conflicto'];
            $haystack = function_exists('mb_strtolower')
                ? mb_strtolower($texto, 'UTF-8')
                : strtolower($texto);
            return strpos($haystack, $searchLower) !== false;
        }));
    }

    usort($conflictos, function ($a, $b) {
        $cmpFecha = strcmp($a['fecha'], $b['fecha']);
        if ($cmpFecha !== 0) {
            return $cmpFecha;
        }
        return $a['id_tabla'] <=> $b['id_tabla'];
    });

    $total = count($conflictos);
    $pagina = array_slice($conflictos, $start, $length);

    $data = [];
    foreach ($pagina as $item) {
        $data[] = [
            $item['id_tabla'],
            $item['fecha_texto'],
            htmlspecialchars($item['conflicto'], ENT_QUOTES, 'UTF-8'),
            [
                'id_tabla' => $item['id_tabla'],
                'fecha' => $item['fecha'],
                'fecha_texto' => $item['fecha_texto'],
                'falta_apertura' => $item['falta_apertura'],
                'falta_cierre' => $item['falta_cierre'],
                'apertura_id_erroneo' => !empty($item['apertura_id_erroneo']),
                'cierre_id_erroneo' => !empty($item['cierre_id_erroneo']),
                'cierre_no_coincide' => !empty($item['cierre_no_coincide']),
                'conflicto_texto' => $item['conflicto'],
                'importe_apertura_sugerido' => isset($item['importe_apertura_sugerido'])
                    ? (float) $item['importe_apertura_sugerido']
                    : null,
            ],
        ];
    }

    mysqli_close($conexion);

    echo json_encode([
        'draw' => $draw,
        'recordsTotal' => $total,
        'recordsFiltered' => $total,
        'data' => $data,
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    error_log('Error en load_list correccion_de_cajas: ' . $e->getMessage());
    if (isset($conexion) && $conexion) {
        mysqli_close($conexion);
    }
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
}
