<?php
/**
 * Consultas de im?genes para el visor unificado (camera).
 */

require_once __DIR__ . '/../../include/functions.php';

/**
 * @return array<int, array{id_foto:int,nombre_foto:string,foto:string,descripcion:string,fecha_subida:string}>
 */
function camera_catalog_imagenes_cliente(int $id_cliente): array
{
    if ($id_cliente <= 0) {
        throw new InvalidArgumentException('ID de cliente no v?lido');
    }

    $conexion = conectar_bd();
    if (!$conexion) {
        throw new RuntimeException('Error al conectar a la base de datos');
    }

    $query_cliente = 'SELECT id_cliente FROM clientes WHERE id_cliente = ?';
    $stmt_cliente = mysqli_prepare($conexion, $query_cliente);
    mysqli_stmt_bind_param($stmt_cliente, 'i', $id_cliente);
    mysqli_stmt_execute($stmt_cliente);
    $result_cliente = mysqli_stmt_get_result($stmt_cliente);

    if (!$result_cliente || mysqli_num_rows($result_cliente) === 0) {
        mysqli_stmt_close($stmt_cliente);
        mysqli_close($conexion);
        throw new RuntimeException('Cliente no encontrado');
    }
    mysqli_stmt_close($stmt_cliente);

    $imagenes = [];
    $photos_dir = __DIR__ . '/../../photos/';
    $result_tablas = mysqli_query($conexion, "SHOW TABLES LIKE 'fotos_app_%'");
    if ($result_tablas) {
        while ($row_tabla = mysqli_fetch_row($result_tablas)) {
            if (!preg_match('/^fotos_app_\d+$/', $row_tabla[0])) {
                continue;
            }
            $query = '
                SELECT id_foto, nombre_foto
                FROM ' . $row_tabla[0] . '
                WHERE id_cliente = ?
                ORDER BY id_foto DESC
            ';
            $stmt = mysqli_prepare($conexion, $query);
            if (!$stmt) {
                continue;
            }
            mysqli_stmt_bind_param($stmt, 'i', $id_cliente);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            if ($result) {
                while ($row = mysqli_fetch_assoc($result)) {
                    $ruta_archivo = $photos_dir . $row['nombre_foto'];
                    if (file_exists($ruta_archivo)) {
                        $imagenes[] = [
                            'id_foto' => (int) $row['id_foto'],
                            'nombre_foto' => $row['nombre_foto'],
                            'foto' => $row['nombre_foto'],
                            'descripcion' => '',
                            'fecha_subida' => '',
                        ];
                    }
                }
            }
            mysqli_stmt_close($stmt);
        }
    }

    mysqli_close($conexion);

    return $imagenes;
}

/**
 * @return array<int, array{id_foto:int,nombre_foto:string,foto:string,descripcion:string,fecha_subida:string}>
 */
function camera_catalog_imagenes_lote(int $id_lote, int $id_sucursal): array
{
    if ($id_lote <= 0) {
        throw new InvalidArgumentException('ID de lote no v?lido');
    }
    if ($id_sucursal <= 0) {
        throw new InvalidArgumentException('ID de sucursal no v?lido');
    }

    $conexion = conectar_bd();
    if (!$conexion) {
        throw new RuntimeException('Error al conectar a la base de datos');
    }

    $tabla_fotos = 'fotos_app_' . $id_sucursal;
    $query = '
        SELECT id_foto, nombre_foto
        FROM ' . $tabla_fotos . '
        WHERE id_lote = ?
        ORDER BY id_foto DESC
    ';
    $stmt = mysqli_prepare($conexion, $query);
    if (!$stmt) {
        mysqli_close($conexion);
        throw new RuntimeException(mysqli_error($conexion));
    }
    mysqli_stmt_bind_param($stmt, 'i', $id_lote);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $imagenes = [];
    $photos_dir = __DIR__ . '/../../photos/';
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $nombre = (string) ($row['nombre_foto'] ?? '');
            if ($nombre === '') {
                continue;
            }
            $ruta_archivo = $photos_dir . $nombre;
            if (file_exists($ruta_archivo)) {
                $imagenes[] = [
                    'id_foto' => (int) $row['id_foto'],
                    'nombre_foto' => $nombre,
                    'foto' => $nombre,
                    'descripcion' => '',
                    'fecha_subida' => '',
                ];
            }
        }
    }

    mysqli_stmt_close($stmt);
    mysqli_close($conexion);
    return $imagenes;
}

