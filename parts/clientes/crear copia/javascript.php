<!-- JavaScript para el formulario de creación de cliente (flujo identificación = lotes/crear) -->
<?php if (isset($app_country_id) && (int)$app_country_id === 68) { ?>
<?php
$vComprobarIdentificacionSpain = filemtime(__DIR__ . '/../../universal/js/comprobar_identificacion_spain.js');
$vJavascriptDirecciones = filemtime(__DIR__ . '/../../universal/js/javascript_direcciones.js');
?>
<script src="parts/universal/js/comprobar_identificacion_spain.js?v=<?php echo $vComprobarIdentificacionSpain; ?>"></script>
<?php } ?>
<script>
var autocompletandoCliente = false;
var identificacionComprobada = false;

document.addEventListener('DOMContentLoaded', function() {
    $('#nacionalidad').select2({
        placeholder: 'Seleccionar...',
        allowClear: true
    });

    $('#sexo').select2({
        placeholder: 'Seleccionar...',
        allowClear: true
    });

    configurarValidacion();
    configurarValidacionesEspeciales();
    configurarEnvioFormulario();

    var placeholderSelect2 = 'Seleccionar...';
    var select2 = $('.select2:not(#pais):not(#c_provincia):not(#c_poblacion):not(#tipo_identificacion):not(#nacionalidad):not(#sexo)');
    if (select2.length) {
        select2.each(function () {
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
    }

    cargarOpcionesTipoIdentificacion(function () {
        initSelect2TipoIdentificacion();
        configurarSyncNacionalidadPorTipo();
        iniciarFormularioIdentificacionCliente();
    });
});

/**
 * Rellena #tipo_identificacion si el PHP no devolvió opciones (p. ej. country_id vacío).
 */
function cargarOpcionesTipoIdentificacion(callback) {
    const sel = document.getElementById('tipo_identificacion');
    if (!sel) {
        if (callback) callback();
        return;
    }

    const tieneOpciones = Array.from(sel.querySelectorAll('option')).some(function (opt) {
        return String(opt.value || '').trim() !== '';
    });

    if (tieneOpciones) {
        if (callback) callback();
        return;
    }

    $.getJSON('parts/clientes/listar/get_tipos_identificacion.php')
        .done(function (data) {
            if (data && data.success && Array.isArray(data.tipos) && data.tipos.length) {
                sel.innerHTML = '<option value="">Seleccionar...</option>';
                data.tipos.forEach(function (t) {
                    const opt = document.createElement('option');
                    opt.value = String(t.id);
                    opt.textContent = t.texto || t.nombre || '';
                    sel.appendChild(opt);
                });
            }
            if (callback) callback();
        })
        .fail(function () {
            console.error('No se pudieron cargar los tipos de identificación');
            if (callback) callback();
        });
}

function initSelect2TipoIdentificacion() {
    const $t = $('#tipo_identificacion');
    if (!$t.length) return;

    if ($t.data('select2')) {
        $t.select2('destroy');
    }

    const $parent = $t.closest('.form-floating-outline, .form-floating');
    $t.select2({
        placeholder: 'Seleccionar...',
        allowClear: true,
        width: '100%',
        dropdownParent: $parent.length ? $parent : $('body')
    });
}

function aplicarNacionalidadPorTipoIdentificacion() {
    const idTipo = getTipoIdentificacionValor();
    const $nac = $('#nacionalidad');
    if (!idTipo || !$nac.length) return;
    if (idTipo === '1' || idTipo === '3' || idTipo === '4') {
        if ($nac.find('option[value="54"]').length) {
            $nac.val('54').trigger('change');
        }
    } else if (idTipo === '2' || idTipo === '5') {
        $nac.val('').trigger('change');
    }
}

function configurarSyncNacionalidadPorTipo() {
    $('#tipo_identificacion').off('change.syncNac').on('change.syncNac', function () {
        if (!identificacionComprobada && !autocompletandoCliente) return;
        aplicarNacionalidadPorTipoIdentificacion();
    });
}

/**
 * Sincroniza disabled nativo + Select2 (nacionalidad, sexo, etc.).
 */
function sincronizarSelect2Disabled(selectId, deshabilitar) {
    const el = document.getElementById(selectId);
    if (!el || selectId === 'tipo_identificacion') return;

    el.disabled = deshabilitar;
    const $el = $(el);
    if (!$el.length) return;

    $el.prop('disabled', deshabilitar);
    if ($el.data('select2')) {
        $el.select2('enable', !deshabilitar);
    }
}

function habilitarFormularioTrasComprobarIdentificacion() {
    identificacionComprobada = true;
    const form = document.getElementById('formCrearCliente');
    if (form) {
        aplicarDisabledFormLoteExceptoTipo(form, false);
    }
    aplicarNacionalidadPorTipoIdentificacion();
}

function bloquearFormularioHastaComprobarIdentificacion() {
    identificacionComprobada = false;
    const form = document.getElementById('formCrearCliente');
    if (form) {
        aplicarDisabledFormLoteExceptoTipo(form, true);
    }
}

function iniciarFormularioIdentificacionCliente() {
    const tipoIdentificacion = document.getElementById('tipo_identificacion');
    const identificacion = document.getElementById('identificacion');
    const formCliente = document.getElementById('formCrearCliente');

    if (!tipoIdentificacion || !identificacion || !formCliente) return;

    bloquearFormularioHastaComprobarIdentificacion();
    identificacion.disabled = false;
    identificacion.placeholder = 'Primero seleccione el tipo de identificación';
    $('.inputgroupidentificacion').addClass('disabled');

    $('#tipo_identificacion').off('change.identificacion').on('change.identificacion', function () {
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
            bloquearFormularioHastaComprobarIdentificacion();
            identificacion.disabled = false;
            identificacion.value = '';
            identificacion.placeholder = 'Primero seleccione el tipo de identificación';
            identificacion.classList.remove('is-valid', 'is-invalid');
            $('#btn_comprobar_identificacion').addClass('d-none');
            $('.inputgroupidentificacion').addClass('disabled');
            const idClienteEl = document.getElementById('id_cliente');
            if (idClienteEl) idClienteEl.value = 'false';
        }
        validarIdentificacion();
    });

    $('#tipo_identificacion').off('select2:close.identificacion').on('select2:close.identificacion', function () {
        if (autocompletandoCliente) return;
        if (!$('#tipo_identificacion').val()) return;
        window.setTimeout(function () {
            identificacion.focus();
        }, 10);
    });

    identificacion.addEventListener('input', validarIdentificacion);

    identificacion.addEventListener('blur', function () {
        if (autocompletandoCliente) return;
        ejecutarComprobacionIdentificacionLote();
    });

    const btnComprobarIdent = document.getElementById('btn_comprobar_identificacion');
    if (btnComprobarIdent) {
        btnComprobarIdent.addEventListener('click', function (e) {
            e.preventDefault();
            ejecutarComprobacionIdentificacionLote();
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

function configurarValidacion() {
    const form = document.getElementById('formCrearCliente');
    if (!form) return;

    form.addEventListener('input', function(event) {
        const field = event.target;
        if (field.hasAttribute('required')) {
            validarCampo(field);
        }
    });

    form.addEventListener('change', function(event) {
        const field = event.target;
        if (field.hasAttribute('required')) {
            validarCampo(field);
        }
    });
}

function validarCampo(field) {
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
        if (label) {
            label.classList.remove('text-success');
            label.classList.add('text-danger');
        }
    }

    return isValid;
}

function configurarEnvioFormulario() {
    const form = document.getElementById('formCrearCliente');
    if (!form) return;

    form.addEventListener('submit', function(event) {
        event.preventDefault();

        if (!form.checkValidity()) {
            form.classList.add('was-validated');
            return;
        }

        const btnCrear = document.getElementById('btnCrearCliente');
        if (!btnCrear) return;

        btnCrear.disabled = true;
        btnCrear.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Creando cliente...';

        const formData = new FormData(form);
        formData.append('f_alta', new Date().toISOString().split('T')[0]);
        crearCliente(formData);
    });
}

function crearCliente(formData) {
    fetch('parts/clientes/crear/procesar_cliente.php', {
        method: 'POST',
        body: formData
    })
    .then(function(response) {
        if (!response.ok) {
            throw new Error('HTTP error en procesar_cliente.php! status: ' + response.status);
        }
        return response.json();
    })
    .then(function(data) {
        if (data.success) {
            Swal.fire({
                title: '¡Cliente Creado!',
                text: data.message || 'El cliente se ha creado exitosamente',
                icon: 'success',
                confirmButtonText: 'Aceptar',
                confirmButtonColor: '#198754',
                timer: 3000,
                timerProgressBar: true
            }).then(function() {
                window.location.href = data.redirect;
            });
        } else {
            throw new Error(data.error || 'Error desconocido al crear el cliente');
        }
    })
    .catch(function(error) {
        Swal.fire({
            title: 'Error',
            text: 'No se pudo crear el cliente: ' + error.message,
            icon: 'error',
            confirmButtonText: 'Aceptar',
            confirmButtonColor: '#dc3545'
        });
        restaurarBotonCrear();
    });
}

function restaurarBotonCrear() {
    const btnCrear = document.getElementById('btnCrearCliente');
    if (btnCrear) {
        btnCrear.disabled = false;
        btnCrear.innerHTML = '<i class="icon-base ri ri-check-line me-2"></i>Crear Cliente';
    }
}

function configurarValidacionesEspeciales() {
    const codigoPostal = document.getElementById('codigo_postal');
    if (codigoPostal) {
        codigoPostal.addEventListener('input', function() {
            this.value = this.value.replace(/[^a-zA-Z0-9\s-]/g, '');
            this.value = this.value.toUpperCase();
        });
    }

    const telefono = document.getElementById('telefono');
    if (telefono) {
        let timeoutTelefono = null;
        telefono.addEventListener('input', function() {
            this.value = this.value.replace(/[^0-9\s\+\-\(\)]/g, '');
            if (timeoutTelefono) clearTimeout(timeoutTelefono);
            const telefonoValor = this.value.trim();
            if (telefonoValor.length >= 9) {
                timeoutTelefono = setTimeout(function() {
                    verificarTelefonoExistente(telefonoValor);
                }, 500);
            }
        });
    }

    const email = document.getElementById('email');
    if (email) {
        email.addEventListener('blur', function() {
            if (this.value && !this.checkValidity()) {
                this.classList.add('is-invalid');
            }
        });
    }

    if (window.TpvFecha) {
        configurarValidacionFechaCliente('f_nacimiento', validarCampoFechaNacimiento);
        configurarValidacionFechaCliente('f_vencimiento', validarCampoFechaVencimiento);
    }
}

function validarCampoFechaNacimiento(input) {
    const fechaSeleccionada = window.TpvFecha.parseLocal(input.value);
    if (!fechaSeleccionada) return;
    const hoy = window.TpvFecha.hoyLocal();
    let edad = hoy.getFullYear() - fechaSeleccionada.getFullYear();
    const mes = hoy.getMonth() - fechaSeleccionada.getMonth();
    if (mes < 0 || (mes === 0 && hoy.getDate() < fechaSeleccionada.getDate())) {
        edad--;
    }
    if (fechaSeleccionada > hoy) {
        Swal.fire({ title: 'Error', text: 'La fecha de nacimiento no puede ser futura', icon: 'error', confirmButtonText: 'Aceptar', confirmButtonColor: '#dc3545' });
        input.value = '';
        input.setCustomValidity('La fecha de nacimiento no puede ser futura');
        input.classList.add('is-invalid');
    } else if (edad < 18) {
        Swal.fire({ title: 'Error', text: 'El cliente debe ser mayor de edad (18 años)', icon: 'error', confirmButtonText: 'Aceptar', confirmButtonColor: '#dc3545' });
        input.value = '';
        input.setCustomValidity('Debe ser mayor de edad');
        input.classList.add('is-invalid');
    } else {
        input.setCustomValidity('');
        input.classList.remove('is-invalid');
    }
}

function validarCampoFechaVencimiento(input) {
    const fechaSeleccionada = window.TpvFecha.parseLocal(input.value);
    if (!fechaSeleccionada) return;
    const hoy = window.TpvFecha.hoyLocal();
    if (fechaSeleccionada <= hoy) {
        Swal.fire({ title: 'Error', text: 'La fecha de vencimiento debe ser mayor a la fecha actual', icon: 'error', confirmButtonText: 'Aceptar', confirmButtonColor: '#dc3545' });
        input.value = '';
        input.setCustomValidity('La fecha de vencimiento no puede ser pasada');
        input.classList.add('is-invalid');
    } else {
        input.setCustomValidity('');
        input.classList.remove('is-invalid');
    }
}

function configurarValidacionFechaCliente(inputId, validarFn) {
    const input = document.getElementById(inputId);
    if (!input) return;
    input.addEventListener('tpv-fecha-completa', function () {
        validarFn(input);
    });
}

function validarIdentificacion() {
    const tipoIdentificacion = getTipoIdentificacionValor();
    const identificacion = document.getElementById('identificacion');
    if (!identificacion) return true;
    const valor = identificacion.value.trim();
    if (!tipoIdentificacion || !valor) {
        identificacion.setCustomValidity('');
        if (typeof validarCampo === 'function') validarCampo(identificacion);
        return true;
    }
    const tiposValidacionSpain = ['1', '2', '3', '4'];
    if (tiposValidacionSpain.includes(tipoIdentificacion) && typeof validarIdentificacionSpain === 'function') {
        const resultado = validarIdentificacionSpain(tipoIdentificacion, valor);
        identificacion.setCustomValidity(resultado.valido ? '' : resultado.mensaje);
        if (typeof validarCampo === 'function') validarCampo(identificacion);
        return resultado.valido;
    }
    if (tipoIdentificacion === '5') {
        identificacion.setCustomValidity('');
        if (typeof validarCampo === 'function') validarCampo(identificacion);
    }
    return true;
}

function verificarTelefonoExistente(telefono) {
    if (!telefono || telefono.length < 9) return;

    const idClienteEl = document.getElementById('id_cliente');
    let idCliente = idClienteEl && idClienteEl.value ? String(idClienteEl.value).trim() : '';
    if (idCliente === 'false') idCliente = '';

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
                document.getElementById('telefono').value = '';
                document.getElementById('telefono').focus();
            }
        },
        error: function(xhr, status, error) {
            console.error('Error al verificar teléfono:', error);
        }
    });
}

