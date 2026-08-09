// Variable global para placeholder de Select2
var placeholderSelect2 = typeof placeholderSelect2 !== 'undefined' ? placeholderSelect2 : 'Seleccionar...';

function valorVacioSelectDireccion($select) {
    if ($select.find('option[value="0"]').length) {
        return '0';
    }
    return '';
}

function esValorSelectDireccionVacio(valor) {
    var v = String(valor || '').trim();
    return v === '' || v === '0';
}

var ID_PAIS_ESPANIA = typeof window.ID_PAIS_ESPANIA !== 'undefined' ? window.ID_PAIS_ESPANIA : 68;

function esPaisEspanaDireccion() {
    var idPais = String($('#pais').val() || '').trim();
    return idPais === String(ID_PAIS_ESPANIA);
}

function mostrarSelectsProvinciaPoblacion() {
    $('#c_provincia').prop('required', true).parent().show();
    $('#provincia_no_id_container').remove();

    $('#c_poblacion').prop('required', true).parent().show();
    $('#poblacion_no_id_container').remove();
}

function mostrarInputsProvinciaPoblacionManuales() {
    $('#c_provincia').prop('required', false).parent().hide();
    if ($('#provincia_no_id').length === 0) {
        $('#c_provincia').parent().after(
            '<div class="mb-4 form-floating form-floating-outline" id="provincia_no_id_container">' +
                '<input type="text" class="form-control" id="provincia_no_id" name="provincia_no_id" placeholder="Provincia" required />' +
                '<label for="provincia_no_id" class="form-label">Provincia *</label>' +
            '</div>'
        );
    }

    $('#c_poblacion').prop('required', false).parent().hide();
    if ($('#poblacion_no_id').length === 0) {
        $('#c_poblacion').parent().after(
            '<div class="mb-4 form-floating form-floating-outline" id="poblacion_no_id_container">' +
                '<input type="text" class="form-control" id="poblacion_no_id" name="poblacion_no_id" placeholder="Población" required />' +
                '<label for="poblacion_no_id" class="form-label">Población *</label>' +
            '</div>'
        );
    }
}

function aplicarModoProvinciaPoblacionSegunPais() {
    if (esPaisEspanaDireccion()) {
        mostrarSelectsProvinciaPoblacion();
        return;
    }

    var idPais = String($('#pais').val() || '').trim();
    if (!esValorSelectDireccionVacio(idPais)) {
        mostrarInputsProvinciaPoblacionManuales();
    }
}

/**
 * Tras autocompletar #codigo_postal al elegir población: marcar el campo válido y reevaluar el botón de envío.
 */
function notificarCodigoPostalAutocompletado() {
    var cp = document.getElementById('codigo_postal');
    if (!cp) {
        return;
    }
    cp.setCustomValidity('');
    var val = String(cp.value || '').trim();
    if (val !== '') {
        cp.classList.remove('is-invalid');
        cp.classList.add('is-valid');
    } else {
        cp.classList.remove('is-valid', 'is-invalid');
    }
    if (typeof window.validarCampo === 'function') {
        window.validarCampo(cp);
    }
    if (typeof window.verificarEstadoBotonCrear === 'function') {
        window.verificarEstadoBotonCrear();
    }
}

/**
 * Select2 + ajax para país / provincia / población.
 * Debe llamarse tras inyectar el HTML del formulario de dirección (p. ej. autocompletar cliente)
 * y en DOMContentLoaded si ya existen #pais, #c_provincia, #c_poblacion en el DOM.
 */
