<!-- JavaScript para el formulario de edición de cliente -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Configurar validación del formulario
    configurarValidacion();
    
    // Configurar envío del formulario
    configurarEnvioFormulario();
    
    // Configurar máscaras y validaciones especiales
    configurarValidacionesEspeciales();
    
    // Los datos ya se cargan en PHP, no es necesario cargarlos con JavaScript
    
    // Inicializar Select2 usando el código del template
    var select2 = $('.select2');
    // For all Select2
    if (select2.length) {
        select2.each(function () {
            var $this = $(this);
            select2Focus($this);
            $this.select2({
                dropdownParent: $this.parent()
            });
        });
    }
    
    // Inicializar Select2 estático para nacionalidades (con opciones pre-cargadas)
    inicializarSelect2NacionalidadesEstatico();
});

// Los datos del cliente se cargan directamente en PHP en content.php
// No es necesario JavaScript para cargar los datos

/**
 * Llenar formulario con datos del cliente
 */
function llenarFormulario(cliente) {
    console.log('Llenando formulario con datos:', cliente);
    
    // Campos principales
    document.getElementById('nombre').value = cliente.nombre || '';
    document.getElementById('apellido').value = cliente.apellido || '';
    document.getElementById('tipo_identificacion').value = cliente.tipo_identificacion || '';
    document.getElementById('identificacion').value = cliente.identificacion || '';
    document.getElementById('nacionalidad').value = cliente.nacionalidad || '';
    document.getElementById('f_nacimiento').value = cliente.f_nacimiento || '';
    document.getElementById('telefono').value = cliente.telefono || '';
    document.getElementById('sucursal').value = cliente.sucursal || '';
    document.getElementById('f_vencimiento').value = cliente.f_vencimiento || '';
    
    // Campos de datos adicionales
    if (cliente.datos_cliente) {
        document.getElementById('direccion').value = cliente.datos_cliente.direccion || '';
        document.getElementById('c_provincia').value = cliente.datos_cliente.c_provincia || '';
        document.getElementById('c_poblacion').value = cliente.datos_cliente.c_poblacion || '';
        document.getElementById('codigo_postal').value = cliente.datos_cliente.codigo_postal || '';
        document.getElementById('email').value = cliente.datos_cliente.email || '';
        document.getElementById('observaciones').value = cliente.datos_cliente.observaciones || '';
        document.getElementById('sexo').value = cliente.datos_cliente.sexo || 'MASCULINO';
    }
    
    // Actualizar Select2 si está disponible
    if (typeof $ !== 'undefined' && $.fn.select2) {
        $('#tipo_identificacion').trigger('change');
        $('#sucursal').trigger('change');
        $('#nacionalidad').trigger('change');
    }
}

/**
 * Configurar validación del formulario
 */
function configurarValidacion() {
    const form = document.getElementById('formEditarCliente');
    
    // Validación en tiempo real
    form.addEventListener('input', function(event) {
        const field = event.target;
        if (field.hasAttribute('required')) {
            validarCampo(field);
        }
    });
    
    // Validación al cambiar select
    form.addEventListener('change', function(event) {
        const field = event.target;
        if (field.hasAttribute('required')) {
            validarCampo(field);
        }
    });
}

/**
 * Validar un campo específico
 */
function validarCampo(field) {
    const isValid = field.checkValidity();
    
    // Buscar el label asociado
    const label = field.parentElement.querySelector('label[for="' + field.id + '"]');
    
    if (isValid) {
        field.classList.remove('is-invalid');
        field.classList.add('is-valid');
        
        // Actualizar label si existe
        if (label) {
            label.classList.remove('text-danger');
            label.classList.add('text-success');
        }
    } else {
        field.classList.remove('is-valid');
        field.classList.add('is-invalid');
        
        // Actualizar label si existe
        if (label) {
            label.classList.remove('text-success');
            label.classList.add('text-danger');
        }
    }
    
    return isValid;
}

