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
    fetch('parts/jerarquias/listar/load_privilegios.php', {
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
 * Escapar texto para HTML
 */
function escapeHtmlPrivilegios(texto) {
    return String(texto || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

/**
 * Formatear cantidad de usuarios
 */
function formatearTextoUsuarios(total) {
    var cantidad = parseInt(String(total || 0), 10);
    if (isNaN(cantidad) || cantidad < 0) {
        cantidad = 0;
    }
    return cantidad + ' usuario' + (cantidad === 1 ? '' : 's');
}

/**
 * Función para crear una card de privilegio
 */
function crearCardPrivilegio(privilegio) {
    const col = document.createElement('div');
    col.className = 'col-xl-4 col-lg-6 col-md-6 mb-4';

    const totalUsuarios = parseInt(String(privilegio.total_usuarios || 0), 10) || 0;
    const nombreEscapado = escapeHtmlPrivilegios(privilegio.nombre_privilegio);
    const nombreJs = String(privilegio.nombre_privilegio || '').replace(/\\/g, '\\\\').replace(/'/g, "\\'");

    col.innerHTML = `
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <p class="mb-0">ID: ${privilegio.id_privilegios}</p>
                    <div class="d-flex align-items-center gap-2">
                        <div class="badge bg-label-primary">
                            <i class="ri ri-shield-user-line me-1"></i>
                            Privilegio
                        </div>
                        <button
                            type="button"
                            class="badge bg-label-info border-0 usuarios-privilegio-badge"
                            style="cursor: pointer;"
                            data-id-privilegio="${privilegio.id_privilegios}"
                            data-nombre-privilegio="${nombreEscapado}"
                            title="Ver usuarios con este privilegio"
                        >
                            <i class="ri ri-user-star-line me-1"></i>
                            ${formatearTextoUsuarios(totalUsuarios)}
                        </button>
                    </div>
                </div>
                <div class="d-flex justify-content-between align-items-center">
                    <div class="role-heading">
                        <h5 class="mb-1">${nombreEscapado}</h5>
                        <a href="javascript:void(0);"
                           class="role-edit-modal"
                           onclick="editarPrivilegio(${privilegio.id_privilegios}, '${nombreJs}')">
                            <p class="mb-0">Editar Privilegio</p>
                        </a>
                    </div>
                    <a href="editar_jerarquia.php?id=${privilegio.id_privilegios}"
                       class="text-secondary"
                       title="Editar jerarquía">
                        <i class="icon-base ri ri-edit-line icon-22px"></i>
                    </a>
                </div>
            </div>
        </div>
    `;

    const btnUsuarios = col.querySelector('.usuarios-privilegio-badge');
    if (btnUsuarios) {
        btnUsuarios.addEventListener('click', function () {
            abrirModalUsuariosPrivilegio(
                parseInt(String(btnUsuarios.getAttribute('data-id-privilegio') || '0'), 10),
                privilegio.nombre_privilegio || ''
            );
        });
    }

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
 * Marca la sección activa en el formulario del modal
 */
function establecerSectionActivaJerarquia(privilegio) {
    const columnas = [
        'central_section',
        'sucursal_section',
        'recepcion_lotes_section',
        'auditoria_section'
    ];

    let sectionActiva = 'central_section';
    columnas.forEach(function (columna) {
        if (privilegio && privilegio[columna] === 'true') {
            sectionActiva = columna;
        }
    });

    const radio = document.getElementById(sectionActiva + '_radio');
    if (radio) {
        radio.checked = true;
    }
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
    fetch(`parts/jerarquias/listar/get_privilegio.php?id=${id}`, {
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
            establecerSectionActivaJerarquia(data.privilegio);
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
    abrirModalUsuariosPrivilegio(id, '');
}

var usuarioPrivilegioSeleccionadoId = 0;

function resetSeleccionUsuarioPrivilegio() {
    usuarioPrivilegioSeleccionadoId = 0;

    const btnVerFicha = document.getElementById('btnVerFichaUsuarioPrivilegio');
    if (!btnVerFicha) {
        return;
    }

    btnVerFicha.href = '#';
    btnVerFicha.classList.add('disabled');
    btnVerFicha.setAttribute('aria-disabled', 'true');
    btnVerFicha.tabIndex = -1;
}

function seleccionarUsuarioPrivilegio(idUsuario) {
    usuarioPrivilegioSeleccionadoId = parseInt(String(idUsuario || 0), 10) || 0;

    const btnVerFicha = document.getElementById('btnVerFichaUsuarioPrivilegio');
    const filas = document.querySelectorAll('#tablaUsuariosPrivilegio tbody tr');

    filas.forEach(function (fila) {
        const idFila = parseInt(String(fila.getAttribute('data-id-usuario') || '0'), 10);
        fila.classList.toggle('table-active', idFila === usuarioPrivilegioSeleccionadoId);
    });

    if (!btnVerFicha || usuarioPrivilegioSeleccionadoId <= 0) {
        resetSeleccionUsuarioPrivilegio();
        return;
    }

    btnVerFicha.href = 'usuario.php?id=' + encodeURIComponent(String(usuarioPrivilegioSeleccionadoId));
    btnVerFicha.classList.remove('disabled');
    btnVerFicha.removeAttribute('aria-disabled');
    btnVerFicha.tabIndex = 0;
}

function renderizarTablaUsuariosPrivilegio(usuarios) {
    const modalBody = document.getElementById('modalUsuariosPrivilegioBody');
    if (!modalBody) {
        return;
    }

    resetSeleccionUsuarioPrivilegio();

    if (!usuarios.length) {
        modalBody.innerHTML = `
            <div class="text-center text-muted py-5 px-3">
                <i class="ri ri-user-unfollow-line fs-3 d-block mb-2"></i>
                No hay usuarios con este privilegio
            </div>
        `;
        return;
    }

    const filas = usuarios.map(function (usuario) {
        const estadoClass = usuario.estado_usuario === 'Habilitado' ? 'success' : 'danger';
        return `
            <tr data-id-usuario="${usuario.id_usuario}" role="button" tabindex="0">
                <td>${escapeHtmlPrivilegios(usuario.id_usuario)}</td>
                <td>${escapeHtmlPrivilegios(usuario.usuario)}</td>
                <td><span class="badge bg-label-${estadoClass}">${escapeHtmlPrivilegios(usuario.estado_usuario)}</span></td>
                <td>${escapeHtmlPrivilegios(usuario.sucursal_usuario)}</td>
            </tr>
        `;
    }).join('');

    modalBody.innerHTML = `
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="tablaUsuariosPrivilegio">
                <thead class="table-light">
                    <tr>
                        <th>ID usuario</th>
                        <th>Usuario</th>
                        <th>Estado</th>
                        <th>Sucursal</th>
                    </tr>
                </thead>
                <tbody>${filas}</tbody>
            </table>
        </div>
    `;

    modalBody.querySelectorAll('#tablaUsuariosPrivilegio tbody tr').forEach(function (fila) {
        const activar = function () {
            seleccionarUsuarioPrivilegio(fila.getAttribute('data-id-usuario'));
        };

        fila.addEventListener('click', activar);
        fila.addEventListener('keydown', function (evento) {
            if (evento.key === 'Enter' || evento.key === ' ') {
                evento.preventDefault();
                activar();
            }
        });
    });
}

function abrirModalUsuariosPrivilegio(idPrivilegio, nombrePrivilegio) {
    const id = parseInt(String(idPrivilegio || 0), 10);
    if (id <= 0) {
        return;
    }

    const modalEl = document.getElementById('modalUsuariosPrivilegio');
    const modalBody = document.getElementById('modalUsuariosPrivilegioBody');
    const modalTitle = document.getElementById('modalUsuariosPrivilegioLabel');

    if (!modalEl || !modalBody) {
        return;
    }

    if (modalTitle) {
        modalTitle.textContent = nombrePrivilegio
            ? 'Usuarios · ' + nombrePrivilegio
            : 'Usuarios del privilegio';
    }

    modalBody.innerHTML = `
        <div class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Cargando...</span>
            </div>
            <p class="mt-2 mb-0">Cargando usuarios...</p>
        </div>
    `;
    resetSeleccionUsuarioPrivilegio();

    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    modal.show();

    fetch('parts/jerarquias/listar/get_usuarios_privilegio.php?id=' + encodeURIComponent(String(id)), {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(function (response) {
        if (!response.ok) {
            throw new Error('HTTP error! status: ' + response.status);
        }
        return response.json();
    })
    .then(function (data) {
        if (!data.success) {
            throw new Error(data.error || 'Error desconocido');
        }

        if (modalTitle && data.privilegio && data.privilegio.nombre_privilegio) {
            modalTitle.textContent = 'Usuarios · ' + data.privilegio.nombre_privilegio;
        }

        renderizarTablaUsuariosPrivilegio(data.usuarios || []);
    })
    .catch(function (error) {
        console.error('Error al cargar usuarios del privilegio:', error);
        modalBody.innerHTML = `
            <div class="alert alert-danger m-3 mb-0">
                Error al cargar los usuarios: ${escapeHtmlPrivilegios(error.message)}
            </div>
        `;
        resetSeleccionUsuarioPrivilegio();
    });
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
    establecerSectionActivaJerarquia({ central_section: 'true' });
    
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
    fetch('parts/jerarquias/listar/save_privilegio.php', {
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
