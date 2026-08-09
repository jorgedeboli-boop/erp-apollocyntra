// ── Adjuntar foto al chat (modal universal) ────────────────────────────────
var IA_CHAT_ADJUNTO_MAX_BYTES = 5242880;

/** Misma lógica que ia_mensaje_sugiere_adjuntos_creacion en ajax_ia_chat.php */
function iaChatMensajePideAdjuntosCreacion(texto) {
    var p = String(texto || '').toLowerCase();
    try {
        if (typeof p.normalize === 'function') {
            p = p.normalize('NFD').replace(/[\u0300-\u036f]/g, '');
        }
    } catch (e) {}
    var rx = [
        /(crear|registrar|alta de|alta |dar de alta)[\s\S]{0,55}(cliente|clientes)/i,
        /(nuevo|nueva)[\s\S]{0,22}(cliente|clientes)/i,
        /(cliente|clientes)[\s\S]{0,28}(nuevo|nueva|crear|alta)\b/i,
        /(crear|registrar|alta de|alta |dar de alta)[\s\S]{0,55}(lote|lotes|empeno|empeño|empeños)/i,
        /(nuevo|nueva)[\s\S]{0,22}(lote|lotes|empeno|empeño)/i,
        /(lote|lotes|empeno|empeño)[\s\S]{0,28}(nuevo|nueva|crear|alta)\b/i
    ];
    for (var i = 0; i < rx.length; i++) {
        if (rx[i].test(p)) return true;
    }
    return false;
}

/** Alineado con ia_pregunta_es_solo_flujo_adjunto_documentacion en ajax_ia_chat.php (mostrar (+) solo si no irá por SQL). */
function iaChatEsSoloFlujoAdjuntoDocumentacion(texto) {
    if (!iaChatMensajePideAdjuntosCreacion(texto)) return false;
    var p = String(texto || '').trim();
    if (p.length > 240) return false;
    var ln = p.toLowerCase();
    try {
        if (typeof ln.normalize === 'function') {
            ln = ln.normalize('NFD').replace(/[\u0300-\u036f]/g, '');
        }
    } catch (e) {}
    if (/\b(cuanto|cuanta|cuantos|cuantas|listado|lista de|dame|muestra|muestrame|consulta|totales|total |suma |promedio|informe|exportar|excel|pdf|ventas de|sql|tabla con|cuantos hay|dame los|dame las)\b/i.test(ln)) {
        return false;
    }
    return true;
}

function iaChatAdjuntoMostrar() {
    var el = document.getElementById('iaChatAdjuntoToggle') || document.querySelector('#modalIAChat .ia-chat-adjunto-toggle');
    if (!el) return;
    el.classList.remove('d-none');
    el.setAttribute('aria-hidden', 'false');
}

function iaChatAdjuntoOcultar() {
    var el = document.getElementById('iaChatAdjuntoToggle') || document.querySelector('#modalIAChat .ia-chat-adjunto-toggle');
    if (!el) return;
    el.classList.add('d-none');
    el.setAttribute('aria-hidden', 'true');
    iaChatAdjuntoDropdownCerrar();
}

function iaChatAdjuntoDropdownCerrar() {
    var toggle = document.querySelector('#modalIAChat .ia-chat-adjunto-toggle .dropdown-toggle');
    if (toggle && typeof bootstrap !== 'undefined' && bootstrap.Dropdown) {
        var inst = bootstrap.Dropdown.getInstance(toggle);
        if (inst) inst.hide();
    }
}

function iaChatAdjuntoSubirFoto() {
    iaChatAdjuntoDropdownCerrar();
    var form = document.getElementById('formSubirFotoUniversal');
    var inp  = document.getElementById('archivo_foto_universal');
    if (form) form.reset();
    if (inp) inp.value = '';
    var el = document.getElementById('modalSubirFotoUniversal');
    if (!el || typeof bootstrap === 'undefined' || !bootstrap.Modal) return;
    var modal = bootstrap.Modal.getOrCreateInstance(el);
    modal.show();
}

function iaChatAdjuntoHacerFoto() {
    iaChatAdjuntoDropdownCerrar();
    var root = document.getElementById('modalIAChat');
    if (!root) return;
    var uid = parseInt(root.getAttribute('data-ia-camera-usuario-id') || '0', 10);
    var sid = parseInt(root.getAttribute('data-ia-camera-sucursal') || '0', 10);
    if (!uid || !sid) {
        alert('No se puede usar la foto desde móvil: falta usuario o sucursal en sesión.');
        return;
    }
    function abrirQrCamara() {
        if (!window.CameraDocPanel || typeof window.CameraDocPanel.open !== 'function') {
            alert('No está disponible el módulo de cámara (CameraDocPanel).');
            return;
        }
        if (typeof window.generarNuevoQR !== 'function' || typeof QRCode === 'undefined') {
            alert('No está cargada la librería de QR. Recarga la página o inténtalo desde otra pantalla.');
            return;
        }
        window.CameraDocPanel.open({
            tipo: 'ia_chat',
            id: uid,
            idSucursal: sid,
            onTokenUtilizado: function (tipoQr, data) {
                if (tipoQr !== 'ia_chat') return;
                var td = (data && data.token_data) ? data.token_data : {};
                var url = td.foto_url ? String(td.foto_url) : '';
                if (!url && td.nombre_foto) {
                    var nom = String(td.nombre_foto).replace(/\\/g, '/').split('/').pop() || '';
                    if (nom) {
                        var origin = (typeof window !== 'undefined' && window.location && window.location.origin)
                            ? String(window.location.origin).replace(/\/$/, '')
                            : '';
                        if (typeof window.APP_URL === 'string' && window.APP_URL) {
                            url = window.APP_URL.replace(/\/$/, '') + '/photos/' + encodeURIComponent(nom);
                        } else if (origin) {
                            url = origin + '/photos/' + encodeURIComponent(nom);
                        } else {
                            url = '/photos/' + encodeURIComponent(nom);
                        }
                    }
                }
                if (!url) {
                    console.warn('IA chat: token utilizado sin foto_url ni nombre_foto', data);
                    return;
                }
                var fakeFile = { name: 'foto-movil.jpg' };
                window.__iaChatUltimoAdjunto = { file: fakeFile, tipo: 'imagen', dataUrl: url, imagen_url: url };
                iaChatAgregarMensajeAdjuntoUsuario(fakeFile, url);
                iaChatAnalizarDocumentoAdjunto({ imagen_url: url });
            }
        }).catch(function (err) {
            console.error('CameraDocPanel (IA chat)', err);
            alert('No se pudo abrir el código QR. ¿Tienes conexión?');
        });
    }
    if (window.CameraDocPanel && typeof window.CameraDocPanel.open === 'function' && typeof QRCode !== 'undefined') {
        abrirQrCamara();
        return;
    }
    function cargarScript(src) {
        return new Promise(function (resolve, reject) {
            var s = document.createElement('script');
            s.src = src;
            s.onload = resolve;
            s.onerror = function () {
                reject(new Error('No se pudo cargar: ' + src));
            };
            document.head.appendChild(s);
        });
    }
    var cadena = Promise.resolve();
    if (typeof QRCode === 'undefined') {
        cadena = cadena.then(function () {
            return cargarScript(IA_CAM_JS_QRCODE);
        });
    }
    if (typeof window.generarNuevoQR !== 'function') {
        cadena = cadena.then(function () {
            return cargarScript(IA_CAM_JS_QR);
        });
    }
    if (!window.CameraDocPanel) {
        cadena = cadena.then(function () {
            return cargarScript(IA_CAM_JS_DP);
        });
    }
    cadena.then(abrirQrCamara).catch(function (e) {
        console.error(e);
        alert('No se pudieron cargar los scripts de cámara/QR. Recarga la página.');
    });
}

