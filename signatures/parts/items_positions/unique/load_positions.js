/**
 * Script para cargar y gestionar las posiciones de los items del menú
 * con funcionalidad de drag & drop
 */

document.addEventListener('DOMContentLoaded', function() {
    cargarPosiciones();
});

/**
 * Función para cargar las posiciones de los items del menú
 */
function cargarPosiciones() {
    // Mostrar indicador de carga
    mostrarCarga();
    
    // Realizar petición AJAX
    fetch('parts/items_positions/unique/load_positions.php', {
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
            // Ocultar indicador de carga
            ocultarCarga();
            
            // Renderizar los items
            renderizarItems(data.data);
        } else {
            throw new Error(data.error || 'Error desconocido');
        }
    })
    .catch(error => {
        console.error('Error al cargar posiciones:', error);
        ocultarCarga();
        mostrarError('Error al cargar las posiciones: ' + error.message);
    });
}

/**
 * Función para renderizar los items en el HTML
 */
function renderizarItems(items) {
    const container = document.getElementById('sortable-items');
    const noItemsMessage = document.getElementById('no-items-message');
    
    if (!container) {
        console.error('No se encontró el contenedor de items');
        return;
    }
    
    // Limpiar contenedor
    container.innerHTML = '';
    
    if (items.length === 0) {
        container.style.display = 'none';
        noItemsMessage.style.display = 'block';
        return;
    }
    
    // Mostrar contenedor y ocultar mensaje de no items
    container.style.display = 'block';
    noItemsMessage.style.display = 'none';
    
    items.forEach((item) => {
        const itemElement = crearItemOrdenable(item, item.position_menu);
        container.appendChild(itemElement);
    });
    
    setTimeout(() => {
        inicializarSortable();
    }, 100);
}

/**
 * Función para crear un item ordenable
 */
function crearItemOrdenable(item, posicion) {
    const col = document.createElement('div');
    col.className = 'col-12 mb-2';
    col.setAttribute('data-id', item.id);
    col.setAttribute('data-position', item.position_menu);
    
    // Determinar el estado del item
    const estadoTexto = item.state_item === 'true' ? 'Activo' : 'Inactivo';
    const estadoClase = item.state_item === 'true' ? 'success' : 'secondary';
    const estadoIcono = item.state_item === 'true' ? 'ri-check-circle-line' : 'ri-close-circle-line';
    
    // Determinar el tipo de item
    const tipoClase = getTipoClase(item.typ_item);
    const tipoIcono = getTipoIcono(item.typ_item);
    
    col.innerHTML = `
        <div class="card sortable-item" draggable="true">
            <div class="card-body py-3">
                <div class="row align-items-center">
                    <!-- Columna izquierda: Posición y tipo -->
                    <div class="col-auto">
                        <div class="d-flex align-items-center">
                            <div class="badge bg-primary me-2">#${posicion}</div>
                            <div class="badge bg-label-${tipoClase}">
                                <i class="${tipoIcono} me-1"></i>
                                ${item.typ_item}
                            </div>
                        </div>
                    </div>
                    
                    <!-- Columna central: Información del item -->
                    <div class="col">
                        <div class="d-flex align-items-center">
                            <div class="me-3">
                                <h6 class="mb-0 text-capitalize">${item.itemnameText}</h6>
                                ${item.itemName ? `<small class="text-muted">${item.itemName}</small>` : ''}
                            </div>
                            <div class="me-3">
                                ${item.url_item ? `<small class="text-muted d-block">${item.url_item}</small>` : ''}
                                ${item.icon_menu ? `<small class="text-muted d-block">${item.icon_menu}</small>` : ''}
                            </div>
                        </div>
                    </div>
                    
                    <!-- Columna derecha: Estados y handle -->
                    <div class="col-auto">
                        <div class="d-flex align-items-center">
                            <div class="me-2">
                                <div class="badge bg-label-${estadoClase} me-1">
                                    <i class="${estadoIcono} me-1"></i>
                                    ${estadoTexto}
                                </div>
                                <div class="badge bg-label-success">
                                    <i class="ri ri-menu-line me-1"></i>
                                    En Menú
                                </div>
                            </div>
                            <div class="drag-handle">
                                <i class="ri ri-drag-drop-line text-muted"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    return col;
}

/**
 * Función para inicializar SortableJS
 */
function inicializarSortable() {
    const container = document.getElementById('sortable-items');
    
    if (!container) {
        console.error('No se encontró el contenedor para SortableJS');
        return;
    }
    
    if (window.sortableInstance) {
        window.sortableInstance.destroy();
    }
    
    if (typeof Sortable === 'undefined') {
        return;
    }
    
    window.sortableInstance = new Sortable(container, {
        animation: 150,
        ghostClass: 'sortable-ghost',
        chosenClass: 'sortable-chosen',
        dragClass: 'sortable-drag',
        handle: '.drag-handle',
        forceFallback: true,
        fallbackClass: 'sortable-fallback',
        onStart: function(evt) {
            evt.item.classList.add('sortable-chosen');
        },
        onEnd: function(evt) {
            evt.item.classList.remove('sortable-chosen');
            actualizarPosiciones();
        },
        onMove: function(evt) {
            return true;
        }
    });
}

/**
 * Función para actualizar las posiciones después del drag & drop
 */
function actualizarPosiciones() {
    const container = document.getElementById('sortable-items');
    const items = container.querySelectorAll('.col-12');
    
    const posiciones = [];
    
    items.forEach((item, index) => {
        const id = item.getAttribute('data-id');
        const nuevaPosicion = index + 1;
        
        item.setAttribute('data-position', nuevaPosicion);
        
        posiciones.push({
            id: id,
            position: nuevaPosicion
        });
    });
    

    fetch('parts/items_positions/unique/update_positions.php', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            posiciones: posiciones
        })
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            mostrarExito('Posiciones actualizadas correctamente');
            actualizarNumerosPosicion();
            actualizarMenu();
        } else {
            throw new Error(data.error || 'Error desconocido');
        }
    })
    .catch(error => {
        mostrarError('Error al actualizar las posiciones: ' + error.message);
        cargarPosiciones();
    });
}

/**
 * Función para actualizar los números de posición en la interfaz
 */
function actualizarNumerosPosicion() {
    const container = document.getElementById('sortable-items');
    const items = container.querySelectorAll('.col-12');
    
    items.forEach((item, index) => {
        const badge = item.querySelector('.badge.bg-primary');
        if (badge) {
            const position = item.getAttribute('data-position');
            badge.textContent = `#${position}`;
        }
    });
}

