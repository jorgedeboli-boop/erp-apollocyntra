/**
 * Script universal de Flatpickr para filtros de fecha en DataTables
 * Uso: Solo incluir este script después de cargar el DataTable
 */

document.addEventListener('DOMContentLoaded', function() {
  // Soportar múltiples rangos (por ejemplo: artículos, artículos vendidos, etc.)
  const configuraciones = [
    { rangeId: 'rangeFechas', desdeId: 'filtro_fecha_desde', hastaId: 'filtro_fecha_hasta' },
    { rangeId: 'rangeFechasVendidos', desdeId: 'filtro_fecha_desde_vendidos', hastaId: 'filtro_fecha_hasta_vendidos' },
    { rangeId: 'rangeFechasFs', desdeId: 'filtro_fecha_desde_fs', hastaId: 'filtro_fecha_hasta_fs' },
    { rangeId: 'rangeFechasRen', desdeId: 'filtro_fecha_desde_ren', hastaId: 'filtro_fecha_hasta_ren' }
  ];

  const configLocale = {
    firstDayOfWeek: 1,
    weekdays: {
      shorthand: ['Do', 'Lu', 'Ma', 'Mi', 'Ju', 'Vi', 'Sa'],
      longhand: ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado']
    },
    months: {
      shorthand: ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'],
      longhand: ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre']
    },
    rangeSeparator: ' hasta ',
    weekAbbreviation: 'Sem',
    scrollTitle: 'Desplazar para aumentar',
    toggleTitle: 'Hacer clic para cambiar'
  };

  let inicializoAlMenosUno = false;

  configuraciones.forEach(cfg => {
    const rangeInput = document.getElementById(cfg.rangeId);
    if (!rangeInput) return;

    inicializoAlMenosUno = true;

    // Limpiar inputs al cargar
    rangeInput.value = '';
    const inputDesde = document.getElementById(cfg.desdeId);
    const inputHasta = document.getElementById(cfg.hastaId);
    if (inputDesde) inputDesde.value = '';
    if (inputHasta) inputHasta.value = '';

    flatpickr('#' + cfg.rangeId, {
      mode: 'range',
      dateFormat: 'd-m-Y',
      locale: configLocale,
      onChange: function(selectedDates, dateStr, instance) {
        if (selectedDates.length === 2) {
          const elDesde = document.getElementById(cfg.desdeId);
          const elHasta = document.getElementById(cfg.hastaId);
          if (elDesde) elDesde.value = instance.formatDate(selectedDates[0], 'Y-m-d');
          if (elHasta) elHasta.value = instance.formatDate(selectedDates[1], 'Y-m-d');

          const fecha1 = instance.formatDate(selectedDates[0], 'd-m-Y');
          const fecha2 = instance.formatDate(selectedDates[1], 'd-m-Y');
          instance.input.value = fecha1 + ' > ' + fecha2;

          if (typeof window.actualizarTituloVentas === 'function') {
            window.actualizarTituloVentas();
          }
          if (typeof window.actualizarTituloVentasPlazos === 'function') {
            window.actualizarTituloVentasPlazos();
          }

          // Verificar si existen botones de tipo de fecha (lotes, artículos, traspasos, reportes)
          const tieneBotonesTipoFecha = document.getElementById('filtro_por_fecha_compra') ||
                                         document.getElementById('filtro_por_fecha_vencimiento') ||
                                         document.getElementById('filtro_por_fecha_apunte') ||
                                         document.getElementById('filtro_por_fecha_traspaso') ||
                                         document.getElementById('filtro_por_fecha_informe');

          // Si NO hay botones de tipo de fecha, recargar automáticamente (movimientos)
          if (!tieneBotonesTipoFecha) {
            window.filtro_periodo_activo = 'personalizado';
            document.querySelectorAll('#filtro_hoy, #filtro_mes, #filtro_todos, #filtro_dia, #filtro_por_fecha_compra, #filtro_por_fecha_vencimiento, #filtro_por_fecha_apunte').forEach(btn => {
              btn.classList.remove('active');
            });

            recargarDataTable();
            recargarEstadisticas();

            if (typeof window.actualizarTituloFiltros === 'function') {
              window.actualizarTituloFiltros();
            }
          }
        }
      }
    });
  });

  if (!inicializoAlMenosUno) return;
  
  /**
   * Función para buscar y recargar el DataTable activo
   */
  function recargarDataTable() {
    // Lista de nombres comunes de variables DataTable
    const nombresPosibles = [
      'dt_auditorias',
      'dt_movimiento',
      'dt_gastos',
      'dt_lote',
      'dt_articulo',
      'dt_stock',
      'dt_cliente',
      'dt_usuario',
      'dt_sucursal',
      'dt_user',
      'dt_facturas',
      'dt_facturas_simplificadas',
      'dt_semanas_config'
    ];
    
    // Buscar en window
    for (const nombre of nombresPosibles) {
      if (typeof window[nombre] !== 'undefined' && window[nombre] && typeof window[nombre].ajax === 'object') {
        window[nombre].ajax.reload();
        return true;
      }
    }
    
    // Si no se encontró por nombre, buscar cualquier propiedad que sea DataTable
    for (const key in window) {
      if (window.hasOwnProperty(key) && window[key] && typeof window[key].ajax === 'object' && typeof window[key].ajax.reload === 'function') {
        window[key].ajax.reload();
        return true;
      }
    }
    
    return false;
  }
  
  /**
   * Función para recargar estadísticas si existe
   */
  function recargarEstadisticas() {
    // Lista de nombres comunes de funciones de recarga
    const funcionesPosibles = [
      'cargarEstadisticas',
      'recargarEstadisticasAuditorias',
      'recargarEstadisticasMovimientos',
      'recargarEstadisticasLotes',
      'recargarEstadisticasArticulos',
      'recargarEstadisticasStock',
      'recargarEstadisticasVentas',
      'recargarEstadisticas'
    ];
    
    for (const nombreFuncion of funcionesPosibles) {
      if (typeof window[nombreFuncion] === 'function') {
        window[nombreFuncion]();
        return true;
      }
    }
    
    return false;
  }
});

