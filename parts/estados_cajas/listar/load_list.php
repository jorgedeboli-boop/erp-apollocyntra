<?php
require_once '../../../include/session.php';
require_once '../../../include/functions.php';

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$draw = isset($_POST['draw']) ? intval($_POST['draw']) : 1;
$start = isset($_POST['start']) ? intval($_POST['start']) : 0;
$length = isset($_POST['length']) ? intval($_POST['length']) : 10;
$search = isset($_POST['search']['value']) ? trim($_POST['search']['value']) : '';
$orderColumn = isset($_POST['order'][0]['column']) ? (int)$_POST['order'][0]['column'] : 0;
$orderDir = isset($_POST['order'][0]['dir']) ? $_POST['order'][0]['dir'] : 'asc';
$filtro_estado = isset($_POST['filtro_estado']) ? trim($_POST['filtro_estado']) : '';

if ($start < 0) {
    $start = 0;
}
if ($length < 1 || $length > 100) {
    $length = 10;
}

function estados_cajas_listar_tablas(mysqli $conexion)
{
    $tablas = [];
    $result = mysqli_query($conexion, "SHOW TABLES LIKE 'movimientos_de_caja_%'");
    if ($result) {
        while ($row = mysqli_fetch_row($result)) {
            if (preg_match('/^movimientos_de_caja_(\d+)$/', $row[0], $m)) {
                $tablas[] = [
                    'name' => $row[0],
                    'id' => (int) $m[1],
                ];
            }
        }
    }
    usort($tablas, function ($a, $b) {
        return $a['id'] <=> $b['id'];
    });
    return $tablas;
}

function estados_cajas_esta_cerrada(mysqli $conexion, $tableName)
{
    $query = "SELECT
                (SELECT MAX(id_movimientos) FROM `{$tableName}` WHERE TRIM(grupos) = 'CAJA INICIO') AS id_apertura,
                (SELECT MAX(id_movimientos) FROM `{$tableName}` WHERE cierre_caja = 'true') AS id_cierre";
    $result = mysqli_query($conexion, $query);
    if (!$result) {
        return true;
    }
    $row = mysqli_fetch_assoc($result);
    $idApertura = isset($row['id_apertura']) ? (int) $row['id_apertura'] : 0;
    $idCierre = isset($row['id_cierre']) ? (int) $row['id_cierre'] : 0;
    return $idCierre >= $idApertura;
}

