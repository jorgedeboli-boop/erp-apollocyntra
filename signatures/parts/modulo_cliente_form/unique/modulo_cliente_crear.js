/**
 * Lógica JS del módulo cliente — modo crear (basado en parts/lotes/crear).
 */
(function () {
    'use strict';

    var BASE = 'parts/modulo_cliente_form/unique/';
    var AJAX_VERIFICAR = BASE + 'ajax_verificar_cliente.php';
    var LOAD_DIRECCION = BASE + 'load_formulario_direccion.php';
    var autocompletandoCliente = false;
    var placeholderSelect2 = 'Seleccionar...';

    function getModuloRoot() {
        return document.getElementById('modulo_cliente_form');
    }

    function getFormRoot() {
        var modulo = getModuloRoot();
        if (!modulo) {
            return null;
        }
        return modulo.closest('form') || modulo;
    }

    function getTipoIdentificacionValor() {
        var el = document.getElementById('tipo_identificacion');
        if (!el) {
            return '';
        }
        if (typeof $ !== 'undefined' && $('#tipo_identificacion').length) {
            return String($('#tipo_identificacion').val() || '');
        }
        return String(el.value || '');
    }

    function validarCampoSiExiste(field) {
        if (typeof validarCampo === 'function') {
            validarCampo(field);
        }
    }

    function aplicarDisabledModuloClienteExceptoTipo(container, deshabilitar) {
        if (!container) {
            return;
        }
        var datosCliente = document.getElementById('datos_cliente');
        if (datosCliente) {
            datosCliente.classList.toggle('formulario-borroso', deshabilitar);
        }
        var inputGruposAfectar = new Set();
        container.querySelectorAll('input, select, textarea').forEach(function (el) {
            if (el.id === 'tipo_identificacion') {
                return;
            }
            var t = (el.type || '').toLowerCase();
            if (t === 'hidden' || t === 'submit' || t === 'button' || t === 'reset') {
                return;
            }
            if (el.readOnly) {
                return;
            }
            var grupo = el.closest('.input-group-merge, .input-group');
            if (grupo && container.contains(grupo)) {
                inputGruposAfectar.add(grupo);
                return;
            }
            el.disabled = deshabilitar;
        });
        inputGruposAfectar.forEach(function (grupo) {
            grupo.classList.toggle('disabled', deshabilitar);
        });
        var btnSms = document.getElementById('btnSolicitarAutorizacionSMS');
        if (btnSms) {
            btnSms.disabled = deshabilitar;
        }
        var btnComprobarIdent = document.getElementById('btn_comprobar_identificacion');
        if (btnComprobarIdent) {
            btnComprobarIdent.disabled = deshabilitar;
        }
    }

    function validarIdentificacion() {
        var campoIdentificacion = document.getElementById('identificacion');
        if (!campoIdentificacion) {
            return true;
        }
        var tipoIdValor = getTipoIdentificacionValor();
        var identificacionValor = campoIdentificacion.value.trim();
        if (!tipoIdValor || !identificacionValor) {
            campoIdentificacion.setCustomValidity('');
            validarCampoSiExiste(campoIdentificacion);
            return true;
        }
        var tiposValidacionSpain = ['1', '2', '3', '4'];
        if (tiposValidacionSpain.indexOf(tipoIdValor) !== -1 && typeof validarIdentificacionSpain === 'function') {
            var resultado = validarIdentificacionSpain(tipoIdValor, identificacionValor);
            campoIdentificacion.setCustomValidity(resultado.valido ? '' : resultado.mensaje);
            validarCampoSiExiste(campoIdentificacion);
            return resultado.valido;
        }
        if (tipoIdValor === '5') {
            campoIdentificacion.setCustomValidity('');
            validarCampoSiExiste(campoIdentificacion);
        }
        return true;
    }

    function cargarFormularioDireccion(tipo, callback) {
        $.ajax({
            url: LOAD_DIRECCION,
            method: 'GET',
            data: { tipo: tipo },
            success: function (html) {
                $('#container_direccion').html(html);
                if (typeof window.inicializarDireccionesSelect2ConAjax === 'function') {
                    window.inicializarDireccionesSelect2ConAjax();
                }
                if (callback) {
                    setTimeout(callback, 500);
                }
            },
            error: function (xhr, status, error) {
                console.error('Error al cargar formulario de dirección:', error);
            }
        });
    }

    function autocompletarDatosCliente(cliente, direccion, datos_cliente) {
        var container = getFormRoot();
        if (container) {
            aplicarDisabledModuloClienteExceptoTipo(container, false);
        }
        var idClienteEl = document.getElementById('id_cliente');
        if (idClienteEl && cliente && cliente.id_cliente) {
            idClienteEl.value = cliente.id_cliente;
        }
        if (cliente && cliente.tipo_identificacion_id) {
            $('#tipo_identificacion').val(cliente.tipo_identificacion_id).trigger('change');
        }
        var campoIdentificacion = document.getElementById('identificacion');
        if (campoIdentificacion && cliente && cliente.identificacion) {
            campoIdentificacion.value = cliente.identificacion;
        }
        if (cliente && cliente.nacionalidad_id) {
            $('#nacionalidad').val(cliente.nacionalidad_id).trigger('change');
        }
        var fVencimiento = datos_cliente && datos_cliente.f_vencimiento ? datos_cliente.f_vencimiento : (cliente ? cliente.f_vencimiento : null);
        if (fVencimiento) {
            var elFv = document.getElementById('f_vencimiento');
            if (elFv && window.TpvFecha && typeof window.TpvFecha.setValor === 'function') {
                window.TpvFecha.setValor(elFv, fVencimiento);
            } else if (elFv) {
                elFv.value = fVencimiento;
            }
        }
        if (cliente && cliente.nombre) {
            document.getElementById('nombre').value = cliente.nombre;
        }
        if (cliente && cliente.apellido) {
            document.getElementById('apellido').value = cliente.apellido;
        }
        var fNacimiento = datos_cliente && datos_cliente.f_nacimiento ? datos_cliente.f_nacimiento : (cliente ? cliente.f_nacimiento : null);
        if (fNacimiento) {
            var elFn = document.getElementById('f_nacimiento');
            if (elFn && window.TpvFecha && typeof window.TpvFecha.setValor === 'function') {
                window.TpvFecha.setValor(elFn, fNacimiento);
            } else if (elFn) {
                elFn.value = fNacimiento;
            }
        }
        var sexo = datos_cliente && datos_cliente.sexo ? datos_cliente.sexo : (cliente ? cliente.sexo : null);
        if (sexo) {
            $('#sexo').val(sexo).trigger('change');
        }
        var email = datos_cliente && datos_cliente.email ? datos_cliente.email : (cliente ? cliente.email : null);
        if (email) {
            document.getElementById('email').value = email;
        }
        if (direccion) {
            if (direccion.direccion) {
                var direccionField = document.getElementById('direccion');
                if (direccionField) {
                    direccionField.value = direccion.direccion;
                }
            }
            if (direccion.codigo_postal) {
                var codigoPostalField = document.getElementById('codigo_postal');
                if (codigoPostalField) {
                    codigoPostalField.value = direccion.codigo_postal;
                }
            }
            if (direccion.rel_id_pais && direccion.c_pais) {
                var paisField = $('#pais');
                if (paisField.length) {
                    var optPais = new Option(direccion.c_pais, direccion.rel_id_pais, true, true);
                    paisField.append(optPais).trigger('change');
                }
            }
            if (direccion.rel_id_provincia && direccion.c_provincia) {
                setTimeout(function () {
                    var provinciaField = $('#c_provincia');
                    if (provinciaField.length) {
                        var optProv = new Option(direccion.c_provincia, direccion.rel_id_provincia, true, true);
                        provinciaField.append(optProv).trigger('change');
                    }
                }, 500);
            }
            if (direccion.rel_id_poblacion && direccion.c_poblacion) {
                setTimeout(function () {
                    var poblacionField = $('#c_poblacion');
                    if (poblacionField.length) {
                        var optPob = new Option(direccion.c_poblacion, direccion.rel_id_poblacion, true, true);
                        poblacionField.append(optPob).trigger('change');
                    }
                }, 1000);
            }
        }
        if (cliente && cliente.telefono !== undefined && cliente.telefono !== null) {
            var telStr = String(cliente.telefono).trim();
            if (telStr !== '') {
                setTimeout(function () {
                    var inpTel = document.getElementById('telefono');
                    if (inpTel) {
                        inpTel.value = telStr;
                    }
                }, 1250);
            }
        }
        if (typeof ocultarLoaderUniversal === 'function') {
            ocultarLoaderUniversal();
        }
        Swal.fire({
            title: 'Datos Cargados',
            text: 'El formulario se ha completado con los datos del cliente existente',
            icon: 'success',
            timer: 2000,
            showConfirmButton: false
        });
    }

    function autocompletarFormularioCliente(cliente, direccion, datos_cliente) {
        autocompletandoCliente = true;
        cargarFormularioDireccion('edit', function () {
            autocompletarDatosCliente(cliente, direccion, datos_cliente);
            setTimeout(function () {
                autocompletandoCliente = false;
            }, 1000);
        });
    }

    function verificarIdentificacionExistente(identificacion) {
        if (!identificacion || identificacion.length < 5) {
            return;
        }
        if (typeof mostrarLoaderUniversal === 'function') {
            mostrarLoaderUniversal('Comprobando cliente...');
        }
        $.ajax({
            url: AJAX_VERIFICAR,
            method: 'GET',
            dataType: 'json',
            data: {
                action: 'verificar_identificacion',
                valor: identificacion
            },
            success: function (response) {
                setTimeout(function () {
                    if (response.existe) {
                        autocompletarFormularioCliente(response.cliente, response.direccion, response.datos_cliente);
                    } else {
                        if (typeof ocultarLoaderUniversal === 'function') {
                            ocultarLoaderUniversal();
                        }
                        var container = getFormRoot();
                        if (container) {
                            aplicarDisabledModuloClienteExceptoTipo(container, false);
                        }
                    }
                }, 3000);
            },
            error: function (xhr, status, error) {
                if (typeof ocultarLoaderUniversal === 'function') {
                    ocultarLoaderUniversal();
                }
                console.error('Error al verificar identificación:', error);
            }
        });
    }

    function ejecutarComprobacionIdentificacion() {
        if (autocompletandoCliente) {
            return;
        }
        var campoIdentificacion = document.getElementById('identificacion');
        if (!campoIdentificacion) {
            return;
        }
        var identificacionValor = campoIdentificacion.value.trim();
        var tipoIdValor = getTipoIdentificacionValor();
        if (!identificacionValor || !tipoIdValor) {
            return;
        }
        var tiposValidacionSpain = ['1', '2', '3', '4'];
        if (tiposValidacionSpain.indexOf(tipoIdValor) !== -1) {
            if (typeof validarIdentificacionSpain === 'function') {
                var resultado = validarIdentificacionSpain(tipoIdValor, identificacionValor);
                campoIdentificacion.setCustomValidity(resultado.valido ? '' : resultado.mensaje);
                validarCampoSiExiste(campoIdentificacion);
                if (!resultado.valido) {
                    return;
                }
            }
            if (identificacionValor.length >= 5) {
                verificarIdentificacionExistente(identificacionValor);
            }
        } else if (tipoIdValor === '5') {
            campoIdentificacion.setCustomValidity('');
            validarCampoSiExiste(campoIdentificacion);
            if (identificacionValor.length >= 5) {
                verificarIdentificacionExistente(identificacionValor);
            }
        }
    }

    function verificarTelefonoExistente(telefono) {
        if (!telefono || telefono.length < 9) {
            return;
        }
        var idClienteEl = document.getElementById('id_cliente');
        var idCliente = idClienteEl && idClienteEl.value ? String(idClienteEl.value).trim() : '';
        if (idCliente === 'false') {
            idCliente = '';
        }
        $.ajax({
            url: AJAX_VERIFICAR,
            method: 'GET',
            dataType: 'json',
            data: {
                action: 'verificar_telefono',
                valor: telefono,
                id_cliente: idCliente
            },
            success: function (response) {
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
            error: function (xhr, status, error) {
                console.error('Error al verificar teléfono:', error);
            }
        });
    }

    function configurarValidacionesContacto() {
        var codigoPostal = document.getElementById('codigo_postal');
        if (codigoPostal) {
            codigoPostal.addEventListener('input', function () {
                this.value = this.value.replace(/[^a-zA-Z0-9\s-]/g, '').toUpperCase();
            });
        }
        var telefono = document.getElementById('telefono');
        if (telefono) {
            var timeoutTelefono = null;
            telefono.addEventListener('input', function () {
                this.value = this.value.replace(/[^0-9\s\+\-\(\)]/g, '');
                if (timeoutTelefono) {
                    clearTimeout(timeoutTelefono);
                }
                var telefonoValor = this.value.trim();
                if (telefonoValor.length >= 9) {
                    timeoutTelefono = setTimeout(function () {
                        verificarTelefonoExistente(telefonoValor);
                    }, 500);
                }
            });
        }
    }

    function initSelect2Modulo() {
        if (typeof $ === 'undefined') {
            return;
        }
        var modulo = getModuloRoot();
        if (!modulo) {
            return;
        }
        $('#tipo_identificacion').select2({
            dropdownParent: $('#tipo_identificacion').parent(),
            placeholder: placeholderSelect2,
            allowClear: true
        });
        $('#tipo_identificacion').on('change', function () {
            var idTipo = String($(this).val() || '');
            var $nac = $('#nacionalidad');
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
        $('#nacionalidad, #sexo').each(function () {
            var $this = $(this);
            $this.select2({
                dropdownParent: $this.parent(),
                placeholder: placeholderSelect2,
                allowClear: true
            });
        });
        if (typeof window.inicializarDireccionesSelect2ConAjax === 'function') {
            window.inicializarDireccionesSelect2ConAjax();
        }
    }

    function configurarIdentificacion() {
        var container = getFormRoot();
        var tipoIdentificacion = document.getElementById('tipo_identificacion');
        var identificacion = document.getElementById('identificacion');
        if (!container || !tipoIdentificacion || !identificacion) {
            return;
        }
        aplicarDisabledModuloClienteExceptoTipo(container, true);
        identificacion.disabled = true;
        identificacion.placeholder = 'Primero seleccione el tipo de identificación';
        $(tipoIdentificacion).on('change', function () {
            if (autocompletandoCliente) {
                if (this.value) {
                    identificacion.disabled = false;
                    var btnAutoc = document.getElementById('btn_comprobar_identificacion');
                    if (btnAutoc) {
                        btnAutoc.disabled = false;
                    }
                }
                return;
            }
            if (this.value) {
                aplicarDisabledModuloClienteExceptoTipo(container, false);
                identificacion.disabled = false;
                identificacion.placeholder = 'Número de identificación';
                var btnComprobar = document.getElementById('btn_comprobar_identificacion');
                if (btnComprobar) {
                    btnComprobar.disabled = false;
                }
                $('.inputgroupidentificacion').removeClass('disabled');
                setTimeout(function () {
                    identificacion.focus();
                }, 50);
            } else {
                aplicarDisabledModuloClienteExceptoTipo(container, true);
                identificacion.disabled = true;
                identificacion.value = '';
                identificacion.placeholder = 'Primero seleccione el tipo de identificación';
                identificacion.classList.remove('is-valid', 'is-invalid');
                $('.inputgroupidentificacion').addClass('disabled');
            }
            validarIdentificacion();
        });
        $(tipoIdentificacion).on('select2:close', function () {
            if (autocompletandoCliente) {
                return;
            }
            if (!$(tipoIdentificacion).val()) {
                return;
            }
            setTimeout(function () {
                if (!identificacion.disabled) {
                    identificacion.focus();
                }
            }, 10);
        });
        identificacion.addEventListener('input', validarIdentificacion);
        identificacion.addEventListener('blur', function () {
            if (autocompletandoCliente) {
                return;
            }
            ejecutarComprobacionIdentificacion();
        });
        var btnComprobarIdent = document.getElementById('btn_comprobar_identificacion');
        if (btnComprobarIdent) {
            btnComprobarIdent.addEventListener('click', function (e) {
                e.preventDefault();
                ejecutarComprobacionIdentificacion();
            });
        }
    }

    function initModuloClienteCrear() {
        initSelect2Modulo();
        configurarIdentificacion();
        configurarValidacionesContacto();
    }

    document.addEventListener('DOMContentLoaded', function () {
        var modulo = getModuloRoot();
        if (!modulo || modulo.getAttribute('data-modo') !== 'crear') {
            return;
        }
        initModuloClienteCrear();
    });
})();
