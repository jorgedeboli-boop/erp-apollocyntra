<!-- JavaScript -->
<?php
$vLoadStats = filemtime(__DIR__ . '/load_stats.js');
$vTablesDatatablesLoad = filemtime(__DIR__ . '/tables-datatables-load.js');
?>
<script src="parts/proveedores/listar/load_stats.js?v=<?php echo $vLoadStats; ?>"></script>
<script src="parts/proveedores/listar/tables-datatables-load.js?v=<?php echo $vTablesDatatablesLoad; ?>"></script>

<script>
/**
 * Función para eliminar proveedor
 */
function eliminarProveedor(idProveedor) {
    Swal.fire({
        title: '¿Eliminar proveedor?',
        text: 'Esta acción eliminará el proveedor y TODOS sus datos asociados. Esta acción NO se puede deshacer.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, eliminar proveedor',
        cancelButtonText: 'Cancelar',
        showCloseButton: true,
        allowOutsideClick: false
    }).then((result) => {
        if (result.isConfirmed) {
            // Mostrar loading
            Swal.fire({
                title: 'Eliminando proveedor...',
                text: 'Por favor espera mientras se eliminan todos los datos',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            const formData = new FormData();
            formData.append('id_proveedor', idProveedor);

            fetch('parts/proveedores/listar/eliminar_proveedor.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Error en la respuesta del servidor');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        title: '¡Proveedor eliminado!',
                        text: data.message,
                        icon: 'success',
                        confirmButtonText: 'Aceptar'
                    }).then(() => {
                        // Recargar la tabla
                        if (window.dt_proveedores) {
                            window.dt_proveedores.ajax.reload();
                        }
                        // Recargar estadísticas
                        if (typeof cargarEstadisticas === 'function') {
                            cargarEstadisticas();
                        }
                    });
                } else {
                    throw new Error(data.message || 'Error al eliminar el proveedor');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    title: 'Error',
                    text: error.message || 'Error al eliminar el proveedor',
                    icon: 'error',
                    confirmButtonText: 'Aceptar'
                });
            });
        }
    });
}
</script>