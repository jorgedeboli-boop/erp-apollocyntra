<script>
document.addEventListener('DOMContentLoaded', function () {
  $('.select2').each(function () {
    var $this = $(this);
    $this.select2({ dropdownParent: $this.parent(), width: '100%' });
  });

  var form = document.getElementById('formEditarCuenta');
  if (!form) return;

  form.addEventListener('submit', function (e) {
    e.preventDefault();
    if (!form.checkValidity()) {
      form.classList.add('was-validated');
      return;
    }
    var btn = document.getElementById('btnEditarCuenta');
    if (btn) {
      btn.disabled = true;
      btn.innerHTML = '<span class="spinner-border me-1"></span>Actualizando...';
    }
    fetch('parts/cuentas_banco_config/editar/procesar_editar_cuenta.php', {
      method: 'POST',
      body: new FormData(form)
    })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if (data.success) {
          Swal.fire({ icon: 'success', title: 'Actualizado', text: data.message, timer: 1200, showConfirmButton: false })
            .then(function () { window.location.href = data.redirect; });
          return;
        }
        Swal.fire('Error', data.error || 'No se pudo actualizar', 'error');
        if (btn) {
          btn.disabled = false;
          btn.innerHTML = '<i class="icon-base ri ri-check-line me-2"></i>Actualizar cuenta';
        }
      })
      .catch(function () {
        Swal.fire('Error', 'Error de conexión', 'error');
        if (btn) {
          btn.disabled = false;
          btn.innerHTML = '<i class="icon-base ri ri-check-line me-2"></i>Actualizar cuenta';
        }
      });
  });
});
</script>
