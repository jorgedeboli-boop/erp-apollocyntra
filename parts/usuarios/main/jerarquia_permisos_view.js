(function () {
  'use strict';

  const SECTION_LABELS = {
    central_section: 'Central',
    recepcion_lotes_section: 'Recepción de lotes',
    auditoria_section: 'Auditoría',
  };

  let todosLosItemsUsuarioJerarquia = [];
  let todosLosElementosUsuarioJerarquia = [];
  let permisosActualesUsuarioJerarquia = [];
  let permisosSoloUsuarioJerarquia = [];
  let permisosElementosJerarquiaUsuario = [];
  let permisosElementosSoloUsuario = [];
  let filtroEstadoUsuarioJerarquia = 'todos';
  let permisosJerarquiaUsuarioCargados = false;

  function getIdJerarquiaUsuario() {
    const input = document.getElementById('id_jerarquia_usuario');
    return input ? parseInt(input.value, 10) || 0 : 0;
  }

  function getIdUsuarioPermisos() {
    const input = document.getElementById('id_usuario_permisos');
    return input ? parseInt(input.value, 10) || 0 : 0;
  }

  function escapeHtmlUsuarioJerarquia(texto) {
    if (texto === null || texto === undefined) {
      return '';
    }
    return String(texto)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  function obtenerTituloItemUsuarioJerarquia(item) {
    if (item.itemnameText && String(item.itemnameText).trim() !== '') {
      return item.itemnameText;
    }
    if (item.itemName) {
      return item.itemName.charAt(0).toUpperCase() + item.itemName.slice(1);
    }
    return 'Sin título';
  }

  function agruparItemsUsuarioJerarquia(items) {
    const itemsPorId = {};
    items.forEach(function (i) {
      itemsPorId[parseInt(i.id_type_Item, 10)] = i;
    });

    const hijosCrudPorListar = {};
    const idsHijoCrud = new Set();

    items.forEach(function (item) {
      const typ = (item.typ_item || '').toLowerCase();
      const fhaterId = parseInt(item.fhater_item, 10) || 0;
      if (fhaterId > 0 && ['main', 'editar', 'crear'].indexOf(typ) !== -1) {
        const padre = itemsPorId[fhaterId];
        if (padre && (padre.typ_item || '').toLowerCase() === 'listar') {
          if (!hijosCrudPorListar[fhaterId]) {
            hijosCrudPorListar[fhaterId] = {};
          }
          hijosCrudPorListar[fhaterId][typ] = item;
          idsHijoCrud.add(parseInt(item.id_type_Item, 10));
        }
      }
    });

    const filas = [];
    items.forEach(function (item) {
      const itemId = parseInt(item.id_type_Item, 10);
      const typ = (item.typ_item || '').toLowerCase();

      if (idsHijoCrud.has(itemId)) {
        return;
      }

      if (typ === 'listar') {
        filas.push({
          tipo: 'grupo',
          listar: item,
          hijos: hijosCrudPorListar[itemId] || {},
        });
        return;
      }

      const fhaterId = parseInt(item.fhater_item, 10) || 0;
      if (fhaterId === 0) {
        filas.push({ tipo: 'individual', item: item });
      }
    });

    return filas;
  }

  function obtenerItemsDeFilaUsuarioJerarquia(fila) {
    if (fila.tipo === 'grupo') {
      const items = [fila.listar];
      ['main', 'editar', 'crear'].forEach(function (typ) {
        if (fila.hijos[typ]) {
          items.push(fila.hijos[typ]);
        }
      });
      return items;
    }
    return [fila.item];
  }

  function itemCoincideBusquedaUsuarioJerarquia(item, searchTerm) {
    if (!searchTerm) {
      return true;
    }

    const itemName = (item.itemName || '').toLowerCase();
    const itemnameText = (item.itemnameText || '').toLowerCase();
    const fhaterItem = (item.fhater_item || '').toString().toLowerCase();
    const tipo = (item.typ_item || '').toLowerCase();
    const url = (item.url_item || '').toLowerCase();
    const id = (item.id_type_Item || '').toString().toLowerCase();

    return itemName.indexOf(searchTerm) !== -1 ||
      itemnameText.indexOf(searchTerm) !== -1 ||
      fhaterItem.indexOf(searchTerm) !== -1 ||
      tipo.indexOf(searchTerm) !== -1 ||
      url.indexOf(searchTerm) !== -1 ||
      id.indexOf(searchTerm) !== -1;
  }

  function elementoEstaEnJerarquiaUsuario(elementId) {
    const id = parseInt(elementId, 10);
    for (let i = 0; i < permisosElementosJerarquiaUsuario.length; i++) {
      if (parseInt(permisosElementosJerarquiaUsuario[i], 10) === id) {
        return true;
      }
    }
    return false;
  }

  function elementoEsSoloUsuario(elementId) {
    const id = parseInt(elementId, 10);
    for (let i = 0; i < permisosElementosSoloUsuario.length; i++) {
      if (parseInt(permisosElementosSoloUsuario[i], 10) === id) {
        return true;
      }
    }
    return false;
  }

  function elementoEstaActivoGlobal(elemento) {
    return (elemento.state_element_rel || '') === 'true';
  }

  function elementoTieneAccesoUsuarioJerarquia(elemento) {
    const id = parseInt(elemento.id_element, 10);
    return elementoEstaActivoGlobal(elemento) && (
      elementoEstaEnJerarquiaUsuario(id) || elementoEsSoloUsuario(id)
    );
  }

  function elementoCoincideBusquedaUsuarioJerarquia(elemento, searchTerm) {
    if (!searchTerm) {
      return true;
    }

    const nombre = (elemento.name_text_element || '').toLowerCase();
    const domId = (elemento.id_dom_element || '').toLowerCase();
    const url = (elemento.url_item || '').toLowerCase();
    const idItem = (elemento.id_type_Item || '').toString().toLowerCase();
    const idElement = (elemento.id_element || '').toString().toLowerCase();

    return nombre.indexOf(searchTerm) !== -1 ||
      domId.indexOf(searchTerm) !== -1 ||
      url.indexOf(searchTerm) !== -1 ||
      idItem.indexOf(searchTerm) !== -1 ||
      idElement.indexOf(searchTerm) !== -1;
  }

  function renderBadgeEstadoElemento(state) {
    const activo = state === 'true';
    const clase = activo ? 'bg-label-success' : 'bg-label-secondary';
    const texto = activo ? 'Activo' : 'Inactivo';
    return '<span class="badge ' + clase + '">' + texto + '</span>';
  }

  function sincronizarClaseElementoSoloUsuario(checkbox, label, activo) {
    if (!checkbox) {
      return;
    }
    if (activo) {
      checkbox.classList.add('permiso-item-checkbox-usuario-solo');
      checkbox.title = 'Acceso personalizado del usuario';
      if (label) {
        label.classList.add('text-danger');
        label.textContent = 'Personalizado';
        label.title = 'Acceso personalizado del usuario';
      }
    } else {
      checkbox.classList.remove('permiso-item-checkbox-usuario-solo');
      checkbox.title = 'Activar acceso personalizado del usuario';
      if (label) {
        label.classList.remove('text-danger');
        label.textContent = 'Personalizado';
        label.title = 'Activar acceso personalizado del usuario';
      }
    }
  }

  function actualizarPermisoElementoUsuario(elementId, estado, checkboxElement) {
    const idUsuario = getIdUsuarioPermisos();
    const idJerarquia = getIdJerarquiaUsuario();
    const checkbox = checkboxElement || document.getElementById('permiso_elemento_usuario_' + elementId);
    const label = checkbox ? checkbox.nextElementSibling : null;

    if (!idUsuario || !idJerarquia || !elementId) {
      mostrarMensajePermisoUsuario('danger', 'Datos de usuario o jerarquía no válidos');
      if (checkbox) {
        checkbox.checked = estado !== 'activo';
      }
      return;
    }

    const formData = new FormData();
    formData.append('id_usuario', String(idUsuario));
    formData.append('id_jerarquia', String(idJerarquia));
    formData.append('element_id', String(elementId));
    formData.append('estado', estado);

    if (checkbox) {
      checkbox.disabled = true;
    }

    fetch('parts/usuarios/main/actualizar_permiso_elemento_usuario.php', {
      method: 'POST',
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
      body: formData,
    })
      .then(function (response) {
        if (!response.ok) {
          throw new Error('HTTP error! status: ' + response.status);
        }
        return response.json();
      })
      .then(function (data) {
        if (!data.success) {
          throw new Error(data.error || 'Error desconocido');
        }

        const elementIdInt = parseInt(elementId, 10);
        if (estado === 'activo') {
          if (permisosElementosSoloUsuario.indexOf(elementIdInt) === -1) {
            permisosElementosSoloUsuario.push(elementIdInt);
          }
          sincronizarClaseElementoSoloUsuario(checkbox, label, true);
        } else {
          permisosElementosSoloUsuario = permisosElementosSoloUsuario.filter(function (id) {
            return parseInt(id, 10) !== elementIdInt;
          });
          sincronizarClaseElementoSoloUsuario(checkbox, label, false);
        }

        mostrarMensajePermisoUsuario('success', data.message || 'Elemento actualizado');
      })
      .catch(function (error) {
        if (checkbox) {
          checkbox.checked = estado !== 'activo';
        }
        mostrarMensajePermisoUsuario('danger', error.message || 'Error al actualizar elemento');
      })
      .finally(function () {
        if (checkbox && !elementoEstaEnJerarquiaUsuario(elementId)) {
          checkbox.disabled = false;
        }
      });
  }

  function renderizarElementosUsuarioJerarquia(elementos) {
    const section = document.getElementById('usuario-jerarquia-elements-container');
    const wrap = document.getElementById('usuario-jerarquia-elements-table-wrap');

    if (!section || !wrap) {
      return;
    }

    if (!elementos.length) {
      section.classList.remove('d-none');
      wrap.innerHTML = `
        <div class="alert alert-info text-center mb-0 mx-3">
          <i class="ri ri-information-line me-2"></i>
          No hay elementos DOM configurados
        </div>
      `;
      return;
    }

    let filas = '';
    elementos.forEach(function (elemento) {
      const nombre = elemento.name_text_element && String(elemento.name_text_element).trim() !== ''
        ? elemento.name_text_element
        : elemento.id_dom_element;
      const elementId = parseInt(elemento.id_element, 10);
      const enJerarquia = elementoEstaEnJerarquiaUsuario(elementId);
      const esSoloUsuario = elementoEsSoloUsuario(elementId);
      const checked = (enJerarquia || esSoloUsuario) ? 'checked' : '';
      const esEditable = !enJerarquia;
      const claseExtra = esSoloUsuario ? ' permiso-item-checkbox-usuario-solo' : '';
      const titulo = enJerarquia
        ? 'Permiso de la jerarquía (solo lectura)'
        : (esSoloUsuario
          ? 'Acceso personalizado del usuario'
          : 'Activar acceso personalizado del usuario');
      const etiqueta = enJerarquia ? 'Jerarquía' : 'Personalizado';

      filas += `
        <tr data-element-id="${elementId}">
          <td class="fw-medium">${escapeHtmlUsuarioJerarquia(nombre)}</td>
          <td><code>${escapeHtmlUsuarioJerarquia(elemento.id_dom_element)}</code></td>
          <td>${renderBadgeEstadoElemento(elemento.state_element_rel)}</td>
          <td><code>${escapeHtmlUsuarioJerarquia(elemento.url_item || 'N/A')}</code></td>
          <td><code>${parseInt(elemento.id_type_Item, 10)}</code></td>
          <td class="text-end">
            <div class="form-check mb-0 d-inline-block">
              <input class="form-check-input permiso-elemento-checkbox-usuario${claseExtra}"
                     type="checkbox"
                     id="permiso_elemento_usuario_${elementId}"
                     data-element-id="${elementId}"
                     ${checked}
                     ${esEditable ? '' : 'disabled'}
                     title="${escapeHtmlUsuarioJerarquia(titulo)}">
              <label class="form-check-label small${esSoloUsuario ? ' text-danger' : ''}"
                     for="permiso_elemento_usuario_${elementId}"
                     title="${escapeHtmlUsuarioJerarquia(titulo)}">${etiqueta}</label>
            </div>
          </td>
        </tr>
      `;
    });

    section.classList.remove('d-none');
    wrap.innerHTML = `
      <div class="table-responsive px-3">
        <table class="table table-sm table-hover table-flush-spacing mb-0">
          <thead>
            <tr>
              <th>Nombre</th>
              <th>ID DOM</th>
              <th>Estado</th>
              <th>URL ítem</th>
              <th>ID ítem</th>
              <th class="text-end">Permiso</th>
            </tr>
          </thead>
          <tbody>${filas}</tbody>
        </table>
      </div>
    `;

    document.querySelectorAll('.permiso-elemento-checkbox-usuario:not(:disabled)').forEach(function (checkbox) {
      checkbox.addEventListener('change', function () {
        const elementId = checkbox.getAttribute('data-element-id');
        const estado = checkbox.checked ? 'activo' : 'no_activo';
        actualizarPermisoElementoUsuario(elementId, estado, checkbox);
      });
    });
  }

  function obtenerElementosFiltradosUsuarioJerarquia() {
    const searchInput = document.getElementById('usuario-jerarquia-search');
    const searchTerm = searchInput ? searchInput.value.trim().toLowerCase() : '';

    return todosLosElementosUsuarioJerarquia.filter(function (elemento) {
      if (!elementoCoincideBusquedaUsuarioJerarquia(elemento, searchTerm)) {
        return false;
      }

      if (filtroEstadoUsuarioJerarquia === 'todos') {
        return true;
      }

      const activo = elementoTieneAccesoUsuarioJerarquia(elemento);
      if (filtroEstadoUsuarioJerarquia === 'activas') {
        return activo;
      }
      if (filtroEstadoUsuarioJerarquia === 'no_activas') {
        return !activo;
      }

      return true;
    });
  }

  function itemEstaActivoUsuarioJerarquia(itemId) {
    const id = parseInt(itemId, 10);
    for (let i = 0; i < permisosActualesUsuarioJerarquia.length; i++) {
      if (parseInt(permisosActualesUsuarioJerarquia[i], 10) === id) {
        return true;
      }
    }
    return false;
  }

  function itemEsSoloUsuarioJerarquia(itemId) {
    const id = parseInt(itemId, 10);
    for (let i = 0; i < permisosSoloUsuarioJerarquia.length; i++) {
      if (parseInt(permisosSoloUsuarioJerarquia[i], 10) === id) {
        return true;
      }
    }
    return false;
  }

  function itemTieneAccesoUsuarioJerarquia(itemId) {
    return itemEstaActivoUsuarioJerarquia(itemId) || itemEsSoloUsuarioJerarquia(itemId);
  }

  function renderCheckboxCeldaUsuarioJerarquia(item, label) {
    if (!item) {
      return '';
    }

    const itemId = parseInt(item.id_type_Item, 10);
    const esSoloUsuario = itemEsSoloUsuarioJerarquia(itemId);
    const esJerarquia = itemEstaActivoUsuarioJerarquia(itemId);
    const checked = (esSoloUsuario || esJerarquia) ? 'checked' : '';
    const esEditable = !esJerarquia;
    const claseExtra = esSoloUsuario ? ' permiso-item-checkbox-usuario-solo' : '';
    const titulo = esJerarquia
      ? 'Permiso de la jerarquía (solo lectura)'
      : (esSoloUsuario
        ? 'Acceso personalizado del usuario'
        : 'Activar acceso personalizado del usuario');

    return `
      <div class="form-check mb-0 mt-1 me-0 ms-lg-12 fs-6">
        <input class="form-check-input permiso-item-checkbox-usuario${claseExtra}" type="checkbox"
               id="permiso_usuario_${itemId}"
               data-item-id="${itemId}"
               ${checked}
               ${esEditable ? '' : 'disabled'}
               title="${escapeHtmlUsuarioJerarquia(titulo)}">
        <label class="form-check-label${esSoloUsuario ? ' text-danger' : ''}" for="permiso_usuario_${itemId}" title="${escapeHtmlUsuarioJerarquia(titulo)}">${escapeHtmlUsuarioJerarquia(label)}</label>
      </div>
    `;
  }

  function initTooltipsUsuarioJerarquia() {
    if (typeof bootstrap === 'undefined' || !bootstrap.Tooltip) {
      return;
    }
    document.querySelectorAll('#usuario-jerarquia-items-container [data-bs-toggle="tooltip"]').forEach(function (el) {
      new bootstrap.Tooltip(el);
    });
  }

  function actualizarEstadoSelectAllUsuarioJerarquia() {
    const selectAll = document.getElementById('selectAllPermisosUsuarioJerarquia');
    const checkboxes = document.querySelectorAll('.permiso-item-checkbox-usuario:not(:disabled)');
    if (!selectAll) {
      return;
    }

    if (!checkboxes.length) {
      selectAll.checked = false;
      selectAll.indeterminate = false;
      selectAll.disabled = true;
      return;
    }

    selectAll.disabled = false;
    const total = checkboxes.length;
    const activos = Array.from(checkboxes).filter(function (cb) { return cb.checked; }).length;

    selectAll.checked = activos === total;
    selectAll.indeterminate = activos > 0 && activos < total;
  }

  function mostrarMensajePermisoUsuario(tipo, mensaje) {
    if (typeof Swal !== 'undefined') {
      Swal.fire({
        toast: true,
        position: 'top-end',
        icon: tipo === 'success' ? 'success' : 'error',
        title: mensaje,
        showConfirmButton: false,
        timer: 2200,
        timerProgressBar: true,
      });
      return;
    }

    const toast = document.createElement('div');
    toast.className = 'toast align-items-center text-white bg-' + (tipo === 'success' ? 'success' : 'danger') + ' border-0 position-fixed';
    toast.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    toast.innerHTML = '<div class="d-flex"><div class="toast-body">' + escapeHtmlUsuarioJerarquia(mensaje) + '</div></div>';
    document.body.appendChild(toast);
    setTimeout(function () {
      if (toast.parentNode) {
        toast.remove();
      }
    }, 3000);
  }

  function sincronizarClaseSoloUsuario(checkbox, activo) {
    if (!checkbox) {
      return;
    }
    const label = checkbox.nextElementSibling;
    if (activo) {
      checkbox.classList.add('permiso-item-checkbox-usuario-solo');
      if (label) {
        label.classList.add('text-danger');
        label.title = 'Acceso personalizado del usuario';
      }
      checkbox.title = 'Acceso personalizado del usuario';
    } else {
      checkbox.classList.remove('permiso-item-checkbox-usuario-solo');
      if (label) {
        label.classList.remove('text-danger');
        label.title = 'Activar acceso personalizado del usuario';
      }
      checkbox.title = 'Activar acceso personalizado del usuario';
    }
  }

  function actualizarPermisoUsuario(itemId, estado, checkboxElement) {
    const idUsuarioEl = document.getElementById('id_usuario_permisos');
    const idJerarquiaEl = document.getElementById('id_jerarquia_usuario');
    const idUsuario = idUsuarioEl ? parseInt(idUsuarioEl.value, 10) : 0;
    const idJerarquia = idJerarquiaEl ? parseInt(idJerarquiaEl.value, 10) : 0;
    const checkbox = checkboxElement || document.getElementById('permiso_usuario_' + itemId);

    if (!idUsuario || !idJerarquia || !itemId) {
      mostrarMensajePermisoUsuario('danger', 'Datos de usuario o jerarquía no válidos');
      if (checkbox) {
        checkbox.checked = estado !== 'activo';
      }
      return;
    }

    const formData = new FormData();
    formData.append('id_usuario', String(idUsuario));
    formData.append('id_jerarquia', String(idJerarquia));
    formData.append('item_id', String(itemId));
    formData.append('estado', estado);

    if (checkbox) {
      checkbox.disabled = true;
    }

    fetch('parts/usuarios/main/actualizar_permiso_usuario.php', {
      method: 'POST',
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
      body: formData,
    })
      .then(function (response) {
        if (!response.ok) {
          throw new Error('HTTP error! status: ' + response.status);
        }
        return response.json();
      })
      .then(function (data) {
        if (!data.success) {
          throw new Error(data.error || 'Error desconocido');
        }

        const itemIdInt = parseInt(itemId, 10);
        if (estado === 'activo') {
          if (permisosSoloUsuarioJerarquia.indexOf(itemIdInt) === -1) {
            permisosSoloUsuarioJerarquia.push(itemIdInt);
          }
          sincronizarClaseSoloUsuario(checkbox, true);
        } else {
          permisosSoloUsuarioJerarquia = permisosSoloUsuarioJerarquia.filter(function (id) {
            return parseInt(id, 10) !== itemIdInt;
          });
          sincronizarClaseSoloUsuario(checkbox, false);
        }

        actualizarEstadoSelectAllUsuarioJerarquia();
        mostrarMensajePermisoUsuario('success', data.message || 'Permiso actualizado');
      })
      .catch(function (error) {
        if (checkbox) {
          checkbox.checked = estado !== 'activo';
        }
        mostrarMensajePermisoUsuario('danger', error.message || 'Error al actualizar permiso');
      })
      .finally(function () {
        if (checkbox) {
          checkbox.disabled = false;
        }
        actualizarEstadoSelectAllUsuarioJerarquia();
      });
  }

  function onPermisoUsuarioCheckboxChange(checkbox) {
    const itemId = checkbox.getAttribute('data-item-id');
    const estado = checkbox.checked ? 'activo' : 'no_activo';
    actualizarPermisoUsuario(itemId, estado, checkbox);
  }

  function bindPermisosUsuarioEditables() {
    document.querySelectorAll('.permiso-item-checkbox-usuario:not(:disabled)').forEach(function (checkbox) {
      checkbox.addEventListener('change', function () {
        onPermisoUsuarioCheckboxChange(checkbox);
      });
    });

    const selectAll = document.getElementById('selectAllPermisosUsuarioJerarquia');
    if (!selectAll) {
      return;
    }

    selectAll.addEventListener('change', function () {
      const marcarActivo = selectAll.checked;
      const checkboxes = document.querySelectorAll('.permiso-item-checkbox-usuario:not(:disabled)');

      checkboxes.forEach(function (checkbox) {
        if (checkbox.checked === marcarActivo) {
          return;
        }
        checkbox.checked = marcarActivo;
        actualizarPermisoUsuario(
          checkbox.getAttribute('data-item-id'),
          marcarActivo ? 'activo' : 'no_activo',
          checkbox
        );
      });
    });
  }

  function renderizarItemsUsuarioJerarquia(filas) {
    const itemsContainer = document.getElementById('usuario-jerarquia-items-container');

    if (!itemsContainer) {
      return;
    }

    if (filas.length === 0) {
      itemsContainer.innerHTML = `
        <div class="alert alert-info text-center mb-0">
          <i class="ri ri-information-line me-2"></i>
          No hay items configurados en el sistema
        </div>
      `;
      return;
    }

    let filasItems = '';

    filas.forEach(function (fila) {
      if (fila.tipo === 'grupo') {
        const listar = fila.listar;
        const hijos = fila.hijos;
        const listarId = parseInt(listar.id_type_Item, 10);
        const tituloItem = escapeHtmlUsuarioJerarquia(obtenerTituloItemUsuarioJerarquia(listar));
        const detalleItem = [
          'URL: ' + (listar.url_item || 'N/A'),
          'ID listar: ' + listarId,
        ].map(escapeHtmlUsuarioJerarquia).join(' · ');

        filasItems += `
          <tr data-grupo-listar="${listarId}">
            <td class="text-nowrap fw-medium fs-6">
              ${tituloItem}
              <small class="text-muted d-block mt-1 fw-normal fs-tiny">${detalleItem}</small>
            </td>
            <td>
              <div class="d-flex justify-content-end flex-wrap fs-6">
                ${renderCheckboxCeldaUsuarioJerarquia(listar, 'Listar')}
                ${renderCheckboxCeldaUsuarioJerarquia(hijos.main, 'Ficha')}
                ${renderCheckboxCeldaUsuarioJerarquia(hijos.editar, 'Editar')}
                ${renderCheckboxCeldaUsuarioJerarquia(hijos.crear, 'Crear')}
              </div>
            </td>
          </tr>
        `;
        return;
      }

      const item = fila.item;
      const itemId = parseInt(item.id_type_Item, 10);
      const tituloItem = escapeHtmlUsuarioJerarquia(obtenerTituloItemUsuarioJerarquia(item));
      const detalleItem = [
        'Tipo: ' + (item.typ_item || 'N/A'),
        'URL: ' + (item.url_item || 'N/A'),
        'ID: ' + itemId,
      ].map(escapeHtmlUsuarioJerarquia).join(' · ');

      filasItems += `
        <tr data-item-id="${itemId}">
          <td class="text-nowrap fw-medium fs-6">
            ${tituloItem}
            <small class="text-muted d-block mt-1 fw-normal">${detalleItem}</small>
          </td>
          <td>
            <div class="d-flex justify-content-end flex-wrap fs-tiny opacity-75">
              ${renderCheckboxCeldaUsuarioJerarquia(item, 'Activo')}
            </div>
          </td>
        </tr>
      `;
    });

    itemsContainer.innerHTML = `
      <div class="table-responsive">
        <table class="table table-flush-spacing">
          <tbody>
            <tr>
              <td class="text-nowrap fw-medium fs-5">
                Acceso total
                <i class="icon-base ri ri-information-line icon-sm"
                   data-bs-toggle="tooltip"
                   data-bs-placement="top"
                   title="Activa o desactiva todos los accesos personalizados visibles (no afecta a la jerarquía)"></i>
              </td>
              <td>
                <div class="d-flex justify-content-end">
                  <div class="form-check mb-0 mt-1 fs-5">
                    <input class="form-check-input" type="checkbox" id="selectAllPermisosUsuarioJerarquia">
                    <label class="form-check-label" for="selectAllPermisosUsuarioJerarquia">Seleccionar todos</label>
                  </div>
                </div>
              </td>
            </tr>
            ${filasItems}
          </tbody>
        </table>
      </div>
    `;

    bindPermisosUsuarioEditables();
    actualizarEstadoSelectAllUsuarioJerarquia();
    initTooltipsUsuarioJerarquia();
  }

  function obtenerFilasFiltradasUsuarioJerarquia() {
    const searchInput = document.getElementById('usuario-jerarquia-search');
    const searchTerm = searchInput ? searchInput.value.trim().toLowerCase() : '';
    const filas = agruparItemsUsuarioJerarquia(todosLosItemsUsuarioJerarquia);

    return filas.filter(function (fila) {
      const itemsFila = obtenerItemsDeFilaUsuarioJerarquia(fila);

      const coincideBusqueda = searchTerm === '' || itemsFila.some(function (item) {
        return itemCoincideBusquedaUsuarioJerarquia(item, searchTerm);
      });

      if (!coincideBusqueda) {
        return false;
      }

      if (filtroEstadoUsuarioJerarquia === 'todos') {
        return true;
      }

      return itemsFila.some(function (item) {
        const activo = itemTieneAccesoUsuarioJerarquia(parseInt(item.id_type_Item, 10));
        if (filtroEstadoUsuarioJerarquia === 'activas') {
          return activo;
        }
        if (filtroEstadoUsuarioJerarquia === 'no_activas') {
          return !activo;
        }
        return true;
      });
    });
  }

  function aplicarFiltrosUsuarioJerarquia() {
    const container = document.getElementById('usuario-jerarquia-items-container');

    if (!container) {
      return;
    }

    const searchInput = document.getElementById('usuario-jerarquia-search');
    const searchTerm = searchInput ? searchInput.value.trim().toLowerCase() : '';
    const filasFiltradas = obtenerFilasFiltradasUsuarioJerarquia();
    const elementosFiltrados = obtenerElementosFiltradosUsuarioJerarquia();

    if (todosLosItemsUsuarioJerarquia.length === 0) {
      container.innerHTML = `
        <div class="alert alert-info text-center mb-0">
          <i class="ri ri-information-line me-2"></i>
          No hay items configurados en el sistema
        </div>
      `;
      renderizarElementosUsuarioJerarquia(elementosFiltrados);
      return;
    }

    if (filasFiltradas.length === 0) {
      let mensajeFiltro = '';
      if (filtroEstadoUsuarioJerarquia === 'activas') {
        mensajeFiltro = 'activas';
      } else if (filtroEstadoUsuarioJerarquia === 'no_activas') {
        mensajeFiltro = 'no activas';
      }

      let texto = 'No se encontraron items';
      if (searchTerm !== '' && mensajeFiltro !== '') {
        texto += ` ${mensajeFiltro} que coincidan con "<strong>${searchTerm}</strong>"`;
      } else if (searchTerm !== '') {
        texto += ` que coincidan con "<strong>${searchTerm}</strong>"`;
      } else if (mensajeFiltro !== '') {
        texto += ` ${mensajeFiltro}`;
      }

      container.innerHTML = `
        <div class="alert alert-info text-center mb-0">
          <i class="ri ri-search-line me-2"></i>
          ${texto}
        </div>
      `;
      renderizarElementosUsuarioJerarquia(elementosFiltrados);
      return;
    }

    renderizarItemsUsuarioJerarquia(filasFiltradas);
    renderizarElementosUsuarioJerarquia(elementosFiltrados);
  }

  function ocultarSeccionElementosUsuarioJerarquia() {
    const section = document.getElementById('usuario-jerarquia-elements-container');
    if (section) {
      section.classList.add('d-none');
    }
  }

  function mostrarSectionBadge(sectionActiva) {
    const badge = document.getElementById('usuario-jerarquia-section-badge');
    if (!badge || !sectionActiva) {
      return;
    }
    badge.textContent = SECTION_LABELS[sectionActiva] || sectionActiva;
    badge.classList.remove('d-none');
  }

  function cargarPermisosJerarquiaUsuario() {
    const itemsContainer = document.getElementById('usuario-jerarquia-items-container');
    const elementsWrap = document.getElementById('usuario-jerarquia-elements-table-wrap');
    const idJerarquia = getIdJerarquiaUsuario();
    const idUsuario = getIdUsuarioPermisos();

    if (!itemsContainer) {
      return;
    }

    if (idJerarquia <= 0) {
      itemsContainer.innerHTML = `
        <div class="alert alert-warning text-center mb-0">
          <i class="ri ri-error-warning-line me-2"></i>
          Este usuario no tiene una jerarquía asignada
        </div>
      `;
      ocultarSeccionElementosUsuarioJerarquia();
      return;
    }

    if (idUsuario <= 0) {
      itemsContainer.innerHTML = `
        <div class="alert alert-warning text-center mb-0">
          <i class="ri ri-error-warning-line me-2"></i>
          ID de usuario no válido
        </div>
      `;
      ocultarSeccionElementosUsuarioJerarquia();
      return;
    }

    itemsContainer.innerHTML = `
      <div class="text-center py-4">
        <div class="spinner-border text-primary" role="status">
          <span class="visually-hidden">Cargando...</span>
        </div>
        <p class="mt-2">Cargando permisos de la jerarquía...</p>
      </div>
    `;

    if (elementsWrap) {
      const section = document.getElementById('usuario-jerarquia-elements-container');
      if (section) {
        section.classList.remove('d-none');
      }
      elementsWrap.innerHTML = `
        <div class="text-center py-3 text-muted small">Cargando elementos DOM...</div>
      `;
    }

    fetch('parts/usuarios/main/get_items_permisos.php?id_jerarquia=' + idJerarquia + '&id_usuario=' + idUsuario, {
      method: 'GET',
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
      },
    })
      .then(function (response) {
        if (!response.ok) {
          throw new Error('HTTP error! status: ' + response.status);
        }
        return response.json();
      })
      .then(function (data) {
        if (!data.success) {
          throw new Error(data.error || 'Error desconocido');
        }

        todosLosItemsUsuarioJerarquia = data.items || [];
        todosLosElementosUsuarioJerarquia = data.elementos_dom || [];
        permisosActualesUsuarioJerarquia = data.permisos_jerarquia || data.permisos_actuales || [];
        permisosSoloUsuarioJerarquia = data.permisos_solo_usuario || [];
        permisosElementosJerarquiaUsuario = data.permisos_elementos_jerarquia || [];
        permisosElementosSoloUsuario = data.permisos_elementos_solo_usuario || [];
        permisosJerarquiaUsuarioCargados = true;
        mostrarSectionBadge(data.section_activa);
        aplicarFiltrosUsuarioJerarquia();
      })
      .catch(function (error) {
        ocultarSeccionElementosUsuarioJerarquia();
        itemsContainer.innerHTML = `
          <div class="alert alert-danger">
            <i class="ri ri-error-warning-line me-2"></i>
            Error al cargar los permisos: ${escapeHtmlUsuarioJerarquia(error.message)}
            <button class="btn btn-sm btn-outline-danger ms-3" type="button" id="usuario-jerarquia-retry">
              <i class="ri ri-refresh-line me-1"></i>
              Reintentar
            </button>
          </div>
        `;
        const retryBtn = document.getElementById('usuario-jerarquia-retry');
        if (retryBtn) {
          retryBtn.addEventListener('click', cargarPermisosJerarquiaUsuario);
        }
      });
  }

  function inicializarBuscadorUsuarioJerarquia() {
    const searchInput = document.getElementById('usuario-jerarquia-search');
    const clearButton = document.getElementById('usuario-jerarquia-clear-search');

    if (!searchInput) {
      return;
    }

    searchInput.addEventListener('input', function () {
      const searchTerm = this.value.trim();
      if (clearButton) {
        clearButton.style.display = searchTerm.length > 0 ? 'block' : 'none';
      }
      aplicarFiltrosUsuarioJerarquia();
    });

    if (clearButton) {
      clearButton.addEventListener('click', function () {
        searchInput.value = '';
        clearButton.style.display = 'none';
        aplicarFiltrosUsuarioJerarquia();
        searchInput.focus();
      });
    }

    searchInput.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') {
        searchInput.value = '';
        if (clearButton) {
          clearButton.style.display = 'none';
        }
        aplicarFiltrosUsuarioJerarquia();
      }
    });
  }

  function inicializarFiltrosEstadoUsuarioJerarquia() {
    const botones = document.querySelectorAll('#navs-pills-top-accesocustom [data-filtro-estado]');
    if (!botones.length) {
      return;
    }

    botones.forEach(function (boton) {
      boton.addEventListener('click', function () {
        filtroEstadoUsuarioJerarquia = this.getAttribute('data-filtro-estado') || 'todos';
        botones.forEach(function (btn) {
          btn.classList.remove('active');
        });
        this.classList.add('active');
        aplicarFiltrosUsuarioJerarquia();
      });
    });
  }

  function inicializarTabAccesoPersonalizado() {
    const tabTrigger = document.querySelector('[data-bs-target="#navs-pills-top-accesocustom"]');
    if (!tabTrigger) {
      return;
    }

    tabTrigger.addEventListener('shown.bs.tab', function () {
      if (!permisosJerarquiaUsuarioCargados) {
        cargarPermisosJerarquiaUsuario();
      }
    });

    if (tabTrigger.classList.contains('active')) {
      cargarPermisosJerarquiaUsuario();
    }
  }

  document.addEventListener('DOMContentLoaded', function () {
    inicializarBuscadorUsuarioJerarquia();
    inicializarFiltrosEstadoUsuarioJerarquia();
    inicializarTabAccesoPersonalizado();
  });
})();