function iaChatSubirFotoUniversalSpinner(mostrar) {
    var sp = document.getElementById('iaChatSubirFotoUniversalSpinner');
    if (!sp) return;
    if (mostrar) sp.classList.remove('d-none');
    else sp.classList.add('d-none');
}

function iaChatScrollAlFinal() {
    var zona = document.getElementById('iaChatMensajes');
    if (!zona) return;
    requestAnimationFrame(function () {
        zona.scrollTop = zona.scrollHeight;
        var ultimo = zona.querySelector('.ia-msg:last-child');
        if (ultimo && typeof ultimo.scrollIntoView === 'function') {
            ultimo.scrollIntoView({ block: 'end', behavior: 'auto' });
        }
        requestAnimationFrame(function () {
            zona.scrollTop = zona.scrollHeight;
        });
    });
}

function iaChatAgregarMensajeAdjuntoUsuario(file, previewSrc) {
    var zona = document.getElementById('iaChatMensajes');
    if (!zona || !file) return;
    var div = document.createElement('div');
    div.className = 'ia-msg ia-msg-user';
    var hora = new Date().toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' });
    var burbuja = document.createElement('div');
    burbuja.className = 'ia-msg-burbuja';
    if (previewSrc) {
        var img = document.createElement('img');
        img.className = 'ia-chat-foto-adjunta img-fluid';
        img.alt = file.name || 'Foto adjunta';
        img.src = previewSrc;
        burbuja.appendChild(img);
    } else {
        var p = document.createElement('p');
        p.className = 'mb-0';
        p.textContent = 'Documento PDF: ' + (file.name || '');
        burbuja.appendChild(p);
    }
    var nom = document.createElement('div');
    nom.className = 'ia-chat-adjunto-nombre';
    nom.textContent = file.name || '';
    burbuja.appendChild(nom);
    div.appendChild(burbuja);
    var horaEl = document.createElement('div');
    horaEl.className = 'ia-msg-hora';
    horaEl.textContent = hora;
    div.appendChild(horaEl);
    zona.appendChild(div);
    iaChatScrollAlFinal();
}

function iaChatAgregarRespuestaDocumento(res) {
    var zona = document.getElementById('iaChatMensajes');
    if (!zona) return;
    var div = document.createElement('div');
    div.className = 'ia-msg ia-msg-bot';
    var hora = new Date().toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' });
    div.innerHTML =
        '<div class="ia-msg-burbuja">' + (res.texto || '') + '</div>' +
        '<div class="ia-msg-hora">' + hora + '</div>';
    zona.appendChild(div);
    iaChatScrollAlFinal();
    iaChatLeerSiActivo(res.texto || '');
}

/**
 * Envía la imagen al servidor para clasificar DNI/NIE/pasaporte y extraer datos.
 * opts: { dataUrl: 'data:image/...' } y/o { imagen_url: 'https://.../photos/arch.jpg' }
 */
function iaChatAnalizarDocumentoAdjunto(opts) {
    opts = opts || {};
    var dataUrl = opts.dataUrl ? String(opts.dataUrl).trim() : '';
    var imagenUrl = opts.imagen_url ? String(opts.imagen_url).trim() : '';
    if (dataUrl.indexOf('http') === 0 || (dataUrl.indexOf('/') === 0 && dataUrl.indexOf('/photos/') === 0)) {
        imagenUrl = dataUrl;
        dataUrl = '';
    }
    if (!dataUrl && !imagenUrl) {
        iaChatAgregarMensaje('No se pudo enviar la imagen al análisis.', 'bot');
        return;
    }
    var loading = iaChatAgregarLoading('Analizando documento...');
    if (iaChatTtsActivo) {
        iaChatTtsDesbloquear();
        iaChatTtsIniciarKeepAlive();
    }
    var xhr = new XMLHttpRequest();
    xhr.open('POST', IA_CHAT_URL, true);
    xhr.timeout = 130000;
    xhr.onreadystatechange = function () {
        if (xhr.readyState !== 4) return;
        iaChatTtsPararKeepAlive();
        if (loading && loading.parentNode) loading.remove();
        if (xhr.status !== 200) {
            iaChatAgregarMensaje('⚠ Error de conexión al analizar el documento.', 'bot');
            return;
        }
        try {
            var res = JSON.parse(xhr.responseText);
            if (res.ok) {
                if (res.mostrar_adjuntos) iaChatAdjuntoMostrar();
                window.__iaChatUltimoDocumentoAnalisis = {
                    tipo_documento: res.tipo_documento || '',
                    datos: res.datos_documento || {}
                };
                iaChatAgregarRespuestaDocumento(res);
            } else {
                iaChatAgregarMensaje('⚠ ' + (res.error || 'No se pudo analizar el documento.'), 'bot');
            }
        } catch (e) {
            iaChatAgregarMensaje('⚠ Respuesta inválida del servidor.', 'bot');
        }
    };
    xhr.ontimeout = function () {
        iaChatTtsPararKeepAlive();
        if (loading && loading.parentNode) loading.remove();
        iaChatAgregarMensaje('⚠ Tiempo de espera agotado al analizar el documento.', 'bot');
    };
    var fd = new FormData();
    fd.append('accion', 'analizar_documento_chat');
    if (dataUrl) fd.append('imagen_data_url', dataUrl);
    if (imagenUrl) fd.append('imagen_url', imagenUrl);
    xhr.send(fd);
}

function iaChatSubirFotoUniversalConfirmar() {
    var inp = document.getElementById('archivo_foto_universal');
    if (!inp || !inp.files || !inp.files[0]) {
        alert('Selecciona un archivo.');
        return;
    }
    var file = inp.files[0];
    if (file.size > IA_CHAT_ADJUNTO_MAX_BYTES) {
        alert('El archivo supera el máximo de 5 MB.');
        return;
    }
    var ext = (file.name.split('.').pop() || '').toLowerCase();
    var okImg = ['jpg', 'jpeg', 'gif', 'png', 'webp'].indexOf(ext) !== -1;
    var okPdf = ext === 'pdf';
    if (!okImg && !okPdf) {
        alert('Formato no permitido. Usa JPG, JPEG, GIF, PNG o PDF.');
        return;
    }
    iaChatSubirFotoUniversalSpinner(true);
    var modalEl = document.getElementById('modalSubirFotoUniversal');
    function terminarModal() {
        iaChatSubirFotoUniversalSpinner(false);
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal && modalEl) {
            var m = bootstrap.Modal.getInstance(modalEl);
            if (!m) {
                m = bootstrap.Modal.getOrCreateInstance(modalEl);
            }
            m.hide();
        }
        var form = document.getElementById('formSubirFotoUniversal');
        if (form) form.reset();
    }
    if (okPdf) {
        window.__iaChatUltimoAdjunto = { file: file, tipo: 'pdf', dataUrl: null };
        terminarModal();
        iaChatAgregarMensajeAdjuntoUsuario(file, null);
        iaChatAgregarMensaje(
            'El reconocimiento automático del documento solo está disponible para fotos en formato imagen (JPG, PNG, GIF o WebP). '
                + 'Si tienes un PDF, haz una captura o foto de la página del DNI, NIE o pasaporte y súbela.',
            'bot'
        );
        return;
    }
    var reader = new FileReader();
    reader.onload = function() {
        var dataUrl = reader.result;
        window.__iaChatUltimoAdjunto = { file: file, tipo: 'imagen', dataUrl: dataUrl };
        terminarModal();
        iaChatAgregarMensajeAdjuntoUsuario(file, dataUrl);
        iaChatAnalizarDocumentoAdjunto({ dataUrl: dataUrl });
    };
    reader.onerror = function() {
        iaChatSubirFotoUniversalSpinner(false);
        alert('No se pudo leer el archivo.');
    };
    reader.readAsDataURL(file);
}