/**
 * Configurar envío del formulario
 */
function configurarEnvioFormulario() {
    const form = document.getElementById('formEditarCliente');
    
    if (!form) {
        console.error('Formulario formEditarCliente no encontrado');
        return;
    }
    
    form.addEventListener('submit', function(event) {
        event.preventDefault();
        
        console.log('Formulario enviado, validando...');
        
        // Validar todo el formulario
        if (!form.checkValidity()) {
            console.log('Formulario no válido, mostrando validaciones...');
            form.classList.add('was-validated');
            return;
        }
        
        console.log('Formulario válido, obteniendo botón...');
        
        // Obtener el botón de editar
        const btnEditar = document.getElementById('btnEditarCliente');
        if (!btnEditar) {
            console.error('Botón de editar no encontrado');
            return;
        }
        
        console.log('Botón encontrado, deshabilitando...');
        
        // Deshabilitar botón y mostrar loading
        btnEditar.disabled = true;
        btnEditar.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Actualizando cliente...';
        
        // Recopilar datos del formulario
        const formData = new FormData(form);
        
        console.log('Enviando formulario...');
        
        // Enviar formulario
        actualizarCliente(formData);
    });
}

/**
 * Actualizar cliente en el servidor
 */
function actualizarCliente(formData) {
    console.log('Iniciando petición fetch...');
    
    fetch('parts/clientes/editar/procesar_editar_cliente.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        console.log('Respuesta recibida, status:', response.status);
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        console.log('Datos recibidos:', data);
        if (data.success) {
            // Mostrar mensaje de éxito
            Swal.fire({
                title: '¡Cliente Actualizado!',
                text: data.message || 'El cliente se ha actualizado exitosamente',
                icon: 'success',
                confirmButtonText: 'Aceptar',
                confirmButtonColor: '#198754',
                timer: 3000,
                timerProgressBar: true
            }).then(() => {
                // Redirigir a la lista de clientes
                window.location.href = 'clientes.php';
            });
        } else {
            throw new Error(data.error || 'Error desconocido al actualizar el cliente');
        }
    })
    .catch(error => {
        console.error('Error en actualizarCliente:', error);
        
        // Mostrar mensaje de error
        Swal.fire({
            title: 'Error',
            text: 'No se pudo actualizar el cliente: ' + error.message,
            icon: 'error',
            confirmButtonText: 'Aceptar',
            confirmButtonColor: '#dc3545'
        });
        
        // Restaurar botón
        restaurarBotonEditar();
    });
}

/**
 * Restaurar botón de editar
 */
function restaurarBotonEditar() {
    const btnEditar = document.getElementById('btnEditarCliente');
    if (btnEditar) {
        btnEditar.disabled = false;
        btnEditar.innerHTML = '<i class="icon-base ri ri-check-line me-2"></i>Actualizar Cliente';
    }
}

/**
 * Configurar validaciones especiales
 */
