<!-- JAVASCRIPT CUSTOM CREAR CLIENTE -->
<script>

var autocompletandoCliente = false;
/** true tras comprobar identificación y que el cliente no exista (formulario desbloqueado) */
var formularioClienteDesbloqueado = false;

// Event listeners
document.addEventListener('DOMContentLoaded', function() {
    
    // Inicializar Select2 para campos de cliente
    $('#tipo_identificacion').select2({
        placeholder: 'Seleccionar...',
        allowClear: true
    });
    
    $('#nacionalidad').select2({
        placeholder: 'Seleccionar...',
        allowClear: true
    });

    $('#tipo_identificacion').on('change', function () {
        const idTipo = String($(this).val() || '');
        const $nac = $('#nacionalidad');
        // DNI (1), CIF (3), pasaporte español (4) → nacionalidad Española (54)
        if (idTipo === '1' || idTipo === '3' || idTipo === '4') {
            if ($nac.find('option[value="54"]').length) {
                $nac.val('54').trigger('change');
            }
        } else if (idTipo === '2' || idTipo === '5') {
            // NIE (2) u Otros (5) → vaciar nacionalidad
            $nac.val('').trigger('change');
        }
    });
    
    $('#sexo').select2({
        placeholder: 'Seleccionar...',
        allowClear: true
    });

    // Select2 no siempre dispara el listener `change` del formulario: revalidar botón Crear explícitamente
    $('#sexo, #nacionalidad, #tipo_identificacion, #pais, #c_provincia, #c_poblacion').off('change.verifBtnCliente select2:select.verifBtnCliente select2:clear.verifBtnCliente');
    $('#sexo, #nacionalidad, #tipo_identificacion, #pais, #c_provincia, #c_poblacion').on(
        'change.verifBtnCliente select2:select.verifBtnCliente select2:clear.verifBtnCliente',
        function () {
            verificarEstadoBotonCrear();
        }
    );
    
    const emailInicial = document.getElementById('email');
    if (emailInicial) {
        emailInicial.removeAttribute('required');
    }

    // Configurar validaciones de cliente
    configurarValidacion();
    configurarValidacionesEspeciales();
    
    // Configurar manejo del submit del formulario
    configurarSubmitFormulario();
    
    
    // Verificar estado del botón al cambiar cualquier campo
    verificarEstadoBotonCrear();

});

/**
 * Configurar validación del formulario
 */
function configurarValidacion() {
    const form = document.getElementById('formCrearCliente');
    if (!form) return;

    function revalidarBotonCrearDesdeCampo(field) {
        if (!field || field.id === 'email') {
            verificarEstadoBotonCrear();
            return;
        }
        if (field.classList && field.classList.contains('date-mask')) {
            verificarEstadoBotonCrear();
            return;
        }
        if (field.hasAttribute('required')) {
            validarCampo(field);
        }
        verificarEstadoBotonCrear();
    }

    form.addEventListener('input', function (event) {
        revalidarBotonCrearDesdeCampo(event.target);
    }, true);

    form.addEventListener('change', function (event) {
        revalidarBotonCrearDesdeCampo(event.target);
    }, true);

    form.addEventListener('blur', function () {
        verificarEstadoBotonCrear();
    }, true);
}
       
function mostrarLoaderUniversal(texto_loader_parsed) {
    mostrarLoaderUniversalCliente(texto_loader_parsed);
}

function mostrarLoaderUniversalCliente(texto_loader_parsed) {
    const loaderContainer = document.getElementById('loaderContainer-cliente');
    const textLoader = document.getElementById('textLoader-cliente');
    
    if (loaderContainer) {
        // Establecer opacity inicial a 0 y display a flex
        loaderContainer.style.opacity = '0';
        loaderContainer.style.display = 'flex';
        loaderContainer.style.transition = 'opacity 0.4s ease-in-out';
        
        // Forzar reflow para que la transición funcione
        void loaderContainer.offsetWidth;
        
        // Hacer fadeIn
        setTimeout(function() {
            loaderContainer.style.opacity = '1';
        }, 10);
    }
    
    if (textLoader) {
        textLoader.textContent = '' + texto_loader_parsed + '...';
    }
}



