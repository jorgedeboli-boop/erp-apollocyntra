/**
 * Page BinList - Papelera de Reciclaje
 */

'use strict';

// Datatable (js)
document.addEventListener('DOMContentLoaded', function (e) {
  let borderColor, bodyBg, headingColor;

  borderColor = config.colors.borderColor;
  bodyBg = config.colors.bodyBg;
  headingColor = config.colors.headingColor;

  // Variable declaration for table
  const dt_binlist_table = document.querySelector('.datatables-binlist');

  // Variable global para DataTable
  let dt_binlist;

  // BinList datatable
  if (dt_binlist_table) {
    dt_binlist = new DataTable(dt_binlist_table, {
      processing: true, // Mostrar indicador de procesamiento
      serverSide: true, // Procesar en el servidor para grandes volúmenes
      deferRender: true, // Mejorar rendimiento con grandes volúmenes
      searchDelay: 500, // Delay de 500ms para búsquedas
      timeout: 60000, // Timeout de 60 segundos para peticiones grandes
      ajax: {
        url: 'parts/BinList/listar/load_list.php',
        type: 'POST',
        error: function (xhr, error, code) {
          console.error('Error en DataTable BinList:', error);
          console.error('Código:', code);
          console.error('Respuesta:', xhr.responseText);
        }
      },
      
      language: DATATABLES_SPANISH,
      
      columns: [
        // columns according to JSON
        { data: 0 }, // ID BinList
        { data: 1 }, // Item ID
        { data: 2 }, // Tipo (itemnameText)
        { data: 3 }, // Fecha y Hora
        { data: 4 }, // Usuario que eliminó
        { data: 5 }, // Descripción
        { data: 6 }, // Acciones
        { data: 7, visible: false }  // id_type_item_rel (oculto)
      ],
      
      columnDefs: [
        {
          // ID
          targets: 0,
          responsivePriority: 1,
          render: function (data, type, full, meta) {
            return '<span class="fw-medium">#' + data + '</span>';
          }
        },
        {
          // Item ID
          targets: 1,
          responsivePriority: 2,
          render: function (data, type, full, meta) {
            return '<span class="badge bg-label-info rounded-pill">' + data + '</span>';
          }
        },
        {
          // Tipo (itemnameText)
          targets: 2,
          responsivePriority: 3,
          render: function (data, type, full, meta) {
            if (data && data !== 'N/A') {
              return '<span class="fw-medium text-heading">' + data + '</span>';
            } else {
              return '<span class="text-muted">N/A</span>';
            }
          }
        },
        {
          // Fecha y Hora
          targets: 3,
          responsivePriority: 4,
          render: function (data, type, full, meta) {
            if (data) {
              return '<span class="text-nowrap"><i class="icon-base ri ri-calendar-line me-1"></i>' + data + '</span>';
            } else {
              return '<span class="text-muted">Sin fecha</span>';
            }
          }
        },
        {
          // Usuario que eliminó
          targets: 4,
          render: function (data, type, full, meta) {
            if (data) {
              return '<span class="text-truncate"><i class="icon-base ri ri-user-line me-1"></i>' + data + '</span>';
            } else {
              return '<span class="text-muted">Desconocido</span>';
            }
          }
        },
        {
          // Descripción
          targets: 5,
          render: function (data, type, full, meta) {
            if (data) {
              var descripcion = data.length > 50 ? data.substr(0, 50) + '...' : data;
              return '<span class="text-truncate" title="' + data + '">' + descripcion + '</span>';
            } else {
              return '<span class="text-muted">Sin descripción</span>';
            }
          }
        },
        {
          // Acciones
          targets: 6,
          title: 'Acciones',
          searchable: false,
          orderable: false,
          render: (data, type, full, meta) => {
            const idBinList = full[0]; // ID del BinList
            const itemId = full[1]; // Item ID
            const idTypeItem = full[7]; // id_type_item_rel
            
            return `
              <div class="d-flex align-items-center">
                <button type="button" 
                        class="btn btn-sm btn-success waves-effect waves-light" 
                        onclick="recuperarItem(${idBinList}, ${itemId}, ${idTypeItem})" 
                        title="Recuperar elemento">
                  <i class="icon-base ri ri-refresh-line me-1"></i>Recuperar
                </button>
              </div>
            `;
          }
        }
      ],
      
      order: [[0, 'desc']],
      
      layout: {
        topStart: {
          rowClass: 'row m-2 my-0 mt-0 justify-content-between',
          features: [
            {
              buttons: [
                {
                  extend: 'collection',
                  className: 'btn btn-outline-secondary dropdown-toggle waves-effect',
                  text: '<span class="d-flex align-items-center gap-2"><i class="icon-base ri ri-upload-2-line icon-16px me-sm-1"></i> <span class="d-none d-sm-inline-block">Exportar</span></span>',
                  buttons: [
                                                            {
                      extend: 'excel',
                      text: `<span class="d-flex align-items-center"><i class="icon-base ri ri-file-excel-line me-1"></i>Excel</span>`,
                      className: 'dropdown-item'
                    },
                    {
                      extend: 'pdf',
                      text: `<span class="d-flex align-items-center"><i class="icon-base ri ri-file-pdf-line me-1"></i>PDF</span>`,
                      className: 'dropdown-item'
                    },
                    {
                      extend: 'copy',
                      text: `<i class="icon-base ri ri-file-copy-line me-1"></i>Copiar`,
                      className: 'dropdown-item'
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
        details: {
          display: $.fn.dataTable.Responsive.display.modal({
            header: function (row) {
              var data = row.data();
              return 'Detalles del Item #' + data[0];
            }
          }),
          type: 'column',
          renderer: function (api, rowIdx, columns) {
            var data = $.map(columns, function (col, i) {
              return col.hidden
                ? '<tr data-dt-row="' +
                    col.rowIndex +
                    '" data-dt-column="' +
                    col.columnIndex +
                    '">' +
                    '<td>' +
                    col.title +
                    ':' +
                    '</td> ' +
                    '<td>' +
                    col.data +
                    '</td>' +
                    '</tr>'
                : '';
            }).join('');

            return data ? $('<table class="table"/><tbody />').append(data) : false;
          }
        }
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
});

/**
 * Función para recuperar un item de la papelera
 */
function recuperarItem(idBinList, itemId, idTypeItem) {
  Swal.fire({
    title: '¿Recuperar elemento?',
    html: '<p class="mb-0">¿Está seguro que desea recuperar este item?</p><p class="text-muted mt-2">El elemento será restaurado.</p>',
    icon: 'question',
    showCancelButton: true,
    confirmButtonColor: '#198754',
    cancelButtonColor: '#6c757d',
    confirmButtonText: '<i class="ri-refresh-line me-2"></i>Recuperar',
    cancelButtonText: 'Cancelar',
    showLoaderOnConfirm: true,
    allowOutsideClick: false,
    preConfirm: () => {
      // Preparar datos
      const formData = new FormData();
      formData.append('idBinList', idBinList);
      formData.append('itemId', itemId);
      formData.append('idTypeItem', idTypeItem);
      
      // Realizar petición AJAX
      return fetch('parts/BinList/listar/recuperar_item.php', {
        method: 'POST',
        body: formData
      })
      .then(response => {
        if (!response.ok) {
          throw new Error('Error en la respuesta del servidor');
        }
        return response.json();
      })
      .then(data => {
        if (!data.success) {
          throw new Error(data.error || 'Error desconocido');
        }
        return data;
      })
      .catch(error => {
        Swal.showValidationMessage(
          `Error: ${error.message}`
        );
      });
    }
  }).then((result) => {
    if (result.isConfirmed && result.value) {
      // Mostrar mensaje de éxito
      Swal.fire({
        title: '¡Elemento recuperado!',
        text: result.value.message,
        icon: 'success',
        confirmButtonText: 'Aceptar',
        confirmButtonColor: '#198754',
        timer: 3000,
        timerProgressBar: true
      }).then(() => {
        // Recargar DataTable
        $('.datatables-binlist').DataTable().ajax.reload(null, false);
      });
    }
  });
}
