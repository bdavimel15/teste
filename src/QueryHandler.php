<?php
declare(strict_types=1);

final class QueryHandler
{
    private const ALLOWED_ACTIONS = [
        'health','sales_summary','top_products','customers_count',
        'low_stock','recent_orders','products_list','customers_by_product',
    ];

    public static function handle(array $payload): array
    {
        $action = isset($payload['action']) ? trim((string)$payload['action']) : 'health';

        if (!in_array($action, self::ALLOWED_ACTIONS, true)) {
            return self::error('Ação desconhecida: "' . $action . '". Use: ' . implode(', ', self::ALLOWED_ACTIONS), 400);
        }

        return match ($action) {
            'health'          => self::health(),
            'sales_summary'   => self::salesSummary($payload),
            'top_products'    => self::topProducts($payload),
            'customers_count' => self::customersCount(),
            'low_stock'       => self::lowStock($payload),
            'recent_orders'   => self::recentOrders($payload),
            'products_list'   => self::productsList($payload),
            'customers_by_product' => self::customersByProduct($payload),
        };
    }

    private static function health(): array
    {
        try {
            $db = Database::connection();
            $tables = $db->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
            return [
                'success' => true,
                'status' => 'online',
                'db' => 'connected',
                'database' => Config::get('DB_DATABASE') ?: Config::get('DB_NAME') ?: Config::get('MYSQLDATABASE'),
                'tables' => $tables,
                'action' => 'health',
            ];
        } catch (Throwable $e) {
            $out = ['success' => true, 'status' => 'online', 'db' => 'error', 'action' => 'health'];
            if (Config::bool('APP_DEBUG')) {
                $out['db_error'] = $e->getMessage();
            }
            return $out;
        }
    }

    private static function salesSummary(array $p): array
    {
        $period = (string)($p['period'] ?? 'today');
        [$start, $end] = self::periodRange($p);

        $db = Database::connection();
        $statusSql = Database::completedStatusSql('o');
        $stmt = $db->prepare(<<<SQL
            SELECT COUNT(DISTINCT o.id) AS total_orders,
                   COALESCE(SUM(o.total), 0) AS revenue,
                   COALESCE(AVG(NULLIF(o.total, 0)), 0) AS avg_ticket
            FROM orders o
            WHERE {$statusSql}
              AND o.created_at >= ? AND o.created_at < ?
        SQL);
        $stmt->execute([$start, $end]);
        $row = $stmt->fetch() ?: ['total_orders' => 0, 'revenue' => 0, 'avg_ticket' => 0];

        return [
            'success' => true,
            'action' => 'sales_summary',
            'period' => $period,
            'data' => [
                'total_orders' => (int)$row['total_orders'],
                'revenue' => round((float)$row['revenue'], 2),
                'avg_ticket' => round((float)$row['avg_ticket'], 2),
                'period_label' => self::periodLabel($period),
            ],
        ];
    }

    private static function topProducts(array $p): array
    {
        $limit = max(1, min(20, (int)($p['limit'] ?? 5)));
        $period = (string)($p['period'] ?? 'all');
        [$start, $end] = self::periodRange($p);

        $db = Database::connection();
        $statusSql = Database::completedStatusSql('o');
        $dateSql = $period === 'all' ? '' : ' AND o.created_at >= ? AND o.created_at < ?';

        $stmt = $db->prepare(<<<SQL
            SELECT p.name AS product,
                   SUM(oi.quantity) AS units_sold,
                   SUM(COALESCE(NULLIF(oi.subtotal, 0), oi.quantity * oi.unit_price, oi.quantity * p.price)) AS revenue
            FROM order_items oi
            INNER JOIN orders o ON o.id = oi.order_id
            INNER JOIN products p ON p.id = oi.product_id
            WHERE {$statusSql}{$dateSql}
            GROUP BY p.id, p.name
            ORDER BY revenue DESC, units_sold DESC
            LIMIT ?
        SQL);
        $bindings = $period === 'all' ? [$limit] : [$start, $end, $limit];
        $stmt->execute($bindings);

        return [
            'success' => true,
            'action' => 'top_products',
            'period' => $period,
            'period_label' => self::periodLabel($period),
            'data' => array_map(static fn($r) => [
                'product' => $r['product'],
                'units_sold' => (int)$r['units_sold'],
                'revenue' => round((float)$r['revenue'], 2),
            ], $stmt->fetchAll()),
        ];
    }

