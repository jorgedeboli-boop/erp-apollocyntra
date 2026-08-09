<?php
/**
 * Carga todos los datos del contrato de compra.
 */

/**
 * Busca en qué sucursal existe un lote.
 */

function contrato_compra_buscar_sucursal_por_lote(mysqli $conexion, $id_lote)
{
    $id_lote = (int) $id_lote;
    if ($id_lote <= 0) {
        return 0;
    }

    $querySucursales = "SELECT id_sucursal FROM sucursal WHERE estado_tienda = 'habilitada'";
    $resultSucursales = mysqli_query($conexion, $querySucursales);
    if (!$resultSucursales) {
        return 0;
    }

    while ($row = mysqli_fetch_assoc($resultSucursales)) {
        $idSucursal = (int) $row['id_sucursal'];
        $tableName = 'lotes_' . $idSucursal;
        $check = mysqli_query($conexion, "SHOW TABLES LIKE '" . mysqli_real_escape_string($conexion, $tableName) . "'");
        if (!$check || mysqli_num_rows($check) === 0) {
            continue;
        }

        $stmt = mysqli_prepare($conexion, "SELECT id_lote FROM $tableName WHERE id_lote = ? LIMIT 1");
        if (!$stmt) {
            continue;
        }
        mysqli_stmt_bind_param($stmt, 'i', $id_lote);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $found = $result && mysqli_num_rows($result) > 0;
        mysqli_stmt_close($stmt);

        if ($found) {
            return $idSucursal;
        }
    }

    return 0;
}


function contrato_compra_firma_html($encodeData, $textSignature)
{
    $textSignature = trim((string) $textSignature);
    $encodeData = is_string($encodeData) ? trim($encodeData) : '';

    $html = '<div style="width:100%; font-size:10pt; font-weight:bold; text-align:center; padding-top:5px;">'
        . htmlspecialchars($textSignature, ENT_QUOTES, 'UTF-8') . '</div>';

    if ($encodeData === '' || strpos($encodeData, ',') === false) {
        return $html;
    }

    $firmaRaw = generateSignatureContratoFinal($encodeData, '');
    if (preg_match('#<img[^>]+>#', $firmaRaw, $matches)) {
        $img = preg_replace(
            '/style="[^"]*"/',
            'style="max-height:110px; width:100%; height:auto; display:block; padding-top:5px;"',
            $matches[0]
        );
        $html .= '<div style="width:100%; font-size:10pt; text-align:center; padding-top:5px;">' . $img . '</div>';
    }

    return $html;
}


function contrato_compra_cargar_fotos_dni_cliente(mysqli $conexion, $id_cliente)
{
    $id_cliente = (int) $id_cliente;
    if ($id_cliente <= 0) {
        return [];
    }

    $sucursal_cliente = 0;
    $stmtCliente = mysqli_prepare($conexion, 'SELECT sucursal FROM clientes WHERE id_cliente = ? LIMIT 1');
    if (!$stmtCliente) {
        return [];
    }
    mysqli_stmt_bind_param($stmtCliente, 'i', $id_cliente);
    mysqli_stmt_execute($stmtCliente);
    $resultCliente = mysqli_stmt_get_result($stmtCliente);
    $rowCliente = $resultCliente ? mysqli_fetch_assoc($resultCliente) : null;
    mysqli_stmt_close($stmtCliente);

    if (!$rowCliente) {
        return [];
    }

    $sucursal_cliente = (int) ($rowCliente['sucursal'] ?? 0);
    if ($sucursal_cliente <= 0) {
        return [];
    }

    $tabla_fotos = 'fotos_app_' . $sucursal_cliente;
    $check = mysqli_query(
        $conexion,
        "SHOW TABLES LIKE '" . mysqli_real_escape_string($conexion, $tabla_fotos) . "'"
    );
    if (!$check || mysqli_num_rows($check) === 0) {
        return [];
    }

    $query = "SELECT nombre_foto FROM `$tabla_fotos` WHERE id_cliente = ? ORDER BY id_foto DESC";
    $stmt = mysqli_prepare($conexion, $query);
    if (!$stmt) {
        return [];
    }

    mysqli_stmt_bind_param($stmt, 'i', $id_cliente);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $photosDir = realpath(dirname(__DIR__) . '/photos');
    $fotos = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $nombre_foto = isset($row['nombre_foto']) ? basename((string) $row['nombre_foto']) : '';
        if ($nombre_foto === '' || !$photosDir) {
            continue;
        }

        $path_abs = $photosDir . '/' . $nombre_foto;
        if (!is_readable($path_abs)) {
            continue;
        }

        $fotos[] = [
            'nombre_foto' => $nombre_foto,
            'path_abs' => $path_abs,
            'src_web' => '../photos/' . $nombre_foto,
        ];
    }
    mysqli_stmt_close($stmt);

    return $fotos;
}


