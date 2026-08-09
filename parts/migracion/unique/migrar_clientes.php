<?php
/**
 * Migración de clientes_old → clientes + datos_clientes + direcciones.
 */
require_once __DIR__ . '/../../../include/session.php';
require_once __DIR__ . '/../../../include/functions.php';

header('Content-Type: text/plain; charset=utf-8');

set_time_limit(0);
ini_set('max_execution_time', '0');
ini_set('memory_limit', '512M');

if (function_exists('apache_setenv')) {
    @apache_setenv('no-gzip', '1');
}
@ini_set('zlib.output_compression', '0');

function migrar_clientes_fecha($valor, $fallback = '1970-01-01')
{
    $fecha = trim((string) ($valor ?? ''));
    if ($fecha === '' || $fecha === '0000-00-00' || $fecha === '0000-00-00 00:00:00') {
        return $fallback;
    }
    if (strlen($fecha) >= 10) {
        $fecha = substr($fecha, 0, 10);
    }
    $dt = DateTime::createFromFormat('Y-m-d', $fecha);
    if (!$dt || $dt->format('Y-m-d') !== $fecha) {
        return $fallback;
    }
    return $fecha;
}

function migrar_clientes_estado($valor)
{
    $estado = strtolower(trim((string) ($valor ?? '')));
    return $estado === 'deshabilitado' ? 'deshabilitado' : 'habilitado';
}

function migrar_clientes_tabla_existe($conexion, $tabla)
{
    $tabla = preg_replace('/[^a-zA-Z0-9_]/', '', $tabla);
    if ($tabla === '') {
        return false;
    }
    $res = mysqli_query($conexion, "SHOW TABLES LIKE '" . $tabla . "'");
    if (!$res) {
        return false;
    }
    $existe = mysqli_num_rows($res) > 0;
    mysqli_free_result($res);
    return $existe;
}

function migrar_clientes_abort($mensaje, $conexion = null, $conexionWrite = null)
{
    if ($conexionWrite instanceof mysqli) {
        mysqli_close($conexionWrite);
    }
    if ($conexion instanceof mysqli) {
        mysqli_close($conexion);
    }
    echo "ERROR: " . $mensaje . "\n";
    exit(1);
}

$conexion = conectar_bd();
if (!$conexion) {
    migrar_clientes_abort('No se pudo conectar a la base de datos.');
}

$conexionWrite = conectar_bd();
if (!$conexionWrite) {
    migrar_clientes_abort('No se pudo abrir la conexión de escritura.', $conexion);
}

$tablasRequeridas = array('clientes_old', 'clientes', 'datos_clientes', 'direcciones');
$faltantes = array();
foreach ($tablasRequeridas as $tabla) {
    if (!migrar_clientes_tabla_existe($conexion, $tabla)) {
        $faltantes[] = $tabla;
    }
}

if (!empty($faltantes)) {
    migrar_clientes_abort(
        'Faltan tablas requeridas: ' . implode(', ', $faltantes) . '. '
        . 'Importa clientes.sql desde Importaciones (se guarda como clientes_old).',
        $conexion,
        $conexionWrite
    );
}

echo "=== COMPROBACIÓN DE TABLAS ===\n";
foreach ($tablasRequeridas as $tabla) {
    echo "OK: {$tabla}\n";
}

echo "\n=== TRUNCATE ===\n";
mysqli_query($conexionWrite, 'SET FOREIGN_KEY_CHECKS=0');
$tablasTruncate = array('clientes', 'datos_clientes', 'direcciones');
foreach ($tablasTruncate as $tabla) {
    if (!mysqli_query($conexionWrite, "TRUNCATE TABLE `{$tabla}`")) {
        mysqli_query($conexionWrite, 'SET FOREIGN_KEY_CHECKS=1');
        migrar_clientes_abort(
            'Error al truncar ' . $tabla . ': ' . mysqli_error($conexionWrite),
            $conexion,
            $conexionWrite
        );
    }
    echo "OK: TRUNCATE {$tabla}\n";
}
mysqli_query($conexionWrite, 'SET FOREIGN_KEY_CHECKS=1');

