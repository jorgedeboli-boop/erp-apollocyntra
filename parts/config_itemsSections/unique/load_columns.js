(function () {
  'use strict';

  const els = {
    loading: document.getElementById('columnas-loading'),
    error: document.getElementById('columnas-error'),
    tableWrap: document.getElementById('columnas-table-wrap'),
    tbody: document.getElementById('columnas-tbody'),
    btnNueva: document.getElementById('btnNuevaColumna'),
    modalEl: document.getElementById('modalNuevaColumna'),
    form: document.getElementById('formNuevaColumna'),
    selectDespues: document.getElementById('despues_columna'),
    btnGuardar: document.getElementById('btnGuardarColumna'),
  };

  let modal = null;
  let columnasCache = [];

  function escapeHtml(text) {
    if (text === null || text === undefined) return '';
    return String(text)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  function badgeClave(key) {
    if (!key) return '<span class="text-muted">—</span>';
    const cls = key === 'PRI' ? 'badge-key-pri' : (key === 'UNI' ? 'badge-key-uni' : 'bg-label-secondary');
    return '<span class="badge rounded-pill ' + cls + '">' + escapeHtml(key) + '</span>';
  }

  function mostrarError(mensaje) {
    els.loading.classList.add('d-none');
    els.tableWrap.classList.add('d-none');
    els.error.textContent = mensaje;
    els.error.classList.remove('d-none');
  }

  function rellenarSelectDespues() {
    if (!els.selectDespues) return;
    const valorActual = els.selectDespues.value;
    els.selectDespues.innerHTML = '<option value="">Al final de la tabla</option>';
    columnasCache.forEach(function (col) {
      const opt = document.createElement('option');
      opt.value = col.field;
      opt.textContent = col.field;
      els.selectDespues.appendChild(opt);
    });
    if (valorActual) {
      els.selectDespues.value = valorActual;
    }
  }

  function pintarTabla(columnas) {
    columnasCache = columnas;
    els.tbody.innerHTML = '';

    columnas.forEach(function (col, index) {
      const tr = document.createElement('tr');
      tr.innerHTML =
        '<td>' + (index + 1) + '</td>' +
        '<td><strong>' + escapeHtml(col.field) + '</strong></td>' +
        '<td><code>' + escapeHtml(col.type) + '</code></td>' +
        '<td>' + escapeHtml(col.null) + '</td>' +
        '<td>' + badgeClave(col.key) + '</td>' +
        '<td>' + (col.default !== null && col.default !== '' ? '<code>' + escapeHtml(col.default) + '</code>' : '<span class="text-muted">NULL</span>') + '</td>' +
        '<td>' + (col.extra ? escapeHtml(col.extra) : '<span class="text-muted">—</span>') + '</td>';
      els.tbody.appendChild(tr);
    });

    els.loading.classList.add('d-none');
    els.error.classList.add('d-none');
    els.tableWrap.classList.remove('d-none');
    rellenarSelectDespues();
  }

  async function parseJsonResponse(res) {
    const text = await res.text();
    try {
      return JSON.parse(text);
    } catch (err) {
      throw new Error(
        res.status === 403
          ? 'Acceso denegado (403). Solo usuarios root pueden modificar la estructura.'
          : 'Respuesta no válida del servidor'
      );
    }
  }

  async function cargarColumnas() {
    els.loading.classList.remove('d-none');
    els.error.classList.add('d-none');
    els.tableWrap.classList.add('d-none');

    try {
      const res = await fetch('parts/config_itemsSections/unique/load_columns.php', {
        credentials: 'same-origin',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
      });
      const data = await parseJsonResponse(res);
      if (!data.success) {
        throw new Error(data.error || 'No se pudieron cargar las columnas');
      }
      pintarTabla(data.columnas || []);
    } catch (err) {
      mostrarError(err.message || 'Error al cargar columnas');
    }
  }

  function abrirModal() {
    if (!modal) {
      modal = new bootstrap.Modal(els.modalEl);
    }
    els.form.reset();
    rellenarSelectDespues();
    modal.show();
  }

  async function guardarColumna(event) {
    event.preventDefault();

    const formData = new FormData(els.form);
    const textoOriginal = els.btnGuardar.innerHTML;
    els.btnGuardar.disabled = true;
    els.btnGuardar.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Guardando...';

    try {
      const res = await fetch('parts/config_itemsSections/unique/add_column.php', {
        method: 'POST',
        credentials: 'same-origin',
        body: formData,
      });
      const data = await parseJsonResponse(res);

      if (!data.success) {
        throw new Error(data.error || 'No se pudo añadir la columna');
      }

      if (modal) {
        modal.hide();
      }

      await Swal.fire({
        title: 'Columna añadida',
        text: data.message,
        icon: 'success',
        confirmButtonText: 'Aceptar',
      });

      await cargarColumnas();
    } catch (err) {
      Swal.fire({
        title: 'Error',
        text: err.message || 'Error al añadir la columna',
        icon: 'error',
        confirmButtonText: 'Aceptar',
      });
    } finally {
      els.btnGuardar.disabled = false;
      els.btnGuardar.innerHTML = textoOriginal;
    }
  }

  document.addEventListener('DOMContentLoaded', function () {
    if (els.btnNueva) {
      els.btnNueva.addEventListener('click', abrirModal);
    }
    if (els.form) {
      els.form.addEventListener('submit', guardarColumna);
    }
    cargarColumnas();
  });
})();
