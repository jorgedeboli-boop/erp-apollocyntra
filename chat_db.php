<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Asistente · Datos test git</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600&family=JetBrains+Mono:wght@400;500&family=Playfair+Display:ital,wght@0,600;1,400&display=swap" rel="stylesheet">
<style>
:root {
  --bg:          #0b0b0d;
  --surface:     #111115;
  --surface2:    #18181f;
  --surface3:    #1e1e28;
  --border:      #25252f;
  --border2:     #2e2e3a;
  --gold:        #d4a843;
  --gold-light:  #e8c46a;
  --gold-dim:    #8c6d28;
  --gold-glow:   rgba(212,168,67,0.12);
  --gold-glow2:  rgba(212,168,67,0.06);
  --text:        #eceae3;
  --text-dim:    #8a8880;
  --text-muted:  #4a4846;
  --user-bg:     #16162a;
  --user-border: #2d2b52;
  --user-text:   #c5c2f5;
  --error-bg:    #180f0f;
  --error-border:#4a2020;
  --error-text:  #d47070;
  --r:           12px;
  --r-sm:        8px;
  --font:        'Sora', sans-serif;
  --mono:        'JetBrains Mono', monospace;
  --serif:       'Playfair Display', serif;
  --shadow:      0 4px 24px rgba(0,0,0,0.4);
}

*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

html, body {
  height: 100%;
  overflow: hidden;
  background: var(--bg);
  color: var(--text);
  font-family: var(--font);
  font-size: 14px;
  -webkit-font-smoothing: antialiased;
}

/* ──────────────────── LAYOUT ──────────────────── */
.app {
  display: flex;
  flex-direction: column;
  height: 100vh;
}

/* ──────────────────── HEADER ──────────────────── */
.header {
  flex-shrink: 0;
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 13px 22px;
  background: var(--surface);
  border-bottom: 1px solid var(--border);
  position: relative;
}

.header::after {
  content: '';
  position: absolute;
  bottom: 0; left: 0; right: 0;
  height: 1px;
  background: linear-gradient(90deg, transparent, var(--gold-dim), transparent);
  opacity: 0.5;
}

.logo {
  width: 36px; height: 36px;
  background: linear-gradient(135deg, var(--gold) 0%, var(--gold-dim) 100%);
  border-radius: 10px;
  display: flex; align-items: center; justify-content: center;
  font-size: 18px;
  flex-shrink: 0;
  box-shadow: 0 0 16px rgba(212,168,67,0.25);
}

.header-info { flex: 1; }

.header-title {
  font-family: var(--serif);
  font-size: 17px;
  color: var(--text);
  line-height: 1;
}

.header-sub {
  font-size: 11px;
  color: var(--text-muted);
  margin-top: 3px;
  font-family: var(--mono);
  letter-spacing: 0.04em;
}

.header-actions {
  display: flex;
  align-items: center;
  gap: 8px;
}

.badge {
  font-size: 10px;
  font-family: var(--mono);
  color: var(--gold-dim);
  background: var(--gold-glow2);
  border: 1px solid rgba(212,168,67,0.18);
  padding: 3px 10px;
  border-radius: 20px;
  letter-spacing: 0.06em;
}

.btn-reset {
  font-size: 11px;
  font-family: var(--mono);
  color: var(--text-muted);
  background: none;
  border: 1px solid var(--border2);
  padding: 5px 12px;
  border-radius: var(--r-sm);
  cursor: pointer;
  transition: color 0.2s, border-color 0.2s;
  letter-spacing: 0.04em;
}
.btn-reset:hover { color: var(--gold); border-color: var(--gold-dim); }

/* ──────────────────── MESSAGES ──────────────────── */
.messages {
  flex: 1;
  overflow-y: auto;
  overflow-x: hidden;
  padding: 28px 24px;
  display: flex;
  flex-direction: column;
  gap: 18px;
  scroll-behavior: smooth;
}

.messages::-webkit-scrollbar { width: 3px; }
.messages::-webkit-scrollbar-track { background: transparent; }
.messages::-webkit-scrollbar-thumb { background: var(--border2); border-radius: 2px; }

