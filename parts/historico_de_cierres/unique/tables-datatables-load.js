/**
 * Page Histórico de Cierres
 */

'use strict';

// Datatable (js)
document.addEventListener('DOMContentLoaded', function (e) {
  let borderColor, bodyBg, headingColor;

  borderColor = config.colors.borderColor;
  bodyBg = config.colors.bodyBg;
  headingColor = config.colors.headingColor;

  // Variable declaration for table
  const dt_cierres_table = document.querySelector('.datatables-historico-cierres');

  window.filtro_periodo_activo = 'todos';

  if (dt_cierres_table) {
    window.dt_cierres = $(dt_cierres_table).DataTable({
      processing: true,
      serverSide: true,
      ajax: {
        url: 'parts/historico_de_cierres/unique/load_list.php',
        type: 'POST',
        data: function(d) {
          d.filtro_fecha_desde = document.getElementById('filtro_fecha_desde') ? document.getElementById('filtro_fecha_desde').value : '';
          d.filtro_fecha_hasta = document.getElementById('filtro_fecha_hasta') ? document.getElementById('filtro_fecha_hasta').value : '';
          d.filtro_periodo = window.filtro_periodo_activo || 'todos';
        }
      },
      columns: [
        { data: 0 },
        { data: 1 },
        { data: 2 },
        { data: 3 },
        { data: 4 },
        { data: 5 },
      ],
      columnDefs: [
        {
          targets: 0,
          className: 'text-center',
          render: function (data, type, full, meta) {
            return '<a href="javascript:void(0);" class="btn-ver-arqueo fw-medium" data-id="' + data + '" data-id-tabla="' + full[6].id_tabla + '">' + data + '</a>';
          }
        },
        {
          targets: 1,
          render: function (data, type, full, meta) {
            return '<span>' + data + '</span>';
          }
        },
        {
          targets: 2,
          className: 'text-end',
          render: function (data, type, full, meta) {
            const valor = parseFloat(data) || 0;
            return '<span style="font-weight: 600; color: #007bff;">' + valor.toFixed(2) + ' €</span>';
          }
        },
        {
          // Efectivo
          targets: 3,
          className: 'text-end',
          render: function (data, type, full, meta) {
            const valor = parseFloat(data) || 0;
            return '<span style="font-weight: 600; color: #28a745;">' + valor.toFixed(2) + ' €</span>';
          }
        },
        {
          // Diferencia
          targets: 4,
          className: 'text-end',
          render: function (data, type, full, meta) {
            const valor = parseFloat(data) || 0;
            const color = valor === 0 ? '#6c757d' : (valor > 0 ? '#28a745' : '#dc3545');
            return '<span style="font-weight: 600; color: ' + color + ';">' + valor.toFixed(2) + ' €</span>';
          }
        },
        {
          // Usuario
          targets: 5,
          render: function (data, type, full, meta) {
            return '<span>' + data + '</span>';
          }
        }
      ],
      
      order: [[0, 'desc']], // Ordenar por Nº Arqueo descendente
      pageLength: 25,
      lengthMenu: [10, 25, 50, 100],
      language: DATATABLES_SPANISH,
      layout: {
        topStart: {
          rowClass: 'row m-2 my-0 mt-0 justify-content-between',
          features: [
            {
              buttons: [
                {
                  extend: 'collection',
                  className: 'btn buttons-collection btn-outline-secondary dropdown-toggle waves-effect',
                  text: '<span class="d-flex align-items-center gap-2"><i class="icon-base ri ri-upload-2-line icon-16px me-sm-1"></i> <span class="d-none d-sm-inline-block">Exportar</span></span>',
                  buttons: [
                                                            {
                      extend: 'excel',
                      text: '<span class="d-flex align-items-center"><i class="icon-base ri ri-file-excel-line me-1"></i>Excel</span>',
                      className: 'dropdown-item',
                      exportOptions: {
                        columns: [0, 1, 2, 3, 4],
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
                      text: '<span class="d-flex align-items-center"><i class="icon-base ri ri-file-pdf-line me-1"></i>PDF</span>',
                      className: 'dropdown-item',
                      orientation: 'landscape',
                      exportOptions: {
                        columns: [0, 1, 2, 3, 4],
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
                        
                        doc.content[0].text = 'Histórico de Cierres de Caja';
                        doc.content[0].alignment = 'center';
                        doc.content[0].fontSize = 14;
                        doc.content[0].margin = [0, 0, 0, 10];
                        
                        doc.pageMargins = [5, 5, 5, 5];
                        doc.content[1].table.widths = Array(doc.content[1].table.body[0].length).fill('*');
                      }
                    },
                    {
                      extend: 'copy',
                      text: '<i class="icon-base ri ri-file-copy-line me-1"></i>Copiar',
                      className: 'dropdown-item',
                      exportOptions: {
                        columns: [0, 1, 2, 3, 4],
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
        bottomStart: 'info',
        bottomEnd: 'paging'
      }
    });
    
    // Event listener para ver detalle del arqueo
    $(dt_cierres_table).on('click', '.btn-ver-arqueo', function() {
      const idArqueo = $(this).data('id');
      const idTabla = $(this).data('id-tabla');
      abrirModalDetalleArqueo(idArqueo, idTabla);
    });
  }
  
  // Función para abrir el modal de detalle del arqueo
  function abrirModalDetalleArqueo(idArqueo, idTabla) {
    // Mostrar el ID en el modal
    document.getElementById('modal-arqueo-id').textContent = idArqueo;
    
    // Realizar petición para obtener los datos del arqueo
    fetch('parts/historico_de_cierres/unique/get_detalle_arqueo.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/x-www-form-urlencoded'},
      body: 'id_fecha_cierre=' + encodeURIComponent(idArqueo) + '&id_tabla=' + encodeURIComponent(idTabla)
    })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        const arq = data.arqueo;
        
        // Llenar los datos de billetes y monedas
        document.getElementById('detalle-billete500').textContent = arq.b500;
        document.getElementById('total-billete500').textContent = arq.t500.toFixed(2) + ' €';
        
        document.getElementById('detalle-billete200').textContent = arq.b200;
        document.getElementById('total-billete200').textContent = arq.t200.toFixed(2) + ' €';
        
        document.getElementById('detalle-billete100').textContent = arq.b100;
        document.getElementById('total-billete100').textContent = arq.t100.toFixed(2) + ' €';
        
        document.getElementById('detalle-billete50').textContent = arq.b50;
        document.getElementById('total-billete50').textContent = arq.t50.toFixed(2) + ' €';
        
        document.getElementById('detalle-billete20').textContent = arq.b20;
        document.getElementById('total-billete20').textContent = arq.t20.toFixed(2) + ' €';
        
        document.getElementById('detalle-billete10').textContent = arq.b10;
        document.getElementById('total-billete10').textContent = arq.t10.toFixed(2) + ' €';
        
        document.getElementById('detalle-billete5').textContent = arq.b5;
        document.getElementById('total-billete5').textContent = arq.t5.toFixed(2) + ' €';
        
        document.getElementById('detalle-moneda2').textContent = arq.m2;
        document.getElementById('total-moneda2').textContent = arq.t2.toFixed(2) + ' €';
        
        document.getElementById('detalle-moneda1').textContent = arq.m1;
        document.getElementById('total-moneda1').textContent = arq.t1.toFixed(2) + ' €';
        
        document.getElementById('detalle-moneda50cent').textContent = arq['50cent'];
        document.getElementById('total-moneda50cent').textContent = arq.t50cent.toFixed(2) + ' €';
        
        document.getElementById('detalle-moneda20cent').textContent = arq['20cent'];
        document.getElementById('total-moneda20cent').textContent = arq.t20cent.toFixed(2) + ' €';
        
        document.getElementById('detalle-moneda10cent').textContent = arq['10cent'];
        document.getElementById('total-moneda10cent').textContent = arq.t10cent.toFixed(2) + ' €';
        
        document.getElementById('detalle-moneda5cent').textContent = arq['5cent'];
        document.getElementById('total-moneda5cent').textContent = arq.t5cent.toFixed(2) + ' €';
        
        document.getElementById('detalle-moneda2cent').textContent = arq['2cent'];
        document.getElementById('total-moneda2cent').textContent = arq.t2cent.toFixed(2) + ' €';
        
        document.getElementById('detalle-moneda1cent').textContent = arq['1cent'];
        document.getElementById('total-moneda1cent').textContent = arq.t1cent.toFixed(2) + ' €';
        
        // Llenar los totales
        document.getElementById('modal-totalEfectivo').textContent = arq.efectivo.toFixed(2) + ' €';
        document.getElementById('modal-totalDiferencia').textContent = arq.diferencia.toFixed(2) + ' €';
        document.getElementById('modal-totalCaja').textContent = arq.caja.toFixed(2) + ' €';
        
        // Aplicar color a la diferencia
        const boxDiferencia = document.getElementById('modal-boxDiferencia');
        const totalDiferencia = document.getElementById('modal-totalDiferencia');
        if (arq.diferencia === 0) {
          totalDiferencia.style.color = '#6c757d';
        } else if (arq.diferencia > 0) {
          totalDiferencia.style.color = '#28a745';
        } else {
          totalDiferencia.style.color = '#dc3545';
        }
        
        // Abrir el modal
        const modal = new bootstrap.Modal(document.getElementById('modalDetalleArqueo'));
        modal.show();
      } else {
        Swal.fire({
          title: 'Error',
          text: data.message || 'No se pudo cargar el detalle del arqueo',
          icon: 'error',
          confirmButtonText: 'Aceptar'
        });
      }
    })
    .catch(error => {
      console.error('Error:', error);
      Swal.fire({
        title: 'Error',
        text: 'Error al cargar el detalle del arqueo',
        icon: 'error',
        confirmButtonText: 'Aceptar'
      });
    });
  }
  
  // ========================================
  // Event Listeners para botones de filtro
  // ========================================
  
  // Botón Hoy
  document.getElementById('filtro_hoy')?.addEventListener('click', function() {
    window.filtro_periodo_activo = 'hoy';
    
    // Limpiar rango de fechas
    document.getElementById('filtro_fecha_desde').value = '';
    document.getElementById('filtro_fecha_hasta').value = '';
    document.getElementById('rangeFechas').value = '';
    
    // Actualizar clase active
    document.querySelectorAll('#filtro_hoy, #filtro_mes, #filtro_todos').forEach(btn => {
      btn.classList.remove('active');
    });
    this.classList.add('active');
    
    // Recargar tabla
    if (window.dt_cierres) {
      window.dt_cierres.ajax.reload();
    }
  });
  
  // Botón Mes
  document.getElementById('filtro_mes')?.addEventListener('click', function() {
    window.filtro_periodo_activo = 'mes';
    
    // Limpiar rango de fechas
    document.getElementById('filtro_fecha_desde').value = '';
    document.getElementById('filtro_fecha_hasta').value = '';
    document.getElementById('rangeFechas').value = '';
    
    // Actualizar clase active
    document.querySelectorAll('#filtro_hoy, #filtro_mes, #filtro_todos').forEach(btn => {
      btn.classList.remove('active');
    });
    this.classList.add('active');
    
    // Recargar tabla
    if (window.dt_cierres) {
      window.dt_cierres.ajax.reload();
    }
  });
  
  // Botón Todos
  document.getElementById('filtro_todos')?.addEventListener('click', function() {
    window.filtro_periodo_activo = 'todos';
    
    // Limpiar rango de fechas
    document.getElementById('filtro_fecha_desde').value = '';
    document.getElementById('filtro_fecha_hasta').value = '';
    document.getElementById('rangeFechas').value = '';
    
    // Actualizar clase active
    document.querySelectorAll('#filtro_hoy, #filtro_mes, #filtro_todos').forEach(btn => {
      btn.classList.remove('active');
    });
    this.classList.add('active');
    
    // Recargar tabla
    if (window.dt_cierres) {
      window.dt_cierres.ajax.reload();
    }
  });
  
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
});

