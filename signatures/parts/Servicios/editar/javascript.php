<script>
document.addEventListener('DOMContentLoaded', function () {
  if ($('#rel_id_empresa').length) {
    $('#rel_id_empresa').select2({
      dropdownParent: $('#rel_id_empresa').closest('.form-floating'),
      placeholder: 'Seleccionar...',
      width: '100%',
    });
  }
  ['#id_categoria', '#unidad_tiempo', '#tipo_facturacion'].forEach(function (sel) {
    if ($(sel).length) {
      $(sel).select2({
        dropdownParent: $(sel).closest('.form-floating'),
        width: '100%',
      });
    }
  });

  function irLista() {
    window.location.href = 'servicios.php';
  }
  function irFicha() {
    var id = document.querySelector('input[name="id_servicio"]');
    if (id && id.value) {
      window.location.href = 'servicio.php?id=' + id.value;
    } else {
      irLista();
    }
  }

  document.getElementById('btn_cancelar_edit_servicio')?.addEventListener('click', function (e) {
    e.preventDefault();
    Swal.fire({
      icon: 'warning',
      title: '¿Descartar cambios?',
      showCancelButton: true,
      confirmButtonText: 'Sí',
      cancelButtonText: 'No',
      reverseButtons: true,
    }).then(function (r) {
      if (r.isConfirmed) irFicha();
    });
  });

  document.getElementById('btn_volver_servicios_edit')?.addEventListener('click', function (e) {
    e.preventDefault();
    document.getElementById('btn_cancelar_edit_servicio')?.click();
  });

  var form = document.getElementById('formEditarServicio');
  if (form) {
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      var fd = new FormData(form);
      if (!document.getElementById('activo').checked) {
        fd.delete('activo');
      } else {
        fd.set('activo', '1');
      }
      fetch('parts/servicios/editar/actualizar_servicio.php', { method: 'POST', body: fd })
        .then(function (r) {
          return r.json();
        })
        .then(function (data) {
          if (data.success) {
            Swal.fire({ icon: 'success', title: 'Guardado', text: data.message || 'Actualizado' }).then(function () {
              irFicha();
            });
          } else {
            Swal.fire({ icon: 'error', title: 'Error', text: data.error || 'No se pudo guardar' });
          }
        })
        .catch(function () {
          Swal.fire({ icon: 'error', title: 'Error', text: 'Error de red' });
        });
    });
  }
});
</script>
