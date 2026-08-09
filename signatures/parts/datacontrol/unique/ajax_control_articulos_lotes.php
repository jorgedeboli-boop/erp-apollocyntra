<?php
/**
 * Control artículos sucursal -> articulos_lotes.
 *
 * Orden de ejecución (según especificación):
 * 1) TRUNCATE control_articulos_tablas
 * 2) COUNT por sucursal y guardar en control_articulos_tablas
 * 3) Recorrer sucursal por sucursal y poblar articulos_control_cache con los que no existen
 * 4) Insertar en articulos_lotes desde la cache (solo faltantes)
 */
require_once '../../../include/session.php';
require_once '../../../include/functions.php';

header('Content-Type: application/json');

@ini_set('max_execution_time', '0');
@set_time_limit(0);
@ignore_user_abort(true);

function datacontrol_control_data_dir() {
    $dir = realpath(__DIR__ . '/../../../control_data');
    if ($dir === false || !is_dir($dir)) {
        throw new Exception('No existe la carpeta control_data en la raíz del proyecto.');
    }
    return $dir;
}

function datacontrol_ensure_control_tabla($conexion) {
    $dir = datacontrol_control_data_dir();
    $path = $dir . '/01_control_articulos_tablas.sql';
    if (!is_readable($path)) {
        throw new Exception('No se encuentra control_data/01_control_articulos_tablas.sql');
    }
    $sql = file_get_contents($path);
    if ($sql === false || trim($sql) === '') {
        throw new Exception('No se pudo leer 01_control_articulos_tablas.sql');
    }
    if (!mysqli_query($conexion, $sql)) {
        throw new Exception('Error creando control_articulos_tablas: ' . mysqli_error($conexion));
    }
}

function datacontrol_tabla_articulos_sucursal($id_sucursal) {
    $id = (int)$id_sucursal;
    if ($id <= 0) {
        return null;
    }
    return 'articulos_' . $id;
}

function datacontrol_tabla_articulos_existe($conexion, $nombre_tabla) {
    if (!preg_match('/^articulos_[0-9]+$/', $nombre_tabla)) {
        return false;
    }
    $nombre_esc = mysqli_real_escape_string($conexion, $nombre_tabla);
    $res = mysqli_query($conexion, "SHOW TABLES LIKE '" . $nombre_esc . "'");
    if (!$res) {
        return false;
    }
    $ok = mysqli_num_rows($res) > 0;
    mysqli_free_result($res);
    return $ok;
}

function datacontrol_truncate_control_tabla($conexion) {
    if (!mysqli_query($conexion, "TRUNCATE TABLE control_articulos_tablas")) {
        throw new Exception('TRUNCATE control_articulos_tablas: ' . mysqli_error($conexion));
    }
}

function datacontrol_truncate_cache($conexion) {
    if (!mysqli_query($conexion, "TRUNCATE TABLE articulos_control_cache")) {
        throw new Exception('TRUNCATE articulos_control_cache: ' . mysqli_error($conexion));
    }
}

/**
 * COUNT + INSERT control para una sucursal.
 */
function datacontrol_fase_conteo_una_sucursal($conexion, $id_suc) {
    $id_suc = (int)$id_suc;
    $tabla = datacontrol_tabla_articulos_sucursal($id_suc);
    if ($tabla === null) {
        return ['sucursal' => $id_suc, 'skipped' => true, 'motivo' => 'id sucursal inválido'];
    }

    if (!datacontrol_tabla_articulos_existe($conexion, $tabla)) {
        return [
            'sucursal' => $id_suc,
            'skipped' => true,
            'motivo' => 'No existe tabla ' . $tabla
        ];
    }

    $tabla_esc = '`' . str_replace('`', '``', $tabla) . '`';

    // COUNT directo (la cache se llena en un paso posterior, una vez guardados todos los conteos).
    $sqlCount = "
        SELECT COUNT(*) AS faltan
        FROM {$tabla_esc} s
        WHERE NOT EXISTS (
            SELECT 1 FROM articulos_lotes al
            WHERE al.id_articulo = s.id_articulo
            AND al.sucursal_articulo = {$id_suc}
        )
    ";
    $resCount = mysqli_query($conexion, $sqlCount);
    if (!$resCount) {
        return [
            'sucursal' => $id_suc,
            'tabla' => $tabla,
            'error' => 'COUNT faltantes: ' . mysqli_error($conexion)
        ];
    }
    $faltanRow = mysqli_fetch_assoc($resCount);
    mysqli_free_result($resCount);
    $faltan = isset($faltanRow['faltan']) ? (int)$faltanRow['faltan'] : 0;

    $stmtIns = mysqli_prepare(
        $conexion,
        "INSERT INTO control_articulos_tablas (cantidad_noexisten, cantidad_reempalzados, tipo_control, sucursal_control) VALUES (?, 0, 'articulos_lotes', ?)"
    );
    if (!$stmtIns) {
        return [
            'sucursal' => $id_suc,
            'tabla' => $tabla,
            'error' => 'INSERT control: ' . mysqli_error($conexion)
        ];
    }
    mysqli_stmt_bind_param($stmtIns, 'ii', $faltan, $id_suc);
    if (!mysqli_stmt_execute($stmtIns)) {
        $err = mysqli_stmt_error($stmtIns);
        mysqli_stmt_close($stmtIns);
        return [
            'sucursal' => $id_suc,
            'tabla' => $tabla,
            'error' => 'INSERT control: ' . $err
        ];
    }
    $id_control = mysqli_insert_id($conexion);
    mysqli_stmt_close($stmtIns);

    return [
        'sucursal' => $id_suc,
        'tabla' => $tabla,
        'id_control_articulos' => (int)$id_control,
        'cantidad_noexisten' => $faltan
    ];
}

