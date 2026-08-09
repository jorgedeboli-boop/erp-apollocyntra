/**
 * Visor y gestión de documentos del artículo (articulos_venta_imagenes).
 */
(function () {
  'use strict';

  var idArticulo = 0;
  /** Igual que cantidadFotosLote en lotes — usado por verificarCambiosFotos */
  var cantidadFotosArticulo = 0;
  var intervalo_fotos = null;
  /** Más frecuente que lote (22s): al subir foto desde móvil hay que refrescar y cerrar sin esperar tanto */
  var INTERVALO_VERIFICACION_FOTOS_ARTICULO_MS = 4000;

  function escapeHtml(str) {
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  function getIdArticulo() {
    if (idArticulo > 0) {
      return idArticulo;
    }
    var boot = document.getElementById('articulo-imagenes-boot');
    if (!boot) {
      return 0;
    }
    var raw = boot.getAttribute('data-id-articulo');
    idArticulo = raw ? parseInt(raw, 10) : 0;
    return idArticulo > 0 ? idArticulo : 0;
  }

  window.cargarImagenesArticulo = function cargarImagenesArticulo() {
    var aid = getIdArticulo();
    var contenedor = document.getElementById('contenedor_imagenes_articulo');
    var sinImagenes = document.getElementById('sin_imagenes_articulo');
    var loading = document.getElementById('loading_imagenes_articulo');
    if (!contenedor || !sinImagenes || !loading || !aid) {
      return;
    }
    loading.style.display = 'block';
    contenedor.innerHTML = '';
    sinImagenes.style.display = 'none';

    var fdImg = new FormData();
    fdImg.append('tipo', 'articulo');
    fdImg.append('id', String(aid));
    fdImg.append('id_sucursal', String((global.articuloFotoMovilIds && global.articuloFotoMovilIds.idSucursal) || 0));

    fetch('camera/api/list_imagenes.php', {
      method: 'POST',
      credentials: 'same-origin',
      body: fdImg
    })
      .then(function (response) {
        return response.json();
      })
      .then(function (data) {
        loading.style.display = 'none';
        if (data.success && data.imagenes && data.imagenes.length > 0) {
          cantidadFotosArticulo = data.imagenes.length;
          mostrarImagenesArticulo(data.imagenes);
        } else {
          cantidadFotosArticulo = 0;
          sinImagenes.style.display = 'block';
        }
      })
      .catch(function (err) {
        console.error('Error al cargar imágenes del artículo:', err);
        loading.style.display = 'none';
        cantidadFotosArticulo = 0;
        sinImagenes.style.display = 'block';
      });
  };

  function mostrarImagenesArticulo(imagenes) {
    var contenedor = document.getElementById('contenedor_imagenes_articulo');
    if (!contenedor) {
      return;
    }
    contenedor.innerHTML = '';
    imagenes.forEach(function (imagen) {
      var columna = document.createElement('div');
      columna.className = 'col-md-6';
      var esPDF = imagen.foto.toLowerCase().endsWith('.pdf');
      var iconoArchivo = esPDF ? 'ri-file-text-line' : 'ri-image-line';
      var altText = esPDF ? 'Documento PDF del artículo' : 'Documento del artículo';
      var ruta = 'photos/' + imagen.foto;

      // onclick con comilla simple en el atributo: JSON.stringify usa ", si usáramos onclick="..." romperíamos el HTML.
      var html =
        '<div class="card h-100">' +
        '<div class="card-body p-0">' +
        '<div class="position-relative">' +
        (esPDF
          ? '<div class="pdf-preview" onclick=\'descargarPDFArticulo(' +
            JSON.stringify(ruta) +
            ', ' +
            JSON.stringify(imagen.foto) +
            ')\'>' +
            '<i class="icon-base ri ' +
            iconoArchivo +
            ' icon-48px text-primary"></i>' +
            '<div class="mt-2"><small class="text-muted">' +
            escapeHtml(imagen.foto) +
            '</small></div></div>'
          : '<img src="' +
            ruta.replace(/"/g, '&quot;') +
            '" alt="' +
            altText.replace(/"/g, '&quot;') +
            '" class="img-fluid w-100" style="cursor:pointer;" onclick=\'ampliarImagenArticulo(' +
            JSON.stringify(ruta) +
            ')\'>') +
        '<div class="position-absolute top-0 end-0 p-2">' +
        '<button type="button" class="btn btn-danger btn-sm" onclick=\'eliminarFotoArticulo(' +
        imagen.id_foto +
        ', ' +
        JSON.stringify(imagen.foto) +
        ')\' title="Eliminar">' +
        '<i class="icon-base ri ri-delete-bin-line icon-14px"></i>' +
        '</button></div></div></div></div>';

      columna.innerHTML = html;
      contenedor.appendChild(columna);
    });
  }

  window.abrirModalSubirFotoArticulo = function abrirModalSubirFotoArticulo() {
    var form = document.getElementById('formSubirFotoArticulo');
    if (form) {
      form.reset();
    }
    var el = document.getElementById('modalSubirFotoArticulo');
    if (el && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
      var modal = new bootstrap.Modal(el);
      modal.show();
    }
  };

  window.subirFotoArticulo = function subirFotoArticulo() {
    var aid = getIdArticulo();
    var archivoInput = document.getElementById('archivo_foto_articulo');
    var modalEl = document.getElementById('modalSubirFotoArticulo');
    var btnSubir = modalEl ? modalEl.querySelector('.btn-primary') : null;
    var spinner = btnSubir ? btnSubir.querySelector('.spinner-border') : null;

    if (!archivoInput || !archivoInput.files || !archivoInput.files[0]) {
      if (typeof Swal !== 'undefined') {
        Swal.fire({
          title: 'Error',
          text: 'Por favor selecciona un archivo',
          icon: 'error',
          confirmButtonText: 'Aceptar'
        });
      }
      return;
    }

    var archivo = archivoInput.files[0];
    if (archivo.size > 5 * 1024 * 1024) {
      if (typeof Swal !== 'undefined') {
        Swal.fire({
          title: 'Error',
          text: 'El archivo es demasiado grande. Máximo 5MB',
          icon: 'error',
          confirmButtonText: 'Aceptar'
        });
      }
      return;
    }

    var formatosPermitidos = ['jpg', 'jpeg', 'gif', 'png', 'pdf'];
    var extension = archivo.name.split('.').pop().toLowerCase();
    if (formatosPermitidos.indexOf(extension) === -1) {
      if (typeof Swal !== 'undefined') {
        Swal.fire({
          title: 'Error',
          text: 'Formato de archivo no permitido. Solo JPG, JPEG, GIF, PNG y PDF',
          icon: 'error',
          confirmButtonText: 'Aceptar'
        });
      }
      return;
    }

    var formData = new FormData();
    formData.append('archivo_foto', archivo);
    formData.append('id_articulo', String(aid));

    if (btnSubir) {
      btnSubir.disabled = true;
    }
    if (spinner) {
      spinner.classList.remove('d-none');
    }

    fetch('parts/articulos/main/subir_foto_articulo.php', {
      method: 'POST',
      body: formData
    })
      .then(function (response) {
        return response.json();
      })
      .then(function (data) {
        if (btnSubir) {
          btnSubir.disabled = false;
        }
        if (spinner) {
          spinner.classList.add('d-none');
        }
        if (data.success) {
          if (modalEl && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            var inst = bootstrap.Modal.getInstance(modalEl);
            if (inst) {
              inst.hide();
            }
          }
          var esImagen = ['jpg', 'jpeg', 'gif', 'png'].indexOf(extension) !== -1;
          var mensajeExtra = esImagen
            ? ' (redimensionada automáticamente a 800px de ancho máximo)'
            : '';
          if (typeof Swal !== 'undefined') {
            Swal.fire({
              title: '¡Archivo subido!',
              text: (data.message || '') + mensajeExtra,
              icon: 'success',
              confirmButtonText: 'Aceptar',
              timer: 3000,
              timerProgressBar: true
            });
          }
          cargarImagenesArticulo();
        } else {
          throw new Error(data.error || 'Error desconocido');
        }
      })
      .catch(function (error) {
        console.error('Error al subir documento:', error);
        if (btnSubir) {
          btnSubir.disabled = false;
        }
        if (spinner) {
          spinner.classList.add('d-none');
        }
        if (typeof Swal !== 'undefined') {
          Swal.fire({
            title: 'Error',
            text: 'No se pudo subir el archivo: ' + error.message,
            icon: 'error',
            confirmButtonText: 'Aceptar'
          });
        }
      });
  };

  window.ampliarImagenArticulo = function ampliarImagenArticulo(rutaImagen) {
    var img = document.getElementById('imagen_ampliada_articulo');
    var el = document.getElementById('modalAmpliarImagenArticulo');
    if (img) {
      img.src = rutaImagen;
    }
    if (el && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
      var modal = new bootstrap.Modal(el);
      modal.show();
    }
  };

  window.descargarPDFArticulo = function descargarPDFArticulo(url, filename) {
    if (typeof Swal !== 'undefined') {
      Swal.fire({
        title: 'Descargando PDF…',
        text: 'Preparando descarga de ' + filename,
        icon: 'info',
        timer: 2000,
        timerProgressBar: true,
        showConfirmButton: false
      });
    }
    var link = document.createElement('a');
    link.href = url;
    link.download = filename;
    link.target = '_blank';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    setTimeout(function () {
      if (typeof Swal !== 'undefined') {
        Swal.fire({
          title: '¡Descarga iniciada!',
          text: 'El PDF se está descargando',
          icon: 'success',
          timer: 3000,
          timerProgressBar: true,
          showConfirmButton: false
        });
      }
    }, 1000);
  };

  window.eliminarFotoArticulo = function eliminarFotoArticulo(idFoto, nombreFoto) {
    var aid = getIdArticulo();
    if (typeof Swal === 'undefined') {
      return;
    }
    Swal.fire({
      title: 'Confirmar eliminación',
      text: '¿Estás seguro de que quieres eliminar este documento? Esta acción no se puede deshacer.',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#dc3545',
      cancelButtonColor: '#6c757d',
      confirmButtonText: 'Eliminar',
      cancelButtonText: 'Cancelar'
    }).then(function (result) {
      if (!result.isConfirmed) {
        return;
      }
      var body =
        'id_foto=' +
        encodeURIComponent(String(idFoto)) +
        '&nombre_foto=' +
        encodeURIComponent(nombreFoto) +
        '&id_articulo=' +
        encodeURIComponent(String(aid));

      fetch('parts/articulos/main/eliminar_foto_articulo.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: body
      })
        .then(function (response) {
          return response.json();
        })
        .then(function (data) {
          if (data.success) {
            Swal.fire({
              title: '¡Documento eliminado!',
              text: data.message || '',
              icon: 'success',
              confirmButtonText: 'Aceptar',
              timer: 3000,
              timerProgressBar: true
            });
            cargarImagenesArticulo();
          } else {
            throw new Error(data.error || 'Error desconocido');
          }
        })
        .catch(function (error) {
          console.error('Error al eliminar documento:', error);
          Swal.fire({
            title: 'Error',
            text: 'No se pudo eliminar: ' + error.message,
            icon: 'error',
            confirmButtonText: 'Aceptar'
          });
        });
    });
  };

  /**
   * Verificar si hay cambios en la cantidad de fotos (misma idea que parts/lotes/main/javascript.php).
   */
  window.verificarCambiosFotos = function verificarCambiosFotos(tipo_verificacion) {
    console.log('verificarCambiosFotos: ' + tipo_verificacion);
    var ids = window.articuloFotoMovilIds;
    if (!ids || !ids.idArticulo) {
      return;
    }
    if (tipo_verificacion !== 'articulo') {
      return;
    }
    var idSucPoll = parseInt(String(ids.idSucursal != null ? ids.idSucursal : 0), 10);
    if (isNaN(idSucPoll) || idSucPoll < 0) {
      idSucPoll = 0;
    }
    fetch(
      'parts/lotes/main/get_cantidad_fotos.php?tipo=articulo&id_item=' +
        ids.idArticulo +
        '&id_sucursal=' +
        idSucPoll,
      { credentials: 'same-origin' }
    )
      .then(function (response) {
        return response.json();
      })
      .then(function (data) {
        if (data.success) {
          if (data.cantidad !== cantidadFotosArticulo) {
            console.log(
              'Cambio detectado en fotos del artículo: ' +
                cantidadFotosArticulo +
                ' -> ' +
                data.cantidad
            );
            // El modal lo gestiona camera-doc-panel (se cierra al detectar token usado).
            if (typeof window.cargarImagenesArticulo === 'function') {
              window.cargarImagenesArticulo();
            }
            window.detenerVerificacion_fotos();
          }
        }
      })
      .catch(function (error) {
        console.error('Error al verificar fotos del artículo:', error);
        window.detenerVerificacion_fotos();
      });
  };

  window.iniciarVerificacion_fotos = function iniciarVerificacion_fotos(tipo_verificacion) {
    if (!intervalo_fotos) {
      window.verificarCambiosFotos(tipo_verificacion);
      intervalo_fotos = setInterval(function () {
        window.verificarCambiosFotos(tipo_verificacion);
      }, INTERVALO_VERIFICACION_FOTOS_ARTICULO_MS);
      console.log('Verificación iniciada (cada ' + INTERVALO_VERIFICACION_FOTOS_ARTICULO_MS + 'ms)');
    }
  };

  window.detenerVerificacion_fotos = function detenerVerificacion_fotos() {
    if (intervalo_fotos) {
      clearInterval(intervalo_fotos);
      intervalo_fotos = null;
      console.log('Verificación detenida');
    }
  };

  function registrarCallbackCameraQrArticulo() {
    if (typeof window.CameraQR === 'undefined' || typeof window.CameraQR.init !== 'function') {
      return;
    }
    window.CameraQR.init({
      callbacks: {
        onTokenUtilizado: function (tipo_qr) {
          if (tipo_qr !== 'articulo') {
            return;
          }
          if (typeof window.cargarImagenesArticulo === 'function') {
            window.cargarImagenesArticulo();
          }
          if (typeof window.detenerVerificacion_fotos === 'function') {
            window.detenerVerificacion_fotos();
          }
        }
      }
    });
  }

  window.abrirModalFotoMovilArticulo = function abrirModalFotoMovilArticulo() {
    var ids = global.articuloFotoMovilIds;
    if (!ids || !ids.idArticulo || !ids.idSucursal) {
      return;
    }
    if (global.CameraDocPanel && typeof global.CameraDocPanel.open === 'function') {
      global.CameraDocPanel.open({
        tipo: 'articulo',
        id: ids.idArticulo,
        idSucursal: ids.idSucursal
      }).catch(function (err) {
        console.error('CameraDocPanel', err);
      });
    }
    if (typeof window.iniciarVerificacion_fotos === 'function') {
      window.iniciarVerificacion_fotos('articulo');
    }
  };

  document.addEventListener('DOMContentLoaded', function () {
    if (!document.getElementById('articulo-imagenes-boot')) {
      return;
    }
    registrarCallbackCameraQrArticulo();
    if (getIdArticulo() > 0) {
      cargarImagenesArticulo();
    }
  });
})();
