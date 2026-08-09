<!-- Content -->
<div class="container-fluid flex-grow-1 container-p-y">
  <div class="row">
    <div class="col-12">
      <h4 class="mb-1">Gestión de Jerarquías</h4>
      <p class="mb-4">
        Administra los niveles de privilegios y jerarquías de usuarios del sistema.
      </p>
    </div>
  </div>

  <!-- Botón para agregar nueva jerarquía -->
  <div class="row mb-4">
    <div class="col-12">
      <button type="button" class="btn btn-primary" onclick="agregarNuevaJerarquia()">
        <i class="ri ri-add-line me-2"></i>
        Nueva Jerarquía
      </button>
    </div>
  </div>

  <!-- Contenedor para las tarjetas de privilegios -->
  <div class="row g-4" id="privilegios-container">
    <!-- Los privilegios se cargarán dinámicamente aquí -->
    <div class="col-12 text-center">
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Cargando...</span>
      </div>
      <p class="mt-2">Cargando jerarquías...</p>
    </div>
  </div>
</div>

<!-- Modal para agregar/editar jerarquía -->
<div class="modal fade" id="modalJerarquia" tabindex="-1" aria-labelledby="modalJerarquiaLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalJerarquiaLabel">Nueva Jerarquía</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="formJerarquia">
          <div class="mb-3">
            <label for="nombrePrivilegio" class="form-label">Nombre de la Jerarquía</label>
            <input type="text" class="form-control" id="nombrePrivilegio" name="nombrePrivilegio" required>
          </div>
          <div class="mb-3">
            <label for="descripcionPrivilegio" class="form-label">Descripción</label>
            <textarea class="form-control" id="descripcionPrivilegio" name="descripcionPrivilegio" rows="3"></textarea>
          </div>
          <input type="hidden" id="idPrivilegio" name="idPrivilegio">
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-primary" onclick="guardarJerarquia()">Guardar</button>
      </div>
    </div>
  </div>
</div>

<!-- Script para cargar privilegios -->
<script src="parts/jerarquias/unique/load_privilegios.js"></script>
