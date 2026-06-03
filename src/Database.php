<?php
declare(strict_types=1);

final class Database
{
    private static ?PDO $instance = null;
    private static array $columnsCache = [];

    public static function connection(): PDO
    {
        if (self::$instance !== null) {
            return self::$instance;
        }

        $url = Config::get('DATABASE_URL') ?: Config::get('MYSQL_URL');
        $cfg = self::configFromUrl($url);

        $host = $cfg['host'] ?? self::envFirst(['DB_HOST', 'MYSQLHOST', 'MYSQL_HOST'], '127.0.0.1');
        $port = $cfg['port'] ?? self::envFirst(['DB_PORT', 'MYSQLPORT', 'MYSQL_PORT'], '3306');
        $name = $cfg['database'] ?? self::envFirst(['DB_DATABASE', 'DB_NAME', 'MYSQLDATABASE', 'MYSQL_DATABASE'], 'querybot');
        $user = $cfg['username'] ?? self::envFirst(['DB_USERNAME', 'DB_USER', 'MYSQLUSER', 'MYSQL_USER'], 'root');
        $pass = $cfg['password'] ?? self::envFirst(['DB_PASSWORD', 'DB_PASS', 'MYSQLPASSWORD', 'MYSQL_ROOT_PASSWORD', 'MYSQL_PASSWORD'], '');

        $dsn = "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";

        try {
            $pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::ATTR_TIMEOUT            => 12,
            ]);
        } catch (Throwable $e) {
            Logger::error('Database connection failed', [
                'host' => $host,
                'port' => $port,
                'database' => $name,
                'user' => $user,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }

        self::applySchema($pdo);
        self::$instance = $pdo;
        return $pdo;
    }

    private static function envFirst(array $keys, string $default = ''): string
    {
        foreach ($keys as $key) {
            $value = Config::get($key);
            if ($value !== null && $value !== '') {
                return $value;
            }
        }
        return $default;
    }

    private static function configFromUrl(?string $url): array
    {
        if (!$url) {
            return [];
        }

        $parts = parse_url($url);
        if (!$parts || empty($parts['host'])) {
            return [];
        }

        return [
            'host' => $parts['host'],
            'port' => isset($parts['port']) ? (string)$parts['port'] : '3306',
            'database' => isset($parts['path']) ? ltrim($parts['path'], '/') : 'railway',
            'username' => isset($parts['user']) ? urldecode($parts['user']) : 'root',
            'password' => isset($parts['pass']) ? urldecode($parts['pass']) : '',
        ];
    }

    private static function applySchema(PDO $pdo): void
    {
        // Estrutura normalizada: cliente -> pedido -> item -> produto.
        // As instruções são idempotentes para funcionar tanto local quanto Railway.
        $pdo->exec(<<<SQL
            CREATE TABLE IF NOT EXISTS categories (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL UNIQUE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE IF NOT EXISTS products (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(200) NOT NULL,
                description VARCHAR(255) NULL,
                price DECIMAL(10,2) NOT NULL DEFAULT 0,
                stock INT NOT NULL DEFAULT 0,
                category_id INT NULL,
                active TINYINT(1) NOT NULL DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE IF NOT EXISTS customers (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(200) NOT NULL,
                email VARCHAR(200) NULL,
                phone VARCHAR(30) NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE IF NOT EXISTS orders (
                id INT AUTO_INCREMENT PRIMARY KEY,
                customer_id INT NULL,
                total DECIMAL(10,2) NOT NULL DEFAULT 0,
                status VARCHAR(40) NOT NULL DEFAULT 'completed',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_orders_customer_id (customer_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE IF NOT EXISTS order_items (
                id INT AUTO_INCREMENT PRIMARY KEY,
                order_id INT NOT NULL,
                product_id INT NOT NULL,
                quantity INT NOT NULL DEFAULT 1,
                unit_price DECIMAL(10,2) NOT NULL DEFAULT 0,
                subtotal DECIMAL(10,2) NOT NULL DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_order_items_order_id (order_id),
                INDEX idx_order_items_product_id (product_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        SQL);

        // Compatibilidade com dumps antigos/incompletos.
        self::ensureColumn($pdo, 'products', 'description', 'VARCHAR(255) NULL');
        self::ensureColumn($pdo, 'products', 'price', 'DECIMAL(10,2) NOT NULL DEFAULT 0');
        self::ensureColumn($pdo, 'products', 'stock', 'INT NOT NULL DEFAULT 0');
        self::ensureColumn($pdo, 'products', 'category_id', 'INT NULL');
        self::ensureColumn($pdo, 'products', 'active', 'TINYINT(1) NOT NULL DEFAULT 1');
        self::ensureColumn($pdo, 'products', 'created_at', 'TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP');
        self::ensureColumn($pdo, 'products', 'updated_at', 'TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');

        self::ensureColumn($pdo, 'customers', 'email', 'VARCHAR(200) NULL');
        self::ensureColumn($pdo, 'customers', 'phone', 'VARCHAR(30) NULL');
        self::ensureColumn($pdo, 'customers', 'created_at', 'TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP');
        self::ensureColumn($pdo, 'customers', 'updated_at', 'TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');

        self::ensureColumn($pdo, 'orders', 'customer_id', 'INT NULL');
        self::ensureColumn($pdo, 'orders', 'total', 'DECIMAL(10,2) NOT NULL DEFAULT 0');
        self::ensureColumn($pdo, 'orders', 'status', "VARCHAR(40) NOT NULL DEFAULT 'completed'");
        self::ensureColumn($pdo, 'orders', 'created_at', 'TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP');
        self::ensureColumn($pdo, 'orders', 'updated_at', 'TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');

        self::ensureColumn($pdo, 'order_items', 'quantity', 'INT NOT NULL DEFAULT 1');
        self::ensureColumn($pdo, 'order_items', 'unit_price', 'DECIMAL(10,2) NOT NULL DEFAULT 0');
        self::ensureColumn($pdo, 'order_items', 'subtotal', 'DECIMAL(10,2) NOT NULL DEFAULT 0');
        self::ensureColumn($pdo, 'order_items', 'created_at', 'TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP');

        self::$columnsCache = [];
    }

    public static function hasTable(string $table): bool
    {
        $pdo = self::connection();
        $stmt = $pdo->prepare('SHOW TABLES LIKE ?');
        $stmt->execute([$table]);
        return (bool)$stmt->fetchColumn();
    }

    public static function hasColumn(string $table, string $column): bool
    {
        $key = $table . '.' . $column;
        if (array_key_exists($key, self::$columnsCache)) {
            return self::$columnsCache[$key];
        }

        $pdo = self::connection();
        $stmt = $pdo->prepare("SHOW COLUMNS FROM `{$table}` LIKE ?");
        $stmt->execute([$column]);
        return self::$columnsCache[$key] = (bool)$stmt->fetchColumn();
    }

    private static function ensureColumn(PDO $pdo, string $table, string $column, string $definition): void
    {
        $stmt = $pdo->prepare("SHOW COLUMNS FROM `{$table}` LIKE ?");
        $stmt->execute([$column]);
        if (!$stmt->fetchColumn()) {
            $pdo->exec("ALTER TABLE `{$table}` ADD COLUMN `{$column}` {$definition}");
        }
    }

    public static function completedStatusSql(string $alias = 'o'): string
    {
        return "LOWER({$alias}.status) IN ('completed','complete','paid','pago','concluido','concluida','finalizado','finalizada')";
    }

    /** Seed rápido — produtos, clientes e pedidos de exemplo */
    public static function seedProducts(int $qty = 20): int
    {
        $pdo = self::connection();

        $cats = ['Lanches','Bebidas','Sobremesas','Combos','Carnes','Acompanhamentos'];
        foreach ($cats as $cat) {
            $pdo->prepare('INSERT IGNORE INTO categories (name) VALUES (?)')->execute([$cat]);
        }
        $catRows = $pdo->query('SELECT id, name FROM categories')->fetchAll();
        $catMap = array_column($catRows, 'id', 'name');

        $names = [
            'Carnes' => ['Picanha','Filé acebolado','Contra filé','Fraldinha'],
            'Lanches' => ['X-Burguer','X-Salada','X-Bacon','X-Tudo','Hambúrguer Artesanal'],
            'Bebidas' => ['Coca-Cola 350ml','Suco de Laranja','Água Mineral','Refrigerante Lata'],
            'Sobremesas' => ['Brownie','Sorvete 2 Bolas','Cheesecake','Açaí 300ml'],
            'Combos' => ['Combo Família','Combo Casal','Combo Individual'],
            'Acompanhamentos' => ['Batata Frita P','Batata Frita G','Onion Rings'],
        ];

        $existing = (int)$pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();
        $inserted = 0;
        if ($existing === 0) {
            $stmt = $pdo->prepare('INSERT INTO products (name, description, price, stock, category_id, active) VALUES (?, ?, ?, ?, ?, 1)');
            $prices = [9.90,12.90,15.90,19.90,24.90,29.90,34.90,39.90,49.90,89.90];
            $all = [];
            foreach ($names as $cat => $list) {
                foreach ($list as $pname) {
                    $all[] = [$pname, $cat];
                }
            }
            shuffle($all);
            foreach (array_slice($all, 0, $qty) as [$pname, $cat]) {
                $stmt->execute([$pname, 'Produto de exemplo', $prices[array_rand($prices)], rand(0, 50), $catMap[$cat] ?? null]);
                $inserted++;
            }
        }

        if ((int)$pdo->query('SELECT COUNT(*) FROM customers')->fetchColumn() === 0) {
            $customerNames = ['João Silva','Maria Costa','Pedro Souza','Ana Lima','Carlos Ferreira','Fernanda Rocha','Lucas Mendes','Juliana Alves','Roberto Nunes','Patrícia Gomes'];
            $cstmt = $pdo->prepare('INSERT INTO customers (name, email, phone) VALUES (?,?,?)');
            foreach ($customerNames as $cn) {
                $slug = strtolower(str_replace(' ', '.', iconv('UTF-8', 'ASCII//TRANSLIT', $cn) ?: $cn));
                $cstmt->execute([$cn, "{$slug}@email.com", '(11) 9' . rand(1000,9999) . '-' . rand(1000,9999)]);
            }
        }

        if ((int)$pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn() === 0) {
            $prods = $pdo->query('SELECT id, name, price FROM products LIMIT 50')->fetchAll();
            $custs = $pdo->query('SELECT id, name FROM customers LIMIT 20')->fetchAll();
            if ($prods && $custs) {
                $ostmt = $pdo->prepare('INSERT INTO orders (customer_id, total, status, created_at) VALUES (?,?,?,?)');
                $istmt = $pdo->prepare('INSERT INTO order_items (order_id, product_id, quantity, unit_price, subtotal, created_at) VALUES (?,?,?,?,?,?)');
                for ($i = 0; $i < 30; $i++) {
                    $p = $prods[array_rand($prods)];
                    $c = $custs[array_rand($custs)];
                    $qty = rand(1, 4);
                    $subtotal = round($qty * (float)$p['price'], 2);
                    $ts = date('Y-m-d H:i:s', strtotime('-' . rand(0, 30) . ' days'));
                    $status = rand(0, 9) < 8 ? 'completed' : (rand(0, 1) ? 'pending' : 'cancelled');
                    $ostmt->execute([$c['id'], $subtotal, $status, $ts]);
                    $orderId = (int)$pdo->lastInsertId();
                    $istmt->execute([$orderId, $p['id'], $qty, $p['price'], $subtotal, $ts]);
                }
            }
        }

        return $inserted;
    }
}
