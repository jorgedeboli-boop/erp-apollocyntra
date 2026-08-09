/**
 * DataTable para acciones del usuario
 */

'use strict';

// Variable global para DataTable
let dt_usuario_acciones;

// Función para inicializar el DataTable de acciones
function inicializarDataTableAcciones(idUsuario) {
    const dt_table = document.querySelector('.datatables-usuario-acciones');
    
    if (!dt_table) {
        return;
    }
    
    if (dt_table.classList.contains('dataTable')) {
        return;
    }
    
    if (dt_table && !dt_table.classList.contains('dataTable')) {
        dt_usuario_acciones = new DataTable(dt_table, {
            processing: true,
            serverSide: true,
            deferRender: true,
            searchDelay: 500,
            timeout: 60000,
            
            // Configuración de idioma español
            language: {
                processing: "Procesando...",
                search: "Buscar:",
                lengthMenu: "Mostrar _MENU_ registros",
                info: "Mostrando _START_ a _END_ de _TOTAL_ registros",
                infoEmpty: "Mostrando 0 a 0 de 0 registros",
                infoFiltered: "(filtrado de _MAX_ registros totales)",
                infoPostFix: "",
                loadingRecords: "Cargando...",
                zeroRecords: "No se encontraron registros coincidentes",
                emptyTable: "No hay datos disponibles en la tabla",
                paginate: {
                    first: "Primero",
                    previous: "Anterior",
                    next: "Siguiente",
                    last: "Último"
                },
                aria: {
                    sortAscending: ": activar para ordenar la columna ascendente",
                    sortDescending: ": activar para ordenar la columna descendente"
                }
            },
            
            // Configuración de columnas
            columns: [
                { data: 0 }, // ID
                { data: 1 }, // Fecha
                { data: 2 }, // Acción
                { data: 3 }, // Descripción
                { data: 4 }, // Sucursal
                { data: 5 }, // IP
                { data: 6 }, // URL
                { data: 7 }  // Item
            ],
            
            // Configuración de columnas
            columnDefs: [
                {
                    // ID column
                    targets: 0,
                    responsivePriority: 1,
                    render: function (data, type, full, meta) {
                        return '<span class="fw-semibold">#' + data + '</span>';
                    }
                },
                {
                    // Fecha column
                    targets: 1,
                    responsivePriority: 2,
                    render: function (data, type, full, meta) {
                        return '<span class="fw-medium">' + data + '</span>';
                    }
                },
                {
                    // Acción column
                    targets: 2,
                    responsivePriority: 3,
                    render: function (data, type, full, meta) {
                        if (data && data !== 'N/A') {
                            return '<span class="badge bg-label-primary rounded-pill">' +
                                   '<i class="icon-base ri ri-time-line me-1"></i>' +
                                   data +
                                   '</span>';
                        } else {
                            return '<span class="text-muted">N/A</span>';
                        }
                    }
                },
                {
                    // Descripción column
                    targets: 3,
                    responsivePriority: 4,
                    render: function (data, type, full, meta) {
                        if (data && data !== 'Sin descripción') {
                            return '<span class="fw-medium">' + data + '</span>';
                        } else {
                            return '<span class="text-muted">Sin descripción</span>';
                        }
                    }
                },
                {
                    // Sucursal column
                    targets: 4,
                    responsivePriority: 5,
                    render: function (data, type, full, meta) {
                        if (data && data !== 'Sin sucursal') {
                            return '<span class="badge bg-label-info rounded-pill">' +
                                   '<i class="icon-base ri ri-building-line me-1"></i>' +
                                   data +
                                   '</span>';
                        } else {
                            return '<span class="text-muted">Sin sucursal</span>';
                        }
                    }
                },
                {
                    // IP column
                    targets: 5,
                    responsivePriority: 6,
                    render: function (data, type, full, meta) {
                        if (data && data !== 'N/A') {
                            return '<code class="text-primary">' + data + '</code>';
                        } else {
                            return '<span class="text-muted">N/A</span>';
                        }
                    }
                },
                {
                    // URL column
                    targets: 6,
                    responsivePriority: 7,
                    render: function (data, type, full, meta) {
                        if (data && data !== 'N/A') {
                            return '<span class="text-truncate d-inline-block" style="max-width: 150px;" title="' + data + '">' + data + '</span>';
                        } else {
                            return '<span class="text-muted">N/A</span>';
                        }
                    }
                },
                {
                    // Item column
                    targets: 7,
                    responsivePriority: 8,
                    render: function (data, type, full, meta) {
                        if (data && data !== 'N/A') {
                            return '<span class="badge bg-label-secondary rounded-pill">' +
                                   '<i class="icon-base ri ri-file-text-line me-1"></i>' +
                                   data +
                                   '</span>';
                        } else {
                            return '<span class="text-muted">N/A</span>';
                        }
                    }
                }
            ],
            
            // Configuración de paginación
            pageLength: 25,
            lengthMenu: [10, 25, 50, 100],
            
            // Configuración de ordenamiento
            order: [[1, 'desc']], // Ordenar por fecha descendente por defecto
            
            // Configuración de búsqueda
            search: {
                smart: true,
                regex: false,
                caseInsensitive: true
            },
            
            // Configuración de responsive
            responsive: {
                details: {
                    display: $.fn.dataTable.Responsive.display.modal({
                        header: function (row) {
                            var data = row.data();
                            return 'Detalles de la acción #' + data[0];
                        }
                    }),
                    renderer: $.fn.dataTable.Responsive.renderer.tableAll()
                }
            },
            
            // Configuración de AJAX
            ajax: {
                url: 'parts/usuarios/main/load_usuario_acciones.php',
                type: 'POST',
                data: function (d) {
                    d.id_usuario = idUsuario;
                    return d;
                },
                dataSrc: function (json) {
                    if (json.error) {
                        console.error('Error del servidor:', json.error);
                    }
                    return json.data || [];
                },
                error: function (xhr, error, thrown) {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            title: 'Error al cargar datos',
                            text: 'No se pudieron cargar las acciones del usuario. Por favor, recarga la página.',
                            icon: 'error',
                            confirmButtonText: 'Aceptar',
                            confirmButtonColor: '#dc3545'
                        });
                    }
                }
            },
            
            // Configuración de inicialización
            initComplete: function () {
                // DataTable inicializado
            }
        });
    }
}

// Función para recargar el DataTable
function recargarDataTableAcciones() {
    if (dt_usuario_acciones) {
        dt_usuario_acciones.ajax.reload();
    }
}

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    // Obtener el ID del usuario desde la URL o variable global
    const urlParams = new URLSearchParams(window.location.search);
    const idUsuario = urlParams.get('id') || 0;
    
    if (idUsuario) {
        // Buscar el botón del tab, no el contenido del tab
        const btnTabActividad = document.querySelector('button[data-bs-target="#navs-pills-top-actividad"]');
        
        if (btnTabActividad) {
            // Inicializar el DataTable cuando se active el tab de Actividad
            btnTabActividad.addEventListener('shown.bs.tab', function(e) {
                if (!dt_usuario_acciones) {
                    inicializarDataTableAcciones(idUsuario);
                } else {
                    recargarDataTableAcciones();
                }
            });
            
            // Si el tab de actividad está activo al cargar, inicializar inmediatamente
            if (btnTabActividad.classList.contains('active')) {
                inicializarDataTableAcciones(idUsuario);
            }
        }
    }
});
