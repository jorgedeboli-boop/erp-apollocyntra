/**
 * Page Articulos Venta List
 */

'use strict';

const COLUMNAS_EXPORTABLES_ARTICULOS = [
  { index: 0, label: 'SKU' },
  { index: 1, label: 'Descripción' },
  { index: 2, label: 'Peso' },
  { index: 3, label: 'Precio' },
  { index: 4, label: 'Precio Coste' },
  { index: 5, label: '€/g' },
  { index: 6, label: 'Tipo' },
  { index: 7, label: 'Estado' },
  { index: 8, label: 'F. Enviado' },
  { index: 9, label: 'F. En Venta' },
  { index: 10, label: 'F. Vendido' },
  { index: 11, label: 'F. Retirado' },
  { index: 12, label: 'Creado Por' },
  { index: 13, label: 'Origen' }
];

/** Columnas que no se pueden ocultar desde el selector de la página */
const COLUMNAS_FIJAS_ARTICULOS = [0, 1, 3]; // SKU, Descripción, Precio

const COLUMNAS_TOGGLEABLES_ARTICULOS = [
  { index: 2, label: 'Peso' },
  { index: 4, label: 'Precio Coste' },
  { index: 5, label: '€/g' },
  { index: 6, label: 'Tipo' },
  { index: 7, label: 'Estado' },
  { index: 8, label: 'F. Enviado' },
  { index: 9, label: 'F. En Venta' },
  { index: 10, label: 'F. Vendido' },
  { index: 11, label: 'F. Retirado' },
  { index: 12, label: 'Creado Por' },
  { index: 13, label: 'Origen' },
  { index: 14, label: 'Venta' }
];

function crearHtmlSelectorColumnasArticulos() {
  const checks = COLUMNAS_TOGGLEABLES_ARTICULOS.map(function (col, i) {
    const id = 'col_vis_articulos_' + col.index;
    const mb = i === COLUMNAS_TOGGLEABLES_ARTICULOS.length - 1 ? 'mb-0' : 'mb-2';
    return (
      '<div class="form-check ' + mb + '">' +
        '<input class="form-check-input" type="checkbox" id="' + id + '" data-column="' + col.index + '" checked>' +
        '<label class="form-check-label" for="' + id + '">' + col.label + '</label>' +
      '</div>'
    );
  }).join('');

  return (
    '<div class="dropdown dt-articulos-columnas-dropdown">' +
      '<button type="button" class="btn buttons-collection btn-outline-secondary dropdown-toggle waves-effect button-exportar" ' +
        'id="btn_columnas_articulos" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">' +
        '<span class="d-flex align-items-center justify-content-center gap-2">' +
          '<i class="icon-base ri ri-layout-column-line icon-16px"></i>' +
          '<span>Columnas</span>' +
        '</span>' +
      '</button>' +
      '<div class="dropdown-menu p-3 articulos-columnas-menu" aria-labelledby="btn_columnas_articulos">' +
        '<div class="mb-2 small text-muted">Mostrar u ocultar columnas</div>' +
        '<div id="articulos_columnas_toggle" class="articulos-columnas-toggle">' + checks + '</div>' +
      '</div>' +
    '</div>'
  );
}

