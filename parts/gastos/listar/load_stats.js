/**
 * Scripts para cargar estadísticas de gastos
 * Carga los totales en las tarjetas superiores
 */

document.addEventListener('DOMContentLoaded', function() {
    // Cargar estadísticas al iniciar la página
    cargarEstadisticas();
    
    // Recargar estadísticas cada 30 segundos (opcional)
    setInterval(cargarEstadisticas, 30000);
});

/**
 * Función principal para cargar todas las estadísticas
 */
function cargarEstadisticas() {
    // Cargar total de gastos
    cargarTotalGastos();
    
    // Cargar total en euros
    cargarTotalEuros();
    
    // Cargar media por gasto
    cargarMediaGasto();
    
    // Cargar gastos pendientes
    cargarGastosPendientes();
}

/**
 * Función para obtener los filtros actuales del DataTable
 */
function obtenerFiltrosActuales() {
    function valorFiltro(id) {
        const el = document.getElementById(id);
        if (!el) {
            return '';
        }
        if (typeof jQuery !== 'undefined' && jQuery(el).data('select2')) {
            return jQuery(el).val() || '';
        }
        return el.value || '';
    }

    return {
        filtro_empresa: valorFiltro('filtro_empresa'),
        filtro_sucursal: valorFiltro('filtro_sucursal'),
        filtro_proveedor: valorFiltro('filtro_proveedor'),
        filtro_estado: valorFiltro('filtro_estado'),
        filtro_tipo_gasto: valorFiltro('filtro_tipo_gasto'),
        filtro_forma_pago: valorFiltro('filtro_forma_pago'),
        filtro_fecha_desde: valorFiltro('filtro_fecha_desde'),
        filtro_fecha_hasta: valorFiltro('filtro_fecha_hasta')
    };
}

/**
 * Cargar total de gastos
 */
function cargarTotalGastos() {
    const filtros = obtenerFiltrosActuales();
    const formData = new URLSearchParams({
        tipo: 'total_gastos',
        ...filtros
    });
    
    fetch('parts/gastos/listar/load_stats.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const elemento = document.getElementById('total-gastos');
            if (elemento) {
                elemento.textContent = data.total;
            }
        } else {
            console.error('Error al cargar total gastos:', data.error);
        }
    })
    .catch(error => {
        console.error('Error en fetch total gastos:', error);
    });
}

/**
 * Cargar total en euros
 */
function cargarTotalEuros() {
    const filtros = obtenerFiltrosActuales();
    const formData = new URLSearchParams({
        tipo: 'total_euros',
        ...filtros
    });
    
    fetch('parts/gastos/listar/load_stats.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const elemento = document.getElementById('total-euros');
            if (elemento) {
                elemento.textContent = data.total + '€';
            }
        } else {
            console.error('Error al cargar total euros:', data.error);
        }
    })
    .catch(error => {
        console.error('Error en fetch total euros:', error);
    });
}

/**
 * Cargar media por gasto
 */
function cargarMediaGasto() {
    const filtros = obtenerFiltrosActuales();
    const formData = new URLSearchParams({
        tipo: 'media_gasto',
        ...filtros
    });
    
    fetch('parts/gastos/listar/load_stats.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const elemento = document.getElementById('media-gasto');
            if (elemento) {
                elemento.textContent = data.total + '€';
            }
        } else {
            console.error('Error al cargar media gasto:', data.error);
        }
    })
    .catch(error => {
        console.error('Error en fetch media gasto:', error);
    });
}

/**
 * Cargar gastos pendientes
 */
function cargarGastosPendientes() {
    const filtros = obtenerFiltrosActuales();
    const formData = new URLSearchParams({
        tipo: 'gastos_pendientes',
        ...filtros
    });
    
    fetch('parts/gastos/listar/load_stats.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const elemento = document.getElementById('gastos-pendientes');
            if (elemento) {
                elemento.textContent = data.total;
            }
        } else {
            console.error('Error al cargar gastos pendientes:', data.error);
        }
    })
    .catch(error => {
        console.error('Error en fetch gastos pendientes:', error);
    });
}
