/**
 * Presupuesto: capa sobre presupuesto-form-base.js (cliente + artículos) y guarda en presupuestos.
 */
'use strict';

function tipoLineaDesdeArticulo(tipo) {
  if (!tipo) return 'producto';
  var t = String(tipo).toLowerCase();
  if (t.indexOf('servicio') !== -1) return 'servicio';
  return 'producto';
}

/**
 * Carga líneas guardadas al editar un presupuesto (después de presupuesto-form-base.js).
 */
function presupuestoAplicarLineasEdicion(lineas) {
  if (!lineas || !lineas.length) {
    return;
  }
  window.articulosVenta = [];
  var tbody = document.getElementById('articulos_venta_body');
  if (!tbody) {
    return;
  }
  tbody.innerHTML = '';

  var nf =
    typeof window.number_format === 'function'
      ? window.number_format
      : function (n, d) {
          return parseFloat(n).toFixed(d || 2);
        };

  lineas.forEach(function (ln) {
    var tipo = (ln.tipo || 'producto').toLowerCase();
    if (tipo === 'comentario') {
      return;
    }
    if (tipo === 'servicio') {
      window.articulosVenta.push({
        id_articulo: parseInt(ln.id_articulo, 10) || 0,
        es_servicio: true,
        sku: String(ln.referencia || '').trim(),
        descripcion: ln.descripcion || '',
        unidades: parseFloat(ln.cantidad) || 1,
        peso: 0,
        tipo: 'servicio',
        precio: parseFloat(ln.precio_unitario) || 0
      });
    } else {
      window.articulosVenta.push({
        id_articulo: parseInt(ln.id_articulo, 10) || 0,
        es_servicio: false,
        sku: String(ln.referencia || '').trim(),
        descripcion: ln.descripcion || '',
        unidades: parseFloat(ln.cantidad) || 1,
        peso: 0,
        tipo: 'producto',
        precio: parseFloat(ln.precio_unitario) || 0
      });
    }
  });

  if (typeof actualizarInputsArticulos === 'function') {
    actualizarInputsArticulos();
  }

  window.articulosVenta.forEach(function (art, idx) {
    var tr = document.createElement('tr');
    tr.className = 'fila-guardada';
    tr.dataset.index = String(idx);
    tr.dataset.articuloId = String(art.id_articulo);
    tr.dataset.articuloSku = art.sku;
    if (art.es_servicio) {
      tr.dataset.esServicio = '1';
    }
    var unit = parseFloat(art.precio) || 0;
    var qty = parseFloat(art.unidades) || 1;
    var lineTotal = unit * qty;
    var subTxt = art.es_servicio
      ? art.sku + ' · Servicio'
      : art.sku + ' · ' + nf(0, 2) + ' g · ' + (art.tipo || 'producto');
    tr.innerHTML =
      '<td><div><span>' +
      (art.descripcion || '') +
      '</span><br><small class="text-muted">' +
      subTxt +
      '</small></div></td>' +
      '<td class="text-center">' +
      qty +
      '</td>' +
      '<td class="text-start">' +
      nf(unit, 2) +
      ' €</td>' +
      '<td class="text-start">' +
      nf(lineTotal, 2) +
      ' €</td>' +
      '<td><a href="javascript:;" class="btn btn-text-primary waves-effect waves-light discount-record p-0 me-2" title="Solicitar autorización para cambiar precio"><i class="icon-base ri ri-discount-percent-fill icon-22px"></i></a>' +
      '<a href="javascript:;" class="btn btn-text-danger waves-effect waves-light delete-record p-0" title="Eliminar línea"><i class="icon-base ri ri-close-line icon-22px"></i></a></td>';
    tbody.appendChild(tr);
  });

  if (typeof window.calcularTotales === 'function') {
    window.calcularTotales();
  }
}