function datacontrol_fase_conteos_todas_sucursales($conexion) {
    $resSuc = mysqli_query($conexion, 'SELECT id_sucursal FROM sucursal ORDER BY id_sucursal ASC');
    if (!$resSuc) {
        throw new Exception('Error listando sucursales: ' . mysqli_error($conexion));
    }
    $conteos = [];
    while ($row = mysqli_fetch_assoc($resSuc)) {
        $conteos[] = datacontrol_fase_conteo_una_sucursal($conexion, (int)$row['id_sucursal']);
    }
    mysqli_free_result($resSuc);
    return $conteos;
}

/**
 * Poblar cache para una sucursal (una vez finalizados los conteos).
 * Inserta en articulos_control_cache SOLO los faltantes según NOT EXISTS.
 *
 * @throws Exception
 */
function datacontrol_fase_cache_una_sucursal($conexion, $id_suc) {
    $id_suc = (int)$id_suc;
    if ($id_suc <= 0) {
        throw new Exception('Parámetro sucursal inválido.');
    }

    $tabla = datacontrol_tabla_articulos_sucursal($id_suc);
    if ($tabla === null || !datacontrol_tabla_articulos_existe($conexion, $tabla)) {
        throw new Exception('Tabla de artículos de sucursal no disponible.');
    }

    $tabla_esc = '`' . str_replace('`', '``', $tabla) . '`';
    mysqli_autocommit($conexion, true);

    $sqlCache = "
        INSERT INTO articulos_control_cache (id_articulo_parset, id_sucursal_parset)
        SELECT s.id_articulo, {$id_suc}
        FROM {$tabla_esc} s
        WHERE NOT EXISTS (
            SELECT 1 FROM articulos_lotes al
            WHERE al.id_articulo = s.id_articulo
            AND al.sucursal_articulo = {$id_suc}
        )
    ";
    if (!mysqli_query($conexion, $sqlCache)) {
        throw new Exception('Insert cache: ' . mysqli_error($conexion));
    }
    $cacheados = (int)mysqli_affected_rows($conexion);

    return [
        'sucursal' => $id_suc,
        'tabla' => $tabla,
        'cacheados' => $cacheados
    ];
}

/**
 * Insertar en articulos_lotes leyendo de articulos_control_cache + articulos_<sucursal>.
 * @throws Exception
 */