// ── LECTURA EN VOZ ALTA (TTS — respuestas del asistente) ─────────────────────

var iaChatTtsActivo = false;
var iaChatTtsHablando = false;
var iaChatTtsDesbloqueado = false;
var iaChatTtsPendiente = null;
var iaChatTtsResumeTimer = null;
var iaChatTtsKeepAliveTimer = null;
var iaChatTtsUtteranceSeq = 0;
var iaChatTtsVozCache = null;

function iaChatHtmlATextoPlano(html) {
    var tmp = document.createElement('div');
    tmp.innerHTML = html || '';
    var tablas = tmp.querySelectorAll('table');
    var notaTabla = tablas.length ? ' Los datos detallados están en la tabla en pantalla.' : '';
    tablas.forEach(function (t) { t.remove(); });
    tmp.querySelectorAll('button, .ia-export-btn, script, style').forEach(function (el) {
        el.remove();
    });
    return (tmp.textContent || tmp.innerText || '').replace(/\s+/g, ' ').trim() + notaTabla;
}

function iaChatTtsSoportado() {
    return !!(window.speechSynthesis && typeof SpeechSynthesisUtterance !== 'undefined');
}

function iaChatTtsActualizarBoton() {
    var btn = document.getElementById('iaChatTts');
    if (!btn) return;
    btn.classList.toggle('ia-chat-tts-activo', iaChatTtsActivo);
    btn.classList.toggle('ia-chat-tts-hablando', iaChatTtsHablando);
    btn.setAttribute('aria-pressed', iaChatTtsActivo ? 'true' : 'false');
    btn.title = iaChatTtsActivo ? 'Desactivar respuesta hablada' : 'Activar respuesta hablada';
    btn.setAttribute('aria-label', btn.title);
}

function iaChatTtsPararResumeInterval() {
    if (iaChatTtsResumeTimer) {
        clearInterval(iaChatTtsResumeTimer);
        iaChatTtsResumeTimer = null;
    }
}

function iaChatTtsPararKeepAlive() {
    if (iaChatTtsKeepAliveTimer) {
        clearInterval(iaChatTtsKeepAliveTimer);
        iaChatTtsKeepAliveTimer = null;
    }
}

function iaChatTtsIniciarKeepAlive() {
    if (iaChatTtsKeepAliveTimer || !window.speechSynthesis) return;
    iaChatTtsKeepAliveTimer = setInterval(function () {
        try {
            speechSynthesis.resume();
        } catch (e) {}
    }, 300);
}

function iaChatTtsIniciarResumeInterval() {
    if (iaChatTtsResumeTimer || !window.speechSynthesis) return;
    iaChatTtsResumeTimer = setInterval(function () {
        if (!iaChatTtsHablando || !window.speechSynthesis) return;
        try {
            if (speechSynthesis.paused) {
                speechSynthesis.resume();
            }
        } catch (e) {}
    }, 250);
}

function iaChatDetenerLectura() {
    iaChatTtsPararResumeInterval();
    iaChatTtsPararKeepAlive();
    iaChatTtsUtteranceSeq++;
    if (window.speechSynthesis) {
        try {
            speechSynthesis.cancel();
        } catch (e) {}
    }
    iaChatTtsHablando = false;
    iaChatTtsActualizarBoton();
}

function iaChatTtsEsVozEspanola(voz) {
    return !!(voz && voz.lang && String(voz.lang).toLowerCase().indexOf('es') === 0);
}

function iaChatTtsCadenaVoz(voz) {
    return (String(voz.voiceURI || '') + ' ' + String(voz.name || '')).toLowerCase();
}

function iaChatTtsEsArgentina(voz) {
    var lang = String(voz.lang || '').toLowerCase().replace('_', '-');
    if (lang.indexOf('es-ar') === 0) return true;
    return iaChatTtsCadenaVoz(voz).indexOf('diego') !== -1;
}

/** Voces de España que suelen sonar más naturales en macOS / iOS / Chrome. */
function iaChatTtsEsPreferidaEspana(voz) {
    var s = iaChatTtsCadenaVoz(voz);
    return /jorge|mónica|monica|\.monica|helena|enrique|lucia|lucía|\.lucia|google español|google spanish|español \(españa\)|spanish \(spain\)/.test(s);
}

/**
 * Español fluido (preferencia es-ES). Evita argentino (Diego / es-AR).
 * Si no hay candidata clara, null → el navegador usa la voz por defecto del sistema.
 */
function iaChatTtsElegirVozEspanol() {
    if (!window.speechSynthesis) return iaChatTtsVozCache || null;
    var voces = speechSynthesis.getVoices();
    if (!voces || !voces.length) return iaChatTtsVozCache || null;

    var i, v, lang;
    var candidatas = [];

    for (i = 0; i < voces.length; i++) {
        v = voces[i];
        if (!iaChatTtsEsVozEspanola(v) || iaChatTtsEsArgentina(v)) continue;
        candidatas.push(v);
    }

    for (i = 0; i < candidatas.length; i++) {
        v = candidatas[i];
        lang = String(v.lang || '').toLowerCase().replace('_', '-');
        if (lang.indexOf('es-es') === 0 && iaChatTtsEsPreferidaEspana(v)) {
            iaChatTtsVozCache = v;
            return v;
        }
    }
    for (i = 0; i < candidatas.length; i++) {
        v = candidatas[i];
        lang = String(v.lang || '').toLowerCase().replace('_', '-');
        if (lang.indexOf('es-es') === 0 && v.localService) {
            iaChatTtsVozCache = v;
            return v;
        }
    }
    for (i = 0; i < candidatas.length; i++) {
        v = candidatas[i];
        lang = String(v.lang || '').toLowerCase().replace('_', '-');
        if (lang.indexOf('es-es') === 0) {
            iaChatTtsVozCache = v;
            return v;
        }
    }
    for (i = 0; i < candidatas.length; i++) {
        if (iaChatTtsEsPreferidaEspana(candidatas[i])) {
            iaChatTtsVozCache = candidatas[i];
            return candidatas[i];
        }
    }
    for (i = 0; i < candidatas.length; i++) {
        if (candidatas[i].localService) {
            iaChatTtsVozCache = candidatas[i];
            return candidatas[i];
        }
    }
    if (candidatas.length) {
        iaChatTtsVozCache = candidatas[0];
        return candidatas[0];
    }

    return iaChatTtsVozCache || null;
}

