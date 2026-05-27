<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/src/bootstrap.php';

$botName  = Config::get('BOT_NAME', 'QueryBot');
$botColor = Config::get('BOT_COLOR', '#7c3aed');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin — <?= htmlspecialchars($botName) ?></title>
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
  --success:#16a34a;
  --danger:#dc2626;
  --warn:#d97706;
  --radius:12px;
  --sidebar:220px;
}
html,body{height:100%;overflow:hidden}
body{font-family:system-ui,-apple-system,sans-serif;background:var(--bg);display:flex;flex-direction:column;color:var(--text)}

/* Header */
.header{
  background:var(--primary);color:#fff;
  padding:14px 20px;display:flex;align-items:center;gap:12px;
  flex-shrink:0;box-shadow:0 2px 8px rgba(0,0,0,.2);
}
.header h1{font-size:1rem;font-weight:600}
.header a{
  margin-left:auto;color:#fff;text-decoration:none;
  background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.25);
  padding:6px 12px;border-radius:8px;font-size:.78rem;transition:background .15s;
}
.header a:hover{background:rgba(255,255,255,.25)}

/* Layout */
.layout{display:flex;flex:1;overflow:hidden}

/* Sidebar */
.sidebar{
  width:var(--sidebar);background:var(--surface);
  border-right:1px solid var(--border);
  padding:16px;display:flex;flex-direction:column;gap:4px;flex-shrink:0;
}
.sidebar h2{font-size:.65rem;text-transform:uppercase;letter-spacing:.06em;color:var(--muted);margin:12px 0 4px}
.sidebar h2:first-child{margin-top:0}
.nav-item{
  padding:8px 10px;border-radius:8px;cursor:pointer;
  font-size:.85rem;color:var(--muted);transition:all .15s;
  display:flex;align-items:center;gap:8px;border:none;background:none;width:100%;text-align:left;
}
.nav-item:hover{background:var(--bg);color:var(--text)}
.nav-item.active{background:color-mix(in srgb,var(--primary) 10%,#fff);color:var(--primary);font-weight:500}
.nav-item svg{width:16px;height:16px;flex-shrink:0}

/* Main */
main{flex:1;overflow-y:auto;padding:24px}

/* Cards de stats */
.stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:14px;margin-bottom:24px}
.stat-card{
  background:var(--surface);border:1px solid var(--border);
  border-radius:var(--radius);padding:16px;
}
.stat-card .label{font-size:.72rem;color:var(--muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:6px}
.stat-card .value{font-size:1.6rem;font-weight:600;color:var(--text);line-height:1}
.stat-card .sub{font-size:.72rem;color:var(--muted);margin-top:4px}
.stat-card.warn .value{color:var(--warn)}
.stat-card.success .value{color:var(--success)}

/* Section header */
.section-header{
  display:flex;align-items:center;gap:10px;margin-bottom:16px;flex-wrap:wrap;
}
.section-header h2{font-size:1rem;font-weight:600}
.section-header .actions{margin-left:auto;display:flex;gap:8px;flex-wrap:wrap}

/* Buttons */
.btn{
  padding:8px 14px;border-radius:8px;font-size:.82rem;
  cursor:pointer;border:none;font-family:inherit;
  transition:all .15s;display:inline-flex;align-items:center;gap:6px;
}
.btn-primary{background:var(--primary);color:#fff}
.btn-primary:hover{background:var(--primary-dark)}
.btn-secondary{background:var(--surface);color:var(--text);border:1px solid var(--border)}
.btn-secondary:hover{background:var(--bg)}
.btn-danger{background:#fee2e2;color:var(--danger);border:1px solid #fca5a5}
.btn-danger:hover{background:#fecaca}
.btn-success{background:#dcfce7;color:var(--success);border:1px solid #86efac}
.btn-success:hover{background:#bbf7d0}
.btn:disabled{opacity:.5;cursor:wait}
.btn-seed{
  background:linear-gradient(135deg,#6366f1,#8b5cf6);
  color:#fff;padding:10px 18px;font-size:.88rem;border-radius:10px;
}
.btn-seed:hover{opacity:.9}

/* Table */
.table-wrap{overflow-x:auto;border:1px solid var(--border);border-radius:var(--radius)}
table{width:100%;border-collapse:collapse;font-size:.85rem}
thead{background:var(--bg)}
th{padding:10px 14px;text-align:left;font-weight:500;font-size:.75rem;text-transform:uppercase;letter-spacing:.04em;color:var(--muted);border-bottom:1px solid var(--border)}
td{padding:10px 14px;border-bottom:1px solid var(--border);color:var(--text)}
tr:last-child td{border-bottom:none}
tr:hover td{background:color-mix(in srgb,var(--primary) 3%,#fff)}
.badge{
  display:inline-block;padding:2px 8px;border-radius:999px;
  font-size:.7rem;font-weight:500;
}
.badge-completed{background:#dcfce7;color:#15803d}
.badge-pending{background:#fef9c3;color:#a16207}
.badge-cancelled{background:#fee2e2;color:#b91c1c}
.badge-ok{background:#dbeafe;color:#1d4ed8}
.badge-low{background:#fee2e2;color:#b91c1c}

/* Modal */
.modal-bg{
  position:fixed;inset:0;background:rgba(0,0,0,.45);
  display:flex;align-items:center;justify-content:center;z-index:100;
  opacity:0;pointer-events:none;transition:opacity .2s;
}
.modal-bg.open{opacity:1;pointer-events:all}
.modal{
  background:var(--surface);border-radius:var(--radius);
  width:min(480px,94vw);padding:24px;box-shadow:0 20px 60px rgba(0,0,0,.2);
  max-height:90vh;overflow-y:auto;
}
.modal h3{font-size:1rem;font-weight:600;margin-bottom:16px}
.field{display:flex;flex-direction:column;gap:4px;margin-bottom:14px}
.field label{font-size:.75rem;font-weight:500;color:var(--muted)}
.field input,.field select{
  padding:9px 12px;border:1px solid var(--border);border-radius:8px;
  font-size:.88rem;font-family:inherit;color:var(--text);outline:none;
  transition:border-color .15s;
}
.field input:focus,.field select:focus{border-color:var(--primary)}
.modal-footer{display:flex;gap:8px;justify-content:flex-end;margin-top:20px}

/* Toast */
.toast{
  position:fixed;bottom:24px;right:24px;z-index:200;
  background:#1f2937;color:#fff;padding:12px 18px;border-radius:10px;
  font-size:.85rem;opacity:0;transform:translateY(10px);
  transition:all .25s;pointer-events:none;max-width:320px;
}
.toast.show{opacity:1;transform:translateY(0)}
.toast.toast-error{background:#991b1b}
.toast.toast-success{background:#15803d}

/* Tabs */
.page{display:none}
.page.active{display:block}

/* Seed promo card */
.seed-card{
  background:linear-gradient(135deg,color-mix(in srgb,var(--primary) 8%,#fff),color-mix(in srgb,var(--primary) 4%,#fff));
  border:1px solid color-mix(in srgb,var(--primary) 20%,#fff);
  border-radius:var(--radius);padding:20px;margin-bottom:20px;
  display:flex;align-items:center;gap:16px;flex-wrap:wrap;
}
.seed-card .seed-info h3{font-size:.95rem;font-weight:600;color:var(--primary);margin-bottom:4px}
.seed-card .seed-info p{font-size:.8rem;color:var(--muted)}
.seed-card .seed-info{flex:1}

/* Empty state */
.empty{text-align:center;padding:40px 20px;color:var(--muted);font-size:.88rem}

@media(max-width:640px){.sidebar{display:none}.stats-grid{grid-template-columns:repeat(2,1fr)}}
</style>
</head>
<body>

<div class="header">
  <div style="font-size:20px">⚙</div>
  <h1><?= htmlspecialchars($botName) ?> — Admin</h1>
  <a href="/">← Voltar ao chat</a>
</div>

<div class="layout">
  <aside class="sidebar">
    <h2>Menu</h2>
    <button class="nav-item active" onclick="showPage('dashboard',this)">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
      Dashboard
    </button>
    <button class="nav-item" onclick="showPage('products',this)">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 01-8 0"/></svg>
      Produtos
    </button>
    <button class="nav-item" onclick="showPage('orders',this)">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"/></svg>
      Pedidos
    </button>
    <button class="nav-item" onclick="showPage('test',this)">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>
      Testar API
    </button>
  </aside>

  <main id="mainContent">

    <!-- ══ Dashboard ══ -->
    <div class="page active" id="page-dashboard">
      <div class="section-header">
        <h2>Dashboard</h2>
        <div class="actions">
          <button class="btn btn-secondary" onclick="loadDashboard()">↺ Atualizar</button>
        </div>
      </div>
      <div class="stats-grid" id="statsGrid">
        <div class="stat-card"><div class="label">Carregando...</div><div class="value">—</div></div>
      </div>
      <div class="section-header" style="margin-top:8px">
        <h2>Últimos pedidos</h2>
      </div>
      <div class="table-wrap">
        <table id="dashOrders">
          <thead><tr><th>Cliente</th><th>Produto</th><th>Qtd</th><th>Total</th><th>Status</th><th>Data</th></tr></thead>
          <tbody><tr><td colspan="6" class="empty">Carregando...</td></tr></tbody>
        </table>
      </div>
    </div>

    <!-- ══ Produtos ══ -->
    <div class="page" id="page-products">
      <div class="seed-card">
        <div class="seed-info">
          <h3>🚀 Gerar produtos de teste</h3>
          <p>Popula o banco com produtos, categorias, clientes e pedidos aleatórios para testar as consultas da Zaia.</p>
        </div>
        <button class="btn btn-seed" id="seedBtn" onclick="seedProducts()">
          ✨ Gerar 20 produtos
        </button>
      </div>

      <div class="section-header">
        <h2>Produtos <span id="prodCount" style="font-size:.78rem;color:var(--muted);font-weight:400"></span></h2>
        <div class="actions">
          <button class="btn btn-secondary" onclick="loadProducts()">↺ Atualizar</button>
          <button class="btn btn-primary" onclick="openModal()">+ Novo produto</button>
        </div>
      </div>
      <div class="table-wrap">
        <table>
          <thead><tr><th>#</th><th>Nome</th><th>Categoria</th><th>Preço</th><th>Estoque</th><th>Status</th><th>Ações</th></tr></thead>
          <tbody id="prodTable"><tr><td colspan="7" class="empty">Carregando...</td></tr></tbody>
        </table>
      </div>
    </div>

    <!-- ══ Pedidos ══ -->
    <div class="page" id="page-orders">
      <div class="section-header">
        <h2>Pedidos</h2>
        <div class="actions">
          <button class="btn btn-secondary" onclick="loadOrders()">↺ Atualizar</button>
        </div>
      </div>
      <div class="table-wrap">
        <table>
          <thead><tr><th>#</th><th>Cliente</th><th>Produto</th><th>Qtd</th><th>Unit.</th><th>Total</th><th>Status</th><th>Data</th></tr></thead>
          <tbody id="ordersTable"><tr><td colspan="8" class="empty">Carregando...</td></tr></tbody>
        </table>
      </div>
    </div>

    <!-- ══ Testar API ══ -->
    <div class="page" id="page-test">
      <div class="section-header"><h2>Testar API do Backend</h2></div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px" class="api-grid">
        <div>
          <div class="field">
            <label>Endpoint</label>
            <input id="apiUrl" value="/api/querybot.php">
          </div>
          <div class="field">
            <label>Authorization Bearer</label>
            <input id="apiToken" value="<?= htmlspecialchars(Config::get('QUERYBOT_API_TOKEN','')) ?>">
          </div>
          <div class="field">
            <label>Body JSON</label>
            <textarea id="apiBody" style="padding:9px 12px;border:1px solid var(--border);border-radius:8px;font-size:.82rem;font-family:monospace;resize:vertical;min-height:120px;color:var(--text);outline:none" onFocus="this.style.borderColor='var(--primary)'" onBlur="this.style.borderColor='var(--border)'">{
  "action": "health"
}</textarea>
          </div>
          <div style="display:flex;gap:8px;flex-wrap:wrap">
            <button class="btn btn-primary" onclick="runApi()">▶ Executar</button>
            <button class="btn btn-secondary" onclick="setBody('sales_summary','today')">Vendas hoje</button>
            <button class="btn btn-secondary" onclick="setBody('top_products')">Top produtos</button>
            <button class="btn btn-secondary" onclick="setBody('low_stock')">Estoque baixo</button>
            <button class="btn btn-secondary" onclick="setBody('recent_orders')">Pedidos</button>
          </div>
        </div>
        <div>
          <div class="field"><label>Resposta</label></div>
          <pre id="apiResult" style="background:#1e293b;color:#7dd3fc;padding:14px;border-radius:10px;font-size:.78rem;overflow:auto;min-height:280px;white-space:pre-wrap;word-break:break-word">Aguardando...</pre>
        </div>
      </div>
    </div>

  </main>
</div>

<!-- Modal produto -->
<div class="modal-bg" id="modalBg" onclick="if(event.target===this)closeModal()">
  <div class="modal">
    <h3 id="modalTitle">Novo Produto</h3>
    <input type="hidden" id="prodId">
    <div class="field"><label>Nome *</label><input id="prodName" placeholder="Ex: X-Burguer"></div>
    <div class="field"><label>Preço (R$) *</label><input id="prodPrice" type="number" min="0.01" step="0.01" placeholder="19.90"></div>
    <div class="field"><label>Estoque</label><input id="prodStock" type="number" min="0" value="0"></div>
    <div class="field">
      <label>Categoria</label>
      <select id="prodCat"><option value="">— selecione —</option></select>
    </div>
    <div class="field">
      <label style="flex-direction:row;align-items:center;gap:8px;display:flex">
        <input type="checkbox" id="prodActive" checked style="width:16px;height:16px">
        Produto ativo
      </label>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" onclick="closeModal()">Cancelar</button>
      <button class="btn btn-primary" onclick="saveProduct()">Salvar</button>
    </div>
  </div>
</div>

<!-- Toast -->
<div class="toast" id="toast"></div>

<script>
const API = '/api/admin.php';

// ── Utils ───────────────────────────────────────────────────────
function toast(msg, type='success') {
  const t = document.getElementById('toast');
  t.textContent = msg;
  t.className = 'toast show toast-' + type;
  setTimeout(() => t.className='toast', 3000);
}

function fmt(v) { return 'R$ ' + parseFloat(v).toFixed(2).replace('.',','); }
function fmtDate(s) {
  if (!s) return '—';
  return new Date(s).toLocaleString('pt-BR',{day:'2-digit',month:'2-digit',hour:'2-digit',minute:'2-digit'});
}
function badgeStatus(s) {
  const map = {completed:'badge-completed Concluído',pending:'badge-pending Pendente',cancelled:'badge-cancelled Cancelado'};
  const [cls,label] = (map[s]||'badge-ok '+s).split(' ');
  return `<span class="badge ${cls}">${label||s}</span>`;
}

// ── Nav ─────────────────────────────────────────────────────────
function showPage(id, btn) {
  document.querySelectorAll('.page').forEach(p => p.classList.remove('active'));
  document.querySelectorAll('.nav-item').forEach(b => b.classList.remove('active'));
  document.getElementById('page-'+id).classList.add('active');
  btn.classList.add('active');
  if (id==='dashboard') loadDashboard();
  if (id==='products')  loadProducts();
  if (id==='orders')    loadOrders();
}

// ── Dashboard ───────────────────────────────────────────────────
async function loadDashboard() {
  const r = await fetch(API + '?action=dashboard_stats').then(r=>r.json());
  if (!r.success) return;
  const d = r.data;
  document.getElementById('statsGrid').innerHTML = `
    <div class="stat-card success">
      <div class="label">Receita hoje</div>
      <div class="value">${fmt(d.revenue_today)}</div>
    </div>
    <div class="stat-card">
      <div class="label">Receita no mês</div>
      <div class="value">${fmt(d.revenue_month)}</div>
    </div>
    <div class="stat-card">
      <div class="label">Pedidos totais</div>
      <div class="value">${d.total_orders}</div>
    </div>
    <div class="stat-card">
      <div class="label">Produtos ativos</div>
      <div class="value">${d.total_products}</div>
    </div>
    <div class="stat-card">
      <div class="label">Clientes</div>
      <div class="value">${d.total_customers}</div>
    </div>
    <div class="stat-card ${d.low_stock > 0 ? 'warn' : ''}">
      <div class="label">Estoque crítico</div>
      <div class="value">${d.low_stock}</div>
      <div class="sub">produtos com ≤ 5 un.</div>
    </div>
  `;

  const r2 = await fetch(API + '?action=orders_list&limit=10').then(r=>r.json());
  if (!r2.success) return;
  const tbody = document.querySelector('#dashOrders tbody');
  if (!r2.data.length) { tbody.innerHTML='<tr><td colspan="6" class="empty">Nenhum pedido ainda.</td></tr>'; return; }
  tbody.innerHTML = r2.data.map(o => `
    <tr>
      <td>${o.customer}</td>
      <td>${o.product}</td>
      <td>${o.quantity}</td>
      <td>${fmt(o.total)}</td>
      <td>${badgeStatus(o.status)}</td>
      <td>${fmtDate(o.created_at)}</td>
    </tr>
  `).join('');
}

// ── Produtos ────────────────────────────────────────────────────
let products = [];

async function loadProducts() {
  const r = await fetch(API + '?action=products_list').then(r=>r.json());
  if (!r.success) return;
  products = r.data;
  document.getElementById('prodCount').textContent = `(${products.length})`;
  const tbody = document.getElementById('prodTable');
  if (!products.length) {
    tbody.innerHTML = '<tr><td colspan="7" class="empty">Nenhum produto. Use o botão "Gerar 20 produtos" acima.</td></tr>';
    return;
  }
  tbody.innerHTML = products.map(p => `
    <tr>
      <td style="color:var(--muted);font-size:.78rem">${p.id}</td>
      <td><strong>${p.name}</strong></td>
      <td>${p.category || '—'}</td>
      <td>${fmt(p.price)}</td>
      <td>
        <span class="badge ${parseInt(p.stock)<=5?'badge-low':'badge-ok'}">${p.stock}</span>
      </td>
      <td><span class="badge ${p.active?'badge-completed':'badge-cancelled'}">${p.active?'Ativo':'Inativo'}</span></td>
      <td>
        <button class="btn btn-secondary" style="padding:4px 8px;font-size:.75rem" onclick="editProduct(${p.id})">✏</button>
        <button class="btn btn-danger"    style="padding:4px 8px;font-size:.75rem;margin-left:4px" onclick="deleteProduct(${p.id})">🗑</button>
      </td>
    </tr>
  `).join('');
}

async function seedProducts() {
  const btn = document.getElementById('seedBtn');
  btn.disabled = true; btn.textContent = 'Gerando...';
  try {
    const r = await fetch(API + '?action=seed_products', {
      method:'POST', headers:{'Content-Type':'application/json'},
      body: JSON.stringify({qty:20})
    }).then(r=>r.json());
    toast(r.message || 'Gerado!', r.success ? 'success' : 'error');
    if (r.success) loadProducts();
  } finally {
    btn.disabled=false; btn.textContent='✨ Gerar 20 produtos';
  }
}

// ── Modal produto ────────────────────────────────────────────────
async function openModal(id=0) {
  document.getElementById('modalBg').classList.add('open');
  document.getElementById('modalTitle').textContent = id ? 'Editar Produto' : 'Novo Produto';
  document.getElementById('prodId').value = id;

  // Carrega categorias
  const cats = await fetch(API+'?action=categories_list').then(r=>r.json());
  const sel = document.getElementById('prodCat');
  sel.innerHTML = '<option value="">— selecione —</option>' +
    (cats.data||[]).map(c=>`<option value="${c.id}">${c.name}</option>`).join('');

  if (id) {
    const p = products.find(x=>x.id==id);
    if (p) {
      document.getElementById('prodName').value  = p.name;
      document.getElementById('prodPrice').value = p.price;
      document.getElementById('prodStock').value = p.stock;
      document.getElementById('prodActive').checked = !!+p.active;
      // tenta selecionar categoria pelo nome
      const opt = [...sel.options].find(o=>o.text===p.category);
      if (opt) sel.value = opt.value;
    }
  } else {
    document.getElementById('prodName').value  = '';
    document.getElementById('prodPrice').value = '';
    document.getElementById('prodStock').value = '0';
    document.getElementById('prodActive').checked = true;
  }
}

function closeModal() { document.getElementById('modalBg').classList.remove('open'); }

async function saveProduct() {
  const body = {
    id:       parseInt(document.getElementById('prodId').value)||0,
    name:     document.getElementById('prodName').value.trim(),
    price:    parseFloat(document.getElementById('prodPrice').value)||0,
    stock:    parseInt(document.getElementById('prodStock').value)||0,
    category_id: document.getElementById('prodCat').value||null,
    active:   document.getElementById('prodActive').checked,
  };
  const r = await fetch(API+'?action=product_save',{
    method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(body)
  }).then(r=>r.json());
  toast(r.message||'Salvo!', r.success?'success':'error');
  if (r.success) { closeModal(); loadProducts(); }
}

function editProduct(id) { openModal(id); }

async function deleteProduct(id) {
  if (!confirm('Remover este produto?')) return;
  const r = await fetch(API+'?action=product_delete',{
    method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({id})
  }).then(r=>r.json());
  toast(r.message||'Removido!', r.success?'success':'error');
  if (r.success) loadProducts();
}

// ── Pedidos ─────────────────────────────────────────────────────
async function loadOrders() {
  const r = await fetch(API+'?action=orders_list&limit=50').then(r=>r.json());
  if (!r.success) return;
  const tbody = document.getElementById('ordersTable');
  if (!r.data.length) { tbody.innerHTML='<tr><td colspan="8" class="empty">Nenhum pedido.</td></tr>'; return; }
  tbody.innerHTML = r.data.map((o,i) => `
    <tr>
      <td style="color:var(--muted);font-size:.78rem">${o.id}</td>
      <td>${o.customer}</td>
      <td>${o.product}</td>
      <td>${o.quantity}</td>
      <td>${fmt(o.unit_price)}</td>
      <td><strong>${fmt(o.total)}</strong></td>
      <td>${badgeStatus(o.status)}</td>
      <td>${fmtDate(o.created_at)}</td>
    </tr>
  `).join('');
}

// ── API Tester ───────────────────────────────────────────────────
function setBody(action, period) {
  const bodies = {
    sales_summary: `{\n  "action": "sales_summary",\n  "period": "${period||'today'}"\n}`,
    top_products:  `{\n  "action": "top_products",\n  "limit": 5\n}`,
    low_stock:     `{\n  "action": "low_stock",\n  "threshold": 10\n}`,
    recent_orders: `{\n  "action": "recent_orders",\n  "limit": 5\n}`,
  };
  document.getElementById('apiBody').value = bodies[action] || `{"action":"${action}"}`;
}

async function runApi() {
  const url   = document.getElementById('apiUrl').value.trim();
  const token = document.getElementById('apiToken').value.trim();
  const body  = document.getElementById('apiBody').value.trim();
  const result= document.getElementById('apiResult');
  result.textContent = 'Enviando...';
  result.style.color = '#7dd3fc';

  const headers = {'Content-Type':'application/json'};
  if (token) headers['Authorization'] = 'Bearer '+token;

  try {
    const t0 = performance.now();
    const res = await fetch(url,{method:'POST',headers,body});
    const ms  = Math.round(performance.now()-t0);
    const raw = await res.text();
    let data; try { data=JSON.parse(raw); } catch { data=raw; }
    result.textContent = `// HTTP ${res.status} · ${ms}ms\n\n` + JSON.stringify(data,null,2);
    result.style.color = res.ok ? '#86efac' : '#fca5a5';
  } catch(e) {
    result.textContent = 'Erro: ' + e.message;
    result.style.color = '#fca5a5';
  }
}

// ── Init ─────────────────────────────────────────────────────────
loadDashboard();

// Responsividade api tester
const mediaQ = window.matchMedia('(max-width:700px)');
if (mediaQ.matches) document.querySelector('.api-grid').style.gridTemplateColumns='1fr';
</script>
</body>
</html>
