<!-- JAVASCRIPT CUSTOM gasto - main  -->

<!-- Scripts personalizados de gasto main -->
<?php
$vTablesDatatablesLoad = filemtime(__DIR__ . '/../../../gastos/main/tables-datatables-load.js');
$vTablesDatatablesLoadCuentasBancarias = filemtime(__DIR__ . '/../../../gastos/main/tables-datatables-load-cuentas-bancarias.js');
$vTablesDatatablesLoadTarjetasBanco = filemtime(__DIR__ . '/../../../gastos/main/tables-datatables-load-tarjetas-banco.js');
?>
<script src="parts/gastos/main/tables-datatables-load.js?v=<?php echo $vTablesDatatablesLoad; ?>"></script>
<script src="parts/gastos/main/tables-datatables-load-cuentas-bancarias.js?v=<?php echo $vTablesDatatablesLoadCuentasBancarias; ?>"></script>
<script src="parts/gastos/main/tables-datatables-load-tarjetas-banco.js?v=<?php echo $vTablesDatatablesLoadTarjetasBanco; ?>"></script>

<script>
// Variables globales
let idEmpresa = window.idEmpresa || 0;

// Inicialización cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    // Cargar logotipo de la gasto al inicializar
    cargarLogotipoEmpresa();
});

/**
 * Función para cargar el logotipo de la gasto
 */
