<!-- Template: Modal Crear Registro -->
<!-- Uso: Incluir en content.php para crear nuevos registros -->
<!-- Variables requeridas: $modal_id, $modal_title, $form_id, $fields, $submit_text -->

<div class="modal fade" id="<?php echo $modal_id; ?>" tabindex="-1" aria-labelledby="<?php echo $modal_id; ?>Label" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="<?php echo $modal_id; ?>Label">
          <i class="icon-base ri ri-add-line me-2"></i>
          <?php echo $modal_title; ?>
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="<?php echo $form_id; ?>">
        <div class="modal-body">
          <?php foreach ($fields as $field): ?>
            <div class="mb-3">
              <label for="<?php echo $field['id']; ?>" class="form-label"><?php echo $field['label']; ?></label>
              <?php if ($field['type'] === 'select'): ?>
                <select class="form-select" id="<?php echo $field['id']; ?>" name="<?php echo $field['name']; ?>" <?php echo isset($field['required']) && $field['required'] ? 'required' : ''; ?>>
                  <option value=""><?php echo $field['placeholder'] ?? 'Seleccione una opción'; ?></option>
                  <?php foreach ($field['options'] as $option): ?>
                    <option value="<?php echo $option['value']; ?>"><?php echo $option['text']; ?></option>
                  <?php endforeach; ?>
                </select>
              <?php else: ?>
                <input type="<?php echo $field['type']; ?>" 
                       class="form-control" 
                       id="<?php echo $field['id']; ?>" 
                       name="<?php echo $field['name']; ?>" 
                       placeholder="<?php echo $field['placeholder'] ?? ''; ?>"
                       <?php echo isset($field['required']) && $field['required'] ? 'required' : ''; ?>>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary" id="<?php echo $submit_text; ?>"><?php echo $submit_text; ?></button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- 
EJEMPLO DE USO:
$modal_id = 'modalCrearTipoGasto';
$modal_title = 'Crear Nuevo Tipo de Gasto';
$form_id = 'formCrearTipoGasto';
$submit_text = 'Crear';
$fields = [
  [
    'type' => 'text',
    'id' => 'nombreTipoGasto',
    'name' => 'nombre',
    'label' => 'Nombre del Tipo',
    'placeholder' => 'Ingrese el nombre del tipo',
    'required' => true
  ],
  [
    'type' => 'select',
    'id' => 'categoriaTipoGasto',
    'name' => 'categoria',
    'label' => 'Categoría',
    'required' => true,
    'options' => [
      ['value' => 'gasto', 'text' => 'Gasto'],
      ['value' => 'ingreso', 'text' => 'Ingreso']
    ]
  ]
];
include 'parts/universal/templates/modal-create.php';
-->
