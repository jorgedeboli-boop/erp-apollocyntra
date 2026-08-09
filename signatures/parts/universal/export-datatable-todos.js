/**
 * Exportación DataTable: todos los registros filtrados (no solo la página actual).
 */
'use strict';

window.recogerFiltrosMovimientosExport = function (searchValue) {
  const formData = new FormData();
  formData.append('search', searchValue || '');

  const sucursalFilter = document.getElementById('filtro_sucursal');
  const grupoFilter = document.getElementById('filtro_grupo');
  const fechaDesdeFilter = document.getElementById('filtro_fecha_desde');
  const fechaHastaFilter = document.getElementById('filtro_fecha_hasta');

  formData.append('filtro_sucursal', sucursalFilter ? sucursalFilter.value : '');
  formData.append('filtro_grupo', grupoFilter ? grupoFilter.value : '');
  formData.append('filtro_fecha_desde', fechaDesdeFilter ? fechaDesdeFilter.value : '');
  formData.append('filtro_fecha_hasta', fechaHastaFilter ? fechaHastaFilter.value : '');
  formData.append('filtro_periodo', window.filtro_periodo_activo || 'dia');

  return formData;
};

window.obtenerTituloExportacionMovimientos = function () {
  if (typeof window.obtenerTituloExportacionFiltros === 'function') {
    return window.obtenerTituloExportacionFiltros();
  }
  const tituloElement = document.getElementById('titulo-listados');
  return tituloElement ? tituloElement.textContent.trim() : 'Exportación';
};

window.exportarTodosLosDatos = function (tipo, dt, exportConfig) {
  if (!exportConfig || !exportConfig.exportUrl || !exportConfig.headers || !exportConfig.headers.length) {
    console.error('exportarTodosLosDatos: falta exportConfig válido');
    return;
  }

  const formData = window.recogerFiltrosMovimientosExport(dt.search());
  const titulo = window.obtenerTituloExportacionMovimientos();
  const columnCount = exportConfig.headers.length;

  if (typeof Swal !== 'undefined') {
    Swal.fire({
      title: 'Generando exportación...',
      text: 'Obteniendo todos los registros',
      allowOutsideClick: false,
      didOpen: function () {
        Swal.showLoading();
      }
    });
  }

  fetch(exportConfig.exportUrl, {
    method: 'POST',
    body: formData
  })
    .then(function (response) {
      return response.json();
    })
    .then(function (responseData) {
      if (typeof Swal !== 'undefined') {
        Swal.close();
      }

      if (!responseData.success) {
        throw new Error(responseData.error || 'Error al obtener datos');
      }

      if (!responseData.data || responseData.data.length === 0) {
        if (typeof Swal !== 'undefined') {
          Swal.fire({
            title: 'Sin datos',
            text: 'No hay datos para exportar con los filtros aplicados',
            icon: 'info',
            confirmButtonText: 'Aceptar'
          });
        }
        return;
      }

      const tempTableId = 'temp-export-movimientos-' + Date.now();
      const tempDiv = document.createElement('div');
      tempDiv.style.display = 'none';
      tempDiv.innerHTML =
        '<table id="' + tempTableId + '"><thead><tr>' +
        exportConfig.headers.map(function (h) {
          return '<th>' + h + '</th>';
        }).join('') +
        '</tr></thead></table>';
      document.body.appendChild(tempDiv);

      const columns = [];
      for (let i = 0; i < columnCount; i++) {
        columns.push({ data: i });
      }

      const tempTable = $('#' + tempTableId).DataTable({
        data: responseData.data,
        columns: columns,
        paging: false,
        searching: false,
        ordering: false,
        dom: 't',
        buttons: []
      });

      const exportOptions = {
        title: titulo,
        exportOptions: {
          columns: ':visible',
          format: {
            body: function (inner) {
              if (!inner || inner.length <= 0) {
                return inner;
              }
              if (typeof inner !== 'string') {
                return inner;
              }
              const el = new DOMParser().parseFromString(inner, 'text/html').body.childNodes;
              let result = '';
              el.forEach(function (item) {
                result += item.textContent || item.innerText || '';
              });
              return result || inner;
            }
          }
        }
      };

      if (tipo === 'pdf') {
        exportOptions.orientation = 'landscape';
        exportOptions.customize = function (doc) {
          doc.pageOrientation = 'landscape';
          doc.pageSize = 'LEGAL';
          doc.defaultStyle.fontSize = 7;
          doc.styles.tableHeader.fontSize = 8;
          doc.styles.tableHeader.fillColor = '#2d4154';
          doc.styles.tableHeader.bold = true;
          doc.styles.tableHeader.color = 'white';
          if (doc.content[0]) {
            doc.content[0].text = titulo;
            doc.content[0].alignment = 'center';
            doc.content[0].fontSize = 12;
            doc.content[0].margin = [0, 0, 0, 10];
          }
          doc.pageMargins = [5, 5, 5, 5];
          if (doc.content[1] && doc.content[1].table) {
            doc.content[1].table.widths = Array(doc.content[1].table.body[0].length).fill('*');
          }
        };
      }

      const buttonType = tipo === 'excel' ? 'excelHtml5' : tipo;

      try {
        const tempButton = tempTable.button().add(0, Object.assign({ extend: buttonType }, exportOptions));
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
      if (typeof Swal !== 'undefined') {
        Swal.close();
        Swal.fire({
          title: 'Error',
          text: 'Ha ocurrido un error al exportar: ' + (error.message || 'Error desconocido'),
          icon: 'error',
          confirmButtonText: 'Aceptar'
        });
      } else {
        console.error(error);
      }
    });
};

window.crearBotonesExportarMovimientos = function (exportConfig) {
  return [
    {
      extend: 'excel',
      text: '<span class="d-flex align-items-center"><i class="icon-base ri ri-file-excel-line me-1"></i>Excel</span>',
      className: 'dropdown-item',
      action: function (e, dt) {
        window.exportarTodosLosDatos('excel', dt, exportConfig);
      }
    },
    {
      extend: 'pdf',
      text: '<span class="d-flex align-items-center"><i class="icon-base ri ri-file-pdf-line me-1"></i>PDF</span>',
      className: 'dropdown-item',
      action: function (e, dt) {
        window.exportarTodosLosDatos('pdf', dt, exportConfig);
      }
    },
    {
      extend: 'copy',
      text: '<i class="icon-base ri ri-file-copy-line me-1"></i>Copiar',
      className: 'dropdown-item',
      action: function (e, dt) {
        window.exportarTodosLosDatos('copy', dt, exportConfig);
      }
    }
  ];
};
