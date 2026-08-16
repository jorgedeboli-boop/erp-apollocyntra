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

$href_etiqueta_base = 'Impresiones/Articulos/etiquetas_articulos.php?id_articulo=' . $id_articulo_btn;
?>
<?php if ($puede_acceder_editar): ?>
<?php if (in_array($estado_articulo_btn, ['enventa', 'en_venta'], true)): ?>
<button type="button" class="btn btn-xs btn-primary waves-effect button-actions-datatable" style="border-radius: 7px !important;" onclick="enviarAVentaDesdeFichaArticulo(<?php echo $id_articulo_btn; ?>)">
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
