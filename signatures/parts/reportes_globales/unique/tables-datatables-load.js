/**
 * Page Reportes Globales
 */

'use strict';

const COLUMNAS_EXPORTABLES_REPORTES_GLOBALES = [
  { index: 0, label: 'Fecha', required: true },
  { index: 1, label: 'Sucursal', required: true },
  { index: 2, label: 'Rango lotes' },
  { index: 3, label: 'Oro · Pagado' },
  { index: 4, label: 'Oro · Lotes' },
  { index: 5, label: 'Oro · Peso' },
  { index: 6, label: 'Oro · Media €/gr' },
  { index: 7, label: 'Plata · Pagado' },
  { index: 8, label: 'Plata · Peso' },
  { index: 9, label: 'Plata · Lotes' },
  { index: 10, label: 'Plata · Media €/gr' },
  { index: 11, label: 'Empeños · Total' },
  { index: 12, label: 'Empeños · Pagado' },
  { index: 13, label: 'Empeños · Peso' },
  { index: 14, label: 'Empeños · Benef. renovaciones' },
  { index: 15, label: 'Empeños retirados · Total' },
  { index: 16, label: 'Empeños retirados · Valor' },
  { index: 17, label: 'Empeños retirados · Peso' },
  { index: 18, label: 'Intervenidos · Lotes' },
  { index: 19, label: 'Intervenidos · Pagado' },
  { index: 20, label: 'Intervenidos · Peso' },
  { index: 21, label: 'Ventas · Total' },
  { index: 22, label: 'Ventas · Cobrado' },
  { index: 23, label: 'Ventas · Beneficio' },
  { index: 24, label: 'Ventas · Total plazos' },
  { index: 25, label: 'Ventas · Euros plazos' },
  { index: 26, label: 'Gastos' },
  { index: 27, label: 'Contado entrada' },
  { index: 28, label: 'Contado salida' },
  { index: 29, label: 'Tarjeta' },
  { index: 30, label: 'Transferencia entrada' },
  { index: 31, label: 'Transferencia salida' },
  { index: 32, label: 'Bizum' }
];

/** Columnas que no se pueden ocultar desde el selector de la página */
const COLUMNAS_FIJAS_REPORTES_GLOBALES = [0, 1]; // Fecha, Sucursal

const COLUMNAS_TOGGLEABLES_REPORTES_GLOBALES = COLUMNAS_EXPORTABLES_REPORTES_GLOBALES.filter(function (col) {
  return COLUMNAS_FIJAS_REPORTES_GLOBALES.indexOf(col.index) === -1;
});

function crearHtmlSelectorColumnasReportesGlobales() {
  const checks = COLUMNAS_TOGGLEABLES_REPORTES_GLOBALES.map(function (col, i) {
    const id = 'col_vis_reportes_globales_' + col.index;
    const mb = i === COLUMNAS_TOGGLEABLES_REPORTES_GLOBALES.length - 1 ? 'mb-0' : 'mb-2';
    return (
      '<div class="form-check ' + mb + '">' +
        '<input class="form-check-input" type="checkbox" id="' + id + '" data-column="' + col.index + '" checked>' +
        '<label class="form-check-label" for="' + id + '">' + col.label + '</label>' +
      '</div>'
    );
  }).join('');

  return (
    '<div class="dropdown dt-reportes-globales-columnas-dropdown">' +
      '<button type="button" class="btn buttons-collection btn-outline-secondary dropdown-toggle waves-effect button-exportar" ' +
        'id="btn_columnas_reportes_globales" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">' +
        '<span class="d-flex align-items-center justify-content-center gap-2">' +
          '<i class="icon-base ri ri-layout-column-line icon-16px"></i>' +
          '<span>Columnas</span>' +
        '</span>' +
      '</button>' +
      '<div class="dropdown-menu p-3 reportes-globales-columnas-menu" aria-labelledby="btn_columnas_reportes_globales">' +
        '<div class="mb-2 small text-muted">Mostrar u ocultar columnas</div>' +
        '<div id="reportes_globales_columnas_toggle" class="reportes-globales-columnas-toggle">' + checks + '</div>' +
      '</div>' +
    '</div>'
  );
}

function insertarSelectorColumnasEnToolbarReportesGlobales() {
  if (document.getElementById('btn_columnas_reportes_globales')) {
    return;
  }

  const tableEl = document.querySelector('.datatables-reportes-globales');
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
  wrap.className = 'dt-layout-cell dt-reportes-globales-columnas-cell';
  wrap.setAttribute(
    'style',
    'position:absolute;left:50%;top:50%;transform:translate(-50%,-50%);margin:0;z-index:1;display:flex;align-items:center;justify-content:center;'
  );
  wrap.innerHTML = crearHtmlSelectorColumnasReportesGlobales();

  if (layoutRow && layoutEnd && layoutEnd.parentElement === layoutRow) {
    layoutRow.classList.add('dt-reportes-globales-toolbar-row');
    layoutRow.style.position = 'relative';
    layoutRow.insertBefore(wrap, layoutEnd);
    return;
  }

  if (layoutStart) {
    const row = layoutStart.parentElement;
    if (row) {
      row.classList.add('dt-reportes-globales-toolbar-row');
      row.style.position = 'relative';
    }
    layoutStart.appendChild(wrap);
    return;
  }

  if (buttons && buttons.parentElement) {
    const row = buttons.closest('.row') || buttons.parentElement;
    if (row && row.classList) {
      row.classList.add('dt-reportes-globales-toolbar-row');
      row.style.position = 'relative';
    }
    buttons.parentElement.insertBefore(wrap, buttons.nextSibling);
    return;
  }

  if (search && search.parentElement) {
    const row = search.closest('.row') || search.parentElement;
    if (row && row.classList) {
      row.classList.add('dt-reportes-globales-toolbar-row');
      row.style.position = 'relative';
    }
    search.parentElement.insertBefore(wrap, search);
  }
}