/**
 * Función para obtener la clase CSS del tipo de item
 */
function getTipoClase(tipo) {
    const tipos = {
        'unique': 'primary',
        'main': 'info',
        'listar': 'warning',
        'editar': 'success',
        'crear': 'success',
        'menu': 'success',
        'crud': 'success',
        'acces_special': 'success',
        'blank_page': 'secondary'
    };
    return tipos[tipo] || 'secondary';
}

/**
 * Función para obtener el icono del tipo de item
 */
function getTipoIcono(tipo) {
    const iconos = {
        'unique': 'ri-file-text-line',
        'main': 'ri-home-line',
        'listar': 'ri-list-check',
        'editar': 'ri-edit-line',
        'crear': 'ri-add-circle-fill',
        'menu': 'ri-menu-line',
        'crud': 'ri-edit-line',
        'acces_special': 'ri-edit-line',
        'blank_page': 'ri-home-line'
    };
    return iconos[tipo] || 'ri-file-line';
}

/**
 * Función para mostrar indicador de carga
 */
function mostrarCarga() {
    const loading = document.getElementById('loading-positions');
    const container = document.getElementById('sortable-items');
    const noItems = document.getElementById('no-items-message');
    
    if (loading) loading.style.display = 'block';
    if (container) container.style.display = 'none';
    if (noItems) noItems.style.display = 'none';
}

/**
 * Función para ocultar indicador de carga
 */
function ocultarCarga() {
    const loading = document.getElementById('loading-positions');
    if (loading) loading.style.display = 'none';
}

/**
 * Función para mostrar errores
 */
function mostrarError(mensaje) {
    const container = document.getElementById('sortable-items');
    if (container) {
        container.innerHTML = `
            <div class="col-12">
                <div class="alert alert-danger text-center">
                    <i class="ri ri-error-warning-line me-2"></i>
                    ${mensaje}
                    <button class="btn btn-sm btn-outline-danger ms-3" onclick="cargarPosiciones()">
                        <i class="ri ri-refresh-line me-1"></i>
                        Reintentar
                    </button>
                </div>
            </div>
        `;
        container.style.display = 'block';
    }
}

/**
 * Función para mostrar mensajes de éxito
 */
function mostrarExito(mensaje) {
    // Crear toast de éxito si existe Bootstrap Toast
    if (typeof bootstrap !== 'undefined' && bootstrap.Toast) {
        const toastContainer = document.getElementById('toast-container') || crearToastContainer();
        const toast = crearToast('success', mensaje);
        toastContainer.appendChild(toast);
        
        const bsToast = new bootstrap.Toast(toast);
        bsToast.show();
    } else {
        // Fallback a alert si no hay Bootstrap
        alert('Éxito: ' + mensaje);
    }
}

/**
 * Función para crear el contenedor de toasts
 */
function crearToastContainer() {
    const container = document.createElement('div');
    container.id = 'toast-container';
    container.className = 'toast-container position-fixed top-0 end-0 p-3';
    container.style.zIndex = '9999';
    document.body.appendChild(container);
    return container;
}

/**
 * Función para crear un toast
 */
function crearToast(tipo, mensaje) {
    const toast = document.createElement('div');
    toast.className = `toast align-items-center text-white bg-${tipo === 'success' ? 'success' : 'danger'} border-0`;
    toast.setAttribute('role', 'alert');
    toast.setAttribute('aria-live', 'assertive');
    toast.setAttribute('aria-atomic', 'true');
    
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">
                <i class="ri ri-${tipo === 'success' ? 'checkbox-circle-fill' : 'error-warning-line'} me-2"></i>
                ${mensaje}
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    `;
    
    return toast;
}

/**
 * Función para actualizar el menú después de cambiar las posiciones
 */
function actualizarMenu() {
    fetch('parts/items_positions/unique/update_menu.php', {
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
            const menuInner = document.querySelector('.menu-inner');
            if (menuInner) {
                const menuItems = menuInner.querySelectorAll('.menu-item');
                menuItems.forEach(item => {
                    if (item.querySelector('.menu-link')) {
                        item.remove();
                    }
                });
                
                menuInner.insertAdjacentHTML('beforeend', data.menu_html);
            }
        } else {
            throw new Error(data.error || 'Error al actualizar el menú');
        }
    })
    .catch(error => {
        // Error silencioso - no afecta la funcionalidad principal
    });
}
