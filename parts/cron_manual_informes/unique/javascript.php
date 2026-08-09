<!-- JAVASCRIPT CUSTOM cron_manual_informes - unique  -->
<script>
(function () {
  'use strict';

  var inputFecha = document.getElementById('cron_manual_fecha');
  if (!inputFecha) {
    return;
  }

  if (!inputFecha.value) {
    var hoy = new Date();
    var y = hoy.getFullYear();
    var m = String(hoy.getMonth() + 1).padStart(2, '0');
    var d = String(hoy.getDate()).padStart(2, '0');
    inputFecha.value = y + '-' + m + '-' + d;
  }

  document.querySelectorAll('[data-cron-manual]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var tipo = btn.getAttribute('data-cron-manual');
      var fecha = (inputFecha.value || '').trim();
      if (!/^\d{4}-\d{2}-\d{2}$/.test(fecha)) {
        window.alert('Indica una fecha válida (YYYY-MM-DD).');
        return;
      }

      var archivo = 'informe_diario.php';
      if (tipo === 'semanal') {
        archivo = 'informe_semanal.php';
      } else if (tipo === 'mensual') {
        archivo = 'informe_mensual.php';
      }

      var url = 'parts/cron_manual_informes/' + archivo + '?fecha=' + encodeURIComponent(fecha);
      window.open(url, '_blank', 'noopener,noreferrer');
    });
  });
})();
</script>
