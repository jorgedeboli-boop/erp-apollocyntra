/**
 * Script para cargar items desde la base de datos
 * y mostrarlos dinámicamente en la página
 */

document.addEventListener('DOMContentLoaded', function() {
    // Cargar items al iniciar la página
    cargarItems();
    
    // Cargar items padre para el select
    cargarItemsPadre();
    
    // Cargar items de tipo "menu" para el select de menú padre
    cargarItemsMenu();
    
    // Inicializar buscador
    inicializarBuscador();
    
    // Configurar evento SQL al cargar la página
    configurarEventoSQL();
});

/**
 * Función para cargar los items padre desde la base de datos
 */
function cargarItemsPadre() {
    // Realizar petición AJAX
    fetch('parts/items/unique/load_items_padre.php', {
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
            // Renderizar las opciones del select
            renderizarItemsPadre(data.data);
        } else {
            throw new Error(data.error || 'Error desconocido');
        }
    })
    .catch(error => {
        console.error('Error al cargar items padre:', error);
        // Mostrar error en el select
        const select = document.getElementById('fhater_item');
        if (select) {
            select.innerHTML = '<option value="0">Error al cargar items padre</option>';
        }
    });
}

/**
 * Función para renderizar las opciones del select de items padre
 */
function renderizarItemsPadre(itemsPadre) {
    const select = document.getElementById('fhater_item');
    
    if (!select) {
        console.error('No se encontró el select de items padre');
        return;
    }
    
    // Limpiar select
    select.innerHTML = '';
    
    // Agregar opciones
    itemsPadre.forEach(item => {
        const option = document.createElement('option');
        option.value = item.id_type_Item;
        option.textContent = item.itemName;
        select.appendChild(option);
    });
}

/**
 * Función para cargar los items de tipo "menu" desde la base de datos
 */
function cargarItemsMenu() {
    // Realizar petición AJAX
    fetch('parts/items/unique/load_items_menu.php', {
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
            // Renderizar las opciones del select
            renderizarItemsMenu(data.data);
        } else {
            throw new Error(data.error || 'Error desconocido');
        }
    })
    .catch(error => {
        console.error('Error al cargar items de menú:', error);
        // Mostrar error en el select
        const select = document.getElementById('fhater_menu');
        if (select) {
            select.innerHTML = '<option value="0">Error al cargar items de menú</option>';
        }
    });
}

/**
 * Función para renderizar las opciones del select de items de menú
 */
function renderizarItemsMenu(itemsMenu) {
    const select = document.getElementById('fhater_menu');
    
    if (!select) {
        console.error('No se encontró el select de items de menú');
        return;
    }
    
    // Limpiar select
    select.innerHTML = '<option value="0">Sin item padre de menú</option>';
    
    // Agregar opciones
    itemsMenu.forEach(item => {
        const option = document.createElement('option');
        option.value = item.id_type_Item;
        option.textContent = item.itemName;
        select.appendChild(option);
    });
}

/**
 * Función para cargar los items desde la base de datos
 */
function cargarItems() {
    // Mostrar indicador de carga
    mostrarCarga();
    
    // Realizar petición AJAX
    fetch('parts/items/unique/load_items.php', {
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
        console.error('Error al cargar items:', error);
        ocultarCarga();
        mostrarError('Error al cargar los items: ' + error.message);
    });
}

/**
 * Función para renderizar los items en el HTML
 */
function renderizarItems(items) {
    const container = document.getElementById('items-container');
    
    if (!container) {
        console.error('No se encontró el contenedor de items');
        return;
    }
    
    // Actualizar la lista de items para búsqueda
    if (typeof actualizarListaItems === 'function') {
        actualizarListaItems(items);
    }
    
    // Limpiar contenedor
    container.innerHTML = '';
    
    if (items.length === 0) {
        container.innerHTML = `
            <div class="col-12">
                <div class="alert alert-info text-center">
                    <i class="ri ri-information-line me-2"></i>
                    No hay items configurados
                </div>
            </div>
        `;
        return;
    }
    
    // Crear cards para cada item
    items.forEach(item => {
        const card = crearCardItem(item);
        container.appendChild(card);
    });
}

/**
 * Función para crear una card de item
 */