function insertarSelectorColumnasEnToolbarArticulos() {
  if (document.getElementById('btn_columnas_articulos')) {
    return;
  }

  const tableEl = document.querySelector('.datatables-articulos-venta');
  if (!tableEl) {
    return;
  }

  const root = tableEl.closest('.dt-container') || tableEl.closest('.card-datatable') || document;
  const buttons = root.querySelector('.dt-buttons');
  const search = root.querySelector('.dt-search');
  const layoutStart = root.querySelector('.dt-layout-start');
  const layoutEnd = root.querySelector('.dt-layout-end');
  const layoutRow =
    (layoutStart && layoutStart.parentElement) ||
    (layoutEnd && layoutEnd.parentElement) ||
    (buttons && buttons.closest('.dt-layout-row')) ||
    (search && search.closest('.dt-layout-row'));

  const wrap = document.createElement('div');
  wrap.className = 'dt-layout-cell dt-articulos-columnas-cell';
  wrap.setAttribute(
    'style',
    'position:absolute;left:50%;top:50%;transform:translate(-50%,-50%);margin:0;z-index:1;display:flex;align-items:center;justify-content:center;'
  );
  wrap.innerHTML = crearHtmlSelectorColumnasArticulos();

  if (layoutRow && layoutEnd && layoutEnd.parentElement === layoutRow) {
    layoutRow.classList.add('dt-articulos-toolbar-row');
    layoutRow.style.position = 'relative';
    layoutRow.insertBefore(wrap, layoutEnd);
    return;
  }

  if (layoutStart) {
    const row = layoutStart.parentElement;
    if (row) {
      row.classList.add('dt-articulos-toolbar-row');
      row.style.position = 'relative';
    }
    layoutStart.appendChild(wrap);
    return;
  }

  if (buttons && buttons.parentElement) {
    const row = buttons.closest('.row') || buttons.parentElement;
    if (row && row.classList) {
      row.classList.add('dt-articulos-toolbar-row');
      row.style.position = 'relative';
    }
    buttons.parentElement.insertBefore(wrap, buttons.nextSibling);
    return;
  }

  if (search && search.parentElement) {
    const row = search.closest('.row') || search.parentElement;
    if (row && row.classList) {
      row.classList.add('dt-articulos-toolbar-row');
      row.style.position = 'relative';
    }
    search.parentElement.insertBefore(wrap, search);
  }
}

function configurarVisibilidadColumnasArticulos(dt) {
  insertarSelectorColumnasEnToolbarArticulos();

  const container = document.getElementById('articulos_columnas_toggle');
  if (!container || !dt || container.getAttribute('data-articulos-colvis-bound') === '1') {
    return;
  }

  container.setAttribute('data-articulos-colvis-bound', '1');

  const inputs = container.querySelectorAll('input[data-column]');
  inputs.forEach(function (input) {
    const idx = parseInt(input.getAttribute('data-column'), 10);
    if (Number.isNaN(idx) || COLUMNAS_FIJAS_ARTICULOS.indexOf(idx) !== -1) {
      return;
    }
    input.checked = dt.column(idx).visible();
  });

  container.addEventListener('change', function (e) {
    const input = e.target.closest('input[data-column]');
    if (!input) {
      return;
    }

    const idx = parseInt(input.getAttribute('data-column'), 10);
    if (Number.isNaN(idx) || COLUMNAS_FIJAS_ARTICULOS.indexOf(idx) !== -1) {
      input.checked = true;
      return;
    }

    dt.column(idx).visible(!!input.checked);
  });
}

const SELECTORES_BOTONES_FECHA_ARTICULOS = '#filtro_por_fecha_enviado, #filtro_por_fecha_en_venta, #filtro_por_fecha_vendido, #filtro_dia, #filtro_mes, #filtro_todos';

function obtenerTextoTipoFechaArticulos() {
  const tipo = window.filtro_tipo_fecha || 'vendido';
  if (tipo === 'enviado') {
    return 'envío';
  }
  if (tipo === 'en_venta') {
    return 'en venta';
  }
  return 'vendido';
}

function activarBotonFiltroFechaArticulos(activeId) {
  document.querySelectorAll(SELECTORES_BOTONES_FECHA_ARTICULOS).forEach(function (btn) {
    btn.classList.remove('active');
  });
  const activeBtn = document.getElementById(activeId);
  if (activeBtn) {
    activeBtn.classList.add('active');
  }
}

