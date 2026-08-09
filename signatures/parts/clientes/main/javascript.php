<?php require_once __DIR__ . '/../../../camera/render-config-script.php'; ?>
<!-- JAVASCRIPT CUSTOM cliente  -->
<?php
$vQrcodeMin = filemtime(__DIR__ . '/../../../js/qrcode.min.js');
$vCameraQr = filemtime(__DIR__ . '/../../../camera/js/camera-qr.js');
$vCameraDocPanel = filemtime(__DIR__ . '/../../../camera/js/camera-doc-panel.js');
?>
<script src="js/qrcode.min.js?v=<?php echo $vQrcodeMin; ?>"></script>
<script src="camera/js/camera-qr.js?v=<?php echo $vCameraQr; ?>"></script>
<script src="camera/js/camera-doc-panel.js?v=<?php echo $vCameraDocPanel; ?>"></script>
<script>
/**
 * Función para cambiar el estado del cliente (habilitar/deshabilitar)
 */
function toggleEstadoCliente(idCliente) {
    const btnToggle = document.getElementById('btnToggleEstado');
    const btnEditar = btnToggle.previousElementSibling;
    
    // Obtener el estado actual del botón
    const estadoActual = btnToggle.classList.contains('btn-danger') ? 'habilitado' : 'deshabilitado';
    const nuevoEstado = estadoActual === 'habilitado' ? 'deshabilitado' : 'habilitado';
    
    // Confirmar la acción
    const mensaje = nuevoEstado === 'habilitado' 
        ? '¿Estás seguro de que quieres habilitar este cliente?' 
        : '¿Estás seguro de que quieres deshabilitar este cliente?';
    
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
            
            // Preparar datos para la petición
            const formData = new FormData();
            formData.append('id_cliente', idCliente);
            formData.append('nuevo_estado', nuevoEstado);
            
            // Realizar petición AJAX
            fetch('parts/clientes/main/toggle_estado.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Actualizar botón
                    actualizarBotonEstado(nuevoEstado);
                    
                    // Mostrar mensaje de éxito
                    Swal.fire({
                        title: '¡Estado actualizado!',
                        text: data.message,
                        icon: 'success',
                        confirmButtonText: 'Aceptar',
                        confirmButtonColor: '#198754',
                        timer: 3000,
                        timerProgressBar: true
                    });
                    
                    // Actualizar el estado en la página
                    actualizarEstadoEnPagina(nuevoEstado);
                    
                } else {
                    throw new Error(data.error || 'Error desconocido');
                }
            })
            .catch(error => {
                console.error('Error en toggleEstadoCliente:', error);
                
                // Mostrar mensaje de error
                Swal.fire({
                    title: 'Error',
                    text: 'No se pudo cambiar el estado: ' + error.message,
                    icon: 'error',
                    confirmButtonText: 'Aceptar',
                    confirmButtonColor: '#dc3545'
                });
                
                // Restaurar botón
                restaurarBotonEstado(estadoActual);
            });
        }
    });
}

/**
 * Actualizar el botón según el nuevo estado
 */
