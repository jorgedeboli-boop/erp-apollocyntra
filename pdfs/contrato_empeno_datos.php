<?php
/**
 * Carga todos los datos del contrato de empeño (opción de compra).
 */

require_once __DIR__ . '/contrato_compra_datos.php';

function contrato_empeno_estado_lote_texto($estado_lote)
{
    switch ($estado_lote) {
        case 'vencido':
            return ' - Vencido';
        case 'retirado':
            return ' - Retirado';
        default:
            return '';
    }
}

function contrato_empeno_cargar_renovaciones(mysqli $conexion, $id_lote, $id_sucursal)
{
    $renovaciones = [];
    $query = "SELECT * FROM historico_renovaciones_$id_sucursal WHERE lote = ? ORDER BY id_renovaciones ASC";
    $stmt = mysqli_prepare($conexion, $query);
    if (!$stmt) {
        return $renovaciones;
    }

    mysqli_stmt_bind_param($stmt, 'i', $id_lote);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $i = 1;
    while ($row = mysqli_fetch_assoc($result)) {
        $fecharenovacion = $row['fecha_renovacion'];
        $proximo_vencimiento = $row['proximo_vencimiento'];
        $estado_historico = $row['estado_historico'];

        if ($estado_historico === 'enfecha') {
            $estado_historico = 'Pendiente';
        }

        if ($proximo_vencimiento !== '0000-00-00' && $proximo_vencimiento !== null) {
            $proximo_vencimiento = date('d-m-Y', strtotime($proximo_vencimiento));
        } else {
            $proximo_vencimiento = '-----';
        }

        if ($fecharenovacion !== '0000-00-00' && $fecharenovacion !== null) {
            $fecharenovacion = date('d-m-Y', strtotime($fecharenovacion));
        } else {
            $fecharenovacion = '-----';
        }

        $renovaciones[] = [
            'numero' => $i,
            'fecha_renovacion' => $fecharenovacion,
            'importe_renovacion' => number_format((float) $row['importe_renovacion'], 2, '.', ' '),
            'proximo_vencimiento' => $proximo_vencimiento,
            'estado_historico' => $estado_historico,
        ];
        $i++;
    }

    mysqli_stmt_close($stmt);

    return $renovaciones;
}

function contrato_empeno_cargar_adelantos(mysqli $conexion, $id_lote, $id_sucursal)
{
    $adelantos = [];
    $total = 0;
    $precio_compra_inicial = null;

    $query_count = 'SELECT COUNT(id_adelanto_capital) AS total FROM adelantos_capital WHERE id_lote_adelanto = ? AND sucursal_adelanto = ?';
    $stmt_count = mysqli_prepare($conexion, $query_count);
    if (!$stmt_count) {
        return ['total' => 0, 'precio_compra_inicial' => null, 'adelantos' => []];
    }

    mysqli_stmt_bind_param($stmt_count, 'ii', $id_lote, $id_sucursal);
    mysqli_stmt_execute($stmt_count);
    $result_count = mysqli_stmt_get_result($stmt_count);
    $row_count = mysqli_fetch_assoc($result_count);
    $total = (int) ($row_count['total'] ?? 0);
    mysqli_stmt_close($stmt_count);

    if ($total <= 0) {
        return ['total' => 0, 'precio_compra_inicial' => null, 'adelantos' => []];
    }

    $query_inicial = 'SELECT capital_antiguo FROM adelantos_capital WHERE id_lote_adelanto = ? AND sucursal_adelanto = ? ORDER BY id_adelanto_capital ASC LIMIT 1';
    $stmt_inicial = mysqli_prepare($conexion, $query_inicial);
    if ($stmt_inicial) {
        mysqli_stmt_bind_param($stmt_inicial, 'ii', $id_lote, $id_sucursal);
        mysqli_stmt_execute($stmt_inicial);
        $result_inicial = mysqli_stmt_get_result($stmt_inicial);
        if ($row_inicial = mysqli_fetch_assoc($result_inicial)) {
            $precio_compra_inicial = $row_inicial['capital_antiguo'];
        }
        mysqli_stmt_close($stmt_inicial);
    }

    $query = 'SELECT * FROM adelantos_capital WHERE id_lote_adelanto = ? AND sucursal_adelanto = ? ORDER BY id_adelanto_capital ASC';
    $stmt = mysqli_prepare($conexion, $query);
    if (!$stmt) {
        return ['total' => $total, 'precio_compra_inicial' => $precio_compra_inicial, 'adelantos' => []];
    }

    mysqli_stmt_bind_param($stmt, 'ii', $id_lote, $id_sucursal);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $i = 1;
    while ($row = mysqli_fetch_assoc($result)) {
        $fecha_adelanto = $row['fecha_adelanto'];
        $nuevo_capital = $row['nuevo_capital'];
        $nuevo_precio_recompra = $row['nuevo_precio_recompra'];
        $importe_renovacion = number_format((float) $nuevo_precio_recompra - (float) $nuevo_capital, 2, '.', ' ');

        if ($fecha_adelanto !== '0000-00-00' && $fecha_adelanto !== null) {
            $fecha_adelanto = date('d-m-Y', strtotime($fecha_adelanto));
        } else {
            $fecha_adelanto = '-----';
        }

        $adelantos[] = [
            'numero' => $i,
            'importe_adelanto' => $row['importe_adelanto'],
            'fecha_adelanto' => $fecha_adelanto,
            'nuevo_capital' => $nuevo_capital,
            'nuevo_precio_recompra' => $nuevo_precio_recompra,
            'importe_renovacion' => $importe_renovacion,
        ];
        $i++;
    }

    mysqli_stmt_close($stmt);

    return [
        'total' => $total,
        'precio_compra_inicial' => $precio_compra_inicial,
        'adelantos' => $adelantos,
    ];
}