document.addEventListener('DOMContentLoaded', function () {
  if (typeof window.PRESUPUESTO_EDICION_BOOTSTRAP !== 'undefined' && window.PRESUPUESTO_EDICION_BOOTSTRAP && window.PRESUPUESTO_EDICION_BOOTSTRAP.lineas) {
    presupuestoAplicarLineasEdicion(window.PRESUPUESTO_EDICION_BOOTSTRAP.lineas);
  }
  // Verificación DNI / documento: endpoint de presupuestos (misma lógica que ventas)
  window.verificarIdentificacion = function (identificacion) {
    $.ajax({
      url: 'parts/presupuestos/crear/ajax_verificar_cliente.php',
      data: {
        action: 'verificar_identificacion',
        valor: identificacion
      },
      dataType: 'json',
      success: function (response) {
        if (response.existe) {
          Swal.fire({
            icon: 'info',
            title: 'Cliente encontrado',
            text: response.message,
            confirmButtonText: '¿Cargar datos?',
            showCancelButton: true,
            cancelButtonText: 'No, crear nuevo'
          }).then(function (result) {
            if (result.isConfirmed) {
              cargarDatosCliente(response.cliente, response.direccion, response.datos_cliente);
              setTimeout(function () {
                guardarDatosCliente();
              }, 1000);
            }
          });
        }
      },
      error: function (xhr, status, error) {
        console.error('Error al verificar identificación:', error);
      }
    });
  };

  var origCalc = window.calcularTotales;
  window.calcularTotales = function () {
    if (typeof origCalc === 'function') {
      origCalc();
    }
    var sub = 0;
    if (window.articulosVenta && window.articulosVenta.length) {
      window.articulosVenta.forEach(function (articulo) {
        var pu = parseFloat(articulo.precio) || 0;
        var uds = parseFloat(articulo.unidades) || 1;
        sub += pu * uds;
      });
    }
    var pct =
      parseFloat(
        document.getElementById('presupuesto_porcentaje_iva') &&
          document.getElementById('presupuesto_porcentaje_iva').value
      ) || 0;
    var iva = Math.round(sub * (pct / 100) * 100) / 100;
    var total = Math.round((sub + iva) * 100) / 100;

    if (document.getElementById('subtotal_venta')) {
      document.getElementById('subtotal_venta').textContent = window.number_format(sub, 2) + ' €';
    }
    if (document.getElementById('iva_venta')) {
      document.getElementById('iva_venta').textContent = window.number_format(iva, 2) + ' €';
    }
    if (document.getElementById('total_venta')) {
      document.getElementById('total_venta').textContent = window.number_format(total, 2) + ' €';
    }
    if (document.getElementById('total_resumen')) {
      document.getElementById('total_resumen').textContent = window.number_format(total, 2) + ' €';
    }
    var ivLabel = document.getElementById('iva_venta_label');
    if (ivLabel) {
      ivLabel.textContent = 'IVA (' + pct + '%):';
    }
  };

  $('#presupuesto_porcentaje_iva').on('change input', function () {
    window.calcularTotales();
  });

  if (typeof window.calcularTotales === 'function') {
    window.calcularTotales();
  }

  function confirmarSalida() {
    Swal.fire({
      icon: 'warning',
      title: '¿Salir?',
      text: 'Se perderán los cambios no guardados.',
      showCancelButton: true,
      confirmButtonText: 'Sí, salir',
      cancelButtonText: 'Continuar',
      confirmButtonColor: '#dc3545',
      reverseButtons: true
    }).then(function (result) {
      if (result.isConfirmed) {
        window.location.href = 'presupuestos.php';
      }
    });
  }

  $('#btn_volver_presupuestos').on('click', function (e) {
    e.preventDefault();
    confirmarSalida();
  });

  $('#btn_cancelar_presupuesto').on('click', function (e) {
    e.preventDefault();
    confirmarSalida();
  });

  $('#btn_guardar_presupuesto').on('click', function () {
    var titulo = ($('#presupuesto_titulo').val() || '').trim();
    if (!titulo) {
      Swal.fire('Revisar', 'Indique un título para el presupuesto', 'warning');
      return;
    }

    var idCliente = parseInt($('#insert_id_cliente').val(), 10) || 0;
    if (!idCliente) {
      Swal.fire('Revisar', 'Debe indicar los datos del cliente (modal)', 'warning');
      return;
    }

    if (!window.articulosVenta || window.articulosVenta.length === 0) {
      Swal.fire('Revisar', 'Debe agregar al menos un artículo o servicio (SKU)', 'warning');
      return;
    }

    var idEmpresa = parseInt($('#presupuesto_rel_id_empresa').val(), 10) || 0;
    if (!idEmpresa) {
      Swal.fire('Error', 'No se pudo determinar la empresa de la sucursal', 'error');
      return;
    }

    var pctIva =
      parseFloat($('#presupuesto_porcentaje_iva').val()) || 21;
    var lineas = [];
    window.articulosVenta.forEach(function (art, idx) {
      lineas.push({
        orden: idx + 1,
        tipo: tipoLineaDesdeArticulo(art.tipo),
        id_articulo: parseInt(art.id_articulo, 10) || 0,
        referencia: String(art.sku || '').trim(),
        descripcion: art.descripcion || '',
        cantidad: parseFloat(art.unidades) || 1,
        unidad: 'ud',
        precio_unitario: parseFloat(art.precio) || 0,
        porcentaje_dto: 0,
        porcentaje_iva: pctIva
      });
    });

    var notasInt = ($('#presupuesto_notas_internas').val() || '').trim();
    var obs = ($('#observaciones_venta').val() || '').trim();
    if (obs) {
      notasInt = notasInt ? notasInt + '\n\n' + obs : obs;
    }

    var payload = {
      rel_id_empresa: idEmpresa,
      titulo: titulo,
      descripcion: $('#presupuesto_descripcion').val() || '',
      notas_cliente: $('#presupuesto_notas_cliente').val() || '',
      notas_internas: notasInt,
      condiciones: $('#presupuesto_condiciones').val() || '',
      id_cliente: idCliente,
      fecha_validez: $('#presupuesto_fecha_validez').val() || '',
      porcentaje_iva: pctIva,
      estado: $('#presupuesto_estado').val() || 'borrador',
      lineas: lineas
    };

    var idEdicion = parseInt($('#id_presupuesto_edicion').val(), 10) || 0;
    if (idEdicion) {
      payload.id_presupuesto = idEdicion;
    }

    var urlGuardar = idEdicion
      ? 'parts/presupuestos/editar/actualizar_presupuesto.php'
      : 'parts/presupuestos/crear/guardar_presupuesto.php';

    var btn = $('#btn_guardar_presupuesto');
    btn.prop('disabled', true);

    fetch(urlGuardar, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    })
      .then(function (r) {
        return r.json().then(function (j) {
          return { ok: r.ok, j: j };
        });
      })
      .then(function (res) {
        btn.prop('disabled', false);
        if (!res.ok || !res.j.success) {
          var msg = res.j && res.j.message ? res.j.message : 'Error al guardar';
          Swal.fire('Error', msg, 'error');
          return;
        }
        Swal.fire({
          icon: 'success',
          title: idEdicion ? 'Actualizado' : 'Guardado',
          text: res.j.numero
            ? 'Presupuesto ' + res.j.numero + (idEdicion ? ' actualizado' : '')
            : idEdicion
              ? 'Cambios guardados'
              : 'Presupuesto creado'
        }).then(function () {
          window.location.href = 'presupuestos.php';
        });
      })
      .catch(function (err) {
        btn.prop('disabled', false);
        console.error(err);
        Swal.fire('Error', 'No se pudo guardar', 'error');
      });
  });
});