function verificarIdentificacionExistente(identificacion) {
    if (!identificacion || identificacion.length < 5) return;

    if (typeof mostrarLoaderUniversal === 'function') {
        mostrarLoaderUniversal('Comprobando cliente...');
    }

    $.ajax({
        url: 'parts/clientes/crear/ajax_verificar_cliente.php',
        method: 'GET',
        dataType: 'json',
        data: {
            action: 'verificar_identificacion',
            valor: identificacion
        },
        success: function(response) {
            setTimeout(function() {
                if (response.existe) {
                    autocompletarFormularioCliente(response.cliente, response.direccion, response.datos_cliente);
                } else {
                    if (typeof ocultarLoaderUniversal === 'function') ocultarLoaderUniversal();
                    habilitarFormularioTrasComprobarIdentificacion();
                }
            }, 3000);
        },
        error: function(xhr, status, error) {
            if (typeof ocultarLoaderUniversal === 'function') ocultarLoaderUniversal();
            console.error('Error al verificar identificación:', error);
        }
    });
}

function ejecutarComprobacionIdentificacionLote() {
    if (autocompletandoCliente) return;

    const campoIdentificacion = document.getElementById('identificacion');
    const tipoIdentificacion = document.getElementById('tipo_identificacion');
    if (!campoIdentificacion || !tipoIdentificacion) return;

    const identificacionValor = campoIdentificacion.value.trim();
    const tipoIdValor = getTipoIdentificacionValor();

    if (!identificacionValor || !tipoIdValor) return;

    const tiposValidacionSpain = ['1', '2', '3', '4'];

    if (tiposValidacionSpain.includes(tipoIdValor)) {
        if (typeof validarIdentificacionSpain === 'function') {
            const resultado = validarIdentificacionSpain(tipoIdValor, identificacionValor);
            campoIdentificacion.setCustomValidity(resultado.valido ? '' : resultado.mensaje);
            if (typeof validarCampo === 'function') validarCampo(campoIdentificacion);
            if (!resultado.valido) return;
        }
        if (identificacionValor.length >= 5) {
            verificarIdentificacionExistente(identificacionValor);
        }
    } else if (tipoIdValor === '5') {
        campoIdentificacion.setCustomValidity('');
        if (typeof validarCampo === 'function') validarCampo(campoIdentificacion);
        if (identificacionValor.length >= 5) {
            verificarIdentificacionExistente(identificacionValor);
        }
    }
}

