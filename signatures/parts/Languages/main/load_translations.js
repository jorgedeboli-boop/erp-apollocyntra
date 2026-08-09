// Cargar traducciones del language
function cargarTraducciones() {
    const idLang = new URLSearchParams(window.location.search).get('id');
    
    if (!idLang) {
        console.error('ID de language no encontrado');
        return;
    }

    // Configuración de DataTables
    tablaTraducciones = $('#tablaTraducciones').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: 'parts/Languages/main/load_translations.php?id=' + idLang,
            type: 'POST',
            error: function(xhr, error, thrown) {
                console.error('Error al cargar traducciones:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Error al cargar las traducciones'
                });
            }
        },
        columns: [
            { 
                data: 0,
                title: 'ID',
                width: '80px',
                className: 'text-center'
            },
            { 
                data: 1,
                title: 'Entrada',
                className: 'fw-medium'
            },
            { 
                data: 2,
                title: 'Traducción',
                className: 'text-wrap'
            },
            {
                data: null,
                title: 'Acciones',
                width: '100px',
                orderable: false,
                searchable: false,
                render: function(data, type, row) {
                    return `
                        <button class="btn btn-icon btn-text-primary btn-sm" onclick="editarTraduccion(${row[0]})" title="Editar traducción">
                            <i class="icon-base ri ri-pencil-line"></i>
                        </button>
                    `;
                }
            }
        ],
        columnDefs: [
            {
                targets: 0,
                searchable: false,
                orderable: true
            },
            {
                targets: 1,
                searchable: true,
                orderable: true
            },
            {
                targets: 2,
                searchable: true,
                orderable: true
            }
        ],
        order: [[1, 'asc']], // Ordenar por entrada
        pageLength: 10,
        lengthMenu: [5, 10, 25, 50],
        language: {
            processing: "Procesando...",
            lengthMenu: "Mostrar _MENU_ registros",
            zeroRecords: "No se encontraron traducciones",
            info: "Mostrando _START_ a _END_ de _TOTAL_ traducciones",
            infoEmpty: "Mostrando 0 a 0 de 0 traducciones",
            infoFiltered: "(filtrado de _MAX_ traducciones totales)",
            search: "Buscar:",
            paginate: {
                first: "Primero",
                last: "Último",
                next: "Siguiente",
                previous: "Anterior"
            }
        },
        responsive: true,
        dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
             '<"row"<"col-sm-12"tr>>' +
             '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
        drawCallback: function(settings) {
            // Callback después de dibujar la tabla
        }
    });
}

// Variables globales
let tablaTraducciones;
let traduccionActual = null;

// Función para editar traducción
function editarTraduccion(idTraduccion) {
    // Obtener datos de la traducción
    $.ajax({
        url: 'parts/Languages/main/get_traduccion.php',
        type: 'POST',
        data: { id: idTraduccion },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                traduccionActual = response.data;
                $('#modalEditarTraduccion #id_translations').val(response.data.id_translations);
                $('#modalEditarTraduccion #entry_translate').val(response.data.entry_translate);
                $('#modalEditarTraduccion #exit_translate').val(response.data.exit_translate);
                $('#modalEditarTraduccion').modal('show');
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: response.message || 'Error al cargar la traducción'
                });
            }
        },
        error: function() {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Error de conexión al cargar la traducción'
            });
        }
    });
}

// Función para guardar traducción
function guardarTraduccion() {
    const form = $('#formEditarTraduccion');
    const formData = form.serialize();
    
    // Validaciones básicas
    if (!$('#entry_translate').val().trim()) {
        Swal.fire({
            icon: 'warning',
            title: 'Validación',
            text: 'El campo Entrada es obligatorio'
        });
        return;
    }
    
    if (!$('#exit_translate').val().trim()) {
        Swal.fire({
            icon: 'warning',
            title: 'Validación',
            text: 'El campo Traducción es obligatorio'
        });
        return;
    }
    
    // Mostrar loader
    $('#btnGuardarTraduccion').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Guardando...');
    
    $.ajax({
        url: 'parts/Languages/main/procesar_editar_traduccion.php',
        type: 'POST',
        data: formData,
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Éxito',
                    text: 'Traducción actualizada correctamente'
                });
                $('#modalEditarTraduccion').modal('hide');
                tablaTraducciones.ajax.reload(); // Refrescar DataTable
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: response.message || 'Error al actualizar la traducción'
                });
            }
        },
        error: function() {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Error de conexión al actualizar la traducción'
            });
        },
        complete: function() {
            // Restaurar botón
            $('#btnGuardarTraduccion').prop('disabled', false).html('<i class="icon-base ri ri-check-line me-2"></i>Guardar');
        }
    });
}

// Inicializar cuando el documento esté listo
$(document).ready(function() {
    cargarTraducciones();
    
    // Evento para el botón guardar
    $('#btnGuardarTraduccion').on('click', guardarTraduccion);
    
    // Limpiar modal al cerrar
    $('#modalEditarTraduccion').on('hidden.bs.modal', function() {
        traduccionActual = null;
        $('#formEditarTraduccion')[0].reset();
    });
});
