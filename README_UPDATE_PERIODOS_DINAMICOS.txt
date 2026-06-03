Atualização: períodos dinâmicos no QueryBot

Arquivos alterados:
- src/QueryHandler.php
- src/Database.php

O que foi feito:
- Adicionado suporte a períodos dinâmicos: today, yesterday, this_week, last_week, last_7_days, this_month, last_month, last_30_days, this_year, last_year, all e custom.
- Adicionado suporte a aliases em português: hoje, ontem, essa_semana, semana_passada, esse_mes, mes_passado, esse_ano, ano_passado.
- Corrigido typo comum da IA: last_mouth agora é tratado como last_month.
- Adicionado suporte a período customizado via start_date/end_date.
- top_products agora também aceita period.
- Database.php agora usa PDO::ATTR_EMULATE_PREPARES = true para evitar erro de placeholder '?' em alguns ambientes MySQL/Railway com LIMIT.

Exemplos de body:
{
  "action": "sales_summary",
  "period": "last_month"
}

{
  "action": "sales_summary",
  "period": "last_year"
}

{
  "action": "top_products",
  "period": "last_30_days",
  "limit": 10
}

{
  "action": "sales_summary",
  "period": "custom",
  "start_date": "2026-05-01",
  "end_date": "2026-05-31"
}
