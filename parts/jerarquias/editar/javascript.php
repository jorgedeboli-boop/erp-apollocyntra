<!-- JavaScript para editar jerarquías -->
<script>
// Variables globales para el buscador
let todosLosItems = [];
let permisosActualesGlobal = [];
let filtroEstadoActual = 'todos';

document.addEventListener('DOMContentLoaded', function() {
    const itemsContainer = document.getElementById('items-container');
    
    // Cargar items al iniciar la página
    cargarItems();
    
    // Inicializar buscador y filtros de estado
    inicializarBuscador();
    inicializarFiltrosEstado();
});

/**
 * Función para cargar todos los items del sistema
 */
function cargarItems() {
    const itemsContainer = document.getElementById('items-container');
    const idJerarquia = document.querySelector('input[name="id_jerarquia"]').value;
    
    // Mostrar indicador de carga
    itemsContainer.innerHTML = `
        <div class="text-center py-4">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Cargando...</span>
            </div>
            <p class="mt-2">Cargando items del sistema...</p>
        </div>
    `;
    
    // Realizar petición AJAX para obtener items y permisos actuales
    fetch(`parts/jerarquias/editar/get_items_permisos.php?id_jerarquia=${idJerarquia}`, {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            // Guardar datos en variables globales para el buscador
            todosLosItems = data.items;
            permisosActualesGlobal = data.permisos_actuales;
            
            aplicarFiltros();
        } else {
            throw new Error(data.error || 'Error desconocido');
        }
    })
    .catch(error => {
        console.error('Error al cargar items:', error);
        itemsContainer.innerHTML = `
            <div class="alert alert-danger">
                <i class="ri ri-error-warning-line me-2"></i>
                Error al cargar los items: ${error.message}
                <button class="btn btn-sm btn-outline-danger ms-3" onclick="cargarItems()">
                    <i class="ri ri-refresh-line me-1"></i>
                    Reintentar
                </button>
            </div>
        `;
    });
}

/**
 * Escapa HTML para textos dinámicos en la tabla
 */
