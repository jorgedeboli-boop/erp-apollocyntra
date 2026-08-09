<script>
document.addEventListener('DOMContentLoaded', function () {
  configurarEnvioFormularioBanco();

  setTimeout(function () {
    $('#pais').select2({
      dropdownParent: $('#pais').parent(),
      placeholder: 'Seleccionar país',
      allowClear: true,
      ajax: {
        url: 'parts/universal/ajax_poblaciones.php',
        dataType: 'json',
        delay: 250,
        data: function (params) {
          return {
            action: 'paises',
            search: params.term || '',
            page: params.page || 1
          };
        },
        processResults: function (data) {
          return {
            results: data.results || [],
            pagination: data.pagination || { more: false }
          };
        }
      }
    });

    $('#c_provincia').select2({
      dropdownParent: $('#c_provincia').parent(),
      placeholder: 'Seleccionar provincia',
      allowClear: true,
      ajax: {
        url: 'parts/universal/ajax_poblaciones.php',
        dataType: 'json',
        delay: 250,
        data: function (params) {
          return {
            action: 'provincias',
            search: params.term || '',
            page: params.page || 1,
            idpais: $('#pais').val()
          };
        },
        processResults: function (data) {
          return {
            results: data.results || [],
            pagination: data.pagination || { more: false }
          };
        }
      }
    });

    $('#c_poblacion').select2({
      dropdownParent: $('#c_poblacion').parent(),
      placeholder: 'Seleccionar población',
      allowClear: true,
      ajax: {
        url: 'parts/universal/ajax_poblaciones.php',
        dataType: 'json',
        delay: 250,
        data: function (params) {
          return {
            action: 'poblaciones',
            search: params.term || '',
            page: params.page || 1,
            idprovincia: $('#c_provincia').val()
          };
        },
        processResults: function (data) {
          return {
            results: data.results || [],
            pagination: data.pagination || { more: false }
          };
        }
      }
    });

    $('#pais').on('change', function () {
      $('#c_provincia').val('').trigger('change');
      $('#c_poblacion').val('').trigger('change');
      $('#codigo_postal').val('');
    });

    $('#c_provincia').on('change', function () {
      $('#c_poblacion').val('').trigger('change');
      $('#codigo_postal').val('');
    });

    $('#c_poblacion').on('change', function () {
      var idPoblacion = $(this).val();
      if (!idPoblacion) {
        $('#codigo_postal').val('');
        return;
      }

      $.ajax({
        url: 'parts/universal/ajax_poblaciones.php',
        dataType: 'json',
        data: {
          action: 'poblacion_detalle',
          idpoblacion: idPoblacion
        },
        success: function (response) {
          if (!response.success) {
            return;
          }
          var data = response.data;
          $('#codigo_postal').val(data.codigo_postal || '');
          if (!$('#c_provincia').val() && data.idprovincia) {
            $('#c_provincia').append(new Option(data.provincia, data.idprovincia, true, true)).trigger('change');
          }
          if (!$('#pais').val() && data.id_rel_country) {
            $('#pais').append(new Option(data.pais, data.id_rel_country, true, true)).trigger('change');
          }
        }
      });
    });
  }, 100);
});

function configurarEnvioFormularioBanco() {
  var form = document.getElementById('formEditarBanco');
  if (!form) {
    return;
  }

  form.addEventListener('submit', function (event) {
    event.preventDefault();
    if (!form.checkValidity()) {
      form.classList.add('was-validated');
      return;
    }

    var btnEditar = document.getElementById('btnEditarBanco');
    if (btnEditar) {
      btnEditar.disabled = true;
      btnEditar.innerHTML = '<span class="spinner-border me-1" role="status" aria-hidden="true"></span>Actualizando...';
    }

    fetch('parts/bancos_config/editar/procesar_editar_banco.php', {
      method: 'POST',
      body: new FormData(form)
    })
      .then(function (response) {
        return response.json();
      })
      .then(function (data) {
        if (data.success) {
          Swal.fire({
            icon: 'success',
            title: 'Actualizado',
            text: data.message,
            timer: 1400,
            showConfirmButton: false
          }).then(function () {
            window.location.href = data.redirect || 'bancos_config.php';
          });
          return;
        }

        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: data.error || 'No se pudo actualizar el banco'
        });
        if (btnEditar) {
          btnEditar.disabled = false;
          btnEditar.innerHTML = '<i class="icon-base ri ri-check-line me-2"></i>Actualizar banco';
        }
      })
      .catch(function () {
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: 'Error de conexión. Intente nuevamente.'
        });
        if (btnEditar) {
          btnEditar.disabled = false;
          btnEditar.innerHTML = '<i class="icon-base ri ri-check-line me-2"></i>Actualizar banco';
        }
      });
  });
}
</script>