function iaChatTtsRefrescarVoces() {
    try {
        speechSynthesis.getVoices();
    } catch (e) {}
    return iaChatTtsElegirVozEspanol();
}

/** Safari iOS: getVoices() suele ir vacío hasta tras un gesto del usuario. */
function iaChatTtsEsperarVoces(callback) {
    var intentos = 0;
    function tick() {
        intentos++;
        iaChatTtsRefrescarVoces();
        var hayLista = false;
        try {
            var voces = speechSynthesis.getVoices();
            hayLista = !!(voces && voces.length);
        } catch (e) {}
        if (hayLista || intentos >= 20) {
            callback(iaChatTtsElegirVozEspanol());
            return;
        }
        setTimeout(tick, 100);
    }
    if (typeof speechSynthesis.addEventListener === 'function') {
        var onVoices = function () {
            speechSynthesis.removeEventListener('voiceschanged', onVoices);
            tick();
        };
        speechSynthesis.addEventListener('voiceschanged', onVoices);
    } else if ('onvoiceschanged' in speechSynthesis) {
        speechSynthesis.onvoiceschanged = function () {
            speechSynthesis.onvoiceschanged = null;
            tick();
        };
    }
    tick();
}

function iaChatTtsAplicarVoz(u) {
    var voz = iaChatTtsElegirVozEspanol();
    if (voz) {
        u.voice = voz;
        u.lang = voz.lang || 'es-ES';
    } else {
        // Sin forzar voz concreta: el navegador usa la predeterminada del sistema
        u.lang = 'es-ES';
    }
    u.rate = 1;
    u.pitch = 1;
    u.volume = 1;
    return voz;
}

/** Desbloquea TTS tras gesto del usuario (imprescindible en iOS/Safari). */
function iaChatTtsDesbloquear() {
    if (!iaChatTtsSoportado()) return;
    iaChatTtsDesbloqueado = true;
    try {
        speechSynthesis.cancel();
        speechSynthesis.resume();
    } catch (e) {}
    try {
        var silencio = new SpeechSynthesisUtterance('\u200B');
        silencio.volume = 0.01;
        silencio.rate = 2;
        iaChatTtsAplicarVoz(silencio);
        speechSynthesis.speak(silencio);
    } catch (e2) {}
    if (iaChatTtsPendiente) {
        var pendiente = iaChatTtsPendiente;
        iaChatTtsPendiente = null;
        setTimeout(function () {
            iaChatLeerTexto(pendiente, true);
        }, 120);
    }
}

function iaChatTtsCrearUtterance(texto) {
    var max = 1400;
    var t = texto.length > max ? texto.slice(0, max) + '…' : texto;
    var u = new SpeechSynthesisUtterance(t);
    iaChatTtsAplicarVoz(u);
    return u;
}

function iaChatTtsEjecutarLectura(texto, seq, forzar) {
    if (!forzar && !iaChatTtsActivo) return;
    if (seq !== iaChatTtsUtteranceSeq) return;

    var u = iaChatTtsCrearUtterance(texto);
    var inicioOk = false;

    u.onstart = function () {
        inicioOk = true;
        iaChatTtsHablando = true;
        iaChatTtsActualizarBoton();
        iaChatTtsIniciarResumeInterval();
    };
    u.onend = function () {
        if (seq !== iaChatTtsUtteranceSeq) return;
        iaChatTtsPararResumeInterval();
        iaChatTtsHablando = false;
        iaChatTtsActualizarBoton();
    };
    u.onerror = function () {
        if (seq !== iaChatTtsUtteranceSeq) return;
        iaChatTtsPararResumeInterval();
        iaChatTtsHablando = false;
        iaChatTtsActualizarBoton();
    };

    try {
        speechSynthesis.resume();
        speechSynthesis.speak(u);
    } catch (e) {
        iaChatTtsHablando = false;
        iaChatTtsActualizarBoton();
        return;
    }

    setTimeout(function () {
        if (seq !== iaChatTtsUtteranceSeq || inicioOk) return;
        try {
            speechSynthesis.cancel();
            speechSynthesis.resume();
            var u2 = iaChatTtsCrearUtterance(texto);
            u2.onstart = u.onstart;
            u2.onend = u.onend;
            u2.onerror = u.onerror;
            speechSynthesis.speak(u2);
        } catch (e2) {}
    }, 600);
}

function iaChatLeerTexto(texto, forzar) {
    if (!texto || !iaChatTtsSoportado()) return;
    if (!forzar && !iaChatTtsActivo) return;
    if (!iaChatTtsDesbloqueado && !forzar) {
        iaChatTtsPendiente = texto;
        return;
    }

    iaChatDetenerLectura();
    var seq = ++iaChatTtsUtteranceSeq;

    iaChatTtsEsperarVoces(function () {
        setTimeout(function () {
            iaChatTtsEjecutarLectura(texto, seq, forzar);
        }, 80);
    });
}

function iaChatLeerSiActivo(textoHtml) {
    if (!iaChatTtsActivo) return;
    iaChatTtsPararKeepAlive();
    var plano = iaChatHtmlATextoPlano(textoHtml);
    if (!plano) return;
    iaChatLeerTexto(plano, false);
}

function iaChatToggleTts() {
    iaChatTtsActivo = !iaChatTtsActivo;
    try {
        localStorage.setItem('ia_chat_tts_activo', iaChatTtsActivo ? '1' : '0');
    } catch (e) {}
    if (!iaChatTtsActivo) {
        iaChatDetenerLectura();
        iaChatTtsPendiente = null;
    } else {
        iaChatTtsDesbloquear();
        iaChatTtsEsperarVoces(function () {
            iaChatLeerTexto('Modo voz activado.', true);
        });
    }
    iaChatTtsActualizarBoton();
}

function iaChatInicializarTts() {
    var btn = document.getElementById('iaChatTts');
    if (!btn) return;
    if (!iaChatTtsSoportado()) {
        btn.disabled = true;
        btn.title = 'Respuesta hablada no disponible en este navegador';
        btn.setAttribute('aria-label', btn.title);
        return;
    }
    try {
        if (localStorage.getItem('ia_chat_tts_activo') === '1') {
            iaChatTtsActivo = true;
        }
    } catch (e) {}
    iaChatTtsDesbloqueado = false;
    iaChatTtsActualizarBoton();

    function onTtsPointer() {
        try {
            speechSynthesis.resume();
            iaChatTtsRefrescarVoces();
        } catch (e) {}
    }
    btn.addEventListener('pointerdown', onTtsPointer, { passive: true });
    btn.addEventListener('click', function () {
        iaChatToggleTts();
    });

    function cargarVoces() {
        iaChatTtsRefrescarVoces();
    }
    cargarVoces();
    if (typeof speechSynthesis.addEventListener === 'function') {
        speechSynthesis.addEventListener('voiceschanged', cargarVoces);
    } else if ('onvoiceschanged' in speechSynthesis) {
        speechSynthesis.onvoiceschanged = cargarVoces;
    }
}

// ── VOZ (Web Speech API) y teclado móvil ────────────────────────────────────

