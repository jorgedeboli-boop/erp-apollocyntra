// Configuración de idioma español para Select2
// Este archivo se carga globalmente para toda la aplicación

if (typeof $.fn.select2 !== 'undefined') {
    // Configurar idioma español para Select2
    $.fn.select2.defaults.set('language', {
        errorLoading: function() {
            return 'No se pudieron cargar los resultados.';
        },
        inputTooLong: function(args) {
            var overChars = args.input.length - args.maximum;
            var message = 'Por favor borre ' + overChars + ' carácter';
            if (overChars != 1) {
                message += 'es';
            }
            return message;
        },
        inputTooShort: function(args) {
            var remainingChars = args.minimum - args.input.length;
            var message = 'Por favor introduzca ' + remainingChars + ' carácter';
            if (remainingChars != 1) {
                message += 'es';
            }
            message += ' o más';
            return message;
        },
        loadingMore: function() {
            return 'Cargando más resultados…';
        },
        maximumSelected: function(args) {
            var message = 'Solo puede seleccionar ' + args.maximum + ' elemento';
            if (args.maximum != 1) {
                message += 's';
            }
            return message;
        },
        noResults: function() {
            return 'No se encontraron resultados';
        },
        searching: function() {
            return 'Buscando…';
        }
    });
    
    //console.log('✅ Select2 configurado en español para toda la aplicación');
} else {
    //console.log('⚠️ Select2 no está disponible para configurar idioma español');
}
