<!-- Template: Estructura Completa de Content.php -->
<!-- Uso: Incluir en content.php para crear módulos completos -->
<!-- Variables requeridas: Ver ejemplos de uso al final -->

<div class="container-fluid flex-grow-1 container-p-y">
  <?php
  // 1. CONFIGURAR ESTADÍSTICAS
  $stats = [
    [
      'title' => $stats_config['title_1'] ?? 'Total Registros',
      'value' => $stats_config['value_1'] ?? '0',
      'subtitle' => $stats_config['subtitle_1'] ?? 'Registros en el sistema',
      'icon' => $stats_config['icon_1'] ?? 'database-line',
      'color' => $stats_config['color_1'] ?? 'primary',
      'loading_id' => $stats_config['loading_id_1'] ?? 'total-registros'
    ],
    [
      'title' => $stats_config['title_2'] ?? 'Registros Activos',
      'value' => $stats_config['value_2'] ?? '0',
      'subtitle' => $stats_config['subtitle_2'] ?? 'Registros en uso',
      'icon' => $stats_config['icon_2'] ?? 'checkbox-circle-fill',
      'color' => $stats_config['color_2'] ?? 'success',
      'loading_id' => $stats_config['loading_id_2'] ?? 'total-activos'
    ],
    [
      'title' => $stats_config['title_3'] ?? 'Fecha Último',
      'value' => $stats_config['value_3'] ?? 'N/A',
      'subtitle' => $stats_config['subtitle_3'] ?? 'Último registro creado',
      'icon' => $stats_config['icon_3'] ?? 'calendar-line',
      'color' => $stats_config['color_3'] ?? 'info',
      'loading_id' => $stats_config['loading_id_3'] ?? 'fecha-ultimo'
    ]
  ];
  
  // 2. INCLUIR TARJETAS DE ESTADÍSTICAS
  include 'parts/universal/templates/stats-cards.php';
  
  // 3. INCLUIR DATATABLE
  include 'parts/universal/templates/datatable-basic.php';
  
  // 4. INCLUIR MODAL CREAR
  include 'parts/universal/templates/modal-create.php';
  
  // 5. INCLUIR MODAL EDITAR
  include 'parts/universal/templates/modal-edit.php';
  ?>
</div>

<!-- 
EJEMPLO DE USO COMPLETO:

<?php
// Configuración de estadísticas
$stats_config = [
  'title_1' => 'Total Tipos de Gasto',
  'value_1' => '17',
  'subtitle_1' => 'Registrados en el sistema',
  'icon_1' => 'money-dollar-circle-line',
  'color_1' => 'primary',
  'loading_id_1' => 'total-tipos-gasto',
  
  'title_2' => 'Tipos Activos',
  'value_2' => '17',
  'subtitle_2' => 'Tipos en uso',
  'icon_2' => 'checkbox-circle-fill',
  'color_2' => 'success',
  'loading_id_2' => 'total-tipos-activos',
  
  'title_3' => 'Fecha Último',
  'value_3' => '22/7/2025',
  'subtitle_3' => 'Último tipo creado',
  'icon_3' => 'calendar-line',
  'color_3' => 'info',
  'loading_id_3' => 'fecha-ultimo-tipo'
];

// Configuración del DataTable
$module_title = 'Tipos de Gasto';
$table_class = 'datatables-tipos-gastos';
$table_id = 'datatable-tipos-gastos';
$columns = ['ID', 'NOMBRE', 'DESCRIPCION', 'ACCIONES'];

// Configuración del modal crear
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
    'type' => 'text',
    'id' => 'descripcionTipoGasto',
    'name' => 'descripcion',
    'label' => 'Descripción',
    'placeholder' => 'Ingrese la descripción',
    'required' => false
  ]
];

// Configuración del modal editar
$edit_modal_id = 'modalEditarTipoGasto';
$edit_modal_title = 'Editar Tipo de Gasto';
$edit_form_id = 'formEditarTipoGasto';
$edit_submit_text = 'Actualizar';
$edit_hidden_field = [
  'id' => 'editIdTipoGasto',
  'name' => 'id'
];
$edit_fields = [
  [
    'type' => 'text',
    'id' => 'editNombreTipoGasto',
    'name' => 'nombre',
    'label' => 'Nombre del Tipo',
    'placeholder' => 'Ingrese el nombre del tipo',
    'required' => true
  ],
  [
    'type' => 'text',
    'id' => 'editDescripcionTipoGasto',
    'name' => 'descripcion',
    'label' => 'Descripción',
    'placeholder' => 'Ingrese la descripción',
    'required' => false
  ]
];

// Incluir el template completo
include 'parts/universal/templates/content-structure.php';
?>
-->