var iaChatReconocimientoVoz = null;
var iaChatMicEscuchando = false;

function iaChatOcultarTeclado() {
    var input = document.getElementById('iaChatInput');
    if (input) {
        input.blur();
    }
    if (document.activeElement && document.activeElement !== document.body) {
        try {
            document.activeElement.blur();
        } catch (e) {}
    }
}

function iaChatMicActualizarEstado(escuchando) {
    iaChatMicEscuchando = !!escuchando;
    var btn = document.getElementById('iaChatMic');
    if (!btn) return;
    btn.classList.toggle('ia-chat-mic-activo', iaChatMicEscuchando);
    btn.setAttribute('aria-pressed', iaChatMicEscuchando ? 'true' : 'false');
    btn.title = iaChatMicEscuchando ? 'Detener dictado' : 'Dictar por voz';
}

function iaChatDetenerVoz() {
    if (!iaChatReconocimientoVoz) {
        iaChatMicActualizarEstado(false);
        return;
    }
    try {
        iaChatReconocimientoVoz.stop();
    } catch (e) {}
    iaChatMicActualizarEstado(false);
}

function iaChatIniciarVoz() {
    if (!iaChatReconocimientoVoz) {
        alert('Tu navegador no admite dictado por voz. Prueba con Chrome, Edge o Safari en HTTPS.');
        return;
    }
    try {
        iaChatReconocimientoVoz.start();
        iaChatMicActualizarEstado(true);
    } catch (e) {
        if (e && e.name === 'InvalidStateError') {
            iaChatDetenerVoz();
            try {
                iaChatReconocimientoVoz.start();
                iaChatMicActualizarEstado(true);
            } catch (e2) {
                iaChatMicActualizarEstado(false);
            }
        } else {
            iaChatMicActualizarEstado(false);
        }
    }
}

function iaChatToggleVoz() {
    if (iaChatMicEscuchando) {
        iaChatDetenerVoz();
    } else {
        iaChatIniciarVoz();
    }
}

function iaChatInicializarReconocimientoVoz() {
    var SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
    var btnMic = document.getElementById('iaChatMic');
    if (!btnMic) return;

    if (!SpeechRecognition) {
        btnMic.disabled = true;
        btnMic.title = 'Dictado por voz no disponible en este navegador';
        return;
    }

    var rec = new SpeechRecognition();
    rec.continuous = false;
    rec.interimResults = true;
    rec.maxAlternatives = 1;
    rec.lang = 'es-ES';

    rec.onresult = function (event) {
        var input = document.getElementById('iaChatInput');
        if (!input) return;
        var finalTxt = '';
        var interim = '';
        for (var i = event.resultIndex; i < event.results.length; i++) {
            var t = event.results[i][0].transcript;
            if (event.results[i].isFinal) {
                finalTxt += t;
            } else {
                interim += t;
            }
        }
        if (finalTxt) {
            var base = input.value.replace(/\s+$/, '');
            input.value = (base ? base + ' ' : '') + finalTxt.trim();
            iaChatActualizarSendActivo();
        } else if (interim && !input.value) {
            input.value = interim.trim();
            iaChatActualizarSendActivo();
        }
    };

    rec.onerror = function (ev) {
        iaChatMicActualizarEstado(false);
        if (!ev || ev.error === 'aborted' || ev.error === 'no-speech') {
            return;
        }
        if (ev.error === 'not-allowed' || ev.error === 'service-not-allowed') {
            alert('Permiso de micrófono denegado. Actívalo en los ajustes del navegador o del dispositivo.');
        } else if (ev.error === 'network') {
            alert('Error de red al transcribir. Comprueba la conexión.');
        }
    };

    rec.onend = function () {
        iaChatMicActualizarEstado(false);
    };

    iaChatReconocimientoVoz = rec;
    btnMic.addEventListener('click', function () {
        iaChatToggleVoz();
    });
}

// ── ESTADO VISUAL DEL BOTÓN ENVIAR (hay texto en el input) ─────────────────

function iaChatActualizarSendActivo() {
    var input = document.getElementById('iaChatInput');
    var btn   = document.getElementById('iaChatEnviar');
    if (!input || !btn) return;
    if (input.value.length > 0) {
        btn.classList.add('ia-chat-send-activo');
    } else {
        btn.classList.remove('ia-chat-send-activo');
    }
}

// ── ENVIAR MENSAJE ──────────────────────────────────────────────────────────
// ELIMINA esta línea:
var iaChatHistorial = [];

function iaChatEsDispositivoTactil() {
    try {
        return window.matchMedia('(hover: none) and (pointer: coarse)').matches
            || window.matchMedia('(max-width: 991.98px)').matches;
    } catch (e) {
        return /Android|iPhone|iPad|iPod/i.test(navigator.userAgent || '');
    }
}

/** En móvil: blur del input para ocultar el teclado virtual. */
function iaChatOcultarTecladoMovil() {
    var input = document.getElementById('iaChatInput');
    if (input) {
        input.blur();
    }
    if (document.activeElement && typeof document.activeElement.blur === 'function') {
        document.activeElement.blur();
    }
}

function iaChatEnfocarInputSiEscritorio() {
    if (iaChatEsDispositivoTactil()) {
        iaChatOcultarTecladoMovil();
        return;
    }
    var input = document.getElementById('iaChatInput');
    if (input) {
        input.focus();
    }
}

// Y deja iaChatEnviar así:
function iaChatEnviar() {
    var input    = document.getElementById('iaChatInput');
    if (!input) return;
    var pregunta = input.value.trim();
    if (!pregunta) return;

    // Al pulsar enviar en el teléfono, cerrar el teclado virtual.
    iaChatOcultarTecladoMovil();

    iaChatDetenerVoz();
    if (iaChatTtsActivo) {
        iaChatTtsDesbloquear();
    }

    // Detectar si pide limpiar el chat
    var p = pregunta.toLowerCase();
    if (p.indexOf('limpiar') !== -1 || p.indexOf('borrar chat') !== -1 || p.indexOf('reiniciar') !== -1) {
        var fdLimpiar = new FormData();
        fdLimpiar.append('accion', 'limpiar_historial');
        var xhrLimpiar = new XMLHttpRequest();
        xhrLimpiar.open('POST', IA_CHAT_URL, true);
        xhrLimpiar.onreadystatechange = function() {
            if (xhrLimpiar.readyState === 4) {
                document.getElementById('iaChatMensajes').innerHTML = iaChatHtmlBienvenida();
                iaChatAdjuntoOcultar();
                iaChatActivaId = '';
                iaChatGuardarLocal(iaChatObtenerHtmlLimpio(), '');
                iaChatProgramarGuardado();
            }
        };
        xhrLimpiar.send(fdLimpiar);
        input.value = '';
        iaChatActualizarSendActivo();
        iaChatEnfocarInputSiEscritorio();
        return;
    }

    if (iaChatEsSoloFlujoAdjuntoDocumentacion(pregunta)) {
        iaChatAdjuntoMostrar();
    }

    iaChatAgregarMensaje(pregunta, 'user');
    input.value = '';
    iaChatActualizarSendActivo();
    iaChatEnfocarInputSiEscritorio();

    var loadingEl = iaChatAgregarLoading();
    document.getElementById('iaChatEnviar').disabled = true;
    if (iaChatTtsActivo) {
        iaChatTtsIniciarKeepAlive();
    }

    var xhr = new XMLHttpRequest();
    xhr.open('POST', IA_CHAT_URL, true);
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4) {
            iaChatTtsPararKeepAlive();
            loadingEl.remove();
            document.getElementById('iaChatEnviar').disabled = false;
            iaChatEnfocarInputSiEscritorio();
            if (xhr.status === 200) {
                try {
                    var res = JSON.parse(xhr.responseText);
                    if (res.ok) {
                        if (res.mostrar_adjuntos) {
                            iaChatAdjuntoMostrar();
                        }
                        iaChatAgregarRespuesta(res);
                    } else {
                        iaChatAgregarMensaje('⚠ ' + (res.error || 'Error desconocido'), 'bot');
                    }
                } catch(e) {
                    iaChatAgregarMensaje('⚠ Error al procesar la respuesta.', 'bot');
                }
            } else {
                iaChatAgregarMensaje('⚠ Error de conexión (' + xhr.status + ').', 'bot');
            }
        }
    };

    var fd = new FormData();
    fd.append('accion',   'chat');
    fd.append('pregunta', pregunta);
    xhr.send(fd);
}

