# QueryBot — Backend PHP + Zaia

## Arquitetura

```
Frontend → Zaia → /api/querybot.php → Zaia → Frontend
```

A **Zaia** é o cérebro: gerencia conversa, IA e resposta final.
O **backend PHP** é apenas uma ferramenta de dados que a Zaia chama.

---

## Início rápido

```bash
# 1. Copie o .env
copy .env.example .env

# 2. Edite o .env com suas credenciais

# 3. Inicie o servidor (Windows)
INICIAR-SERVIDOR.bat

# ou manualmente:
php -S localhost:8000 -t public public/router.php
```

Acesse: **http://localhost:8000**

---

## Variáveis de ambiente (.env)

| Variável              | Descrição                                              |
|-----------------------|--------------------------------------------------------|
| `ZAIA_WEBHOOK_URL`    | URL do Webhook de entrada da Zaia (sem Bearer)         |
| `QUERYBOT_API_TOKEN`  | Token compartilhado entre Zaia → Backend               |
| `DB_PATH`             | Caminho do banco SQLite (padrão: `../database/querybot.sqlite`) |
| `APP_DEBUG`           | `true` exibe erros e ativa log verbose                 |
| `APP_TIMEZONE`        | Fuso horário (padrão: `America/Sao_Paulo`)             |
| `LOG_PATH`            | Caminho do arquivo de log                              |

---

## Endpoints

### `GET /api/health.php` — sem autenticação

```json
{ "success": true, "status": "online", "db": "connected", "php": "8.x.x" }
```

### `POST /api/querybot.php` — requer Bearer token (se configurado)

**Headers:**
```
Content-Type:  application/json
Authorization: Bearer <QUERYBOT_API_TOKEN>
```

**Ações disponíveis:**

| `action`           | Payload extra                  | Descrição                     |
|--------------------|--------------------------------|-------------------------------|
| `health`           | —                              | Status do backend             |
| `sales_summary`    | `period`: today/yesterday/week/month | Resumo de vendas       |
| `top_products`     | `limit`: 1–20 (padrão 5)      | Produtos mais vendidos        |
| `customers_count`  | —                              | Total de clientes             |
| `low_stock`        | `threshold`: int (padrão 10)  | Produtos com estoque baixo    |
| `recent_orders`    | `limit`: 1–50 (padrão 10)     | Pedidos recentes              |

**Exemplo:**
```bash
curl -X POST http://localhost:8000/api/querybot.php \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer querybot123" \
  -d '{"action":"sales_summary","period":"today"}'
```

---

## Configuração da Zaia

### Webhook Request (entrada)
- Sem autenticação / Credential Pool

### HTTP Request (chamada ao backend)
- URL: `http://SEU_HOST:8000/api/querybot.php`
- Método: `POST`
- Headers: `Content-Type: application/json`, `Authorization: Bearer <token>`
- Body: `{ "action": "sales_summary", "period": "today" }`

### Webhook Response
- Response Behavior: **Webhook Response Node**

---

## Estrutura do projeto

```
/
├── .env
├── .env.example
├── INICIAR-SERVIDOR.bat
├── database/
│   └── querybot.sqlite     ← criado automaticamente
├── logs/
│   └── querybot.log
├── public/
│   ├── router.php
│   ├── index.php           ← frontend
│   ├── ping.php
│   └── api/
│       ├── health.php
│       └── querybot.php    ← endpoint principal
└── src/
    ├── bootstrap.php
    ├── Config.php
    ├── Database.php
    ├── Logger.php
    └── QueryHandler.php
```

---

## Adicionando novas ações

1. Adicione o nome ao array `ALLOWED_ACTIONS` em `src/QueryHandler.php`
2. Crie o método privado estático correspondente
3. Adicione o case no `match` de `handle()`
