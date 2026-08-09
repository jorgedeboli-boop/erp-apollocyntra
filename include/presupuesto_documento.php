<?php
/**
 * Datos y HTML para documento de presupuesto (impresión / PDF).
 */

if (!function_exists('presupuesto_obtener_datos_documento')) {
    /**
     * @return array<string,mixed>|null
     */
    function presupuesto_obtener_datos_documento($conexion, $id_presupuesto)
    {
        $id_presupuesto = (int)$id_presupuesto;
        if ($id_presupuesto <= 0) {
            return null;
        }

        $stmt = mysqli_prepare($conexion, 'SELECT * FROM presupuestos WHERE id = ? LIMIT 1');
        mysqli_stmt_bind_param($stmt, 'i', $id_presupuesto);
        mysqli_stmt_execute($stmt);
        $pres = mysqli_stmt_get_result($stmt);
        $p = mysqli_fetch_assoc($pres);
        mysqli_stmt_close($stmt);

        if (!$p) {
            return null;
        }

        $idEmp = (int)$p['rel_id_empresa'];
        $stmt = mysqli_prepare(
            $conexion,
            'SELECT id_empresa, nombre_empresa, cif_empresa, direccion_empresa, poblacion_empresa,
                    provincia_empresa, telefono_empresa, codigo_postal_empresa, pais_empresa, email_empresa,
                    logotipo_empresa
             FROM empresas WHERE id_empresa = ? LIMIT 1'
        );
        mysqli_stmt_bind_param($stmt, 'i', $idEmp);
        mysqli_stmt_execute($stmt);
        $empresa = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);

        if (!$empresa) {
            return null;
        }

        $idCliente = (int)$p['id_cliente'];
        $cli = null;
        if ($idCliente > 0) {
            $st = mysqli_prepare($conexion, 'SELECT * FROM clientes WHERE id_cliente = ? LIMIT 1');
            mysqli_stmt_bind_param($st, 'i', $idCliente);
            mysqli_stmt_execute($st);
            $cli = mysqli_fetch_assoc(mysqli_stmt_get_result($st));
            mysqli_stmt_close($st);
        }

        $direccion = $idCliente > 0 ? obtenerDireccionCliente($idCliente) : null;
        $datosExtra = $idCliente > 0 ? obtenerDatosCliente($idCliente) : null;
        $emailCliente = '';
        if (is_array($datosExtra) && !empty($datosExtra['email'])) {
            $emailCliente = (string)$datosExtra['email'];
        }

        $stmtL = mysqli_prepare(
            $conexion,
            'SELECT * FROM presupuestos_lineas WHERE id_presupuesto = ? ORDER BY orden ASC, id ASC'
        );
        mysqli_stmt_bind_param($stmtL, 'i', $id_presupuesto);
        mysqli_stmt_execute($stmtL);
        $resL = mysqli_stmt_get_result($stmtL);
        $lineas = [];
        while ($row = mysqli_fetch_assoc($resL)) {
            $lineas[] = $row;
        }
        mysqli_stmt_close($stmtL);

        return [
            'presupuesto' => $p,
            'empresa' => $empresa,
            'cliente' => $cli,
            'direccion_cliente' => $direccion,
            'email_cliente' => $emailCliente,
            'lineas' => $lineas,
        ];
    }
}

if (!function_exists('presupuesto_fmt_importe')) {
    function presupuesto_fmt_importe($n)
    {
        return number_format((float)$n, 2, ',', ' ') . ' €';
    }
}

if (!function_exists('presupuesto_fmt_fecha')) {
    function presupuesto_fmt_fecha($sqlDate)
    {
        if ($sqlDate === null || $sqlDate === '' || $sqlDate === '0000-00-00') {
            return '—';
        }
        $t = strtotime($sqlDate);

        return $t ? date('d/m/Y', $t) : '—';
    }
}

