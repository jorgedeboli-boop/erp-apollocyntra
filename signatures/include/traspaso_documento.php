<?php
/**
 * Datos y HTML para albarán de traspaso (impresión), estilo documents/invoice.php.
 */

if (!function_exists('traspaso_obtener_datos_documento')) {
    /**
     * @return array<string,mixed>|null
     */
    function traspaso_obtener_datos_documento($conexion, $id_traspaso)
    {
        $id_traspaso = (int) $id_traspaso;
        if ($id_traspaso <= 0) {
            return null;
        }

        $st = mysqli_prepare(
            $conexion,
            'SELECT t.*, uc.usuario AS creador_usuario
             FROM traspasos t
             LEFT JOIN usuarios uc ON uc.id_usuario = t.creado_por
             WHERE t.id_traspaso = ?
             LIMIT 1'
        );
        if (!$st) {
            return null;
        }
        mysqli_stmt_bind_param($st, 'i', $id_traspaso);
        mysqli_stmt_execute($st);
        $tras = mysqli_fetch_assoc(mysqli_stmt_get_result($st));
        mysqli_stmt_close($st);

        if (!$tras) {
            return null;
        }

        $idOrigen = (int) $tras['sucursal_traspaso'];
        $idDest = (int) $tras['sucursal_destino'];

        $nombreOrigen = '';
        $nombreDestino = '';
        $empresa = null;
        $sucursal_origen = array(
            'nombre' => '',
            'direccion' => '',
            'codigo_postal' => '',
            'poblacion' => '',
            'provincia' => '',
        );
        $sucursal_destino = array(
            'nombre' => '',
            'direccion' => '',
            'codigo_postal' => '',
            'poblacion' => '',
            'provincia' => '',
        );

        $stS = mysqli_prepare(
            $conexion,
            'SELECT s.nombre_sucursal, s.empresa_id,
                    s.direccion_tienda, s.codigo_postal_tienda, s.poblacion_tienda, s.provincia_tienda,
                    e.id_empresa, e.nombre_empresa, e.cif_empresa, e.direccion_empresa, e.poblacion_empresa,
                    e.provincia_empresa, e.telefono_empresa, e.codigo_postal_empresa, e.pais_empresa, e.email_empresa,
                    e.logotipo_empresa
             FROM sucursal s
             LEFT JOIN empresas e ON e.id_empresa = s.empresa_id
             WHERE s.id_sucursal = ?
             LIMIT 1'
        );
        if ($stS) {
            mysqli_stmt_bind_param($stS, 'i', $idOrigen);
            mysqli_stmt_execute($stS);
            $rowO = mysqli_fetch_assoc(mysqli_stmt_get_result($stS));
            mysqli_stmt_close($stS);
            if ($rowO) {
                $nombreOrigen = (string) ($rowO['nombre_sucursal'] ?? '');
                $empresa = $rowO;
                $sucursal_origen = array(
                    'nombre' => $nombreOrigen,
                    'direccion' => trim((string) ($rowO['direccion_tienda'] ?? '')),
                    'codigo_postal' => trim((string) ($rowO['codigo_postal_tienda'] ?? '')),
                    'poblacion' => trim((string) ($rowO['poblacion_tienda'] ?? '')),
                    'provincia' => trim((string) ($rowO['provincia_tienda'] ?? '')),
                );
            }
        }

        $stD = mysqli_prepare(
            $conexion,
            'SELECT nombre_sucursal, direccion_tienda, codigo_postal_tienda, poblacion_tienda, provincia_tienda
             FROM sucursal WHERE id_sucursal = ? LIMIT 1'
        );
        if ($stD) {
            mysqli_stmt_bind_param($stD, 'i', $idDest);
            mysqli_stmt_execute($stD);
            $rowD = mysqli_fetch_assoc(mysqli_stmt_get_result($stD));
            mysqli_stmt_close($stD);
            if ($rowD) {
                $nombreDestino = (string) ($rowD['nombre_sucursal'] ?? '');
                $sucursal_destino = array(
                    'nombre' => $nombreDestino,
                    'direccion' => trim((string) ($rowD['direccion_tienda'] ?? '')),
                    'codigo_postal' => trim((string) ($rowD['codigo_postal_tienda'] ?? '')),
                    'poblacion' => trim((string) ($rowD['poblacion_tienda'] ?? '')),
                    'provincia' => trim((string) ($rowD['provincia_tienda'] ?? '')),
                );
            }
        }

        if ($empresa === null || empty($empresa['id_empresa'])) {
            $r = mysqli_query($conexion, 'SELECT id_empresa, nombre_empresa, cif_empresa, direccion_empresa, poblacion_empresa,
                provincia_empresa, telefono_empresa, codigo_postal_empresa, pais_empresa, email_empresa, logotipo_empresa
                FROM empresas ORDER BY id_empresa ASC LIMIT 1');
            if ($r) {
                $empresa = mysqli_fetch_assoc($r);
            }
        }

        $articulos = array();
        $stArt = mysqli_prepare(
            $conexion,
            'SELECT r.id_articulo_rel, av.descripcion, av.peso, av.precio, av.tipo_articulo AS tipo
             FROM rel_articulos_traspaso r
             INNER JOIN articulos_venta av ON av.id = r.id_articulo_rel
             WHERE r.id_traspaso_rel = ?
             ORDER BY r.fecha_creacion_rel ASC, r.id_articulo_rel ASC'
        );
        if ($stArt) {
            mysqli_stmt_bind_param($stArt, 'i', $id_traspaso);
            mysqli_stmt_execute($stArt);
            $ra = mysqli_stmt_get_result($stArt);
            while ($row = mysqli_fetch_assoc($ra)) {
                $idArt = (int) $row['id_articulo_rel'];
                $articulos[] = array(
                    'id_articulo' => $idArt,
                    'sku' => (string) $idArt,
                    'descripcion' => $row['descripcion'],
                    'unidades' => 1,
                    'peso' => $row['peso'],
                    'tipo' => $row['tipo'],
                    'precio' => $row['precio'],
                );
            }
            mysqli_stmt_close($stArt);
        }

        return array(
            'traspaso' => $tras,
            'empresa' => is_array($empresa) ? $empresa : array(),
            'nombre_sucursal_origen' => $nombreOrigen,
            'nombre_sucursal_destino' => $nombreDestino,
            'sucursal_origen' => $sucursal_origen,
            'sucursal_destino' => $sucursal_destino,
            'articulos' => $articulos,
        );
    }
}

if (!function_exists('traspaso_fmt_importe')) {
    function traspaso_fmt_importe($n)
    {
        return number_format((float) $n, 2, ',', ' ') . ' €';
    }
}

if (!function_exists('traspaso_fmt_fecha_hora')) {
    function traspaso_fmt_fecha_hora($sqlDate)
    {
        if ($sqlDate === null || $sqlDate === '' || $sqlDate === '0000-00-00 00:00:00') {
            return '—';
        }
        $t = strtotime($sqlDate);

        return $t ? date('d/m/Y H:i', $t) : '—';
    }
}

if (!function_exists('traspaso_estado_etiqueta')) {
    function traspaso_estado_etiqueta($estado)
    {
        $estado = (string) $estado;
        switch ($estado) {
            case 'PENDIENTEENVIO':
                return 'Pendiente de envío';
            case 'PENDIENTEDERECIBIR':
                return 'Pendiente de recibir';
            case 'TRASPASADO':
                return 'Traspasado';
            case 'ANULADO':
                return 'Anulado';
            default:
                return $estado !== '' ? $estado : '—';
        }
    }
}

if (!function_exists('traspaso_sucursal_linea_cp_poblacion')) {
    /**
     * Código postal, población y provincia en una línea (estilo sello tienda).
     *
     * @param array<string,string> $s
     */
    function traspaso_sucursal_linea_cp_poblacion(array $s)
    {
        $cp = isset($s['codigo_postal']) ? trim((string) $s['codigo_postal']) : '';
        $pob = isset($s['poblacion']) ? trim((string) $s['poblacion']) : '';
        $prov = isset($s['provincia']) ? trim((string) $s['provincia']) : '';
        $part = trim($cp . ' ' . $pob);
        if ($part === '' && $prov === '') {
            return '';
        }
        if ($prov !== '') {
            return trim($part . ' (' . $prov . ')');
        }

        return $part;
    }
}

if (!function_exists('traspaso_empresa_logo_ruta_absoluta')) {
    function traspaso_empresa_logo_ruta_absoluta($logotipo)
    {
        $logotipo = trim((string) $logotipo);
        if ($logotipo === '') {
            return '';
        }
        $safe = basename($logotipo);
        if ($safe === '' || $safe === '.' || $safe === '..') {
            return '';
        }
        $baseDir = dirname(__DIR__);
        $path = $baseDir . '/photos/' . $safe;

        return is_readable($path) ? $path : '';
    }
}

if (!function_exists('traspaso_empresa_logo_src_web_desde_main')) {
    /**
     * URL relativa para parts/traspasos/main/albaran_traspaso.php → ../../../photos/archivo
     */
    function traspaso_empresa_logo_src_web_desde_main($logotipo)
    {
        if (traspaso_empresa_logo_ruta_absoluta($logotipo) === '') {
            return '';
        }

        return '../../../photos/' . basename($logotipo);
    }
}

if (!function_exists('traspaso_albaran_body_inner')) {
    /**
     * Bloque interior tipo documents/invoice.php (Bootstrap).
     *
     * @param array<string,mixed> $data
     */
    function traspaso_albaran_body_inner(array $data)
    {
        $t = $data['traspaso'];
        $e = $data['empresa'];
        $arts = $data['articulos'];
        $nomOrigen = (string) ($data['nombre_sucursal_origen'] ?? '');
        $nomDest = (string) ($data['nombre_sucursal_destino'] ?? '');
        $sucO = isset($data['sucursal_origen']) && is_array($data['sucursal_origen']) ? $data['sucursal_origen'] : array();
        $sucD = isset($data['sucursal_destino']) && is_array($data['sucursal_destino']) ? $data['sucursal_destino'] : array();
        $dirO = isset($sucO['direccion']) ? trim((string) $sucO['direccion']) : '';
        $dirD = isset($sucD['direccion']) ? trim((string) $sucD['direccion']) : '';
        $cpPobO = traspaso_sucursal_linea_cp_poblacion($sucO);
        $cpPobD = traspaso_sucursal_linea_cp_poblacion($sucD);

        $empDir = '';
        if (is_array($e) && !empty($e)) {
            $empDir = trim(
                ($e['direccion_empresa'] ?? '') . ', '
                . ($e['codigo_postal_empresa'] ?? '') . ' '
                . ($e['poblacion_empresa'] ?? '') . ' (' . ($e['provincia_empresa'] ?? '') . ')'
            );
        }

        $idDoc = (int) ($t['id_traspaso'] ?? 0);
        $fechaTxt = traspaso_fmt_fecha_hora($t['fecha_traspaso'] ?? '');
        $estadoTxt = traspaso_estado_etiqueta($t['estado_traspaso'] ?? '');
        $obs = isset($t['observaciones_traspaso']) ? trim((string) $t['observaciones_traspaso']) : '';
        $creador = trim((string) ($t['creador_usuario'] ?? ''));
        if ($creador === '' && isset($_SESSION['usuario_nombre_completo'])) {
            $creador = (string) $_SESSION['usuario_nombre_completo'];
        }

        $sumPeso = 0.0;
        $sumValor = 0.0;
        foreach ($arts as $a) {
            $sumPeso += (float) ($a['peso'] ?? 0);
            $sumValor += (float) ($a['precio'] ?? 0) * (int) ($a['unidades'] ?? 1);
        }

        ob_start();
        ?>
    <div class="invoice-print p-6">
      <div class="d-flex justify-content-between flex-row">
        <div class="mb-6">
          <?php
            $logoWeb = is_array($e) ? traspaso_empresa_logo_src_web_desde_main($e['logotipo_empresa'] ?? '') : '';
        if ($logoWeb !== '') {
            ?>
          <div class="mb-3">
            <img src="<?php echo htmlspecialchars($logoWeb, ENT_QUOTES, 'UTF-8'); ?>" alt="" class="d-block" style="max-height:64px;max-width:220px;object-fit:contain;" />
          </div>
            <?php
        }
        if (is_array($e) && !empty($e['nombre_empresa'])) {
            ?>
          <div style="font-size:15px;font-weight:400;line-height:1.4;">
            <p class="mb-1 fw-semibold"><?php echo htmlspecialchars((string) $e['nombre_empresa'], ENT_QUOTES, 'UTF-8'); ?></p>
            <?php if (!empty($e['direccion_empresa'])) { ?>
            <p class="mb-1"><?php echo htmlspecialchars((string) $e['direccion_empresa'], ENT_QUOTES, 'UTF-8'); ?></p>
            <?php } ?>
            <?php if ($empDir !== '') { ?>
            <p class="mb-1"><?php echo htmlspecialchars($empDir, ENT_QUOTES, 'UTF-8'); ?></p>
            <?php } ?>
            <p class="mb-0"><?php echo htmlspecialchars(trim(($e['telefono_empresa'] ?? '') . (!empty($e['email_empresa']) ? ' · ' . $e['email_empresa'] : '')), ENT_QUOTES, 'UTF-8'); ?></p>
          </div>
            <?php
            if (!empty($e['cif_empresa'])) {
                ?>
          <p class="mb-0 mt-1 small text-muted"><?php echo htmlspecialchars((string) $e['cif_empresa'], ENT_QUOTES, 'UTF-8'); ?></p>
                <?php
            }
        }
        ?>
        </div>
        <div class="text-end">
          <h4 class="mb-4">ALBARÁN DE TRASPASO #<?php echo (int) $idDoc; ?></h4>
          <div class="mb-1">
            <span class="text-muted">Fecha:</span>
            <span><?php echo htmlspecialchars($fechaTxt, ENT_QUOTES, 'UTF-8'); ?></span>
          </div>
          <div class="mb-1">
            <span class="text-muted">Estado:</span>
            <span><?php echo htmlspecialchars($estadoTxt, ENT_QUOTES, 'UTF-8'); ?></span>
          </div>
        </div>
      </div>

      <hr class="mb-6" />

      <div class="d-flex justify-content-between mb-6 flex-wrap gap-4">
        <div class="my-2">
          <h6 class="mb-2">Origen</h6>
          <p class="mb-1 fw-medium"><?php echo htmlspecialchars($nomOrigen !== '' ? $nomOrigen : '—', ENT_QUOTES, 'UTF-8'); ?></p>
          <?php if ($dirO !== '') { ?>
          <p class="mb-0 small text-body-secondary"><?php echo htmlspecialchars($dirO, ENT_QUOTES, 'UTF-8'); ?></p>
          <?php } ?>
          <?php if ($cpPobO !== '') { ?>
          <p class="mb-0 small text-body-secondary"><?php echo htmlspecialchars($cpPobO, ENT_QUOTES, 'UTF-8'); ?></p>
          <?php } ?>
        </div>
        <div class="my-2">
          <h6 class="mb-2">Destino</h6>
          <p class="mb-1 fw-medium"><?php echo htmlspecialchars($nomDest !== '' ? $nomDest : '—', ENT_QUOTES, 'UTF-8'); ?></p>
          <?php if ($dirD !== '') { ?>
          <p class="mb-0 small text-body-secondary"><?php echo htmlspecialchars($dirD, ENT_QUOTES, 'UTF-8'); ?></p>
          <?php } ?>
          <?php if ($cpPobD !== '') { ?>
          <p class="mb-0 small text-body-secondary"><?php echo htmlspecialchars($cpPobD, ENT_QUOTES, 'UTF-8'); ?></p>
          <?php } ?>
        </div>
        <div class="my-2">
          <h6 class="mb-2">Documento</h6>
          <table class="table table-sm table-borderless mb-0">
            <tbody>
              <tr>
                <td class="text-muted pe-3">Creado por</td>
                <td><?php echo htmlspecialchars($creador !== '' ? $creador : '—', ENT_QUOTES, 'UTF-8'); ?></td>
              </tr>
              <tr>
                <td class="text-muted pe-3">Artículos</td>
                <td><?php echo count($arts); ?></td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <div class="table-responsive border border-bottom-0 rounded">
        <table class="table m-0">
          <thead class="table-light">
            <tr>
              <th>SKU</th>
              <th>Descripción</th>
              <th class="text-end">Peso (g)</th>
              <th>Tipo</th>
              <th class="text-center">Uds.</th>
              <th class="text-end">Valor ref.</th>
            </tr>
          </thead>
          <tbody>
            <?php
            if (count($arts) === 0) {
                echo '<tr><td colspan="6" class="text-center text-muted py-4">Sin líneas</td></tr>';
            } else {
                foreach ($arts as $a) {
                    $sku = htmlspecialchars((string) ($a['sku'] ?? ''), ENT_QUOTES, 'UTF-8');
                    $desc = htmlspecialchars((string) ($a['descripcion'] ?? ''), ENT_QUOTES, 'UTF-8');
                    $peso = (float) ($a['peso'] ?? 0);
                    $tipo = htmlspecialchars((string) ($a['tipo'] ?? ''), ENT_QUOTES, 'UTF-8');
                    $uds = (int) ($a['unidades'] ?? 1);
                    $precio = (float) ($a['precio'] ?? 0);
                    $lineTotal = $precio * $uds;
                    ?>
            <tr>
              <td><?php echo $sku; ?></td>
              <td><?php echo $desc; ?></td>
              <td class="text-end"><?php echo htmlspecialchars(number_format($peso, 2, ',', ' '), ENT_QUOTES, 'UTF-8'); ?></td>
              <td><?php echo $tipo; ?></td>
              <td class="text-center"><?php echo (int) $uds; ?></td>
              <td class="text-end"><?php echo htmlspecialchars(traspaso_fmt_importe($lineTotal), ENT_QUOTES, 'UTF-8'); ?></td>
            </tr>
                    <?php
                }
            }
        ?>
          </tbody>
        </table>
      </div>

      <div class="table-responsive">
        <table class="table m-0 table-borderless">
          <tbody>
            <tr>
              <td class="align-top px-0 py-4">
                <?php if ($obs !== '') { ?>
                <p class="mb-1"><span class="fw-medium">Observaciones:</span></p>
                <p class="mb-0"><?php echo nl2br(htmlspecialchars($obs, ENT_QUOTES, 'UTF-8')); ?></p>
                <?php } else { ?>
                <span class="text-muted small">Sin observaciones.</span>
                <?php } ?>
              </td>
              <td class="text-end px-0 py-4 w-px-100" style="min-width: 140px;">
                <p class="mb-1"><span class="text-muted">Peso total (g):</span> <?php echo htmlspecialchars(number_format($sumPeso, 2, ',', ' '), ENT_QUOTES, 'UTF-8'); ?></p>
                <p class="mb-0 fw-medium">Valor ref. total: <?php echo htmlspecialchars(traspaso_fmt_importe($sumValor), ENT_QUOTES, 'UTF-8'); ?></p>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <hr class="mt-0 mb-4" />
      <p class="mb-0 small text-muted">Documento interno de movimiento de mercancía entre sucursales. No constituye factura.</p>
    </div>
        <?php
        return (string) ob_get_clean();
    }
}
