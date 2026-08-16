<!-- JAVASCRIPT CUSTOM GASTOS FIJOS CREAR -->
<script>
document.addEventListener('DOMContentLoaded', function () {
  // Select2
  $('#empresa_gasto_fijo, #proveedor_gasto_fijo, #forma_pago_gasto_fijo, #tipo_de_gasto_fijo, #periodo_gasto_fijo').each(function () {
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