/**
 * UI Toasts
 */

'use strict';

document.addEventListener('DOMContentLoaded', function (e) {

  // Custom Notyf class to allow HTML content in messages
  class CustomNotyf extends Notyf {
    _renderNotification(options) {
      const notification = super._renderNotification(options);

      // Replace textContent with innerHTML to render HTML content
      if (options.message) {
        notification.message.innerHTML = options.message;
      }

      return notification;
    }
  }

  // Initialize CustomNotyf instance with default behaviors
  const notyf = new CustomNotyf({
    duration: 8000,
    ripple: true,
    dismissible: true,
    position: { x: 'right', y: 'top' },
    types: [
      {
        type: 'info',
        background: config.colors.info,
        className: 'notyf__info',
        icon: {
          className: 'icon-base ri ri-information-fill icon-md text-white',
          tagName: 'i'
        }
      },
      {
        type: 'warning',
        background: config.colors.warning,
        className: 'notyf__warning',
        icon: {
          className: 'icon-base ri ri-alert-fill icon-md text-white',
          tagName: 'i'
        }
      },
      {
        type: 'success',
        background: config.colors.success,
        className: 'notyf__success',
        icon: {
          className: 'icon-base ri ri-checkbox-circle-fill icon-md text-white',
          tagName: 'i'
        }
      },
      {
        type: 'error',
        background: config.colors.danger,
        className: 'notyf__error',
        icon: {
          className: 'icon-base ri ri-close-circle-fill icon-md text-white',
          tagName: 'i'
        }
      },
      {
        type: 'secondary',
        background: '#6d788d',
        className: 'notyf__secondary',
        icon: {
          className: 'icon-base ri ri-wifi-off-line icon-md text-white',
          tagName: 'i'
        }
      }
    ]
  });

  // =========================================
  // Demo UI (solo si existen los elementos)
  // =========================================

  // Initialize message index
  let i = -1;

  // Function to cycle through messages
  const getMessage = function () {
    const msgs = [
      "Don't be pushed around by the fears in your mind. Be led by the dreams in your heart.",
      '<div class="mb-3"><input class="input-small form-control" value="Textbox"/>&nbsp;<a href="http://example.com" target="_blank" class="text-white">This is a hyperlink</a></div><div class="d-flex"><button type="button" id="okBtn" class="btn btn-primary btn-sm me-2">Close me</button><button type="button" id="surpriseBtn" class="btn btn-sm btn-secondary">Surprise me</button></div>',
      'Live the Life of Your Dreams',
      'Believe in Yourself!',
      'Be mindful. Be grateful. Be positive.',
      'Accept yourself, love yourself!'
    ];
    i = (i + 1) % msgs.length;
    return msgs[i];
  };

  const showNotificationBtn = document.getElementById('showNotification');
  const clearNotificationsBtn = document.getElementById('clearNotifications');

  if (showNotificationBtn && clearNotificationsBtn) {
    // Event listener for Show Notification button
    showNotificationBtn.addEventListener('click', () => {

      const message = document.getElementById('message').value || getMessage(); // Use getMessage() to get the next message
      const dismissible = document.getElementById('dismissible').checked;
      const ripple = document.getElementById('ripple').checked;
      const durationInput = document.getElementById('duration').value;
      const duration = durationInput ? parseInt(durationInput) : 3000;

      // Get selected position
      const positionYValue = document.querySelector('input[name="positiony"]:checked').value;
      const positionXValue = document.querySelector('input[name="positionx"]:checked').value;
      const position = { x: positionXValue, y: positionYValue };

      // Get selected notification type
      const type = document.querySelector('input[name="notificationType"]:checked').value;

      // Build the notification options
      const notificationOptions = {
        type: type,
        message: message,
        duration: duration,
        dismissible: dismissible,
        ripple: ripple,
        position: position
      };

      // Display notification and get the reference
      attachNotificationEventListeners();
      notyf.open(notificationOptions);

    });

    // Event listener for Clear Notifications button
    clearNotificationsBtn.addEventListener('click', () => {
      notyf.dismissAll();
    });

    // Function to attach event listeners to elements inside the notification
    function attachNotificationEventListeners() {
      // Wait for the DOM to update
      setTimeout(() => {
        const okBtn = document.getElementById('okBtn');
        const surpriseBtn = document.getElementById('surpriseBtn');

        if (okBtn) {
          okBtn.addEventListener('click', () => {
            notyf.dismissAll(); // Close all notifications
          });
        }

        if (surpriseBtn) {
          surpriseBtn.addEventListener('click', () => {
            notyf.success('Surprise! This is a new message.');
          });
        }
      }, 100);
    }
  }

  // =========================================
  // Sistema global de notificaciones
  // =========================================

  function mapColorToType(color) {
    switch (color) {
      case 'Success':
        return 'success';
      case 'Error':
        return 'error';
      case 'Info':
        return 'info';
      case 'Warning':
        return 'warning';
      case 'Secondary':
        return 'secondary';
      default:
        return 'info';
    }
  }

  function mostrarNotificacionSistema(notificacion) {
    const type = mapColorToType(notificacion.color_notificacion);

    // Construir mensaje (permitimos HTML gracias a CustomNotyf)
    let mensaje = notificacion.mensaje_notificacion || '';

    if (notificacion.url_notificacion) {
      // Añadir enlace si viene URL
      mensaje += `<br><a href="${notificacion.url_notificacion}" class="text-white text-decoration-underline" target="_self">Ver más</a>`;
    }

    notyf.open({
      type: type,
      message: mensaje
    });
  }

  function consultarNotificacionesPendientes() {
    /*console.log('Consultando notificaciones pendientes');*/
    fetch('parts/universal/notificaciones_pendientes.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded'
      },
      body: ''
    })
      .then(response => response.json())
      .then(data => {
        console.log('Notificaciones pendientes:', data);
        if (!data.success || !Array.isArray(data.notificaciones)) {
          return;
        }

        data.notificaciones.forEach(notificacion => {
          mostrarNotificacionSistema(notificacion);
        });
      })
      .catch(error => {
        console.error('Error al consultar notificaciones:', error);
      });
  }

  // Primera consulta y polling cada 5 segundos
  consultarNotificacionesPendientes();
  setInterval(consultarNotificacionesPendientes, 10000);

  // API global para lanzar toasts manualmente desde cualquier script/evento
  window.mostrarNotificacionSistema = mostrarNotificacionSistema;
  window.mostrarNotificacion = function (opciones) {
    const opts = opciones || {};
    mostrarNotificacionSistema({
      mensaje_notificacion: opts.mensaje || opts.message || '',
      color_notificacion: opts.color || opts.tipo || 'Info',
      url_notificacion: opts.url || ''
    });
  };
});
