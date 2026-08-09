/**
 * Page Sellos List - DataTables
 */
'use strict';

document.addEventListener('DOMContentLoaded', function () {
  const dt_sellos_table = document.querySelector('.datatables-sellos');
  let dt_sellos;

  if (dt_sellos_table) {
    dt_sellos = new DataTable(dt_sellos_table, {
      processing: true,
      serverSide: true,
      deferRender: true,
      searchDelay: 500,
      language: DATATABLES_SPANISH,
      columns: [
        { data: 0 },
        { data: 1 },
        { data: 2 },
        { data: 3 },
        { data: 4 }
      ],
      createdRow: function (row, data) {
        const selloId = data[0];
        $(row).css('cursor', 'pointer');
        $(row).on('click', function (e) {
          if (!$(e.target).closest('a, button').length) {
            window.location.href = 'sello.php?id=' + selloId;
          }
        });
      },
      columnDefs: [
        {
          targets: 0,
          render: function (data) {
            const id = parseInt(data, 10);
            if (!id) {
              return '—';
            }
            return (
              '<a href="sello.php?id=' +
              id +
              '" class="fw-semibold text-primary text-decoration-none">' +
              id +
              '</a>'
            );
          }
        },
        {
          targets: 1,
          render: function (data) {
            if (typeof data === 'string' && data) {
              return '<span class="fw-medium text-heading">' + data + '</span>';
            }
            return '<span class="text-muted">Sin nombre</span>';
          }
        },
        {
          targets: 2,
          className: 'text-center',
          render: function (data) {
            if (data === 'SI') {
              return '<span class="badge bg-label-success">SI</span>';
            }
            return '<span class="badge bg-label-secondary">NO</span>';
          }
        },
        {
          targets: 3,
          render: function (data) {
            if (data) {
              return '<span class="fw-semibold">' + data + '</span>';
            }
            return '<span class="text-muted">—</span>';
          }
        },
        {
          targets: 4,
          render: function (data) {
            if (typeof data === 'string' && data) {
              return '<span class="fw-semibold">' + data + '</span>';
            }
            return '<span class="text-muted">—</span>';
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
                  className: 'btn buttons-collection btn-primary dropdown-toggle waves-effect',
                  text: '<span class="d-flex align-items-center gap-2"><i class="icon-base ri ri-upload-2-line icon-16px me-sm-1"></i> <span class="d-none d-sm-inline-block">Exportar</span></span>',
                  buttons: [
                    {
                      extend: 'excel',
                      text: '<span class="d-flex align-items-center"><i class="icon-base ri ri-file-excel-line me-1"></i>Excel</span>',
                      className: 'dropdown-item',
                      exportOptions: { columns: [0, 1, 2, 3, 4] }
                    },
                    {
                      extend: 'pdf',
                      text: '<span class="d-flex align-items-center"><i class="icon-base ri ri-file-pdf-line me-1"></i>PDF</span>',
                      className: 'dropdown-item',
                      exportOptions: { columns: [0, 1, 2, 3, 4] }
                    },
                    {
                      extend: 'copy',
                      text: '<i class="icon-base ri ri-file-copy-line me-1"></i>Copiar',
                      className: 'dropdown-item',
                      exportOptions: { columns: [0, 1, 2, 3, 4] }
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
            },
            {
              buttons: [
                {
                  text: '<i class="icon-base ri ri-add-line icon-sm me-0 me-sm-2 d-sm-none d-inline-block"></i><span class="d-none d-sm-inline-block">Nuevo sello</span>',
                  className: 'add-new btn btn-primary',
                  action: function () {
                    window.location.href = 'crear_sello.php';
                  }
                }
              ]
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
        url: 'parts/sellos/listar/load_list.php',
        type: 'POST',
        dataSrc: function (json) {
          return json.data || [];
        }
      },
      responsive: {
        details: {
          display: DataTable.Responsive.display.modal({
            header: function (row) {
              const data = row.data();
              return 'Detalles de ' + (data[1] || 'sello');
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

    window.dt_sellos = dt_sellos;
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

    elementsToModify.forEach(function (item) {
      document.querySelectorAll(item.selector).forEach(function (element) {
        if (item.classToRemove) {
          item.classToRemove.split(' ').forEach(function (className) {
            element.classList.remove(className);
          });
        }
        if (item.classToAdd) {
          item.classToAdd.split(' ').forEach(function (className) {
            element.classList.add(className);
          });
        }
      });
    });
  }, 100);
});
