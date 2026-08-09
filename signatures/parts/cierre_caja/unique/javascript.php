<!-- JAVASCRIPT CUSTOM cierre_caja - unique  -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    
    function calcularTotales() {
        let totalEfectivo = 0;
        
        // Calcular cada línea
        document.querySelectorAll('.unidades').forEach(function(input) {
            const unidades = parseFloat(input.value) || 0;
            const valor = parseFloat(input.dataset.valor);
            const totalLinea = unidades * valor;
            
            // Actualizar el total de la línea
            const totalDisplay = input.closest('tr').querySelector('.total-linea-display');
            totalDisplay.textContent = totalLinea.toFixed(2) + ' €';
            
            // Sumar al total efectivo
            totalEfectivo += totalLinea;
        });
        
        // Actualizar total efectivo
        document.getElementById('totalEfectivo').textContent = totalEfectivo.toFixed(2) + ' €';
        
        // Calcular diferencia
        const caja = parseFloat(document.getElementById('inputCaja').value) || 0;
        const diferencia = caja - totalEfectivo;
        const diferenciaElement = document.getElementById('totalDiferencia');
        const boxDiferencia = document.getElementById('boxDiferencia');
        const btnGuardar = document.getElementById('btnGuardarArqueo');
        
        diferenciaElement.textContent = diferencia.toFixed(2) + ' €';
        
        // Cambiar color según la diferencia
        diferenciaElement.style.color = '';
        
        if (diferencia < 0) {
            // Negativa: rojo - deshabilitar botón
            diferenciaElement.style.color = '#dc3545';
            btnGuardar.disabled = true;
        } else if (diferencia === 0) {
            // Cero: verde - habilitar botón
            diferenciaElement.style.color = '#28a745';
            btnGuardar.disabled = false;
        } else {
            // Positiva: amarillo - deshabilitar botón
            diferenciaElement.style.color = '#ffc107';
            btnGuardar.disabled = true;
        }
    }
    
    // Forzar a 0 si el input está vacío
    function forzarCero(input) {
        if (input.value === '' || input.value === null) {
            input.value = 0;
        }
    }
    
    // Escuchar cambios en todos los inputs de unidades
    document.querySelectorAll('.unidades').forEach(function(input) {
        input.addEventListener('input', calcularTotales);
        input.addEventListener('blur', function() {
            forzarCero(this);
            calcularTotales();
        });
    });
    
    // Evento click del botón guardar
    document.getElementById('btnGuardarArqueo').addEventListener('click', function() {
        Swal.fire({
            title: '¿Guardar arqueo?',
            text: '¿Está seguro de que desea guardar el arqueo de caja?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sí, guardar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#6c757d',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                // Mostrar loading
                Swal.fire({
                    title: 'Guardando...',
                    text: 'Por favor espere',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
                
                // PREPARAR DATOS PARA GUARDAR
                const datos = new FormData();
                
                // Billetes (unidades b)
                datos.append('billete500', document.getElementById('billete500').value);
                datos.append('billete200', document.getElementById('billete200').value);
                datos.append('billete100', document.getElementById('billete100').value);
                datos.append('billete50', document.getElementById('billete50').value);
                datos.append('billete20', document.getElementById('billete20').value);
                datos.append('billete10', document.getElementById('billete10').value);
                datos.append('billete5', document.getElementById('billete5').value);
                
                // Monedas euro (unidades m)
                datos.append('moneda2', document.getElementById('moneda2').value);
                datos.append('moneda1', document.getElementById('moneda1').value);
                
                // Monedas céntimos (unidades)
                datos.append('50cent', document.getElementById('moneda50cent').value);
                datos.append('20cent', document.getElementById('moneda20cent').value);
                datos.append('10cent', document.getElementById('moneda10cent').value);
                datos.append('5cent', document.getElementById('moneda5cent').value);
                datos.append('2cent', document.getElementById('moneda2cent').value);
                datos.append('1cent', document.getElementById('moneda1cent').value);
                
                // Totales de billetes (t)
                datos.append('t500', (parseFloat(document.getElementById('billete500').value) * 500).toFixed(2));
                datos.append('t200', (parseFloat(document.getElementById('billete200').value) * 200).toFixed(2));
                datos.append('t100', (parseFloat(document.getElementById('billete100').value) * 100).toFixed(2));
                datos.append('t50', (parseFloat(document.getElementById('billete50').value) * 50).toFixed(2));
                datos.append('t20', (parseFloat(document.getElementById('billete20').value) * 20).toFixed(2));
                datos.append('t10', (parseFloat(document.getElementById('billete10').value) * 10).toFixed(2));
                datos.append('t5', (parseFloat(document.getElementById('billete5').value) * 5).toFixed(2));
                
                // Totales de monedas euro (t)
                datos.append('t2', (parseFloat(document.getElementById('moneda2').value) * 2).toFixed(2));
                datos.append('t1', (parseFloat(document.getElementById('moneda1').value) * 1).toFixed(2));
                
                // Totales de monedas céntimos (t)
                datos.append('t50cent', (parseFloat(document.getElementById('moneda50cent').value) * 0.50).toFixed(2));
                datos.append('t20cent', (parseFloat(document.getElementById('moneda20cent').value) * 0.20).toFixed(2));
                datos.append('t10cent', (parseFloat(document.getElementById('moneda10cent').value) * 0.10).toFixed(2));
                datos.append('t5cent', (parseFloat(document.getElementById('moneda5cent').value) * 0.05).toFixed(2));
                datos.append('t2cent', (parseFloat(document.getElementById('moneda2cent').value) * 0.02).toFixed(2));
                datos.append('t1cent', (parseFloat(document.getElementById('moneda1cent').value) * 0.01).toFixed(2));
                
                // Totales generales
                const totalEfectivo = parseFloat(document.getElementById('totalEfectivo').textContent.replace(' €', '')) || 0;
                const totalCaja = parseFloat(document.getElementById('inputCaja').value) || 0;
                const totalDiferencia = parseFloat(document.getElementById('totalDiferencia').textContent.replace(' €', '')) || 0;
                
                datos.append('efectivo', totalEfectivo.toFixed(2));
                datos.append('caja', totalCaja.toFixed(2));
                datos.append('diferencia', totalDiferencia.toFixed(2));
                
                // ID de sucursal
                const urlParams = new URLSearchParams(window.location.search);
                datos.append('id_sucursal', urlParams.get('id'));
                
                // Llamada AJAX para guardar el arqueo
                fetch('parts/cierre_caja/unique/guardar_arqueo.php', {
                    method: 'POST',
                    body: datos
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            title: '¡Guardado!',
                            text: data.message,
                            icon: 'success',
                            confirmButtonText: 'Aceptar'
                        }).then(() => {
                            // Ocultar botón Guardar Arqueo y mostrar botón Cerrar Caja
                            document.getElementById('btnGuardarArqueo').style.display = 'none';
                            document.getElementById('btnCerrarCaja').style.display = 'inline-block';
                            
                            // Recargar el DataTable de cierres si existe
                            if (window.dt_cierres) {
                                window.dt_cierres.ajax.reload();
                            }
                        });
                    } else {
                        throw new Error(data.message || 'Error al guardar el arqueo');
                    }
                })
                .catch(error => {
                    Swal.fire({
                        title: 'Error',
                        text: error.message || 'Error al guardar el arqueo',
                        icon: 'error',
                        confirmButtonText: 'Aceptar'
                    });
                });
            }
        });
    });
    
    // Evento click del botón Cerrar Caja
    document.getElementById('btnCerrarCaja').addEventListener('click', function() {
        const urlParams = new URLSearchParams(window.location.search);
        const idSucursal = urlParams.get('id');
        const nombreSucursal = urlParams.get('nombre');
        
        Swal.fire({
            title: '¿Cerrar caja?',
            html: `¿Está seguro de que desea cerrar la caja de <strong>${nombreSucursal}</strong>?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, cerrar caja',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                // Mostrar loading
                Swal.fire({
                    title: 'Cerrando caja...',
                    text: 'Por favor espere',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
                
                // Obtener el total de caja
                const totalCaja = parseFloat(document.getElementById('inputCaja').value) || 0;
                
                // Preparar datos
                const formData = new FormData();
                formData.append('id_sucursal', idSucursal);
                formData.append('total_caja', totalCaja);
                
                // Llamada AJAX para cerrar la caja
                fetch('parts/cierre_caja/unique/cerrar_caja.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            title: '¡Caja Cerrada!',
                            text: data.message,
                            icon: 'success',
                            confirmButtonText: 'Aceptar'
                        }).then(() => {
                            // Redirigir a estados de cajas
                            window.location.href = 'estados_cajas.php';
                        });
                    } else {
                        throw new Error(data.message || 'Error al cerrar la caja');
                    }
                })
                .catch(error => {
                    Swal.fire({
                        title: 'Error',
                        text: error.message || 'Error al cerrar la caja',
                        icon: 'error',
                        confirmButtonText: 'Aceptar'
                    });
                });
            }
        });
    });
    
    // Calcular al cargar
    calcularTotales();
    
    // ========================================
    // DataTable de Histórico de Cierres
    // ========================================
    
    // Obtener ID de sucursal de la URL
    const urlParams = new URLSearchParams(window.location.search);
    const idSucursal = urlParams.get('id');
    
    // Variable para el periodo activo
    window.filtro_periodo_activo_cierres = 'todos';
    
    if (idSucursal && document.getElementById('dt_cierres_table')) {
        window.dt_cierres = $('#dt_cierres_table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: 'parts/cierre_caja/unique/load_cierres.php',
                type: 'POST',
                data: function(d) {
                    d.id_sucursal = idSucursal;
                    d.filtro_fecha_desde = document.getElementById('filtro_fecha_desde_cierres') ? document.getElementById('filtro_fecha_desde_cierres').value : '';
                    d.filtro_fecha_hasta = document.getElementById('filtro_fecha_hasta_cierres') ? document.getElementById('filtro_fecha_hasta_cierres').value : '';
                    d.filtro_periodo = window.filtro_periodo_activo_cierres || 'todos';
                }
            },
            columns: [
                { 
                    data: 0,
                    title: 'Nº Arqueo'
                },
                { 
                    data: 1,
                    title: 'Fecha de Arqueo'
                },
                { 
                    data: 2,
                    title: 'Caja'
                },
                { 
                    data: 3,
                    title: 'Efectivo'
                },
                { 
                    data: 4,
                    title: 'Diferencia'
                }
            ],
            columnDefs: [
                {
                    // Nº Arqueo
                    targets: 0,
                    className: 'text-center'
                },
                {
                    // Fecha de Arqueo
                    targets: 1,
                    render: function(data, type, row) {
                        if (type === 'display' && data) {
                            // Formatear fecha: DD/MM/YYYY HH:mm
                            const fecha = new Date(data);
                            const dia = String(fecha.getDate()).padStart(2, '0');
                            const mes = String(fecha.getMonth() + 1).padStart(2, '0');
                            const anio = fecha.getFullYear();
                            const horas = String(fecha.getHours()).padStart(2, '0');
                            const minutos = String(fecha.getMinutes()).padStart(2, '0');
                            return `${dia}/${mes}/${anio} ${horas}:${minutos}`;
                        }
                        return data;
                    }
                },
                {
                    // Caja
                    targets: 2,
                    className: 'text-end',
                    render: function(data, type, row) {
                        if (type === 'display') {
                            const valor = parseFloat(data) || 0;
                            return `<span style="font-weight: 600; color: #007bff;">${valor.toFixed(2)} €</span>`;
                        }
                        return data;
                    }
                },
                {
                    // Efectivo
                    targets: 3,
                    className: 'text-end',
                    render: function(data, type, row) {
                        if (type === 'display') {
                            const valor = parseFloat(data) || 0;
                            return `<span style="font-weight: 600; color: #28a745;">${valor.toFixed(2)} €</span>`;
                        }
                        return data;
                    }
                },
                {
                    // Diferencia
                    targets: 4,
                    className: 'text-end',
                    render: function(data, type, row) {
                        if (type === 'display') {
                            const valor = parseFloat(data) || 0;
                            const color = valor === 0 ? '#6c757d' : (valor > 0 ? '#28a745' : '#dc3545');
                            return `<span style="font-weight: 600; color: ${color};">${valor.toFixed(2)} €</span>`;
                        }
                        return data;
                    }
                }
            ],
            order: [[0, 'desc']], // Ordenar por Nº Arqueo descendente
            pageLength: 10,
            language: DATATABLES_SPANISH,
            layout: {
                topStart: {
                    rowClass: 'row m-2 my-0 mt-0 justify-content-between',
                    features: [
                        {
                            buttons: [
                                {
                                    extend: 'collection',
                                    className: 'btn buttons-collection btn-outline-secondary dropdown-toggle waves-effect',
                                    text: '<span class="d-flex align-items-center gap-2"><i class="icon-base ri ri-upload-2-line icon-16px me-sm-1"></i> <span class="d-none d-sm-inline-block">Exportar</span></span>',
                                    buttons: [
                                        {
                                            extend: 'print',
                                            text: '<span class="d-flex align-items-center"><i class="icon-base ri ri-printer-line me-1"></i>Imprimir</span>',
                                            className: 'dropdown-item',
                                            exportOptions: {
                                                columns: ':visible',
                                                format: {
                                                    body: function (data, row, column, node) {
                                                        if (typeof data === 'string') {
                                                            const tempDiv = document.createElement('div');
                                                            tempDiv.innerHTML = data;
                                                            return tempDiv.textContent || tempDiv.innerText || data;
                                                        }
                                                        return data;
                                                    }
                                                }
                                            }
                                        },
                                        {
                                            extend: 'csv',
                                            text: '<span class="d-flex align-items-center"><i class="icon-base ri ri-file-text-line me-1"></i>CSV</span>',
                                            className: 'dropdown-item',
                                            exportOptions: {
                                                columns: ':visible',
                                                format: {
                                                    body: function (data, row, column, node) {
                                                        if (typeof data === 'string') {
                                                            const tempDiv = document.createElement('div');
                                                            tempDiv.innerHTML = data;
                                                            return tempDiv.textContent || tempDiv.innerText || data;
                                                        }
                                                        return data;
                                                    }
                                                }
                                            }
                                        },
                                        {
                                            extend: 'excel',
                                            text: '<span class="d-flex align-items-center"><i class="icon-base ri ri-file-excel-line me-1"></i>Excel</span>',
                                            className: 'dropdown-item',
                                            exportOptions: {
                                                columns: ':visible',
                                                format: {
                                                    body: function (data, row, column, node) {
                                                        if (typeof data === 'string') {
                                                            const tempDiv = document.createElement('div');
                                                            tempDiv.innerHTML = data;
                                                            return tempDiv.textContent || tempDiv.innerText || data;
                                                        }
                                                        return data;
                                                    }
                                                }
                                            }
                                        },
                                        {
                                            extend: 'pdf',
                                            text: '<span class="d-flex align-items-center"><i class="icon-base ri ri-file-pdf-line me-1"></i>PDF</span>',
                                            className: 'dropdown-item',
                                            orientation: 'landscape',
                                            exportOptions: {
                                                columns: ':visible',
                                                format: {
                                                    body: function (data, row, column, node) {
                                                        if (typeof data === 'string') {
                                                            const tempDiv = document.createElement('div');
                                                            tempDiv.innerHTML = data;
                                                            return tempDiv.textContent || tempDiv.innerText || data;
                                                        }
                                                        return data;
                                                    }
                                                }
                                            },
                                            customize: function(doc) {
                                                doc.pageOrientation = 'landscape';
                                                doc.pageSize = 'LEGAL';
                                                doc.defaultStyle.fontSize = 7;
                                                doc.styles.tableHeader.fontSize = 8;
                                                doc.styles.tableHeader.fillColor = '#2d4154';
                                                doc.styles.tableHeader.bold = true;
                                                doc.styles.tableHeader.color = 'white';
                                                
                                                doc.content[0].text = 'Histórico de Cierres de Caja';
                                                doc.content[0].alignment = 'center';
                                                doc.content[0].fontSize = 14;
                                                doc.content[0].margin = [0, 0, 0, 10];
                                                
                                                doc.pageMargins = [5, 5, 5, 5];
                                                doc.content[1].table.widths = Array(doc.content[1].table.body[0].length).fill('*');
                                            }
                                        },
                                        {
                                            extend: 'copy',
                                            text: '<i class="icon-base ri ri-file-copy-line me-1"></i>Copiar',
                                            className: 'dropdown-item',
                                            exportOptions: {
                                                columns: ':visible',
                                                format: {
                                                    body: function (data, row, column, node) {
                                                        if (typeof data === 'string') {
                                                            const tempDiv = document.createElement('div');
                                                            tempDiv.innerHTML = data;
                                                            return tempDiv.textContent || tempDiv.innerText || data;
                                                        }
                                                        return data;
                                                    }
                                                }
                                            }
                                        }
                                    ]
                                }
                            ]
                        }
                    ]
                },
                topEnd: {
                    features: [
                        {
                            search: {
                                placeholder: 'Buscar...',
                                text: '_INPUT_'
                            }
                        }
                    ]
                },
                bottomStart: 'info',
                bottomEnd: 'paging'
            }
        });
        
        // ========================================
        // Filtros de Fecha para Cierres
        // ========================================
        
        // Flatpickr para el rango de fechas
        flatpickr('#rangeFechasCierres', {
            mode: 'range',
            dateFormat: 'd/m/Y',
            locale: 'es',
            rangeSeparator: ' hasta ',
            onChange: function(selectedDates, dateStr, instance) {
                if (selectedDates.length === 2) {
                    // Formatear fechas a YYYY-MM-DD para MySQL
                    const fechaDesde = selectedDates[0].getFullYear() + '-' + 
                                      String(selectedDates[0].getMonth() + 1).padStart(2, '0') + '-' + 
                                      String(selectedDates[0].getDate()).padStart(2, '0');
                    const fechaHasta = selectedDates[1].getFullYear() + '-' + 
                                      String(selectedDates[1].getMonth() + 1).padStart(2, '0') + '-' + 
                                      String(selectedDates[1].getDate()).padStart(2, '0');
                    
                    document.getElementById('filtro_fecha_desde_cierres').value = fechaDesde;
                    document.getElementById('filtro_fecha_hasta_cierres').value = fechaHasta;
                    window.filtro_periodo_activo_cierres = 'rango';
                    
                    // Remover clase active de los botones
                    document.querySelectorAll('#filtro_hoy_cierres, #filtro_mes_cierres, #filtro_todos_cierres').forEach(btn => {
                        btn.classList.remove('active');
                    });
                    
                    // Prepend "Desde " al valor mostrado
                    instance.input.value = 'Desde ' + dateStr;
                    
                    // Recargar la tabla
                    if (window.dt_cierres) {
                        window.dt_cierres.ajax.reload();
                    }
                }
            }
        });
        
        // Limpiar inputs de fecha al cargar la página
        const filtroFechaDesde = document.getElementById('filtro_fecha_desde_cierres');
        const filtroFechaHasta = document.getElementById('filtro_fecha_hasta_cierres');
        const rangeFechas = document.getElementById('rangeFechasCierres');
        
        if (filtroFechaDesde) filtroFechaDesde.value = '';
        if (filtroFechaHasta) filtroFechaHasta.value = '';
        if (rangeFechas) rangeFechas.value = '';
        
        // Event listeners para los botones de filtro
        document.getElementById('filtro_hoy_cierres')?.addEventListener('click', function() {
            window.filtro_periodo_activo_cierres = 'hoy';
            
            // Limpiar rango de fechas
            document.getElementById('filtro_fecha_desde_cierres').value = '';
            document.getElementById('filtro_fecha_hasta_cierres').value = '';
            document.getElementById('rangeFechasCierres').value = '';
            
            // Actualizar clase active
            document.querySelectorAll('#filtro_hoy_cierres, #filtro_mes_cierres, #filtro_todos_cierres').forEach(btn => {
                btn.classList.remove('active');
            });
            this.classList.add('active');
            
            // Recargar tabla
            if (window.dt_cierres) {
                window.dt_cierres.ajax.reload();
            }
        });
        
        document.getElementById('filtro_mes_cierres')?.addEventListener('click', function() {
            window.filtro_periodo_activo_cierres = 'mes';
            
            // Limpiar rango de fechas
            document.getElementById('filtro_fecha_desde_cierres').value = '';
            document.getElementById('filtro_fecha_hasta_cierres').value = '';
            document.getElementById('rangeFechasCierres').value = '';
            
            // Actualizar clase active
            document.querySelectorAll('#filtro_hoy_cierres, #filtro_mes_cierres, #filtro_todos_cierres').forEach(btn => {
                btn.classList.remove('active');
            });
            this.classList.add('active');
            
            // Recargar tabla
            if (window.dt_cierres) {
                window.dt_cierres.ajax.reload();
            }
        });
        
        document.getElementById('filtro_todos_cierres')?.addEventListener('click', function() {
            window.filtro_periodo_activo_cierres = 'todos';
            
            // Limpiar rango de fechas
            document.getElementById('filtro_fecha_desde_cierres').value = '';
            document.getElementById('filtro_fecha_hasta_cierres').value = '';
            document.getElementById('rangeFechasCierres').value = '';
            
            // Actualizar clase active
            document.querySelectorAll('#filtro_hoy_cierres, #filtro_mes_cierres, #filtro_todos_cierres').forEach(btn => {
                btn.classList.remove('active');
            });
            this.classList.add('active');
            
            // Recargar tabla
            if (window.dt_cierres) {
                window.dt_cierres.ajax.reload();
            }
        });
        
        // ========================================
        // Ajustes de clases CSS del DataTable
        // ========================================
        
        // Ajustar las clases CSS de los elementos del DataTable después de la inicialización
        setTimeout(() => {
            const elementsToModify = [
                { selector: '.dt-buttons .btn', classToRemove: 'btn-secondary' },
                { selector: '.dt-length .form-select', classToAdd: 'ms-0' },
                { selector: '.dt-length', classToAdd: 'mb-md-4 mb-0' },
                {
                    selector: '.dt-layout-end',
                    classToRemove: 'justify-content-between',
                    classToAdd: 'd-flex gap-md-4 justify-content-md-between justify-content-center gap-md-2 flex-wrap mt-0'
                },
                { selector: '.dt-layout-start', classToAdd: 'mt-md-0 mt-5' },
                {
                    selector: '.dt-layout-start .dt-buttons',
                    classToAdd: 'd-md-flex d-block gap-4 justify-content-center'
                },
                {
                    selector: '.dt-layout-end .dt-buttons',
                    classToAdd: 'd-md-flex d-block gap-4 mb-md-0 mb-5 justify-content-center'
                },
                { selector: '.dt-layout-table', classToRemove: 'row mt-2' },
                { selector: '.dt-layout-full', classToRemove: 'col-md col-12' },
                { selector: '.dt-layout-full .table', classToAdd: 'table-responsive' }
            ];

            elementsToModify.forEach(({ selector, classToRemove, classToAdd }) => {
                document.querySelectorAll(selector).forEach(element => {
                    if (classToRemove) {
                        classToRemove.split(' ').forEach(className => element.classList.remove(className));
                    }
                    if (classToAdd) {
                        classToAdd.split(' ').forEach(className => element.classList.add(className));
                    }
                });
            });
        }, 100);
    }
});
</script>
