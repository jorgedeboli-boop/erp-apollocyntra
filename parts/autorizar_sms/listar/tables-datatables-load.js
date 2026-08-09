/**
 * Page Autorizaciones SMS List
 */

'use strict';

// Datatable (js)
document.addEventListener('DOMContentLoaded', function (e) {
  let borderColor, bodyBg, headingColor;

  borderColor = config.colors.borderColor;
  bodyBg = config.colors.bodyBg;
  headingColor = config.colors.headingColor;

  // Variable declaration for table
  const dt_autorizaciones_table = document.querySelector('.datatables-autorizaciones-sms');

  // Variable global para DataTable
  let dt_autorizaciones;

  function reloadPorFiltrosAutorizaciones() {
    if (dt_autorizaciones) {
      dt_autorizaciones.ajax.reload();
    }
  }

  if (window.ListarFiltros) {
    window.ListarFiltros.setOnChange(reloadPorFiltrosAutorizaciones);
  }


  // Autorizaciones datatable
  if (dt_autorizaciones_table) {
    dt_autorizaciones = new DataTable(dt_autorizaciones_table, {
      processing: true,
      serverSide: true,
      deferRender: true,
      searchDelay: 500,
      timeout: 60000,
      
      language: DATATABLES_SPANISH,
      columns: [
        { data: 0 }, // ID
        { data: 1 }, // Sucursal
        { data: 2 }, // Código
        { data: 3 }, // Estado SMS
        { data: 4 }, // Estado Código
        { data: 5 }, // Fecha
        { data: 6 }, // Usuario
        { data: 7 }, // Cliente
        { data: 8 }, // Lote
        { data: 9 }, // Móvil
        { data: 10 }, // Tipo
        { data: 11 }, // Mensaje
        { data: 12 }  // Autorizar
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
          // Sucursal
          targets: 1,
          responsivePriority: 4,
          render: function (data, type, full, meta) {
            if (typeof data === 'string' && data) {
              return '<span class="fw-medium text-heading">' + data + '</span>';
            } else {
              return '<span class="text-muted">Sin sucursal</span>';
            }
          }
        },
        {
          // Código
          targets: 2,
          render: function (data, type, full, meta) {
            return '<span class="fw-medium">' + (data || '-') + '</span>';
          }
        },
        {
          // Estado SMS
          targets: 3,
          render: function (data, type, full, meta) {
            if (data === 'true') {
              return '<span class="badge bg-label-success rounded-pill"><i class="icon-base ri ri-checkbox-circle-fill me-1"></i>Enviado</span>';
            } else {
              return '<span class="badge bg-label-warning rounded-pill"><i class="icon-base ri ri-close-circle-line me-1"></i>No enviado</span>';
            }
          }
        },
        {
          // Estado Código
          targets: 4,
          render: function (data, type, full, meta) {
            if (data === 'true') {
              return '<span class="badge bg-label-success rounded-pill"><i class="icon-base ri ri-checkbox-circle-fill me-1"></i>Correcto</span>';
            } else {
              return '<span class="badge bg-label-warning rounded-pill"><i class="icon-base ri ri-close-circle-line me-1"></i>Incorrecto</span>';
            }
          }
        },
        {
          // Fecha
          targets: 5,
          render: function (data, type, full, meta) {
            if (!data) {
              return '<span class="text-muted">-</span>';
            }
            const fecha = new Date(data).toLocaleDateString('es-ES', { day: '2-digit', month: '2-digit', year: 'numeric' });
            const hora = new Date(data).toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' });
            return '<span class="fw-medium">' + fecha + ' ' + hora + '</span>';
          }
        },
        {
          // Usuario
          targets: 6,
          render: function (data, type, full, meta) {
            if (typeof data === 'string' && data) {
              return '<span class="text-heading">' + data + '</span>';
            } else {
              return '<span class="text-muted">Sin usuario</span>';
            }
          }
        },
        {
          // Cliente
          targets: 7,
          render: function (data, type, full, meta) {
            if (typeof data === 'string' && data) {
              return '<span class="fw-medium">' + data + '</span>';
            } else {
              return '<span class="text-muted">Sin cliente</span>';
            }
          }
        },
        {
          // Lote
          targets: 8,
          render: function (data, type, full, meta) {
            if (!data || data === '0' || data === 0) {
              return '<span class="text-muted">-</span>';
            }
            const acciones = full[12]; // Objeto con datos adicionales
            const identificador = acciones && acciones.identificador ? acciones.identificador : null;
            
            if (identificador) {
              return '<a href="lote.php?id=' + identificador + '" target="_blank" class="fw-medium text-primary text-decoration-underline">' + data + '</a>';
            } else {
              return '<span class="fw-medium">' + data + '</span>';
            }
          }
        },
        {
          // Móvil
          targets: 9,
          render: function (data, type, full, meta) {
            return '<span class="fw-medium">' + (data || '-') + '</span>';
          }
        },
        {
          // Tipo
          targets: 10,
          render: function (data, type, full, meta) {
            const tipo = data || '';
            const tipos = {
              'pago_lote': 'Pago Lote',
              'manual': 'Manual',
              'otros': 'Otros',
              'vencimiento': 'Vencimiento'
            };
            return '<span class="badge bg-label-info">' + (tipos[tipo] || tipo) + '</span>';
          }
        },
        {
          // Mensaje
          targets: 11,
          render: function (data, type, full, meta) {
            if (!data) {
              return '<span class="text-muted">-</span>';
            }
            const mensajeCorto = data.length > 50 ? data.substring(0, 50) + '...' : data;
            return '<span class="fw-medium" title="' + data.replace(/"/g, '&quot;') + '">' + mensajeCorto + '</span>';
          }
        },
        {
          // Autorizar
          targets: 12,
          render: function (data, type, full, meta) {
            const acciones = full[12]; // Objeto con datos adicionales
            const estado_sms = full[3]; // estado_sms
            const estado_codigo = full[4]; // estado_codigo
            const estado_autorizado = acciones.estado_autorizado;
            const autorizado_central = acciones.autorizado_central;
            
            // Mostrar botón si estado_sms es 'true' y estado_codigo es 'false'
            if (estado_sms === 'true' && estado_codigo === 'false') {
              if (estado_autorizado === 'true') {
                return '<span class="badge bg-label-success rounded-pill"><i class="icon-base ri ri-checkbox-circle-fill me-1"></i>Autorizado</span>';
              } else if (estado_autorizado === 'cancelada') {
                return '<span class="badge bg-label-secondary rounded-pill"><i class="icon-base ri ri-close-circle-line me-1"></i>Cancelada</span>';
              } else {
                // estado_autorizado === 'false', mostrar botón para autorizar
                if (!window.puede_acceder_edit) {
                  return '<span class="badge bg-label-warning rounded-pill"><i class="icon-base ri ri-time-line me-1"></i>Pendiente</span>';
                }
                return `<button type="button" 
                                class="btn btn-warning btn-sm waves-effect waves-light btn-autorizar-sms" 
                                data-id-sms="${full[0]}" 
                                data-sucursal="${full[1] || ''}" 
                                data-usuario="${full[6] || ''}" 
                                data-cliente="${full[7] || ''}" 
                                data-lote="${full[8] || ''}" 
                                data-movil="${full[9] || ''}" 
                                data-tipo="${full[10] || ''}" 
                                data-mensaje="${full[11] || ''}"
                                title="Autorizar">
                          <i class="icon-base ri ri-time-line me-1"></i>Autorizar
                        </button>`;
              }
            } else {
              if (autorizado_central === 'true' && estado_autorizado === 'false') {
                // estado_autorizado === 'false', mostrar botón para autorizar
                if (!window.puede_acceder_edit) {
                  return '<span class="badge bg-label-warning rounded-pill"><i class="icon-base ri ri-time-line me-1"></i>Pendiente</span>';
                }
                return `<button type="button" 
                                class="btn btn-warning btn-sm waves-effect waves-light btn-autorizar-sms" 
                                data-id-sms="${full[0]}" 
                                data-sucursal="${full[1] || ''}" 
                                data-usuario="${full[6] || ''}" 
                                data-cliente="${full[7] || ''}" 
                                data-lote="${full[8] || ''}" 
                                data-movil="${full[9] || ''}" 
                                data-tipo="${full[10] || ''}" 
                                data-mensaje="${full[11] || ''}"
                                title="Autorizar">
                          <i class="icon-base ri ri-time-line me-1"></i>Autorizar
                        </button>`;
              } else if (autorizado_central === 'true' && estado_autorizado === 'true') {
                return '<span class="badge bg-label-success rounded-pill"><i class="icon-base ri ri-checkbox-circle-fill me-1"></i>Autorizado</span>';
              } else if (autorizado_central === 'true' && estado_autorizado === 'false' && autorizado_central === 'cancelada') {
                return '<span class="badge bg-label-secondary rounded-pill"><i class="icon-base ri ri-close-circle-line me-1"></i>Cancelada</span>';
              } else {
                // No cumple las condiciones, no mostrar botón
                return '<span class="text-muted">--</span>';
              }
            }
          }
        }
      ],

      order: [[0, 'desc']], // Ordenar por ID descendente
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
                  className: 'btn buttons-collection btn-primary dropdown-toggle waves-effect',
                  text: '<span class="d-flex align-items-center gap-2"><i class="icon-base ri ri-upload-2-line icon-16px me-sm-1"></i> <span class="d-none d-sm-inline-block">Exportar</span></span>',
                  buttons: [
                                                            {
                      extend: 'excel',
                      text: `<span class="d-flex align-items-center"><i class="icon-base ri ri-file-excel-line me-1"></i>Excel</span>`,
                      className: 'dropdown-item',
                      exportOptions: {
                        columns: [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12],
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
                        columns: [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12],
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
                        doc.content[0].text = 'Autorizaciones SMS';
                        doc.content[0].alignment = 'center';
                        doc.content[0].fontSize = 16;
                        doc.content[0].margin = [0, 0, 0, 10];
                        doc.content[1].table.widths = Array(doc.content[1].table.body[0].length).fill('*');
                        doc.pageMargins = [20, 20, 20, 20];
                        doc.content[1].layout = {
                          hLineWidth: function() { return 0; },
                          vLineWidth: function() { return 0; },
                          paddingLeft: function() { return 4; },
                          paddingRight: function() { return 4; },
                          paddingTop: function() { return 2; },
                          paddingBottom: function() { return 2; }
                        };
                        doc.content[1].table.body.forEach(function(row, index) {
                          if (row[0]) {
                            row[0].alignment = 'left';
                          }
                          for (let i = 1; i < row.length; i++) {
                            if (row[i]) {
                              row[i].alignment = 'center';
                            }
                          }
                        });
                        doc.content[1].table.widths = doc.content[1].table.widths.map(() => '*');
                      }
                    },
                    {
                      extend: 'copy',
                      text: `<i class="icon-base ri ri-file-copy-line me-1"></i>Copiar`,
                      className: 'dropdown-item',
                      exportOptions: {
                        columns: [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12],
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

      ajax: {
        url: 'parts/autorizar_sms/listar/load_list.php',
        type: 'POST',
        data: function(d) {
          d.filtro_sucursal = document.getElementById('FiltroSucursal') ? document.getElementById('FiltroSucursal').value : '';
          d.filtro_estado_sms = document.getElementById('FiltroEstadoSMS') ? document.getElementById('FiltroEstadoSMS').value : '';
          d.filtro_estado_autorizado = document.getElementById('FiltroEstadoAutorizado') ? document.getElementById('FiltroEstadoAutorizado').value : '';
          return d;
        },
        dataSrc: function(json) {
          return json.data || [];
        },
        error: function(xhr, error, thrown) {
          //console.error('Error AJAX:', error, thrown);
        }
      },
      
      responsive: {
        details: {
          display: DataTable.Responsive.display.modal({
            header: function (row) {
              const data = row.data();
              return 'Detalles de SMS #' + data[0];
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
      }
    });
  }

  setTimeout(function () {
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

  if (window.puede_acceder_edit) {
  // Variables para el modal
  let modalIdSMS = null;

  // Event listener para botón Autorizar (delegación de eventos)
  document.addEventListener('click', function(e) {
    if (e.target.closest('.btn-autorizar-sms')) {
      const btn = e.target.closest('.btn-autorizar-sms');
      const idSMS = btn.getAttribute('data-id-sms');
      const sucursal = btn.getAttribute('data-sucursal');
      const usuario = btn.getAttribute('data-usuario');
      const cliente = btn.getAttribute('data-cliente');
      const lote = btn.getAttribute('data-lote');
      const movil = btn.getAttribute('data-movil');
      const tipo = btn.getAttribute('data-tipo');
      const mensaje = btn.getAttribute('data-mensaje');
      
      abrirModalAutorizacion(idSMS, sucursal, usuario, cliente, lote, movil, tipo, mensaje);
    }
  });

  // Función para abrir el modal de autorización
  function abrirModalAutorizacion(idSMS, sucursal, usuario, cliente, lote, movil, tipo, mensaje) {
    modalIdSMS = idSMS;
    
    // Mostrar valores en el modal
    document.getElementById('modal-sucursal').textContent = sucursal || '-';
    document.getElementById('modal-usuario').textContent = usuario || '-';
    document.getElementById('modal-cliente').textContent = cliente || '-';
    document.getElementById('modal-lote').textContent = lote || '-';
    document.getElementById('modal-movil').textContent = movil || '-';
    
    const tipos = {
      'pago_lote': 'Pago Lote',
      'manual': 'Manual',
      'otros': 'Otros',
      'vencimiento': 'Vencimiento'
    };
    document.getElementById('modal-tipo').textContent = tipos[tipo] || tipo || '-';
    document.getElementById('modal-mensaje').textContent = mensaje || '-';
    
    // Abrir el modal
    const modal = new bootstrap.Modal(document.getElementById('modalAutorizarSMS'));
    modal.show();
  }

  // Event listener para el botón confirmar autorización
  document.getElementById('btn-confirmar-autorizacion').addEventListener('click', function() {
    if (!modalIdSMS) {
      return;
    }
    
    // Cerrar el modal
    const modal = bootstrap.Modal.getInstance(document.getElementById('modalAutorizarSMS'));
    modal.hide();
    
    // Mostrar loading
    Swal.fire({
      title: 'Autorizando...',
      text: 'Por favor espere',
      allowOutsideClick: false,
      didOpen: () => {
        Swal.showLoading();
      }
    });

    // Enviar petición AJAX para autorizar
    fetch('parts/autorizar_sms/listar/actualizar_estado.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
      },
      body: 'id_sms=' + modalIdSMS + '&estado=true'
    })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        Swal.fire({
          title: '¡Autorizado!',
          text: data.message,
          icon: 'success',
          confirmButtonText: 'Aceptar'
        });
        
        // Recargar tabla
        if (typeof dt_autorizaciones !== 'undefined') {
          dt_autorizaciones.ajax.reload(null, false);
        }
      } else {
        throw new Error(data.message || 'Error al autorizar');
      }
    })
    .catch(error => {
      Swal.fire({
        title: 'Error',
        text: error.message || 'Error al autorizar',
        icon: 'error',
        confirmButtonText: 'Aceptar'
      });
    });
  });

  // Event listener para el botón cancelar del modal
  const btnCancelarModal = document.querySelector('#modalAutorizarSMS .btn-secondary[data-bs-dismiss="modal"]');
  if (btnCancelarModal) {
    btnCancelarModal.addEventListener('click', function(e) {
      e.preventDefault();
      
      if (!modalIdSMS) {
        // Si no hay ID, solo cerrar el modal
        const modal = bootstrap.Modal.getInstance(document.getElementById('modalAutorizarSMS'));
        if (modal) {
          modal.hide();
        }
        return;
      }
      
      // Cerrar el modal
      const modal = bootstrap.Modal.getInstance(document.getElementById('modalAutorizarSMS'));
      if (modal) {
        modal.hide();
      }
      
      // Mostrar loading
      Swal.fire({
        title: 'Cancelando...',
        text: 'Por favor espere',
        allowOutsideClick: false,
        didOpen: () => {
          Swal.showLoading();
        }
      });

      // Enviar petición AJAX para cancelar
      fetch('parts/autorizar_sms/listar/actualizar_estado.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'id_sms=' + modalIdSMS + '&estado=cancelada'
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          Swal.fire({
            title: 'Cancelada',
            text: data.message,
            icon: 'info',
            confirmButtonText: 'Aceptar'
          });
          
          // Limpiar variables del modal
          modalIdSMS = null;
          
          // Recargar tabla
          if (typeof dt_autorizaciones !== 'undefined') {
            dt_autorizaciones.ajax.reload(null, false);
          }
        } else {
          throw new Error(data.message || 'Error al cancelar');
        }
      })
      .catch(error => {
        Swal.fire({
          title: 'Error',
          text: error.message || 'Error al cancelar',
          icon: 'error',
          confirmButtonText: 'Aceptar'
        });
      });
    });
  }
  
  const modalAutorizarSMS = document.getElementById('modalAutorizarSMS');
  if (modalAutorizarSMS) {
  modalAutorizarSMS.addEventListener('hidden.bs.modal', function() {
    // Solo limpiar si no se hizo ninguna acción (se cerró con X o click fuera)
    if (modalIdSMS) {
      modalIdSMS = null;
    }
  });
  }
  }

});

