<!-- JAVASCRIPT CUSTOM importar - unique  -->
<script>
document.addEventListener('DOMContentLoaded', function () {
  const form = document.getElementById('formUploadSql');
  const input = document.getElementById('sqlFile');
  const status = document.getElementById('uploadStatus');
  const list = document.getElementById('migrationList');
  const btnRefresh = document.getElementById('btnRefreshMigrations');
  const btnUpload = document.getElementById('btnUploadSql');

  const formImport = document.getElementById('formImportData');
  const importFile = document.getElementById('importFile');
  const destTable = document.getElementById('destTable');
  const importMode = document.getElementById('importMode');
  const columnMap = document.getElementById('columnMap');
  const importStatus = document.getElementById('importStatus');
  const btnImport = document.getElementById('btnImportData');

  const histBody = document.getElementById('importacionesHistBody');
  const btnHistRefresh = document.getElementById('btnRefreshImportacionesHist');
  const btnHistTruncate = document.getElementById('btnTruncateImportacionesHist');
  const histAccion = document.getElementById('importHistAccion');
  const histEstado = document.getElementById('importHistEstado');

  function setStatus(text, kind) {
    status.className = 'small ' + (kind === 'error' ? 'text-danger' : kind === 'ok' ? 'text-success' : 'text-muted');
    status.textContent = text || '';
  }

  function setImportStatus(text, kind) {
    if (!importStatus) return;
    importStatus.className = 'small ' + (kind === 'error' ? 'text-danger' : kind === 'ok' ? 'text-success' : 'text-muted');
    importStatus.textContent = text || '';
  }

  function renderList(items) {
    if (!Array.isArray(items) || items.length === 0) {
      list.innerHTML = '<div class="list-group-item text-muted">No hay ficheros en migration.</div>';
      return;
    }
    list.innerHTML = items.map(name => {
      const safeName = String(name);
      return `
        <div class="list-group-item d-flex align-items-center justify-content-between gap-2">
          <div class="text-truncate"><code>${safeName.replace(/</g, '&lt;').replace(/>/g, '&gt;')}</code></div>
          <div class="d-flex align-items-center gap-2">
            <button type="button" class="btn btn-sm btn-success btn-ejecutar-sql" data-mode="all" data-filename="${safeName.replace(/"/g, '&quot;')}">
              Ejecutar ${safeName.replace(/</g, '&lt;').replace(/>/g, '&gt;')}
            </button>
            <button type="button" class="btn btn-sm btn-outline-success btn-ejecutar-sql" data-mode="no_triggers" data-filename="${safeName.replace(/"/g, '&quot;')}">
              Ejecutar sin triggers
            </button>
            <button type="button" class="btn btn-sm btn-outline-primary btn-ejecutar-sql" data-mode="only_triggers" data-filename="${safeName.replace(/"/g, '&quot;')}">
              Ejecutar triggers
            </button>
            <button type="button" class="btn btn-sm btn-danger btn-borrar-sql" data-filename="${safeName.replace(/"/g, '&quot;')}">
              Borrar
            </button>
          </div>
        </div>
      `;
    }).join('');
  }

  async function cargarMigrations() {
    list.innerHTML = '<div class="list-group-item text-muted">Cargando…</div>';
    try {
      const res = await fetch('parts/importaciones/unique/ajax_list_migrations.php', { method: 'GET' });
      const json = await res.json();
      if (!json.success) throw new Error(json.message || 'Error listando migration');
      renderList(json.files || []);
      // Rellenar select del flujo 2
      if (importFile) {
        const files = json.files || [];
        if (files.length === 0) {
          importFile.innerHTML = '<option value="">No hay ficheros</option>';
        } else {
          importFile.innerHTML = '<option value="">Selecciona…</option>' + files.map(f => `<option value="${String(f).replace(/"/g, '&quot;')}">${String(f)}</option>`).join('');
        }
      }
    } catch (e) {
      list.innerHTML = `<div class="list-group-item text-danger">${String(e.message || e)}</div>`;
    }
  }

  function escapeHtml(s) {
    return String(s ?? '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  async function readJsonOrThrow(res) {
    const text = await res.text();
    const trimmed = String(text || '').trim();
    if (trimmed.startsWith('<!DOCTYPE') || trimmed.startsWith('<html')) {
      throw new Error('El servidor devolvió HTML en vez de JSON. Revisa el error real en la respuesta:\n\n' + trimmed.slice(0, 1000));
    }
    try {
      return JSON.parse(text);
    } catch (e) {
      throw new Error('Respuesta no JSON:\n\n' + trimmed.slice(0, 1000));
    }
  }

  async function cargarHistorialImportaciones() {
    if (!histBody) return;
    histBody.innerHTML = '<tr><td colspan="7" class="text-muted">Cargando…</td></tr>';
    try {
      const accion = String(histAccion?.value || 'execute_sql');
      const estado = String(histEstado?.value || 'success');
      const qs = new URLSearchParams({ accion, estado, limit: '50' });
      const res = await fetch('parts/importaciones/unique/ajax_list_importaciones_jobs.php?' + qs.toString(), { method: 'GET' });
      const json = await readJsonOrThrow(res);
      if (!json.success) throw new Error(json.message || 'Error cargando historial');
      const rows = Array.isArray(json.rows) ? json.rows : [];
      if (rows.length === 0) {
        histBody.innerHTML = '<tr><td colspan="7" class="text-muted">No hay registros para estos filtros.</td></tr>';
        return;
      }
      histBody.innerHTML = rows.map(r => {
        const badgeClass = String(r.estado) === 'success'
          ? 'success'
          : String(r.estado) === 'error'
            ? 'danger'
            : String(r.estado) === 'running'
              ? 'warning'
              : 'secondary';
        return `
          <tr>
            <td>${escapeHtml(r.id)}</td>
            <td><code>${escapeHtml(r.filename)}</code></td>
            <td>${escapeHtml(r.accion)}</td>
            <td><span class="badge bg-label-${badgeClass} rounded-pill">${escapeHtml(r.estado)}</span></td>
            <td>${escapeHtml(r.started_at || '')}</td>
            <td>${escapeHtml(r.finished_at || '')}</td>
            <td class="text-truncate" style="max-width: 360px;">${escapeHtml(r.message || '')}</td>
          </tr>
        `;
      }).join('');
    } catch (e) {
      histBody.innerHTML = `<tr><td colspan="7" class="text-danger">${escapeHtml(String(e.message || e))}</td></tr>`;
    }
  }

  function parseColumnMap(text) {
    const map = {};
    const raw = String(text || '').split(/\r?\n/);
    raw.forEach(line => {
      const t = line.trim();
      if (!t || t.startsWith('#')) return;
      const idx = t.indexOf('=');
      if (idx === -1) return;
      const src = t.slice(0, idx).trim();
      const dst = t.slice(idx + 1).trim();
      if (src && dst) map[src] = dst;
    });
    return map;
  }

  async function cargarTablasDestino() {
    if (!destTable) return;
    destTable.innerHTML = '<option value="">Cargando…</option>';
    try {
      const res = await fetch('parts/importaciones/unique/ajax_list_db_tables.php', { method: 'GET' });
      const json = await readJsonOrThrow(res);
      if (!json.success) throw new Error(json.message || 'Error listando tablas');
      const tables = json.tables || [];
      if (tables.length === 0) {
        destTable.innerHTML = '<option value="">Sin tablas</option>';
        return;
      }
      destTable.innerHTML = '<option value="">Selecciona…</option>' + tables.map(t => `<option value="${String(t).replace(/"/g, '&quot;')}">${String(t)}</option>`).join('');
    } catch (e) {
      destTable.innerHTML = '<option value="">Error</option>';
      setImportStatus(String(e.message || e), 'error');
    }
  }

  async function ejecutarSql(filename, btn) {
    const original = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = `
      <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
      Importando…
    `;
    try {
      let jobId = 0;
      let offset = 0;
      let total = 0;
      let loops = 0;
      const execMode = btn.getAttribute('data-mode') || 'all';

      while (true) {
        loops++;
        const fd = new FormData();
        fd.append('filename', filename);
        fd.append('exec_mode', execMode);
        if (jobId) fd.append('job_id', String(jobId));
        fd.append('offset', String(offset));

        const res = await fetch('parts/importaciones/unique/ajax_execute_sql.php', { method: 'POST', body: fd });
        const json = await readJsonOrThrow(res);
        if (!json.success) throw new Error(json.message || 'Error ejecutando SQL');

        jobId = json.job_id || jobId;
        offset = typeof json.offset === 'number' ? json.offset : offset;
        total = typeof json.total === 'number' ? json.total : total;

        setStatus(json.message || ('Ejecutadas ' + offset + ' / ' + total + '…'), 'ok');

        if (json.done) break;
        if (loops > 5000) throw new Error('Demasiados trozos, abortando.');
      }

      await cargarHistorialImportaciones();
    } catch (e) {
      setStatus(String(e.message || e), 'error');
    } finally {
      btn.disabled = false;
      btn.innerHTML = original;
    }
  }

  async function borrarSql(filename, btn) {
    const result = await Swal.fire({
      icon: 'warning',
      title: 'Borrar fichero',
      text: '¿Seguro que quieres borrar ' + filename + '?',
      showCancelButton: true,
      confirmButtonText: 'Sí, borrar',
      cancelButtonText: 'Cancelar',
      reverseButtons: true
    });

    if (!result.isConfirmed) return;

    const original = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>Borrando…';

    try {
      const fd = new FormData();
      fd.append('filename', filename);
      const res = await fetch('parts/importaciones/unique/ajax_delete_sql.php', { method: 'POST', body: fd });
      const json = await readJsonOrThrow(res);
      if (!json.success) throw new Error(json.message || 'Error borrando SQL');
      setStatus(json.message || 'Fichero borrado.', 'ok');
      await cargarMigrations();
    } catch (e) {
      setStatus(String(e.message || e), 'error');
    } finally {
      btn.disabled = false;
      btn.innerHTML = original;
    }
  }

  btnRefresh?.addEventListener('click', function () {
    cargarMigrations();
  });

  btnHistRefresh?.addEventListener('click', function () {
    cargarHistorialImportaciones();
  });

  btnHistTruncate?.addEventListener('click', async function () {
    const result = await Swal.fire({
      icon: 'warning',
      title: 'Vaciar historial',
      text: 'Se borrarán todos los registros del historial de importaciones. ¿Continuar?',
      showCancelButton: true,
      confirmButtonText: 'Sí, vaciar',
      cancelButtonText: 'Cancelar',
      reverseButtons: true
    });

    if (!result.isConfirmed) return;

    const original = btnHistTruncate.innerHTML;
    btnHistTruncate.disabled = true;
    btnHistTruncate.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>Vaciando…';

    try {
      const res = await fetch('parts/importaciones/unique/ajax_truncate_importaciones_jobs.php', { method: 'POST' });
      const json = await readJsonOrThrow(res);
      if (!json.success) throw new Error(json.message || 'Error vaciando historial');
      await cargarHistorialImportaciones();
      setStatus(json.message || 'Historial vaciado.', 'ok');
    } catch (e) {
      setStatus(String(e.message || e), 'error');
    } finally {
      btnHistTruncate.disabled = false;
      btnHistTruncate.innerHTML = original;
    }
  });

  histAccion?.addEventListener('change', function () {
    cargarHistorialImportaciones();
  });
  histEstado?.addEventListener('change', function () {
    cargarHistorialImportaciones();
  });

  list?.addEventListener('click', function (e) {
    const btn = e.target.closest('.btn-ejecutar-sql');
    if (btn) {
      const filename = btn.getAttribute('data-filename') || '';
      if (!filename) return;
      ejecutarSql(filename, btn);
      return;
    }

    const btnDelete = e.target.closest('.btn-borrar-sql');
    if (btnDelete) {
      const filename = btnDelete.getAttribute('data-filename') || '';
      if (!filename) return;
      borrarSql(filename, btnDelete);
    }
  });

  form?.addEventListener('submit', async function (e) {
    e.preventDefault();
    setStatus('', 'muted');
    const file = input?.files?.[0];
    if (!file) {
      setStatus('Selecciona un fichero .sql, .zip o .gz', 'error');
      return;
    }
    if (!/\.(sql|zip|gz|gzip)$/i.test(file.name)) {
      setStatus('Solo se permiten ficheros .sql, .zip o .gz', 'error');
      return;
    }

    btnUpload.disabled = true;
    btnUpload.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Subiendo…';

    try {
      const fd = new FormData();
      fd.append('sql', file);
      const res = await fetch('parts/importaciones/unique/ajax_upload_sql.php', { method: 'POST', body: fd });
      const json = await readJsonOrThrow(res);
      if (!json.success) throw new Error(json.message || 'Error subiendo SQL');
      setStatus(json.message || 'Subido correctamente.', 'ok');
      await cargarMigrations();
    } catch (err) {
      setStatus(String(err.message || err), 'error');
    } finally {
      btnUpload.disabled = false;
      btnUpload.textContent = 'Subir';
      input.value = '';
    }
  });

  formImport?.addEventListener('submit', async function (e) {
    e.preventDefault();
    setImportStatus('', 'muted');
    const file = importFile?.value || '';
    const table = destTable?.value || '';
    const mode = importMode?.value || 'insert';
    if (!file) {
      setImportStatus('Selecciona un fichero', 'error');
      return;
    }
    if (!table) {
      setImportStatus('Selecciona una tabla destino', 'error');
      return;
    }

    const original = btnImport.innerHTML;
    btnImport.disabled = true;
    btnImport.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Importando…';

    try {
      const fd = new FormData();
      fd.append('filename', file);
      fd.append('dest_table', table);
      fd.append('import_mode', mode);
      fd.append('column_map', JSON.stringify(parseColumnMap(columnMap?.value || '')));
      const res = await fetch('parts/importaciones/unique/ajax_import_data.php', { method: 'POST', body: fd });
      const json = await readJsonOrThrow(res);
      if (!json.success) throw new Error(json.message || 'Error importando datos');
      setImportStatus(json.message || 'Importación completada.', 'ok');
    } catch (err) {
      setImportStatus(String(err.message || err), 'error');
    } finally {
      btnImport.disabled = false;
      btnImport.innerHTML = original;
    }
  });

  cargarMigrations();
  cargarTablasDestino();
  cargarHistorialImportaciones();
});
</script>