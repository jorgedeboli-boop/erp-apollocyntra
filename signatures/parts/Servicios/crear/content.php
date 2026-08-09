<?php
$conexion_cat = conectar_bd();
$categorias_serv = [];
$qc = mysqli_query($conexion_cat, 'SELECT id_categoria, nombre_categoria FROM categorias ORDER BY nombre_categoria ASC');
if ($qc) {
    while ($r = mysqli_fetch_assoc($qc)) {
        $categorias_serv[] = $r;
    }
}
mysqli_close($conexion_cat);
?>
<div class="container-fluid flex-grow-1 container-p-y">
  <div class="row">
    <div class="col-12">
      <div class="card">
        <div class="card-header border-bottom card-header-forms">
          <h5 class="card-title mb-0">Crear servicio</h5>
          <small class="text-muted">Catálogo de servicios (precios, tiempo e IVA)</small>
          <button type="button" id="btn_volver_servicios" class="btn btn-text-primary btn-header-card-right">
            <i class="icon-base ri ri-arrow-left-s-line me-2"></i>Servicios
          </button>
        </div>
        <div class="card-body mt-5">
          <form id="formCrearServicio" method="POST" action="parts/servicios/crear/insertar_servicio.php" class="fv-plugins-bootstrap5 fv-plugins-framework">

            <div class="row mb-4">
              <div class="col-12">
                <h5 class="mb-4">Datos generales</h5>
              </div>
              <div class="col-md-6 mb-4">
                <div class="form-floating form-floating-outline">
                  <?php generarSelectEmpresas(0, 'rel_id_empresa', 'rel_id_empresa', true); ?>
                  <label for="rel_id_empresa" class="form-label">Empresa *</label>
                </div>
              </div>
              <div class="col-md-3 mb-3">
                <div class="form-floating form-floating-outline">
                  <input type="text" class="form-control" id="codigo" name="codigo" placeholder="Código" maxlength="50" />
                  <label for="codigo">Código</label>
                </div>
              </div>
              <div class="col-md-3 mb-3">
                <div class="form-check form-switch mt-3">
                  <input class="form-check-input" type="checkbox" id="activo" name="activo" value="1" checked />
                  <label class="form-check-label" for="activo">Activo</label>
                </div>
              </div>
              <div class="col-md-12 mb-3">
                <div class="form-floating form-floating-outline">
                  <input type="text" class="form-control" id="nombre" name="nombre" placeholder="Nombre" required maxlength="255" />
                  <label for="nombre">Nombre *</label>
                </div>
              </div>
              <div class="col-12 mb-3">
                <div class="form-floating form-floating-outline">
                  <textarea class="form-control" id="descripcion" name="descripcion" placeholder="Descripción" style="min-height: 80px;"></textarea>
                  <label for="descripcion">Descripción</label>
                </div>
              </div>
              <div class="col-md-6 mb-4">
                <div class="form-floating form-floating-outline">
                  <select class="form-select select2" id="id_categoria" name="id_categoria">
                    <option value="0">— Sin categoría —</option>
                    <?php foreach ($categorias_serv as $cat) { ?>
                    <option value="<?php echo (int)$cat['id_categoria']; ?>"><?php echo htmlspecialchars($cat['nombre_categoria']); ?></option>
                    <?php } ?>
                  </select>
                  <label for="id_categoria" class="form-label">Categoría</label>
                </div>
              </div>
            </div>

            <hr class="my-4" />

            <div class="row mb-4">
              <div class="col-12">
                <h5 class="mb-4">Tiempo</h5>
              </div>
              <div class="col-md-4 mb-3">
                <div class="form-floating form-floating-outline">
                  <input type="number" step="0.01" class="form-control" id="duracion_horas" name="duracion_horas" value="0" />
                  <label for="duracion_horas">Duración (horas)</label>
                </div>
              </div>
              <div class="col-md-4 mb-3">
                <div class="form-floating form-floating-outline">
                  <input type="number" step="1" class="form-control" id="duracion_minutos" name="duracion_minutos" value="0" />
                  <label for="duracion_minutos">Duración (minutos)</label>
                </div>
              </div>
              <div class="col-md-4 mb-4">
                <div class="form-floating form-floating-outline">
                  <select class="form-select select2" id="unidad_tiempo" name="unidad_tiempo">
                    <option value="hora" selected>Hora</option>
                    <option value="media_hora">Media hora</option>
                    <option value="dia">Día</option>
                    <option value="sesion">Sesión</option>
                  </select>
                  <label for="unidad_tiempo" class="form-label">Unidad de tiempo</label>
                </div>
              </div>
            </div>

            <hr class="my-4" />

            <div class="row mb-4">
              <div class="col-12">
                <h5 class="mb-4">Precios e IVA</h5>
              </div>
              <div class="col-md-4 mb-3">
                <div class="form-floating form-floating-outline">
                  <select class="form-select select2" id="tipo_facturacion" name="tipo_facturacion">
                    <option value="por_hora" selected>Por hora</option>
                    <option value="precio_fijo">Precio fijo</option>
                    <option value="por_sesion">Por sesión</option>
                  </select>
                  <label for="tipo_facturacion" class="form-label">Tipo de facturación</label>
                </div>
              </div>
              <div class="col-md-4 mb-3">
                <div class="form-floating form-floating-outline">
                  <input type="number" step="0.01" class="form-control" id="precio_hora" name="precio_hora" value="0" />
                  <label for="precio_hora">Precio hora (venta)</label>
                </div>
              </div>
              <div class="col-md-4 mb-3">
                <div class="form-floating form-floating-outline">
                  <input type="number" step="0.01" class="form-control" id="precio_coste_hora" name="precio_coste_hora" value="0" />
                  <label for="precio_coste_hora">Precio coste / hora</label>
                </div>
              </div>
              <div class="col-md-4 mb-3">
                <div class="form-floating form-floating-outline">
                  <input type="number" step="0.01" class="form-control" id="precio_fijo" name="precio_fijo" value="0" />
                  <label for="precio_fijo">Precio fijo</label>
                </div>
              </div>
              <div class="col-md-4 mb-3">
                <div class="form-floating form-floating-outline">
                  <input type="number" step="0.01" class="form-control" id="porcentaje_iva" name="porcentaje_iva" value="21" />
                  <label for="porcentaje_iva">IVA %</label>
                </div>
              </div>
              <div class="col-md-4 mb-3">
                <div class="form-floating form-floating-outline">
                  <input type="number" step="0.01" class="form-control" id="minimo_horas" name="minimo_horas" value="0" />
                  <label for="minimo_horas">Mínimo horas</label>
                </div>
              </div>
              <div class="col-md-4 mb-3">
                <div class="form-floating form-floating-outline">
                  <input type="number" step="0.01" class="form-control" id="incremento_horas" name="incremento_horas" value="0.25" />
                  <label for="incremento_horas">Incremento horas</label>
                </div>
              </div>
            </div>

            <div class="row mb-4">
              <div class="col-12 mb-3">
                <div class="form-floating form-floating-outline">
                  <textarea class="form-control" id="notas" name="notas" placeholder="Notas internas" style="min-height: 90px;"></textarea>
                  <label for="notas">Notas</label>
                </div>
              </div>
            </div>

            <div class="mt-4">
              <button type="submit" class="btn btn-primary me-2">
                <i class="icon-base ri ri-check-line me-2"></i>Guardar servicio
              </button>
              <button type="button" id="btn_cancelar_servicio" class="btn btn-outline-secondary">Cancelar</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>
