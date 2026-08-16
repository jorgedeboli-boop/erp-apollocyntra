<?php
/**
 * Registra una venta TPV: una sola fila en `ventas` por ticket, histórico por línea en `rel_articulos_venta`,
 * actualiza `articulos_venta` y `rel_articulos_estados`, trazabilidad.
 */
require_once __DIR__ . '/../../../include/session.php';
require_once __DIR__ . '/../../../include/functions.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!is_array($data)) {
    echo json_encode(['success' => false, 'message' => 'JSON inválido']);
    exit;
}

/**
 * @param float|int|string $a
 * @param float|int|string $b
 */
function venta_importes_coinciden($a, $b)
{
    return abs(round((float) $a, 2) - round((float) $b, 2)) < 0.01;
}

$sucursal_venta = isset($data['sucursal_venta']) ? (int) $data['sucursal_venta'] : 0;
$id_cliente_raw = isset($data['id_cliente']) ? trim((string) $data['id_cliente']) : '';
$id_cliente = $id_cliente_raw !== '' ? (int) $id_cliente_raw : 0;
$datos_cliente_venta = isset($data['cliente']) && is_array($data['cliente']) ? $data['cliente'] : array();
if ($id_cliente <= 0 && isset($datos_cliente_venta['id_cliente'])) {
    $id_cliente = (int) $datos_cliente_venta['id_cliente'];
}
$datos_cliente_venta['id_cliente'] = $id_cliente;

$tipo_venta = isset($data['tipo_venta']) ? strtolower(trim((string) $data['tipo_venta'])) : 'normal';   
$numero_plazos_raw = isset($data['numero_plazos']) ? trim((string) $data['numero_plazos']) : '';
$numero_plazos = $numero_plazos_raw !== '' ? (int) $numero_plazos_raw : 0;

if($origen_parset != "Central"){
    $origen = $origen_parset;
}else{
    $origen = "Central";
}

$tipo_factura = isset($data['tipo_factura']) ? strtolower(trim((string) $data['tipo_factura'])) : '';


$forma_pago = isset($data['forma_pago']) ? strtolower(trim((string) $data['forma_pago'])) : '';
$permitidas = ['contado', 'tarjeta', 'bizum', 'transferencia', 'combinado'];
if (!in_array($forma_pago, $permitidas, true)) {
    echo json_encode(['success' => false, 'message' => 'Forma de pago no válida']);
    exit;
}

$articulos = isset($data['articulos']) && is_array($data['articulos']) ? $data['articulos'] : [];
if (count($articulos) === 0) {
    echo json_encode(['success' => false, 'message' => 'No hay artículos en la venta']);
    exit;
}

$importe_cliente = isset($data['importe_a_cobrar']) ? (float) $data['importe_a_cobrar'] : 0;
$cobro = isset($data['cobro']) && is_array($data['cobro']) ? $data['cobro'] : [];

$total_venta_base = 0.0;
foreach ($articulos as $row) {
    $total_venta_base += isset($row['precio']) ? (float) $row['precio'] : 0;
}

$venta_plazos_db = ($tipo_venta === 'plazos') ? 'si' : 'no';
$numero_plazos_db = ($venta_plazos_db === 'si') ? ($numero_plazos > 0 ? $numero_plazos : 3) : 0;

$intereses = 0;
$porcentaje_plazos = 0;
$interes_plazos_cliente = isset($data['interes_plazos']) ? (float) $data['interes_plazos'] : null;
if ($venta_plazos_db === 'si') {
    if ($interes_plazos_cliente !== null && $interes_plazos_cliente >= 0 && $interes_plazos_cliente <= 100) {
        $intereses = $interes_plazos_cliente;
        $porcentaje_plazos = $interes_plazos_cliente;
    } elseif ($numero_plazos_db === 6) {
        $intereses = 6;
        $porcentaje_plazos = 6;
    } elseif ($numero_plazos_db === 12) {
        $intereses = 10;
        $porcentaje_plazos = 10;
    }
}

// El interés a plazos ya está repartido proporcionalmente en el precio de cada artículo (cliente).
$total_venta = $total_venta_base;

$importe_esperado = $total_venta;
if ($tipo_venta === 'plazos' && $numero_plazos > 0) {
    $importe_esperado = round($total_venta / $numero_plazos, 2);
}

if (!venta_importes_coinciden($importe_cliente, $importe_esperado)) {
    echo json_encode(['success' => false, 'message' => 'El importe a cobrar no coincide con el cálculo del servidor.']);
    exit;
}

if ($forma_pago === 'combinado') {
    $c = isset($cobro['combinado']) && is_array($cobro['combinado']) ? $cobro['combinado'] : [];
    $sum = (float) ($c['contado'] ?? 0) + (float) ($c['tarjeta'] ?? 0) + (float) ($c['bizum'] ?? 0) + (float) ($c['transferencia'] ?? 0);
    if (!venta_importes_coinciden($sum, $importe_esperado)) {
        echo json_encode(['success' => false, 'message' => 'La suma del pago combinado no coincide con el total a cobrar.']);
        exit;
    }
} elseif ($forma_pago === 'contado') {
    $ent = isset($cobro['importe_entregado']) ? (float) $cobro['importe_entregado'] : 0;
    if ($ent + 1e-9 < $importe_esperado - 0.009) {
        echo json_encode(['success' => false, 'message' => 'El importe entregado es insuficiente.']);
        exit;
    }
} else {
    $ent = isset($cobro['importe_entregado']) ? (float) $cobro['importe_entregado'] : 0;
    if (!venta_importes_coinciden($ent, $importe_esperado)) {
        echo json_encode(['success' => false, 'message' => 'El importe debe coincidir exactamente con el total a cobrar.']);
        exit;
    }
}

$usuario_id = isset($_SESSION['usuario_id']) ? (int) $_SESSION['usuario_id'] : 0;
if ($usuario_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Sesión de usuario no válida']);
    exit;
}

$rel_id_empresa_usuario = obtener_rel_id_empresa_sesion();
if ($rel_id_empresa_usuario <= 0) {
    echo json_encode(['success' => false, 'message' => 'El usuario no tiene empresa asignada']);
    exit;
}

if ($venta_plazos_db === 'si') {
    $estado_venta = "enfecha";
    $estado_articulo = "reservado";
    // en rel_estados_articulos, actualizar el estado_articulo a "reservado" es igual
}else{
    $estado_venta = "vendido";
    $estado_articulo = "vendido";
    // en rel_estados_articulos, actualizar el estado_articulo a "Vendido" con mayuscula
}

$cant_contado = 0.0;
$cant_tarjeta = 0.0;
$cant_transferencia = 0.0;
$cant_bizum = 0.0;
if ($forma_pago === 'combinado') {
    $c = $cobro['combinado'];
    $cant_contado = (float) ($c['contado'] ?? 0);
    $cant_tarjeta = (float) ($c['tarjeta'] ?? 0);
    $cant_bizum = (float) ($c['bizum'] ?? 0);
    $cant_transferencia = (float) ($c['transferencia'] ?? 0);
} elseif ($forma_pago === 'contado') {
    $cant_contado = $importe_esperado;
} elseif ($forma_pago === 'tarjeta') {
    $cant_tarjeta = $importe_esperado;
} elseif ($forma_pago === 'bizum') {
    $cant_bizum = $importe_esperado;
} else {
    $cant_transferencia = $importe_esperado;
}