function contrato_compra_cargar_datos(mysqli $conexion, $id_lote, $id_sucursal)
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
            'descripcion_articulo' => $row['descripcion_articulo'],
            'peso_neto_bruto' => $row['peso_articulo'] . ' / ' . $row['peso_bruto'] . ' grs',
            'tipo_de_articulo' => $row['tipo_de_articulo'],
            'ley' => $row['ley'],
            'inscripciones' => ($row['active_inscripciones'] === 'si') ? $row['inscripciones'] : 'No',
            'piedras' => ($row['active_piedras'] === 'si') ? $row['descripcion_piedras'] : 'No',
            'precio_compra_articulo' => $row['precio_compra_articulo'],
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

    $textos_legales = [];
    $tipo_documento_ultima = 'compra';
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
        'textos_legales' => $textos_legales,
        'fotos_dni_cliente' => $fotos_dni_cliente,
    ];
}


/**
 * Datos de ejemplo para probar logotipo/sello de una sucursal.
 * Usa datos reales de la sucursal (logo, sello, empresa) y cliente/lote ficticios.
 *
 * @return array<string,mixed>|null
 */
function contrato_compra_cargar_datos_ejemplo(mysqli $conexion, $id_sucursal)
{
    $id_sucursal = (int) $id_sucursal;
    if ($id_sucursal <= 0) {
        return null;
    }

    $query = "SELECT
            s.*,
            e.nombre_empresa,
            e.cif_empresa
        FROM sucursal s
        LEFT JOIN empresas e ON s.empresa_id = e.id_empresa
        WHERE s.id_sucursal = ?
        LIMIT 1";

    $stmt = mysqli_prepare($conexion, $query);
    if (!$stmt) {
        return null;
    }

    mysqli_stmt_bind_param($stmt, 'i', $id_sucursal);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $sucursal = $result ? mysqli_fetch_assoc($result) : null;
    mysqli_stmt_close($stmt);

    if (!$sucursal) {
        return null;
    }

    $id_lote = 99999;
    $sqldate = date('d-m-Y');
    $sqldatef = date('d-m-Y', strtotime('+30 days'));
    $fecha_nacimiento = '15-03-1985';

    $nombre_cliente = 'Juan Ejemplo Prueba';
    $nombre_empleado = 'Empleado Demo';
    $tipo_identificacion_cliente = 'DNI: ';

    $nombre_empresa = (string) ($sucursal['nombre_empresa'] ?? ($sucursal['empresa'] ?? ''));
    $cif_empresa = (string) ($sucursal['cif_empresa'] ?? '');
    $direccion_empresa = trim(
        ($sucursal['direccion_tienda'] ?? '') . ' ' .
        ($sucursal['poblacion_tienda'] ?? '') . ' ' .
        ($sucursal['provincia_tienda'] ?? '') . ' ' .
        ($sucursal['codigo_postal_tienda'] ?? '')
    );
    $correo_electronico_empresa = (string) ($sucursal['email_tienda'] ?? '');

    $logo_tienda = (string) ($sucursal['logotipo_sucursal'] ?? '');
    $logo_path_abs = '';
    $logo_src_web = '';
    if ($logo_tienda !== '') {
        $logoCandidate = dirname(__DIR__) . '/photos/' . basename($logo_tienda);
        if (is_readable($logoCandidate)) {
            $logo_path_abs = $logoCandidate;
            $logo_src_web = '../photos/' . basename($logo_tienda);
        }
    }

    $sello_sucursal = generaSello($id_sucursal, $conexion);
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

    $firma_footer = contrato_compra_firma_html('', $nombre_cliente);
    $signatureInsert_cliente = $firma_footer;
    $signatureInsert_empleado = contrato_compra_firma_html('', $nombre_empleado);

    $rsItem = array_merge($sucursal, [
        'id_lote' => $id_lote,
        'precio_compra' => '350,00',
        'peso' => '12.50',
        'peso_bruto' => '13.20',
        'identificacion' => '12345678Z',
        'nacionalidad' => 'Española',
        'telefono' => '600123456',
        'sexo' => 'Hombre',
        'direccion_cliente' => 'Calle Ficticia 12, 3º B',
        'poblacion_cliente' => 'Madrid',
        'cp_cliente' => '28001',
        'provincia_cliente' => 'Madrid',
        'pais_cliente' => 'España',
        'empresa' => $nombre_empresa !== '' ? $nombre_empresa : (string) ($sucursal['empresa'] ?? 'Empresa demo'),
    ]);

    $articulos = [
        [
            'id_articulo_lote' => 1,
            'unidades' => 1,
            'descripcion_articulo' => 'Anillo de oro de ejemplo',
            'peso_neto_bruto' => '5.20 / 5.50 grs',
            'tipo_de_articulo' => 'Oro',
            'ley' => '18K',
            'inscripciones' => 'No',
            'piedras' => 'No',
            'precio_compra_articulo' => '200,00',
        ],
        [
            'id_articulo_lote' => 2,
            'unidades' => 1,
            'descripcion_articulo' => 'Cadena de plata de ejemplo',
            'peso_neto_bruto' => '7.30 / 7.70 grs',
            'tipo_de_articulo' => 'Plata',
            'ley' => '925',
            'inscripciones' => 'No',
            'piedras' => 'No',
            'precio_compra_articulo' => '150,00',
        ],
    ];

    $textos_legales = [];
    $tipo_documento_ultima = 'compra';
    $query_ultima = "SELECT titulo_text, content_text
                     FROM textos_documentos
                     WHERE (tipo_documento = ? OR tipo_documento = 'texto_legal_datos')
                     AND state_texto_doc = 'true'
                     ORDER BY id_texto_doc";
    $stmt_ultima = mysqli_prepare($conexion, $query_ultima);
    if ($stmt_ultima) {
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
            $nombre_empresa,
            (string) ($sucursal['telefono_tienda'] ?? ''),
            $cif_empresa,
            $nombre_cliente,
            (string) $id_lote,
        ];

        while ($row_legal = mysqli_fetch_assoc($result_ultima)) {
            $textos_legales[] = [
                'titulo' => $row_legal['titulo_text'],
                'contenido' => str_replace($buscar, $reemplazar, $row_legal['content_text']),
            ];
        }
        mysqli_stmt_close($stmt_ultima);
    }

    return [
        'id_lote' => $id_lote,
        'id_sucursal' => $id_sucursal,
        'rsItem' => $rsItem,
        'sqldate' => $sqldate,
        'sqldatef' => $sqldatef,
        'fecha_nacimiento' => $fecha_nacimiento,
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
        'smatoria' => 2,
        'textos_legales' => $textos_legales,
        'fotos_dni_cliente' => [],
    ];
}
