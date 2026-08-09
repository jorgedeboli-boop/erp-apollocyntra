<!-- JavaScript -->
<?php
$vLoadStats = filemtime(__DIR__ . '/../../../gastos/listar/load_stats.js');
$vTablesDatatablesLoad = filemtime(__DIR__ . '/../../../gastos/listar/tables-datatables-load.js');
?>
<script src="parts/gastos/listar/load_stats.js?v=<?php echo $vLoadStats; ?>"></script>
<script src="parts/gastos/listar/tables-datatables-load.js?v=<?php echo $vTablesDatatablesLoad; ?>"></script>

<script>
/**
 * Función para eliminar gasto
 */
function eliminarGasto(idGasto) {
    Swal.fire({
        title: '¿Eliminar gasto?',
        text: 'Esta acción eliminará el gasto y TODOS sus datos asociados. Esta acción NO se puede deshacer.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, eliminar gasto',
        cancelButtonText: 'Cancelar',
        showCloseButton: true,
        allowOutsideClick: false
    }).then((result) => {
        if (result.isConfirmed) {
            // Mostrar loading
            Swal.fire({
                title: 'Eliminando gasto...',
                text: 'Por favor espera mientras se eliminan todos los datos',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            const formData = new FormData();
            formData.append('id_gasto', idGasto);

            fetch('parts/gastos/listar/eliminar_gasto.php', {
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
                        title: '¡Gasto eliminado!',
                        text: data.message,
                        icon: 'success',
                        confirmButtonText: 'Aceptar'
                    }).then(() => {
                        // Recargar la tabla
                        if (window.dt_gastos) {
                            window.dt_gastos.ajax.reload();
                        }
                    });
                } else {
                    throw new Error(data.message || 'Error al eliminar el gasto');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    title: 'Error',
                    text: error.message || 'Error al eliminar el gasto',
                    icon: 'error',
                    confirmButtonText: 'Aceptar'
                });
            });
        }
    });
}
</script>