    private static function customersCount(): array
    {
        $total = (int)Database::connection()->query('SELECT COUNT(*) FROM customers')->fetchColumn();
        return ['success' => true, 'action' => 'customers_count', 'data' => ['total_customers' => $total]];
    }

    private static function lowStock(array $p): array
    {
        $threshold = max(0, (int)($p['threshold'] ?? 10));
        $db = Database::connection();
        $hasCategory = Database::hasColumn('products', 'category_id');

        $sql = $hasCategory
            ? 'SELECT p.name, p.stock, c.name AS category FROM products p LEFT JOIN categories c ON c.id = p.category_id WHERE p.active=1 AND p.stock <= ? ORDER BY p.stock ASC'
            : 'SELECT p.name, p.stock, NULL AS category FROM products p WHERE p.active=1 AND p.stock <= ? ORDER BY p.stock ASC';

        $stmt = $db->prepare($sql);
        $stmt->execute([$threshold]);

        return [
            'success' => true,
            'action' => 'low_stock',
            'threshold' => $threshold,
            'data' => array_map(static fn($r) => [
                'name' => $r['name'],
                'stock' => (int)$r['stock'],
                'category' => $r['category'] ?? 'geral',
            ], $stmt->fetchAll()),
        ];
    }

    private static function recentOrders(array $p): array
    {
        $limit = max(1, min(50, (int)($p['limit'] ?? 10)));
        $db = Database::connection();
        $stmt = $db->prepare(<<<SQL
            SELECT o.id,
                   COALESCE(c.name, CONCAT('Cliente #', o.customer_id), 'Cliente') AS customer,
                   GROUP_CONCAT(CONCAT(p.name, ' x', oi.quantity) ORDER BY p.name SEPARATOR ', ') AS product,
                   SUM(oi.quantity) AS quantity,
                   SUM(COALESCE(NULLIF(oi.subtotal, 0), oi.quantity * oi.unit_price, oi.quantity * p.price)) AS total,
                   o.status,
                   o.created_at
            FROM orders o
            LEFT JOIN customers c ON c.id = o.customer_id
            LEFT JOIN order_items oi ON oi.order_id = o.id
            LEFT JOIN products p ON p.id = oi.product_id
            GROUP BY o.id, c.name, o.customer_id, o.status, o.created_at
            ORDER BY o.created_at DESC
            LIMIT ?
        SQL);
        $stmt->execute([$limit]);

        return [
            'success' => true,
            'action' => 'recent_orders',
            'data' => array_map(static fn($r) => [
                'customer' => $r['customer'],
                'product' => $r['product'] ?: 'Sem itens',
                'quantity' => (int)($r['quantity'] ?? 0),
                'total' => round((float)($r['total'] ?? 0), 2),
                'status' => $r['status'],
                'created_at' => $r['created_at'],
            ], $stmt->fetchAll()),
        ];
    }

    private static function productsList(array $p): array
    {
        $limit = max(1, min(100, (int)($p['limit'] ?? 20)));
        $db = Database::connection();
        $hasCategory = Database::hasColumn('products', 'category_id');

        $sql = $hasCategory
            ? 'SELECT p.id, p.name, p.price, p.stock, c.name AS category, p.active FROM products p LEFT JOIN categories c ON c.id = p.category_id WHERE p.active=1 ORDER BY p.name LIMIT ?'
            : 'SELECT p.id, p.name, p.price, p.stock, NULL AS category, p.active FROM products p WHERE p.active=1 ORDER BY p.name LIMIT ?';

        $stmt = $db->prepare($sql);
        $stmt->execute([$limit]);

        return [
            'success' => true,
            'action' => 'products_list',
            'data' => array_map(static fn($r) => [
                'id' => (int)$r['id'],
                'name' => $r['name'],
                'price' => round((float)$r['price'], 2),
                'stock' => (int)$r['stock'],
                'category' => $r['category'] ?? 'geral',
            ], $stmt->fetchAll()),
        ];
    }