function actualizarBotonEstado(nuevoEstado) {
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
function restaurarBotonEstado(estadoAnterior) {
    const btnToggle = document.getElementById('btnToggleEstado');
    
    if (estadoAnterior === 'habilitado') {
        btnToggle.className = 'btn btn-danger waves-effect waves-light';
        btnToggle.innerHTML = '<i class="icon-base ri ri-user-forbid-line icon-16px me-2"></i>Deshabilitar';
    } else {
        btnToggle.className = 'btn btn-success waves-effect waves-light';
        btnToggle.innerHTML = '<i class="icon-base ri ri-user-check-line icon-16px me-2"></i>Habilitar';
    }
    
    btnToggle.disabled = false;
}

/**
 * Actualizar el estado mostrado en la página
 */
function actualizarEstadoEnPagina(nuevoEstado) {
    // Buscar y actualizar el badge del estado en la información personal
    const estadoElements = document.querySelectorAll('.fw-medium');
    estadoElements.forEach(element => {
        if (element.textContent.includes('Estado:')) {
            const spanEstado = element.nextElementSibling;
            if (spanEstado && spanEstado.tagName === 'SPAN') {
                // Actualizar el badge del estado
                const badge = spanEstado.querySelector('.badge');
                if (badge) {
                    // Cambiar la clase del badge
                    badge.className = nuevoEstado === 'habilitado' 
                        ? 'badge bg-label-success me-2 ms-2 rounded-pill' 
                        : 'badge bg-label-danger me-2 ms-2 rounded-pill';
                    
                    // Cambiar el texto del estado
                    badge.textContent = nuevoEstado === 'habilitado' ? 'habilitado' : 'deshabilitado';
                }
            }
        }
    });
}

/**
 * Cargar imágenes del cliente al cargar la página
 */
document.addEventListener('DOMContentLoaded', function() {
    const idCliente = <?php echo $id_cliente ?: 0; ?>;
    const idSucursalCliente = <?php echo (int)($cliente['sucursal'] ?? 0); ?>;
    if (idCliente > 0) {
        cargarImagenesCliente(idCliente);
    }

    // Inicialización lazy de DataTables en pestañas
    initTablasCliente(idCliente);

    window.__clienteQR = { idCliente, idSucursalCliente };

    if (window.CameraQR && typeof window.CameraQR.init === 'function') {
        window.CameraQR.init({
            callbacks: {
                onTokenUtilizado: function (tipo_qr) {
                    if (tipo_qr !== 'cliente') {
                        return;
                    }
                    var ctx = window.__clienteQR || { idCliente: 0 };
                    if (ctx.idCliente > 0) {
                        cargarImagenesCliente(ctx.idCliente);
                    }
                }
            }
        });
    }
});

function abrirModalFotoMovilCliente() {
    const ctx = window.__clienteQR || { idCliente: 0, idSucursalCliente: 0 };
    if (!ctx.idCliente || !ctx.idSucursalCliente) {
        return;
    }
    if (window.CameraDocPanel && typeof window.CameraDocPanel.open === 'function') {
        window.CameraDocPanel.open({
            tipo: 'cliente',
            id: ctx.idCliente,
            idSucursal: ctx.idSucursalCliente
        }).catch(function (err) {
            console.error('CameraDocPanel', err);
            Swal.fire({
                title: 'Error',
                text: err && err.message ? err.message : 'No se pudo abrir el visor de cámara',
                icon: 'error',
                confirmButtonText: 'Aceptar'
            });
        });
    }
}

/**
 * Guarda observaciones del cliente en datos_clientes (AJAX).
 */
function actualizarComentariosCliente(idCliente) {
    if (!idCliente || idCliente <= 0) {
        Swal.fire({ title: 'Error', text: 'ID de cliente no válido', icon: 'error', confirmButtonText: 'Aceptar' });
        return;
    }
    const ta = document.getElementById('textarea_comentarios_cliente');
    const btn = document.getElementById('btnActualizarComentariosCliente');
    if (!ta || !btn) return;

    const observaciones = ta.value;
    const formData = new FormData();
    formData.append('id_cliente', String(idCliente));
    formData.append('observaciones', observaciones);

    btn.disabled = true;
    const labelOriginal = btn.innerHTML;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Guardando...';

    fetch('parts/clientes/main/actualizar_observaciones_cliente.php', {
        method: 'POST',
        body: formData
    })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            btn.disabled = false;
            btn.innerHTML = labelOriginal;
            if (data && data.success) {
                Swal.fire({
                    title: 'Guardado',
                    text: data.message || 'Comentarios actualizados.',
                    icon: 'success',
                    confirmButtonText: 'Aceptar',
                    timer: 2500,
                    timerProgressBar: true
                });
            } else {
                throw new Error((data && data.error) ? data.error : 'Error desconocido');
            }
        })
        .catch(function (err) {
            btn.disabled = false;
            btn.innerHTML = labelOriginal;
            Swal.fire({
                title: 'Error',
                text: err.message || 'No se pudieron guardar los comentarios.',
                icon: 'error',
                confirmButtonText: 'Aceptar'
            });
        });
}

/**
 * QR / token: módulo central camera/js/camera-qr.js (generarNuevoQR global).
 */