/**
 * Renovaci?n: 1 foto en historico_renovaciones_{sucursal}.
 *
 * @return array<int, array{id_foto:int,nombre_foto:string,foto:string,descripcion:string,fecha_subida:string}>
 */
function camera_catalog_imagenes_renovacion(int $id_renovacion, int $id_sucursal): array
{
    if ($id_renovacion <= 0) {
        throw new InvalidArgumentException('ID de renovaci?n no v?lido');
    }
    if ($id_sucursal <= 0) {
        throw new InvalidArgumentException('ID de sucursal no v?lido');
    }

    $conexion = conectar_bd();
    if (!$conexion) {
        throw new RuntimeException('Error al conectar a la base de datos');
    }

    $tabla = 'historico_renovaciones_' . $id_sucursal;
    $query = '
        SELECT id_renovaciones, nombre_foto
        FROM ' . $tabla . '
        WHERE id_renovaciones = ?
        LIMIT 1
    ';
    $stmt = mysqli_prepare($conexion, $query);
    if (!$stmt) {
        mysqli_close($conexion);
        throw new RuntimeException(mysqli_error($conexion));
    }
    mysqli_stmt_bind_param($stmt, 'i', $id_renovacion);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = $result ? mysqli_fetch_assoc($result) : null;

    $imagenes = [];
    $nombre = $row && isset($row['nombre_foto']) ? trim((string) $row['nombre_foto']) : '';
    if ($nombre !== '') {
        $photos_dir = __DIR__ . '/../../photos/';
        if (file_exists($photos_dir . $nombre)) {
            $imagenes[] = [
                'id_foto' => $id_renovacion,
                'nombre_foto' => $nombre,
                'foto' => $nombre,
                'descripcion' => '',
                'fecha_subida' => '',
            ];
        }
    }

    mysqli_stmt_close($stmt);
    mysqli_close($conexion);
    return $imagenes;
}

/**
 * Adelanto (cache): 1 foto en fotos_app_adelantos_cache por id_foto.
 *
 * @return array<int, array{id_foto:int,nombre_foto:string,foto:string,descripcion:string,fecha_subida:string}>
 */
function camera_catalog_imagenes_adelanto_cache(int $id_foto_cache): array
{
    if ($id_foto_cache <= 0) {
        throw new InvalidArgumentException('ID de foto (cache) no v?lido');
    }

    $conexion = conectar_bd();
    if (!$conexion) {
        throw new RuntimeException('Error al conectar a la base de datos');
    }

    $query = '
        SELECT id_foto, nombre_foto
        FROM fotos_app_adelantos_cache
        WHERE id_foto = ?
          AND nombre_foto <> \"\"
        LIMIT 1
    ';
    $stmt = mysqli_prepare($conexion, $query);
    if (!$stmt) {
        mysqli_close($conexion);
        throw new RuntimeException(mysqli_error($conexion));
    }
    mysqli_stmt_bind_param($stmt, 'i', $id_foto_cache);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = $result ? mysqli_fetch_assoc($result) : null;

    $imagenes = [];
    $nombre = $row && isset($row['nombre_foto']) ? trim((string) $row['nombre_foto']) : '';
    if ($nombre !== '') {
        $photos_dir = __DIR__ . '/../../photos/';
        if (file_exists($photos_dir . $nombre)) {
            $imagenes[] = [
                'id_foto' => $id_foto_cache,
                'nombre_foto' => $nombre,
                'foto' => $nombre,
                'descripcion' => '',
                'fecha_subida' => '',
            ];
        }
    }

    mysqli_stmt_close($stmt);
    mysqli_close($conexion);
    return $imagenes;
}

/**
 * Adelanto (cache) filtrado por sucursal: evita colisiones si id_foto no es globalmente ?nico.
 *
 * @return array<int, array{id_foto:int,nombre_foto:string,foto:string,descripcion:string,fecha_subida:string}>
 */
