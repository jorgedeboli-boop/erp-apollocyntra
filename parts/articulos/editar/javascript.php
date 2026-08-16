<!-- JAVASCRIPT CUSTOM editar_articulo - editar  -->
<script>
document.addEventListener('DOMContentLoaded', function() {
  // Event listener para tipo de IVA (manejar el estilo visual)
  document.querySelectorAll('input[name="system_codigo_regimen"]').forEach(radio => {
    radio.addEventListener('change', function() {
      // Quitar clase 'checked' de todos los radio buttons de tipo de IVA
      document.querySelectorAll('input[name="system_codigo_regimen"]').forEach(r => {
        const parentOption = r.closest('.custom-option-basic');
        if (parentOption) {
          parentOption.classList.remove('checked');
        }
      });
      
      // Agregar clase 'checked' al seleccionado
      const parentOption = this.closest('.custom-option-basic');
      if (parentOption) {
        parentOption.classList.add('checked');
      }
    });
  });
  
  // Event listener para cancelar artículo
  const btnCancelar = document.getElementById('btn_cancelar_articulo');
  if (btnCancelar) {
    btnCancelar.addEventListener('click', function(e) {
      e.preventDefault();
      
      Swal.fire({
        icon: 'warning',
        title: '¿Cancelar edición?',
        text: '¿Está seguro que desea cancelar? Se perderán los cambios no guardados',
        showCancelButton: true,
        confirmButtonText: 'Sí, cancelar',
        cancelButtonText: 'No, continuar',
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#28a745',
        reverseButtons: true
      }).then((result) => {
        if (result.isConfirmed) {
          const idArticulo = document.querySelector('input[name="id_articulo"]').value;
          if (idArticulo) {
            window.location.href = 'articulo.php?id=' + idArticulo;
          } else {
            window.location.href = 'articulos.php';
          }
        }
      });
    });
  }
  
  // Event listener para volver a artículos (botón del header)
  const btnVolverArticulos = document.getElementById('btn_volver_articulos');
  if (btnVolverArticulos) {
    btnVolverArticulos.addEventListener('click', function(e) {
      e.preventDefault();
      
      Swal.fire({
        icon: 'warning',
        title: '¿Cancelar edición?',
        text: '¿Está seguro que desea cancelar? Se perderán los cambios no guardados',
        showCancelButton: true,
        confirmButtonText: 'Sí, cancelar',
        cancelButtonText: 'No, continuar',
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#28a745',
        reverseButtons: true
      }).then((result) => {
        if (result.isConfirmed) {
          const idArticulo = document.querySelector('input[name="id_articulo"]').value;
          if (idArticulo) {
            window.location.href = 'articulo.php?id=' + idArticulo;
          } else {
            window.location.href = 'articulos.php';
          }
        }
      });
    });
  }
  
  // Interceptar envío del formulario
  const formEditarArticulo = document.getElementById('formEditarArticulo');
  if (formEditarArticulo) {
    formEditarArticulo.addEventListener('submit', function(e) {
      e.preventDefault();
      
      // Obtener datos del formulario
      const formData = new FormData(formEditarArticulo);
      
      // Enviar por AJAX
      fetch('parts/articulos/editar/actualizar_articulo.php', {
        method: 'POST',
        body: formData
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          // Éxito: mostrar mensaje y redirigir
          Swal.fire({
            icon: 'success',
            title: '¡Éxito!',
            text: data.message || 'Artículo actualizado correctamente',
            confirmButtonText: 'Aceptar'
          }).then(() => {
            // Redirigir a articulo.php?id=
            const idArticulo = document.querySelector('input[name="id_articulo"]').value;
            if (idArticulo) {
              window.location.href = 'articulo.php?id=' + idArticulo;
            } else {
              window.location.href = 'articulos.php';
            }
          });
        } else {
          // Error: mostrar mensaje de error
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: data.error || 'Error al actualizar el artículo',
            confirmButtonText: 'Aceptar'
          });
        }
      })
      .catch(error => {
        // Error de red o parsing
        console.error('Error:', error);
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: 'Error al procesar la solicitud. Por favor, intente nuevamente.',
          confirmButtonText: 'Aceptar'
        });
      });
    });
  }
});
</script>