function initTablasCliente(idCliente) {
    if (!idCliente || idCliente <= 0) return;

    const state = {
        lotes: null,
        empenos: null,
        ventas: null
    };

    // Layout (DataTables v2): buscador a la derecha y paginación a la derecha.
    // Sin selector de longitud (dt-length).
    const DT_LAYOUT_RIGHT_SEARCH_RIGHT_PAGING = {
        topEnd: {
            features: [
                {
                    search: {
                        placeholder: 'Buscar...',
                        text: '_INPUT_'
                    }
                }
            ]
        },
        bottomStart: {
            rowClass: 'row mx-0 justify-content-between w-100',
            features: ['info']
        },
        bottomEnd: 'paging'
    };

    const initLotes = () => {
        const el = document.getElementById('tabla_lotes_cliente');
        if (!el || state.lotes) return;

        state.lotes = new DataTable(el, {
            processing: true,
            serverSide: true,
            deferRender: true,
            searchDelay: 400,
            lengthChange: false,
            language: DATATABLES_SPANISH,
            layout: DT_LAYOUT_RIGHT_SEARCH_RIGHT_PAGING,
            ajax: {
                url: 'parts/clientes/main/datatable_lotes_cliente.php',
                type: 'POST',
                data: function (d) {
                    d.id_cliente = idCliente;
                    return d;
                }
            },
            columns: [
                { data: 0 }, // id_lote
                { data: 1 }, // identificador
                { data: 2 }, // tipo
                { data: 3 }, // peso
                { data: 4 }, // compra
                { data: 5 }, // fecha
                { data: 6 }, // sucursal
                { data: 7 }  // estado
            ],
            order: [[5, 'desc']]
        });
    };

    const initEmpenos = () => {
        const el = document.getElementById('tabla_empenos_cliente');
        if (!el || state.empenos) return;

        state.empenos = new DataTable(el, {
            processing: true,
            serverSide: true,
            deferRender: true,
            searchDelay: 400,
            lengthChange: false,
            language: DATATABLES_SPANISH,
            layout: DT_LAYOUT_RIGHT_SEARCH_RIGHT_PAGING,
            ajax: {
                url: 'parts/clientes/main/datatable_empenos_cliente.php',
                type: 'POST',
                data: function (d) {
                    d.id_cliente = idCliente;
                    return d;
                }
            },
            columns: [
                { data: 0 }, // id_lote
                { data: 1 }, // identificador
                { data: 2 }, // tipo
                { data: 3 }, // importe
                { data: 4 }, // fecha
                { data: 5 }, // vencimiento
                { data: 6 }, // sucursal
                { data: 7 }  // estado
            ],
            order: [[4, 'desc']]
        });
    };

    const initVentas = () => {
        const el = document.getElementById('tabla_ventas_cliente');
        if (!el || state.ventas) return;

        state.ventas = new DataTable(el, {
            processing: true,
            serverSide: true,
            deferRender: true,
            searchDelay: 400,
            lengthChange: false,
            language: DATATABLES_SPANISH,
            layout: DT_LAYOUT_RIGHT_SEARCH_RIGHT_PAGING,
            ajax: {
                url: 'parts/clientes/main/datatable_ventas_cliente.php',
                type: 'POST',
                data: function (d) {
                    d.id_cliente = idCliente;
                    return d;
                }
            },
            columns: [
                { data: 0 }, // Nº venta + link
                { data: 1 }, // total
                { data: 2 }, // fecha
                { data: 3 }, // sucursal
                { data: 4 }, // estado
                { data: 5 }, // plazos
                { data: 6 }, // pago
                { data: 7, visible: false } // id hidden
            ],
            order: [[2, 'desc']]
        });
    };

    // Si entran directo con hash o ya se abrió una pestaña, init en shown.
    document.addEventListener('shown.bs.tab', function (event) {
        const target = event.target && event.target.id ? event.target.id : '';
        if (target === 'tab-lotes') initLotes();
        if (target === 'tab-empenos') initEmpenos();
        if (target === 'tab-ventas') initVentas();
    });

    // Inicializar la tabla activa si no es Perfil por alguna razón
    const active = document.querySelector('.nav.nav-pills [data-bs-toggle=\"tab\"].active');
    if (active && active.id === 'tab-lotes') initLotes();
    if (active && active.id === 'tab-empenos') initEmpenos();
    if (active && active.id === 'tab-ventas') initVentas();
}

/**
 * Cargar las imágenes del cliente desde la base de datos
 */
function cargarImagenesCliente(idCliente) {
    const contenedor = document.getElementById('contenedor_imagenes');
    const sinImagenes = document.getElementById('sin_imagenes');
    const loading = document.getElementById('loading_imagenes');
    
    // Mostrar loading
    loading.style.display = 'block';
    contenedor.innerHTML = '';
    sinImagenes.style.display = 'none';
    
    const fdImg = new FormData();
    fdImg.append('tipo', 'cliente');
    fdImg.append('id', String(idCliente));

    fetch('camera/api/list_imagenes.php', {
        method: 'POST',
        body: fdImg
    })
    .then(response => response.json())
    .then(data => {
        loading.style.display = 'none';
        
        if (data.success && data.imagenes && data.imagenes.length > 0) {
            mostrarImagenes(data.imagenes);
        } else {
            sinImagenes.style.display = 'block';
        }
    })
    .catch(error => {
        console.error('Error al cargar imágenes:', error);
        loading.style.display = 'none';
        sinImagenes.style.display = 'block';
    });
}

