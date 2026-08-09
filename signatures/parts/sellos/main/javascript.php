<script>
'use strict';

var idSello = window.idSello || 0;

document.addEventListener('DOMContentLoaded', function () {
  var form = document.getElementById('formActualizarSello');
  var btnSubmit = document.getElementById('btnActualizarSello');
  var btnLoader = document.getElementById('loaderbtn');
  var radios = document.querySelectorAll('input[name="sello_logotipo"]');

  radios.forEach(function (radio) {
    radio.addEventListener('change', function () {
      togglePreviewSello(this.value === 'true');
    });
  });

  if (form) {
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      actualizarSello();
    });
  }

  if (idSello) {
    cargarLogotipoSello();
  }

  function actualizarSello() {
    if (!form) return;

    if (btnSubmit) btnSubmit.style.display = 'none';
    if (btnLoader) btnLoader.style.display = 'inline-flex';

    var formData = new FormData(form);

    fetch('parts/sellos/main/actualizar_sello.php', {
      method: 'POST',
      body: formData
    })
      .then(function (response) {
        if (!response.ok) throw new Error('Error en la respuesta del servidor');
        return response.json();
      })
      .then(function (data) {
        if (!data.success) {
          throw new Error(data.message || 'Error al actualizar el sello');
        }

        Swal.fire({
          title: '¡Actualizado!',
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
          text: error.message || 'Error al actualizar el sello',
          icon: 'error',
          confirmButtonText: 'Aceptar'
        });
      })
      .finally(function () {
        if (btnSubmit) btnSubmit.style.display = '';
        if (btnLoader) btnLoader.style.display = 'none';
      });
  }
});

function togglePreviewSello(conLogo) {
  var con = document.getElementById('sello_con_logo');
  var sin = document.getElementById('sello_sin_logo');
  var btn = document.getElementById('btnSubirLogotipoSello');

  if (con) con.style.display = conLogo ? '' : 'none';
  if (sin) sin.style.display = conLogo ? 'none' : '';
  if (btn) btn.style.display = conLogo ? '' : 'none';
}

function cargarLogotipoSello() {
  if (!idSello) return;

  var ordago = document.getElementById('ordago');
  if (!ordago) return;

  fetch('parts/sellos/main/get_logotipo_info.php?id=' + idSello)
    .then(function (response) {
      if (!response.ok) throw new Error('Error en la respuesta del servidor');
      return response.json();
    })
    .then(function (data) {
      if (data.success && data.logotipo) {
        mostrarLogotipoSello(data.logotipo);
      } else {
        mostrarSinLogotipoSello();
      }
    })
    .catch(function () {
      mostrarSinLogotipoSello();
    });
}

function mostrarLogotipoSello(nombreArchivo) {
  var ordago = document.getElementById('ordago');
  if (!ordago) return;

  var timestamp = new Date().getTime();
  ordago.innerHTML =
    '<img src="photos/' +
    nombreArchivo +
    '?t=' +
    timestamp +
    '" alt="Logotipo del sello" class="img-fluid">';

  var contenedor = document.getElementById('contenedor_preview_sello');
  if (!contenedor) return;

  var btnExistente = contenedor.querySelector('.btn-eliminar-logotipo-sello');
  if (!btnExistente) {
    var botonEliminar = document.createElement('button');
    botonEliminar.type = 'button';
    botonEliminar.className = 'btn btn-icon btn-danger waves-effect waves-light position-absolute btn-eliminar-logotipo-sello';
    botonEliminar.innerHTML = '<i class="icon-base ri ri-delete-bin-line"></i>';
    botonEliminar.style.cssText = 'top: 10px; right: 10px; z-index: 10;';
    botonEliminar.onclick = function () {
      eliminarLogotipoSello();
    };
    contenedor.appendChild(botonEliminar);
  }
}

function mostrarSinLogotipoSello() {
  var ordago = document.getElementById('ordago');
  if (ordago) {
    ordago.innerHTML = '<span class="text-muted small">Sin imagen</span>';
  }

  var contenedor = document.getElementById('contenedor_preview_sello');
  if (!contenedor) return;
  var btnExistente = contenedor.querySelector('.btn-eliminar-logotipo-sello');
  if (btnExistente) btnExistente.remove();
}