/**
 * Tras buscar artículo por SKU sin resultado, intenta servicio del catálogo (misma empresa que la sucursal).
 */
(function () {
  var anadiendoServicioPresupuesto = false;

  window.agregarServicioLineaPresupuesto = function (servicio, q) {
    if (anadiendoServicioPresupuesto) {
      return;
    }
    anadiendoServicioPresupuesto = true;

    var ref =
      servicio.codigo && String(servicio.codigo).trim()
        ? String(servicio.codigo).trim()
        : 'SVC-' + servicio.id;
    var item = {
      id_articulo: parseInt(servicio.id, 10),
      es_servicio: true,
      sku: ref,
      descripcion: servicio.nombre || servicio.descripcion || '',
      unidades: 1,
      peso: 0,
      tipo: 'servicio',
      precio: parseFloat(servicio.precio) || 0
    };

    var dup = window.articulosVenta.find(function (art) {
      return art.es_servicio && parseInt(art.id_articulo, 10) === item.id_articulo;
    });
    if (dup) {
      anadiendoServicioPresupuesto = false;
      Swal.fire({
        icon: 'warning',
        title: 'Duplicado',
        text: 'Este servicio ya está en el presupuesto',
        timer: 3000
      }).then(function () {
        var inputSku = document.querySelector('.input-sku-articulo');
        if (inputSku) {
          inputSku.value = '';
          inputSku.focus();
        }
      });
      return;
    }

    window.articulosVenta.push(item);
    if (typeof actualizarInputsArticulos === 'function') {
      actualizarInputsArticulos();
    }

    var tbody = document.getElementById('articulos_venta_body');
    if (tbody) {
      var mensajeVacio = tbody.querySelector('td[colspan="5"]');
      if (mensajeVacio) {
        mensajeVacio.parentElement.remove();
      }
    }

    var tr = document.createElement('tr');
    tr.className = 'fila-guardada';
    tr.dataset.index = window.articulosVenta.length - 1;
    tr.dataset.articuloId = String(item.id_articulo);
    tr.dataset.articuloSku = item.sku;
    tr.dataset.esServicio = '1';

    var sub = ref + ' · Servicio';
    if (servicio.tipo_facturacion) {
      sub += ' · ' + String(servicio.tipo_facturacion).replace(/_/g, ' ');
    }

    var nf = typeof window.number_format === 'function' ? window.number_format : function (n, d) {
      return parseFloat(n).toFixed(d || 2);
    };

    tr.innerHTML =
      '<td><div><span>' +
      (item.descripcion || '') +
      '</span><br><small class="text-muted">' +
      sub +
      '</small></div></td>' +
      '<td class="text-center">' +
      item.unidades +
      '</td>' +
      '<td class="text-start">' +
      nf(parseFloat(item.precio), 2) +
      ' €</td>' +
      '<td class="text-start">' +
      nf(parseFloat(item.precio), 2) +
      ' €</td>' +
      '<td>' +
      '<a href="javascript:;" class="btn btn-text-primary waves-effect waves-light discount-record p-0 me-2" title="Solicitar autorización para cambiar precio">' +
      '<i class="icon-base ri ri-discount-percent-fill icon-22px"></i></a>' +
      '<a href="javascript:;" class="btn btn-text-danger waves-effect waves-light delete-record p-0" title="Eliminar línea">' +
      '<i class="icon-base ri ri-close-line icon-22px"></i></a></td>';

    tbody.appendChild(tr);

    var inputSku = document.querySelector('.input-sku-articulo');
    if (inputSku) {
      inputSku.value = '';
      setTimeout(function () {
        inputSku.focus();
      }, 100);
    }

    if (typeof window.calcularTotales === 'function') {
      window.calcularTotales();
    }

    setTimeout(function () {
      anadiendoServicioPresupuesto = false;
    }, 500);
  };

  window.buscarArticuloPorSku = function (sku) {
    var idSucursal = $('#insert_id_sucursal').val();
    if (!idSucursal) {
      Swal.fire({
        icon: 'warning',
        title: 'Atención',
        text: 'Debe seleccionar una sucursal primero',
        timer: 3000
      });
      return;
    }
    var q = String(sku).trim();
    var idEmpresa = parseInt($('#presupuesto_rel_id_empresa').val(), 10) || 0;

    if (!idEmpresa) {
      Swal.fire({
        icon: 'warning',
        title: 'Atención',
        text: 'No se ha determinado la empresa del presupuesto',
        timer: 4000
      });
      return;
    }

    $.ajax({
      url: 'parts/presupuestos/crear/buscar_articulo_presupuesto.php',
      data: { q: q, rel_id_empresa: idEmpresa, id_sucursal: idSucursal },
      dataType: 'json',
      success: function (response) {
        if (response.success && response.encontrado) {
          agregarArticuloAutomaticamente(response.articulo, q);
          return;
        }
        $.ajax({
          url: 'parts/presupuestos/crear/buscar_servicio_presupuesto.php',
          data: { q: q, rel_id_empresa: idEmpresa },
          dataType: 'json',
          success: function (r2) {
            if (r2.success && r2.encontrado && r2.servicio) {
              window.agregarServicioLineaPresupuesto(r2.servicio, q);
            }
          },
          error: function (xhr, st, err) {
            console.error('buscar servicio presupuesto:', err);
          }
        });
      },
      error: function (xhr, status, error) {
        console.error('Error AJAX al buscar artículo:', error);
      }
    });
  };
})();
