<script src="assets/leaflet/leaflet.js"></script>
<script>
(function () {
    'use strict';

    const REFRESH_INTERVAL_MS = 10000;
    let refreshTimer = null;
    let mapa = null;
    let capaMarcadores = null;
    let estadoConexionesSucursales = {};
    let estadoConexionesInicializado = false;

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function setLoading(isLoading) {
        const loadingEl = document.getElementById('connectionsStateLoading');
        if (loadingEl) {
            loadingEl.classList.toggle('d-none', !isLoading);
        }
    }

    function setError(message) {
        const errorEl = document.getElementById('connectionsStateError');
        if (!errorEl) {
            return;
        }
        if (message) {
            errorEl.textContent = message;
            errorEl.classList.remove('d-none');
        } else {
            errorEl.textContent = '';
            errorEl.classList.add('d-none');
        }
    }

    function updateStats(data) {
        const totalConexiones = document.getElementById('stats-total-conexiones');
        const totalUsuarios = document.getElementById('stats-total-usuarios');
        const statsSucursales = document.getElementById('stats-total-sucursales');
        const updatedAt = document.getElementById('connectionsStateUpdatedAt');

        if (totalConexiones) {
            totalConexiones.textContent = data.total_conexiones ?? 0;
        }
        if (totalUsuarios) {
            totalUsuarios.textContent = data.total_usuarios ?? 0;
        }
        if (statsSucursales) {
            statsSucursales.textContent = data.total_sucursales ?? 0;
        }
        if (updatedAt) {
            const now = new Date();
            updatedAt.textContent = 'Actualizado: ' + now.toLocaleTimeString('es-ES');
        }
    }

    function renderConexionRow(conexion) {
        const idUsuario = parseInt(conexion.id_usuario || '0', 10);
        const nombreUsuario = escapeHtml(conexion.nombre_usuario || '—');
        const loginUsuario = escapeHtml(conexion.login_usuario || '');
        const usuarioHtml = idUsuario > 0
            ? '<a href="usuario.php?id=' + idUsuario + '" class="fw-medium text-primary">' + nombreUsuario + '</a>'
            : '<span class="fw-medium">' + nombreUsuario + '</span>';

        const loginHtml = loginUsuario !== ''
            ? '<br><small class="text-muted">@' + loginUsuario + '</small>'
            : '';

        return '<tr>' +
            '<td><span class="fw-semibold">#' + escapeHtml(conexion.id_user_conexion) + '</span></td>' +
            '<td>' + usuarioHtml + loginHtml + '</td>' +
            '<td><span class="fw-medium">' + escapeHtml(conexion.fecha_conexion) + '</span></td>' +
            '<td><code class="text-primary">' + escapeHtml(conexion.ip) + '</code></td>' +
            '<td><span class="text-truncate d-inline-block" style="max-width: 220px;" title="' + escapeHtml(conexion.user_agent) + '">' + escapeHtml(conexion.user_agent) + '</span></td>' +
            '<td>' + (conexion.ubicacion !== 'N/A'
                ? '<span class="badge bg-label-warning rounded-pill"><i class="icon-base ri ri-map-pin-line me-1"></i>' + escapeHtml(conexion.ubicacion) + '</span>'
                : '<span class="text-muted">N/A</span>') +
            '</td>' +
            '<td><code class="text-secondary text-truncate d-inline-block" style="max-width: 120px;" title="' + escapeHtml(conexion.token) + '">' + escapeHtml(conexion.token) + '</code></td>' +
            '</tr>';
    }

    function renderSucursalAccordionItem(sucursal, index, expanded) {
        const idSucursal = parseInt(sucursal.id_sucursal || '0', 10);
        const nombreSucursal = escapeHtml(sucursal.nombre_sucursal || 'Sin sucursal');
        const conexiones = Array.isArray(sucursal.conexiones) ? sucursal.conexiones : [];
        const collapseId = 'collapseConexionesSucursal' + idSucursal + '_' + index;
        const headingId = 'headingConexionesSucursal' + idSucursal + '_' + index;

        const sucursalLink = idSucursal > 0
            ? '<a href="sucursal.php?id=' + idSucursal + '" class="text-primary ms-2" onclick="event.stopPropagation();">Ver sucursal</a>'
            : '';

        const rowsHtml = conexiones.map(renderConexionRow).join('');

        return '<div class="accordion-item">' +
            '<h2 class="accordion-header" id="' + headingId + '">' +
            '<button class="accordion-button ' + (expanded ? '' : 'collapsed') + '" type="button" data-bs-toggle="collapse" data-bs-target="#' + collapseId + '" aria-expanded="' + (expanded ? 'true' : 'false') + '" aria-controls="' + collapseId + '">' +
            '<i class="icon-base ri ri-building-line me-2"></i>' +
            '<span class="fw-semibold">' + nombreSucursal + '</span>' +
            '<span class="badge bg-label-success rounded-pill ms-3">' + conexiones.length + ' conectado' + (conexiones.length === 1 ? '' : 's') + '</span>' +
            sucursalLink +
            '</button>' +
            '</h2>' +
            '<div id="' + collapseId + '" class="accordion-collapse collapse ' + (expanded ? 'show' : '') + '" aria-labelledby="' + headingId + '" data-bs-parent="#connectionsStateAccordion">' +
            '<div class="accordion-body p-0">' +
            '<div class="table-responsive">' +
            '<table class="table table-hover mb-0">' +
            '<thead>' +
            '<tr>' +
            '<th>ID</th>' +
            '<th>Usuario</th>' +
            '<th>Conectado desde</th>' +
            '<th>IP</th>' +
            '<th>User Agent</th>' +
            '<th>Ubicación</th>' +
            '<th>Token</th>' +
            '</tr>' +
            '</thead>' +
            '<tbody>' + rowsHtml + '</tbody>' +
            '</table>' +
            '</div>' +
            '</div>' +
            '</div>' +
            '</div>';
    }

    function renderConnectionsState(data) {
        const emptyEl = document.getElementById('connectionsStateEmpty');
        const accordionEl = document.getElementById('connectionsStateAccordion');
        const sucursales = Array.isArray(data.sucursales) ? data.sucursales : [];

        if (!accordionEl) {
            return;
        }

        if (sucursales.length === 0) {
            accordionEl.innerHTML = '';
            accordionEl.classList.add('d-none');
            if (emptyEl) {
                emptyEl.classList.remove('d-none');
            }
            return;
        }

        if (emptyEl) {
            emptyEl.classList.add('d-none');
        }

        accordionEl.classList.remove('d-none');
        accordionEl.innerHTML = sucursales.map(function (sucursal, index) {
            return renderSucursalAccordionItem(sucursal, index, index === 0);
        }).join('');
    }

    function cargarConnectionsState(showLoader) {
        const btnRefrescar = document.getElementById('btnRefrescarConexionesState');

        if (showLoader !== false) {
            setLoading(true);
        }
        setError('');
        if (btnRefrescar) {
            btnRefrescar.disabled = true;
        }

        return fetch('parts/connections_state/unique/load_connections_state.php', {
            method: 'GET',
            headers: {
                'Accept': 'application/json'
            }
        })
        .then(function (response) {
            return response.json().then(function (json) {
                if (!response.ok) {
                    throw new Error(json.error || 'Error al cargar conexiones');
                }
                return json;
            });
        })
        .then(function (data) {
            if (!data.success) {
                throw new Error(data.error || 'Error al cargar conexiones');
            }

            updateStats(data);
            renderConnectionsState(data);
        })
        .catch(function (error) {
            console.error('Error cargarConnectionsState:', error);
            setError(error.message || 'No se pudieron cargar las conexiones activas.');

            const accordionEl = document.getElementById('connectionsStateAccordion');
            const emptyEl = document.getElementById('connectionsStateEmpty');
            if (accordionEl) {
                accordionEl.innerHTML = '';
                accordionEl.classList.add('d-none');
            }
            if (emptyEl) {
                emptyEl.classList.add('d-none');
            }
        })
        .finally(function () {
            setLoading(false);
            if (btnRefrescar) {
                btnRefrescar.disabled = false;
            }
        });
    }

    function sucursalEstaConectada(sucursal) {
        return sucursal.sucursal_conectada === 'true' || sucursal.sucursal_conectada === true;
    }

    function colorMarcadorSucursal(sucursalesGrupo) {
        const algunaConectada = sucursalesGrupo.some(sucursalEstaConectada);
        return algunaConectada ? '#7FCC45' : '#6d788d';
    }

    function construirGruposSucursales(sucursales) {
        const grupos = {};

        sucursales.forEach(function (s) {
            if (!s.lat || !s.lng) {
                return;
            }

            const clave = (s.poblacion_tienda || '').trim().toLowerCase();
            if (!grupos[clave]) {
                grupos[clave] = {
                    clave: clave,
                    poblacion: s.poblacion_tienda,
                    lat: parseFloat(s.lat),
                    lng: parseFloat(s.lng),
                    sucursales: []
                };
            }
            grupos[clave].sucursales.push(s);
        });

        return grupos;
    }

    function construirPopupHtml(sucursalesGrupo) {
        return sucursalesGrupo.map(function (s) {
            const conectada = sucursalEstaConectada(s);
            const estadoHtml = conectada
                ? '<span style="color:#7FCC45;font-weight:600;">● Conectada</span>'
                : '<span style="color:#6d788d;font-weight:600;">● Desconectada</span>';

            return '<b>' + escapeHtml(s.nombre_sucursal) + '</b> ' + estadoHtml + '<br>' +
                   escapeHtml(s.direccion_tienda) + '<br>' +
                   escapeHtml(s.codigo_postal_tienda) + ' ' + escapeHtml(s.poblacion_tienda) +
                   ' (' + escapeHtml(s.provincia_tienda) + ')';
        }).join('<hr style="margin:6px 0;">');
    }

    function notificarCambiosConexionSucursales(sucursales) {
        if (typeof window.mostrarNotificacionSistema !== 'function') {
            return;
        }

        sucursales.forEach(function (s) {
            const idSucursal = parseInt(s.id_sucursal || '0', 10);
            if (idSucursal <= 0) {
                return;
            }

            const conectada = sucursalEstaConectada(s);
            const estadoAnterior = estadoConexionesSucursales[idSucursal];
            const nombreSucursal = s.nombre_sucursal || ('Sucursal #' + idSucursal);
            const urlSucursal = 'sucursal.php?id=' + idSucursal;

            if (estadoConexionesInicializado && estadoAnterior !== undefined && estadoAnterior !== conectada) {
                if (conectada) {
                    window.mostrarNotificacionSistema({
                        mensaje_notificacion: '<strong>' + escapeHtml(nombreSucursal) + '</strong> conectada',
                        color_notificacion: 'Success',
                        url_notificacion: urlSucursal
                    });
                } else {
                    window.mostrarNotificacionSistema({
                        mensaje_notificacion: '<strong>' + escapeHtml(nombreSucursal) + '</strong> desconectada',
                        color_notificacion: 'Secondary',
                        url_notificacion: urlSucursal
                    });
                }
            }

            estadoConexionesSucursales[idSucursal] = conectada;
        });

        estadoConexionesInicializado = true;
    }

    function pintarMarcadoresMapa(sucursales) {
        if (!mapa || !capaMarcadores) {
            return;
        }

        capaMarcadores.clearLayers();

        const grupos = construirGruposSucursales(sucursales);

        Object.keys(grupos).forEach(function (clave) {
            const g = grupos[clave];
            const n = g.sucursales.length;
            const etiqueta = n > 1
                ? g.poblacion + ' (' + n + ')'
                : g.poblacion;
            const fillColor = colorMarcadorSucursal(g.sucursales);
            const popupHtml = construirPopupHtml(g.sucursales);

            L.circleMarker([g.lat, g.lng], {
                radius: n > 1 ? 10 : 8,
                fillColor: fillColor,
                color: '#fff',
                weight: 2,
                fillOpacity: 0.9
            }).addTo(capaMarcadores)
              .bindTooltip(etiqueta, {
                  permanent: true,
                  direction: 'top',
                  className: 'etiqueta-ciudad',
                  offset: [0, -8]
              })
              .bindPopup(popupHtml);
        });
    }

    function cargarMarcadoresMapa() {
        return fetch('parts/connections_state/unique/get_sucursales.php', {
            method: 'GET',
            headers: {
                'Accept': 'application/json'
            }
        })
        .then(function (r) {
            if (!r.ok) {
                throw new Error('Error HTTP: ' + r.status);
            }
            return r.json();
        })
        .then(function (sucursales) {
            if (!Array.isArray(sucursales)) {
                throw new Error('Respuesta de sucursales no válida');
            }
            notificarCambiosConexionSucursales(sucursales);
            if (mapa && capaMarcadores) {
                pintarMarcadoresMapa(sucursales);
            }
        })
        .catch(function (error) {
            console.error('Error al cargar sucursales del mapa:', error);
        });
    }

    function crearMapaBase() {
        const mapaEl = document.getElementById('mapa');
        if (!mapaEl || mapa !== null) {
            return;
        }

        mapa = L.map('mapa').setView([40.0, -3.7], 6);

        L.tileLayer('https://{s}.basemaps.cartocdn.com/light_nolabels/{z}/{x}/{y}{r}.png', {
            attribution: '&copy; OpenStreetMap &copy; CARTO',
            maxZoom: 19
        }).addTo(mapa);

        capaMarcadores = L.layerGroup().addTo(mapa);
    }

    function refrescarEstadoConexiones(showLoader) {
        cargarConnectionsState(showLoader);
        cargarMarcadoresMapa();
    }

    function ajustarTamanoMapa() {
        if (mapa) {
            setTimeout(function () {
                mapa.invalidateSize();
            }, 150);
        }
    }

    function mostrarVistaMapa() {
        const mapaEl = document.getElementById('mapa');
        const listadoEl = document.getElementById('connectionsStateListado');
        const btnVerMapa = document.getElementById('btnVerMapa');
        const btnVerListado = document.getElementById('btnVerListado');

        if (listadoEl) {
            listadoEl.classList.add('d-none');
        }
        if (mapaEl) {
            mapaEl.classList.remove('d-none');
        }
        if (btnVerMapa) {
            btnVerMapa.classList.add('d-none');
        }
        if (btnVerListado) {
            btnVerListado.classList.remove('d-none');
        }

        if (!mapa) {
            crearMapaBase();
            cargarMarcadoresMapa();
        }

        ajustarTamanoMapa();
    }

    function mostrarVistaListado() {
        const mapaEl = document.getElementById('mapa');
        const listadoEl = document.getElementById('connectionsStateListado');
        const btnVerMapa = document.getElementById('btnVerMapa');
        const btnVerListado = document.getElementById('btnVerListado');

        if (mapaEl) {
            mapaEl.classList.add('d-none');
        }
        if (listadoEl) {
            listadoEl.classList.remove('d-none');
        }
        if (btnVerMapa) {
            btnVerMapa.classList.remove('d-none');
        }
        if (btnVerListado) {
            btnVerListado.classList.add('d-none');
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        const btnRefrescar = document.getElementById('btnRefrescarConexionesState');
        const btnVerMapa = document.getElementById('btnVerMapa');
        const btnVerListado = document.getElementById('btnVerListado');

        refrescarEstadoConexiones(true);

        if (btnVerMapa) {
            btnVerMapa.addEventListener('click', mostrarVistaMapa);
        }

        if (btnVerListado) {
            btnVerListado.addEventListener('click', mostrarVistaListado);
        }

        if (btnRefrescar) {
            btnRefrescar.addEventListener('click', function () {
                refrescarEstadoConexiones(false);
            });
        }

        refreshTimer = setInterval(function () {
            refrescarEstadoConexiones(false);
        }, REFRESH_INTERVAL_MS);

        window.addEventListener('beforeunload', function () {
            if (refreshTimer) {
                clearInterval(refreshTimer);
            }
        });
    });

})();
</script>