// ── AGREGAR MENSAJE SIMPLE ──────────────────────────────────────────────────

function iaChatAgregarMensaje(texto, tipo) {
    var zona = document.getElementById('iaChatMensajes');
    var div  = document.createElement('div');
    div.className = 'ia-msg ia-msg-' + tipo;
    var hora = new Date().toLocaleTimeString('es-ES', {hour:'2-digit', minute:'2-digit'});
    div.innerHTML =
        '<div class="ia-msg-burbuja">' + texto + '</div>' +
        '<div class="ia-msg-hora">' + hora + '</div>';
    zona.appendChild(div);
    iaChatScrollAlFinal();
    if (tipo === 'bot') {
        iaChatLeerSiActivo(texto);
    }
    iaChatProgramarGuardado();
    return div;
}

// ── LOADING ANIMADO ─────────────────────────────────────────────────────────

function iaChatAgregarLoading(mensaje) {
    var zona = document.getElementById('iaChatMensajes');
    var div  = document.createElement('div');
    div.className = 'ia-msg ia-msg-bot';
    var txt = mensaje && String(mensaje).trim() ? String(mensaje).trim() : 'Consultando...';
    div.innerHTML =
        '<div class="ia-loading">' +
            '<div class="ia-dot"></div>' +
            '<div class="ia-dot"></div>' +
            '<div class="ia-dot"></div>' +
            '&nbsp;' + txt.replace(/</g, '&lt;') +
        '</div>';
    zona.appendChild(div);
    iaChatScrollAlFinal();
    return div;
}

// ── AGREGAR RESPUESTA CON TABLA + EXPORT ────────────────────────────────────

function iaChatTextoSinSql(html) {
    if (!html) return html || '';
    var s = String(html);
    s = s.replace(/```(?:sql|mysql)?[\s\S]*?```/gi, '');
    s = s.replace(/\bSELECT\b[\s\S]*?(?=<br|<\/|$)/gi, '');
    return s.trim();
}

function iaChatAgregarRespuesta(res) {
    var zona = document.getElementById('iaChatMensajes');
    var div  = document.createElement('div');
    div.className = 'ia-msg ia-msg-bot';
    var hora = new Date().toLocaleTimeString('es-ES', {hour:'2-digit', minute:'2-digit'});

    var html = '<div class="ia-msg-burbuja">';

    // Texto respuesta Claude (nunca mostrar SQL al usuario)
    html += iaChatTextoSinSql(res.texto);

    var sqlCrudo    = (res.sql || res.sql_crudo || '').trim();

    // Tabla + export: el SQL en onclick rompe con comillas/saltos de línea; se guarda en sessionStorage
    if (res.filas && res.filas.length > 0) {
        var tablaId = 'iaTabla_' + Date.now();

        if (sqlCrudo) {
            var exportKey = 'ia_chat_sql_' + Date.now() + '_' + Math.random().toString(36).slice(2, 10);
            try {
                sessionStorage.setItem(exportKey, sqlCrudo);
            } catch (err) {
                exportKey = '';
            }
            if (exportKey) {
                html += '<div class="d-flex gap-2 mt-2 mb-1 flex-wrap">';
                html += '<button type="button" class="btn rounded-pill btn-icon btn-success waves-effect waves-light ia-export-btn" data-ia-export="excel" data-ia-export-key="' +
                    exportKey + '"><i class="icon-base ri ri-file-excel-2-fill icon-20px"></i></button>';
                html += '<button type="button" class="btn rounded-pill btn-icon btn-danger waves-effect waves-light ia-export-btn" data-ia-export="pdf" data-ia-export-key="' +
                    exportKey + '"><i class="icon-base ri ri-file-pdf-2-line icon-20px"></i></button>';
                html += '</div>';
            }
        }

        // Tabla datos
        html += '<div class="table-responsive">';
        html += '<table class="table table-sm ia-tabla" id="' + tablaId + '">';
        html += '<thead><tr>';
        var cols = Object.keys(res.filas[0]);
        var cab = (res.cabeceras_tabla && typeof res.cabeceras_tabla === 'object') ? res.cabeceras_tabla : {};
        for (var i = 0; i < cols.length; i++) {
            var clave = cols[i];
            var titulo = cab.hasOwnProperty(clave) ? cab[clave] : clave;
            html += '<th>' + titulo + '</th>';
        }
        html += '</tr></thead><tbody>';
        for (var r = 0; r < res.filas.length; r++) {
            html += '<tr>';
            for (var c = 0; c < cols.length; c++) {
                var val = res.filas[r][cols[c]];
                html += '<td>' + (val !== null && val !== undefined ? val : '&mdash;') + '</td>';
            }
            html += '</tr>';
        }
        html += '</tbody></table></div>';

        if (res.total > res.filas.length) {
            html += '<small class="text-muted">Mostrando ' + res.filas.length + ' de ' + res.total + ' registros</small>';
        }
    }

    html += '</div>';
    html += '<div class="ia-msg-hora">' + hora + '</div>';

    div.innerHTML = html;
    zona.appendChild(div);
    iaChatScrollAlFinal();
    iaChatLeerSiActivo(res.texto || '');
    iaChatProgramarGuardado();
}

// ── EXPORT EXCEL (.xlsx servidor) ───────────────────────────────────────────

function iaExportExcel(sql) {
    var form = document.createElement('form');
    form.method = 'POST';
    form.action = IA_CHAT_URL;
    form.target = '_blank';
    form.style.display = 'none';

    var campos = {accion: 'export_excel', sql: sql, titulo: 'informe'};
    for (var k in campos) {
        var input = document.createElement('input');
        input.type = 'hidden';
        input.name = k;
        input.value = campos[k];
        form.appendChild(input);
    }

    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
}

// ── EXPORT PDF (HTML imprimible servidor) ───────────────────────────────────

function iaExportPDF(sql) {
    var form = document.createElement('form');
    form.method = 'POST';
    form.action = IA_CHAT_URL;
    form.target = '_blank';
    form.style.display = 'none';

    var campos = {accion: 'export_pdf', sql: sql, titulo: 'Informe'};
    for (var k in campos) {
        var input = document.createElement('input');
        input.type = 'hidden';
        input.name = k;
        input.value = campos[k];
        form.appendChild(input);
    }

    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
}

