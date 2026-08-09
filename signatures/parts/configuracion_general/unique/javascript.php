<!-- JAVASCRIPT CUSTOM configuracion_general - unique  -->
<script>
(function () {
  'use strict';

  var modalId = 'modalNuevaConfiguracion';
  var $modal = $('#' + modalId);
  var select2Inicializado = false;
  var baseUrl = 'parts/configuracion_general/unique/';

  function initSelect2Tipo() {
    if (select2Inicializado) {
      return;
    }
    if (typeof $.fn.select2 === 'undefined') {
      return;
    }
    $('#cfg_typ_config').select2({
      theme: 'bootstrap-5',
      dropdownParent: $modal,
      width: '100%',
      minimumResultsForSearch: Infinity
    });
    select2Inicializado = true;
  }

  function initCfgListaSelect2() {
    if (typeof $.fn.select2 === 'undefined') {
      return;
    }
    $('#cfg-lista-general .cfg-select2-options').each(function () {
      var $el = $(this);
      if ($el.data('select2')) {
        return;
      }
      $el.select2({
        theme: 'bootstrap-5',
        width: '100%',
        minimumResultsForSearch: 8,
        language: 'es'
      });
    });
  }

  function aplicarValorRefrescado(row, data) {
    if (!row || !data || !data.success) {
      return;
    }
    var typ = row.getAttribute('data-cfg-typ');
    var el = row.querySelector('.cfg-campo-actualizable');
    if (!el) {
      return;
    }
    if (typ === 'text') {
      el.value = data.texto_value != null ? data.texto_value : '';
    } else if (typ === 'varchar') {
      el.value = data.varchar_value != null ? data.varchar_value : '';
    } else if (typ === 'integro') {
      el.value = data.integro_value != null ? String(data.integro_value) : '';
    } else if (typ === 'decimal') {
      var d = data.decimal_value;
      if (d === null || d === undefined || d === '') {
        el.value = '0.00';
      } else if (typeof d === 'number') {
        el.value = d.toFixed(2);
      } else {
        var n = parseFloat(String(d).replace(',', '.'));
        el.value = isNaN(n) ? String(d) : n.toFixed(2);
      }
    }
  }

  function refrescarItemDesdeServidor(idConfig, row) {
    return fetch(baseUrl + 'get_configuracion_item.php?id=' + encodeURIComponent(idConfig))
      .then(function (r) { return r.json(); })
      .then(function (data) {
        aplicarValorRefrescado(row, data);
        return data;
      });
  }

  document.addEventListener('DOMContentLoaded', function () {
    initCfgListaSelect2();
  });

  document.getElementById(modalId).addEventListener('shown.bs.modal', function () {
    initSelect2Tipo();
  });

  document.getElementById(modalId).addEventListener('hidden.bs.modal', function () {
    var form = document.getElementById('formNuevaConfiguracion');
    if (form) {
      form.reset();
    }
    if (select2Inicializado && $('#cfg_typ_config').length) {
      $('#cfg_typ_config').val('text').trigger('change');
    }
  });

  var panelGeneral = document.getElementById('navs-pills-cg-general');
  if (panelGeneral) {
    panelGeneral.addEventListener('click', function (e) {
      var btn = e.target.closest('.cfg-btn-actualizar');
      if (!btn) {
        return;
      }
      var row = btn.closest('.cfg-item');
      if (!row) {
        return;
      }
      var idConfig = row.getAttribute('data-cfg-id');
      var campo = row.querySelector('.cfg-campo-actualizable');
      if (!idConfig || !campo) {
        return;
      }
      var valor = campo.value;

      if (typeof Swal === 'undefined') {
        if (!window.confirm('¿Actualizar este valor?')) {
          return;
        }
        ejecutarActualizar(idConfig, valor, row, btn);
        return;
      }

      Swal.fire({
        title: '¿Actualizar?',
        text: '¿Confirma que desea guardar el nuevo valor?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sí, actualizar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#696cff',
        cancelButtonColor: '#8592a3'
      }).then(function (result) {
        if (!result.isConfirmed) {
          return;
        }
        ejecutarActualizar(idConfig, valor, row, btn);
      });
    });
  }

  function ejecutarActualizar(idConfig, valor, row, btn) {
    var textoOriginal = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>…';

    var fd = new FormData();
    fd.append('id_config', idConfig);
    fd.append('valor', valor);

    fetch(baseUrl + 'actualizar_configuracion.php', { method: 'POST', body: fd })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if (!data.success) {
          if (typeof Swal !== 'undefined') {
            Swal.fire({ icon: 'error', title: 'Error', text: data.message || 'No se pudo actualizar.' });
          } else {
            alert(data.message || 'Error');
          }
          return;
        }
        return refrescarItemDesdeServidor(idConfig, row).then(function () {
          if (typeof Swal !== 'undefined') {
            Swal.fire({ icon: 'success', title: 'Actualizado', text: data.message || 'Valor guardado.', timer: 1800, showConfirmButton: false });
          }
        });
      })
      .catch(function () {
        if (typeof Swal !== 'undefined') {
          Swal.fire({ icon: 'error', title: 'Error', text: 'Error de conexión.' });
        } else {
          alert('Error de conexión.');
        }
      })
      .finally(function () {
        btn.disabled = false;
        btn.innerHTML = textoOriginal;
      });
  }

  document.getElementById('btnGuardarConfiguracion').addEventListener('click', function () {
    var form = document.getElementById('formNuevaConfiguracion');
    if (!form || !form.checkValidity()) {
      form.reportValidity();
      return;
    }

    var btn = this;
    var textoOriginal = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Guardando...';

    var fd = new FormData(form);

    fetch(baseUrl + 'guardar_configuracion.php', {
      method: 'POST',
      body: fd
    })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if (data.success) {
          if (typeof Swal !== 'undefined') {
            Swal.fire({
              icon: 'success',
              title: 'Guardado',
              text: data.message || 'Registro creado.',
              confirmButtonText: 'Aceptar'
            }).then(function () {
              var inst = bootstrap.Modal.getInstance(document.getElementById(modalId));
              if (inst) {
                inst.hide();
              }
              window.location.href = 'configuracion_general.php';
            });
          } else {
            window.location.href = 'configuracion_general.php';
          }
        } else {
          if (typeof Swal !== 'undefined') {
            Swal.fire({ icon: 'error', title: 'Error', text: data.message || 'No se pudo guardar.' });
          } else {
            alert(data.message || 'Error');
          }
        }
      })
      .catch(function () {
        if (typeof Swal !== 'undefined') {
          Swal.fire({ icon: 'error', title: 'Error', text: 'Error de conexión.' });
        } else {
          alert('Error de conexión.');
        }
      })
      .finally(function () {
        btn.disabled = false;
        btn.innerHTML = textoOriginal;
      });
  });
})();
</script>
