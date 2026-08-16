<?php
require_once '../../../include/session.php';

header('Content-Type: application/json');

try {
    $conexion = conectar_bd();

    $diagnostico = [
        'tablas' => [],
        'todas_tablas_movimientos' => []
    ];

    $resultTablas = mysqli_query($conexion, "SHOW TABLES LIKE 'movimientos_de_caja_%'");
    while ($resultTablas && ($row = mysqli_fetch_row($resultTablas))) {
        if (!preg_match('/^movimientos_de_caja_\d+$/', $row[0])) {
            continue;
        }

        $tableName = $row[0];
        $diagnostico['todas_tablas_movimientos'][] = $tableName;

        $info = [
            'tabla' => $tableName,
            'existe' => true,
            'total_registros' => 0,
            'ejemplo_datos' => []
        ];

        $queryCount = "SELECT COUNT(*) as total FROM $tableName";
        $resultCount = mysqli_query($conexion, $queryCount);
        if ($resultCount) {
            $rowCount = mysqli_fetch_assoc($resultCount);
            $info['total_registros'] = (int)$rowCount['total'];
        }

        $queryEjemplo = "SELECT id_movimientos, fecha_apunte, grupos, concepto, salida, entrada, usuario FROM $tableName LIMIT 3";
        $resultEjemplo = mysqli_query($conexion, $queryEjemplo);
        if ($resultEjemplo) {
            while ($rowEjemplo = mysqli_fetch_assoc($resultEjemplo)) {
                $info['ejemplo_datos'][] = $rowEjemplo;
            }
        }

        $diagnostico['tablas'][] = $info;
    }

    echo json_encode($diagnostico, JSON_PRETTY_PRINT);

} catch (Exception $e) {
    echo json_encode([
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ], JSON_PRETTY_PRINT);
} finally {
    if (isset($conexion)) {
        mysqli_close($conexion);
    }
}
?>
