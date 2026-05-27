<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/src/bootstrap.php';

$zaiaUrl  = Config::get('ZAIA_WEBHOOK_URL', '');
$botName  = Config::get('BOT_NAME', 'QueryBot');
$botSub   = Config::get('BOT_SUBTITLE', 'Pergunte sobre o seu delivery');
$botColor = Config::get('BOT_COLOR', '#7c3aed');
$debug    = Config::bool('APP_DEBUG');
$hasZaia  = $zaiaUrl !== '';

// Sugestões rápidas
$suggestions = [
    ['label' => 'Vendas hoje',        'msg' => 'Quanto vendemos hoje?'],
    ['label' => 'Vendas ontem',       'msg' => 'Quanto vendemos ontem?'],
    ['label' => 'Top produtos',       'msg' => 'Quais são os produtos mais vendidos?'],
    ['label' => 'Estoque baixo',      'msg' => 'Quais produtos estão com estoque baixo?'],
    ['label' => 'Últimos pedidos',    'msg' => 'Mostre os últimos pedidos.'],
    ['label' => 'Total de clientes',  'msg' => 'Quantos clientes temos cadastrados?'],
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($botName) ?></title>
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
  --primary:<?= $botColor ?>;
  --primary-dark:color-mix(in srgb,<?= $botColor ?> 80%,#000);
  --bg:#f5f5f5;
  --surface:#fff;
  --border:#e5e7eb;
  --text:#111827;
  --muted:#6b7280;
  --msg-user-bg:<?= $botColor ?>;
  --msg-bot-bg:#f3f4f6;
  --radius:16px;
  --sidebar:260px;
}
html,body{height:100%;overflow:hidden}
body{font-family:system-ui,-apple-system,sans-serif;background:var(--bg);display:flex;flex-direction:column}

/* ── Header ── */
.header{
  background:var(--primary);
  color:#fff;
  padding:14px 20px;
  display:flex;
  align-items:center;
  gap:12px;
  flex-shrink:0;
  box-shadow:0 2px 8px rgba(0,0,0,.2);
}
.header-avatar{
  width:42px;height:42px;
  background:rgba(255,255,255,.2);
  border-radius:50%;
  display:flex;align-items:center;justify-content:center;
  font-size:20px;flex-shrink:0;
}
.header-info h1{font-size:1rem;font-weight:600;line-height:1.2}
.header-info p{font-size:.78rem;opacity:.8}
.header-actions{margin-left:auto;display:flex;gap:8px}
.header-actions a,.header-actions button{
  color:#fff;background:rgba(255,255,255,.15);
  border:1px solid rgba(255,255,255,.25);
  padding:6px 12px;border-radius:8px;font-size:.78rem;
  cursor:pointer;text-decoration:none;display:flex;align-items:center;gap:5px;
  transition:background .15s;
}
.header-actions a:hover,.header-actions button:hover{background:rgba(255,255,255,.25)}

/* ── Layout ── */
.layout{display:flex;flex:1;overflow:hidden}

/* ── Sidebar ── */
.sidebar{
  width:var(--sidebar);
  background:var(--surface);
  border-right:1px solid var(--border);
  display:flex;flex-direction:column;
  padding:16px;gap:6px;overflow-y:auto;flex-shrink:0;
}
.sidebar h2{font-size:.68rem;text-transform:uppercase;letter-spacing:.06em;color:var(--muted);margin:12px 0 6px}
.sidebar h2:first-child{margin-top:0}
.quick-btn{
  background:none;border:1px solid var(--border);
  border-radius:10px;padding:9px 12px;
  color:var(--text);font-size:.82rem;cursor:pointer;
  text-align:left;transition:border-color .15s,background .15s;line-height:1.3;
}
.quick-btn:hover{border-color:var(--primary);background:color-mix(in srgb,var(--primary) 6%,#fff)}
.sidebar-link{
  display:flex;align-items:center;gap:8px;
  padding:8px 10px;border-radius:8px;
  color:var(--muted);font-size:.82rem;text-decoration:none;
  transition:background .15s,color .15s;
}
.sidebar-link:hover{background:var(--bg);color:var(--text)}
.sidebar-link svg{width:16px;height:16px;flex-shrink:0}

/* ── Main ── */
main{flex:1;display:flex;flex-direction:column;overflow:hidden;background:var(--surface)}

/* ── Tokens bar ── */
.tokens-bar{
  padding:6px 16px;font-size:.72rem;color:var(--muted);
  border-bottom:1px solid var(--border);text-align:center;
  background:color-mix(in srgb,var(--primary) 4%,#fff);
}

/* ── Messages ── */
.messages{flex:1;overflow-y:auto;padding:20px 16px;display:flex;flex-direction:column;gap:12px}
.msg-wrap{display:flex;flex-direction:column;max-width:78%}
.msg-wrap.user{align-self:flex-end;align-items:flex-end}
.msg-wrap.bot{align-self:flex-start;align-items:flex-start}
.msg-label{font-size:.68rem;color:var(--muted);margin-bottom:3px;padding:0 4px}
.msg{
  padding:11px 15px;border-radius:var(--radius);
  font-size:.9rem;line-height:1.55;word-break:break-word;
}
.msg.user{
  background:var(--primary);color:#fff;
  border-bottom-right-radius:4px;
}
.msg.bot{
  background:var(--msg-bot-bg);color:var(--text);
  border-bottom-left-radius:4px;border:1px solid var(--border);
}
.msg.bot strong{font-weight:700}
.msg.bot em{font-style:italic}
.msg.bot.debug-msg{
  background:#fefce8;border-color:#fde047;
  font-family:monospace;font-size:.78rem;color:#713f12;
}
.msg .time{font-size:.65rem;opacity:.6;margin-top:4px;display:block;text-align:right}
.typing{display:flex;gap:4px;align-items:center;padding:14px 16px}
.typing span{width:7px;height:7px;background:var(--muted);border-radius:50%;animation:bounce .9s infinite}
.typing span:nth-child(2){animation-delay:.15s}
.typing span:nth-child(3){animation-delay:.3s}
@keyframes bounce{0%,80%,100%{transform:translateY(0)}40%{transform:translateY(-6px)}}

/* ── Poll status ── */
.poll-info{
  font-size:.68rem;color:var(--muted);padding:2px 6px;
  background:var(--bg);border:1px solid var(--border);
  border-radius:6px;display:inline-block;
}

/* ── Input area ── */
.input-area{
  padding:12px 16px;border-top:1px solid var(--border);
  background:var(--surface);
}
.input-row{display:flex;gap:8px;align-items:flex-end}
.input-row textarea{
  flex:1;padding:10px 14px;
  border:1.5px solid var(--border);border-radius:12px;
  font-size:.9rem;font-family:inherit;
  resize:none;outline:none;min-height:44px;max-height:120px;
  transition:border-color .15s;line-height:1.4;
  color:var(--text);background:var(--bg);
}
.input-row textarea:focus{border-color:var(--primary)}
.send-btn{
  width:44px;height:44px;border-radius:12px;
  background:var(--primary);color:#fff;border:none;
  cursor:pointer;display:flex;align-items:center;justify-content:center;
  flex-shrink:0;transition:background .15s,transform .1s;
}
.send-btn:hover{background:var(--primary-dark)}
.send-btn:active{transform:scale(.94)}
.send-btn:disabled{opacity:.45;cursor:wait}
.input-footer{
  display:flex;align-items:center;justify-content:space-between;
  margin-top:8px;font-size:.72rem;color:var(--muted);
}
.debug-toggle{display:flex;align-items:center;gap:6px;cursor:pointer;user-select:none}
.debug-toggle input{accent-color:var(--primary)}

/* ── Reply button on hover ── */
.msg-wrap{position:relative}
.msg-wrap:hover .reply-btn{opacity:1}
.reply-btn{
  position:absolute;
  opacity:0;transition:opacity .15s;
  background:var(--surface);border:1px solid var(--border);
  border-radius:999px;padding:3px 8px;font-size:.7rem;
  cursor:pointer;color:var(--muted);
  display:flex;align-items:center;gap:4px;
  box-shadow:0 1px 4px rgba(0,0,0,.1);
  white-space:nowrap;
}
.reply-btn:hover{background:var(--bg);color:var(--text)}
.msg-wrap.user .reply-btn{left:0;bottom:-10px}
.msg-wrap.bot  .reply-btn{right:0;bottom:-10px}

/* ── Reply quote inside bubble ── */
.reply-quote{
  border-left:3px solid var(--primary);
  background:rgba(0,0,0,.06);
  border-radius:4px;
  padding:5px 8px;
  margin-bottom:7px;
  font-size:.78rem;
  line-height:1.35;
  cursor:pointer;
}
.msg.user .reply-quote{border-left-color:rgba(255,255,255,.6);background:rgba(255,255,255,.15)}
.reply-quote .rq-author{font-weight:600;margin-bottom:2px;font-size:.72rem}
.reply-quote .rq-text{
  overflow:hidden;display:-webkit-box;
  -webkit-line-clamp:2;-webkit-box-orient:vertical;
  opacity:.85;
}

/* ── Reply preview bar above input ── */
.reply-bar{
  display:none;
  align-items:center;gap:10px;
  padding:8px 12px;margin-bottom:6px;
  background:color-mix(in srgb,var(--primary) 8%,#fff);
  border:1px solid color-mix(in srgb,var(--primary) 20%,#fff);
  border-radius:10px;
  font-size:.8rem;
}
.reply-bar.show{display:flex}
.reply-bar-bar{
  width:3px;height:100%;min-height:30px;
  background:var(--primary);border-radius:2px;flex-shrink:0;
}
.reply-bar-info{flex:1;overflow:hidden}
.reply-bar-author{font-weight:600;color:var(--primary);font-size:.75rem;margin-bottom:2px}
.reply-bar-text{
  color:var(--muted);white-space:nowrap;
  overflow:hidden;text-overflow:ellipsis;
}
.reply-bar-close{
  background:none;border:none;cursor:pointer;
  color:var(--muted);font-size:1.1rem;line-height:1;
  padding:2px 4px;border-radius:4px;transition:color .15s;
}
.reply-bar-close:hover{color:var(--text)}

/* ── Scrollbar ── */
.messages::-webkit-scrollbar{width:4px}
.messages::-webkit-scrollbar-track{background:transparent}
.messages::-webkit-scrollbar-thumb{background:var(--border);border-radius:4px}

@media(max-width:640px){
  .sidebar{display:none}
  .header-actions a[href="/admin"]{display:none}
}
</style>
</head>
<body>

<div class="header">
  <div class="header-avatar">🤖</div>
  <div class="header-info">
    <h1><?= htmlspecialchars($botName) ?></h1>
    <p><?= htmlspecialchars($botSub) ?></p>
  </div>
  <div class="header-actions">
    <?php if (!$hasZaia): ?>
      <span style="font-size:.75rem;opacity:.7">⚠ ZAIA_WEBHOOK_URL não configurado</span>
    <?php endif; ?>
    <a href="/admin">⚙ Admin</a>
  </div>
</div>

<div class="layout">

  <aside class="sidebar">
    <h2>Sugestões</h2>
    <?php foreach ($suggestions as $s): ?>
      <button class="quick-btn" onclick="sendQuick(<?= htmlspecialchars(json_encode($s['msg']), ENT_QUOTES) ?>)">
        <?= htmlspecialchars($s['label']) ?>
      </button>
    <?php endforeach; ?>

    <h2 style="margin-top:16px">Navegação</h2>
    <a class="sidebar-link" href="/admin">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
      Painel Admin
    </a>
    <a class="sidebar-link" href="/api/health.php" target="_blank">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
      Health check
    </a>
  </aside>

  <main>
    <div class="tokens-bar" id="tokensBar">Tokens (IA) disponíveis: —</div>

    <div class="messages" id="messages">
      <div class="msg-wrap bot">
        <span class="msg-label">assistant</span>
        <div class="msg bot">Olá! 👋 Pode me perguntar sobre vendas, produtos, clientes ou pedidos do seu delivery.</div>
      </div>
    </div>

    <div class="input-area">
      <div class="reply-bar" id="replyBar">
        <div class="reply-bar-bar"></div>
        <div class="reply-bar-info">
          <div class="reply-bar-author" id="replyBarAuthor"></div>
          <div class="reply-bar-text"   id="replyBarText"></div>
        </div>
        <button class="reply-bar-close" onclick="cancelReply()" title="Cancelar resposta">✕</button>
      </div>
      <div class="input-row">
        <textarea id="msgInput" placeholder="Digite uma mensagem..." rows="1"></textarea>
        <button class="send-btn" id="sendBtn" onclick="sendMessage()" title="Enviar">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
        </button>
      </div>
      <div class="input-footer">
        <label class="debug-toggle">
          <input type="checkbox" id="debugCheck" onchange="toggleDebug(this.checked)">
          Modo depuração
        </label>
        <span id="pollInfo" class="poll-info" style="display:none"></span>
      </div>
    </div>
  </main>
</div>

<script>
const ZAIA_URL  = <?= json_encode($zaiaUrl) ?>;
const IS_DEBUG  = <?= json_encode($debug) ?>;

let debugMode   = false;
let pollTimer   = null;
let pollCount   = 0;
let pollStart   = 0;
let jobActive   = 0;

// ── Textarea auto-resize ────────────────────────────────────────
const input = document.getElementById('msgInput');
input.addEventListener('input', () => {
  input.style.height = 'auto';
  input.style.height = Math.min(input.scrollHeight, 120) + 'px';
});
input.addEventListener('keydown', e => {
  if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendMessage(); }
});

// ── Debug toggle ────────────────────────────────────────────────
function toggleDebug(on) {
  debugMode = on;
  document.querySelectorAll('.debug-msg').forEach(el => {
    el.closest('.msg-wrap').style.display = on ? '' : 'none';
  });
}

// ── Reply state ─────────────────────────────────────────────────
let replyingTo = null; // { author, text, msgId }
let msgCounter = 0;

function startReply(msgId, author, text) {
  replyingTo = { msgId, author, text };
  document.getElementById('replyBarAuthor').textContent = author;
  document.getElementById('replyBarText').textContent   = text.replace(/<[^>]+>/g,'').slice(0,120);
  document.getElementById('replyBar').classList.add('show');
  document.getElementById('msgInput').focus();
  // Highlight the quoted message
  document.querySelectorAll('.msg-wrap').forEach(w => w.style.background = '');
  const target = document.getElementById('msg-' + msgId);
  if (target) {
    target.style.background = 'color-mix(in srgb,var(--primary) 8%,var(--surface))';
    target.style.borderRadius = 'var(--radius)';
    target.scrollIntoView({behavior:'smooth', block:'nearest'});
  }
}

function cancelReply() {
  replyingTo = null;
  document.getElementById('replyBar').classList.remove('show');
  document.querySelectorAll('.msg-wrap').forEach(w => { w.style.background=''; w.style.borderRadius=''; });
}

function scrollToMsg(msgId) {
  const el = document.getElementById('msg-' + msgId);
  if (!el) return;
  el.scrollIntoView({behavior:'smooth', block:'center'});
  el.style.transition = 'background .2s';
  el.style.background = 'color-mix(in srgb,var(--primary) 14%,var(--surface))';
  el.style.borderRadius = 'var(--radius)';
  setTimeout(() => { el.style.background=''; el.style.borderRadius=''; }, 1200);
}

// ── Append message ──────────────────────────────────────────────
// agentName: nome do agente que gerou a resposta (ex: "Lia", "QueryBot", "CS")
function appendMsg(text, role, isDebug = false, agentName = null, quotedReply = null) {
  const msgs = document.getElementById('messages');
  const wrap = document.createElement('div');
  const id   = ++msgCounter;
  wrap.id        = 'msg-' + id;
  wrap.className = 'msg-wrap ' + role;
  wrap.style.paddingBottom = '14px'; // space for reply btn

  const label = document.createElement('span');
  label.className = 'msg-label';

  const bubble = document.createElement('div');
  bubble.className = 'msg ' + role + (isDebug ? ' debug-msg' : '');

  const now = new Date().toLocaleTimeString('pt-BR', {hour:'2-digit',minute:'2-digit'});

  let quoteHtml = '';
  if (quotedReply) {
    quoteHtml = `<div class="reply-quote" onclick="scrollToMsg(${quotedReply.msgId})">
      <div class="rq-author">${esc(quotedReply.author)}</div>
      <div class="rq-text">${esc(quotedReply.text.replace(/<[^>]+>/g,'').slice(0,120))}</div>
    </div>`;
  }

  if (isDebug) {
    label.textContent = 'assistant · CONSULTANDO ZAIA (RECEPCIONISTA)...';
    bubble.innerHTML  = text + `<span class="time">${now}</span>`;
    wrap.style.display = debugMode ? '' : 'none';
  } else if (role === 'user') {
    label.textContent = 'user';
    bubble.innerHTML  = quoteHtml + esc(text) + `<span class="time">${now}</span>`;
  } else {
    const displayName = agentName ? agentName.toLowerCase() : 'assistant';
    label.textContent = displayName;
    bubble.innerHTML  = quoteHtml + esc(text) + `<span class="time">${now}</span>`;
  }

  // Reply button
  if (!isDebug) {
    const rBtn = document.createElement('button');
    rBtn.className   = 'reply-btn';
    rBtn.innerHTML   = '↩ Responder';
    const author     = role === 'user' ? 'Você' : (agentName || 'assistant');
    const plainText  = text.replace(/<[^>]+>/g,'');
    rBtn.onclick     = () => startReply(id, author, plainText);
    wrap.appendChild(rBtn);
  }

  wrap.appendChild(label);
  wrap.appendChild(bubble);
  msgs.appendChild(wrap);
  msgs.scrollTop = msgs.scrollHeight;
  return { wrap, id };
}

function appendTyping() {
  const msgs = document.getElementById('messages');
  const div = document.createElement('div');
  div.id = 'typing';
  div.className = 'msg-wrap bot';
  div.innerHTML = '<div class="msg bot"><div class="typing"><span></span><span></span><span></span></div></div>';
  msgs.appendChild(div);
  msgs.scrollTop = msgs.scrollHeight;
}

function removeTyping() {
  document.getElementById('typing')?.remove();
}

function esc(s) {
  return String(s)
    .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
    .replace(/\*\*(.+?)\*\*/g,'<strong>$1</strong>')
    .replace(/\*([^*]+)\*/g,'<em>$1</em>')
    .replace(/\n/g,'<br>');
}

// ── Polling status ──────────────────────────────────────────────
function startPoll() {
  jobActive++;
  pollCount = 0;
  pollStart = Date.now();
  updatePollInfo();
  if (!pollTimer) pollTimer = setInterval(tickPoll, 2000);
}

function stopPoll() {
  jobActive = Math.max(0, jobActive - 1);
  if (jobActive === 0) {
    clearInterval(pollTimer); pollTimer = null;
    document.getElementById('pollInfo').style.display = 'none';
  }
}

function tickPoll() {
  pollCount++;
  updatePollInfo();
  // Opcional: fazer GET /api/status.php e mostrar no debug
  if (debugMode) {
    fetch('/api/status.php?action=status').then(r => r.json()).then(d => {
      // atualiza tokens bar se vier info
    }).catch(() => {});
  }
}

function updatePollInfo() {
  const el = document.getElementById('pollInfo');
  const ms = Math.round((Date.now() - pollStart) / 100) * 100;
  el.textContent = `Polling ~${ms}ms · ${pollCount} job(s) · ${jobActive} ativo(s)   Timeout job: 120s`;
  el.style.display = jobActive > 0 ? 'inline-block' : 'none';
}

// ── Send ────────────────────────────────────────────────────────
let isSending = false;

function lockInput(lock) {
  isSending = lock;
  const btn = document.getElementById('sendBtn');
  const inp = document.getElementById('msgInput');
  btn.disabled = lock;
  inp.disabled = lock;
  inp.style.opacity = lock ? '0.5' : '1';
  inp.style.cursor  = lock ? 'not-allowed' : '';
}

async function sendMessage() {
  if (isSending) return;          // bloqueia duplo envio
  const msg = input.value.trim();
  if (!msg) return;

  if (!ZAIA_URL) {
    appendMsg('⚠ ZAIA_WEBHOOK_URL não configurado no .env', 'bot');
    return;
  }

  input.value = '';
  input.style.height = 'auto';
  lockInput(true);

  // Captura reply antes de limpar
  const currentReply = replyingTo ? { ...replyingTo } : null;
  cancelReply();

  appendMsg(msg, 'user', false, null, currentReply);

  // Debug info
  const pollInfo = `poll #${pollCount + 1} · ~1s · GET /api.php?action=status&poll_zaia=1\nConsultando Zaia (recepcionista)......`;
  appendMsg(pollInfo, 'bot', true);

  appendTyping();
  startPoll();

  try {
    const res  = await fetch(ZAIA_URL, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ message: msg, question: msg, content: msg }),
    });

    const raw = await res.text();
    let data;
    try { data = JSON.parse(raw); } catch { data = { reply: raw }; }

    removeTyping();

    // Suporte à estrutura { agent, reply } ou { data: { agent, reply } }
    const payload   = data.data ?? data;
    const reply     = payload.reply ?? payload.message ?? payload.error ?? JSON.stringify(data, null, 2);
    const agentName = payload.agent ?? null;

    appendMsg(reply, 'bot', false, agentName);

  } catch (e) {
    removeTyping();
    appendMsg('❌ Erro ao contatar a Zaia: ' + e.message, 'bot');
  } finally {
    lockInput(false);
    stopPoll();
    input.focus();
  }
}

function sendQuick(msg) {
  input.value = msg;
  sendMessage();
}

// ── Health check passivo ────────────────────────────────────────
fetch('/api/status.php?action=status')
  .then(r => r.json())
  .then(d => {
    document.getElementById('tokensBar').textContent =
      'Tokens (IA) disponíveis: —   Backend: ' + (d.backend ?? '?') + '   DB: ' + (d.db ?? '?');
  })
  .catch(() => {});
</script>
</body>
</html>
