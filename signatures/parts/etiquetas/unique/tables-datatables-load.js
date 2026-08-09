/**
 * Etiquetas pendientes de impresión
 */
'use strict';

document.addEventListener('DOMContentLoaded', function () {
  const dtTable = document.querySelector('.datatables-etiquetas-pendientes');
  let dtEtiquetas;
  let recargaEtiquetasProgramada = false;

  function recargarListaEtiquetas() {
    if (dtEtiquetas) {
      dtEtiquetas.ajax.reload(null, false);
    }
    if (typeof window.cargarTotalEtiquetasPendientes === 'function') {
      window.cargarTotalEtiquetasPendientes();
    }
  }

  function recargarListaEtiquetasUnaVez() {
    if (recargaEtiquetasProgramada) {
      return;
    }
    recargaEtiquetasProgramada = true;
    recargarListaEtiquetas();
    window.setTimeout(function () {
      recargaEtiquetasProgramada = false;
    }, 1500);
  }

  function abrirPopupImpresionEtiqueta(url) {
    const width = 640;
    const height = 720;
    const left = Math.max(0, Math.floor((window.screen.width - width) / 2));
    const top = Math.max(0, Math.floor((window.screen.height - height) / 2));
    const features =
      'popup=yes,width=' + width + ',height=' + height + ',left=' + left + ',top=' + top + ',menubar=no,toolbar=no,location=no,status=no';
    return window.open(url, '_blank', features);
  }

  function vigilarCierrePopupImpresion(win) {
    if (!win) return;
    const maxMs = 5 * 60 * 1000;
    const startedAt = Date.now();
    const timer = window.setInterval(function () {
      if (Date.now() - startedAt > maxMs) {
        window.clearInterval(timer);
        return;
      }
      if (win.closed) {
        window.clearInterval(timer);
        recargarListaEtiquetasUnaVez();
      }
    }, 600);
  }

  window.addEventListener('message', function (event) {
    if (!event || !event.origin || event.origin !== window.location.origin) {
      return;
    }
    if (!event.data || typeof event.data !== 'object') {
      return;
    }
    if (event.data.type === 'etiqueta:printed') {
      recargarListaEtiquetasUnaVez();
    }
  });

  function obtenerSkuDesdeEnlaceImpresion(link, href) {
    const dataSku = link.getAttribute('data-sku');
    if (dataSku) {
      return parseInt(String(dataSku), 10) || 0;
    }
    try {
      const url = new URL(href, window.location.origin);
      const id = url.searchParams.get('id_articulo');
      return id ? parseInt(String(id), 10) || 0 : 0;
    } catch (e) {
      const match = href.match(/id_articulo=(\d+)/);
      return match ? parseInt(match[1], 10) || 0 : 0;
    }
  }

  function confirmarImpresionIndividualEtiqueta(sku, onConfirm) {
    const idArticulo = parseInt(String(sku), 10) || 0;
    const refArticulo = idArticulo > 0 ? ' del artículo <strong>SKU ' + idArticulo + '</strong>' : ' del artículo';
    const mensaje = '¿Está seguro de imprimir la etiqueta' + refArticulo + '?';

    if (typeof Swal === 'undefined') {
      const textoPlano = idArticulo > 0
        ? '¿Está seguro de imprimir la etiqueta del artículo SKU ' + idArticulo + '?'
        : '¿Está seguro de imprimir la etiqueta del artículo?';
      if (window.confirm('Imprimir etiqueta\n\n' + textoPlano)) {
        onConfirm();
      }
      return;
    }

    Swal.fire({
      icon: 'warning',
      title: 'Imprimir etiqueta',
      html: mensaje,
      showCancelButton: true,
      confirmButtonText: 'Sí, imprimir',
      cancelButtonText: 'Cancelar',
      showCloseButton: true,
      allowOutsideClick: false
    }).then(function (result) {
      if (result.isConfirmed) {
        onConfirm();
      }
    });
  }

  function confirmarImpresionMasivaEtiquetas(cantidad, onConfirm) {
    const total = parseInt(String(cantidad), 10) || 0;
    const mensaje = '¿Está seguro de imprimir todas las etiquetas?<br>Se imprimirán <strong>' + total + '</strong> etiquetas.';

    if (typeof Swal === 'undefined') {
      if (window.confirm('Imprimir todas las etiquetas\n\n¿Está seguro de imprimir todas las etiquetas? Se imprimirán ' + total + ' etiquetas.')) {
        onConfirm();
      }
      return;
    }

    Swal.fire({
      icon: 'warning',
      title: 'Imprimir todas las etiquetas',
      html: mensaje,
      showCancelButton: true,
      confirmButtonText: 'Sí, imprimir',
      cancelButtonText: 'Cancelar',
      showCloseButton: true,
      allowOutsideClick: false
    }).then(function (result) {
      if (result.isConfirmed) {
        onConfirm();
      }
    });
  }

  function abrirImpresionEtiquetaDesdeEnlace(href) {
    const win = abrirPopupImpresionEtiqueta(href);
    if (!win) {
      window.location.href = href;
      return;
    }
    vigilarCierrePopupImpresion(win);
  }

  document.addEventListener('click', function (ev) {
    const linkMasivo = ev.target && ev.target.closest ? ev.target.closest('.etiqueta-print-link-masivo') : null;
    const linkIndividual = ev.target && ev.target.closest ? ev.target.closest('.etiqueta-print-link') : null;
    const link = linkMasivo || linkIndividual;
    if (!link) {
      return;
    }
    if (ev.button !== 0 || ev.metaKey || ev.ctrlKey || ev.shiftKey || ev.altKey) {
      return;
    }
    const href = link.getAttribute('href') || '';
    if (!href || href.indexOf('etiquetas_articulos.php') === -1) {
      return;
    }
    ev.preventDefault();

    if (linkMasivo) {
      const totalEl = document.getElementById('total_etiquetas');
      const total = totalEl ? parseInt(String(totalEl.textContent).replace(/\D/g, ''), 10) || 0 : 0;
      confirmarImpresionMasivaEtiquetas(total, function () {
        abrirImpresionEtiquetaDesdeEnlace(href);
      });
      return;
    }

    const sku = obtenerSkuDesdeEnlaceImpresion(linkIndividual, href);
    confirmarImpresionIndividualEtiqueta(sku, function () {
      abrirImpresionEtiquetaDesdeEnlace(href);
    });
  });

  function actualizarBotonImprimirMasivo() {
    const btn = document.getElementById('btn_imprimir_etiquetas_masivo');
    const textoBtn = document.getElementById('texto_btn_imprimir_etiquetas');
    const filtroSucursal = document.getElementById('filtro_sucursal_etiquetas');
    if (!btn || !textoBtn) return;

    const idSucursal = filtroSucursal && filtroSucursal.value ? parseInt(filtroSucursal.value, 10) : 0;
    let url = 'Impresiones/Articulos/etiquetas_articulos.php?varios=true';
    if (idSucursal > 0) {
      url += '&por_sucursal=' + encodeURIComponent(String(idSucursal));
      const nombre = filtroSucursal.options[filtroSucursal.selectedIndex].text;
      textoBtn.textContent = 'Imprimir etiquetas de ' + nombre;
    } else {
      textoBtn.textContent = 'Imprimir todo';
    }
    btn.href = url;
  }

  function actualizarTituloEtiquetas() {
    const textoSucursal = document.getElementById('texto_etiquetas_sucursal_titulo');
    const textoFiltros = document.getElementById('texto_etiquetas_filtros_titulo');
    const filtroSucursal = document.getElementById('filtro_sucursal_etiquetas');
    if (!textoSucursal) return;

    if (filtroSucursal && filtroSucursal.value) {
      textoSucursal.textContent = filtroSucursal.options[filtroSucursal.selectedIndex].text;
    } else {
      textoSucursal.textContent = 'todas las sucursales operativas';
    }

    if (!textoFiltros) return;

    const partes = [];
    const periodo = window.filtro_periodo_activo_etiquetas || '';
    const fechaDesde = document.getElementById('filtro_fecha_desde')?.value || '';
    const fechaHasta = document.getElementById('filtro_fecha_hasta')?.value || '';

    if (periodo === 'dia') {
      partes.push('de hoy');
    } else if (periodo === 'mes') {
      partes.push('de este mes');
    } else if (periodo === 'fecha') {
      if (fechaDesde && fechaHasta) {
        const fd = new Date(fechaDesde + 'T00:00:00');
        const fh = new Date(fechaHasta + 'T00:00:00');
        if (fechaDesde === fechaHasta) {
          partes.push('del ' + fd.toLocaleDateString('es-ES'));
        } else {
          partes.push('entre el ' + fd.toLocaleDateString('es-ES') + ' y el ' + fh.toLocaleDateString('es-ES'));
        }
      }
    }

    textoFiltros.textContent = partes.length ? ' — ' + partes.join(' ') : '';
    window.titulo_filtros_etiquetas = partes.join(' ');
    actualizarBotonImprimirMasivo();
  }

  window.cargarTotalEtiquetasPendientes = function () {
    const totalEl = document.getElementById('total_etiquetas');
    if (!totalEl) return;

    const filtroSucursal = document.getElementById('filtro_sucursal_etiquetas');
    const filtroFechaDesde = document.getElementById('filtro_fecha_desde');
    const filtroFechaHasta = document.getElementById('filtro_fecha_hasta');

    const formData = new FormData();
    formData.append('filtro_sucursal', filtroSucursal ? filtroSucursal.value : '');
    formData.append('filtro_fecha_desde', filtroFechaDesde ? filtroFechaDesde.value : '');
    formData.append('filtro_fecha_hasta', filtroFechaHasta ? filtroFechaHasta.value : '');
    formData.append('filtro_periodo', window.filtro_periodo_activo_etiquetas || '');

    fetch('parts/etiquetas/unique/get_total.php', { method: 'POST', body: formData })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        totalEl.textContent = data.success ? String(data.total) : '0';
      })
      .catch(function () {
        totalEl.textContent = '0';
      });
  };

  window.recargarEstadisticasEtiquetas = function () {
    window.cargarTotalEtiquetasPendientes();
  };

  const createFilterSucursal = function (containerClass, selectId) {
    const select = document.createElement('select');
    select.id = selectId;
    select.className = 'form-select select2-filter text-capitalize select2-custom';
    select.innerHTML = '<option value="">Todas las sucursales</option>';
    document.querySelector(containerClass).appendChild(select);

    const sucursalInicial = document.getElementById('sucursal_articulo_inicial');
    const idInicial = sucursalInicial ? String(sucursalInicial.value || '') : '';

    fetch('parts/clientes/listar/get_sucursales.php')
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if (!data.success) return;
        data.sucursales.forEach(function (sucursal) {
          const option = document.createElement('option');
          option.value = sucursal.id_sucursal;
          option.textContent = sucursal.nombre_sucursal;
          if (idInicial && String(sucursal.id_sucursal) === idInicial) {
            option.selected = true;
          }
          select.appendChild(option);
        });

        const select2 = $(select);
        if (select2.length) {
          select2.each(function () {
            const $this = $(this);
            $this.select2({ dropdownParent: $this.parent(), allowClear: true, placeholder: 'Todas las sucursales' });
            $this.on('select2:select select2:unselect', function () {
              if (dtEtiquetas) dtEtiquetas.ajax.reload();
              actualizarTituloEtiquetas();
              window.cargarTotalEtiquetasPendientes();
            });
          });
        }

        select.addEventListener('change', function () {
          if (dtEtiquetas) dtEtiquetas.ajax.reload();
          actualizarTituloEtiquetas();
          window.cargarTotalEtiquetasPendientes();
        });

        actualizarTituloEtiquetas();
        if (dtEtiquetas) dtEtiquetas.ajax.reload();
        window.cargarTotalEtiquetasPendientes();
      })
      .catch(function (err) {
        console.error('Error al cargar sucursales:', err);
      });

    return select;
  };

  if (dtTable) {
    dtEtiquetas = new DataTable(dtTable, {
      processing: true,
      serverSide: true,
      deferRender: true,
      searchDelay: 500,
      timeout: 60000,
      language: DATATABLES_SPANISH,
      ajax: {
        url: 'parts/etiquetas/unique/load_list.php',
        type: 'POST',
        data: function (d) {
          const sucursalFilter = document.getElementById('filtro_sucursal_etiquetas');
          const fechaDesde = document.getElementById('filtro_fecha_desde');
          const fechaHasta = document.getElementById('filtro_fecha_hasta');

          d.filtro_sucursal = sucursalFilter ? sucursalFilter.value : '';
          d.filtro_fecha_desde = fechaDesde ? fechaDesde.value : '';
          d.filtro_fecha_hasta = fechaHasta ? fechaHasta.value : '';
          d.filtro_periodo = window.filtro_periodo_activo_etiquetas || '';

          return d;
        },
        dataSrc: function (json) {
          return json.data || [];
        },
        error: function (xhr, error, thrown) {
          console.error('Error AJAX etiquetas:', error, thrown);
          console.log('Respuesta del servidor:', xhr.responseText);
        }
      },
      columns: [
        { data: 0 },
        { data: 1 },
        { data: 2 },
        { data: 3 },
        { data: 4 },
        { data: 5 },
        { data: 6 },
        { data: 7 },
        { data: 8 },
        { data: 9 },
        { data: 10 }
      ],
      columnDefs: [
        {
          targets: [3, 7, 10],
          orderable: false
        },
        {
          targets: 0,
          responsivePriority: 1,
          render: function (data, type) {
            const id = parseInt(String(data), 10);
            if (!id) {
              return '-';
            }
            if (type !== 'display') {
              return String(id);
            }
            return '<a href="articulo.php?id=' + id + '" target="_blank" rel="noopener" class="fw-semibold">' + id + '</a>';
          }
        }
      ],
      order: [[0, 'desc']],
      pageLength: 25,
      lengthMenu: [10, 25, 50, 100],
      layout: {
        topStart: {
          rowClass: 'row m-2 my-0 mt-0 justify-content-between',
          features: [
            {
              buttons: [
                {
                  extend: 'collection',
                  className: 'btn buttons-collection btn-outline-secondary dropdown-toggle waves-effect button-exportar',
                  text: '<span class="d-flex align-items-center justify-content-center gap-2"><i class="icon-base ri ri-upload-2-line icon-16px"></i> <span>Exportar</span></span>',
                  buttons: [
                    {
                      extend: 'excel',
                      text: '<span class="d-flex align-items-center"><i class="icon-base ri ri-file-excel-line me-1"></i>Excel</span>',
                      className: 'dropdown-item',
                      title: function () {
                        return 'Etiquetas pendientes ' + (document.getElementById('texto_etiquetas_sucursal_titulo')?.textContent || '');
                      },
                      exportOptions: {
                        columns: [0, 1, 2, 3, 4, 5, 6, 7, 8, 9],
                        format: {
                          body: function (data) {
                            if (typeof data === 'string') {
                              const temp = document.createElement('div');
                              temp.innerHTML = data;
                              return temp.textContent || temp.innerText || data;
                            }
                            return data;
                          }
                        }
                      }
                    },
                    {
                      extend: 'pdf',
                      text: '<span class="d-flex align-items-center"><i class="icon-base ri ri-file-pdf-line me-1"></i>PDF</span>',
                      className: 'dropdown-item',
                      orientation: 'landscape',
                      title: function () {
                        return 'Etiquetas pendientes ' + (document.getElementById('texto_etiquetas_sucursal_titulo')?.textContent || '');
                      },
                      exportOptions: {
                        columns: [0, 1, 2, 3, 4, 5, 6, 7, 8, 9],
                        format: {
                          body: function (data) {
                            if (typeof data === 'string') {
                              const temp = document.createElement('div');
                              temp.innerHTML = data;
                              return temp.textContent || temp.innerText || data;
                            }
                            return data;
                          }
                        }
                      }
                    }
                  ]
                }
              ]
            }
          ]
        },
        topEnd: {
          features: [
            {
              search: {
                placeholder: 'Buscar...',
                text: '_INPUT_'
              }
            }
          ]
        },
        bottomStart: {
          rowClass: 'row mx-3 justify-content-between',
          features: ['info']
        },
        bottomEnd: 'paging'
      },
      responsive: { details: false }
    });

    window.dt_etiquetas_pendientes = dtEtiquetas;
    createFilterSucursal('.etiquetas_sucursal', 'filtro_sucursal_etiquetas');
    configurarFiltrosFechaEtiquetas();
  }

  function configurarFiltrosFechaEtiquetas() {
    window.filtro_periodo_activo_etiquetas = '';
    const filtroFechaDesde = document.getElementById('filtro_fecha_desde');
    const filtroFechaHasta = document.getElementById('filtro_fecha_hasta');
    const rangeFechas = document.getElementById('rangeFechas');

    const recargar = function () {
      if (dtEtiquetas) dtEtiquetas.ajax.reload();
      actualizarTituloEtiquetas();
      window.cargarTotalEtiquetasPendientes();
    };

    const btnFecha = document.getElementById('filtro_por_fecha_alta_etiquetas');
    if (btnFecha) {
      btnFecha.addEventListener('click', function () {
        if (!filtroFechaDesde.value && !filtroFechaHasta.value) {
          Swal.fire({
            icon: 'warning',
            title: 'Atención',
            text: 'Debe seleccionar al menos una fecha',
            confirmButtonText: 'Aceptar'
          });
          return;
        }
        window.filtro_periodo_activo_etiquetas = 'fecha';
        if (rangeFechas) rangeFechas.value = '';
        recargar();
      });
    }

    const btnDia = document.getElementById('filtro_dia_etiquetas');
    if (btnDia) {
      btnDia.addEventListener('click', function () {
        const hoy = new Date().toISOString().split('T')[0];
        filtroFechaDesde.value = hoy;
        filtroFechaHasta.value = hoy;
        window.filtro_periodo_activo_etiquetas = 'dia';
        recargar();
      });
    }

    const btnMes = document.getElementById('filtro_mes_etiquetas');
    if (btnMes) {
      btnMes.addEventListener('click', function () {
        filtroFechaDesde.value = '';
        filtroFechaHasta.value = '';
        if (rangeFechas) rangeFechas.value = '';
        window.filtro_periodo_activo_etiquetas = 'mes';
        recargar();
      });
    }

    const btnTodos = document.getElementById('filtro_todos_etiquetas');
    if (btnTodos) {
      btnTodos.addEventListener('click', function () {
        filtroFechaDesde.value = '';
        filtroFechaHasta.value = '';
        if (rangeFechas) rangeFechas.value = '';
        window.filtro_periodo_activo_etiquetas = 'todos';
        recargar();
      });
    }
  }
});