/**
 * Mostrar las imágenes en el contenedor
 */
function mostrarImagenes(imagenes) {
    const contenedor = document.getElementById('contenedor_imagenes');
    contenedor.innerHTML = '';
    
    imagenes.forEach(imagen => {
        const columna = document.createElement('div');
        columna.className = 'col-md-6';

        const esPDF = imagen.foto.toLowerCase().endsWith('.pdf');
        const iconoArchivo = esPDF ? 'ri-file-text-line' : 'ri-image-line';
        const altText = esPDF ? 'Documento PDF del cliente' : 'Documento del cliente';
        const idFoto = Number(imagen.id_foto) || 0;
        const nombreFoto = String(imagen.nombre_foto || imagen.foto || '');
        const puedeEliminar = idFoto > 0 && nombreFoto !== '';

        columna.innerHTML = `
            <div class="card h-100">
                <div class="card-body p-2">
                    <div class="position-relative">
                        ${puedeEliminar ? '<button type="button" class="btn btn-sm btn-icon btn-danger rounded-circle position-absolute top-0 end-0 m-1 shadow-sm btn-eliminar-foto-cliente" style="z-index:2;" title="Eliminar documento" aria-label="Eliminar documento"><i class="icon-base ri ri-delete-bin-line"></i></button>' : ''}
                        ${esPDF ?
                            `<div class="pdf-preview" onclick="descargarPDF('photos/${imagen.foto}', '${imagen.foto}')">
                                <i class="icon-base ri ${iconoArchivo} icon-48px text-primary"></i>
                                <div class="mt-2">
                                    <small class="text-muted">${imagen.foto}</small>
                                </div>
                            </div>` :
                            `<img src="photos/${imagen.foto}"
                                 alt="${altText}"
                                 class="img-fluid w-100"
                                 style="cursor: pointer;"
                                 onclick="ampliarImagen('photos/${imagen.foto}')">`
                        }
                    </div>
                </div>
            </div>
        `;

        if (puedeEliminar) {
            const btnEliminar = columna.querySelector('.btn-eliminar-foto-cliente');
            if (btnEliminar) {
                btnEliminar.addEventListener('click', function(event) {
                    event.stopPropagation();
                    eliminarFoto(idFoto, nombreFoto);
                });
            }
        }

        contenedor.appendChild(columna);
    });
}

/**
 * Abrir modal para subir foto
 */
function abrirModalSubirFoto() {
    // Limpiar formulario
    document.getElementById('formSubirFoto').reset();
    
    // Mostrar modal
    const modal = new bootstrap.Modal(document.getElementById('modalSubirFoto'));
    modal.show();
}

/**
 * Subir foto del cliente
 */
function subirFoto() {
    const formData = new FormData();
    const archivoInput = document.getElementById('archivo_foto');
    const btnSubir = document.querySelector('#modalSubirFoto .btn-primary');
    const spinner = btnSubir.querySelector('.spinner-border');
    
    // Validar archivo
    if (!archivoInput.files[0]) {
        Swal.fire({
            title: 'Error',
            text: 'Por favor selecciona un archivo',
            icon: 'error',
            confirmButtonText: 'Aceptar'
        });
        return;
    }
    
    const archivo = archivoInput.files[0];
    
    // Validar tamaño (5MB máximo)
    if (archivo.size > 5 * 1024 * 1024) {
        Swal.fire({
            title: 'Error',
            text: 'El archivo es demasiado grande. Máximo 5MB',
            icon: 'error',
            confirmButtonText: 'Aceptar'
        });
        return;
    }
    
    // Validar formato
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
    
    // Preparar datos
    formData.append('archivo_foto', archivo);
    formData.append('id_cliente', <?php echo $id_cliente ?: 0; ?>);
    
    // Mostrar loading
    btnSubir.disabled = true;
    spinner.classList.remove('d-none');
    
    // Realizar petición AJAX
    fetch('parts/clientes/main/subir_foto.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        btnSubir.disabled = false;
        spinner.classList.add('d-none');
        
        if (data.success) {
            // Cerrar modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('modalSubirFoto'));
            modal.hide();
            
            // Mostrar mensaje de éxito
            const esImagen = ['jpg', 'jpeg', 'gif', 'png'].includes(extension);
            const mensajeExtra = esImagen ? ' (redimensionada automáticamente a 800px de ancho máximo)' : '';
            
            Swal.fire({
                title: '¡Archivo subido!',
                text: data.message + mensajeExtra,
                icon: 'success',
                confirmButtonText: 'Aceptar',
                timer: 3000,
                timerProgressBar: true
            });
            
            // Recargar imágenes
            cargarImagenesCliente(<?php echo $id_cliente ?: 0; ?>);
            
        } else {
            throw new Error(data.error || 'Error desconocido');
        }
    })
    .catch(error => {
        console.error('Error al subir foto:', error);
        btnSubir.disabled = false;
        spinner.classList.add('d-none');
        
        Swal.fire({
            title: 'Error',
            text: 'No se pudo subir la foto: ' + error.message,
            icon: 'error',
            confirmButtonText: 'Aceptar'
        });
    });
}

