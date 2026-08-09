'use strict';

/**
 * Solicita contraseña del usuario para autorizar una acción protegida.
 *
 * @param {number|string} idItem id_type_Item de la acción
 * @param {number|string|function} sucursalOrCallback ID sucursal o callback si se omite sucursal
 * @param {function} [onAutorizado] Callback al autorizar correctamente
 * @returns {Promise<{autorizado: boolean, estado: string, mensaje: string}>}
 */
function solicitarAutorizacionAction(idItem, sucursalOrCallback, onAutorizado) {
  let sucursal = 0;
  let callback = null;

  if (typeof sucursalOrCallback === 'function') {
    callback = sucursalOrCallback;
  } else {
    sucursal = parseInt(sucursalOrCallback, 10) || 0;
    callback = typeof onAutorizado === 'function' ? onAutorizado : null;
  }

  if (!sucursal && typeof window.usuarioSucursal !== 'undefined') {
    sucursal = parseInt(window.usuarioSucursal, 10) || 0;
  }

  const idItemNum = parseInt(idItem, 10) || 0;

  return Swal.fire({
    title: 'Autorización requerida',
    text: 'Para realizar esta acción debe introducir su contraseña',
    input: 'password',
    inputPlaceholder: 'Contraseña',
    inputAttributes: {
      required: 'required',
      autocomplete: 'current-password',
      maxlength: '255'
    },
    showCancelButton: true,
    confirmButtonText: 'Comprobar',
    cancelButtonText: 'Cancelar',
    reverseButtons: true,
    inputValidator: function (value) {
      if (!value) {
        return 'Debe introducir su contraseña';
      }
    }
  }).then(function (result) {
    if (!result.isConfirmed) {
      const cancelado = {
        autorizado: false,
        estado: 'no autorizado',
        mensaje: 'Operación cancelada'
      };
      return cancelado;
    }

    const formData = new FormData();
    formData.append('contrasena', result.value || '');
    formData.append('sucursal', String(sucursal));
    formData.append('id_item', String(idItemNum));

    return fetch('parts/universal/comprobar_contrasena_usuario_autorizado_action.php', {
      method: 'POST',
      body: formData,
      credentials: 'same-origin'
    })
      .then(function (response) {
        return response.json().then(function (data) {
          if (!response.ok && !data) {
            throw new Error('No se pudo comprobar la autorización');
          }
          return data;
        });
      })
      .then(function (data) {
        const autorizado = data.autorizado === 'autorizado' || data.success === true;
        const respuesta = {
          autorizado: autorizado,
          estado: data.autorizado || (autorizado ? 'autorizado' : 'no autorizado'),
          mensaje: data.mensaje || ''
        };

        if (autorizado) {
          if (callback) {
            callback(respuesta);
          }
          return respuesta;
        }

        Swal.fire({
          title: 'No autorizado',
          text: respuesta.mensaje || 'No se pudo autorizar la acción',
          icon: 'error',
          confirmButtonText: 'Aceptar'
        });

        return respuesta;
      })
      .catch(function () {
        Swal.fire({
          title: 'Error',
          text: 'No se pudo comprobar la autorización',
          icon: 'error',
          confirmButtonText: 'Aceptar'
        });

        return {
          autorizado: false,
          estado: 'no autorizado',
          mensaje: 'Error de conexión'
        };
      });
  });
}

window.solicitarAutorizacionAction = solicitarAutorizacionAction;
