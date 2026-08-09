/**
 * Módulo central QR + token para foto desde móvil (Camera).
 * Requiere: librería QRCode global, Bootstrap 5, fetch.
 *
 * Antes de cargar este script, definir window.CAMERA_QR (p. ej. vía PHP camera/embed-snippet.php).
 */
(function (global) {
    'use strict';

    var cfg = {
        capturePageBase: '',
        endpoints: {
            guardarToken: '/camera/api/guardar_token.php',
            consultarToken: '/camera/api/consultar_token.php',
            borrarToken: '/camera/api/borrar_token.php'
        },
        pollIntervalMs: 5000,
        pollDurationMs: 60000,
        modalSuffixByType: {
            cliente: 'Cliente',
            lote: 'Lote',
            gasto: 'Gasto',
            gasto_prueba: 'GastoPrueba',
            traspaso: 'Traspaso',
            renovacion: 'Renovacion',
            adelanto: 'Adelanto',
            articulo: 'Articulo',
            venta: 'Venta',
            articulo_venta: 'ArticulosVenta',
            adelanto_venta: 'AdelantoVenta',
            plazo_venta: 'CobrarPlazoVenta'
        },
        callbacks: {
            /**
             * Tras generar QR correctamente (antes del polling).
             */
            afterQrShown: null,
            /**
             * Cuando consultar_token indica utilizado.
             */
            onTokenUtilizado: null,
            /**
             * Al cerrar por timeout (60s) sin uso.
             */
            onPollTimeout: null
        }
    };

    function mergeConfig(user) {
        if (!user || typeof user !== 'object') {
            return;
        }
        if (user.endpoints) {
            Object.assign(cfg.endpoints, user.endpoints);
        }
        if (user.callbacks) {
            Object.assign(cfg.callbacks, user.callbacks);
        }
        if (user.modalSuffixByType) {
            Object.assign(cfg.modalSuffixByType, user.modalSuffixByType);
        }
        ['capturePageBase', 'pollIntervalMs', 'pollDurationMs'].forEach(function (k) {
            if (user[k] !== undefined && user[k] !== null) {
                cfg[k] = user[k];
            }
        });
    }

    function generarToken() {
        return 'tok_' + Math.random().toString(36).substr(2, 28) + Date.now().toString(36);
    }

    function defaultModalId(tipo_qr, opts) {
        if (opts && opts.modalElementId) {
            return opts.modalElementId;
        }
        var suf = cfg.modalSuffixByType[tipo_qr];
        if (!suf) {
            suf = tipo_qr.charAt(0).toUpperCase() + tipo_qr.slice(1).replace(/_([a-z])/g, function (_, c) {
                return c.toUpperCase();
            });
        }
        return 'modalFotoMovil' + suf;
    }

    function defaultQrContainerId(tipo_qr, opts) {
        if (opts && opts.qrContainerId) {
            return opts.qrContainerId;
        }
        return 'qrcode_' + tipo_qr;
    }

    function captureUrl(id_sucursal, id_token, tipo_qr, id_item, token, opts) {
        var base = cfg.capturePageBase || (global.location && global.location.origin ? global.location.origin + '/camera/index.php' : '');
        if (!base) {
            console.error('CameraQR: falta capturePageBase');
            return '';
        }
        var q =
            'id_sucursal=' +
            encodeURIComponent(String(id_sucursal)) +
            '&id_token=' +
            encodeURIComponent(String(id_token)) +
            '&type=' +
            encodeURIComponent(tipo_qr) +
            '&id=' +
            encodeURIComponent(String(id_item)) +
            '&token=' +
            encodeURIComponent(token);
        opts = opts || {};
        if (tipo_qr === 'gasto' && opts.idEmpresa) {
            q += '&id_empresa=' + encodeURIComponent(String(opts.idEmpresa));
        }
        return base.indexOf('?') >= 0 ? base + '&' + q : base + '?' + q;
    }

    function hideModalById(modalId) {
        var el = document.getElementById(modalId);
        if (!el || typeof global.bootstrap === 'undefined') {
            return;
        }
        var inst = global.bootstrap.Modal.getInstance(el);
        if (inst) {
            inst.hide();
        }
    }

    function consultarToken(token, tipo_qr, id_token, interval, id_sucursal, id_item, opts) {
        var modalId = defaultModalId(tipo_qr, opts);
        fetch(cfg.endpoints.consultarToken, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ token: token, id_token: id_token })
        })
            .then(function (r) {
                return r.json();
            })
            .then(function (data) {
                if (data.success && data.utilizado) {
                    clearInterval(interval);
                    hideModalById(modalId);
                    var cbUtil =
                        opts && typeof opts.onTokenUtilizado === 'function'
                            ? opts.onTokenUtilizado
                            : cfg.callbacks.onTokenUtilizado;
                    if (typeof cbUtil === 'function') {
                        cbUtil(tipo_qr, data);
                    }
                }
            })
            .catch(function (e) {
                console.error('CameraQR consultarToken', e);
            });
    }

    /**
     * @param {string} tipo_qr
     * @param {number|string} id_item
     * @param {number|string} id_sucursal
     * @param {object} [opts] modalElementId, qrContainerId
     */
    function generarNuevoQR(tipo_qr, id_item, id_sucursal, opts) {
        opts = opts || {};
        if (typeof QRCode === 'undefined') {
            console.error('CameraQR: falta la librería QRCode');
            return;
        }
        var token = generarToken();
        var modalId = defaultModalId(tipo_qr, opts);
        var qrId = defaultQrContainerId(tipo_qr, opts);

        fetch(cfg.endpoints.guardarToken, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                tipo_qr: tipo_qr,
                id_item: id_item,
                token: token,
                id_sucursal: id_sucursal
            })
        })
            .then(function (r) {
                return r.json();
            })
            .then(function (data) {
                if (!data.success) {
                    throw new Error(data.error || 'No se pudo guardar el token');
                }
                var id_token = data.id_token;
                var url = captureUrl(id_sucursal, id_token, tipo_qr, id_item, token, opts);
                var qrcodeDiv = document.getElementById(qrId);
                if (!qrcodeDiv) {
                    console.warn('CameraQR: no existe contenedor', qrId);
                    return;
                }
                qrcodeDiv.innerHTML = '';
                new QRCode(qrcodeDiv, {
                    text: url,
                    width: 200,
                    height: 200,
                    colorDark: '#000000',
                    colorLight: '#ffffff',
                    correctLevel: QRCode.CorrectLevel.H
                });

                var cbAfter =
                    opts && typeof opts.afterQrShown === 'function'
                        ? opts.afterQrShown
                        : cfg.callbacks.afterQrShown;
                if (typeof cbAfter === 'function') {
                    cbAfter(tipo_qr, { id_token: id_token, token: token, modalId: modalId, qrId: qrId });
                }

                var pollEveryMs =
                    opts && typeof opts.pollIntervalMs === 'number' && opts.pollIntervalMs > 0
                        ? opts.pollIntervalMs
                        : cfg.pollIntervalMs;
                var pollUntilMs =
                    opts && typeof opts.pollDurationMs === 'number' && opts.pollDurationMs > 0
                        ? opts.pollDurationMs
                        : cfg.pollDurationMs;

                var interval = setInterval(function () {
                    consultarToken(token, tipo_qr, id_token, interval, id_sucursal, id_item, opts);
                }, pollEveryMs);

                setTimeout(function () {
                    clearInterval(interval);
                    hideModalById(modalId);
                    var skipBorrar = opts && opts.skipBorrarTokenTimeout === true;
                    if (!skipBorrar) {
                        fetch(cfg.endpoints.borrarToken, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({
                                id_token: id_token,
                                tipo_qr: tipo_qr,
                                id_item: id_item,
                                id_sucursal: id_sucursal
                            })
                        }).catch(function () {});
                    }
                    var cbTo =
                        opts && typeof opts.onPollTimeout === 'function'
                            ? opts.onPollTimeout
                            : cfg.callbacks.onPollTimeout;
                    if (typeof cbTo === 'function') {
                        cbTo(tipo_qr, { id_token: id_token });
                    }
                }, pollUntilMs);
            })
            .catch(function (e) {
                console.error('CameraQR generarNuevoQR', e);
            });
    }

    function init(userCfg) {
        mergeConfig(global.CAMERA_QR || {});
        mergeConfig(userCfg || {});
    }

    var api = {
        init: init,
        generarToken: generarToken,
        generarNuevoQR: generarNuevoQR,
        consultarToken: consultarToken,
        getConfig: function () {
            return cfg;
        },
        setCapturePageBase: function (url) {
            cfg.capturePageBase = url;
        }
    };

    init();
    global.CameraQR = api;
    global.generarNuevoQR = function (tipo_qr, id_item, id_sucursal, opts) {
        return api.generarNuevoQR(tipo_qr, id_item, id_sucursal, opts);
    };
})(typeof window !== 'undefined' ? window : this);
