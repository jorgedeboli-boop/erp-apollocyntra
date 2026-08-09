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

<!-- Content -->
<div class="container-fluid flex-grow-1 container-p-y">
  <!-- Campo oculto para ID de jerarquía -->
  <input type="hidden" name="id_jerarquia" value="<?php echo $id_jerarquia; ?>">
  
  <div class="row">
    <div class="col-12">
      <div class="card">
        <div class="card-header border-bottom card-header-forms">
          <h4 class="card-title mb-0">Editar Jerarquía: <?php echo htmlspecialchars($jerarquia['nombre_privilegio']); ?></h4>
          <small class="text-muted">Gestionar permisos de acceso para esta jerarquía</small>
          <div class="d-flex flex-column align-items-end">
            <button type="button" class="btn btn-text-primary btn-header-card-right mb-2" onclick="window.location.href='jerarquias.php'">
              <i class="icon-base ri ri-arrow-left-s-line me-2"></i>Jerarquías
            </button>
            <div class="d-flex flex-wrap gap-2 justify-content-end">
              <button type="button" class="btn btn-success btn-xs waves-effect waves-light btn-accion-lote button-actions-datatable" id="filtroActivas" data-filtro-estado="activas">
                <span class="icon-base ri ri-checkbox-circle-fill icon-20px me-1"></span>Activas
              </button>
              <button type="button" class="btn btn-danger btn-xs waves-effect waves-light btn-accion-lote button-actions-datatable" id="filtroNoActivas" data-filtro-estado="no_activas">
                <span class="icon-base ri ri-close-circle-fill icon-20px me-1"></span>No activas
              </button>
              <button type="button" class="btn btn-primary btn-xs waves-effect waves-light btn-accion-lote button-actions-datatable active" id="filtroTodos" data-filtro-estado="todos">
                <span class="icon-base ri ri-list-check icon-20px me-1"></span>Todos
              </button>
            </div>
          </div>
        </div>
        <div class="card-body" style="padding: 9px 18px 9px 5px;">
          <div class="row align-items-center">
            <div class="col-md-12">
              <div class="input-group" style="border: none; outline: none;" >
              <span class="input-group-text">
                  <svg class="aa-SubmitIcon" viewBox="0 0 24 24" width="20" height="20" fill="currentColor">
                    <path d="M16.041 15.856c-0.034 0.026-0.067 0.055-0.099 0.087s-0.060 0.064-0.087 0.099c-1.258 1.213-2.969 1.958-4.855 1.958-1.933 0-3.682-0.782-4.95-2.050s-2.050-3.017-2.050-4.95 0.782-3.682 2.050-4.95 3.017-2.050 4.95-2.050 3.682 0.782 4.95 2.050 2.050 3.017 2.050 4.95c0 1.886-0.745 3.597-1.959 4.856zM21.707 20.293l-3.675-3.675c1.231-1.54 1.968-3.493 1.968-5.618 0-2.485-1.008-4.736-2.636-6.364s-3.879-2.636-6.364-2.636-4.736 1.008-6.364 2.636-2.636 3.879-2.636 6.364 1.008 4.736 2.636 6.364 3.879 2.636 6.364 2.636c2.125 0 4.078-0.737 5.618-1.968l3.675 3.675c0.391 0.391 1.024 0.391 1.414 0s0.391-1.024 0-1.414z"></path>
                  </svg>
                </span>
                <input type="text" class="form-control search-items" id="searchItems" placeholder="Buscar jerarquías por nombre..." autocomplete="off" >
                <button class="btn btn-outline-secondary" type="button" id="clearSearch" style="display: none;">
                  <i class="ri ri-close-line"></i>
                </button>
              </div>
            </div>
           
          </div>
        </div>
      </div>
    </div>
  </div>



  <!-- Permisos en tabla -->
  <div class="row mt-4">
    <div class="col-12">
      <div class="card">
        <div class="card-body px-0 py-4">

          <form id="formPermisosJerarquia" class="row g-3" onsubmit="return false">
            <div class="col-12 m-0">
              <div id="items-container">
                <div class="text-center py-4">
                  <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Cargando...</span>
                  </div>
                  <p class="mt-2">Cargando permisos...</p>
                </div>
              </div>
            </div>
          </form>
          
        </div>
      </div>
    </div>
  </div>

</div>


