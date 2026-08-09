<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Firma Digital — Wacom STU-500B</title>
  <link rel="preconnect" href="https://fonts.googleapis.com"/>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@300;400;600&family=DM+Mono:wght@300;400&display=swap" rel="stylesheet"/>
  <style>
    :root {
      --ink:#0d1117; --paper:#f7f4ef; --rule:#d6cfc4;
      --accent:#1a3a5c; --gold:#c8a96e; --ok:#2d7a4f; --err:#8b1a1a;
      --mono:'DM Mono',monospace; --serif:'Cormorant Garamond',Georgia,serif;
    }
    *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
    body{background:var(--paper);color:var(--ink);font-family:var(--serif);
         min-height:100vh;display:flex;flex-direction:column;align-items:center;
         padding:2rem 1rem 4rem}
    header{text-align:center;margin-bottom:2rem}
    .eyebrow{font-family:var(--mono);font-size:.7rem;letter-spacing:.25em;
             text-transform:uppercase;color:var(--gold);margin-bottom:.4rem}
    h1{font-size:2.2rem;font-weight:300;color:var(--accent)}
    h1 strong{font-weight:600}

    .card{background:#fff;border:1px solid var(--rule);border-radius:4px;
          box-shadow:0 2px 20px rgba(13,17,23,.1);width:100%;max-width:700px}
    .card-header{display:flex;align-items:center;justify-content:space-between;
                 padding:.9rem 1.4rem;background:var(--accent);gap:1rem;border-radius:4px 4px 0 0}
    .device-label{font-family:var(--mono);font-size:.68rem;color:rgba(255,255,255,.45);letter-spacing:.1em}
    .device-name{font-family:var(--mono);font-size:.82rem;color:#fff;margin-top:.1rem}
    #pill{display:flex;align-items:center;gap:.4rem;font-family:var(--mono);font-size:.68rem;
          letter-spacing:.1em;padding:.3rem .85rem;border-radius:20px;
          background:rgba(255,255,255,.1);color:rgba(255,255,255,.55);white-space:nowrap;transition:all .3s}
    #pill.ok {background:rgba(45,122,79,.4);color:#7ee8a2}
    #pill.err{background:rgba(139,26,26,.4);color:#f4a0a0}
    #pill.ink{background:rgba(200,169,110,.3);color:var(--gold)}
    .dot{width:7px;height:7px;border-radius:50%;background:currentColor;flex-shrink:0}
    .ink .dot{animation:blink 1s ease-in-out infinite}
    @keyframes blink{0%,100%{opacity:1}50%{opacity:.2}}

    /* ── Área de firma ── */
    /* El canvas se dimensiona en CSS pero internamente tiene resolución×DPR */
    .sig-wrap{position:relative;width:100%;background:#fafaf8;
              border-bottom:1px solid var(--rule);overflow:hidden}
    #cvs{display:block;width:100%;cursor:crosshair;touch-action:none}
    .hint{position:absolute;inset:0;display:flex;flex-direction:column;
          align-items:center;justify-content:center;gap:.6rem;pointer-events:none;
          transition:opacity .3s}
    .hint.gone{opacity:0}
    .hint .ico{font-size:3rem;opacity:.1}
    .hint p{font-family:var(--mono);font-size:.7rem;letter-spacing:.12em;color:var(--ink);opacity:.22}
    .baseline{position:absolute;bottom:22%;left:6%;right:6%;
              border-bottom:1px solid var(--rule);pointer-events:none}
    .baseline::before{content:'Firma';position:absolute;bottom:5px;left:0;
                      font-family:var(--mono);font-size:.58rem;letter-spacing:.15em;color:var(--rule)}
    #pBar{position:absolute;top:10px;right:12px;display:flex;
          flex-direction:column;align-items:flex-end;gap:4px;pointer-events:none}
    #pBar label{font-family:var(--mono);font-size:.56rem;letter-spacing:.1em;color:var(--accent);opacity:.35}
    #pTrack{width:6px;height:56px;background:var(--rule);border-radius:3px;overflow:hidden;position:relative}
    #pFill{position:absolute;bottom:0;left:0;right:0;height:0%;
           background:linear-gradient(to top,var(--accent),var(--gold));border-radius:3px;transition:height .04s}

    .controls{display:flex;align-items:center;flex-wrap:wrap;gap:.7rem;padding:1rem 1.4rem}
    .left{display:flex;gap:.7rem;flex:1;flex-wrap:wrap}
    .right{display:flex;gap:.7rem;flex-wrap:wrap}
    button{font-family:var(--mono);font-size:.7rem;letter-spacing:.1em;text-transform:uppercase;
           border:1px solid var(--rule);background:#fff;color:var(--ink);padding:.5rem 1rem;
           border-radius:3px;cursor:pointer;white-space:nowrap;
           transition:background .18s,color .18s,border-color .18s,transform .1s}
    button:hover{background:var(--ink);color:#fff;border-color:var(--ink)}
    button:active{transform:scale(.97)}
    button:disabled{opacity:.35;cursor:not-allowed;pointer-events:none}
    .bp{background:var(--accent);color:#fff;border-color:var(--accent)}
    .bp:hover{background:#0d1f33;border-color:#0d1f33}
    .bg{background:var(--gold);color:#fff;border-color:var(--gold)}
    .bg:hover{background:#a6843d;border-color:#a6843d}
    .bd{border-color:var(--err);color:var(--err)}
    .bd:hover{background:var(--err);color:#fff;border-color:var(--err)}

    .info-bar{display:flex;gap:1.5rem;flex-wrap:wrap;padding:.65rem 1.4rem;
              border-top:1px solid var(--rule);font-family:var(--mono);
              font-size:.63rem;color:#999;letter-spacing:.06em}
    .info-bar strong{color:var(--ink);font-weight:400}

    /* ── Diagnóstico ── */
    #dbPanel{margin-top:1rem;width:100%;max-width:700px;border:1px solid #2a3f55;
             border-radius:4px;overflow:hidden}
    .dbh{display:flex;align-items:center;justify-content:space-between;
         padding:.55rem 1rem;background:#1e2a38;cursor:pointer;user-select:none}
    .dbh .t{font-family:var(--mono);font-size:.65rem;color:#7fa8cc;letter-spacing:.08em}
    .dbh .s{font-family:var(--mono);font-size:.65rem;color:#4a6a88}
    #dbBody.h{display:none}
    #dbLog{font-family:var(--mono);font-size:.63rem;color:#7ee8a2;background:#0d1117;
           padding:.8rem 1rem;height:200px;overflow-y:auto;white-space:pre-wrap;line-height:1.7}
    .lt{color:#f4f47e}.li{color:#7ec8f4}.le{color:#f48080}.ls{color:#7ee8a2}
    .dbb{display:flex;gap:.5rem;align-items:center;padding:.5rem 1rem;
         background:#080d14;border-top:1px solid #1e2a38;flex-wrap:wrap}
    .dbb button{background:#1e2a38;border-color:#2a3f55;color:#7fa8cc;font-size:.61rem;padding:.28rem .65rem}
    .dbb button:hover{background:#2a3f55;color:#fff;border-color:#3a5570}

    #toast{position:fixed;bottom:2rem;right:2rem;background:var(--ink);color:#fff;
           font-family:var(--mono);font-size:.7rem;padding:.65rem 1.15rem;border-radius:3px;
           box-shadow:0 4px 20px rgba(0,0,0,.22);opacity:0;transform:translateY(8px);
           transition:opacity .3s,transform .3s;pointer-events:none;max-width:360px;z-index:999}
    #toast.show{opacity:1;transform:translateY(0)}
    #toast.ok{background:var(--ok)} #toast.err{background:var(--err)}
    #noHid{width:100%;max-width:700px;margin-bottom:1.2rem;padding:.8rem 1.2rem;
           border:1px solid var(--gold);border-radius:4px;background:#fff8ec;
           font-family:var(--mono);font-size:.68rem;color:#7a5c1e;line-height:1.7;display:none}
    #noHid.show{display:block}
  </style>
</head>
<body>

<header>
  <p class="eyebrow">Captura de Firma Digital</p>
  <h1>Wacom <strong>STU-500B</strong></h1>
</header>

<div id="noHid">⚠ <strong>Requiere Chrome o Edge 89+</strong> con soporte WebHID.</div>

<div class="card">
  <div class="card-header">
    <div>
      <div class="device-label">Dispositivo</div>
      <div class="device-name" id="devName">Sin conectar</div>
    </div>
    <div id="pill"><span class="dot"></span><span id="pillTxt">DESCONECTADO</span></div>
  </div>

  <div class="sig-wrap" id="sigWrap">
    <canvas id="cvs"></canvas>
    <div class="baseline"></div>
    <div class="hint" id="hint"><div class="ico">✒</div><p>Firme sobre la tableta</p></div>
    <div id="pBar"><label>PRES</label><div id="pTrack"><div id="pFill"></div></div></div>
  </div>

  <div class="controls">
    <div class="left">
      <button class="bp" id="btnCon">Conectar Tableta</button>
      <button id="btnDis" disabled>Desconectar</button>
    </div>
    <div class="right">
      <button id="btnMou">Modo Ratón</button>
      <button class="bd" id="btnClr" disabled>Limpiar</button>
      <button class="bg" id="btnSav" disabled>Guardar PNG</button>
    </div>
  </div>

  <div class="info-bar">
    <span>DPR: <strong id="iDpr">—</strong></span>
    <span>Trazos: <strong id="iStr">0</strong></span>
    <span>Puntos: <strong id="iPts">0</strong></span>
    <span>Presión máx: <strong id="iMaxP">—</strong></span>
    <span>Modo: <strong id="iMod">—</strong></span>
  </div>
</div>

<div id="dbPanel">
  <div class="dbh" id="dbTog">
    <span class="t" id="dbTit">▶ DIAGNÓSTICO HID</span>
    <span class="s" id="dbBtn">MOSTRAR</span>
  </div>
  <div id="dbBody" class="h">
    <div id="dbLog">Conecta la tableta para ver los eventos.\n</div>
    <div class="dbb">
      <button id="bClr">Limpiar</button>
      <button id="bPause">Pausar</button>
      <button id="bTip">Solo tip=1</button>
      <span style="font-family:var(--mono);font-size:.6rem;color:#2a3f55;margin-left:auto" id="dbCnt">0 ev.</span>
    </div>
  </div>
</div>

<div id="toast"></div>

<script>
/* ══════════════════════════════════════════════════════════════════════
   CAUSA REAL DE LOS ESCALONES: devicePixelRatio no aplicado al canvas
   ──────────────────────────────────────────────────────────────────────
   En pantallas HiDPI (Retina, 4K, la mayoría de portátiles modernos)
   window.devicePixelRatio es 2 o superior.

   Si el canvas tiene width=640 CSS pero sólo 640 píxeles físicos,
   cada "píxel de canvas" ocupa 4 píxeles reales de pantalla
   → las líneas se ven pixeladas y escalonadas sin remedio posible.

   La solución correcta:
     canvas.width  = cssWidth  * dpr   ← píxeles físicos reales
     canvas.height = cssHeight * dpr
     canvas.style.width  = cssWidth  + 'px'   ← tamaño CSS sin cambiar
     canvas.style.height = cssHeight + 'px'
     ctx.scale(dpr, dpr)               ← escalar el contexto de vuelta

   Con esto, cada operación de dibujo usa coordenadas CSS normales
   pero el renderizador trabaja con la resolución física real.
   El antialiasing del navegador opera sobre la resolución completa
   → trazos perfectamente suaves.
   ══════════════════════════════════════════════════════════════════════ */

'use strict';

// ── Parámetros STU-500B ───────────────────────────────────────────
const VID   = 0x056A;
const XMAX  = 9600;
const YMAX  = 7200;
const PMAX  = 511;

// ── Canvas con DPR correcto ───────────────────────────────────────
const cvs  = document.getElementById('cvs');
const ctx  = cvs.getContext('2d');
const wrap = document.getElementById('sigWrap');

// El DPR del dispositivo actual
const DPR = window.devicePixelRatio || 1;

// Dimensiones CSS: aspect ratio 4:3, ancho = 100% del contenedor
// Se calculan al iniciar y al redimensionar
let CSS_W, CSS_H;

function resizeCanvas() {
  CSS_W = wrap.clientWidth;
  CSS_H = Math.round(CSS_W * 0.75);  // ratio 4:3 (640:480)

  // Fijar altura CSS del wrapper para que no colapase
  wrap.style.height = CSS_H + 'px';

  // Dimensiones físicas = CSS × DPR
  cvs.width  = CSS_W * DPR;
  cvs.height = CSS_H * DPR;

  // Tamaño visual en pantalla = CSS (sin cambio visual)
  cvs.style.width  = CSS_W + 'px';
  cvs.style.height = CSS_H + 'px';

  // Escalar el contexto para que las coordenadas de dibujo
  // correspondan al espacio CSS (no al espacio físico ampliado)
  ctx.scale(DPR, DPR);

  // Limpiar con fondo
  ctx.fillStyle = '#fafaf8';
  ctx.fillRect(0, 0, CSS_W, CSS_H);

  document.getElementById('iDpr').textContent = DPR + 'x  (' + cvs.width + '×' + cvs.height + ' físicos)';
}

resizeCanvas();
window.addEventListener('resize', () => {
  // Al redimensionar hay que redibujar (los trazos se pierden,
  // pero es comportamiento estándar de canvas)
  resizeCanvas();
});

// ── Coordenadas tableta → espacio CSS (el ctx ya escala a físico) ─
function tabToCss(rx, ry) {
  return {
    x: (rx / XMAX) * CSS_W,
    y: (ry / YMAX) * CSS_H,
  };
}

// ══════════════════════════════════════════════════════════════════
//  DIBUJO — curveTo por punto medio
//  Con DPR correcto y lineWidth fijo, CERO escalones.
// ══════════════════════════════════════════════════════════════════
let prevX = 0, prevY = 0;
let midPrevX = null, midPrevY = null;
let strokeActive = false;

function penBegin(x, y) {
  prevX = x; prevY = y;
  midPrevX = null; midPrevY = null;
  strokeActive = true;

  ctx.fillStyle = 'rgba(10,20,40,0.88)';
  ctx.beginPath();
  ctx.arc(x, y, 0.8, 0, Math.PI * 2);
  ctx.fill();
}

function penMove(x, y) {
  if (!strokeActive) return;

  const midX = (prevX + x) / 2;
  const midY = (prevY + y) / 2;

  ctx.lineWidth   = 1.6;         // en espacio CSS; físicamente será 1.6×DPR
  ctx.strokeStyle = 'rgba(10,20,40,0.88)';
  ctx.lineCap     = 'round';
  ctx.lineJoin    = 'round';
  ctx.beginPath();

  if (midPrevX === null) {
    ctx.moveTo(prevX, prevY);
    ctx.lineTo(midX, midY);
  } else {
    ctx.moveTo(midPrevX, midPrevY);
    ctx.quadraticCurveTo(prevX, prevY, midX, midY);
  }
  ctx.stroke();

  midPrevX = midX;
  midPrevY = midY;
  prevX = x;
  prevY = y;
}

function penEnd() {
  if (strokeActive && midPrevX !== null) {
    ctx.lineWidth   = 1.6;
    ctx.strokeStyle = 'rgba(10,20,40,0.88)';
    ctx.lineCap     = 'round';
    ctx.beginPath();
    ctx.moveTo(midPrevX, midPrevY);
    ctx.lineTo(prevX, prevY);
    ctx.stroke();
  }
  strokeActive = false;
  midPrevX = null; midPrevY = null;
}

// ── Estado ────────────────────────────────────────────────────────
let hid       = null;
let mouseMode = false;
let down      = false;
let nStr = 0, nPts = 0, maxP = 0, hasSig = false;
let dbPause = false, dbTip = false, dbOpen = false, dbN = 0, dbBuf = [];

// ── DOM ───────────────────────────────────────────────────────────
const $  = id => document.getElementById(id);
const ui = {
  btnCon:$('btnCon'), btnDis:$('btnDis'), btnMou:$('btnMou'),
  btnClr:$('btnClr'), btnSav:$('btnSav'),
  pill:$('pill'), pillTxt:$('pillTxt'), devName:$('devName'),
  hint:$('hint'), pFill:$('pFill'),
  iStr:$('iStr'), iPts:$('iPts'), iMaxP:$('iMaxP'), iMod:$('iMod'),
  dbLog:$('dbLog'), dbTit:$('dbTit'), dbBtn:$('dbBtn'), dbCnt:$('dbCnt'),
};

if (!navigator.hid) { $('noHid').classList.add('show'); ui.btnCon.disabled = true; }

const setPres = p => ui.pFill.style.height = Math.round(p*100) + '%';
const setStatus = (cls,txt) => { ui.pill.className=cls; ui.pill.id='pill'; ui.pillTxt.textContent=txt; };
const markSig = () => {
  if (hasSig) return; hasSig=true;
  ui.hint.classList.add('gone');
  ui.btnClr.disabled=false; ui.btnSav.disabled=false;
};
const stats = () => {
  ui.iStr.textContent  = nStr;
  ui.iPts.textContent  = nPts;
  ui.iMaxP.textContent = maxP>0 ? Math.round(maxP/PMAX*100)+'%' : '—';
  ui.iMod.textContent  = mouseMode ? 'Ratón' : (hid ? 'HID Wacom' : '—');
};

function log(html) {
  if (dbPause) return;
  dbBuf.push(html);
  if (dbBuf.length > 500) dbBuf.shift();
  ui.dbLog.innerHTML = dbBuf.join('\n');
  ui.dbLog.scrollTop = ui.dbLog.scrollHeight;
}
function toggleDb() {
  dbOpen=!dbOpen;
  $('dbBody').classList.toggle('h',!dbOpen);
  ui.dbTit.textContent=(dbOpen?'▼':'▶')+' DIAGNÓSTICO HID';
  ui.dbBtn.textContent=dbOpen?'OCULTAR':'MOSTRAR';
}

// ══════════════════════════════════════════════════════════════════
//  PARSEO HID — STU-500B
// ══════════════════════════════════════════════════════════════════
function parse(data) {
  if (data.byteLength < 7) return null;
  const st = data.getUint8(0);
  const tip = !!(st & 0x01);
  const rx  = data.getUint16(1, true);
  const ry  = data.getUint16(3, true);
  const rp  = data.getUint16(5, true);
  const c   = tabToCss(rx, ry);
  return { tip, x:c.x, y:c.y, rx, ry, rp, st };
}

function onReport(e) {
  const pen = parse(e.data);
  if (!pen) return;
  dbN++; ui.dbCnt.textContent = dbN+' ev.';
  setPres(Math.min(1, pen.rp/PMAX));

  if (!dbPause && (!dbTip || pen.tip)) {
    log(`<span class="${pen.tip?'lt':'li'}">tip=${pen.tip?'▶1◀':' 0 '}</span>`+
        `  x=${String(pen.rx).padStart(4)}  y=${String(pen.ry).padStart(4)}  p=${String(pen.rp).padStart(3)}`);
  }

  if (pen.tip) {
    if (!down) {
      down=true; markSig(); setStatus('ink','FIRMANDO...');
      penBegin(pen.x, pen.y);
    } else {
      penMove(pen.x, pen.y);
    }
    nPts++; if (pen.rp>maxP) maxP=pen.rp; stats();
  } else {
    if (down) {
      down=false; penEnd(); nStr++;
      setStatus('ok','CONECTADO'); setPres(0); stats();
    }
  }
}

// ══════════════════════════════════════════════════════════════════
//  WebHID
// ══════════════════════════════════════════════════════════════════
async function connect() {
  if (!navigator.hid) return;
  try {
    const devs = await navigator.hid.requestDevice({ filters:[{ vendorId:VID }] });
    if (!devs?.length) { toast('No se seleccionó dispositivo.','err'); return; }
    hid = devs[0];
    if (!hid.opened) await hid.open();
    hid.addEventListener('inputreport', onReport);

    const pid = hid.productId.toString(16).toUpperCase();
    ui.devName.textContent = `${hid.productName||'Wacom STU-500B'}  [0x${pid}]`;
    setStatus('ok','CONECTADO');
    ui.btnCon.disabled=true; ui.btnDis.disabled=false;
    ui.btnClr.disabled=false; mouseMode=false; stats();
    if (!dbOpen) toggleDb();
    log(`<span class="ls">✓ ${hid.productName}  PID:0x${pid}  DPR:${DPR}</span>`);
    log(`<span class="li">Canvas físico: ${cvs.width}×${cvs.height}px — CSS: ${CSS_W}×${CSS_H}px</span>`);
    toast('✓ Tableta conectada.','ok');

    navigator.hid.addEventListener('disconnect', ({device}) => {
      if (device!==hid) return;
      hid=null; down=false; penEnd();
      ui.devName.textContent='Desconectado';
      setStatus('err','DESCONECTADO');
      ui.btnCon.disabled=false; ui.btnDis.disabled=true;
      log('<span class="le">✗ Desconectado.</span>');
      toast('Tableta desconectada.','err'); stats();
    });
  } catch(err) {
    toast('Error: '+err.message,'err');
    log(`<span class="le">Error: ${err.message}</span>`);
  }
}

async function disconnect() {
  if (!hid) return;
  try { hid.removeEventListener('inputreport',onReport); await hid.close(); } catch(_) {}
  hid=null; down=false; penEnd();
  ui.devName.textContent='Sin conectar'; setStatus('','DESCONECTADO');
  ui.btnCon.disabled=false; ui.btnDis.disabled=true;
  mouseMode=false; stats(); toast('Desconectado.','ok');
}

// ══════════════════════════════════════════════════════════════════
//  MODO RATÓN
// ══════════════════════════════════════════════════════════════════
function cpos(e) {
  const r=cvs.getBoundingClientRect();
  const s=e.touches?e.touches[0]:e;
  // Los eventos del ratón están en espacio CSS, igual que el ctx escalado
  return {
    x:(s.clientX-r.left)*(CSS_W/r.width),
    y:(s.clientY-r.top )*(CSS_H/r.height),
  };
}

cvs.addEventListener('mousedown', e => {
  if (!mouseMode) return; e.preventDefault();
  down=true; markSig(); setStatus('ink','FIRMANDO...');
  const p=cpos(e); penBegin(p.x,p.y); nPts++; stats();
});
cvs.addEventListener('mousemove', e => {
  if (!mouseMode||!down) return; e.preventDefault();
  const p=cpos(e); penMove(p.x,p.y); nPts++; stats();
});
cvs.addEventListener('mouseup', e => {
  if (!mouseMode||!down) return; e.preventDefault();
  down=false; penEnd(); nStr++;
  setStatus(hid?'ok':'ink',hid?'CONECTADO':'MODO RATÓN'); setPres(0); stats();
});
cvs.addEventListener('mouseleave', e => {
  if (mouseMode&&down) cvs.dispatchEvent(new MouseEvent('mouseup'));
});
cvs.addEventListener('touchstart', e => {
  if (!mouseMode) return; e.preventDefault();
  down=true; markSig(); setStatus('ink','FIRMANDO...');
  const p=cpos(e); penBegin(p.x,p.y); nPts++; stats();
},{passive:false});
cvs.addEventListener('touchmove', e => {
  if (!mouseMode||!down) return; e.preventDefault();
  const p=cpos(e); penMove(p.x,p.y); nPts++; stats();
},{passive:false});
cvs.addEventListener('touchend', e => {
  if (!mouseMode||!down) return; e.preventDefault();
  down=false; penEnd(); nStr++;
  setStatus(hid?'ok':'ink',hid?'CONECTADO':'MODO RATÓN'); setPres(0); stats();
},{passive:false});

// ══════════════════════════════════════════════════════════════════
//  BOTONES
// ══════════════════════════════════════════════════════════════════
ui.btnCon.addEventListener('click', connect);
ui.btnDis.addEventListener('click', disconnect);

ui.btnMou.addEventListener('click', () => {
  mouseMode=!mouseMode;
  if (mouseMode) {
    ui.btnMou.textContent='✓ Ratón ON';
    ui.btnMou.style.cssText='background:var(--accent);color:#fff;border-color:var(--accent)';
    setStatus('ink','MODO RATÓN'); ui.btnClr.disabled=false;
    toast('Modo ratón activado.','ok');
  } else {
    ui.btnMou.textContent='Modo Ratón'; ui.btnMou.style.cssText='';
    down=false; penEnd();
    setStatus(hid?'ok':'',hid?'CONECTADO':'DESCONECTADO');
  }
  stats();
});

ui.btnClr.addEventListener('click', () => {
  down=false; penEnd();
  ctx.fillStyle='#fafaf8'; ctx.fillRect(0,0,CSS_W,CSS_H);
  nStr=0; nPts=0; maxP=0; hasSig=false;
  ui.hint.classList.remove('gone'); ui.btnSav.disabled=true;
  if (!mouseMode&&!hid) ui.btnClr.disabled=true;
  setPres(0);
  setStatus(hid?'ok':(mouseMode?'ink':''), hid?'CONECTADO':(mouseMode?'MODO RATÓN':'DESCONECTADO'));
  stats(); toast('Firma eliminada.','ok');
});

ui.btnSav.addEventListener('click', () => {
  if (!hasSig) return;
  // Exportar a tamaño CSS (el canvas físico es DPR× mayor pero queremos 640×480)
  const off=document.createElement('canvas');
  off.width=CSS_W; off.height=CSS_H;
  const o=off.getContext('2d');
  o.fillStyle='#fff'; o.fillRect(0,0,CSS_W,CSS_H);
  // Dibujar el canvas físico escalado a CSS → downsampling = máxima nitidez
  o.drawImage(cvs, 0,0, CSS_W,CSS_H);
  const a=document.createElement('a');
  a.download='firma_'+new Date().toISOString().replace(/[:.]/g,'-').slice(0,19)+'.png';
  a.href=off.toDataURL('image/png'); a.click();
  toast('✓ Firma guardada.','ok');
});

// ── Diagnóstico ───────────────────────────────────────────────────
$('dbTog').addEventListener('click', toggleDb);
$('bClr').addEventListener('click', () => { dbBuf=[]; dbN=0; ui.dbLog.innerHTML=''; ui.dbCnt.textContent='0 ev.'; });
$('bPause').addEventListener('click', function(){
  dbPause=!dbPause; this.textContent=dbPause?'Reanudar':'Pausar';
  if (!dbPause){ ui.dbLog.innerHTML=dbBuf.join('\n'); ui.dbLog.scrollTop=ui.dbLog.scrollHeight; }
});
$('bTip').addEventListener('click', function(){
  dbTip=!dbTip; this.textContent=dbTip?'✓ Solo tip=1':'Solo tip=1';
  this.style.color=dbTip?'#c8a96e':'';
});

// ── Toast ─────────────────────────────────────────────────────────
let toastT;
function toast(msg,type) {
  const t=$('toast'); t.textContent=msg;
  t.className='show'+(type?' '+type:'');
  clearTimeout(toastT); toastT=setTimeout(()=>t.className='',3300);
}

// ── Init ──────────────────────────────────────────────────────────
setStatus('','DESCONECTADO'); stats();
log(`<span class="ls">DPR del dispositivo: ${DPR}  — Canvas físico: ${cvs.width}×${cvs.height}px</span>`);
log(`<span class="li">Pulsa "Conectar Tableta" y selecciona el Wacom STU-500B.</span>`);
</script>
</body>
</html>