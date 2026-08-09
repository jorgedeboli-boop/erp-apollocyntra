<!-- JAVASCRIPT CUSTOM GASTOS FIJOS CREAR -->
<script>
document.addEventListener('DOMContentLoaded', function () {
  // Select2
  $('#empresa_gasto_fijo, #sucursal_gasto_fijo, #proveedor_gasto_fijo, #forma_pago_gasto_fijo, #tipo_de_gasto_fijo, #periodo_gasto_fijo').each(function () {
    const $this = $(this);
    if ($this.length) $this.select2({ dropdownParent: $this.parent() });
  });

  const extraPagoContainer = document.getElementById('extra_forma_pago_container');
  const form = document.getElementById('formCrearGastoFijo');

  const refreshExtraPago = () => {
    const idForma = document.getElementById('forma_pago_gasto_fijo')?.value;
    // legacy: 223 o 224
    const show = idForma === '223' || idForma === '224';
    if (extraPagoContainer) {
      extraPagoContainer.classList.toggle('d-none', !show);
    }
  };

  $(document).on('change', '#forma_pago_gasto_fijo', refreshExtraPago);
  refreshExtraPago();

  // Empresa -> cargar sucursales (filtradas por empresa)
  const cargarSucursales = async (empresaId, selectedId) => {
    const select = document.getElementById('sucursal_gasto_fijo');
    if (!select) return;

    select.innerHTML = `<option value="">Cargando...</option>`;
    try {
      const url = new URL('parts/gastos_fijos/crear/get_sucursales_por_empresa.php', window.location.origin);
      if (empresaId) url.searchParams.set('empresa_id', empresaId);
      const resp = await fetch(url.toString());
      const data = await resp.json();
      if (!data.success) throw new Error(data.message || 'Error cargando sucursales');

      select.innerHTML = `<option value="">Seleccionar...</option>
                          <option value="no_es_sucursal">No es sucursal</option>`;
      data.sucursales.forEach(s => {
        const opt = document.createElement('option');
        opt.value = String(s.id_sucursal);
        opt.textContent = s.nombre_sucursal;
        if (selectedId && String(selectedId) === String(s.id_sucursal)) opt.selected = true;
        select.appendChild(opt);
      });

      // refrescar select2
      const $sel = $(select);
      if ($sel.data('select2')) {
        $sel.trigger('change.select2');
      } else {
        $sel.select2({ dropdownParent: $sel.parent() });
      }
    } catch (e) {
      select.innerHTML = `<option value="">Error al cargar</option>`;
      console.error(e);
    }
  };

  $('#empresa_gasto_fijo').on('change', function () {
    const empresaId = this.value;
    if (empresaId) cargarSucursales(empresaId);
  });

  // Sucursal -> autoseleccionar empresa
  $(document).on('change', '#sucursal_gasto_fijo', async function () {
    const sucursalId = this.value;
    if (!sucursalId || sucursalId === 'no_es_sucursal') return;
    try {
      const url = new URL('parts/gastos_fijos/crear/get_empresa_por_sucursal.php', window.location.origin);
      url.searchParams.set('sucursal_id', sucursalId);
      const resp = await fetch(url.toString());
      const data = await resp.json();
      if (data.success && data.empresa_id) {
        $('#empresa_gasto_fijo').val(String(data.empresa_id)).trigger('change.select2');
      }
    } catch (e) {
      console.error(e);
    }
  });

  // Submit por AJAX (mismo patrón moderno: sin recargar si OK)
  if (form) {
    form.addEventListener('submit', async function (e) {
      e.preventDefault();
      const fd = new FormData(form);
      try {
        const resp = await fetch(form.action, { method: 'POST', body: fd });
        const data = await resp.json();
        if (!data.success) {
          alert(data.message || 'Error al guardar');
          return;
        }
        window.location.href = data.redirect || 'gastos_fijos.php?categoria=gastos&page=gastos_fijos&btn=list';
      } catch (err) {
        console.error(err);
        alert('Error de conexión');
      }
    });
  }
});
</script>