function camera_catalog_imagenes_adelanto_cache_sucursal(int $id_foto_cache, int $id_sucursal): array
{
    if ($id_foto_cache <= 0) {
        throw new InvalidArgumentException('ID de foto (cache) no v?lido');
    }
    if ($id_sucursal <= 0) {
        throw new InvalidArgumentException('ID de sucursal no v?lido');
    }

    $conexion = conectar_bd();
    if (!$conexion) {
        throw new RuntimeException('Error al conectar a la base de datos');
    }

    $query = '
        SELECT id_foto, nombre_foto
        FROM fotos_app_adelantos_cache
        WHERE id_foto = ?
          AND id_sucursal = ?
          AND nombre_foto <> ""
        LIMIT 1
    ';
    $stmt = mysqli_prepare($conexion, $query);
    if (!$stmt) {
        mysqli_close($conexion);
        throw new RuntimeException(mysqli_error($conexion));
    }
    mysqli_stmt_bind_param($stmt, 'ii', $id_foto_cache, $id_sucursal);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = $result ? mysqli_fetch_assoc($result) : null;

    $imagenes = [];
    $nombre = $row && isset($row['nombre_foto']) ? trim((string) $row['nombre_foto']) : '';
    if ($nombre !== '') {
        $photos_dir = __DIR__ . '/../../photos/';
        if (file_exists($photos_dir . $nombre)) {
            $imagenes[] = [
                'id_foto' => $id_foto_cache,
                'nombre_foto' => $nombre,
                'foto' => $nombre,
                'descripcion' => '',
                'fecha_subida' => '',
            ];
        }
    }

    mysqli_stmt_close($stmt);
    mysqli_close($conexion);
    return $imagenes;
}

/**
 * Art?culo: im?genes en articulos_venta_imagenes.
 *
 * @return array<int, array{id_foto:int,nombre_foto:string,foto:string,descripcion:string,fecha_subida:string}>
 */
function camera_catalog_imagenes_articulo(int $id_articulo): array
{
    if ($id_articulo <= 0) {
        throw new InvalidArgumentException('ID de art?culo no v?lido');
    }

    $conexion = conectar_bd();
    if (!$conexion) {
        throw new RuntimeException('Error al conectar a la base de datos');
    }

    $stArt = mysqli_prepare($conexion, 'SELECT sku FROM articulos WHERE sku = ? LIMIT 1');
    if (!$stArt) {
        $err = mysqli_error($conexion);
        mysqli_close($conexion);
        throw new RuntimeException($err);
    }
    mysqli_stmt_bind_param($stArt, 'i', $id_articulo);
    mysqli_stmt_execute($stArt);
    $rArt = mysqli_stmt_get_result($stArt);
    if (!$rArt || !mysqli_fetch_assoc($rArt)) {
        mysqli_stmt_close($stArt);
        mysqli_close($conexion);
        throw new RuntimeException('Artículo no encontrado');
    }
    mysqli_stmt_close($stArt);

    $stmt = mysqli_prepare($conexion, 'SELECT id, src FROM articulos_venta_imagenes WHERE rel_sku_articulo = ? ORDER BY id DESC');
    if (!$stmt) {
        mysqli_close($conexion);
        throw new RuntimeException(mysqli_error($conexion));
    }
    mysqli_stmt_bind_param($stmt, 'i', $id_articulo);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $imagenes = [];
    $photos_dir = __DIR__ . '/../../photos/';
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $src = isset($row['src']) ? trim((string) $row['src']) : '';
            if ($src === '') {
                continue;
            }
            if (file_exists($photos_dir . $src)) {
                $imagenes[] = [
                    'id_foto' => (int) $row['id'],
                    'nombre_foto' => $src,
                    'foto' => $src,
                    'descripcion' => '',
                    'fecha_subida' => '',
                ];
            }
        }
    }

    mysqli_stmt_close($stmt);
    mysqli_close($conexion);
    return $imagenes;
}

/**
 * Venta: comprobantes en ventas_imagenes.
 *
 * @return array<int, array{id_foto:int,nombre_foto:string,foto:string,descripcion:string,fecha_subida:string}>
 */
