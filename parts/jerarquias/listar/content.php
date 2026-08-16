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
            <label for="nombrePrivilegio" class="form-label">Nombre de la Jerarquía *</label>
            <input type="text" class="form-control" id="nombrePrivilegio" name="nombrePrivilegio" 
                   placeholder="Ej: Administrador, Usuario, Editor..." required>
          </div>
          <label class="form-label">Sección del sistema *</label>
          <div class="form-floating form-floating-outline mb-3 mt-2">
            <div class="form-check mb-2">
              <input name="section_activa" class="form-check-input" type="radio" value="central_section" id="central_section_radio" checked required />
              <label class="form-check-label" for="central_section_radio">Central</label>
            </div>
            <div class="form-check mb-2">
              <input name="section_activa" class="form-check-input" type="radio" value="recepcion_lotes_section" id="recepcion_lotes_section_radio" required />
              <label class="form-check-label" for="recepcion_lotes_section_radio">Recepción de lotes</label>
            </div>
            <div class="form-check mb-2">
              <input name="section_activa" class="form-check-input" type="radio" value="auditoria_section" id="auditoria_section_radio" required />
              <label class="form-check-label" for="auditoria_section_radio">Auditoría</label>
            </div>
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

<!-- Modal usuarios por privilegio -->
<div class="modal fade" id="modalUsuariosPrivilegio" tabindex="-1" aria-labelledby="modalUsuariosPrivilegioLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalUsuariosPrivilegioLabel">Usuarios del privilegio</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body p-0" id="modalUsuariosPrivilegioBody">
        <div class="text-center py-5">
          <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Cargando...</span>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
        <a
          href="#"
          id="btnVerFichaUsuarioPrivilegio"
          class="btn btn-primary disabled"
          aria-disabled="true"
          tabindex="-1"
        >Ver ficha de usuario</a>
      </div>
    </div>
  </div>
</div>

