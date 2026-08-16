<?php
/**
 * Formulario compartido crear / editar presupuesto.
 * Variables esperadas: $titulo_card, $nombre_sucursal, $id_sucursal, $id_empresa_rel, $fecha_val_def,
 * $es_edicion (bool), $id_presupuesto (int), $val (array campos), $id_articulo_pre (int opcional),
 * $numero_presupuesto (string opcional), $bootstrap_edicion_json (string JSON opcional para líneas).
 */
if (!isset($val) || !is_array($val)) {
    $val = [];
}
$v = function ($key, $default = '') use ($val) {
    return isset($val[$key]) ? $val[$key] : $default;
};
$es_edicion = !empty($es_edicion);
$id_presupuesto = isset($id_presupuesto) ? (int)$id_presupuesto : 0;
$numero_presupuesto = isset($numero_presupuesto) ? (string)$numero_presupuesto : '';
$bootstrap_edicion_json = isset($bootstrap_edicion_json) ? $bootstrap_edicion_json : '';
if (!isset($empresa) || !is_array($empresa)) {
    $empresa = function_exists('obtener_datos_empresa_sesion') ? (obtener_datos_empresa_sesion() ?: []) : [];
}
$empTxt = function ($k, $d = '-') use ($empresa) {
    $v = trim((string) ($empresa[$k] ?? ''));
    return $v !== '' ? $v : $d;
};
?>
<!-- Content -->
<div class="container-fluid flex-grow-1 container-p-y">

