<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/src/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$raw    = file_get_contents('php://input');
$body   = json_decode($raw ?: '{}', true) ?? [];

try {
    $db = Database::connection();

    switch ($action) {

        // ── Produtos ────────────────────────────────────────────
        case 'products_list':
            $stmt = $db->query("
                SELECT p.id, p.name, p.price, p.stock, p.active,
                       c.name AS category, p.created_at
                FROM products p
                LEFT JOIN categories c ON c.id = p.category_id
                ORDER BY p.created_at DESC
            ");
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll()], JSON_UNESCAPED_UNICODE);
            break;

        case 'product_save':
            $name       = trim($body['name'] ?? '');
            $price      = (float)($body['price'] ?? 0);
            $stock      = (int)($body['stock'] ?? 0);
            $categoryId = $body['category_id'] ? (int)$body['category_id'] : null;
            $active     = isset($body['active']) ? (int)(bool)$body['active'] : 1;
            $id         = (int)($body['id'] ?? 0);

            if ($name === '' || $price <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Nome e preço são obrigatórios.'], JSON_UNESCAPED_UNICODE);
                break;
            }

            if ($id > 0) {
                $db->prepare("UPDATE products SET name=?, price=?, stock=?, category_id=?, active=?, updated_at=NOW() WHERE id=?")
                   ->execute([$name, $price, $stock, $categoryId, $active, $id]);
                echo json_encode(['success' => true, 'message' => 'Produto atualizado.'], JSON_UNESCAPED_UNICODE);
            } else {
                $db->prepare("INSERT INTO products (name, price, stock, category_id, active) VALUES (?,?,?,?,?)")
                   ->execute([$name, $price, $stock, $categoryId, $active]);
                echo json_encode(['success' => true, 'id' => $db->lastInsertId(), 'message' => 'Produto criado.'], JSON_UNESCAPED_UNICODE);
            }
            break;

        case 'product_delete':
            $id = (int)($body['id'] ?? 0);
            if (!$id) { http_response_code(400); echo json_encode(['success'=>false,'error'=>'ID inválido.']); break; }
            $db->prepare("DELETE FROM products WHERE id=?")->execute([$id]);
            echo json_encode(['success' => true, 'message' => 'Produto removido.'], JSON_UNESCAPED_UNICODE);
            break;

        case 'seed_products':
            $qty = (int)($body['qty'] ?? 20);
            $inserted = Database::seedProducts($qty);
            echo json_encode(['success' => true, 'inserted' => $inserted, 'message' => "{$inserted} produtos gerados com sucesso."], JSON_UNESCAPED_UNICODE);
            break;

        // ── Categorias ─────────────────────────────────────────
        case 'categories_list':
            $rows = $db->query("SELECT id, name FROM categories ORDER BY name")->fetchAll();
            echo json_encode(['success' => true, 'data' => $rows], JSON_UNESCAPED_UNICODE);
            break;

        // ── Pedidos ─────────────────────────────────────────────
        case 'orders_list':
            $limit = min(100, max(10, (int)($_GET['limit'] ?? 50)));
            $stmt  = $db->prepare("
                SELECT o.id, o.customer, o.product, o.quantity,
                       o.unit_price, o.total, o.status, o.created_at
                FROM orders o ORDER BY o.created_at DESC LIMIT ?
            ");
            $stmt->execute([$limit]);
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll()], JSON_UNESCAPED_UNICODE);
            break;

        // ── Stats para o dashboard ──────────────────────────────
        case 'dashboard_stats':
            $todayStart = date('Y-m-d 00:00:00');
            $monthStart = date('Y-m-01 00:00:00');

            $revenue = $db->prepare("SELECT COALESCE(SUM(total),0) FROM orders WHERE status='completed' AND created_at >= ?");
            $revenue->execute([$todayStart]);
            $revenueToday = round((float)$revenue->fetchColumn(), 2);

            $revenue->execute([$monthStart]);
            $revenueMonth = round((float)$revenue->fetchColumn(), 2);

            $totalOrders   = (int)$db->query("SELECT COUNT(*) FROM orders WHERE status='completed'")->fetchColumn();
            $totalProducts = (int)$db->query("SELECT COUNT(*) FROM products WHERE active=1")->fetchColumn();
            $totalCustomers= (int)$db->query("SELECT COUNT(*) FROM customers")->fetchColumn();
            $lowStock      = (int)$db->query("SELECT COUNT(*) FROM products WHERE active=1 AND stock <= 5")->fetchColumn();

            echo json_encode(['success' => true, 'data' => [
                'revenue_today'   => $revenueToday,
                'revenue_month'   => $revenueMonth,
                'total_orders'    => $totalOrders,
                'total_products'  => $totalProducts,
                'total_customers' => $totalCustomers,
                'low_stock'       => $lowStock,
            ]], JSON_UNESCAPED_UNICODE);
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => "Ação desconhecida: {$action}"], JSON_UNESCAPED_UNICODE);
    }

} catch (Throwable $e) {
    Logger::error('Admin API error', ['msg' => $e->getMessage(), 'action' => $action]);
    http_response_code(500);
    $out = ['success' => false, 'error' => 'Erro interno.'];
    if (Config::bool('APP_DEBUG')) $out['debug'] = $e->getMessage();
    echo json_encode($out, JSON_UNESCAPED_UNICODE);
}