function camera_catalog_imagenes_venta(int $id_venta): array
{
    if ($id_venta <= 0) {
        throw new InvalidArgumentException('ID de venta no v?lido');
    }

    $conexion = conectar_bd();
    if (!$conexion) {
        throw new RuntimeException('Error al conectar a la base de datos');
    }

    $stmt = mysqli_prepare($conexion, 'SELECT id FROM ventas WHERE id = ? LIMIT 1');
    if (!$stmt) {
        mysqli_close($conexion);
        throw new RuntimeException(mysqli_error($conexion));
    }
    mysqli_stmt_bind_param($stmt, 'i', $id_venta);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $ok = $res && mysqli_fetch_assoc($res);
    mysqli_stmt_close($stmt);
    if (!$ok) {
        mysqli_close($conexion);
        return [];
    }

    $stmt2 = mysqli_prepare($conexion, 'SELECT id, src FROM ventas_imagenes WHERE id_venta = ? ORDER BY id DESC');
    if (!$stmt2) {
        mysqli_close($conexion);
        throw new RuntimeException(mysqli_error($conexion));
    }
    mysqli_stmt_bind_param($stmt2, 'i', $id_venta);
    mysqli_stmt_execute($stmt2);
    $res2 = mysqli_stmt_get_result($stmt2);

    $imagenes = [];
    $photos_dir = __DIR__ . '/../../photos/';
    if ($res2) {
        while ($row = mysqli_fetch_assoc($res2)) {
            $src = isset($row['src']) ? trim((string) $row['src']) : '';
            if ($src === '') {
                continue;
            }
            if (file_exists($photos_dir . $src)) {
                $imagenes[] = [
                    'id_foto' => (int) $row['id'],
                    'nombre_foto' => $src,
                    'foto' => $src,
                    'descripcion' => '',
                    'fecha_subida' => '',
                ];
            }
        }
    }

    mysqli_stmt_close($stmt2);
    mysqli_close($conexion);
    return $imagenes;
}

/**
 * Ticket de venta: fotos en articulos_venta_imagenes (todas las l?neas del ticket).
 *
 * @return array<int, array{id_foto:int,nombre_foto:string,foto:string,descripcion:string,fecha_subida:string}>
 */
function camera_catalog_imagenes_articulo_venta_ticket(int $id_venta): array
{
    if ($id_venta <= 0) {
        throw new InvalidArgumentException('ID de venta no v?lido');
    }

    require_once __DIR__ . '/../../parts/ventas/main/_ticket_articulos_ids.php';

    $conexion = conectar_bd();
    if (!$conexion) {
        throw new RuntimeException('Error al conectar a la base de datos');
    }

    $ids = ventas_main_obtener_ids_articulo_venta_ticket($conexion, $id_venta);
    if (count($ids) === 0) {
        mysqli_close($conexion);
        return [];
    }

    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $types = str_repeat('i', count($ids));
    $sql = 'SELECT id, src, id_articulo_venta FROM articulos_venta_imagenes WHERE id_articulo_venta IN (' . $placeholders . ') ORDER BY id DESC';
    $stmt = mysqli_prepare($conexion, $sql);
    if (!$stmt) {
        mysqli_close($conexion);
        throw new RuntimeException(mysqli_error($conexion));
    }
    mysqli_stmt_bind_param($stmt, $types, ...$ids);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);

    $imagenes = [];
    $photos_dir = __DIR__ . '/../../photos/';
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
            $src = isset($row['src']) ? trim((string) $row['src']) : '';
            if ($src === '') {
                continue;
            }
            if (file_exists($photos_dir . $src)) {
                $imagenes[] = [
                    'id_foto' => (int) $row['id'],
                    'nombre_foto' => $src,
                    'foto' => $src,
                    'descripcion' => '',
                    'fecha_subida' => '',
                ];
            }
        }
    }

    mysqli_stmt_close($stmt);
    mysqli_close($conexion);
    return $imagenes;
}

/**
 * Tablas SQL permitidas para el módulo de gastos.
 */
function camera_fotos_gasto_resolver_tabla_gastos(string $tabla = 'gastos'): string
{
    $permitidas = array(
        'gastos' => 'gastos',
        'gastos_pruebas' => 'gastos_pruebas',
    );
    if (!isset($permitidas[$tabla])) {
        throw new InvalidArgumentException('Tabla de gastos no permitida');
    }
    return $permitidas[$tabla];
}

function camera_fotos_gasto_resolver_tabla_fotos(string $tabla = 'fotos_gastos'): string
{
    $permitidas = array(
        'fotos_gastos' => 'fotos_gastos',
        'fotos_gastos_pruebas' => 'fotos_gastos_pruebas',
    );
    if (!isset($permitidas[$tabla])) {
        throw new InvalidArgumentException('Tabla de fotos de gasto no permitida');
    }
    return $permitidas[$tabla];
}

