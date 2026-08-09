/**
 * Script universal para actualizar el título según los filtros aplicados
 * Uso: Incluir después de cargar los scripts del módulo
 */

window.obtenerFechaLocalISO = function (fecha) {
  const d = fecha ? new Date(fecha) : new Date();
  if (isNaN(d.getTime())) {
    return '';
  }
  return [
    d.getFullYear(),
    String(d.getMonth() + 1).padStart(2, '0'),
    String(d.getDate()).padStart(2, '0')
  ].join('-');
};

/**
 * Devuelve el título con filtros (sin modificar el DOM).
 */
window.obtenerTituloExportacionFiltros = function() {
  const tituloElement = document.getElementById('titulo-listados');
  if (!window.tituloBase) {
    window.tituloBase = tituloElement ? tituloElement.textContent.trim() : 'Listado';
  }

  let titulo = window.tituloBase;

  const periodo = obtenerTextoPeriodo();
  if (periodo) {
    titulo += ' - ' + periodo;
  }

  const sucursal = obtenerTextoSucursal();
  titulo += ' - ' + sucursal;

  const grupo = obtenerTextoGrupo();
  titulo += ' - ' + grupo;

  return titulo;
};

/**
 * Función global para actualizar el título con información de filtros
 */
window.actualizarTituloFiltros = function() {
  const tituloElement = document.getElementById('titulo-listados');
  const titulo = window.obtenerTituloExportacionFiltros();
  if (tituloElement) {
    tituloElement.textContent = titulo;
  }
  return titulo;
};

/**
 * Obtener el texto del periodo según filtros activos
 */
function obtenerTextoPeriodo() {
  const filtroDesde = document.getElementById('filtro_fecha_desde');
  const filtroHasta = document.getElementById('filtro_fecha_hasta');
  const periodo = window.filtro_periodo_activo || 'dia';

  if (periodo === 'hoy' || periodo === 'dia') {
    const etiqueta = 'Hoy';
    const fechaRef = (filtroDesde && filtroDesde.value)
      ? filtroDesde.value
      : (filtroHasta && filtroHasta.value)
        ? filtroHasta.value
        : window.obtenerFechaLocalISO();
    return etiqueta + ' ' + formatearFecha(fechaRef);
  }
  if (periodo === 'mes') {
    return obtenerMesActual();
  }
  if (periodo === 'todos') {
    return 'Todos';
  }
  
  // Si hay fechas personalizadas
  if (filtroDesde && filtroHasta && filtroDesde.value && filtroHasta.value) {
    const fecha1 = formatearFecha(filtroDesde.value);
    const fecha2 = formatearFecha(filtroHasta.value);
    
    if (fecha1 === fecha2) {
      return fecha1;
    }
    return 'Desde ' + fecha1 + ' hasta ' + fecha2;
  }
  
  // Si solo hay fecha desde
  if (filtroDesde && filtroDesde.value) {
    return 'Desde ' + formatearFecha(filtroDesde.value);
  }
  
  // Si solo hay fecha hasta
  if (filtroHasta && filtroHasta.value) {
    return 'Hasta ' + formatearFecha(filtroHasta.value);
  }
  
  if (periodo === 'personalizado') {
    return 'Periodo personalizado';
  }

  return 'Hoy ' + formatearFecha(window.obtenerFechaLocalISO());
}

/**
 * Obtener el texto de la sucursal según filtro activo
 */
function obtenerTextoSucursal() {
  const sucursalFilter = document.getElementById('filtro_sucursal');
  
  if (sucursalFilter && sucursalFilter.value) {
    const selectedOption = sucursalFilter.options[sucursalFilter.selectedIndex];
    return selectedOption ? selectedOption.textContent.trim() : sucursalFilter.value;
  }
  
  return 'Todas las sucursales';
}

function obtenerTextoGrupo() {
  const grupoFilter = document.getElementById('filtro_grupo');
  
  if (grupoFilter && grupoFilter.value) {
    return grupoFilter.value;
  }
  
  return 'Todas los grupos';
}

/**
 * Formatear fecha de YYYY-MM-DD a DD/MM/YYYY
 */
function formatearFecha(fecha) {
  if (!fecha) return '';
  const partes = fecha.split('-');
  if (partes.length === 3) {
    return partes[2] + '/' + partes[1] + '/' + partes[0];
  }
  return fecha;
}

/**
 * Obtener el mes actual en formato texto
 */
function obtenerMesActual() {
  const meses = [
    'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
    'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'
  ];
  const fecha = new Date();
  return meses[fecha.getMonth()] + ' ' + fecha.getFullYear();
}

// Actualizar título al cargar la página
document.addEventListener('DOMContentLoaded', function() {
  setTimeout(function() {
    if (typeof window.actualizarTituloFiltros === 'function') {
      window.actualizarTituloFiltros();
    }
  }, 600);
});

