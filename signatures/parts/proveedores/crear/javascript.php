<script>
// Agregar validación de identificación al cambiar tipo
document.addEventListener('DOMContentLoaded', function() {
    
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
</script>