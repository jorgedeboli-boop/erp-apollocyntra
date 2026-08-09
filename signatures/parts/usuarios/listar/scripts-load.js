/**
 * Scripts para cargar estadísticas de usuarios
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
    // Cargar usuarios conectados
    cargarUsuariosConectados();
    
    // Cargar total de usuarios
    cargarTotalUsuarios();
    
    // Cargar usuarios habilitados
    cargarUsuariosHabilitados();
    
    // Cargar usuarios bloqueados
    cargarUsuariosBloqueados();
}

/**
 * Cargar total de usuarios conectados
 * Tabla: usersConexions, agrupado por userId y último estado
 */
function cargarUsuariosConectados() {
    fetch('parts/usuarios/listar/load_stats.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'tipo=usuarios_conectados'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const elemento = document.getElementById('total-usuarios-conectados');
            if (elemento) {
                elemento.textContent = data.total;
            }
        } else {
            console.error('Error cargando usuarios conectados:', data.error);
        }
    })
    .catch(error => {
        console.error('Error en la petición:', error);
    });
}

/**
 * Cargar total de usuarios
 * Tabla: usuarios
 */
function cargarTotalUsuarios() {
    fetch('parts/usuarios/listar/load_stats.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'tipo=total_usuarios'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const elemento = document.getElementById('total-usuarios');
            if (elemento) {
                elemento.textContent = data.total;
            }
        } else {
            console.error('Error cargando total usuarios:', data.error);
        }
    })
    .catch(error => {
        console.error('Error en la petición:', error);
    });
}

/**
 * Cargar usuarios habilitados
 * Tabla: usuarios, estado: estado_usuario = 'true'
 */
function cargarUsuariosHabilitados() {
    fetch('parts/usuarios/listar/load_stats.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'tipo=usuarios_habilitados'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const elemento = document.getElementById('total-usuarios-habilitados');
            if (elemento) {
                elemento.textContent = data.total;
            }
        } else {
            console.error('Error cargando usuarios habilitados:', data.error);
        }
    })
    .catch(error => {
        console.error('Error en la petición:', error);
    });
}

/**
 * Cargar usuarios bloqueados
 * Tabla: usuarios, estado: estado_usuario = 'false'
 */
function cargarUsuariosBloqueados() {
    fetch('parts/usuarios/listar/load_stats.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'tipo=usuarios_bloqueados'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const elemento = document.getElementById('total-usuarios-bloqueados');
            if (elemento) {
                elemento.textContent = data.total;
            }
        } else {
            console.error('Error cargando usuarios bloqueados:', data.error);
        }
    })
    .catch(error => {
        console.error('Error en la petición:', error);
    });
}

/**
 * Función para recargar estadísticas manualmente
 * Útil para botones de refresh o después de operaciones
 */
function recargarEstadisticas() {
    cargarEstadisticas();
}

/**
 * Función para mostrar/ocultar indicador de carga
 */
function mostrarCarga(mostrar = true) {
    const elementos = document.querySelectorAll('.stats-loading');
    elementos.forEach(elemento => {
        if (mostrar) {
            elemento.style.display = 'block';
        } else {
            elemento.style.display = 'none';
        }
    });
}

// Verificar si hay mensajes de estado en la URL
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const mensaje = urlParams.get('mensaje');
    const id = urlParams.get('id');
    
    if (mensaje === 'usuario_actualizado') {
        Swal.fire({
            title: '¡Éxito!',
            text: 'El usuario ha sido actualizado correctamente',
            icon: 'success',
            confirmButtonText: 'Aceptar',
            confirmButtonColor: '#696cff',
            timer: 3000,
            timerProgressBar: true,
            showConfirmButton: true
        }).then(() => {
            // Limpiar la URL después de mostrar el mensaje
            const nuevaUrl = window.location.pathname;
            window.history.replaceState({}, document.title, nuevaUrl);
        });
    } else if (mensaje === 'error_actualizacion') {
        const error = urlParams.get('error');
        Swal.fire({
            title: 'Error',
            text: error || 'Ha ocurrido un error al actualizar el usuario',
            icon: 'error',
            confirmButtonText: 'Aceptar',
            confirmButtonColor: '#dc3545',
            timer: 5000,
            timerProgressBar: true,
            showConfirmButton: true
        }).then(() => {
            // Limpiar la URL después de mostrar el mensaje
            const nuevaUrl = window.location.pathname;
            window.history.replaceState({}, document.title, nuevaUrl);
        });
    }
});
  
