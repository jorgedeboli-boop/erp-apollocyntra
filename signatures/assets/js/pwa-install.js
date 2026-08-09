// Código para controlar la instalación de la PWA
// Agregar este código en el login.php antes del cierre de </body>

let deferredPrompt;
let installButton = null;

// Capturar el evento beforeinstallprompt
window.addEventListener('beforeinstallprompt', (e) => {
  /*console.log('beforeinstallprompt evento disparado');*/
  
  // Prevenir que Chrome muestre el banner automáticamente
  e.preventDefault();
  
  // Guardar el evento para usarlo después
  deferredPrompt = e;
  
  // Mostrar tu propio botón de instalación (opcional)
  // Si quieres crear un botón personalizado, descomenta las siguientes líneas:
  
  if (!installButton) {
    installButton = document.createElement('button');
    installButton.textContent = 'Instalar App';
    installButton.className = 'btn btn-primary btn-sm';
    installButton.style.cssText = 'position: fixed; bottom: 20px; right: 20px; z-index: 9999;';
    document.body.appendChild(installButton);
    
    installButton.addEventListener('click', async () => {
      if (deferredPrompt) {
        deferredPrompt.prompt();
        const { outcome } = await deferredPrompt.userChoice;
        /*console.log(`Usuario ${outcome === 'accepted' ? 'aceptó' : 'rechazó'} la instalación`);*/
        deferredPrompt = null;
        installButton.remove();
      }
    });
  }

  
  // OPCIÓN ALTERNATIVA: Mostrar el prompt automáticamente después de 3 segundos
  setTimeout(() => {
    if (deferredPrompt) {
      deferredPrompt.prompt();
      deferredPrompt.userChoice.then((choiceResult) => {
        if (choiceResult.outcome === 'accepted') {
          /*console.log('Usuario aceptó la instalación');*/
        } else {
          /*console.log('Usuario rechazó la instalación');*/
        }
        deferredPrompt = null;
      });
    }
  }, 3000);
});

// Detectar cuando la app ya está instalada
window.addEventListener('appinstalled', (evt) => {
  /*console.log('App instalada exitosamente');*/
  if (installButton) {
    installButton.remove();
  }
});

// Registrar Service Worker
if ('serviceWorker' in navigator) {
  window.addEventListener('load', function() {
    navigator.serviceWorker.register('/service-worker.js')
      .then(function(registration) {
        /*console.log('Service Worker registrado con éxito:', registration.scope);*/
      })
      .catch(function(error) {
        /*console.log('Error al registrar Service Worker:', error);*/
      });
  });
}