function configurarValidacionesEspeciales() {
    // Validación de código postal
    const codigoPostal = document.getElementById('codigo_postal');
    if (codigoPostal) {
        codigoPostal.addEventListener('input', function() {
            // Solo permitir números
            this.value = this.value.replace(/[^0-9]/g, '');
            
            // Limitar a 5 dígitos
            if (this.value.length > 5) {
                this.value = this.value.slice(0, 5);
            }
        });
    }
    
    // Validación de teléfono
    const telefono = document.getElementById('telefono');
    if (telefono) {
        let timeoutTelefono = null;
        
        telefono.addEventListener('input', function() {
            // Permitir números, espacios, +, - y paréntesis
            this.value = this.value.replace(/[^0-9\s\+\-\(\)]/g, '');
            
            // Limpiar timeout anterior
            if (timeoutTelefono) {
                clearTimeout(timeoutTelefono);
            }
            
            // Verificar si el teléfono ya existe (después de 500ms de inactividad)
            const telefonoValor = this.value.trim();
            if (telefonoValor.length >= 9) {
                timeoutTelefono = setTimeout(function() {
                    verificarTelefonoExistente(telefonoValor);
                }, 500);
            }
        });
    }
    
    // Validación de email
    const email = document.getElementById('email');
    if (email) {
        email.addEventListener('blur', function() {
            if (this.value && !this.checkValidity()) {
                this.classList.add('is-invalid');
            }
        });
    }
    
    // Validación de fecha de nacimiento
    const fNacimiento = document.getElementById('f_nacimiento');
    if (fNacimiento) {
        fNacimiento.addEventListener('change', function() {
            const fechaSeleccionada = new Date(this.value);
            const fechaActual = new Date();
            
            if (fechaSeleccionada > fechaActual) {
                this.setCustomValidity('La fecha de nacimiento no puede ser futura');
                this.classList.add('is-invalid');
            } else {
                this.setCustomValidity('');
                this.classList.remove('is-invalid');
            }
        });
    }
    
    // Validación de fecha de vencimiento
    const fVencimiento = document.getElementById('f_vencimiento');
    if (fVencimiento) {
        fVencimiento.addEventListener('change', function() {
            const fechaSeleccionada = new Date(this.value);
            const fechaActual = new Date();
            
            if (fechaSeleccionada < fechaActual) {
                this.setCustomValidity('La fecha de vencimiento no puede ser pasada');
                this.classList.add('is-invalid');
            } else {
                this.setCustomValidity('');
                this.classList.remove('is-invalid');
            }
        });
    }
}

/**
 * Limpiar formulario
 */
function limpiarFormulario() {
    const form = document.getElementById('formEditarCliente');
    form.reset();
    
    // Limpiar clases de validación
    form.classList.remove('was-validated');
    form.querySelectorAll('.is-valid, .is-invalid').forEach(field => {
        field.classList.remove('is-valid', 'is-invalid');
    });
    
    // Restaurar botón
    restaurarBotonEditar();
}

/**
 * Validar identificación según tipo
 */
function validarIdentificacion() {
    const tipoIdentificacion = document.getElementById('tipo_identificacion').value;
    const identificacion = document.getElementById('identificacion').value;
    
    if (!tipoIdentificacion || !identificacion) return true;
    
    let esValida = true;
    let mensaje = '';
    
    switch (tipoIdentificacion) {
        case 'DNI':
            // Validar formato DNI español (8 dígitos + 1 letra)
            const dniRegex = /^[0-9]{8}[A-Z]$/i;
            esValida = dniRegex.test(identificacion);
            mensaje = 'El DNI debe tener 8 dígitos seguidos de una letra';
            break;
            
        case 'NIE':
            // Validar formato NIE (1 letra + 7 dígitos + 1 letra)
            const nieRegex = /^[A-Z][0-9]{7}[A-Z]$/i;
            esValida = nieRegex.test(identificacion);
            mensaje = 'El NIE debe tener 1 letra + 7 dígitos + 1 letra';
            break;
            
        case 'PASAPORTE':
            // Validar formato pasaporte (1 letra + 8 dígitos)
            const pasaporteRegex = /^[A-Z][0-9]{8}$/i;
            esValida = pasaporteRegex.test(identificacion);
            mensaje = 'El pasaporte debe tener 1 letra + 8 dígitos';
            break;
            
        case 'CIF':
            // Validar formato CIF (1 letra + 7 dígitos + 1 letra)
            const cifRegex = /^[A-Z][0-9]{7}[A-Z]$/i;
            esValida = cifRegex.test(identificacion);
            mensaje = 'El CIF debe tener 1 letra + 7 dígitos + 1 letra';
            break;
    }
    
    if (!esValida) {
        document.getElementById('identificacion').setCustomValidity(mensaje);
        document.getElementById('identificacion').classList.add('is-invalid');
    } else {
        document.getElementById('identificacion').setCustomValidity('');
        document.getElementById('identificacion').classList.remove('is-invalid');
    }
    
    return esValida;
}