function contrato_empeno_cargar_datos(mysqli $conexion, $id_lote, $id_sucursal)
{
    $id_lote = (int) $id_lote;
    $id_sucursal = (int) $id_sucursal;

    if ($id_lote <= 0) {
        return null;
    }

    if ($id_sucursal <= 0) {
        $id_sucursal = contrato_compra_buscar_sucursal_por_lote($conexion, $id_lote);
    }

    if ($id_sucursal <= 0) {
        return null;
    }

    $query = "SELECT 
        l.*,
        c.*,
        s.*,
        u.nombre_usuario,
        u.apellido_usuario,
        u.firma_usuario,
        d.direccion AS direccion_cliente,
        d.c_provincia AS provincia_cliente,
        d.c_poblacion AS poblacion_cliente,
        d.c_pais AS pais_cliente,
        d.codigo_postal AS cp_cliente,
        d.observaciones_direccion,
        dc.f_nacimiento,
        dc.movil AS movil_cliente,
        dc.email AS email_cliente,
        dc.observaciones AS observaciones_cliente,
        dc.publicidad,
        dc.sexo,
        dc.f_vencimiento AS f_vencimiento_dni,
        dc.firma_cliente
    FROM lotes_$id_sucursal l
    LEFT JOIN clientes c ON l.cliente = c.id_cliente 
    LEFT JOIN sucursal s ON l.sucursal = s.id_sucursal 
    LEFT JOIN usuarios u ON u.id_usuario = l.comprado_por
    LEFT JOIN direcciones d ON d.rel_id_item = c.id_cliente AND d.type_direccion = 'clientes'
    LEFT JOIN datos_clientes dc ON dc.rel_id_cliente = c.id_cliente
    WHERE l.id_lote = ?
    LIMIT 1";

    $stmt = mysqli_prepare($conexion, $query);
    if (!$stmt) {
        return null;
    }

    mysqli_stmt_bind_param($stmt, 'i', $id_lote);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $rsItem = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if (!$rsItem) {
        return null;
    }

    $sqldate = date('d-m-Y', strtotime($rsItem['fecha_compra']));
    $sqldatef = date('d-m-Y', strtotime($rsItem['fecha_vencimiento']));
    $fecha_nacimiento = !empty($rsItem['f_nacimiento'])
        ? date('d-m-Y', strtotime($rsItem['f_nacimiento']))
        : '';
    $estado_lote_texto = contrato_empeno_estado_lote_texto($rsItem['estado_lote'] ?? '');

    $logo_tienda = isset($rsItem['logotipo_sucursal']) ? $rsItem['logotipo_sucursal'] : '';
    $empresa_id = (int) (isset($rsItem['empresa_id']) ? $rsItem['empresa_id'] : 0);
    $sello_sucursal = generaSello($id_sucursal, $conexion);

    $nombre_cliente = trim($rsItem['nombre'] . ' ' . $rsItem['apellido']);
    $nombre_empleado = trim(
        (isset($rsItem['nombre_usuario']) ? $rsItem['nombre_usuario'] : '') . ' ' .
        (isset($rsItem['apellido_usuario']) ? $rsItem['apellido_usuario'] : '')
    );
    $firma_cliente = isset($rsItem['firma_cliente']) ? $rsItem['firma_cliente'] : '';
    $firma_usuario = isset($rsItem['firma_usuario']) ? $rsItem['firma_usuario'] : '';
    $firma_footer = contrato_compra_firma_html($firma_cliente, $nombre_cliente);
    $signatureInsert_cliente = contrato_compra_firma_html($firma_cliente, $nombre_cliente);
    $signatureInsert_empleado = contrato_compra_firma_html($firma_usuario, $nombre_empleado);

    $tipo_identificacion_id = isset($rsItem['tipo_identificacion_id']) ? (int) $rsItem['tipo_identificacion_id'] : 0;
    if ($tipo_identificacion_id > 0) {
        $texto_tipo_identificacion = obtenerTextoTipoIdentificacion($conexion, $tipo_identificacion_id);
    } else {
        $texto_tipo_identificacion = isset($rsItem['tipo_identificacion']) ? trim((string) $rsItem['tipo_identificacion']) : '';
    }
    $tipo_identificacion_cliente = strtoupper($texto_tipo_identificacion) . ': ';

    $nombre_empresa = '';
    $cif_empresa = '';
    if ($empresa_id > 0) {
        $query_empresa = 'SELECT nombre_empresa, cif_empresa FROM empresas WHERE id_empresa = ?';
        $stmt_empresa = mysqli_prepare($conexion, $query_empresa);
        mysqli_stmt_bind_param($stmt_empresa, 'i', $empresa_id);
        mysqli_stmt_execute($stmt_empresa);
        $result_empresa = mysqli_stmt_get_result($stmt_empresa);
        if ($row_empresa = mysqli_fetch_assoc($result_empresa)) {
            $nombre_empresa = $row_empresa['nombre_empresa'];
            $cif_empresa = $row_empresa['cif_empresa'];
        }
        mysqli_stmt_close($stmt_empresa);
    }

    $direccion_empresa = trim(
        $rsItem['direccion_tienda'] . ' ' .
        $rsItem['poblacion_tienda'] . ' ' .
        $rsItem['provincia_tienda'] . ' ' .
        $rsItem['codigo_postal_tienda']
    );
    $correo_electronico_empresa = isset($rsItem['email_tienda']) ? $rsItem['email_tienda'] : '';

    $logo_path_abs = '';
    $logo_src_web = '';
    if ($logo_tienda !== '') {
        $logoCandidate = dirname(__DIR__) . '/photos/' . basename($logo_tienda);
        if (is_readable($logoCandidate)) {
            $logo_path_abs = $logoCandidate;
            $logo_src_web = '../photos/' . basename($logo_tienda);
        }
    }

    $sello_footer = preg_replace(
        '#(<div id="sello" style=")width: 180px;#',
        '$1width: 100%;',
        $sello_sucursal
    );
    $sello_footer = str_replace(
        ['margin-top: -15px', 'transform: rotate(-15deg);'],
        ['margin-top: 0', ''],
        $sello_footer
    );
    $sello_footer_web = $sello_footer;

    $photosDir = realpath(dirname(__DIR__) . '/photos');
    if ($photosDir) {
        $sello_footer = preg_replace_callback(
            '#src="\.\./photos/([^"]+)"#',
            function ($m) use ($photosDir) {
                $path = $photosDir . '/' . basename($m[1]);
                return is_readable($path) ? 'src="' . $path . '"' : $m[0];
            },
            $sello_footer
        );
    }

    $id_cliente = (int) ($rsItem['id_cliente'] ?? $rsItem['cliente'] ?? 0);
    $fotos_dni_cliente = contrato_compra_cargar_fotos_dni_cliente($conexion, $id_cliente);

    $articulos = [];
    $query_articulos = "SELECT * FROM articulos_$id_sucursal WHERE id_lote_articulos = ?";
    $stmt_articulos = mysqli_prepare($conexion, $query_articulos);
    mysqli_stmt_bind_param($stmt_articulos, 'i', $id_lote);
    mysqli_stmt_execute($stmt_articulos);
    $result_articulos = mysqli_stmt_get_result($stmt_articulos);
    while ($row = mysqli_fetch_assoc($result_articulos)) {
        $articulos[] = [
            'id_articulo_lote' => $row['id_articulo_lote'],
            'unidades' => $row['unidades'],
            'descripcion_articulo' => $row['descripcion_articulo'] . ' (' . $row['tipo_de_articulo'] . ' ' . $row['ley'] . ')',
            'inscripciones' => $row['inscripciones'],
        ];
    }
    mysqli_stmt_close($stmt_articulos);

    $smatoria = 0;
    $query_sum = "SELECT SUM(unidades) AS total_unidades FROM articulos_$id_sucursal WHERE id_lote_articulos = ?";
    $stmt_sum = mysqli_prepare($conexion, $query_sum);
    mysqli_stmt_bind_param($stmt_sum, 'i', $id_lote);
    mysqli_stmt_execute($stmt_sum);
    $result_sum = mysqli_stmt_get_result($stmt_sum);
    if ($row_sum = mysqli_fetch_assoc($result_sum)) {
        $smatoria = (int) $row_sum['total_unidades'];
    }
    mysqli_stmt_close($stmt_sum);

    $renovaciones = contrato_empeno_cargar_renovaciones($conexion, $id_lote, $id_sucursal);
    $datos_adelantos = contrato_empeno_cargar_adelantos($conexion, $id_lote, $id_sucursal);
    $precio_compra_inicial = $datos_adelantos['precio_compra_inicial'] ?? $rsItem['precio_compra'];

    $textos_legales = [];
    $tipo_documento_ultima = 'empenio';
    $query_ultima = "SELECT titulo_text, content_text 
                     FROM textos_documentos 
                     WHERE (tipo_documento = ? OR tipo_documento = 'texto_legal_datos') 
                     AND state_texto_doc = 'true' 
                     ORDER BY id_texto_doc";
    $stmt_ultima = mysqli_prepare($conexion, $query_ultima);
    mysqli_stmt_bind_param($stmt_ultima, 's', $tipo_documento_ultima);
    mysqli_stmt_execute($stmt_ultima);
    $result_ultima = mysqli_stmt_get_result($stmt_ultima);

    $buscar = [
        '{direccion_empresa}',
        '{correo_electronico_empresa}',
        '{nombre_empresa}',
        '{telefono_empresa}',
        '{cif_empresa}',
        '{cliente_nombre}',
        '{lote_numero}',
    ];
    $reemplazar = [
        $direccion_empresa,
        $correo_electronico_empresa,
        $rsItem['empresa'],
        $rsItem['telefono_tienda'],
        $cif_empresa,
        $nombre_cliente,
        $rsItem['id_lote'],
    ];

    while ($row_legal = mysqli_fetch_assoc($result_ultima)) {
        $textos_legales[] = [
            'titulo' => $row_legal['titulo_text'],
            'contenido' => str_replace($buscar, $reemplazar, $row_legal['content_text']),
        ];
    }
    mysqli_stmt_close($stmt_ultima);

    return [
        'id_lote' => $id_lote,
        'id_sucursal' => $id_sucursal,
        'rsItem' => $rsItem,
        'sqldate' => $sqldate,
        'sqldatef' => $sqldatef,
        'fecha_nacimiento' => $fecha_nacimiento,
        'estado_lote_texto' => $estado_lote_texto,
        'nombre_cliente' => $nombre_cliente,
        'nombre_empleado' => $nombre_empleado,
        'tipo_identificacion_cliente' => $tipo_identificacion_cliente,
        'nombre_empresa' => $nombre_empresa,
        'cif_empresa' => $cif_empresa,
        'direccion_empresa' => $direccion_empresa,
        'correo_electronico_empresa' => $correo_electronico_empresa,
        'logo_path_abs' => $logo_path_abs,
        'logo_src_web' => $logo_src_web,
        'sello_footer' => $sello_footer,
        'sello_footer_web' => $sello_footer_web,
        'firma_footer' => $firma_footer,
        'signatureInsert_cliente' => $signatureInsert_cliente,
        'signatureInsert_empleado' => $signatureInsert_empleado,
        'articulos' => $articulos,
        'smatoria' => $smatoria,
        'renovaciones' => $renovaciones,
        'total_adelantos' => $datos_adelantos['total'],
        'precio_compra_inicial' => $precio_compra_inicial,
        'adelantos' => $datos_adelantos['adelantos'],
        'textos_legales' => $textos_legales,
        'fotos_dni_cliente' => $fotos_dni_cliente,
    ];
}
