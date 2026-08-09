<!doctype html>
<html>
<head>
<meta charset="UTF-8">
<title>Documento sin título</title>
</head>

<body>

<script type="text/javascript">
function actualizarEstadoConexionUsuarioGet() {
    const idUsuario = 121;
    
    fetch('test_json.php?id=' + idUsuario)
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            console.log(data.test);
            /*
            const estadoConexion = data.estado_conexion;
            const badge = document.querySelector('.badge.bg-label-success, .badge.bg-label-secondary');
            
            if (badge) {
                const nuevoEstado = (estadoConexion == 'true') ? 'Conectado' : 'Desconectado';
                const nuevaClase = (estadoConexion == 'true') ? 'bg-label-success' : 'bg-label-secondary';
                const nuevoIcono = (estadoConexion == 'true') ? 'ri-wifi-line' : 'ri-wifi-off-line';
                
                badge.className = `badge ${nuevaClase} me-2 ms-2 rounded-pill`;
                badge.innerHTML = `<i class="icon-base ri ${nuevoIcono} me-1"></i>${nuevoEstado}`;
            }
                */
        }
    })
    .catch(error => {
        console.error('Error al actualizar estado de conexión:', error);
    });
}

// Actualizar estado de conexión al cargar la página
document.addEventListener('DOMContentLoaded', function() {
    actualizarEstadoConexionUsuarioGet();
});
</script>

</body>
</html>