/**
 * Verificar si el teléfono ya existe en la base de datos
 */
function verificarTelefonoExistente(telefono) {
    if (!telefono || telefono.length < 9) return;
    
    const idCliente = document.getElementById('id_cliente').value;
    
    $.ajax({
        url: 'parts/universal/ajax_verificar_cliente.php',
        method: 'GET',
        dataType: 'json',
        data: {
            action: 'verificar_telefono',
            valor: telefono,
            id_cliente: idCliente
        },
        success: function(response) {
            if (response.existe) {
                Swal.fire({
                    title: '¡Teléfono Duplicado!',
                    text: response.message,
                    icon: 'warning',
                    confirmButtonText: 'Entendido',
                    confirmButtonColor: '#f39c12'
                });
                
                // Vaciar el campo
                document.getElementById('telefono').value = '';
                document.getElementById('telefono').focus();
            }
        },
        error: function(xhr, status, error) {
            console.error('Error al verificar teléfono:', error);
        }
    });
}

/**
 * Verificar si la identificación ya existe en la base de datos
 */
function verificarIdentificacionExistente(identificacion) {
    if (!identificacion || identificacion.length < 5) return;
    
    const idCliente = document.getElementById('id_cliente').value;
    
    $.ajax({
        url: 'parts/clientes/editar/ajax_verificar_cliente.php',
        method: 'GET',
        dataType: 'json',
        data: {
            action: 'verificar_identificacion',
            valor: identificacion,
            id_cliente: idCliente
        },
        success: function(response) {
            if (response.existe) {
                Swal.fire({
                    title: '¡Identificación Duplicada!',
                    text: response.message,
                    icon: 'warning',
                    confirmButtonText: 'Entendido',
                    confirmButtonColor: '#f39c12'
                });
                
                // Vaciar el campo
                document.getElementById('identificacion').value = '';
                document.getElementById('identificacion').focus();
            }
        },
        error: function(xhr, status, error) {
            console.error('Error al verificar identificación:', error);
        }
    });
}

