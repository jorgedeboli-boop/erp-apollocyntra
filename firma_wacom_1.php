<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Firma Digital — Wacom STU-540</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@300;400;600&family=DM+Mono:wght@300;400&display=swap" rel="stylesheet" />
  <style>
    :root {
      --ink:    #0d1117; --paper: #f7f4ef; --rule: #d6cfc4;
      --accent: #1a3a5c; --gold: #c8a96e;
      --ok: #2d7a4f; --err: #8b1a1a;
      --mono: 'DM Mono', monospace; --serif: 'Cormorant Garamond', Georgia, serif;
    }
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body { background: var(--paper); color: var(--ink); font-family: var(--serif); min-height: 100vh; display: flex; flex-direction: column; align-items: center; padding: 2rem 1rem 4rem; }
    header { text-align: center; margin-bottom: 2rem; }
    .eyebrow { font-family: var(--mono); font-size: .7rem; letter-spacing: .25em; text-transform: uppercase; color: var(--gold); margin-bottom: .4rem; }
    h1 { font-size: 2.2rem; font-weight: 300; color: var(--accent); }
    h1 strong { font-weight: 600; }

    .card { background: #fff; border: 1px solid var(--rule); border-radius: 4px; box-shadow: 0 2px 20px rgba(13,17,23,.12); width: 100%; max-width: 860px; }
    .card-header { display: flex; align-items: center; justify-content: space-between; padding: .9rem 1.4rem; background: var(--accent); gap: 1rem; }
    .device-label { font-family: var(--mono); font-size: .7rem; color: rgba(255,255,255,.5); letter-spacing: .1em; }
    .device-name  { font-family: var(--mono); font-size: .8rem; color: #fff; }

    #statusPill { display: flex; align-items: center; gap: .4rem; font-family: var(--mono); font-size: .68rem; letter-spacing: .1em; padding: .3rem .8rem; border-radius: 20px; background: rgba(255,255,255,.1); color: rgba(255,255,255,.6); white-space: nowrap; transition: background .3s, color .3s; }
    #statusPill.connected { background: rgba(45,122,79,.35); color: #7ee8a2; }
    #statusPill.error     { background: rgba(139,26,26,.35); color: #f4a0a0; }
    #statusPill.signing   { background: rgba(200,169,110,.25); color: var(--gold); }
    .dot { width: 7px; height: 7px; border-radius: 50%; background: currentColor; flex-shrink: 0; }
    .signing .dot { animation: pulse 1s ease-in-out infinite; }
    @keyframes pulse { 0%,100%{opacity:1} 50%{opacity:.25} }

    .canvas-wrap { position: relative; background: #fafaf8; border-bottom: 1px solid var(--rule); }
    #sigCanvas   { display: block; width: 100%; cursor: crosshair; touch-action: none; }
    .canvas-overlay { position: absolute; inset: 0; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: .5rem; pointer-events: none; transition: opacity .4s; }
    .canvas-overlay .icon { font-size: 2.5rem; opacity: .15; }
    .canvas-overlay p { font-family: var(--mono); font-size: .7rem; letter-spacing: .12em; color: var(--ink); opacity: .25; }
    .has-sig .canvas-overlay { opacity: 0; }
    .baseline { position: absolute; bottom: 22%; left: 6%; right: 6%; border-bottom: 1px solid var(--rule); pointer-events: none; }
    .baseline::before { content: 'Firma'; position: absolute; bottom: 4px; left: 0; font-family: var(--mono); font-size: .58rem; letter-spacing: .15em; color: var(--rule); }

    #pressureBar { position: absolute; top: 8px; right: 10px; display: flex; flex-direction: column; align-items: flex-end; gap: 3px; pointer-events: none; }
    #pressureBar label { font-family: var(--mono); font-size: .58rem; letter-spacing: .1em; color: var(--accent); opacity: .4; }
    #pTrack { width: 6px; height: 60px; background: var(--rule); border-radius: 3px; overflow: hidden; position: relative; }
    #pFill  { position: absolute; bottom: 0; left: 0; right: 0; height: 0%; background: linear-gradient(to top, var(--accent), var(--gold)); border-radius: 3px; transition: height .04s; }

    .controls { display: flex; align-items: center; flex-wrap: wrap; gap: .7rem; padding: 1rem 1.4rem; }
    .left  { display: flex; gap: .7rem; flex: 1; flex-wrap: wrap; }
    .right { display: flex; gap: .7rem; flex-wrap: wrap; }
    button { font-family: var(--mono); font-size: .7rem; letter-spacing: .1em; text-transform: uppercase; border: 1px solid var(--rule); background: #fff; color: var(--ink); padding: .5rem 1rem; border-radius: 3px; cursor: pointer; white-space: nowrap; transition: background .2s, color .2s, border-color .2s, transform .1s; }
    button:hover  { background: var(--ink); color: #fff; border-color: var(--ink); }
    button:active { transform: scale(.97); }
    button:disabled { opacity: .35; cursor: not-allowed; }
    button:disabled:hover { background: #fff; color: var(--ink); border-color: var(--rule); transform: none; }
    .btn-primary { background: var(--accent); color: #fff; border-color: var(--accent); }
    .btn-primary:hover { background: var(--ink); border-color: var(--ink); }
    .btn-primary:disabled:hover { background: var(--accent); border-color: var(--accent); }
    .btn-gold  { background: var(--gold); color: #fff; border-color: var(--gold); }
    .btn-gold:hover  { background: #a6863d; border-color: #a6863d; }
    .btn-gold:disabled:hover { background: var(--gold); border-color: var(--gold); }
    .btn-danger { border-color: var(--err); color: var(--err); }
    .btn-danger:hover { background: var(--err); color: #fff; border-color: var(--err); }

    .info-bar { display: flex; gap: 1.5rem; flex-wrap: wrap; padding: .65rem 1.4rem; border-top: 1px solid var(--rule); font-family: var(--mono); font-size: .63rem; color: #999; letter-spacing: .06em; }
    .info-bar strong { color: var(--ink); font-weight: 400; }

    /* ── Debug ── */
    #debugPanel { margin-top: 1rem; width: 100%; max-width: 860px; border: 1px solid #2a3f55; border-radius: 4px; overflow: hidden; }
    .debug-header { display: flex; align-items: center; justify-content: space-between; padding: .55rem 1rem; background: #1e2a38; cursor: pointer; user-select: none; }
    .dh-title  { font-family: var(--mono); font-size: .68rem; color: #7fa8cc; letter-spacing: .1em; }
    .dh-toggle { font-family: var(--mono); font-size: .65rem; color: #4a6a88; }
    #debugBody.hidden { display: none; }
    #debugLog { font-family: var(--mono); font-size: .64rem; color: #7ee8a2; padding: .8rem 1rem; height: 200px; overflow-y: auto; white-space: pre-wrap; line-height: 1.7; background: #0d1117; }
    .lw { color: #f4a050; } .li { color: #7ec8f4; } .le { color: #f48080; } .ls { color: #7ee8a2; } .lt { color: #f4f47e; font-weight: bold; }
    .debug-bar { display: flex; gap: .5rem; align-items: center; padding: .5rem 1rem; background: #080d14; border-top: 1px solid #1e2a38; flex-wrap: wrap; }
    .debug-bar button { background: #1e2a38; border-color: #2a3f55; color: #7fa8cc; font-size: .61rem; padding: .28rem .65rem; }
    .debug-bar button:hover { background: #2a3f55; color: #fff; border-color: #3a5570; }
    .debug-bar .sep { font-family: var(--mono); font-size: .62rem; color: #2a3f55; margin: 0 .3rem; }

    #toast { position: fixed; bottom: 2rem; right: 2rem; background: var(--ink); color: #fff; font-family: var(--mono); font-size: .7rem; padding: .65rem 1.1rem; border-radius: 3px; box-shadow: 0 4px 20px rgba(0,0,0,.25); opacity: 0; transform: translateY(8px); transition: opacity .3s, transform .3s; pointer-events: none; max-width: 340px; z-index: 999; }
    #toast.show { opacity: 1; transform: translateY(0); }
    #toast.ok { background: var(--ok); } #toast.err { background: var(--err); }
    #hidNotice { background: #fff8ec; border: 1px solid var(--gold); border-radius: 3px; padding: .7rem 1.1rem; font-family: var(--mono); font-size: .68rem; color: #7a5c1e; margin-bottom: 1.2rem; max-width: 860px; width: 100%; display: none; line-height: 1.65; }
    #hidNotice.show { display: block; }
  </style>
</head>
<body>

<header>
  <p class="eyebrow">Captura de Firma Digital</p>
  <h1>Wacom <strong>STU-540</strong></h1>
</header>
<div id="hidNotice">⚠ Esta aplicación requiere <strong>Chrome o Edge 89+</strong> con soporte WebHID.</div>

<div class="card">
  <div class="card-header">
    <div>
      <div class="device-label">Dispositivo</div>
      <div class="device-name" id="deviceName">Sin conectar</div>
    </div>
    <div id="statusPill"><span class="dot"></span><span id="statusText">DESCONECTADO</span></div>
  </div>

  <div class="canvas-wrap" id="canvasWrap">
    <canvas id="sigCanvas"></canvas>
    <div class="baseline"></div>
    <div class="canvas-overlay">
      <div class="icon">✒</div>
      <p>Conecte la tableta y firme sobre ella</p>
    </div>
    <div id="pressureBar">
      <label>PSI</label>
      <div id="pTrack"><div id="pFill"></div></div>
    </div>
  </div>

  <div class="controls">
    <div class="left">
      <button class="btn-primary" id="btnConnect">Conectar Tableta</button>
      <button id="btnDisconnect" disabled>Desconectar</button>
    </div>
    <div class="right">
      <button id="btnMouse">Modo Ratón</button>
      <button class="btn-danger" id="btnClear" disabled>Limpiar</button>
      <button class="btn-gold"  id="btnSave"  disabled>Guardar PNG</button>
    </div>
  </div>

  <div class="info-bar">
    <span>Trazos: <strong id="iStrokes">0</strong></span>
    <span>Puntos: <strong id="iPoints">0</strong></span>
    <span>Presión máx: <strong id="iMaxP">—</strong></span>
    <span>Modo: <strong id="iMode">—</strong></span>
  </div>
</div>

<!-- ── Panel de diagnóstico ── -->
<div id="debugPanel">
  <div class="debug-header" id="debugToggle">
    <span class="dh-title" id="dhTitle">▶ DIAGNÓSTICO HID — RAW BYTES</span>
    <span class="dh-toggle" id="dhToggle">MOSTRAR</span>
  </div>
  <div id="debugBody" class="hidden">
    <div id="debugLog">Conecta la tableta para ver los datos en bruto.\n</div>
    <div class="debug-bar">
      <button id="btnClearLog">Limpiar log</button>
      <button id="btnPause">Pausar</button>
      <span class="sep">|</span>
      <button id="btnOnlyTip">Solo tip=1</button>
      <span class="sep">|</span>
      <span style="font-family:var(--mono);font-size:.62rem;color:#4a6a88;" id="dbCounter">0 reports</span>
    </div>
  </div>
</div>

<div id="toast"></div>

<script>
'use strict';

// ═══════════════════════════════════════════════════════════════════
//  PARSEO — igual que V1 (que funcionaba en Windows)
//
//  WebHID entrega event.data sin el Report ID byte.
//  El STU-540 report ID 0x01 tiene este layout:
//
//    Byte 0        uint8    status
//                           bit 0 = tip  (pluma tocando la pantalla)
//                           bit 1 = barrel (botón lateral, opcional)
//    Bytes 1–2     uint16 LE  X  (0–9600)
//    Bytes 3–4     uint16 LE  Y  (0–6000)
//    Bytes 5–6     uint16 LE  P  (0–511)
//
//  TOTAL mínimo esperado: 7 bytes.
// ═══════════════════════════════════════════════════════════════════

const WACOM_VENDOR = 0x056A;
const TAB_X_MAX   = 9600;
const TAB_Y_MAX   = 6000;
const PRESS_MAX   = 511;   // 9 bits

const CW = 800, CH = 480;

// ── Canvas ────────────────────────────────────────────────────────
const canvas     = document.getElementById('sigCanvas');
const ctx        = canvas.getContext('2d');
const canvasWrap = document.getElementById('canvasWrap');
canvas.width = CW; canvas.height = CH;

function clearCanvas() {
  ctx.fillStyle = '#fafaf8';
  ctx.fillRect(0, 0, CW, CH);
  canvasWrap.classList.remove('has-sig');
}
clearCanvas();

// ═══════════════════════════════════════════════════════════════════
//  MOTOR DE DIBUJO — quadraticCurveTo por punto medio
//
//  Técnica estándar para pads de firma:
//    - Se mantienen 2 puntos: el anterior (prev) y el actual (curr).
//    - El punto de inicio de cada curva es el punto medio entre
//      prev y curr_anterior.
//    - El punto de control es prev.
//    - El punto de destino es el punto medio entre prev y curr.
//
//  Esto produce curvas C1-continuas (sin esquinas ni escalones)
//  porque cada curva empieza exactamente donde terminó la anterior.
//
//  La presión se aplica como lineWidth de cada segmento (media
//  entre los dos extremos del segmento).
// ═══════════════════════════════════════════════════════════════════

let prevPt  = null;   // último punto recibido {x,y,p}
let midPrev = null;   // punto medio entre los dos últimos puntos

function pressureToWidth(p) {
  return 0.8 + p * 3.0;
}
function pressureToAlpha(p) {
  return 0.55 + p * 0.45;
}

/**
 * Inicia un trazo nuevo en (x,y) con presión p.
 */
function strokeBegin(x, y, p) {
  prevPt  = { x, y, p };
  midPrev = null;

  // Dibuja el primer punto como un dot sólido.
  ctx.fillStyle = `rgba(10,20,40,${pressureToAlpha(p).toFixed(3)})`;
  ctx.beginPath();
  ctx.arc(x, y, pressureToWidth(p) / 2, 0, Math.PI * 2);
  ctx.fill();
}

/**
 * Añade el punto (x,y,p) al trazo activo.
 * Dibuja solo el nuevo segmento incremental.
 */
function strokeMove(x, y, p) {
  if (!prevPt) { strokeBegin(x, y, p); return; }

  const curr = { x, y, p };

  // Punto medio entre prevPt y curr
  const midCurr = {
    x: (prevPt.x + curr.x) / 2,
    y: (prevPt.y + curr.y) / 2,
    p: (prevPt.p + curr.p) / 2
  };

  const avgP = (prevPt.p + midCurr.p) / 2;

  ctx.lineWidth   = pressureToWidth(avgP);
  ctx.strokeStyle = `rgba(10,20,40,${pressureToAlpha(avgP).toFixed(3)})`;
  ctx.lineCap     = 'round';
  ctx.lineJoin    = 'round';
  ctx.beginPath();

  if (midPrev === null) {
    // Solo tenemos el primer punto y el midpoint actual:
    // trazamos línea recta desde prevPt hasta midCurr.
    ctx.moveTo(prevPt.x, prevPt.y);
    ctx.lineTo(midCurr.x, midCurr.y);
  } else {
    // Curva Bézier cuadrática:
    //   inicio   = midPrev  (donde terminó la curva anterior)
    //   control  = prevPt
    //   fin      = midCurr
    ctx.moveTo(midPrev.x, midPrev.y);
    ctx.quadraticCurveTo(prevPt.x, prevPt.y, midCurr.x, midCurr.y);
  }

  ctx.stroke();

  midPrev = midCurr;
  prevPt  = curr;
}

/**
 * Finaliza el trazo: dibuja el último segmento hasta el
 * punto exacto donde se levantó la pluma.
 */
function strokeEnd() {
  if (prevPt && midPrev) {
    const avgP = prevPt.p;
    ctx.lineWidth   = pressureToWidth(avgP);
    ctx.strokeStyle = `rgba(10,20,40,${pressureToAlpha(avgP).toFixed(3)})`;
    ctx.lineCap     = 'round';
    ctx.beginPath();
    ctx.moveTo(midPrev.x, midPrev.y);
    ctx.lineTo(prevPt.x, prevPt.y);
    ctx.stroke();
  }
  prevPt  = null;
  midPrev = null;
}

// ── Estado ────────────────────────────────────────────────────────
let hidDevice = null;
let mouseMode = false;
let penDown   = false;
let nStrokes  = 0, nPoints = 0, maxPres = 0, hasSig = false;

// Debug
let logPaused = false, onlyTip = false, dbVisible = false;
let logBuf = [], reportCount = 0;

// ── UI refs ───────────────────────────────────────────────────────
const ui = {
  connect:    document.getElementById('btnConnect'),
  disconnect: document.getElementById('btnDisconnect'),
  mouse:      document.getElementById('btnMouse'),
  clear:      document.getElementById('btnClear'),
  save:       document.getElementById('btnSave'),
  pill:       document.getElementById('statusPill'),
  pillTxt:    document.getElementById('statusText'),
  devName:    document.getElementById('deviceName'),
  iStrokes:   document.getElementById('iStrokes'),
  iPoints:    document.getElementById('iPoints'),
  iMaxP:      document.getElementById('iMaxP'),
  iMode:      document.getElementById('iMode'),
  pFill:      document.getElementById('pFill'),
  debugLog:   document.getElementById('debugLog'),
  dhTitle:    document.getElementById('dhTitle'),
  dhToggle:   document.getElementById('dhToggle'),
  dbCounter:  document.getElementById('dbCounter'),
};

if (!navigator.hid) {
  document.getElementById('hidNotice').classList.add('show');
  ui.connect.disabled = true;
}

function setPres(p) { ui.pFill.style.height = Math.round(p * 100) + '%'; }
function updateStats() {
  ui.iStrokes.textContent = nStrokes;
  ui.iPoints.textContent  = nPoints;
  ui.iMaxP.textContent    = maxPres > 0 ? Math.round(maxPres * 100) + '%' : '—';
  ui.iMode.textContent    = mouseMode ? 'Ratón/Táctil' : (hidDevice ? 'HID (Wacom)' : '—');
}

// ═══════════════════════════════════════════════════════════════════
//  INPUT REPORT — parseo y dibujo
// ═══════════════════════════════════════════════════════════════════

function onReport(e) {
  const { reportId, data } = e;
  reportCount++;

  // ── Construir hex string del payload completo ──────────────────
  const bytes = new Uint8Array(data.buffer, data.byteOffset, data.byteLength);
  const hex   = Array.from(bytes).map(b => b.toString(16).padStart(2,'0')).join(' ');

  // ── Parseo: igual que V1 ───────────────────────────────────────
  if (bytes.length < 7) {
    addLog(`<span class="le">CORTO ${bytes.length}B  hex: ${hex}</span>`);
    return;
  }

  const status = bytes[0];
  const tip    = !!(status & 0x01);   // bit 0 = tip (verificado V1)
  const rawX   = data.getUint16(1, true);
  const rawY   = data.getUint16(3, true);
  const rawP   = data.getUint16(5, true);
  const px     = (rawX / TAB_X_MAX) * CW;
  const py     = (rawY / TAB_Y_MAX) * CH;
  const pp     = Math.min(1, rawP / PRESS_MAX);

  // ── Log ────────────────────────────────────────────────────────
  if (!logPaused) {
    ui.dbCounter.textContent = reportCount + ' reports';
    const isTipLog = tip;
    if (!onlyTip || isTipLog) {
      const tipClass = tip ? 'lt' : 'li';
      const tipVal   = tip ? '▶TIP◀' : '  0  ';
      addLog(
        `<span class="${tipClass}">tip=${tipVal}</span>` +
        `  st=0x${status.toString(16).padStart(2,'0')}` +
        `  x=${String(rawX).padStart(4)}  y=${String(rawY).padStart(4)}  p=${String(rawP).padStart(3)}` +
        `  hex: <span style="color:#4a6a88">${hex}</span>`
      );
    }
  }

  // ── Presión visual ─────────────────────────────────────────────
  setPres(pp);

  // ── Dibujo ─────────────────────────────────────────────────────
  if (tip) {
    if (!penDown) {
      // Inicio de trazo
      penDown = true;
      hasSig  = true;
      canvasWrap.classList.add('has-sig');
      ui.clear.disabled = false;
      ui.save.disabled  = false;
      setStatus('signing', 'FIRMANDO...');
      strokeBegin(px, py, pp);
    } else {
      // Continúa el trazo
      strokeMove(px, py, pp);
    }
    nPoints++;
    if (pp > maxPres) maxPres = pp;
    updateStats();

  } else {
    // Pluma levantada o fuera de rango
    if (penDown) {
      penDown = false;
      strokeEnd();
      nStrokes++;
      setStatus('connected', 'CONECTADO');
      setPres(0);
      updateStats();
    }
  }
}

// ═══════════════════════════════════════════════════════════════════
//  WebHID
// ═══════════════════════════════════════════════════════════════════

async function connectDevice() {
  if (!navigator.hid) return;
  try {
    const devs = await navigator.hid.requestDevice({ filters: [{ vendorId: WACOM_VENDOR }] });
    if (!devs.length) { showToast('No se seleccionó dispositivo.', 'err'); return; }

    hidDevice = devs[0];
    if (!hidDevice.opened) await hidDevice.open();
    hidDevice.addEventListener('inputreport', onReport);

    ui.devName.textContent = hidDevice.productName || 'Wacom STU';
    mouseMode = false;
    setStatus('connected', 'CONECTADO');
    ui.connect.disabled    = true;
    ui.disconnect.disabled = false;
    ui.clear.disabled      = false;
    updateStats();
    if (!dbVisible) toggleDebug();

    addLog(`<span class="ls">✓ Conectado: ${hidDevice.productName}</span>`);
    addLog(`<span class="ls">  VID: 0x${hidDevice.vendorId.toString(16).toUpperCase()}   PID: 0x${hidDevice.productId.toString(16).toUpperCase()}</span>`);
    addLog(`<span class="li">─────────────────────────────────────────────────────</span>`);
    addLog(`<span class="li">Firma sobre la tableta. "tip=▶TIP◀" indica contacto.</span>`);
    addLog(`<span class="li">Si tip nunca cambia, comparte los bytes hex de este log.</span>`);
    addLog(`<span class="li">─────────────────────────────────────────────────────</span>`);
    showToast('✓ Tableta conectada.', 'ok');

    navigator.hid.addEventListener('disconnect', ({ device }) => {
      if (device !== hidDevice) return;
      hidDevice = null;
      ui.devName.textContent = 'Desconectado';
      setStatus('error', 'DESCONECTADO');
      ui.connect.disabled = false; ui.disconnect.disabled = true;
      addLog('<span class="le">✗ Tableta desconectada.</span>');
      showToast('Tableta desconectada.', 'err');
      updateStats();
    });
  } catch (err) {
    showToast('Error: ' + err.message, 'err');
    addLog(`<span class="le">Error al conectar: ${err.message}</span>`);
  }
}

async function disconnectDevice() {
  if (!hidDevice) return;
  try {
    hidDevice.removeEventListener('inputreport', onReport);
    await hidDevice.close();
    hidDevice = null;
    ui.devName.textContent = 'Sin conectar';
    setStatus('', 'DESCONECTADO');
    ui.connect.disabled = false; ui.disconnect.disabled = true;
    mouseMode = false; updateStats();
    showToast('Desconectado.', 'ok');
  } catch (err) { showToast(err.message, 'err'); }
}

// ═══════════════════════════════════════════════════════════════════
//  MODO RATÓN
// ═══════════════════════════════════════════════════════════════════

function cpos(e) {
  const r = canvas.getBoundingClientRect();
  const s = e.touches ? e.touches[0] : e;
  return { x: (s.clientX - r.left) * (CW / r.width), y: (s.clientY - r.top) * (CH / r.height) };
}

canvas.addEventListener('mousedown', e => {
  if (!mouseMode) return; e.preventDefault();
  const pos = cpos(e); penDown = true; hasSig = true;
  canvasWrap.classList.add('has-sig'); ui.clear.disabled = false; ui.save.disabled = false;
  setStatus('signing', 'FIRMANDO...');
  strokeBegin(pos.x, pos.y, 0.6); nPoints++; updateStats();
});
canvas.addEventListener('mousemove', e => {
  if (!mouseMode || !penDown) return; e.preventDefault();
  const pos = cpos(e);
  const sp  = prevPt ? Math.sqrt((pos.x-prevPt.x)**2 + (pos.y-prevPt.y)**2) : 0;
  const p   = Math.max(0.2, Math.min(0.95, 0.7 - sp / 80));
  strokeMove(pos.x, pos.y, p); nPoints++;
  if (p > maxPres) maxPres = p; updateStats();
});
canvas.addEventListener('mouseup',    e => { if (mouseMode && penDown) { e.preventDefault(); penDown=false; strokeEnd(); nStrokes++; setStatus('connected','MODO RATÓN'); setPres(0); updateStats(); } });
canvas.addEventListener('mouseleave', e => { if (mouseMode && penDown) { e.preventDefault(); penDown=false; strokeEnd(); nStrokes++; setStatus('connected','MODO RATÓN'); setPres(0); updateStats(); } });
canvas.addEventListener('touchstart', e => {
  if (!mouseMode) return; e.preventDefault();
  const pos=cpos(e); penDown=true; hasSig=true;
  canvasWrap.classList.add('has-sig'); ui.clear.disabled=false; ui.save.disabled=false;
  setStatus('signing','FIRMANDO...'); strokeBegin(pos.x,pos.y,0.6); nPoints++; updateStats();
}, { passive: false });
canvas.addEventListener('touchmove', e => {
  if (!mouseMode||!penDown) return; e.preventDefault();
  const pos=cpos(e); strokeMove(pos.x,pos.y,0.65); nPoints++; updateStats();
}, { passive: false });
canvas.addEventListener('touchend', e => {
  if (mouseMode&&penDown) { e.preventDefault(); penDown=false; strokeEnd(); nStrokes++; setStatus('connected','MODO RATÓN'); setPres(0); updateStats(); }
}, { passive: false });

// ═══════════════════════════════════════════════════════════════════
//  BOTONES
// ═══════════════════════════════════════════════════════════════════

ui.connect.addEventListener('click', connectDevice);
ui.disconnect.addEventListener('click', disconnectDevice);

ui.mouse.addEventListener('click', () => {
  mouseMode = !mouseMode;
  if (mouseMode) {
    ui.mouse.textContent   = '✓ Ratón ON';
    ui.mouse.style.cssText = 'background:var(--accent);color:#fff;border-color:var(--accent)';
    setStatus('signing', 'MODO RATÓN');
    ui.clear.disabled = false;
    showToast('Modo ratón activado. Prueba la suavidad del trazo.', 'ok');
  } else {
    ui.mouse.textContent   = 'Modo Ratón';
    ui.mouse.style.cssText = '';
    penDown = false; strokeEnd();
    setStatus(hidDevice ? 'connected' : '', hidDevice ? 'CONECTADO' : 'DESCONECTADO');
  }
  updateStats();
});

ui.clear.addEventListener('click', () => {
  clearCanvas(); penDown=false; strokeEnd();
  nStrokes=0; nPoints=0; maxPres=0; hasSig=false;
  ui.save.disabled=true;
  if (!mouseMode && !hidDevice) ui.clear.disabled=true;
  setPres(0);
  setStatus(hidDevice?'connected':(mouseMode?'signing':''), hidDevice?'CONECTADO':(mouseMode?'MODO RATÓN':'DESCONECTADO'));
  updateStats(); showToast('Firma eliminada.','ok');
});

ui.save.addEventListener('click', () => {
  if (!hasSig) return;
  const off=document.createElement('canvas'); off.width=CW; off.height=CH;
  const o=off.getContext('2d'); o.fillStyle='#fff'; o.fillRect(0,0,CW,CH); o.drawImage(canvas,0,0);
  const a=document.createElement('a');
  a.download='firma_'+new Date().toISOString().replace(/[:.]/g,'-').slice(0,19)+'.png';
  a.href=off.toDataURL('image/png'); a.click();
  showToast('✓ Firma guardada como PNG.','ok');
});

// ═══════════════════════════════════════════════════════════════════
//  DEBUG
// ═══════════════════════════════════════════════════════════════════

function addLog(html) {
  logBuf.push(html);
  if (logBuf.length > 500) logBuf.shift();
  if (!logPaused) {
    ui.debugLog.innerHTML = logBuf.join('\n');
    ui.debugLog.scrollTop = ui.debugLog.scrollHeight;
  }
}
function toggleDebug() {
  dbVisible = !dbVisible;
  document.getElementById('debugBody').classList.toggle('hidden', !dbVisible);
  ui.dhTitle.textContent  = (dbVisible ? '▼' : '▶') + ' DIAGNÓSTICO HID — RAW BYTES';
  ui.dhToggle.textContent = dbVisible ? 'OCULTAR' : 'MOSTRAR';
}
document.getElementById('debugToggle').addEventListener('click', toggleDebug);
document.getElementById('btnClearLog').addEventListener('click', () => { logBuf=[]; reportCount=0; ui.debugLog.innerHTML=''; ui.dbCounter.textContent='0 reports'; });
document.getElementById('btnPause').addEventListener('click', function() {
  logPaused = !logPaused; this.textContent = logPaused ? 'Reanudar' : 'Pausar';
  if (!logPaused) { ui.debugLog.innerHTML=logBuf.join('\n'); ui.debugLog.scrollTop=ui.debugLog.scrollHeight; }
});
document.getElementById('btnOnlyTip').addEventListener('click', function() {
  onlyTip = !onlyTip;
  this.textContent = onlyTip ? '✓ Solo tip=1' : 'Solo tip=1';
  this.style.color = onlyTip ? '#c8a96e' : '';
  addLog(`<span class="li">Filtro "solo tip=1": ${onlyTip ? 'activado' : 'desactivado'}</span>`);
});

// ═══════════════════════════════════════════════════════════════════
//  UTILS
// ═══════════════════════════════════════════════════════════════════

function setStatus(cls, txt) { ui.pill.className=cls||''; ui.pill.id='statusPill'; ui.pillTxt.textContent=txt; }
let toastT;
function showToast(msg, type) {
  const t=document.getElementById('toast'); t.textContent=msg; t.className='show'+(type?' '+type:'');
  clearTimeout(toastT); toastT=setTimeout(()=>t.className='',3200);
}

updateStats();
setStatus('','DESCONECTADO');
addLog('<span class="li">Listo. Pulsa "Conectar Tableta" y selecciona la Wacom STU-540.</span>');
</script>
</body>
</html>