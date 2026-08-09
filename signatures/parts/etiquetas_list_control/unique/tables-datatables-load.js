/**
 * Etiquetas impresas de un control — reimpresión
 */
'use strict';

document.addEventListener('DOMContentLoaded', function () {
  const dtTable = document.querySelector('.datatables-etiquetas-list-control');
  let dtEtiquetasControl;

  function abrirPopupImpresionEtiqueta(url) {
    const width = 640;
    const height = 720;
    const left = Math.max(0, Math.floor((window.screen.width - width) / 2));
    const top = Math.max(0, Math.floor((window.screen.height - height) / 2));
    const features =
      'popup=yes,width=' + width + ',height=' + height + ',left=' + left + ',top=' + top + ',menubar=no,toolbar=no,location=no,status=no';
    return window.open(url, '_blank', features);
  }

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
    }
  }

  document.addEventListener('click', function (ev) {
    const linkMasivo = ev.target && ev.target.closest ? ev.target.closest('.etiqueta-repetir-print-link-masivo') : null;
    const linkIndividual = ev.target && ev.target.closest ? ev.target.closest('.etiqueta-repetir-print-link') : null;
    const link = linkMasivo || linkIndividual;
    if (!link) {
      return;
    }
    if (ev.button !== 0 || ev.metaKey || ev.ctrlKey || ev.shiftKey || ev.altKey) {
      return;
    }
    const href = link.getAttribute('href') || '';
    if (!href || href.indexOf('repetir_impresion.php') === -1) {
      return;
    }
    ev.preventDefault();

    if (linkMasivo) {
      const totalEl = document.getElementById('total_etiquetas_control');
      const total = totalEl ? parseInt(String(totalEl.value), 10) || 0 : 0;
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

  if (dtTable) {
    const inputControl = document.getElementById('id_control_etiquetado');
    const idControl = inputControl ? parseInt(inputControl.value, 10) : 0;

    if (!idControl) {
      return;
    }

    dtEtiquetasControl = new DataTable(dtTable, {
      processing: true,
      serverSide: true,
      deferRender: true,
      searchDelay: 500,
      timeout: 60000,
      language: DATATABLES_SPANISH,
      ajax: {
        url: 'parts/etiquetas_list_control/unique/load_list.php',
        type: 'POST',
        data: function (d) {
          d.id_control_etiquetado = idControl;
          return d;
        },
        dataSrc: function (json) {
          return json.data || [];
        },
        error: function (xhr, error, thrown) {
          console.error('Error AJAX etiquetas list control:', error, thrown);
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
        { data: 6, visible: false }
      ],
      columnDefs: [
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
        },
        {
          targets: [4, 5],
          orderable: false
        }
      ],
      order: [[6, 'asc']],
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
                      title: 'Etiquetas control Nº ' + idControl,
                      exportOptions: {
                        columns: [0, 1, 2, 3, 4],
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
                      title: 'Etiquetas control Nº ' + idControl,
                      exportOptions: {
                        columns: [0, 1, 2, 3, 4],
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

    window.dt_etiquetas_list_control = dtEtiquetasControl;
  }
});