function ocultarLoaderUniversal() {
    const loaderContainer = document.getElementById('loaderContainer-cliente');
    const textLoader = document.getElementById('textLoader-cliente');
    
    if (loaderContainer) {
        // Asegurar que la transición esté configurada
        loaderContainer.style.transition = 'opacity 0.4s ease-in-out';
        
        // Hacer fadeOut
        loaderContainer.style.opacity = '0';
        
        // Ocultar después de que termine la animación
        setTimeout(function() {
            loaderContainer.style.display = 'none';
            loaderContainer.style.opacity = '1'; // Resetear para la próxima vez
        }, 400);
    }
    
    if (textLoader) {
        textLoader.textContent = '';
    }
}
/**
 * Enviar formulario de lote por AJAX
 * @param {function} [onSendFailed] Se llama si no hay redirección (error de red, validación servidor, etc.) para restaurar el botón de envío.
 */
function enviarFormularioCliente(onSendFailed) {
    
    const form = document.getElementById('formCrearCliente');
    if (!form) {
        console.error('Formulario no encontrado');
        ocultarLoaderUniversal();
        if (typeof onSendFailed === 'function') {
            onSendFailed();
        }
        return;
    }
    mostrarLoaderUniversal("Creando cliente...");
    ['f_vencimiento', 'f_nacimiento'].forEach(function (id) {
        const campo = document.getElementById(id);
        if (campo && window.TpvFecha) {
            const iso = window.TpvFecha.toIso(campo.value);
            if (iso) {
                campo.value = iso;
            }
        }
    });
    const formData = new FormData(form);
    
    fetch(form.action, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        },
        redirect: 'follow'
    })
    .then(response => {
        if (response.ok) {
            return response.json().then(data => {
                if (data.success) {
                    ocultarLoaderUniversal();
                    // Usar la URL que viene del servidor en el JSON
                    if (data.url) {
                        if (data.firma_digital === 'true') {
                            mostrarLoaderUniversalCliente('Solicite al cliente que firme desde el dispositivo de firma, por favor esperar...');
                            window.location.href = data.url;
                        } else {
                            window.location.href = data.url;
                        }
                    } else {
                        ocultarLoaderUniversal();
                        Swal.fire({
                            title: data.message || 'Cliente creado',
                            text: data.message || 'El cliente se ha creado correctamente',
                            icon: 'success',
                            confirmButtonText: 'Aceptar'
                        }).then(() => {
                            window.location.href = 'clientes.php';
                        });
                    }
                } else {
                    ocultarLoaderUniversal();
                    throw new Error(data.error || data.message || 'Error al crear el cliente');
                }
            });
        } else {
            ocultarLoaderUniversal();
            throw new Error('Error en la respuesta del servidor');
        }
    })
    .catch(error => {
        ocultarLoaderUniversal();
        if (typeof onSendFailed === 'function') {
            onSendFailed();
        }
        ocultarLoaderUniversal();
        Swal.fire({
            title: 'Error',
            text: error.message || 'Error al crear el cliente. Por favor, inténtelo de nuevo.',
            icon: 'error',
            confirmButtonText: 'Aceptar'
        });
    });
}

/**
 * Configurar manejo del submit del formulario
 * Deshabilita el botón y muestra loader al enviar
 */
function configurarSubmitFormulario() {
    const form = document.getElementById('formCrearCliente');
    const btnCrearCliente = document.getElementById('btnCrearCliente');
    const loaderCrearCliente = document.getElementById('loaderCrearCliente');
    const iconCrearCliente = document.getElementById('iconCrearCliente');
    const textCrearCliente = document.getElementById('textCrearCliente');
    
    let formSubmitted = false;
    
    form.addEventListener('submit', function(e) {
        // Si el formulario ya se está enviando, prevenir envío múltiple
        if (formSubmitted) {
            e.preventDefault();
            return false;
        }
        
        if (!formularioClienteValidoParaCrear() || !emailOpcionalValidoParaEnviar()) {
            e.preventDefault();
            form.classList.add('was-validated');
            verificarEstadoBotonCrear();
            return false;
        }
        
        // Comprobar condiciones de SMS antes de enviar
        // Marcar como enviado
        formSubmitted = true;
        
        // Deshabilitar botón
        btnCrearCliente.disabled = true;
        
        // Mostrar loader y ocultar icono
        if (loaderCrearCliente) {
            loaderCrearCliente.classList.remove('d-none');
        }
        if (iconCrearCliente) {
            iconCrearCliente.style.display = 'none';
        }
        if (textCrearCliente) {
            textCrearCliente.textContent = 'Creando...';
        }
        
        e.preventDefault();
        enviarFormularioCliente(function restaurarEnvioCliente() {
            formSubmitted = false;
            verificarEstadoBotonCrear();
            if (loaderCrearCliente) {
                loaderCrearCliente.classList.add('d-none');
            }
            if (iconCrearCliente) {
                iconCrearCliente.style.display = '';
            }
            if (textCrearCliente) {
                textCrearCliente.textContent = 'Crear Cliente';
            }
        });
        return false;
    });
}

