<!-- JAVASCRIPT CUSTOM GASTOS FIJOS MAIN -->
<script>
document.addEventListener('DOMContentLoaded', function () {
  // Select2
  $('#periodo_gasto_fijo, #sucursal_gasto_fijo, #proveedor_gasto_fijo, #forma_pago_gasto_fijo, #tipo_de_gasto_fijo').each(function () {
    const $this = $(this);
    if ($this.length) $this.select2({ dropdownParent: $this.parent() });
  });

  const btnToggle = document.getElementById('btnToggleEstado');
  const inputEstado = document.getElementById('estado_gasto_fijo');
  const idGasto = document.getElementById('id_gasto_fijo')?.value;
  const badgeEstado = document.getElementById('badge_estado_gasto_fijo');

  const setEstadoUI = (estado) => {
    if (!inputEstado) return;
    inputEstado.value = estado;
    const activo = estado === 'true';
    if (btnToggle) {
      btnToggle.classList.toggle('btn-success', activo);
      btnToggle.classList.toggle('btn-warning', !activo);
      const icon = btnToggle.querySelector('i');
      const text = btnToggle.querySelector('span');
      if (icon) {
        icon.classList.toggle('ri-checkbox-circle-line', activo);
        icon.classList.toggle('ri-close-circle-line', !activo);
      }
      if (text) {
        text.textContent = activo ? 'Activo' : 'Inactivo';
      } else {
        btnToggle.textContent = activo ? 'Activo' : 'Inactivo';
      }
    }
    if (badgeEstado) badgeEstado.textContent = activo ? 'Activo' : 'Inactivo';
  };

  // Toggle estado por AJAX
  if (btnToggle && idGasto) {
    btnToggle.addEventListener('click', async function () {
      const current = inputEstado?.value || 'false';
      const next = current === 'true' ? 'false' : 'true';
      const activar = next === 'true';
      const mensaje = activar
        ? '¿Estás seguro de que quieres activar este gasto fijo?'
        : '¿Estás seguro de que quieres desactivar este gasto fijo?';

      const confirm = await Swal.fire({
        title: 'Confirmar acción',
        text: mensaje,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: activar ? '#198754' : '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: activar ? 'Activar' : 'Desactivar',
        cancelButtonText: 'Cancelar'
      });
      if (!confirm.isConfirmed) return;

      btnToggle.disabled = true;
      try {
        const fd = new FormData();
        fd.append('id_gasto_fijo', idGasto);
        fd.append('estado_gasto_fijo', next);
        const resp = await fetch('parts/gastos_fijos/main/actualizar_estado.php', { method: 'POST', body: fd });
        const data = await resp.json();
        if (!data.success) {
          Swal.fire({
            title: 'Error',
            text: data.message || 'Error al actualizar estado',
            icon: 'error',
            confirmButtonText: 'Aceptar',
            confirmButtonColor: '#dc3545'
          });
        } else {
          setEstadoUI(next);
          Swal.fire({
            title: '¡Estado actualizado!',
            text: data.message || 'El estado del gasto fijo se ha actualizado correctamente',
            icon: 'success',
            confirmButtonText: 'Aceptar',
            confirmButtonColor: '#198754',
            timer: 3000,
            timerProgressBar: true
          });
        }
      } catch (e) {
        console.error(e);
        Swal.fire({
          title: 'Error',
          text: 'No se pudo cambiar el estado: error de conexión',
          icon: 'error',
          confirmButtonText: 'Aceptar',
          confirmButtonColor: '#dc3545'
        });
      } finally {
        btnToggle.disabled = false;
      }
    });
  }

  // Guardar cambios por AJAX
  const form = document.getElementById('formEditarGastoFijo');
  if (form) {
    form.addEventListener('submit', async function (e) {
      e.preventDefault();
      const fd = new FormData(form);
      try {
        const resp = await fetch(form.action, { method: 'POST', body: fd });
        const data = await resp.json();
        if (!data.success) {
          alert(data.message || 'Error al actualizar');
          return;
        }
        // feedback mínimo
        alert('Actualizado correctamente');
      } catch (err) {
        console.error(err);
        alert('Error de conexión');
      }
    });
  }
});
</script>