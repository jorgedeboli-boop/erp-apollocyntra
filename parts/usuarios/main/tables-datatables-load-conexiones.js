/**
 * DataTable para conexiones del usuario
 */

'use strict';

// Variable global para DataTable
let dt_usuario_conexiones;

// Función para inicializar el DataTable de conexiones
function inicializarDataTableConexiones(idUsuario) {
    const dt_table = document.querySelector('.datatables-usuario-conexiones');
    
    if (!dt_table) {
        return;
    }
    
    if (dt_table.classList.contains('dataTable')) {
        return;
    }
    
    if (dt_table && !dt_table.classList.contains('dataTable')) {
        dt_usuario_conexiones = new DataTable(dt_table, {
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
                { data: 2 }, // Estado
                { data: 3 }, // IP
                { data: 4 }, // User Agent
                { data: 5 }, // Ubicación
                { data: 6 }  // Token
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
                    // Estado column
                    targets: 2,
                    responsivePriority: 3,
                    render: function (data, type, full, meta) {
                        if (data === 'true') {
                            return '<span class="badge bg-label-success rounded-pill">' +
                                   '<i class="icon-base ri ri-wifi-line me-1"></i>' +
                                   'Conectado' +
                                   '</span>';
                        } else {
                            return '<span class="badge bg-label-secondary rounded-pill">' +
                                   '<i class="icon-base ri ri-wifi-off-line me-1"></i>' +
                                   'Desconectado' +
                                   '</span>';
                        }
                    }
                },
                {
                    // IP column
                    targets: 3,
                    responsivePriority: 4,
                    render: function (data, type, full, meta) {
                        if (data && data !== 'N/A') {
                            return '<code class="text-primary">' + data + '</code>';
                        } else {
                            return '<span class="text-muted">N/A</span>';
                        }
                    }
                },
                {
                    // User Agent column
                    targets: 4,
                    responsivePriority: 5,
                    render: function (data, type, full, meta) {
                        if (data && data !== 'N/A') {
                            return '<span class="text-truncate d-inline-block" style="max-width: 200px;" title="' + data + '">' + data + '</span>';
                        } else {
                            return '<span class="text-muted">N/A</span>';
                        }
                    }
                },
                {
                    // Ubicación column
                    targets: 5,
                    responsivePriority: 6,
                    render: function (data, type, full, meta) {
                        if (data && data !== 'N/A') {
                            return '<span class="badge bg-label-warning rounded-pill">' +
                                   '<i class="icon-base ri ri-map-pin-line me-1"></i>' +
                                   data +
                                   '</span>';
                        } else {
                            return '<span class="text-muted">N/A</span>';
                        }
                    }
                },
                {
                    // Token column
                    targets: 6,
                    responsivePriority: 7,
                    render: function (data, type, full, meta) {
                        if (data && data !== 'N/A') {
                            return '<code class="text-secondary text-truncate d-inline-block" style="max-width: 150px;" title="' + data + '">' + data + '</code>';
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
                            return 'Detalles de la conexión #' + data[0];
                        }
                    }),
                    renderer: $.fn.dataTable.Responsive.renderer.tableAll()
                }
            },
            
            // Configuración de AJAX
            ajax: {
                url: 'parts/usuarios/main/load_usuario_conexiones.php',
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
                            text: 'No se pudieron cargar las conexiones del usuario. Por favor, recarga la página.',
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
function recargarDataTableConexiones() {
    if (dt_usuario_conexiones) {
        dt_usuario_conexiones.ajax.reload();
    }
}

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    // Obtener el ID del usuario desde la URL o variable global
    const urlParams = new URLSearchParams(window.location.search);
    const idUsuario = urlParams.get('id') || 0;
    
    if (idUsuario) {
        // Buscar el botón del tab, no el contenido del tab
        const btnTabConexiones = document.querySelector('button[data-bs-target="#navs-pills-top-conexiones"]');
        
        if (btnTabConexiones) {
            // Inicializar el DataTable cuando se active el tab de Conexiones
            btnTabConexiones.addEventListener('shown.bs.tab', function(e) {
                if (!dt_usuario_conexiones) {
                    inicializarDataTableConexiones(idUsuario);
                } else {
                    recargarDataTableConexiones();
                }
            });
            
            // Si el tab de conexiones está activo al cargar, inicializar inmediatamente
            if (btnTabConexiones.classList.contains('active')) {
                inicializarDataTableConexiones(idUsuario);
            }
        }
    }
});

