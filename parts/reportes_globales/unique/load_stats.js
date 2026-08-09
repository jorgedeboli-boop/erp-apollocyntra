/**
 * Totales inferiores de Reportes Globales
 */
'use strict';

const ESTADISTICAS_REPORTES_GLOBALES = [
  { key: 'total_lotes', elementoId: 'rg-total-lotes', formato: 'count' },
  { key: 'total_lotes_gramos', elementoId: 'rg-total-lotes-gramos', formato: 'peso' },
  { key: 'total_lotes_euros', elementoId: 'rg-total-lotes-euros', formato: 'euros' },
  { key: 'total_compras', elementoId: 'rg-total-compras', formato: 'count' },
  { key: 'total_compras_gramos', elementoId: 'rg-total-compras-gramos', formato: 'peso' },
  { key: 'total_compras_euros', elementoId: 'rg-total-compras-euros', formato: 'euros' },
  { key: 'total_compras_media', elementoId: 'rg-total-compras-media', formato: 'euros' },
  { key: 'total_empenos', elementoId: 'rg-total-empenos', formato: 'count' },
  { key: 'total_empenos_gramos', elementoId: 'rg-total-empenos-gramos', formato: 'peso' },
  { key: 'total_empenos_euros', elementoId: 'rg-total-empenos-euros', formato: 'euros' },
  { key: 'total_empenos_media', elementoId: 'rg-total-empenos-media', formato: 'euros' },
  { key: 'total_oro', elementoId: 'rg-total-oro', formato: 'count' },
  { key: 'total_oro_gramos', elementoId: 'rg-total-oro-gramos', formato: 'peso' },
  { key: 'total_oro_euros', elementoId: 'rg-total-oro-euros', formato: 'euros' },
  { key: 'total_oro_media', elementoId: 'rg-total-oro-media', formato: 'euros' },
  { key: 'total_plata', elementoId: 'rg-total-plata', formato: 'count' },
  { key: 'total_plata_gramos', elementoId: 'rg-total-plata-gramos', formato: 'peso' },
  { key: 'total_plata_euros', elementoId: 'rg-total-plata-euros', formato: 'euros' },
  { key: 'total_plata_media', elementoId: 'rg-total-plata-media', formato: 'euros' },
  { key: 'total_ventas', elementoId: 'rg-total-ventas', formato: 'count' },
  { key: 'total_euros_ventas', elementoId: 'rg-total-euros-ventas', formato: 'euros' },
  { key: 'total_coste_art_venta', elementoId: 'rg-total-coste-art-venta', formato: 'euros' },
  { key: 'total_beneficio_ventas', elementoId: 'rg-total-beneficio-ventas', formato: 'euros' },
  { key: 'base_imponible_ventas', elementoId: 'rg-base-imponible-ventas', formato: 'euros' },
  { key: 'cuota_iva_ventas', elementoId: 'rg-cuota-iva-ventas', formato: 'euros' },
  { key: 'beneficio_final_ventas', elementoId: 'rg-beneficio-final-ventas', formato: 'euros' },
  { key: 'total_ventas_plazo', elementoId: 'rg-total-ventas-plazo', formato: 'count' },
  { key: 'total_ventas_plazo_euro', elementoId: 'rg-total-euros-ventas-plazo', formato: 'euros' },
  { key: 'total_euros_renovaciones', elementoId: 'rg-total-euros-renovaciones', formato: 'euros' },
  { key: 'iva_renovaciones', elementoId: 'rg-iva-renovaciones', formato: 'euros' },
  { key: 'beneficio_renovaciones', elementoId: 'rg-beneficio-renovaciones', formato: 'euros' },
  { key: 'total_gastos', elementoId: 'rg-total-gastos', formato: 'euros' },
  { key: 'total_contratos_intervenidos', elementoId: 'rg-total-lotes-intervenidos', formato: 'count' },
  { key: 'total_euros_contratos_intervenidos', elementoId: 'rg-total-euros-intervenidos', formato: 'euros' },
  { key: 'total_gramos_contratos_intervenidos', elementoId: 'rg-total-gramos-intervenidos', formato: 'peso' },
  { key: 'total_caja_entradas', elementoId: 'rg-total-contado-entrada', formato: 'euros' },
  { key: 'total_caja_salidas', elementoId: 'rg-total-contado-salida', formato: 'euros' },
  { key: 'total_operaciones_tarjeta', elementoId: 'rg-total-tarjeta', formato: 'euros' },
  { key: 'total_operaciones_trasnferencia_entrada', elementoId: 'rg-total-transf-entrada', formato: 'euros' },
  { key: 'total_operaciones_trasnferencia_salida', elementoId: 'rg-total-transf-salida', formato: 'euros' },
  { key: 'total_operaciones_bizum', elementoId: 'rg-total-bizum', formato: 'euros' },
  { key: 'ventas_contado', elementoId: 'rg-ventas-contado', formato: 'count' },
  { key: 'ventas_contado_euros', elementoId: 'rg-ventas-contado-euros', formato: 'euros' },
  { key: 'ventas_contado_media', elementoId: 'rg-ventas-contado-media', formato: 'euros' },
  { key: 'ventas_transferencia', elementoId: 'rg-ventas-transferencia', formato: 'count' },
  { key: 'ventas_transferencia_euros', elementoId: 'rg-ventas-transferencia-euros', formato: 'euros' },
  { key: 'ventas_transferencia_media', elementoId: 'rg-ventas-transferencia-media', formato: 'euros' },
  { key: 'ventas_tarjeta', elementoId: 'rg-ventas-tarjeta', formato: 'count' },
  { key: 'ventas_tarjeta_euros', elementoId: 'rg-ventas-tarjeta-euros', formato: 'euros' },
  { key: 'ventas_tarjeta_media', elementoId: 'rg-ventas-tarjeta-media', formato: 'euros' },
  { key: 'ventas_bizum', elementoId: 'rg-ventas-bizum', formato: 'count' },
  { key: 'ventas_bizum_euros', elementoId: 'rg-ventas-bizum-euros', formato: 'euros' },
  { key: 'ventas_bizum_media', elementoId: 'rg-ventas-bizum-media', formato: 'euros' }
];