function datacontrol_fase_insert_desde_cache($conexion, $id_suc, $id_control) {
    $id_suc = (int)$id_suc;
    $id_control = (int)$id_control;

    if ($id_suc <= 0 || $id_control <= 0) {
        throw new Exception('Parámetros sucursal o id_control inválidos.');
    }

    $stmtChk = mysqli_prepare(
        $conexion,
        "SELECT id_control_articulos, sucursal_control, cantidad_noexisten FROM control_articulos_tablas WHERE id_control_articulos = ? AND tipo_control = 'articulos_lotes' LIMIT 1"
    );
    if (!$stmtChk) {
        throw new Exception('Validar control: ' . mysqli_error($conexion));
    }
    mysqli_stmt_bind_param($stmtChk, 'i', $id_control);
    mysqli_stmt_execute($stmtChk);
    $resChk = mysqli_stmt_get_result($stmtChk);
    $chk = mysqli_fetch_assoc($resChk);
    mysqli_stmt_close($stmtChk);

    if (!$chk || (int)$chk['sucursal_control'] !== $id_suc) {
        throw new Exception('id_control no coincide con sucursal o no existe.');
    }

    $faltan = isset($chk['cantidad_noexisten']) ? (int)$chk['cantidad_noexisten'] : 0;

    $tabla = datacontrol_tabla_articulos_sucursal($id_suc);
    if ($tabla === null || !datacontrol_tabla_articulos_existe($conexion, $tabla)) {
        throw new Exception('Tabla de artículos de sucursal no disponible.');
    }

    $tabla_esc = '`' . str_replace('`', '``', $tabla) . '`';
    mysqli_autocommit($conexion, true);

    if ($faltan <= 0) {
        // Nada que insertar; cerrar control.
        $stmtFin0 = mysqli_prepare(
            $conexion,
            'UPDATE control_articulos_tablas SET cantidad_reempalzados = 0, fecha_control_final = NOW() WHERE id_control_articulos = ?'
        );
        if (!$stmtFin0) {
            throw new Exception('UPDATE fin control: ' . mysqli_error($conexion));
        }
        mysqli_stmt_bind_param($stmtFin0, 'i', $id_control);
        if (!mysqli_stmt_execute($stmtFin0)) {
            $err = mysqli_stmt_error($stmtFin0);
            mysqli_stmt_close($stmtFin0);
            throw new Exception('UPDATE fin control: ' . $err);
        }
        mysqli_stmt_close($stmtFin0);

        return [
            'sucursal' => $id_suc,
            'tabla' => $tabla,
            'id_control_articulos' => $id_control,
            'insertados' => 0,
            'cantidad_reempalzados' => 0,
            'solo_insert' => true,
            'skipped_insert' => true,
        ];
    }

    $sqlInsert = "
        INSERT INTO articulos_lotes (
            id_articulo, unidades, descripcion_articulo, ley, id_lote_articulos,
            id_lote_cuarentena_articulos, inscripciones, tipo_de_articulo, peso_articulo,
            active_inscripciones, active_piedras, descripcion_piedras, peso_real,
            kilate_piedras, precio_compra_articulo, id_articulo_lote, fecha_compra_articulo,
            estado_articulo, merma, merma_real, precio_venta_articulo, articulo_venta,
            fecha_venta_articulo, peso_bruto, peso_bruto_real, articulo_auditado,
            identificador_lote, categoria_articulo, rel_id_proforma, rel_proforma_state,
            precio_fundicion, rentabilidad, rel_id_item_proforma,
            total_gramos_fundicion, total_pagado_fundicion, rel_numero_semana, sucursal_articulo
        )
        SELECT
            s.id_articulo, s.unidades, s.descripcion_articulo, s.ley, s.id_lote_articulos,
            s.id_lote_cuarentena_articulos, s.inscripciones, s.tipo_de_articulo, s.peso_articulo,
            s.active_inscripciones, s.active_piedras, s.descripcion_piedras, s.peso_real,
            s.kilate_piedras, s.precio_compra_articulo, s.id_articulo_lote, s.fecha_compra_articulo,
            s.estado_articulo, s.merma, s.merma_real, s.precio_venta_articulo, s.articulo_venta,
            s.fecha_venta_articulo, s.peso_bruto, s.peso_bruto_real, s.articulo_auditado,
            s.new_identificador_lote, s.categoria_articulo, s.rel_id_proforma, s.rel_proforma_state,
            s.precio_fundicion, s.rentabilidad, s.rel_id_item_proforma,
            s.total_gramos_fundicion, s.total_pagado_fundicion, s.rel_numero_semana, {$id_suc}
        FROM {$tabla_esc} s
        INNER JOIN articulos_control_cache c
            ON c.id_sucursal_parset = {$id_suc}
            AND c.id_articulo_parset = s.id_articulo
    ";
    if (!mysqli_query($conexion, $sqlInsert)) {
        throw new Exception('INSERT desde cache: ' . mysqli_error($conexion));
    }
    $insertados = (int)mysqli_affected_rows($conexion);

    $stmtFin = mysqli_prepare(
        $conexion,
        'UPDATE control_articulos_tablas SET cantidad_reempalzados = ?, fecha_control_final = NOW() WHERE id_control_articulos = ?'
    );
    if (!$stmtFin) {
        throw new Exception('UPDATE fin control: ' . mysqli_error($conexion));
    }
    mysqli_stmt_bind_param($stmtFin, 'ii', $insertados, $id_control);
    if (!mysqli_stmt_execute($stmtFin)) {
        $err = mysqli_stmt_error($stmtFin);
        mysqli_stmt_close($stmtFin);
        throw new Exception('UPDATE fin control: ' . $err);
    }
    mysqli_stmt_close($stmtFin);

    return [
        'sucursal' => $id_suc,
        'tabla' => $tabla,
        'id_control_articulos' => $id_control,
        'insertados' => $insertados,
        'cantidad_reempalzados' => $insertados,
        'solo_insert' => true,
    ];
}

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'sucursales') {
        $conexion = conectar_bd();
        mysqli_set_charset($conexion, 'utf8');
        $resSuc = mysqli_query($conexion, 'SELECT id_sucursal FROM sucursal ORDER BY id_sucursal ASC');
        if (!$resSuc) {
            throw new Exception('Error listando sucursales: ' . mysqli_error($conexion));
        }
        $ids = [];
        while ($row = mysqli_fetch_assoc($resSuc)) {
            $ids[] = (int)$row['id_sucursal'];
        }
        mysqli_free_result($resSuc);
        mysqli_close($conexion);
        echo json_encode(['success' => true, 'sucursales' => $ids]);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Método no permitido']);
        exit;
    }

    $conexion = conectar_bd();
    mysqli_set_charset($conexion, 'utf8');

    datacontrol_ensure_control_tabla($conexion);

    $fase = isset($_POST['fase']) ? trim((string)$_POST['fase']) : '';

    if ($fase === 'reset') {
        datacontrol_truncate_control_tabla($conexion);
        datacontrol_truncate_cache($conexion);
        mysqli_close($conexion);
        echo json_encode(['success' => true, 'message' => 'Reset OK (control_articulos_tablas y articulos_control_cache truncadas).']);
        exit;
    }

    if ($fase === 'conteos') {
        // Por seguridad: si alguien llama conteos sin reset previo, limpiamos control_articulos_tablas primero.
        datacontrol_truncate_control_tabla($conexion);
        $conteos = datacontrol_fase_conteos_todas_sucursales($conexion);
        mysqli_close($conexion);
        echo json_encode(['success' => true, 'conteos' => $conteos]);
        exit;
    }

    if ($fase === 'conteo_sucursal') {
        $sid = isset($_POST['solo_sucursal']) ? (int)$_POST['solo_sucursal'] : 0;
        if ($sid <= 0) {
            mysqli_close($conexion);
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Falta solo_sucursal.']);
            exit;
        }
        $one = datacontrol_fase_conteo_una_sucursal($conexion, $sid);
        mysqli_close($conexion);
        echo json_encode(['success' => true, 'conteo' => $one]);
        exit;
    }

    $solo_sucursal = isset($_POST['solo_sucursal']) ? (int)$_POST['solo_sucursal'] : 0;
    $id_control = isset($_POST['id_control_articulos']) ? (int)$_POST['id_control_articulos'] : 0;

    if ($fase === 'cache_sucursal' && $solo_sucursal > 0) {
        try {
            $row = datacontrol_fase_cache_una_sucursal($conexion, $solo_sucursal);
            mysqli_close($conexion);
            echo json_encode(['success' => true, 'cache' => $row]);
        } catch (Exception $e) {
            mysqli_close($conexion);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    if ($solo_sucursal > 0 && $id_control > 0) {
        try {
            $row = datacontrol_fase_insert_desde_cache($conexion, $solo_sucursal, $id_control);
            mysqli_close($conexion);
            echo json_encode(['success' => true, 'informe' => [$row]]);
        } catch (Exception $e) {
            mysqli_close($conexion);
            echo json_encode([
                'success' => true,
                'informe' => [[
                    'sucursal' => $solo_sucursal,
                    'id_control_articulos' => $id_control,
                    'error' => $e->getMessage(),
                ]],
            ]);
        }
        exit;
    }

    mysqli_close($conexion);
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Flujo: fase=reset, luego fase=conteo_sucursal por cada sucursal (o fase=conteos), luego fase=cache_sucursal por cada sucursal, luego POST solo_sucursal + id_control_articulos (insert desde cache).',
    ]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