/**
 * Indica si un control del formulario está habilitado (no disabled ni dentro de .input-group.disabled).
 */
function campoFormularioClienteEditable(campo) {
    if (!campo || campo.disabled) return false;
    const grupo = campo.closest('.input-group-merge, .input-group');
    if (grupo && grupo.classList.contains('disabled')) return false;
    return true;
}

/**
 * Comprueba formato básico de email (solo si hay texto).
 */
function formatoEmailBasicoValido(val) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val);
}

/**
 * Email opcional: solo valida formato al enviar si el usuario escribió algo.
 */
function emailOpcionalValidoParaEnviar() {
    const email = document.getElementById('email');
    if (!email) return true;
    email.removeAttribute('required');
    const val = String(email.value || '').trim();
    if (!val) return true;
    return formatoEmailBasicoValido(val);
}

/**
 * Valida si el formulario puede crear cliente (estado del botón).
 * No incluye #email: es opcional y no influye en habilitar el botón.
 */
function formularioClienteValidoParaCrear() {
    if (!formularioClienteDesbloqueado) {
        return false;
    }

    const form = document.getElementById('formCrearCliente');
    if (!form) {
        return false;
    }

    let valido = true;
    form.querySelectorAll('[required]').forEach(function (campo) {
        if (campo.id === 'email') return;
        if (!campoFormularioClienteEditable(campo)) return;
        const val = campo.value != null ? String(campo.value).trim() : '';
        if (val === '' || !campo.checkValidity()) {
            valido = false;
        }
        if (campo.classList && campo.classList.contains('is-invalid')) {
            valido = false;
        }
    });

    return valido;
}

/**
 * Verificar estado del botón Crear Cliente.
 */
function verificarEstadoBotonCrear() {
    const btnCrearCliente = document.getElementById('btnCrearCliente');
    if (!btnCrearCliente) return;
    btnCrearCliente.disabled = !formularioClienteValidoParaCrear();
}

/**
 * Validar un campo específico
 */
function validarCampo(field) {
    if (!field || field.id === 'email') {
        return true;
    }
    const isValid = field.checkValidity();
    const feedbackId = 'validation_feedback_' + field.id;
    let feedbackEl = document.getElementById(feedbackId);

    const mergeIdent =
        field.id === 'identificacion'
            ? field.closest('.inputgroupidentificacion, .input-group')
            : null;
    const label = mergeIdent
        ? mergeIdent.querySelector('label[for="' + field.id + '"]')
        : field.parentElement.querySelector('label[for="' + field.id + '"]');
    const container = field.parentElement;
    
    if (isValid) {
        field.classList.remove('is-invalid');
        field.classList.add('is-valid');

        if (feedbackEl) {
            feedbackEl.remove();
            feedbackEl = null;
        }
        
        // Actualizar label si existe
        if (label) {
            label.classList.remove('text-danger');
            label.classList.add('text-success');
        }
    } else {
        field.classList.remove('is-valid');
        field.classList.add('is-invalid');

        const msg = (field.validationMessage && String(field.validationMessage).trim() !== '')
            ? String(field.validationMessage)
            : 'Error: campo no válido';

        if (!feedbackEl) {
            feedbackEl = document.createElement('div');
            feedbackEl.id = feedbackId;
            feedbackEl.className = 'invalid-feedback d-block';
        }
        if (mergeIdent) {
            mergeIdent.insertAdjacentElement('afterend', feedbackEl);
        } else if (label && container) {
            container.insertBefore(feedbackEl, label);
        } else if (container) {
            container.appendChild(feedbackEl);
        }
        feedbackEl.textContent = msg;
        
        // Actualizar label si existe
        if (label) {
            label.classList.remove('text-success');
            label.classList.add('text-danger');
        }
    }
    
    return isValid;
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
            verificarEstadoBotonCrear();
        });
    }
    
    const email = document.getElementById('email');
    if (email) {
        email.removeAttribute('required');
        email.addEventListener('blur', function () {
            const val = String(this.value || '').trim();
            if (!val) {
                this.classList.remove('is-invalid', 'is-valid');
            } else if (!formatoEmailBasicoValido(val)) {
                this.classList.add('is-invalid');
                this.classList.remove('is-valid');
            } else {
                this.classList.remove('is-invalid');
            }
        });
    }

    if (!window.TpvFecha) {
        return;
    }
    configurarValidacionFechaCliente('f_nacimiento', validarCampoFechaNacimiento);
    configurarValidacionFechaCliente('f_vencimiento', validarCampoFechaVencimiento);
}

