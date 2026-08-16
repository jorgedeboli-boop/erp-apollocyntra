/**
 * Nueva Venta - Gestión de artículos y cliente
 */

'use strict';

/**
 * Tope (€) factura simplificada: PHP `obtenerMaximoTotalFacturaSimplificada` → `window.MAX_TOTAL_FACTURA_SIMPLIFICADA`.
 */
function obtenerMaximoTotalFacturaSimplificada() {
  var v = typeof window.MAX_TOTAL_FACTURA_SIMPLIFICADA !== 'undefined'
    ? Number(window.MAX_TOTAL_FACTURA_SIMPLIFICADA)
    : NaN;
  return isFinite(v) && v > 0 ? v : 400;
}

document.addEventListener('DOMContentLoaded', function() {
  window.VENTA_CREAR_API = 'parts/ventas/crear/';
  window.interesPlazosVentaPersonalizado = null;

  const cuerpoVenta = document.getElementById('cuerpo_venta');
  const invoiceActions = document.getElementById('invoice_actions');
  
  if (cuerpoVenta) {
    cuerpoVenta.classList.add('formulario-borroso');
  }
  
  if (invoiceActions) {
    invoiceActions.classList.add('formulario-borroso');
  }

  const inputSucursal = document.getElementById('sucursal_venta');
  if (inputSucursal) {
    $('#insert_id_sucursal').val(inputSucursal.value || '0');
  }
  mostrarDatosVenta();
  
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
      document.querySelectorAll('.custom-option-tipo_venta').forEach(option => {
        option.classList.remove('checked');
      });
      
      // Agregar clase 'checked' al seleccionado
      const parentOption = this.closest('.custom-option-tipo_venta');
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
            
            // Quitar clase 'checked' de todos los radio buttons de numero_plazos
            document.querySelectorAll('input[name="numero_plazos"]').forEach(r => {
              const parentOption = r.closest('.custom-option-plazos');
              if (parentOption) {
                parentOption.classList.remove('checked');
              }
            });
            numeroPlazos3.checked = true;
            // Agregar clase 'checked' al contenedor del radio seleccionado
            const parentOption = numeroPlazos3.closest('.custom-option-plazos');
            if (parentOption) {
              parentOption.classList.add('checked');
            }
          }
          
          if (window.calcularTotales) {
            window.calcularTotales();
          }
        }
      } else {
        window.interesPlazosVentaPersonalizado = null;
        if (tipoPagoPlazos) {
          tipoPagoPlazos.style.display = 'none';
        }
        // Ocultar información de plazos
        const plazosInfo = document.getElementById('plazos_venta_info');
        const plazosIntereses = document.getElementById('plazos_venta_intereses');
        if (plazosInfo) {
          plazosInfo.style.display = 'none';
        }
        if (plazosIntereses) {
          plazosIntereses.style.display = 'none';
        }
        if (window.calcularTotales) {
          window.calcularTotales();
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
  const plazosIntereses = document.getElementById('plazos_venta_intereses');
  if (tipoVentaNormal && tipoVentaNormal.checked && plazosInfo) {
    plazosInfo.style.display = 'none';
  }
  if (tipoVentaNormal && tipoVentaNormal.checked && plazosIntereses) {
    plazosIntereses.style.display = 'none';
  }
  
  // Inicializar estado visual de los radio buttons marcados por defecto al cargar la página
  // Asegurar que tipo_venta_normal tenga la clase checked en su contenedor
  if (tipoVentaNormal && tipoVentaNormal.checked) {
    const parentOptionNormal = tipoVentaNormal.closest('.custom-option-tipo_venta');
    if (parentOptionNormal) {
      parentOptionNormal.classList.add('checked');
    }
  }
  
  // Asegurar que venta_forma_de_pago_contado tenga la clase checked en su contenedor
  const formaPagoContado = document.getElementById('venta_forma_de_pago_contado');
  if (formaPagoContado && formaPagoContado.checked) {
    const parentOptionContado = formaPagoContado.closest('.option-forma-pago');
    if (parentOptionContado) {
      parentOptionContado.classList.add('checked');
    }
  }
  
  // Event listener para forma de pago (manejar el estilo visual)
  document.querySelectorAll('input[name="forma_pago"]').forEach(radio => {
    radio.addEventListener('change', function() {
      // Quitar clase 'checked' de todos los radio buttons de forma de pago
      document.querySelectorAll('.forma_pago_venta').forEach(r => {
        const parentOption = r.closest('.option-forma-pago');
        if (parentOption) {
          parentOption.classList.remove('checked');
        }
      });
      
      // Agregar clase 'checked' al seleccionado
      const parentOption = this.closest('.option-forma-pago');
      if (parentOption) {
        parentOption.classList.add('checked');
      }
    });
  });
  
  // Event listener para número de plazos (manejar el estilo visual y actualizar información)
  document.querySelectorAll('input[name="numero_plazos"]').forEach(radio => {
    radio.addEventListener('change', function() {
      window.interesPlazosVentaPersonalizado = null;
      // Quitar clase 'checked' de todos los radio buttons de numero_plazos
      document.querySelectorAll('input[name="numero_plazos"]').forEach(r => {
        const parentOption = r.closest('.custom-option-plazos');
        if (parentOption) {
          parentOption.classList.remove('checked');
        }
      });
      
      // Agregar clase 'checked' al seleccionado
      const parentOption = this.closest('.custom-option-plazos');
      if (parentOption) {
        parentOption.classList.add('checked');
      }
      
      // Actualizar información de plazos
      if (window.calcularTotales) {
        window.calcularTotales();
      }
    });
  });
  
  window.obtenerInteresPctPlazos = function (numeroPlazos) {
    if (window.interesPlazosVentaPersonalizado !== null && window.interesPlazosVentaPersonalizado !== undefined) {
      const custom = parseFloat(window.interesPlazosVentaPersonalizado);
      if (!isNaN(custom) && custom >= 0) {
        return custom;
      }
    }
    const n = parseInt(numeroPlazos, 10);
    if (n === 6) {
      return 6;
    }
    if (n === 12) {
      return 10;
    }
    return 0;
  };

  window.normalizarPrecioOriginalArticulo = function (articulo) {
    if (!articulo) {
      return;
    }
    const p = parseFloat(articulo.precio) || 0;
    if (articulo.precio_original === undefined || articulo.precio_original === null) {
      articulo.precio_original = p;
    }
  };

  window.obtenerSubtotalVentaSinInteres = function () {
    let subtotal = 0;
    if (window.articulosVenta && window.articulosVenta.length > 0) {
      window.articulosVenta.forEach(function (articulo) {
        window.normalizarPrecioOriginalArticulo(articulo);
        subtotal += parseFloat(articulo.precio_original) || 0;
      });
    }
    return subtotal;
  };

  window.actualizarFilasPreciosArticulosTabla = function () {
    const tbody = document.getElementById('articulos_venta_body');
    if (!tbody || !window.articulosVenta || window.articulosVenta.length === 0) {
      return;
    }
    tbody.querySelectorAll('tr.fila-guardada').forEach(function (tr) {
      const idArt = parseInt(tr.dataset.articuloId, 10) || 0;
      const skuRow = tr.dataset.articuloSku ? String(tr.dataset.articuloSku) : '';
      let art = null;
      if (idArt > 0) {
        art = window.articulosVenta.find(function (a) {
          return parseInt(a.id_articulo, 10) === idArt;
        });
      }
      if (!art && skuRow) {
        art = window.articulosVenta.find(function (a) {
          return String(a.sku || '') === skuRow;
        });
      }
      if (!art) {
        return;
      }
      const tds = tr.querySelectorAll('td');
      if (tds.length < 4) {
        return;
      }
      const uds = parseFloat(art.unidades) || 1;
      const precio = parseFloat(art.precio) || 0;
      tds[2].textContent = number_format(precio, 2) + ' €';
      tds[3].textContent = number_format(precio * uds, 2) + ' €';
    });
  };

  window.aplicarPreciosArticulosSegunTipoVenta = function () {
    if (!window.articulosVenta || window.articulosVenta.length === 0) {
      return;
    }
    window.articulosVenta.forEach(function (art) {
      window.normalizarPrecioOriginalArticulo(art);
    });

    const tipoPlazos = document.getElementById('tipo_venta_plazos');
    const esPlazos = tipoPlazos && tipoPlazos.checked;

    if (!esPlazos) {
      window.articulosVenta.forEach(function (art) {
        art.precio = parseFloat(art.precio_original) || 0;
      });
      window.actualizarFilasPreciosArticulosTabla();
      return;
    }

    const nRadio = document.querySelector('input[name="numero_plazos"]:checked');
    const interesPct = nRadio ? window.obtenerInteresPctPlazos(nRadio.value) : 0;
    let subtotalBase = 0;
    window.articulosVenta.forEach(function (art) {
      subtotalBase += parseFloat(art.precio_original) || 0;
    });
    if (subtotalBase <= 0) {
      return;
    }

    if (interesPct <= 0) {
      window.articulosVenta.forEach(function (art) {
        art.precio = parseFloat(art.precio_original) || 0;
      });
      window.actualizarFilasPreciosArticulosTabla();
      return;
    }

    const totalTarget = Math.round(subtotalBase * (1 + interesPct / 100) * 100) / 100;
    let acumulado = 0;
    const n = window.articulosVenta.length;
    window.articulosVenta.forEach(function (art, idx) {
      if (idx < n - 1) {
        const p =
          Math.round(((parseFloat(art.precio_original) || 0) * totalTarget) / subtotalBase * 100) / 100;
        art.precio = p;
        acumulado += p;
      } else {
        art.precio = Math.round((totalTarget - acumulado) * 100) / 100;
      }
    });
    window.actualizarFilasPreciosArticulosTabla();
  };

  window.obtenerTotalVentaConPlazos = function () {
    const subtotal = window.obtenerSubtotalVentaSinInteres();
    const tipoPlazos = document.getElementById('tipo_venta_plazos');
    if (!tipoPlazos || !tipoPlazos.checked || subtotal <= 0) {
      return subtotal;
    }
    if (typeof window.aplicarPreciosArticulosSegunTipoVenta === 'function') {
      window.aplicarPreciosArticulosSegunTipoVenta();
    }
    let total = 0;
    window.articulosVenta.forEach(function (articulo) {
      total += parseFloat(articulo.precio) || 0;
    });
    return Math.round(total * 100) / 100;
  };

  /** Importe a cobrar en caja: total si venta normal; importe de una cuota si venta a plazos. */
  window.obtenerImporteACobrarVenta = function () {
    const total = window.obtenerTotalVentaConPlazos();
    const tipoPlazos = document.getElementById('tipo_venta_plazos');
    let cobrar = total;
    if (tipoPlazos && tipoPlazos.checked && total > 0) {
      const nRadio = document.querySelector('input[name="numero_plazos"]:checked');
      if (nRadio) {
        const n = parseInt(nRadio.value, 10);
        if (n > 0) {
          cobrar = Math.round((total / n) * 100) / 100;
        }
      }
    }
    return cobrar;
  };

  window.actualizarTotalCobrarResumen = function () {
    if (typeof window.number_format !== 'function') {
      return;
    }
    const totalConInteres = window.obtenerTotalVentaConPlazos();
    const totalResumenElement = document.getElementById('total_resumen');
    if (totalResumenElement) {
      totalResumenElement.textContent = window.number_format(totalConInteres, 2) + ' €';
    }
    const el = document.getElementById('total_cobrar');
    if (el) {
      el.textContent = window.number_format(window.obtenerImporteACobrarVenta(), 2) + ' €';
    }
  };

  /**
   * Modal de cobro: valida importes y llama onConfirm(payload) si todo es correcto.
   * @param {string} formaPago contado|tarjeta|bizum|transferencia|combinado
   * @param {number} importeACobrar
   * @param {function(Object):void} onConfirm
   */
  window.mostrarModalCobroVenta = function (formaPago, importeACobrar, onConfirm) {
    const fmt = function (n) {
      return window.number_format(n, 2);
    };
    const tol = 0.009;
    const forma = (formaPago || '').toLowerCase();

    if (forma === 'combinado') {
      Swal.fire({
        title: 'Debe Cobrar',
        html:
          '<p class="mb-3">Total a cobrar: <strong>' +
          fmt(importeACobrar) +
          ' €</strong></p>' +
          '<div class="text-start">' +
          '<label class="form-label small">Contado (€)</label>' +
          '<input type="number" step="0.01" min="0" id="swal-cobro-contado" class="form-control mb-2" />' +
          '<label class="form-label small">Tarjeta (€)</label>' +
          '<input type="number" step="0.01" min="0" id="swal-cobro-tarjeta" class="form-control mb-2" />' +
          '<label class="form-label small">Bizum (€)</label>' +
          '<input type="number" step="0.01" min="0" id="swal-cobro-bizum" class="form-control mb-2" />' +
          '<label class="form-label small">Transferencia (€)</label>' +
          '<input type="number" step="0.01" min="0" id="swal-cobro-transferencia" class="form-control mb-2" />' +
          '<p id="swal-cobro-combinado-sum" class="small text-muted mb-2"></p>' +
          '</div>' +
          '<div class="d-flex gap-2 justify-content-end mt-3">' +
          '<button type="button" class="btn btn-outline-secondary" id="swal-cobro-cancelar">Cancelar</button>' +
          '<button type="button" class="btn btn-primary" id="swal-cobro-confirmar">Cobrar</button>' +
          '</div>',
        showConfirmButton: false,
        showCancelButton: false,
        allowOutsideClick: true,
        didOpen: function () {
          const sumEl = document.getElementById('swal-cobro-combinado-sum');
          function refreshSum() {
            const c = parseFloat(document.getElementById('swal-cobro-contado').value) || 0;
            const t = parseFloat(document.getElementById('swal-cobro-tarjeta').value) || 0;
            const b = parseFloat(document.getElementById('swal-cobro-bizum').value) || 0;
            const tr = parseFloat(document.getElementById('swal-cobro-transferencia').value) || 0;
            const s = c + t + b + tr;
            sumEl.textContent = 'Suma: ' + fmt(s) + ' €';
          }
          ['swal-cobro-contado', 'swal-cobro-tarjeta', 'swal-cobro-bizum', 'swal-cobro-transferencia'].forEach(function (id) {
            const el = document.getElementById(id);
            if (el) el.addEventListener('input', refreshSum);
          });
          refreshSum();
          document.getElementById('swal-cobro-cancelar').addEventListener('click', function () {
            Swal.close();
          });
          document.getElementById('swal-cobro-confirmar').addEventListener('click', function () {
            const c = parseFloat(document.getElementById('swal-cobro-contado').value) || 0;
            const t = parseFloat(document.getElementById('swal-cobro-tarjeta').value) || 0;
            const b = parseFloat(document.getElementById('swal-cobro-bizum').value) || 0;
            const tr = parseFloat(document.getElementById('swal-cobro-transferencia').value) || 0;
            const suma = c + t + b + tr;
            if (Math.abs(suma - importeACobrar) > tol) {
              Swal.fire({
                icon: 'warning',
                title: 'Importes',
                text:
                  'La suma debe ser ' +
                  fmt(importeACobrar) +
                  ' € (actual: ' +
                  fmt(suma) +
                  ' €).'
              });
              return;
            }
            Swal.close();
            onConfirm({
              combinado: { contado: c, tarjeta: t, bizum: b, transferencia: tr }
            });
          });
        }
      });
      return;
    }

    const hintContado =
      forma === 'contado'
        ? '<p id="swal-vuelta" class="small text-primary mt-2 mb-0"></p><p class="small text-muted">Si entrega más importe, se muestra la vuelta.</p>'
        : '<p class="small text-muted">El importe debe coincidir con el total a cobrar.</p>';

    Swal.fire({
      title: 'Debe Cobrar',
      html:
        '<p class="mb-2">Total a cobrar: <strong>' +
        fmt(importeACobrar) +
        ' €</strong></p>' +
        '<label class="form-label">Importe</label>' +
        '<input type="number" step="0.01" min="0" id="swal-importe-cobro" class="form-control" />' +
        hintContado,
      showCancelButton: true,
      confirmButtonText: 'Cobrar',
      cancelButtonText: 'Cancelar',
      focusConfirm: false,
      preConfirm: function () {
        const raw = document.getElementById('swal-importe-cobro').value;
        const v = parseFloat(raw);
        if (raw === '' || isNaN(v)) {
          Swal.showValidationMessage('Indique un importe válido.');
          return false;
        }
        if (forma === 'contado') {
          if (v + 1e-9 < importeACobrar - tol) {
            Swal.showValidationMessage(
              'El importe entregado no puede ser inferior a ' + fmt(importeACobrar) + ' €.'
            );
            return false;
          }
          const vuelta = v - importeACobrar;
          return { importe_entregado: v, vuelta: vuelta > tol ? vuelta : 0 };
        }
        if (Math.abs(v - importeACobrar) > tol) {
          Swal.showValidationMessage('El importe debe ser exactamente ' + fmt(importeACobrar) + ' €.');
          return false;
        }
        return { importe_entregado: v };
      },
      didOpen: function () {
        if (forma !== 'contado') {
          return;
        }
        const inp = document.getElementById('swal-importe-cobro');
        const info = document.getElementById('swal-vuelta');
        function refresh() {
          const v = parseFloat(inp.value);
          if (!info) {
            return;
          }
          if (!isNaN(v) && v + 1e-9 >= importeACobrar - tol) {
            const vu = v - importeACobrar;
            info.textContent = vu > tol ? 'Vuelta: ' + fmt(vu) + ' €' : 'Sin vuelta';
          } else {
            info.textContent = '';
          }
        }
        inp.addEventListener('input', refresh);
      }
    }).then(function (result) {
      if (result.isConfirmed && result.value) {
        onConfirm(result.value);
      }
    });
  };
  
  // Función para calcular y mostrar información de plazos
  window.actualizarPlazosInfo = function() {
    const tipoVentaPlazos = document.getElementById('tipo_venta_plazos');
    const plazosInfo = document.getElementById('plazos_venta_info');
    const plazosIntereses = document.getElementById('plazos_venta_intereses');
    
    // Solo actualizar si está seleccionado tipo plazos
    if (!tipoVentaPlazos || !tipoVentaPlazos.checked || !plazosInfo) {
      if (plazosInfo) {
        plazosInfo.style.display = 'none';
      }
      if (plazosIntereses) {
        plazosIntereses.style.display = 'none';
      }
      return;
    }
    
    // Obtener número de plazos seleccionado
    const numeroPlazosRadio = document.querySelector('input[name="numero_plazos"]:checked');
    if (!numeroPlazosRadio) {
      if (plazosInfo) {
        plazosInfo.style.display = 'none';
      }
      if (plazosIntereses) {
        plazosIntereses.style.display = 'none';
      }
      return;
    }
    
    const numeroPlazos = parseInt(numeroPlazosRadio.value, 10);
    const interesPct = window.obtenerInteresPctPlazos(numeroPlazos);
    const subtotal = window.obtenerSubtotalVentaSinInteres();
    const total = window.obtenerTotalVentaConPlazos();

    const inpPct = document.getElementById('porcentaje_venta_plazos');
    const inpInt = document.getElementById('interes_valor');
    if (inpPct) {
      inpPct.value = String(interesPct);
    }
    if (inpInt) {
      inpInt.value = String(interesPct);
    }

    if (plazosIntereses) {
      if (interesPct > 0) {
        const extraInteres = Math.round((total - subtotal) * 100) / 100;
        plazosIntereses.textContent =
          'Interés del ' + interesPct + '% (+' + number_format(extraInteres, 2) + ' €)';
      } else {
        plazosIntereses.textContent = 'Sin intereses';
      }
      plazosIntereses.style.display = 'block';
    }
    
    if (total <= 0) {
      plazosInfo.textContent = 'Sin artículos!';
      plazosInfo.style.display = 'block';
      return;
    }
    
    const importePorCuota = Math.round((total / numeroPlazos) * 100) / 100;
    
    plazosInfo.textContent =
      numeroPlazos +
      ' pagos de ' +
      number_format(importePorCuota, 2) +
      ' € (total ' +
      number_format(total, 2) +
      ' €)';
    plazosInfo.style.display = 'block';
  }
  
  // Función para calcular totales
  window.calcularTotales = function() {
    // Asegurar que el array esté inicializado
    if (!window.articulosVenta) {
      window.articulosVenta = [];
    }

    if (typeof window.aplicarPreciosArticulosSegunTipoVenta === 'function') {
      window.aplicarPreciosArticulosSegunTipoVenta();
    }
    
    const iva = 0;
    const totalConInteres = window.obtenerTotalVentaConPlazos();
    const subtotalFactura = Math.round(totalConInteres * 100) / 100;
    const totalFactura = Math.round((totalConInteres + iva) * 100) / 100;
    
    // Actualizar UI
    const subtotalElement = document.getElementById('subtotal_venta');
    const ivaElement = document.getElementById('iva_venta');
    const totalElement = document.getElementById('total_venta');
    const totalHeaderElement = document.getElementById('total_venta_header');
    const totalResumenElement = document.getElementById('total_resumen');
    
    if (subtotalElement) subtotalElement.textContent = number_format(subtotalFactura, 2) + ' €';
    if (ivaElement) ivaElement.textContent = number_format(iva, 2) + ' €';
    if (totalElement) totalElement.textContent = number_format(totalFactura, 2) + ' €';
    if (totalHeaderElement) totalHeaderElement.textContent = number_format(totalFactura, 2) + ' €';
    if (totalResumenElement) totalResumenElement.textContent = number_format(totalFactura, 2) + ' €';
    
    // Actualizar resumen
    const totalArticulos = window.articulosVenta ? window.articulosVenta.length : 0;
    const pesoTotal = (window.articulosVenta && window.articulosVenta.length > 0) 
      ? window.articulosVenta.reduce((sum, art) => sum + parseFloat(art.peso || 0), 0)
      : 0;
    
    const totalArticulosElement = document.getElementById('total_articulos_resumen');
    const pesoTotalElement = document.getElementById('peso_total_resumen');
    
    if (totalArticulosElement) totalArticulosElement.textContent = totalArticulos;
    if (pesoTotalElement) pesoTotalElement.textContent = number_format(pesoTotal, 2) + ' g';

    /* forma_pago: por encima del tope de factura simplificada se oculta contado y se fuerza tarjeta; por debajo se permite contado */
    const formaPagoContado = document.getElementById('venta_forma_de_pago_contado');
    const formaPagoTarjeta = document.getElementById('venta_forma_de_pago_tarjeta');
    const formaPagoRadios = document.querySelectorAll('input[name="forma_pago"]');

    if (totalConInteres > obtenerMaximoTotalFacturaSimplificada()) {
      if (formaPagoRadios && formaPagoRadios.length > 0) {
        formaPagoRadios.forEach(radio => {
          radio.checked = false;
          const parentOption = radio.closest('.option-forma-pago');
          if (parentOption) parentOption.classList.remove('checked');
        });
      }
      if (formaPagoContado) {
        formaPagoContado.disabled = true;
        formaPagoContado.checked = false;
        formaPagoContado.hidden = true;
        const parentOptionContado = formaPagoContado.closest('.option-forma-pago');
        if (parentOptionContado) parentOptionContado.classList.remove('checked');
        if (parentOptionContado) parentOptionContado.style.display = 'none';
        if (parentOptionContado) parentOptionContado.remove();
      }
      if (formaPagoTarjeta) {
        formaPagoTarjeta.checked = true;
        const parentOptionTarjeta = formaPagoTarjeta.closest('.option-forma-pago');
        if (parentOptionTarjeta) parentOptionTarjeta.classList.add('checked');
      }
    } else {
      if (!formaPagoContado || !formaPagoContado.value) {
        let div_pago_contado = '<label class="btn btn-outline-primary d-flex justify-content-start align-items-start option-forma-pago" for="venta_forma_de_pago_contado"> <input type="radio" class="btn-check forma_pago_venta" name="forma_pago" id="venta_forma_de_pago_contado"  value="contado" checked="checked" /> Contado</label>';
        $('.btnformadepago').prepend(div_pago_contado);
      } 
      
        /*
      if (formaPagoContado) {
        formaPagoContado.disabled = false;
        formaPagoContado.hidden = false;
        formaPagoContado.checked = true;
        if (formaPagoRadios && formaPagoRadios.length > 0) {
          formaPagoRadios.forEach(radio => {
            radio.checked = false;
            const parentOption = radio.closest('.option-forma-pago');
            if (parentOption) parentOption.classList.remove('checked');
          });
        }
        let div_pago_contado = '<label class="btn btn-outline-primary d-flex justify-content-start align-items-start option-forma-pago" for="venta_forma_de_pago_contado"> <input type="radio" class="btn-check forma_pago_venta" name="forma_pago" id="venta_forma_de_pago_contado"  value="contado" checked="checked" /> Contado</label>';
        $('.btnformadepago').prepend(div_pago_contado);
        const parentOptionContado = formaPagoContado.closest('.option-forma-pago');
        if (parentOptionContado) parentOptionContado.classList.add('checked');
        if (parentOptionContado) parentOptionContado.style.display = 'block';
      }*/


    }
    
    // Actualizar información de plazos si está seleccionado tipo plazos
    if (window.actualizarPlazosInfo) {
      window.actualizarPlazosInfo();
    }
    if (window.actualizarTotalCobrarResumen) {
      window.actualizarTotalCobrarResumen();
    }
  }

  /** Total numérico de la venta (incluye intereses si es venta a plazos). */
  window.obtenerTotalVentaActual = function () {
    return window.obtenerTotalVentaConPlazos();
  };

  /** Campos mínimos del cliente ya guardados en #form_insert_venta (modal). */
  function datosClienteVentaCompletos() {
    var tipo = $('#insert_tipo_identificacion').val();
    var idDoc = ($('#insert_identificacion').val() || '').trim();
    var nom = ($('#insert_nombre').val() || '').trim();
    var ape = ($('#insert_apellido').val() || '').trim();
    var tel = ($('#insert_telefono').val() || '').trim();
    return !!(tipo && idDoc && nom && ape && tel);
  }
  
  // Función para formatear números
  window.number_format = function(number, decimals) {
    // UI: punto SOLO para decimales, sin separador de miles.
    var n = parseFloat(number);
    if (isNaN(n)) n = 0;
    var d = (decimals === undefined || decimals === null) ? 2 : parseInt(decimals, 10);
    return n.toFixed(d);
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
      
      const idCliente = $('#insert_id_cliente').val();
      console.log("idCliente: " + idCliente);
      const idSucursal = $('#insert_id_sucursal').val() || '0';
      console.log("idSucursal: " + idSucursal);
      const tipoVenta = document.querySelector('input[name="tipo_venta"]:checked');
      console.log("tipoVenta: " + tipoVenta);
      const numeroPlazos = document.querySelector('input[name="numero_plazos"]:checked');
      console.log("numeroPlazos: " + numeroPlazos);
      const observaciones = $('#observaciones_venta').val();
      console.log("observaciones: " + observaciones);
      const formaPago = document.querySelector('input[name="forma_pago"]:checked');
      console.log("formaPago: " + formaPago);

      if (!tipoVenta || !tipoVenta.value) {
        Swal.fire({
          icon: 'warning',
          title: 'Atención',
          text: 'Seleccione el tipo de venta.'
        });
        return;
      }
      let tipo_venta = tipoVenta.value;
      if (tipo_venta === 'plazos') {
        if (!datosClienteVentaCompletos()) {
          Swal.fire({
            icon: 'warning',
            title: 'Datos del cliente obligatorios',
            text: 'Para ventas a plazosdebe completar y guardar los datos del cliente (tipo e identificación, nombre, apellidos y teléfono).'
          });
          return;
        }
      }

      if (!formaPago || !formaPago.value) {
        Swal.fire({
          icon: 'warning',
          title: 'Atención',
          text: 'Debe seleccionar una forma de pago'
        });
        return;
      }

      if (typeof window.obtenerTotalVentaActual === 'function' && window.obtenerTotalVentaActual() > obtenerMaximoTotalFacturaSimplificada()) {
        if (!datosClienteVentaCompletos()) {
          Swal.fire({
            icon: 'warning',
            title: 'Datos del cliente obligatorios',
            text: 'Para ventas superiores a ' + window.number_format(obtenerMaximoTotalFacturaSimplificada(), 2) + ' € debe completar y guardar los datos del cliente (tipo e identificación, nombre, apellidos y teléfono).'
          });
          return;
        }
      }

      const importeACobrar =
        typeof window.obtenerImporteACobrarVenta === 'function'
          ? window.obtenerImporteACobrarVenta()
          : window.obtenerTotalVentaActual();

      if (importeACobrar <= 0) {
        Swal.fire({
          icon: 'warning',
          title: 'Atención',
          text: 'El importe a cobrar debe ser mayor que cero.'
        });
        return;
      }

      window.mostrarModalCobroVenta(formaPago.value, importeACobrar, function (cobroPayload) {
        const payload = {
          sucursal_venta: parseInt(idSucursal, 10) || 0,
          id_cliente: idCliente ? String(idCliente) : '',
          cliente: {
            id_cliente: idCliente ? String(idCliente) : '',
            tipo_identificacion: $('#insert_tipo_identificacion').val() || '',
            identificacion: ($('#insert_identificacion').val() || '').trim(),
            nombre: ($('#insert_nombre').val() || '').trim(),
            apellido: ($('#insert_apellido').val() || '').trim(),
            telefono: ($('#insert_telefono').val() || '').trim(),
            email: ($('#insert_email').val() || '').trim(),
            id_direccion: $('#insert_id_direccion').val() || '',
            pais: $('#insert_pais').val() || '',
            provincia: $('#insert_provincia').val() || '',
            poblacion: $('#insert_poblacion').val() || '',
            direccion: ($('#insert_direccion').val() || '').trim(),
            codigo_postal: ($('#insert_codigo_postal').val() || '').trim()
          },
          tipo_venta: tipoVenta.value,
          numero_plazos: numeroPlazos ? String(numeroPlazos.value) : '',
          forma_pago: formaPago.value,
          observaciones: observaciones ? String(observaciones) : '',
          articulos: window.articulosVenta.map(function (a) {
            return {
              id_articulo: parseInt(a.id_articulo, 10),
              precio: parseFloat(a.precio) || 0
            };
          }),
          importe_a_cobrar: importeACobrar,
          interes_plazos:
            tipoVenta.value === 'plazos' && numeroPlazos && typeof window.obtenerInteresPctPlazos === 'function'
              ? window.obtenerInteresPctPlazos(numeroPlazos.value)
              : 0,
          cobro: cobroPayload
        };

        Swal.fire({
          title: 'Registrando venta…',
          allowOutsideClick: false,
          didOpen: function () {
            Swal.showLoading();
          }
        });

        fetch('parts/ventas/crear/insertar_venta.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(payload)
        })
          .then(function (res) {
            return res.json();
          })
          .then(function (data) {
            Swal.close();
            if (!data || !data.success) {
              Swal.fire({
                icon: 'error',
                title: 'No se pudo guardar',
                text: (data && data.message) ? data.message : 'Respuesta inválida del servidor.'
              });
              return;
            }
            window.location.href = 'venta.php?id=' + encodeURIComponent(data.id_venta);
          })
          .catch(function () {
            Swal.close();
            Swal.fire({
              icon: 'error',
              title: 'Error de red',
              text: 'No se pudo contactar con el servidor.'
            });
          });
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
      desactivarAutocompletadoIdentificacionModal();
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
      $('#modal_identificacion').prop('disabled', false);
    } else {
      $('#modal_tipo_identificacion').removeAttr('data-selected-id');
      $('#modal_tipo_identificacion').removeAttr('data-selected-text');
      $('#modal_identificacion').prop('disabled', true);
    }
  });

  // Tras Select2 el foco sigue en el desplegable; enfocar el input cuando ya se cerró
  $(document).on('select2:close', '#modal_tipo_identificacion', function () {
    const tipoId = $(this).val();
    if (!tipoId) return;
    const el = document.getElementById('modal_identificacion');
    if (!el) return;
    window.setTimeout(function () {
      el.focus();
    }, 50);
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
  
  if (!divDatosVenta || !divInvoiceActions) {
    return;
  }

  const idSucursal = inputSucursal && inputSucursal.value ? inputSucursal.value : '0';
  $('#insert_id_sucursal').val(idSucursal);

  cargarDatosEmpresa();

  divDatosVenta.classList.remove('formulario-borroso');
  divInvoiceActions.classList.remove('formulario-borroso');
}

/**
 * Cargar datos de la empresa del usuario logueado
 */
function cargarDatosEmpresa() {
  $.ajax({
    url: 'parts/ventas/crear/get_empresa_sucursal.php',
    dataType: 'json',
    success: function(response) {
      if (response.success && response.empresa) {
        const empresa = response.empresa;
        $('#nombre_empresa').text(empresa.nombre_empresa || '-');
        $('#cif_empresa').text('CIF: ' + (empresa.cif_empresa || '-'));
        $('#email_empresa').text(empresa.email_empresa || '-');
        $('#direccion_sucursal').text(empresa.direccion_empresa || '-');
        $('#poblacion_sucursal').text(empresa.poblacion_empresa || '-');
        $('#codigo_postal_sucursal').text(empresa.codigo_postal_empresa || '-');
        $('#telefono_sucursal').text(empresa.telefono_empresa || '-');
      } else {
        $('#nombre_empresa').text('-');
        $('#cif_empresa').text('-');
        $('#email_empresa').text('-');
      }
    },
    error: function() {
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
 * Configurar búsqueda de artículo en el input de SKU (botón o Enter; sin búsqueda automática)
 */
let busquedaSkuVentaBotonInicializado = false;

function ejecutarBusquedaSkuVentaDesdeBoton() {
  const inputSku = document.querySelector('.input-sku-articulo');
  if (!inputSku) return;
  if (anadiendoArticulo) return;

  const sku = String(inputSku.value || '').trim();
  if (!sku) {
    Swal.fire({
      icon: 'info',
      title: 'SKU',
      text: 'Introduce un SKU para buscar.',
      timer: 2500,
      showConfirmButton: false
    });
    inputSku.focus();
    return;
  }
  if (sku.length < 3) {
    Swal.fire({
      icon: 'info',
      title: 'SKU',
      text: 'El SKU debe tener al menos 3 caracteres.',
      timer: 2500,
      showConfirmButton: false
    });
    inputSku.focus();
    return;
  }
  if (typeof window.buscarArticuloPorSku === 'function') {
    window.buscarArticuloPorSku(sku);
  }
  inputSku.focus();
}

function configurarBusquedaArticulo() {
  const inputSku = document.querySelector('.input-sku-articulo');
  const btnBuscarSkuVenta = document.getElementById('btn_buscar_sku_venta');

  if (!inputSku) return;

  const newInputSku = inputSku.cloneNode(true);
  inputSku.parentNode.replaceChild(newInputSku, inputSku);

  newInputSku.addEventListener('keydown', function (e) {
    if (e.key === 'Enter') {
      e.preventDefault();
      ejecutarBusquedaSkuVentaDesdeBoton();
    }
  });

  if (btnBuscarSkuVenta && !busquedaSkuVentaBotonInicializado) {
    busquedaSkuVentaBotonInicializado = true;
    btnBuscarSkuVenta.addEventListener('mousedown', function (e) {
      e.preventDefault();
    });
    btnBuscarSkuVenta.addEventListener('click', function () {
      ejecutarBusquedaSkuVentaDesdeBoton();
    });
  }
}

/**
 * Buscar artículo por SKU
 */
function buscarArticuloPorSku(sku) {
  $.ajax({
    url: 'parts/ventas/crear/buscar_articulo.php',
    data: {
      sku: sku
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
    precio: articulo.precio,
    precio_original: parseFloat(articulo.precio) || 0
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
    precio: window.articuloPreview.precio,
    precio_original: parseFloat(window.articuloPreview.precio) || 0
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
  
  const inputSku = document.querySelector('.input-sku-articulo');
  if (inputSku) {
    inputSku.value = '';
    inputSku.focus();
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
    precio: tr.dataset.precio,
    precio_original: parseFloat(tr.dataset.precio) || 0
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

var interesesAuthPollTimer = null;
var interesesAuthPollEnCurso = false;
var interesesFlujoAuthContinuado = false;

function detenerPollEstadoAutorizacionIntereses() {
  if (interesesAuthPollTimer) {
    clearInterval(interesesAuthPollTimer);
    interesesAuthPollTimer = null;
  }
}

function iniciarPollEstadoAutorizacionIntereses(idAutorizacion, apiBase, interesesOriginales) {
  detenerPollEstadoAutorizacionIntereses();
  interesesAuthPollTimer = setInterval(function () {
    consultarEstadoAutorizacionIntereses(idAutorizacion, apiBase, interesesOriginales);
  }, 2000);
}

function consultarEstadoAutorizacionIntereses(idAutorizacion, apiBase, interesesOriginales) {
  if (interesesAuthPollEnCurso || interesesFlujoAuthContinuado) {
    return;
  }
  var idNum = parseInt(String(idAutorizacion || ''), 10);
  if (!idNum) {
    return;
  }
  interesesAuthPollEnCurso = true;
  var fd = new FormData();
  fd.append('id_autorizacion', String(idNum));
  fetch(apiBase + 'verificar_estado_autorizacion_intereses_venta.php', {
    method: 'POST',
    body: fd,
    credentials: 'same-origin'
  })
    .then(function (r) {
      return r.json();
    })
    .then(function (data) {
      if (data && data.success === true && data.autorizada === true) {
        continuarFlujoInteresesTrasAutorizacion(idNum, apiBase, interesesOriginales);
      }
    })
    .catch(function () {})
    .finally(function () {
      interesesAuthPollEnCurso = false;
    });
}

function continuarFlujoInteresesTrasAutorizacion(idAutorizacion, apiBase, interesesOriginales) {
  if (interesesFlujoAuthContinuado) {
    return;
  }
  interesesFlujoAuthContinuado = true;
  detenerPollEstadoAutorizacionIntereses();
  if (typeof Swal !== 'undefined' && Swal.isVisible && Swal.isVisible()) {
    Swal.close();
  }
  abrirSwalNuevoInteresVenta(idAutorizacion, interesesOriginales, apiBase);
}

function abrirSwalNuevoInteresVenta(idNum, interesesOriginales, apiBase) {
  Swal.fire({
    title: 'Nuevo interés (%)',
    html:
      '<p class="small text-muted mb-2">Interés actual: <strong>' +
      number_format(interesesOriginales, 2) +
      '%</strong></p>' +
      '<input id="interes_nuevo_venta" type="number" class="swal2-input" placeholder="Nuevo interés %" step="0.01" min="0" max="100" />' +
      '<input type="hidden" id="id_autorizacion_interes_nuevo" value="' + String(idNum) + '"/>',
    showCancelButton: true,
    confirmButtonText: 'Aplicar interés',
    cancelButtonText: 'Cancelar',
    focusConfirm: false,
    preConfirm: function () {
      var popup = Swal.getPopup && Swal.getPopup();
      var input = popup ? popup.querySelector('#interes_nuevo_venta') : null;
      var interesStr = input ? input.value : '';
      var interes = parseFloat(String(interesStr || '').replace(',', '.'));
      var idPrecio = popup ? popup.querySelector('#id_autorizacion_interes_nuevo') : null;
      var idAut2 = idPrecio ? idPrecio.value : '';
      var idAut2Num = parseInt(String(idAut2 || ''), 10);
      if (!idAut2Num || isNaN(interes) || interes < 0 || interes > 100) {
        Swal.showValidationMessage('Indique un interés válido (0-100).');
        return false;
      }
      var subtotal =
        typeof window.obtenerSubtotalVentaSinInteres === 'function'
          ? window.obtenerSubtotalVentaSinInteres()
          : 0;
      var precioNuevo = Math.round(subtotal * (1 + interes / 100) * 100) / 100;
      var fdUp = new FormData();
      fdUp.append('id_autorizacion', String(idAut2Num));
      fdUp.append('interes_nuevo', String(interes));
      fdUp.append('precio_nuevo', String(precioNuevo));
      return fetch(apiBase + 'actualizar_intereses_autorizacion_venta.php', {
        method: 'POST',
        body: fdUp,
        credentials: 'same-origin'
      })
        .then(function (r) {
          return r.json();
        })
        .then(function (res2) {
          if (!res2 || !res2.success) {
            Swal.showValidationMessage((res2 && res2.message) ? res2.message : 'No se pudo actualizar.');
            return false;
          }
          return { interes: interes, precioNuevo: precioNuevo };
        })
        .catch(function () {
          Swal.showValidationMessage('Error al aplicar el interés.');
          return false;
        });
    }
  }).then(function (r2) {
    if (r2.isConfirmed && r2.value && r2.value.interes !== undefined) {
      window.interesPlazosVentaPersonalizado = parseFloat(r2.value.interes);
      if (typeof window.calcularTotales === 'function') {
        window.calcularTotales();
      }
      Swal.fire({
        icon: 'success',
        title: 'Actualizado',
        text: 'Interés aplicado correctamente.',
        timer: 2000,
        showConfirmButton: false
      });
    }
  });
}

function solicitarCambioInteresesVenta() {
  interesesFlujoAuthContinuado = false;
  const tipoPlazos = document.getElementById('tipo_venta_plazos');
  if (!tipoPlazos || !tipoPlazos.checked) {
    Swal.fire({ icon: 'info', title: 'Venta a plazos', text: 'Seleccione el tipo de venta a plazos.' });
    return;
  }

  if (!window.articulosVenta || window.articulosVenta.length === 0) {
    Swal.fire({ icon: 'warning', title: 'Atención', text: 'Debe agregar al menos un artículo a la venta.' });
    return;
  }

  const sucursal = parseInt($('#insert_id_sucursal').val(), 10) || 0;

  const numeroPlazosRadio = document.querySelector('input[name="numero_plazos"]:checked');
  const numeroPlazosVal = numeroPlazosRadio ? numeroPlazosRadio.value : '3';
  const interesesOriginales =
    typeof window.obtenerInteresPctPlazos === 'function' ? window.obtenerInteresPctPlazos(numeroPlazosVal) : 0;
  const precioOriginal =
    typeof window.obtenerSubtotalVentaSinInteres === 'function' ? window.obtenerSubtotalVentaSinInteres() : 0;
  const idsArticulos = window.articulosVenta
    .map(function (a) {
      return parseInt(a.id_articulo, 10);
    })
    .filter(function (id) {
      return id > 0;
    })
    .join(',');

  if (!idsArticulos) {
    Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudieron obtener los artículos de la venta.' });
    return;
  }

  const apiBase = window.VENTA_CREAR_API || 'parts/ventas/crear/';

  Swal.fire({
    title: '¿Está seguro?',
    text: '¿Está seguro que desea solicitar cambio de intereses?',
    icon: 'question',
    showCancelButton: true,
    confirmButtonText: 'Sí, solicitar',
    cancelButtonText: 'Cancelar',
    confirmButtonColor: '#007bff',
    cancelButtonColor: '#6c757d'
  }).then(function (result) {
    if (!result.isConfirmed) {
      return;
    }

    const fd = new FormData();
    fd.append('sucursal', String(sucursal));
    fd.append('ids_articulos', idsArticulos);
    fd.append('intereses_originales', String(interesesOriginales));
    fd.append('precio_original', String(precioOriginal));

    fetch(apiBase + 'insertar_autorizacion_intereses_venta.php', {
      method: 'POST',
      body: fd,
      credentials: 'same-origin'
    })
      .then(function (r) {
        return r.json();
      })
      .then(function (data) {
        if (!data || !data.success || !data.id_autorizacion) {
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: (data && data.message) ? data.message : 'No se pudo crear la solicitud.'
          });
          return;
        }

        var idAutCreado = null;
        Swal.fire({
          title: 'Solicitud de autorización',
          html:
            '<input id="codigo_autorizacion_intereses" class="swal2-input" placeholder="Código" maxlength="5" />' +
            '<input type="hidden" id="id_autorizacion_intereses" value="' + String(data.id_autorizacion) + '"/>',
          showCancelButton: true,
          confirmButtonText: 'Comprobar código',
          cancelButtonText: 'Cancelar',
          focusConfirm: false,
          didOpen: function () {
            iniciarPollEstadoAutorizacionIntereses(data.id_autorizacion, apiBase, interesesOriginales);
          },
          willClose: function () {
            detenerPollEstadoAutorizacionIntereses();
          },
          preConfirm: function () {
            const popup = Swal.getPopup && Swal.getPopup();
            const codigoInput = popup ? popup.querySelector('#codigo_autorizacion_intereses') : null;
            const idInput = popup ? popup.querySelector('#id_autorizacion_intereses') : null;
            const codigo = codigoInput ? codigoInput.value : '';
            const idAut = idInput ? idInput.value : '';
            const idNum = parseInt(String(idAut || ''), 10);
            const codigoTxt = String(codigo || '').trim();
            if (!idNum || codigoTxt.length === 0) {
              Swal.showValidationMessage('Indique el código.');
              return false;
            }
            idAutCreado = idNum;
            const fdCod = new FormData();
            fdCod.append('id_autorizacion', String(idNum));
            fdCod.append('codigo', codigoTxt);
            return fetch(apiBase + 'comprobar_codigo_autorizacion_intereses_venta.php', {
              method: 'POST',
              body: fdCod,
              credentials: 'same-origin'
            })
              .then(function (r) {
                return r.json();
              })
              .then(function (res) {
                if (!res || !res.success) {
                  Swal.showValidationMessage((res && res.message) ? res.message : 'Código incorrecto');
                  return false;
                }
                return true;
              })
              .catch(function () {
                Swal.showValidationMessage('Error al comprobar el código.');
                return false;
              });
          }
        }).then(function (res) {
          if (interesesFlujoAuthContinuado) {
            return;
          }
          if (!res.isConfirmed) {
            return;
          }
          var idNum = parseInt(String(idAutCreado || ''), 10);
          abrirSwalNuevoInteresVenta(idNum, interesesOriginales, apiBase);
        });
      })
      .catch(function () {
        Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo conectar para crear la solicitud.' });
      });
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
      const idArticulo = parseInt(tr && tr.dataset ? tr.dataset.articuloId : 0, 10) || 0;

      const sucursal = parseInt($('#insert_id_sucursal').val(), 10) || 0;

      // Precio original tomado del array (evita problemas con el formateo del DOM)
      let precioOriginal = 0;
      if (window.articulosVenta && window.articulosVenta.length > 0) {
        const art =
          window.articulosVenta.find(a => parseInt(a.id_articulo, 10) === idArticulo) ||
          window.articulosVenta.find(a => String(a.sku || '') === String(tr && tr.dataset ? tr.dataset.articuloSku : ''));
        if (art) {
          if (art.precio_original !== undefined && art.precio_original !== null) {
            precioOriginal = parseFloat(art.precio_original) || 0;
          } else if (art.precio !== undefined && art.precio !== null) {
            precioOriginal = parseFloat(art.precio) || 0;
          }
        }
      }

      if (!idArticulo) {
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: 'No se pudo determinar el artículo.'
        });
        return;
      }

      const fd = new FormData();
      fd.append('id_articulo', String(idArticulo));
      fd.append('sucursal', String(sucursal));
      fd.append('precio_original', String(precioOriginal));

      fetch('parts/ventas/crear/insertar_autorizacion_descuento_articulo_venta.php', {
        method: 'POST',
        body: fd,
        credentials: 'same-origin'
      })
        .then(function (r) {
          return r.json();
        })
        .then(function (data) {
          if (!data || !data.success || !data.id_autorizacion) {
            Swal.fire({
              icon: 'error',
              title: 'Error',
              text: (data && data.message) ? data.message : 'No se pudo crear la solicitud.'
            });
            return;
          }

          var idAutCreado = null;
          Swal.fire({
            title: 'Solicitud de autorización',
            html:
              '<input id="codigo_autorizacion" class="swal2-input" placeholder="Código" maxlength="5" />' +
              '<input type="hidden" id="id_autorizacion" value="' + String(data.id_autorizacion) + '"/>',
            showCancelButton: true,
            confirmButtonText: 'Comprobar codigo',
            cancelButtonText: 'Cancelar',
            focusConfirm: false,
            preConfirm: () => {
              const popup = Swal.getPopup && Swal.getPopup();
              const codigoInput = popup ? popup.querySelector('#codigo_autorizacion') : null;
              const idInput = popup ? popup.querySelector('#id_autorizacion') : null;
              const codigo = codigoInput ? codigoInput.value : '';
              const idAut = idInput ? idInput.value : '';
              const idNum = parseInt(String(idAut || ''), 10);
              const codigoTxt = String(codigo || '').trim();

              if (!idNum || codigoTxt.length === 0) {
                Swal.showValidationMessage('Indique el código.');
                return false;
              }
              idAutCreado = idNum;

              const fd = new FormData();
              fd.append('id_autorizacion', String(idNum));
              fd.append('codigo', codigoTxt);

              return fetch('parts/ventas/crear/comprobar_codigo_autorizacion_descuento_articulo_venta.php', {
                method: 'POST',
                body: fd,
                credentials: 'same-origin'
              })
                .then(function (r) {
                  return r.json();
                })
                .then(function (res) {
                  if (!res || !res.success) {
                    Swal.showValidationMessage((res && res.message) ? res.message : 'Código incorrecto');
                    return false;
                  }
                  return true;
                })
                .catch(function () {
                  Swal.showValidationMessage('Error al comprobar el código.');
                  return false;
                });
            }
          }).then(function (res) {
            if (res.isConfirmed) {
              var idNum = parseInt(String(idAutCreado || ''), 10);
              var precioNuevoGuardado = null;

              Swal.fire({
                title: 'Precio nuevo',
                html:
                  '<input id="precio_nuevo" type="number" class="swal2-input" placeholder="Precio nuevo" step="0.01" min="0" />' +
                  '<input type="hidden" id="id_autorizacion_precio" value="' + String(idNum) + '"/>',
                showCancelButton: true,
                confirmButtonText: 'Enviar precio',
                cancelButtonText: 'Cancelar',
                focusConfirm: false,
                preConfirm: function () {
                  var popup = Swal.getPopup && Swal.getPopup();
                  var input = popup ? popup.querySelector('#precio_nuevo') : null;
                  var precioStr = input ? input.value : '';
                  var precio = parseFloat(String(precioStr || '').replace(',', '.'));
                  var idPrecio = popup ? popup.querySelector('#id_autorizacion_precio') : null;
                  var idAut2 = idPrecio ? idPrecio.value : '';
                  var idAut2Num = parseInt(String(idAut2 || ''), 10);

                  if (!idAut2Num || isNaN(precio) || precio < 0) {
                    Swal.showValidationMessage('Indique un precio válido.');
                    return false;
                  }

                  var fd = new FormData();
                  fd.append('id_autorizacion', String(idAut2Num));
                  fd.append('precio_nuevo', String(precio));

                  return fetch('parts/ventas/crear/actualizar_precio_autorizacion_descuento_articulo_venta.php', {
                    method: 'POST',
                    body: fd,
                    credentials: 'same-origin'
                  })
                    .then(function (r) {
                      return r.json();
                    })
                    .then(function (res2) {
                      if (!res2 || !res2.success) {
                        Swal.showValidationMessage((res2 && res2.message) ? res2.message : 'No se pudo actualizar.');
                        return false;
                      }
                      precioNuevoGuardado = precio;
                      return true;
                    })
                    .catch(function () {
                      Swal.showValidationMessage('Error al enviar el precio.');
                      return false;
                    });
                }
              }).then(function (r2) {
                if (r2.isConfirmed && precioNuevoGuardado !== null && !isNaN(precioNuevoGuardado)) {
                  var idArtRow = parseInt(tr && tr.dataset ? tr.dataset.articuloId : 0, 10) || 0;
                  var skuRow = tr && tr.dataset ? String(tr.dataset.articuloSku || '') : '';
                  if (window.articulosVenta && window.articulosVenta.length > 0) {
                    var artUpd =
                      window.articulosVenta.find(function (a) {
                        return parseInt(a.id_articulo, 10) === idArtRow;
                      }) ||
                      window.articulosVenta.find(function (a) {
                        return String(a.sku || '') === skuRow;
                      });
                    if (artUpd) {
                      artUpd.precio = precioNuevoGuardado;
                      artUpd.precio_original = precioNuevoGuardado;
                    }
                  }
                  var tds = tr ? tr.querySelectorAll('td') : null;
                  if (tds && tds.length >= 4) {
                    var uds = 1;
                    if (window.articulosVenta && window.articulosVenta.length > 0) {
                      var artU = window.articulosVenta.find(function (a) {
                        return parseInt(a.id_articulo, 10) === idArtRow;
                      });
                      if (artU && artU.unidades !== undefined && artU.unidades !== null) {
                        uds = parseFloat(artU.unidades) || 1;
                      }
                    }
                    var importeLinea = precioNuevoGuardado * uds;
                    tds[2].textContent = number_format(precioNuevoGuardado, 2) + ' €';
                    tds[3].textContent = number_format(importeLinea, 2) + ' €';
                  }
                  if (typeof actualizarInputsArticulos === 'function') {
                    actualizarInputsArticulos();
                  }
                  if (typeof window.calcularTotales === 'function') {
                    window.calcularTotales();
                  }
                  Swal.fire({
                    icon: 'success',
                    title: 'Actualizado',
                    text: 'Precio nuevo guardado correctamente.',
                    timer: 2000,
                    showConfirmButton: false
                  });
                }
              });
            }
          });
        })
        .catch(function () {
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'No se pudo conectar para crear la solicitud.'
          });
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
 * Evita autocompletado del navegador en el número de identificación.
 */
function desactivarAutocompletadoIdentificacionModal() {
  const el = document.getElementById('modal_identificacion');
  if (!el) return;

  el.setAttribute('autocomplete', 'new-password');
  el.setAttribute('autocapitalize', 'off');
  el.setAttribute('autocorrect', 'off');
  el.setAttribute('spellcheck', 'false');
  el.setAttribute('data-lpignore', 'true');
  el.setAttribute('data-1p-ignore', 'true');
  el.setAttribute('data-form-type', 'other');
  el.setAttribute('name', 'doc_id_manual');

  el.setAttribute('readonly', 'readonly');
  const quitarReadonly = function () {
    el.removeAttribute('readonly');
    el.removeEventListener('focus', quitarReadonly);
  };
  el.addEventListener('focus', quitarReadonly);
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
  
  // Solo comprobar al salir del campo (escritura manual, sin sugerencias del navegador)
  if (inputIdentificacion.dataset.verificacionBlur === '1') {
    return;
  }
  inputIdentificacion.dataset.verificacionBlur = '1';

  inputIdentificacion.addEventListener('blur', function() {
    clearTimeout(timeoutVerificacion);
    const identificacion = this.value.trim();
    if (identificacion.length >= 5) {
      timeoutVerificacion = setTimeout(() => {
        verificarIdentificacion(identificacion);
      }, 200);
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
            /*
            // También cerrar el modal y mostrar los datos
            setTimeout(() => {
              guardarDatosCliente();
            }, 1000);
            */
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
  $('#form_datos_cliente').find('input, select, textarea').prop('disabled', false);

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
    document.querySelectorAll('.custom-option-tipo_venta').forEach(option => {
      option.classList.remove('checked');
    });
    const parentOption = tipoVentaNormal.closest('.custom-option-tipo_venta');
    if (parentOption) {
      parentOption.classList.add('checked');
    }
  }
  
  // Deseleccionar todos los radio buttons de forma de pago
  const formaPagoRadios = document.querySelectorAll('input[name="forma_pago"]');
  formaPagoRadios.forEach(radio => {
    radio.checked = false;
    // Remover clase checked del contenedor
    const parentOption = radio.closest('.option-forma-pago');
    if (parentOption) {
      parentOption.classList.remove('checked');
    }
  });
  
  
  // Actualizar totales
  window.calcularTotales();
}

