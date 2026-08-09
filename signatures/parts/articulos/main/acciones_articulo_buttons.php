<?php
/**
 * Botones de acción en la ficha del artículo (venta).
 * Requiere: $articulo (fila de articulos_venta).
 */
if (!isset($articulo) || !isset($articulo['id'])) {
    return;
}
$id_articulo_btn = (int) $articulo['id'];
$estado_articulo_btn = strtolower((string) ($articulo['estado'] ?? ''));
$estados_pasar_merma_btn = ['noetiquetado_c', 'noetiquetado_u', 'enventa', 'enviado', 'enreparacion'];
$estados_retirar_articulo_btn = ['noetiquetado_c', 'noetiquetado_u', 'enventa'];

$id_sucursal_actual_art = isset($articulo['id_sucursal_destino']) ? (int) $articulo['id_sucursal_destino'] : 0;
$nombre_sucursal_actual = isset($articulo['nombre_sucursal_destino']) && $articulo['nombre_sucursal_destino'] !== ''
    ? $articulo['nombre_sucursal_destino']
    : '—';

$href_etiqueta_base = 'Impresiones/Articulos/etiquetas_articulos.php?id_articulo=' . $id_articulo_btn;
?>
<?php if ($puede_acceder_editar): ?>
<?php if (in_array($estado_articulo_btn, ['enventa', 'en_venta'], true) && $id_sucursal_actual_art > 0): ?>
<button type="button" class="btn btn-xs btn-primary waves-effect button-actions-datatable" style="border-radius: 7px !important;" onclick="enviarAVentaDesdeFichaArticulo(<?php echo (int) $id_sucursal_actual_art; ?>, <?php echo $id_articulo_btn; ?>)">
  <i class="icon-base ri ri-money-euro-circle-line icon-20px me-1"></i>Vender artículo
</button>

<?php endif; ?>
<?php if (in_array($estado_articulo_btn, $estados_retirar_articulo_btn, true)): ?>
<button type="button" id="retirar_articulo" class="btn btn-xs btn-danger waves-effect waves-light button-actions-datatable" style="border-radius: 7px !important;" onclick="retirarArticulo(<?php echo $id_articulo_btn; ?>)">
  <i class="icon-base ri ri-logout-box-r-line icon-20px me-1"></i>Retirar artículo
</button>
<?php endif; ?>
<?php if (in_array($estado_articulo_btn, $estados_pasar_merma_btn, true)): ?>
<button type="button" class="btn btn-xs btn-warning waves-effect waves-light button-actions-datatable" style="border-radius: 7px !important;" onclick="pasarAMerma(<?php echo $id_articulo_btn; ?>)">
  <i class="icon-base ri ri-arrow-down-circle-line icon-20px me-1"></i>Pasar a merma
</button>
<?php endif; ?>
<?php if ($estado_articulo_btn === 'mermado'): ?>
<button type="button" class="btn btn-xs btn-danger waves-effect waves-light button-actions-datatable" style="border-radius: 7px !important;" onclick="eliminarArticuloVenta(<?php echo $id_articulo_btn; ?>)">
  <i class="icon-base ri ri-delete-bin-line icon-20px me-1"></i>Eliminar
</button>
<?php endif; ?>
<?php if ($estado_articulo_btn === 'vendido'): ?>
<button type="button" id="btn_get_autorization_devolucion" class="btn btn-xs btn-danger waves-effect waves-light button-actions-datatable" style="border-radius: 7px !important;" data-type-devolucion="normal">
  <i class="icon-base ri ri-time-line icon-20px me-1"></i>Solicitar autorización devolución
</button>
<?php elseif ($estado_articulo_btn === 'vendido_web'): ?>
<button type="button" id="btn_get_autorization_devolucion" class="btn btn-xs btn-danger waves-effect waves-light button-actions-datatable" style="border-radius: 7px !important;" data-type-devolucion="web">
  <i class="icon-base ri ri-time-line icon-20px me-1"></i>Solicitar autorización devolución web
</button>
<?php endif; ?>
<a href="editar_articulo.php?id=<?php echo $id_articulo_btn; ?>" class="btn btn-xs btn-primary waves-effect waves-light button-actions-datatable" style="border-radius: 7px !important;">
  <i class="icon-base ri ri-edit-line icon-20px me-1"></i>Editar
</a>
<?php endif; ?>
<?php if ($estado_articulo_btn === 'enventa'): ?>
  <?php if ($puede_acceder_editar): ?>
<button type="button" class="btn btn-xs btn-info waves-effect waves-light button-actions-datatable" style="border-radius: 7px !important;" data-bs-toggle="modal" data-bs-target="#modalTraspasarArticulo">
  <i class="icon-base ri ri-share-forward-line icon-20px me-1"></i>Traspasar artículo
