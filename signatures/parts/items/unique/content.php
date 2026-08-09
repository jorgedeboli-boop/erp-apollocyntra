<!-- Content -->
<div class="container-fluid flex-grow-1 container-p-y">
  <div class="row">
    <div class="col-12">
      <div class="card">
        <div class="card-header border-bottom card-header-forms">
          <h4 class="card-title mb-0">Gestión de Items</h4>
          <small class="text-muted">Administra los items y secciones del sistema de navegación</small>
          <div class="d-flex flex-column align-items-end">
            <button type="button" class="btn btn-text-primary btn-header-card-right mb-2" onclick="window.location.href='items.php'">
              <i class="icon-base ri ri-arrow-left-s-line me-2"></i>Items
            </button>
            <button type="button" class="btn btn-primary" onclick="agregarNuevoItem()">
              <i class="ri ri-add-line me-2"></i>
              Nuevo Item
            </button>
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
                <input type="text" class="form-control search-items" id="searchItems" placeholder="Buscar items por nombre, tipo, URL o icono..." autocomplete="off" >
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



  <!-- Contenedor para las tarjetas de items -->
  <div class="row g-4 mt-5" id="items-container">
    <!-- Los items se cargarán dinámicamente aquí -->
    <div class="col-12 text-center">
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Cargando...</span>
      </div>
      <p class="mt-2">Cargando items...</p>
    </div>
  </div>
</div>