(function iaChatBindExportButtons() {
    var zona = document.getElementById('iaChatMensajes');
    if (!zona || zona.getAttribute('data-ia-export-bound') === '1') return;
    zona.setAttribute('data-ia-export-bound', '1');
    zona.addEventListener('click', function(e) {
        var btn = e.target.closest('.ia-export-btn');
        if (!btn) return;
        var key = btn.getAttribute('data-ia-export-key');
        var tipo = btn.getAttribute('data-ia-export');
        if (!key || !tipo) return;
        var sql = '';
        try {
            sql = sessionStorage.getItem(key) || '';
        } catch (x) {}
        if (!sql) return;
        if (tipo === 'excel') iaExportExcel(sql);
        else if (tipo === 'pdf') iaExportPDF(sql);
    });
})();

// ── ENTER PARA ENVIAR ───────────────────────────────────────────────────────

(function iaChatInputInit() {
    var input = document.getElementById('iaChatInput');
    if (!input) return;
    input.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            iaChatEnviar();
        }
    });
    input.addEventListener('input', iaChatActualizarSendActivo);
    iaChatInicializarReconocimientoVoz();
    iaChatInicializarTts();
    iaChatActualizarSendActivo();
})();

(function iaChatModalTecladoInit() {
    var modal = document.getElementById('modalIAChat');
    if (!modal) return;

    modal.addEventListener('shown.bs.modal', function () {
        iaChatRestaurarConversacion();
        iaChatScrollAlFinal();
        if (iaChatTtsActivo) {
            iaChatTtsDesbloquear();
        }
        var input = document.getElementById('iaChatInput');
        if (input && !iaChatEsDispositivoTactil()) input.focus();
    });
    modal.addEventListener('hidden.bs.modal', function () {
        iaChatDetenerVoz();
        iaChatDetenerLectura();
        iaChatTtsDesbloqueado = false;
        iaChatTtsPendiente = null;
        iaChatOcultarTeclado();
        iaChatSalirFullscreen();
        iaChatGuardarConversacionAhora(true);
    });
    var btnEnviar = document.getElementById('iaChatEnviar');
    if (btnEnviar) {
        btnEnviar.addEventListener('pointerdown', function () {
            if (iaChatTtsActivo) iaChatTtsDesbloquear();
        }, { passive: true });
    }
})();

// ── CONVERSACIONES PERSISTENTES (entre páginas + al cerrar sesión) ───────────

var iaChatGuardadoTimer = null;
var iaChatActivaId = '';
var iaChatRestaurando = false;

function iaChatStorageKey() {
    var uid = (typeof IA_CHAT_USUARIO_ID !== 'undefined') ? String(IA_CHAT_USUARIO_ID) : '0';
    return 'ia_chat_ui_v1_' + uid;
}

function iaChatHtmlBienvenida() {
    var nombre = (typeof IA_CHAT_USUARIO_NOMBRE !== 'undefined' && IA_CHAT_USUARIO_NOMBRE)
        ? String(IA_CHAT_USUARIO_NOMBRE)
        : '';
    return ''
        + '<div class="ia-msg ia-msg-bot">'
        +   '<div class="ia-msg-burbuja">'
        +     '👋 Hola, <span class="ia-chat-nombre-usuario">' + nombre.replace(/</g, '&lt;') + '</span> <br> ¿Qué quieres saber?'
        +   '</div>'
        + '</div>';
}

function iaChatObtenerHtmlLimpio() {
    var zona = document.getElementById('iaChatMensajes');
    if (!zona) return '';
    var clone = zona.cloneNode(true);
    var loadings = clone.querySelectorAll('.ia-loading');
    for (var i = 0; i < loadings.length; i++) {
        var msg = loadings[i].closest('.ia-msg');
        if (msg && msg.parentNode) msg.parentNode.removeChild(msg);
    }
    return clone.innerHTML;
}

function iaChatTieneMensajesUsuario(html) {
    return String(html || '').indexOf('ia-msg-user') !== -1;
}

function iaChatGuardarLocal(html, activaId) {
    try {
        localStorage.setItem(iaChatStorageKey(), JSON.stringify({
            activa_id: activaId || iaChatActivaId || '',
            html: html || '',
            ts: Date.now()
        }));
    } catch (e) {}
}

function iaChatLeerLocal() {
    try {
        var raw = localStorage.getItem(iaChatStorageKey());
        if (!raw) return null;
        var data = JSON.parse(raw);
        return data && typeof data === 'object' ? data : null;
    } catch (e) {
        return null;
    }
}

function iaChatPintarHtml(html) {
    var zona = document.getElementById('iaChatMensajes');
    if (!zona) return;
    iaChatRestaurando = true;
    if (html && String(html).trim() !== '') {
        zona.innerHTML = html;
    } else {
        zona.innerHTML = iaChatHtmlBienvenida();
    }
    iaChatRestaurando = false;
    iaChatScrollAlFinal();
}

