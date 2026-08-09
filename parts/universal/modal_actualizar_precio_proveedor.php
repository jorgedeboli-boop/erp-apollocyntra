<?php
$proveedores_modal_precio_oro = function_exists('obtener_proveedores_fundicion_modal_precio_oro')
    ? obtener_proveedores_fundicion_modal_precio_oro()
    : [];
?>

<div class="modal fade" id="modalActualizarPrecioProveedor" tabindex="-1" aria-labelledby="modalActualizarPrecioProveedorLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-sm">
    <div class="modal-content">
      <div class="modal-header card-header border-bottom card-header-forms pb-4">
        <h4 class="card-title mb-0" id="modalActualizarPrecioProveedorLabel" style="font-size: 20px;">Actualizar precio proveedor</h4>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>

      <div class="modal-body" style="padding: 15px 24px 18px;">
        <?php if (empty($proveedores_modal_precio_oro)) : ?>
          <p class="text-body-secondary mb-0">No hay proveedores de fundición configurados.</p>
        <?php else : ?>
          <form id="formActualizarPrecioProveedor" method="post" action="javascript:void(0);" autocomplete="off" class="needs-validation">
            <?php foreach ($proveedores_modal_precio_oro as $provModal) : ?>
              <div class="mb-4 proveedor-precio-oro-row border-bottom pb-3">
                <p class="fw-medium mb-3"><?php echo htmlspecialchars($provModal['nombre_proveedor'], ENT_QUOTES, 'UTF-8'); ?></p>
                <div class="row g-3">
                  <div class="col-sm-6">
                    <label class="form-label" for="precio_24k_<?php echo (int) $provModal['id_proveedor']; ?>">Precio 24kl *</label>
                    <input
                      type="text"
                      class="form-control"
                      id="precio_24k_<?php echo (int) $provModal['id_proveedor']; ?>"
                      name="precio_24k[<?php echo (int) $provModal['id_proveedor']; ?>]"
                      value="<?php echo htmlspecialchars($provModal['precio_fmt'], ENT_QUOTES, 'UTF-8'); ?>"
                      required
                      inputmode="decimal"
                      autocomplete="off"
                      placeholder="0,00">
                  </div>
                  <div class="col-sm-6">
                    <label class="form-label" for="fecha_standby_<?php echo (int) $provModal['id_proveedor']; ?>">Fecha standby</label>
                    <input
                      type="text"
                      class="form-control date-mask"
                      id="fecha_standby_<?php echo (int) $provModal['id_proveedor']; ?>"
                      name="fecha_standby[<?php echo (int) $provModal['id_proveedor']; ?>]"
                      value="<?php echo htmlspecialchars($provModal['fecha_standby_fmt'], ENT_QUOTES, 'UTF-8'); ?>"
                      autocomplete="off"
                      placeholder="DD/MM/YYYY"
                      inputmode="numeric">
                  </div>
                </div>
              </div>
            <?php endforeach; ?>

            <div class="d-grid pt-2">
              <button type="submit" class="btn btn-primary" id="btnActualizarPrecioProveedor">Actualizar</button>
            </div>
          </form>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
