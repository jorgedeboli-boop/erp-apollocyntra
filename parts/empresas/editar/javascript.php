<!-- JavaScript para el formulario de edición de empresa -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Configurar validación del formulario
    configurarValidacion();
    
    // Configurar envío del formulario
    configurarEnvioFormulario();
    
    // Configurar máscaras y validaciones especiales
    configurarValidacionesEspeciales();

    // Actualizar región ITP al cambiar la provincia
    configurarRegionItp();

    // Select2 para país, provincia y población
    configurarSelectsUbicacion();
});

function actualizarTextoSelect($select, $hidden) {
    var texto = $select.find('option:selected').text().trim();
    $hidden.val(texto === 'Seleccionar país' || texto === 'Seleccionar provincia' || texto === 'Seleccionar población' ? '' : texto);
}

function sincronizarTextosUbicacion() {
    actualizarTextoSelect($('#rel_id_pais'), $('#pais_empresa'));
    actualizarTextoSelect($('#rel_id_provincia'), $('#provincia_empresa'));
    actualizarTextoSelect($('#rel_id_poblacion'), $('#poblacion_empresa'));
}

function configurarSelectsUbicacion() {
    setTimeout(function() {
        $('#rel_id_pais').select2({
            dropdownParent: $('#rel_id_pais').parent(),
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
                    return {
                        results: data.results || [],
                        pagination: data.pagination || {more: false}
                    };
                }
            }
        });

        $('#rel_id_provincia').select2({
            dropdownParent: $('#rel_id_provincia').parent(),
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
                        idpais: $('#rel_id_pais').val()
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

        $('#rel_id_poblacion').select2({
            dropdownParent: $('#rel_id_poblacion').parent(),
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
                        idprovincia: $('#rel_id_provincia').val()
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

        sincronizarTextosUbicacion();

        $('#rel_id_pais').on('change', function() {
            actualizarTextoSelect($(this), $('#pais_empresa'));
            $('#rel_id_provincia').val('').trigger('change');
            $('#provincia_empresa').val('');
            $('#rel_id_poblacion').val('').trigger('change');
            $('#poblacion_empresa').val('');
            $('#codigo_postal_empresa').val('');
            $(document).trigger('empresa:provincia-cambiada');
        });

        $('#rel_id_provincia').on('change', function() {
            actualizarTextoSelect($(this), $('#provincia_empresa'));
            $('#rel_id_poblacion').val('').trigger('change');
            $('#poblacion_empresa').val('');
            $('#codigo_postal_empresa').val('');
            $(document).trigger('empresa:provincia-cambiada');
        });

        $('#rel_id_poblacion').on('change', function() {
            actualizarTextoSelect($(this), $('#poblacion_empresa'));

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

                            $('#codigo_postal_empresa').val(data.codigo_postal || '');

                            if (!$('#rel_id_provincia').val() && data.idprovincia) {
                                var optProv = new Option(data.provincia, data.idprovincia, true, true);
                                $('#rel_id_provincia').append(optProv).trigger('change');
                                $('#provincia_empresa').val(data.provincia || '');
                                $(document).trigger('empresa:provincia-cambiada');
                            }

                            if (!$('#rel_id_pais').val() && data.id_rel_country) {
                                var optPais = new Option(data.pais, data.id_rel_country, true, true);
                                $('#rel_id_pais').append(optPais).trigger('change');
                                $('#pais_empresa').val(data.pais || '');
                            }
                        }
                    }
                });
            } else {
                $('#codigo_postal_empresa').val('');
            }
        });
    }, 100);

    var select2 = $('.select2:not(#rel_id_pais):not(#rel_id_provincia):not(#rel_id_poblacion)');
    if (select2.length) {
        select2.each(function () {
            var $this = $(this);
            if (typeof select2Focus === 'function') {
                select2Focus($this);
            }
            $this.select2({
                dropdownParent: $this.parent()
            });
        });
    }
}

/**
 * Configurar validación del formulario
 */
function configurarValidacion() {
    const form = document.getElementById('formEditarEmpresa');
    
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
    const form = document.getElementById('formEditarEmpresa');
    
    if (!form) {
        console.error('Formulario formEditarEmpresa no encontrado');
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
        const btnEditar = document.getElementById('btnEditarEmpresa');
        if (!btnEditar) {
            console.error('Botón de editar no encontrado');
            return;
        }
        
        console.log('Botón encontrado, deshabilitando...');
        
        // Deshabilitar botón y mostrar loader
        btnEditar.disabled = true;
        btnEditar.innerHTML = '<span class="spinner-border me-1" role="status" aria-hidden="true"></span>Actualizando...';
        
        // Obtener datos del formulario
        sincronizarTextosUbicacion();
        const formData = new FormData(form);
        
        console.log('Enviando datos al servidor...');
        
        // Enviar datos al servidor
        fetch('parts/empresas/editar/procesar_editar_empresa.php', {
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
                mostrarMensaje('error', data.error || 'Error al actualizar la empresa');
                
                // Rehabilitar botón
                btnEditar.disabled = false;
                btnEditar.innerHTML = '<i class="icon-base ri ri-check-line me-2"></i>Actualizar Empresa';
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
    const telefonoField = document.getElementById('telefono_empresa');
    if (telefonoField) {
        telefonoField.addEventListener('input', function(e) {
            // Permitir solo números y algunos caracteres especiales
            this.value = this.value.replace(/[^0-9+\s\-\(\)]/g, '');
        });
    }
    
    // Validación de código postal (solo números, si se edita manualmente)
    const codigoPostalField = document.getElementById('codigo_postal_empresa');
    if (codigoPostalField && !codigoPostalField.readOnly) {
        codigoPostalField.addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '');
        });
    }
    
    // Validación de email
    const emailField = document.getElementById('email_empresa');
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
 * Actualizar el campo de solo lectura Región ITP según la provincia
 */
function configurarRegionItp() {
    const regionItpField = document.getElementById('region_itp_empresa');

    if (!regionItpField) {
        return;
    }

    let regionItpTimer = null;

    function actualizarRegionItp() {
        const idProvincia = $('#rel_id_provincia').val();

        if (!idProvincia) {
            regionItpField.value = '';
            return;
        }

        const params = new URLSearchParams({ id_provincia: idProvincia });

        fetch('parts/empresas/editar/get_region_itp.php?' + params.toString())
            .then(function (response) {
                return response.json();
            })
            .then(function (data) {
                if (data.success) {
                    regionItpField.value = data.texto || '';
                } else {
                    regionItpField.value = '';
                }
            })
            .catch(function () {
                regionItpField.value = '';
            });
    }

    function programarActualizacionRegionItp() {
        if (regionItpTimer) {
            clearTimeout(regionItpTimer);
        }
        regionItpTimer = setTimeout(actualizarRegionItp, 300);
    }

    $(document).on('empresa:provincia-cambiada', programarActualizacionRegionItp);
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
    const form = document.getElementById('formEditarEmpresa');
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
    const form = document.getElementById('formEditarEmpresa');
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