/**
 * Ampliar imagen en modal
 */
function ampliarImagen(rutaImagen) {
    document.getElementById('imagen_ampliada').src = rutaImagen;
    const modal = new bootstrap.Modal(document.getElementById('modalAmpliarImagen'));
    modal.show();
}

/**
 * Descargar PDF
 */
function descargarPDF(url, filename) {
    // Mostrar indicador de descarga
    Swal.fire({
        title: 'Descargando PDF...',
        text: 'Preparando descarga de ' + filename,
        icon: 'info',
        timer: 2000,
        timerProgressBar: true,
        showConfirmButton: false
    });
    
    // Crear enlace de descarga
    const link = document.createElement('a');
    link.href = url;
    link.download = filename;
    link.target = '_blank';
    
    // Agregar al DOM, hacer clic y remover
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    // Mensaje de confirmación después de un breve delay
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

/**
 * Eliminar foto del cliente
 */
function eliminarFoto(idFoto, nombreFoto) {
    Swal.fire({
        title: 'Confirmar eliminación',
        text: '¿Estás seguro de que quieres eliminar esta foto? Esta acción no se puede deshacer.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            // Realizar petición AJAX para eliminar
            fetch('parts/clientes/main/eliminar_foto.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'id_foto=' + idFoto + '&nombre_foto=' + encodeURIComponent(nombreFoto)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Mostrar mensaje de éxito
                    Swal.fire({
                        title: '¡Foto eliminada!',
                        text: data.message,
                        icon: 'success',
                        confirmButtonText: 'Aceptar',
                        timer: 3000,
                        timerProgressBar: true
                    });
                    
                    // Recargar imágenes
                    cargarImagenesCliente(<?php echo $id_cliente ?: 0; ?>);
                    
                } else {
                    throw new Error(data.error || 'Error desconocido');
                }
            })
            .catch(error => {
                console.error('Error al eliminar foto:', error);
                
                Swal.fire({
                    title: 'Error',
                    text: 'No se pudo eliminar la foto: ' + error.message,
                    icon: 'error',
                    confirmButtonText: 'Aceptar'
                });
            });
        }
    });
}

/**
 * Confirmar y eliminar cliente
 */
function confirmarEliminarCliente(idCliente, relItemAction, itemname) {
    Swal.fire({
        title: '¿Eliminar cliente?',
        html: '<p class="mb-0">¿Está seguro que desea eliminar este cliente?</p><p class="text-muted mt-2">Esta acción no se puede deshacer.</p>',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '<i class="ri-delete-bin-line me-2"></i>Eliminar',
        cancelButtonText: 'Cancelar',
        showLoaderOnConfirm: true,
        allowOutsideClick: false,
        preConfirm: () => {
            // Preparar datos
            const formData = new FormData();
            formData.append('id_cliente', idCliente);
            formData.append('relItemAction', relItemAction);
            formData.append('itemname', itemname);
            // Realizar petición AJAX
            return fetch('parts/clientes/main/eliminar_cliente.php', {
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
                if (!data.success) {
                    throw new Error(data.error || 'Error desconocido');
                }
                return data;
            })
            .catch(error => {
                Swal.showValidationMessage(
                    `Error: ${error.message}`
                );
            });
        }
    }).then((result) => {
        if (result.isConfirmed && result.value) {
            // Mostrar mensaje de éxito
            Swal.fire({
                title: '¡Cliente eliminado!',
                text: result.value.message,
                icon: 'success',
                confirmButtonText: 'Aceptar',
                confirmButtonColor: '#198754',
                timer: 3000,
                timerProgressBar: true
            }).then(() => {
                // Redirigir a la lista de clientes
                window.location.href = 'clientes.php';
            });
        }
    });
}
</script>