function escapeHtmlJerarquia(texto) {
    if (texto === null || texto === undefined) {
        return '';
    }
    return String(texto)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

/**
 * Obtiene el título legible de un item
 */
function obtenerTituloItemJerarquia(item) {
    if (item.itemnameText && String(item.itemnameText).trim() !== '') {
        return item.itemnameText;
    }
    if (item.itemName) {
        return item.itemName.charAt(0).toUpperCase() + item.itemName.slice(1);
    }
    return 'Sin título';
}

/**
 * Agrupa items: listar + hijos main/editar/crear en una fila; resto individual
 */
function agruparItemsJerarquia(items) {
    const itemsPorId = {};
    items.forEach(function (i) {
        itemsPorId[parseInt(i.id_type_Item, 10)] = i;
    });

    const hijosCrudPorListar = {};
    const idsHijoCrud = new Set();

    items.forEach(function (item) {
        const typ = (item.typ_item || '').toLowerCase();
        const fhaterId = parseInt(item.fhater_item, 10) || 0;
        if (fhaterId > 0 && ['main', 'editar', 'crear'].indexOf(typ) !== -1) {
            const padre = itemsPorId[fhaterId];
            if (padre && (padre.typ_item || '').toLowerCase() === 'listar') {
                if (!hijosCrudPorListar[fhaterId]) {
                    hijosCrudPorListar[fhaterId] = {};
                }
                hijosCrudPorListar[fhaterId][typ] = item;
                idsHijoCrud.add(parseInt(item.id_type_Item, 10));
            }
        }
    });

    const filas = [];
    items.forEach(function (item) {
        const itemId = parseInt(item.id_type_Item, 10);
        const typ = (item.typ_item || '').toLowerCase();

        if (idsHijoCrud.has(itemId)) {
            return;
        }

        if (typ === 'listar') {
            filas.push({
                tipo: 'grupo',
                listar: item,
                hijos: hijosCrudPorListar[itemId] || {}
            });
            return;
        }

        const fhaterId = parseInt(item.fhater_item, 10) || 0;
        if (fhaterId === 0) {
            filas.push({ tipo: 'individual', item: item });
        }
    });

    return filas;
}

/**
 * Items que componen una fila (grupo o individual)
 */
function obtenerItemsDeFila(fila) {
    if (fila.tipo === 'grupo') {
        const items = [fila.listar];
        ['main', 'editar', 'crear'].forEach(function (typ) {
            if (fila.hijos[typ]) {
                items.push(fila.hijos[typ]);
            }
        });
        return items;
    }
    return [fila.item];
}

/**
 * Comprueba si un item coincide con el término de búsqueda
 */
function itemCoincideBusqueda(item, searchTerm) {
    if (!searchTerm) {
        return true;
    }

    const itemName = (item.itemName || '').toLowerCase();
    const itemnameText = (item.itemnameText || '').toLowerCase();
    const fhaterItem = (item.fhater_item || '').toString().toLowerCase();
    const tipo = (item.typ_item || '').toLowerCase();
    const url = (item.url_item || '').toLowerCase();
    const id = (item.id_type_Item || '').toString().toLowerCase();

    return itemName.indexOf(searchTerm) !== -1 ||
           itemnameText.indexOf(searchTerm) !== -1 ||
           fhaterItem.indexOf(searchTerm) !== -1 ||
           tipo.indexOf(searchTerm) !== -1 ||
           url.indexOf(searchTerm) !== -1 ||
           id.indexOf(searchTerm) !== -1;
}

/**
 * Renderiza un checkbox de permiso para la tabla
 */
function renderCheckboxCelda(item, label) {
    if (!item) {
        return '';
    }

    const itemId = parseInt(item.id_type_Item, 10);
    const checked = itemEstaActivo(itemId) ? 'checked' : '';

    return `
        <div class="form-check mb-0 mt-1 me-0 ms-lg-12 fs-6">
            <input class="form-check-input permiso-item-checkbox" type="checkbox"
                   id="permiso_${itemId}"
                   data-item-id="${itemId}"
                   ${checked}
                   onchange="onPermisoCheckboxChange(this)">
            <label class="form-check-label" for="permiso_${itemId}">${escapeHtmlJerarquia(label)}</label>
        </div>
    `;
}

/**
 * Función para renderizar los items con sus permisos en tabla
 */
function renderizarItems(filas) {
    const itemsContainer = document.getElementById('items-container');

    if (filas.length === 0) {
        itemsContainer.innerHTML = `
            <div class="alert alert-info text-center mb-0">
                <i class="ri ri-information-line me-2"></i>
                No hay items configurados en el sistema
            </div>
        `;
        return;
    }

    let filasItems = '';

    filas.forEach(function (fila) {
        if (fila.tipo === 'grupo') {
            const listar = fila.listar;
            const hijos = fila.hijos;
            const listarId = parseInt(listar.id_type_Item, 10);
            const tituloItem = escapeHtmlJerarquia(obtenerTituloItemJerarquia(listar));
            const detalleItem = [
                'URL: ' + (listar.url_item || 'N/A'),
                'ID listar: ' + listarId
            ].map(escapeHtmlJerarquia).join(' · ');

            filasItems += `
                <tr data-grupo-listar="${listarId}">
                    <td class="text-nowrap fw-medium fs-6">
                        ${tituloItem}
                        <small class="text-muted d-block mt-1 fw-normal fs-tiny">${detalleItem}</small>
                    </td>
                    <td>
                        <div class="d-flex justify-content-end flex-wrap fs-6">
                            ${renderCheckboxCelda(listar, 'Listar')}
                            ${renderCheckboxCelda(hijos.main, 'Ficha')}
                            ${renderCheckboxCelda(hijos.editar, 'Editar')}
                            ${renderCheckboxCelda(hijos.crear, 'Crear')}
                        </div>
                    </td>
                </tr>
            `;
            return;
        }

        const item = fila.item;
        const itemId = parseInt(item.id_type_Item, 10);
        const tituloItem = escapeHtmlJerarquia(obtenerTituloItemJerarquia(item));
        const detalleItem = [
            'Tipo: ' + (item.typ_item || 'N/A'),
            'URL: ' + (item.url_item || 'N/A'),
            'ID: ' + itemId
        ].map(escapeHtmlJerarquia).join(' · ');

        filasItems += `
            <tr data-item-id="${itemId}">
                <td class="text-nowrap fw-medium fs-6">
                    ${tituloItem}
                    <small class="text-muted d-block mt-1 fw-normal">${detalleItem}</small>
                </td>
                <td>
                    <div class="d-flex justify-content-end flex-wrap fs-tiny opacity-75">
                        ${renderCheckboxCelda(item, 'Activo')}
                    </div>
                </td>
            </tr>
        `;
    });

    itemsContainer.innerHTML = `
        <div class="table-responsive">
            <table class="table table-flush-spacing">
                <tbody>
                    <tr>
                        <td class="text-nowrap fw-medium fs-5">
                            Acceso total
                            <i class="icon-base ri ri-information-line icon-sm"
                               data-bs-toggle="tooltip"
                               data-bs-placement="top"
                               title="Activa o desactiva todos los permisos visibles"></i>
                        </td>
                        <td>
                            <div class="d-flex justify-content-end">
                                <div class="form-check mb-0 mt-1 fs-5">
                                    <input class="form-check-input" type="checkbox" id="selectAllPermisos">
                                    <label class="form-check-label" for="selectAllPermisos">Seleccionar todos</label>
                                </div>
                            </div>
                        </td>
                    </tr>
                    ${filasItems}
                </tbody>
            </table>
        </div>
    `;

    bindSelectAllPermisos();
    actualizarEstadoSelectAll();
    initTooltipsJerarquia();
}

/**
 * Inicializa tooltips de Bootstrap en la tabla
 */
function initTooltipsJerarquia() {
    if (typeof bootstrap === 'undefined' || !bootstrap.Tooltip) {
        return;
    }
    document.querySelectorAll('#items-container [data-bs-toggle="tooltip"]').forEach(function (el) {
        new bootstrap.Tooltip(el);
    });
}

/**
 * Sincroniza el checkbox "Seleccionar todos"
 */
function actualizarEstadoSelectAll() {
    const selectAll = document.getElementById('selectAllPermisos');
    const checkboxes = document.querySelectorAll('.permiso-item-checkbox');
    if (!selectAll || !checkboxes.length) {
        return;
    }

    const total = checkboxes.length;
    const activos = Array.from(checkboxes).filter(function (cb) { return cb.checked; }).length;

    selectAll.checked = activos === total;
    selectAll.indeterminate = activos > 0 && activos < total;
}

/**
 * Evento al cambiar un permiso individual (checkbox)
 */
function onPermisoCheckboxChange(checkbox) {
    const itemId = checkbox.getAttribute('data-item-id');
    const estado = checkbox.checked ? 'activo' : 'no_activo';
    actualizarPermiso(itemId, estado, checkbox);
}

/**
 * Checkbox "Seleccionar todos"
 */
function bindSelectAllPermisos() {
    const selectAll = document.getElementById('selectAllPermisos');
    if (!selectAll) {
        return;
    }

    selectAll.addEventListener('change', function () {
        const marcarActivo = selectAll.checked;
        const checkboxes = document.querySelectorAll('.permiso-item-checkbox');

        checkboxes.forEach(function (checkbox) {
            if (checkbox.checked === marcarActivo || checkbox.disabled) {
                return;
            }
            checkbox.checked = marcarActivo;
            actualizarPermiso(checkbox.getAttribute('data-item-id'), marcarActivo ? 'activo' : 'no_activo', checkbox);
        });
    });
}

/**
 * Función para actualizar un permiso individual
 */
function actualizarPermiso(itemId, estado, checkboxElement) {
    const idJerarquia = document.querySelector('input[name="id_jerarquia"]').value;
    const checkbox = checkboxElement || document.getElementById('permiso_' + itemId);

    const formData = new FormData();
    formData.append('id_jerarquia', idJerarquia);
    formData.append('item_id', itemId);
    formData.append('estado', estado);

    if (checkbox) {
        checkbox.disabled = true;
    }

    fetch('parts/jerarquias/editar/actualizar_permiso_individual.php', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('HTTP error! status: ' + response.status);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            const itemIdInt = parseInt(itemId, 10);
            if (estado === 'activo') {
                if (permisosActualesGlobal.indexOf(itemIdInt) === -1) {
                    permisosActualesGlobal.push(itemIdInt);
                }
            } else {
                permisosActualesGlobal = permisosActualesGlobal.filter(function (id) {
                    return parseInt(id, 10) !== itemIdInt;
                });
            }
            actualizarEstadoSelectAll();
            mostrarMensajeTemporal('success', 'Permiso ' + (estado === 'activo' ? 'activado' : 'desactivado') + ' correctamente');
        } else {
            throw new Error(data.error || 'Error desconocido');
        }
    })
    .catch(error => {
        console.error('Error al actualizar permiso:', error);
        mostrarMensajeTemporal('danger', 'Error al actualizar permiso: ' + error.message);

        if (checkbox) {
            checkbox.checked = estado !== 'activo';
        }
    })
    .finally(function () {
        if (checkbox) {
            checkbox.disabled = false;
        }
        actualizarEstadoSelectAll();
    });
}