$tipo_pago_db = $forma_pago === 'combinado' ? 'combinado' : $forma_pago;

$conexion = conectar_bd();
if (!$conexion) {
    echo json_encode(['success' => false, 'message' => 'No hay conexión a la base de datos']);
    exit;
}

$id_venta_pk = 0;

try {
    mysqli_begin_transaction($conexion);


    // comprobar si existe articulo_venta, comprobar si existe rel_articulos_estados, si no existe, crearlo

    if (!function_exists('crear_rel_articulos_estados_desde_articulos_venta')) {
        /**
         * Crea el registro en rel_articulos_estados cuando falta (a partir de articulos_venta y tablas origen).
         * Requerido para ventas donde el histórico no se generó previamente.
         */
        function crear_rel_articulos_estados_desde_articulos_venta(mysqli $conexion, int $id_articulo_venta, int $id_sucursal_venta, float $precio_venta): void
        {
            // Leer articulos_venta completo
            $stmt = mysqli_prepare($conexion, 'SELECT * FROM articulos_venta WHERE id = ? LIMIT 1');
            if (!$stmt) {
                throw new Exception('Error al preparar SELECT articulos_venta: ' . mysqli_error($conexion));
            }
            mysqli_stmt_bind_param($stmt, 'i', $id_articulo_venta);
            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception('Error al ejecutar SELECT articulos_venta: ' . mysqli_stmt_error($stmt));
            }
            $res = mysqli_stmt_get_result($stmt);
            $row = $res ? mysqli_fetch_assoc($res) : null;
            mysqli_stmt_close($stmt);
            if (!$row) {
                throw new Exception('No se encontró articulos_venta para el artículo ' . $id_articulo_venta . '.');
            }

            $id_sucursal_origen = (int) ($row['id_sucursal_origen'] ?? 0);
            $id_lote_origen = (int) ($row['id_lote_origen'] ?? 0);
            $rel_id_articulo = (int) ($row['id_articulo_sucursal'] ?? 0);
            $ley = (string) ($row['ley'] ?? '');
            $peso = (float) ($row['peso'] ?? 0);
            $precio_coste = (float) ($row['precio_coste'] ?? 0);

            // Tipo de artículo (Oro/Plata)
            $tipo_raw = strtolower(trim((string) ($row['tipo_articulo'] ?? $row['tipo'] ?? '')));
            $tipo_de_articulo = ($tipo_raw === 'oro') ? 'Oro' : 'Plata';

            // Defaults
            $fecha_compra_articulo = '0000-00-00';
            $articulo_auditado = 'false';
            $rel_id_proforma = 0;
            $rel_proforma_state = 'false';
            $rel_id_item_proforma = 0;
            $rel_numero_semana = 0;

            // Leer articulos_{id_sucursal_origen}
            if ($id_sucursal_origen > 0 && $rel_id_articulo > 0) {
                $tabla_articulos = 'articulos_' . (int) $id_sucursal_origen;
                $sqlArt = "SELECT * FROM `$tabla_articulos` WHERE id_articulo = ? LIMIT 1";
                $stmt2 = mysqli_prepare($conexion, $sqlArt);
                if (!$stmt2) {
                    throw new Exception('Error al preparar SELECT ' . $tabla_articulos . ': ' . mysqli_error($conexion));
                }
                mysqli_stmt_bind_param($stmt2, 'i', $rel_id_articulo);
                if (mysqli_stmt_execute($stmt2)) {
                    $res2 = mysqli_stmt_get_result($stmt2);
                    $row2 = $res2 ? mysqli_fetch_assoc($res2) : null;
                    if ($row2) {
                        $fecha_compra_articulo = (string) ($row2['fecha_compra_articulo'] ?? $fecha_compra_articulo);
                        $articulo_auditado = (string) ($row2['articulo_auditado'] ?? $articulo_auditado);
                        $rel_id_proforma = (int) ($row2['rel_id_proforma'] ?? $rel_id_proforma);
                        $rel_proforma_state = (string) ($row2['rel_proforma_state'] ?? $rel_proforma_state);
                        $rel_id_item_proforma = (int) ($row2['rel_id_item_proforma'] ?? $rel_id_item_proforma);
                        $rel_numero_semana = (int) ($row2['rel_numero_semana'] ?? $rel_numero_semana);
                    }
                }
                mysqli_stmt_close($stmt2);
            }

            // Leer lotes_{id_sucursal_origen} para envío/empeño
            $rel_id_envio = 0;
            $articulo_empeno = 'false';
            if ($id_sucursal_origen > 0 && $id_lote_origen > 0) {
                $tabla_lotes = 'lotes_' . (int) $id_sucursal_origen;
                $sqlLote = "SELECT envio_numero, compra_opcion FROM `$tabla_lotes` WHERE id_lote = ? LIMIT 1";
                $stmt3 = mysqli_prepare($conexion, $sqlLote);
                if ($stmt3) {
                    mysqli_stmt_bind_param($stmt3, 'i', $id_lote_origen);
                    if (mysqli_stmt_execute($stmt3)) {
                        $res3 = mysqli_stmt_get_result($stmt3);
                        $row3 = $res3 ? mysqli_fetch_assoc($res3) : null;
                        if ($row3) {
                            $rel_id_envio = (int) ($row3['envio_numero'] ?? 0);
                            $compra_opcion = (string) ($row3['compra_opcion'] ?? 'no');
                            $articulo_empeno = ($compra_opcion === 'no') ? 'false' : 'true';
                        }
                    }
                    mysqli_stmt_close($stmt3);
                }
            }

            // Empresa de sucursal origen
            $rel_id_empresa = obtener_rel_id_empresa_sesion();

            // Insert en rel_articulos_estados
            $sqlInsRel = "
                INSERT INTO rel_articulos_estados (
                    rel_id_articulo,
                    rel_id_sucursal,
                    ley,
                    rel_id_lote,
                    tipo_de_articulo,
                    peso_articulo,
                    precio_compra_articulo,
                    fecha_compra_articulo,
                    estado_articulo,
                    articulo_auditado,
                    rel_id_proforma,
                    rel_proforma_state,
                    rel_id_item_proforma,
                    rel_numero_semana,
                    rel_id_empresa,
                    rel_id_envio,
                    rel_id_articulo_venta,
                    rel_id_sucursal_venta,
                    precio_venta,
                    articulo_empeno
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Stock', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ";
            $stmtIns = mysqli_prepare($conexion, $sqlInsRel);
            if (!$stmtIns) {
                throw new Exception('Error al preparar INSERT rel_articulos_estados: ' . mysqli_error($conexion));
            }

            mysqli_stmt_bind_param(
                $stmtIns,
                'iisisddssisiiiiiids',
                $rel_id_articulo,
                $id_sucursal_origen,
                $ley,
                $id_lote_origen,
                $tipo_de_articulo,
                $peso,
                $precio_coste,
                $fecha_compra_articulo,
                $articulo_auditado,
                $rel_id_proforma,
                $rel_proforma_state,
                $rel_id_item_proforma,
                $rel_numero_semana,
                $rel_id_empresa,
                $rel_id_envio,
                $id_articulo_venta,
                $id_sucursal_venta,
                $precio_venta,
                $articulo_empeno
            );
            if (!mysqli_stmt_execute($stmtIns)) {
                throw new Exception('Error al insertar rel_articulos_estados: ' . mysqli_stmt_error($stmtIns));
            }
            mysqli_stmt_close($stmtIns);
        }
    }

    // 1) Validar que TODOS los artículos existen en articulos_venta (si falta alguno, parar)
    $idsNoEncontrados = [];
    $stmtExisteAV = mysqli_prepare($conexion, 'SELECT id FROM articulos_venta WHERE id = ? LIMIT 1');
    if (!$stmtExisteAV) {
        throw new Exception('Error al preparar comprobación de articulos_venta: ' . mysqli_error($conexion));
    }
    foreach ($articulos as $artTmp) {
        $idTmp = isset($artTmp['id_articulo']) ? (int) $artTmp['id_articulo'] : 0;
        if ($idTmp <= 0) {
            $idsNoEncontrados[] = $idTmp;
            continue;
        }
        mysqli_stmt_bind_param($stmtExisteAV, 'i', $idTmp);
        if (!mysqli_stmt_execute($stmtExisteAV)) {
            mysqli_stmt_close($stmtExisteAV);
            throw new Exception('Error al comprobar articulos_venta: ' . mysqli_stmt_error($stmtExisteAV));
        }
        $rTmp = mysqli_stmt_get_result($stmtExisteAV);
        $ok = ($rTmp && mysqli_fetch_row($rTmp)) ? true : false;
        if (!$ok) {
            $idsNoEncontrados[] = $idTmp;
        }
    }
    mysqli_stmt_close($stmtExisteAV);
    if (count($idsNoEncontrados) > 0) {
        throw new Exception('Artículos en articulos_venta no encontrados: ' . implode(',', $idsNoEncontrados));
    }

    // 2) Asegurar rel_articulos_estados para TODOS los artículos (si falta, crearlo)
    $stmtExisteRel = mysqli_prepare($conexion, 'SELECT 1 FROM rel_articulos_estados WHERE rel_id_articulo_venta = ? LIMIT 1');
    if (!$stmtExisteRel) {
        throw new Exception('Error al preparar comprobación de rel_articulos_estados: ' . mysqli_error($conexion));
    }
    foreach ($articulos as $artTmp) {
        $idTmp = isset($artTmp['id_articulo']) ? (int) $artTmp['id_articulo'] : 0;
        $precioTmp = isset($artTmp['precio']) ? (float) $artTmp['precio'] : 0;
        mysqli_stmt_bind_param($stmtExisteRel, 'i', $idTmp);
        if (!mysqli_stmt_execute($stmtExisteRel)) {
            mysqli_stmt_close($stmtExisteRel);
            throw new Exception('Error al comprobar rel_articulos_estados: ' . mysqli_stmt_error($stmtExisteRel));
        }
        $rTmp = mysqli_stmt_get_result($stmtExisteRel);
        $existeRel = ($rTmp && mysqli_fetch_row($rTmp)) ? true : false;
        if (!$existeRel) {
            crear_rel_articulos_estados_desde_articulos_venta($conexion, $idTmp, $sucursal_venta, $precioTmp);
        }
    }
    mysqli_stmt_close($stmtExisteRel);

    $id_venta_sucursal = 1;
    $id_cliente = asegurarClienteParaVenta($conexion, $datos_cliente_venta, $usuario_id, $sucursal_venta);

    $venta_web = 'false';
    $id_order_web = 0;
    $cantidad_articulos = count($articulos);
    $motivo_anulacion = '';
    $fecha_anulacion = '0000-00-00';
    $anulado_por = 0;

    $sqlIns = "INSERT INTO ventas (
            rel_id_empresa,
            cliente,
            vendido_por,
            intereses,
            estado,
            tipo_pago,
            precio,
            cantidad_contado,
            cantidad_tarjeta,
            cantidad_transferencia,
            cantidad_bizum,
            cantidad_items,
            motivo_anulacion,
            fecha_anulacion,
            anulado_por
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmtI = mysqli_prepare($conexion, $sqlIns);
    if (!$stmtI) {
        throw new Exception('Error al preparar INSERT ventas: ' . mysqli_error($conexion));
    }

    $intereses_int = (int) $intereses;
    mysqli_stmt_bind_param(
        $stmtI,
        'iiisssddddisssi',
        $rel_id_empresa_usuario,
        $id_cliente,
        $usuario_id,
        $intereses_int,
        $estado_venta,
        $tipo_pago_db,
        $total_venta,
        $cant_contado,
        $cant_tarjeta,
        $cant_transferencia,
        $cant_bizum,
        $cantidad_articulos,
        $motivo_anulacion,
        $fecha_anulacion,
        $anulado_por
    );

    if (!mysqli_stmt_execute($stmtI)) {
        throw new Exception(
            'Error al insertar venta: ' . mysqli_stmt_error($stmtI)
            . ' (código ' . mysqli_stmt_errno($stmtI) . ')'
        );
    }
    mysqli_stmt_close($stmtI);

    $id_venta_pk = (int) mysqli_insert_id($conexion);
    if ($id_venta_pk <= 0) {
        throw new Exception('No se obtuvo id de venta tras el INSERT.');
    }

    $stmtRelIns = mysqli_prepare(
        $conexion,
        'INSERT INTO rel_articulos_venta (
            sku_articulo,
            sucursal_venta,
            descripcion_articulo_rel,
            id_venta_rel,
            rel_id_venta,
            precio_venta,
            fecha_venta,
            hora_venta,
            vendido_por,
            venta_web,
            backupfecha,
            id_order_web,
            coste_articulo_venta,
            tipo_iva_articulo,
            system_codigo_regimen
        ) VALUES (?, ?, ?, ?, ?, ?, CURDATE(), CURTIME(), ?, ?, NOW(), ?, ?, ?, ?)'
    );
    if (!$stmtRelIns) {
        throw new Exception('Error al preparar INSERT rel_articulos_venta: ' . mysqli_error($conexion));

    }

    $stmtA = mysqli_prepare(
        $conexion,
        'SELECT id, estado, id_sucursal_destino, descripcion, precio_coste, tipo_iva_articulo, system_codigo_regimen,
                peso, articulo_web, tipo
         FROM articulos_venta WHERE id = ? LIMIT 1'
    );
    if (!$stmtA) {
        throw new Exception('Error al leer artículo: ' . mysqli_error($conexion));
    }

    $tipo_factura_items = 'articulos';

    foreach ($articulos as $art) {
        $id_art = isset($art['id_articulo']) ? (int) $art['id_articulo'] : 0;
        $precio_linea = isset($art['precio']) ? (float) $art['precio'] : 0;
        if ($id_art <= 0 || $precio_linea <= 0) {
            throw new Exception('Datos de artículo inválidos.');
        }

        mysqli_stmt_bind_param($stmtA, 'i', $id_art);
        mysqli_stmt_execute($stmtA);
        $resA = mysqli_stmt_get_result($stmtA);
        $fila = $resA ? mysqli_fetch_assoc($resA) : null;

        if (!$fila) {
            throw new Exception('Artículo no encontrado: ' . $id_art);
        }
        if (strtolower((string) ($fila['estado'] ?? '')) !== 'enventa') {
            throw new Exception('El artículo ' . $id_art . ' no está en venta (enventa).');
        }

        $desc_rel = trim((string) ($fila['descripcion'] ?? ''));
        if ($desc_rel === '') {
            $desc_rel = 'Artículo #' . $id_art;
        }
        $coste = (float) ($fila['precio_coste'] ?? 0);
        $tipoIva = venta_rel_tipo_iva_valido((string) ($fila['tipo_iva_articulo'] ?? 'IVA'));
        $regimen = venta_rel_regimen_valido((string) ($fila['system_codigo_regimen'] ?? 'GENERAL'));
        if( $regimen == 'REBU'){
            $tipo_factura_items = 'articulos';
        }elseif( $regimen == 'INVERSION'){
            $tipo_factura_items = 'oro_inversion';
        }else{
            $tipo_factura_items = 'articulos';
        }

        mysqli_stmt_bind_param(
            $stmtRelIns,
            'iisiisisisss',
            $id_art,
            $sucursal_venta,
            $desc_rel,
            $id_venta_sucursal,
            $id_venta_pk,
            $precio_linea,
            $usuario_id,
            $venta_web,
            $id_order_web,
            $coste,
            $tipoIva,
            $regimen
        );
        if (!mysqli_stmt_execute($stmtRelIns)) {
            throw new Exception('Error al insertar rel_articulos_venta: ' . mysqli_stmt_error($stmtRelIns));
        }

        $stmtU = mysqli_prepare(
            $conexion,
            "UPDATE articulos_venta SET
                estado = ?,
                fecha_vendido = CURDATE(),
                hora_vendido = CURTIME(),
                precio = ?,
                last_id_venta = ?,
                id_venta_sucursal = ?,
                update_register = CURDATE()
             WHERE id = ? AND id_sucursal_destino = ? AND estado = 'enventa'"
        );
        if (!$stmtU) {
            throw new Exception('Error al preparar UPDATE articulos_venta: ' . mysqli_error($conexion));
        }
        mysqli_stmt_bind_param($stmtU, 'sdiiii', $estado_articulo, $precio_linea, $id_venta_pk, $id_venta_sucursal, $id_art, $sucursal_venta);
        if (!mysqli_stmt_execute($stmtU)) {
            throw new Exception('Error al actualizar artículo: ' . mysqli_stmt_error($stmtU));
        }
        if (mysqli_stmt_affected_rows($stmtU) !== 1) {
            mysqli_stmt_close($stmtU);
            throw new Exception('No se pudo actualizar el estado del artículo ' . $id_art . '.');
        }
        mysqli_stmt_close($stmtU);
        $numero_semana = numeroSemanaActual();
        $year_rel = numeroSemanaActualConAnyo();

        $stmtR = mysqli_prepare(
            $conexion,
            "UPDATE rel_articulos_estados SET
                estado_articulo = ?,
                rel_id_sucursal_venta = ?,
                precio_venta = ?,
                fecha_venta = CURDATE(),
                rel_id_venta = ?,
                rel_numero_semana_venta = ?,
                year_rel = ?
             WHERE rel_id_articulo_venta = ?"
        );
        if (!$stmtR) {
            throw new Exception('Error al preparar UPDATE rel_articulos_estados: ' . mysqli_error($conexion));
        }
        mysqli_stmt_bind_param($stmtR, 'sisiiii', $estado_articulo, $sucursal_venta, $precio_linea, $id_venta_pk, $numero_semana, $year_rel, $id_art);
        if (!mysqli_stmt_execute($stmtR)) {
            throw new Exception('Error al actualizar relación: ' . mysqli_stmt_error($stmtR));
        }
        $affectedR = mysqli_stmt_affected_rows($stmtR);
        mysqli_stmt_close($stmtR);
        // Un UPDATE puede devolver 0 filas afectadas aunque exista el registro (si los valores ya eran iguales).
        if ($affectedR === 0) {
            $stmtChk = mysqli_prepare($conexion, 'SELECT 1 FROM rel_articulos_estados WHERE rel_id_articulo_venta = ? LIMIT 1');
            if (!$stmtChk) {
                throw new Exception('Error al comprobar rel_articulos_estados: ' . mysqli_error($conexion));
            }
            mysqli_stmt_bind_param($stmtChk, 'i', $id_art);
            if (!mysqli_stmt_execute($stmtChk)) {
                throw new Exception('Error al comprobar rel_articulos_estados: ' . mysqli_stmt_error($stmtChk));
            }
            $resChk = mysqli_stmt_get_result($stmtChk);
            $existe = ($resChk && mysqli_fetch_row($resChk)) ? true : false;
            mysqli_stmt_close($stmtChk);
            if (!$existe) {
                throw new Exception('No se encontró rel_articulos_estados para el artículo ' . $id_art . '.');
            }
        }
        $comentarios_accion = 'Artículo vendido SKU: ' . $id_art . ' en la venta Nº: ' . $id_venta_sucursal;
        try {
            trazabilidad_articulos_venta(
                $id_venta_sucursal,
                $usuario_id,
                $estado_articulo,
                $comentarios_accion,
                $sucursal_venta,
                $id_art,
                $id_venta_pk
            );
        } catch (Throwable $e) {
            insertErrorLog('trazabilidad_articulos_venta: ' . $e->getMessage());
        }

        // Reportes: solo ventas al contado (no plazos). Los plazos se reportan al cerrar la venta.
        if ($venta_plazos_db !== 'si') {
            try {
                $peso_articulo_reporte = (float) ($fila['peso'] ?? 0);
                $articulo_web_reporte = (string) ($fila['articulo_web'] ?? 'false');
                if ($articulo_web_reporte === '') {
                    $articulo_web_reporte = 'false';
                }
                $tipo_metal_reporte = (string) ($fila['tipo'] ?? 'oro');

                insert_reporte_ventas(
                    $id_art,
                    $sucursal_venta,
                    $desc_rel,
                    $id_venta_sucursal,
                    $id_venta_pk,
                    $precio_linea,
                    $peso_articulo_reporte,
                    $articulo_web_reporte,
                    $tipo_metal_reporte,
                    $venta_plazos_db,
                    $numero_plazos_db,
                    $tipo_pago_db,
                    $cant_contado,
                    $cant_tarjeta,
                    $cant_transferencia,
                    $cant_bizum,
                    $usuario_id,
                    date('Y-m-d'),
                    $coste
                );
            } catch (Throwable $e) {
                insertErrorLog('insert_reporte_ventas: ' . $e->getMessage());
            }
        }

    }

    if ($venta_plazos_db === 'si') {
        $concepto_caja = "Cobro plazo de la venta Nº ". $id_venta_sucursal." (Cuota Nº1)";
        $grupos_caja = "Ventas a plazos";
    }else{
        $concepto_caja = "Venta Nº " . $id_venta_sucursal;
        $grupos_caja = "Ventas";
    }
    $skus_venta = [];
    foreach ($articulos as $artSku) {
        $idSku = isset($artSku['id_articulo']) ? (int) $artSku['id_articulo'] : 0;
        if ($idSku > 0) {
            $skus_venta[] = (string) $idSku;
        }
    }
    $concepto_caja .= ' (SKUs: ' . implode(',', $skus_venta) . ')';
    if($tipo_pago_db == "contado"){
        insertar_movimiento_caja($grupos_caja, $concepto_caja, $cant_contado, 0, $usuario_id, $sucursal_venta);
    } else if($tipo_pago_db == "tarjeta"){
        insertar_movimiento_tarjeta($sucursal_venta, 0, $id_venta_sucursal, $concepto_caja, $cant_tarjeta, $usuario_id, $grupos_caja);
    } else if($tipo_pago_db == "bizum"){
        insertar_movimiento_bizum($sucursal_venta, 0, $id_venta_sucursal, $concepto_caja, $cant_bizum, $usuario_id, $grupos_caja);
    } else if($tipo_pago_db == "transferencia"){
        insertar_movimiento_transferencia($sucursal_venta, 0, $id_venta_sucursal, $concepto_caja, $cant_transferencia, 0, $usuario_id, $grupos_caja);
    } else if($tipo_pago_db == "combinado"){

        if($cant_contado > 0){
            insertar_movimiento_caja($grupos_caja, $concepto_caja, $cant_contado, 0, $usuario_id, $sucursal_venta);
        }
        if($cant_tarjeta > 0){
            insertar_movimiento_tarjeta($sucursal_venta, 0, $id_venta_sucursal, $concepto_caja, $cant_tarjeta, $usuario_id, $grupos_caja);
        }
        if($cant_bizum > 0){
            insertar_movimiento_bizum($sucursal_venta, 0, $id_venta_sucursal, $concepto_caja, $cant_bizum, $usuario_id, $grupos_caja);
        }
        if($cant_transferencia > 0){
            insertar_movimiento_transferencia($sucursal_venta, 0, $id_venta_sucursal, $concepto_caja, $cant_transferencia, 0, $usuario_id, $grupos_caja);
        }
    }

    if ($venta_plazos_db === 'si') {

        $importe_cuota_plazo = $importe_esperado;
        $fecha_cobrado_plazo = date('Y-m-d H:i:s');
        $fecha_venc_siguiente = date('Y-m-d', strtotime('+1 month'));

        $stmtVpPagado = mysqli_prepare(
            $conexion,
            'INSERT INTO ventas_plazos (id_venta, estado, fecha_cobrado, importe, metodo_pago, cantidad_contado, cantidad_transferencia, cantidad_bizum, cantidad_tarjeta) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        if (!$stmtVpPagado) {
            throw new Exception('Error al preparar INSERT ventas_plazos (pagado): ' . mysqli_error($conexion));
        }
        $estado_plazo_pagado = 'Pagado';
        mysqli_stmt_bind_param(
            $stmtVpPagado,
            'issdsdddd',
            $id_venta_pk,
            $estado_plazo_pagado,
            $fecha_cobrado_plazo,
            $importe_cuota_plazo,
            $tipo_pago_db,
            $cant_contado,
            $cant_transferencia,
            $cant_bizum,
            $cant_tarjeta
        );
        if (!mysqli_stmt_execute($stmtVpPagado)) {
            $errVp = mysqli_stmt_error($stmtVpPagado);
            mysqli_stmt_close($stmtVpPagado);
            throw new Exception('Error al insertar ventas_plazos (pagado): ' . $errVp);
        }
        $id_plazo = (int) mysqli_insert_id($conexion);
        mysqli_stmt_close($stmtVpPagado);

        $accion_historico = 'Cobro plazo venta Nº 1 de la venta Nº ' . $id_venta_sucursal . ' metodo de pago '.$tipo_pago_db;
        insertAccionPlazoVenta($sucursal_venta, $id_plazo, $id_venta_pk, $usuario_id, $accion_historico, $origen);

        $stmtVpPendiente = mysqli_prepare(
            $conexion,
            'INSERT INTO ventas_plazos (id_venta, estado, fecha_vencimiento, importe) VALUES (?, ?, ?, ?)'
        );
        if (!$stmtVpPendiente) {
            throw new Exception('Error al preparar INSERT ventas_plazos (pendiente): ' . mysqli_error($conexion));
        }
        $estado_plazo_pendiente = 'Pendiente';
        mysqli_stmt_bind_param(
            $stmtVpPendiente,
            'issd',
            $id_venta_pk,
            $estado_plazo_pendiente,
            $fecha_venc_siguiente,
            $importe_cuota_plazo
        );
        if (!mysqli_stmt_execute($stmtVpPendiente)) {
            $errVp2 = mysqli_stmt_error($stmtVpPendiente);
            mysqli_stmt_close($stmtVpPendiente);
            throw new Exception('Error al insertar ventas_plazos (pendiente): ' . $errVp2);
        }
        $id_plazo_2 = (int) mysqli_insert_id($conexion);
        mysqli_stmt_close($stmtVpPendiente);

        $accion_historico = 'Plazo creado en la venta Nº ' . $id_venta_sucursal . ' (Cuota Nº 2)';
        insertAccionPlazoVenta($sucursal_venta, $id_plazo_2, $id_venta_pk, $usuario_id, $accion_historico, $origen);

    }

    mysqli_stmt_close($stmtA);
    mysqli_stmt_close($stmtRelIns);

    mysqli_commit($conexion);

    /**
     * Facturación automática (importe con IVA incluido), venta no a plazos:
     * - total > 400 €: factura completa con cliente.
     * - total > 0 y ≤ 400 €: factura simplificada (sin datos de cliente en cabecera).
     * Si falla la generación, la venta sigue válida; solo se registra en log.
     */
    $id_factura_generada = 0;
    $id_factura_simplificada_generada = 0;
    $id_factura_fiskaly_generada = 0;
    $fiskaly_resultado = null;
    $fiskaly_eval = null;

    $rellenarLineasFacturaDesdeVenta = static function (int $idVenta, int $idSucursal) use ($conexion) {
        $filasItems = [];
        $stmtLines = mysqli_prepare(
            $conexion,
            'SELECT sku_articulo, descripcion_articulo_rel, precio_venta,
                    coste_articulo_venta, tipo_iva_articulo, system_codigo_regimen
             FROM rel_articulos_venta
             WHERE rel_id_venta = ? AND sucursal_venta = ?'
        );
        if ($stmtLines) {
            mysqli_stmt_bind_param($stmtLines, 'ii', $idVenta, $idSucursal);
            mysqli_stmt_execute($stmtLines);
            $resLines = mysqli_stmt_get_result($stmtLines);
            while ($rowL = mysqli_fetch_assoc($resLines)) {
                $p = (float) ($rowL['precio_venta'] ?? 0);
                $filasItems[] = [
                    'id_rel_sucursal' => $idSucursal,
                    'rel_id_item' => (int) ($rowL['sku_articulo'] ?? 0),
                    'descripcion_articulo_rel' => (string) ($rowL['descripcion_articulo_rel'] ?? ''),
                    'precio_unitario' => $p,
                    'precio_coste_articulo' => (float) ($rowL['coste_articulo_venta'] ?? 0),
                    'tipo_iva_articulo' => (string) ($rowL['tipo_iva_articulo'] ?? 'IVA'),
                    'system_codigo_regimen' => venta_rel_regimen_valido((string) ($rowL['system_codigo_regimen'] ?? '')),
                    'cantidad' => 1,
                    'total_linea' => $p,
                ];
            }
            mysqli_stmt_close($stmtLines);
        }

        return $filasItems;
    };
    if ($venta_plazos_db === 'no' && $total_venta > obtenerMaximoTotalFacturaSimplificada()) {

        try {
            $rel_id_empresa_fact = $rel_id_empresa_usuario;
            $prefijo_f = facturaConstruirPrefijo($sucursal_venta, false, $tipo_factura_items);

            $fiskaly_eval = fiskalyEvaluarSucursalEmpresa($sucursal_venta, $rel_id_empresa_fact);
            if (!$fiskaly_eval['activo']) {
                insertErrorLog(
                    'insertar_venta: Fiskaly omitido (sucursal ' . $sucursal_venta . ', empresa ' . $rel_id_empresa_fact . ', tipo_api ' . ($fiskaly_eval['tipo_api'] !== '' ? $fiskaly_eval['tipo_api'] : 'n/a') . '): ' . $fiskaly_eval['motivo']
                );
            }
            $regimen_empresa = $fiskaly_eval['regimen'] !== ''
                ? $fiskaly_eval['regimen']
                : obtenerRegimenEmpresa($rel_id_empresa_fact);
            if (!in_array($regimen_empresa, ['false', 'General', 'Verifactu', 'TicketBAIBizkaia', 'TicketBAIAlava', 'TicketBAIGipuzkua'], true)) {
                $regimen_empresa = 'General';
            }
            $generar_factura_fiskaly = !empty($fiskaly_eval['activo']) ? 'true' : 'false';

            $numero_sig = (int) obtenerNumeroFactura($sucursal_venta, $tipo_factura_items);
            
            $id_factura_generada = crearFactura(
                [
                    'id_sucursal' => $sucursal_venta,
                    'numero_factura' => $numero_sig,
                    'cliente_factura' => $id_cliente,
                    'facturado_por' => $usuario_id,
                    'estado_factura' => 'pagada',
                    'tipo_pago_factura' => $tipo_pago_db,
                    'total_factura' => $total_venta,
                    'rel_id_venta' => $id_venta_pk,
                    'prefijo_factura' => $prefijo_f,
                    'tipo_factura' => $tipo_factura_items,
                    'rel_id_empresa' => $rel_id_empresa_fact,
                    'factura_regimen' => $regimen_empresa,
                ]
            );

            $filasItems = $rellenarLineasFacturaDesdeVenta($id_venta_pk, $sucursal_venta);
            foreach ($filasItems as &$fi) {
                $fi['rel_id_factura'] = $id_factura_generada;
            }
            unset($fi);
            if (count($filasItems) > 0) {
                insertarItemsFactura($filasItems);
            }
            /*generarPdfFactura($id_factura_generada, 'factura', 'sucursal', $sucursal_venta);*/
            
            if ($generar_factura_fiskaly === 'true') {
                try {
                    $id_factura_fiskaly_generada = crearFacturaFiskaly(
                        [
                            'id_sucursal' => $sucursal_venta,
                            'numero_factura' => $numero_sig,
                            'cliente_factura' => $id_cliente,
                            'facturado_por' => $usuario_id,
                            'estado_factura' => 'pagada',
                            'tipo_pago_factura' => $tipo_pago_db,
                            'total_factura' => $total_venta,
                            'rel_id_venta' => $id_venta_pk,
                            'prefijo_factura' => $prefijo_f,
                            'tipo_factura' => $tipo_factura_items,
                            'rel_id_empresa' => $rel_id_empresa_fact,
                            'factura_regimen' => $regimen_empresa,
                        ]
                    );

                    fiskalyVincularFacturaTpv($id_factura_generada, $id_factura_fiskaly_generada);

                    $filasItemsFiskaly = $rellenarLineasFacturaDesdeVenta($id_venta_pk, $sucursal_venta);
                    foreach ($filasItemsFiskaly as &$fiF) {
                        $fiF['rel_factura_id_fiskaly'] = $id_factura_fiskaly_generada;
                        $fiF['rel_id_factura'] = $id_factura_generada;
                        $fiF['rel_id_empresa'] = $rel_id_empresa_fact;
                    }
                    unset($fiF);
                    if (count($filasItemsFiskaly) > 0) {
                        insertarItemsFacturaFiskaly($filasItemsFiskaly);
                    }

                    try {
                        $fiskaly_resultado = enviarFacturaFiskaly(
                            $id_factura_fiskaly_generada,
                            $rel_id_empresa_fact,
                            $sucursal_venta
                        );
                    } catch (Throwable $exFiskaly) {
                        insertErrorLog('insertar_venta: envío Fiskaly no completado: ' . $exFiskaly->getMessage());
                        $fiskaly_resultado = [
                            'success' => false,
                            'estado_cache' => 'error',
                            'message' => $exFiskaly->getMessage(),
                        ];
                    }

                    if (is_array($fiskaly_resultado) && !empty($fiskaly_resultado['success'])) {
                        generarPdfFactura($id_factura_generada, 'factura', 'sucursal', $sucursal_venta);
                    } else {
                        generarPdfFactura($id_factura_generada, 'factura', 'sucursal', $sucursal_venta);
                    }
                } catch (Throwable $exFiskalyCache) {
                    insertErrorLog('insertar_venta: factura Fiskaly no creada: ' . $exFiskalyCache->getMessage());
                    $fiskaly_resultado = [
                        'success' => false,
                        'estado_cache' => 'error',
                        'message' => $exFiskalyCache->getMessage(),
                    ];
                    generarPdfFactura($id_factura_generada, 'factura', 'sucursal', $sucursal_venta);
                }
            } else {
                generarPdfFactura($id_factura_generada, 'factura', 'sucursal', $sucursal_venta);
            }
            
            
        } catch (Throwable $exFact) {
            insertErrorLog('insertar_venta: factura automática no generada: ' . $exFact->getMessage());
        }


    } elseif ($venta_plazos_db === 'no' && $total_venta > 0.0 && $total_venta <= obtenerMaximoTotalFacturaSimplificada()) {

        if($tipo_factura === 'completa'){

            try {
                $rel_id_empresa_fact = $rel_id_empresa_usuario;
                $prefijo_f = facturaConstruirPrefijo($sucursal_venta, false, $tipo_factura_items);

                $fiskaly_eval = fiskalyEvaluarSucursalEmpresa($sucursal_venta, $rel_id_empresa_fact);
                if (!$fiskaly_eval['activo']) {
                    insertErrorLog(
                        'insertar_venta: Fiskaly omitido (sucursal ' . $sucursal_venta . ', empresa ' . $rel_id_empresa_fact . ', tipo_api ' . ($fiskaly_eval['tipo_api'] !== '' ? $fiskaly_eval['tipo_api'] : 'n/a') . '): ' . $fiskaly_eval['motivo']
                    );
                }
                $regimen_empresa = $fiskaly_eval['regimen'] !== ''
                    ? $fiskaly_eval['regimen']
                    : obtenerRegimenEmpresa($rel_id_empresa_fact);
                if (!in_array($regimen_empresa, ['false', 'General', 'Verifactu', 'TicketBAIBizkaia', 'TicketBAIAlava', 'TicketBAIGipuzkua'], true)) {
                    $regimen_empresa = 'General';
                }
                $generar_factura_fiskaly = !empty($fiskaly_eval['activo']) ? 'true' : 'false';

                $numero_sig = (int) obtenerNumeroFactura($sucursal_venta, $tipo_factura_items);

                $id_factura_generada = crearFactura(
                    [
                        'id_sucursal' => $sucursal_venta,
                        'numero_factura' => $numero_sig,
                        'cliente_factura' => $id_cliente,
                        'facturado_por' => $usuario_id,
                        'estado_factura' => 'pagada',
                        'tipo_pago_factura' => $tipo_pago_db,
                        'total_factura' => $total_venta,
                        'rel_id_venta' => $id_venta_pk,
                        'prefijo_factura' => $prefijo_f,
                        'tipo_factura' => $tipo_factura_items,
                        'rel_id_empresa' => $rel_id_empresa_fact,
                        'factura_regimen' => $regimen_empresa,
                    ]
                );
    
                $filasItems = $rellenarLineasFacturaDesdeVenta($id_venta_pk, $sucursal_venta);
                foreach ($filasItems as &$fi) {
                    $fi['rel_id_factura'] = $id_factura_generada;
                }
                unset($fi);
                if (count($filasItems) > 0) {
                    insertarItemsFactura($filasItems);
                }

                if ($generar_factura_fiskaly === 'true') {
                    try {
                        $id_factura_fiskaly_generada = crearFacturaFiskaly(
                            [
                                'id_sucursal' => $sucursal_venta,
                                'numero_factura' => $numero_sig,
                                'cliente_factura' => $id_cliente,
                                'facturado_por' => $usuario_id,
                                'estado_factura' => 'pagada',
                                'tipo_pago_factura' => $tipo_pago_db,
                                'total_factura' => $total_venta,
                                'rel_id_venta' => $id_venta_pk,
                                'prefijo_factura' => $prefijo_f,
                                'tipo_factura' => $tipo_factura_items,
                                'rel_id_empresa' => $rel_id_empresa_fact,
                                'factura_regimen' => $regimen_empresa,
                            ]
                        );

                        fiskalyVincularFacturaTpv($id_factura_generada, $id_factura_fiskaly_generada);

                        $filasItemsFiskaly = $rellenarLineasFacturaDesdeVenta($id_venta_pk, $sucursal_venta);
                        foreach ($filasItemsFiskaly as &$fiF) {
                            $fiF['rel_factura_id_fiskaly'] = $id_factura_fiskaly_generada;
                            $fiF['rel_id_factura'] = $id_factura_generada;
                            $fiF['rel_id_empresa'] = $rel_id_empresa_fact;
                        }
                        unset($fiF);
                        if (count($filasItemsFiskaly) > 0) {
                            insertarItemsFacturaFiskaly($filasItemsFiskaly);
                        }

                        try {
                            $fiskaly_resultado = enviarFacturaFiskaly(
                                $id_factura_fiskaly_generada,
                                $rel_id_empresa_fact,
                                $sucursal_venta
                            );
                        } catch (Throwable $exFiskaly) {
                            insertErrorLog('insertar_venta: envío Fiskaly no completado: ' . $exFiskaly->getMessage());
                            $fiskaly_resultado = [
                                'success' => false,
                                'estado_cache' => 'error',
                                'message' => $exFiskaly->getMessage(),
                            ];
                        }
                    } catch (Throwable $exFiskalyCache) {
                        insertErrorLog('insertar_venta: factura Fiskaly no creada: ' . $exFiskalyCache->getMessage());
                        $fiskaly_resultado = [
                            'success' => false,
                            'estado_cache' => 'error',
                            'message' => $exFiskalyCache->getMessage(),
                        ];
                    }
                }

                generarPdfFactura($id_factura_generada, 'factura', 'sucursal', $sucursal_venta);
            } catch (Throwable $exFact) {
                insertErrorLog('insertar_venta: factura automática no generada: ' . $exFact->getMessage());
            }
            
        }else{

            try {
                $rel_id_empresa_fact = $rel_id_empresa_usuario;
                $prefijo_f = facturaConstruirPrefijo($sucursal_venta, true, $tipo_factura_items);

                $fiskaly_eval = fiskalyEvaluarSucursalEmpresa($sucursal_venta, $rel_id_empresa_fact);
                if (!$fiskaly_eval['activo']) {
                    insertErrorLog(
                        'insertar_venta: Fiskaly omitido (sucursal ' . $sucursal_venta . ', empresa ' . $rel_id_empresa_fact . ', tipo_api ' . ($fiskaly_eval['tipo_api'] !== '' ? $fiskaly_eval['tipo_api'] : 'n/a') . '): ' . $fiskaly_eval['motivo']
                    );
                }
                $regimen_empresa = $fiskaly_eval['regimen'] !== ''
                    ? $fiskaly_eval['regimen']
                    : obtenerRegimenEmpresa($rel_id_empresa_fact);
                if (!in_array($regimen_empresa, ['false', 'General', 'Verifactu', 'TicketBAIBizkaia', 'TicketBAIAlava', 'TicketBAIGipuzkua'], true)) {
                    $regimen_empresa = 'General';
                }
                $generar_factura_fiskaly = !empty($fiskaly_eval['activo']) ? 'true' : 'false';

                $numero_sig = (int) obtenerNumeroFactura($sucursal_venta, $tipo_factura_items);
                $id_factura_simplificada_generada = crearFacturaSimplificada(
                    [
                        'id_sucursal' => $sucursal_venta,
                        'numero_factura' => $numero_sig,
                        'facturado_por' => $usuario_id,
                        'estado_factura' => 'pagada',
                        'tipo_pago_factura' => $tipo_pago_db,
                        'total_factura' => $total_venta,
                        'rel_id_venta' => $id_venta_pk,
                        'prefijo_factura' => $prefijo_f,
                        'tipo_factura' => $tipo_factura_items,
                        'rel_id_empresa' => $rel_id_empresa_fact,
                        'factura_regimen' => $regimen_empresa,
                    ]
                );

                $filasItems = $rellenarLineasFacturaDesdeVenta($id_venta_pk, $sucursal_venta);
                foreach ($filasItems as &$fi) {
                    $fi['rel_id_factura'] = $id_factura_simplificada_generada;
                }
                unset($fi);
                if (count($filasItems) > 0) {
                    insertarItemsFacturaSimplificada($filasItems);
                }

                if ($generar_factura_fiskaly === 'true') {
                    try {
                        $id_factura_fiskaly_generada = crearFacturaFiskalySimplificada(
                            [
                                'id_sucursal' => $sucursal_venta,
                                'numero_factura' => $numero_sig,
                                'facturado_por' => $usuario_id,
                                'estado_factura' => 'pagada',
                                'tipo_pago_factura' => $tipo_pago_db,
                                'total_factura' => $total_venta,
                                'rel_id_venta' => $id_venta_pk,
                                'prefijo_factura' => $prefijo_f,
                                'tipo_factura' => $tipo_factura_items,
                                'rel_id_empresa' => $rel_id_empresa_fact,
                                'factura_regimen' => $regimen_empresa,
                            ]
                        );

                        fiskalyVincularFacturaSimplificadaTpv($id_factura_simplificada_generada, $id_factura_fiskaly_generada);

                        $filasItemsFiskaly = $rellenarLineasFacturaDesdeVenta($id_venta_pk, $sucursal_venta);
                        foreach ($filasItemsFiskaly as &$fiF) {
                            $fiF['rel_factura_id_fiskaly'] = $id_factura_fiskaly_generada;
                            $fiF['rel_id_factura'] = $id_factura_simplificada_generada;
                            $fiF['rel_id_empresa'] = $rel_id_empresa_fact;
                        }
                        unset($fiF);
                        if (count($filasItemsFiskaly) > 0) {
                            insertarItemsFacturaFiskaly($filasItemsFiskaly);
                        }

                        try {
                            $fiskaly_resultado = enviarFacturaFiskaly(
                                $id_factura_fiskaly_generada,
                                $rel_id_empresa_fact,
                                $sucursal_venta
                            );
                        } catch (Throwable $exFiskaly) {
                            insertErrorLog('insertar_venta: envío Fiskaly simplificada no completado: ' . $exFiskaly->getMessage());
                            $fiskaly_resultado = [
                                'success' => false,
                                'estado_cache' => 'error',
                                'message' => $exFiskaly->getMessage(),
                            ];
                        }
                    } catch (Throwable $exFiskalyCache) {
                        insertErrorLog('insertar_venta: factura Fiskaly simplificada no creada: ' . $exFiskalyCache->getMessage());
                        $fiskaly_resultado = [
                            'success' => false,
                            'estado_cache' => 'error',
                            'message' => $exFiskalyCache->getMessage(),
                        ];
                    }
                }

                generarPdfFactura($id_factura_simplificada_generada, 'factura', 'sucursal', $sucursal_venta);
            } catch (Throwable $exFactS) {
                insertErrorLog('insertar_venta: factura simplificada automática no generada: ' . $exFactS->getMessage());
            }

        }



    }

    mysqli_close($conexion);

    echo json_encode([
        'success' => true,
        'id_venta' => $id_venta_pk,
        'id_venta_sucursal' => $id_venta_sucursal,
        'id_factura' => $id_factura_generada,
        'id_factura_simplificada' => $id_factura_simplificada_generada,
        'id_factura_fiskaly' => $id_factura_fiskaly_generada,
        'fiskaly' => $fiskaly_resultado,
        'fiskaly_eval' => $fiskaly_eval,
    ]);
} catch (Exception $e) {
    if ($conexion) {
        mysqli_rollback($conexion);

        // Revertir artículos tocados por la venta si el flujo falló a mitad
        if (!empty($articulos) && is_array($articulos)) {
            // Nombre de sucursal para dejarlo consistente en articulos_venta (primera letra en mayúscula)
            $nombre_sucursal_venta = '';
            $nomSucRaw = obtener_nombre_sucursal($sucursal_venta);
            if ($nomSucRaw !== false) {
                $nombre_sucursal_venta = trim((string) $nomSucRaw);
            }
            if ($nombre_sucursal_venta !== '') {
                $nombre_sucursal_venta = ucfirst(mb_strtolower($nombre_sucursal_venta, 'UTF-8'));
            }

            $stmtRevertAV = mysqli_prepare(
                $conexion,
                "UPDATE articulos_venta SET
                    estado = 'enventa',
                    fecha_vendido = '0000-00-00',
                    hora_vendido = '00:00:00',
                    last_id_venta = 0,
                    id_venta_sucursal = 0,
                    nombre_sucursal_venta = ?,
                    update_register = CURDATE()
                 WHERE id = ?"
            );
            $stmtRevertRel = mysqli_prepare(
                $conexion,
                "UPDATE rel_articulos_estados SET
                    estado_articulo = 'Stock',
                    rel_id_sucursal_venta = 0,
                    precio_venta = 0,
                    fecha_venta = '0000-00-00',
                    rel_id_venta = 0,
                    rel_numero_semana_venta = 0,
                    year_rel = 0
                 WHERE rel_id_articulo_venta = ?"
            );

            foreach ($articulos as $artTmp) {
                $idTmp = isset($artTmp['id_articulo']) ? (int) $artTmp['id_articulo'] : 0;
                if ($idTmp <= 0) {
                    continue;
                }
                if ($stmtRevertAV) {
                    mysqli_stmt_bind_param($stmtRevertAV, 'si', $nombre_sucursal_venta, $idTmp);
                    mysqli_stmt_execute($stmtRevertAV);
                }
                if ($stmtRevertRel) {
                    mysqli_stmt_bind_param($stmtRevertRel, 'i', $idTmp);
                    mysqli_stmt_execute($stmtRevertRel);
                }
            }

            if ($stmtRevertAV) {
                mysqli_stmt_close($stmtRevertAV);
            }
            if ($stmtRevertRel) {
                mysqli_stmt_close($stmtRevertRel);
            }
        }

        if ($id_venta_pk > 0) {
            $stmtDelRel = mysqli_prepare(
                $conexion,
                'DELETE FROM rel_articulos_venta WHERE rel_id_venta = ? AND sucursal_venta = ?'
            );
            if ($stmtDelRel) {
                mysqli_stmt_bind_param($stmtDelRel, 'ii', $id_venta_pk, $sucursal_venta);
                mysqli_stmt_execute($stmtDelRel);
                mysqli_stmt_close($stmtDelRel);
            }
            $stmtDelV = mysqli_prepare(
                $conexion,
                'DELETE FROM ventas WHERE id = ? AND id_sucursal = ? LIMIT 1'
            );
            if ($stmtDelV) {
                mysqli_stmt_bind_param($stmtDelV, 'ii', $id_venta_pk, $sucursal_venta);
                mysqli_stmt_execute($stmtDelV);
                mysqli_stmt_close($stmtDelV);
            }
        }
        mysqli_close($conexion);
    }
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