function formatearTotalReportesGlobales(valor, formato) {
  if (formato === 'peso' || formato === 'euros') {
    return parseFloat(valor || 0).toLocaleString('es-ES', {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2
    });
  }

  return parseInt(valor || 0, 10).toLocaleString('es-ES');
}

function obtenerFiltrosReportesGlobales() {
  const params = new URLSearchParams();
  const searchValue = window.dt_reportes_globales ? window.dt_reportes_globales.search() : '';

  params.append('filtro_empresa', document.getElementById('filtro_empresa')?.value || '');
  params.append('filtro_sucursal', document.getElementById('filtro_sucursal')?.value || '');
  params.append('filtro_fecha_desde', document.getElementById('filtro_fecha_desde')?.value || '');
  params.append('filtro_fecha_hasta', document.getElementById('filtro_fecha_hasta')?.value || '');
  params.append(
    'filtro_periodo',
    window.filtro_periodo_activo || document.getElementById('filtro_periodo')?.value || 'dia'
  );
  params.append('search', searchValue || '');

  return params;
}

function setLoadingStatsReportesGlobales(mostrar) {
  document.querySelectorAll('#totales_reportes_globales .stats-loading').forEach(function (el) {
    el.style.display = mostrar ? 'block' : 'none';
  });
}

function pintarTotalesReportesGlobales(totales) {
  ESTADISTICAS_REPORTES_GLOBALES.forEach(function (stat) {
    const elemento = document.getElementById(stat.elementoId);
    if (!elemento) {
      return;
    }
    elemento.textContent = formatearTotalReportesGlobales(totales[stat.key], stat.formato);
    elemento.style.color = '';
  });
}

function marcarErrorTotalesReportesGlobales() {
  ESTADISTICAS_REPORTES_GLOBALES.forEach(function (stat) {
    const elemento = document.getElementById(stat.elementoId);
    if (!elemento) {
      return;
    }
    elemento.textContent = 'Error';
    elemento.style.color = '#dc3545';
  });
}

function setEstadoLotesDescuadrados(estado) {
  const loading = document.getElementById('rg-lotes-descuadrados-loading');
  const vacio = document.getElementById('rg-lotes-descuadrados-vacio');
  const lista = document.getElementById('rg-lotes-descuadrados-lista');

  if (loading) {
    loading.classList.toggle('d-none', estado !== 'loading');
  }
  if (vacio) {
    vacio.classList.toggle('d-none', estado !== 'vacio');
  }
  if (lista) {
    lista.classList.toggle('d-none', estado !== 'lista');
  }
}

