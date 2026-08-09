/**
 * Lógica JS del módulo cliente — modo editar (basado en parts/clientes/editar).
 */
(function () {
    'use strict';

    var BASE = 'parts/modulo_cliente_form/unique/';
    var AJAX_VERIFICAR = BASE + 'ajax_verificar_cliente.php';
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

    function verificarIdentificacionExistente(identificacion) {
        if (!identificacion || identificacion.length < 5) {
            return;
        }
        var idClienteEl = document.getElementById('id_cliente');
        var idCliente = idClienteEl ? idClienteEl.value : '';
        $.ajax({
            url: AJAX_VERIFICAR,
            method: 'GET',
            dataType: 'json',
            data: {
                action: 'verificar_identificacion',
                valor: identificacion,
                id_cliente: idCliente
            },
            success: function (response) {
                var campoIdentificacion = document.getElementById('identificacion');
                if (response.existe) {
                    campoIdentificacion.setCustomValidity(response.message || 'Identificación duplicada');
                    validarCampoSiExiste(campoIdentificacion);
                    Swal.fire({
                        title: '¡Identificación Duplicada!',
                        text: response.message,
                        icon: 'warning',
                        confirmButtonText: 'Entendido',
                        confirmButtonColor: '#f39c12'
                    }).then(function () {
                        campoIdentificacion.value = '';
                        campoIdentificacion.setCustomValidity('');
                        campoIdentificacion.focus();
                    });
                } else {
                    campoIdentificacion.setCustomValidity('');
                    validarCampoSiExiste(campoIdentificacion);
                }
            },
            error: function (xhr, status, error) {
                console.error('Error al verificar identificación:', error);
            }
        });
    }

    function ejecutarComprobacionIdentificacion() {
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
        var idCliente = idClienteEl ? idClienteEl.value : '';
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
        var modulo = getModuloRoot();
        if (!modulo) {
            return;
        }
        $(modulo).find('.select2:not(#pais):not(#c_provincia):not(#c_poblacion)').each(function () {
            var $this = $(this);
            if ($this.data('select2')) {
                return;
            }
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
        var tieneTipoInicial = !!String($(tipoIdentificacion).val() || tipoIdentificacion.value || '');
        aplicarDisabledModuloClienteExceptoTipo(container, !tieneTipoInicial);
        if (!tieneTipoInicial) {
            identificacion.disabled = true;
            identificacion.placeholder = 'Primero seleccione el tipo de identificación';
        }
        $(tipoIdentificacion).on('change', function () {
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
            identificacion.setCustomValidity('');
            validarIdentificacion();
        });
        identificacion.addEventListener('input', validarIdentificacion);
        identificacion.addEventListener('blur', ejecutarComprobacionIdentificacion);
        var btnComprobarIdent = document.getElementById('btn_comprobar_identificacion');
        if (btnComprobarIdent) {
            btnComprobarIdent.addEventListener('click', function (e) {
                e.preventDefault();
                ejecutarComprobacionIdentificacion();
            });
        }
        if (tieneTipoInicial && identificacion.value.trim()) {
            validarIdentificacion();
        }
    }

    function initModuloClienteEditar() {
        initSelect2Modulo();
        configurarIdentificacion();
        configurarValidacionesContacto();
    }

    document.addEventListener('DOMContentLoaded', function () {
        var modulo = getModuloRoot();
        if (!modulo || modulo.getAttribute('data-modo') !== 'editar') {
            return;
        }
        initModuloClienteEditar();
    });
})();