    private static function customersByProduct(array $p): array
    {
        $product = trim((string)($p['product'] ?? $p['produto'] ?? $p['name'] ?? ''));
        $limit = max(1, min(100, (int)($p['limit'] ?? 20)));

        if ($product === '') {
            return self::error('Informe o produto. Exemplo: {"action":"customers_by_product","product":"Picanha"}', 400);
        }

        $db = Database::connection();
        $statusSql = Database::completedStatusSql('o');

        $stmt = $db->prepare(<<<SQL
            SELECT c.id,
                   c.name,
                   c.email,
                   c.phone,
                   COUNT(DISTINCT o.id) AS orders_count,
                   COALESCE(SUM(oi.quantity), 0) AS units,
                   COALESCE(SUM(COALESCE(NULLIF(oi.subtotal, 0), oi.quantity * oi.unit_price, oi.quantity * p.price)), 0) AS total_spent,
                   MAX(o.created_at) AS last_order_at
            FROM customers c
            INNER JOIN orders o ON o.customer_id = c.id
            INNER JOIN order_items oi ON oi.order_id = o.id
            INNER JOIN products p ON p.id = oi.product_id
            WHERE {$statusSql}
              AND p.name LIKE ?
            GROUP BY c.id, c.name, c.email, c.phone
            ORDER BY last_order_at DESC, total_spent DESC
            LIMIT ?
        SQL);

        $stmt->execute(['%' . $product . '%', $limit]);

        return [
            'success' => true,
            'action' => 'customers_by_product',
            'product' => $product,
            'data' => array_map(static fn($r) => [
                'id' => (int)$r['id'],
                'name' => $r['name'],
                'email' => $r['email'],
                'phone' => $r['phone'],
                'orders_count' => (int)$r['orders_count'],
                'units' => (int)$r['units'],
                'total_spent' => round((float)$r['total_spent'], 2),
                'last_order_at' => $r['last_order_at'],
            ], $stmt->fetchAll()),
        ];
    }

    private static function periodRange(array|string $input): array
    {
        $payload = is_array($input) ? $input : ['period' => $input];
        $period = self::normalizePeriod((string)($payload['period'] ?? 'today'));

        $tz = new DateTimeZone(Config::get('APP_TIMEZONE', 'America/Sao_Paulo'));
        $now = new DateTimeImmutable('now', $tz);

        // Permite período customizado sem deixar a IA gerar SQL.
        // Exemplo: {"period":"custom","start_date":"2026-05-01","end_date":"2026-06-01"}
        if ($period === 'custom') {
            $startRaw = (string)($payload['start_date'] ?? $payload['start'] ?? '');
            $endRaw = (string)($payload['end_date'] ?? $payload['end'] ?? '');

            if ($startRaw !== '' && $endRaw !== '') {
                $start = new DateTimeImmutable($startRaw, $tz);
                $end = new DateTimeImmutable($endRaw, $tz);
                return [$start->setTime(0, 0, 0)->format('Y-m-d H:i:s'), $end->setTime(23, 59, 59)->format('Y-m-d H:i:s')];
            }
        }

        return match ($period) {
            'all' => ['1970-01-01 00:00:00', $now->modify('+100 years')->format('Y-m-d H:i:s')],

            'today' => [
                $now->setTime(0, 0, 0)->format('Y-m-d H:i:s'),
                $now->modify('+1 day')->setTime(0, 0, 0)->format('Y-m-d H:i:s'),
            ],

            'yesterday' => [
                $now->modify('-1 day')->setTime(0, 0, 0)->format('Y-m-d H:i:s'),
                $now->setTime(0, 0, 0)->format('Y-m-d H:i:s'),
            ],

            'last_7_days' => [
                $now->modify('-7 days')->setTime(0, 0, 0)->format('Y-m-d H:i:s'),
                $now->modify('+1 day')->setTime(0, 0, 0)->format('Y-m-d H:i:s'),
            ],

            'this_week' => [
                $now->modify('monday this week')->setTime(0, 0, 0)->format('Y-m-d H:i:s'),
                $now->modify('monday next week')->setTime(0, 0, 0)->format('Y-m-d H:i:s'),
            ],

            'last_week' => [
                $now->modify('monday last week')->setTime(0, 0, 0)->format('Y-m-d H:i:s'),
                $now->modify('monday this week')->setTime(0, 0, 0)->format('Y-m-d H:i:s'),
            ],

            'last_30_days' => [
                $now->modify('-30 days')->setTime(0, 0, 0)->format('Y-m-d H:i:s'),
                $now->modify('+1 day')->setTime(0, 0, 0)->format('Y-m-d H:i:s'),
            ],

            'this_month' => [
                $now->modify('first day of this month')->setTime(0, 0, 0)->format('Y-m-d H:i:s'),
                $now->modify('first day of next month')->setTime(0, 0, 0)->format('Y-m-d H:i:s'),
            ],

            'last_month' => [
                $now->modify('first day of last month')->setTime(0, 0, 0)->format('Y-m-d H:i:s'),
                $now->modify('first day of this month')->setTime(0, 0, 0)->format('Y-m-d H:i:s'),
            ],

            'this_year' => [
                $now->setDate((int)$now->format('Y'), 1, 1)->setTime(0, 0, 0)->format('Y-m-d H:i:s'),
                $now->setDate(((int)$now->format('Y')) + 1, 1, 1)->setTime(0, 0, 0)->format('Y-m-d H:i:s'),
            ],

            'last_year' => [
                $now->setDate(((int)$now->format('Y')) - 1, 1, 1)->setTime(0, 0, 0)->format('Y-m-d H:i:s'),
                $now->setDate((int)$now->format('Y'), 1, 1)->setTime(0, 0, 0)->format('Y-m-d H:i:s'),
            ],

            default => [
                $now->setTime(0, 0, 0)->format('Y-m-d H:i:s'),
                $now->modify('+1 day')->setTime(0, 0, 0)->format('Y-m-d H:i:s'),
            ],
        };
    }