/* ──────────────────── WELCOME ──────────────────── */
.welcome {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 18px;
  padding: 48px 24px 24px;
  text-align: center;
  animation: fadeUp 0.7s ease forwards;
}

.welcome-icon {
  width: 64px; height: 64px;
  background: radial-gradient(circle at 35% 35%, var(--gold-light), var(--gold-dim));
  border-radius: 18px;
  display: flex; align-items: center; justify-content: center;
  font-size: 30px;
  box-shadow: 0 0 32px rgba(212,168,67,0.2), 0 8px 32px rgba(0,0,0,0.4);
  margin-bottom: 4px;
}

.welcome h2 {
  font-family: var(--serif);
  font-size: 26px;
  font-weight: 600;
  color: var(--text);
  line-height: 1.2;
}

.welcome p {
  color: var(--text-dim);
  font-size: 13px;
  max-width: 400px;
  line-height: 1.7;
}

.chips-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 8px;
  width: 100%;
  max-width: 560px;
  margin-top: 6px;
}

.chip {
  background: var(--surface2);
  border: 1px solid var(--border);
  border-radius: var(--r-sm);
  padding: 9px 14px;
  font-size: 12px;
  color: var(--text-dim);
  cursor: pointer;
  text-align: left;
  transition: all 0.2s;
  line-height: 1.4;
  font-family: var(--font);
}
.chip:hover {
  background: var(--gold-glow);
  border-color: var(--gold-dim);
  color: var(--gold-light);
}

/* ──────────────────── MESSAGE BUBBLES ──────────────────── */
.msg {
  display: flex;
  flex-direction: column;
  gap: 5px;
  max-width: 860px;
  opacity: 0;
  animation: fadeUp 0.35s ease forwards;
}

.msg.user  { align-self: flex-end;  }
.msg.assistant { align-self: flex-start; }

.msg-label {
  font-size: 9px;
  font-family: var(--mono);
  letter-spacing: 0.1em;
  text-transform: uppercase;
  color: var(--text-muted);
  padding: 0 4px;
}
.msg.user .msg-label { text-align: right; }

.bubble {
  padding: 13px 17px;
  border-radius: var(--r);
  line-height: 1.7;
  font-size: 13.5px;
  word-break: break-word;
}

.msg.user .bubble {
  background: var(--user-bg);
  border: 1px solid var(--user-border);
  border-radius: var(--r) var(--r) 3px var(--r);
  color: var(--user-text);
}

.msg.assistant .bubble {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--r) var(--r) var(--r) 3px;
  color: var(--text);
}

.msg.assistant .bubble.is-error {
  background: var(--error-bg);
  border-color: var(--error-border);
  color: var(--error-text);
}

/* Markdown dentro de bubble */
.bubble p { margin-bottom: 8px; }
.bubble p:last-child { margin-bottom: 0; }

.bubble strong { color: var(--gold); font-weight: 600; }

.bubble ul, .bubble ol {
  padding-left: 20px;
  margin-bottom: 10px;
}
.bubble li { margin-bottom: 4px; }

.bubble table {
  width: 100%;
  border-collapse: collapse;
  margin: 10px 0;
  font-size: 12.5px;
}
.bubble th {
  background: var(--surface3);
  color: var(--gold);
  padding: 8px 12px;
  text-align: left;
  font-family: var(--mono);
  font-size: 10px;
  letter-spacing: 0.06em;
  text-transform: uppercase;
  border-bottom: 1px solid var(--border2);
  font-weight: 500;
}
.bubble td {
  padding: 7px 12px;
  border-bottom: 1px solid rgba(37,37,47,0.7);
  color: var(--text);
}
.bubble tr:last-child td { border-bottom: none; }
.bubble tr:hover td { background: rgba(255,255,255,0.015); }

/* ──────────────────── SQL BLOCK ──────────────────── */
.sql-block { margin-top: 10px; }

.sql-btn {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-size: 10px;
  font-family: var(--mono);
  color: var(--text-muted);
  background: none;
  border: none;
  cursor: pointer;
  padding: 2px 0;
  letter-spacing: 0.07em;
  transition: color 0.2s;
}
.sql-btn:hover { color: var(--gold-dim); }
.sql-btn .chevron { transition: transform 0.2s; }
.sql-btn.open .chevron { transform: rotate(90deg); }