function abrirModalSubirLogotipoSello() {
  var modal = document.createElement('div');
  modal.className = 'modal fade';
  modal.id = 'modalSubirLogotipoSello';
  modal.setAttribute('tabindex', '-1');
  modal.setAttribute('aria-hidden', 'true');

  modal.innerHTML =
    '<div class="modal-dialog">' +
    '<div class="modal-content">' +
    '<div class="modal-header">' +
    '<h5 class="modal-title">Subir logotipo del sello</h5>' +
    '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>' +
    '</div>' +
    '<div class="modal-body">' +
    '<form id="formSubirLogotipoSello" enctype="multipart/form-data">' +
    '<div class="mb-3">' +
    '<label for="logotipo_sello" class="form-label">Seleccionar imagen</label>' +
    '<input type="file" class="form-control" id="logotipo_sello" name="logotipo" accept="image/jpeg,image/png,image/gif" required>' +
    '<div class="form-text">Formatos: JPG, PNG, GIF. Máximo 5MB. Recomendado 260×150 px.</div>' +
    '</div>' +
    '</form>' +
    '</div>' +
    '<div class="modal-footer">' +
    '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>' +
    '<button type="button" class="btn btn-primary" onclick="subirLogotipoSello()">Subir</button>' +
    '</div>' +
    '</div>' +
    '</div>';

  document.body.appendChild(modal);
  var modalInstance = new bootstrap.Modal(modal);
  modalInstance.show();

  modal.addEventListener('hidden.bs.modal', function () {
    document.body.removeChild(modal);
  });
}

function subirLogotipoSello() {
  var fileInput = document.getElementById('logotipo_sello');
  if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
    Swal.fire('Error', 'Por favor selecciona un archivo', 'error');
    return;
  }

  var formData = new FormData();
  formData.append('logotipo', fileInput.files[0]);
  formData.append('id_sello', idSello);

  var btnSubir = document.querySelector('#modalSubirLogotipoSello .btn-primary');
  if (btnSubir) {
    btnSubir.disabled = true;
    btnSubir.innerHTML =
      '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Subiendo...';
  }

  fetch('parts/sellos/main/subir_logotipo.php', {
    method: 'POST',
    body: formData
  })
    .then(function (response) {
      if (!response.ok) throw new Error('Error en la respuesta del servidor');
      return response.json();
    })
    .then(function (data) {
      if (!data.success) throw new Error(data.message || 'Error al subir logotipo');

      Swal.fire('¡Éxito!', 'Logotipo subido correctamente', 'success');
      var modalEl = document.getElementById('modalSubirLogotipoSello');
      if (modalEl) {
        var instance = bootstrap.Modal.getInstance(modalEl);
        if (instance) instance.hide();
      }
      setTimeout(function () {
        cargarLogotipoSello();
      }, 300);
    })
    .catch(function (error) {
      Swal.fire('Error', error.message || 'Error al subir logotipo', 'error');
    })
    .finally(function () {
      if (btnSubir) {
        btnSubir.disabled = false;
        btnSubir.innerHTML = 'Subir';
      }
    });
}

function eliminarLogotipoSello() {
  Swal.fire({
    title: '¿Eliminar logotipo?',
    text: 'Esta acción no se puede deshacer',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#d33',
    cancelButtonColor: '#3085d6',
    confirmButtonText: 'Sí, eliminar',
    cancelButtonText: 'Cancelar'
  }).then(function (result) {
    if (!result.isConfirmed) return;

    fetch('parts/sellos/main/eliminar_logotipo.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'id_sello=' + idSello
    })
      .then(function (response) {
        if (!response.ok) throw new Error('Error en la respuesta del servidor');
        return response.json();
      })
      .then(function (data) {
        if (!data.success) throw new Error(data.message || 'Error al eliminar logotipo');
        Swal.fire('¡Éxito!', 'Logotipo eliminado correctamente', 'success');
        setTimeout(function () {
          cargarLogotipoSello();
        }, 300);
      })
      .catch(function (error) {
        Swal.fire('Error', error.message || 'Error al eliminar logotipo', 'error');
      });
  });
}
</script>
