<!-- JAVASCRIPT CUSTOM gasto - main  -->
<?php require_once __DIR__ . '/../../../camera/render-config-script.php'; ?>
<?php
$vQrcodeMin = filemtime(__DIR__ . '/../../../js/qrcode.min.js');
$vCameraQr = filemtime(__DIR__ . '/../../../camera/js/camera-qr.js');
$vCameraDocPanel = filemtime(__DIR__ . '/../../../camera/js/camera-doc-panel.js');
$vTablesDatatablesLoad = filemtime(__DIR__ . '/tables-datatables-load.js');
$vTablesDatatablesLoadCuentasBancarias = filemtime(__DIR__ . '/tables-datatables-load-cuentas-bancarias.js');
$vTablesDatatablesLoadTarjetasBanco = filemtime(__DIR__ . '/tables-datatables-load-tarjetas-banco.js');
?>
<script src="js/qrcode.min.js?v=<?php echo $vQrcodeMin; ?>"></script>
<script src="camera/js/camera-qr.js?v=<?php echo $vCameraQr; ?>"></script>
<script src="camera/js/camera-doc-panel.js?v=<?php echo $vCameraDocPanel; ?>"></script>

<!-- Scripts personalizados de gasto main -->
<script src="parts/gastos/main/tables-datatables-load.js?v=<?php echo $vTablesDatatablesLoad; ?>"></script>
<script src="parts/gastos/main/tables-datatables-load-cuentas-bancarias.js?v=<?php echo $vTablesDatatablesLoadCuentasBancarias; ?>"></script>
<script src="parts/gastos/main/tables-datatables-load-tarjetas-banco.js?v=<?php echo $vTablesDatatablesLoadTarjetasBanco; ?>"></script>

<script>
// IDs del gasto expuestos en content.php
let idGasto = Number(window.idGasto || 0) || 0;
let idEmpresaGasto = Number(window.idEmpresaGasto || 0) || 0;
let idSucursalGasto = Number(window.idSucursalGasto || 0) || 0;
let cantidadFotosGasto = 0;
let intervalo_fotos_gasto = null;

document.addEventListener('DOMContentLoaded', function() {
    idGasto = Number(window.idGasto || 0) || 0;
    idEmpresaGasto = Number(window.idEmpresaGasto || 0) || 0;
    idSucursalGasto = Number(window.idSucursalGasto || 0) || 0;
    if (document.getElementById('contenedor_imagenes_gasto') && idGasto) {
        cargarImagenesGasto(idGasto);
    }
});

function cargarImagenesGasto(idGasto) {
    const contenedor = document.getElementById('contenedor_imagenes_gasto');
    const sinImagenes = document.getElementById('sin_imagenes_gasto');
    const loading = document.getElementById('loading_imagenes_gasto');
    if (!contenedor || !sinImagenes || !loading) {
        return;
    }

    loading.style.display = 'block';
    contenedor.innerHTML = '';
    sinImagenes.style.display = 'none';

    const fdImg = new FormData();
    fdImg.append('tipo', 'gasto');
    fdImg.append('id', String(idGasto));

    fetch('camera/api/list_imagenes.php', {
        method: 'POST',
        body: fdImg
    })
        .then(response => response.json())
        .then(data => {
            loading.style.display = 'none';
            if (data.success && data.imagenes && data.imagenes.length > 0) {
                cantidadFotosGasto = data.imagenes.length;
                mostrarImagenesGasto(data.imagenes);
            } else {
                cantidadFotosGasto = 0;
                sinImagenes.style.display = 'block';
            }
        })
        .catch(error => {
            console.error('Error al cargar imágenes del gasto:', error);
            loading.style.display = 'none';
            sinImagenes.style.display = 'block';
        });
}