    private static function normalizePeriod(string $period): string
    {
        $period = strtolower(trim($period));
        $period = str_replace([' ', '-', 'ã', 'á', 'à', 'â', 'é', 'ê', 'í', 'ó', 'ô', 'õ', 'ú', 'ç'], ['_', '_', 'a', 'a', 'a', 'a', 'e', 'e', 'i', 'o', 'o', 'o', 'u', 'c'], $period);

        return match ($period) {
            'all', 'todos', 'tudo', 'geral' => 'all',
            'today', 'hoje' => 'today',
            'yesterday', 'ontem' => 'yesterday',

            'week', 'this_week', 'essa_semana', 'esta_semana', 'semana_atual' => 'this_week',
            'last_week', 'semana_passada' => 'last_week',
            'last_7_days', 'ultimos_7_dias', 'ultimos_7' => 'last_7_days',

            'month', 'this_month', 'mes', 'esse_mes', 'este_mes', 'mes_atual' => 'this_month',
            'last_month', 'last_mouth', 'mes_passado', 'ultimo_mes' => 'last_month',
            'last_30_days', 'ultimos_30_dias', 'ultimos_30' => 'last_30_days',

            'year', 'this_year', 'ano', 'esse_ano', 'este_ano', 'ano_atual' => 'this_year',
            'last_year', 'ano_passado', 'ultimo_ano' => 'last_year',

            'custom', 'personalizado' => 'custom',
            default => $period,
        };
    }

    private static function periodLabel(string $period): string
    {
        return match (self::normalizePeriod($period)) {
            'all' => 'todo o período',
            'today' => 'hoje',
            'yesterday' => 'ontem',
            'this_week' => 'esta semana',
            'last_week' => 'semana passada',
            'last_7_days' => 'últimos 7 dias',
            'this_month' => 'este mês',
            'last_month' => 'mês passado',
            'last_30_days' => 'últimos 30 dias',
            'this_year' => 'este ano',
            'last_year' => 'ano passado',
            'custom' => 'período personalizado',
            default => 'hoje',
        };
    }

    private static function error(string $msg, int $code = 400): array
    {
        http_response_code($code);
        return ['success' => false, 'error' => $msg];
    }
}
