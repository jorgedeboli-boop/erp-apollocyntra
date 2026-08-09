/**
 * Panel unificado: fetch a camera/api/doc_panel.php (tipo + id + id_sucursal)
 * y apertura del modal QR con camera-qr.js (generarNuevoQR).
 *
 * Opcional: window.CAMERA_DOC_API_BASE = 'camera/api'; (sin barra final o con)
 */
(function (global) {
    'use strict';

    /** @type {Object.<string, object>} */
    var panelCache = {};

    function apiBase() {
        var b = global.CAMERA_DOC_API_BASE || 'camera/api';
        return b.replace(/\/?$/, '/');
    }

    function cacheKey(tipo, id, idSucursal) {
        return String(tipo) + ':' + String(id) + ':' + String(idSucursal);
    }

    function ensureModal(data, onRefreshQr) {
        var el = document.getElementById(data.modal_id);
        if (el) {
            return el;
        }
        var wrap = document.createElement('div');
        wrap.innerHTML = data.modal_html.trim();
        el = wrap.firstElementChild;
        if (!el) {
            return null;
        }
        document.body.appendChild(el);
        var btn = el.querySelector('.camdp-btn-refresh-qr');
        if (btn) {
            btn.addEventListener('click', function () {
                onRefreshQr();
            });
        }
        return el;
    }

    function showPanel(data, tipo, id, idSucursal, panelCallbacks) {
        function refreshQr() {
            if (typeof global.generarNuevoQR !== 'function') {
                console.error('CameraDocPanel: falta generarNuevoQR (camera-qr.js)');
                return;
            }
            var qrOpts = {
                modalElementId: data.modal_id,
                qrContainerId: data.qr_container_id
            };
            // ia_chat: el móvil tarda en escanear/fotografiar; a los 60s borrarToken eliminaba la fila y subir_foto fallaba.
            if (tipo === 'ia_chat' || tipo === 'documento_ocr' || tipo === 'factura_ocr') {
                qrOpts.pollDurationMs = 900000;
                qrOpts.pollIntervalMs = 3000;
                qrOpts.skipBorrarTokenTimeout = true;
            }
            if (panelCallbacks) {
                if (typeof panelCallbacks.onTokenUtilizado === 'function') {
                    qrOpts.onTokenUtilizado = panelCallbacks.onTokenUtilizado;
                }
                if (typeof panelCallbacks.onPollTimeout === 'function') {
                    qrOpts.onPollTimeout = panelCallbacks.onPollTimeout;
                }
                if (typeof panelCallbacks.afterQrShown === 'function') {
                    qrOpts.afterQrShown = panelCallbacks.afterQrShown;
                }
            }
            if (panelCallbacks && panelCallbacks.idEmpresa) {
                qrOpts.idEmpresa = panelCallbacks.idEmpresa;
            }
            global.generarNuevoQR(tipo, id, idSucursal, qrOpts);
        }

        var modalEl = ensureModal(data, refreshQr);
        if (!modalEl || typeof global.bootstrap === 'undefined') {
            throw new Error('CameraDocPanel: modal o Bootstrap no disponible');
        }
        // Overlay QR: no debe cerrar el modal subyacente (p.ej. adelanto/renovación).
        // Usamos backdrop:false y focus:false para que sea un "popup" encima.
        var modal = global.bootstrap.Modal.getOrCreateInstance(modalEl, { backdrop: false, focus: false });
        modal.show();
        refreshQr();
    }

    /**
     * @param {object} opts
     * @param {string} opts.tipo p.ej. 'cliente'
     * @param {number} opts.id id del ítem (p.ej. id_cliente)
     * @param {number} opts.idSucursal
     */
    function open(opts) {
        opts = opts || {};
        var tipo = opts.tipo;
        var id = opts.id;
        var idSucursal = opts.idSucursal;
        if (!tipo || !id || !idSucursal) {
            console.error('CameraDocPanel.open: requiere tipo, id e idSucursal');
            return Promise.reject(new Error('CameraDocPanel: parámetros incompletos'));
        }

        var key = cacheKey(tipo, id, idSucursal);
        var panelCallbacks = {
            onTokenUtilizado: opts.onTokenUtilizado,
            onPollTimeout: opts.onPollTimeout,
            afterQrShown: opts.afterQrShown,
            idEmpresa: opts.idEmpresa
        };

        var cached = panelCache[key];
        if (cached && document.getElementById(cached.modal_id)) {
            showPanel(cached, tipo, id, idSucursal, panelCallbacks);
            return Promise.resolve();
        }

        var fd = new FormData();
        fd.append('tipo', String(tipo));
        fd.append('id', String(id));
        fd.append('id_sucursal', String(idSucursal));

        return fetch(apiBase() + 'doc_panel.php', { method: 'POST', body: fd })
            .then(function (r) {
                return r.json();
            })
            .then(function (data) {
                if (!data.success) {
                    throw new Error(data.error || 'doc_panel');
                }
                panelCache[key] = data;
                showPanel(data, tipo, id, idSucursal, panelCallbacks);
            });
    }

    global.CameraDocPanel = {
        open: open
    };
})(typeof window !== 'undefined' ? window : this);
