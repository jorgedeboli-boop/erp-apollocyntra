<?php
/**
 * Archivo para cargar estadísticas de estados de cajas via AJAX
 */

ob_clean();

require_once '../../../include/session.php';
require_once '../../../include/functions.php';

header('Content-Type: application/json');

function estados_cajas_listar_tablas(mysqli $conexion)
{
    $tablas = [];
    $result = mysqli_query($conexion, "SHOW TABLES LIKE 'movimientos_de_caja_%'");
    if ($result) {
        while ($row = mysqli_fetch_row($result)) {
            if (preg_match('/^movimientos_de_caja_\d+$/', $row[0])) {
                $tablas[] = $row[0];
            }
        }
    }
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
    if (!isset($_POST['tipo'])) {
        throw new Exception("Tipo de consulta no especificado");
    }

    $tipo = $_POST['tipo'];
    $conexion = conectar_bd();
    $tablas = estados_cajas_listar_tablas($conexion);
    $total = 0;

    switch ($tipo) {
        case 'total_cajas':
            $total = count($tablas);
            break;

        case 'cajas_abiertas':
        case 'cajas_cerradas':
            foreach ($tablas as $tableName) {
                $cerrada = estados_cajas_esta_cerrada($conexion, $tableName);
                if ($tipo === 'cajas_cerradas' && $cerrada) {
                    $total++;
                } elseif ($tipo === 'cajas_abiertas' && !$cerrada) {
                    $total++;
                }
            }
            break;

        default:
            throw new Exception("Tipo de consulta no válido: " . $tipo);
    }

    echo json_encode([
        'success' => true,
        'total' => $total,
        'tipo' => $tipo
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'tipo' => $tipo ?? 'desconocido'
    ]);
} finally {
    if (isset($conexion)) {
        mysqli_close($conexion);
    }
}