try {
    $conexion = conectar_bd();
    if (!$conexion) {
        throw new Exception('Error de conexión a la base de datos');
    }

    $filas = [];
    foreach (estados_cajas_listar_tablas($conexion) as $tablaCaja) {
        $idTabla = $tablaCaja['id'];
        $tableName = $tablaCaja['name'];
        $cajaCerrada = estados_cajas_esta_cerrada($conexion, $tableName) ? 'true' : 'false';

        if ($filtro_estado !== '' && $cajaCerrada !== $filtro_estado) {
            continue;
        }
        if ($search !== '' && strpos((string) $idTabla, $search) === false) {
            continue;
        }

        $apertura = null;
        $cierre = null;
        $saldo = null;
        $idAperturaActiva = null;

        $queryApertura = "SELECT id_movimientos, fecha_apunte, hora_de_apunte, entrada
                         FROM `{$tableName}`
                         WHERE TRIM(grupos) = 'CAJA INICIO'
                         AND fecha_apunte = CURDATE()
                         ORDER BY id_movimientos DESC";
        $resultApertura = mysqli_query($conexion, $queryApertura);

        if ($resultApertura && mysqli_num_rows($resultApertura) > 0) {
            while ($rowApertura = mysqli_fetch_assoc($resultApertura)) {
                $idApertura = (int) $rowApertura['id_movimientos'];
                $queryCierrePost = "SELECT COUNT(*) as tiene_cierre
                                   FROM `{$tableName}`
                                   WHERE cierre_caja = 'true'
                                   AND DATE(fecha_apunte) = CURDATE()
                                   AND id_movimientos > ?";
                $stmtCierrePost = mysqli_prepare($conexion, $queryCierrePost);
                mysqli_stmt_bind_param($stmtCierrePost, 'i', $idApertura);
                mysqli_stmt_execute($stmtCierrePost);
                $resultCierrePost = mysqli_stmt_get_result($stmtCierrePost);
                $rowCierrePost = mysqli_fetch_assoc($resultCierrePost);
                mysqli_stmt_close($stmtCierrePost);

                if ((int) $rowCierrePost['tiene_cierre'] === 0) {
                    $apertura = [
                        'fecha' => $rowApertura['fecha_apunte'],
                        'hora' => $rowApertura['hora_de_apunte'],
                        'importe' => floatval($rowApertura['entrada']),
                    ];
                    $idAperturaActiva = $idApertura;

                    if ($cajaCerrada === 'false') {
                        $querySaldo = "SELECT
                                        COALESCE(SUM(entrada), 0) as total_entradas,
                                        COALESCE(SUM(salida), 0) as total_salidas
                                      FROM `{$tableName}`
                                      WHERE fecha_apunte = CURDATE()
                                      AND id_movimientos >= ?";
                        $stmtSaldo = mysqli_prepare($conexion, $querySaldo);
                        mysqli_stmt_bind_param($stmtSaldo, 'i', $idApertura);
                        mysqli_stmt_execute($stmtSaldo);
                        $resultSaldo = mysqli_stmt_get_result($stmtSaldo);
                        $rowSaldo = mysqli_fetch_assoc($resultSaldo);
                        mysqli_stmt_close($stmtSaldo);
                        $saldo = floatval($rowSaldo['total_entradas']) - floatval($rowSaldo['total_salidas']);
                    }
                    break;
                }
            }
        }

        if ($cajaCerrada === 'true') {
            $queryCierre = "SELECT fecha_apunte, hora_de_apunte, salida
                           FROM `{$tableName}`
                           WHERE cierre_caja = 'true'
                           ORDER BY id_movimientos DESC
                           LIMIT 1";
            $resultCierre = mysqli_query($conexion, $queryCierre);
            if ($resultCierre && mysqli_num_rows($resultCierre) > 0) {
                $rowCierre = mysqli_fetch_assoc($resultCierre);
                $cierre = [
                    'fecha' => $rowCierre['fecha_apunte'],
                    'hora' => $rowCierre['hora_de_apunte'],
                    'importe' => floatval($rowCierre['salida']),
                ];
            }
        } elseif ($idAperturaActiva !== null) {
            $queryCierre = "SELECT fecha_apunte, hora_de_apunte, salida
                           FROM `{$tableName}`
                           WHERE cierre_caja = 'true'
                           AND fecha_apunte = CURDATE()
                           AND id_movimientos > ?
                           ORDER BY id_movimientos DESC
                           LIMIT 1";
            $stmtCierre = mysqli_prepare($conexion, $queryCierre);
            mysqli_stmt_bind_param($stmtCierre, 'i', $idAperturaActiva);
            mysqli_stmt_execute($stmtCierre);
            $resultCierre = mysqli_stmt_get_result($stmtCierre);
            if ($resultCierre && mysqli_num_rows($resultCierre) > 0) {
                $rowCierre = mysqli_fetch_assoc($resultCierre);
                $cierre = [
                    'fecha' => $rowCierre['fecha_apunte'],
                    'hora' => $rowCierre['hora_de_apunte'],
                    'importe' => floatval($rowCierre['salida']),
                ];
            }
            mysqli_stmt_close($stmtCierre);
        }

        $filas[] = [
            'id' => $idTabla,
            'caja_cerrada' => $cajaCerrada,
            'saldo' => $saldo,
            'apertura' => $apertura,
            'cierre' => $cierre,
        ];
    }

    $sortKeys = [0 => 'id', 1 => 'caja_cerrada'];
    $sortKey = isset($sortKeys[$orderColumn]) ? $sortKeys[$orderColumn] : 'id';
    usort($filas, function ($a, $b) use ($sortKey, $orderDir) {
        $cmp = $a[$sortKey] <=> $b[$sortKey];
        return strtoupper($orderDir) === 'DESC' ? -$cmp : $cmp;
    });

    $total = count($filas);
    $pagina = array_slice($filas, $start, $length);
    $data = [];
    foreach ($pagina as $fila) {
        $data[] = [
            $fila['id'],
            $fila['caja_cerrada'],
            $fila['saldo'],
            $fila['apertura'],
            $fila['apertura'] ? $fila['apertura']['importe'] : null,
            $fila['cierre'],
            $fila['cierre'] ? $fila['cierre']['importe'] : null,
            ['id' => $fila['id']],
        ];
    }

    mysqli_close($conexion);

    echo json_encode([
        'draw' => $draw,
        'recordsTotal' => $total,
        'recordsFiltered' => $total,
        'data' => $data,
    ]);
} catch (Exception $e) {
    error_log('Error en load_list estados_cajas: ' . $e->getMessage());
    if (isset($conexion)) {
        mysqli_close($conexion);
    }
    http_response_code(500);
    echo json_encode([
        'error' => 'Error del servidor: ' . $e->getMessage(),
    ]);
}
