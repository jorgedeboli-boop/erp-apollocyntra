<?php
if (!function_exists('venta_fmt_fecha_cliente')) {
    function venta_fmt_fecha_cliente($valor)
    {
        $v = trim((string) $valor);
        if ($v === '' || substr($v, 0, 10) === '0000-00-00') {
            return '—';
        }
        $t = strtotime($v);

        return $t ? date('d/m/Y', $t) : '—';
    }
}

$id_venta = isset($id) ? (int) $id : 0;
$venta_principal = null;
$lineas_venta = [];
$cliente_ficha = null;
$id_comprobantes = 0;
$id_factura = 0;
$total_ticket = 0.0;
$hay_datos = false;
$plazos_pagados_count = 0;
$plazos_vencidos_count = 0;
$plazos_pendientes_count = 0;
$total_pagado_plazos = 0.0;
$total_pendiente_plazos = 0.0;

if ($id_venta > 0) {
    $conexion = conectar_bd();
    $stmt = mysqli_prepare(
        $conexion,
        'SELECT v.*, u.usuario AS comprado_por_usuario, u.nombre_usuario, u.apellido_usuario,
                ua.usuario AS anulado_por_usuario, ua.nombre_usuario AS anulado_nombre_usuario, ua.apellido_usuario AS anulado_apellido_usuario
         FROM ventas v
         LEFT JOIN usuarios u ON v.comprado_por = u.id_usuario
         LEFT JOIN usuarios ua ON v.anulado_por = ua.id_usuario
         WHERE v.id = ?
         LIMIT 1'
    );
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'i', $id_venta);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        if ($res && ($venta_principal = mysqli_fetch_assoc($res))) {
            $hay_datos = true;
            $sid = (int) $venta_principal['id_sucursal'];
            $nvs = (int) $venta_principal['id_venta_sucursal'];

            // Comprobantes ligados a esta ficha (ventas.id de la URL), no al MIN(id) del ticket legacy.
            $id_comprobantes = $id_venta;

            $stmtL = mysqli_prepare(
                $conexion,
                'SELECT r.id_rel_art_venta AS id,
                        r.sku_articulo AS id_articulo_venta,
                        r.precio_venta AS precio,
                        av.descripcion, av.peso, av.tipo, av.estado AS estado_articulo_av
                 FROM rel_articulos_venta r
                 INNER JOIN articulos_venta av ON av.id = r.sku_articulo
                 WHERE r.sucursal_venta = ? AND r.rel_id_venta = ?
                 ORDER BY r.id_rel_art_venta ASC'
            );
            if ($stmtL) {
                mysqli_stmt_bind_param($stmtL, 'ii', $sid, $id_venta);
                mysqli_stmt_execute($stmtL);
                $resL = mysqli_stmt_get_result($stmtL);
                if ($resL) {
                    while ($lv = mysqli_fetch_assoc($resL)) {
                        $lineas_venta[] = $lv;
                        $total_ticket += (float) ($lv['precio'] ?? 0);
                    }
                }
                mysqli_stmt_close($stmtL);
            }
            if (count($lineas_venta) === 0) {
                $stmtL2 = mysqli_prepare(
                    $conexion,
                    'SELECT v.*, av.descripcion, av.peso, av.tipo, av.estado AS estado_articulo_av
                     FROM ventas v
                     LEFT JOIN articulos_venta av ON v.id_articulo_venta = av.id
                     WHERE v.id_sucursal = ? AND v.id_venta_sucursal = ?
                     ORDER BY v.id ASC'
                );
                if ($stmtL2) {
                    mysqli_stmt_bind_param($stmtL2, 'ii', $sid, $nvs);
                    mysqli_stmt_execute($stmtL2);
                    $resL2 = mysqli_stmt_get_result($stmtL2);
                    if ($resL2) {
                        while ($lv = mysqli_fetch_assoc($resL2)) {
                            $lineas_venta[] = $lv;
                            $total_ticket += (float) ($lv['precio'] ?? 0);
                        }
                    }
                    mysqli_stmt_close($stmtL2);
                }
            }

            $stmtFr = mysqli_prepare(
                $conexion,
                'SELECT id_factura FROM facturas WHERE rel_id_venta = ? AND id_sucursal = ? LIMIT 1'
            );
            if ($stmtFr) {
                mysqli_stmt_bind_param($stmtFr, 'ii', $id_venta, $sid);
                mysqli_stmt_execute($stmtFr);
                $resFr = mysqli_stmt_get_result($stmtFr);
                if ($resFr && ($rowFr = mysqli_fetch_assoc($resFr))) {
                    $id_factura = (int) ($rowFr['id_factura'] ?? 0);
                }
                mysqli_stmt_close($stmtFr);
            }

            $idClienteV = (int) ($venta_principal['cliente'] ?? 0);
            if ($idClienteV > 0) {
                $stmtC = mysqli_prepare(
                    $conexion,
                    "SELECT c.*, dc.movil AS cliente_movil, dc.email AS cliente_email, dc.f_nacimiento AS cliente_f_nacimiento,
                            dc.sexo AS cliente_sexo,
                            d.direccion AS cliente_direccion, d.c_poblacion AS cliente_poblacion, d.c_provincia AS cliente_provincia,
                            d.c_pais AS cliente_pais,
                            d.codigo_postal AS cliente_cp
                     FROM clientes c
                     LEFT JOIN datos_clientes dc ON c.id_cliente = dc.rel_id_cliente
                     LEFT JOIN direcciones d ON c.id_cliente = d.rel_id_item AND d.type_direccion = 'clientes'
                     WHERE c.id_cliente = ?
                     LIMIT 1"
                );
                if ($stmtC) {
                    mysqli_stmt_bind_param($stmtC, 'i', $idClienteV);
                    mysqli_stmt_execute($stmtC);
                    $resC = mysqli_stmt_get_result($stmtC);
                    if ($resC) {
                        $cliente_ficha = mysqli_fetch_assoc($resC) ?: null;
                    }
                    mysqli_stmt_close($stmtC);
                }
            }

            if (($venta_principal['venta_plazos'] ?? '') === 'si') {
                $stmtPlz = mysqli_prepare(
                    $conexion,
                    "SELECT COUNT(id) AS c, COALESCE(SUM(importe), 0) AS total_pagado
                     FROM ventas_plazos WHERE id_venta = ? AND estado = 'Pagado'"
                );
                if ($stmtPlz) {
                    mysqli_stmt_bind_param($stmtPlz, 'i', $id_venta);
                    mysqli_stmt_execute($stmtPlz);
                    $resPlz = mysqli_stmt_get_result($stmtPlz);
                    $rowPlz = $resPlz ? mysqli_fetch_assoc($resPlz) : null;
                    mysqli_stmt_close($stmtPlz);
                    $plazos_pagados_count = (int) ($rowPlz['c'] ?? 0);
                    $total_pagado_plazos = (float) ($rowPlz['total_pagado'] ?? 0);
                }
                $stmtPlzVen = mysqli_prepare(
                    $conexion,
                    "SELECT COUNT(id) AS c FROM ventas_plazos WHERE id_venta = ? AND estado = 'Vencido'"
                );
                if ($stmtPlzVen) {
                    mysqli_stmt_bind_param($stmtPlzVen, 'i', $id_venta);
                    mysqli_stmt_execute($stmtPlzVen);
                    $resPlzVen = mysqli_stmt_get_result($stmtPlzVen);
                    $rowPlzVen = $resPlzVen ? mysqli_fetch_assoc($resPlzVen) : null;
                    mysqli_stmt_close($stmtPlzVen);
                    $plazos_vencidos_count = (int) ($rowPlzVen['c'] ?? 0);
                }
                $numero_plazos_venta = (int) ($venta_principal['numero_plazos'] ?? 0);
                $plazos_pendientes_count = max(0, $numero_plazos_venta - $plazos_pagados_count);
                $precio_venta_plazos = (float) ($venta_principal['precio'] ?? 0);
                $total_pendiente_plazos = max(0, $precio_venta_plazos - $total_pagado_plazos);
            }

        }
        mysqli_stmt_close($stmt);
    }
    mysqli_close($conexion);
}

