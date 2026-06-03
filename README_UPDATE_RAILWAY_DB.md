# Correção QueryBot + Railway DB

Arquivos alterados:
- `src/Database.php`
- `src/QueryHandler.php`
- `public/api/health.php`
- `public/api/status.php`
- `.env.example`

O que foi corrigido:
- Suporte a variáveis `DB_*` e `MYSQL*`.
- Suporte a `DATABASE_URL`/`MYSQL_URL`.
- Conexão com Railway usando host externo/local.
- Diagnóstico agora mostra o erro real do banco quando `APP_DEBUG=true`.
- Queries adaptadas para estrutura normalizada:
  `customers -> orders -> order_items -> products`.
- Schema idempotente cria/ajusta tabelas básicas se faltarem colunas.

## .env local com Railway externo

Use o host externo que funciona no DBeaver:

```env
DB_HOST=zephyr.proxy.rlwy.net
DB_PORT=56086
DB_DATABASE=railway
DB_USERNAME=root
DB_PASSWORD=SUA_SENHA

MYSQLHOST=zephyr.proxy.rlwy.net
MYSQLPORT=56086
MYSQLDATABASE=railway
MYSQLUSER=root
MYSQLPASSWORD=SUA_SENHA

APP_DEBUG=true
```

## Testar

```bash
php -S 127.0.0.1:8000 -t public public/router.php
```

Acesse:

```text
http://127.0.0.1:8000/api/health.php
```

Esperado:

```json
{
  "db": "connected"
}
```

## Testar API

```bash
curl -X POST http://127.0.0.1:8000/api/querybot.php \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer querybot123" \
  -d "{\"action\":\"products_list\",\"limit\":5}"
```
