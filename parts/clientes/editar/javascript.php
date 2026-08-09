<!-- JavaScript para el formulario de edición de cliente -->
<?php if (isset($app_country_id) && (int)$app_country_id === 68) { ?>
<?php
$vComprobarIdentificacionSpain = filemtime(__DIR__ . '/../../universal/js/comprobar_identificacion_spain.js');
$vJavascriptDirecciones = filemtime(__DIR__ . '/../../universal/js/javascript_direcciones.js');
?>
<script src="parts/universal/js/comprobar_identificacion_spain.js?v=<?php echo $vComprobarIdentificacionSpain; ?>"></script>
<?php } ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Esperar a que jQuery esté disponible
    
    // Configurar validación del formulario
    configurarValidacion();
    
    // Configurar envío del formulario
    configurarEnvioFormulario();
    
    // Configurar máscaras y validaciones especiales
    configurarValidacionesEspeciales();

});
    $('#nacionalidad').select2({
        placeholder: 'Seleccionar...',
        allowClear: true
    });

    $('#sexo').select2({
        placeholder: 'Seleccionar...',
        allowClear: true
    });

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
        
        // Obtener el botón de crear
        const btnCrear = document.getElementById('btnEditarCliente');
        if (!btnCrear) {
            console.error('Botón de crear no encontrado');
            return;
        }
        
        console.log('Botón encontrado, deshabilitando...');
        
        // Deshabilitar botón y mostrar loading
        btnCrear.disabled = true;
        btnCrear.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Actualizando cliente...';
        
        // Recopilar datos del formulario
        const formData = new FormData(form);
        
        // Agregar fecha de alta
        formData.append('f_alta', new Date().toISOString().split('T')[0]);
        
        console.log('Enviando formulario...');
        
        // Enviar formulario
        actualizarCliente(formData);
    });
}

/**
 * Crear cliente en el servidor
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
            var redirect = data.redirect;
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
                window.location.href = redirect;
                
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
 * Restaurar botón de crear
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
            // Permitir números, letras y espacios/guiones (comunes en códigos postales)
            this.value = this.value.replace(/[^a-zA-Z0-9\s-]/g, '');
            
            // Convertir a mayúsculas (opcional, común en códigos postales)
            this.value = this.value.toUpperCase();
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
            
            // Calcular edad
            let edad = fechaActual.getFullYear() - fechaSeleccionada.getFullYear();
            const mes = fechaActual.getMonth() - fechaSeleccionada.getMonth();
            if (mes < 0 || (mes === 0 && fechaActual.getDate() < fechaSeleccionada.getDate())) {
                edad--;
            }
            
            if (fechaSeleccionada > fechaActual) {
                Swal.fire({
                    title: 'Error',
                    text: 'La fecha de nacimiento no puede ser futura',
                    icon: 'error',
                    confirmButtonText: 'Aceptar',
                    confirmButtonColor: '#dc3545'
                });
                this.value = '';
                this.setCustomValidity('La fecha de nacimiento no puede ser futura');
                this.classList.add('is-invalid');
            } else if (edad < 18) {
                Swal.fire({
                    title: 'Error',
                    text: 'El cliente debe ser mayor de edad (18 años)',
                    icon: 'error',
                    confirmButtonText: 'Aceptar',
                    confirmButtonColor: '#dc3545'
                });
                this.value = '';
                this.setCustomValidity('Debe ser mayor de edad');
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
                Swal.fire({
                    title: 'Error',
                    text: 'La fecha de vencimiento debe ser mayor a la fecha actual',
                    icon: 'error',
                    confirmButtonText: 'Aceptar',
                    confirmButtonColor: '#dc3545'
                });
                this.value = '';
                this.setCustomValidity('La fecha de vencimiento no puede ser pasada');
                this.classList.add('is-invalid');
            } else {
                this.setCustomValidity('');
                this.classList.remove('is-invalid');
            }
        });
    }
}

function getTipoIdentificacionValor() {
    const el = document.getElementById('tipo_identificacion');
    if (!el) return '';
    if (typeof $ !== 'undefined' && $('#tipo_identificacion').length) {
        return String($('#tipo_identificacion').val() || '');
    }
    return String(el.value || '');
}

/**
 * Validar formato de identificación al escribir (misma lógica que lotes/crear, sin consulta BD).
 */