function configurarVisibilidadColumnasReportesGlobales(dt) {
  insertarSelectorColumnasEnToolbarReportesGlobales();

  const container = document.getElementById('reportes_globales_columnas_toggle');
  if (!container || !dt || container.getAttribute('data-reportes-globales-colvis-bound') === '1') {
    return;
  }

  container.setAttribute('data-reportes-globales-colvis-bound', '1');

  const inputs = container.querySelectorAll('input[data-column]');
  inputs.forEach(function (input) {
    const idx = parseInt(input.getAttribute('data-column'), 10);
    if (Number.isNaN(idx) || COLUMNAS_FIJAS_REPORTES_GLOBALES.indexOf(idx) !== -1) {
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
    if (Number.isNaN(idx) || COLUMNAS_FIJAS_REPORTES_GLOBALES.indexOf(idx) !== -1) {
      input.checked = true;
      return;
    }

    dt.column(idx).visible(!!input.checked);
  });
}

/**
 * Estructura de cards de totales para export (misma info que #totales_reportes_globales).
 */
const CARDS_TOTALES_EXPORT_REPORTES_GLOBALES = [
  {
    titulo: 'Total lotes',
    campos: [
      { key: 'total_lotes', label: 'Cantidad', formato: 'count' },
      { key: 'total_lotes_gramos', label: 'Peso', formato: 'peso', sufijo: ' grs' },
      { key: 'total_lotes_euros', label: 'Precio compra', formato: 'euros', sufijo: ' €' }
    ]
  },
  {
    titulo: 'Total compras',
    campos: [
      { key: 'total_compras', label: 'Cantidad', formato: 'count' },
      { key: 'total_compras_gramos', label: 'Peso', formato: 'peso', sufijo: ' grs' },
      { key: 'total_compras_euros', label: 'Precio compra', formato: 'euros', sufijo: ' €' },
      { key: 'total_compras_media', label: 'Media pagado', formato: 'euros', sufijo: ' € / gramo' }
    ]
  },
  {
    titulo: 'Total empeños',
    campos: [
      { key: 'total_empenos', label: 'Cantidad', formato: 'count' },
      { key: 'total_empenos_gramos', label: 'Peso', formato: 'peso', sufijo: ' grs' },
      { key: 'total_empenos_euros', label: 'Precio compra', formato: 'euros', sufijo: ' €' },
      { key: 'total_empenos_media', label: 'Media pagado', formato: 'euros', sufijo: ' € / gramo' }
    ]
  },
  {
    titulo: 'Total oro',
    campos: [
      { key: 'total_oro', label: 'Cantidad', formato: 'count' },
      { key: 'total_oro_gramos', label: 'Peso', formato: 'peso', sufijo: ' grs' },
      { key: 'total_oro_euros', label: 'Precio compra', formato: 'euros', sufijo: ' €' },
      { key: 'total_oro_media', label: 'Media pagado', formato: 'euros', sufijo: ' € / gramo' }
    ]
  },
  {
    titulo: 'Total plata',
    campos: [
      { key: 'total_plata', label: 'Cantidad', formato: 'count' },
      { key: 'total_plata_gramos', label: 'Peso', formato: 'peso', sufijo: ' grs' },
      { key: 'total_plata_euros', label: 'Precio compra', formato: 'euros', sufijo: ' €' },
      { key: 'total_plata_media', label: 'Media pagado', formato: 'euros', sufijo: ' € / gramo' }
    ]
  },
  {
    titulo: 'Totales ventas',
    campos: [
      { key: 'total_ventas', label: 'Total ventas', formato: 'count' },
      { key: 'total_euros_ventas', label: 'Cobrado', formato: 'euros', sufijo: ' €' },
      { key: 'total_coste_art_venta', label: 'Coste artículos', formato: 'euros', sufijo: ' €' },
      { key: 'total_beneficio_ventas', label: 'Total beneficio ventas', formato: 'euros', sufijo: ' €' },
      { key: 'base_imponible_ventas', label: 'Base imponible', formato: 'euros', sufijo: ' €' },
      { key: 'cuota_iva_ventas', label: 'Cuota de IVA', formato: 'euros', sufijo: ' €' },
      { key: 'beneficio_final_ventas', label: 'Beneficio final', formato: 'euros', sufijo: ' €' }
    ]
  },
  {
    titulo: 'Ventas a plazos',
    campos: [
      { key: 'total_ventas_plazo', label: 'Total ventas plazos', formato: 'count' },
      { key: 'total_ventas_plazo_euro', label: 'Euros plazos', formato: 'euros', sufijo: ' €' }
    ]
  },
  {
    titulo: 'Beneficio renovaciones',
    campos: [
      { key: 'total_euros_renovaciones', label: 'Total renovaciones', formato: 'euros', sufijo: ' €' },
      { key: 'iva_renovaciones', label: 'IVA renovaciones', formato: 'euros', sufijo: ' €' },
      { key: 'beneficio_renovaciones', label: 'Beneficio', formato: 'euros', sufijo: ' €' }
    ]
  },
  {
    titulo: 'Total gastos',
    campos: [
      { key: 'total_gastos', label: 'Gastos', formato: 'euros', sufijo: ' €' }
    ]
  },
  {
    titulo: 'Lotes intervenidos',
    campos: [
      { key: 'total_contratos_intervenidos', label: 'Lotes', formato: 'count' },
      { key: 'total_euros_contratos_intervenidos', label: 'Pagado', formato: 'euros', sufijo: ' €' },
      { key: 'total_gramos_contratos_intervenidos', label: 'Peso', formato: 'peso', sufijo: ' grs' }
    ]
  },
  {
    titulo: 'Métodos de pago',
    campos: [
      { key: 'total_caja_entradas', label: 'Contado entrada', formato: 'euros', sufijo: ' €' },
      { key: 'total_caja_salidas', label: 'Contado salida', formato: 'euros', sufijo: ' €' },
      { key: 'total_operaciones_tarjeta', label: 'Tarjeta', formato: 'euros', sufijo: ' €' },
      { key: 'total_operaciones_trasnferencia_entrada', label: 'Transf. entrada', formato: 'euros', sufijo: ' €' },
      { key: 'total_operaciones_trasnferencia_salida', label: 'Transf. salida', formato: 'euros', sufijo: ' €' },
      { key: 'total_operaciones_bizum', label: 'Bizum', formato: 'euros', sufijo: ' €' }
    ]
  },
  {
    titulo: 'Ventas contado',
    campos: [
      { key: 'ventas_contado', label: 'Total', formato: 'count' },
      { key: 'ventas_contado_euros', label: 'Total euros', formato: 'euros', sufijo: ' €' },
      { key: 'ventas_contado_media', label: 'Media / venta', formato: 'euros', sufijo: ' €' }
    ]
  },
  {
    titulo: 'Ventas transferencia',
    campos: [
      { key: 'ventas_transferencia', label: 'Total', formato: 'count' },
      { key: 'ventas_transferencia_euros', label: 'Total euros', formato: 'euros', sufijo: ' €' },
      { key: 'ventas_transferencia_media', label: 'Media / venta', formato: 'euros', sufijo: ' €' }
    ]
  },
  {
    titulo: 'Ventas tarjeta',
    campos: [
      { key: 'ventas_tarjeta', label: 'Total', formato: 'count' },
      { key: 'ventas_tarjeta_euros', label: 'Total euros', formato: 'euros', sufijo: ' €' },
      { key: 'ventas_tarjeta_media', label: 'Media / venta', formato: 'euros', sufijo: ' €' }
    ]
  },
  {
    titulo: 'Ventas bizum',
    campos: [
      { key: 'ventas_bizum', label: 'Total', formato: 'count' },
      { key: 'ventas_bizum_euros', label: 'Total euros', formato: 'euros', sufijo: ' €' },
      { key: 'ventas_bizum_media', label: 'Media / venta', formato: 'euros', sufijo: ' €' }
    ]
  }
];

function formatearValorExportTotalesReportesGlobales(valor, formato) {
  if (typeof formatearTotalReportesGlobales === 'function') {
    return formatearTotalReportesGlobales(valor, formato);
  }
  if (formato === 'peso' || formato === 'euros') {
    return parseFloat(valor || 0).toLocaleString('es-ES', {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2
    });
  }
  return parseInt(valor || 0, 10).toLocaleString('es-ES');
}

function construirCardsTotalesDesdeStatsReportesGlobales(totales) {
  const stats = totales || {};
  return CARDS_TOTALES_EXPORT_REPORTES_GLOBALES.map(function (card) {
    return {
      titulo: card.titulo,
      lineas: (card.campos || []).map(function (campo) {
        const valor = formatearValorExportTotalesReportesGlobales(stats[campo.key], campo.formato);
        return campo.label + ': ' + valor + (campo.sufijo || '');
      })
    };
  });
}

function normalizarTextoExportReportesGlobales(texto) {
  return String(texto || '')
    .replace(/\u00a0/g, ' ')
    .replace(/\s+/g, ' ')
    .trim();
}

/**
 * Lee las cards de #totales_reportes_globales con los valores visibles en pantalla.
 */
function obtenerCardsTotalesReportesGlobalesParaExport() {
  const root = document.getElementById('totales_reportes_globales');
  if (!root) {
    return [];
  }

  const cards = [];

  root.querySelectorAll('.flex-fill > .card').forEach(function (card) {
    const tituloEl = card.querySelector('h5');
    const titulo = normalizarTextoExportReportesGlobales(tituloEl ? tituloEl.textContent : '');
    if (!titulo) {
      return;
    }

    const lineas = [];
    const body = card.querySelector('.card-body') || card;

    Array.prototype.forEach.call(body.children, function (row) {
      if (!row.classList || !row.classList.contains('d-flex')) {
        return;
      }
      if (row.querySelector('h5')) {
        return;
      }
      const texto = normalizarTextoExportReportesGlobales(row.textContent);
      if (texto) {
        lineas.push(texto);
      }
    });

    const listaDescuadrados = card.querySelector('#rg-lotes-descuadrados-lista');
    const vacioDescuadrados = card.querySelector('#rg-lotes-descuadrados-vacio');
    const loadingDescuadrados = card.querySelector('#rg-lotes-descuadrados-loading');

    if (listaDescuadrados || vacioDescuadrados) {
      if (loadingDescuadrados && !loadingDescuadrados.classList.contains('d-none')) {
        lineas.push('Cargando…');
      } else if (listaDescuadrados && !listaDescuadrados.classList.contains('d-none')) {
        const items = listaDescuadrados.querySelectorAll('.rg-lote-descuadrado-item');
        if (items.length) {
          items.forEach(function (item) {
            const texto = normalizarTextoExportReportesGlobales(item.textContent);
            if (texto) {
              lineas.push(texto);
            }
          });
        } else {
          lineas.push('Sin lotes descuadrados.');
        }
      } else if (vacioDescuadrados && !vacioDescuadrados.classList.contains('d-none')) {
        lineas.push(normalizarTextoExportReportesGlobales(vacioDescuadrados.textContent) || 'Sin lotes descuadrados.');
      }
    }

    cards.push({
      titulo: titulo,
      lineas: lineas
    });
  });

  return cards;
}

function insertarTotalesEnPdfReportesGlobales(doc, cards) {
  if (!cards || !cards.length) {
    return;
  }

  const colsPerRow = 4;
  const body = [];

  for (let i = 0; i < cards.length; i += colsPerRow) {
    const row = [];
    for (let c = 0; c < colsPerRow; c++) {
      const card = cards[i + c];
      if (!card) {
        row.push({ text: ' ', border: [false, false, false, false] });
        continue;
      }
      row.push({
        stack: [
          { text: card.titulo, bold: true, fontSize: 8, margin: [0, 0, 0, 2] },
          {
            text: card.lineas.length ? card.lineas.join('\n') : '—',
            fontSize: 7,
            color: '#333333'
          }
        ],
        margin: [3, 3, 3, 3]
      });
    }
    body.push(row);
  }

  const bloqueTotales = [
    {
      text: 'Totales',
      bold: true,
      fontSize: 10,
      margin: [0, 6, 0, 4]
    },
    {
      table: {
        widths: Array(colsPerRow).fill('*'),
        body: body
      },
      layout: {
        hLineWidth: function () { return 0.4; },
        vLineWidth: function () { return 0.4; },
        hLineColor: function () { return '#999999'; },
        vLineColor: function () { return '#999999'; },
        paddingLeft: function () { return 2; },
        paddingRight: function () { return 2; },
        paddingTop: function () { return 2; },
        paddingBottom: function () { return 2; }
      },
      margin: [0, 0, 0, 8]
    }
  ];

  // Tras el título (content[0]), antes de la tabla de datos
  doc.content.splice(1, 0, bloqueTotales[0], bloqueTotales[1]);
}

/**
 * Construye filas de hoja: título, cabecera, datos y debajo TOTALES (misma hoja).
 * Excel/Numbers suelen ignorar tablas HTML posteriores a la primera.
 */
function letraColumnaExcelReportesGlobales(index) {
  let n = index + 1;
  let s = '';
  while (n > 0) {
    const m = (n - 1) % 26;
    s = String.fromCharCode(65 + m) + s;
    n = Math.floor((n - 1) / 26);
  }
  return s;
}

function escaparXmlExcelReportesGlobales(texto) {
  return String(texto == null ? '' : texto)
    .replace(/[\x00-\x08\x0B\x0C\x0E-\x1F]/g, '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;');
}

function celdaSheetXmlReportesGlobales(colIdx, rowNum, texto) {
  const ref = letraColumnaExcelReportesGlobales(colIdx) + rowNum;
  return (
    '<c r="' +
    ref +
    '" t="inlineStr"><is><t xml:space="preserve">' +
    escaparXmlExcelReportesGlobales(texto) +
    '</t></is></c>'
  );
}

function construirFilasHojaExcelReportesGlobales(titulo, columnasMeta, filasDatos, columnasExport, cards) {
  const filas = [];
  const numCols = Math.max((columnasMeta && columnasMeta.length) || 0, 2);

  filas.push([titulo]);
  filas.push((columnasMeta || []).map(function (col) {
    return col.label;
  }));

  (filasDatos || []).forEach(function (row) {
    const fila = [];
    (columnasExport || []).forEach(function (idx) {
      fila.push(row[idx] != null ? row[idx] : '');
    });
    filas.push(fila);
  });

  filas.push([]);
  filas.push(['TOTALES']);

  (cards || []).forEach(function (card) {
    filas.push([]);
    filas.push([card.titulo]);
    (card.lineas || []).forEach(function (linea) {
      const texto = String(linea || '');
      const sep = texto.indexOf(':');
      if (sep > 0) {
        filas.push([texto.substring(0, sep).trim() + ':', texto.substring(sep + 1).trim()]);
      } else if (texto) {
        filas.push([texto]);
      }
    });
  });

  return { filas: filas, numCols: numCols };
}

function sheetXmlDesdeFilasReportesGlobales(filas) {
  let xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
  xml += '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"';
  xml += ' xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">';
  xml += '<sheetData>';

  filas.forEach(function (fila, idx) {
    const rowNum = idx + 1;
    xml += '<row r="' + rowNum + '">';
    (fila || []).forEach(function (valor, colIdx) {
      xml += celdaSheetXmlReportesGlobales(colIdx, rowNum, valor);
    });
    xml += '</row>';
  });

  xml += '</sheetData></worksheet>';
  return xml;
}

function descargarBlobReportesGlobales(blob, nombreArchivo) {
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = nombreArchivo;
  a.style.display = 'none';
  document.body.appendChild(a);
  a.click();
  setTimeout(function () {
    URL.revokeObjectURL(url);
    a.remove();
  }, 2000);
}

function nombreArchivoExportReportesGlobales(titulo, ext) {
  const base = String(titulo || 'reportes_globales')
    .replace(/[\\/:*?"<>|]+/g, '_')
    .replace(/\s+/g, ' ')
    .trim()
    .substring(0, 120);
  return (base || 'reportes_globales') + '.' + ext;
}

function descargarXlsxJsZipReportesGlobales(titulo, columnasMeta, filasDatos, columnasExport, cards) {
  const built = construirFilasHojaExcelReportesGlobales(
    titulo,
    columnasMeta,
    filasDatos,
    columnasExport,
    cards
  );
  const sheet = sheetXmlDesdeFilasReportesGlobales(built.filas);

  const styles =
    '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' +
    '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">' +
    '<fonts count="1"><font><sz val="11"/><name val="Calibri"/></font></fonts>' +
    '<fills count="1"><fill><patternFill patternType="none"/></fill></fills>' +
    '<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>' +
    '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>' +
    '<cellXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/></cellXfs>' +
    '</styleSheet>';

  const workbook =
    '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' +
    '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" ' +
    'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">' +
    '<sheets><sheet name="Reportes Globales" sheetId="1" r:id="rId1"/></sheets></workbook>';

  const relsRoot =
    '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' +
    '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' +
    '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>' +
    '</Relationships>';

  const relsWb =
    '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' +
    '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' +
    '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>' +
    '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>' +
    '</Relationships>';

  const contentTypes =
    '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' +
    '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">' +
    '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>' +
    '<Default Extension="xml" ContentType="application/xml"/>' +
    '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>' +
    '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>' +
    '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>' +
    '</Types>';

  const zip = new JSZip();
  zip.file('[Content_Types].xml', contentTypes);
  zip.folder('_rels').file('.rels', relsRoot);
  const xl = zip.folder('xl');
  xl.file('workbook.xml', workbook);
  xl.file('styles.xml', styles);
  xl.folder('_rels').file('workbook.xml.rels', relsWb);
  xl.folder('worksheets').file('sheet1.xml', sheet);

  const nombre = nombreArchivoExportReportesGlobales(titulo, 'xlsx');

  if (typeof zip.generateAsync === 'function') {
    return zip.generateAsync({ type: 'blob' }).then(function (blob) {
      descargarBlobReportesGlobales(blob, nombre);
    });
  }

  const blobSync = zip.generate({ type: 'blob' });
  descargarBlobReportesGlobales(blobSync, nombre);
  return Promise.resolve();
}

function descargarExcelHtmlReportesGlobales(titulo, columnasMeta, filasDatos, columnasExport, cards) {
  const esc = function (texto) {
    return String(texto == null ? '' : texto)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  };

  const numCols = Math.max((columnasMeta && columnasMeta.length) || 0, 2);
  const built = construirFilasHojaExcelReportesGlobales(
    titulo,
    columnasMeta,
    filasDatos,
    columnasExport,
    cards
  );
  const titulosCard = {};
  (cards || []).forEach(function (c) {
    titulosCard[c.titulo] = true;
  });

  let html = '<html xmlns:o="urn:schemas-microsoft-com:office:office"';
  html += ' xmlns:x="urn:schemas-microsoft-com:office:excel"';
  html += ' xmlns="http://www.w3.org/TR/REC-html40"><head><meta charset="UTF-8"></head><body>';
  html += '<table border="1" cellspacing="0" cellpadding="4">';

  built.filas.forEach(function (fila, idx) {
    html += '<tr>';
    if (!fila || !fila.length) {
      html += '<td colspan="' + numCols + '">&nbsp;</td>';
    } else if (
      fila.length === 1 &&
      (idx === 0 || String(fila[0]) === 'TOTALES' || titulosCard[fila[0]])
    ) {
      html +=
        '<td colspan="' +
        numCols +
        '" style="font-weight:bold;background:#e9ecef">' +
        esc(fila[0]) +
        '</td>';
    } else {
      for (let c = 0; c < numCols; c++) {
        const esHeader = idx === 1;
        const tag = esHeader ? 'th' : 'td';
        const estilo = esHeader ? ' style="background:#2d4154;color:#fff"' : '';
        html += '<' + tag + estilo + '>' + esc(fila[c] != null ? fila[c] : '') + '</' + tag + '>';
      }
    }
    html += '</tr>';
  });

  html += '</table></body></html>';

  const blob = new Blob(['\ufeff' + html], {
    type: 'application/vnd.ms-excel;charset=utf-8;'
  });
  descargarBlobReportesGlobales(blob, nombreArchivoExportReportesGlobales(titulo, 'xls'));
}

function descargarExcelReportesGlobalesConTotales(titulo, columnasMeta, filasDatos, columnasExport, cards) {
  const cardsFinal = cards && cards.length ? cards : [];

  if (typeof JSZip !== 'undefined') {
    return descargarXlsxJsZipReportesGlobales(
      titulo,
      columnasMeta,
      filasDatos,
      columnasExport,
      cardsFinal
    ).catch(function (err) {
      console.error('Error generando xlsx, fallback HTML:', err);
      descargarExcelHtmlReportesGlobales(
        titulo,
        columnasMeta,
        filasDatos,
        columnasExport,
        cardsFinal
      );
    });
  }

  descargarExcelHtmlReportesGlobales(
    titulo,
    columnasMeta,
    filasDatos,
    columnasExport,
    cardsFinal
  );
  return Promise.resolve();
}

function dispararPdfTempReportesGlobales(tempTable, tempDiv, exportConfig, modo) {
  const extendType = 'pdfHtml5';
  const pdfMakeRef = window.pdfMake;
  let restoreCreatePdf = null;

  if (modo === 'print' && pdfMakeRef && typeof pdfMakeRef.createPdf === 'function') {
    const originalCreatePdf = pdfMakeRef.createPdf.bind(pdfMakeRef);
    restoreCreatePdf = function () {
      pdfMakeRef.createPdf = originalCreatePdf;
    };

    pdfMakeRef.createPdf = function (docDefinition) {
      const pdf = originalCreatePdf(docDefinition);

      pdf.download = function () {
        if (restoreCreatePdf) {
          restoreCreatePdf();
          restoreCreatePdf = null;
        }
        if (typeof pdf.print === 'function') {
          return pdf.print();
        }
        return pdf.getBlob(function (blob) {
          const url = URL.createObjectURL(blob);
          const iframe = document.createElement('iframe');
          iframe.setAttribute('title', 'Imprimir reportes');
          iframe.style.cssText = 'position:fixed;right:0;bottom:0;width:0;height:0;border:0;';
          document.body.appendChild(iframe);
          iframe.onload = function () {
            try {
              iframe.contentWindow.focus();
              iframe.contentWindow.print();
            } catch (err) {
              window.open(url, '_blank');
            }
            setTimeout(function () {
              URL.revokeObjectURL(url);
              iframe.remove();
            }, 60000);
          };
          iframe.src = url;
        });
      };

      return pdf;
    };
  }

  const tempButton = tempTable.button().add(0, Object.assign({ extend: extendType }, exportConfig));
  tempButton.trigger();

  setTimeout(function () {
    if (restoreCreatePdf) {
      restoreCreatePdf();
    }
    tempTable.destroy();
    if (tempDiv && tempDiv.parentNode) {
      tempDiv.remove();
    }
  }, 4000);
}

function customizarPdfExportacionReportesGlobales(doc, tituloPDF, cardsTotales) {
  doc.pageOrientation = 'landscape';
  doc.pageSize = 'A3';
  doc.styles.tableHeader.fillColor = '#2d4154';
  doc.styles.tableHeader.bold = true;
  doc.styles.tableHeader.color = 'white';
  doc.defaultStyle.fontSize = 7;
  doc.styles.tableHeader.fontSize = 7;

  doc.content[0].text = tituloPDF;
  doc.content[0].alignment = 'center';
  doc.content[0].fontSize = 12;

  doc.pageMargins = [5, 5, 5, 5];

  insertarTotalesEnPdfReportesGlobales(doc, cardsTotales || []);

  let tableContent = null;
  let maxCols = 0;
  doc.content.forEach(function (item) {
    if (!item.table || !item.table.body || !item.table.body[0]) {
      return;
    }
    const cols = item.table.body[0].length;
    if (cols > maxCols) {
      maxCols = cols;
      tableContent = item;
    }
  });
  if (tableContent && tableContent.table && tableContent.table.body[0]) {
    const numCols = tableContent.table.body[0].length;
    const availableWidth = 1008 - doc.pageMargins[0] - doc.pageMargins[2];
    const colWidth = availableWidth / numCols;
    tableContent.table.widths = Array(numCols).fill(colWidth);
    tableContent.layout = {
      hLineWidth: function () { return 0.5; },
      vLineWidth: function () { return 0.5; },
      paddingLeft: function () { return 1; },
      paddingRight: function () { return 1; },
      paddingTop: function () { return 1; },
      paddingBottom: function () { return 1; }
    };
  }
}

function exportarTextoCeldaReportesGlobales(data) {
  if (typeof data === 'string') {
    const tempDiv = document.createElement('div');
    tempDiv.innerHTML = data;
    return tempDiv.textContent || tempDiv.innerText || data;
  }
  return data;
}

document.addEventListener('DOMContentLoaded', function () {
  window.filtro_periodo_activo = 'dia';

  const FILTROS_REPORTES_GLOBALES_STORAGE_KEY = 'tpv_reportes_globales_filtros';
  let restaurandoFiltrosReportesGlobales = false;
  const filtrosCacheReportesGlobales = leerFiltrosReportesGlobalesCache();
  const selectorBotonesFecha =
    '#filtro_por_fecha_informe, #filtro_dia, #filtro_mes, #filtro_todos';

  const dt_table = document.querySelector('.datatables-reportes-globales');
  let dt_reportes;

  function leerFiltrosReportesGlobalesCache() {
    try {
      const raw = sessionStorage.getItem(FILTROS_REPORTES_GLOBALES_STORAGE_KEY);
      return raw ? JSON.parse(raw) : null;
    } catch (error) {
      return null;
    }
  }

  function obtenerValorFiltro(id) {
    const el = document.getElementById(id);
    return el ? el.value : '';
  }

  function guardarFiltrosReportesGlobalesCache() {
    if (restaurandoFiltrosReportesGlobales) {
      return;
    }

    const botonActivo = document.querySelector(
      '#filtro_por_fecha_informe.active, #filtro_dia.active, #filtro_mes.active, #filtro_todos.active'
    );
    const estado = {
      filtro_empresa: obtenerValorFiltro('filtro_empresa'),
      filtro_sucursal: obtenerValorFiltro('filtro_sucursal'),
      filtro_fecha_desde: obtenerValorFiltro('filtro_fecha_desde'),
      filtro_fecha_hasta: obtenerValorFiltro('filtro_fecha_hasta'),
      range_fechas: obtenerValorFiltro('rangeFechas'),
      filtro_periodo: window.filtro_periodo_activo || 'dia',
      boton_fecha_activo: botonActivo ? botonActivo.id : '',
      search: dt_reportes ? dt_reportes.search() : '',
      titulo_filtros: window.titulo_filtros_reportes_globales || ''
    };

    sessionStorage.setItem(FILTROS_REPORTES_GLOBALES_STORAGE_KEY, JSON.stringify(estado));
  }

  function aplicarValorSelectFiltro(selectId, value) {
    const select = document.getElementById(selectId);
    if (!select) {
      return;
    }

    const valor = value || '';
    if (valor && !Array.from(select.options).some(function (opt) {
      return opt.value === valor;
    })) {
      select.appendChild(new Option(valor, valor));
    }

    select.value = valor;
    const $select = $(select);
    if ($select.data('select2')) {
      $select.val(valor || null).trigger('change.select2');
    }
  }

  function recargarTabla() {
    if (window.dt_reportes_globales) {
      window.dt_reportes_globales.ajax.reload();
    }
  }

  function sincronizarPeriodo(periodo) {
    window.filtro_periodo_activo = periodo;
    const filtroPeriodo = document.getElementById('filtro_periodo');
    if (filtroPeriodo) {
      filtroPeriodo.value = periodo;
    }
  }

  function actualizarTituloReportesGlobales() {
    const partes = [];

    const filtroEmpresa = document.getElementById('filtro_empresa');
    if (filtroEmpresa && filtroEmpresa.value) {
      partes.push(filtroEmpresa.options[filtroEmpresa.selectedIndex].text);
    }

    const filtroSucursal = document.getElementById('filtro_sucursal');
    if (filtroSucursal && filtroSucursal.value) {
      partes.push(filtroSucursal.options[filtroSucursal.selectedIndex].text);
    }

    const filtroActivo = window.filtro_periodo_activo || 'dia';
    const filtroFechaDesde = document.getElementById('filtro_fecha_desde');
    const filtroFechaHasta = document.getElementById('filtro_fecha_hasta');
    const fechaDesde = filtroFechaDesde ? filtroFechaDesde.value : '';
    const fechaHasta = filtroFechaHasta ? filtroFechaHasta.value : '';

    if (filtroActivo === 'dia') {
      partes.push('hoy');
    } else if (filtroActivo === 'mes') {
      partes.push('este mes');
    } else if (filtroActivo === 'todos') {
      partes.push('todos');
    } else if (fechaDesde || fechaHasta) {
      if (fechaDesde && fechaHasta) {
        if (fechaDesde === fechaHasta) {
          const fecha = new Date(fechaDesde + 'T00:00:00');
          partes.push('del ' + fecha.toLocaleDateString('es-ES'));
        } else {
          const fechaD = new Date(fechaDesde + 'T00:00:00');
          const fechaH = new Date(fechaHasta + 'T00:00:00');
          partes.push(
            'entre el ' +
              fechaD.toLocaleDateString('es-ES') +
              ' y el ' +
              fechaH.toLocaleDateString('es-ES')
          );
        }
      } else if (fechaDesde) {
        const fechaD = new Date(fechaDesde + 'T00:00:00');
        partes.push('desde el ' + fechaD.toLocaleDateString('es-ES'));
      } else {
        const fechaH = new Date(fechaHasta + 'T00:00:00');
        partes.push('hasta el ' + fechaH.toLocaleDateString('es-ES'));
      }
    }

    const textoFinal = partes.length ? ' - ' + partes.join(' - ') : '';
    const textoTitulo = document.getElementById('texto_reportes_globales_titulo');
    if (textoTitulo) {
      textoTitulo.textContent = textoFinal;
    }
    window.titulo_filtros_reportes_globales = textoFinal;
    return 'Reportes Globales' + textoFinal;
  }

  function sincronizarFiltrosReportesGlobales(actualizarTitulo) {
    guardarFiltrosReportesGlobalesCache();
    recargarTabla();
    if (actualizarTitulo) {
      actualizarTituloReportesGlobales();
      guardarFiltrosReportesGlobalesCache();
    }
  }

  function restaurarFiltrosReportesGlobalesDesdeCache() {
    if (!filtrosCacheReportesGlobales || !dt_reportes) {
      return;
    }
    restaurarFiltrosReportesGlobales(filtrosCacheReportesGlobales);
  }

  function restaurarFiltrosReportesGlobales(cache) {
    if (!cache || !dt_reportes) {
      return;
    }

    restaurandoFiltrosReportesGlobales = true;

    sincronizarPeriodo(cache.filtro_periodo || 'dia');
    aplicarValorSelectFiltro('filtro_empresa', cache.filtro_empresa);
    aplicarValorSelectFiltro('filtro_sucursal', cache.filtro_sucursal);

    const filtroFechaDesde = document.getElementById('filtro_fecha_desde');
    const filtroFechaHasta = document.getElementById('filtro_fecha_hasta');
    const rangeFechas = document.getElementById('rangeFechas');

    if (filtroFechaDesde) {
      filtroFechaDesde.value = cache.filtro_fecha_desde || '';
    }
    if (filtroFechaHasta) {
      filtroFechaHasta.value = cache.filtro_fecha_hasta || '';
    }
    if (rangeFechas) {
      if (rangeFechas._flatpickr && cache.filtro_fecha_desde && cache.filtro_fecha_hasta) {
        rangeFechas._flatpickr.setDate(
          [cache.filtro_fecha_desde, cache.filtro_fecha_hasta],
          false
        );
        rangeFechas.value = cache.range_fechas || rangeFechas.value;
      } else if (cache.range_fechas) {
        rangeFechas.value = cache.range_fechas;
      } else {
        rangeFechas.value = '';
      }
    }

    document.querySelectorAll(selectorBotonesFecha).forEach(function (btn) {
      btn.classList.remove('active');
    });
    if (cache.boton_fecha_activo) {
      const botonActivo = document.getElementById(cache.boton_fecha_activo);
      if (botonActivo) {
        botonActivo.classList.add('active');
      }
    }

    if (cache.search) {
      dt_reportes.search(cache.search);
    }

    restaurandoFiltrosReportesGlobales = false;
    sincronizarFiltrosReportesGlobales(true);
  }

  if (window.ListarFiltros) {
    window.ListarFiltros.setOnChange(function () {
      if (restaurandoFiltrosReportesGlobales) {
        return;
      }
      sincronizarFiltrosReportesGlobales(true);
    });
  }

  function configurarFiltrosFecha() {
    const filtroFechaDesde = document.getElementById('filtro_fecha_desde');
    const filtroFechaHasta = document.getElementById('filtro_fecha_hasta');
    const rangeFechas = document.getElementById('rangeFechas');

    function marcarBotonActivo(boton) {
      document.querySelectorAll(selectorBotonesFecha).forEach(function (btn) {
        btn.classList.remove('active');
      });
      if (boton) {
        boton.classList.add('active');
      }
    }

    function formatearRangoVisible(desdeYmd, hastaYmd) {
      if (!rangeFechas || !desdeYmd || !hastaYmd) {
        return;
      }
      const partesDesde = desdeYmd.split('-');
      const partesHasta = hastaYmd.split('-');
      if (partesDesde.length !== 3 || partesHasta.length !== 3) {
        return;
      }
      rangeFechas.value =
        partesDesde[2] + '-' + partesDesde[1] + '-' + partesDesde[0] +
        ' > ' +
        partesHasta[2] + '-' + partesHasta[1] + '-' + partesHasta[0];
    }

    const filtroPorFecha = document.getElementById('filtro_por_fecha_informe');
    if (filtroPorFecha) {
      filtroPorFecha.addEventListener('click', function () {
        const desde = filtroFechaDesde ? filtroFechaDesde.value : '';
        const hasta = filtroFechaHasta ? filtroFechaHasta.value : '';
        if (!desde && !hasta) {
          if (typeof Swal !== 'undefined') {
            Swal.fire({
              icon: 'warning',
              title: 'Atención',
              text: 'Debe seleccionar al menos una fecha',
              confirmButtonText: 'Aceptar'
            });
          }
          return;
        }
        sincronizarPeriodo('fecha');
        marcarBotonActivo(this);
        sincronizarFiltrosReportesGlobales(true);
      });
    }

    const filtroDia = document.getElementById('filtro_dia');
    if (filtroDia) {
      filtroDia.addEventListener('click', function () {
        const hoy = new Date().toISOString().split('T')[0];
        if (filtroFechaDesde) filtroFechaDesde.value = hoy;
        if (filtroFechaHasta) filtroFechaHasta.value = hoy;
        formatearRangoVisible(hoy, hoy);
        sincronizarPeriodo('dia');
        marcarBotonActivo(this);
        sincronizarFiltrosReportesGlobales(true);
      });
    }

    const filtroMes = document.getElementById('filtro_mes');
    if (filtroMes) {
      filtroMes.addEventListener('click', function () {
        if (filtroFechaDesde) filtroFechaDesde.value = '';
        if (filtroFechaHasta) filtroFechaHasta.value = '';
        if (rangeFechas) rangeFechas.value = '';
        if (rangeFechas && rangeFechas._flatpickr) {
          rangeFechas._flatpickr.clear();
        }
        sincronizarPeriodo('mes');
        marcarBotonActivo(this);
        sincronizarFiltrosReportesGlobales(true);
      });
    }

    const filtroTodos = document.getElementById('filtro_todos');
    if (filtroTodos) {
      filtroTodos.addEventListener('click', function () {
        if (filtroFechaDesde) filtroFechaDesde.value = '';
        if (filtroFechaHasta) filtroFechaHasta.value = '';
        if (rangeFechas) rangeFechas.value = '';
        if (rangeFechas && rangeFechas._flatpickr) {
          rangeFechas._flatpickr.clear();
        }
        sincronizarPeriodo('todos');
        marcarBotonActivo(this);
        sincronizarFiltrosReportesGlobales(true);
      });
    }

    if (!filtrosCacheReportesGlobales) {
      if (filtroDia) {
        filtroDia.click();
      } else {
        sincronizarPeriodo('dia');
        actualizarTituloReportesGlobales();
      }
    }
  }

  function renderMoneda(data) {
    return '<span class="text-nowrap fw-medium">' + (data || '0,00 €') + '</span>';
  }

  function renderGramos(data) {
    return '<span class="text-nowrap">' + (data || '0,00 gr') + '</span>';
  }

  function esTotalCero(valor) {
    if (valor === null || valor === undefined || valor === '') {
      return true;
    }

    const texto = String(valor)
      .replace(/\s/g, '')
      .replace(/%/g, '')
      .replace(/€/g, '')
      .replace(/\./g, '')
      .replace(',', '.');

    const numero = parseFloat(texto);
    return isNaN(numero) || numero === 0;
  }

  function clasesTotalSpan(claseBase, valor) {
    return esTotalCero(valor) ? claseBase + ' nototals' : claseBase;
  }

  function renderPorcentajeBadge(data, type) {
    if (type && type !== 'display') {
      return data;
    }
    const texto = data || '0 %';
    return '<span class="' + clasesTotalSpan('totales-porcentajes', texto) + '">' + texto + '</span>';
  }

  function renderEurosBadge(data, type) {
    if (type && type !== 'display') {
      return data;
    }
    const texto = data || '0 €';
    return '<span class="' + clasesTotalSpan('totales-euros', texto) + '">' + texto + '</span>';
  }

  function aplicarTitulosBeneficioOroPlata(thead, api) {
    // Grupo Beneficio Oro/Plata sustituido por Lotes intervenidos (cabeceras en HTML).
  }

  const modalEditarInformeEl = document.getElementById('modalEditarInformeGlobal');
  const modalEditarInforme = modalEditarInformeEl && typeof bootstrap !== 'undefined'
    ? new bootstrap.Modal(modalEditarInformeEl)
    : null;

  function hacerModalArrastrable(modalEl) {
    if (!modalEl) {
      return;
    }

    const dialog = modalEl.querySelector('.modal-dialog');
    const header = modalEl.querySelector('.modal-draggable-handle');

    if (!dialog || !header) {
      return;
    }

    let arrastrando = false;
    let inicioX = 0;
    let inicioY = 0;
    let posX = 0;
    let posY = 0;

    function reiniciarPosicion() {
      dialog.classList.remove('modal-dialog-draggable');
      dialog.style.removeProperty('position');
      dialog.style.removeProperty('left');
      dialog.style.removeProperty('top');
      dialog.style.removeProperty('margin');
      dialog.style.removeProperty('transform');
      dialog.style.removeProperty('width');
      header.classList.remove('is-dragging');
      arrastrando = false;
    }

    function fijarPosicionInicial() {
      const rect = dialog.getBoundingClientRect();
      dialog.classList.add('modal-dialog-draggable');
      dialog.style.position = 'fixed';
      dialog.style.margin = '0';
      dialog.style.left = rect.left + 'px';
      dialog.style.top = rect.top + 'px';
      dialog.style.transform = 'none';
      dialog.style.width = rect.width + 'px';
      posX = rect.left;
      posY = rect.top;
    }

    modalEl.addEventListener('shown.bs.modal', fijarPosicionInicial);
    modalEl.addEventListener('hidden.bs.modal', reiniciarPosicion);

    header.addEventListener('mousedown', function (evento) {
      if (evento.button !== 0 || evento.target.closest('.btn-close')) {
        return;
      }

      arrastrando = true;
      inicioX = evento.clientX;
      inicioY = evento.clientY;
      header.classList.add('is-dragging');
      evento.preventDefault();
    });

    document.addEventListener('mousemove', function (evento) {
      if (!arrastrando || !modalEl.classList.contains('show')) {
        return;
      }

      posX += evento.clientX - inicioX;
      posY += evento.clientY - inicioY;
      inicioX = evento.clientX;
      inicioY = evento.clientY;
      dialog.style.left = posX + 'px';
      dialog.style.top = posY + 'px';
    });

    document.addEventListener('mouseup', function () {
      if (!arrastrando) {
        return;
      }

      arrastrando = false;
      header.classList.remove('is-dragging');
    });
  }

  hacerModalArrastrable(modalEditarInformeEl);

  function mostrarEstadoLotes(modo) {
    const loading = document.getElementById('lotesInformeLoading');
    const vacio = document.getElementById('lotesInformeVacio');
    const error = document.getElementById('lotesInformeError');
    const wrap = document.getElementById('lotesInformeTablaWrap');

    if (loading) {
      loading.classList.toggle('d-none', modo !== 'loading');
    }
    if (vacio) {
      vacio.classList.toggle('d-none', modo !== 'vacio');
    }
    if (error) {
      error.classList.toggle('d-none', modo !== 'error');
      if (modo !== 'error') {
        error.textContent = '';
      }
    }
    if (wrap) {
      wrap.classList.toggle('d-none', modo !== 'tabla');
    }
  }

  function renderFilasLotes(lotes, idLoteResaltar) {
    const tbody = document.getElementById('lotesInformeTbody');
    if (!tbody) {
      return;
    }

    const idResaltar = parseInt(idLoteResaltar, 10) || 0;
    tbody.innerHTML = '';
    lotes.forEach(function (lote) {
      const tr = document.createElement('tr');
      const clasePeso = lote.peso_coincide
        ? 'bg-success-subtle text-success fw-semibold'
        : 'bg-warning-subtle text-warning-emphasis fw-semibold';

      if (idResaltar && parseInt(lote.id_lote, 10) === idResaltar) {
        tr.classList.add('table-warning');
      }

      const idLote = parseInt(lote.id_lote, 10) || 0;
      const identificador = parseInt(lote.identificador, 10) || 0;
      const celdaLote = idLote > 0 && identificador > 0
        ? '<a href="lote.php?id=' + encodeURIComponent(String(identificador)) + '" target="_blank" rel="noopener noreferrer" class="fw-semibold">' + idLote + '</a>'
        : (idLote > 0 ? String(idLote) : '—');

      tr.innerHTML =
        '<td class="text-nowrap">' + celdaLote + '</td>' +
        '<td class="text-nowrap">' + (lote.fecha_compra || '—') + '</td>' +
        '<td class="text-capitalize">' + (lote.tipo_de_lote || '—') + '</td>' +
        '<td>' + (lote.empeno || '—') + '</td>' +
        '<td class="text-end ' + clasePeso + '">' + (lote.peso_neto || '—') + '</td>' +
        '<td class="text-end">' + (lote.peso_bruto || '—') + '</td>' +
        '<td class="text-end">' + (lote.merma || '—') + '</td>' +
        '<td class="text-nowrap">' + (lote.fecha_vencimiento || '—') + '</td>' +
        '<td class="text-center">' + (lote.cantidad_articulos != null ? lote.cantidad_articulos : '—') + '</td>' +
        '<td class="text-end ' + clasePeso + '">' + (lote.peso_articulos || '—') + '</td>';

      tbody.appendChild(tr);
    });
  }

  function cargarLotesInforme(idSucursal, fechaYmd, idLoteResaltar) {
    mostrarEstadoLotes('loading');
    renderFilasLotes([]);

    const formData = new FormData();
    formData.append('id_sucursal', idSucursal);
    formData.append('fecha', fechaYmd);

    return fetch('parts/reportes_globales/unique/load_lotes_dia.php', {
      method: 'POST',
      body: formData
    })
      .then(function (response) {
        return response.json();
      })
      .then(function (data) {
        if (!data || !data.ok) {
          throw new Error((data && data.error) || 'No se pudieron cargar los lotes');
        }

        const lotes = data.lotes || [];
        if (!lotes.length) {
          mostrarEstadoLotes('vacio');
          return;
        }

        renderFilasLotes(lotes, idLoteResaltar);
        mostrarEstadoLotes('tabla');
      })
      .catch(function (error) {
        const errorEl = document.getElementById('lotesInformeError');
        if (errorEl) {
          errorEl.textContent = error.message || 'Error al cargar los lotes';
        }
        mostrarEstadoLotes('error');
      });
  }

  function abrirModalLotesPorDia(opts) {
    opts = opts || {};
    const idSucursal = parseInt(opts.id_sucursal, 10) || 0;
    const fechaYmd = opts.fecha_ymd || '';
    const fechaLabel = opts.fecha_label || fechaYmd || '-';
    const nombreSucursal = opts.nombre_sucursal || '-';
    const nombreEmpresa = opts.nombre_empresa || '-';
    const idLoteResaltar = parseInt(opts.id_lote, 10) || 0;

    document.getElementById('editar_informe_sucursal').textContent = nombreSucursal;
    document.getElementById('editar_nombre_empresa').textContent = nombreEmpresa;
    document.getElementById('editar_fecha_informe').textContent = fechaLabel;

    if (modalEditarInforme) {
      modalEditarInforme.show();
    }

    if (!idSucursal || !fechaYmd) {
      const errorEl = document.getElementById('lotesInformeError');
      if (errorEl) {
        errorEl.textContent = 'No se pudo identificar la sucursal o la fecha del informe.';
      }
      mostrarEstadoLotes('error');
      return;
    }

    cargarLotesInforme(idSucursal, fechaYmd, idLoteResaltar);
  }

  window.abrirModalLotesPorDiaReportesGlobales = abrirModalLotesPorDia;

  function abrirModalEditarInforme(row) {
    const data = row.data();
    if (!data || !data[33]) {
      return;
    }

    const meta = data[34] || {};
    const idSucursal = meta.id_sucursal ? parseInt(meta.id_sucursal, 10) : 0;
    const fechaYmdRaw = meta.fecha_informe || '';
    const fechaYmd = fechaYmdRaw.length >= 10 ? fechaYmdRaw.substring(0, 10) : fechaYmdRaw;

    abrirModalLotesPorDia({
      id_sucursal: idSucursal,
      fecha_ymd: fechaYmd,
      fecha_label: data[0] || fechaYmd,
      nombre_sucursal: meta.nombre_sucursal || data[1] || '-',
      nombre_empresa: meta.nombre_empresa || data[35] || '-'
    });
  }

  function bindModalEditarInforme() {
    if (modalEditarInformeEl) {
      modalEditarInformeEl.addEventListener('hidden.bs.modal', function () {
        renderFilasLotes([]);
        mostrarEstadoLotes('tabla');
      });
    }
  }

  if (dt_table) {
    dt_reportes = new DataTable(dt_table, {
      processing: true,
      serverSide: true,
      deferRender: true,
      searchDelay: 500,
      timeout: 60000,
      language: DATATABLES_SPANISH,
      ajax: {
        url: 'parts/reportes_globales/unique/load_list.php',
        type: 'POST',
        data: function (d) {
          d.filtro_empresa = document.getElementById('filtro_empresa')?.value || '';
          d.filtro_sucursal = document.getElementById('filtro_sucursal')?.value || '';
          d.filtro_fecha_desde = document.getElementById('filtro_fecha_desde')?.value || '';
          d.filtro_fecha_hasta = document.getElementById('filtro_fecha_hasta')?.value || '';
          d.filtro_periodo = window.filtro_periodo_activo || document.getElementById('filtro_periodo')?.value || 'dia';
          return d;
        },
        dataSrc: function (json) {
          return json.data || [];
        },
        error: function (xhr) {
          console.error('Error en DataTable reportes globales:', xhr.responseText);
          if (xhr.status === 401) {
            window.location.href = 'login.php';
          }
        }
      },
      columns: [
        { data: 0, title: 'Fecha' },
        { data: 1, title: 'Sucursal' },
        { data: 2, title: 'Rango lotes' },
        { data: 3, title: 'Pagado' },
        { data: 4, title: 'Lotes' },
        { data: 5, title: 'Peso' },
        { data: 6, title: 'Media €/gr' },
        { data: 7, title: 'Pagado' },
        { data: 8, title: 'Peso' },
        { data: 9, title: 'Lotes' },
        { data: 10, title: 'Media €/gr' },
        { data: 11, title: 'Total' },
        { data: 12, title: 'Pagado' },
        { data: 13, title: 'Peso' },
        { data: 14, title: 'Beneficios Renovaciones' },
        { data: 15, title: 'Total' },
        { data: 16, title: 'Valor' },
        { data: 17, title: 'Peso' },
        { data: 18, title: 'Lotes' },
        { data: 19, title: 'Pagado' },
        { data: 20, title: 'Peso' },
        { data: 21, title: 'Total ventas' },
        { data: 22, title: 'Cobrado' },
        { data: 23, title: 'Beneficio' },
        { data: 24, title: 'Total ventas plazos' },
        { data: 25, title: 'Euros ventas plazo' },
        { data: 26, title: 'Gastos' },
        { data: 27, title: 'Contado entrada' },
        { data: 28, title: 'Contado salida' },
        { data: 29, title: 'Tarjeta' },
        { data: 30, title: 'Transferencia entrada' },
        { data: 31, title: 'Transferencia salida' },
        { data: 32, title: 'Bizum' },
        { data: 33, visible: false, searchable: false, title: 'ID' },
        { data: 34, visible: false, searchable: false, title: 'Meta' },
        { data: 35, visible: false, searchable: true, title: 'Empresa' }
      ],
      rowGroup: {
        dataSrc: 35,
        startClassName: 'bg-label-primary',
        startRender: function (rows, group) {
          return $('<tr class="bg-label-primary"/>')
            .append(
              '<td colspan="33">' +
                '<span class="reportes-globales-empresa-group-label">' +
                  '<i class="icon-base ri ri-building-line me-2"></i>' +
                  (group || 'Sin empresa') +
                '</span>' +
              '</td>'
            );
        }
      },
      createdRow: function (row) {
        row.classList.add('cursor-pointer');
        row.setAttribute('title', 'Click para ver los lotes del día');
      },
      columnDefs: [
        {
          targets: 0,
          className: 'text-center',
          render: function (data, type) {
            const label = data || '-';
            if (type === 'display') {
              return '<span class="fw-medium text-heading text-nowrap">' + label + '</span>';
            }
            return label;
          }
        },
        {
          targets: 1,
          render: function (data) {
            return '<span class="fw-medium text-heading">' + (data || 'Sin sucursal') + '</span>';
          }
        },
        {
          targets: 2,
          className: 'text-center text-nowrap',
          orderable: true,
          render: function (data) {
            return '<span class="fw-medium text-heading">' + (data || '-') + '</span>';
          }
        },
        {
          targets: [3, 6, 7, 10, 12, 14, 16, 19, 22, 23, 25, 26, 27, 28, 29, 30, 31, 32],
          className: 'text-end',
          render: renderMoneda
        },
        {
          targets: [4, 9, 11, 15, 18, 21, 24],
          className: 'text-center'
        },
        {
          targets: [5, 8, 13, 17, 20],
          className: 'text-end',
          render: renderGramos
        },
        {
          targets: [33, 34, 35],
          visible: false,
          searchable: false,
          className: 'reportes-globales-col-oculta'
        }
      ],
      headerCallback: function (thead) {
        thead.querySelectorAll('tr:last-child th').forEach(function (th) {
          th.classList.add('border-0');
        });
      },
      initComplete: function () {
        configurarFiltrosFecha();
        configurarVisibilidadColumnasReportesGlobales(this.api());
      },
      order: [[0, 'asc']],
      pageLength: 25,
      lengthMenu: [10, 25, 50, 100],
      autoWidth: false,
      stateSave: false,
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
                      action: function (e, dt, button, config) {
                        exportarTodosLosDatosReportesGlobales('excel', dt);
                      },
                      exportOptions: {
                        columns: ':visible'
                      }
                    },
                    {
                      extend: 'pdf',
                      text: '<span class="d-flex align-items-center"><i class="icon-base ri ri-file-pdf-line me-1"></i>PDF</span>',
                      className: 'dropdown-item',
                      orientation: 'landscape',
                      action: function (e, dt, button, config) {
                        exportarTodosLosDatosReportesGlobales('pdf', dt);
                      },
                      exportOptions: {
                        columns: ':visible'
                      }
                    },
                    {
                      extend: 'copy',
                      text: '<i class="icon-base ri ri-file-copy-line me-1"></i>Copiar',
                      className: 'dropdown-item',
                      action: function (e, dt, button, config) {
                        exportarTodosLosDatosReportesGlobales('copy', dt);
                      },
                      exportOptions: {
                        columns: ':visible'
                      }
                    }
                  ]
                },
                {
                  text:
                    '<span class="d-flex align-items-center justify-content-center gap-2">' +
                    '<i class="icon-base ri ri-printer-line icon-16px"></i>' +
                    '<span>Imprimir</span>' +
                    '</span>',
                  className: 'btn btn-primary waves-effect ms-2 button-imprimir-reportes-globales',
                  action: function (e, dt) {
                    exportarTodosLosDatosReportesGlobales('print', dt);
                  }
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
          rowId: 'footer_pagin_reportes',
          rowClass: 'row mx-3 justify-content-between',
          features: ['info']
        },
        bottomEnd: 'paging'
      },
      responsive: false
    });

    window.dt_reportes_globales = dt_reportes;
    bindModalEditarInforme();
    setTimeout(function () {
      configurarVisibilidadColumnasReportesGlobales(dt_reportes);
    }, 0);

    dt_reportes.on('search.dt', function () {
      guardarFiltrosReportesGlobalesCache();
    });

    if (filtrosCacheReportesGlobales) {
      if (window.ListarFiltros && typeof window.ListarFiltros.setOnReady === 'function') {
        window.ListarFiltros.setOnReady(restaurarFiltrosReportesGlobalesDesdeCache);
      } else {
        setTimeout(restaurarFiltrosReportesGlobalesDesdeCache, 0);
      }
    }

    dt_reportes.on('xhr.dt', function () {
      if (typeof window.cargarEstadisticasReportesGlobales === 'function') {
        window.cargarEstadisticasReportesGlobales();
      }
    });

    $(dt_table).on('click', 'tbody tr:not(.dtrg-group)', function () {
      const row = dt_reportes.row(this);
      if (!row || !row.data() || !row.data()[33]) {
        return;
      }

      abrirModalEditarInforme(row);
    });
  }

  function exportarTodosLosDatosReportesGlobales(tipo, dt) {
    if (tipo === 'excel' || tipo === 'pdf' || tipo === 'print') {
      if (typeof pedirColumnasExportacion !== 'function') {
        ejecutarExportacionReportesGlobales(tipo, dt, COLUMNAS_EXPORTABLES_REPORTES_GLOBALES.map(function (col) {
          return col.index;
        }));
        return;
      }

      pedirColumnasExportacion({
        columnas: COLUMNAS_EXPORTABLES_REPORTES_GLOBALES,
        idPrefix: 'reportes-globales',
        mensajeObligatorias: 'Fecha y Sucursal son obligatorias'
      })
        .then(function (columnasSeleccionadas) {
          ejecutarExportacionReportesGlobales(tipo, dt, columnasSeleccionadas);
        })
        .catch(function () {});
      return;
    }

    ejecutarExportacionReportesGlobales(tipo, dt, COLUMNAS_EXPORTABLES_REPORTES_GLOBALES.map(function (col) {
      return col.index;
    }));
  }

  function ejecutarExportacionReportesGlobales(tipo, dt, columnasSeleccionadas) {
    const columnasExport = columnasSeleccionadas.slice().sort(function (a, b) {
      return a - b;
    });
    const columnasMeta = columnasExport.map(function (idx) {
      return COLUMNAS_EXPORTABLES_REPORTES_GLOBALES.find(function (col) {
        return col.index === idx;
      });
    }).filter(Boolean);

    Swal.fire({
      title: tipo === 'print' ? 'Preparando impresión...' : 'Generando exportación...',
      text: 'Obteniendo todos los registros',
      allowOutsideClick: false,
      didOpen: function () {
        Swal.showLoading();
      }
    });

    const formData = new FormData();
    formData.append('export_all', '1');
    formData.append('search', dt.search() || '');
    formData.append('filtro_empresa', document.getElementById('filtro_empresa')?.value || '');
    formData.append('filtro_sucursal', document.getElementById('filtro_sucursal')?.value || '');
    formData.append('filtro_fecha_desde', document.getElementById('filtro_fecha_desde')?.value || '');
    formData.append('filtro_fecha_hasta', document.getElementById('filtro_fecha_hasta')?.value || '');
    formData.append('filtro_periodo', window.filtro_periodo_activo || document.getElementById('filtro_periodo')?.value || 'dia');

    const filtrosStats = new URLSearchParams();
    filtrosStats.append('filtro_empresa', document.getElementById('filtro_empresa')?.value || '');
    filtrosStats.append('filtro_sucursal', document.getElementById('filtro_sucursal')?.value || '');
    filtrosStats.append('filtro_fecha_desde', document.getElementById('filtro_fecha_desde')?.value || '');
    filtrosStats.append('filtro_fecha_hasta', document.getElementById('filtro_fecha_hasta')?.value || '');
    filtrosStats.append(
      'filtro_periodo',
      window.filtro_periodo_activo || document.getElementById('filtro_periodo')?.value || 'dia'
    );
    filtrosStats.append('search', dt.search() || '');

    const peticionListado = fetch('parts/reportes_globales/unique/load_list.php', {
      method: 'POST',
      body: formData
    }).then(function (response) {
      return response.json();
    });

    const peticionTotales =
      tipo === 'excel' || tipo === 'pdf' || tipo === 'print'
        ? fetch('parts/reportes_globales/unique/load_stats.php', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/x-www-form-urlencoded',
              'X-Requested-With': 'XMLHttpRequest'
            },
            body: filtrosStats.toString()
          })
            .then(function (response) {
              return response.json();
            })
            .catch(function () {
              return null;
            })
        : Promise.resolve(null);

    Promise.all([peticionListado, peticionTotales])
      .then(function (results) {
        const responseData = results[0];
        const statsData = results[1];

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

        const headersHtml = columnasMeta.map(function (col) {
          return '<th>' + col.label + '</th>';
        }).join('');
        const columnsConfig = columnasExport.map(function (idx) {
          return { data: idx };
        });
        const exportColumnIndices = columnasExport.map(function (_, i) {
          return i;
        });

        let cardsTotales = [];
        if (statsData && statsData.success && statsData.totales) {
          cardsTotales = construirCardsTotalesDesdeStatsReportesGlobales(statsData.totales);
        } else {
          cardsTotales = obtenerCardsTotalesReportesGlobalesParaExport();
        }

        const filasDatos = responseData.data.map(function (row) {
          return row.map(exportarTextoCeldaReportesGlobales);
        });

        const tituloExport = actualizarTituloReportesGlobales();

        if (tipo === 'excel') {
          return descargarExcelReportesGlobalesConTotales(
            tituloExport,
            columnasMeta,
            filasDatos,
            columnasExport,
            cardsTotales
          );
        }

        const tempTableId = 'temp-export-table-reportes-globales-' + Date.now();
        const tempDiv = document.createElement('div');
        tempDiv.style.display = 'none';
        tempDiv.innerHTML = '<table id="' + tempTableId + '"><thead><tr>' + headersHtml + '</tr></thead></table>';
        document.body.appendChild(tempDiv);

        const tempTable = $('#' + tempTableId).DataTable({
          data: filasDatos,
          columns: columnsConfig,
          paging: false,
          searching: false,
          ordering: false,
          dom: 't',
          buttons: []
        });

        const exportConfig = {
          title: tituloExport,
          exportOptions: {
            columns: exportColumnIndices
          }
        };

        if (tipo === 'pdf' || tipo === 'print') {
          exportConfig.orientation = 'landscape';
          exportConfig.pageSize = 'A3';
          exportConfig.customize = function (doc) {
            customizarPdfExportacionReportesGlobales(doc, tituloExport, cardsTotales);
          };
          dispararPdfTempReportesGlobales(
            tempTable,
            tempDiv,
            exportConfig,
            tipo === 'print' ? 'print' : 'download'
          );
          return;
        }

        const tempButton = tempTable.button().add(0, Object.assign({ extend: tipo }, exportConfig));
        tempButton.trigger();

        setTimeout(function () {
          tempTable.destroy();
          tempDiv.remove();
        }, 2000);
      })
      .catch(function (error) {
        Swal.close();
        Swal.fire({
          title: 'Error',
          text: 'Ha ocurrido un error al exportar: ' + (error.message || error),
          icon: 'error',
          confirmButtonText: 'Aceptar'
        });
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