.sql-rows-badge {
  background: var(--surface3);
  border: 1px solid var(--border);
  color: var(--text-muted);
  font-size: 9px;
  padding: 1px 7px;
  border-radius: 10px;
  margin-left: 4px;
}

.sql-pre {
  display: none;
  margin-top: 8px;
  background: #080809;
  border: 1px solid var(--border);
  border-radius: var(--r-sm);
  padding: 13px 16px;
  font-family: var(--mono);
  font-size: 11.5px;
  color: #85b87a;
  white-space: pre-wrap;
  word-break: break-all;
  line-height: 1.65;
  position: relative;
}
.sql-pre.open { display: block; }

.copy-btn {
  position: absolute;
  top: 9px; right: 9px;
  font-size: 9px;
  font-family: var(--mono);
  color: var(--text-muted);
  background: var(--surface2);
  border: 1px solid var(--border);
  padding: 2px 8px;
  border-radius: 4px;
  cursor: pointer;
  transition: all 0.2s;
}
.copy-btn:hover { color: var(--gold); border-color: var(--gold-dim); }

/* ──────────────────── TYPING ──────────────────── */
.typing-msg {
  display: flex;
  flex-direction: column;
  gap: 5px;
  align-self: flex-start;
  opacity: 0;
  animation: fadeUp 0.3s ease forwards;
}

.typing-bubble {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 14px 18px;
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--r) var(--r) var(--r) 3px;
}

.dot {
  width: 5px; height: 5px;
  background: var(--gold-dim);
  border-radius: 50%;
  animation: pulse 1.3s ease-in-out infinite;
}
.dot:nth-child(2) { animation-delay: 0.18s; }
.dot:nth-child(3) { animation-delay: 0.36s; }

/* ──────────────────── INPUT AREA ──────────────────── */
.input-area {
  flex-shrink: 0;
  padding: 14px 22px 18px;
  background: var(--surface);
  border-top: 1px solid var(--border);
}

.input-box {
  display: flex;
  align-items: flex-end;
  gap: 10px;
  background: var(--surface2);
  border: 1px solid var(--border2);
  border-radius: var(--r);
  padding: 11px 13px;
  transition: border-color 0.2s, box-shadow 0.2s;
}
.input-box:focus-within {
  border-color: var(--gold-dim);
  box-shadow: 0 0 0 3px rgba(212,168,67,0.07);
}

#userInput {
  flex: 1;
  background: none;
  border: none;
  outline: none;
  color: var(--text);
  font-family: var(--font);
  font-size: 14px;
  resize: none;
  line-height: 1.5;
  min-height: 22px;
  max-height: 130px;
}
#userInput::placeholder { color: var(--text-muted); }

.send-btn {
  width: 34px; height: 34px;
  background: linear-gradient(135deg, var(--gold), var(--gold-dim));
  border: none;
  border-radius: var(--r-sm);
  cursor: pointer;
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
  transition: opacity 0.2s, transform 0.1s;
  color: #0b0b0d;
}
.send-btn:hover:not(:disabled) { opacity: 0.85; }
.send-btn:active:not(:disabled) { transform: scale(0.93); }
.send-btn:disabled { opacity: 0.28; cursor: not-allowed; }

.input-hint {
  text-align: center;
  font-size: 10px;
  font-family: var(--mono);
  color: var(--text-muted);
  margin-top: 8px;
  letter-spacing: 0.03em;
}