window.inicializarDireccionesSelect2ConAjax = function () {
    if (!$('#pais').length || !$('#c_provincia').length || !$('#c_poblacion').length) {
        return;
    }

    $('#pais, #c_provincia, #c_poblacion').each(function () {
        var $el = $(this);
        if ($el.data('select2')) {
            $el.select2('destroy');
        }
    });

    $('#pais').off('.direccionesDeps');
    $('#c_provincia').off('.direccionesDeps');
    $('#c_poblacion').off('.direccionesDeps');

    $('#pais').select2({
        dropdownParent: $('#pais').parent(),
        placeholder: placeholderSelect2,
        allowClear: false,
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
                    pagination: data.pagination || { more: false }
                };
            },
            error: function () {}
        }
    });

    $('#c_provincia').select2({
        dropdownParent: $('#c_provincia').parent(),
        placeholder: placeholderSelect2,
        allowClear: false,
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
                if (!esPaisEspanaDireccion() && (!data.results || data.results.length === 0)) {
                    mostrarInputsProvinciaPoblacionManuales();
                } else {
                    mostrarSelectsProvinciaPoblacion();
                }

                return {
                    results: data.results || [],
                    pagination: data.pagination || { more: false }
                };
            }
        }
    });

    $('#c_poblacion').select2({
        dropdownParent: $('#c_poblacion').parent(),
        placeholder: placeholderSelect2,
        allowClear: false,
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
                    pagination: data.pagination || { more: false }
                };
            }
        }
    });

    $('#pais').on('change.direccionesDeps', function () {
        $('#c_provincia').val(valorVacioSelectDireccion($('#c_provincia'))).trigger('change');
        $('#c_poblacion').val(valorVacioSelectDireccion($('#c_poblacion'))).trigger('change');
        $('#codigo_postal').val('');

        aplicarModoProvinciaPoblacionSegunPais();

        if (esPaisEspanaDireccion() && $(this).val()) {
            $('#c_provincia').select2('open');
            setTimeout(function () {
                $('#c_provincia').select2('close');
            }, 100);
        }
    });

    $('#c_provincia').on('change.direccionesDeps', function () {
        $('#c_poblacion').val(valorVacioSelectDireccion($('#c_poblacion'))).trigger('change');
        $('#codigo_postal').val('');
        notificarCodigoPostalAutocompletado();
    });

    $('#c_poblacion').on('change.direccionesDeps', function () {
        var idPoblacion = $(this).val();

        if (idPoblacion) {
            $.ajax({
                url: 'parts/universal/ajax_poblaciones.php',
                dataType: 'json',
                data: {
                    action: 'poblacion_detalle',
                    idpoblacion: idPoblacion
                },
                success: function (response) {
                    if (response.success) {
                        var data = response.data;

                        $('#codigo_postal').val(data.codigo_postal);
                        notificarCodigoPostalAutocompletado();

                        if (!$('#c_provincia').val() && data.idprovincia) {
                            var newOptionProv = new Option(data.provincia, data.idprovincia, true, true);
                            $('#c_provincia').append(newOptionProv).trigger('change');
                        }

                        if (!$('#pais').val() && data.id_rel_country) {
                            var newOptionPais = new Option(data.pais, data.id_rel_country, true, true);
                            $('#pais').append(newOptionPais).trigger('change');
                        }
                    }
                },
                error: function () {}
            });
        } else {
            $('#codigo_postal').val('');
            notificarCodigoPostalAutocompletado();
        }
    });

    // Crear lote (y otras pantallas): el `change` del <form> no siempre recibe eventos de Select2 en cascada
    $('#pais, #c_provincia, #c_poblacion').off('change.verifLoteCrear select2:select.verifLoteCrear select2:clear.verifLoteCrear');
    if (typeof window.verificarEstadoBotonCrear === 'function') {
        $('#pais, #c_provincia, #c_poblacion').on(
            'change.verifLoteCrear select2:select.verifLoteCrear select2:clear.verifLoteCrear',
            function () {
                window.verificarEstadoBotonCrear();
            }
        );
    }
};

document.addEventListener('DOMContentLoaded', function () {
    if (
        $('#c_provincia').length &&
        esValorSelectDireccionVacio($('#c_provincia option:selected').val()) &&
        $('#c_provincia').data('manual-text') &&
        !esPaisEspanaDireccion()
    ) {
        const provinciaTexto = $('#c_provincia').data('manual-text');
        $('#c_provincia').prop('required', false).parent().hide();
        $('#c_provincia').parent().after(
            '<div class="mb-4 form-floating form-floating-outline" id="provincia_no_id_container">' +
                '<input type="text" class="form-control" id="provincia_no_id" name="provincia_no_id" placeholder="Provincia" value="' + provinciaTexto + '" required />' +
                '<label for="provincia_no_id" class="form-label">Provincia *</label>' +
            '</div>'
        );
    }

    if (
        $('#c_poblacion').length &&
        esValorSelectDireccionVacio($('#c_poblacion option:selected').val()) &&
        $('#c_poblacion').data('manual-text') &&
        !esPaisEspanaDireccion()
    ) {
        const poblacionTexto = $('#c_poblacion').data('manual-text');
        $('#c_poblacion').prop('required', false).parent().hide();
        $('#c_poblacion').parent().after(
            '<div class="mb-4 form-floating form-floating-outline" id="poblacion_no_id_container">' +
                '<input type="text" class="form-control" id="poblacion_no_id" name="poblacion_no_id" placeholder="Población" value="' + poblacionTexto + '" required />' +
                '<label for="poblacion_no_id" class="form-label">Población *</label>' +
            '</div>'
        );
    }

    if ($('#pais').length) {
        window.inicializarDireccionesSelect2ConAjax();
        aplicarModoProvinciaPoblacionSegunPais();
    }
});
