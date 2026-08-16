<div class="container-fluid flex-grow-1 container-p-y">
  <div class="card">
    <div class="card-header border-bottom card-header-forms">
      <div>
        <h4 class="card-title mb-0">Corrección cajas</h4>
        <small class="text-muted">Primer conflicto pendiente de los últimos 2 meses (fecha más antigua, excluye hoy). Si no hay errores previos, comprueba la apertura de hoy.</small>
      </div>
    </div>
    <div class="card-datatable table-responsive">
      <table class="datatables-correccion-cajas table border-top">
        <thead>
          <tr>
            <th>ID</th>
            <th>FECHA</th>
            <th>CONFLICTO</th>
            <th>ACCIONES</th>
          </tr>
        </thead>
      </table>
    </div>
  </div>
</div>

<div class="modal fade" id="modalCorreccionCaja" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Corrección de caja</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <p class="mb-2">
          <span id="modal-correccion-fecha"></span>
        </p>
        <p class="text-danger mb-3" id="modal-correccion-conflicto"></p>

        <div class="d-flex justify-content-between align-items-center mb-2 gap-2 flex-wrap">
          <small class="text-muted" id="modal-correccion-ayuda-orden" hidden>
            Arrastre libremente los movimientos del día, incluidos CAJA INICIO y CAJA FINAL.
          </small>
          <button type="button" class="btn btn-sm btn-outline-primary ms-auto" id="btn-nuevo-apunte-correccion">
            <i class="icon-base ri ri-add-line me-1"></i>Nuevo apunte
          </button>
        </div>

        <div class="table-responsive mb-3">
          <table class="table table-sm table-bordered">
            <thead>
              <tr>
                <th class="drag-handle"></th>
                <th>ID</th>
                <th>Fecha</th>
                <th>Hora</th>
                <th>Grupo</th>
                <th>Concepto</th>
                <th class="text-end">Entrada</th>
                <th class="text-end">Salida</th>
                <th class="text-center" style="width: 1%;">Acciones</th>
              </tr>
            </thead>
            <tbody id="modal-correccion-movimientos">
              <tr>
                <td colspan="9" class="text-center text-muted">Cargando movimientos...</td>
              </tr>
            </tbody>
            <tfoot>
              <tr class="table-light fw-semibold">
                <td colspan="6" class="text-end">Totales del día</td>
                <td class="text-end" id="modal-correccion-total-entradas">0,00 €</td>
                <td class="text-end" id="modal-correccion-total-salidas">0,00 €</td>
                <td></td>
              </tr>
              <tr class="table-primary fw-bold">
                <td colspan="7" class="text-end">Balance (entradas − salidas)</td>
                <td class="text-end" id="modal-correccion-total-balance">0,00 €</td>
                <td></td>
              </tr>
            </tfoot>
          </table>
        </div>

        <div class="border rounded p-3 mb-3 border-warning" id="wrap-correccion-editar-cierre" hidden>
          <p class="mb-2 fw-semibold">Corregir importe de caja final</p>
          <p class="text-muted small mb-3" id="correccion-cierre-detalle"></p>
          <label for="input-editar-importe-cierre" class="form-label">Importe cierre (salida)</label>
          <div class="input-group" style="max-width: 280px;">
            <input type="number" class="form-control" id="input-editar-importe-cierre" step="0.01" min="0">
            <span class="input-group-text">€</span>
          </div>
          <small class="text-muted d-block mt-1">
            Calculado: apertura + entradas − salidas = <span id="correccion-cierre-esperado"></span>
          </small>
        </div>

        <div class="border rounded p-3" id="wrap-correccion-insertar">
          <div class="form-check mb-2" id="wrap-check-apertura">
            <input class="form-check-input" type="checkbox" id="check-agregar-apertura">
            <label class="form-check-label" for="check-agregar-apertura">Agregar apertura de caja</label>
          </div>
          <div class="mb-3" id="wrap-input-apertura" hidden>
            <label for="input-importe-apertura" class="form-label">Importe apertura</label>
            <div class="input-group" style="max-width: 280px;">
              <input type="number" class="form-control" id="input-importe-apertura" step="0.01" min="0">
              <span class="input-group-text">€</span>
            </div>
            <small class="text-muted">Sugerido: cierre del día anterior</small>
          </div>

          <div class="form-check mb-2" id="wrap-check-cierre">
            <input class="form-check-input" type="checkbox" id="check-agregar-cierre">
            <label class="form-check-label" for="check-agregar-cierre">Agregar cierre de caja</label>
          </div>
          <div id="wrap-input-cierre" hidden>
            <label for="input-importe-cierre" class="form-label">Importe cierre</label>
            <div class="input-group" style="max-width: 280px;">
              <input type="number" class="form-control" id="input-importe-cierre" step="0.01" min="0">
              <span class="input-group-text">€</span>
            </div>
            <small class="text-muted">Sugerido: balance del día (editable)</small>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" id="btn-cancelar-correccion-caja" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-warning" id="btn-guardar-orden-correccion" hidden>
          <i class="icon-base ri ri-drag-move-2-line me-1"></i>Guardar orden
        </button>
        <button type="button" class="btn btn-warning" id="btn-guardar-cierre-correccion" hidden>
          <i class="icon-base ri ri-save-line me-1"></i>Guardar cierre
        </button>
        <button type="button" class="btn btn-primary" id="btn-aplicar-correccion-caja" hidden>
          <i class="icon-base ri ri-check-line me-1"></i>Aplicar corrección
        </button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="modalNuevoApunteCorreccion" tabindex="-1" aria-labelledby="modalNuevoApunteCorreccionLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalNuevoApunteCorreccionLabel">Nuevo Apunte de Caja</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="formNuevoApunteCorreccion">
          <input type="hidden" id="nuevo-correccion-tabla" name="id_tabla">
          <div class="mb-3">
            <label for="nuevo-correccion-fecha" class="form-label">Fecha</label>
            <input type="date" class="form-control" id="nuevo-correccion-fecha" name="fecha_apunte" required readonly>
          </div>
          <div class="mb-3">
            <label for="nuevo-correccion-grupo" class="form-label">Grupo</label>
            <select class="form-select" id="nuevo-correccion-grupo" name="grupos" required>
              <option value="">Seleccionar grupo...</option>
            </select>
          </div>
          <div class="mb-3">
            <label for="nuevo-correccion-concepto" class="form-label">Concepto</label>
            <textarea class="form-control" id="nuevo-correccion-concepto" name="concepto" rows="3" required></textarea>
          </div>
          <div class="row">
            <div class="col-md-6">
              <div class="mb-3">
                <label for="nuevo-correccion-salida" class="form-label">Salida (€)</label>
                <input type="number" class="form-control" id="nuevo-correccion-salida" name="salida" step="0.01" min="0" value="0">
              </div>
            </div>
            <div class="col-md-6">
              <div class="mb-3">
                <label for="nuevo-correccion-entrada" class="form-label">Entrada (€)</label>
                <input type="number" class="form-control" id="nuevo-correccion-entrada" name="entrada" step="0.01" min="0" value="0">
              </div>
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-primary" id="btnCrearApunteCorreccion">
          <i class="ri-save-line me-1"></i> Crear Apunte
        </button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="modalEditarApunteCorreccion" tabindex="-1" aria-labelledby="modalEditarApunteCorreccionLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalEditarApunteCorreccionLabel">Editar apunte de caja</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="formEditarApunteCorreccion">
          <input type="hidden" id="editar-correccion-id-movimiento" name="id_movimiento">
          <input type="hidden" id="editar-correccion-tabla" name="id_tabla">
          <div class="mb-3">
            <label for="editar-correccion-grupo" class="form-label">Grupo</label>
            <select class="form-select" id="editar-correccion-grupo" name="grupos" required>
              <option value="">Seleccionar grupo...</option>
            </select>
          </div>
          <div class="mb-3">
            <label for="editar-correccion-concepto" class="form-label">Concepto</label>
            <textarea class="form-control" id="editar-correccion-concepto" name="concepto" rows="3" required></textarea>
          </div>
          <div class="row">
            <div class="col-md-6">
              <div class="mb-3">
                <label for="editar-correccion-salida" class="form-label">Salida (€)</label>
                <input type="number" class="form-control" id="editar-correccion-salida" name="salida" step="0.01" min="0" value="0">
              </div>
            </div>
            <div class="col-md-6">
              <div class="mb-3">
                <label for="editar-correccion-entrada" class="form-label">Entrada (€)</label>
                <input type="number" class="form-control" id="editar-correccion-entrada" name="entrada" step="0.01" min="0" value="0">
              </div>
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-primary" id="btnGuardarApunteCorreccion">
          <i class="ri-save-line me-1"></i> Guardar cambios
        </button>
      </div>
    </div>
  </div>
</div>
