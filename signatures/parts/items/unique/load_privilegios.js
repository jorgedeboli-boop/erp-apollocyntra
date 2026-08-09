/**
 * Script para cargar privilegios desde la base de datos
 * y mostrarlos dinámicamente en la página
 */

document.addEventListener('DOMContentLoaded', function() {
    // Cargar privilegios al iniciar la página
    cargarPrivilegios();
});

/**
 * Función para cargar los privilegios desde la base de datos
 */
function cargarPrivilegios() {
    // Mostrar indicador de carga
    mostrarCarga();
    
    // Realizar petición AJAX
    fetch('parts/jerarquias/unique/load_privilegios.php', {
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
            
            // Renderizar los privilegios
            renderizarPrivilegios(data.data);
        } else {
            throw new Error(data.error || 'Error desconocido');
        }
    })
    .catch(error => {
        console.error('Error al cargar privilegios:', error);
        ocultarCarga();
        mostrarError('Error al cargar los privilegios: ' + error.message);
    });
}

/**
 * Función para renderizar los privilegios en el HTML
 */
function renderizarPrivilegios(privilegios) {
    const container = document.getElementById('privilegios-container');
    
    if (!container) {
        console.error('No se encontró el contenedor de privilegios');
        return;
    }
    
    // Limpiar contenedor
    container.innerHTML = '';
    
    if (privilegios.length === 0) {
        container.innerHTML = `
            <div class="col-12">
                <div class="alert alert-info text-center">
                    <i class="ri ri-information-line me-2"></i>
                    No hay privilegios configurados
                </div>
            </div>
        `;
        return;
    }
    
    // Crear cards para cada privilegio
    privilegios.forEach(privilegio => {
        const card = crearCardPrivilegio(privilegio);
        container.appendChild(card);
    });
}

/**
 * Función para crear una card de privilegio
 */
function crearCardPrivilegio(privilegio) {
    const col = document.createElement('div');
    col.className = 'col-xl-4 col-lg-6 col-md-6 mb-4';
    
    col.innerHTML = `
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <p class="mb-0">ID: ${privilegio.id_privilegios}</p>
                    <div class="badge bg-label-primary">
                        <i class="ri ri-shield-user-line me-1"></i>
                        Privilegio
                    </div>
                </div>
                <div class="d-flex justify-content-between align-items-center">
                    <div class="role-heading">
                        <h5 class="mb-1">${privilegio.nombre_privilegio}</h5>
                        <a href="javascript:void(0);" 
                           class="role-edit-modal"
                           onclick="editarPrivilegio(${privilegio.id_privilegios}, '${privilegio.nombre_privilegio}')">
                            <p class="mb-0">Editar Privilegio</p>
                        </a>
                    </div>
                    <a href="javascript:void(0);" 
                       class="text-secondary"
                       onclick="verDetallesPrivilegio(${privilegio.id_privilegios})">
                        <i class="icon-base ri ri-eye-line icon-22px"></i>
                    </a>
                </div>
            </div>
        </div>
    `;
    
    return col;
}

/**
 * Función para mostrar indicador de carga
 */
