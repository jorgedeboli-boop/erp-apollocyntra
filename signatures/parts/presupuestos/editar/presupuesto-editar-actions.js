/**
 * Imprimir / enviar presupuesto por email (solo edición).
 */
'use strict';

document.addEventListener('DOMContentLoaded', function () {
  var btnEnviar = document.getElementById('btn_confirmar_email_presupuesto');
  var idPres = document.getElementById('id_presupuesto_edicion');
  var inputEmail = document.getElementById('email_envio_presupuesto');
  var msgEl = document.getElementById('email_presupuesto_mensaje');

  if (!btnEnviar || !idPres || !inputEmail) {
    return;
  }

  btnEnviar.addEventListener('click', function () {
    var id = parseInt(idPres.value, 10) || 0;
    var email = (inputEmail.value || '').trim();
    msgEl.textContent = '';
    msgEl.className = 'small mt-2';

    if (!id) {
      msgEl.textContent = 'No se identifica el presupuesto.';
      msgEl.classList.add('text-danger');
      return;
    }
    if (!email) {
      msgEl.textContent = 'Indique un correo electrónico.';
      msgEl.classList.add('text-warning');
      return;
    }

    btnEnviar.disabled = true;
    var fd = new FormData();
    fd.append('id_presupuesto', String(id));
    fd.append('email', email);

    fetch('parts/presupuestos/editar/enviar_presupuesto_email.php', {
      method: 'POST',
      body: fd,
      credentials: 'same-origin'
    })
      .then(function (r) {
        return r.json();
      })
      .then(function (data) {
        if (data.success) {
          msgEl.textContent = data.message || 'Enviado correctamente.';
          msgEl.classList.add('text-success');
          if (typeof Swal !== 'undefined') {
            Swal.fire({
              icon: 'success',
              title: 'Enviado',
              text: data.message || 'Presupuesto enviado por email.',
              timer: 3500
            });
          }
          var modalEl = document.getElementById('modal_email_presupuesto');
          if (modalEl && typeof bootstrap !== 'undefined' && bootstrap.Modal && bootstrap.Modal.getOrCreateInstance) {
            bootstrap.Modal.getOrCreateInstance(modalEl).hide();
          }
        } else {
          msgEl.textContent = data.message || 'No se pudo enviar.';
          msgEl.classList.add('text-danger');
          if (typeof Swal !== 'undefined') {
            Swal.fire({
              icon: 'error',
              title: 'Error',
              text: data.message || 'No se pudo enviar el correo.'
            });
          }
        }
      })
      .catch(function () {
        msgEl.textContent = 'Error de red al enviar.';
        msgEl.classList.add('text-danger');
        if (typeof Swal !== 'undefined') {
          Swal.fire({ icon: 'error', title: 'Error', text: 'Error de red.' });
        }
      })
      .finally(function () {
        btnEnviar.disabled = false;
      });
  });
});