/**
 * Función para mostrar mensajes temporales
 */
function mostrarMensajeTemporal(tipo, mensaje) {
    // Crear toast temporal
    const toast = document.createElement('div');
    toast.className = `toast align-items-center text-white bg-${tipo === 'success' ? 'success' : 'danger'} border-0 position-fixed`;
    toast.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    toast.setAttribute('role', 'alert');
    toast.setAttribute('aria-live', 'assertive');
    toast.setAttribute('aria-atomic', 'true');
    
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">
                <i class="ri ri-${tipo === 'success' ? 'check-circle' : 'error-warning'}-line me-2"></i>
                ${mensaje}
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    `;
    
    // Agregar al body
    document.body.appendChild(toast);
    
    // Mostrar toast
    if (typeof bootstrap !== 'undefined' && bootstrap.Toast) {
        const bsToast = new bootstrap.Toast(toast);
        bsToast.show();
    }
    
    // Auto-ocultar después de 3 segundos
    setTimeout(() => {
        if (toast.parentNode) {
            toast.remove();
        }
    }, 3000);
}

/**
 * Función para mostrar mensajes
 */
function mostrarMensaje(tipo, mensaje) {
    // Crear alerta
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${tipo} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        <i class="ri ri-${tipo === 'success' ? 'check-circle' : 'error-warning'}-line me-2"></i>
        ${mensaje}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;
    
    // Insertar al inicio del formulario
    const form = document.getElementById('formPermisosJerarquia');
    form.insertBefore(alertDiv, form.firstChild);
    
    // Auto-ocultar después de 5 segundos
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.remove();
        }
    }, 5000);
}

// ==========================================
// FUNCIONES DEL BUSCADOR
// ==========================================

/**
 * Función para inicializar el buscador
 */
function inicializarBuscador() {
    const searchInput = document.getElementById('searchItems');
    const clearButton = document.getElementById('clearSearch');
    
    if (!searchInput) return;
    
    // Evento de búsqueda en tiempo real
    searchInput.addEventListener('input', function() {
        const searchTerm = this.value.trim().toLowerCase();
        
        if (searchTerm.length > 0) {
            clearButton.style.display = 'block';
        } else {
            clearButton.style.display = 'none';
        }
        aplicarFiltros();
    });
    
    // Evento para limpiar búsqueda
    clearButton.addEventListener('click', function() {
        searchInput.value = '';
        clearButton.style.display = 'none';
        aplicarFiltros();
        searchInput.focus();
    });
    
    // Evento para búsqueda con Escape
    searchInput.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            this.value = '';
            clearButton.style.display = 'none';
            aplicarFiltros();
        }
    });
}

/**
 * Filtros Activas / No activas / Todos
 */
function inicializarFiltrosEstado() {
    const botones = document.querySelectorAll('[data-filtro-estado]');
    if (!botones.length) {
        return;
    }

    botones.forEach(function(boton) {
        boton.addEventListener('click', function() {
            filtroEstadoActual = this.getAttribute('data-filtro-estado') || 'todos';
            botones.forEach(function(btn) {
                btn.classList.remove('active');
            });
            this.classList.add('active');
            aplicarFiltros();
        });
    });
}

/**
 * Comprueba si un item tiene permiso activo
 */
function itemEstaActivo(itemId) {
    const id = parseInt(itemId, 10);
    for (let i = 0; i < permisosActualesGlobal.length; i++) {
        if (parseInt(permisosActualesGlobal[i], 10) === id) {
            return true;
        }
    }
    return false;
}

/**
 * Obtiene filas filtradas por búsqueda y estado
 */
function obtenerFilasFiltradas() {
    const searchInput = document.getElementById('searchItems');
    const searchTerm = searchInput ? searchInput.value.trim().toLowerCase() : '';
    const filas = agruparItemsJerarquia(todosLosItems);

    return filas.filter(function (fila) {
        const itemsFila = obtenerItemsDeFila(fila);

        const coincideBusqueda = searchTerm === '' || itemsFila.some(function (item) {
            return itemCoincideBusqueda(item, searchTerm);
        });

        if (!coincideBusqueda) {
            return false;
        }

        if (filtroEstadoActual === 'todos') {
            return true;
        }

        return itemsFila.some(function (item) {
            const activo = itemEstaActivo(parseInt(item.id_type_Item, 10));
            if (filtroEstadoActual === 'activas') {
                return activo;
            }
            if (filtroEstadoActual === 'no_activas') {
                return !activo;
            }
            return true;
        });
    });
}

/**
 * Obtiene items filtrados por búsqueda y estado (aplanado, compatibilidad)
 */
function obtenerItemsFiltrados() {
    const filas = obtenerFilasFiltradas();
    const items = [];
    filas.forEach(function (fila) {
        obtenerItemsDeFila(fila).forEach(function (item) {
            items.push(item);
        });
    });
    return items;
}

/**
 * Aplica búsqueda y filtro de estado al listado
 */
function aplicarFiltros() {
    const container = document.getElementById('items-container');

    if (!container || todosLosItems.length === 0) {
        return;
    }

    const searchInput = document.getElementById('searchItems');
    const searchTerm = searchInput ? searchInput.value.trim().toLowerCase() : '';
    const filasFiltradas = obtenerFilasFiltradas();

    if (filasFiltradas.length === 0) {
        let mensajeFiltro = '';
        if (filtroEstadoActual === 'activas') {
            mensajeFiltro = 'activas';
        } else if (filtroEstadoActual === 'no_activas') {
            mensajeFiltro = 'no activas';
        }

        let texto = 'No se encontraron items';
        if (searchTerm !== '' && mensajeFiltro !== '') {
            texto += ` ${mensajeFiltro} que coincidan con "<strong>${searchTerm}</strong>"`;
        } else if (searchTerm !== '') {
            texto += ` que coincidan con "<strong>${searchTerm}</strong>"`;
        } else if (mensajeFiltro !== '') {
            texto += ` ${mensajeFiltro}`;
        }

        container.innerHTML = `
            <div class="alert alert-info text-center mb-0">
                <i class="ri ri-search-line me-2"></i>
                ${texto}
            </div>
        `;
        return;
    }

    renderizarItems(filasFiltradas);
}

/**
 * Función para filtrar items según el término de búsqueda
 */
function filtrarItems(searchTerm) {
    aplicarFiltros();
}

/**
 * Función para mostrar todos los items
 */
function mostrarTodosLosItems() {
    aplicarFiltros();
}
</script>