/**
 * Datos del gasto necesarios para insertar en fotos_gastos.
 *
 * @return array{id_empresa:int,id_sucursal:int,subida_por:int}
 */
function camera_fotos_gasto_datos_desde_gasto($conexion, int $id_gasto, string $tabla_gastos = 'gastos'): array
{
    $tabla_gastos = camera_fotos_gasto_resolver_tabla_gastos($tabla_gastos);
    $sql = 'SELECT empresa_gasto, sucursal_gasto, usuario_creacion_gasto FROM ' . $tabla_gastos . ' WHERE id_gasto = ? LIMIT 1';
    $stmt = mysqli_prepare($conexion, $sql);
    if (!$stmt) {
        throw new RuntimeException(mysqli_error($conexion));
    }
    mysqli_stmt_bind_param($stmt, 'i', $id_gasto);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = $result ? mysqli_fetch_assoc($result) : null;
    mysqli_stmt_close($stmt);

    if (!$row) {
        throw new InvalidArgumentException('Gasto no encontrado');
    }

    return array(
        'id_empresa' => (int) ($row['empresa_gasto'] ?? 0),
        'id_sucursal' => (int) ($row['sucursal_gasto'] ?? 0),
        'subida_por' => (int) ($row['usuario_creacion_gasto'] ?? 0),
    );
}

/**
 * Normaliza metodo_subida al enum de fotos_gastos.
 */
function camera_fotos_gasto_normalizar_metodo_subida($metodo_subida): string
{
    $metodo = strtolower(trim((string) $metodo_subida));
    return $metodo === 'automatico' ? 'automatico' : 'manual';
}

/**
 * Resuelve id_empresa para un gasto (POST o tabla gastos).
 */
function camera_fotos_gasto_resolver_id_empresa($conexion, int $id_gasto, int $id_empresa = 0): int
{
    if ($id_empresa > 0) {
        return $id_empresa;
    }
    $datos = camera_fotos_gasto_datos_desde_gasto($conexion, $id_gasto);
    return $datos['id_empresa'];
}

/**
 * Inserta documento de gasto en fotos_gastos (tabla existente en BD).
 *
 * @return int id_foto insertado
 */
function camera_insertar_foto_gasto(
    $conexion,
    int $id_gasto,
    int $id_empresa,
    int $id_sucursal,
    string $nombre_foto,
    int $subida_por = 0,
    string $metodo_subida = 'manual',
    string $tabla_gastos = 'gastos',
    string $tabla_fotos = 'fotos_gastos'
): int {
    if (!$conexion) {
        throw new RuntimeException('Error de conexión a la base de datos');
    }
    if ($id_gasto <= 0) {
        throw new InvalidArgumentException('ID de gasto no válido');
    }
    if ($nombre_foto === '') {
        throw new InvalidArgumentException('Nombre de foto no válido');
    }

    $tabla_gastos = camera_fotos_gasto_resolver_tabla_gastos($tabla_gastos);
    $tabla_fotos = camera_fotos_gasto_resolver_tabla_fotos($tabla_fotos);

    $nombre_foto = substr($nombre_foto, 0, 71);

    $datos = camera_fotos_gasto_datos_desde_gasto($conexion, $id_gasto, $tabla_gastos);

    if ($id_empresa <= 0) {
        $id_empresa = $datos['id_empresa'];
    }
    if ($id_sucursal <= 0) {
        $id_sucursal = $datos['id_sucursal'];
    }
    if ($subida_por <= 0) {
        $subida_por = $datos['subida_por'];
    }
    if ($id_empresa <= 0) {
        throw new InvalidArgumentException('ID de empresa no válido para el gasto');
    }
    if ($subida_por <= 0) {
        throw new InvalidArgumentException('Usuario de subida no válido');
    }

    $fecha_create = date('Y-m-d');
    $metodo_subida = camera_fotos_gasto_normalizar_metodo_subida($metodo_subida);

    $query = '
        INSERT INTO ' . $tabla_fotos . ' (nombre_foto, id_sucursal, id_gasto, id_empresa, fecha_create, subida_por, metodo_subida)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ';
    $stmt = mysqli_prepare($conexion, $query);
    if (!$stmt) {
        throw new RuntimeException(mysqli_error($conexion));
    }
    mysqli_stmt_bind_param(
        $stmt,
        'siiisis',
        $nombre_foto,
        $id_sucursal,
        $id_gasto,
        $id_empresa,
        $fecha_create,
        $subida_por,
        $metodo_subida
    );

    if (!mysqli_stmt_execute($stmt)) {
        $err = mysqli_stmt_error($stmt);
        mysqli_stmt_close($stmt);
        throw new RuntimeException('Error al guardar en la base de datos: ' . $err);
    }
    $id_foto = (int) mysqli_insert_id($conexion);
    mysqli_stmt_close($stmt);
    return $id_foto;
}

