<?php
  if (!isset($semana_numero) || !isset($anyo_semana)) {
    $semana_datos = obtener_numero_semana_menos_3();
    $semana_numero = (is_array($semana_datos) && !empty($semana_datos['numero_semana']))
        ? (int) $semana_datos['numero_semana']
        : 0;
    $anyo_semana = (is_array($semana_datos) && !empty($semana_datos['anyo_listado']))
        ? (int) $semana_datos['anyo_listado']
        : 0;
  }
?>
<input type="hidden" id="semana_recepcion" name="semana_recepcion_form" value="<?php echo (int) $semana_numero; ?>">
<input type="hidden" id="anyo_semana_recepcion" name="anyo_semana_recepcion_form" value="<?php echo (int) $anyo_semana; ?>">
<input type="hidden" id="todas_semanas_recepcion" name="todas_semanas_recepcion_form" value="0">

<div
  class="offcanvas offcanvas-start"
  tabindex="-1"
  id="offcanvas_semana_recepcion"
  aria-labelledby="offcanvasSemanaRecepcionLabel"
  data-bs-theme="system"
>
  <div class="offcanvas-body p-0 d-flex flex-column">
    <div id="listado_recepcion_semanas" class="listado-recepcion-semanas-scroll flex-grow-1 overflow-auto px-3 pt-7 mt-6">
      <div class="text-center text-muted py-4">Cargando semanas…</div>
    </div>
  </div>
</div>
