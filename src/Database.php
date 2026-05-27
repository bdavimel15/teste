<?php
declare(strict_types=1);

final class Database
{
    private static ?PDO $instance = null;

    public static function connection(): PDO
    {
        if (self::$instance !== null) return self::$instance;

        $host = Config::get('DB_HOST', '127.0.0.1');
        $port = Config::get('DB_PORT', '3306');
        $name = Config::get('DB_NAME', 'querybot');
        $user = Config::get('DB_USER', 'root');
        $pass = Config::get('DB_PASS', '');

        $dsn = "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";

        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);

        self::applySchema($pdo);
        self::$instance = $pdo;
        return $pdo;
    }

    private static function applySchema(PDO $pdo): void
    {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS categories (
                id         INT AUTO_INCREMENT PRIMARY KEY,
                name       VARCHAR(100) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE IF NOT EXISTS products (
                id          INT AUTO_INCREMENT PRIMARY KEY,
                name        VARCHAR(200) NOT NULL,
                price       DECIMAL(10,2) NOT NULL,
                stock       INT NOT NULL DEFAULT 0,
                category_id INT NULL,
                active      TINYINT(1) NOT NULL DEFAULT 1,
                created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE IF NOT EXISTS customers (
                id         INT AUTO_INCREMENT PRIMARY KEY,
                name       VARCHAR(200) NOT NULL,
                email      VARCHAR(200) UNIQUE NOT NULL,
                phone      VARCHAR(30)  NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE IF NOT EXISTS orders (
                id          INT AUTO_INCREMENT PRIMARY KEY,
                customer_id INT NULL,
                customer    VARCHAR(200) NOT NULL,
                product_id  INT NULL,
                product     VARCHAR(200) NOT NULL,
                quantity    INT NOT NULL DEFAULT 1,
                unit_price  DECIMAL(10,2) NOT NULL,
                total       DECIMAL(10,2) GENERATED ALWAYS AS (quantity * unit_price) STORED,
                status      ENUM('pending','completed','cancelled') NOT NULL DEFAULT 'completed',
                created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL,
                FOREIGN KEY (product_id)  REFERENCES products(id)  ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
    }

    /** Seed rápido — 20 produtos + pedidos de exemplo */
    public static function seedProducts(int $qty = 20): int
    {
        $pdo = self::connection();

        // Garante categorias
        $cats = ['Lanches','Bebidas','Sobremesas','Combos','Acompanhamentos'];
        foreach ($cats as $cat) {
            $pdo->prepare("INSERT IGNORE INTO categories (name) VALUES (?)")->execute([$cat]);
        }
        $catRows = $pdo->query("SELECT id, name FROM categories")->fetchAll();
        $catMap  = array_column($catRows, 'id', 'name');

        $names = [
            'Lanches'         => ['X-Burguer','X-Salada','X-Bacon','X-Tudo','Hambúrguer Artesanal','Wrap Frango','Hot Dog Especial'],
            'Bebidas'         => ['Coca-Cola 350ml','Suco de Laranja','Água Mineral','Limonada','Milkshake Chocolate','Refrigerante Lata'],
            'Sobremesas'      => ['Brownie','Sorvete 2 Bolas','Cheesecake','Petit Gâteau','Açaí 300ml'],
            'Combos'          => ['Combo Família','Combo Casal','Combo Individual','Combo Kids'],
            'Acompanhamentos' => ['Batata Frita P','Batata Frita G','Onion Rings','Nuggets 6un'],
        ];

        $inserted = 0;
        $stmt = $pdo->prepare("INSERT INTO products (name, price, stock, category_id) VALUES (?, ?, ?, ?)");
        $prices = [9.90,12.90,15.90,19.90,24.90,29.90,34.90,39.90,44.90,49.90,54.90,59.90];

        $all = [];
        foreach ($names as $cat => $list) {
            foreach ($list as $pname) {
                $all[] = [$pname, $cat];
            }
        }
        shuffle($all);
        $all = array_slice($all, 0, $qty);

        foreach ($all as [$pname, $cat]) {
            $price = $prices[array_rand($prices)];
            $stock = rand(0, 50);
            $stmt->execute([$pname, $price, $stock, $catMap[$cat] ?? null]);
            $inserted++;
        }

        // Seed clientes e pedidos se vazio
        $hasCustomers = (int)$pdo->query("SELECT COUNT(*) FROM customers")->fetchColumn();
        if ($hasCustomers === 0) {
            $customerNames = ['João Silva','Maria Costa','Pedro Souza','Ana Lima','Carlos Ferreira',
                              'Fernanda Rocha','Lucas Mendes','Juliana Alves','Roberto Nunes','Patrícia Gomes'];
            $cstmt = $pdo->prepare("INSERT INTO customers (name, email, phone) VALUES (?,?,?)");
            foreach ($customerNames as $i => $cn) {
                $slug  = strtolower(str_replace(' ','.',$cn));
                $phone = '(11) 9' . rand(1000,9999) . '-' . rand(1000,9999);
                $cstmt->execute([$cn, "{$slug}@email.com", $phone]);
            }
        }

        // 30 pedidos aleatórios nos últimos 30 dias
        $prods = $pdo->query("SELECT id, name, price FROM products LIMIT 50")->fetchAll();
        $custs = $pdo->query("SELECT id, name FROM customers LIMIT 20")->fetchAll();
        if ($prods && $custs) {
            $ostmt = $pdo->prepare("INSERT INTO orders (customer_id, customer, product_id, product, quantity, unit_price, status, created_at) VALUES (?,?,?,?,?,?,?,?)");
            for ($i = 0; $i < 30; $i++) {
                $p   = $prods[array_rand($prods)];
                $c   = $custs[array_rand($custs)];
                $qty = rand(1, 4);
                $daysAgo = rand(0, 30);
                $ts  = date('Y-m-d H:i:s', strtotime("-{$daysAgo} days"));
                $status = rand(0,9) < 8 ? 'completed' : (rand(0,1) ? 'pending' : 'cancelled');
                $ostmt->execute([$c['id'], $c['name'], $p['id'], $p['name'], $qty, $p['price'], $status, $ts]);
            }
        }

        return $inserted;
    }
}
