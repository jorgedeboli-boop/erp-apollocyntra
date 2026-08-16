/**
 * Page Estados de Cajas List
 */

'use strict';

// Datatable (js)
document.addEventListener('DOMContentLoaded', function (e) {
  let borderColor, bodyBg, headingColor;

  borderColor = config.colors.borderColor;
  bodyBg = config.colors.bodyBg;
  headingColor = config.colors.headingColor;

  // Variable declaration for table
  const dt_estados_cajas_table = document.querySelector('.datatables-estados-cajas');

  // Variable global para DataTable
  let dt_estados_cajas;

  // Estados de Cajas datatable
  if (dt_estados_cajas_table) {
    dt_estados_cajas = new DataTable(dt_estados_cajas_table, {
      processing: true, // Mostrar indicador de procesamiento
      serverSide: true, // Procesar en el servidor para grandes volúmenes
      deferRender: true, // Mejorar rendimiento con grandes volúmenes
      searchDelay: 500, // Delay de 500ms para búsquedas
      timeout: 60000, // Timeout de 60 segundos para peticiones grandes
      
      language: DATATABLES_SPANISH,
      columns: [
        { data: 0 },
        { data: 1 },
        { data: 2 },
        { data: 3 },
        { data: 4 },
        { data: 5 },
        { data: 6 },
        { data: 7 }
      ],
      
      columnDefs: [
        {
          // ID
          targets: 0,
          render: function (data, type, full, meta) {
            return '<span class="fw-medium">' + data + '</span>';
          }
        },
        {
          // Estado Caja
          targets: 1,
          render: function (data, type, full, meta) {
            const caja = data;
            
            if (!caja || caja === '') {
              return '<span class="badge bg-label-secondary rounded-pill"><i class="icon-base ri ri-question-line me-1"></i>Sin datos</span>';
            }
            
            // Convertir boolean a texto legible
            if (caja === 'false' || caja === false) {
              return '<span class="badge bg-label-success rounded-pill"><i class="icon-base ri ri-checkbox-circle-fill me-1"></i>Abierta</span>';
            } else if (caja === 'true' || caja === true) {
              return '<span class="badge bg-label-warning rounded-pill"><i class="icon-base ri ri-lock-line me-1"></i>Cerrada</span>';
            } else {
              return '<span class="badge bg-label-secondary rounded-pill"><i class="icon-base ri ri-question-line me-1"></i>Sin datos</span>';
            }
          }
        },
        {
          // Saldo
          targets: 2,
          render: function (data, type, full, meta) {
            if (data === null || data === undefined) {
              return '<span class="text-muted">-----</span>';
            }
            
            // Formatear como moneda
            const saldo = parseFloat(data).toLocaleString('es-ES', { 
              minimumFractionDigits: 2, 
              maximumFractionDigits: 2 
            });
            
            return '<span class="fw-bold text-primary">' + saldo + ' €</span>';
          }
        },
        {
          // Apertura
          targets: 3,
          render: function (data, type, full, meta) {
            if (!data || !data.fecha) {
              return '<span class="text-muted"><i class="icon-base ri ri-time-line me-1"></i>Sin apertura</span>';
            }
            
            // Formatear fecha y hora
            const fecha = new Date(data.fecha).toLocaleDateString('es-ES', { day: '2-digit', month: '2-digit', year: 'numeric' });
            const hora = data.hora;
            
            return '<div class="d-flex flex-column"><span class="fw-medium">' + fecha + '</span><small class="text-muted">' + hora + '</small></div>';
          }
        },
        {
          // Importe Apertura
          targets: 4,
          render: function (data, type, full, meta) {
            if (data === null || data === undefined) {
              return '<span class="text-muted">-</span>';
            }
            
            // Formatear como moneda
            const importe = parseFloat(data).toLocaleString('es-ES', { 
              minimumFractionDigits: 2, 
              maximumFractionDigits: 2 
            });
            
            return '<span class="fw-semibold text-success">' + importe + ' €</span>';
          }
        },
        {
          // Cierre
          targets: 5,
          render: function (data, type, full, meta) {
            if (!data || !data.fecha) {
              return '<span class="text-muted"><i class="icon-base ri ri-time-line me-1"></i>Sin cierre</span>';
            }
            
            // Formatear fecha y hora
            const fecha = new Date(data.fecha).toLocaleDateString('es-ES', { day: '2-digit', month: '2-digit', year: 'numeric' });
            const hora = data.hora;
            
            return '<div class="d-flex flex-column"><span class="fw-medium">' + fecha + '</span><small class="text-muted">' + hora + '</small></div>';
          }
        },
        {
          // Importe Cierre
          targets: 6,
          render: function (data, type, full, meta) {
            if (data === null || data === undefined) {
              return '<span class="text-muted">-</span>';
            }
            
            // Formatear como moneda
            const importe = parseFloat(data).toLocaleString('es-ES', { 
              minimumFractionDigits: 2, 
              maximumFractionDigits: 2 
            });
            
            return '<span class="fw-semibold text-danger">' + importe + ' €</span>';
          }
        },
        {
          targets: 7,
          title: 'Acciones',
          searchable: false,
          orderable: false,
          render: (data, type, full, meta) => {
            if (!window.puede_acceder_edit) {
              return '<span class="text-muted">-</span>';
            }

            const idTabla = data.id;
            const cajaCerrada = full[1];
            
            let botonAccion = '';
            
            if (cajaCerrada === 'false' || cajaCerrada === false) {
              botonAccion = `
                <a href="cierre_caja.php?id=${idTabla}" 
                   class="btn btn-sm btn-danger waves-effect waves-light" 
                   title="Cerrar Caja">
                  <i class="icon-base ri ri-lock-line me-1"></i>Cerrar Caja
                </a>
              `;
            } 
            else if (cajaCerrada === 'true' || cajaCerrada === true) {
              botonAccion = `
                <button type="button" 
                        class="btn btn-sm btn-success waves-effect waves-light btn-abrir-caja" 
                        data-id-tabla="${idTabla}"
                        title="Abrir Caja">
                  <i class="icon-base ri ri-lock-unlock-line me-1"></i>Abrir Caja
                </button>
              `;
            }
            
            return `<div class="d-flex align-items-center gap-2">${botonAccion}</div>`;
          }
        }
      ],

      order: [[0, 'asc']],
      pageLength: 25, // Mostrar 25 registros por defecto
      lengthMenu: [10, 25, 50, 100], // Opciones: 10, 25, 50, 100 (sin -1 para serverSide)
      layout: {
        topStart: {
          rowClass: 'row m-2 my-0 mt-0 justify-content-between',
          features: [
            {
              buttons: [
                {
                  extend: 'collection',
                  className: 'btn buttons-collection btn-primary dropdown-toggle waves-effect',
                  text: '<span class="d-flex align-items-center gap-2"><i class="icon-base ri ri-upload-2-line icon-16px me-sm-1"></i> <span class="d-none d-sm-inline-block">Exportar</span></span>',
                  buttons: [
                                                            {
                      extend: 'excel',
                      text: `<span class="d-flex align-items-center"><i class="icon-base ri ri-file-excel-line me-1"></i>Excel</span>`,
                      className: 'dropdown-item',
                      exportOptions: {
                        columns: [0, 1, 2, 3, 4, 5, 6],
                        format: {
                          body: function (inner, coldex, rowdex) {
                            if (inner.length <= 0) return inner;
                            const el = new DOMParser().parseFromString(inner, 'text/html').body.childNodes;
                            let result = '';
                            el.forEach(item => {
                              result += item.textContent || item.innerText || '';
                            });
                            return result;
                          }
                        }
                      }
                    },
                    {
                      extend: 'pdf',
                      text: `<span class="d-flex align-items-center"><i class="icon-base ri ri-file-pdf-line me-1"></i>PDF</span>`,
                      className: 'dropdown-item',
                      exportOptions: {
                        columns: [0, 1, 2, 3, 4, 5, 6],
                        format: {
                          body: function (inner, coldex, rowdex) {
                            if (inner.length <= 0) return inner;
                            const el = new DOMParser().parseFromString(inner, 'text/html').body.childNodes;
                            let result = '';
                            el.forEach(item => {
                              result += item.textContent || item.innerText || '';
                            });
                            return result;
                          }
                        }
                      },
                      customize: function(doc) {
                        doc.pageOrientation = 'landscape';
                        doc.defaultStyle.fontSize = 8;
                        doc.styles.tableHeader.fontSize = 9;
                        doc.styles.tableHeader.fillColor = '#2d4154';
                        doc.styles.tableHeader.bold = true;
                        doc.styles.tableHeader.color = 'white';
                        
                        // Cambiar el título del PDF
                        doc.content[0].text = 'Estados de Cajas';
                        doc.content[0].alignment = 'center';
                        doc.content[0].fontSize = 16;
                        doc.content[0].margin = [0, 0, 0, 10];
                        
                        // Configurar tabla para ocupar 100% del ancho
                        doc.content[1].table.widths = Array(doc.content[1].table.body[0].length).fill('*');
                        
                        // Ajustar márgenes para aprovechar todo el ancho
                        doc.pageMargins = [20, 20, 20, 20];
                        
                        // Configurar el ancho de la tabla al 100% sin bordes
                        doc.content[1].layout = {
                          hLineWidth: function() { return 0; },
                          vLineWidth: function() { return 0; },
                          paddingLeft: function() { return 4; },
                          paddingRight: function() { return 4; },
                          paddingTop: function() { return 2; },
                          paddingBottom: function() { return 2; }
                        };
                        
                        // Alinear columnas al centro (excepto la primera)
                        doc.content[1].table.body.forEach(function(row, index) {
                          // Primera columna (ID) alineada a la izquierda
                          if (row[0]) {
                            row[0].alignment = 'left';
                          }
                          
                          // Resto de columnas alineadas al centro
                          for (let i = 1; i < row.length; i++) {
                            if (row[i]) {
                              row[i].alignment = 'center';
                            }
                          }
                        });
                        
                        // Forzar ancho de tabla al 100%
                        doc.content[1].table.widths = doc.content[1].table.widths.map(() => '*');
                      }
                    },
                    {
                      extend: 'copy',
                      text: `<i class="icon-base ri ri-file-copy-line me-1"></i>Copiar`,
                      className: 'dropdown-item',
                      exportOptions: {
                        columns: [0, 1, 2, 3, 4, 5, 6],
                        format: {
                          body: function (inner, coldex, rowdex) {
                            if (inner.length <= 0) return inner;
                            const el = new DOMParser().parseFromString(inner, 'text/html').body.childNodes;
                            let result = '';
                            el.forEach(item => {
                              result += item.textContent || item.innerText || '';
                            });
                            return result;
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

      // Callbacks para debug
      ajax: {
        url: 'parts/estados_cajas/listar/load_list.php',
        type: 'POST',
        data: function(d) {
          // Agregar filtros de columna personalizados
          d.filtro_estado = document.getElementById('EstadoCaja') ? document.getElementById('EstadoCaja').value : '';
          
          return d;
        },
        dataSrc: function(json) {
          console.log('DEBUG Estados Cajas - Datos recibidos:', json);
          console.log('DEBUG - Fecha hoy:', json.debug_fecha_hoy);
          if (json.data && json.data.length > 0) {
            console.log('DEBUG - Primera fila:', json.data[0]);
          }
          return json.data || [];
        },
        error: function(xhr, error, thrown) {
          //console.error('Error AJAX:', error, thrown);
        }
      },
      
      // For responsive popup
      responsive: {
        details: {
          display: DataTable.Responsive.display.modal({
            header: function (row) {
              const data = row.data();
              return 'Detalles de caja #' + data[0];
            }
          }),
          type: 'column',
          renderer: function (api, rowIdx, columns) {
            const data = columns
              .map(function (col) {
                return col.title !== ''
                  ? `<tr data-dt-row="${col.rowIndex}" data-dt-column="${col.columnIndex}">
                      <td>${col.title}:</td>
                      <td>${col.data}</td>
                    </tr>`
                  : '';
              })
              .join('');

            if (data) {
              const div = document.createElement('div');
              div.classList.add('table-responsive');
              const table = document.createElement('table');
              div.appendChild(table);
              table.classList.add('table');
              const tbody = document.createElement('tbody');
              tbody.innerHTML = data;
              table.appendChild(tbody);
              return div;
            }
            return false;
          }
        }
      },
      
      initComplete: function () {
        const api = this.api();

        // Estado Caja filter
        const selectEstadoCaja = document.createElement('select');
        selectEstadoCaja.id = 'EstadoCaja';
        selectEstadoCaja.className = 'form-select select2-filter text-capitalize select2-custom';
        selectEstadoCaja.innerHTML = `<option value="">Seleccionar Estado Caja</option>`;
        document.querySelector('.user_estado').appendChild(selectEstadoCaja);

        // Agregar opciones fijas para estado caja
        const opcionesEstadoCaja = [
          { value: 'false', text: 'Abierta' },
          { value: 'true', text: 'Cerrada' }
        ];

        opcionesEstadoCaja.forEach(opcion => {
          const option = document.createElement('option');
          option.value = opcion.value;
          option.textContent = opcion.text;
          selectEstadoCaja.appendChild(option);
        });

        // Inicializar Select2 usando el código del template
        var select2 = $(selectEstadoCaja);
        if (select2.length) {
          select2.each(function () {
            var $this = $(this);
            select2Focus($this);
            $this.select2({
              dropdownParent: $this.parent()
            });
          });
        }

        $(selectEstadoCaja).on('change', function() {
          dt_estados_cajas.ajax.reload();
        });
      }
    });
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

  // Variables para el modal
  let modalIdTabla = null;
  let modalImporteInicial = 0;

  document.addEventListener('click', function(e) {
    if (e.target.closest('.btn-abrir-caja')) {
      const btn = e.target.closest('.btn-abrir-caja');
      abrirModalAperturaCaja(btn.getAttribute('data-id-tabla'));
    }
  });

  function abrirModalAperturaCaja(idTabla) {
    modalIdTabla = idTabla;
    
    document.getElementById('modal-caja-id').textContent = idTabla;
    
    fetch('parts/estados_cajas/listar/obtener_importe_cierre.php?id_tabla=' + encodeURIComponent(idTabla))
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          modalImporteInicial = data.importe || 0;
          document.getElementById('importe-apertura').value = modalImporteInicial.toFixed(2);
        } else {
          document.getElementById('importe-apertura').value = '0.00';
        }
      })
      .catch(error => {
        console.error('Error al obtener importe:', error);
        document.getElementById('importe-apertura').value = '0.00';
      });
    
    // Abrir el modal
    const modal = new bootstrap.Modal(document.getElementById('modalAbrirCaja'));
    modal.show();
  }

  // Event listener para el botón confirmar apertura
  document.getElementById('btn-confirmar-apertura').addEventListener('click', function() {
    const importe = parseFloat(document.getElementById('importe-apertura').value) || 0;
    
    if (importe < 0) {
      Swal.fire({
        title: 'Error',
        text: 'El importe no puede ser negativo',
        icon: 'error',
        confirmButtonText: 'Aceptar'
      });
      return;
    }
    
    // Cerrar el modal
    const modal = bootstrap.Modal.getInstance(document.getElementById('modalAbrirCaja'));
    modal.hide();
    
    // Mostrar loading
    Swal.fire({
      title: 'Abriendo caja...',
      text: 'Por favor espere',
      allowOutsideClick: false,
      didOpen: () => {
        Swal.showLoading();
      }
    });

    // Enviar petición AJAX con el importe
    fetch('parts/estados_cajas/listar/abrir_caja.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
      },
      body: 'id_tabla=' + encodeURIComponent(modalIdTabla) + '&importe_apertura=' + importe
    })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        Swal.fire({
          title: '¡Caja Abierta!',
          text: data.message,
          icon: 'success',
          confirmButtonText: 'Aceptar'
        });
        
        // Recargar tabla
        if (typeof dt_estados_cajas !== 'undefined') {
          dt_estados_cajas.ajax.reload(null, false); // false = mantener página actual
        }
        
        // Recargar estadísticas
        if (typeof cargarEstadisticas === 'function') {
          cargarEstadisticas();
        }
      } else {
        throw new Error(data.message || 'Error al abrir la caja');
      }
    })
    .catch(error => {
      Swal.fire({
        title: 'Error',
        text: error.message || 'Error al abrir la caja',
        icon: 'error',
        confirmButtonText: 'Aceptar'
      });
    });
  });

});