$resCount = mysqli_query($conexion, 'SELECT COUNT(*) AS total FROM clientes_old');
if ($resCount === false) {
    migrar_clientes_abort('Error COUNT clientes_old: ' . mysqli_error($conexion), $conexion, $conexionWrite);
}
$rowCount = mysqli_fetch_assoc($resCount);
$totalGlobal = (int) ($rowCount['total'] ?? 0);
mysqli_free_result($resCount);

echo "\n=== MIGRACIÓN ===\n";
echo "Total origen (clientes_old): {$totalGlobal}\n";

$columnas = array(
    'id_cliente',
    'nombre',
    'apellido',
    'tipo_identificacion',
    'identificacion',
    'nacionalidad',
    'telefono',
    'sucursal',
    'estado',
    'f_alta',
    'f_nacimiento',
    'movil',
    'email',
    'observaciones',
    'publicidad',
    'sexo',
    'f_vencimiento',
    'firma_cliente',
    'direccion',
    'c_provincia',
    'c_poblacion',
    'codigo_postal',
);

$selectParts = array();
foreach ($columnas as $col) {
    $selectParts[] = '`' . $col . '`';
}
$colsSql = implode(', ', $selectParts);

$query_cliente = 'INSERT INTO clientes (
    id_cliente, nombre, apellido, tipo_identificacion, tipo_identificacion_id,
    identificacion, nacionalidad, nacionalidad_id, telefono, sucursal,
    estado, f_alta, creado_por, delete_state
) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';

$stmt_cliente = mysqli_prepare($conexionWrite, $query_cliente);
if (!$stmt_cliente) {
    migrar_clientes_abort('Error prepare clientes: ' . mysqli_error($conexionWrite), $conexion, $conexionWrite);
}

$query_datos = 'INSERT INTO datos_clientes (
    rel_id_cliente, f_nacimiento, movil, email, observaciones,
    publicidad, sexo, f_vencimiento, firma_cliente
) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)';

$stmt_datos = mysqli_prepare($conexionWrite, $query_datos);
if (!$stmt_datos) {
    mysqli_stmt_close($stmt_cliente);
    migrar_clientes_abort('Error prepare datos_clientes: ' . mysqli_error($conexionWrite), $conexion, $conexionWrite);
}

$query_direccion = 'INSERT INTO direcciones (
    rel_id_item, type_direccion, direccion, c_provincia, c_poblacion,
    c_pais, codigo_postal, observaciones_direccion, rel_id_provincia,
    rel_id_pais, rel_id_poblacion
) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';

$stmt_direccion = mysqli_prepare($conexionWrite, $query_direccion);
if (!$stmt_direccion) {
    mysqli_stmt_close($stmt_datos);
    mysqli_stmt_close($stmt_cliente);
    migrar_clientes_abort('Error prepare direcciones: ' . mysqli_error($conexionWrite), $conexion, $conexionWrite);
}

$query = "SELECT {$colsSql} FROM clientes_old ORDER BY id_cliente ASC";
$result = mysqli_query($conexion, $query, MYSQLI_USE_RESULT);
if ($result === false) {
    mysqli_stmt_close($stmt_direccion);
    mysqli_stmt_close($stmt_datos);
    mysqli_stmt_close($stmt_cliente);
    migrar_clientes_abort('Error al consultar clientes_old: ' . mysqli_error($conexion), $conexion, $conexionWrite);
}

mysqli_begin_transaction($conexionWrite);

$n = 0;
$errorMigracion = null;