// Función global para vender artículo
window.venderArticulo = function(idArticulo, descripcion) {
  Swal.fire({
    title: '¿Estás seguro que quieres vender este artículo?',
    html: '<p class="mb-1"><strong>SKU:</strong> ' + idArticulo + '</p>' +
          '<p class="mb-0"><strong>Descripción:</strong> ' + descripcion + '</p>',
    icon: 'question',
    showCancelButton: true,
    confirmButtonColor: '#28c76f',
    cancelButtonColor: '#6c757d',
    confirmButtonText: 'Sí, vender',
    cancelButtonText: 'Cancelar'
  }).then((result) => {
    if (result.isConfirmed) {
      // Crear formulario y enviarlo
      const form = document.createElement('form');
      form.method = 'POST';
      form.action = 'crear_venta.php';
      
      const inputArticulo = document.createElement('input');
      inputArticulo.type = 'hidden';
      inputArticulo.name = 'id_articulo';
      inputArticulo.value = idArticulo;
      
      form.appendChild(inputArticulo);
      document.body.appendChild(form);
      form.submit();
    }
  });
};

// Datatable (js)
document.addEventListener('DOMContentLoaded', function (e) {
  let borderColor, bodyBg, headingColor;

  borderColor = config.colors.borderColor;
  bodyBg = config.colors.bodyBg;
  headingColor = config.colors.headingColor;

  // Variable declaration for table
  const dt_articulo_table = document.querySelector('.datatables-articulos-venta');

  // Variable global para DataTable
  let dt_articulo;

  function reloadPorFiltrosArticulos() {
    if (dt_articulo) {
      dt_articulo.ajax.reload();
    }
    if (typeof window.recargarEstadisticasArticulos === 'function') {
      window.recargarEstadisticasArticulos();
    }
    if (typeof actualizarTituloArticulos === 'function') {
      actualizarTituloArticulos();
    }
  }

  if (window.ArticulosFiltros) {
    window.ArticulosFiltros.setOnChange(reloadPorFiltrosArticulos);
  }



  // Articulos datatable
  if (dt_articulo_table) {
    dt_articulo = new DataTable(dt_articulo_table, {
      processing: true,
      serverSide: true,
      deferRender: true,
      searchDelay: 500,
      timeout: 60000,
      
      language: DATATABLES_SPANISH,
      
      ajax: {
        url: 'parts/articulos/listar/load_list.php',
        type: 'POST',
        data: function(d) {
          const tipoFilter = document.getElementById('filtro_tipo');
          const estadoFilter = document.getElementById('filtro_estado');
          const origenFilter = document.getElementById('filtro_origen');
          const fechaDesdeFilter = document.getElementById('filtro_fecha_desde');
          const fechaHastaFilter = document.getElementById('filtro_fecha_hasta');
          
          d.filtro_tipo = tipoFilter ? tipoFilter.value : '';
          d.filtro_estado = estadoFilter ? estadoFilter.value : '';
          d.filtro_origen = origenFilter ? origenFilter.value : '';
          d.filtro_fecha_desde = fechaDesdeFilter ? fechaDesdeFilter.value : '';
          d.filtro_fecha_hasta = fechaHastaFilter ? fechaHastaFilter.value : '';
          d.filtro_periodo = window.filtro_periodo_activo || 'todos';
          d.filtro_tipo_fecha = window.filtro_tipo_fecha || 'vendido';
          
          return d;
        },
        dataSrc: function(json) {
          return json.data || [];
        },
        error: function(xhr, error, thrown) {
          console.error('Error AJAX:', error, thrown);
          console.log('Respuesta del servidor:', xhr.responseText);
        }
      },
      
      columns: [
        { data: 0 },  // SKU
        { data: 1 },  // Descripción
        { data: 2 },  // Peso
        { data: 3 },  // Precio
        { data: 4 },  // Precio Coste
        { data: 5 },  // € gramo
        { data: 6 },  // Tipo
        { data: 7 },  // Estado
        { data: 8 },  // F. Enviado
        { data: 9 },  // F. En Venta
        { data: 10 }, // F. Vendido
        { data: 11 }, // F. Retirado
        { data: 12 }, // Creado Por
        { data: 13 }, // Origen
        { data: 14 }, // Acciones
        { data: 15, visible: false } // ID (hidden para click)
      ],
      
      columnDefs: [
        {
          // SKU
          targets: 0,
          responsivePriority: 1,
          render: function (data, type, full, meta) {
            return '<span class="fw-semibold">' + data + '</span>';
          }
        }
      ],
      
      order: [[0, 'desc']], // Ordenar por ID descendente
      pageLength: 25,
      lengthMenu: [10, 25, 50, 100],
      initComplete: function () {
        configurarVisibilidadColumnasArticulos(this.api());
      },
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
                      text: `<span class="d-flex align-items-center"><i class="icon-base ri ri-file-excel-line me-1"></i>Excel</span>`,
                      className: 'dropdown-item',
                      action: function (e, dt) {
                        exportarTodosLosDatos('excel', dt);
                      },
                      exportOptions: {
                        columns: ':visible',
                        format: {
                          body: function (data, row, column, node) {
                            if (typeof data === 'string') {
                              const tempDiv = document.createElement('div');
                              tempDiv.innerHTML = data;
                              return tempDiv.textContent || tempDiv.innerText || data;
                            }
                            return data;
                          }
                        }
                      }
                    },
                    {
                      extend: 'pdf',
                      text: `<span class="d-flex align-items-center"><i class="icon-base ri ri-file-pdf-line me-1"></i>PDF</span>`,
                      className: 'dropdown-item',
                      orientation: 'landscape',
                      action: function (e, dt) {
                        exportarTodosLosDatos('pdf', dt);
                      },
                      exportOptions: {
                        columns: ':visible',
                        format: {
                          body: function (data, row, column, node) {
                            if (typeof data === 'string') {
                              const tempDiv = document.createElement('div');
                              tempDiv.innerHTML = data;
                              return tempDiv.textContent || tempDiv.innerText || data;
                            }
                            return data;
                          }
                        }
                      },
                      customize: function(doc) {
                        doc.pageOrientation = 'landscape';
                        doc.pageSize = 'LEGAL';
                        doc.defaultStyle.fontSize = 7;
                        doc.styles.tableHeader.fontSize = 8;
                        doc.styles.tableHeader.fillColor = '#2d4154';
                        doc.styles.tableHeader.bold = true;
                        doc.styles.tableHeader.color = 'white';
                        
                        let tituloPDF = 'Listado de Artículos';
                        
                        if (window.titulo_filtros_articulos) {
                          tituloPDF += ' - ' + window.titulo_filtros_articulos;
                        }
                        
                        doc.content[0].text = tituloPDF;
                        doc.content[0].alignment = 'center';
                        doc.content[0].fontSize = 14;
                        doc.content[0].margin = [0, 0, 0, 10];
                        
                        doc.pageMargins = [5, 5, 5, 5];
                        
                        doc.content[1].table.widths = Array(doc.content[1].table.body[0].length).fill('*');
                      }
                    },
                    {
                      extend: 'copy',
                      text: `<i class="icon-base ri ri-file-copy-line me-1"></i>Copiar`,
                      className: 'dropdown-item',
                      action: function (e, dt) {
                        exportarTodosLosDatos('copy', dt);
                      },
                      exportOptions: {
                        columns: ':visible',
                        format: {
                          body: function (data, row, column, node) {
                            if (typeof data === 'string') {
                              const tempDiv = document.createElement('div');
                              tempDiv.innerHTML = data;
                              return tempDiv.textContent || tempDiv.innerText || data;
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
      
      responsive: {
        details: false
      },
      
      // Event listener para hacer clickable toda la fila
      createdRow: function(row, data, dataIndex) {
        // Hacer la fila clickable (excepto si se hace click en un botón)
        $(row).css('cursor', 'pointer');
        $(row).on('click', function(e) {
          // No redirigir si se hace click en un botón o enlace
          if ($(e.target).closest('button, a, .select2').length > 0) {
            return;
          }
          // Obtener el ID del artículo (columna 15)
          const idArticulo = data[15];
          if (idArticulo) {
            window.location.href = 'articulo.php?id=' + idArticulo;
          }
        });
      }
    });

    // Configurar filtros de fecha
    configurarFiltrosFecha();

    window.dt_articulo = dt_articulo;
    setTimeout(function () {
      configurarVisibilidadColumnasArticulos(dt_articulo);
    }, 0);
  }


  // Función para configurar los filtros de fecha
  function configurarFiltrosFecha() {
    window.filtro_periodo_activo = 'todos';
    window.filtro_tipo_fecha = 'vendido';

    const filtroFechaDesde = document.getElementById('filtro_fecha_desde');
    const filtroFechaHasta = document.getElementById('filtro_fecha_hasta');
    const rangeFechas = document.getElementById('rangeFechas');

    function aplicarFiltroPorRango(tipoFecha, botonId) {
      if (!filtroFechaDesde.value && !filtroFechaHasta.value) {
        Swal.fire({
          icon: 'warning',
          title: 'Atención',
          text: 'Debe seleccionar al menos una fecha',
          confirmButtonText: 'Aceptar'
        });
        return;
      }

      window.filtro_periodo_activo = 'fecha';
      window.filtro_tipo_fecha = tipoFecha;
      if (rangeFechas) {
        rangeFechas.value = '';
      }

      activarBotonFiltroFechaArticulos(botonId);
      dt_articulo.ajax.reload();
      window.recargarEstadisticasArticulos();
      actualizarTituloArticulos();
    }

    const filtroPorFechaEnviado = document.getElementById('filtro_por_fecha_enviado');
    if (filtroPorFechaEnviado) {
      filtroPorFechaEnviado.addEventListener('click', function () {
        aplicarFiltroPorRango('enviado', 'filtro_por_fecha_enviado');
      });
    }

    const filtroPorFechaEnVenta = document.getElementById('filtro_por_fecha_en_venta');
    if (filtroPorFechaEnVenta) {
      filtroPorFechaEnVenta.addEventListener('click', function () {
        aplicarFiltroPorRango('en_venta', 'filtro_por_fecha_en_venta');
      });
    }

    const filtroPorFechaVendido = document.getElementById('filtro_por_fecha_vendido');
    if (filtroPorFechaVendido) {
      filtroPorFechaVendido.addEventListener('click', function () {
        aplicarFiltroPorRango('vendido', 'filtro_por_fecha_vendido');
      });
    }

    const filtroDia = document.getElementById('filtro_dia');
    if (filtroDia) {
      filtroDia.addEventListener('click', function () {
        const hoy = new Date().toISOString().split('T')[0];
        filtroFechaDesde.value = hoy;
        filtroFechaHasta.value = hoy;
        window.filtro_periodo_activo = 'dia';

        activarBotonFiltroFechaArticulos('filtro_dia');
        dt_articulo.ajax.reload();
        window.recargarEstadisticasArticulos();
        actualizarTituloArticulos();
      });
    }

    const filtroMes = document.getElementById('filtro_mes');
    if (filtroMes) {
      filtroMes.addEventListener('click', function () {
        filtroFechaDesde.value = '';
        filtroFechaHasta.value = '';
        if (rangeFechas) {
          rangeFechas.value = '';
        }
        window.filtro_periodo_activo = 'mes';

        activarBotonFiltroFechaArticulos('filtro_mes');
        dt_articulo.ajax.reload();
        window.recargarEstadisticasArticulos();
        actualizarTituloArticulos();
      });
    }

    const filtroTodos = document.getElementById('filtro_todos');
    if (filtroTodos) {
      filtroTodos.addEventListener('click', function () {
        filtroFechaDesde.value = '';
        filtroFechaHasta.value = '';
        if (rangeFechas) {
          rangeFechas.value = '';
        }
        window.filtro_periodo_activo = 'todos';

        activarBotonFiltroFechaArticulos('filtro_todos');
        dt_articulo.ajax.reload();
        window.recargarEstadisticasArticulos();
        actualizarTituloArticulos();
      });
    }

    actualizarTituloArticulos();
  }
  
  // Función para actualizar el título combinando todos los filtros
  function actualizarTituloArticulos() {
    const textoTitulo = document.getElementById('texto_articulos_titulo');
    if (!textoTitulo) return;
    
    let partes = [];
    
    // 1. Agregar filtro de fechas si existe
    const filtroActivo = window.filtro_periodo_activo || 'todos';
    const filtroFechaDesde = document.getElementById('filtro_fecha_desde');
    const filtroFechaHasta = document.getElementById('filtro_fecha_hasta');
    
    if (filtroActivo === 'dia') {
      partes.push('de hoy (fecha ' + obtenerTextoTipoFechaArticulos() + ')');
    } else if (filtroActivo === 'mes') {
      partes.push('de este mes (fecha ' + obtenerTextoTipoFechaArticulos() + ')');
    } else if (filtroActivo === 'fecha') {
      const fechaDesde = filtroFechaDesde ? filtroFechaDesde.value : '';
      const fechaHasta = filtroFechaHasta ? filtroFechaHasta.value : '';
      const prefijoFecha = 'fecha ' + obtenerTextoTipoFechaArticulos() + ' ';

      if (fechaDesde && fechaHasta) {
        if (fechaDesde === fechaHasta) {
          const fecha = new Date(fechaDesde + 'T00:00:00');
          partes.push(prefijoFecha + 'del ' + fecha.toLocaleDateString('es-ES'));
        } else {
          const fechaD = new Date(fechaDesde + 'T00:00:00');
          const fechaH = new Date(fechaHasta + 'T00:00:00');
          partes.push(prefijoFecha + 'entre el ' + fechaD.toLocaleDateString('es-ES') + ' y el ' + fechaH.toLocaleDateString('es-ES'));
        }
      } else if (fechaDesde) {
        const fechaD = new Date(fechaDesde + 'T00:00:00');
        partes.push(prefijoFecha + 'desde el ' + fechaD.toLocaleDateString('es-ES'));
      } else if (fechaHasta) {
        const fechaH = new Date(fechaHasta + 'T00:00:00');
        partes.push(prefijoFecha + 'hasta el ' + fechaH.toLocaleDateString('es-ES'));
      }
    }
    
    // 2. Agregar tipo si está seleccionado
    const filtroTipo = document.getElementById('filtro_tipo');
    if (filtroTipo && filtroTipo.value && filtroTipo.value !== '') {
      let textoTipo = filtroTipo.options[filtroTipo.selectedIndex].text;
      partes.push(textoTipo);
    }
    
    // 3. Agregar estado si está seleccionado
    const filtroEstado = document.getElementById('filtro_estado');
    if (filtroEstado && filtroEstado.value && filtroEstado.value !== '') {
      let textoEstado = filtroEstado.options[filtroEstado.selectedIndex].text;
      partes.push(textoEstado);
    }
    
    // 4. Agregar origen si está seleccionado
    const filtroOrigen = document.getElementById('filtro_origen');
    if (filtroOrigen && filtroOrigen.value && filtroOrigen.value !== '') {
      let textoOrigen = filtroOrigen.options[filtroOrigen.selectedIndex].text;
      partes.push('origen ' + textoOrigen);
    }
    
    let textoFinal = '';
    if (partes.length > 0) {
      textoFinal = partes.join(' - ');
    }
    
    textoTitulo.textContent = textoFinal;
    
    // Guardar en variable global para usar en el PDF
    window.titulo_filtros_articulos = textoFinal;
  }

  // Filter form control to default size
  setTimeout(() => {
    const elementsToModify = [
      { selector: '.dt-buttons .btn', classToRemove: 'btn-secondary' },
      { selector: '.dt-length .form-select', classToAdd: 'ms-0' },
      { selector: '.dt-length', classToAdd: 'mb-md-4 mb-0' },
      {
        selector: '.dt-layout-end',
        classToRemove: 'justify-content-between',
        classToAdd: 'd-flex gap-md-4 justify-content-md-between justify-content-center gap-md-2 flex-wrap mt-0'
      },
      { selector: '.dt-layout-start', classToAdd: 'mt-md-0 mt-5' },
      {
        selector: '.dt-layout-start .dt-buttons',
        classToAdd: 'd-md-flex d-block gap-4 justify-content-center'
      },
      {
        selector: '.dt-layout-end .dt-buttons',
        classToAdd: 'd-md-flex d-block gap-4 mb-md-0 mb-5 justify-content-center'
      },
      { selector: '.dt-layout-table', classToRemove: 'row mt-2' },
      { selector: '.dt-layout-full', classToRemove: 'col-md col-12' },
      { selector: '.dt-layout-full .table', classToAdd: 'table-responsive' }
    ];

    elementsToModify.forEach(({ selector, classToRemove, classToAdd }) => {
      document.querySelectorAll(selector).forEach(element => {
        if (classToRemove) {
          classToRemove.split(' ').forEach(className => element.classList.remove(className));
        }
        if (classToAdd) {
          classToAdd.split(' ').forEach(className => element.classList.add(className));
        }
      });
    });
  }, 100);
  
  // Función global para recargar estadísticas con filtros
  window.recargarEstadisticasArticulos = function() {
    if (typeof cargarEstadisticas === 'function') {
      cargarEstadisticas();
    }
  };

  /**
   * Función para exportar TODOS los datos (con filtros aplicados)
   */
  window.exportarTodosLosDatos = function (tipo, dt) {
    pedirColumnasExportacion({
      columnas: COLUMNAS_EXPORTABLES_ARTICULOS,
      idPrefix: 'articulo'
    })
      .then(function (columnasSeleccionadas) {
        ejecutarExportacionArticulos(tipo, dt, columnasSeleccionadas);
      })
      .catch(function () {
        // Usuario canceló la selección de columnas
      });
  };

  function ejecutarExportacionArticulos(tipo, dt, columnasSeleccionadas) {
    const searchValue = dt.search();
    const filtroTipo = document.getElementById('filtro_tipo');
    const filtroEstado = document.getElementById('filtro_estado');
    const filtroOrigen = document.getElementById('filtro_origen');
    const filtroFechaDesde = document.getElementById('filtro_fecha_desde');
    const filtroFechaHasta = document.getElementById('filtro_fecha_hasta');

    Swal.fire({
      title: 'Generando exportación...',
      text: 'Obteniendo todos los registros',
      allowOutsideClick: false,
      didOpen: function () {
        Swal.showLoading();
      }
    });

    const formData = new FormData();
    formData.append('search', searchValue);
    formData.append('filtro_tipo', filtroTipo ? filtroTipo.value : '');
    formData.append('filtro_estado', filtroEstado ? filtroEstado.value : '');
    formData.append('filtro_origen', filtroOrigen ? filtroOrigen.value : '');
    formData.append('filtro_fecha_desde', filtroFechaDesde ? filtroFechaDesde.value : '');
    formData.append('filtro_fecha_hasta', filtroFechaHasta ? filtroFechaHasta.value : '');
    formData.append('filtro_periodo', window.filtro_periodo_activo || 'todos');
    formData.append('filtro_tipo_fecha', window.filtro_tipo_fecha || 'vendido');

    fetch('parts/articulos/listar/export_all.php', {
      method: 'POST',
      body: formData
    })
      .then(function (response) {
        return response.json();
      })
      .then(function (responseData) {
        Swal.close();

        if (!responseData.success) {
          throw new Error(responseData.error || 'Error al obtener datos');
        }

        if (!responseData.data || responseData.data.length === 0) {
          Swal.fire({
            title: 'Sin datos',
            text: 'No hay datos para exportar con los filtros aplicados',
            icon: 'info',
            confirmButtonText: 'Aceptar'
          });
          return;
        }

        const headersHtml = COLUMNAS_EXPORTABLES_ARTICULOS
          .map(function (col) {
            return '<th>' + col.label + '</th>';
          })
          .join('');

        const columnsConfig = COLUMNAS_EXPORTABLES_ARTICULOS.map(function (col) {
          return { data: col.index };
        });

        const tempTableId = 'temp-export-table-' + Date.now();
        const tempDiv = document.createElement('div');
        tempDiv.style.display = 'none';
        tempDiv.innerHTML = '<table id="' + tempTableId + '"><thead><tr>' + headersHtml + '</tr></thead></table>';
        document.body.appendChild(tempDiv);

        const tempTable = $('#' + tempTableId).DataTable({
          data: responseData.data,
          columns: columnsConfig,
          paging: false,
          searching: false,
          ordering: false,
          dom: 't',
          buttons: []
        });

        const exportConfig = {
          exportOptions: {
            columns: columnasSeleccionadas
          }
        };

        if (tipo === 'pdf') {
          exportConfig.customize = function (doc) {
            doc.pageOrientation = 'landscape';
            doc.pageSize = 'LEGAL';
            doc.defaultStyle.fontSize = 7;
            doc.styles.tableHeader.fontSize = 8;
            doc.styles.tableHeader.fillColor = '#2d4154';
            doc.styles.tableHeader.bold = true;
            doc.styles.tableHeader.color = 'white';

            let tituloPDF = 'Listado de Artículos';

            if (window.titulo_filtros_articulos) {
              tituloPDF += ' - ' + window.titulo_filtros_articulos;
            }

            doc.content[0].text = tituloPDF;
            doc.content[0].alignment = 'center';
            doc.content[0].fontSize = 14;
            doc.content[0].margin = [0, 0, 0, 10];

            doc.pageMargins = [5, 5, 5, 5];
            doc.content[1].table.widths = Array(doc.content[1].table.body[0].length).fill('*');
          };
        }

        const buttonType = tipo === 'excel' ? 'excelHtml5' : tipo;

        try {
          const tempButton = tempTable.button().add(0, Object.assign({
            extend: buttonType
          }, exportConfig));

          tempButton.trigger();

          setTimeout(function () {
            tempTable.destroy();
            tempDiv.remove();
          }, 2000);
        } catch (error) {
          tempTable.destroy();
          tempDiv.remove();
          throw error;
        }
      })
      .catch(function (error) {
        Swal.close();
        console.error('Error:', error);
        Swal.fire({
          title: 'Error',
          text: 'Ha ocurrido un error al exportar: ' + error.message,
          icon: 'error',
          confirmButtonText: 'Aceptar'
        });
      });
  }
  
  // Event listener para nuevo artículo
  const btnNuevoArticulo = document.getElementById('btn_nuevo_articulo');
  if (btnNuevoArticulo) {
    btnNuevoArticulo.addEventListener('click', function(e) {
      e.preventDefault();
      
      Swal.fire({
        icon: 'question',
        title: '¿Crear nuevo artículo?',
        text: '¿Está seguro que desea crear un nuevo artículo?',
        showCancelButton: true,
        confirmButtonText: 'Sí, crear',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#007bff',
        cancelButtonColor: '#6c757d',
        reverseButtons: true
      }).then((result) => {
        if (result.isConfirmed) {
          window.location.href = 'crear_articulo.php';
        }
      });
    });
  }
});