if (!function_exists('presupuesto_mpdf_config')) {
    /**
     * Opciones para \Mpdf\Mpdf del presupuesto.
     * Fuente por defecto: arial (TTF incluida en vendor/mpdf); en CSS «helvetica» se resuelve a la misma.
     *
     * @return array<string,mixed>
     */
    function presupuesto_mpdf_config()
    {
        return [
            'mode' => 'utf-8',
            'format' => 'A4',
            'margin_left' => 12,
            'margin_right' => 12,
            'margin_top' => 14,
            'margin_bottom' => 14,
            'default_font' => 'chelvetica',
            'default_font_size' => 10,
        ];
    }
}

if (!function_exists('presupuesto_empresa_logo_ruta_absoluta')) {
    /**
     * Ruta local segura al fichero en /photos (raíz del proyecto).
     */
    function presupuesto_empresa_logo_ruta_absoluta($logotipo)
    {
        $logotipo = trim((string)$logotipo);
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

if (!function_exists('presupuesto_empresa_logo_src_web_desde_documents')) {
    /**
     * URL relativa para documents/presupuesto_invoice.php → ../photos/archivo
     */
    function presupuesto_empresa_logo_src_web_desde_documents($logotipo)
    {
        if (presupuesto_empresa_logo_ruta_absoluta($logotipo) === '') {
            return '';
        }

        return '../photos/' . basename($logotipo);
    }
}

if (!function_exists('presupuesto_empresa_logo_img_mpdf')) {
    /**
     * Etiqueta img para mPDF (data URI; evita rutas según CWD).
     */
    function presupuesto_empresa_logo_img_mpdf($logotipo)
    {
        $path = presupuesto_empresa_logo_ruta_absoluta($logotipo);
        if ($path === '') {
            return '';
        }
        $bin = @file_get_contents($path);
        if ($bin === false || $bin === '') {
            return '';
        }
        $mime = function_exists('mime_content_type') ? @mime_content_type($path) : '';
        if ($mime === false || $mime === '') {
            $mime = 'image/png';
        }
        $b64 = base64_encode($bin);

        return '<div style="margin-bottom:12px;"><img src="data:'
            . htmlspecialchars($mime, ENT_QUOTES, 'UTF-8')
            . ';base64,'
            . $b64
            . '" style="width:350px;height:60px;object-fit:contain;" alt="" /></div>';
    }
}

if (!function_exists('presupuesto_invoice_body_inner')) {
    /**
     * Bloque interior tipo documents/invoice.php (Bootstrap).
     *
     * @param array<string,mixed> $data
     */
    function presupuesto_invoice_body_inner(array $data)
    {
        $p = $data['presupuesto'];
        $e = $data['empresa'];
        $c = $data['cliente'];
        $dir = $data['direccion_cliente'];
        $lineas = $data['lineas'];

        $nombreCli = '';
        if (is_array($c)) {
            $nombreCli = trim(($c['nombre'] ?? '') . ' ' . ($c['apellido'] ?? ''));
        }
        if ($nombreCli === '') {
            $nombreCli = 'Cliente';
        }

        $dirLinea = '';
        if (is_array($dir)) {
            $dirLinea = trim(
                ($dir['direccion'] ?? '') . ', '
                . ($dir['c_poblacion'] ?? '') . ' '
                . ($dir['codigo_postal'] ?? '')
            );
        }

        $empDir = trim(
            ($e['direccion_empresa'] ?? '') . ', '
            . ($e['codigo_postal_empresa'] ?? '') . ' '
            . ($e['poblacion_empresa'] ?? '') . ' (' . ($e['provincia_empresa'] ?? '') . ')'
        );

        $numero = htmlspecialchars((string)($p['numero'] ?? ''), ENT_QUOTES, 'UTF-8');
        $tituloDoc = htmlspecialchars((string)($p['titulo'] ?? ''), ENT_QUOTES, 'UTF-8');
        $fechaCreacion = presupuesto_fmt_fecha($p['fecha_creacion'] ?? '');
        $fechaValidez = presupuesto_fmt_fecha($p['fecha_validez'] ?? '');

        $base = (float)($p['base_imponible'] ?? 0);
        $pctIva = (float)($p['porcentaje_iva'] ?? 0);
        $impIva = (float)($p['importe_iva'] ?? 0);
        $total = (float)($p['total'] ?? 0);

        $vendedor = isset($_SESSION['usuario_nombre_completo']) ? (string)$_SESSION['usuario_nombre_completo'] : '—';

        ob_start();
        ?>
    <div class="invoice-print p-0">
      <div class="d-flex justify-content-between flex-row">
        <div class="mb-2">
          <?php
            $logoWeb = presupuesto_empresa_logo_src_web_desde_documents($e['logotipo_empresa'] ?? '');
            if ($logoWeb !== '') {
                ?>
          <div class="mb-2">
            <img src="<?php echo htmlspecialchars($logoWeb, ENT_QUOTES, 'UTF-8'); ?>" alt="" class="d-block" style="max-height:64px;max-width:220px;object-fit:contain;" />
          </div>
            <?php
            }
            ?>
          <div class="presupuesto-empresa-cabecera" style="font-size:15px;font-weight:400;line-height:1;">
            <p style="font-size:inherit;font-weight:inherit;line-height:inherit;margin:0 0 0.25rem 0;padding:0;"><?php echo htmlspecialchars($e['nombre_empresa'] ?? '', ENT_QUOTES, 'UTF-8'); ?></p>
            <p style="font-size:inherit;font-weight:inherit;line-height:inherit;margin:0 0 0.25rem 0;padding:0;"><?php echo htmlspecialchars($e['direccion_empresa'] ?? '', ENT_QUOTES, 'UTF-8'); ?></p>
            <p style="font-size:inherit;font-weight:inherit;line-height:inherit;margin:0 0 0.25rem 0;padding:0;"><?php echo htmlspecialchars($empDir, ENT_QUOTES, 'UTF-8'); ?></p>
            <p style="font-size:inherit;font-weight:inherit;line-height:inherit;margin:0;padding:0;"><?php echo htmlspecialchars(($e['telefono_empresa'] ?? '') . ($e['email_empresa'] ? ' · ' . $e['email_empresa'] : ''), ENT_QUOTES, 'UTF-8'); ?></p>
          </div>
          <?php if (!empty($e['cif_empresa'])): ?>
          <p class="mb-0 small text-muted"><?php echo htmlspecialchars($e['cif_empresa'], ENT_QUOTES, 'UTF-8'); ?></p>
          <?php endif; ?>
        </div>
        <div> 
          <h4 class="mb-2 mt-3">PRESUPUESTO <?php echo $numero; ?></h4>
          <div class="mb-1">
            <span class="text-muted">Documento:</span>
            <span><?php echo $tituloDoc; ?></span>
          </div>
          <div class="mb-1">
            <span class="text-muted">Fecha:</span>
            <span><?php echo htmlspecialchars($fechaCreacion, ENT_QUOTES, 'UTF-8'); ?></span>
          </div>
          <div>
            <span class="text-muted">Válido hasta:</span>
            <span><?php echo htmlspecialchars($fechaValidez, ENT_QUOTES, 'UTF-8'); ?></span>
          </div>
        </div>
      </div>

      <hr class="mb-2" />

      <div class="d-flex justify-content-between mb-6">
        <div class="my-2">
          <h6 style="margin-bottom: 0px;font-size: 19px;">Presupuesto para</h6>
          <p class="mb-1"><?php echo htmlspecialchars($nombreCli, ENT_QUOTES, 'UTF-8'); ?></p>
          <?php if (is_array($c) && !empty($c['identificacion'])): ?>
          <p class="mb-1"><?php echo htmlspecialchars((string)$c['identificacion'], ENT_QUOTES, 'UTF-8'); ?></p>
          <?php endif; ?>
          <?php if ($dirLinea !== ''): ?>
          <p class="mb-1"><?php echo htmlspecialchars($dirLinea, ENT_QUOTES, 'UTF-8'); ?></p>
          <?php endif; ?>
          <?php if (is_array($c) && !empty($c['telefono'])): ?>
          <p class="mb-1"><?php echo htmlspecialchars((string)$c['telefono'], ENT_QUOTES, 'UTF-8'); ?></p>
          <?php endif; ?>
          <?php if (!empty($data['email_cliente'])): ?>
          <p class="mb-0"><?php echo htmlspecialchars($data['email_cliente'], ENT_QUOTES, 'UTF-8'); ?></p>
          <?php endif; ?>
        </div>
        <div class="my-2">
        <h6 style="margin-bottom: 5px;font-size: 19px;">Importes</h6>
        <table class="table table-sm table-borderless mb-0" style="font-size: 18px;line-height: 18px;">
            <tbody>
              <tr>
                <td style="padding-left: 0px;">Base imponible:</td>
                <td><?php echo htmlspecialchars(presupuesto_fmt_importe($base), ENT_QUOTES, 'UTF-8'); ?></td>
              </tr>
              <tr>
                <td style="padding-left: 0px;">IVA (<?php echo htmlspecialchars((string)$pctIva, ENT_QUOTES, 'UTF-8'); ?>%):</td>
                <td><?php echo htmlspecialchars(presupuesto_fmt_importe($impIva), ENT_QUOTES, 'UTF-8'); ?></td>
              </tr>
              <tr>
                <td class="fw-semibold" style="padding-left: 0px;">Total:</td>
                <td class="fw-semibold"><?php echo htmlspecialchars(presupuesto_fmt_importe($total), ENT_QUOTES, 'UTF-8'); ?></td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <div class="table-responsive border border-bottom-0 rounded">
        <table class="table m-0">
          <thead class="table-light">
            <tr>
              <th>Ref. / tipo</th>
              <th>Descripción</th>
              <th style="width: 104px;">P. unit.</th>
              <th>Cant.</th>
              <th style="width: 104px;">Importe</th>
            </tr>
          </thead>
          <tbody style="font-size: 12px;line-height: 12px;">
            <?php
            foreach ($lineas as $ln) {
                $tipo = strtolower((string)($ln['tipo'] ?? 'producto'));
                if ($tipo === 'comentario') {
                    echo '<tr><td colspan="5" class="text-muted"><em>'
                        . htmlspecialchars((string)($ln['descripcion'] ?? ''), ENT_QUOTES, 'UTF-8')
                        . '</em></td></tr>';
                    continue;
                }
                $ref = htmlspecialchars((string)($ln['referencia'] ?? ''), ENT_QUOTES, 'UTF-8');
                $desc = htmlspecialchars((string)($ln['descripcion'] ?? ''), ENT_QUOTES, 'UTF-8');
                $pu = (float)($ln['precio_unitario'] ?? 0);
                $cant = (float)($ln['cantidad'] ?? 1);
                $totL = isset($ln['total']) ? (float)$ln['total'] : round($pu * $cant, 2);
                ?>
            <tr>
              <td><?php echo $ref !== '' ? $ref : htmlspecialchars($tipo, ENT_QUOTES, 'UTF-8'); ?></td>
              <td><?php echo $desc; ?></td>
              <td><?php echo htmlspecialchars(presupuesto_fmt_importe($pu), ENT_QUOTES, 'UTF-8'); ?></td>
              <td align="center"><?php echo htmlspecialchars((string)$cant, ENT_QUOTES, 'UTF-8'); ?></td>
              <td><?php echo htmlspecialchars(presupuesto_fmt_importe($totL), ENT_QUOTES, 'UTF-8'); ?></td>
            </tr>
            <?php } ?>
          </tbody>
        </table>
      </div>
      <div class="table-responsive">
        <table class="table m-0 table-borderless">
          <tbody>
            <tr>
              <td class="align-top px-6 py-6">
                <p class="mb-1">
                  <span class="me-2 fw-medium">Enviado por:</span>
                  <span><?php echo htmlspecialchars($vendedor, ENT_QUOTES, 'UTF-8'); ?></span>
                </p>
                <?php if (!empty($p['descripcion'])): ?>
                <p class="mb-1 small">Descripción: <?php echo nl2br(htmlspecialchars((string)$p['descripcion'], ENT_QUOTES, 'UTF-8')); ?></p>
                <?php endif; ?>
              </td>
              <td class="px-0 py-12 w-px-100">
                <p class="mb-2">Subtotal:</p>
                <p class="mb-2 border-bottom pb-2">IVA (<?php echo htmlspecialchars((string)$pctIva, ENT_QUOTES, 'UTF-8'); ?>%):</p>
                <p class="mb-0 pt-2 fw-medium">Total:</p>
              </td>
              <td class="text-end px-0 py-6 w-px-100">
                <p class="fw-medium mb-2"><?php echo htmlspecialchars(presupuesto_fmt_importe($base), ENT_QUOTES, 'UTF-8'); ?></p>
                <p class="fw-medium mb-2 border-bottom pb-2"><?php echo htmlspecialchars(presupuesto_fmt_importe($impIva), ENT_QUOTES, 'UTF-8'); ?></p>
                <p class="fw-medium mb-0 pt-2"><?php echo htmlspecialchars(presupuesto_fmt_importe($total), ENT_QUOTES, 'UTF-8'); ?></p>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <hr class="mt-0 mb-6" />
      <div class="row">
        <div class="col-12">
          <?php if (!empty($p['notas_cliente'])): ?>
          <p class="mb-2"><span class="fw-medium">Notas:</span></p>
          <p class="mb-3"><?php echo nl2br(htmlspecialchars((string)$p['notas_cliente'], ENT_QUOTES, 'UTF-8')); ?></p>
          <?php endif; ?>
          <?php if (!empty($p['condiciones'])): ?>
          <p class="mb-1"><span class="fw-medium">Condiciones:</span></p>
          <span><?php echo nl2br(htmlspecialchars((string)$p['condiciones'], ENT_QUOTES, 'UTF-8')); ?></span>
          <?php endif; ?>
        </div>
      </div>
    </div>
        <?php
        return (string)ob_get_clean();
    }
}

if (!function_exists('presupuesto_invoice_html_mpdf')) {
    /**
     * HTML completo para mPDF.
     *
     * @param array<string,mixed> $data
     */
    function presupuesto_invoice_html_mpdf(array $data)
    {
        $p = $data['presupuesto'];
        $e = $data['empresa'];
        $c = $data['cliente'];
        $dir = $data['direccion_cliente'];
        $lineas = $data['lineas'];

        $nombreCli = '';
        if (is_array($c)) {
            $nombreCli = trim(($c['nombre'] ?? '') . ' ' . ($c['apellido'] ?? ''));
        }
        if ($nombreCli === '') {
            $nombreCli = 'Cliente';
        }

        $dirLinea = '';
        if (is_array($dir)) {
            $dirLinea = trim(
                ($dir['direccion'] ?? '') . ', '
                . ($dir['c_poblacion'] ?? '') . ' '
                . ($dir['codigo_postal'] ?? '')
            );
        }

        $empDir = trim(
            ($e['direccion_empresa'] ?? '') . ', '
            . ($e['codigo_postal_empresa'] ?? '') . ' '
            . ($e['poblacion_empresa'] ?? '') . ' (' . ($e['provincia_empresa'] ?? '') . ')'
        );

        $base = (float)($p['base_imponible'] ?? 0);
        $pctIva = (float)($p['porcentaje_iva'] ?? 0);
        $impIva = (float)($p['importe_iva'] ?? 0);
        $total = (float)($p['total'] ?? 0);
        $vendedor = isset($_SESSION['usuario_nombre_completo']) ? (string)$_SESSION['usuario_nombre_completo'] : '—';

        $rows = '';
        foreach ($lineas as $ln) {
            $tipo = strtolower((string)($ln['tipo'] ?? 'producto'));
            if ($tipo === 'comentario') {
                $rows .= '<tr><td colspan="5" style="font-style:italic;color:#666;">'
                    . htmlspecialchars((string)($ln['descripcion'] ?? ''), ENT_QUOTES, 'UTF-8')
                    . '</td></tr>';
                continue;
            }
            $ref = htmlspecialchars((string)($ln['referencia'] ?? ''), ENT_QUOTES, 'UTF-8');
            $desc = htmlspecialchars((string)($ln['descripcion'] ?? ''), ENT_QUOTES, 'UTF-8');
            $pu = (float)($ln['precio_unitario'] ?? 0);
            $cant = (float)($ln['cantidad'] ?? 1);
            $totL = isset($ln['total']) ? (float)$ln['total'] : round($pu * $cant, 2);
            $rShow = $ref !== '' ? $ref : htmlspecialchars($tipo, ENT_QUOTES, 'UTF-8');
            $rows .= '<tr><td>' . $rShow . '</td><td>' . $desc . '</td><td style="text-align:right;">'
                . htmlspecialchars(presupuesto_fmt_importe($pu), ENT_QUOTES, 'UTF-8') . '</td><td style="text-align:center;">'
                . htmlspecialchars((string)$cant, ENT_QUOTES, 'UTF-8') . '</td><td style="text-align:right;">'
                . htmlspecialchars(presupuesto_fmt_importe($totL), ENT_QUOTES, 'UTF-8') . '</td></tr>';
        }

        // Nombres en minúscula (convención mPDF). Evitar solo «sans-serif»: sustituye por DejaVu.
        $css = 'body,table,th,td,p,h2,h6{font-family:helvetica,sans-serif;font-size:10pt;color:#333; line-height:1.5;}'
            . 'h2{margin:0 0 8px 0;font-size:15pt;}h6{font-size:inherit;}table{border-collapse:collapse;width:100%;}'
            . 'th,td{padding:6px 8px;border:1px solid #ccc;}th{background:#f0f0f0;} .nb{border:none;}'
            . 'table.importes-pdf td,table.importes-pdf th{border:none !important;padding:3px 0;}';

        $html = '<html><head><meta charset="UTF-8"/><style>' . $css . '</style></head><body>';
        $html .= '<table class="nb" style="width:100%;margin-bottom:1px;"><tr><td style="vertical-align:top;border:none;width:55%;">';
        $html .= presupuesto_empresa_logo_img_mpdf($e['logotipo_empresa'] ?? '');
        $html .= '<p style="margin:2px 0;">' . htmlspecialchars($e['nombre_empresa'] ?? '', ENT_QUOTES, 'UTF-8') . '</p>';
        $html .= '<p style="margin:4px 0;">' . htmlspecialchars($e['direccion_empresa'] ?? '', ENT_QUOTES, 'UTF-8') . '</p>';
        $html .= '<p style="margin:4px 0;">' . htmlspecialchars($empDir, ENT_QUOTES, 'UTF-8') . '</p>';
        $html .= '<p style="margin:4px 0;">' . htmlspecialchars(
            ($e['telefono_empresa'] ?? '') . ($e['email_empresa'] ? ' · ' . $e['email_empresa'] : ''),
            ENT_QUOTES,
            'UTF-8'
        ) . '</p>';
        if (!empty($e['cif_empresa'])) {
            $html .= '<p style="margin:4px 0;font-size:9pt;">' . htmlspecialchars($e['cif_empresa'], ENT_QUOTES, 'UTF-8') . '</p>';
        }
        $html .= '</td><td style="vertical-align:top;border:none;text-align:right; padding-top: 6pt;">';
        $html .= '<br><h2 style="margin-bottom: 3px; margin-top: 6pt;">PRESUPUESTO ' . htmlspecialchars((string)($p['numero'] ?? ''), ENT_QUOTES, 'UTF-8') . '</h2>';
        $html .= '<p><strong>Documento:</strong> ' . htmlspecialchars((string)($p['titulo'] ?? ''), ENT_QUOTES, 'UTF-8') . '</p>';
        $html .= '<p><strong>Fecha:</strong> ' . htmlspecialchars(presupuesto_fmt_fecha($p['fecha_creacion'] ?? ''), ENT_QUOTES, 'UTF-8') . '</p>';
        $html .= '<p><strong>Válido hasta:</strong> ' . htmlspecialchars(presupuesto_fmt_fecha($p['fecha_validez'] ?? ''), ENT_QUOTES, 'UTF-8') . '</p>';
        $html .= '</td></tr></table><hr style="border:none;border-top:1px solid #ccc;"/>';

        $html .= '<table class="nb" style="width:100%;margin:1px 0;"><tr><td style="vertical-align:top;border:none;width:50%;">';
        $html .= '<h6 style="margin-bottom:10pt !important;">Presupuesto para</h6>';
        $html .= '<p style="margin:2px 0;">' . htmlspecialchars($nombreCli, ENT_QUOTES, 'UTF-8') . '</p>';
        if (is_array($c) && !empty($c['identificacion'])) {
            $html .= '<p style="margin:4px 0;">' . htmlspecialchars((string)$c['identificacion'], ENT_QUOTES, 'UTF-8') . '</p>';
        }
        if ($dirLinea !== '') {
            $html .= '<p style="margin:4px 0;">' . htmlspecialchars($dirLinea, ENT_QUOTES, 'UTF-8') . '</p>';
        }
        if (is_array($c) && !empty($c['telefono'])) {
            $html .= '<p style="margin:4px 0;">' . htmlspecialchars((string)$c['telefono'], ENT_QUOTES, 'UTF-8') . '</p>';
        }
        if (!empty($data['email_cliente'])) {
            $html .= '<p style="margin:4px 0;">' . htmlspecialchars($data['email_cliente'], ENT_QUOTES, 'UTF-8') . '</p>';
        }
        $html .= '</td><td style="vertical-align:top;border:none;">';
        $html .= '<h6 style="margin:0 0 6px 0;">Importes</h6>';
        $html .= '<table class="importes-pdf" style="width:100%; margin-bottom: 6pt;"><tr><td>Base imponible</td><td style="text-align:right;">'
            . htmlspecialchars(presupuesto_fmt_importe($base), ENT_QUOTES, 'UTF-8') . '</td></tr>';
        $html .= '<tr><td>IVA (' . htmlspecialchars((string)$pctIva, ENT_QUOTES, 'UTF-8') . '%)</td><td style="text-align:right;">'
            . htmlspecialchars(presupuesto_fmt_importe($impIva), ENT_QUOTES, 'UTF-8') . '</td></tr>';
        $html .= '<tr><td><strong>Total</strong></td><td style="text-align:right;"><strong>'
            . htmlspecialchars(presupuesto_fmt_importe($total), ENT_QUOTES, 'UTF-8') . '</strong></td></tr></table>';
        $html .= '</td></tr></table>';

        $html .= '<table style="margin-top: 6pt;"><thead><tr><th>Ref. / tipo</th><th>Descripción</th><th>P. unit.</th><th>Cant.</th><th>Importe</th></tr></thead><tbody>';
        $html .= $rows;
        $html .= '</tbody></table>';

        $html .= '<p style="margin-top:16px;"><strong>Enviado por:</strong> ' . htmlspecialchars($vendedor, ENT_QUOTES, 'UTF-8') . '</p>';
        if (!empty($p['descripcion'])) {
            $html .= '<p style="font-size:9pt;">Descripción: ' . nl2br(htmlspecialchars((string)$p['descripcion'], ENT_QUOTES, 'UTF-8')) . '</p>';
        }
        if (!empty($p['notas_cliente'])) {
            $html .= '<p><strong>Notas:</strong><br/>' . nl2br(htmlspecialchars((string)$p['notas_cliente'], ENT_QUOTES, 'UTF-8')) . '</p>';
        }
        if (!empty($p['condiciones'])) {
            $html .= '<p><strong>Condiciones:</strong><br/>' . nl2br(htmlspecialchars((string)$p['condiciones'], ENT_QUOTES, 'UTF-8')) . '</p>';
        }
        $html .= '</body></html>';

        return $html;
    }
}
