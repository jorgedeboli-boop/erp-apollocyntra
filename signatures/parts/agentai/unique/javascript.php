<!-- JAVASCRIPT CUSTOM agentai - unique  -->
<script>
(function () {
  'use strict';

  if (!window.IA_CFG || !window.IA_CFG.tablasOk) {
    return;
  }

  var API_LIST = 'parts/agentai/unique/load_prompts.php';
  var API_SAVE = 'parts/agentai/unique/guardar_prompt.php';
  var API_DEL = 'parts/agentai/unique/eliminar_prompt.php';
  var API_IMPORT = 'parts/agentai/unique/importar_desde_codigo.php';

  var lista = document.getElementById('iaCfgListaPrompts');
  var editorCard = document.getElementById('iaCfgEditorCard');
  var form = document.getElementById('iaCfgFormPrompt');
  var alertEl = document.getElementById('iaCfgAlert');
  var contador = document.getElementById('iaCfgContadorPrompts');
  var tituloGrupo = document.getElementById('iaCfgTituloGrupo');
  var descGrupo = document.getElementById('iaCfgDescGrupo');
  var contenido = document.getElementById('iaCfgContenido');
  var chars = document.getElementById('iaCfgChars');
  var disparadoresWrap = document.getElementById('iaCfgDisparadoresWrap');
  var disparadores = document.getElementById('iaCfgDisparadores');
  var helpContenido = document.getElementById('iaCfgHelpContenido');
  var labelContenido = document.getElementById('iaCfgContenidoLabel');

  function esGrupoFlujos() {
    return (window.IA_CFG.grupoActivo || '') === 'flujos';
  }

  function syncCamposFlujos() {
    var flujos = esGrupoFlujos();
    if (disparadoresWrap) {
      disparadoresWrap.classList.toggle('d-none', !flujos);
    }
    if (labelContenido) {
      labelContenido.textContent = flujos ? 'Respuesta (texto exacto)' : 'Contenido del prompt';
    }
    if (helpContenido) {
      helpContenido.innerHTML = flujos
        ? 'Escribe la <strong>frase exacta</strong> que responderá el chat. Placeholder: <code>{{NOMBRE_USUARIO}}</code>.'
        : 'Placeholders: <code>{{CABECERAS}}</code> (mapa de columnas), <code>{{NOMBRE_USUARIO}}</code> (nombre de la sesión).';
    }
    if (contenido) {
      contenido.rows = flujos ? 8 : 18;
    }
  }

  function showAlert(tipo, msg) {
    if (!alertEl) return;
    alertEl.className = 'alert alert-' + tipo;
    alertEl.textContent = msg;
    alertEl.classList.remove('d-none');
  }

  function hideAlert() {
    if (!alertEl) return;
    alertEl.classList.add('d-none');
  }

  function updateChars() {
    if (!contenido || !chars) return;
    chars.textContent = (contenido.value || '').length + ' caracteres';
  }

  function setGrupoMeta() {
    var codigo = window.IA_CFG.grupoActivo || '';
    var g = (window.IA_CFG.grupos || []).find(function (x) { return x.codigo === codigo; });
    if (tituloGrupo) {
      tituloGrupo.textContent = g ? g.nombre : 'Prompts';
    }
    if (descGrupo) {
      descGrupo.textContent = g ? (g.descripcion || '') : '';
    }
    var inpGrupo = document.getElementById('iaCfgGrupoCodigo');
    if (inpGrupo) inpGrupo.value = codigo;
  }

  function cerrarEditor() {
    if (editorCard) editorCard.classList.add('d-none');
    document.querySelectorAll('.ia-cfg-prompt-item.is-editing').forEach(function (el) {
      el.classList.remove('is-editing');
    });
  }

  function abrirEditor(data) {
    if (!editorCard || !form) return;
    hideAlert();
    syncCamposFlujos();
    document.getElementById('iaCfgIdPrompt').value = data.id_prompt || 0;
    document.getElementById('iaCfgCodigo').value = data.codigo || '';
    document.getElementById('iaCfgCodigo').readOnly = !!(data.id_prompt && Number(data.id_prompt) > 0);
    document.getElementById('iaCfgTitulo').value = data.titulo || '';
    document.getElementById('iaCfgOrden').value = data.orden != null ? data.orden : 10;
    document.getElementById('iaCfgActivo').value = data.activo === 'false' ? 'false' : 'true';
    if (disparadores) {
      disparadores.value = data.disparadores || '';
    }
    contenido.value = data.contenido || '';
    document.getElementById('iaCfgEditorTitulo').textContent = data.id_prompt ? 'Editar prompt' : 'Nuevo prompt';
    updateChars();
    editorCard.classList.remove('d-none');
    editorCard.scrollIntoView({ behavior: 'smooth', block: 'start' });
  }

  function renderPrompts(items) {
    if (!lista) return;
    if (!items || !items.length) {
      lista.innerHTML = '<div class="text-muted py-4 text-center">No hay prompts en este grupo. Crea uno o importa desde código.</div>';
      if (contador) contador.textContent = '0';
      return;
    }
    if (contador) contador.textContent = String(items.length);
    var flujos = esGrupoFlujos();
    var html = '';
    items.forEach(function (p) {
      var activo = p.activo === 'true';
      var preview = (p.contenido || '').replace(/\s+/g, ' ').trim().slice(0, 160);
      var triggers = (p.disparadores || '').replace(/\s+/g, ' ').trim().slice(0, 120);
      html += '<div class="ia-cfg-prompt-item" data-id="' + p.id_prompt + '">'
        + '<div class="d-flex justify-content-between align-items-start gap-2">'
        + '<div class="min-w-0">'
        + '<div class="fw-medium">' + escapeHtml(p.titulo) + '</div>'
        + '<div class="ia-cfg-prompt-meta">código: <code>' + escapeHtml(p.codigo) + '</code> · orden ' + p.orden
        + (activo ? '' : ' · <span class="text-warning">inactivo</span>')
        + '</div>';
      if (flujos && triggers) {
        html += '<div class="small text-primary mt-1">Dispara: ' + escapeHtml(triggers) + (triggers.length >= 120 ? '…' : '') + '</div>';
      }
      html += '<div class="small text-muted mt-1">' + escapeHtml(preview) + (preview.length >= 160 ? '…' : '') + '</div>'
        + '</div>'
        + '<button type="button" class="btn btn-sm btn-outline-primary ia-cfg-btn-edit flex-shrink-0">Editar</button>'
        + '</div></div>';
    });
    lista.innerHTML = html;

    lista.querySelectorAll('.ia-cfg-prompt-item').forEach(function (el) {
      var id = parseInt(el.getAttribute('data-id'), 10);
      var item = items.find(function (x) { return Number(x.id_prompt) === id; });
      var btn = el.querySelector('.ia-cfg-btn-edit');
      if (btn && item) {
        btn.addEventListener('click', function () {
          document.querySelectorAll('.ia-cfg-prompt-item.is-editing').forEach(function (n) {
            n.classList.remove('is-editing');
          });
          el.classList.add('is-editing');
          abrirEditor(item);
        });
      }
    });
  }

  function escapeHtml(str) {
    return String(str == null ? '' : str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  function cargarPrompts() {
    var grupo = window.IA_CFG.grupoActivo;
    if (!grupo) return;
    setGrupoMeta();
    lista.innerHTML = '<div class="text-muted py-4 text-center">Cargando…</div>';
    fetch(API_LIST + '?grupo=' + encodeURIComponent(grupo), {
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
      credentials: 'same-origin'
    })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if (!data || !data.success) {
          lista.innerHTML = '<div class="text-danger py-3">' + escapeHtml((data && data.message) || 'Error al cargar') + '</div>';
          return;
        }
        renderPrompts(data.prompts || []);
      })
      .catch(function () {
        lista.innerHTML = '<div class="text-danger py-3">Error de red al cargar prompts.</div>';
      });
  }

  if (contenido) {
    contenido.addEventListener('input', updateChars);
  }

  var btnNuevo = document.getElementById('iaCfgBtnNuevoPrompt');
  if (btnNuevo) {
    btnNuevo.addEventListener('click', function () {
      abrirEditor({
        id_prompt: 0,
        codigo: '',
        titulo: '',
        contenido: '',
        disparadores: '',
        orden: 10,
        activo: 'true'
      });
      document.getElementById('iaCfgCodigo').readOnly = false;
    });
  }

  var btnCerrar = document.getElementById('iaCfgEditorCerrar');
  if (btnCerrar) {
    btnCerrar.addEventListener('click', cerrarEditor);
  }

  if (form) {
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      hideAlert();
      var fd = new FormData(form);
      var btn = document.getElementById('iaCfgBtnGuardar');
      if (btn) btn.disabled = true;
      fetch(API_SAVE, {
        method: 'POST',
        body: fd,
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        credentials: 'same-origin'
      })
        .then(function (r) { return r.json(); })
        .then(function (data) {
          if (btn) btn.disabled = false;
          if (!data || !data.success) {
            showAlert('danger', (data && data.message) || 'No se pudo guardar');
            return;
          }
          showAlert('success', data.message || 'Guardado');
          if (data.id_prompt) {
            document.getElementById('iaCfgIdPrompt').value = data.id_prompt;
            document.getElementById('iaCfgCodigo').readOnly = true;
          }
          cargarPrompts();
        })
        .catch(function () {
          if (btn) btn.disabled = false;
          showAlert('danger', 'Error de red al guardar');
        });
    });
  }

  var btnEliminar = document.getElementById('iaCfgBtnEliminar');
  if (btnEliminar) {
    btnEliminar.addEventListener('click', function () {
      var id = parseInt(document.getElementById('iaCfgIdPrompt').value, 10) || 0;
      if (id <= 0) {
        cerrarEditor();
        return;
      }
      if (!window.confirm('¿Eliminar este prompt?')) return;
      var fd = new FormData();
      fd.append('id_prompt', String(id));
      fetch(API_DEL, {
        method: 'POST',
        body: fd,
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        credentials: 'same-origin'
      })
        .then(function (r) { return r.json(); })
        .then(function (data) {
          if (!data || !data.success) {
            showAlert('danger', (data && data.message) || 'No se pudo eliminar');
            return;
          }
          cerrarEditor();
          showAlert('success', data.message || 'Eliminado');
          cargarPrompts();
        })
        .catch(function () {
          showAlert('danger', 'Error de red al eliminar');
        });
    });
  }

  var btnImport = document.getElementById('iaCfgBtnImportar');
  if (btnImport) {
    btnImport.addEventListener('click', function () {
      if (!window.confirm('¿Importar/actualizar prompts desde el código actual del chat IA?\nNo borra prompts personalizados con otros códigos.')) {
        return;
      }
      btnImport.disabled = true;
      fetch(API_IMPORT, {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        credentials: 'same-origin'
      })
        .then(function (r) { return r.json(); })
        .then(function (data) {
          btnImport.disabled = false;
          if (!data || !data.success) {
            showAlert('danger', (data && data.message) || 'Importación fallida');
            return;
          }
          showAlert('success', data.message || 'Importado');
          if (data.reload) {
            window.location.reload();
            return;
          }
          cargarPrompts();
        })
        .catch(function () {
          btnImport.disabled = false;
          showAlert('danger', 'Error de red al importar');
        });
    });
  }

  syncCamposFlujos();
  cargarPrompts();
})();
</script>
