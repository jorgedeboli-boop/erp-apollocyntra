<script>
/**
 * Función para cambiar el estado del usuario (habilitar/deshabilitar)
 */
function toggleEstadoUsuario(idUsuario) {
    const btnToggle = document.getElementById('btnToggleEstado');
    
    // Obtener el estado actual del botón
    const estadoActual = btnToggle.classList.contains('btn-danger') ? 'habilitado' : 'deshabilitado';
    const nuevoEstado = estadoActual === 'habilitado' ? 'deshabilitado' : 'habilitado';
    
    // Confirmar la acción
    const mensaje = nuevoEstado === 'habilitado' 
        ? '¿Estás seguro de que quieres habilitar este usuario?' 
        : '¿Estás seguro de que quieres deshabilitar este usuario?';
    
    Swal.fire({
        title: 'Confirmar acción',
        text: mensaje,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: nuevoEstado === 'habilitado' ? '#198754' : '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: nuevoEstado === 'habilitado' ? 'Habilitar' : 'Deshabilitar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            // Deshabilitar botón durante la petición
            btnToggle.disabled = true;
            btnToggle.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Procesando...';
            
            // Realizar petición AJAX
            fetch('parts/usuarios/main/procesar_cambiar_estado_usuario.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'id_usuario=' + idUsuario
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Actualizar botón
                    actualizarBotonEstadoUsuario(nuevoEstado);
                    
                    // Mostrar mensaje de éxito
                    Swal.fire({
                        title: '¡Estado actualizado!',
                        text: data.message || 'El estado del usuario se ha actualizado correctamente',
                        icon: 'success',
                        confirmButtonText: 'Aceptar',
                        confirmButtonColor: '#198754',
                        timer: 3000,
                        timerProgressBar: true
                    });
                    
                    // Actualizar el estado en la página
                    actualizarEstadoEnPaginaUsuario(nuevoEstado);
                    
                } else {
                    throw new Error(data.error || 'Error desconocido');
                }
            })
            .catch(error => {
                console.error('Error en toggleEstadoUsuario:', error);
                
                // Mostrar mensaje de error
                Swal.fire({
                    title: 'Error',
                    text: 'No se pudo cambiar el estado: ' + error.message,
                    icon: 'error',
                    confirmButtonText: 'Aceptar',
                    confirmButtonColor: '#dc3545'
                });
                
                // Restaurar botón
                restaurarBotonEstadoUsuario(estadoActual);
            });
        }
    });
}

/**
 * Actualizar badge de conexión en la ficha del usuario
 */
function actualizarBadgeConexionUsuario(estadoConexion) {
    const conectado = estadoConexion === 'true' || estadoConexion === true;
    const badge = document.querySelector('.badge.bg-label-success, .badge.bg-label-secondary');

    if (!badge) {
        return;
    }

    badge.className = `badge ${conectado ? 'bg-label-success' : 'bg-label-secondary'} me-2 ms-2 rounded-pill`;
    badge.innerHTML = `<i class="icon-base ri ${conectado ? 'ri-wifi-line' : 'ri-wifi-off-line'} me-1"></i>${conectado ? 'Conectado' : 'Desconectado'}`;
}

/**
 * Desconectar usuario: confirmación y petición AJAX
 */
function desconectarUsuario(idUsuario, nombreUsuario, btnDesconectar) {
    const nombre = (nombreUsuario || '').trim() !== '' ? nombreUsuario.trim() : 'este usuario';

    Swal.fire({
        title: 'Confirmar desconexión',
        text: '¿Está seguro de desconectar al usuario ' + nombre + '?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, desconectar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (!result.isConfirmed) {
            return;
        }

        const textoOriginal = btnDesconectar.innerHTML;
        btnDesconectar.disabled = true;
        btnDesconectar.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Desconectando...';

        fetch('parts/usuarios/main/procesar_desconectar_usuario.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'id_usuario=' + encodeURIComponent(idUsuario)
        })
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                throw new Error(data.error || 'Error desconocido');
            }

            actualizarBadgeConexionUsuario('false');
            btnDesconectar.remove();

            if (typeof dt_usuario_conexiones !== 'undefined' && dt_usuario_conexiones) {
                dt_usuario_conexiones.ajax.reload(null, false);
            }

            Swal.fire({
                title: 'Usuario desconectado',
                text: data.message || 'El usuario se ha desconectado correctamente',
                icon: 'success',
                confirmButtonText: 'Aceptar',
                confirmButtonColor: '#198754',
                timer: 3000,
                timerProgressBar: true
            });
        })
        .catch(error => {
            console.error('Error en desconectarUsuario:', error);
            btnDesconectar.disabled = false;
            btnDesconectar.innerHTML = textoOriginal;

            Swal.fire({
                title: 'Error',
                text: 'No se pudo desconectar al usuario: ' + error.message,
                icon: 'error',
                confirmButtonText: 'Aceptar',
                confirmButtonColor: '#dc3545'
            });
        });
    });
}