function validarCampoFechaNacimiento(input) {
    const fechaSeleccionada = window.TpvFecha.parseLocal(input.value);
    if (!fechaSeleccionada) {
        verificarEstadoBotonCrear();
        return;
    }
    const hoy = window.TpvFecha.hoyLocal();

    let edad = hoy.getFullYear() - fechaSeleccionada.getFullYear();
    const mes = hoy.getMonth() - fechaSeleccionada.getMonth();
    if (mes < 0 || (mes === 0 && hoy.getDate() < fechaSeleccionada.getDate())) {
        edad--;
    }

    if (fechaSeleccionada > hoy) {
        Swal.fire({
            title: 'Error',
            text: 'La fecha de nacimiento no puede ser futura',
            icon: 'error',
            confirmButtonText: 'Aceptar',
            confirmButtonColor: '#dc3545'
        });
        input.value = '';
        input.setCustomValidity('La fecha de nacimiento no puede ser futura');
        input.classList.add('is-invalid');
    } else if (edad < 18) {
        Swal.fire({
            title: 'Error',
            text: 'El cliente debe ser mayor de edad (18 años)',
            icon: 'error',
            confirmButtonText: 'Aceptar',
            confirmButtonColor: '#dc3545'
        });
        input.value = '';
        input.setCustomValidity('Debe ser mayor de edad');
        input.classList.add('is-invalid');
    } else {
        input.setCustomValidity('');
        input.classList.remove('is-invalid');
    }
    verificarEstadoBotonCrear();
}

function validarCampoFechaVencimiento(input) {
    const fechaSeleccionada = window.TpvFecha.parseLocal(input.value);
    if (!fechaSeleccionada) {
        verificarEstadoBotonCrear();
        return;
    }
    const hoy = window.TpvFecha.hoyLocal();

    if (fechaSeleccionada <= hoy) {
        Swal.fire({
            title: 'Error',
            text: 'La fecha de vencimiento debe ser mayor a la fecha actual',
            icon: 'error',
            confirmButtonText: 'Aceptar',
            confirmButtonColor: '#dc3545'
        });
        input.value = '';
        input.setCustomValidity('La fecha de vencimiento no puede ser pasada');
        input.classList.add('is-invalid');
    } else {
        input.setCustomValidity('');
        input.classList.remove('is-invalid');
    }
    verificarEstadoBotonCrear();
}

/**
 * Valida al salir del campo cuando la fecha DD/MM/YYYY está completa (custom.js → tpv-fecha-completa).
 */
function configurarValidacionFechaCliente(inputId, validarFn) {
    const input = document.getElementById(inputId);
    if (!input) {
        return;
    }

    input.addEventListener('tpv-fecha-completa', function () {
        validarFn(input);
    });
    input.addEventListener('input', function () {
        verificarEstadoBotonCrear();
    });
}

/**
 * Validar identificación según tipo
 */
function validarIdentificacion() {
    const tipoIdentificacion = document.getElementById('tipo_identificacion').value;
    const identificacion = document.getElementById('identificacion').value;
    
    if (!tipoIdentificacion || !identificacion) return true;
    
    // TODO: Implementar validación
    
    return true;
}

/**
 * Verificar si el teléfono ya existe en la base de datos (solo validación de duplicados)
 */