$ids_articulo_venta_ticket = [];
$id_articulo_venta_ancla = 0;
if ($hay_datos) {
    foreach ($lineas_venta as $ln) {
        $av = (int) ($ln['id_articulo_venta'] ?? 0);
        if ($av > 0) {
            $ids_articulo_venta_ticket[] = $av;
        }
    }
    $ids_articulo_venta_ticket = array_values(array_unique($ids_articulo_venta_ticket));
    $id_articulo_venta_ancla = count($ids_articulo_venta_ticket) ? min($ids_articulo_venta_ticket) : 0;
}

$venta_permite_adelanto_capital = false;
$venta_permite_anular_plazos = false;
if ($hay_datos) {
    $est_adel = strtolower((string) ($venta_principal['estado'] ?? ''));
    $venta_plazos_activa_en_curso = (($venta_principal['venta_plazos'] ?? '') === 'si')
        && in_array($est_adel, ['enfecha', 'vencido'], true);
    $venta_permite_adelanto_capital = $venta_plazos_activa_en_curso && $plazos_vencidos_count === 0;
    $venta_permite_anular_plazos = $venta_plazos_activa_en_curso;
}

$estado_class = 'secondary';
$estado_texto = '';
if ($hay_datos) {
    $est = strtolower((string) ($venta_principal['estado'] ?? ''));
    $estado_texto = (string) ($venta_principal['estado'] ?? '');
    if ($est === 'vendido') {
        $estado_class = 'success';
    } elseif ($est === 'anulada') {
        $estado_class = 'danger';
    } elseif ($est === 'enfecha') {
        $estado_class = 'info';
        $estado_texto = 'en plazo';
    } elseif ($est === 'vencido') {
        $estado_class = 'warning';
        $estado_texto = 'vencida';
    }
}

$labels_tipo_pago = [
    'contado' => 'Contado',
    'tarjeta' => 'Tarjeta',
    'bizum' => 'Bizum',
    'transferencia' => 'Transferencia',
    'combinado' => 'Combinado',
];
?>
<!-- Content -->
<div class="container-fluid flex-grow-1 container-p-y">
<?php if (!$id_venta) : ?>
  <div class="alert alert-warning">No se ha indicado la venta.</div>
<?php elseif (!$hay_datos) : ?>
  <div class="alert alert-danger">Venta no encontrada.</div>