<!-- Modal para agregar/editar item -->
<div class="modal fade" id="modalItem" tabindex="-1" aria-labelledby="modalItemLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalItemLabel">Nuevo Item</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="formItem" enctype="multipart/form-data">
          <div class="mb-3">
            <label for="itemName" class="form-label">Nombre del Item (uso interno) *</label>
            <input type="text" class="form-control" id="itemName" name="itemName" 
                   placeholder="Ej: Usuarios, Productos, Reportes..." required>
          </div>

          <div class="mb-3">
            <label for="nombre_singular" class="form-label">Nombre Singular (uso interno)</label>
            <input type="text" class="form-control" id="nombre_singular" name="nombre_singular" 
                   placeholder="Ej: Usuario, Producto, Reporte...">
            <small class="form-text text-muted">Nombre en singular para uso interno (opcional)</small>
          </div>

          <div class="mb-3">
            <label for="itemnameText" class="form-label">Nombre del Item a mostrar en el menú *</label>
            <input type="text" class="form-control" id="itemnameText" name="itemnameText" 
                   placeholder="Ej: Usuarios, Productos, Reportes..." required>
          </div>
          
          <div class="mb-3">
            <label for="nombre_singular_text" class="form-label">Nombre Singular texto</label>
            <input type="text" class="form-control" id="nombre_singular_text" name="nombre_singular_text" 
                   placeholder="Ej: Usuario, Producto, Reporte...">
            <small class="form-text text-muted">Nombre en singular para uso texto</small>
          </div>
            
            <div class="mb-3">
                <label for="tabla_mysql_name" class="form-label">Nombre de la tabla mysql</label>
                <input type="text" class="form-control" id="tabla_mysql_name" name="tabla_mysql_name" 
                       placeholder="Ej: tabla_textos...">
                <small class="form-text text-muted">Nombre de la tabla mysql</small>
              </div>
                      
          <div class="mb-3">
            <label for="typ_item" class="form-label">Tipo de Item *</label>
            <select class="form-select" id="typ_item" name="typ_item" required>
              <option value="unique">Unique</option>
              <option value="main">Main</option>
              <option value="listar">Listar</option>
              <option value="editar">Editar</option>
              <option value="crear">Crear</option>
              <option value="delete">Delete (permiso borrar)</option>
              <option value="edit">Edit (permiso editar)</option>
              <option value="menu">Menu</option>
              <option value="crud">CRUD</option>
              <option value="acces_special">Special</option>
              <option value="blank_page">Pagina en blanco</option>
            </select>
            <small class="form-text text-muted">CRUD: Crea automáticamente estructura de Crear, Leer, Actualizar, Eliminar</small>
          </div>
          
          <div class="mb-3">
            <label for="fhater_item" class="form-label">Item Padre</label>
            <select class="form-select" id="fhater_item" name="fhater_item">
              <option value="0">Cargando items padre...</option>
            </select>
          </div>
          
          <div class="mb-3">
            <label for="fhater_menu" class="form-label">Item Padre de Menú</label>
            <select class="form-select" id="fhater_menu" name="fhater_menu">
              <option value="0">Sin item padre de menú</option>
            </select>
            <small class="form-text text-muted">Selecciona un item de tipo "menu" para crear submenús</small>
          </div>
          
          <div class="mb-3">
            <label for="url_item" class="form-label">URL del Item</label>
            <input type="text" class="form-control" id="url_item" name="url_item" 
                   placeholder="Ej: usuarios.php, productos/listar.php...">
          </div>
          
          <div class="mb-3">
            <label for="icon_menu" class="form-label">Icono del Menú</label>
            <input type="text" class="form-control" id="icon_menu" name="icon_menu" 
                   placeholder="Ej: ri-user-line, ri-shopping-cart-line...">
          </div>
          
          <div class="mb-3" id="sql_upload_section" style="display: none;">
            <label for="sql_file" class="form-label">Archivo SQL de la Tabla</label>
            <input type="file" class="form-control" id="sql_file" name="sql_file" 
                   accept=".sql" onchange="mostrarNombreArchivo()">
            <small class="form-text text-muted">Sube el archivo SQL con la estructura de la tabla MySQL (solo para tipo CRUD)</small>
            <div id="nombre_archivo" class="mt-2" style="display: none;">
              <span class="badge bg-info">Archivo seleccionado: <span id="nombre_archivo_texto"></span></span>
            </div>
          </div>
          
          <div class="mb-3">
            <label class="form-label">Estado del Item *</label>
            <div class="form-check">
              <input class="form-check-input" type="radio" name="state_item" id="state_item_true" value="true" checked>
              <label class="form-check-label" for="state_item_true">
                Activo
              </label>
            </div>
            <div class="form-check">
              <input class="form-check-input" type="radio" name="state_item" id="state_item_false" value="false">
              <label class="form-check-label" for="state_item_false">
                Inactivo
              </label>
            </div>
          </div>
          
          <div class="mb-3">
            <label class="form-label">Mostrar en Menú *</label>
            <div class="form-check">
              <input class="form-check-input" type="radio" name="in_menu" id="in_menu_true" value="true" checked>
              <label class="form-check-label" for="in_menu_true">
                Sí
              </label>
            </div>
            <div class="form-check">
              <input class="form-check-input" type="radio" name="in_menu" id="in_menu_false" value="false">
              <label class="form-check-label" for="in_menu_false">
                No
              </label>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label">Sección del sistema *</label>
            <div class="form-check mb-2">
              <input class="form-check-input" type="radio" name="section_activa" value="central_section" id="central_section_radio" checked required>
              <label class="form-check-label" for="central_section_radio">Central</label>
            </div>
            <div class="form-check mb-2">
              <input class="form-check-input" type="radio" name="section_activa" value="sucursal_section" id="sucursal_section_radio" required>
              <label class="form-check-label" for="sucursal_section_radio">Sucursal</label>
            </div>
            <div class="form-check mb-2">
              <input class="form-check-input" type="radio" name="section_activa" value="recepcion_lotes_section" id="recepcion_lotes_section_radio" required>
              <label class="form-check-label" for="recepcion_lotes_section_radio">Recepción de lotes</label>
            </div>
            <div class="form-check mb-2">
              <input class="form-check-input" type="radio" name="section_activa" value="auditoria_section" id="auditoria_section_radio" required>
              <label class="form-check-label" for="auditoria_section_radio">Auditoría</label>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label">Item tipo Root</label>
            <div class="form-check">
              <input class="form-check-input" type="radio" name="item_root" id="item_root_true" value="true">
              <label class="form-check-label" for="item_root_true">
                Sí
              </label>
            </div>
            <div class="form-check">
              <input class="form-check-input" type="radio" name="item_root" id="item_root_false" value="false" checked>
              <label class="form-check-label" for="item_root_false">
                No
              </label>
            </div>
          </div>
          
          <input type="hidden" id="idItem" name="id">
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-primary" onclick="guardarItem()">Guardar</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal para editar item -->
<div class="modal fade" id="editarmodalitem" tabindex="-1" aria-labelledby="editarmodalitemLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="editarmodalitemLabel">Editar Item</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="formEditarItem" enctype="multipart/form-data">
          <div class="mb-3">
            <label for="edit_itemnameText" class="form-label">Nombre del Item a mostrar en el menú *</label>
            <input type="text" class="form-control" id="edit_itemnameText" name="itemnameText" 
                   placeholder="Ej: Usuarios, Productos, Reportes..." required>
          </div>
          
          <div class="mb-3">
            <label for="edit_fhater_item" class="form-label">Item Padre</label>
            <select class="form-select" id="edit_fhater_item" name="fhater_item">
              <option value="0">Cargando items padre...</option>
            </select>
          </div>
            
        <div class="mb-3">
                <label for="edit_tabla_mysql_name" class="form-label">Nombre de la tabla mysql</label>
                <input type="text" class="form-control" id="edit_tabla_mysql_name" name="edit_tabla_mysql_name" 
                       placeholder="Ej: tabla_textos...">
                <small class="form-text text-muted">Nombre de la tabla mysql</small>
              </div>
          
          <div class="mb-3">
            <label for="edit_fhater_menu" class="form-label">Item Padre de Menú</label>
            <select class="form-select" id="edit_fhater_menu" name="fhater_menu">
              <option value="0">Sin item padre de menú</option>
            </select>
            <small class="form-text text-muted">Selecciona un item de tipo "menu" para crear submenús</small>
          </div>
          
          <div class="mb-3">
            <label for="edit_icon_menu" class="form-label">Icono del Menú</label>
            <input type="text" class="form-control" id="edit_icon_menu" name="icon_menu" 
                   placeholder="Ej: ri-user-line, ri-shopping-cart-line...">
          </div>
          
          <div class="mb-3" id="edit_sql_upload_section" style="display: none;">
            <label for="edit_sql_file" class="form-label">Archivo SQL de la Tabla</label>
            <input type="file" class="form-control" id="edit_sql_file" name="sql_file" 
                   accept=".sql" onchange="mostrarNombreArchivoEdit()">
            <small class="form-text text-muted">Sube el archivo SQL con la estructura de la tabla MySQL (solo para tipo CRUD)</small>
            <div id="edit_nombre_archivo" class="mt-2" style="display: none;">
              <span class="badge bg-info">Archivo seleccionado: <span id="edit_nombre_archivo_texto"></span></span>
            </div>
          </div>
          
          <div class="mb-3">
            <label class="form-label">Estado del Item *</label>
            <div class="form-check">
              <input class="form-check-input" type="radio" name="edit_state_item" id="edit_state_item_true" value="true" checked>
              <label class="form-check-label" for="edit_state_item_true">
                Activo
              </label>
            </div>
            <div class="form-check">
              <input class="form-check-input" type="radio" name="edit_state_item" id="edit_state_item_false" value="false">
              <label class="form-check-label" for="edit_state_item_false">
                Inactivo
              </label>
            </div>
          </div>
          
          <div class="mb-3">
            <label class="form-label">Mostrar en Menú *</label>
            <div class="form-check">
              <input class="form-check-input" type="radio" name="edit_in_menu" id="edit_in_menu_true" value="true" checked>
              <label class="form-check-label" for="edit_in_menu_true">
                Sí
              </label>
            </div>
            <div class="form-check">
              <input class="form-check-input" type="radio" name="edit_in_menu" id="edit_in_menu_false" value="false">
              <label class="form-check-label" for="edit_in_menu_false">
                No
              </label>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label">Sección del sistema *</label>
            <div class="form-check mb-2">
              <input class="form-check-input" type="radio" name="section_activa" value="central_section" id="edit_central_section_radio" checked required>
              <label class="form-check-label" for="edit_central_section_radio">Central</label>
            </div>
            <div class="form-check mb-2">
              <input class="form-check-input" type="radio" name="section_activa" value="sucursal_section" id="edit_sucursal_section_radio" required>
              <label class="form-check-label" for="edit_sucursal_section_radio">Sucursal</label>
            </div>
            <div class="form-check mb-2">
              <input class="form-check-input" type="radio" name="section_activa" value="recepcion_lotes_section" id="edit_recepcion_lotes_section_radio" required>
              <label class="form-check-label" for="edit_recepcion_lotes_section_radio">Recepción de lotes</label>
            </div>
            <div class="form-check mb-2">
              <input class="form-check-input" type="radio" name="section_activa" value="auditoria_section" id="edit_auditoria_section_radio" required>
              <label class="form-check-label" for="edit_auditoria_section_radio">Auditoría</label>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label">Item tipo Root</label>
            <div class="form-check">
              <input class="form-check-input" type="radio" name="item_root" id="edit_item_root_true" value="true">
              <label class="form-check-label" for="edit_item_root_true">
                Sí
              </label>
            </div>
            <div class="form-check">
              <input class="form-check-input" type="radio" name="item_root" id="edit_item_root_false" value="false" checked>
              <label class="form-check-label" for="edit_item_root_false">
                No
              </label>
            </div>
          </div>
          
          <input type="hidden" id="edit_typ_item" name="typ_item">
          <input type="hidden" id="edit_idItem" name="id">
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-primary" onclick="guardarItemEditado()">Guardar Cambios</button>
      </div>
    </div>
  </div>
</div>