function iaChatRenderListaConversaciones(lista) {
    var menu = document.getElementById('iaChatHistMenu');
    if (!menu) return;
    var convs = (lista && lista.conversaciones) ? lista.conversaciones : [];
    if (!convs.length) {
        menu.innerHTML = '<li><span class="dropdown-item-text text-muted small">Sin conversaciones guardadas</span></li>';
        return;
    }
    var html = '';
    for (var i = 0; i < convs.length; i++) {
        var c = convs[i];
        var titulo = (c.titulo || 'Conversación').replace(/</g, '&lt;');
        var fecha = c.actualizada ? String(c.actualizada) : '';
        var activo = c.activa ? ' active' : '';
        html += '<li><button type="button" class="dropdown-item' + activo + '" data-ia-conv-id="' +
            String(c.id || '').replace(/"/g, '') + '">' +
            titulo +
            (fecha ? '<span class="ia-hist-fecha">' + fecha.replace(/</g, '&lt;') + '</span>' : '') +
            '</button></li>';
    }
    menu.innerHTML = html;
}

function iaChatProgramarGuardado() {
    if (iaChatRestaurando) return;
    var html = iaChatObtenerHtmlLimpio();
    iaChatGuardarLocal(html, iaChatActivaId);
    if (iaChatGuardadoTimer) {
        clearTimeout(iaChatGuardadoTimer);
    }
    iaChatGuardadoTimer = setTimeout(function () {
        iaChatGuardarConversacionAhora(false);
    }, 600);
}

function iaChatGuardarConversacionAhora(sync) {
    var html = iaChatObtenerHtmlLimpio();
    iaChatGuardarLocal(html, iaChatActivaId);
    if (!IA_CHAT_URL || !(typeof IA_CHAT_USUARIO_ID !== 'undefined' && IA_CHAT_USUARIO_ID > 0)) {
        return;
    }
    var fd = new FormData();
    fd.append('accion', 'conv_guardar');
    fd.append('html', html);
    if (sync && navigator.sendBeacon) {
        try {
            navigator.sendBeacon(IA_CHAT_URL, fd);
            return;
        } catch (e) {}
    }
    var xhr = new XMLHttpRequest();
    xhr.open('POST', IA_CHAT_URL, true);
    xhr.onreadystatechange = function () {
        if (xhr.readyState !== 4) return;
        try {
            var res = JSON.parse(xhr.responseText);
            if (res && res.ok) {
                if (res.activa_id) iaChatActivaId = res.activa_id;
                if (res.lista) iaChatRenderListaConversaciones(res.lista);
            }
        } catch (e) {}
    };
    xhr.send(fd);
}

window.iaChatPersistirAntesDeLogout = function () {
    try {
        iaChatGuardarConversacionAhora(true);
    } catch (e) {}
};

function iaChatRestaurarConversacion() {
    var local = iaChatLeerLocal();
    if (local && local.html && iaChatTieneMensajesUsuario(local.html)) {
        iaChatActivaId = local.activa_id || '';
        iaChatPintarHtml(local.html);
    }

    if (!IA_CHAT_URL) return;
    var fd = new FormData();
    fd.append('accion', 'conv_obtener_activa');
    var xhr = new XMLHttpRequest();
    xhr.open('POST', IA_CHAT_URL, true);
    xhr.onreadystatechange = function () {
        if (xhr.readyState !== 4) return;
        try {
            var res = JSON.parse(xhr.responseText);
            if (!res || !res.ok) return;
            if (res.lista) iaChatRenderListaConversaciones(res.lista);
            var c = res.conversacion || null;
            if (c && c.id) iaChatActivaId = c.id;
            var htmlSrv = c && c.html ? c.html : '';
            var local2 = iaChatLeerLocal();
            var htmlLocal = local2 && local2.html ? local2.html : '';
            // Preferir la copia más completa (con mensajes de usuario)
            if (iaChatTieneMensajesUsuario(htmlSrv)) {
                if (!iaChatTieneMensajesUsuario(htmlLocal) || String(htmlSrv).length >= String(htmlLocal).length) {
                    iaChatPintarHtml(htmlSrv);
                    iaChatGuardarLocal(htmlSrv, iaChatActivaId);
                }
            } else if (!iaChatTieneMensajesUsuario(htmlLocal)) {
                iaChatPintarHtml(iaChatHtmlBienvenida());
            }
        } catch (e) {}
    };
    xhr.send(fd);
}

function iaChatNuevaConversacion() {
    var html = iaChatObtenerHtmlLimpio();
    var fd = new FormData();
    fd.append('accion', 'conv_nueva');
    fd.append('html', html);
    var xhr = new XMLHttpRequest();
    xhr.open('POST', IA_CHAT_URL, true);
    xhr.onreadystatechange = function () {
        if (xhr.readyState !== 4) return;
        try {
            var res = JSON.parse(xhr.responseText);
            if (!res || !res.ok) return;
            if (res.conversacion && res.conversacion.id) {
                iaChatActivaId = res.conversacion.id;
            }
            iaChatPintarHtml(iaChatHtmlBienvenida());
            iaChatGuardarLocal(iaChatObtenerHtmlLimpio(), iaChatActivaId);
            if (res.lista) iaChatRenderListaConversaciones(res.lista);
            iaChatAdjuntoOcultar();
            iaChatActualizarSendActivo();
        } catch (e) {}
    };
    xhr.send(fd);
}

function iaChatCargarConversacion(id) {
    if (!id) return;
    var fd = new FormData();
    fd.append('accion', 'conv_cargar');
    fd.append('id', id);
    // Guardar la actual antes de cambiar
    iaChatGuardarConversacionAhora(false);
    var xhr = new XMLHttpRequest();
    xhr.open('POST', IA_CHAT_URL, true);
    xhr.onreadystatechange = function () {
        if (xhr.readyState !== 4) return;
        try {
            var res = JSON.parse(xhr.responseText);
            if (!res || !res.ok || !res.conversacion) return;
            iaChatActivaId = res.conversacion.id || id;
            var html = res.conversacion.html || '';
            iaChatPintarHtml(iaChatTieneMensajesUsuario(html) ? html : iaChatHtmlBienvenida());
            iaChatGuardarLocal(iaChatObtenerHtmlLimpio(), iaChatActivaId);
            if (res.lista) iaChatRenderListaConversaciones(res.lista);
            iaChatAdjuntoOcultar();
        } catch (e) {}
    };
    xhr.send(fd);
}

(function iaChatConversacionesInit() {
    var btnNueva = document.getElementById('iaChatNuevaConv');
    if (btnNueva) {
        btnNueva.addEventListener('click', function (e) {
            e.preventDefault();
            iaChatNuevaConversacion();
        });
    }
    var menu = document.getElementById('iaChatHistMenu');
    if (menu) {
        menu.addEventListener('click', function (e) {
            var btn = e.target.closest('[data-ia-conv-id]');
            if (!btn) return;
            e.preventDefault();
            iaChatCargarConversacion(btn.getAttribute('data-ia-conv-id'));
        });
    }
    // Restaurar al cargar la página (aunque el modal esté cerrado)
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            var local = iaChatLeerLocal();
            if (local && local.html && iaChatTieneMensajesUsuario(local.html)) {
                iaChatActivaId = local.activa_id || '';
                iaChatPintarHtml(local.html);
            }
        });
    } else {
        var local = iaChatLeerLocal();
        if (local && local.html && iaChatTieneMensajesUsuario(local.html)) {
            iaChatActivaId = local.activa_id || '';
            iaChatPintarHtml(local.html);
        }
    }
    window.addEventListener('pagehide', function () {
        iaChatGuardarConversacionAhora(true);
    });
})();

// ── PANTALLA COMPLETA DENTRO DEL BODY (sin Fullscreen API del SO) ────────────

function iaChatEstaFullscreen() {
    var modal = document.getElementById('modalIAChat');
    return !!(modal && modal.classList.contains('ia-chat-body-fullscreen'));
}

function iaChatFullscreenActualizarBoton() {
    var btn = document.getElementById('iaChatFullscreen');
    if (!btn) return;
    var activo = iaChatEstaFullscreen();
    var icon = btn.querySelector('i');
    btn.classList.toggle('ia-chat-fullscreen-activo', activo);
    btn.setAttribute('aria-pressed', activo ? 'true' : 'false');
    btn.title = activo ? 'Salir de pantalla completa' : 'Pantalla completa';
    btn.setAttribute('aria-label', btn.title);
    if (icon) {
        icon.className = activo
            ? 'icon-base ri ri-fullscreen-exit-line'
            : 'icon-base ri ri-fullscreen-line';
    }
}

function iaChatSalirFullscreen() {
    var modal = document.getElementById('modalIAChat');
    if (!modal) return;
    modal.classList.remove('ia-chat-body-fullscreen');
    iaChatFullscreenActualizarBoton();
}

function iaChatToggleFullscreen() {
    var modal = document.getElementById('modalIAChat');
    if (!modal) return;
    modal.classList.toggle('ia-chat-body-fullscreen');
    iaChatFullscreenActualizarBoton();
}

(function iaChatFullscreenInit() {
    var btn = document.getElementById('iaChatFullscreen');
    if (!btn) return;
    btn.addEventListener('click', function (e) {
        e.preventDefault();
        iaChatToggleFullscreen();
    });
    iaChatFullscreenActualizarBoton();
})();