function cargarLogotipoEmpresa() {
    if (!idEmpresa) {
        console.error('ID de gasto no disponible');
        mostrarSinLogotipo();
        return;
    }

    const contenedor = document.getElementById('contenedor_logotipo_actual');
    if (!contenedor) {
        console.error('Contenedor de logotipo no encontrado');
        return;
    }

    // Realizar petición AJAX para obtener información del logotipo
    // console.log('Haciendo fetch a:', `parts/gastos/main/get_logotipo_info.php?id=${idEmpresa}`);

    fetch(`parts/gastos/main/get_logotipo_info.php?id=${idEmpresa}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Error en la respuesta del servidor');
            }
            return response.json();
        })
        .then(data => {
            console.log('Respuesta del servidor:', data);
            
            if (data.success && data.logotipo) {
                console.log('Mostrando logotipo:', data.logotipo);
                mostrarLogotipo(data.logotipo, idEmpresa);
            } else {
                console.log('No hay logotipo, mostrando mensaje');
                mostrarSinLogotipo();
            }
        })
        .catch(error => {
            console.error('Error al cargar logotipo:', error);
            mostrarSinLogotipo();
        });
}

/**
 * Función para mostrar el logotipo con opciones de gestión
 */
function mostrarLogotipo(nombreArchivo, idEmpresa) {
    const contenedor = document.getElementById('contenedor_logotipo_actual');
    // Usar ruta directa al archivo en photos/ con timestamp para evitar cache
    const timestamp = new Date().getTime();
    const imageUrl = `photos/${nombreArchivo}?t=${timestamp}`;
    console.log('URL de la imagen:', imageUrl);
    console.log('Nombre del archivo:', nombreArchivo);
    
    contenedor.innerHTML = `
        <div class="text-center">
            <img src="${imageUrl}" alt="Logotipo" class="img-fluid rounded" style="max-width: 400px;">
        </div>
    `;

    // Agregar el botón de eliminar al contenedor principal del logotipo
    const contenedorPrincipal = document.getElementById('contenedor_logotipo');
    const botonEliminarExistente = contenedorPrincipal.querySelector('.btn-eliminar-logotipo');
    
    if (!botonEliminarExistente) {
        const botonEliminar = document.createElement('button');
        botonEliminar.className = 'btn btn-icon btn-danger waves-effect waves-light position-absolute btn-eliminar-logotipo';
        botonEliminar.innerHTML = '<i class="icon-base ri ri-delete-bin-line"></i>';
        botonEliminar.onclick = () => eliminarLogotipo(idEmpresa);
        botonEliminar.style = 'top: 10px; right: 10px; z-index: 10;';
        //botonEliminar.title = 'Eliminar logotipo';
        contenedorPrincipal.appendChild(botonEliminar);
    }
}

/**
 * Función para mostrar mensaje cuando no hay logotipo
 */
function mostrarSinLogotipo() {
    const contenedor = document.getElementById('contenedor_logotipo_actual');
    contenedor.innerHTML = `
        <div class="text-center">
            <i class="icon-base ri ri-image-line icon-48px text-body-secondary mb-2"></i>
            <p class="mb-0">No hay logotipo disponible</p>
        </div>
    `;

    const contenedorPrincipal = document.getElementById('contenedor_logotipo');
    const botonEliminarExistente = contenedorPrincipal.querySelector('.btn-eliminar-logotipo');
    if (botonEliminarExistente) {
        botonEliminarExistente.remove();
    }
}

/**
 * Función para abrir modal de subir logotipo
 */
function abrirModalSubirLogotipo() {
    // Crear modal dinámicamente
    const modal = document.createElement('div');
    modal.className = 'modal fade';
    modal.id = 'modalSubirLogotipo';
    modal.setAttribute('tabindex', '-1');
    modal.setAttribute('aria-labelledby', 'modalSubirLogotipoLabel');
    modal.setAttribute('aria-hidden', 'true');

    modal.innerHTML = `
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Subir Logotipo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="formSubirLogotipo" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="logotipo" class="form-label">Seleccionar imagen</label>
                            <input type="file" class="form-control" id="logotipo" name="logotipo" accept="image/*" required>
                            <div class="form-text">Formatos permitidos: JPG, PNG, GIF. Máximo 5MB.</div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" onclick="subirLogotipo()">Subir</button>
                </div>
            </div>
        </div>
    `;

    // Agregar modal al body
    document.body.appendChild(modal);

    // Mostrar modal
    const modalInstance = new bootstrap.Modal(modal);
    modalInstance.show();

    // Limpiar modal cuando se oculte
    modal.addEventListener('hidden.bs.modal', function() {
        document.body.removeChild(modal);
    });
}

/**
 * Función para subir logotipo
 */
function subirLogotipo() {
    const fileInput = document.getElementById('logotipo');
    
    if (!fileInput.files || fileInput.files.length === 0) {
        Swal.fire('Error', 'Por favor selecciona un archivo', 'error');
        return;
    }

    const formData = new FormData();
    formData.append('logotipo', fileInput.files[0]);
    formData.append('id_gasto', idEmpresa);

    // Deshabilitar botón durante la subida
    const btnSubir = document.querySelector('#modalSubirLogotipo .btn-primary');
    btnSubir.disabled = true;
    btnSubir.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Subiendo...';

    // console.log('Enviando petición a subir_logotipo.php con id_gasto:', idEmpresa);

    fetch('parts/gastos/main/subir_logotipo.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Error en la respuesta del servidor');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            Swal.fire('¡Éxito!', 'Logotipo subido correctamente', 'success');
            bootstrap.Modal.getInstance(document.getElementById('modalSubirLogotipo')).hide();
            // Recargar logotipo con delay para asegurar que se procese
            setTimeout(() => {
                cargarLogotipoEmpresa();
            }, 500);
        } else {
            throw new Error(data.message || 'Error al subir logotipo');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        Swal.fire('Error', error.message || 'Error al subir logotipo', 'error');
    })
    .finally(() => {
        // Restaurar botón
        btnSubir.disabled = false;
        btnSubir.innerHTML = 'Subir';
    });
}

/**
 * Función para eliminar logotipo
 */
function eliminarLogotipo(idEmpresa) {
    Swal.fire({
        title: '¿Eliminar logotipo?',
        text: 'Esta acción no se puede deshacer',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            // console.log('Enviando petición a eliminar_logotipo.php con id_gasto:', idEmpresa);

            fetch('parts/gastos/main/eliminar_logotipo.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `id_gasto=${idEmpresa}`
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Error en la respuesta del servidor');
                }
                return response.json();
            })
            .then(data => {
                            if (data.success) {
                Swal.fire('¡Éxito!', 'Logotipo eliminado correctamente', 'success');
                // Recargar logotipo con delay para asegurar que se procese
                setTimeout(() => {
                    cargarLogotipoEmpresa();
                }, 500);
            } else {
                    throw new Error(data.message || 'Error al eliminar logotipo');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire('Error', error.message || 'Error al eliminar logotipo', 'error');
            });
        }
    });
}

/**
 * Función para poner una cuenta bancaria por defecto
 */
function ponerPorDefectoCuentaBancaria(idCuentaBanco) {
    Swal.fire({
        title: '¿Poner por defecto?',
        text: 'Esta cuenta será marcada como principal y las demás se marcarán como normales',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, poner por defecto',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch('parts/gastos/main/poner_por_defecto_cuenta_bancaria.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `id_cuenta_banco=${idCuentaBanco}&id_gasto=${idEmpresa}`
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Error en la respuesta del servidor');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    Swal.fire('¡Éxito!', 'Cuenta bancaria marcada como por defecto', 'success');
                    // Recargar tabla
                    if (window.tableCuentasBancarias) {
                        window.tableCuentasBancarias.ajax.reload();
                    }
                } else {
                    throw new Error(data.message || 'Error al marcar como por defecto');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire('Error', error.message || 'Error al marcar como por defecto', 'error');
            });
        }
    });
}

/**
 * Función para abrir modal de crear cuenta bancaria
 */
function abrirModalCrearCuenta() {
    // Crear modal dinámicamente
    const modal = document.createElement('div');
    modal.className = 'modal fade';
    modal.id = 'modalCrearCuenta';
    modal.setAttribute('tabindex', '-1');
    modal.setAttribute('aria-labelledby', 'modalCrearCuentaLabel');
    modal.setAttribute('aria-hidden', 'true');

    modal.innerHTML = `
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Crear Nueva Cuenta Bancaria</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="formCrearCuenta">
                        <div class="mb-3">
                            <label for="numerocuenta" class="form-label">Número de Cuenta *</label>
                            <input type="text" class="form-control" id="numerocuenta" name="numerocuenta" required>
                        </div>
                        <div class="mb-3">
                            <label for="banco_cuenta" class="form-label">Nombre del Banco *</label>
                            <input type="text" class="form-control" id="banco_cuenta" name="banco_cuenta" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" onclick="crearCuentaBancaria()">Crear Cuenta</button>
                </div>
            </div>
        </div>
    `;

    // Agregar modal al body
    document.body.appendChild(modal);

    // Mostrar modal
    const modalInstance = new bootstrap.Modal(modal);
    modalInstance.show();

    // Limpiar modal cuando se oculte
    modal.addEventListener('hidden.bs.modal', function() {
        document.body.removeChild(modal);
    });
}

/**
 * Función para crear cuenta bancaria
 */
function crearCuentaBancaria() {
    const numerocuenta = document.getElementById('numerocuenta').value.trim();
    const banco_cuenta = document.getElementById('banco_cuenta').value.trim();
    
    if (!numerocuenta || !banco_cuenta) {
        Swal.fire('Error', 'Por favor completa todos los campos obligatorios', 'error');
        return;
    }

    const formData = new FormData();
    formData.append('numerocuenta', numerocuenta);
    formData.append('banco_cuenta', banco_cuenta);
    formData.append('id_gasto', idEmpresa);

    // Deshabilitar botón durante la creación
    const btnCrear = document.querySelector('#modalCrearCuenta .btn-primary');
    btnCrear.disabled = true;
    btnCrear.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Creando...';

    fetch('parts/gastos/main/crear_cuenta_bancaria.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Error en la respuesta del servidor');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            Swal.fire('¡Éxito!', 'Cuenta bancaria creada correctamente', 'success');
            bootstrap.Modal.getInstance(document.getElementById('modalCrearCuenta')).hide();
            // Recargar tabla
            if (window.tableCuentasBancarias) {
                window.tableCuentasBancarias.ajax.reload();
            }
        } else {
            throw new Error(data.message || 'Error al crear la cuenta bancaria');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        Swal.fire('Error', error.message || 'Error al crear la cuenta bancaria', 'error');
    })
    .finally(() => {
        // Restaurar botón
        btnCrear.disabled = false;
        btnCrear.innerHTML = 'Crear Cuenta';
    });
}

/**
 * Función para eliminar cuenta bancaria
 */
function eliminarCuentaBancaria(idCuentaBanco) {
    Swal.fire({
        title: '¿Eliminar cuenta bancaria?',
        text: 'Esta acción no se puede deshacer',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch('parts/gastos/main/eliminar_cuenta_bancaria.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `id_cuenta_banco=${idCuentaBanco}`
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Error en la respuesta del servidor');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    Swal.fire('¡Éxito!', 'Cuenta bancaria eliminada correctamente', 'success');
                    // Recargar tabla
                    if (window.tableCuentasBancarias) {
                        window.tableCuentasBancarias.ajax.reload();
                    }
                } else {
                    throw new Error(data.message || 'Error al eliminar la cuenta bancaria');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire('Error', error.message || 'Error al eliminar la cuenta bancaria', 'error');
            });
        }
    });
}

/**
 * Función para abrir modal de crear tarjeta banco
 */
function abrirModalCrearTarjeta() {
    // Crear modal dinámicamente
    const modal = document.createElement('div');
    modal.className = 'modal fade';
    modal.id = 'modalCrearTarjeta';
    modal.setAttribute('tabindex', '-1');
    modal.setAttribute('aria-labelledby', 'modalCrearTarjetaLabel');
    modal.setAttribute('aria-hidden', 'true');

    modal.innerHTML = `
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Crear Nueva Tarjeta Banco</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="formCrearTarjeta">
                        <div class="mb-3">
                            <label for="numerotarjeta" class="form-label">Número de Tarjeta *</label>
                            <input type="text" class="form-control" id="numerotarjeta" name="numerotarjeta" required>
                        </div>
                        <div class="mb-3">
                            <label for="banco_tarjeta" class="form-label">Nombre del Banco *</label>
                            <input type="text" class="form-control" id="banco_tarjeta" name="banco_tarjeta" required>
                        </div>

                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" onclick="crearTarjetaBanco()">Crear Tarjeta</button>
                </div>
            </div>
        </div>
    `;

    // Agregar modal al body
    document.body.appendChild(modal);

    // Mostrar modal
    const modalInstance = new bootstrap.Modal(modal);
    modalInstance.show();

    // Limpiar modal cuando se oculte
    modal.addEventListener('hidden.bs.modal', function() {
        document.body.removeChild(modal);
    });
}

/**
 * Función para crear tarjeta banco
 */
function crearTarjetaBanco() {
    const numerotarjeta = document.getElementById('numerotarjeta').value.trim();
    const banco_tarjeta = document.getElementById('banco_tarjeta').value.trim();
    
    if (!numerotarjeta || !banco_tarjeta) {
        Swal.fire('Error', 'Por favor completa todos los campos obligatorios', 'error');
        return;
    }

    const formData = new FormData();
    formData.append('numerotarjeta', numerotarjeta);
    formData.append('banco_tarjeta', banco_tarjeta);
    formData.append('id_gasto', idEmpresa);

    // Deshabilitar botón durante la creación
    const btnCrear = document.querySelector('#modalCrearTarjeta .btn-primary');
    btnCrear.disabled = true;
    btnCrear.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Creando...';

    fetch('parts/gastos/main/crear_tarjeta_banco.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Error en la respuesta del servidor');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            Swal.fire('¡Éxito!', 'Tarjeta banco creada correctamente', 'success');
            bootstrap.Modal.getInstance(document.getElementById('modalCrearTarjeta')).hide();
            // Recargar tabla
            if (window.tableTarjetasBanco) {
                window.tableTarjetasBanco.ajax.reload();
            }
        } else {
            throw new Error(data.message || 'Error al crear la tarjeta banco');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        Swal.fire('Error', error.message || 'Error al crear la tarjeta banco', 'error');
    })
    .finally(() => {
        // Restaurar botón
        btnCrear.disabled = false;
        btnCrear.innerHTML = 'Crear Tarjeta';
    });
}

/**
 * Función para eliminar tarjeta banco
 */
function eliminarTarjetaBanco(idTarjetaBanco) {
    Swal.fire({
        title: '¿Eliminar tarjeta banco?',
        text: 'Esta acción no se puede deshacer',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch('parts/gastos/main/eliminar_tarjeta_banco.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `id_tarjeta_banco=${idTarjetaBanco}`
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Error en la respuesta del servidor');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    Swal.fire('¡Éxito!', 'Tarjeta banco eliminada correctamente', 'success');
                    // Recargar tabla
                    if (window.tableTarjetasBanco) {
                        window.tableTarjetasBanco.ajax.reload();
                    }
                } else {
                    throw new Error(data.message || 'Error al eliminar la tarjeta banco');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire('Error', error.message || 'Error al eliminar la tarjeta banco', 'error');
            });
        }
    });
}

/**
 * Función para poner por defecto una tarjeta banco
 */
function ponerPorDefectoTarjetaBanco(idTarjetaBanco) {
    Swal.fire({
        title: '¿Poner por defecto?',
        text: 'Esta tarjeta será marcada como por defecto para la gasto',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, poner por defecto',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            const formData = new FormData();
            formData.append('id_tarjeta_banco', idTarjetaBanco);
            formData.append('id_gasto', idEmpresa);

            fetch('parts/gastos/main/poner_por_defecto_tarjeta_banco.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Error en la respuesta del servidor');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    Swal.fire('¡Éxito!', 'Tarjeta banco marcada como por defecto correctamente', 'success');
                    // Recargar tabla
                    if (window.tableTarjetasBanco) {
                        window.tableTarjetasBanco.ajax.reload();
                    }
                } else {
                    throw new Error(data.message || 'Error al marcar la tarjeta como por defecto');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire('Error', error.message || 'Error al marcar la tarjeta como por defecto', 'error');
            });
        }
    });
}

</script>