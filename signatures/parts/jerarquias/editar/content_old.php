<?php
// Obtener el ID de la jerarquía desde GET
$id_jerarquia = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id_jerarquia <= 0) {
    echo '<div class="alert alert-danger">ID de jerarquía no válido</div>';
    exit();
}

// Obtener información de la jerarquía
$conexion = conectar_bd();
$query_jerarquia = "SELECT nombre_privilegio FROM privilegios_usuarios WHERE id_privilegios = ?";
$stmt = mysqli_prepare($conexion, $query_jerarquia);
mysqli_stmt_bind_param($stmt, "i", $id_jerarquia);
mysqli_stmt_execute($stmt);
$resultado = mysqli_stmt_get_result($stmt);
$jerarquia = mysqli_fetch_assoc($resultado);

if (!$jerarquia) {
    echo '<div class="alert alert-danger">Jerarquía no encontrada</div>';
    exit();
}
?>

<div class="container-fluid flex-grow-1 container-p-y">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header border-bottom">
                    <h4 class="card-title mb-0">Editar Jerarquía: <?php echo htmlspecialchars($jerarquia['nombre_privilegio']); ?></h4>
                    <small class="text-muted">Gestionar permisos de acceso para esta jerarquía</small>
                    <button type="button" class="btn btn-text-primary btn-header-card-right" onclick="window.location.href='jerarquias.php'">
                        <i class="icon-base ri ri-arrow-left-s-line me-2"></i>Jerarquías
                    </button>
                </div>
                <div class="card-body mt-4">
                    <form id="formPermisosJerarquia" method="POST">
                        <input type="hidden" name="id_jerarquia" value="<?php echo $id_jerarquia; ?>">
                        
                        <div class="row mb-4">
                            <div class="col-12">
                                <h5>Permisos de Items del Sistema</h5>
                                <p class="text-muted">Selecciona qué items puede acceder esta jerarquía</p>
                            </div>
                        </div>

                        <div id="items-container">
                            <!-- Aquí se cargarán dinámicamente los items -->
                        </div>

                        <hr class="my-4">

                        <div class="d-flex justify-content-end">
                            <a href="jerarquias.php" class="btn btn-text-primary me-2">
                                <i class="icon-base ri ri-arrow-left-line me-2"></i>
                                Volver a Jerarquías
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>


