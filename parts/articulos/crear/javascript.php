<!-- JAVASCRIPT CUSTOM crear_articulo - crear  -->
<script>
document.addEventListener('DOMContentLoaded', function() {
  function enlazarRadiosCustomOption(nombreGrupo) {
    document.querySelectorAll('input[name="' + nombreGrupo + '"]').forEach(function (radio) {
      radio.addEventListener('change', function () {
        document.querySelectorAll('input[name="' + nombreGrupo + '"]').forEach(function (r) {
          const po = r.closest('.custom-option-basic');
          if (po) po.classList.remove('checked');
        });
        const parentOption = this.closest('.custom-option-basic');
        if (parentOption) parentOption.classList.add('checked');
      });
    });
  }
  enlazarRadiosCustomOption('system_codigo_regimen');
  enlazarRadiosCustomOption('tipo_iva_articulo');
  
  // Event listener para cancelar artículo
  const btnCancelar = document.getElementById('btn_cancelar_articulo');
  if (btnCancelar) {
    btnCancelar.addEventListener('click', function(e) {
      e.preventDefault();
      
      Swal.fire({
        icon: 'warning',
        title: '¿Cancelar creación?',
        text: '¿Está seguro que desea cancelar? Se perderán los datos ingresados',
        showCancelButton: true,
        confirmButtonText: 'Sí, cancelar',
        cancelButtonText: 'No, continuar',
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#28a745',
        reverseButtons: true
      }).then((result) => {
        if (result.isConfirmed) {
          window.location.href = 'articulos.php';
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
        title: '¿Cancelar creación?',
        text: '¿Está seguro que desea cancelar? Se perderán los datos ingresados',
        showCancelButton: true,
        confirmButtonText: 'Sí, cancelar',
        cancelButtonText: 'No, continuar',
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#28a745',
        reverseButtons: true
      }).then((result) => {
        if (result.isConfirmed) {
          window.location.href = 'articulos.php';
        }
      });
    });
  }
  
  // Interceptar envío del formulario
  const formCrearArticulo = document.getElementById('formCrearArticulo');
  if (formCrearArticulo) {
    formCrearArticulo.addEventListener('submit', function(e) {
      e.preventDefault();
      
      // Obtener datos del formulario
      const formData = new FormData(formCrearArticulo);
      
      // Enviar por AJAX
      fetch('parts/articulos/crear/insertar_articulo.php', {
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
            text: data.message || 'Artículo creado correctamente',
            confirmButtonText: 'Aceptar'
          }).then(() => {
            // Redirigir a articulo.php?id=
            if (data.id_articulo) {
              window.location.href = 'articulo.php?id=' + data.id_articulo;
            } else {
              window.location.href = 'articulos.php';
            }
          });
        } else {
          // Error: mostrar mensaje de error
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: data.error || data.message || 'Error al crear el artículo',
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
