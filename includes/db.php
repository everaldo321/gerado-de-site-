<?php
require_once dirname(__DIR__) . '/config.php';

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        // Railway fornece MYSQL_URL ou DATABASE_URL no formato:
        // mysql://user:pass@host:port/dbname
        $dbUrl = getenv('MYSQL_URL') ?: getenv('DATABASE_URL') ?: '';

        if ($dbUrl && str_starts_with($dbUrl, 'mysql')) {
            $parts = parse_url($dbUrl);
            $host  = $parts['host'] ?? 'localhost';
            $port  = $parts['port'] ?? 3306;
            $db    = ltrim($parts['path'] ?? '', '/');
            $user  = $parts['user'] ?? '';
            $pass  = $parts['pass'] ?? '';
            $dsn   = "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4";
        } else {
            $dsn  = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $user = DB_USER;
            $pass = DB_PASS;
        }

        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);

        ensureSchema($pdo);
    }
    return $pdo;
}

/**
 * Garante que as tabelas necessárias existem. Se não existirem (primeira
 * execução), cria automaticamente toda a estrutura e um usuário admin
 * padrão, para que a aplicação funcione imediatamente após o deploy.
 */
function ensureSchema(PDO $pdo): void {
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE 'sites'");
        $tableExists = $stmt && $stmt->fetch();
    } catch (Exception $e) {
        $tableExists = false;
    }

    if ($tableExists) {
        return;
    }

    // ── Tabela users ──
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(150) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            role ENUM('admin','user') DEFAULT 'user',
            ativo TINYINT(1) DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    // ── Tabela sites ──
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS sites (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            slug VARCHAR(80) NOT NULL UNIQUE,
            cnpj VARCHAR(20) DEFAULT '',
            nome_empresarial VARCHAR(255) DEFAULT '',
            nome_fantasia VARCHAR(255) DEFAULT '',
            data_abertura VARCHAR(20) DEFAULT '',
            situacao VARCHAR(50) DEFAULT '',
            porte VARCHAR(50) DEFAULT '',
            atividade_principal TEXT,
            atividades_secundarias TEXT,
            natureza_juridica VARCHAR(255) DEFAULT '',
            logradouro VARCHAR(255) DEFAULT '',
            numero VARCHAR(20) DEFAULT '',
            complemento VARCHAR(100) DEFAULT '',
            bairro VARCHAR(100) DEFAULT '',
            municipio VARCHAR(100) DEFAULT '',
            uf VARCHAR(2) DEFAULT '',
            cep VARCHAR(15) DEFAULT '',
            telefone VARCHAR(30) DEFAULT '',
            email_empresa VARCHAR(150) DEFAULT '',
            sobre_empresa TEXT,
            visao TEXT,
            missao TEXT,
            valores TEXT,
            diferenciais TEXT,
            servicos TEXT,
            beneficios TEXT,
            html_content LONGTEXT,
            custom_domain VARCHAR(255) DEFAULT NULL,
            meta_tag_fb TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    // ── Tabela settings ──
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            key_name VARCHAR(100) NOT NULL UNIQUE,
            value TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    // ── Tabela domains (verificações DNS/Facebook) ──
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS domains (
            id INT AUTO_INCREMENT PRIMARY KEY,
            site_id INT NOT NULL,
            dns_txt VARCHAR(255) DEFAULT '',
            verified TINYINT(1) DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (site_id) REFERENCES sites(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    // ── Admin padrão ──
    $adminEmail = 'admin@metashield.com';
    $adminPass  = password_hash('admin123', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$adminEmail]);
    if (!$stmt->fetch()) {
        $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'admin')")
            ->execute(['Admin', $adminEmail, $adminPass]);
    }
}

