<?php
// EJEMPLO: Cómo usar content-structure.php para grupo_apuntes

// 1. CONFIGURAR ESTADÍSTICAS
$stats_config = [
  'title_1' => 'Total Grupos de Apuntes',
  'value_1' => '17',
  'subtitle_1' => 'Registrados en el sistema',
  'icon_1' => 'money-dollar-circle-line',
  'color_1' => 'primary',
  'loading_id_1' => 'total-grupos-apuntes',
  
  'title_2' => 'Grupos Activos',
  'value_2' => '17',
  'subtitle_2' => 'Grupos en uso',
  'icon_2' => 'checkbox-circle-fill',
  'color_2' => 'success',
  'loading_id_2' => 'total-grupos-activos',
  
  'title_3' => 'Fecha Último',
  'value_3' => '22/7/2025',
  'subtitle_3' => 'Último grupo creado',
  'icon_3' => 'calendar-line',
  'color_3' => 'info',
  'loading_id_3' => 'fecha-ultimo-grupo'
];

// 2. CONFIGURAR DATATABLE
$module_title = 'Grupos de Apuntes';
$table_class = 'datatables-grupos-apuntes';
$table_id = 'datatable-grupos-apuntes';
$columns = ['ID', 'NOMBRE GRUPO', 'TIPO GRUPO', 'ACCIONES'];

// 3. CONFIGURAR MODAL CREAR
$modal_id = 'modalCrearGrupoApuntes';
$modal_title = 'Crear Nuevo Grupo de Apuntes';
$form_id = 'formCrearGrupoApuntes';
$submit_text = 'Crear';
$fields = [
  [
    'type' => 'text',
    'id' => 'nombreGrupoApuntes',
    'name' => 'nombre_grupo',
    'label' => 'Nombre del Grupo',
    'placeholder' => 'Ingrese el nombre del grupo',
    'required' => true
  ],
  [
    'type' => 'select',
    'id' => 'tipoGrupoApuntes',
    'name' => 'tipo_grupo',
    'label' => 'Tipo de Grupo',
    'required' => true,
    'options' => [
      ['value' => 'Entrada y salida', 'text' => 'Entrada y salida'],
      ['value' => 'Entrada o Salida', 'text' => 'Entrada o Salida']
    ]
  ]
];

// 4. CONFIGURAR MODAL EDITAR
$edit_modal_id = 'modalEditarGrupoApuntes';
$edit_modal_title = 'Editar Grupo de Apuntes';
$edit_form_id = 'formEditarGrupoApuntes';
$edit_submit_text = 'Actualizar';
$edit_hidden_field = [
  'id' => 'editIdGrupoApuntes',
  'name' => 'id_grupo'
];
$edit_fields = [
  [
    'type' => 'text',
    'id' => 'editNombreGrupoApuntes',
    'name' => 'nombre_grupo',
    'label' => 'Nombre del Grupo',
    'placeholder' => 'Ingrese el nombre del grupo',
    'required' => true
  ],
  [
    'type' => 'select',
    'id' => 'editTipoGrupoApuntes',
    'name' => 'tipo_grupo',
    'label' => 'Tipo de Grupo',
    'required' => true,
    'options' => [
      ['value' => 'Entrada y salida', 'text' => 'Entrada y salida'],
      ['value' => 'Entrada o Salida', 'text' => 'Entrada o Salida']
    ]
  ]
];

// 5. INCLUIR TEMPLATE COMPLETO
include 'parts/universal/templates/content-structure.php';
?>