function pintarLotesPesoDescuadrados(lotes) {
  const lista = document.getElementById('rg-lotes-descuadrados-lista');
  if (!lista) {
    return;
  }

  lista.innerHTML = '';

  if (!lotes || !lotes.length) {
    setEstadoLotesDescuadrados('vacio');
    return;
  }

  lotes.forEach(function (lote) {
    const btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'rg-lote-descuadrado-item';
    btn.innerHTML =
      '<span class="rg-lote-descuadrado-titulo">Nº lote ' +
      (lote.id_lote || '—') +
      '</span>' +
      '<span class="rg-lote-descuadrado-meta">Sucursal ' +
      (lote.nombre_sucursal || '—') +
      ' · ' +
      (lote.fecha_compra_label || lote.fecha_compra || '—') +
      '</span>';

    btn.addEventListener('click', function () {
      if (typeof window.abrirModalLotesPorDiaReportesGlobales === 'function') {
        window.abrirModalLotesPorDiaReportesGlobales({
          id_sucursal: lote.id_sucursal,
          fecha_ymd: lote.fecha_compra,
          fecha_label: lote.fecha_compra_label || lote.fecha_compra,
          nombre_sucursal: lote.nombre_sucursal,
          nombre_empresa: lote.nombre_empresa,
          id_lote: lote.id_lote
        });
      }
    });

    lista.appendChild(btn);
  });

  setEstadoLotesDescuadrados('lista');
}

window.cargarLotesPesoDescuadradosReportesGlobales = function () {
  if (!document.getElementById('rg-lotes-descuadrados-lista')) {
    return;
  }

  setEstadoLotesDescuadrados('loading');

  fetch('parts/reportes_globales/unique/load_lotes_peso_descuadrado.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded',
      'X-Requested-With': 'XMLHttpRequest'
    },
    body: obtenerFiltrosReportesGlobales().toString()
  })
    .then(function (response) {
      if (!response.ok) {
        throw new Error('HTTP ' + response.status);
      }
      return response.json();
    })
    .then(function (data) {
      if (!data || !data.ok) {
        throw new Error((data && data.error) || 'Error al cargar lotes descuadrados');
      }
      pintarLotesPesoDescuadrados(data.lotes || []);
    })
    .catch(function (error) {
      console.error('Error lotes peso descuadrado:', error);
      const vacio = document.getElementById('rg-lotes-descuadrados-vacio');
      if (vacio) {
        vacio.textContent = 'No se pudieron cargar los lotes descuadrados.';
      }
      setEstadoLotesDescuadrados('vacio');
    });
};

window.cargarEstadisticasReportesGlobales = function () {
  if (!document.getElementById('totales_reportes_globales')) {
    return;
  }

  setLoadingStatsReportesGlobales(true);

  if (typeof window.cargarLotesPesoDescuadradosReportesGlobales === 'function') {
    window.cargarLotesPesoDescuadradosReportesGlobales();
  }

  fetch('parts/reportes_globales/unique/load_stats.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded',
      'X-Requested-With': 'XMLHttpRequest'
    },
    body: obtenerFiltrosReportesGlobales().toString()
  })
    .then(function (response) {
      if (!response.ok) {
        throw new Error('HTTP ' + response.status);
      }
      return response.json();
    })
    .then(function (data) {
      setLoadingStatsReportesGlobales(false);
      if (!data || !data.success) {
        throw new Error((data && data.error) || 'Error al cargar totales');
      }
      pintarTotalesReportesGlobales(data.totales || {});
    })
    .catch(function (error) {
      console.error('Error totales reportes globales:', error);
      setLoadingStatsReportesGlobales(false);
      marcarErrorTotalesReportesGlobales();
    });
};

document.addEventListener('DOMContentLoaded', function () {
  // La primera carga se dispara con xhr.dt de la DataTable; esto cubre si aún no hay tabla.
  if (!window.dt_reportes_globales) {
    window.cargarEstadisticasReportesGlobales();
  }
});
