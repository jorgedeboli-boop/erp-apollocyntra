'use strict';

(function () {
  const API_URL = 'parts/universal/elements_dom_levels_api.php';
  const SELECTABLE_TAGS = [
    'DIV', 'BUTTON', 'SPAN', 'A', 'LI', 'UL',
    'FORM', 'INPUT', 'SELECT', 'TEXTAREA', 'LABEL', 'FIELDSET', 'LEGEND',
  ];
  const HIGHLIGHT_CLASS = 'root-dom-select-highlight';
  const MODE_CLASS = 'root-dom-select-mode';

  let active = false;
  let highlightedEl = null;
  let contentWrapper = null;
  let toggleBtn = null;
  let modalInstance = null;

  function injectStyles() {
    if (document.getElementById('root-dom-select-styles')) {
      return;
    }
    const style = document.createElement('style');
    style.id = 'root-dom-select-styles';
    style.textContent = [
      '.' + MODE_CLASS + ' { cursor: crosshair !important; }',
      '.' + MODE_CLASS + ' * { cursor: crosshair !important; }',
      '.' + HIGHLIGHT_CLASS + ' {',
      '  outline: 2px solid #ff4c51 !important;',
      '  outline-offset: 2px !important;',
      '  box-shadow: 0 0 0 4px rgba(255, 76, 81, 0.25) !important;',
      '}',
      '#rootDomSelectModal .modal-dialog { max-width: 380px; }',
      '#btnRootSelectDomElements, .root-dom-select-fab {',
      '  position: fixed;',
      '  bottom: 20px;',
      '  left: 20px;',
      '  z-index: 1090;',
      '  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);',
      '}',
      '#rootDomSelectOverlay {',
      '  position: fixed;',
      '  inset: 0;',
      '  z-index: 1085;',
      '  pointer-events: none;',
      '  display: none;',
      '}',
      '#rootDomSelectOverlay.is-active { display: block; }',
    ].join('\n');
    document.head.appendChild(style);
  }

  function injectModal() {
    if (document.getElementById('rootDomSelectModal')) {
      return;
    }

    const modalHtml = [
      '<div class="modal fade" id="rootDomSelectModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">',
      '  <div class="modal-dialog modal-sm modal-dialog-centered">',
      '    <div class="modal-content">',
      '      <div class="modal-header py-3">',
      '        <h6 class="modal-title mb-0">Elemento DOM</h6>',
      '        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>',
      '      </div>',
      '      <div class="modal-body py-3" id="rootDomSelectModalBody">',
      '        <p class="mb-2 small text-body-secondary">Cargando…</p>',
      '      </div>',
      '    </div>',
      '  </div>',
      '</div>',
    ].join('');

    document.body.insertAdjacentHTML('beforeend', modalHtml);

    const overlay = document.createElement('div');
    overlay.id = 'rootDomSelectOverlay';
    document.body.appendChild(overlay);

    const modalEl = document.getElementById('rootDomSelectModal');
    if (modalEl && typeof bootstrap !== 'undefined') {
      modalInstance = new bootstrap.Modal(modalEl);
    }
  }

  function isSelectableElement(el) {
    if (!el || el.nodeType !== 1) {
      return false;
    }
    if (!contentWrapper || !contentWrapper.contains(el)) {
      return false;
    }
    if (!el.id || !el.id.trim()) {
      return false;
    }
    if (!SELECTABLE_TAGS.includes(el.tagName)) {
      return false;
    }
    if (el.closest('#rootDomSelectModal, #layout-navbar, #btnRootSelectDomElements')) {
      return false;
    }
    return true;
  }

  function findSelectableFromTarget(target) {
    let node = target;
    while (node && node !== contentWrapper) {
      if (isSelectableElement(node)) {
        return node;
      }
      node = node.parentElement;
    }
    return null;
  }

  function clearHighlight() {
    if (highlightedEl) {
      highlightedEl.classList.remove(HIGHLIGHT_CLASS);
      highlightedEl = null;
    }
  }

  function setHighlight(el) {
    if (highlightedEl === el) {
      return;
    }
    clearHighlight();
    if (el) {
      highlightedEl = el;
      highlightedEl.classList.add(HIGHLIGHT_CLASS);
    }
  }

  function getRelIdTypeItem() {
    if (typeof window.rootDomSelectRelIdTypeItem === 'number' && window.rootDomSelectRelIdTypeItem > 0) {
      return window.rootDomSelectRelIdTypeItem;
    }
    const ctx = window.__menuPageContext;
    if (ctx && ctx.currentIdTypeItem > 0) {
      return ctx.currentIdTypeItem;
    }
    return 0;
  }

  function apiRequest(action, payload) {
    const relIdTypeItem = getRelIdTypeItem();
    if (!relIdTypeItem) {
      return Promise.reject(new Error('No se pudo obtener el id_type_Item de la página actual'));
    }

    const formData = new FormData();
    formData.append('action', action);
    formData.append('rel_id_type_Item', String(relIdTypeItem));
    Object.keys(payload || {}).forEach(function (key) {
      formData.append(key, payload[key]);
    });

    return fetch(API_URL, {
      method: 'POST',
      body: formData,
      credentials: 'same-origin',
    }).then(function (response) {
      return response.json().then(function (data) {
        if (!response.ok && !data) {
          throw new Error('Error en la petición');
        }
        if (!data.success) {
          throw new Error(data.message || 'Operación no completada');
        }
        return data;
      });
    });
  }

  function renderModalLoading(domId, tagName) {
    const body = document.getElementById('rootDomSelectModalBody');
    if (!body) {
      return;
    }
    body.innerHTML = [
      '<p class="mb-1"><strong>#' + domId + '</strong></p>',
      '<p class="mb-0 small text-body-secondary">&lt;' + tagName.toLowerCase() + '&gt;</p>',
      '<p class="mt-3 mb-0 small">Consultando registro…</p>',
    ].join('');
  }

  function escapeHtml(text) {
    return String(text)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
  }

  function suggestNameFromElement(el) {
    if (!el) {
      return '';
    }
    const text = (el.textContent || '').replace(/\s+/g, ' ').trim();
    if (text !== '') {
      return text.length > 124 ? text.slice(0, 124) : text;
    }
    return '';
  }

  function renderModalContent(domId, tagName, data, suggestedName) {
    const body = document.getElementById('rootDomSelectModalBody');
    if (!body) {
      return;
    }

    const exists = data.exists === true;
    const stateActive = data.state_element_rel === 'true';
    const idElement = data.id_element || 0;
    const relIdTypeItem = data.rel_id_type_Item || getRelIdTypeItem();
    const nameText = (data.name_text_element || suggestedName || '').trim();

    let html = [
      '<p class="mb-1"><strong>#' + escapeHtml(domId) + '</strong></p>',
      '<p class="mb-1 small text-body-secondary">&lt;' + escapeHtml(tagName.toLowerCase()) + '&gt;</p>',
      '<p class="mb-3 small text-body-secondary">id_type_Item: <code>' + escapeHtml(String(relIdTypeItem)) + '</code></p>',
    ];

    if (exists) {
      html.push(
        '<p class="mb-3 small"><span class="fw-medium">Nombre:</span> ' + escapeHtml(nameText || '—') + '</p>',
        '<div class="d-flex align-items-center justify-content-between gap-3">',
        '  <div>',
        '    <div class="small fw-medium">Estado en elementsDomLevels</div>',
        '    <div class="small text-body-secondary">ID registro: ' + idElement + '</div>',
        '  </div>',
        '  <div class="form-check form-switch mb-0">',
        '    <input class="form-check-input" type="checkbox" role="switch" id="rootDomSelectStateSwitch"' + (stateActive ? ' checked' : '') + '>',
        '    <label class="form-check-label small" for="rootDomSelectStateSwitch">' + (stateActive ? 'Activo' : 'Inactivo') + '</label>',
        '  </div>',
        '</div>'
      );
    } else {
      html.push(
        '<p class="mb-2 small">No hay un elemento asociado en la tabla <code>elementsDomLevels</code>.</p>',
        '<div class="mb-3">',
        '  <label class="form-label small mb-1" for="rootDomSelectNameInput">Nombre del elemento</label>',
        '  <input type="text" class="form-control form-control-sm" id="rootDomSelectNameInput" maxlength="124" value="' + escapeHtml(nameText) + '" placeholder="Ej: Eliminar Cliente">',
        '</div>',
        '<button type="button" class="btn btn-sm btn-primary w-100" id="rootDomSelectCreateBtn">Crear elemento</button>'
      );
    }

    body.innerHTML = html.join('');

    const switchEl = document.getElementById('rootDomSelectStateSwitch');
    if (switchEl) {
      const label = switchEl.nextElementSibling;
      switchEl.addEventListener('change', function () {
        const newState = switchEl.checked ? 'true' : 'false';
        switchEl.disabled = true;
        apiRequest('update', {
          id_element: String(idElement),
          state_element_rel: newState,
        })
          .then(function () {
            if (label) {
              label.textContent = switchEl.checked ? 'Activo' : 'Inactivo';
            }
            if (typeof Swal !== 'undefined') {
              Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: 'Estado actualizado',
                showConfirmButton: false,
                timer: 1800,
                timerProgressBar: true,
              });
            }
          })
          .catch(function (err) {
            switchEl.checked = !switchEl.checked;
            if (label) {
              label.textContent = switchEl.checked ? 'Activo' : 'Inactivo';
            }
            if (typeof Swal !== 'undefined') {
              Swal.fire({ icon: 'error', title: 'Error', text: err.message || 'No se pudo actualizar' });
            }
          })
          .finally(function () {
            switchEl.disabled = false;
          });
      });
    }

    const nameInput = document.getElementById('rootDomSelectNameInput');
    if (nameInput) {
      nameInput.focus();
      nameInput.select();
    }

    const createBtn = document.getElementById('rootDomSelectCreateBtn');
    if (createBtn) {
      createBtn.addEventListener('click', function () {
        const nameValue = nameInput ? nameInput.value.trim() : '';
        if (nameValue === '') {
          if (typeof Swal !== 'undefined') {
            Swal.fire({ icon: 'warning', title: 'Nombre obligatorio', text: 'Indica un nombre para el elemento' });
          }
          if (nameInput) {
            nameInput.focus();
          }
          return;
        }

        createBtn.disabled = true;
        createBtn.textContent = 'Creando…';
        if (nameInput) {
          nameInput.disabled = true;
        }
        apiRequest('create', {
          id_dom_element: domId,
          name_text_element: nameValue,
        })
          .then(function (created) {
            renderModalContent(domId, tagName, created, nameValue);
            if (typeof Swal !== 'undefined') {
              Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: 'Elemento creado',
                showConfirmButton: false,
                timer: 1800,
                timerProgressBar: true,
              });
            }
          })
          .catch(function (err) {
            createBtn.disabled = false;
            createBtn.textContent = 'Crear elemento';
            if (nameInput) {
              nameInput.disabled = false;
            }
            if (typeof Swal !== 'undefined') {
              Swal.fire({ icon: 'error', title: 'Error', text: err.message || 'No se pudo crear' });
            }
          });
      });
    }
  }

  function openElementModal(el) {
    const domId = el.id.trim();
    const tagName = el.tagName;
    const suggestedName = suggestNameFromElement(el);

    renderModalLoading(domId, tagName);
    if (modalInstance) {
      modalInstance.show();
    }

    apiRequest('get', { id_dom_element: domId })
      .then(function (data) {
        renderModalContent(domId, tagName, data, suggestedName);
      })
      .catch(function (err) {
        const body = document.getElementById('rootDomSelectModalBody');
        if (body) {
          body.innerHTML = '<p class="mb-0 text-danger small">' + (err.message || 'Error al consultar') + '</p>';
        }
      });
  }

  function onMouseMove(event) {
    if (!active) {
      return;
    }
    const selectable = findSelectableFromTarget(event.target);
    setHighlight(selectable);
  }

  function onClickCapture(event) {
    if (!active) {
      return;
    }
    if (event.target.closest('#rootDomSelectModal, #btnRootSelectDomElements')) {
      return;
    }
    const selectable = findSelectableFromTarget(event.target);
    if (!selectable) {
      return;
    }
    event.preventDefault();
    event.stopPropagation();
    openElementModal(selectable);
  }

  function updateToggleButton() {
    if (!toggleBtn) {
      return;
    }
    toggleBtn.classList.remove('btn-primary', 'btn-danger');
    toggleBtn.classList.add(active ? 'btn-danger' : 'btn-primary');
    toggleBtn.setAttribute('aria-pressed', active ? 'true' : 'false');
    toggleBtn.title = active ? 'Desactivar selección DOM' : 'Seleccionar elementos DOM';
  }

  function setActive(state) {
    active = !!state;
    updateToggleButton();

    const overlay = document.getElementById('rootDomSelectOverlay');
    if (overlay) {
      overlay.classList.toggle('is-active', active);
    }

    if (contentWrapper) {
      contentWrapper.classList.toggle(MODE_CLASS, active);
    }

    if (!active) {
      clearHighlight();
    }
  }

  function bindEvents() {
    if (!toggleBtn || !contentWrapper) {
      return;
    }

    toggleBtn.addEventListener('click', function () {
      setActive(!active);
    });

    contentWrapper.addEventListener('mousemove', onMouseMove, true);
    contentWrapper.addEventListener('mouseleave', clearHighlight, true);
    document.addEventListener('click', onClickCapture, true);

    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape' && active) {
        setActive(false);
      }
    });
  }

  function init() {
    toggleBtn = document.getElementById('btnRootSelectDomElements');
    contentWrapper = document.querySelector('.content-wrapper');
    if (!toggleBtn || !contentWrapper) {
      return;
    }

    injectStyles();
    injectModal();
    bindEvents();
    setActive(false);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
