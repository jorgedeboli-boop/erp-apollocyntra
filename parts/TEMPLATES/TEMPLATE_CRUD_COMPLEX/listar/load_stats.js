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
    const filtros = {
        filtro_empresa: document.getElementById('filtro_empresa')?.value || '',
        filtro_sucursal: document.getElementById('filtro_sucursal')?.value || '',
        filtro_proveedor: document.getElementById('filtro_proveedor')?.value || '',
        filtro_estado: document.getElementById('filtro_estado')?.value || '',
        filtro_tipo_gasto: document.getElementById('filtro_tipo_gasto')?.value || '',
        filtro_forma_pago: document.getElementById('filtro_forma_pago')?.value || '',
        filtro_fecha_desde: document.getElementById('filtro_fecha_desde')?.value || '',
        filtro_fecha_hasta: document.getElementById('filtro_fecha_hasta')?.value || ''
    };
    
    return filtros;
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