/**
 * Función para actualizar el estado de conexión en tiempo real
 */
function actualizarEstadoConexion() {
    const idUsuario = <?php echo isset($_GET['id']) ? (int)$_GET['id'] : 0; ?>;
    
    fetch('parts/usuarios/main/get_estado_conexion_usuario.php?id=' + idUsuario)
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            actualizarBadgeConexionUsuario(data.estado_conexion);
        }
    })
    .catch(error => {
        console.error('Error al actualizar estado de conexión:', error);
    });
}

/**
 * Actualizar el botón según el nuevo estado
 */
function actualizarBotonEstadoUsuario(nuevoEstado) {
    const btnToggle = document.getElementById('btnToggleEstado');
    
    if (nuevoEstado === 'habilitado') {
        btnToggle.className = 'btn btn-danger waves-effect waves-light';
        btnToggle.innerHTML = '<i class="icon-base ri ri-user-forbid-line icon-16px me-2"></i>Deshabilitar';
    } else {
        btnToggle.className = 'btn btn-success waves-effect waves-light';
        btnToggle.innerHTML = '<i class="icon-base ri ri-user-follow-line icon-16px me-2"></i>Habilitar';
    }
    
    btnToggle.disabled = false;
}

/**
 * Restaurar el botón al estado anterior
 */
function restaurarBotonEstadoUsuario(estadoAnterior) {
    const btnToggle = document.getElementById('btnToggleEstado');
    
    if (estadoAnterior === 'habilitado') {
        btnToggle.className = 'btn btn-danger waves-effect waves-light';
        btnToggle.innerHTML = '<i class="icon-base ri ri-user-forbid-line icon-16px me-2"></i>Deshabilitar';
    } else {
        btnToggle.className = 'btn btn-success waves-effect waves-light';
        btnToggle.innerHTML = '<i class="icon-base ri ri-user-follow-line icon-16px me-2"></i>Habilitar';
    }
    
    btnToggle.disabled = false;
}

/**
 * Actualizar el estado mostrado en la página
 */
function actualizarEstadoEnPaginaUsuario(nuevoEstado) {
    // Buscar y actualizar el badge del estado en la información personal
    const estadoElements = document.querySelectorAll('.fw-medium');
    estadoElements.forEach(element => {
        if (element.textContent.includes('Acceso:')) {
            const spanEstado = element.nextElementSibling;
            if (spanEstado && spanEstado.tagName === 'SPAN') {
                // Actualizar el badge del estado
                const badge = spanEstado.querySelector('.badge');
                if (badge) {
                    // Cambiar la clase del badge
                    if (nuevoEstado === 'habilitado') {
                        badge.className = 'badge bg-label-success me-2 ms-2 rounded-pill';
                        badge.textContent = 'Habilitado';
                    } else {
                        badge.className = 'badge bg-label-danger me-2 ms-2 rounded-pill';
                        badge.textContent = 'Deshabilitado';
                    }
                }
            }
        }
    });
}

// Actualizar estado de conexión cada 30 segundos
setInterval(actualizarEstadoConexion, 30000);

// Actualizar estado de conexión al cargar la página
document.addEventListener('DOMContentLoaded', function() {
    actualizarEstadoConexion();

    const btnDesconectar = document.getElementById('btndesconectarUser');
    if (!btnDesconectar) {
        return;
    }

    btnDesconectar.addEventListener('click', function() {
        const idUsuario = parseInt(btnDesconectar.dataset.userId || '0', 10);
        const nombreUsuario = btnDesconectar.dataset.nombreUsuario || '';

        if (!idUsuario) {
            return;
        }

        desconectarUsuario(idUsuario, nombreUsuario, btnDesconectar);
    });
});
</script>

<!-- Script para DataTable de acciones del usuario -->
<?php
$vTablesDatatablesLoadAcciones = filemtime(__DIR__ . '/tables-datatables-load-acciones.js');
$vTablesDatatablesLoadConexiones = filemtime(__DIR__ . '/tables-datatables-load-conexiones.js');
$vJerarquiaPermisosView = filemtime(__DIR__ . '/jerarquia_permisos_view.js');
?>
<script src="parts/usuarios/main/tables-datatables-load-acciones.js?v=<?php echo $vTablesDatatablesLoadAcciones; ?>"></script>

<!-- Script para DataTable de conexiones del usuario -->
<script src="parts/usuarios/main/tables-datatables-load-conexiones.js?v=<?php echo $vTablesDatatablesLoadConexiones; ?>"></script>

<!-- Visor de permisos de la jerarquía del usuario -->
<script src="parts/usuarios/main/jerarquia_permisos_view.js?v=<?php echo $vJerarquiaPermisosView; ?>"></script>
