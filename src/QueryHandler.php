<?php
declare(strict_types=1);

final class QueryHandler
{
    private const ALLOWED_ACTIONS = [
        'health','sales_summary','top_products','customers_count',
        'low_stock','recent_orders','products_list',
    ];

    public static function handle(array $payload): array
    {
        $action = isset($payload['action']) ? trim((string)$payload['action']) : 'health';

        if (!in_array($action, self::ALLOWED_ACTIONS, true)) {
            return self::error("Ação desconhecida: \"{$action}\". Use: " . implode(', ', self::ALLOWED_ACTIONS), 400);
        }

        return match ($action) {
            'health'          => self::health(),
            'sales_summary'   => self::salesSummary($payload),
            'top_products'    => self::topProducts($payload),
            'customers_count' => self::customersCount(),
            'low_stock'       => self::lowStock($payload),
            'recent_orders'   => self::recentOrders($payload),
            'products_list'   => self::productsList($payload),
        };
    }

    private static function health(): array
    {
        $db = null;
        try { $db = Database::connection(); $dbOk = true; } catch (\Throwable) { $dbOk = false; }
        return ['success' => true, 'status' => 'online', 'db' => $dbOk ? 'connected' : 'error', 'action' => 'health'];
    }

    private static function salesSummary(array $p): array
    {
        $period = $p['period'] ?? 'today';
        [$start, $end] = self::periodRange($period);

        $db  = Database::connection();
        $stmt = $db->prepare("
            SELECT COUNT(*) AS total_orders,
                   COALESCE(SUM(total),0) AS revenue,
                   COALESCE(AVG(total),0) AS avg_ticket
            FROM orders
            WHERE status = 'completed'
              AND created_at >= ? AND created_at < ?
        ");
        $stmt->execute([$start, $end]);
        $row = $stmt->fetch();

        return [
            'success' => true, 'action' => 'sales_summary', 'period' => $period,
            'data' => [
                'total_orders' => (int)$row['total_orders'],
                'revenue'      => round((float)$row['revenue'],    2),
                'avg_ticket'   => round((float)$row['avg_ticket'], 2),
                'period_label' => self::periodLabel($period),
            ],
        ];
    }

    private static function topProducts(array $p): array
    {
        $limit = max(1, min(20, (int)($p['limit'] ?? 5)));
        $db    = Database::connection();
        $stmt  = $db->prepare("
            SELECT product, SUM(quantity) AS units_sold, SUM(total) AS revenue
            FROM orders WHERE status='completed'
            GROUP BY product ORDER BY revenue DESC LIMIT ?
        ");
        $stmt->execute([$limit]);
        return [
            'success' => true, 'action' => 'top_products',
            'data' => array_map(fn($r) => [
                'product'    => $r['product'],
                'units_sold' => (int)$r['units_sold'],
                'revenue'    => round((float)$r['revenue'], 2),
            ], $stmt->fetchAll()),
        ];
    }

    private static function customersCount(): array
    {
        $total = (int)Database::connection()->query("SELECT COUNT(*) FROM customers")->fetchColumn();
        return ['success' => true, 'action' => 'customers_count', 'data' => ['total_customers' => $total]];
    }

    private static function lowStock(array $p): array
    {
        $threshold = max(0, (int)($p['threshold'] ?? 10));
        $stmt = Database::connection()->prepare("
            SELECT p.name, p.stock, c.name AS category
            FROM products p
            LEFT JOIN categories c ON c.id = p.category_id
            WHERE p.active=1 AND p.stock <= ?
            ORDER BY p.stock ASC
        ");
        $stmt->execute([$threshold]);
        return [
            'success' => true, 'action' => 'low_stock', 'threshold' => $threshold,
            'data' => array_map(fn($r) => [
                'name'     => $r['name'],
                'stock'    => (int)$r['stock'],
                'category' => $r['category'] ?? 'geral',
            ], $stmt->fetchAll()),
        ];
    }

    private static function recentOrders(array $p): array
    {
        $limit = max(1, min(50, (int)($p['limit'] ?? 10)));
        $stmt  = Database::connection()->prepare("
            SELECT customer, product, quantity, unit_price, total, status, created_at
            FROM orders ORDER BY created_at DESC LIMIT ?
        ");
        $stmt->execute([$limit]);
        return [
            'success' => true, 'action' => 'recent_orders',
            'data' => array_map(fn($r) => [
                'customer'   => $r['customer'],
                'product'    => $r['product'],
                'quantity'   => (int)$r['quantity'],
                'unit_price' => round((float)$r['unit_price'], 2),
                'total'      => round((float)$r['total'],      2),
                'status'     => $r['status'],
                'created_at' => $r['created_at'],
            ], $stmt->fetchAll()),
        ];
    }

    private static function productsList(array $p): array
    {
        $limit = max(1, min(100, (int)($p['limit'] ?? 20)));
        $stmt  = Database::connection()->prepare("
            SELECT p.id, p.name, p.price, p.stock, c.name AS category, p.active
            FROM products p
            LEFT JOIN categories c ON c.id = p.category_id
            WHERE p.active=1
            ORDER BY p.name LIMIT ?
        ");
        $stmt->execute([$limit]);
        return [
            'success' => true, 'action' => 'products_list',
            'data' => array_map(fn($r) => [
                'id'       => (int)$r['id'],
                'name'     => $r['name'],
                'price'    => round((float)$r['price'], 2),
                'stock'    => (int)$r['stock'],
                'category' => $r['category'] ?? 'geral',
            ], $stmt->fetchAll()),
        ];
    }

    // ── Helpers ────────────────────────────────────────────────────

    /** Retorna [start, end] em formato MySQL DATETIME */
    private static function periodRange(string $period): array
    {
        $tz = new DateTimeZone(Config::get('APP_TIMEZONE', 'America/Sao_Paulo'));
        $now = new DateTimeImmutable('now', $tz);

        return match ($period) {
            'yesterday' => [
                $now->modify('-1 day')->format('Y-m-d 00:00:00'),
                $now->format('Y-m-d 00:00:00'),
            ],
            'week' => [
                $now->modify('-7 days')->format('Y-m-d H:i:s'),
                $now->modify('+1 second')->format('Y-m-d H:i:s'),
            ],
            'month' => [
                $now->modify('-30 days')->format('Y-m-d H:i:s'),
                $now->modify('+1 second')->format('Y-m-d H:i:s'),
            ],
            default => [ // today
                $now->format('Y-m-d 00:00:00'),
                $now->modify('+1 day')->format('Y-m-d 00:00:00'),
            ],
        };
    }

    private static function periodLabel(string $period): string
    {
        return match ($period) {
            'yesterday' => 'ontem',
            'week'      => 'últimos 7 dias',
            'month'     => 'últimos 30 dias',
            default     => 'hoje',
        };
    }

    private static function error(string $msg, int $code = 400): array
    {
        http_response_code($code);
        return ['success' => false, 'error' => $msg];
    }
}
