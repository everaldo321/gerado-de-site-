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
    }
    return $pdo;
}