try {
    while ($cliente_old = mysqli_fetch_assoc($result)) {
        $id_cliente = (int) $cliente_old['id_cliente'];
        $nombre = (string) ($cliente_old['nombre'] ?? '');
        $apellido = (string) ($cliente_old['apellido'] ?? '');
        $tipo_identificacion = (string) ($cliente_old['tipo_identificacion'] ?? '');
        $identificacion = (string) ($cliente_old['identificacion'] ?? '');
        $nacionalidad = (string) ($cliente_old['nacionalidad'] ?? '');
        $telefono = (string) ($cliente_old['telefono'] ?? '');
        $sucursal = (int) ($cliente_old['sucursal'] ?? 0);
        $estado = migrar_clientes_estado($cliente_old['estado'] ?? '');
        $f_alta = migrar_clientes_fecha($cliente_old['f_alta'] ?? '', date('Y-m-d'));
        $tipo_identificacion_id = 0;
        $nacionalidad_id = 0;
        $creado_por = 0;
        $delete_state = 'false';
        $f_nacimiento = migrar_clientes_fecha($cliente_old['f_nacimiento'] ?? '');
        $movil = (string) ($cliente_old['movil'] ?? '');
        $email = (string) ($cliente_old['email'] ?? '');
        $observaciones = (string) ($cliente_old['observaciones'] ?? '');
        $publicidad = (string) ($cliente_old['publicidad'] ?? '');
        $sexo = (string) ($cliente_old['sexo'] ?? '');
        $f_vencimiento = migrar_clientes_fecha($cliente_old['f_vencimiento'] ?? '');
        $firma_cliente = (string) ($cliente_old['firma_cliente'] ?? '');
        $direccion = (string) ($cliente_old['direccion'] ?? '');
        $c_provincia = (string) ($cliente_old['c_provincia'] ?? '');
        $c_poblacion = (string) ($cliente_old['c_poblacion'] ?? '');
        $codigo_postal = (string) ($cliente_old['codigo_postal'] ?? '');
        $type_direccion = 'clientes';
        $c_pais = '';
        $observaciones_direccion = '';
        $rel_id_provincia = 0;
        $rel_id_pais = 0;
        $rel_id_poblacion = 0;

        mysqli_stmt_bind_param(
            $stmt_cliente,
            'isssissisissis',
            $id_cliente,
            $nombre,
            $apellido,
            $tipo_identificacion,
            $tipo_identificacion_id,
            $identificacion,
            $nacionalidad,
            $nacionalidad_id,
            $telefono,
            $sucursal,
            $estado,
            $f_alta,
            $creado_por,
            $delete_state
        );
        if (!mysqli_stmt_execute($stmt_cliente)) {
            throw new Exception('Error al insertar cliente ID ' . $id_cliente . ': ' . mysqli_stmt_error($stmt_cliente));
        }

        mysqli_stmt_bind_param(
            $stmt_datos,
            'issssssss',
            $id_cliente,
            $f_nacimiento,
            $movil,
            $email,
            $observaciones,
            $publicidad,
            $sexo,
            $f_vencimiento,
            $firma_cliente
        );
        if (!mysqli_stmt_execute($stmt_datos)) {
            throw new Exception('Error al insertar datos_clientes ID ' . $id_cliente . ': ' . mysqli_stmt_error($stmt_datos));
        }

        mysqli_stmt_bind_param(
            $stmt_direccion,
            'isssssssiii',
            $id_cliente,
            $type_direccion,
            $direccion,
            $c_provincia,
            $c_poblacion,
            $c_pais,
            $codigo_postal,
            $observaciones_direccion,
            $rel_id_provincia,
            $rel_id_pais,
            $rel_id_poblacion
        );
        if (!mysqli_stmt_execute($stmt_direccion)) {
            throw new Exception('Error al insertar direccion ID ' . $id_cliente . ': ' . mysqli_stmt_error($stmt_direccion));
        }

        $n++;
    }

    mysqli_commit($conexionWrite);

    if ($n > 0) {
        $resMax = mysqli_query($conexionWrite, 'SELECT IFNULL(MAX(id_cliente), 0) + 1 AS next_ai FROM clientes');
        if ($resMax) {
            $rowMax = mysqli_fetch_assoc($resMax);
            $nextAi = (int) ($rowMax['next_ai'] ?? 1);
            mysqli_free_result($resMax);
            mysqli_query($conexionWrite, 'ALTER TABLE clientes AUTO_INCREMENT = ' . $nextAi);
        }
    }
} catch (Exception $e) {
    mysqli_rollback($conexionWrite);
    $errorMigracion = $e->getMessage();
}

mysqli_stmt_close($stmt_cliente);
mysqli_stmt_close($stmt_datos);
mysqli_stmt_close($stmt_direccion);
mysqli_free_result($result);
mysqli_close($conexionWrite);
mysqli_close($conexion);

echo "\n=== RESUMEN ===\n";
if ($errorMigracion) {
    echo "Error en la migración (rollback): {$errorMigracion}\n";
    echo "Migrados antes del error: {$n}\n";
    echo "ERROR: migración incompleta\n";
    exit(1);
}

echo "Migración completada. Registros migrados: {$n} de {$totalGlobal}\n";
echo "Hecho.\n";