<?php else : ?>
  <!-- Header -->
  <div class="row">
    <div class="col-12">
      <div class="card mb-6">
        <div class="user-profile-header d-flex flex-column flex-sm-row text-sm-start text-center mb-5">
          <div class="flex-grow-1 mt-4 mt-sm-12">
            <div class="d-flex align-items-md-end align-items-sm-start align-items-center justify-content-md-between justify-content-start mx-5 flex-md-row flex-column gap-6">
              <div class="user-profile-info">
                <h4 class="mb-2"><?php echo ($venta_principal['venta_plazos'] ?? '') === 'si' ? 'Venta a plazos' : 'Venta'; ?> Nº <?php echo htmlspecialchars((string) $venta_principal['id_venta_sucursal']); ?></h4>
                <ul class="list-inline mb-0 d-flex align-items-center flex-wrap justify-content-sm-start justify-content-center gap-4">
                  <li class="list-inline-item">
                    <i class="icon-base ri ri-hashtag icon-24px me-2"></i><span class="fw-medium">ID interno: <?php echo (int) $venta_principal['id']; ?></span>
                  </li>
                  <li class="list-inline-item">
                    <i class="icon-base ri ri-calendar-line me-2 icon-24px"></i><span class="fw-medium"><?php echo !empty($venta_principal['fecha']) ? date('d/m/Y H:i', strtotime($venta_principal['fecha'])) : '—'; ?></span>
                  </li>
                </ul>
                <div class="d-flex gap-2 flex-wrap justify-content-sm-start justify-content-center mt-3">
                  <div id="badge_estado_venta_ficha" class="badge bg-label-<?php echo htmlspecialchars($estado_class); ?> rounded-pill lh-xs badget-estados">Estado: <?php echo htmlspecialchars($estado_texto); ?></div>
                  <?php if (($venta_principal['venta_plazos'] ?? '') === 'si') : ?>
                    <div id="badge_plazos_pagados_venta_ficha" class="badge bg-label-success rounded-pill lh-xs badget-estados">Plazos pagados: <?php echo (int) $plazos_pagados_count; ?></div>
                    <div id="badge_plazos_pendientes_venta_ficha" class="badge bg-label-warning rounded-pill lh-xs badget-estados">Plazos pendientes: <?php echo (int) $plazos_pendientes_count; ?></div>
                    <div id="badge_total_pagado_venta_ficha" class="badge bg-label-success rounded-pill lh-xs badget-estados">Total pagado: <?php echo number_format($total_pagado_plazos, 2, ',', '.'); ?> €</div>
                    <div id="badge_total_pendiente_venta_ficha" class="badge bg-label-warning rounded-pill lh-xs badget-estados">Total pendiente: <?php echo number_format($total_pendiente_plazos, 2, ',', '.'); ?> €</div>
                  <?php endif; ?>
                  <div class="badge bg-label-primary rounded-pill lh-xs badget-estados">Total venta: <?php echo number_format($total_ticket, 2, ',', '.'); ?> €</div>
                </div>
              </div>
              <div class="d-flex gap-2 flex-wrap justify-content-center">
                <button
                  id="btnVolverVentas"
                  type="button"
                  class="btn btn-text-primary btn-header-card-right"
                  data-href-ventas="ventas.php"
                  onclick="window.location.href='ventas.php'"
                >
                  <i class="icon-base ri ri-arrow-left-s-line me-2"></i><span id="txtVolverVentas">Ventas</span>
                </button>
                <script>
                  (function () {
                    var btn = document.getElementById('btnVolverVentas');
                    var txt = document.getElementById('txtVolverVentas');
                    if (!btn || !txt) return;

                    var ref = String(document.referrer || '');
                    var sp = new URLSearchParams(window.location.search);
                    var from = (sp.get('from') || '').toLowerCase();

                    var vieneDePlazos =
                      from === 'plazos' ||
                      ref.indexOf('ventas_plazos') !== -1 ||
                      ref.indexOf('parts/ventas_plazos') !== -1;

                    if (!vieneDePlazos) return;

                    txt.textContent = 'Ventas a plazos';
                    btn.onclick = function () {
                      if (ref) {
                        window.location.href = ref;
                      } else {
                        window.history.back();
                      }
                    };
                  })();
                </script>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="row">

    <div class="col-md-12">
      <div class="nav-align-top">
        <ul class="nav nav-pills mb-4" role="tablist">
          <li class="nav-item" role="presentation">
            <button type="button" class="nav-link waves-effect waves-light active" role="tab" data-bs-toggle="tab" data-bs-target="#navs-pills-venta-datos" aria-controls="navs-pills-venta-datos" aria-selected="true">
              <i class="icon-base ri ri-shopping-cart-2-line icon-sm me-2"></i>Datos venta
            </button>
          </li>
          <li class="nav-item" role="presentation">
            <button type="button" class="nav-link waves-effect waves-light" role="tab" data-bs-toggle="tab" data-bs-target="#navs-pills-venta-articulos" aria-controls="navs-pills-venta-articulos" aria-selected="false">
              <i class="icon-base ri ri-shopping-bag-line icon-sm me-2"></i>Artículos
            </button>
          </li>
          <li class="nav-item" role="presentation">
            <button type="button" class="nav-link waves-effect waves-light" role="tab" data-bs-toggle="tab" data-bs-target="#navs-pills-venta-cliente" aria-controls="navs-pills-venta-cliente" aria-selected="false">
              <i class="icon-base ri ri-user-3-line icon-sm me-2"></i>Cliente
            </button>
          </li>
          <?php if (($venta_principal['venta_plazos'] ?? '') === 'si') : ?>
          <li class="nav-item" role="presentation">
            <button type="button" class="nav-link waves-effect waves-light" role="tab" data-bs-toggle="tab" data-bs-target="#navs-pills-venta-historial-plazos" aria-controls="navs-pills-venta-historial-plazos" aria-selected="false">
              <i class="icon-base ri ri-calendar-todo-line icon-sm me-2"></i>Plazos
            </button>
          </li>
          <li class="nav-item" role="presentation">
            <button type="button" class="nav-link waves-effect waves-light" role="tab" data-bs-toggle="tab" data-bs-target="#navs-pills-venta-adelantos-capital" aria-controls="navs-pills-venta-adelantos-capital" aria-selected="false">
              <i class="icon-base ri ri-hand-coin-line icon-sm me-2"></i>Adelantos
            </button>
          </li>
          <?php endif; ?>
        </ul>
      </div>
    </div>

  </div>

  <div class="tab-content">
    <div class="tab-pane fade show active" id="navs-pills-venta-datos" role="tabpanel">
      <div class="row">
      <div class="col-12">
        <!-- Botones de acciones de la venta -->
        <div class="card card-action mb-6">
          <div class="card-header border-bottom align-items-center card-header-forms-buttons-action-lote">
            <div id="contenedor_acciones_venta_ficha" class="d-flex justify-content-end gap-3"></div>
          </div>
        </div>
      </div>

        <div class="col-xl-4 col-lg-5 col-md-6">
          <div class="card mb-6">
            <div class="card-body">
              <small class="card-text text-uppercase text-body-secondary small">Resumen</small>
              <ul class="list-unstyled my-3 py-1">
                <li class="d-flex align-items-center mb-4">
                  <i class="icon-base ri ri-price-tag-3-line icon-24px"></i><span class="fw-medium mx-2">Tipo de pago:</span>
                  <span><?php
                    $tp = strtolower((string) ($venta_principal['tipo_pago'] ?? ''));
                    echo htmlspecialchars($labels_tipo_pago[$tp] ?? $venta_principal['tipo_pago']);
                  ?></span>
                </li>
                <li class="d-flex align-items-center mb-4">
                  <i class="icon-base ri ri-user-star-line icon-24px"></i><span class="fw-medium mx-2">Comprado por:</span>
                  <span><?php
                    $nomU = trim((string) ($venta_principal['nombre_usuario'] ?? '') . ' ' . (string) ($venta_principal['apellido_usuario'] ?? ''));
                    echo htmlspecialchars($nomU !== '' ? $nomU : (string) ($venta_principal['comprado_por_usuario'] ?? '—'));
                  ?></span>
                </li>
                <li class="d-flex align-items-center mb-4">
                  <i class="icon-base ri ri-money-euro-circle-line icon-24px"></i><span class="fw-medium mx-2">Total ticket:</span>
                  <span class="fw-semibold"><?php echo number_format($total_ticket, 2, ',', '.'); ?> €</span>
                </li>
                <li class="d-flex align-items-center mb-4">
                  <i class="icon-base ri ri-percent-line icon-24px"></i><span class="fw-medium mx-2">Plazos / interés:</span>
                  <span><?php echo ($venta_principal['venta_plazos'] ?? '') === 'si'
                    ? (int) ($venta_principal['numero_plazos'] ?? 0) . ' plazos · ' . (int) ($venta_principal['intereses'] ?? 0) . '%'
                    : '—'; ?></span>
                </li>
              
                <li class="d-flex align-items-center mb-4">
                  <i class="icon-base ri ri-wallet-line icon-24px"></i><span class="fw-medium mx-2">Contado:</span>
                  <span><?php echo number_format((float) ($venta_principal['cantidad_contado'] ?? 0), 2, ',', '.'); ?> €</span>
                </li>
                <li class="d-flex align-items-center mb-4">
                  <i class="icon-base ri ri-bank-card-line icon-24px"></i><span class="fw-medium mx-2">Tarjeta:</span>
                  <span><?php echo number_format((float) ($venta_principal['cantidad_tarjeta'] ?? 0), 2, ',', '.'); ?> €</span>
                </li>
                <li class="d-flex align-items-center mb-4">
                  <i class="icon-base ri ri-smartphone-line icon-24px"></i><span class="fw-medium mx-2">Bizum:</span>
                  <span><?php echo number_format((float) ($venta_principal['cantidad_bizum'] ?? 0), 2, ',', '.'); ?> €</span>
                </li>
                <li class="d-flex align-items-center <?php echo strtolower((string) ($venta_principal['estado'] ?? '')) === 'anulado' ? 'mb-4' : 'mb-0'; ?>">
                  <i class="icon-base ri ri-exchange-line icon-24px"></i><span class="fw-medium mx-2">Transferencia:</span>
                  <span><?php echo number_format((float) ($venta_principal['cantidad_transferencia'] ?? 0), 2, ',', '.'); ?> €</span>
                </li>
                <?php if (strtolower((string) ($venta_principal['estado'] ?? '')) === 'anulado') : ?>
                <?php
                  $motivo_anulacion_txt = trim((string) ($venta_principal['motivo_anulacion'] ?? ''));
                  $fecha_anulacion_raw = trim((string) ($venta_principal['fecha_anulacion'] ?? ''));
                  if ($fecha_anulacion_raw === '' || substr($fecha_anulacion_raw, 0, 10) === '0000-00-00') {
                      $fecha_anulacion_fmt = '—';
                  } else {
                      $ts_anul = strtotime($fecha_anulacion_raw);
                      $fecha_anulacion_fmt = $ts_anul
                          ? (strlen($fecha_anulacion_raw) > 10 ? date('d/m/Y H:i', $ts_anul) : date('d/m/Y', $ts_anul))
                          : '—';
                  }
                  $anulado_por_txt = trim(
                      (string) ($venta_principal['anulado_nombre_usuario'] ?? '') . ' '
                      . (string) ($venta_principal['anulado_apellido_usuario'] ?? '')
                  );
                  if ($anulado_por_txt === '') {
                      $anulado_por_txt = trim((string) ($venta_principal['anulado_por_usuario'] ?? ''));
                  }
                  if ($anulado_por_txt === '' && (int) ($venta_principal['anulado_por'] ?? 0) > 0) {
                      $anulado_por_txt = 'Usuario #' . (int) $venta_principal['anulado_por'];
                  }
                  if ($anulado_por_txt === '') {
                      $anulado_por_txt = '—';
                  }
                ?>
                <li class="d-flex align-items-start mb-4">
                  <i class="icon-base ri ri-file-text-line icon-24px"></i><span class="fw-medium mx-2">Motivo de la anulación:</span>
                  <span><?php echo htmlspecialchars($motivo_anulacion_txt !== '' ? $motivo_anulacion_txt : '—'); ?></span>
                </li>
                <li class="d-flex align-items-center mb-4">
                  <i class="icon-base ri ri-calendar-close-line icon-24px"></i><span class="fw-medium mx-2">Fecha de anulación:</span>
                  <span><?php echo htmlspecialchars($fecha_anulacion_fmt); ?></span>
                </li>
                <li class="d-flex align-items-center mb-0">
                  <i class="icon-base ri ri-user-unfollow-line icon-24px"></i><span class="fw-medium mx-2">Anulado por:</span>
                  <span><?php echo htmlspecialchars($anulado_por_txt); ?></span>
                </li>
                <?php endif; ?>
              </ul>
            </div>
          </div>
        </div>
        <div class="col-xl-8 col-lg-7 col-md-6">
          <div class="card card-action mt-0 mb-6">
            <div class="card-header mb-0 pt-2 pb-1">
              <h5 class="text-uppercase text-body-secondary small card-title mb-0 me-auto p-2">Comprobantes de la venta</h5>
              <div class="position-absolute" style="right: 13px; top: 13px;">
                <button type="button" class="p-2 btn rounded-pill btn-icon btn-primary waves-effect waves-light" onclick="abrirModalFotoMovilVenta()" title="Comprobante con móvil (cámara)">
                  <span class="icon-base ri ri-camera-ai-fill icon-22px"></span>
                </button>
                <button type="button" class="p-2 btn rounded-pill btn-icon btn-primary waves-effect waves-light" onclick="abrirModalSubirComprobanteVenta()" title="Subir archivo">
                  <span class="icon-base ri ri-upload-line icon-22px"></span>
                </button>
              </div>
            </div>
            <div class="card-body pt-0">
              <div class="row visor_documento_global" id="visor_documentos">
                <div class="col-12">
                  <div id="contenedor_imagenes" class="row g-3"></div>
                  <div id="sin_imagenes" class="text-center py-5" style="display: none;">
                    <i class="icon-base ri ri-image-line icon-48px text-body-secondary mb-3"></i>
                    <p class="text-body-secondary mb-0">No hay comprobantes cargados para esta venta</p>
                  </div>
                  <div id="loading_imagenes" class="text-center py-4">
                    <div class="spinner-border text-primary" role="status"><span class="visually-hidden">Cargando...</span></div>
                  </div>
                </div>
              </div>

              <div class="modal fade" id="modalSubirComprobanteVenta" tabindex="-1" aria-labelledby="modalSubirComprobanteVentaLabel" aria-hidden="true">
                <div class="modal-dialog">
                  <div class="modal-content">
                    <div class="modal-header">
                      <h5 class="modal-title" id="modalSubirComprobanteVentaLabel">Subir comprobante</h5>
                      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                      <form id="formSubirComprobanteVenta" enctype="multipart/form-data">
                        <div class="mb-3">
                          <label for="archivo_comprobante_venta" class="form-label">Seleccionar archivo</label>
                          <input type="file" class="form-control" id="archivo_comprobante_venta" name="archivo_foto" accept=".jpg,.jpeg,.gif,.png,.pdf" required>
                          <div class="form-text">JPG, PNG, GIF o PDF (máx. 5 MB).</div>
                        </div>
                      </form>
                    </div>
                    <div class="modal-footer">
                      <button type="button" class="btn btn-sm btn-secondary waves-effect waves-light rounded-2 fs-6" data-bs-dismiss="modal"><i class="icon-base ri ri-close-circle-fill me-2 icon-22px"></i> Cerrar</button>
                      <button type="button" class="btn btn-primary" onclick="subirComprobanteVenta()">
                        <span class="spinner-border spinner-border-sm me-2 d-none" role="status" aria-hidden="true"></span>
                        Subir
                      </button>
                    </div>
                  </div>
                </div>
              </div>

              <div class="modal fade" id="modalAmpliarImagenVenta" tabindex="-1" aria-labelledby="modalAmpliarImagenVentaLabel" aria-hidden="true">
                <div class="modal-dialog modal-xl">
                  <div class="modal-content">
                    <div class="modal-header">
                      <h5 class="modal-title" id="modalAmpliarImagenVentaLabel">Vista ampliada</h5>
                      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                      <img id="imagen_ampliada_venta" src="" alt="" class="img-fluid">
                    </div>
                    <div class="modal-footer">
                      <button type="button" class="btn btn-sm btn-secondary waves-effect waves-light rounded-2 fs-6" data-bs-dismiss="modal"><i class="icon-base ri ri-close-circle-fill me-2 icon-22px"></i> Cerrar</button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="card card-action mt-4 mb-6">
            <div class="card-header mb-0 pt-2 pb-1">
              <h5 class="text-uppercase text-body-secondary small card-title mb-0 me-auto p-2">Fotos del ticket (artículos)</h5>
              <div class="position-absolute" style="right: 13px; top: 13px;">
                <button type="button" class="p-2 btn rounded-pill btn-icon btn-primary waves-effect waves-light" onclick="abrirModalFotoMovilArticulosVentaTicket()" title="Foto con móvil" <?php echo $id_articulo_venta_ancla <= 0 ? 'disabled' : ''; ?>>
                  <span class="icon-base ri ri-camera-ai-fill icon-22px"></span>
                </button>
                <button type="button" class="p-2 btn rounded-pill btn-icon btn-primary waves-effect waves-light" onclick="abrirModalSubirFotoArticulosVentaTicket()" title="Subir foto o PDF" <?php echo $id_articulo_venta_ancla <= 0 ? 'disabled' : ''; ?>>
                  <span class="icon-base ri ri-upload-line icon-22px"></span>
                </button>
              </div>
            </div>
            <div class="card-body pt-0">
              <?php if ($id_articulo_venta_ancla <= 0) : ?>
                <p class="text-body-secondary small mb-0">No hay líneas de artículo en este ticket; no se pueden asociar fotos.</p>
              <?php endif; ?>
              <div class="row visor_documento_global" id="visor_documentos_articulos_venta">
                <div class="col-12">
                  <div id="contenedor_imagenes_articulos_venta" class="row g-3"></div>
                  <div id="sin_imagenes_articulos_venta" class="text-center py-5" style="display: none;">
                    <i class="icon-base ri ri-image-line icon-48px text-body-secondary mb-3"></i>
                    <p class="text-body-secondary mb-0">No hay fotos cargadas para este ticket</p>
                  </div>
                  <div id="loading_imagenes_articulos_venta" class="text-center py-4">
                    <div class="spinner-border text-primary" role="status"><span class="visually-hidden">Cargando...</span></div>
                  </div>
                </div>
              </div>

              <div class="modal fade" id="modalSubirFotoArticulosVentaTicket" tabindex="-1" aria-labelledby="modalSubirFotoArticulosVentaTicketLabel" aria-hidden="true">
                <div class="modal-dialog">
                  <div class="modal-content">
                    <div class="modal-header">
                      <h5 class="modal-title" id="modalSubirFotoArticulosVentaTicketLabel">Subir foto del ticket</h5>
                      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                      <form id="formSubirFotoArticulosVentaTicket" enctype="multipart/form-data">
                        <div class="mb-3">
                          <label for="archivo_foto_articulos_venta_ticket" class="form-label">Seleccionar archivo</label>
                          <input type="file" class="form-control" id="archivo_foto_articulos_venta_ticket" name="archivo_foto" accept=".jpg,.jpeg,.gif,.png,.pdf" required>
                          <div class="form-text">JPG, PNG, GIF o PDF (máx. 5 MB). Galería común para todo el ticket.</div>
                        </div>
                      </form>
                    </div>
                    <div class="modal-footer">
                      <button type="button" class="btn btn-sm btn-secondary waves-effect waves-light rounded-2 fs-6" data-bs-dismiss="modal"><i class="icon-base ri ri-close-circle-fill me-2 icon-22px"></i> Cerrar</button>
                      <button type="button" class="btn btn-primary" onclick="subirFotoArticulosVentaTicket()">
                        <span class="spinner-border spinner-border-sm me-2 d-none" role="status" aria-hidden="true"></span>
                        Subir
                      </button>
                    </div>
                  </div>
                </div>
              </div>

              <div class="modal fade" id="modalAmpliarImagenArticulosVenta" tabindex="-1" aria-labelledby="modalAmpliarImagenArticulosVentaLabel" aria-hidden="true">
                <div class="modal-dialog modal-xl">
                  <div class="modal-content">
                    <div class="modal-header">
                      <h5 class="modal-title" id="modalAmpliarImagenArticulosVentaLabel">Vista ampliada</h5>
                      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                      <img id="imagen_ampliada_articulos_venta" src="" alt="" class="img-fluid">
                    </div>
                    <div class="modal-footer">
                      <button type="button" class="btn btn-sm btn-secondary waves-effect waves-light rounded-2 fs-6" data-bs-dismiss="modal"><i class="icon-base ri ri-close-circle-fill me-2 icon-22px"></i> Cerrar</button>
                    </div>
                  </div>
                </div>
              </div>

              <div class="modal fade" id="modalFotoMovilArticulosVenta" tabindex="-1" aria-labelledby="modalFotoMovilArticulosVentaLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                  <div class="modal-content">
                    <div class="modal-header">
                      <h5 class="modal-title" id="modalFotoMovilArticulosVentaLabel">Foto del ticket desde móvil</h5>
                      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body text-center py-5">
                      <p class="mb-4">Escanea el código QR con tu móvil para hacer la foto (se guarda en la galería del ticket).</p>
                      <div id="qrcode_container_articulo_venta" class="d-flex justify-content-center">
                        <div id="qrcode_articulo_venta"></div>
                      </div>
                    </div>
                    <div class="modal-footer">
                      <button type="button" class="btn btn-primary" onclick="generarNuevoQRArticuloVentaTicket()">
                        <i class="icon-base ri ri-refresh-line me-2"></i>Generar nuevo QR
                      </button>
                      <button type="button" class="btn btn-sm btn-secondary waves-effect waves-light rounded-2 fs-6" data-bs-dismiss="modal"><i class="icon-base ri ri-close-circle-fill me-2 icon-22px"></i> Cerrar</button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="tab-pane fade" id="navs-pills-venta-articulos" role="tabpanel">
      <div class="card mb-6">
        <div class="card-header border-bottom">
          <h5 class="card-title mb-0"><i class="icon-base ri ri-shopping-bag-line me-2"></i>Artículos de la venta</h5>
        </div>
        <div class="card-body p-0">
          <div class="card-datatable table-responsive">
            <table class="table table-hover mb-3 datatables-lineas-venta-ficha" id="tabla_articulos_venta_ficha" style="width: 100%;">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Descripción</th>
                  <th>Tipo</th>
                  <th>Peso (g)</th>
                  <th>Precio</th>
                  <th>Estado art.</th>
                  <th></th>
                </tr>
              </thead>
              <tbody></tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <div class="tab-pane fade" id="navs-pills-venta-cliente" role="tabpanel">
      <?php if ($cliente_ficha) :
          $sexo_raw = trim((string) ($cliente_ficha['cliente_sexo'] ?? ''));
          if ($sexo_raw === '') {
              $sexo_mostrar = '—';
          } else {
              $sl = strtolower($sexo_raw);
              if (in_array($sl, ['m', 'masculino', 'hombre', 'varón', 'varon'], true)) {
                  $sexo_mostrar = 'Masculino';
              } elseif (in_array($sl, ['f', 'femenino', 'mujer'], true)) {
                  $sexo_mostrar = 'Femenino';
              } else {
                  $sexo_mostrar = $sexo_raw;
              }
          }
          ?>
        <div class="row">
          <div class="col-xl-4 col-lg-5">
            <div class="card mb-6">
              <div class="card-body">
                <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap mb-1">
                  <small class="card-text text-uppercase text-body-secondary small mb-0">Cliente</small>
                  <a href="cliente.php?id=<?php echo (int) ($cliente_ficha['id_cliente'] ?? 0); ?>" class="btn btn-sm btn-text-primary btn-outline-primary" target="_blank" rel="noopener noreferrer">Ver ficha cliente</a>
                </div>
                <ul class="list-unstyled my-3 py-1">
                  <li class="d-flex align-items-center mb-4">
                    <i class="icon-base ri ri-user-line icon-24px"></i>
                    <span class="fw-medium mx-2">Nombre:</span>
                    <span><?php echo htmlspecialchars(trim((string) ($cliente_ficha['nombre'] ?? '') . ' ' . (string) ($cliente_ficha['apellido'] ?? ''))); ?></span>
                  </li>
                  <li class="d-flex align-items-center mb-4">
                    <i class="icon-base ri ri-id-card-line icon-24px"></i>
                    <span class="fw-medium mx-2">Identificación:</span>
                    <span><?php
                      $tipoDoc = trim((string) ($cliente_ficha['tipo_identificacion'] ?? ''));
                      $numDoc = trim((string) ($cliente_ficha['identificacion'] ?? ''));
                      $partes = [];
                      if ($tipoDoc !== '') {
                          $partes[] = $tipoDoc;
                      }
                      if ($numDoc !== '') {
                          $partes[] = $numDoc;
                      }
                      echo htmlspecialchars(count($partes) ? implode(' ', $partes) : '—');
                    ?></span>
                  </li>
                  <li class="d-flex align-items-center mb-4">
                    <i class="icon-base ri ri-phone-line icon-24px"></i>
                    <span class="fw-medium mx-2">Teléfono:</span>
                    <span><?php echo htmlspecialchars((string) ($cliente_ficha['telefono'] ?? $cliente_ficha['cliente_movil'] ?? '—')); ?></span>
                  </li>
                  <li class="d-flex align-items-center mb-4">
                    <i class="icon-base ri ri-mail-line icon-24px"></i>
                    <span class="fw-medium mx-2">Email:</span>
                    <span><?php echo htmlspecialchars(trim((string) ($cliente_ficha['cliente_email'] ?? '')) !== '' ? (string) $cliente_ficha['cliente_email'] : '—'); ?></span>
                  </li>
                  <li class="d-flex align-items-start mb-4">
                    <i class="icon-base ri ri-map-pin-line icon-24px"></i>
                    <span class="fw-medium mx-2">Dirección:</span>
                    <span><?php echo htmlspecialchars(trim((string) ($cliente_ficha['cliente_direccion'] ?? '')) !== '' ? (string) $cliente_ficha['cliente_direccion'] : '—'); ?></span>
                  </li>
                  <li class="d-flex align-items-center mb-4">
                    <i class="icon-base ri ri-community-line icon-24px"></i>
                    <span class="fw-medium mx-2">Población:</span>
                    <span><?php echo htmlspecialchars(trim((string) ($cliente_ficha['cliente_poblacion'] ?? '')) !== '' ? (string) $cliente_ficha['cliente_poblacion'] : '—'); ?></span>
                  </li>
                  <li class="d-flex align-items-center mb-4">
                    <i class="icon-base ri ri-building-line icon-24px"></i>
                    <span class="fw-medium mx-2">Provincia:</span>
                    <span><?php echo htmlspecialchars(trim((string) ($cliente_ficha['cliente_provincia'] ?? '')) !== '' ? (string) $cliente_ficha['cliente_provincia'] : '—'); ?></span>
                  </li>
                  <li class="d-flex align-items-center mb-4">
                    <i class="icon-base ri ri-earth-line icon-24px"></i>
                    <span class="fw-medium mx-2">País:</span>
                    <span><?php echo htmlspecialchars(trim((string) ($cliente_ficha['cliente_pais'] ?? '')) !== '' ? (string) $cliente_ficha['cliente_pais'] : '—'); ?></span>
                  </li>
                  <li class="d-flex align-items-center mb-4">
                    <i class="icon-base ri ri-flag-line icon-24px"></i>
                    <span class="fw-medium mx-2">Nacionalidad:</span>
                    <span><?php echo htmlspecialchars(trim((string) ($cliente_ficha['nacionalidad'] ?? '')) !== '' ? (string) $cliente_ficha['nacionalidad'] : '—'); ?></span>
                  </li>
                  <li class="d-flex align-items-center mb-4">
                    <i class="icon-base ri ri-men-line icon-24px"></i>
                    <span class="fw-medium mx-2">Sexo:</span>
                    <span><?php echo $sexo_mostrar === '—' ? '—' : htmlspecialchars($sexo_mostrar); ?></span>
                  </li>
                  <li class="d-flex align-items-center mb-4">
                    <i class="icon-base ri ri-cake-line icon-24px"></i>
                    <span class="fw-medium mx-2">Fecha de nacimiento:</span>
                    <span><?php echo htmlspecialchars(venta_fmt_fecha_cliente($cliente_ficha['cliente_f_nacimiento'] ?? '')); ?></span>
                  </li>
                  <li class="d-flex align-items-center mb-0">
                    <i class="icon-base ri ri-calendar-check-line icon-24px"></i>
                    <span class="fw-medium mx-2">Fecha de alta:</span>
                    <span><?php echo htmlspecialchars(venta_fmt_fecha_cliente($cliente_ficha['f_alta'] ?? '')); ?></span>
                  </li>
                </ul>
              </div>
            </div>
          </div>
          <div class="col-xl-8 col-lg-7">
            <div class="card card-action mb-6">
              <div class="card-header mb-0 pt-2 pb-1">
                <h5 class="text-uppercase text-body-secondary small card-title mb-0 me-auto p-2">Documentación del cliente</h5>
                <div class="position-absolute" style="right: 13px; top: 13px;">
                  <button type="button" class="p-2 btn rounded-pill btn-icon btn-primary waves-effect waves-light" id="btnFotoMovilClienteVenta">
                    <span class="icon-base ri ri-camera-ai-fill icon-22px"></span>
                  </button>
                  <button type="button" class="p-2 btn rounded-pill btn-icon btn-primary waves-effect waves-light" onclick="window.abrirModalSubirFotoClienteVenta()">
                    <span class="icon-base ri ri-upload-line icon-22px"></span>
                  </button>
                </div>
              </div>
              <div class="card-body">
                <div class="row visor_documento_global" id="visor_documentos_cliente_venta">
                  <div class="col-12">
                    <div id="contenedor_imagenes_cliente_venta" class="row g-3"></div>
                    <div id="sin_imagenes_cliente_venta" class="text-center py-5" style="display: none;">
                      <i class="icon-base ri ri-image-line icon-48px text-body-secondary mb-3"></i>
                      <p class="text-body-secondary mb-0">No hay documentos cargados para este cliente</p>
                    </div>
                    <div id="loading_imagenes_cliente_venta" class="text-center py-4">
                      <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Cargando...</span>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="modal fade" id="modalSubirFotoClienteVenta" tabindex="-1" aria-labelledby="modalSubirFotoClienteVentaLabel" aria-hidden="true">
                  <div class="modal-dialog">
                    <div class="modal-content">
                      <div class="modal-header">
                        <h5 class="modal-title" id="modalSubirFotoClienteVentaLabel">Subir documento</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                      </div>
                      <div class="modal-body">
                        <form id="formSubirFotoClienteVenta" enctype="multipart/form-data">
                          <div class="mb-3">
                            <label for="archivo_foto_cliente_venta" class="form-label">Seleccionar archivo</label>
                            <input type="file" class="form-control" id="archivo_foto_cliente_venta" name="archivo_foto" accept=".jpg,.jpeg,.gif,.png,.pdf,.PDF,.JPG,.JPEG,.GIF,.PNG" required>
                            <div class="form-text">Formatos permitidos: JPG, JPEG, GIF, PNG, PDF (máximo 5MB).</div>
                          </div>
                        </form>
                      </div>
                      <div class="modal-footer">
                        <button type="button" class="btn btn-sm btn-secondary waves-effect waves-light rounded-2 fs-6" data-bs-dismiss="modal"><i class="icon-base ri ri-close-circle-fill me-2 icon-22px"></i> Cerrar</button>
                        <button type="button" class="btn btn-primary" onclick="window.subirFotoClienteVenta()">
                          <span class="spinner-border spinner-border-sm me-2 d-none" role="status" aria-hidden="true"></span>
                          Subir
                        </button>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="modal fade" id="modalAmpliarImagenClienteVenta" tabindex="-1" aria-labelledby="modalAmpliarImagenClienteVentaLabel" aria-hidden="true">
                  <div class="modal-dialog modal-xl">
                    <div class="modal-content">
                      <div class="modal-header">
                        <h5 class="modal-title" id="modalAmpliarImagenClienteVentaLabel">Vista ampliada</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                      </div>
                      <div class="modal-body">
                        <img id="imagen_ampliada_cliente_venta" src="" alt="Imagen ampliada" class="img-fluid">
                      </div>
                      <div class="modal-footer">
                        <button type="button" class="btn btn-sm btn-secondary waves-effect waves-light rounded-2 fs-6" data-bs-dismiss="modal"><i class="icon-base ri ri-close-circle-fill me-2 icon-22px"></i> Cerrar</button>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div class="modal fade" id="modalFotoMovilClienteVenta" tabindex="-1" aria-labelledby="modalFotoMovilClienteVentaLabel" aria-hidden="true">
              <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                  <div class="modal-header">
                    <h5 class="modal-title" id="modalFotoMovilClienteVentaLabel">Hacer foto desde móvil</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                  </div>
                  <div class="modal-body text-center py-5">
                    <p class="mb-4">Escanea el código QR con tu móvil</p>
                    <div id="qrcode_container_cliente_venta" class="d-flex justify-content-center">
                      <div id="qrcode_cliente_venta"></div>
                    </div>
                  </div>
                  <div class="modal-footer">
                    <button type="button" class="btn btn-primary" onclick="window.generarNuevoQRClienteVenta()">
                      <i class="icon-base ri ri-refresh-line me-2"></i>Generar nuevo QR
                    </button>
                    <button type="button" class="btn btn-sm btn-secondary waves-effect waves-light rounded-2 fs-6" data-bs-dismiss="modal"><i class="icon-base ri ri-close-circle-fill me-2 icon-22px"></i> Cerrar</button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      <?php else : ?>
        <div class="card mb-6">
          <div class="card-body text-center py-5">
            <i class="icon-base ri ri-user-unfollow-line icon-48px text-body-secondary mb-3"></i>
            <p class="text-body-secondary mb-0">Esta venta no tiene cliente asociado.</p>
          </div>
        </div>
      <?php endif; ?>
    </div>

    <?php if (($venta_principal['venta_plazos'] ?? '') === 'si') : ?>
    <div class="tab-pane fade" id="navs-pills-venta-historial-plazos" role="tabpanel">
      <div class="card mb-6">
        <div class="card-header border-bottom d-flex flex-wrap align-items-center justify-content-between gap-2">
          <h5 class="card-title mb-0"><i class="icon-base ri ri-calendar-todo-line me-2"></i>Histórico de plazos</h5>
          <button type="button" class="btn btn-sm btn-primary d-none" id="btnAnadirPlazoVenta">
            <i class="icon-base ri ri-add-line me-1"></i>Añadir plazo
          </button>
        </div>
        <div class="card-body p-0">
          <div class="card-datatable table-responsive">
            <table class="table table-hover mb-3 datatables-ventas-plazos-ficha" id="tabla_ventas_plazos_ficha" style="width: 100%;">
              <thead>
                <tr>
                  <th style="width:15px"></th>
                  <th>id</th>
                  <th>fecha_creado</th>
                  <th>importe</th>
                  <th>fecha_cobrado</th>
                  <th>fecha_vencido</th>
                  <th>fecha_vencimiento</th>
                  <th>estado</th>
                  <th>metodo_pago</th>
                  <th>comprobante</th>
                  <th>Acciones</th>
                </tr>
              </thead>
              <tbody></tbody>
            </table>
          </div>
        </div>
      </div>
      <?php include __DIR__ . '/modal_cobrar_plazo_venta.php'; ?>
      <?php include __DIR__ . '/modal_editar_plazo_venta.php'; ?>
    </div>
    <div class="tab-pane fade" id="navs-pills-venta-adelantos-capital" role="tabpanel">
      <div class="card mb-6">
        <div class="card-header border-bottom">
          <h5 class="card-title mb-0"><i class="icon-base ri ri-hand-coin-line me-2"></i>Adelantos de capital</h5>
        </div>
        <div class="card-body p-0">
          <div class="card-datatable table-responsive">
            <table class="table table-hover mb-3 datatables-adelantos-capital-venta-ficha" id="tabla_adelantos_capital_venta_ficha" style="width: 100%;">
              <thead>
                <tr>
                  <th>Adelanto Nº</th>
                  <th>Fecha de adelanto</th>
                  <th>Importe de adelanto</th>
                  <th>Capital anterior</th>
                  <th>Importe plazo antiguo</th>
                  <th>Nuevo capital</th>
                  <th>Nuevo importe de plazo</th>
                  <th>Forma de pago</th>
                  <th>Comprobante de pago</th>
                  <th>Comprobante cliente</th>
                </tr>
              </thead>
              <tbody></tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
    <?php endif; ?>
    <?php if (!empty($venta_permite_adelanto_capital)) : ?>
      <?php include __DIR__ . '/modal_adelanto_capital_venta.php'; ?>
    <?php endif; ?>
  </div>
<?php endif; ?>
</div>
<!-- / Content -->