function crearCardItem(item) {
    console.log("crearCardItem loaditems.js ha cargado");
    const col = document.createElement('div');
    col.className = 'col-xl-4 col-lg-6 col-md-6 mb-4';
    
    // Determinar el estado del item
    const estadoTexto = item.state_item === 'true' ? 'Activo' : 'Inactivo';
    const estadoClase = item.state_item === 'true' ? 'success' : 'secondary';
    const estadoIcono = item.state_item === 'true' ? 'ri-check-circle-line' : 'ri-close-circle-line';
    
    // Determinar el tipo de item
    const tipoClase = getTipoClase(item.typ_item);
    const tipoIcono = getTipoIcono(item.typ_item);
    
    col.innerHTML = `
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <p class="mb-0">ID: ${item.id}</p>
                    <div class="badge bg-label-${tipoClase}">
                        <i class="${tipoIcono} me-1"></i>
                        ${item.typ_item}
                    </div>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="item-heading">
                        <h5 class="mb-1 text-capitalize">${item.itemnameText}</h5>
                        ${item.itemName ? `<small class="text-muted">ItemName: ${item.itemName}</small>` : ''}
                        <br><small class="text-muted">Padre: ${item.fhater_item || 'Ninguno'}</small>
                        ${item.url_item ? `<br><small class="text-muted">URL: ${item.url_item}</small>` : ''}
                        ${item.icon_menu ? `<br><small class="text-muted">Icono: ${item.icon_menu}</small>` : ''}
                        ${item.position_menu ? `<br><small class="text-muted">Posición: ${item.position_menu}</small>` : ''}
                    </div>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div class="badge bg-label-${estadoClase}">
                        <i class="${estadoIcono} me-1"></i>
                        ${estadoTexto}
                    </div>
                    <div class="badge bg-label-${item.in_menu === 'true' ? 'success' : 'secondary'}">
                        <i class="ri ri-${item.in_menu === 'true' ? 'menu-line' : 'menu-unfold-line'} me-1"></i>
                        ${item.in_menu === 'true' ? 'En Menú' : 'Oculto'}
                    </div>
                </div>
                <div class="d-flex justify-content-between align-items-center">
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-sm btn-outline-primary" 
                                onclick="editarItem(${item.id}, '${item.itemName}')">
                            <i class="ri ri-edit-line"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-${item.state_item === 'true' ? 'warning' : 'success'}" 
                                onclick="toggleEstadoItem(${item.id}, ${item.state_item === 'true'})">
                            <i class="ri ri-${item.state_item === 'true' ? 'pause-line' : 'play-line'}"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-danger" 
                                onclick="eliminarItem(${item.id}, '${item.itemName}')">
                            <i class="ri ri-delete-bin-line"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    return col;
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
        'delete': 'danger',
        'edit': 'danger',
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
        'delete': 'ri-delete-bin-line',
        'edit': 'ri-edit-line',
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
    const container = document.getElementById('items-container');
    if (container) {
        container.innerHTML = `
            <div class="col-12 text-center">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Cargando...</span>
                </div>
                <p class="mt-2">Cargando items...</p>
            </div>
        `;
    }
}

/**
 * Función para ocultar indicador de carga
 */
function ocultarCarga() {
    // La carga se oculta automáticamente al renderizar
}

/**
 * Función para mostrar errores en el contenedor principal
 */
function mostrarError(mensaje) {
    const container = document.getElementById('items-container');
    if (container) {
        container.innerHTML = `
            <div class="col-12">
                <div class="alert alert-danger text-center">
                    <i class="ri ri-error-warning-line me-2"></i>
                    ${mensaje}
                    <button class="btn btn-sm btn-outline-danger ms-3" onclick="cargarItems()">
                        <i class="ri ri-refresh-line me-1"></i>
                        Reintentar
                    </button>
                </div>
            </div>
        `;
    }
}

/**
 * Función para mostrar errores en un modal
 */
function mostrarErrorModal(mensaje) {
    // Crear toast de error si existe Bootstrap Toast
    if (typeof bootstrap !== 'undefined' && bootstrap.Toast) {
        const toastContainer = document.getElementById('toast-container') || crearToastContainer();
        const toast = crearToast('error', mensaje);
        toastContainer.appendChild(toast);
        
        const bsToast = new bootstrap.Toast(toast);
        bsToast.show();
    } else {
        // Fallback a alert si no hay Bootstrap
        alert('Error: ' + mensaje);
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
 * Marca la sección activa en el formulario de items
 */
function establecerSectionActivaItem(item, prefix) {
    const prefijo = prefix || '';
    const columnas = [
        'central_section',
        'sucursal_section',
        'recepcion_lotes_section',
        'auditoria_section'
    ];

    let sectionActiva = 'central_section';
    columnas.forEach(function (columna) {
        if (item && item[columna] === 'true') {
            sectionActiva = columna;
        }
    });

    const radio = document.getElementById(prefijo + sectionActiva + '_radio');
    if (radio) {
        radio.checked = true;
    }
}

/**
 * Función para agregar un nuevo item
 */
function agregarNuevoItem() {
    // Limpiar el formulario
    document.getElementById('formItem').reset();
    document.getElementById('idItem').value = '';
    document.getElementById('modalItemLabel').textContent = 'Nuevo Item';
    
    // Limpiar campos específicos por si acaso
    document.getElementById('itemName').value = '';
    document.getElementById('itemnameText').value = '';
    document.getElementById('nombre_singular').value = '';
    document.getElementById('nombre_singular_text').value = '';
    document.getElementById('typ_item').value = 'unique';
    document.getElementById('fhater_item').value = '0';
    document.getElementById('fhater_menu').value = '0';
    document.getElementById('state_item_true').checked = true;
    document.getElementById('in_menu_true').checked = true;
    document.getElementById('url_item').value = '';
    document.getElementById('icon_menu').value = '';
    establecerSectionActivaItem({ central_section: 'true' }, '');
    
    // Mostrar la opción CRUD al crear (estaba oculta al editar)
    const typItemSelect = document.getElementById('typ_item');
    const crudOption = typItemSelect.querySelector('option[value="crud"]');
    if (crudOption) {
        crudOption.style.display = '';
    }
    
    // Configurar evento para mostrar/ocultar campo SQL
    configurarEventoSQL();
    
    // Abrir el modal
    const modal = new bootstrap.Modal(document.getElementById('modalItem'));
    modal.show();
}

/**
 * Función para editar un item
 */
function editarItem(id, nombre) {
          // Cargar datos completos del item
      cargarDatosItemEditado(id, nombre);    
}

/**
 * Función para cargar datos completos de un item
 */
function cargarDatosItem(id, nombre) {
    // Mostrar indicador de carga
    const modal = document.getElementById('modalItem');
    const modalBody = modal.querySelector('.modal-body');
    const formOriginal = modalBody.innerHTML;
    
    modalBody.innerHTML = `
        <div class="text-center">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Cargando...</span>
            </div>
            <p class="mt-2">Cargando datos del item...</p>
        </div>
    `;
    
    // Realizar petición AJAX para obtener datos completos
    fetch(`parts/items/unique/get_item.php?id=${id}`, {
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
            // Restaurar formulario y llenar datos
            modalBody.innerHTML = formOriginal;
            
            document.getElementById('idItem').value = data.item.id_type_Item;
            document.getElementById('itemName').value = data.item.itemName;
            document.getElementById('itemnameText').value = data.item.itemnameText;
            document.getElementById('nombre_singular').value = data.item.nombre_singular || '';
            document.getElementById('nombre_singular_text').value = data.item.nombre_singular_text || '';
            document.getElementById('tabla_mysql_name').value = data.item.tabla_mysql_name || '';

            // Ocultar la opción CRUD al editar (solo disponible para crear)
            const typItemSelect = document.getElementById('typ_item');
            const crudOption = typItemSelect.querySelector('option[value="crud"]');
            if (crudOption) {
                crudOption.style.display = 'none';
            }
            typItemSelect.value = data.item.typ_item;
            document.getElementById('fhater_item').value = data.item.fhater_item || '0';
            document.getElementById('fhater_menu').value = data.item.fhater_menu || '0';
            document.getElementById('url_item').value = data.item.url_item || '';
            document.getElementById('icon_menu').value = data.item.icon_menu || '';
            
            // Marcar el radio button correcto para state_item
            if (data.item.state_item === 'true') {
                document.getElementById('state_item_true').checked = true;
            } else {
                document.getElementById('state_item_false').checked = true;
            }
            
            // Marcar el radio button correcto para in_menu
            if (data.item.in_menu === 'true') {
                document.getElementById('in_menu_true').checked = true;
            } else {
                document.getElementById('in_menu_false').checked = true;
            }
            
            document.getElementById('modalItemLabel').textContent = 'Editar Item';
            
            // Abrir el modal
            const bsModal = new bootstrap.Modal(modal);
            bsModal.show();
        } else {
            throw new Error(data.error || 'Error desconocido');
        }
    })
    .catch(error => {
        console.error('Error al cargar datos del item:', error);
        // Restaurar formulario y mostrar error
        modalBody.innerHTML = formOriginal;
        mostrarErrorModal('Error al cargar datos: ' + error.message);
    });
}

/**
 * Función para guardar el item
 */
function guardarItem() {
    const form = document.getElementById('formItem');
    const formData = new FormData(form);
    
    // Validar que el nombre no esté vacío
    if (!formData.get('itemName').trim()) {
        mostrarErrorModal('El nombre del item es obligatorio');
        return;
    }
    
    // Mostrar indicador de carga en el botón
    const btnGuardar = document.querySelector('#modalItem .btn-primary');
    const textoOriginal = btnGuardar.innerHTML;
    btnGuardar.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span>Guardando...';
    btnGuardar.disabled = true;
    
    // Realizar petición AJAX para guardar
    fetch('parts/items/unique/save_item.php', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            // Cerrar el modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('modalItem'));
            modal.hide();
            
            // Recargar la lista
            cargarItems();
            
            // Mostrar mensaje de éxito
            mostrarExito(data.message);
            
            // Limpiar formulario
            form.reset();
            
        } else {
            throw new Error(data.error || 'Error desconocido');
        }
    })
    .catch(error => {
        console.error('Error al guardar item:', error);
        mostrarErrorModal('Error al guardar el item: ' + error.message);
    })
    .finally(() => {
        // Restaurar botón
        btnGuardar.innerHTML = textoOriginal;
        btnGuardar.disabled = false;
    });
}

/**
 * Función para cargar datos del item en el modal de edición
 */
function cargarDatosItemEditado(id, nombre) {
    // Mostrar indicador de carga
    const modal = document.getElementById('editarmodalitem');
    const modalBody = modal.querySelector('.modal-body');
    const formOriginal = modalBody.innerHTML;
    
    modalBody.innerHTML = `
        <div class="text-center">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Cargando...</span>
            </div>
            <p class="mt-2">Cargando datos del item...</p>
        </div>
    `;
    
    // Realizar petición AJAX para obtener datos completos
    fetch(`parts/items/unique/get_item.php?id=${id}`, {
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
            // Restaurar formulario y llenar solo los campos específicos
            modalBody.innerHTML = formOriginal;
            
            // Cargar solo los campos del modal de edición
            document.getElementById('edit_idItem').value = data.item.id_type_Item;
            document.getElementById('edit_itemnameText').value = data.item.itemnameText;
            document.getElementById('edit_icon_menu').value = data.item.icon_menu || '';
            document.getElementById('edit_typ_item').value = data.item.typ_item;
            document.getElementById('edit_tabla_mysql_name').value = data.item.tabla_mysql_name;
            
            
            // Marcar el radio button correcto para state_item
            if (data.item.state_item === 'true') {
                document.getElementById('edit_state_item_true').checked = true;
            } else {
                document.getElementById('edit_state_item_false').checked = true;
            }
            
            // Marcar el radio button correcto para in_menu
            if (data.item.in_menu === 'true') {
                document.getElementById('edit_in_menu_true').checked = true;
            } else {
                document.getElementById('edit_in_menu_false').checked = true;
            }

            establecerSectionActivaItem(data.item, 'edit_');

            if (data.item.item_root === 'true') {
                document.getElementById('edit_item_root_true').checked = true;
            } else {
                document.getElementById('edit_item_root_false').checked = true;
            }
            
            // Cargar items padre en el select de edición y luego establecer los valores
            cargarItemsPadreEditado().then(() => {
                // Establecer el valor del fhater_item después de cargar las opciones
                document.getElementById('edit_fhater_item').value = data.item.fhater_item || '0';
            });
            
            cargarItemsMenuEditado().then(() => {
                // Establecer el valor del fhater_menu después de cargar las opciones
                document.getElementById('edit_fhater_menu').value = data.item.fhater_menu || '0';
            });
            
            // Abrir el modal
            const bsModal = new bootstrap.Modal(modal);
            bsModal.show();
        } else {
            throw new Error(data.error || 'Error desconocido');
        }
    })
    .catch(error => {
        console.error('Error al cargar datos del item:', error);
        // Restaurar formulario y mostrar error
        modalBody.innerHTML = formOriginal;
        mostrarErrorModal('Error al cargar datos: ' + error.message);
    });
}

/**
 * Función para cargar items padre en el modal de edición
 */
function cargarItemsPadreEditado() {
    return fetch('parts/items/unique/load_items_padre.php', {
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
            const select = document.getElementById('edit_fhater_item');
            if (select) {
                select.innerHTML = '<option value="0">Sin item padre</option>';
                if (data.data && data.data.length > 0) {
                    data.data.forEach(item => {
                        const option = document.createElement('option');
                        option.value = item.id_type_Item;
                        option.textContent = item.itemName;
                        select.appendChild(option);
                    });
                }
            }
        } else {
            // Si no hay éxito, mostrar opción por defecto
            const select = document.getElementById('edit_fhater_item');
            if (select) {
                select.innerHTML = '<option value="0">Sin item padre</option>';
            }
        }
    })
    .catch(error => {
        console.error('Error al cargar items padre para edición:', error);
        // En caso de error, mostrar opción por defecto
        const select = document.getElementById('edit_fhater_item');
        if (select) {
            select.innerHTML = '<option value="0">Sin item padre</option>';
        }
    });
}

/**
 * Función para cargar items de tipo "menu" en el modal de edición
 */
function cargarItemsMenuEditado() {
    return fetch('parts/items/unique/load_items_menu.php', {
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
            const select = document.getElementById('edit_fhater_menu');
            if (select) {
                select.innerHTML = '<option value="0">Sin item padre de menú</option>';
                if (data.data && data.data.length > 0) {
                    data.data.forEach(item => {
                        const option = document.createElement('option');
                        option.value = item.id_type_Item;
                        option.textContent = item.itemName;
                        select.appendChild(option);
                    });
                }
            }
        } else {
            // Si no hay éxito, mostrar opción por defecto
            const select = document.getElementById('edit_fhater_menu');
            if (select) {
                select.innerHTML = '<option value="0">Sin item padre de menú</option>';
            }
        }
    })
    .catch(error => {
        console.error('Error al cargar items menu para edición:', error);
        // En caso de error, mostrar opción por defecto
        const select = document.getElementById('edit_fhater_menu');
        if (select) {
            select.innerHTML = '<option value="0">Sin item padre de menú</option>';
        }
    });
}

/**
 * Función para cambiar el estado de un item
 */
function toggleEstadoItem(id, estadoActual) {
    const nuevoEstado = !estadoActual;
    const accion = nuevoEstado ? 'activar' : 'desactivar';
    
    if (!confirm(`¿Estás seguro de que quieres ${accion} este item?`)) {
        return;
    }
    
    // Realizar petición AJAX para cambiar estado
    fetch('parts/items/unique/toggle_estado.php', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: `id=${id}&state_item=${nuevoEstado}`
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            // Recargar la lista
            cargarItems();
            
            // Mostrar mensaje de éxito
            mostrarExito(`Item ${accion}do correctamente`);
        } else {
            throw new Error(data.error || 'Error desconocido');
        }
    })
    .catch(error => {
        console.error('Error al cambiar estado:', error);
        mostrarErrorModal('Error al cambiar estado: ' + error.message);
    });
}

/**
 * Función para eliminar un item
 */
function eliminarItem(id, nombre) {
    // Confirmar eliminación
    if (!confirm(`¿Estás seguro de que quieres eliminar el item "${nombre}"?\n\n⚠️ ADVERTENCIA: Esta acción eliminará también:\n• Todos los items hijos de forma permanente\n• Todas las carpetas y archivos asociados\n• La estructura completa del módulo\n\nEsta acción no se puede deshacer.`)) {
        return;
    }
    
    // Mostrar indicador de carga en el botón
    const btnEliminar = event.target.closest('.btn');
    const textoOriginal = btnEliminar.innerHTML;
    btnEliminar.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span>Eliminando...';
    btnEliminar.disabled = true;
    
    // Realizar petición AJAX para eliminar
    fetch('parts/items/unique/procesa_eliminar.php', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: `id=${id}`
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            // Recargar la lista
            cargarItems();
            
            // Mostrar mensaje de éxito
            const mensaje = data.total_eliminados > 1 
                ? `Item "${nombre}" y ${data.total_eliminados - 1} items hijos eliminados correctamente`
                : `Item "${nombre}" eliminado correctamente`;
            mostrarExito(mensaje);
        } else {
            throw new Error(data.error || 'Error desconocido');
        }
    })
    .catch(error => {
        console.error('Error al eliminar item:', error);
        mostrarErrorModal('Error al eliminar el item: ' + error.message);
    })
    .finally(() => {
        // Restaurar botón
        btnEliminar.innerHTML = textoOriginal;
        btnEliminar.disabled = false;
    });
}

/**
 * Función para guardar el item editado
 */
function guardarItemEditado() {
    const form = document.getElementById('formEditarItem');
    const formData = new FormData(form);
    
    // Validar que el nombre no esté vacío
    if (!formData.get('itemnameText').trim()) {
        mostrarErrorModal('El nombre del item a mostrar en el menú es obligatorio');
        return;
    }
    
    // Mostrar indicador de carga en el botón
    const btnGuardar = document.querySelector('#editarmodalitem .btn-primary');
    const textoOriginal = btnGuardar.innerHTML;
    btnGuardar.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span>Guardando...';
    btnGuardar.disabled = true;
    
    // Realizar petición AJAX para guardar
    fetch('parts/items/unique/update_item.php', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            // Cerrar el modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('editarmodalitem'));
            modal.hide();
            
            // Recargar la lista
            cargarItems();
            
            // Mostrar mensaje de éxito
            mostrarExito(data.message || 'Item actualizado correctamente');
            
            // Limpiar formulario
            form.reset();
            
        } else {
            throw new Error(data.error || 'Error desconocido');
        }
    })
    .catch(error => {
        console.error('Error al actualizar item:', error);
        mostrarErrorModal('Error al actualizar el item: ' + error.message);
    })
    .finally(() => {
        // Restaurar botón
        btnGuardar.innerHTML = textoOriginal;
        btnGuardar.disabled = false;
    });
}

/**
 * Función para configurar el evento de mostrar/ocultar campo SQL
 */
function configurarEventoSQL() {
    console.log('Configurando evento SQL...');
    
    // Configurar para modal de crear
    const typItemSelect = document.getElementById('typ_item');
    const sqlSection = document.getElementById('sql_upload_section');
    
    console.log('typItemSelect:', typItemSelect);
    console.log('sqlSection:', sqlSection);
    
    if (typItemSelect && sqlSection) {
        console.log('Agregando event listener para typ_item');
        typItemSelect.addEventListener('change', function() {
            console.log('Cambio en typ_item:', this.value);
            if (this.value === 'crud') {
                console.log('Mostrando campo SQL');
                sqlSection.style.display = 'block';
            } else {
                console.log('Ocultando campo SQL');
                sqlSection.style.display = 'none';
                // Limpiar el archivo seleccionado
                const fileInput = document.getElementById('sql_file');
                const nombreArchivo = document.getElementById('nombre_archivo');
                if (fileInput) fileInput.value = '';
                if (nombreArchivo) nombreArchivo.style.display = 'none';
            }
        });
    } else {
        console.log('No se encontraron elementos para configurar SQL');
    }
    
    // Configurar para modal de editar (si existe)
    const editTypItemSelect = document.getElementById('edit_typ_item');
    const editSqlSection = document.getElementById('edit_sql_upload_section');
    
    if (editTypItemSelect && editSqlSection) {
        editTypItemSelect.addEventListener('change', function() {
            if (this.value === 'crud') {
                editSqlSection.style.display = 'block';
            } else {
                editSqlSection.style.display = 'none';
                // Limpiar el archivo seleccionado
                const fileInput = document.getElementById('edit_sql_file');
                const nombreArchivo = document.getElementById('edit_nombre_archivo');
                if (fileInput) fileInput.value = '';
                if (nombreArchivo) nombreArchivo.style.display = 'none';
            }
        });
    }
}

/**
 * Función para mostrar el nombre del archivo SQL seleccionado
 */
function mostrarNombreArchivo() {
    const fileInput = document.getElementById('sql_file');
    const nombreArchivo = document.getElementById('nombre_archivo');
    const nombreArchivoTexto = document.getElementById('nombre_archivo_texto');
    
    if (fileInput.files.length > 0) {
        const fileName = fileInput.files[0].name;
        nombreArchivoTexto.textContent = fileName;
        nombreArchivo.style.display = 'block';
    } else {
        nombreArchivo.style.display = 'none';
    }
}

/**
 * Función para mostrar el nombre del archivo SQL seleccionado en edición
 */
function mostrarNombreArchivoEdit() {
    const fileInput = document.getElementById('edit_sql_file');
    const nombreArchivo = document.getElementById('edit_nombre_archivo');
    const nombreArchivoTexto = document.getElementById('edit_nombre_archivo_texto');
    
    if (fileInput.files.length > 0) {
        const fileName = fileInput.files[0].name;
        nombreArchivoTexto.textContent = fileName;
        nombreArchivo.style.display = 'block';
    } else {
        nombreArchivo.style.display = 'none';
    }
}