function verificarTelefonoExistente(telefono) {
    if (!telefono || telefono.length < 9) return;

    const idClienteEl = document.getElementById('id_cliente');
    let idCliente = idClienteEl && idClienteEl.value ? String(idClienteEl.value).trim() : '';
    if (idCliente === 'false') {
        idCliente = '';
    }

    $.ajax({
        url: 'parts/clientes/crear/ajax_verificar_cliente.php',
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
 * Verificar si la identificación ya existe en la base de datos.
 * Si existe: aviso y no se cargan datos (crear cliente nuevo).
 * Si no existe: se habilita el resto del formulario.
 */
function verificarIdentificacionExistente(identificacion) {
    if (!identificacion || identificacion.length < 5) {
        console.log('Identificación muy corta o vacía');
        return;
    }

    mostrarLoaderUniversal('Comprobando cliente...');

    $.ajax({
        url: 'parts/clientes/crear/ajax_verificar_cliente.php',
        method: 'GET',
        dataType: 'json',
        data: {
            action: 'verificar_identificacion',
            valor: identificacion
        },
        success: function(response) {
            ocultarLoaderUniversal();
            console.log('Respuesta AJAX identificación:', response);

            const campoIdentificacion = document.getElementById('identificacion');

            if (response.existe) {
                if (campoIdentificacion) {
                    campoIdentificacion.setCustomValidity(response.message || 'Esta identificación ya está registrada');
                    if (typeof validarCampo === 'function') {
                        validarCampo(campoIdentificacion);
                    }
                }

                Swal.fire({
                    title: '¡Identificación Duplicada!',
                    text: response.message || 'Este cliente ya existe en el sistema',
                    icon: 'warning',
                    confirmButtonText: 'Entendido',
                    confirmButtonColor: '#f39c12'
                }).then(function () {
                    if (campoIdentificacion) {
                        campoIdentificacion.value = '';
                        campoIdentificacion.setCustomValidity('');
                        campoIdentificacion.focus();
                    }
                });
            } else {
                if (campoIdentificacion) {
                    campoIdentificacion.setCustomValidity('');
                    if (typeof validarCampo === 'function') {
                        validarCampo(campoIdentificacion);
                    }
                }
                const formClienteAuto = document.getElementById('formCrearCliente');
                if (formClienteAuto) {
                    aplicarDisabledFormClienteExceptoTipo(formClienteAuto, false);
                }
                window.setTimeout(verificarEstadoBotonCrear, 0);
            }
        },
        error: function(xhr, status, error) {
            ocultarLoaderUniversal();
            console.error('Error al verificar identificación:', error);
            console.error('Status:', status);
            console.error('Response:', xhr.responseText);
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Error',
                    text: 'No se pudo comprobar la identificación. Inténtelo de nuevo.',
                    icon: 'error',
                    confirmButtonText: 'Aceptar'
                });
            }
        }
    });
}

/**
 * Valida formato y llama a la verificación en BD (misma lógica que blur en #identificacion; usa el botón Comprobar).
 */
function ejecutarComprobacionIdentificacionCliente() {
    console.log('Llamando a ejecutarComprobacionIdentificacionCliente dentro de la funcion ');
    if (autocompletandoCliente) {
        return;
    }
    const campoIdentificacion = document.getElementById('identificacion');
    const tipoIdentificacion = document.getElementById('tipo_identificacion');
    if (!campoIdentificacion || !tipoIdentificacion) return;

    const identificacionValor = campoIdentificacion.value.trim();
    const tipoIdValor = tipoIdentificacion.value;

    if (!identificacionValor || !tipoIdValor) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Faltan datos',
                text: !tipoIdValor
                    ? 'Seleccione primero el tipo de identificación'
                    : 'Escriba el número de identificación',
                icon: 'warning',
                confirmButtonText: 'Aceptar'
            });
        }
        return;
    }

    const tiposValidacionSpain = ['1', '2', '3', '4'];

    if (tiposValidacionSpain.includes(tipoIdValor)) {
        if (typeof validarIdentificacionSpain === 'function') {
            console.log('Llamando a validarIdentificacionSpain');
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
            console.log('Llamando a verificarIdentificacionExistente');
            verificarIdentificacionExistente(identificacionValor);
        }
    } else if (tipoIdValor === '5') {
        campoIdentificacion.setCustomValidity('');
        if (typeof validarCampo === 'function') {
            validarCampo(campoIdentificacion);
        }
        if (identificacionValor.length >= 5) {
            console.log('Llamando a verificarIdentificacionExistente');
            verificarIdentificacionExistente(identificacionValor);
        }
    }
}

