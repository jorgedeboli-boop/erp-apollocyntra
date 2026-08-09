<!-- JavaScript para el formulario de edición de proveedor -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Configurar validación del formulario
    configurarValidacion();
    
    // Configurar envío del formulario
    configurarEnvioFormulario();
    
    // Configurar máscaras y validaciones especiales
    configurarValidacionesEspeciales();

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
    
    // Inicializar otros Select2 (incluyendo nacionalidad)
    var select2 = $('.select2:not(#pais):not(#c_provincia):not(#c_poblacion)');
    if (select2.length) {
        select2.each(function () {
            var $this = $(this);
            select2Focus($this);
            $this.select2({
                dropdownParent: $this.parent()
            });
        });
    }

    
});

/**
 * Configurar validación del formulario
 */
function configurarValidacion() {
    const form = document.getElementById('formEditarProveedor');
    
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
    const form = document.getElementById('formEditarProveedor');
    
    if (!form) {
        console.error('Formulario formEditarProveedor no encontrado');
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
        const btnEditar = document.getElementById('btnEditarProveedor');
        if (!btnEditar) {
            console.error('Botón de editar no encontrado');
            return;
        }
        
        console.log('Botón encontrado, deshabilitando...');
        
        // Deshabilitar botón y mostrar loader
        btnEditar.disabled = true;
        btnEditar.innerHTML = '<span class="spinner-border me-1" role="status" aria-hidden="true"></span>Actualizando...';
        
        // Obtener datos del formulario
        const formData = new FormData(form);
        
        console.log('Enviando datos al servidor...');
        
        // Enviar datos al servidor
        fetch('parts/proveedores/editar/procesar_editar_proveedor.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            console.log('Respuesta del servidor:', data);
            
            if (data.success) {
                // Éxito
                mostrarMensaje('success', data.message);
                
                // Redirigir después de un breve delay
                setTimeout(() => {
                    window.location.href = data.redirect;
                }, 1500);
            } else {
                // Error
                mostrarMensaje('error', data.error || 'Error al actualizar el proveedor');
                
                // Rehabilitar botón
                btnEditar.disabled = false;
                btnEditar.innerHTML = '<i class="icon-base ri ri-check-line me-2"></i>Actualizar Proveedor';
            }
        })
        .catch(error => {
            console.error('Error en la petición:', error);
            mostrarMensaje('error', 'Error de conexión. Intente nuevamente.');
            
            // Rehabilitar botón
            btnEditar.disabled = false;
            btnEditar.innerHTML = '<i class="icon-base ri ri-check-line me-2"></i>Actualizar Empresa';
        });
    });
}

/**
 * Configurar validaciones especiales
 */
function configurarValidacionesEspeciales() {
    // Validación de teléfono (solo números)
    const telefonoField = document.getElementById('telefono_proveedor');
    if (telefonoField) {
        telefonoField.addEventListener('input', function(e) {
            // Permitir solo números y algunos caracteres especiales
            this.value = this.value.replace(/[^0-9+\s\-\(\)]/g, '');
        });
    }
    
    // Validación de código postal (solo números)
    const codigoPostalField = document.getElementById('codigo_postal_proveedor');
    if (codigoPostalField) {
        codigoPostalField.addEventListener('input', function(e) {
            // Permitir solo números
            this.value = this.value.replace(/[^0-9]/g, '');
        });
    }
    
    // Validación de email
    const emailField = document.getElementById('email_proveedor');
    if (emailField) {
        emailField.addEventListener('blur', function() {
            const email = this.value.trim();
            if (email && !isValidEmail(email)) {
                this.setCustomValidity('Por favor, introduce un email válido');
                this.classList.add('is-invalid');
            } else {
                this.setCustomValidity('');
                this.classList.remove('is-invalid');
            }
        });
    }
}

/**
 * Validar formato de email
 */
function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

/**
 * Mostrar mensaje de éxito o error
 */
function mostrarMensaje(tipo, mensaje) {
    // Crear elemento de alerta
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${tipo === 'success' ? 'success' : 'danger'} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        ${mensaje}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;
    
    // Insertar al inicio del formulario
    const form = document.getElementById('formEditarProveedor');
    if (form) {
        form.parentNode.insertBefore(alertDiv, form);
        
        // Auto-ocultar después de 5 segundos
        setTimeout(() => {
            if (alertDiv.parentNode) {
                alertDiv.remove();
            }
        }, 5000);
    }
}

/**
 * Función para limpiar validaciones
 */
function limpiarValidaciones() {
    const form = document.getElementById('formEditarProveedor');
    if (form) {
        form.classList.remove('was-validated');
        
        // Limpiar clases de validación de todos los campos
        const fields = form.querySelectorAll('.form-control');
        fields.forEach(field => {
            field.classList.remove('is-valid', 'is-invalid');
        });
    }
}

/**
 * Función para restaurar formulario
 */
function restaurarFormulario() {
    // Recargar la página para restaurar los valores originales
    window.location.reload();
}
</script>