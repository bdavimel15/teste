<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/src/bootstrap.php';

$botName = Config::get('BOT_NAME', 'QueryBot');
$botColor = Config::get('BOT_COLOR', '#6D5EF7');
$initialTokens = (int) Config::get('INITIAL_TOKENS', '100');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($botName, ENT_QUOTES, 'UTF-8') ?></title>
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }

  :root {
    --qb-primary: <?= htmlspecialchars($botColor, ENT_QUOTES, 'UTF-8') ?>;
    --qb-primary-dark: #4f46e5;
    --qb-primary-soft: #f1edff;
    --qb-bg: #f7f7fb;
    --qb-card: #ffffff;
    --qb-soft: #fafafe;
    --qb-border: #e7e7ef;
    --qb-border-2: #d8d7e6;
    --qb-text: #171725;
    --qb-muted: #6b7280;
    --qb-muted-2: #8b8fa1;
    --qb-success: #16a34a;
    --qb-danger: #dc2626;
  }

  html, body { min-height: 100%; }

  body {
    font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
    background:
      radial-gradient(circle at top left, color-mix(in srgb, var(--qb-primary) 14%, transparent), transparent 34%),
      radial-gradient(circle at bottom right, rgba(124, 58, 237, .10), transparent 40%),
      var(--qb-bg);
    color: var(--qb-text);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 18px;
  }

  .qb-root {
    display: flex;
    flex-direction: column;
    width: 100%;
    max-width: 880px;
    height: min(740px, calc(100vh - 36px));
    background: rgba(255,255,255,.98);
    border: 1px solid var(--qb-border);
    border-radius: 24px;
    overflow: hidden;
    box-shadow: 0 24px 80px rgba(23, 23, 37, .10);
  }

  .qb-header {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 16px 20px;
    border-bottom: 1px solid var(--qb-border);
    background: #fff;
    flex-shrink: 0;
  }

  .qb-avatar {
    width: 40px;
    height: 40px;
    border-radius: 14px;
    background: linear-gradient(135deg, var(--qb-primary), #8b5cf6);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 19px;
    flex-shrink: 0;
    box-shadow: 0 10px 22px color-mix(in srgb, var(--qb-primary) 24%, transparent);
  }

  .qb-header-info { flex: 1; min-width: 0; }

  .qb-header-name {
    font-size: 15px;
    font-weight: 750;
    color: var(--qb-text);
    line-height: 1.2;
  }

  .qb-status {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 12px;
    color: var(--qb-muted);
    margin-top: 3px;
  }

  .qb-status-dot {
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: var(--qb-success);
    flex-shrink: 0;
    box-shadow: 0 0 0 3px rgba(22,163,74,.12);
  }

  .qb-header-tokens {
    font-size: 12px;
    color: var(--qb-muted);
    background: var(--qb-soft);
    border: 1px solid var(--qb-border);
    border-radius: 999px;
    padding: 6px 11px;
    display: flex;
    align-items: center;
    gap: 6px;
    white-space: nowrap;
  }

  .qb-header-tokens .spark { color: var(--qb-primary); }

  .qb-body {
    flex: 1;
    overflow-y: auto;
    padding: 24px 20px 10px;
    display: flex;
    flex-direction: column;
    gap: 14px;
    scroll-behavior: smooth;
    background: linear-gradient(180deg, #fff, #fafafe);
  }

  .qb-body::-webkit-scrollbar { width: 4px; }
  .qb-body::-webkit-scrollbar-track { background: transparent; }
  .qb-body::-webkit-scrollbar-thumb { background: var(--qb-border-2); border-radius: 4px; }

  .qb-welcome {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 10px;
    margin: auto 0;
    padding: 18px 14px;
    text-align: center;
  }

  .qb-welcome-icon {
    width: 56px;
    height: 56px;
    border-radius: 18px;
    background: var(--qb-primary-soft);
    border: 1px solid color-mix(in srgb, var(--qb-primary) 22%, white);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 27px;
  }

  .qb-welcome-title {
    font-size: 17px;
    font-weight: 750;
    color: var(--qb-text);
  }

  .qb-welcome-sub {
    font-size: 13px;
    color: var(--qb-muted);
    text-align: center;
    max-width: 340px;
    line-height: 1.55;
  }

  .qb-group { display: flex; flex-direction: column; gap: 5px; }

  .qb-agent-card {
    display: flex;
    align-items: center;
    gap: 8px;
    padding-left: 39px;
    margin-bottom: 1px;
  }

  .qb-agent-icon {
    width: 23px;
    height: 23px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--qb-primary-soft);
    border: 1px solid color-mix(in srgb, var(--qb-primary) 18%, white);
    font-size: 13px;
  }

  .qb-agent-meta {
    display: flex;
    flex-direction: column;
    gap: 1px;
    line-height: 1.15;
  }

  .qb-agent-name {
    font-size: 12px;
    font-weight: 850;
    color: var(--qb-text);
  }

  .qb-agent-title {
    font-size: 10.5px;
    color: var(--qb-muted);
  }

  .qb-row {
    display: flex;
    align-items: flex-end;
    gap: 9px;
  }

  .qb-row.user { flex-direction: row-reverse; }

  .qb-msg-avatar {
    width: 30px;
    height: 30px;
    border-radius: 10px;
    background: #fff;
    border: 1px solid var(--qb-border);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    flex-shrink: 0;
    align-self: flex-end;
    margin-bottom: 2px;
    box-shadow: 0 4px 14px rgba(23,23,37,.05);
  }

  .qb-bubble {
    max-width: 76%;
    padding: 11px 14px;
    border-radius: 16px;
    font-size: 14px;
    line-height: 1.62;
    color: var(--qb-text);
    position: relative;
    overflow-wrap: anywhere;
    white-space: pre-wrap;
  }

  .qb-bubble.ai {
    background: #fff;
    border: 1px solid var(--qb-border);
    border-bottom-left-radius: 5px;
    box-shadow: 0 6px 22px rgba(23,23,37,.05);
  }

  .qb-bubble.user {
    background: linear-gradient(135deg, var(--qb-primary), #7c3aed);
    color: #fff;
    border-bottom-right-radius: 5px;
    box-shadow: 0 10px 24px color-mix(in srgb, var(--qb-primary) 22%, transparent);
  }

  .qb-bubble.error {
    border-color: rgba(220,38,38,.25);
    background: #fff5f5;
    color: #991b1b;
  }

  .qb-bubble-time {
    font-size: 11px;
    color: var(--qb-muted-2);
    padding: 0 4px;
  }

  .qb-row.user .qb-bubble-time { text-align: right; }

  .qb-thinking-bubble {
    display: flex;
    align-items: center;
    min-height: 38px;
    min-width: 52px;
  }

  .qb-typing-dots {
    display: flex;
    align-items: center;
    gap: 4px;
  }

  .qb-typing-dots span {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: var(--qb-muted);
    animation: typing 1.2s infinite;
  }

  .qb-typing-dots span:nth-child(2) { animation-delay: 0.2s; }
  .qb-typing-dots span:nth-child(3) { animation-delay: 0.4s; }

  .qb-thinking-text {
    font-size: 14px;
    line-height: 1.55;
    color: var(--qb-muted);
    opacity: 1;
    transition: opacity 0.35s ease;
  }

  .qb-thinking-text.is-hidden {
    display: none;
  }

  .qb-thinking-text.is-fading {
    opacity: 0;
  }

  @keyframes typing {
    0%, 60%, 100% { opacity: 0.3; transform: translateY(0); }
    30% { opacity: 1; transform: translateY(-3px); }
  }

  .qb-footer {
    padding: 12px 16px 16px;
    border-top: 1px solid var(--qb-border);
    background: #fff;
    flex-shrink: 0;
  }

  .qb-input-wrap {
    display: flex;
    align-items: flex-end;
    gap: 8px;
    background: var(--qb-soft);
    border: 1px solid var(--qb-border-2);
    border-radius: 15px;
    padding: 9px 9px 9px 14px;
    transition: border-color 0.15s, box-shadow 0.15s, background .15s;
  }

  .qb-input-wrap:focus-within {
    background: #fff;
    border-color: var(--qb-primary);
    box-shadow: 0 0 0 4px color-mix(in srgb, var(--qb-primary) 13%, transparent);
  }

  .qb-input {
    flex: 1;
    background: transparent;
    border: none;
    outline: none;
    font-size: 14px;
    font-family: inherit;
    color: var(--qb-text);
    resize: none;
    max-height: 120px;
    line-height: 1.5;
    padding: 3px 0;
  }

  .qb-input::placeholder { color: var(--qb-muted-2); }

  .qb-send {
    width: 36px;
    height: 36px;
    border-radius: 11px;
    background: var(--qb-primary);
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    transition: background 0.15s, transform 0.1s, opacity .15s;
    color: #fff;
    font-weight: 900;
  }

  .qb-send:hover { background: var(--qb-primary-dark); }
  .qb-send:active { transform: scale(0.95); }

  .qb-send:disabled {
    background: #e5e7eb;
    color: #9ca3af;
    cursor: not-allowed;
  }

  .qb-footer-hint {
    font-size: 11px;
    color: var(--qb-muted-2);
    text-align: center;
    margin-top: 8px;
  }

  .qb-footer-hint kbd {
    background: #f3f4f6;
    border: 1px solid #e5e7eb;
    border-radius: 4px;
    padding: 1px 4px;
    font-size: 10px;
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
  }

  @media (max-width: 520px) {
    body { padding: 0; }
    .qb-root { height: 100dvh; border-radius: 0; border: 0; }
    .qb-bubble { max-width: 88%; }
    .qb-header-tokens { display: flex; font-size: 11px; padding: 5px 8px; }
    .qb-body { padding: 16px 12px 8px; }
    .qb-footer { padding: 10px 12px 14px; }
  }
</style>
</head>
<body>

<h2 class="sr-only" style="position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0,0,0,0);">QueryBot — Interface de chat com IA</h2>

<div class="qb-root">

  <div class="qb-header">
    <div class="qb-avatar" aria-hidden="true">🤖</div>
    <div class="qb-header-info">
      <div class="qb-header-name"><?= htmlspecialchars($botName, ENT_QUOTES, 'UTF-8') ?></div>
      <div class="qb-status">
        <div class="qb-status-dot" aria-hidden="true"></div>
        Online agora
      </div>
    </div>
    <div class="qb-header-tokens" title="Tokens de IA disponíveis">
      <span class="spark" aria-hidden="true">✦</span>
      <span id="token-display"><?= $initialTokens ?> tokens</span>
    </div>
  </div>

  <div class="qb-body" id="qb-body" role="log" aria-live="polite" aria-label="Conversa">
    <div class="qb-welcome" id="qb-welcome">
      <div class="qb-welcome-icon" aria-hidden="true">🤖</div>
      <div class="qb-welcome-title">Olá! Sou o <?= htmlspecialchars($botName, ENT_QUOTES, 'UTF-8') ?></div>
      <div class="qb-welcome-sub">Digite sua mensagem para iniciar o atendimento.</div>
    </div>
  </div>

  <div class="qb-footer">
    <div class="qb-input-wrap">
      <textarea
        class="qb-input"
        id="qb-input"
        rows="1"
        placeholder="Digite sua mensagem..."
        aria-label="Mensagem para o QueryBot"
      ></textarea>
      <button class="qb-send" id="qb-send" aria-label="Enviar mensagem" disabled>➤</button>
    </div>
    <div class="qb-footer-hint">
      <kbd>Enter</kbd> para enviar &nbsp;·&nbsp; <kbd>Shift</kbd>+<kbd>Enter</kbd> para nova linha
    </div>
  </div>

</div>

<script>
const input = document.getElementById('qb-input');
const sendBtn = document.getElementById('qb-send');
const bodyEl = document.getElementById('qb-body');
const welcome = document.getElementById('qb-welcome');
const tokenDisplay = document.getElementById('token-display');

let currentTokens = <?= $initialTokens ?>;
let isTyping = false;

const THINKING_MIN_DURATION = 800;
const THINKING_PHRASE_DELAY = 2500;
const THINKING_PHRASE_INTERVAL = 1750;

const THINKING_PHRASES = [
  '🔍 Analisando sua solicitação...',
  '🤖 Processando sua mensagem...',
  '🧠 Entendendo o contexto...',
  '💭 Pensando na melhor resposta...',
  '⚙️ Trabalhando na sua solicitação...',
  '📝 Organizando as informações...',
  '🔎 Verificando detalhes...',
  '📋 Preparando uma resposta...',
  '✨ Quase pronto...',
  '⚡ Finalizando...',
];

const thinkingState = {
  wrap: null,
  phraseIndex: 0,
  phraseTimer: null,
  phraseDelayTimer: null,
  textEl: null,
  dotsEl: null,
};

const agentMap = {
  lia: { emoji: '🗣️', name: 'Lia', title: 'Especialista em Conversação' },
  querybot: { emoji: '🤖', name: 'QueryBot', title: 'Análise de Dados' },
  cs: { emoji: '🔧', name: 'CS', title: 'Suporte ao Cliente' },
  maya: { emoji: '💸', name: 'Maya', title: 'Financeiro' },
  gerente: { emoji: '🧠', name: 'Gerente', title: 'Roteamento Inteligente' },
  formatter: { emoji: '✨', name: 'Formatter', title: 'Formatação de Resposta' },
  sistema: { emoji: '⚙️', name: 'Sistema', title: 'Status da Integração' },
  default: { emoji: '🤖', name: 'QueryBot', title: 'Assistente de IA' },
};

function updateTokenDisplay(tokens) {
  if (typeof tokens === 'number' && Number.isFinite(tokens)) {
    currentTokens = Math.max(0, Math.floor(tokens));
  }

  tokenDisplay.textContent = currentTokens === 1
    ? '1 token'
    : `${currentTokens} tokens`;
}

function scrollToBottom() {
  bodyEl.scrollTop = bodyEl.scrollHeight;
}

function now() {
  return new Date().toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
}

function normalizeAgentKey(agent) {
  const key = String(agent || '').trim().toLowerCase();

  if (key.includes('lia')) return 'lia';
  if (key.includes('query')) return 'querybot';
  if (key.includes('cs') || key.includes('suporte')) return 'cs';
  if (key.includes('maya') || key.includes('financeiro') || key.includes('financa') || key.includes('finança')) return 'maya';
  if (key.includes('gerente')) return 'gerente';
  if (key.includes('formatter') || key.includes('format')) return 'formatter';
  if (key.includes('sistema')) return 'sistema';

  return key || 'default';
}

function getAgent(agentKey) {
  return agentMap[normalizeAgentKey(agentKey)] || agentMap.default;
}

function appendUserMsg(text) {
  if (welcome) welcome.style.display = 'none';

  const group = document.createElement('div');
  group.className = 'qb-group';

  const row = document.createElement('div');
  row.className = 'qb-row user';

  const bubble = document.createElement('div');
  bubble.className = 'qb-bubble user';
  bubble.textContent = text;

  row.appendChild(bubble);
  group.appendChild(row);

  const timeEl = document.createElement('div');
  timeEl.className = 'qb-bubble-time';
  timeEl.style.textAlign = 'right';
  timeEl.textContent = now();
  group.appendChild(timeEl);

  bodyEl.appendChild(group);
  scrollToBottom();
}

function clearThinkingTimers() {
  if (thinkingState.phraseTimer) {
    clearInterval(thinkingState.phraseTimer);
    thinkingState.phraseTimer = null;
  }

  if (thinkingState.phraseDelayTimer) {
    clearTimeout(thinkingState.phraseDelayTimer);
    thinkingState.phraseDelayTimer = null;
  }
}

function removeThinkingMessage() {
  clearThinkingTimers();

  const wrap = document.getElementById('qb-typing-wrap');
  if (wrap) wrap.remove();

  thinkingState.wrap = null;
  thinkingState.textEl = null;
  thinkingState.dotsEl = null;
  thinkingState.phraseIndex = 0;
}

function buildAgentCard(agent) {
  const agentCard = document.createElement('div');
  agentCard.className = 'qb-agent-card';
  agentCard.innerHTML = `
    <span class="qb-agent-icon" aria-hidden="true">${agent.emoji}</span>
    <span class="qb-agent-meta">
      <span class="qb-agent-name">${agent.name}</span>
      <span class="qb-agent-title">${agent.title}</span>
    </span>
  `;
  return agentCard;
}

function showThinkingMessage(agentKey = 'lia') {
  removeThinkingMessage();

  const agent = getAgent(agentKey);

  const wrap = document.createElement('div');
  wrap.id = 'qb-typing-wrap';
  wrap.className = 'qb-group qb-thinking';
  wrap.setAttribute('aria-busy', 'true');

  wrap.appendChild(buildAgentCard(agent));

  const row = document.createElement('div');
  row.className = 'qb-row';

  const av = document.createElement('div');
  av.className = 'qb-msg-avatar';
  av.setAttribute('aria-hidden', 'true');
  av.textContent = agent.emoji;

  const bubble = document.createElement('div');
  bubble.className = 'qb-bubble ai qb-thinking-bubble';
  bubble.setAttribute('aria-label', 'Processando resposta...');

  const dotsEl = document.createElement('div');
  dotsEl.className = 'qb-typing-dots';
  dotsEl.innerHTML = '<span></span><span></span><span></span>';

  const textEl = document.createElement('div');
  textEl.className = 'qb-thinking-text is-hidden';
  textEl.setAttribute('aria-live', 'polite');

  bubble.appendChild(dotsEl);
  bubble.appendChild(textEl);
  row.appendChild(av);
  row.appendChild(bubble);
  wrap.appendChild(row);

  bodyEl.appendChild(wrap);
  thinkingState.wrap = wrap;
  thinkingState.textEl = textEl;
  thinkingState.dotsEl = dotsEl;
  scrollToBottom();

  thinkingState.phraseDelayTimer = setTimeout(() => {
    thinkingState.phraseDelayTimer = null;
    if (!thinkingState.wrap) return;

    updateThinkingMessage(THINKING_PHRASES[0]);

    thinkingState.phraseTimer = setInterval(() => {
      if (!thinkingState.wrap) {
        clearThinkingTimers();
        return;
      }

      thinkingState.phraseIndex = (thinkingState.phraseIndex + 1) % THINKING_PHRASES.length;
      updateThinkingMessage(THINKING_PHRASES[thinkingState.phraseIndex]);
    }, THINKING_PHRASE_INTERVAL);
  }, THINKING_PHRASE_DELAY);

  return wrap;
}

function updateThinkingMessage(text) {
  const textEl = thinkingState.textEl || document.querySelector('#qb-typing-wrap .qb-thinking-text');
  const dotsEl = thinkingState.dotsEl || document.querySelector('#qb-typing-wrap .qb-typing-dots');

  if (!textEl || !text) return;

  const applyText = () => {
    textEl.textContent = text;
    textEl.classList.remove('is-hidden', 'is-fading');
    if (dotsEl) dotsEl.style.display = 'none';
  };

  if (textEl.classList.contains('is-hidden')) {
    applyText();
    return;
  }

  textEl.classList.add('is-fading');

  setTimeout(() => {
    if (!thinkingState.wrap && !document.getElementById('qb-typing-wrap')) return;
    applyText();
    requestAnimationFrame(() => textEl.classList.remove('is-fading'));
  }, 180);
}

function waitForThinkingMinimum(startedAt) {
  const elapsed = Date.now() - startedAt;
  if (elapsed >= THINKING_MIN_DURATION) return Promise.resolve();
  return new Promise(resolve => setTimeout(resolve, THINKING_MIN_DURATION - elapsed));
}

function appendAIMsg(text, agentKey = 'default', isError = false) {
  removeThinkingMessage();

  const agent = getAgent(agentKey);

  const group = document.createElement('div');
  group.className = 'qb-group';

  group.appendChild(buildAgentCard(agent));

  const row = document.createElement('div');
  row.className = 'qb-row';

  const av = document.createElement('div');
  av.className = 'qb-msg-avatar';
  av.setAttribute('aria-hidden', 'true');
  av.textContent = agent.emoji;

  const bubble = document.createElement('div');
  bubble.className = isError ? 'qb-bubble ai error' : 'qb-bubble ai';
  bubble.textContent = text;

  row.appendChild(av);
  row.appendChild(bubble);
  group.appendChild(row);

  const timeEl = document.createElement('div');
  timeEl.className = 'qb-bubble-time';
  timeEl.style.paddingLeft = '39px';
  timeEl.textContent = now();
  group.appendChild(timeEl);

  bodyEl.appendChild(group);
  scrollToBottom();
}

function normalizeBackendResponse(data, rawText) {
  const payload = data && typeof data === 'object' && data.data && typeof data.data === 'object'
    ? data.data
    : data;

  if (!payload || typeof payload !== 'object') {
    return {
      reply: rawText || 'Resposta recebida.',
      agent: 'default',
      tokens: null,
    };
  }

  return {
    reply:
      payload.reply ??
      payload.rawReply ??
      payload.raw_reply ??
      payload.message ??
      payload.content ??
      payload.answer ??
      payload.response ??
      payload.text ??
      rawText ??
      'Resposta recebida.',
    agent:
      payload.agent ??
      payload.agentName ??
      payload.agent_name ??
      payload.selectedAgent ??
      payload.responder ??
      payload.route ??
      payload.agente ??
      'Sistema',
    tokens:
      typeof payload.tokens === 'number' ? payload.tokens :
      typeof payload.remainingTokens === 'number' ? payload.remainingTokens :
      typeof payload.remaining_tokens === 'number' ? payload.remaining_tokens :
      typeof data?.tokens === 'number' ? data.tokens :
      null,
  };
}

async function sendToZaia(message) {
  /*
    IMPORTANTE:
    A tela chama o proxy interno /api/chat.php.
    O PHP chama a Zaia pelo servidor.
    Assim não dá erro de CORS/Failed to fetch no navegador.
  */
  const response = await fetch('/api/chat.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      message,
      content: message,
      question: message,
    }),
  });

  const rawText = await response.text();
  let data = null;

  try {
    data = JSON.parse(rawText);
  } catch (_) {}

  if (!response.ok) {
    throw new Error(data?.error || rawText || 'Falha ao consultar a Zaia.');
  }

  return normalizeBackendResponse(data, rawText);
}