function camera_insertar_foto_gasto_prueba(
    $conexion,
    int $id_gasto,
    int $id_empresa,
    int $id_sucursal,
    string $nombre_foto,
    int $subida_por = 0,
    string $metodo_subida = 'manual'
): int {
    return camera_insertar_foto_gasto(
        $conexion,
        $id_gasto,
        $id_empresa,
        $id_sucursal,
        $nombre_foto,
        $subida_por,
        $metodo_subida,
        'gastos_pruebas',
        'fotos_gastos_pruebas'
    );
}

/**
 * @return array<int, array{id_foto:int,nombre_foto:string,foto:string,descripcion:string,fecha_subida:string}>
 */
function camera_catalog_imagenes_gasto(int $id_gasto, string $tabla_fotos = 'fotos_gastos'): array
{
    if ($id_gasto <= 0) {
        throw new InvalidArgumentException('ID de gasto no v?lido');
    }

    $tabla_fotos = camera_fotos_gasto_resolver_tabla_fotos($tabla_fotos);

    $conexion = conectar_bd();
    if (!$conexion) {
        throw new RuntimeException('Error al conectar a la base de datos');
    }

    $query = '
        SELECT id_foto, nombre_foto
        FROM ' . $tabla_fotos . '
        WHERE id_gasto = ?
        ORDER BY id_foto DESC
    ';
    $stmt = mysqli_prepare($conexion, $query);
    if (!$stmt) {
        mysqli_close($conexion);
        throw new RuntimeException(mysqli_error($conexion));
    }
    mysqli_stmt_bind_param($stmt, 'i', $id_gasto);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $imagenes = [];
    $photos_dir = __DIR__ . '/../../photos/';
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $nombre = (string) ($row['nombre_foto'] ?? '');
            if ($nombre === '') {
                continue;
            }
            $ruta_archivo = $photos_dir . $nombre;
            if (file_exists($ruta_archivo)) {
                $imagenes[] = [
                    'id_foto' => (int) $row['id_foto'],
                    'nombre_foto' => $nombre,
                    'foto' => $nombre,
                    'descripcion' => '',
                    'fecha_subida' => '',
                ];
            }
        }
    }

    mysqli_stmt_close($stmt);
    mysqli_close($conexion);

    return $imagenes;
}

/**
 * @return array<int, array{id_foto:int,nombre_foto:string,foto:string,descripcion:string,fecha_subida:string}>
 */
function camera_catalog_imagenes_traspaso(int $id_traspaso): array
{
    if ($id_traspaso <= 0) {
        throw new InvalidArgumentException('ID de traspaso no valido');
    }

    $conexion = conectar_bd();
    if (!$conexion) {
        throw new RuntimeException('Error al conectar a la base de datos');
    }

    $query = '
        SELECT id_foto, nombre_foto
        FROM fotos_traspasos
        WHERE id_trapaso = ?
        ORDER BY id_foto DESC
    ';
    $stmt = mysqli_prepare($conexion, $query);
    if (!$stmt) {
        mysqli_close($conexion);
        throw new RuntimeException(mysqli_error($conexion));
    }
    mysqli_stmt_bind_param($stmt, 'i', $id_traspaso);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $imagenes = [];
    $photos_dir = __DIR__ . '/../../photos/';
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $nombre = (string) ($row['nombre_foto'] ?? '');
            if ($nombre === '') {
                continue;
            }
            $ruta_archivo = $photos_dir . $nombre;
            if (file_exists($ruta_archivo)) {
                $imagenes[] = [
                    'id_foto' => (int) $row['id_foto'],
                    'nombre_foto' => $nombre,
                    'foto' => $nombre,
                    'descripcion' => '',
                    'fecha_subida' => '',
                ];
            }
        }
    }

    mysqli_stmt_close($stmt);
    mysqli_close($conexion);

    return $imagenes;
}