/**
 * Cargar formulario de dirección dinámicamente
 */
function cargarFormularioDireccionCliente(tipo, callback) {
    $.ajax({
        url: 'parts/clientes/crear/load_formulario_direccion.php',
        /*url: 'parts/universal/direcciones/formulario_direccion_edit.php',*/
        method: 'GET',
        data: { tipo: tipo },
        success: function(html) {
            $('#container_direccion').html(html);
            
            // Select2 con ajax (países / provincias / poblaciones) — no usar solo Select2 básico
            if (typeof window.inicializarDireccionesSelect2ConAjax === 'function') {
                window.inicializarDireccionesSelect2ConAjax();
            } else if (typeof inicializarSelect2Direcciones === 'function') {
                inicializarSelect2Direcciones();
            }
            
            // Ejecutar callback si existe
            if (callback) {
                setTimeout(callback, 500);
            }
        },
        error: function(xhr, status, error) {
            console.error('Error al cargar formulario de dirección:', error);
        }
    });
}

/**
 * Habilita o deshabilita todos los input/select del formulario de cliente excepto `tipo_identificacion`.
 * No toca hidden ni submit/button. Los controles dentro de `.input-group` usan la clase `disabled`
 * del grupo (estilos en core.css). También `btnSolicitarAutorizacionSMS` y `btn_comprobar_identificacion`.
 */
