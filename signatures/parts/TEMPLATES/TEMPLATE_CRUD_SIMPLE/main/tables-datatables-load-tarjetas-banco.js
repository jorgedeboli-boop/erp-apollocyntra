/**
 * DataTable para Tarjetas Banco de Empresas
 */

$(document).ready(function() {
    // Inicializar DataTable de tarjetas banco
    if ($('.datatables-tarjetas-banco').length) {
        const tableTarjetasBanco = $('.datatables-tarjetas-banco').DataTable({
            ajax: {
                url: 'parts/empresas/main/get_tarjetas_banco.php',
                type: 'GET',
                data: function(d) {
                    d.id_empresa = window.idEmpresa || 0;
                },
                dataSrc: function(json) {
                    if (json.success) {
                        return json.tarjetas;
                    } else {
                        console.error('Error al cargar tarjetas banco:', json.message);
                        return [];
                    }
                }
            },
            columns: [
                {
                    data: 'numerotarjeta',
                    render: function(data, type, row) {
                        return `<span class="fw-medium">${data}</span>`;
                    }
                },
                {
                    data: 'banco_tarjeta',
                    render: function(data, type, row) {
                        return `<span class="text-body">${data}</span>`;
                    }
                },
                {
                    data: 'por_defecto',
                    render: function(data, type, row) {
                        if (data === 'true') {
                            return '<span class="badge bg-success">Por Defecto</span>';
                        } else {
                            return '<span class="badge bg-secondary">No</span>';
                        }
                    }
                },
                {
                    data: 'fecha_creacion',
                    render: function(data, type, row) {
                        if (type === 'display') {
                            const fecha = new Date(data);
                            return fecha.toLocaleDateString('es-ES');
                        }
                        return data;
                    }
                },
                {
                    data: 'id_tarjeta_banco',
                    orderable: false,
                    searchable: false,
                    render: function(data, type, row) {
                        let botones = '';
                        
                        // Solo mostrar dropdown si no es por defecto
                        if (row.por_defecto !== 'true') {
                            botones += `
                                <div class="dropdown d-inline-block">
                                    <a href="javascript:;" class="btn btn-icon btn-text-secondary rounded-pill dropdown-toggle hide-arrow" 
                                       data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="icon-base ri ri-more-2-line icon-md"></i>
                                    </a>
                                    <div class="dropdown-menu dropdown-menu-end m-0">
                                        <a href="javascript:;" class="dropdown-item" onclick="ponerPorDefectoTarjetaBanco(${data})">
                                            <i class="icon-base ri ri-check-line me-2"></i>
                                            <span class="align-middle">Poner por defecto</span>
                                        </a>
                                        <a href="javascript:;" class="dropdown-item" onclick="eliminarTarjetaBanco(${data})">
                                            <i class="icon-base ri ri-delete-bin-line me-2"></i>
                                            <span class="align-middle">Eliminar</span>
                                        </a>
                                    </div>
                                </div>
                            `;
                        } else {
                            botones = '<span class="badge bg-success">Por Defecto</span>';
                        }
                        
                        return botones;
                    }
                }
            ],
            order: [[3, 'desc']], // Ordenar por fecha de creación
            pageLength: 10,
            lengthMenu: [5, 10, 25, 50],
            // Configuración de idioma en español
            language: {
                lengthMenu: "Mostrar _MENU_ registros por página",
                zeroRecords: "No se encontraron registros",
                info: "Mostrando _START_ a _END_ de _TOTAL_ registros",
                infoEmpty: "Mostrando 0 a 0 de 0 registros",
                infoFiltered: "(filtrado de _MAX_ registros totales)",
                search: "Buscar:",
                paginate: {
                    first: "Primero",
                    previous: "Anterior",
                    next: "Siguiente",
                    last: "Último"
                },
                processing: "Procesando...",
                emptyTable: "No hay datos disponibles en la tabla"
            },
            dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6 d-flex justify-content-end"f>>' +
                  '<"row"<"col-sm-12"tr>>' +
                  '<"row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
            responsive: true,
            processing: true,
            serverSide: false,
            drawCallback: function(settings) {
                // Personalizar después de dibujar la tabla
                if (settings.json && settings.json.total === 0) {
                    $('.datatables-tarjetas-banco tbody').html(
                        '<tr><td colspan="5" class="text-center text-muted py-4">' +
                        '<i class="icon-base ri ri-bank-card-line icon-48px d-block mb-2"></i>' +
                        'No hay tarjetas banco configuradas para esta empresa</td></tr>'
                    );
                }
            }
        });
        
        // Guardar referencia global para poder recargar
        window.tableTarjetasBanco = tableTarjetasBanco;
    }
});