<div class="row invoice-preview">

  <div class="col-xl-9 col-md-8 col-12 mb-md-0 mb-6">
    <div class="card invoice-preview-card">

      <div class="card-header border-bottom card-header-forms titulos-cards-pages pb-3">
          <h5 class="card-title mb-0"><?php echo htmlspecialchars($titulo_card); ?></h5>
          <h4 class="text-muted p-0 m-0" style="line-height: 28px;"><span id="nombre_sucursal"><?php echo htmlspecialchars($nombre_sucursal); ?></span></h4>
          <?php if ($es_edicion && $numero_presupuesto !== ''): ?>
          <p class="mb-0 text-body-secondary small"><?php echo htmlspecialchars($numero_presupuesto); ?></p>
          <?php endif; ?>
          <button type="button" id="btn_volver_presupuestos" class="btn btn-text-primary btn-header-card-right mt-4">
            <i class="icon-base ri ri-arrow-left-s-line me-2"></i>Presupuestos
          </button>
      </div>

      <div class="card-body p-5">

        <input type="hidden" id="sucursal_venta" name="sucursal_venta" value="<?php echo (int)$id_sucursal; ?>" />
        <input type="hidden" id="presupuesto_rel_id_empresa" value="<?php echo (int)$id_empresa_rel; ?>" />
        <?php if ($es_edicion && $id_presupuesto > 0): ?>
        <input type="hidden" id="id_presupuesto_edicion" value="<?php echo (int)$id_presupuesto; ?>" />
        <?php endif; ?>

        <?php if (!empty($id_articulo_pre)): ?>
        <input type="hidden" id="articulo_venta" name="articulo_venta" value="<?php echo (int)$id_articulo_pre; ?>" />
        <?php endif; ?>

        <form id="form_insert_venta" style="display: none;">
         <input type="hidden" id="insert_id_sucursal" name="id_sucursal" value="<?php echo (int)$id_sucursal; ?>" />
         <input type="hidden" value="" id="codigo_autorizacion_correcto">
         <input type="hidden" value="" id="id_autorizacion" name="id_autorizacion">
         <input type="hidden" name="precio_inicial" id="precio_inicial" value="">
         <input type="hidden" name="porcentaje_venta_plazos" id="porcentaje_venta_plazos" value="">
         <input type="hidden" name="interes" value="" id="interes_valor">
         <input type="hidden" id="insert_id_cliente" name="id_cliente" value="<?php echo htmlspecialchars($v('id_cliente')); ?>" />
         <input type="hidden" id="insert_tipo_identificacion" name="tipo_identificacion" value="<?php echo htmlspecialchars($v('insert_tipo_identificacion')); ?>" />
         <input type="hidden" id="insert_identificacion" name="identificacion" value="<?php echo htmlspecialchars($v('insert_identificacion')); ?>" />
         <input type="hidden" id="insert_nombre" name="nombre" value="<?php echo htmlspecialchars($v('insert_nombre')); ?>" />
         <input type="hidden" id="insert_apellido" name="apellido" value="<?php echo htmlspecialchars($v('insert_apellido')); ?>" />
         <input type="hidden" id="insert_telefono" name="telefono" value="<?php echo htmlspecialchars($v('insert_telefono')); ?>" />
         <input type="hidden" id="insert_email" name="email" value="<?php echo htmlspecialchars($v('insert_email')); ?>" />
         <input type="hidden" id="insert_id_direccion" name="id_direccion" value="<?php echo htmlspecialchars($v('insert_id_direccion')); ?>" />
         <input type="hidden" id="insert_pais" name="pais" value="<?php echo htmlspecialchars($v('insert_pais')); ?>" />
         <input type="hidden" id="insert_provincia" name="provincia" value="<?php echo htmlspecialchars($v('insert_provincia')); ?>" />
         <input type="hidden" id="insert_poblacion" name="poblacion" value="<?php echo htmlspecialchars($v('insert_poblacion')); ?>" />
         <input type="hidden" id="insert_direccion" name="direccion" value="<?php echo htmlspecialchars($v('insert_direccion')); ?>" />
         <input type="hidden" id="insert_codigo_postal" name="codigo_postal" value="<?php echo htmlspecialchars($v('insert_codigo_postal')); ?>" />
         <input type="hidden" id="insert_articulos_skus" name="articulos_skus" value="" />
         <input type="hidden" id="insert_articulos_ids" name="articulos_ids" value="" />
       </form>

      <div id="cuerpo_venta" class="desenfocar">
      <div class="invoice-preview-header rounded-4 p-5 mb-2 position-relative">
        <div class="d-flex justify-content-between flex-xl-row flex-md-column flex-sm-row flex-column text-heading align-items-xl-center align-items-md-start align-items-sm-center flex-wrap gap-6">
          <div>
            <div class="d-flex gap-2 mb-2">
              <h5 class="mb-0" id="nombre_empresa"><?php echo htmlspecialchars($empTxt('nombre_empresa')); ?></h5>
            </div>
            <p class="mb-1 texto_direccion" id="cif_empresa"><?php echo $empTxt('cif_empresa') !== '-' ? 'CIF: ' . htmlspecialchars($empTxt('cif_empresa')) : '-'; ?></p>
            <p class="mb-1 texto_direccion" id="direccion_sucursal"><?php echo htmlspecialchars($empTxt('direccion_empresa')); ?></p>
            <p class="mb-1 texto_direccion" id="otrosdatos_sucursal"><span id="poblacion_sucursal"><?php echo htmlspecialchars($empTxt('poblacion_empresa')); ?></span> <span id="codigo_postal_sucursal"><?php echo htmlspecialchars($empTxt('codigo_postal_empresa')); ?></span></p>
            <p class="mb-1 texto_direccion" id="telefono_sucursal"><?php echo htmlspecialchars($empTxt('telefono_empresa')); ?></p>
            <p class="mb-1 texto_direccion" id="email_empresa"><?php echo htmlspecialchars($empTxt('email_empresa')); ?></p>
          </div>

          <div class="text-end align-items-end" id="skeleton-cliente" style="<?php echo $es_edicion && $v('id_cliente') ? 'display: none;' : ''; ?>">
            <div class="mb-2"><div class="skeleton skeleton-h5"></div></div>
            <div class="skeleton skeleton-line mb-1"></div>
            <div class="skeleton skeleton-line-short mb-1"></div>
            <div class="skeleton skeleton-line-medium mb-1"></div>
            <div class="skeleton skeleton-line-short mb-1"></div>
          </div>

          <div class="text-end align-items-end" id="datos-cliente" style="<?php echo $es_edicion && $v('id_cliente') ? 'display: block;' : 'display: none;'; ?>">
            <div class="mb-2">
              <h5 class="mb-0" id="nombre_cliente"><?php echo htmlspecialchars($v('nombre_cliente_cabecera', 'Cliente')); ?></h5>
            </div>
            <p class="mb-1 texto_direccion" id="dni_cliente"><span id="tipo_identificacion_cliente"><?php echo htmlspecialchars($v('tipo_identificacion_txt', 'NIF')); ?></span> <?php echo htmlspecialchars($v('insert_identificacion')); ?></p>
            <p class="mb-1 texto_direccion" id="direccion_cliente"><?php echo htmlspecialchars($v('direccion_cliente_cabecera', '—')); ?></p>
            <p class="mb-1 texto_direccion" id="otrosdatos_cliente"><span id="poblacion_cliente"><?php echo htmlspecialchars($v('poblacion_cliente_cabecera', '—')); ?></span> <span id="codigo_postal_cliente"><?php echo htmlspecialchars($v('codigo_postal_cliente_cabecera', '')); ?></span></p>
            <p class="mb-1 texto_direccion" id="telefono_cliente">Teléfono: <?php echo htmlspecialchars($v('insert_telefono', '—')); ?></p>
          </div>
        </div>

        <a href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#datos_cliente" class="btn btn-primary waves-effect waves-light" style="position: absolute; bottom: -1.2rem; right: 1rem;">
          <i class="icon-base ri ri-user-3-line icon-22px me-2"></i>Datos del cliente
        </a>
      </div>

      <div class="table-responsive border rounded-4 border-bottom-0 mt-10 mb-4">
        <table class="table m-0" id="tabla_articulos_venta">
          <thead>
            <tr>
              <th style="width: auto;">Descripción</th>
              <th style="width: 93px;" class="text-center">Uds.</th>
              <th style="width: 146px;" class="text-start">Precio unitario</th>
              <th style="width: 120px;" class="text-start">Precio</th>
              <th style="width: 70px;"> </th>
            </tr>
          </thead>
          <tbody id="articulos_venta_body">
            <tr>
              <td colspan="5" class="text-center text-muted py-6">
                No hay artículos agregados
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <div class="d-flex align-items-center">
        <input type="text" class="form-control input-sku-articulo" placeholder="SKU artículo o servicio" autocomplete="off" style="max-width: 280px; margin-left: 0;" />
      </div>

      <div class="table-responsive">
        <table class="table m-0 table-borderless">
          <tbody>
            <tr>
              <td class="align-top px-0 py-6">
                <div class="mb-1">
                  <span class="fw-medium text-heading">Usuario:</span>
                  <span id="vendedor_nombre_footer"><?php echo isset($_SESSION['usuario_nombre_completo']) ? htmlspecialchars($_SESSION['usuario_nombre_completo']) : '-'; ?></span>
                </div>
                <span class="text-muted">Presupuesto</span>
              </td>
              <td class="pe-0 py-6 w-px-100">
                <p class="mb-1">Subtotal:</p>
                <p class="mb-1 border-bottom pb-2" id="iva_venta_label">IVA:</p>
                <p class="mb-0 pt-2 fw-medium">Total:</p>
              </td>
              <td class="text-end px-0 py-6 w-px-100">
                <p class="fw-medium mb-1" id="subtotal_venta">0,00 €</p>
                <p class="fw-medium mb-1 border-bottom pb-2" id="iva_venta">0,00 €</p>
                <p class="fw-bold mb-0 pt-2 fs-5" id="total_venta">0,00 €</p>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <hr class="mt-0 mb-6" />

      <div class="mb-0">
        <label class="form-label fw-medium text-heading">Observaciones:</label>
        <textarea class="form-control" id="observaciones_venta" rows="3" placeholder="Observaciones internas del presupuesto..."><?php echo htmlspecialchars($v('observaciones_venta')); ?></textarea>
      </div>
      </div>

      </div>
    </div>
  </div>

  <div id="invoice_actions" class="col-xl-3 col-md-4 col-12 invoice-actions desenfocar">
    <div class="card">
      <div class="card-body">
        <h6 class="mb-4">Datos del presupuesto</h6>

        <div class="mb-3">
          <label class="form-label" for="presupuesto_titulo">Título *</label>
          <input type="text" class="form-control" id="presupuesto_titulo" maxlength="255" placeholder="Ej. Presupuesto reforma" required value="<?php echo htmlspecialchars($v('titulo')); ?>" />
        </div>
        <div class="mb-3">
          <label class="form-label" for="presupuesto_fecha_validez">Fecha validez</label>
          <input type="date" class="form-control" id="presupuesto_fecha_validez" value="<?php echo htmlspecialchars($v('fecha_validez', $fecha_val_def)); ?>" />
        </div>
        <div class="mb-3">
          <label class="form-label" for="presupuesto_descripcion">Descripción</label>
          <textarea class="form-control" id="presupuesto_descripcion" rows="2"><?php echo htmlspecialchars($v('descripcion')); ?></textarea>
        </div>
        <div class="mb-3">
          <label class="form-label" for="presupuesto_notas_cliente">Notas cliente</label>
          <textarea class="form-control" id="presupuesto_notas_cliente" rows="2"><?php echo htmlspecialchars($v('notas_cliente')); ?></textarea>
        </div>
        <div class="mb-3">
          <label class="form-label" for="presupuesto_notas_internas">Notas internas</label>
          <textarea class="form-control" id="presupuesto_notas_internas" rows="2"><?php echo htmlspecialchars($v('notas_internas')); ?></textarea>
        </div>
        <div class="mb-3">
          <label class="form-label" for="presupuesto_condiciones">Condiciones</label>
          <textarea class="form-control" id="presupuesto_condiciones" rows="2"><?php echo htmlspecialchars($v('condiciones')); ?></textarea>
        </div>
        <div class="mb-3">
          <label class="form-label" for="presupuesto_estado">Estado</label>
          <select class="form-select" id="presupuesto_estado">
            <?php
            $estados = ['borrador' => 'Borrador', 'enviado' => 'Enviado', 'aceptado' => 'Aceptado', 'rechazado' => 'Rechazado', 'caducado' => 'Caducado', 'facturado' => 'Facturado', 'cancelado' => 'Cancelado'];
            $estSel = $v('estado', 'borrador');
            foreach ($estados as $ev => $elab) {
                $sel = ($estSel === $ev) ? ' selected' : '';
                echo '<option value="' . htmlspecialchars($ev) . '"' . $sel . '>' . htmlspecialchars($elab) . '</option>';
            }
            ?>
          </select>
        </div>
        <div class="mb-4">
          <label class="form-label" for="presupuesto_porcentaje_iva">IVA (%)</label>
          <input type="number" step="0.01" class="form-control" id="presupuesto_porcentaje_iva" value="<?php echo htmlspecialchars($v('porcentaje_iva', '21')); ?>" />
        </div>

        <hr class="my-4" />

        <button type="button" class="btn btn-primary d-grid w-100 mb-3" id="btn_guardar_presupuesto">
          <span class="d-flex align-items-center justify-content-center text-nowrap">
            <i class="icon-base ri ri-save-line icon-16px me-2"></i><?php echo $es_edicion ? 'Actualizar presupuesto' : 'Guardar presupuesto'; ?>
          </span>
        </button>

        <?php if ($es_edicion && $id_presupuesto > 0): ?>
        <a href="documents/presupuesto_invoice.php?id=<?php echo (int)$id_presupuesto; ?>" target="_blank" rel="noopener" class="btn btn-outline-secondary d-grid w-100 mb-2" id="btn_imprimir_presupuesto">
          <span class="d-flex align-items-center justify-content-center text-nowrap">
            <i class="icon-base ri ri-printer-line icon-16px me-2"></i>Imprimir
          </span>
        </a>
        <button type="button" class="btn btn-outline-primary d-grid w-100 mb-3" id="btn_abrir_modal_email_presupuesto" data-bs-toggle="modal" data-bs-target="#modal_email_presupuesto">
          <span class="d-flex align-items-center justify-content-center text-nowrap">
            <i class="icon-base ri ri-mail-send-line icon-16px me-2"></i>Enviar por email
          </span>
        </button>
        <?php endif; ?>

        <a href="presupuestos.php" class="btn btn-outline-danger d-grid w-100" id="btn_cancelar_presupuesto">
          <span class="d-flex align-items-center justify-content-center text-nowrap">
            <i class="icon-base ri ri-close-line icon-16px me-2"></i>Cancelar
          </span>
        </a>
      </div>
    </div>

    <div class="card mt-4">
      <div class="card-body">
        <h6 class="mb-4">Resumen</h6>
        <div class="d-flex justify-content-between mb-2">
          <span class="text-muted">Artículos / líneas:</span>
          <span class="fw-medium" id="total_articulos_resumen">0</span>
        </div>
        <hr class="my-3" />
        <div class="d-flex justify-content-between">
          <span class="fw-medium">Total:</span>
          <span class="fw-bold fs-5" id="total_resumen">0,00 €</span>
        </div>
      </div>
    </div>
  </div>

