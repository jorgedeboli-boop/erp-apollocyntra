<?php
/**
 * Corrige valores erróneos en la columna grupos de movimientos_de_caja_1 .. _83 (excepto _61):
 *   - Variantes de "CORRECCI...N BALANCE" -> "CORRECCION BALANCE"
 *   - "CABA FINAL" -> "CAJA FINAL"
 *
 * Uso:
 *   Vista previa (sin cambios):  php fix_grupos_correccion_balance.php
 *   Aplicar cambios:             php fix_grupos_correccion_balance.php ejecutar
 *   O en navegador:              fix_grupos_correccion_balance.php?ejecutar=1
 */

define('DB_HOST', 'vl24696.dinaserver.com');
define('DB_NAME', 'quint_bbdd_4822');
define('DB_USER', 'quint_27183');
define('DB_PASS', 'Soul@7891');

define('ID_SUCURSAL_MIN', 1);
define('ID_SUCURSAL_MAX', 83);
define('ID_SUCURSAL_OMITIR', 61);

$reemplazos = [
    [
        'etiqueta' => 'CORRECCION BALANCE',
        'destino' => 'CORRECCION BALANCE',
        'sql_where' => "TRIM(grupos) LIKE 'CORRECCI%BALANCE' AND TRIM(grupos) <> 'CORRECCION BALANCE'",
    ],
    [
        'etiqueta' => 'CAJA FINAL',
        'destino' => 'CAJA FINAL',
        'sql_where' => "TRIM(grupos) = 'CABA FINAL'",
    ],
];

$ejecutar = in_array('ejecutar', $argv ?? [], true) || !empty($_GET['ejecutar']);

header('Content-Type: text/html; charset=utf-8');

$conexion = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if (!$conexion) {
    die('Error de conexión: ' . htmlspecialchars(mysqli_connect_error(), ENT_QUOTES, 'UTF-8'));
}

mysqli_set_charset($conexion, 'utf8mb4');

function correccion_grupos_tabla_existe(mysqli $conexion, $tabla)
{
    $tablaEsc = mysqli_real_escape_string($conexion, $tabla);
    $result = mysqli_query($conexion, "SHOW TABLES LIKE '{$tablaEsc}'");
    return $result && mysqli_num_rows($result) > 0;
}

function correccion_grupos_obtener_valores_distintos(mysqli $conexion, $tabla, $sqlWhere)
{
    $query = "SELECT TRIM(grupos) AS grupos, COUNT(*) AS total
              FROM `{$tabla}`
              WHERE {$sqlWhere}
              GROUP BY TRIM(grupos)
              ORDER BY TRIM(grupos) ASC";
    $result = mysqli_query($conexion, $query);
    if (!$result) {
        return [];
    }

    $valores = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $valores[] = [
            'grupos' => $row['grupos'],
            'total' => (int) $row['total'],
        ];
    }
    mysqli_free_result($result);

    return $valores;
}

echo '<pre>';
echo $ejecutar ? "=== EJECUTANDO ACTUALIZACIÓN ===\n\n" : "=== MODO VISTA PREVIA (sin cambios) ===\n";
echo "Para aplicar: php fix_grupos_correccion_balance.php ejecutar\n";
echo "O en navegador: ?ejecutar=1\n\n";

$totalEncontrados = 0;
$totalActualizados = 0;
$tablasRevisadas = 0;
$tablasInexistentes = [];

for ($idSucursal = ID_SUCURSAL_MIN; $idSucursal <= ID_SUCURSAL_MAX; $idSucursal++) {
    if ($idSucursal === ID_SUCURSAL_OMITIR) {
        continue;
    }

    $tabla = 'movimientos_de_caja_' . $idSucursal;
    if (!correccion_grupos_tabla_existe($conexion, $tabla)) {
        $tablasInexistentes[] = $tabla;
        continue;
    }

    $tablasRevisadas++;

    foreach ($reemplazos as $reemplazo) {
        $valores = correccion_grupos_obtener_valores_distintos($conexion, $tabla, $reemplazo['sql_where']);
        if (empty($valores)) {
            continue;
        }

        $cantidad = 0;
        foreach ($valores as $valor) {
            $cantidad += $valor['total'];
        }

        $totalEncontrados += $cantidad;
        echo "{$tabla} [{$reemplazo['etiqueta']}]: {$cantidad} registro(s) a corregir\n";
        foreach ($valores as $valor) {
            echo "  - [{$valor['grupos']}] x{$valor['total']}\n";
        }

        if ($ejecutar) {
            $destino = mysqli_real_escape_string($conexion, $reemplazo['destino']);
            $sqlUpdate = "UPDATE `{$tabla}`
                          SET grupos = '{$destino}'
                          WHERE {$reemplazo['sql_where']}";
            if (!mysqli_query($conexion, $sqlUpdate)) {
                echo "  [ERROR] " . mysqli_error($conexion) . "\n";
                continue;
            }

            $afectados = mysqli_affected_rows($conexion);
            $totalActualizados += $afectados;
            echo "  -> actualizados: {$afectados}\n";
        }
    }
}

mysqli_close($conexion);

echo "\n--- Resumen ---\n";
echo 'Tablas revisadas (' . ID_SUCURSAL_MIN . '-' . ID_SUCURSAL_MAX . ', sin _' . ID_SUCURSAL_OMITIR . "): {$tablasRevisadas}\n";

if (!empty($tablasInexistentes)) {
    echo 'Tablas inexistentes: ' . implode(', ', $tablasInexistentes) . "\n";
}

echo "Registros encontrados: {$totalEncontrados}\n";

if ($ejecutar) {
    echo "Registros actualizados: {$totalActualizados}\n";
} else {
    echo "Registros pendientes de actualizar: {$totalEncontrados}\n";
}

echo '</pre>';
