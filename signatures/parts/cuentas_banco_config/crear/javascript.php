<script>
document.addEventListener('DOMContentLoaded', function () {
  $('.select2').each(function () {
    var $this = $(this);
    $this.select2({ dropdownParent: $this.parent(), width: '100%', allowClear: true, placeholder: $this.find('option:first').text() });
  });

  function cargarSucursales(empresaId) {
    var select = document.getElementById('sucursal_cuenta_id');
    if (!select) return;

    select.innerHTML = '<option value="">Sin sucursal (opcional)</option>';
    var $sel = $(select);
    if ($sel.data('select2')) {
      $sel.val('').trigger('change.select2');
    }

    if (!empresaId) return;

    var url = new URL('parts/gastos_fijos/crear/get_sucursales_por_empresa.php', window.location.origin);
    url.searchParams.set('empresa_id', empresaId);

    fetch(url.toString())
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if (!data.success) throw new Error(data.message || 'Error cargando sucursales');
        (data.sucursales || []).forEach(function (s) {
          var opt = document.createElement('option');
          opt.value = String(s.id_sucursal);
          opt.textContent = s.nombre_sucursal;
          select.appendChild(opt);
        });
        if ($sel.data('select2')) {
          $sel.trigger('change.select2');
        }
      })
      .catch(function (e) {
        console.error(e);
      });
  }

  $('#empresa_cuenta_id').on('change', function () {
    cargarSucursales(this.value);
  });
});
</script>
