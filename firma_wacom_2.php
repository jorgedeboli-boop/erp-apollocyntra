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
      --ink:    #0d1117;
      --paper:  #f7f4ef;
      --rule:   #d6cfc4;
      --accent: #1a3a5c;
      --gold:   #c8a96e;
      --ok:     #2d7a4f;
      --err:    #8b1a1a;
      --shadow: rgba(13,17,23,.12);
      --mono:   'DM Mono', monospace;
      --serif:  'Cormorant Garamond', Georgia, serif;
    }
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    body {
      background: var(--paper);
      color: var(--ink);
      font-family: var(--serif);
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      align-items: center;
      padding: 2rem 1rem 4rem;
    }

    header { text-align: center; margin-bottom: 2rem; }
    .eyebrow {
      font-family: var(--mono); font-size: .7rem;
      letter-spacing: .25em; text-transform: uppercase;
      color: var(--gold); margin-bottom: .4rem;
    }
    h1 { font-size: 2.2rem; font-weight: 300; color: var(--accent); }
    h1 strong { font-weight: 600; }

    .card {
      background: #fff; border: 1px solid var(--rule);
      border-radius: 4px; box-shadow: 0 2px 24px var(--shadow);
      width: 100%; max-width: 860px;
    }
    .card-header {
      display: flex; align-items: center;
      justify-content: space-between;
      padding: .9rem 1.4rem; background: var(--accent); gap: 1rem;
    }
    .device-label { font-family: var(--mono); font-size: .7rem; color: rgba(255,255,255,.5); letter-spacing: .1em; }
    .device-name  { font-family: var(--mono); font-size: .8rem; color: #fff; }

    #statusPill {
      display: flex; align-items: center; gap: .4rem;
      font-family: var(--mono); font-size: .68rem; letter-spacing: .1em;
      padding: .3rem .8rem; border-radius: 20px;
      background: rgba(255,255,255,.1); color: rgba(255,255,255,.6);
      white-space: nowrap; transition: background .3s, color .3s;
    }
    #statusPill.connected { background: rgba(45,122,79,.35); color: #7ee8a2; }
    #statusPill.error     { background: rgba(139,26,26,.35); color: #f4a0a0; }
    #statusPill.signing   { background: rgba(200,169,110,.25); color: var(--gold); }
    .dot { width:7px; height:7px; border-radius:50%; background:currentColor; flex-shrink:0; }
    .signing .dot { animation: pulse 1s ease-in-out infinite; }
    @keyframes pulse { 0%,100%{opacity:1} 50%{opacity:.25} }

    .canvas-wrap {
      position: relative; background: #fafaf8;
      border-bottom: 1px solid var(--rule);
    }
    #sigCanvas { display:block; width:100%; cursor:crosshair; touch-action:none; }

    .canvas-overlay {
      position:absolute; inset:0;
      display:flex; flex-direction:column;
      align-items:center; justify-content:center;
      gap:.5rem; pointer-events:none; transition:opacity .4s;
    }
    .canvas-overlay .icon { font-size:2.5rem; opacity:.15; }
    .canvas-overlay p { font-family:var(--mono); font-size:.7rem; letter-spacing:.12em; color:var(--ink); opacity:.25; }
    .has-sig .canvas-overlay { opacity:0; }

    .baseline {
      position:absolute; bottom:22%; left:6%; right:6%;
      border-bottom: 1px solid var(--rule); pointer-events:none;
    }
    .baseline::before {
      content:'Firma'; position:absolute; bottom:4px; left:0;
      font-family:var(--mono); font-size:.58rem;
      letter-spacing:.15em; color:var(--rule);
    }

    #pressureBar {
      position:absolute; top:8px; right:10px;
      display:flex; flex-direction:column; align-items:flex-end; gap:3px;
      pointer-events:none;
    }
    #pressureBar label { font-family:var(--mono); font-size:.58rem; letter-spacing:.1em; color:var(--accent); opacity:.4; }
    #pTrack { width:6px; height:60px; background:var(--rule); border-radius:3px; overflow:hidden; position:relative; }
    #pFill  { position:absolute; bottom:0; left:0; right:0; height:0%;
              background:linear-gradient(to top,var(--accent),var(--gold));
              border-radius:3px; transition:height .04s; }

    .controls { display:flex; align-items:center; flex-wrap:wrap; gap:.7rem; padding:1rem 1.4rem; }
    .left  { display:flex; gap:.7rem; flex:1; flex-wrap:wrap; }
    .right { display:flex; gap:.7rem; flex-wrap:wrap; }

    button {
      font-family:var(--mono); font-size:.7rem; letter-spacing:.1em;
      text-transform:uppercase; border:1px solid var(--rule);
      background:#fff; color:var(--ink); padding:.5rem 1rem;
      border-radius:3px; cursor:pointer; white-space:nowrap;
      transition:background .2s, color .2s, border-color .2s, transform .1s;
    }
    button:hover  { background:var(--ink); color:#fff; border-color:var(--ink); }
    button:active { transform:scale(.97); }
    button:disabled { opacity:.35; cursor:not-allowed; }
    button:disabled:hover { background:#fff; color:var(--ink); border-color:var(--rule); transform:none; }

    .btn-primary { background:var(--accent); color:#fff; border-color:var(--accent); }
    .btn-primary:hover { background:var(--ink); border-color:var(--ink); }
    .btn-primary:disabled:hover { background:var(--accent); border-color:var(--accent); }
    .btn-gold  { background:var(--gold); color:#fff; border-color:var(--gold); }
    .btn-gold:hover { background:#a6863d; border-color:#a6863d; }
    .btn-gold:disabled:hover { background:var(--gold); border-color:var(--gold); }
    .btn-danger { border-color:var(--err); color:var(--err); }
    .btn-danger:hover { background:var(--err); color:#fff; border-color:var(--err); }

    .info-bar {
      display:flex; gap:1.5rem; flex-wrap:wrap;
      padding:.65rem 1.4rem; border-top:1px solid var(--rule);
      font-family:var(--mono); font-size:.63rem; color:#999; letter-spacing:.06em;
    }
    .info-bar strong { color:var(--ink); font-weight:400; }

    /* Debug panel */
    #debugPanel { margin-top:1rem; width:100%; max-width:860px; border:1px solid #2a3f55; border-radius:4px; overflow:hidden; }
    .debug-header { display:flex; align-items:center; justify-content:space-between; padding:.55rem 1rem; background:#1e2a38; cursor:pointer; user-select:none; }
    .debug-header .dh-title  { font-family:var(--mono); font-size:.68rem; color:#7fa8cc; letter-spacing:.1em; }
    .debug-header .dh-toggle { font-family:var(--mono); font-size:.65rem; color:#4a6a88; }
    #debugBody { background:#0d1117; }
    #debugBody.hidden { display:none; }
    #debugLog { font-family:var(--mono); font-size:.64rem; color:#7ee8a2; padding:.8rem 1rem; height:160px; overflow-y:auto; white-space:pre-wrap; line-height:1.65; }
    .lw{color:#f4a050;} .li{color:#7ec8f4;} .le{color:#f48080;} .ls{color:#7ee8a2;}
    .debug-bar { display:flex; gap:.5rem; align-items:center; padding:.5rem 1rem; background:#080d14; border-top:1px solid #1e2a38; flex-wrap:wrap; }
    .debug-bar button { background:#1e2a38; border-color:#2a3f55; color:#7fa8cc; font-size:.61rem; padding:.28rem .65rem; }
    .debug-bar button:hover { background:#2a3f55; color:#fff; border-color:#3a5570; }
    .debug-bar .raw-val { font-family:var(--mono); font-size:.62rem; color:#4a6a88; margin-left:auto; }

    #toast {
      position:fixed; bottom:2rem; right:2rem;
      background:var(--ink); color:#fff;
      font-family:var(--mono); font-size:.7rem; letter-spacing:.08em;
      padding:.65rem 1.1rem; border-radius:3px;
      box-shadow:0 4px 20px rgba(0,0,0,.25);
      opacity:0; transform:translateY(8px);
      transition:opacity .3s, transform .3s;
      pointer-events:none; max-width:340px; z-index:999;
    }
    #toast.show { opacity:1; transform:translateY(0); }
    #toast.ok  { background:var(--ok); }
    #toast.err { background:var(--err); }

    #hidNotice {
      background:#fff8ec; border:1px solid var(--gold); border-radius:3px;
      padding:.7rem 1.1rem; font-family:var(--mono); font-size:.68rem;
      letter-spacing:.06em; color:#7a5c1e;
      margin-bottom:1.2rem; max-width:860px; width:100%;
      display:none; line-height:1.65;
    }
    #hidNotice.show { display:block; }
  </style>
</head>
<body>

<header>
  <p class="eyebrow">Captura de Firma Digital</p>
  <h1>Wacom <strong>STU-540</strong></h1>
</header>

<div id="hidNotice">
  ⚠ Esta aplicación requiere <strong>Chrome o Edge 89+</strong> con soporte WebHID.
</div>

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
    <span>Último report: <strong id="iReport">—</strong></span>
  </div>
</div>

<div id="debugPanel">
  <div class="debug-header" id="debugToggle">
    <span class="dh-title" id="dhTitle">▶ DIAGNÓSTICO HID</span>
    <span class="dh-toggle" id="dhToggle">MOSTRAR</span>
  </div>
  <div id="debugBody" class="hidden">
    <div id="debugLog">Listo. Conecta la tableta.\n</div>
    <div class="debug-bar">
      <button id="btnClearLog">Limpiar</button>
      <button id="btnPause">Pausar</button>
      <button id="btnRaw">Raw HEX: OFF</button>
      <span class="raw-val" id="rawLast"></span>
    </div>
  </div>
</div>

<div id="toast"></div>

<script>
'use strict';

// ═══════════════════════════════════════════════════════════
//  WACOM STU-540 — protocolo HID
//    Offset 0-1  uint16 LE  status  (bit0=rdy, bit1=tip)
//    Offset 2-3  uint16 LE  X  (0–9600)
//    Offset 4-5  uint16 LE  Y  (0–6000)
//    Offset 6-7  uint16 LE  P  (0–1023)
// ═══════════════════════════════════════════════════════════

const WACOM_VENDOR = 0x056A;
const TAB_X_MAX   = 9600;
const TAB_Y_MAX   = 6000;
const PRESS_MAX   = 1023;

const CW = 800, CH = 480;

// ── Canvas ──────────────────────────────────────────────────
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

// ═══════════════════════════════════════════════════════════
//  SMOOTH STROKE — algoritmo de punto medio con Bézier
//
//  Acumulamos los puntos del trazo en `strokePoints`.
//  Cada vez que llega un punto nuevo lo añadimos y
//  redibujamos la curva completa sobre el canvas
//  usando curveTo (Bézier cuadrática) con el punto
//  medio entre consecutivos como punto de anclaje.
//
//  Para presión variable sin escalones usamos
//  segmentos cortos con lineWidth interpolado.
// ═══════════════════════════════════════════════════════════

// Buffer de puntos del trazo activo: [{x, y, p}]
let strokePoints = [];

/**
 * Añade un punto al trazo activo y lo redibuja suavizado.
 */
function addPoint(x, y, p) {
  strokePoints.push({ x, y, p });
  renderStroke();
}

/**
 * Redibuja el trazo activo completo usando Bézier cuadráticas.
 * Se borra sólo el área del trazo para no destruir trazos anteriores.
 *
 * Estrategia:
 *  - Si hay 1 punto → punto sólido.
 *  - Si hay 2 puntos → línea directa.
 *  - Si hay 3+ puntos → curvas por punto medio.
 *
 * Para simular presión variable se subdivide cada segmento
 * Bézier en pequeños trozos con lineWidth interpolado.
 */
function renderStroke() {
  const pts = strokePoints;
  if (pts.length === 0) return;

  // Redraw del trazo activo: lo pintamos encima (no borramos
  // trazos finalizados porque están en el canvas base).
  if (pts.length === 1) {
    const lw = pressureToWidth(pts[0].p);
    ctx.fillStyle = pressureToColor(pts[0].p);
    ctx.beginPath();
    ctx.arc(pts[0].x, pts[0].y, lw / 2, 0, Math.PI * 2);
    ctx.fill();
    return;
  }

  if (pts.length === 2) {
    drawTaperedLine(pts[0], pts[1]);
    return;
  }

  // 3+ puntos: Bézier cuadrática por punto medio
  // Entre p[i] y p[i+1] el punto de control es p[i],
  // y el punto de destino es el punto medio entre p[i] y p[i+1].
  for (let i = 1; i < pts.length - 1; i++) {
    const p0 = pts[i - 1];
    const p1 = pts[i];
    const p2 = pts[i + 1];

    const midX = (p1.x + p2.x) / 2;
    const midY = (p1.y + p2.y) / 2;
    const midP = (p1.p + p2.p) / 2;

    // Solo dibujar el segmento nuevo (desde mid(i-1,i) hasta mid(i,i+1))
    const prevMidX = (p0.x + p1.x) / 2;
    const prevMidY = (p0.y + p1.y) / 2;

    drawBezierSegmentWithPressure(
      prevMidX, prevMidY, p0.p,
      p1.x,    p1.y,     p1.p,
      midX,    midY,     midP
    );
  }

  // Último segmento: desde midpoint hasta el último punto
  const last  = pts[pts.length - 1];
  const prev  = pts[pts.length - 2];
  const mX    = (prev.x + last.x) / 2;
  const mY    = (prev.y + last.y) / 2;
  drawTaperedLine({ x: mX, y: mY, p: (prev.p + last.p) / 2 }, last);
}

/**
 * Dibuja una curva Bézier cuadrática simulando variación
 * de presión: subdivide la curva en N segmentos cortos
 * e interpola lineWidth.
 */
function drawBezierSegmentWithPressure(x0, y0, p0, cx, cy, cp, x1, y1, p1) {
  const STEPS = 8;  // segmentos por curva Bézier — suficiente para suavidad
  let prevX = x0, prevY = y0, prevP = p0;

  for (let s = 1; s <= STEPS; s++) {
    const t  = s / STEPS;
    const t1 = 1 - t;

    // Punto en la curva Bézier cuadrática
    const bx = t1*t1*x0 + 2*t1*t*cx + t*t*x1;
    const by = t1*t1*y0 + 2*t1*t*cy + t*t*y1;
    const bp = t1*p0   + t*p1;   // presión interpolada linealmente

    drawTaperedLine(
      { x: prevX, y: prevY, p: prevP },
      { x: bx,    y: by,    p: bp   }
    );

    prevX = bx; prevY = by; prevP = bp;
  }
}

/**
 * Dibuja un único segmento recto con presión interpolada entre
 * los dos extremos. Usa beginPath + lineTo + stroke para cada
 * segmento con su propio lineWidth → sin escalones visibles.
 */
function drawTaperedLine(a, b) {
  const lw = pressureToWidth((a.p + b.p) / 2);
  ctx.beginPath();
  ctx.moveTo(a.x, a.y);
  ctx.lineTo(b.x, b.y);
  ctx.lineWidth   = lw;
  ctx.strokeStyle = pressureToColor((a.p + b.p) / 2);
  ctx.lineCap     = 'round';
  ctx.lineJoin    = 'round';
  ctx.stroke();
}

function pressureToWidth(p) {
  // Rango 0.8px (sin presión) a 3.5px (presión máxima)
  return 0.8 + p * 2.7;
}

function pressureToColor(p) {
  // Opacidad entre 0.55 y 1.0 según presión
  const alpha = Math.min(1, 0.55 + p * 0.45);
  return `rgba(10,20,40,${alpha.toFixed(3)})`;
}

// ── Estado global ────────────────────────────────────────────
let hidDevice  = null;
let mouseMode  = false;
let penDown    = false;
let nStrokes   = 0, nPoints = 0, maxPres = 0, hasSig = false;

// ── Debug ────────────────────────────────────────────────────
let logPaused = false, rawMode = false, dbVisible = false;
let logBuf = [];
const LOG_MAX = 400;

// ── UI refs ──────────────────────────────────────────────────
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
  iReport:    document.getElementById('iReport'),
  pFill:      document.getElementById('pFill'),
  debugLog:   document.getElementById('debugLog'),
  rawLast:    document.getElementById('rawLast'),
  dhTitle:    document.getElementById('dhTitle'),
  dhToggle:   document.getElementById('dhToggle'),
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

// ═══════════════════════════════════════════════════════════
//  GESTIÓN DE TRAZOS
// ═══════════════════════════════════════════════════════════

function beginStroke(x, y, p) {
  strokePoints = [];
  penDown = true;
  setStatus('signing', 'FIRMANDO...');
  addPoint(x, y, p);
  hasSig = true;
  canvasWrap.classList.add('has-sig');
  ui.clear.disabled = false;
  ui.save.disabled  = false;
}

function continueStroke(x, y, p) {
  if (!penDown) return;
  addPoint(x, y, p);
  nPoints++;
  if (p > maxPres) maxPres = p;
  updateStats();
  setPres(p);
}

function endStroke() {
  if (!penDown) return;
  penDown = false;
  strokePoints = [];  // limpiar buffer — el trazo ya está en el canvas
  nStrokes++;
  setStatus('connected', 'CONECTADO');
  setPres(0);
  updateStats();
}

// ═══════════════════════════════════════════════════════════
//  PARSEO HID — WACOM STU-540
// ═══════════════════════════════════════════════════════════

function parseSTU(data) {
  if (data.byteLength < 8) return null;
  const status = data.getUint16(0, true);
  const rdy    = !!(status & 0x01);
  const tip    = !!(status & 0x02);
  const rawX   = data.getUint16(2, true);
  const rawY   = data.getUint16(4, true);
  const rawP   = data.getUint16(6, true);
  return {
    rdy, tip,
    x: (rawX / TAB_X_MAX) * CW,
    y: (rawY / TAB_Y_MAX) * CH,
    p: Math.min(1, rawP / PRESS_MAX),
    rawX, rawY, rawP, status
  };
}

function onReport(e) {
  const { reportId, data } = e;

  // Info bar
  const bytes = [];
  for (let i = 0; i < data.byteLength; i++)
    bytes.push(data.getUint8(i).toString(16).padStart(2,'0'));
  const hex = bytes.join(' ');
  ui.iReport.textContent = `ID:0x${reportId.toString(16).toUpperCase()} [${hex.substring(0,29)}${hex.length>29?'…':''}]`;
  ui.rawLast.textContent = `${data.byteLength}B`;

  if (!logPaused && rawMode)
    addLog(`<span class="li">ID:${reportId.toString(16)}</span> ${hex}`);

  const pen = parseSTU(data);
  if (!pen) { addLog(`<span class="lw">⚠ Payload corto (${data.byteLength}B)</span>`); return; }

  if (!logPaused && !rawMode && (pen.rdy || pen.tip))
    addLog(`<span class="li">pen</span> tip=${pen.tip?1:0} x=${pen.rawX} y=${pen.rawY} p=${pen.rawP}`);

  setPres(pen.p);

  if (!pen.rdy && !pen.tip) { endStroke(); return; }

  if (pen.tip) {
    if (!penDown) beginStroke(pen.x, pen.y, pen.p);
    else          continueStroke(pen.x, pen.y, pen.p);
  } else {
    endStroke();  // hover sin contacto
  }
}

// ═══════════════════════════════════════════════════════════
//  WebHID
// ═══════════════════════════════════════════════════════════

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
    addLog(`<span class="ls">✓ ${hidDevice.productName}  VID:0x${hidDevice.vendorId.toString(16).toUpperCase()}  PID:0x${hidDevice.productId.toString(16).toUpperCase()}</span>`);
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
    addLog(`<span class="le">Error: ${err.message}</span>`);
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

// ═══════════════════════════════════════════════════════════
//  MODO RATÓN
// ═══════════════════════════════════════════════════════════

function cpos(e) {
  const r  = canvas.getBoundingClientRect();
  const sx = CW / r.width, sy = CH / r.height;
  const s  = e.touches ? e.touches[0] : e;
  return { x:(s.clientX-r.left)*sx, y:(s.clientY-r.top)*sy };
}

canvas.addEventListener('mousedown', e => {
  if (!mouseMode) return; e.preventDefault();
  const pos = cpos(e);
  beginStroke(pos.x, pos.y, 0.6);
  nPoints++; updateStats();
});
canvas.addEventListener('mousemove', e => {
  if (!mouseMode || !penDown) return; e.preventDefault();
  const pos = cpos(e);
  const dx = pos.x - (strokePoints.length ? strokePoints[strokePoints.length-1].x : pos.x);
  const dy = pos.y - (strokePoints.length ? strokePoints[strokePoints.length-1].y : pos.y);
  const sp = Math.sqrt(dx*dx+dy*dy);
  const p  = Math.max(0.25, Math.min(0.9, 0.7 - sp/80));
  continueStroke(pos.x, pos.y, p);
});
canvas.addEventListener('mouseup',    e => { if (mouseMode) { e.preventDefault(); endStroke(); } });
canvas.addEventListener('mouseleave', e => { if (mouseMode && penDown) { e.preventDefault(); endStroke(); } });
canvas.addEventListener('touchstart', e => {
  if (!mouseMode) return; e.preventDefault();
  const pos = cpos(e); beginStroke(pos.x, pos.y, 0.6); nPoints++; updateStats();
}, { passive: false });
canvas.addEventListener('touchmove', e => {
  if (!mouseMode || !penDown) return; e.preventDefault();
  const pos = cpos(e);
  continueStroke(pos.x, pos.y, 0.6);
}, { passive: false });
canvas.addEventListener('touchend', e => { if (mouseMode) { e.preventDefault(); endStroke(); } }, { passive: false });

// ═══════════════════════════════════════════════════════════
//  BOTONES
// ═══════════════════════════════════════════════════════════

ui.connect.addEventListener('click', connectDevice);
ui.disconnect.addEventListener('click', disconnectDevice);

ui.mouse.addEventListener('click', () => {
  mouseMode = !mouseMode;
  if (mouseMode) {
    ui.mouse.textContent   = '✓ Ratón ON';
    ui.mouse.style.cssText = 'background:var(--accent);color:#fff;border-color:var(--accent)';
    setStatus('signing', 'MODO RATÓN');
    ui.clear.disabled = false;
    showToast('Modo ratón activado.', 'ok');
  } else {
    ui.mouse.textContent   = 'Modo Ratón';
    ui.mouse.style.cssText = '';
    penDown = false; strokePoints = [];
    setStatus(hidDevice ? 'connected' : '', hidDevice ? 'CONECTADO' : 'DESCONECTADO');
  }
  updateStats();
});

ui.clear.addEventListener('click', () => {
  clearCanvas();
  strokePoints = []; penDown = false;
  nStrokes = 0; nPoints = 0; maxPres = 0; hasSig = false;
  ui.save.disabled = true;
  if (!mouseMode && !hidDevice) ui.clear.disabled = true;
  setPres(0);
  setStatus(hidDevice ? 'connected' : (mouseMode ? 'signing' : ''),
            hidDevice ? 'CONECTADO' : (mouseMode ? 'MODO RATÓN' : 'DESCONECTADO'));
  updateStats();
  showToast('Firma eliminada.', 'ok');
});

ui.save.addEventListener('click', () => {
  if (!hasSig) return;
  const off = document.createElement('canvas');
  off.width = CW; off.height = CH;
  const o = off.getContext('2d');
  o.fillStyle = '#fff'; o.fillRect(0,0,CW,CH);
  o.drawImage(canvas,0,0);
  const a = document.createElement('a');
  a.download = 'firma_' + new Date().toISOString().replace(/[:.]/g,'-').slice(0,19) + '.png';
  a.href = off.toDataURL('image/png');
  a.click();
  showToast('✓ Firma guardada como PNG.', 'ok');
});

// ═══════════════════════════════════════════════════════════
//  DEBUG
// ═══════════════════════════════════════════════════════════

function addLog(html) {
  logBuf.push(html);
  if (logBuf.length > LOG_MAX) logBuf.shift();
  if (!logPaused) {
    ui.debugLog.innerHTML  = logBuf.join('\n');
    ui.debugLog.scrollTop  = ui.debugLog.scrollHeight;
  }
}
function toggleDebug() {
  dbVisible = !dbVisible;
  document.getElementById('debugBody').classList.toggle('hidden', !dbVisible);
  ui.dhTitle.textContent  = (dbVisible?'▼':'▶') + ' DIAGNÓSTICO HID';
  ui.dhToggle.textContent = dbVisible ? 'OCULTAR' : 'MOSTRAR';
}
document.getElementById('debugToggle').addEventListener('click', toggleDebug);
document.getElementById('btnClearLog').addEventListener('click', () => { logBuf=[]; ui.debugLog.innerHTML=''; });
document.getElementById('btnPause').addEventListener('click', function() {
  logPaused = !logPaused;
  this.textContent = logPaused ? 'Reanudar' : 'Pausar';
  if (!logPaused) { ui.debugLog.innerHTML = logBuf.join('\n'); ui.debugLog.scrollTop = ui.debugLog.scrollHeight; }
});
document.getElementById('btnRaw').addEventListener('click', function() {
  rawMode = !rawMode;
  this.textContent = 'Raw HEX: ' + (rawMode ? 'ON' : 'OFF');
  this.style.color = rawMode ? '#c8a96e' : '';
  addLog(`<span class="li">Raw hex ${rawMode?'activado':'desactivado'}</span>`);
});

// ═══════════════════════════════════════════════════════════
//  UTILIDADES
// ═══════════════════════════════════════════════════════════

function setStatus(cls, txt) {
  ui.pill.className  = cls || '';
  ui.pill.id         = 'statusPill';
  ui.pillTxt.textContent = txt;
}
let toastT;
function showToast(msg, type) {
  const t = document.getElementById('toast');
  t.textContent = msg;
  t.className   = 'show' + (type ? ' '+type : '');
  clearTimeout(toastT);
  toastT = setTimeout(() => t.className = '', 3200);
}

updateStats();
setStatus('', 'DESCONECTADO');
addLog('<span class="li">Esperando conexión. Pulsa "Conectar Tableta".</span>');
</script>
</body>
</html>