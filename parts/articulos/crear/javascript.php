<!-- JAVASCRIPT CUSTOM crear_articulo - crear  -->
<script>
document.addEventListener('DOMContentLoaded', function() {
  // Inicializar Select2 para ley oro (por defecto visible)
  if ($('#leyoro').length) {
    $('#leyoro').select2({
      dropdownParent: $('#leyoro').closest('.form-floating'),
      placeholder: 'Seleccionar...',
      allowClear: false,
      width: '100%'
    });
  }
  
  // Inicializar Select2 para ley plata (oculto por defecto)
  if ($('#leyplata').length) {
    $('#leyplata').select2({
      dropdownParent: $('#leyplata').closest('.form-floating'),
      placeholder: 'Seleccionar...',
      allowClear: false,
      width: '100%'
    });
  }
  
  // Event listener para inscripciones (manejar el estilo visual)
  document.querySelectorAll('input[name="inscripciones"]').forEach(radio => {
    radio.addEventListener('change', function() {
      // Quitar clase 'checked' de todos los radio buttons de inscripciones
      document.querySelectorAll('input[name="inscripciones"]').forEach(r => {
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
  
  // Event listener para piedras (manejar el estilo visual)
  document.querySelectorAll('input[name="piedras"]').forEach(radio => {
    radio.addEventListener('change', function() {
      // Quitar clase 'checked' de todos los radio buttons de piedras
      document.querySelectorAll('input[name="piedras"]').forEach(r => {
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
  
  // Event listener para tipo de artículo (manejar el estilo visual y mostrar/ocultar selects de ley)
  document.querySelectorAll('input[name="tipo_articulo"]').forEach(radio => {
    radio.addEventListener('change', function() {
      // Quitar clase 'checked' de todos los radio buttons de tipo de artículo
      document.querySelectorAll('input[name="tipo_articulo"]').forEach(r => {
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
      
      // Mostrar/ocultar selects de ley según el tipo seleccionado
      const tipoSeleccionado = this.value;
      const containerLeyOro = document.getElementById('container_ley_oro');
      const containerLeyPlata = document.getElementById('container_ley_plata');
      const selectLeyOro = document.getElementById('leyoro');
      const selectLeyPlata = document.getElementById('leyplata');
      
      if (tipoSeleccionado === 'oro') {
        // Mostrar ley oro, ocultar ley plata
        if (containerLeyOro) containerLeyOro.style.display = 'block';
        if (containerLeyPlata) containerLeyPlata.style.display = 'none';
        if (selectLeyOro) {
          selectLeyOro.setAttribute('required', 'required');
          selectLeyOro.setAttribute('name', 'ley');
        }
        if (selectLeyPlata) {
          selectLeyPlata.removeAttribute('required');
          selectLeyPlata.removeAttribute('name');
        }
      } else if (tipoSeleccionado === 'plata') {
        // Mostrar ley plata, ocultar ley oro
        if (containerLeyOro) containerLeyOro.style.display = 'none';
        if (containerLeyPlata) containerLeyPlata.style.display = 'block';
        if (selectLeyOro) {
          selectLeyOro.removeAttribute('required');
          selectLeyOro.removeAttribute('name');
        }
        if (selectLeyPlata) {
          selectLeyPlata.setAttribute('required', 'required');
          selectLeyPlata.setAttribute('name', 'ley');
        }
      } else if (tipoSeleccionado === 'acero') {
        // Para acero, ocultar ambos (o mostrar uno específico si es necesario)
        if (containerLeyOro) containerLeyOro.style.display = 'none';
        if (containerLeyPlata) containerLeyPlata.style.display = 'none';
        if (selectLeyOro) {
          selectLeyOro.removeAttribute('required');
          selectLeyOro.removeAttribute('name');
        }
        if (selectLeyPlata) {
          selectLeyPlata.removeAttribute('required');
          selectLeyPlata.removeAttribute('name');
        }
      }
    });
    
    // Ejecutar al cargar la página para establecer el estado inicial (Oro por defecto)
    if (radio.checked && radio.value === 'oro') {
      radio.dispatchEvent(new Event('change'));
    }
  });
  
  // Asegurar que al cargar la página, si Oro está seleccionado, se muestre leyoro
  const tipoOro = document.querySelector('input[name="tipo_articulo"][value="oro"]');
  if (tipoOro && tipoOro.checked) {
    tipoOro.dispatchEvent(new Event('change'));
  }

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
            text: data.error || 'Error al crear el artículo',
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
