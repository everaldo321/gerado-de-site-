<?php
require_once 'config.php';

$secret = $_GET['key'] ?? '';
if ($secret !== 'migrar123') die('<h2>Use: migrar.php?key=migrar123</h2>');

try {
    $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8mb4", DB_USER, DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

    $colunas = [
        "ALTER TABLE sites ADD COLUMN IF NOT EXISTS sobre_empresa TEXT AFTER email_empresa",
        "ALTER TABLE sites ADD COLUMN IF NOT EXISTS visao TEXT AFTER sobre_empresa",
        "ALTER TABLE sites ADD COLUMN IF NOT EXISTS missao TEXT AFTER visao",
        "ALTER TABLE sites ADD COLUMN IF NOT EXISTS valores TEXT AFTER missao",
        "ALTER TABLE sites ADD COLUMN IF NOT EXISTS diferenciais TEXT AFTER valores",
        "ALTER TABLE sites ADD COLUMN IF NOT EXISTS servicos TEXT AFTER diferenciais",
        "ALTER TABLE sites ADD COLUMN IF NOT EXISTS beneficios TEXT AFTER servicos",
    ];

    echo '<pre style="background:#111;color:#0f0;padding:20px;font-family:monospace;">';
    foreach ($colunas as $sql) {
        try { $pdo->exec($sql); echo "✓ OK: " . substr($sql, 0, 60) . "...\n"; }
        catch (Exception $e) { echo "⚠ " . $e->getMessage() . "\n"; }
    }
    echo "\n✅ Migração concluída! Delete este arquivo.\n";
    echo '<a href="index.php" style="color:#f59e0b;">→ Ir para o sistema</a>';
    echo '</pre>';

} catch (Exception $e) {
    echo '<pre style="color:red;">ERRO: ' . $e->getMessage() . '</pre>';
}
