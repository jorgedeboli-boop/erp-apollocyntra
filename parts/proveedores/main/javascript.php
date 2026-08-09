<!-- JAVASCRIPT CUSTOM proveedor - main  -->

<!-- Scripts personalizados de proveedor main -->
<?php
$vTablesDatatablesLoad = filemtime(__DIR__ . '/tables-datatables-load.js');
$vTablesDatatablesLoadCuentasBancarias = filemtime(__DIR__ . '/tables-datatables-load-cuentas-bancarias.js');
$vTablesDatatablesLoadTarjetasBanco = filemtime(__DIR__ . '/tables-datatables-load-tarjetas-banco.js');
?>
<script src="parts/proveedores/main/tables-datatables-load.js?v=<?php echo $vTablesDatatablesLoad; ?>"></script>
<script src="parts/proveedores/main/tables-datatables-load-cuentas-bancarias.js?v=<?php echo $vTablesDatatablesLoadCuentasBancarias; ?>"></script>
<script src="parts/proveedores/main/tables-datatables-load-tarjetas-banco.js?v=<?php echo $vTablesDatatablesLoadTarjetasBanco; ?>"></script>

<script>
// Variables globales
let idProveedor = window.idProveedor || 0;

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
            fetch('parts/proveedores/main/poner_por_defecto_cuenta_bancaria.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `id_cuenta_banco=${idCuentaBanco}&id_proveedor=${idProveedor}`
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
    formData.append('id_proveedor', idProveedor);

    // Deshabilitar botón durante la creación
    const btnCrear = document.querySelector('#modalCrearCuenta .btn-primary');
    btnCrear.disabled = true;
    btnCrear.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Creando...';

    fetch('parts/proveedores/main/crear_cuenta_bancaria.php', {
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
            fetch('parts/proveedores/main/eliminar_cuenta_bancaria.php', {
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
    formData.append('id_proveedor', idProveedor);

    // Deshabilitar botón durante la creación
    const btnCrear = document.querySelector('#modalCrearTarjeta .btn-primary');
    btnCrear.disabled = true;
    btnCrear.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Creando...';

    fetch('parts/proveedores/main/crear_tarjeta_banco.php', {
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
            fetch('parts/proveedores/main/eliminar_tarjeta_banco.php', {
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
        text: 'Esta tarjeta será marcada como por defecto para el proveedor',
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
            formData.append('id_proveedor', idProveedor);

            fetch('parts/proveedores/main/poner_por_defecto_tarjeta_banco.php', {
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