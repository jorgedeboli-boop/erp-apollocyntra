<script>
document.addEventListener('DOMContentLoaded', function() {
    function actualizarTextoSelect($select, $hidden) {
        var texto = $select.find('option:selected').text().trim();
        $hidden.val(texto === 'Seleccionar país' || texto === 'Seleccionar provincia' || texto === 'Seleccionar población' ? '' : texto);
    }

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

        $('#rel_id_pais').on('change', function() {
            actualizarTextoSelect($(this), $('#pais_empresa'));
            $('#rel_id_provincia').val('').trigger('change');
            $('#provincia_empresa').val('');
            $('#rel_id_poblacion').val('').trigger('change');
            $('#poblacion_empresa').val('');
            $('#codigo_postal_empresa').val('');
        });

        $('#rel_id_provincia').on('change', function() {
            actualizarTextoSelect($(this), $('#provincia_empresa'));
            $('#rel_id_poblacion').val('').trigger('change');
            $('#poblacion_empresa').val('');
            $('#codigo_postal_empresa').val('');
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

        $('#formCrearEmpresa').on('submit', function() {
            actualizarTextoSelect($('#rel_id_pais'), $('#pais_empresa'));
            actualizarTextoSelect($('#rel_id_provincia'), $('#provincia_empresa'));
            actualizarTextoSelect($('#rel_id_poblacion'), $('#poblacion_empresa'));
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
});
</script>