// Agregar validación de identificación al cambiar tipo
document.addEventListener('DOMContentLoaded', function() {
    const tipoIdentificacion = document.getElementById('tipo_identificacion');
    const identificacion = document.getElementById('identificacion');
    
    if (tipoIdentificacion && identificacion) {
        tipoIdentificacion.addEventListener('change', validarIdentificacion);
        identificacion.addEventListener('input', validarIdentificacion);
        
        // Verificar si la identificación ya existe al salir del campo (blur)
        identificacion.addEventListener('blur', function() {
            const identificacionValor = this.value.trim();
            
            if (identificacionValor.length >= 5) {
                verificarIdentificacionExistente(identificacionValor);
            }
        });
    }

    // Configurar Select2 para países, provincias y poblaciones
    setTimeout(function() {
        console.log('Inicializando Select2...');
        
        // País
        $('#pais').select2({
            dropdownParent: $('#pais').parent(),
            placeholder: 'Seleccionar país',
            allowClear: true,
            ajax: {
                url: 'parts/universal/ajax_poblaciones.php',
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return {
                        action: 'paises',
                        search: params.term || '',
                        page: params.page || 1
                    };
                },
                processResults: function (data) {
                    console.log('Datos recibidos:', data);
                    return {
                        results: data.results || [],
                        pagination: data.pagination || {more: false}
                    };
                },
                error: function(xhr, status, error) {
                    console.error('Error AJAX:', error);
                    console.error('Response:', xhr.responseText);
                }
            }
        });
        
        // Provincia
        $('#c_provincia').select2({
            dropdownParent: $('#c_provincia').parent(),
            placeholder: 'Seleccionar provincia',
            allowClear: true,
            ajax: {
                url: 'parts/universal/ajax_poblaciones.php',
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return {
                        action: 'provincias',
                        search: params.term || '',
                        page: params.page || 1,
                        idpais: $('#pais').val()
                    };
                },
                processResults: function (data) {
                    return {
                        results: data.results || [],
                        pagination: data.pagination || {more: false}
                    };
                }
            }
        });
        
        // Población
        $('#c_poblacion').select2({
            dropdownParent: $('#c_poblacion').parent(),
            placeholder: 'Seleccionar población',
            allowClear: true,
            ajax: {
                url: 'parts/universal/ajax_poblaciones.php',
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return {
                        action: 'poblaciones',
                        search: params.term || '',
                        page: params.page || 1,
                        idprovincia: $('#c_provincia').val()
                    };
                },
                processResults: function (data) {
                    return {
                        results: data.results || [],
                        pagination: data.pagination || {more: false}
                    };
                }
            }
        });
        
        // Eventos para limpiar selects dependientes
        $('#pais').on('change', function() {
            $('#c_provincia').val('').trigger('change');
            $('#c_poblacion').val('').trigger('change');
            $('#codigo_postal').val('');
        });
        
        $('#c_provincia').on('change', function() {
            $('#c_poblacion').val('').trigger('change');
            $('#codigo_postal').val('');
        });
        
        // Asignación automática al seleccionar población
        $('#c_poblacion').on('change', function() {
            var idPoblacion = $(this).val();
            
            if (idPoblacion) {
                $.ajax({
                    url: 'parts/universal/ajax_poblaciones.php',
                    dataType: 'json',
                    data: {
                        action: 'poblacion_detalle',
                        idpoblacion: idPoblacion
                    },
                    success: function(response) {
                        if (response.success) {
                            var data = response.data;
                            
                            // Asignar código postal
                            $('#codigo_postal').val(data.codigo_postal);
                            
                            // Asignar provincia si no está ya seleccionada
                            if (!$('#c_provincia').val() && data.idprovincia) {
                                var newOption = new Option(data.provincia, data.idprovincia, true, true);
                                $('#c_provincia').append(newOption).trigger('change');
                            }
                            
                            // Asignar país si no está ya seleccionado
                            if (!$('#pais').val() && data.id_rel_country) {
                                var newOption = new Option(data.pais, data.id_rel_country, true, true);
                                $('#pais').append(newOption).trigger('change');
                            }
                        }
                    },
                    error: function() {
                        console.error('Error al obtener detalles de la población');
                    }
                });
            } else {
                $('#codigo_postal').val('');
            }
        });
        
        console.log('Select2 inicializado correctamente');
    }, 100);
});

/**
 * Inicializar Select2 estático para nacionalidades (con opciones pre-cargadas)
 */
function inicializarSelect2NacionalidadesEstatico() {
    const selectNacionalidad = document.getElementById('nacionalidad');
    
    if (!selectNacionalidad) {
        console.error('Select de nacionalidad no encontrado');
        return;
    }
    
    // Convertir el select a Select2 estático (sin AJAX)
    var select2 = $(selectNacionalidad);
    if (select2.length) {
        select2.each(function () {
            var $this = $(this);
            select2Focus($this);
            $this.select2({
                dropdownParent: $this.parent()
            });
        });
    }
    
    // El valor ya está pre-seleccionado desde PHP, no es necesario hacer nada más
}
var placeholderSelect2 = 'Seleccionar...';
document.addEventListener('DOMContentLoaded', function() {
    // Placeholder universal para todos los Select2
    
    // Inicializar otros Select2 (incluyendo nacionalidad)
    var select2 = $('.select2:not(#pais):not(#c_provincia):not(#c_poblacion)');
    if (select2.length) {
        select2.each(function () {
            var $this = $(this);
            select2Focus($this);
            $this.select2({
                dropdownParent: $this.parent(),
                placeholder: placeholderSelect2,
                allowClear: true
            });
        });
    }
});
</script>
<script src="parts/universal/js/javascript_direcciones.js"></script>