<!-- JAVASCRIPT CUSTOM reportes_globales - unique  -->
<?php
$vTablesDatatablesLoad = filemtime(__DIR__ . '/tables-datatables-load.js');
$vFiltrosListar = filemtime(__DIR__ . '/../../universal/filtros-listar.js');
$vFlatpickrDatatable = filemtime(__DIR__ . '/../../universal/flatpickr-datatable.js');
$vPedirColumnasExportacion = filemtime(__DIR__ . '/../../universal/pedir-columnas-exportacion.js');
$vLoadStats = filemtime(__DIR__ . '/load_stats.js');
?>
<script>
window.ListarFiltrosConfig = {
  filterIds: ['filtro_empresa', 'filtro_sucursal'],
  containerId: 'collapse_filtros_reportes_globales',
  readyClass: 'reportes-globales-filtros-ready',
  initMarkerId: 'filtro_empresa',
  select2OptionsById: {
    filtro_empresa: { allowClear: true },
    filtro_sucursal: { allowClear: true }
  }
};
</script>
<script src="parts/universal/pedir-columnas-exportacion.js?v=<?php echo $vPedirColumnasExportacion; ?>"></script>
<script src="parts/universal/filtros-listar.js?v=<?php echo $vFiltrosListar; ?>"></script>
<script src="parts/universal/flatpickr-datatable.js?v=<?php echo $vFlatpickrDatatable; ?>"></script>
<script src="parts/reportes_globales/unique/load_stats.js?v=<?php echo $vLoadStats; ?>"></script>
<script src="parts/reportes_globales/unique/tables-datatables-load.js?v=<?php echo $vTablesDatatablesLoad; ?>"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
  var card = document.querySelector('.datatables-reportes-globales');
  card = card ? card.closest('.card-action') : null;
  if (!card) {
    return;
  }

  var ajustarTablaFullscreen = function () {
    if (window.dt_reportes_globales && typeof window.dt_reportes_globales.columns === 'function') {
      window.dt_reportes_globales.columns.adjust();
    }
  };

  var expandBtn = card.querySelector('.card-expand');
  var expandIcon = expandBtn ? expandBtn.querySelector('i') : null;

  var syncExpandIcon = function () {
    if (!expandIcon) {
      return;
    }
    expandIcon.classList.remove('ri-fullscreen-line', 'ri-fullscreen-fill', 'ri-fullscreen-exit-line');
    if (card.classList.contains('card-fullscreen')) {
      expandIcon.classList.add('ri-fullscreen-exit-line');
    } else {
      expandIcon.classList.add('ri-fullscreen-fill');
    }
  };

  if (expandBtn) {
    expandBtn.addEventListener('click', function () {
      window.setTimeout(function () {
        syncExpandIcon();
        ajustarTablaFullscreen();
      }, 0);
    });
  }

  document.addEventListener('keyup', function (event) {
    if (event.key === 'Escape') {
      window.setTimeout(syncExpandIcon, 0);
    }
  });

  window.addEventListener('resize', function () {
    if (card.classList.contains('card-fullscreen')) {
      ajustarTablaFullscreen();
    }
  });
});
</script>