/* ──────────────────── ANIMATIONS ──────────────────── */
@keyframes fadeUp {
  from { opacity: 0; transform: translateY(6px); }
  to   { opacity: 1; transform: translateY(0); }
}
@keyframes pulse {
  0%, 80%, 100% { transform: scale(1);    opacity: 0.5; }
  40%           { transform: scale(1.35); opacity: 1; }
}
</style>
</head>
<body>
<div class="app">

  <!-- HEADER -->
  <header class="header">
    <div class="logo">💎</div>
    <div class="header-info">
      <div class="header-title">Asistente de Datos</div>
      <div class="header-sub">GEMINI 2.0 FLASH · TEXT-TO-SQL</div>
    </div>
    <div class="header-actions">
      <span class="badge">LIVE</span>
      <button class="btn-reset" onclick="resetChat()">↺ Nueva consulta</button>
    </div>
  </header>

  <!-- MESSAGES -->
  <main class="messages" id="messages">
    <!-- Welcome se inyecta por JS -->
  </main>

  <!-- INPUT -->
  <div class="input-area">
    <div class="input-box">
      <textarea
        id="userInput"
        placeholder="Escribe tu consulta en lenguaje natural…"
        rows="1"
        onkeydown="onKey(event)"
        oninput="autoGrow(this)"
      ></textarea>
      <button class="send-btn" id="sendBtn" onclick="send()" title="Enviar">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor">
          <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/>
        </svg>
      </button>
    </div>
    <div class="input-hint">Enter para enviar &nbsp;·&nbsp; Shift+Enter nueva línea</div>
  </div>

</div>

<script>
// ── Configuración ──────────────────────────────────────────
const API = 'chat_db_api.php';

// ── Estado ────────────────────────────────────────────────
let historial = [];
let busy      = false;

// ── Chips de bienvenida ────────────────────────────────────
const CHIPS = [
  '¿Cuántas ventas hubo este mes?',
  'Top 10 artículos más vendidos',
  'Ventas totales por sucursal',
  'Clientes nuevos últimos 30 días',
  'Artículos de oro en venta con precio y peso',
  'Ventas del mes pasado por tipo de pago',
];

// ── Inicialización ─────────────────────────────────────────
function renderWelcome() {
  const chipsHTML = CHIPS.map(c =>
    `<button class="chip" onclick="useChip(this)">${esc(c)}</button>`
  ).join('');

  return `
    <div class="welcome">
      <div class="welcome-icon">💎</div>
      <h2>¿Qué quieres consultar?</h2>
      <p>Pregunta en lenguaje natural sobre clientes, artículos y ventas. Genero el informe automáticamente.</p>
      <div class="chips-grid">${chipsHTML}</div>
    </div>`;
}

document.getElementById('messages').innerHTML = renderWelcome();
document.getElementById('userInput').focus();

// ── Utilidades ─────────────────────────────────────────────
function esc(t) {
  return String(t)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;');
}

function scrollEnd() {
  const el = document.getElementById('messages');
  el.scrollTop = el.scrollHeight;
}

function autoGrow(el) {
  el.style.height = 'auto';
  el.style.height = Math.min(el.scrollHeight, 130) + 'px';
}

function onKey(e) {
  if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); send(); }
}

function useChip(btn) {
  document.getElementById('userInput').value = btn.textContent;
  send();
}

// ── Markdown ligero ────────────────────────────────────────
function md(text) {
  // Tablas
  text = text.replace(
    /\|(.+)\|\n\|[-|: ]+\|\n((?:\|.+\|\n?)+)/g,
    (_, header, body) => {
      const ths = header.split('|').filter(c => c.trim())
        .map(c => `<th>${c.trim()}</th>`).join('');
      const rows = body.trim().split('\n').map(row => {
        const tds = row.split('|').filter(c => c.trim())
          .map(c => `<td>${c.trim()}</td>`).join('');
        return `<tr>${tds}</tr>`;
      }).join('');
      return `<table><thead><tr>${ths}</tr></thead><tbody>${rows}</tbody></table>`;
    }
  );
  // Bold
  text = text.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');
  // Listas con guión
  text = text.replace(/^[-•]\s+(.+)/gm, '<li>$1</li>');
  text = text.replace(/(<li>[\s\S]+?<\/li>)(?!<li>)/g, '<ul>$1</ul>');
  // Saltos y párrafos
  const blocks = text.split(/\n{2,}/);
  return blocks.map(b => {
    b = b.trim();
    if (!b) return '';
    if (b.startsWith('<table') || b.startsWith('<ul')) return b;
    return `<p>${b.replace(/\n/g, '<br>')}</p>`;
  }).join('');
}

// ── Añadir mensaje ─────────────────────────────────────────
let uid = 0;