async function handleSend(text) {
  text = text.trim();
  if (!text || isTyping) return;

  isTyping = true;
  sendBtn.disabled = true;
  input.value = '';
  input.style.height = 'auto';

  appendUserMsg(text);

  const thinkingStartedAt = Date.now();
  showThinkingMessage('lia');

  try {
    const result = await sendToZaia(text);

    await waitForThinkingMinimum(thinkingStartedAt);
    appendAIMsg(result.reply, result.agent);

    if (typeof result.tokens === 'number') {
      updateTokenDisplay(result.tokens);
    }
  } catch (error) {
    await waitForThinkingMinimum(thinkingStartedAt);
    appendAIMsg('Erro ao enviar mensagem: ' + error.message, 'sistema', true);
  } finally {
    isTyping = false;
    sendBtn.disabled = input.value.trim().length === 0;
    input.focus();
  }
}

input.addEventListener('input', () => {
  sendBtn.disabled = input.value.trim().length === 0 || isTyping;
  input.style.height = 'auto';
  input.style.height = Math.min(input.scrollHeight, 120) + 'px';
});

input.addEventListener('keydown', e => {
  if (e.key === 'Enter' && !e.shiftKey) {
    e.preventDefault();
    if (!sendBtn.disabled) handleSend(input.value);
  }
});

sendBtn.addEventListener('click', () => handleSend(input.value));

updateTokenDisplay(currentTokens);
</script>
</body>
</html>
