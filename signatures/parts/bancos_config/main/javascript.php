<script>
function eliminarBancoConfig(idBanco) {
  Swal.fire({
    title: '¿Eliminar banco?',
    text: 'Esta acción eliminará el banco. No se puede deshacer.',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#d33',
    cancelButtonColor: '#3085d6',
    confirmButtonText: 'Sí, eliminar',
    cancelButtonText: 'Cancelar',
    showCloseButton: true,
    allowOutsideClick: false
  }).then(function (result) {
    if (!result.isConfirmed) {
      return;
    }

    Swal.fire({
      title: 'Eliminando...',
      allowOutsideClick: false,
      didOpen: function () {
        Swal.showLoading();
      }
    });

    const formData = new FormData();
    formData.append('id_banco', idBanco);

    fetch('parts/bancos_config/listar/eliminar_banco.php', {
      method: 'POST',
      body: formData
    })
      .then(function (response) {
        if (!response.ok) {
          throw new Error('Error en la respuesta del servidor');
        }
        return response.json();
      })
      .then(function (data) {
        if (!data.success) {
          throw new Error(data.message || 'Error al eliminar el banco');
        }
        Swal.fire({
          title: 'Eliminado',
          text: data.message,
          icon: 'success',
          confirmButtonText: 'Aceptar'
        }).then(function () {
          window.location.href = 'bancos_config.php';
        });
      })
      .catch(function (error) {
        Swal.fire({
          title: 'Error',
          text: error.message || 'Error al eliminar el banco',
          icon: 'error',
          confirmButtonText: 'Aceptar'
        });
      });
  });
}

function guardarApiBanco() {
  const form = document.getElementById('formApiBanco');
  if (!form) return;

  const idComercio = (document.getElementById('api_id_comercio') || {}).value || '';
  const apiKey = ((document.getElementById('api_api_key') || {}).value || '').trim();
  const token = ((document.getElementById('api_token_value') || {}).value || '').trim();
  const secret = ((document.getElementById('api_secret_api_key') || {}).value || '').trim();
  const urlApi = ((document.getElementById('api_url_api') || {}).value || '').trim();

  if (!idComercio || parseInt(idComercio, 10) <= 0) {
    Swal.fire('Atención', 'El ID cliente es obligatorio', 'warning');
    return;
  }
  if (!apiKey || !token || !secret || !urlApi) {
    Swal.fire('Atención', 'Completa API Key, Token, Secret y URL', 'warning');
    return;
  }

  const formData = new FormData(form);
  const estado = document.getElementById('api_estado_api');
  if (estado && estado.checked) {
    formData.set('estado_api', 'true');
  } else {
    formData.set('estado_api', 'false');
  }

  const btn = document.getElementById('btnGuardarApiBanco');
  if (btn) btn.disabled = true;

  Swal.fire({
    title: 'Guardando...',
    allowOutsideClick: false,
    didOpen: function () {
      Swal.showLoading();
    }
  });

  fetch('parts/bancos_config/main/guardar_api_banco.php', {
    method: 'POST',
    body: formData
  })
    .then(function (response) {
      if (!response.ok) {
        throw new Error('Error en la respuesta del servidor');
      }
      return response.json();
    })
    .then(function (data) {
      if (!data.success) {
        throw new Error(data.message || 'Error al guardar la API');
      }
      Swal.fire({
        title: 'Guardado',
        text: data.message,
        icon: 'success',
        confirmButtonText: 'Aceptar'
      }).then(function () {
        window.location.reload();
      });
    })
    .catch(function (error) {
      Swal.fire({
        title: 'Error',
        text: error.message || 'Error al guardar la API',
        icon: 'error',
        confirmButtonText: 'Aceptar'
      });
    })
    .finally(function () {
      if (btn) btn.disabled = false;
    });
}
</script>