</button>
<?php endif; ?>
<a title="Re-Imprimir etiqueta" class="btn btn-xs btn-secondary waves-effect waves-light button-actions-datatable" style="border-radius: 7px !important;" href="<?php echo htmlspecialchars($href_etiqueta_base); ?>&reimprimir=true" rel="noopener" data-etiqueta-action="reimprimir">
  <i class="icon-base ri ri-printer-line icon-20px me-1"></i>Re-Imprimir etiqueta
</a>

<?php elseif ($estado_articulo_btn === 'noetiquetado_c' || $estado_articulo_btn === 'noetiquetado_u'): ?>
<a title="Imprimir etiqueta" class="btn btn-xs btn-secondary waves-effect waves-light button-actions-datatable" style="border-radius: 7px !important;" href="<?php echo htmlspecialchars($href_etiqueta_base); ?>&individual=true" rel="noopener" data-etiqueta-action="imprimir">
  <i class="icon-base ri ri-printer-line icon-20px me-1"></i>Imprimir etiqueta
</a>
<?php else: ?>
<a title="Re-Imprimir etiqueta" class="btn btn-xs btn-secondary waves-effect waves-light button-actions-datatable" style="border-radius: 7px !important;" href="<?php echo htmlspecialchars($href_etiqueta_base); ?>&reimprimir=true" rel="noopener" data-etiqueta-action="reimprimir">
  <i class="icon-base ri ri-printer-line icon-20px me-1"></i>Re-Imprimir etiqueta
</a>
<?php endif; ?>

<?php if ($estado_articulo_btn === 'enventa'): ?>
<?php
$conexion_sel = conectar_bd();
$opts_suc = array();
if ($id_sucursal_actual_art > 0) {
    $st_sel = mysqli_prepare(
        $conexion_sel,
        'SELECT id_sucursal, nombre_sucursal FROM sucursal WHERE estado_tienda = \'habilitada\' AND id_sucursal <> ? ORDER BY nombre_sucursal ASC'
    );
    if ($st_sel) {
        mysqli_stmt_bind_param($st_sel, 'i', $id_sucursal_actual_art);
        mysqli_stmt_execute($st_sel);
        $rs_sel = mysqli_stmt_get_result($st_sel);
        while ($row_sel = mysqli_fetch_assoc($rs_sel)) {
            $opts_suc[] = $row_sel;
        }
        mysqli_stmt_close($st_sel);
    }
} else {
    $rs_sel = mysqli_query($conexion_sel, "SELECT id_sucursal, nombre_sucursal FROM sucursal WHERE estado_tienda = 'habilitada' ORDER BY nombre_sucursal ASC");
    if ($rs_sel) {
        while ($row_sel = mysqli_fetch_assoc($rs_sel)) {
            $opts_suc[] = $row_sel;
        }
    }
}
mysqli_close($conexion_sel);
?>
<div class="modal fade" id="modalTraspasarArticulo" tabindex="-1" aria-labelledby="modalTraspasarArticuloLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form id="traspasar_articulo_form" action="parts/articulos/main/insertar_traspaso_desde_articulo.php" method="post">
        <div class="modal-header">
          <h5 class="modal-title" id="modalTraspasarArticuloLabel">Traspasar artículo</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
          <div class="alert alert-danger mb-4" role="alert">
            <p class="mb-0 text-center fw-semibold">¿Traspasar artículo n.º <?php echo htmlspecialchars((string) $id_articulo_btn); ?> desde la sucursal <?php echo htmlspecialchars($nombre_sucursal_actual); ?>?</p>
          </div>
          <div class="mb-3">
            <label class="form-label" for="id_sucursal_traspaso">Sucursal destino</label>
            <select name="id_sucursal_traspaso" id="id_sucursal_traspaso" class="form-select select2 select2-custom" data-placeholder="Seleccionar sucursal…" required>
              <option value="">Seleccionar sucursal…</option>
              <?php foreach ($opts_suc as $opt): ?>
              <option value="<?php echo (int) $opt['id_sucursal']; ?>"><?php echo htmlspecialchars($opt['nombre_sucursal']); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <input type="hidden" name="id_articulo" value="<?php echo $id_articulo_btn; ?>">
          <input type="hidden" name="id_sucursal_destino" value="<?php echo (int) $id_sucursal_actual_art; ?>">
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-xs btn-label-secondary waves-effect" data-bs-dismiss="modal" style="border-radius: 7px !important;"><i class="icon-base ri ri-close-line icon-20px me-1"></i> Cerrar</button>
          <button type="submit" class="btn btn-xs btn-danger waves-effect waves-light" style="border-radius: 7px !important;"><i class="icon-base ri ri-check-line icon-20px me-1"></i> Sí, traspasar</button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php endif; ?>
