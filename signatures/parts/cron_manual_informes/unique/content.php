<!-- Content -->
<div class="container-fluid flex-grow-1 container-p-y">
  <div class="row">
    <div class="col-12 col-xl-10">
      <h4 class="mb-1">Cron manual de informes</h4>
      <p class="mb-4 text-body-secondary">
        Ejecuta el mismo flujo que los CRON diario / semanal / mensual, pero con una fecha concreta.
        No modifica la carpeta <code>CRON/</code>: reutiliza sus pasos y solo adapta la fecha.
      </p>
    </div>
  </div>

  <div class="row g-4">
    <div class="col-12 col-lg-6">
      <div class="card h-100">
        <div class="card-body">
          <h5 class="card-title mb-3">Lanzar desde aquí</h5>
          <div class="mb-3">
            <label for="cron_manual_fecha" class="form-label">Fecha (YYYY-MM-DD)</label>
            <input type="date" class="form-control" id="cron_manual_fecha" required>
          </div>
          <div class="d-flex flex-wrap gap-2">
            <button type="button" class="btn btn-primary" data-cron-manual="diario">Informe diario</button>
            <button type="button" class="btn btn-warning" data-cron-manual="semanal">Informe semanal</button>
            <button type="button" class="btn btn-info" data-cron-manual="mensual">Informe mensual</button>
          </div>
          <p class="small text-body-secondary mt-3 mb-0">
            Semanal: usa la <strong>fecha de inicio de semana</strong> (<code>fecha_semana_desde</code>).<br>
            Mensual: usa la <strong>fecha de inicio de mes</strong> (<code>fecha_mes_desde</code>).
          </p>
        </div>
      </div>
    </div>

    <div class="col-12 col-lg-6">
      <div class="card h-100">
        <div class="card-body">
          <h5 class="card-title mb-3">Desde SSH / cURL</h5>
          <pre class="bg-label-secondary rounded p-3 mb-2" style="white-space:pre-wrap;font-size:0.85rem;">curl "https://TU-DOMINIO/parts/cron_manual_informes/informe_diario.php?fecha=2026-07-20"

curl "https://TU-DOMINIO/parts/cron_manual_informes/informe_semanal.php?fecha=2026-07-20"

curl "https://TU-DOMINIO/parts/cron_manual_informes/informe_mensual.php?fecha=2026-07-01"</pre>
          <pre class="bg-label-secondary rounded p-3 mb-0" style="white-space:pre-wrap;font-size:0.85rem;">php parts/cron_manual_informes/informe_diario.php --fecha=2026-07-20
php parts/cron_manual_informes/informe_semanal.php --fecha=2026-07-20
php parts/cron_manual_informes/informe_mensual.php --fecha=2026-07-01</pre>
        </div>
      </div>
    </div>
  </div>
</div>
<!-- / Content -->