function mostrarImagenesGasto(imagenes) {
    const contenedor = document.getElementById('contenedor_imagenes_gasto');
    if (!contenedor) {
        return;
    }
    contenedor.innerHTML = '';

    imagenes.forEach(imagen => {
        const columna = document.createElement('div');
        columna.className = 'col-md-6';
        const esPDF = imagen.foto.toLowerCase().endsWith('.pdf');
        const iconoArchivo = esPDF ? 'ri-file-text-line' : 'ri-image-line';
        const altText = esPDF ? 'Documento PDF del gasto' : 'Documento del gasto';

        columna.innerHTML = `
            <div class="card h-100">
                <div class="card-body p-0">
                    <div class="position-relative">
                        ${esPDF
            ? `<div class="pdf-preview" onclick="descargarPDFGasto('photos/${imagen.foto}', '${imagen.foto}')">
                                <i class="icon-base ri ${iconoArchivo} icon-48px text-primary"></i>
                                <div class="mt-2">
                                    <small class="text-muted">${imagen.foto}</small>
                                </div>
                            </div>`
            : `<img src="photos/${imagen.foto}"
                                 alt="${altText}"
                                 class="img-fluid w-100"
                                 style="cursor: pointer;"
                                 onclick="ampliarImagenGasto('photos/${imagen.foto}')">`
        }
                        <div class="position-absolute top-0 end-0 p-2">
                            <button type="button"
                                    class="btn btn-danger btn-sm"
                                    onclick="eliminarFotoGasto(${imagen.id_foto}, '${imagen.foto}')"
                                    title="Eliminar">
                                <i class="icon-base ri ri-delete-bin-line icon-14px"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        contenedor.appendChild(columna);
    });
}

function abrirModalSubirFotoGasto() {
    const form = document.getElementById('formSubirFotoGasto');
    if (form) {
        form.reset();
    }
    const modalEl = document.getElementById('modalSubirFotoGasto');
    if (modalEl) {
        const modal = new bootstrap.Modal(modalEl);
        modal.show();
    }
}

function subirFotoGasto() {
    const archivoInput = document.getElementById('archivo_foto_gasto');
    const modalEl = document.getElementById('modalSubirFotoGasto');
    const btnSubir = modalEl ? modalEl.querySelector('.btn-primary') : null;
    const spinner = btnSubir ? btnSubir.querySelector('.spinner-border') : null;

    if (!archivoInput || !archivoInput.files[0]) {
        Swal.fire({
            title: 'Error',
            text: 'Por favor selecciona un archivo',
            icon: 'error',
            confirmButtonText: 'Aceptar'
        });
        return;
    }

    const archivo = archivoInput.files[0];
    if (archivo.size > 5 * 1024 * 1024) {
        Swal.fire({
            title: 'Error',
            text: 'El archivo es demasiado grande. Máximo 5MB',
            icon: 'error',
            confirmButtonText: 'Aceptar'
        });
        return;
    }

    const formatosPermitidos = ['jpg', 'jpeg', 'gif', 'png', 'pdf'];
    const extension = archivo.name.split('.').pop().toLowerCase();
    if (!formatosPermitidos.includes(extension)) {
        Swal.fire({
            title: 'Error',
            text: 'Formato de archivo no permitido. Solo JPG, JPEG, GIF, PNG y PDF',
            icon: 'error',
            confirmButtonText: 'Aceptar'
        });
        return;
    }

    const formData = new FormData();
    formData.append('archivo_foto', archivo);
    formData.append('id_gasto', idGasto);
    formData.append('id_empresa', idEmpresaGasto);
    if (idSucursalGasto) {
        formData.append('id_sucursal', idSucursalGasto);
    }

    if (btnSubir) {
        btnSubir.disabled = true;
    }
    if (spinner) {
        spinner.classList.remove('d-none');
    }

    fetch('parts/gastos/main/subir_foto.php', {
        method: 'POST',
        body: formData
    })
        .then(function (response) {
            return response.text().then(function (text) {
                if (!text) {
                    throw new Error('Respuesta vacía del servidor (HTTP ' + response.status + ')');
                }
                try {
                    return JSON.parse(text);
                } catch (e) {
                    throw new Error('Respuesta no válida del servidor: ' + text.substring(0, 200));
                }
            });
        })
        .then(data => {
            if (btnSubir) {
                btnSubir.disabled = false;
            }
            if (spinner) {
                spinner.classList.add('d-none');
            }
            if (data.success) {
                const inst = modalEl ? bootstrap.Modal.getInstance(modalEl) : null;
                if (inst) {
                    inst.hide();
                }
                const esImagen = ['jpg', 'jpeg', 'gif', 'png'].includes(extension);
                const mensajeExtra = esImagen ? ' (redimensionada automáticamente a 800px de ancho máximo)' : '';
                Swal.fire({
                    title: '¡Archivo subido!',
                    text: (data.message || '') + mensajeExtra,
                    icon: 'success',
                    confirmButtonText: 'Aceptar',
                    timer: 3000,
                    timerProgressBar: true
                });
                cargarImagenesGasto(idGasto);
            } else {
                throw new Error(data.error || 'Error desconocido');
            }
        })
        .catch(error => {
            console.error('Error al subir foto del gasto:', error);
            if (btnSubir) {
                btnSubir.disabled = false;
            }
            if (spinner) {
                spinner.classList.add('d-none');
            }
            Swal.fire({
                title: 'Error',
                text: 'No se pudo subir el archivo: ' + error.message,
                icon: 'error',
                confirmButtonText: 'Aceptar'
            });
        });
}

function ampliarImagenGasto(rutaImagen) {
    const img = document.getElementById('imagen_ampliada_gasto');
    const modalEl = document.getElementById('modalAmpliarImagenGasto');
    if (!img || !modalEl) {
        return;
    }
    img.src = rutaImagen;
    const modal = new bootstrap.Modal(modalEl);
    modal.show();
}

function descargarPDFGasto(url, filename) {
    Swal.fire({
        title: 'Descargando PDF...',
        text: 'Preparando descarga de ' + filename,
        icon: 'info',
        timer: 2000,
        timerProgressBar: true,
        showConfirmButton: false
    });
    const link = document.createElement('a');
    link.href = url;
    link.download = filename;
    link.target = '_blank';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    setTimeout(() => {
        Swal.fire({
            title: '¡Descarga iniciada!',
            text: 'El PDF se está descargando',
            icon: 'success',
            timer: 3000,
            timerProgressBar: true,
            showConfirmButton: false
        });
    }, 1000);
}

function eliminarFotoGasto(idFoto, nombreFoto) {
    Swal.fire({
        title: 'Confirmar eliminación',
        text: '¿Estás seguro de que quieres eliminar este documento? Esta acción no se puede deshacer.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (!result.isConfirmed) {
            return;
        }
        const body = 'id_foto=' + idFoto +
            '&nombre_foto=' + encodeURIComponent(nombreFoto) +
            '&id_gasto=' + idGasto;
        fetch('parts/gastos/main/eliminar_foto.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: body
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        title: '¡Eliminado!',
                        text: data.message || 'Documento eliminado',
                        icon: 'success',
                        confirmButtonText: 'Aceptar',
                        timer: 3000,
                        timerProgressBar: true
                    });
                    cargarImagenesGasto(idGasto);
                } else {
                    throw new Error(data.error || 'Error desconocido');
                }
            })
            .catch(error => {
                console.error('Error al eliminar foto del gasto:', error);
                Swal.fire({
                    title: 'Error',
                    text: 'No se pudo eliminar: ' + error.message,
                    icon: 'error',
                    confirmButtonText: 'Aceptar'
                });
            });
    });
}

function iniciarVerificacionFotosGasto() {
    if (!intervalo_fotos_gasto) {
        intervalo_fotos_gasto = setInterval(function () {
            verificarCambiosFotosGasto();
        }, 22000);
    }
}

function detenerVerificacionFotosGasto() {
    if (intervalo_fotos_gasto) {
        clearInterval(intervalo_fotos_gasto);
        intervalo_fotos_gasto = null;
    }
}

function verificarCambiosFotosGasto() {
    if (!idGasto) {
        return;
    }
    fetch('parts/gastos/main/get_cantidad_fotos.php?tipo=gasto&id_item=' + idGasto)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.cantidad !== cantidadFotosGasto) {
                cargarImagenesGasto(idGasto);
                detenerVerificacionFotosGasto();
            }
        })
        .catch(function () {
            detenerVerificacionFotosGasto();
        });
}

function abrirModalFotoMovilGasto() {
    const idSucursalQr = Number(idSucursalGasto || window.usuarioSucursal || 0) || 0;
    if (window.CameraDocPanel && typeof window.CameraDocPanel.open === 'function') {
        window.CameraDocPanel.open({
            tipo: 'gasto',
            id: idGasto,
            idSucursal: idSucursalQr,
            idEmpresa: idEmpresaGasto
        }).catch(function (err) {
            console.error('CameraDocPanel', err);
        });
    }
    iniciarVerificacionFotosGasto();
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
                body: `id_cuenta_banco=${idCuentaBanco}&id_gasto=${idGasto}`
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
    formData.append('id_gasto', idGasto);

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
    formData.append('id_gasto', idGasto);

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
            formData.append('id_gasto', idGasto);

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