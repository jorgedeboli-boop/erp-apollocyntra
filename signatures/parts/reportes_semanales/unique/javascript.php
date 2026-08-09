<!-- JAVASCRIPT CUSTOM reportes_semanales - unique  -->
<?php
$vTablesDatatablesLoad = filemtime(__DIR__ . '/tables-datatables-load.js');
?>
<?php
$vFiltrosListar = filemtime(__DIR__ . '/../../universal/filtros-listar.js');
?>
<script>
window.ListarFiltrosConfig = {
  filterIds: ['filtro_empresa', 'filtro_sucursal', 'filtro_anio', 'filtro_semana'],
  containerId: 'collapse_filtros_reportes_semanales',
  readyClass: 'reportes-semanales-filtros-ready',
  initMarkerId: 'filtro_empresa',
  select2OptionsById: {
    filtro_empresa: { allowClear: true },
    filtro_sucursal: { allowClear: true }
  }
};
</script>
<script src="parts/universal/filtros-listar.js?v=<?php echo $vFiltrosListar; ?>"></script>
<script src="parts/reportes_semanales/unique/tables-datatables-load.js?v=<?php echo $vTablesDatatablesLoad; ?>"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
  var card = document.querySelector('.datatables-reportes-semanales');
  card = card ? card.closest('.card-action') : null;
  if (!card) {
    return;
  }

  var ajustarTablaFullscreen = function () {
    if (window.dt_reportes_semanales && typeof window.dt_reportes_semanales.columns === 'function') {
      window.dt_reportes_semanales.columns.adjust();
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