function addMsg(role, text, sql, filas) {
  // Quitar welcome si existe
  const w = document.querySelector('.welcome');
  if (w) w.remove();

  const id  = 'msg-' + (++uid);
  const div = document.createElement('div');
  div.className = `msg ${role}`;
  div.id = id;

  const label = role === 'user' ? 'TÚ' : 'ASISTENTE';
  const isError = role === 'assistant' && text.startsWith('⚠');
  const bubbleClass = isError ? 'bubble is-error' : 'bubble';

  let inner = `<div class="msg-label">${label}</div>`;
  inner += `<div class="${bubbleClass}">`;
  inner += role === 'user' ? `<p>${esc(text)}</p>` : md(text);
  inner += '</div>';

  // Bloque SQL colapsable (solo en respuestas con SQL)
  if (sql && role === 'assistant') {
    const sqlId  = 'sql-' + uid;
    const rowsTxt = filas + ' fila' + (filas !== 1 ? 's' : '');
    inner += `
      <div class="sql-block">
        <button class="sql-btn" id="btn-${sqlId}" onclick="toggleSQL('${sqlId}')">
          <span class="chevron">▶</span>
          VER SQL GENERADO
          <span class="sql-rows-badge">${rowsTxt}</span>
        </button>
        <div class="sql-pre" id="${sqlId}">
          <button class="copy-btn" onclick="copySQL('${sqlId}', this)">copiar</button>${esc(sql)}
        </div>
      </div>`;
  }

  div.innerHTML = inner;
  document.getElementById('messages').appendChild(div);
  scrollEnd();
}

function toggleSQL(id) {
  document.getElementById(id).classList.toggle('open');
  document.getElementById('btn-' + id).classList.toggle('open');
}

function copySQL(id, btn) {
  const pre  = document.getElementById(id);
  const text = pre.textContent.replace('copiar', '').trim();
  navigator.clipboard.writeText(text).then(() => {
    btn.textContent = '✓ copiado';
    setTimeout(() => btn.textContent = 'copiar', 2000);
  });
}

// ── Typing indicator ───────────────────────────────────────
function showTyping() {
  const div = document.createElement('div');
  div.className = 'typing-msg';
  div.id = 'typing';
  div.innerHTML = `
    <div class="msg-label">ASISTENTE</div>
    <div class="typing-bubble">
      <div class="dot"></div><div class="dot"></div><div class="dot"></div>
    </div>`;
  document.getElementById('messages').appendChild(div);
  scrollEnd();
}

function hideTyping() {
  const t = document.getElementById('typing');
  if (t) t.remove();
}

// ── Enviar ─────────────────────────────────────────────────
async function send() {
  if (busy) return;

  const input = document.getElementById('userInput');
  const texto = input.value.trim();
  if (!texto) return;

  input.value = '';
  input.style.height = 'auto';
  busy = true;
  document.getElementById('sendBtn').disabled = true;

  addMsg('user', texto, null, 0);
  showTyping();

  try {
    const res  = await fetch(API, {
      method:  'POST',
      headers: { 'Content-Type': 'application/json' },
      body:    JSON.stringify({ message: texto, historial })
    });

    if (!res.ok) throw new Error('HTTP ' + res.status);

    const data = await res.json();
    hideTyping();

    if (data.error) {
      addMsg('assistant', '⚠ ' + data.error, null, 0);
    } else {
      addMsg('assistant', data.response, data.sql || null, data.filas || 0);

      // ✅ Actualizar historial DESPUÉS de recibir respuesta (evita duplicados)
      historial.push({ role: 'user',      content: texto });
      historial.push({ role: 'assistant', content: data.response, sql: data.sql || '' });

      // Mantener historial compacto (últimas 10 interacciones = 20 turnos)
      if (historial.length > 20) historial = historial.slice(-20);
    }

  } catch (err) {
    hideTyping();
    addMsg('assistant', '⚠ Error de conexión con el servidor. Comprueba que chat_db_api.php está accesible.', null, 0);
    console.error(err);
  }

  busy = false;
  document.getElementById('sendBtn').disabled = false;
  input.focus();
}

// ── Reset ──────────────────────────────────────────────────
function resetChat() {
  historial = [];
  document.getElementById('messages').innerHTML = renderWelcome();
  document.getElementById('userInput').focus();
}
</script>
</body>
</html>