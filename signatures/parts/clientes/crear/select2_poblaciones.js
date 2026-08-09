/**
 * Inicializar Select2 para países
 */
function inicializarSelectPaises() {
    console.log('=== INICIALIZANDO SELECT PAÍSES ===');
    console.log('jQuery disponible:', typeof $ !== 'undefined');
    console.log('Select2 disponible:', typeof $.fn.select2 !== 'undefined');
    console.log('Elemento #pais encontrado:', $('#pais').length > 0);
    
    if ($('#pais').length === 0) {
        console.error('Elemento #pais no encontrado');
        return;
    }
    
    // Datos estáticos para prueba
    $('#pais').select2({
        dropdownParent: $('#pais').parent(),
        placeholder: 'Seleccionar país',
        allowClear: true,
        data: [
            {id: '1', text: 'España'},
            {id: '2', text: 'Francia'},
            {id: '3', text: 'Portugal'}
        ]
    });
    
    console.log('Select2 países inicializado correctamente');
    
    // Al cambiar país, limpiar provincia y población
    $('#pais').on('change', function() {
        $('#c_provincia').val('').trigger('change');
        $('#c_poblacion').val('').trigger('change');
        $('#codigo_postal').val('');
    });
}

/**
 * Inicializar Select2 para provincias
 */
function inicializarSelectProvincias() {
    console.log('=== INICIALIZANDO SELECT PROVINCIAS ===');
    
    $('#c_provincia').select2({
        dropdownParent: $('#c_provincia').parent(),
        placeholder: 'Seleccionar provincia',
        allowClear: true,
        data: [
            {id: '1', text: 'Madrid'},
            {id: '2', text: 'Barcelona'},
            {id: '3', text: 'Valencia'}
        ]
    });
    
    console.log('Select2 provincias inicializado correctamente');
    
    // Al cambiar provincia, limpiar población
    $('#c_provincia').on('change', function() {
        $('#c_poblacion').val('').trigger('change');
        $('#codigo_postal').val('');
    });
}

/**
 * Inicializar Select2 para poblaciones
 */
function inicializarSelectPoblaciones() {
    console.log('=== INICIALIZANDO SELECT POBLACIONES ===');
    
    $('#c_poblacion').select2({
        dropdownParent: $('#c_poblacion').parent(),
        placeholder: 'Seleccionar población',
        allowClear: true,
        data: [
            {id: '1', text: 'Madrid Centro'},
            {id: '2', text: 'Barcelona Centro'},
            {id: '3', text: 'Valencia Centro'}
        ]
    });
    
    console.log('Select2 poblaciones inicializado correctamente');
    
    // Al cambiar población, obtener detalles y asignar código postal, provincia y país
    $('#c_poblacion').on('change', function() {
        var idPoblacion = $(this).val();
        
        if (idPoblacion) {
            // Obtener detalles de la población
            $.ajax({
                url: 'parts/clientes/crear/ajax_poblaciones_simple.php',
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
            // Limpiar código postal si no hay población seleccionada
            $('#codigo_postal').val('');
        }
    });
}
