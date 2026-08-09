<!-- Template: Tarjetas de Estadísticas -->
<!-- Uso: Incluir en content.php para mostrar estadísticas del módulo -->
<!-- Variables requeridas: $stats (array con title, value, subtitle, icon, color, loading_id) -->

<div class="row g-6 mb-6">
  <?php foreach ($stats as $index => $stat): ?>
    <div class="col-sm-6 col-xl-4">
      <div class="card">
        <div class="card-body">
          <div class="d-flex justify-content-between">
            <div class="me-1">
              <p class="text-heading mb-1"><?php echo $stat['title']; ?></p>
              <div class="d-flex align-items-center">
                <h4 class="mb-1 me-1" id="<?php echo $stat['loading_id']; ?>"><?php echo $stat['value']; ?></h4>
                <div class="stats-loading" style="display: none;">
                  <div class="spinner-border spinner-border-sm text-<?php echo $stat['color']; ?>" role="status"></div>
                </div>
              </div>
              <small class="mb-0"><?php echo $stat['subtitle']; ?></small>
            </div>
            <div class="avatar">
              <div class="avatar-initial bg-label-<?php echo $stat['color']; ?> rounded-circle">
                <div class="icon-base ri ri-<?php echo $stat['icon']; ?> icon-26px"></div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<!-- 
EJEMPLO DE USO:
$stats = [
  [
    'title' => 'Total Tipos de Gasto',
    'value' => '17',
    'subtitle' => 'Registrados en el sistema',
    'icon' => 'money-dollar-circle-line',
    'color' => 'primary',
    'loading_id' => 'total-tipos-gasto'
  ],
  [
    'title' => 'Tipos Activos',
    'value' => '17',
    'subtitle' => 'Tipos en uso',
    'icon' => 'checkbox-circle-fill',
    'color' => 'success',
    'loading_id' => 'total-tipos-activos'
  ],
  [
    'title' => 'Fecha Último',
    'value' => '22/7/2025',
    'subtitle' => 'Último tipo creado',
    'icon' => 'calendar-line',
    'color' => 'info',
    'loading_id' => 'fecha-ultimo-tipo'
  ]
];
include 'parts/universal/templates/stats-cards.php';
-->