function mostrarCarga() {
    const container = document.getElementById('privilegios-container');
    if (container) {
        container.innerHTML = `
            <div class="col-12 text-center">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Cargando...</span>
                </div>
                <p class="mt-2">Cargando privilegios...</p>
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
    const container = document.getElementById('privilegios-container');
    if (container) {
        container.innerHTML = `
            <div class="col-12">
                <div class="alert alert-danger text-center">
                    <i class="ri ri-error-warning-line me-2"></i>
                    ${mensaje}
                    <button class="btn btn-sm btn-outline-danger ms-3" onclick="cargarPrivilegios()">
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
                <i class="ri ri-${tipo === 'success' ? 'check-circle' : 'error-warning'}-line me-2"></i>
                ${mensaje}
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    `;
    
    return toast;
}

/**
 * Función para editar un privilegio
 */
function editarPrivilegio(id, nombre) {
    // Cargar datos completos del privilegio
    cargarDatosPrivilegio(id, nombre);
}

/**
 * Función para cargar datos completos de un privilegio
 */
function cargarDatosPrivilegio(id, nombre) {
    // Mostrar indicador de carga
    const modal = document.getElementById('modalJerarquia');
    const modalBody = modal.querySelector('.modal-body');
    const formOriginal = modalBody.innerHTML;
    
    modalBody.innerHTML = `
        <div class="text-center">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Cargando...</span>
            </div>
            <p class="mt-2">Cargando datos del privilegio...</p>
        </div>
    `;
    
    // Realizar petición AJAX para obtener datos completos
    fetch(`parts/jerarquias/unique/get_privilegio.php?id=${id}`, {
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
            
            document.getElementById('idPrivilegio').value = data.privilegio.id_privilegios;
            document.getElementById('nombrePrivilegio').value = data.privilegio.nombre_privilegio;
            document.getElementById('modalJerarquiaLabel').textContent = 'Editar Jerarquía';
            
            // Abrir el modal
            const bsModal = new bootstrap.Modal(modal);
            bsModal.show();
        } else {
            throw new Error(data.error || 'Error desconocido');
        }
    })
    .catch(error => {
        console.error('Error al cargar datos del privilegio:', error);
        // Restaurar formulario y mostrar error
        modalBody.innerHTML = formOriginal;
        mostrarErrorModal('Error al cargar datos: ' + error.message);
    });
}

/**
 * Función para ver detalles de un privilegio
 */
function verDetallesPrivilegio(id) {
    // TODO: Implementar funcionalidad de detalles
    console.log('Ver detalles del privilegio:', id);
    alert(`Función de detalles para privilegio ID: ${id}`);
}

/**
 * Función para agregar una nueva jerarquía
 */
function agregarNuevaJerarquia() {
    // Limpiar el formulario
    document.getElementById('formJerarquia').reset();
    document.getElementById('idPrivilegio').value = '';
    document.getElementById('modalJerarquiaLabel').textContent = 'Nueva Jerarquía';
    
    // Limpiar campo específico por si acaso
    document.getElementById('nombrePrivilegio').value = '';
    
    // Abrir el modal
    const modal = new bootstrap.Modal(document.getElementById('modalJerarquia'));
    modal.show();
}

/**
 * Función para guardar la jerarquía
 */
function guardarJerarquia() {
    const form = document.getElementById('formJerarquia');
    const formData = new FormData(form);
    
    // Validar que el nombre no esté vacío
    if (!formData.get('nombrePrivilegio').trim()) {
        mostrarErrorModal('El nombre de la jerarquía es obligatorio');
        return;
    }
    
    // Mostrar indicador de carga en el botón
    const btnGuardar = document.querySelector('#modalJerarquia .btn-primary');
    const textoOriginal = btnGuardar.innerHTML;
    btnGuardar.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span>Guardando...';
    btnGuardar.disabled = true;
    
    // Realizar petición AJAX para guardar
    fetch('parts/jerarquias/unique/save_privilegio.php', {
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
            const modal = bootstrap.Modal.getInstance(document.getElementById('modalJerarquia'));
            modal.hide();
            
            // Recargar la lista
            cargarPrivilegios();
            
            // Mostrar mensaje de éxito
            mostrarExito(data.message);
            
            // Limpiar formulario
            form.reset();
            
        } else {
            throw new Error(data.error || 'Error desconocido');
        }
    })
    .catch(error => {
        console.error('Error al guardar jerarquía:', error);
        mostrarErrorModal('Error al guardar la jerarquía: ' + error.message);
    })
    .finally(() => {
        // Restaurar botón
        btnGuardar.innerHTML = textoOriginal;
        btnGuardar.disabled = false;
    });
}