</div>
</div>

<!-- Modal cliente -->
<div class="modal fade" id="datos_cliente" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-simple modal-edit-user">
    <div class="modal-content">
      <div class="modal-body p-0">
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        <div class="text-center mb-6">
          <h4 class="mb-2">Datos del cliente</h4>
          <p class="mb-6">Escribe el documento y se buscará, o créalo nuevo desde aquí.</p>
        </div>
        <form id="form_datos_cliente" class="row g-5" onsubmit="return false" autocomplete="off">
          <input type="hidden" id="modal_id_cliente" name="id_cliente" value="<?php echo htmlspecialchars($v('id_cliente')); ?>" />
          <input type="hidden" id="modal_id_direccion" name="id_direccion" value="<?php echo htmlspecialchars($v('insert_id_direccion')); ?>" />
          <div class="col-md-6">
            <div class="form-floating form-floating-outline mb-4">
              <select class="form-select select2" id="modal_tipo_identificacion" name="tipo_identificacion" required autocomplete="off">
                <option value="">Seleccionar...</option>
              </select>
              <label for="modal_tipo_identificacion">Tipo de Identificación *</label>
            </div>
            <div class="form-floating form-floating-outline mb-4">
              <input type="text" class="form-control" id="modal_identificacion" name="identificacion" placeholder="Número" required autocomplete="off" value="<?php echo htmlspecialchars($v('insert_identificacion')); ?>" />
              <label for="modal_identificacion">Número de Identificación *</label>
            </div>
            <div class="mb-4 form-floating form-floating-outline">
              <input type="text" class="form-control" id="modal_nombre" name="nombre" placeholder="Nombre" required autocomplete="off" value="<?php echo htmlspecialchars($v('insert_nombre')); ?>" />
              <label for="modal_nombre" class="form-label">Nombre *</label>
            </div>
            <div class="form-floating form-floating-outline mb-4">
              <input type="text" class="form-control" id="modal_apellido" name="apellido" placeholder="Apellido" required autocomplete="off" value="<?php echo htmlspecialchars($v('insert_apellido')); ?>" />
              <label for="modal_apellido" class="form-label">Apellido *</label>
            </div>
            <div class="form-floating form-floating-outline mb-4">
              <input type="tel" class="form-control" id="modal_telefono" name="telefono" placeholder="Teléfono" required autocomplete="off" value="<?php echo htmlspecialchars($v('insert_telefono')); ?>" />
              <label for="modal_telefono">Teléfono *</label>
            </div>
            <div class="form-floating form-floating-outline mb-3">
              <input type="email" class="form-control" id="modal_email" name="email" placeholder="Email" autocomplete="off" value="<?php echo htmlspecialchars($v('insert_email')); ?>" />
              <label for="modal_email">Email</label>
            </div>
          </div>
          <div class="col-md-6">
            <div class="form-floating form-floating-outline mb-4">
              <select class="form-select select2" id="modal_pais" name="pais" autocomplete="off">
                <option value="">Seleccionar...</option>
              </select>
              <label for="modal_pais">País</label>
            </div>
            <div class="form-floating form-floating-outline mb-4">
              <select class="form-select select2" id="modal_c_provincia" name="c_provincia" autocomplete="off">
                <option value="">Seleccionar...</option>
              </select>
              <label for="modal_c_provincia">Provincia</label>
            </div>
            <div class="form-floating form-floating-outline mb-4">
              <select class="form-select select2" id="modal_c_poblacion" name="c_poblacion" autocomplete="off">
                <option value="">Seleccionar...</option>
              </select>
              <label for="modal_c_poblacion">Población</label>
            </div>
            <div class="form-floating form-floating-outline mb-4">
              <input type="text" class="form-control" id="modal_direccion" name="direccion" placeholder="Dirección" autocomplete="off" value="<?php echo htmlspecialchars($v('insert_direccion')); ?>" />
              <label for="modal_direccion">Dirección</label>
            </div>
            <div class="form-floating form-floating-outline mb-4">
              <input type="text" class="form-control" id="modal_codigo_postal" name="codigo_postal" placeholder="CP" autocomplete="off" value="<?php echo htmlspecialchars($v('insert_codigo_postal')); ?>" />
              <label for="modal_codigo_postal">Código Postal</label>
            </div>
          </div>
          <div class="col-12 text-center">
            <button type="submit" class="btn btn-primary me-3">Guardar Cliente</button>
            <button type="reset" class="btn btn-outline-secondary" data-bs-dismiss="modal" aria-label="Close">Cancelar</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<?php if (!empty($es_edicion) && !empty($id_presupuesto) && (int)$id_presupuesto > 0): ?>
<div class="modal fade" id="modal_email_presupuesto" tabindex="-1" aria-labelledby="modal_email_presupuesto_label" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modal_email_presupuesto_label">Enviar presupuesto por email</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <p class="text-muted small mb-3">Se adjuntará el presupuesto en formato PDF (mismo diseño que la vista de impresión).</p>
        <div class="form-floating form-floating-outline">
          <input type="email" class="form-control" id="email_envio_presupuesto" placeholder="correo@ejemplo.com" value="<?php echo htmlspecialchars($v('insert_email')); ?>" autocomplete="email" />
          <label for="email_envio_presupuesto">Correo electrónico</label>
        </div>
        <div id="email_presupuesto_mensaje" class="small mt-2"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-primary" id="btn_confirmar_email_presupuesto">
          <i class="icon-base ri ri-send-plane-line me-1"></i>Enviar
        </button>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<?php if ($bootstrap_edicion_json !== ''): ?>
<script>
window.PRESUPUESTO_EDICION_BOOTSTRAP = <?php echo $bootstrap_edicion_json; ?>;
</script>
<?php endif; ?>
