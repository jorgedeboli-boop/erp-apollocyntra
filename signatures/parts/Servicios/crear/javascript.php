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

  function irServicios() {
    window.location.href = 'servicios.php';
  }

  document.getElementById('btn_cancelar_servicio')?.addEventListener('click', function (e) {
    e.preventDefault();
    Swal.fire({
      icon: 'warning',
      title: '¿Cancelar?',
      text: 'Se perderán los datos no guardados',
      showCancelButton: true,
      confirmButtonText: 'Sí',
      cancelButtonText: 'No',
      reverseButtons: true,
    }).then((r) => {
      if (r.isConfirmed) irServicios();
    });
  });

  document.getElementById('btn_volver_servicios')?.addEventListener('click', function (e) {
    e.preventDefault();
    document.getElementById('btn_cancelar_servicio')?.click();
  });

  const form = document.getElementById('formCrearServicio');
  if (form) {
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      const fd = new FormData(form);
      if (!document.getElementById('activo').checked) {
        fd.delete('activo');
      } else {
        fd.set('activo', '1');
      }
      fetch('parts/servicios/crear/insertar_servicio.php', { method: 'POST', body: fd })
        .then((r) => r.json())
        .then((data) => {
          if (data.success) {
            Swal.fire({
              icon: 'success',
              title: 'Guardado',
              text: data.message || 'Servicio creado',
              confirmButtonText: 'Aceptar',
            }).then(() => {
              if (data.id_servicio) {
                window.location.href = 'servicio.php?id=' + data.id_servicio;
              } else {
                irServicios();
              }
            });
          } else {
            Swal.fire({ icon: 'error', title: 'Error', text: data.error || 'No se pudo crear' });
          }
        })
        .catch(() => {
          Swal.fire({ icon: 'error', title: 'Error', text: 'Error de red' });
        });
    });
  }
});
</script>