function aplicarDisabledFormClienteExceptoTipo(form, deshabilitar) {
    if (!form) return;
    const inputGruposAfectar = new Set();
    form.querySelectorAll('input, select').forEach(function (el) {
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
    const btnSms = document.getElementById('btnSolicitarAutorizacionSMS');
    if (btnSms) {
        btnSms.disabled = deshabilitar;
    }
    const btnComprobarIdent = document.getElementById('btn_comprobar_identificacion');
    if (btnComprobarIdent) {
        btnComprobarIdent.disabled = deshabilitar;
    }
    formularioClienteDesbloqueado = !deshabilitar;
    verificarEstadoBotonCrear();
}

/**
 * Autocompletar formulario con datos del cliente existente
 */
function autocompletarFormularioCliente(cliente, direccion, datos_cliente) {
    /*console.log('Autocompletando formulario con:', cliente, direccion, datos_cliente);*/
    
    // Activar flag para prevenir bucle
    autocompletandoCliente = true;
    
    // Cargar formulario de dirección EDIT primero
    cargarFormularioDireccionCliente('edit', function() {
        // Después de cargar el formulario, autocompletar datos
        autocompletarDatosCliente(cliente, direccion, datos_cliente);
        
        // Desactivar flag después de un delay
        setTimeout(function() {
            autocompletandoCliente = false;
            console.log('Autocompletado finalizado');
        }, 1000);
    });
}

/**
 * Autocompletar datos del cliente (después de cargar el formulario correcto)
 */
function autocompletarDatosCliente(cliente, direccion, datos_cliente) {
    /*console.log('Autocompletando datos con:', cliente, direccion, datos_cliente);*/

    const formClienteAuto = document.getElementById('formCrearCliente');
    if (formClienteAuto) {
        aplicarDisabledFormClienteExceptoTipo(formClienteAuto, false);
    }
    
    // Poner el ID del cliente en el campo hidden
    document.getElementById('id_cliente').value = cliente.id_cliente;
    
    // Datos de identificación
    if (cliente.tipo_identificacion_id) {
        $('#tipo_identificacion').val(cliente.tipo_identificacion_id).trigger('change');
    }
    if (cliente.identificacion) {
        const campoIdentificacion = document.getElementById('identificacion');
        if (campoIdentificacion) campoIdentificacion.value = cliente.identificacion;
    }
    if (cliente.nacionalidad_id) {
        $('#nacionalidad').val(cliente.nacionalidad_id).trigger('change');
    }
    
    // Fecha de vencimiento (prioridad a datos_cliente)
    const f_vencimiento = datos_cliente?.f_vencimiento || cliente.f_vencimiento;
    if (f_vencimiento) {
        window.TpvFecha.setValor(document.getElementById('f_vencimiento'), f_vencimiento);
    }
    
    // Datos personales
    if (cliente.nombre) {
        document.getElementById('nombre').value = cliente.nombre;
    }
    if (cliente.apellido) {
        document.getElementById('apellido').value = cliente.apellido;
    }
    
    // Fecha de nacimiento (prioridad a datos_cliente)
    const f_nacimiento = datos_cliente?.f_nacimiento || cliente.f_nacimiento;
    if (f_nacimiento) {
        window.TpvFecha.setValor(document.getElementById('f_nacimiento'), f_nacimiento);
    }
    
    // Sexo (prioridad a datos_cliente)
    const sexo = datos_cliente?.sexo || cliente.sexo;
    if (sexo) {
        $('#sexo').val(sexo).trigger('change');
    }
    
    const email = datos_cliente?.email || cliente.email;
    if (email) {
        document.getElementById('email').value = email;
    }
    
    // Datos de dirección si existen
    if (direccion) {
        if (direccion.direccion) {
            const direccionField = document.getElementById('direccion');
            if (direccionField) direccionField.value = direccion.direccion;
        }
        if (direccion.codigo_postal) {
            const codigoPostalField = document.getElementById('codigo_postal');
            if (codigoPostalField) codigoPostalField.value = direccion.codigo_postal;
        }
        
        // Cargar país si existe
        if (direccion.rel_id_pais && direccion.c_pais) {
            const paisField = $('#pais');
            if (paisField.length) {
                // Crear la opción y agregarla al select
                const newOption = new Option(direccion.c_pais, direccion.rel_id_pais, true, true);
                paisField.append(newOption).trigger('change');
            }
        }
        
        // Cargar provincia si existe (con timeout para que país cargue primero)
        if (direccion.rel_id_provincia && direccion.c_provincia) {
            setTimeout(function() {
                const provinciaField = $('#c_provincia');
                if (provinciaField.length) {
                    // Crear la opción y agregarla al select
                    const newOption = new Option(direccion.c_provincia, direccion.rel_id_provincia, true, true);
                    provinciaField.append(newOption).trigger('change');
                }
            }, 500);
        }
        // Cargar población si existe (con timeout para que provincia cargue primero)
        if (direccion.rel_id_poblacion && direccion.c_poblacion) {
            setTimeout(function() {
                const poblacionField = $('#c_poblacion');
                if (poblacionField.length) {
                    // Crear la opción y agregarla al select
                    const newOption = new Option(direccion.c_poblacion, direccion.rel_id_poblacion, true, true);
                    poblacionField.append(newOption).trigger('change');
                }
            }, 1000);
        }
    }
    // Teléfono: solo `clientes.telefono`. Se aplica al final (tras cargar dirección/selects) para que no lo pise otro handler.
    if (cliente && cliente.telefono !== undefined && cliente.telefono !== null) {
        const telStr = String(cliente.telefono).trim();
        if (telStr !== '') {
            setTimeout(function() {
                const inpTel = document.getElementById('telefono');
                if (inpTel) {
                    inpTel.value = telStr;
                }
            }, 1250);
        }
    }

    ocultarLoaderUniversal();
    // Verificar estado del botón después de autocompletar
    setTimeout(function() {
        verificarEstadoBotonCrear();
    }, 1500);
    
    Swal.fire({
        title: 'Datos Cargados',
        text: 'El formulario se ha completado con los datos del cliente existente',
        icon: 'success',
        timer: 2000,
        showConfirmButton: false
    });

    const identificacion = document.getElementById('identificacion');
    if (identificacion) {
        identificacion.blur();
    }
}

// Agregar validación de identificación al cambiar tipo
document.addEventListener('DOMContentLoaded', function() {
    const tipoIdentificacion = document.getElementById('tipo_identificacion');
    const identificacion = document.getElementById('identificacion');
    const formCliente = document.getElementById('formCrearCliente');
    
    if (tipoIdentificacion && identificacion && formCliente) {
        aplicarDisabledFormClienteExceptoTipo(formCliente, true);
        identificacion.placeholder = 'Primero seleccione el tipo de identificación';
        
        // Habilitar/deshabilitar campo según selección de tipo (usar jQuery para Select2)
        $(tipoIdentificacion).on('change', function() {
            if (autocompletandoCliente) {
                if (this.value) {
                    identificacion.disabled = false;
                    const btnComprobarAutoc = document.getElementById('btn_comprobar_identificacion');
                    if (btnComprobarAutoc) btnComprobarAutoc.disabled = false;
                }
                return;
            }
            
            if (this.value) {
                identificacion.disabled = false;
                identificacion.placeholder = 'Número de identificación';
                window.setTimeout(function () {
                    identificacion.focus();
                }, 50);
                const btnComprobarTipo = document.getElementById('btn_comprobar_identificacion');
                if (btnComprobarTipo) btnComprobarTipo.disabled = false;
                $('#btn_comprobar_identificacion').removeClass('d-none');
                $('.inputgroupidentificacion').removeClass('disabled');
                
            } else {
                aplicarDisabledFormClienteExceptoTipo(formCliente, true);
                identificacion.value = '';
                identificacion.placeholder = 'Primero seleccione el tipo de identificación';
                identificacion.classList.remove('is-valid', 'is-invalid');
                $('#btn_comprobar_identificacion').addClass('d-none');
                $('.inputgroupidentificacion').addClass('disabled');
            }
            validarIdentificacion();
        });

        $(tipoIdentificacion).on('select2:close', function () {
            if (autocompletandoCliente) return;
            if (!$(tipoIdentificacion).val()) return;
            window.setTimeout(function () {
                if (!identificacion.disabled) {
                    identificacion.focus();
                }
            }, 10);
        });
        
        identificacion.addEventListener('input', validarIdentificacion);
        
        // Verificar si la identificación ya existe al salir del campo (blur) o con el botón Comprobar
        identificacion.addEventListener('blur', function() {
            if (autocompletandoCliente) {
                console.log('Autocompletando, no validar');
                return;
            }
            ejecutarComprobacionIdentificacionCliente();
        });

        const btnComprobarIdent = document.getElementById('btn_comprobar_identificacion');
        if (btnComprobarIdent) {
            btnComprobarIdent.addEventListener('click', function (e) {
                e.preventDefault();
                console.log('Llamando a ejecutarComprobacionIdentificacionCliente');
                ejecutarComprobacionIdentificacionCliente();
            });
        }
    }
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

/**
 * Reinicializar Select2 para campos de dirección después de cargar dinámicamente
 */
function inicializarSelect2Direcciones() {
    if (typeof window.inicializarDireccionesSelect2ConAjax === 'function') {
        window.inicializarDireccionesSelect2ConAjax();
        return;
    }
    if ($('#pais').data('select2')) {
        $('#pais').select2('destroy');
    }
    if ($('#c_provincia').data('select2')) {
        $('#c_provincia').select2('destroy');
    }
    if ($('#c_poblacion').data('select2')) {
        $('#c_poblacion').select2('destroy');
    }
    $('#pais, #c_provincia, #c_poblacion').each(function () {
        var $this = $(this);
        if (typeof select2Focus === 'function') {
            select2Focus($this);
        }
        $this.select2({
            dropdownParent: $this.parent(),
            placeholder: placeholderSelect2,
            allowClear: true
        });
    });
    $('#pais, #c_provincia, #c_poblacion').off('change.verifBtnCliente select2:select.verifBtnCliente select2:clear.verifBtnCliente');
    $('#pais, #c_provincia, #c_poblacion').on(
        'change.verifBtnCliente select2:select.verifBtnCliente select2:clear.verifBtnCliente',
        verificarEstadoBotonCrear
    );
}

</script>
<?php
$vComprobarIdentificacionSpain = filemtime(__DIR__ . '/../../universal/js/comprobar_identificacion_spain.js');
$vJavascriptDirecciones = filemtime(__DIR__ . '/../../universal/js/javascript_direcciones.js');
?>
<script src="parts/universal/js/comprobar_identificacion_spain.js?v=<?php echo $vComprobarIdentificacionSpain; ?>"></script>
<script src="parts/universal/js/javascript_direcciones.js?v=<?php echo $vJavascriptDirecciones; ?>"></script>