function aplicarDisabledFormLoteExceptoTipo(form, deshabilitar) {
    if (!form) return;
    const inputGruposAfectar = new Set();
    const idsSelect2Formulario = ['nacionalidad', 'sexo', 'sucursal_cliente'];

    form.querySelectorAll('input, select').forEach(function (el) {
        if (el.id === 'tipo_identificacion' || el.id === 'identificacion') return;
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

    idsSelect2Formulario.forEach(function (id) {
        sincronizarSelect2Disabled(id, deshabilitar);
    });

    form.querySelectorAll('select').forEach(function (el) {
        if (!el.id || el.id === 'tipo_identificacion') return;
        if (idsSelect2Formulario.indexOf(el.id) !== -1) return;
        if ($(el).data('select2')) {
            sincronizarSelect2Disabled(el.id, deshabilitar);
        }
    });

    const btnSms = document.getElementById('btnSolicitarAutorizacionSMS');
    if (btnSms) btnSms.disabled = deshabilitar;

    const btnComprobarIdent = document.getElementById('btn_comprobar_identificacion');
    if (btnComprobarIdent && deshabilitar) {
        const tipoVal = getTipoIdentificacionValor();
        btnComprobarIdent.disabled = !tipoVal;
    } else if (btnComprobarIdent && !deshabilitar) {
        btnComprobarIdent.disabled = false;
    }
}

function cargarFormularioDireccion(tipo, callback) {
    $.ajax({
        url: 'parts/clientes/crear/load_formulario_direccion.php',
        method: 'GET',
        data: { tipo: tipo },
        success: function(html) {
            $('#container_direccion').html(html);
            if (typeof window.inicializarDireccionesSelect2ConAjax === 'function') {
                window.inicializarDireccionesSelect2ConAjax();
            } else if (typeof inicializarSelect2Direcciones === 'function') {
                inicializarSelect2Direcciones();
            }
            if (callback) setTimeout(callback, 500);
        },
        error: function(xhr, status, error) {
            console.error('Error al cargar formulario de dirección:', error);
        }
    });
}

function autocompletarFormularioCliente(cliente, direccion, datos_cliente) {
    autocompletandoCliente = true;
    cargarFormularioDireccion('edit', function() {
        autocompletarDatosCliente(cliente, direccion, datos_cliente);
        setTimeout(function() {
            autocompletandoCliente = false;
        }, 1000);
    });
}

function autocompletarDatosCliente(cliente, direccion, datos_cliente) {
    habilitarFormularioTrasComprobarIdentificacion();

    const idClienteEl = document.getElementById('id_cliente');
    if (idClienteEl) idClienteEl.value = cliente.id_cliente;

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

    const f_vencimiento = datos_cliente?.f_vencimiento || cliente.f_vencimiento;
    if (f_vencimiento && window.TpvFecha) {
        window.TpvFecha.setValor(document.getElementById('f_vencimiento'), f_vencimiento);
    }

    if (cliente.nombre) document.getElementById('nombre').value = cliente.nombre;
    if (cliente.apellido) document.getElementById('apellido').value = cliente.apellido;

    const f_nacimiento = datos_cliente?.f_nacimiento || cliente.f_nacimiento;
    if (f_nacimiento && window.TpvFecha) {
        window.TpvFecha.setValor(document.getElementById('f_nacimiento'), f_nacimiento);
    }

    const sexo = datos_cliente?.sexo || cliente.sexo;
    if (sexo) $('#sexo').val(sexo).trigger('change');

    const email = datos_cliente?.email || cliente.email;
    if (email) document.getElementById('email').value = email;

    if (direccion) {
        if (direccion.direccion) {
            const direccionField = document.getElementById('direccion');
            if (direccionField) direccionField.value = direccion.direccion;
        }
        if (direccion.codigo_postal) {
            const codigoPostalField = document.getElementById('codigo_postal');
            if (codigoPostalField) codigoPostalField.value = direccion.codigo_postal;
        }
        if (direccion.rel_id_pais && direccion.c_pais) {
            const paisField = $('#pais');
            if (paisField.length) {
                const newOption = new Option(direccion.c_pais, direccion.rel_id_pais, true, true);
                paisField.append(newOption).trigger('change');
            }
        }
        if (direccion.rel_id_provincia && direccion.c_provincia) {
            setTimeout(function() {
                const provinciaField = $('#c_provincia');
                if (provinciaField.length) {
                    const newOption = new Option(direccion.c_provincia, direccion.rel_id_provincia, true, true);
                    provinciaField.append(newOption).trigger('change');
                }
            }, 500);
        }
        if (direccion.rel_id_poblacion && direccion.c_poblacion) {
            setTimeout(function() {
                const poblacionField = $('#c_poblacion');
                if (poblacionField.length) {
                    const newOption = new Option(direccion.c_poblacion, direccion.rel_id_poblacion, true, true);
                    poblacionField.append(newOption).trigger('change');
                }
            }, 1000);
        }
    }

    if (cliente && cliente.telefono !== undefined && cliente.telefono !== null) {
        const telStr = String(cliente.telefono).trim();
        if (telStr !== '') {
            setTimeout(function() {
                const inpTel = document.getElementById('telefono');
                if (inpTel) inpTel.value = telStr;
            }, 1250);
        }
    }

    if (cliente.sucursal) {
        const sucursalSel = document.getElementById('sucursal_cliente');
        if (sucursalSel) {
            $('#sucursal_cliente').val(String(cliente.sucursal)).trigger('change');
        }
    }

    if (typeof ocultarLoaderUniversal === 'function') ocultarLoaderUniversal();

    Swal.fire({
        title: 'Datos Cargados',
        text: 'El formulario se ha completado con los datos del cliente existente',
        icon: 'success',
        timer: 2000,
        showConfirmButton: false
    });

    const identificacion = document.getElementById('identificacion');
    if (identificacion) identificacion.blur();
}

function limpiarFormulario() {
    const form = document.getElementById('formCrearCliente');
    if (!form) return;
    form.reset();
    form.classList.remove('was-validated');
    form.querySelectorAll('.is-valid, .is-invalid').forEach(function(field) {
        field.classList.remove('is-valid', 'is-invalid');
    });
    const idClienteEl = document.getElementById('id_cliente');
    if (idClienteEl) idClienteEl.value = 'false';
    bloquearFormularioHastaComprobarIdentificacion();
    $('.inputgroupidentificacion').addClass('disabled');
    restaurarBotonCrear();
}
</script>
<script src="parts/universal/js/javascript_direcciones.js?v=<?php echo $vJavascriptDirecciones; ?>"></script>
