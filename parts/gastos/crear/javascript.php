<!-- JAVASCRIPT CUSTOM GASTOS CREAR -->
<script>
document.addEventListener('DOMContentLoaded', function () {
  // Select2
  $('#empresa_gasto, #sucursal_gasto, #proveedor_gasto, #forma_pago_gasto, #tipo_de_gasto, #estado_gasto, #tipo_iva').each(function () {
    const $this = $(this);
    if ($this.length) $this.select2({ dropdownParent: $this.parent() });
  });

  const form = document.getElementById('formCrearGasto');
  const extraPagoContainer = document.getElementById('extra_forma_pago_container');
  const fechaPagoContainer = document.getElementById('container_fecha_pago_gasto');

  const toNum = (v) => {
    const n = parseFloat(String(v ?? '').replace(',', '.'));
    return Number.isFinite(n) ? n : 0;
  };

  const setNowDatetimeLocal = (input) => {
    if (!input || input.value) return;
    const d = new Date();
    const pad = (x) => String(x).padStart(2, '0');
    const val = `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
    input.value = val;
  };

  const refreshExtraPago = () => {
    const idForma = document.getElementById('forma_pago_gasto')?.value;
    // legacy: 223 o 224
    const show = idForma === '223' || idForma === '224';
    if (extraPagoContainer) extraPagoContainer.classList.toggle('d-none', !show);
  };

  const refreshFechaPago = () => {
    const estado = document.getElementById('estado_gasto')?.value;
    const show = estado === 'pagado';
    if (fechaPagoContainer) fechaPagoContainer.classList.toggle('d-none', !show);
    if (show) setNowDatetimeLocal(document.getElementById('fecha_pago_gasto'));
  };

  const recalcularTotales = () => {
    const base = toNum(document.getElementById('base_impobile')?.value);
    const tipoIva = toNum(document.getElementById('tipo_iva')?.value);
    const irpf = toNum(document.getElementById('irpf')?.value);
    const iva = +(base * (tipoIva / 100)).toFixed(2);
    const total = +(base + iva - irpf).toFixed(2);

    const inIva = document.getElementById('iva_total');
    const inTotal = document.getElementById('total_gasto');
    if (inIva) inIva.value = iva.toFixed(2);
    if (inTotal) inTotal.value = total.toFixed(2);
  };

  $(document).on('change', '#forma_pago_gasto', refreshExtraPago);
  $(document).on('change', '#estado_gasto', refreshFechaPago);
  $(document).on('input change', '#base_impobile, #tipo_iva, #irpf', recalcularTotales);

  refreshExtraPago();
  refreshFechaPago();
  recalcularTotales();

  // Empresa -> cargar sucursales (filtradas por empresa) (mismo patrón que gastos_fijos)
  const cargarSucursales = async (empresaId, selectedId) => {
    const select = document.getElementById('sucursal_gasto');
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

  $('#empresa_gasto').on('change', function () {
    const empresaId = this.value;
    if (empresaId) cargarSucursales(empresaId);
  });

  // Sucursal -> autoseleccionar empresa
  $(document).on('change', '#sucursal_gasto', async function () {
    const sucursalId = this.value;
    if (!sucursalId || sucursalId === 'no_es_sucursal') return;
    try {
      const url = new URL('parts/gastos_fijos/crear/get_empresa_por_sucursal.php', window.location.origin);
      url.searchParams.set('sucursal_id', sucursalId);
      const resp = await fetch(url.toString());
      const data = await resp.json();
      if (data.success && data.empresa_id) {
        $('#empresa_gasto').val(String(data.empresa_id)).trigger('change.select2');
      }
    } catch (e) {
      console.error(e);
    }
  });

  // Submit por AJAX
  if (form) {
    form.addEventListener('submit', async function (e) {
      e.preventDefault();
      const fd = new FormData(form);
      try {
        const resp = await fetch(form.action, { method: 'POST', body: fd });
        const data = await resp.json();
        if (!data.success) {
          alert(data.message || data.error || 'Error al guardar');
          return;
        }
        window.location.href = data.redirect || 'gastos.php';
      } catch (err) {
        console.error(err);
        alert('Error de conexión');
      }
    });
  }
});
</script>