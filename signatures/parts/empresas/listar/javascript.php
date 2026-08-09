<!-- JavaScript -->
<?php
$vLoadStats = filemtime(__DIR__ . '/load_stats.js');
$vTablesDatatablesLoad = filemtime(__DIR__ . '/tables-datatables-load.js');
?>
<script src="parts/empresas/listar/load_stats.js?v=<?php echo $vLoadStats; ?>"></script>
<script src="parts/empresas/listar/tables-datatables-load.js?v=<?php echo $vTablesDatatablesLoad; ?>"></script>

<script>
/**
 * Función para eliminar empresa
 */
function eliminarEmpresa(idEmpresa) {
    Swal.fire({
        title: '¿Eliminar empresa?',
        text: 'Esta acción eliminará la empresa y TODOS sus datos asociados (logotipo, cuentas bancarias, tarjetas, sucursales). Esta acción NO se puede deshacer.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, eliminar empresa',
        cancelButtonText: 'Cancelar',
        showCloseButton: true,
        allowOutsideClick: false
    }).then((result) => {
        if (result.isConfirmed) {
            // Mostrar loading
            Swal.fire({
                title: 'Eliminando empresa...',
                text: 'Por favor espera mientras se eliminan todos los datos',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            const formData = new FormData();
            formData.append('id_empresa', idEmpresa);

            fetch('parts/empresas/listar/eliminar_empresa.php', {
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
                        title: '¡Empresa eliminada!',
                        text: data.message,
                        icon: 'success',
                        confirmButtonText: 'Aceptar'
                    }).then(() => {
                        // Recargar la tabla
                        if (window.dt_empresas) {
                            window.dt_empresas.ajax.reload();
                        }
                        // Recargar estadísticas
                        if (typeof cargarEstadisticas === 'function') {
                            cargarEstadisticas();
                        }
                    });
                } else {
                    throw new Error(data.message || 'Error al eliminar la empresa');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    title: 'Error',
                    text: error.message || 'Error al eliminar la empresa',
                    icon: 'error',
                    confirmButtonText: 'Aceptar'
                });
            });
        }
    });
}
</script>