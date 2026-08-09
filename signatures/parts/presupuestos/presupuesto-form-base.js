/**
 * Formulario presupuesto (cliente + artículos por SKU + modal).
 * Copia independiente de parts/ventas/crear/nueva-venta.js; si cambia la lógica común,
 * actualizar también nueva-venta.js o este fichero según convenga.
 */

'use strict';

document.addEventListener('DOMContentLoaded', function() {
  const cuerpoVenta = document.getElementById('cuerpo_venta');
  const invoiceActions = document.getElementById('invoice_actions');
  
  if (cuerpoVenta) {
    cuerpoVenta.classList.add('formulario-borroso');
  }
  
  if (invoiceActions) {
    invoiceActions.classList.add('formulario-borroso');
  }

  // Obtener id_sucursal del input hidden (recibido por POST)
  const inputSucursal = document.getElementById('sucursal_venta');
  if (inputSucursal && inputSucursal.value) {
    // Actualizar input hidden de sucursal en el formulario de INSERT
    $('#insert_id_sucursal').val(inputSucursal.value);
    
    // Mostrar datos de la venta automáticamente
    mostrarDatosVenta();
  }
  
  // Obtener id_articulo del input hidden (recibido por POST, opcional)
  const inputArticulo = document.getElementById('articulo_venta');
  if (inputArticulo && inputArticulo.value) {
    const idArticulo = inputArticulo.value.trim();
    if (idArticulo) {
      // Esperar un momento para que se inicialice todo y luego buscar el artículo
      setTimeout(() => {
        if (typeof window.buscarArticuloPorSku === 'function') {
          window.buscarArticuloPorSku(idArticulo);
        }
      }, 500);
    }
  }
  // Array para almacenar los artículos agregados
  window.articulosVenta = [];
  
  // Configurar búsqueda en el input de SKU al cargar
  configurarBusquedaArticulo();
  
  // Event delegation para botones de la tabla
  const tablaArticulos = document.getElementById('tabla_articulos_venta');
  if (tablaArticulos) {
    tablaArticulos.addEventListener('click', function(e) {
      const target = e.target.closest('a');
      if (!target) return;
      
      if (target.classList.contains('delete-record')) {
        e.preventDefault();
        const tr = target.closest('tr');
        eliminarFilaArticulo(tr);
      } else if (target.classList.contains('edit-record')) {
        e.preventDefault();
        const tr = target.closest('tr');
        editarArticulo(tr);
      } else if (target.classList.contains('discount-record')) {
        e.preventDefault();
        const tr = target.closest('tr');
        solicitarAutorizacionPrecio(tr);
      }
    });
  }
  
  // Event listener para tipo de venta (manejar el estilo visual y mostrar/ocultar tipo_pago_plazos)
  const tipoPagoPlazos = document.getElementById('tipo_pago_plazos');
  
  document.querySelectorAll('input[name="tipo_venta"]').forEach(radio => {
    radio.addEventListener('change', function() {
      // Quitar clase 'checked' de todos
      document.querySelectorAll('.custom-option-basic').forEach(option => {
        option.classList.remove('checked');
      });
      
      // Agregar clase 'checked' al seleccionado
      const parentOption = this.closest('.custom-option-basic');
      if (parentOption) {
        parentOption.classList.add('checked');
      }
      
      // Mostrar u ocultar tipo_pago_plazos según el tipo de venta
      if (this.value === 'plazos') {
        if (tipoPagoPlazos) {
          tipoPagoPlazos.style.display = 'block';
          
          // Marcar el radio button numero_plazos_3 como checked
          const numeroPlazos3 = document.getElementById('numero_plazos_3');
          if (numeroPlazos3) {
            numeroPlazos3.checked = true;
            // Quitar clase 'checked' de todos los radio buttons de numero_plazos
            document.querySelectorAll('input[name="numero_plazos"]').forEach(r => {
              const parentOption = r.closest('.custom-option-basic');
              if (parentOption) {
                parentOption.classList.remove('checked');
              }
            });
            // Agregar clase 'checked' al contenedor del radio seleccionado
            const parentOption = numeroPlazos3.closest('.custom-option-basic');
            if (parentOption) {
              parentOption.classList.add('checked');
            }
          }
          
          // Actualizar información de plazos
          if (window.actualizarPlazosInfo) {
            window.actualizarPlazosInfo();
          }
        }
      } else {
        if (tipoPagoPlazos) {
          tipoPagoPlazos.style.display = 'none';
        }
        // Ocultar información de plazos
        const plazosInfo = document.getElementById('plazos_venta_info');
        if (plazosInfo) {
          plazosInfo.style.display = 'none';
        }
      }
    });
  });
  
  // Asegurar que al cargar la página, si está seleccionado "normal", el div esté oculto
  const tipoVentaNormal = document.getElementById('tipo_venta_normal');
  if (tipoVentaNormal && tipoVentaNormal.checked && tipoPagoPlazos) {
    tipoPagoPlazos.style.display = 'none';
  }
  
  // Ocultar información de plazos por defecto si el tipo de venta es normal
  const plazosInfo = document.getElementById('plazos_venta_info');
  if (tipoVentaNormal && tipoVentaNormal.checked && plazosInfo) {
    plazosInfo.style.display = 'none';
  }
  
  // Inicializar estado visual de los radio buttons marcados por defecto al cargar la página
  // Asegurar que tipo_venta_normal tenga la clase checked en su contenedor
  if (tipoVentaNormal && tipoVentaNormal.checked) {
    const parentOptionNormal = tipoVentaNormal.closest('.custom-option-basic');
    if (parentOptionNormal) {
      parentOptionNormal.classList.add('checked');
    }
  }
  
  // Asegurar que venta_forma_de_pago_contado tenga la clase checked en su contenedor
  const formaPagoContado = document.getElementById('venta_forma_de_pago_contado');
  if (formaPagoContado && formaPagoContado.checked) {
    const parentOptionContado = formaPagoContado.closest('.custom-option-basic');
    if (parentOptionContado) {
      parentOptionContado.classList.add('checked');
    }
  }
  
  // Event listener para forma de pago (manejar el estilo visual)
  document.querySelectorAll('input[name="forma_pago"]').forEach(radio => {
    radio.addEventListener('change', function() {
      // Quitar clase 'checked' de todos los radio buttons de forma de pago
      document.querySelectorAll('.forma_pago_venta').forEach(r => {
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
  
  // Event listener para número de plazos (manejar el estilo visual y actualizar información)
  document.querySelectorAll('input[name="numero_plazos"]').forEach(radio => {
    radio.addEventListener('change', function() {
      // Quitar clase 'checked' de todos los radio buttons de numero_plazos
      document.querySelectorAll('input[name="numero_plazos"]').forEach(r => {
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
      
      // Actualizar información de plazos
      if (window.actualizarPlazosInfo) {
        window.actualizarPlazosInfo();
      }
    });
  });
  
  // Función para calcular y mostrar información de plazos
  window.actualizarPlazosInfo = function() {
    const tipoVentaPlazos = document.getElementById('tipo_venta_plazos');
    const plazosInfo = document.getElementById('plazos_venta_info');
    
    // Solo actualizar si está seleccionado tipo plazos
    if (!tipoVentaPlazos || !tipoVentaPlazos.checked || !plazosInfo) {
      if (plazosInfo) {
        plazosInfo.style.display = 'none';
      }
      return;
    }
    
    // Obtener número de plazos seleccionado
    const numeroPlazosRadio = document.querySelector('input[name="numero_plazos"]:checked');
    if (!numeroPlazosRadio) {
      if (plazosInfo) {
        plazosInfo.style.display = 'none';
      }
      return;
    }
    
    const numeroPlazos = parseInt(numeroPlazosRadio.value);
    
    // Obtener total de la venta
    let subtotal = 0;
    if (window.articulosVenta && window.articulosVenta.length > 0) {
      window.articulosVenta.forEach(articulo => {
        subtotal += parseFloat(articulo.precio);
      });
    }
    
    const iva = 0; // Sin IVA por ahora
    const total = subtotal + iva;
    
    if (total <= 0) {
      plazosInfo.textContent = 'Sin artículos!';
      plazosInfo.style.display = 'block';
      return;
    }
    
    // Calcular importe por cuota
    const importePorCuota = total / numeroPlazos;
    
    // Mostrar información
    plazosInfo.textContent = numeroPlazos + ' pagos de ' + number_format(importePorCuota, 2) + ' €';
    plazosInfo.style.display = 'block';
  }
  
  // Función para calcular totales
  window.calcularTotales = function() {
    // Asegurar que el array esté inicializado
    if (!window.articulosVenta) {
      window.articulosVenta = [];
    }
    
    let subtotal = 0;
    
    // Calcular subtotal solo si hay artículos
    if (window.articulosVenta && window.articulosVenta.length > 0) {
      window.articulosVenta.forEach(articulo => {
        const precio = parseFloat(articulo.precio) || 0;
        subtotal += precio;
      });
    }
    
    const iva = 0; // Sin IVA por ahora
    const total = subtotal + iva;
    
    // Actualizar UI
    const subtotalElement = document.getElementById('subtotal_venta');
    const ivaElement = document.getElementById('iva_venta');
    const totalElement = document.getElementById('total_venta');
    const totalHeaderElement = document.getElementById('total_venta_header');
    const totalResumenElement = document.getElementById('total_resumen');
    
    if (subtotalElement) subtotalElement.textContent = number_format(subtotal, 2) + ' €';
    if (ivaElement) ivaElement.textContent = number_format(iva, 2) + ' €';
    if (totalElement) totalElement.textContent = number_format(total, 2) + ' €';
    if (totalHeaderElement) totalHeaderElement.textContent = number_format(total, 2) + ' €';
    if (totalResumenElement) totalResumenElement.textContent = number_format(total, 2) + ' €';
    
    // Actualizar resumen
    const totalArticulos = window.articulosVenta ? window.articulosVenta.length : 0;
    const pesoTotal = (window.articulosVenta && window.articulosVenta.length > 0) 
      ? window.articulosVenta.reduce((sum, art) => sum + parseFloat(art.peso || 0), 0)
      : 0;
    
    const totalArticulosElement = document.getElementById('total_articulos_resumen');
    const pesoTotalElement = document.getElementById('peso_total_resumen');
    
    if (totalArticulosElement) totalArticulosElement.textContent = totalArticulos;
    if (pesoTotalElement) pesoTotalElement.textContent = number_format(pesoTotal, 2) + ' g';
    
    // Actualizar información de plazos si está seleccionado tipo plazos
    if (window.actualizarPlazosInfo) {
      window.actualizarPlazosInfo();
    }
  }
  
  // Función para formatear números
  window.number_format = function(number, decimals) {
    return parseFloat(number).toFixed(decimals).replace(/\d(?=(\d{3})+\.)/g, '$&,').replace('.', ',');
  }
  
  // Event listener para guardar venta
  const btnGuardar = document.getElementById('btn_guardar_venta');
  if (btnGuardar) {
    btnGuardar.addEventListener('click', function() {
      // Validaciones
      if (window.articulosVenta.length === 0) {
        Swal.fire({
          icon: 'warning',
          title: 'Atención',
          text: 'Debe agregar al menos un artículo a la venta'
        });
        return;
      }
      
      const formaPago = document.querySelector('input[name="forma_pago"]:checked');
      if (!formaPago || !formaPago.value) {
        Swal.fire({
          icon: 'warning',
          title: 'Atención',
          text: 'Debe seleccionar una forma de pago'
        });
        return;
      }
      
      // TODO: Implementar guardado de venta
      Swal.fire({
        icon: 'info',
        title: 'Función pendiente',
        text: 'El guardado de ventas se implementará en el siguiente paso'
      });
    });
  }
  
  // Event listener para imprimir ticket
  const btnImprimir = document.getElementById('btn_imprimir_ticket');
  if (btnImprimir) {
    btnImprimir.addEventListener('click', function() {
      // TODO: Implementar impresión de ticket
      Swal.fire({
        icon: 'info',
        title: 'Función pendiente',
        text: 'La impresión de ticket se implementará posteriormente'
      });
    });
  }
  
  // Event listener para cancelar venta
  const btnCancelar = document.getElementById('btn_cancelar_venta');
  if (btnCancelar) {
    btnCancelar.addEventListener('click', function(e) {
      e.preventDefault(); // Prevenir la navegación inmediata
      
      Swal.fire({
        icon: 'warning',
        title: '¿Cancelar venta?',
        text: '¿Está seguro que desea cancelar esta venta? Se perderán los datos',
        showCancelButton: true,
        confirmButtonText: 'Sí, cancelar',
        cancelButtonText: 'No, continuar',
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#28a745',
        reverseButtons: true
      }).then((result) => {
        if (result.isConfirmed) {
          // Si confirma, redirigir a ventas.php
          window.location.href = 'ventas.php';
        }
        // Si cancela, no hacer nada (el preventDefault ya evitó la navegación)
      });
    });
  }
  
  // Event listener para volver a ventas (botón del header)
  const btnVolverVentas = document.getElementById('btn_volver_ventas');
  if (btnVolverVentas) {
    btnVolverVentas.addEventListener('click', function(e) {
      e.preventDefault(); // Prevenir la navegación inmediata
      
      Swal.fire({
        icon: 'warning',
        title: '¿Cancelar venta?',
        text: '¿Está seguro que desea cancelar esta venta? Se perderán los datos',
        showCancelButton: true,
        confirmButtonText: 'Sí, cancelar',
        cancelButtonText: 'No, continuar',
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#28a745',
        reverseButtons: true
      }).then((result) => {
        if (result.isConfirmed) {
          // Si confirma, redirigir a ventas.php
          window.location.href = 'ventas.php';
        }
        // Si cancela, no hacer nada (el preventDefault ya evitó la navegación)
      });
    });
  }
  
  // Inicializar totales
  window.calcularTotales();
  
  // Inicializar Select2 para el modal de datos del cliente
  const modalDatosCliente = document.getElementById('datos_cliente');
  if (modalDatosCliente) {
    modalDatosCliente.addEventListener('shown.bs.modal', function () {
      inicializarSelectsModalCliente();
      configurarVerificacionCliente();
    });
  }
  
  // Event listener para el formulario de datos del cliente
  const formDatosCliente = document.getElementById('form_datos_cliente');
  if (formDatosCliente) {
    formDatosCliente.addEventListener('submit', function(e) {
      e.preventDefault();
      guardarDatosCliente();
    });
  }
  
  // Event listener para guardar tipo de identificación cuando cambia
  $(document).on('change', '#modal_tipo_identificacion', function() {
    const tipoId = $(this).val();
    const tipoTexto = $(this).find('option:selected').text();
    
    if (tipoId) {
      $('#modal_tipo_identificacion').attr('data-selected-id', tipoId);
      $('#modal_tipo_identificacion').attr('data-selected-text', tipoTexto);
    } else {
      $('#modal_tipo_identificacion').removeAttr('data-selected-id');
      $('#modal_tipo_identificacion').removeAttr('data-selected-text');
    }
  });
  
  // Event listeners para cambios en país y provincia (adaptado de javascript_direcciones.js)
  $(document).on('change', '#modal_pais', function() {
    const paisId = $(this).val();
    const paisTexto = $(this).find('option:selected').text();
    
    // Guardar en data attributes
    if (paisId) {
      $('#modal_pais').attr('data-selected-id', paisId);
      $('#modal_pais').attr('data-selected-text', paisTexto);
    } else {
      $('#modal_pais').removeAttr('data-selected-id');
      $('#modal_pais').removeAttr('data-selected-text');
    }
    
    // Limpiar selects dependientes
    $('#modal_c_provincia').val(null).trigger('change');
    $('#modal_c_poblacion').val(null).trigger('change');
    $('#modal_codigo_postal').val('');
    
    // Limpiar data attributes de dependientes
    $('#modal_c_provincia').removeAttr('data-selected-id').removeAttr('data-selected-text');
    $('#modal_c_poblacion').removeAttr('data-selected-id').removeAttr('data-selected-text');
  });
  
  $(document).on('change', '#modal_c_provincia', function() {
    const provinciaId = $(this).val();
    const provinciaTexto = $(this).find('option:selected').text();
    
    // Guardar en data attributes
    if (provinciaId) {
      $('#modal_c_provincia').attr('data-selected-id', provinciaId);
      $('#modal_c_provincia').attr('data-selected-text', provinciaTexto);
    } else {
      $('#modal_c_provincia').removeAttr('data-selected-id');
      $('#modal_c_provincia').removeAttr('data-selected-text');
    }
    
    // Limpiar select de población y código postal
    $('#modal_c_poblacion').val(null).trigger('change');
    $('#modal_codigo_postal').val('');
    
    // Limpiar data attributes de población
    $('#modal_c_poblacion').removeAttr('data-selected-id').removeAttr('data-selected-text');
  });
  
  // Event listener para actualizar código postal cuando se selecciona población
  $(document).on('change', '#modal_c_poblacion', function() {
    const idPoblacion = $(this).val();
    const poblacionTexto = $(this).find('option:selected').text();
    
    // Guardar en data attributes
    if (idPoblacion) {
      $('#modal_c_poblacion').attr('data-selected-id', idPoblacion);
      $('#modal_c_poblacion').attr('data-selected-text', poblacionTexto);
      
      // Usar la misma lógica de javascript_direcciones.js para obtener código postal
      $.ajax({
        url: 'parts/universal/ajax_poblaciones.php',
        dataType: 'json',
        data: {
          action: 'poblacion_detalle',
          idpoblacion: idPoblacion
        },
        success: function(response) {
          if (response.success && response.data) {
            // Asignar código postal automáticamente
            $('#modal_codigo_postal').val(response.data.codigo_postal || '');
          }
        },
        error: function() {
          console.error('Error al obtener detalles de población');
        }
      });
    } else {
      $('#modal_c_poblacion').removeAttr('data-selected-id');
      $('#modal_c_poblacion').removeAttr('data-selected-text');
      $('#modal_codigo_postal').val('');
    }
  });
});

function mostrarDatosVenta() {
  const inputSucursal = document.getElementById('sucursal_venta');
  const divDatosVenta = document.getElementById('cuerpo_venta');
  const divInvoiceActions = document.getElementById('invoice_actions');
  const nombreSucursal = document.getElementById('nombre_sucursal');
  
  if (!inputSucursal || !divDatosVenta || !divInvoiceActions) {
    return;
  }
  
  if (inputSucursal.value) {
      // El nombre de la sucursal ya está en el HTML, no necesitamos actualizarlo
      // Actualizar input hidden de sucursal
      $('#insert_id_sucursal').val(inputSucursal.value);

      // Cargar datos de la empresa
      cargarDatosEmpresa(inputSucursal.value);
      // Cargar datos de la sucursal
      cargarDatosSucursal(inputSucursal.value);

      divDatosVenta.classList.remove('formulario-borroso');
      divInvoiceActions.classList.remove('formulario-borroso');
  } else {
      $('#insert_id_sucursal').val('');
      
      divDatosVenta.classList.add('formulario-borroso');
      divInvoiceActions.classList.add('formulario-borroso');
  }
}

/**
 * Cargar datos de la empresa según la sucursal seleccionada
 */
function cargarDatosEmpresa(idSucursal) {
  $.ajax({
    url: 'parts/ventas/crear/get_empresa_sucursal.php',
    data: {
      id_sucursal: idSucursal
    },
    dataType: 'json',
    success: function(response) {
      if (response.success && response.empresa) {
        const empresa = response.empresa;
        
        // Actualizar datos de la empresa en el formulario
        $('#nombre_empresa').text(empresa.nombre_empresa || '-');
        $('#cif_empresa').text('CIF: ' + (empresa.cif_empresa || '-'));
        $('#email_empresa').text(empresa.email_empresa || '-');
      } else {
        // Si hay error, mostrar valores por defecto
        $('#nombre_empresa').text('-');
        $('#cif_empresa').text('-');
        $('#email_empresa').text('-');
      }
    },
    error: function(xhr, status, error) {
      // Si hay error, mostrar valores por defecto
      $('#nombre_empresa').text('-');
      $('#cif_empresa').text('-');
      $('#email_empresa').text('-');
    }
  });
}

/**
 * Cargar datos de la sucursal
 */
function cargarDatosSucursal(idSucursal) {
  $.ajax({
    url: 'parts/ventas/crear/get_sucursal.php',
    data: {
      id_sucursal: idSucursal
    },
    dataType: 'json',
    success: function(response) {
      if (response.success && response.sucursal) {
        const sucursal = response.sucursal;
        
        // Actualizar datos de la sucursal en el formulario
        $('#direccion_sucursal').text(sucursal.direccion_tienda || '-');
        $('#poblacion_sucursal').text(sucursal.poblacion_tienda || '-');
        $('#codigo_postal_sucursal').text(sucursal.codigo_postal_tienda || '-');
        $('#telefono_sucursal').text(sucursal.telefono_tienda || '-');
      } else {
        // Si hay error, mostrar valores por defecto
        $('#direccion_sucursal').text('-');
        $('#poblacion_sucursal').text('-');
        $('#codigo_postal_sucursal').text('-');
        $('#telefono_sucursal').text('-');
      }
    },
    error: function(xhr, status, error) {
      // Si hay error, mostrar valores por defecto
      $('#direccion_sucursal').text('-');
      $('#poblacion_sucursal').text('-');
      $('#codigo_postal_sucursal').text('-');
      $('#telefono_sucursal').text('-');
    }
  });
}

// ============================================
// GESTIÓN DE ARTÍCULOS EN LA TABLA
// ============================================

/**
 * Añadir una fila nueva para agregar artículo
 */
function anadirFilaArticulo() {
  const inputSku = document.querySelector('.input-sku-articulo');
  
  // Limpiar el input y poner foco
  if (inputSku) {
    inputSku.value = '';
    setTimeout(() => {
      inputSku.focus();
    }, 100);
  }
  
  // Limpiar mensaje de "no hay artículos" si existe
  const tbody = document.getElementById('articulos_venta_body');
  const mensajeVacio = tbody.querySelector('td[colspan="5"]');
  if (mensajeVacio) {
    mensajeVacio.parentElement.remove();
  }
  
  // Configurar búsqueda en el input de SKU
  configurarBusquedaArticulo();
}

/**
 * Configurar búsqueda de artículo en el input de SKU
 */
let timeoutBusquedaArticulo = null;

function configurarBusquedaArticulo() {
  const inputSku = document.querySelector('.input-sku-articulo');
  
  if (!inputSku) return;
  
  // Remover event listeners anteriores si existen
  const newInputSku = inputSku.cloneNode(true);
  inputSku.parentNode.replaceChild(newInputSku, inputSku);
  
  // Solo búsqueda por SKU
  newInputSku.addEventListener('input', function() {
    clearTimeout(timeoutBusquedaArticulo);
    const sku = this.value.trim();
    
    // Si el input está vacío o tiene menos de 3 caracteres, no hacer nada
    if (sku.length >= 3) {
      timeoutBusquedaArticulo = setTimeout(() => {
        if (typeof window.buscarArticuloPorSku === 'function') {
          window.buscarArticuloPorSku(sku);
        }
      }, 600);
    }
  });
  
  // Evento blur para actualizar el número cuando pierde el foco
  // Solo buscar si no se está añadiendo un artículo
  newInputSku.addEventListener('blur', function() {
    // Si se está añadiendo un artículo, no hacer nada
    if (anadiendoArticulo) {
      return;
    }
    
    const sku = this.value.trim();
    
    // Si hay un valor, actualizar el input con el valor limpio
    if (sku) {
      this.value = sku;
      // Si tiene al menos 3 caracteres, buscar de nuevo
      if (sku.length >= 3) {
        if (typeof window.buscarArticuloPorSku === 'function') {
          window.buscarArticuloPorSku(sku);
        }
      }
    }
  });
  
}

/**
 * Buscar artículo por SKU
 */
function buscarArticuloPorSku(sku) {
  const idSucursal = $('#insert_id_sucursal').val();
  
  if (!idSucursal) {
    Swal.fire({
      icon: 'warning',
      title: 'Atención',
      text: 'Debe seleccionar una sucursal primero',
      timer: 3000
    });
    return;
  }
  
  $.ajax({
    url: 'parts/ventas/crear/buscar_articulo.php',
    data: {
      sku: sku,
      id_sucursal: idSucursal
    },
    dataType: 'json',
    success: function(response) {
      if (response.success && response.encontrado) {
        // Verificar si el artículo ya existe en el array antes de crear la fila
        const articuloExistente = window.articulosVenta.find(art => 
          art.id_articulo == response.articulo.id || art.sku == sku
        );
        
        if (articuloExistente) {
          // El artículo ya está en la lista, mostrar mensaje y limpiar input
          Swal.fire({
            icon: 'warning',
            title: 'Artículo duplicado',
            text: 'Este artículo ya ha sido agregado a la venta',
            timer: 3000
          }).then(() => {
            // Limpiar el input de SKU cuando se cierre el mensaje
            const inputSku = document.querySelector('.input-sku-articulo');
            if (inputSku) {
              inputSku.value = '';
              inputSku.focus();
            }
          });
          return;
        }
        
        // Si no existe, agregar automáticamente a la tabla
        agregarArticuloAutomaticamente(response.articulo, sku);
      }
    },
    error: function(xhr, status, error) {
      console.error('Error AJAX al buscar artículo:', error);
      console.error('Status:', status);
      console.error('Response:', xhr.responseText);
    }
  });
}

window.buscarArticuloPorSku = buscarArticuloPorSku;

/**
 * Agregar artículo automáticamente cuando se encuentra
 */
function agregarArticuloAutomaticamente(articulo, sku) {
  // Verificar que no se esté procesando ya
  if (anadiendoArticulo) {
    return;
  }
  
  // Marcar como en proceso
  anadiendoArticulo = true;
  
  // Crear objeto artículo
  const articuloParaAgregar = {
    id_articulo: parseInt(articulo.id),
    sku: String(sku).trim(),
    descripcion: articulo.descripcion,
    unidades: 1,
    peso: articulo.peso,
    tipo: articulo.tipo,
    precio: articulo.precio
  };
  
  // Verificar si el artículo ya existe en el array
  const articuloExistente = window.articulosVenta.find(art => {
    const idArticulo = parseInt(art.id_articulo);
    const skuArt = String(art.sku).trim();
    return idArticulo === articuloParaAgregar.id_articulo || skuArt === articuloParaAgregar.sku;
  });
  
  if (articuloExistente) {
    anadiendoArticulo = false;
    Swal.fire({
      icon: 'warning',
      title: 'Artículo duplicado',
      text: 'Este artículo ya ha sido agregado a la venta',
      timer: 3000
    }).then(() => {
      const inputSku = document.querySelector('.input-sku-articulo');
      if (inputSku) {
        inputSku.value = '';
        inputSku.focus();
      }
    });
    return;
  }
  
  // Agregar al array
  window.articulosVenta.push(articuloParaAgregar);
  
  // Actualizar inputs hidden de artículos
  actualizarInputsArticulos();
  
  // Limpiar mensaje de "no hay artículos" si existe
  const tbody = document.getElementById('articulos_venta_body');
  const mensajeVacio = tbody.querySelector('td[colspan="5"]');
  if (mensajeVacio) {
    mensajeVacio.parentElement.remove();
  }
  
  // Crear fila en la tabla
  const tr = document.createElement('tr');
  tr.className = 'fila-guardada';
  tr.dataset.index = window.articulosVenta.length - 1;
  // Guardar ID y SKU para poder eliminar correctamente
  tr.dataset.articuloId = articuloParaAgregar.id_articulo;
  tr.dataset.articuloSku = articuloParaAgregar.sku;
  
  tr.innerHTML = `
    <td>
      <div>
        <span>${articuloParaAgregar.descripcion}</span>
        <br>
        <small class="text-muted">${articuloParaAgregar.sku} - ${number_format(parseFloat(articuloParaAgregar.peso), 2)} g - ${articuloParaAgregar.tipo}</small>
      </div>
    </td>
    <td class="text-center">${articuloParaAgregar.unidades}</td>
    <td class="text-start">${number_format(parseFloat(articuloParaAgregar.precio), 2)} €</td>
    <td class="text-start">${number_format(parseFloat(articuloParaAgregar.precio), 2)} €</td>
    <td>
      <a href="javascript:;" class="btn btn-text-primary waves-effect waves-light discount-record p-0 me-2" title="Solicitar autorización para cambiar precio">
        <i class="icon-base ri ri-discount-percent-fill icon-22px"></i>
      </a>
      <a href="javascript:;" class="btn btn-text-danger waves-effect waves-light delete-record p-0" title="Eliminar artículo">
        <i class="icon-base ri ri-close-line icon-22px"></i>
      </a>
    </td>
  `;
  
  tbody.appendChild(tr);
  
  // Limpiar input y poner foco
  const inputSku = document.querySelector('.input-sku-articulo');
  if (inputSku) {
    inputSku.value = '';
    setTimeout(() => {
      inputSku.focus();
    }, 100);
  }
  
  // Actualizar totales
  window.calcularTotales();
  
  // Liberar el bloqueo
  setTimeout(() => {
    anadiendoArticulo = false;
  }, 500);
}

// Variable para prevenir múltiples ejecuciones
let anadiendoArticulo = false;

/**
 * Añadir artículo desde el preview a la tabla
 */
function anadirArticuloDesdePreview() {
  // Prevenir ejecuciones múltiples - verificación doble
  if (anadiendoArticulo) {
    return false;
  }
  
  if (!window.articuloPreview) {
    return false;
  }
  
  // Marcar como en proceso INMEDIATAMENTE - antes de cualquier otra operación
  anadiendoArticulo = true;
  
  // Guardar los datos del preview antes de limpiarlo
  const previewData = {
    id_articulo: parseInt(window.articuloPreview.id_articulo),
    sku: String(window.articuloPreview.sku).trim(),
    descripcion: window.articuloPreview.descripcion,
    unidades: window.articuloPreview.unidades,
    peso: window.articuloPreview.peso,
    tipo: window.articuloPreview.tipo,
    precio: window.articuloPreview.precio
  };
  
  // Verificar si el artículo ya existe en el array ANTES de limpiar el preview
  const articuloExistente = window.articulosVenta.find(art => {
    const idArticulo = parseInt(art.id_articulo);
    const sku = String(art.sku).trim();
    return idArticulo === previewData.id_articulo || sku === previewData.sku;
  });
  
  if (articuloExistente) {
    anadiendoArticulo = false; // Liberar el bloqueo
    Swal.fire({
      icon: 'warning',
      title: 'Artículo duplicado',
      text: 'Este artículo ya ha sido agregado a la venta',
      timer: 3000
    }).then(() => {
      // Limpiar el input de SKU cuando se cierre el mensaje
      const inputSku = document.querySelector('.input-sku-articulo');
      if (inputSku) {
        inputSku.value = '';
        inputSku.focus();
      }
    });
    return false;
  }
  
  // Agregar al array
  window.articulosVenta.push(previewData);
  
  // Actualizar inputs hidden de artículos
  actualizarInputsArticulos();
  
  // Limpiar mensaje de "no hay artículos" si existe
  const tbody = document.getElementById('articulos_venta_body');
  const mensajeVacio = tbody.querySelector('td[colspan="5"]');
  if (mensajeVacio) {
    mensajeVacio.parentElement.remove();
  }
  
  // Crear fila en la tabla
  const tr = document.createElement('tr');
  tr.className = 'fila-guardada';
  tr.dataset.index = window.articulosVenta.length - 1;
  // Guardar ID y SKU para poder eliminar correctamente
  tr.dataset.articuloId = previewData.id_articulo;
  tr.dataset.articuloSku = previewData.sku;
  
  tr.innerHTML = `
    <td>
      <div>
        <span>${previewData.descripcion}</span>
        <br>
        <small class="text-muted">${previewData.sku} - ${number_format(parseFloat(previewData.peso), 2)} g - ${previewData.tipo}</small>
      </div>
    </td>
    <td class="text-center">${previewData.unidades}</td>
    <td class="text-start">${number_format(parseFloat(previewData.precio), 2)} €</td>
    <td class="text-start">${number_format(parseFloat(previewData.precio), 2)} €</td>
    <td>
      <a href="javascript:;" class="btn btn-text-primary waves-effect waves-light discount-record p-0 me-2" title="Solicitar autorización para cambiar precio">
        <i class="icon-base ri ri-discount-percent-fill icon-22px"></i>
      </a>
      <a href="javascript:;" class="btn btn-text-danger waves-effect waves-light delete-record p-0" title="Eliminar artículo">
        <i class="icon-base ri ri-close-line icon-22px"></i>
      </a>
    </td>
  `;
  
  tbody.appendChild(tr);
  
  // Limpiar input y quitar el foco para evitar que se dispare el blur
  const inputSku = document.querySelector('.input-sku-articulo');
  if (inputSku) {
    inputSku.value = '';
    inputSku.blur(); // Quitar el foco para evitar que se dispare el evento blur
  }
  
  // Actualizar totales
  window.calcularTotales();
  
  // Liberar el bloqueo después de un delay más largo para asegurar que no se ejecute de nuevo
  setTimeout(() => {
    anadiendoArticulo = false;
  }, 1000);
  
  return true;
}


/**
 * Guardar artículo en el array y convertir la fila a modo vista
 */
function guardarArticulo(tr) {
  // Verificar que la fila no esté ya guardada
  if (tr.classList.contains('fila-guardada')) {
    return; // Ya está guardada, no hacer nada
  }
  
  // Validar que se haya encontrado el artículo
  if (tr.dataset.articuloEncontrado !== 'true') {
    Swal.fire({
      icon: 'warning',
      title: 'Artículo no encontrado',
      text: 'Debe buscar y seleccionar un artículo válido antes de guardar',
      timer: 3000
    });
    return;
  }
  
  if (!tr.dataset.descripcion || !tr.dataset.sku) {
    Swal.fire({
      icon: 'warning',
      title: 'Campos incompletos',
      text: 'Debe buscar y encontrar un artículo válido antes de guardar',
      timer: 3000
    });
    return;
  }
  
  // Crear objeto artículo
  const articulo = {
    id_articulo: tr.dataset.articuloId,
    sku: tr.dataset.sku,
    descripcion: tr.dataset.descripcion,
    unidades: 1,
    peso: tr.dataset.peso,
    tipo: tr.dataset.tipo,
    precio: tr.dataset.precio
  };
  
  // Verificar si el artículo ya existe en el array
  const articuloExistente = window.articulosVenta.find(art => 
    art.id_articulo === articulo.id_articulo || art.sku === articulo.sku
  );
  
  if (articuloExistente) {
    Swal.fire({
      icon: 'warning',
      title: 'Artículo duplicado',
      text: 'Este artículo ya ha sido agregado a la venta',
      timer: 3000
    });
    return;
  }
  
  // Agregar al array
  window.articulosVenta.push(articulo);
  
  // Actualizar inputs hidden de artículos
  actualizarInputsArticulos();
  
  // Convertir fila a modo vista
  tr.className = 'fila-guardada';
  tr.dataset.index = window.articulosVenta.length - 1;
  // Guardar ID y SKU para poder eliminar correctamente
  tr.dataset.articuloId = articulo.id_articulo;
  tr.dataset.articuloSku = articulo.sku;
  
  tr.innerHTML = `
    <td>
      <div>
        <span>${articulo.descripcion}</span>
        <br>
        <small class="text-muted">${articulo.sku} - ${number_format(parseFloat(articulo.peso), 2)} g - ${articulo.tipo}</small>
      </div>
    </td>
    <td class="text-center">${articulo.unidades}</td>
    <td class="text-start">${number_format(parseFloat(articulo.precio), 2)} €</td>
    <td class="text-start">${number_format(parseFloat(articulo.precio), 2)} €</td>
    <td>
      <a href="javascript:;" class="btn btn-text-primary waves-effect waves-light discount-record p-0 me-2" title="Solicitar autorización para cambiar precio">
        <i class="icon-base ri ri-discount-percent-fill icon-22px"></i>
      </a>
      <a href="javascript:;" class="btn btn-text-danger waves-effect waves-light delete-record p-0" title="Eliminar artículo">
        <i class="icon-base ri ri-close-line icon-22px"></i>
      </a>
    </td>
  `;
  
  // Limpiar input de SKU
  const inputSku = document.querySelector('.input-sku-articulo');
  if (inputSku) {
    inputSku.value = '';
  }
  filaEdicionActual = null;
  
  // Actualizar totales
  window.calcularTotales();
}

/**
 * Editar artículo guardado
 */
function editarArticulo(tr) {
  const index = parseInt(tr.dataset.index);
  const articulo = window.articulosVenta[index];
  
  if (!articulo) return;
  
  // Eliminar del array temporalmente (se volverá a agregar al guardar)
  window.articulosVenta.splice(index, 1);
  
  // Actualizar índices de las demás filas
  actualizarIndicesFilas();
  
  // Mostrar input de SKU con el valor del artículo
  const inputSku = document.querySelector('.input-sku-articulo');
  if (inputSku) {
    inputSku.value = articulo.sku;
    inputSku.focus();
  }
  
  // Eliminar la fila de la tabla
  tr.remove();
  filaEdicionActual = null;
  
  // Buscar el artículo automáticamente para recrear la fila de edición
  if (typeof window.buscarArticuloPorSku === 'function') {
    window.buscarArticuloPorSku(articulo.sku);
  }
}

/**
 * Eliminar fila de artículo
 */
function eliminarFilaArticulo(tr) {
  // Mostrar confirmación con SweetAlert simple, sin texto
  Swal.fire({
    title: '',
    text: '',
    showCancelButton: true,
    confirmButtonText: 'Borrar',
    cancelButtonText: 'No borrar',
    confirmButtonColor: '#dc3545',
    cancelButtonColor: '#28a745',
    reverseButtons: true,
    width: '300px'
  }).then((result) => {
    if (result.isConfirmed) {
      // Si es una fila guardada, eliminar del array
      if (tr.classList.contains('fila-guardada')) {
        // Asegurar que el array esté inicializado
        if (!window.articulosVenta) {
          window.articulosVenta = [];
        }
        
        // Buscar el artículo por ID o SKU en lugar de usar el índice
        // Esto evita problemas de sincronización cuando se eliminan varios artículos
        const articuloId = tr.dataset.articuloId;
        const articuloSku = tr.dataset.articuloSku;
        
        if (articuloId || articuloSku) {
          // Buscar el índice del artículo en el array
          const index = window.articulosVenta.findIndex(art => 
            (articuloId && art.id_articulo === articuloId) || 
            (articuloSku && art.sku === articuloSku)
          );
          
          // Si se encuentra, eliminar del array
          if (index !== -1) {
            window.articulosVenta.splice(index, 1);
          }
        } else {
          // Fallback: usar el índice si no hay ID o SKU
          const index = parseInt(tr.dataset.index);
          if (!isNaN(index) && index >= 0 && index < window.articulosVenta.length) {
            window.articulosVenta.splice(index, 1);
          }
        }
        
        // Actualizar inputs hidden de artículos
        actualizarInputsArticulos();
        
        // Actualizar totales (esto asegurará que el total sea 0 si no hay artículos)
        window.calcularTotales();
        
        // Actualizar índices
        actualizarIndicesFilas();
      }
      
      // Si es una fila en edición, limpiar el input de SKU
      if (tr.classList.contains('fila-edicion')) {
        const inputSku = document.querySelector('.input-sku-articulo');
        if (inputSku) {
          inputSku.value = '';
        }
        filaEdicionActual = null;
      }
      
      // Eliminar fila
      tr.remove();
      
      // Si no quedan artículos, mostrar mensaje
      const tbody = document.getElementById('articulos_venta_body');
      if (tbody && tbody.children.length === 0) {
        tbody.innerHTML = `
          <tr>
            <td colspan="5" class="text-center text-muted py-6">
              No hay artículos agregados
            </td>
          </tr>
        `;
      }
      
      // Verificar que el array esté sincronizado con las filas visibles
      // Si no hay filas guardadas, asegurar que el array esté vacío
      const filasGuardadas = tbody ? tbody.querySelectorAll('.fila-guardada') : [];
      if (filasGuardadas.length === 0 && window.articulosVenta && window.articulosVenta.length > 0) {
        // Si no hay filas guardadas pero el array tiene elementos, limpiarlo
        window.articulosVenta = [];
        actualizarInputsArticulos();
      }
      
      // Asegurar que los totales se actualicen correctamente después de eliminar
      if (window.calcularTotales) {
        window.calcularTotales();
      }
    }
  });
}

/**
 * Solicitar autorización para cambiar el precio de un artículo
 */
function solicitarAutorizacionPrecio(tr) {
  Swal.fire({
    title: '¿Está seguro?',
    text: '¿Está seguro que quiere pedir autorización para cambiar el precio de este artículo?',
    icon: 'question',
    showCancelButton: true,
    confirmButtonText: 'Sí, solicitar autorización',
    cancelButtonText: 'Cancelar',
    confirmButtonColor: '#007bff',
    cancelButtonColor: '#6c757d'
  }).then((result) => {
    if (result.isConfirmed) {
      // Aquí se puede agregar la lógica para solicitar la autorización
      // Por ahora solo mostramos un mensaje de confirmación
      Swal.fire({
        icon: 'success',
        title: 'Solicitud enviada',
        text: 'La solicitud de autorización ha sido enviada',
        timer: 2000,
        showConfirmButton: false
      });
    }
  });
}

/**
 * Actualizar índices de las filas después de eliminar
 */
function actualizarIndicesFilas() {
  const tbody = document.getElementById('articulos_venta_body');
  const filasGuardadas = tbody.querySelectorAll('.fila-guardada');
  
  filasGuardadas.forEach((fila, index) => {
    fila.dataset.index = index;
  });
}

/**
 * Actualizar inputs hidden con los artículos guardados
 */
function actualizarInputsArticulos() {
  const skus = window.articulosVenta.map(art => art.sku).join(',');
  const ids = window.articulosVenta.map(art => art.id_articulo).join(',');
  
  $('#insert_articulos_skus').val(skus);
  $('#insert_articulos_ids').val(ids);
}

/**
 * Inicializar Select2 para los selects del modal de cliente
 */
function inicializarSelectsModalCliente() {
  // Verificar si hay valores guardados previamente
  const tipoIdSaved = $('#modal_tipo_identificacion').attr('data-selected-id');
  const tipoTextSaved = $('#modal_tipo_identificacion').attr('data-selected-text');
  const paisIdSaved = $('#modal_pais').attr('data-selected-id');
  const paisTextSaved = $('#modal_pais').attr('data-selected-text');
  const provinciaIdSaved = $('#modal_c_provincia').attr('data-selected-id');
  const provinciaTextSaved = $('#modal_c_provincia').attr('data-selected-text');
  const poblacionIdSaved = $('#modal_c_poblacion').attr('data-selected-id');
  const poblacionTextSaved = $('#modal_c_poblacion').attr('data-selected-text');
  
  // Select2 para tipo de identificación
  $('#modal_tipo_identificacion').select2({
    dropdownParent: $('#datos_cliente'),
    placeholder: 'Seleccionar...',
    allowClear: true
  });
  
  // Si hay valor guardado, restaurarlo
  if (tipoIdSaved && tipoTextSaved) {
    const newOption = new Option(tipoTextSaved, tipoIdSaved, true, true);
    $('#modal_tipo_identificacion').append(newOption).trigger('change');
  } else {
    // Cargar opciones de tipo de identificación solo si no hay valor guardado
    cargarOpcionesSelect('modal_tipo_identificacion', 'parts/lotes/main/get_opciones.php?tipo=identificacion');
  }
  
  // Select2 para país con AJAX
  $('#modal_pais').select2({
    dropdownParent: $('#datos_cliente'),
    placeholder: 'Seleccionar...',
    allowClear: true,
    ajax: {
      url: 'parts/universal/ajax_poblaciones.php',
      dataType: 'json',
      delay: 250,
      data: function (params) {
        return {
          action: 'paises',
          search: params.term || '',
          page: params.page || 1
        };
      },
      processResults: function (data) {
        return {
          results: data.results || [],
          pagination: data.pagination || {more: false}
        };
      }
    }
  });
  
  // Select2 para provincia con AJAX
  $('#modal_c_provincia').select2({
    dropdownParent: $('#datos_cliente'),
    placeholder: 'Seleccionar...',
    allowClear: true,
    ajax: {
      url: 'parts/universal/ajax_poblaciones.php',
      dataType: 'json',
      delay: 250,
      data: function (params) {
        return {
          action: 'provincias',
          search: params.term || '',
          page: params.page || 1,
          idpais: $('#modal_pais').val()
        };
      },
      processResults: function (data) {
        return {
          results: data.results || [],
          pagination: data.pagination || {more: false}
        };
      }
    }
  });
  
  // Select2 para población con AJAX
  $('#modal_c_poblacion').select2({
    dropdownParent: $('#datos_cliente'),
    placeholder: 'Seleccionar...',
    allowClear: true,
    ajax: {
      url: 'parts/universal/ajax_poblaciones.php',
      dataType: 'json',
      delay: 250,
      data: function (params) {
        return {
          action: 'poblaciones',
          search: params.term || '',
          page: params.page || 1,
          idprovincia: $('#modal_c_provincia').val()
        };
      },
      processResults: function (data) {
        return {
          results: data.results || [],
          pagination: data.pagination || {more: false}
        };
      }
    }
  });
  
  // Restaurar valores guardados con delay para que no se sobrescriban
  setTimeout(function() {
    if (paisIdSaved && paisTextSaved) {
      const newOption = new Option(paisTextSaved, paisIdSaved, true, true);
      $('#modal_pais').append(newOption).trigger('change');
    }
    
    setTimeout(function() {
      if (provinciaIdSaved && provinciaTextSaved) {
        const newOption = new Option(provinciaTextSaved, provinciaIdSaved, true, true);
        $('#modal_c_provincia').append(newOption).trigger('change');
      }
      
      setTimeout(function() {
        if (poblacionIdSaved && poblacionTextSaved) {
          const newOption = new Option(poblacionTextSaved, poblacionIdSaved, true, true);
          $('#modal_c_poblacion').append(newOption).trigger('change');
        }
      }, 200);
    }, 200);
  }, 200);
}

/**
 * Cargar opciones en un select
 */
function cargarOpcionesSelect(selectId, url, valorSeleccionado, callback) {
  const select = document.getElementById(selectId);
  
  if (!select) {
    console.error('Select no encontrado:', selectId);
    return;
  }
  
  fetch(url)
    .then(response => response.json())
    .then(data => {
      select.innerHTML = '<option value="">Seleccionar...</option>';
      
      if (data.success && data.data) {
        data.data.forEach(item => {
          const option = document.createElement('option');
          option.value = item.id;
          option.textContent = item.nombre;
          if (item.id == valorSeleccionado) {
            option.selected = true;
          }
          select.appendChild(option);
        });
        
        // Trigger change para Select2
        $('#' + selectId).trigger('change');
      }
      
      if (callback) callback();
    })
    .catch(error => {
      console.error('Error al cargar opciones:', error);
    });
}

/**
 * Configurar verificación de cliente existente
 */
let timeoutVerificacion = null;

function configurarVerificacionCliente() {
  const inputIdentificacion = document.getElementById('modal_identificacion');
  
  if (!inputIdentificacion) {
    return;
  }
  
  // Event listener con debounce para verificar identificación
  inputIdentificacion.addEventListener('input', function() {
    clearTimeout(timeoutVerificacion);
    
    const identificacion = this.value.trim();
    
    // Solo verificar si tiene al menos 5 caracteres
    if (identificacion.length >= 5) {
      timeoutVerificacion = setTimeout(() => {
        verificarIdentificacion(identificacion);
      }, 800); // Esperar 800ms después de que deje de escribir
    }
  });
}

/**
 * Verificar si la identificación existe en la base de datos
 */
function verificarIdentificacion(identificacion) {
  $.ajax({
    url: 'parts/ventas/crear/ajax_verificar_cliente.php',
    data: {
      action: 'verificar_identificacion',
      valor: identificacion
    },
    dataType: 'json',
    success: function(response) {
      if (response.existe) {
        // Mostrar mensaje de cliente encontrado
        Swal.fire({
          icon: 'info',
          title: 'Cliente encontrado',
          text: response.message,
          confirmButtonText: '¿Cargar datos?',
          showCancelButton: true,
          cancelButtonText: 'No, crear nuevo'
        }).then((result) => {
          if (result.isConfirmed) {
            // Cargar datos del cliente en el formulario
            cargarDatosCliente(response.cliente, response.direccion, response.datos_cliente);
            
            // También cerrar el modal y mostrar los datos
            setTimeout(() => {
              guardarDatosCliente();
            }, 1000);
          }
        });
      }
    },
    error: function(xhr, status, error) {
      console.error('Error al verificar identificación:', error);
    }
  });
}

/**
 * Cargar datos del cliente en el formulario
 */
function cargarDatosCliente(cliente, direccion, datosCliente) {
  // Cargar datos básicos del cliente
  $('#modal_id_cliente').val(cliente.id_cliente);
  $('#modal_identificacion').val(cliente.identificacion || '');
  $('#modal_nombre').val(cliente.nombre || '');
  $('#modal_apellido').val(cliente.apellido || '');
  $('#modal_telefono').val(cliente.telefono || '');
  
  // Actualizar inputs hidden inmediatamente
  $('#insert_id_cliente').val(cliente.id_cliente);
  $('#insert_identificacion').val(cliente.identificacion || '');
  $('#insert_nombre').val(cliente.nombre || '');
  $('#insert_apellido').val(cliente.apellido || '');
  $('#insert_telefono').val(cliente.telefono || '');
  
  // Cargar tipo de identificación
  if (cliente.tipo_identificacion_id) {
    $.ajax({
      url: 'parts/lotes/main/get_opciones.php?tipo=identificacion',
      dataType: 'json',
      success: function(data) {
        if (data.success && data.data) {
          const option = data.data.find(item => item.id == cliente.tipo_identificacion_id);
          if (option) {
            // Guardar en data attributes para mantenerlo al reabrir modal
            $('#modal_tipo_identificacion').attr('data-selected-id', option.id);
            $('#modal_tipo_identificacion').attr('data-selected-text', option.nombre);
            
            const newOption = new Option(option.nombre, option.id, true, true);
            $('#modal_tipo_identificacion').append(newOption).trigger('change');
            
            // Actualizar input hidden
            $('#insert_tipo_identificacion').val(option.id);
          }
        }
      }
    });
  }
  
  // Cargar email si existe
  if (datosCliente && datosCliente.email) {
    $('#modal_email').val(datosCliente.email);
    $('#insert_email').val(datosCliente.email);
  }
  
  // Cargar dirección si existe
  if (direccion) {
    $('#modal_id_direccion').val(direccion.id_direcciones || '');
    $('#modal_direccion').val(direccion.direccion || '');
    $('#modal_codigo_postal').val(direccion.codigo_postal || '');
    
    // Actualizar inputs hidden de dirección
    $('#insert_id_direccion').val(direccion.id_direcciones || '');
    $('#insert_direccion').val(direccion.direccion || '');
    $('#insert_codigo_postal').val(direccion.codigo_postal || '');
    
    // Cargar país, provincia y población con Select2
    if (direccion.rel_id_pais) {
      $('#insert_pais').val(direccion.rel_id_pais);
      
      cargarPaisEnSelect(direccion.rel_id_pais, direccion.c_pais, function() {
        if (direccion.rel_id_provincia) {
          $('#insert_provincia').val(direccion.rel_id_provincia);
          
          cargarProvinciaEnSelect(direccion.rel_id_provincia, direccion.c_provincia, function() {
            if (direccion.rel_id_poblacion) {
              $('#insert_poblacion').val(direccion.rel_id_poblacion);
              
              cargarPoblacionEnSelect(direccion.rel_id_poblacion, direccion.c_poblacion);
            }
          });
        }
      });
    }
  }
}

/**
 * Cargar país en el select
 */
function cargarPaisEnSelect(idPais, nombrePais, callback) {
  // Guardar en data attributes
  $('#modal_pais').attr('data-selected-id', idPais);
  $('#modal_pais').attr('data-selected-text', nombrePais);
  
  const newOption = new Option(nombrePais, idPais, true, true);
  $('#modal_pais').append(newOption).trigger('change');
  if (callback) setTimeout(callback, 300);
}

/**
 * Cargar provincia en el select
 */
function cargarProvinciaEnSelect(idProvincia, nombreProvincia, callback) {
  // Guardar en data attributes
  $('#modal_c_provincia').attr('data-selected-id', idProvincia);
  $('#modal_c_provincia').attr('data-selected-text', nombreProvincia);
  
  const newOption = new Option(nombreProvincia, idProvincia, true, true);
  $('#modal_c_provincia').append(newOption).trigger('change');
  if (callback) setTimeout(callback, 300);
}

/**
 * Cargar población en el select
 */
function cargarPoblacionEnSelect(idPoblacion, nombrePoblacion) {
  // Guardar en data attributes
  $('#modal_c_poblacion').attr('data-selected-id', idPoblacion);
  $('#modal_c_poblacion').attr('data-selected-text', nombrePoblacion);
  
  const newOption = new Option(nombrePoblacion, idPoblacion, true, true);
  $('#modal_c_poblacion').append(newOption).trigger('change');
}

/**
 * Guardar datos del cliente y mostrarlos en el formulario
 */
function guardarDatosCliente() {
  const form = document.getElementById('form_datos_cliente');
  
  // Validar formulario
  if (!form.checkValidity()) {
    form.classList.add('was-validated');
    Swal.fire({
      icon: 'warning',
      title: 'Campos requeridos',
      text: 'Por favor, complete todos los campos obligatorios'
    });
    return;
  }
  
  // Obtener valores del formulario
  const idCliente = $('#modal_id_cliente').val() || '';
  const idDireccion = $('#modal_id_direccion').val() || '';
  const nombre = $('#modal_nombre').val() || '';
  const apellido = $('#modal_apellido').val() || '';
  const tipoIdentificacionId = $('#modal_tipo_identificacion').val() || '';
  const tipoIdentificacion = $('#modal_tipo_identificacion option:selected').text() || 'NIF';
  const identificacion = $('#modal_identificacion').val() || '';
  const telefono = $('#modal_telefono').val() || '';
  const email = $('#modal_email').val() || '';
  const paisId = $('#modal_pais').val() || '';
  const paisNombre = $('#modal_pais option:selected').text() || '';
  const provinciaId = $('#modal_c_provincia').val() || '';
  const provinciaNombre = $('#modal_c_provincia option:selected').text() || '';
  const poblacionId = $('#modal_c_poblacion').val() || '';
  const poblacionNombre = $('#modal_c_poblacion option:selected').text() || '';
  const direccion = $('#modal_direccion').val() || '';
  const codigoPostal = $('#modal_codigo_postal').val() || '';
  
  // Guardar valores en inputs hidden para mantenerlos al reabrir el modal
  $('#modal_id_cliente').val(idCliente || '0');
  
  // Guardar los valores select2 en data attributes para recargarlos
  $('#modal_tipo_identificacion').attr('data-selected-id', tipoIdentificacionId);
  $('#modal_tipo_identificacion').attr('data-selected-text', tipoIdentificacion);
  
  if (paisId) {
    $('#modal_pais').attr('data-selected-id', paisId);
    $('#modal_pais').attr('data-selected-text', paisNombre);
  }
  
  if (provinciaId) {
    $('#modal_c_provincia').attr('data-selected-id', provinciaId);
    $('#modal_c_provincia').attr('data-selected-text', provinciaNombre);
  }
  
  if (poblacionId) {
    $('#modal_c_poblacion').attr('data-selected-id', poblacionId);
    $('#modal_c_poblacion').attr('data-selected-text', poblacionNombre);
  }
  
  // Actualizar formulario oculto de INSERT
  $('#insert_id_cliente').val(idCliente);
  $('#insert_tipo_identificacion').val(tipoIdentificacionId);
  $('#insert_identificacion').val(identificacion);
  $('#insert_nombre').val(nombre);
  $('#insert_apellido').val(apellido);
  $('#insert_telefono').val(telefono);
  $('#insert_email').val(email);
  $('#insert_id_direccion').val(idDireccion);
  $('#insert_pais').val(paisId);
  $('#insert_provincia').val(provinciaId);
  $('#insert_poblacion').val(poblacionId);
  $('#insert_direccion').val(direccion);
  $('#insert_codigo_postal').val(codigoPostal);
  
  // Actualizar datos en el div de cliente
  const nombreCliente = nombre + ' ' + apellido;
  $('#nombre_cliente').text(nombreCliente);
  $('#tipo_identificacion_cliente').text(tipoIdentificacion);
  $('#dni_cliente').html('<span id="tipo_identificacion_cliente">' + tipoIdentificacion + '</span> ' + identificacion);
  $('#direccion_cliente').text(direccion);
  $('#poblacion_cliente').text(poblacionNombre);
  $('#codigo_postal_cliente').text(codigoPostal);
  $('#telefono_cliente').text('Teléfono: ' + telefono);
  
  // Mostrar div de datos del cliente
  const divDatosCliente = document.getElementById('datos-cliente');
  if (divDatosCliente) {
    divDatosCliente.style.display = 'block';
  }
  
  // Ocultar skeleton
  const skeletonCliente = document.getElementById('skeleton-cliente');
  if (skeletonCliente) {
    skeletonCliente.style.display = 'none';
  }
  
  // Cerrar modal
  const modal = bootstrap.Modal.getInstance(document.getElementById('datos_cliente'));
  if (modal) {
    modal.hide();
  }
  
  // Mensaje de éxito
  Swal.fire({
    icon: 'success',
    title: 'Cliente cargado',
    text: 'Los datos del cliente se han cargado correctamente',
    timer: 2000,
    showConfirmButton: false
  });
}

/**
 * Resetear todo el formulario de venta
 */
function resetearFormularioVenta() {
  // Limpiar array de artículos
  window.articulosVenta = [];
  
  // Limpiar tabla de artículos
  const tbody = document.getElementById('articulos_venta_body');
  if (tbody) {
    tbody.innerHTML = `
      <tr>
        <td colspan="5" class="text-center text-muted py-6">
          No hay artículos agregados
        </td>
      </tr>
    `;
  }
  
  // Limpiar datos del cliente
  const divDatosCliente = document.getElementById('datos-cliente');
  const skeletonCliente = document.getElementById('skeleton-cliente');
  
  if (divDatosCliente) {
    divDatosCliente.style.display = 'none';
  }
  
  if (skeletonCliente) {
    skeletonCliente.style.display = 'block';
  }
  
  // Limpiar datos de la empresa en el header
  $('#nombre_empresa').text('-');
  $('#cif_empresa').text('-');
  $('#email_empresa').text('-');
  
  // Limpiar datos de la sucursal en el header
  $('#direccion_sucursal').text('-');
  $('#poblacion_sucursal').text('-');
  $('#codigo_postal_sucursal').text('-');
  $('#telefono_sucursal').text('-');
  
  // NO limpiar nombre de sucursal ni id_sucursal porque viene por POST y no se puede cambiar
  
  // Limpiar todos los inputs hidden del formulario (excepto id_sucursal)
  // $('#insert_id_sucursal').val(''); // NO limpiar, viene por POST
  $('#insert_id_cliente').val('');
  $('#insert_tipo_identificacion').val('');
  $('#insert_identificacion').val('');
  $('#insert_nombre').val('');
  $('#insert_apellido').val('');
  $('#insert_telefono').val('');
  $('#insert_email').val('');
  $('#insert_id_direccion').val('');
  $('#insert_pais').val('');
  $('#insert_provincia').val('');
  $('#insert_poblacion').val('');
  $('#insert_direccion').val('');
  $('#insert_codigo_postal').val('');
  $('#insert_articulos_skus').val('');
  $('#insert_articulos_ids').val('');
  
  // Limpiar formulario del modal de cliente
  const formDatosCliente = document.getElementById('form_datos_cliente');
  if (formDatosCliente) {
    formDatosCliente.reset();
    formDatosCliente.classList.remove('was-validated');
  }
  
  // Limpiar Select2 del modal de cliente
  $('#modal_tipo_identificacion').val(null).trigger('change');
  $('#modal_pais').val(null).trigger('change');
  $('#modal_c_provincia').val(null).trigger('change');
  $('#modal_c_poblacion').val(null).trigger('change');
  
  // Limpiar data attributes de persistencia
  $('#modal_tipo_identificacion').removeAttr('data-selected-id').removeAttr('data-selected-text');
  $('#modal_pais').removeAttr('data-selected-id').removeAttr('data-selected-text');
  $('#modal_c_provincia').removeAttr('data-selected-id').removeAttr('data-selected-text');
  $('#modal_c_poblacion').removeAttr('data-selected-id').removeAttr('data-selected-text');
  
  // Limpiar observaciones
  const observaciones = document.getElementById('observaciones_venta');
  if (observaciones) {
    observaciones.value = '';
  }
  
  // Resetear opciones de venta
  const tipoVentaNormal = document.getElementById('tipo_venta_normal');
  if (tipoVentaNormal) {
    tipoVentaNormal.checked = true;
    // Actualizar visualmente
    document.querySelectorAll('.custom-option-basic').forEach(option => {
      option.classList.remove('checked');
    });
    const parentOption = tipoVentaNormal.closest('.custom-option-basic');
    if (parentOption) {
      parentOption.classList.add('checked');
    }
  }
  
  // Deseleccionar todos los radio buttons de forma de pago
  const formaPagoRadios = document.querySelectorAll('input[name="forma_pago"]');
  formaPagoRadios.forEach(radio => {
    radio.checked = false;
    // Remover clase checked del contenedor
    const parentOption = radio.closest('.custom-option-basic');
    if (parentOption) {
      parentOption.classList.remove('checked');
    }
  });
  
  
  // Actualizar totales
  window.calcularTotales();
}