function validarIdentificacion() {
    const campoIdentificacion = document.getElementById('identificacion');
    if (!campoIdentificacion) return true;

    const tipoIdValor = getTipoIdentificacionValor();
    const identificacionValor = campoIdentificacion.value.trim();

    if (!tipoIdValor) {
        return true;
    }

    if (!identificacionValor) {
        campoIdentificacion.setCustomValidity('');
        if (typeof validarCampo === 'function') {
            validarCampo(campoIdentificacion);
        }
        return true;
    }

    const tiposValidacionSpain = ['1', '2', '3', '4'];

    if (tiposValidacionSpain.includes(tipoIdValor)) {
        if (typeof validarIdentificacionSpain === 'function') {
            const resultado = validarIdentificacionSpain(tipoIdValor, identificacionValor);
            campoIdentificacion.setCustomValidity(resultado.valido ? '' : resultado.mensaje);
            if (typeof validarCampo === 'function') {
                validarCampo(campoIdentificacion);
            }
            return resultado.valido;
        }
    } else if (tipoIdValor === '5') {
        campoIdentificacion.setCustomValidity('');
        if (typeof validarCampo === 'function') {
            validarCampo(campoIdentificacion);
        }
        return true;
    }

    return true;
}

/**
 * Verificar si el teléfono ya existe en la base de datos
 */
function verificarTelefonoExistente(telefono) {
    if (!telefono || telefono.length < 9) return;
    
    var id_cliente = document.getElementById('id_cliente').value;
    $.ajax({
        url: 'parts/clientes/editar/ajax_verificar_cliente.php',
        method: 'GET',
        dataType: 'json',
        data: {
            action: 'verificar_telefono',
            valor: telefono,
            id_cliente: id_cliente
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
    if (!identificacion || identificacion.length < 5) {
        console.log('Identificación muy corta o vacía');
        return;
    }
    
    console.log('Iniciando AJAX para verificar identificación:', identificacion);
    var id_cliente = document.getElementById('id_cliente').value;
    $.ajax({
        url: 'parts/clientes/editar/ajax_verificar_cliente.php',
        method: 'GET',
        dataType: 'json',
        data: {
            action: 'verificar_identificacion',
            valor: identificacion,
            id_cliente: id_cliente
        },
        success: function(response) {
            console.log('Respuesta AJAX identificación:', response);
            
            if (response.existe) {
                // Marcar como inválido ANTES de mostrar el mensaje
                const campoIdentificacion = document.getElementById('identificacion');
                campoIdentificacion.setCustomValidity(response.message);
                validarCampo(campoIdentificacion);
                
                Swal.fire({
                    title: '¡Identificación Duplicada!',
                    text: response.message,
                    icon: 'warning',
                    confirmButtonText: 'Entendido',
                    confirmButtonColor: '#f39c12'
                }).then(() => {
                    // Vaciar el campo después de cerrar el mensaje
                    campoIdentificacion.value = '';
                    campoIdentificacion.setCustomValidity('');
                    campoIdentificacion.focus();
                });
            } else {
                console.log('Identificación NO existe en BD');
                const campoIdentificacion = document.getElementById('identificacion');
                campoIdentificacion.setCustomValidity('');
                validarCampo(campoIdentificacion);
            }
        },
        error: function(xhr, status, error) {
            console.error('Error al verificar identificación:', error);
            console.error('Status:', status);
            console.error('Response:', xhr.responseText);
        }
    });
}

function aplicarDisabledFormClienteExceptoTipo(form, deshabilitar) {
    if (!form) return;
    const datosCliente = document.getElementById('datos_cliente');
    if (datosCliente) {
        datosCliente.classList.toggle('formulario-borroso', deshabilitar);
    }
    const inputGruposAfectar = new Set();
    form.querySelectorAll('input, select, textarea').forEach(function (el) {
        if (el.id === 'tipo_identificacion') return;
        const t = (el.type || '').toLowerCase();
        if (t === 'hidden' || t === 'submit' || t === 'button' || t === 'reset') return;
        if (el.readOnly) return;
        const grupo = el.closest('.input-group-merge, .input-group');
        if (grupo && form.contains(grupo)) {
            inputGruposAfectar.add(grupo);
            return;
        }
        el.disabled = deshabilitar;
    });
    inputGruposAfectar.forEach(function (grupo) {
        grupo.classList.toggle('disabled', deshabilitar);
    });
    const btnComprobarIdent = document.getElementById('btn_comprobar_identificacion');
    if (btnComprobarIdent) {
        btnComprobarIdent.disabled = deshabilitar;
    }
}

function ejecutarComprobacionIdentificacionCliente() {
    const campoIdentificacion = document.getElementById('identificacion');
    const tipoIdentificacion = document.getElementById('tipo_identificacion');
    if (!campoIdentificacion || !tipoIdentificacion) return;

    const identificacionValor = campoIdentificacion.value.trim();
    const tipoIdValor = getTipoIdentificacionValor();

    if (!identificacionValor || !tipoIdValor) {
        return;
    }

    const tiposValidacionSpain = ['1', '2', '3', '4'];

    if (tiposValidacionSpain.includes(tipoIdValor)) {
        if (typeof validarIdentificacionSpain === 'function') {
            const resultado = validarIdentificacionSpain(tipoIdValor, identificacionValor);
            campoIdentificacion.setCustomValidity(resultado.valido ? '' : resultado.mensaje);
            if (typeof validarCampo === 'function') {
                validarCampo(campoIdentificacion);
            }
            if (!resultado.valido) {
                return;
            }
        }
        if (identificacionValor.length >= 5) {
            verificarIdentificacionExistente(identificacionValor);
        }
    } else if (tipoIdValor === '5') {
        campoIdentificacion.setCustomValidity('');
        if (typeof validarCampo === 'function') {
            validarCampo(campoIdentificacion);
        }
        if (identificacionValor.length >= 5) {
            verificarIdentificacionExistente(identificacionValor);
        }
    }
}

function configurarIdentificacionClienteFormulario(formId) {
    const form = document.getElementById(formId);
    const tipoIdentificacion = document.getElementById('tipo_identificacion');
    const identificacion = document.getElementById('identificacion');

    if (!form || !tipoIdentificacion || !identificacion) return;

    if (typeof $ !== 'undefined' && $('#tipo_identificacion').length) {
        $('#tipo_identificacion').select2({
            placeholder: 'Seleccionar...',
            allowClear: true
        });
        $('#tipo_identificacion').on('change', function () {
            const idTipo = String($(this).val() || '');
            const $nac = $('#nacionalidad');
            if (idTipo === '1' || idTipo === '3' || idTipo === '4') {
                if ($nac.length && $nac.find('option[value="54"]').length) {
                    $nac.val('54').trigger('change');
                }
            } else if (idTipo === '2' || idTipo === '5') {
                if ($nac.length) {
                    $nac.val('').trigger('change');
                }
            }
        });
    }

    const tieneTipoInicial = !!String($(tipoIdentificacion).val() || tipoIdentificacion.value || '');
    aplicarDisabledFormClienteExceptoTipo(form, !tieneTipoInicial);

    if (!tieneTipoInicial) {
        identificacion.disabled = true;
        identificacion.placeholder = 'Primero seleccione el tipo de identificación';
    }

    $(tipoIdentificacion).on('change', function () {
        if (this.value) {
            aplicarDisabledFormClienteExceptoTipo(form, false);
            identificacion.disabled = false;
            identificacion.placeholder = 'Número de identificación';
            const btnComprobarTipo = document.getElementById('btn_comprobar_identificacion');
            if (btnComprobarTipo) btnComprobarTipo.disabled = false;
            $('.inputgroupidentificacion').removeClass('disabled');
            window.setTimeout(function () {
                identificacion.focus();
            }, 50);
        } else {
            aplicarDisabledFormClienteExceptoTipo(form, true);
            identificacion.disabled = true;
            identificacion.value = '';
            identificacion.placeholder = 'Primero seleccione el tipo de identificación';
            identificacion.classList.remove('is-valid', 'is-invalid');
            $('.inputgroupidentificacion').addClass('disabled');
        }
        identificacion.setCustomValidity('');
        validarIdentificacion();
    });

    identificacion.addEventListener('input', function () {
        validarIdentificacion();
    });
    identificacion.addEventListener('blur', function () {
        ejecutarComprobacionIdentificacionCliente();
    });

    const btnComprobarIdent = document.getElementById('btn_comprobar_identificacion');
    if (btnComprobarIdent) {
        btnComprobarIdent.addEventListener('click', function (e) {
            e.preventDefault();
            ejecutarComprobacionIdentificacionCliente();
        });
    }

    if (tieneTipoInicial && identificacion.value.trim()) {
        validarIdentificacion();
    }
}

document.addEventListener('DOMContentLoaded', function() {
    configurarIdentificacionClienteFormulario('formEditarCliente');
});
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
<script src="parts/universal/js/javascript_direcciones.js?v=<?php echo $vJavascriptDirecciones; ?>"></script>
