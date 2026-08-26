<?php
/**
 * Script de instalação — cria tabelas e admin
 * Acesse: https://seudominio/install.php?key=instalar123
 * DELETE ESTE ARQUIVO APÓS INSTALAR!
 */

// Segurança: exige chave na URL
if (($_GET['key'] ?? '') !== 'instalar123') {
    http_response_code(403);
    echo '403 — Acesse com ?key=instalar123';
    exit;
}

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';

ini_set('display_errors', 1);
error_reporting(E_ALL);

$db = getDB();
$msgs = [];

try {
    // ── Tabela users ──
    $db->exec("
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
    $msgs[] = '✅ Tabela users criada';

    // ── Tabela sites ──
    $db->exec("
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
    $msgs[] = '✅ Tabela sites criada';

    // ── Tabela settings ──
    $db->exec("
        CREATE TABLE IF NOT EXISTS settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            key_name VARCHAR(100) NOT NULL UNIQUE,
            value TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    $msgs[] = '✅ Tabela settings criada';

    // ── Tabela domains (verificações DNS/Facebook) ──
    $db->exec("
        CREATE TABLE IF NOT EXISTS domains (
            id INT AUTO_INCREMENT PRIMARY KEY,
            site_id INT NOT NULL,
            dns_txt VARCHAR(255) DEFAULT '',
            verified TINYINT(1) DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (site_id) REFERENCES sites(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    $msgs[] = '✅ Tabela domains criada';

    // ── Admin padrão ──
    $adminEmail = 'admin@metashield.com';
    $adminPass  = password_hash('admin123', PASSWORD_DEFAULT);
    $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$adminEmail]);
    if (!$stmt->fetch()) {
        $db->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'admin')")
           ->execute(['Admin', $adminEmail, $adminPass]);
        $msgs[] = '✅ Usuário admin criado: admin@metashield.com / admin123';
    } else {
        $msgs[] = 'ℹ️ Usuário admin já existe';
    }

    $msgs[] = '';
    $msgs[] = '🎉 INSTALAÇÃO CONCLUÍDA!';
    $msgs[] = '⚠️ DELETE ESTE ARQUIVO (install.php) APÓS INSTALAR!';

} catch (Exception $e) {
    $msgs[] = '❌ ERRO: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Instalação — Meta Shield</title>
<style>
body { font-family: sans-serif; background: #0d0d0d; color: #f3f4f6; padding: 40px; }
.box { max-width: 600px; margin: 0 auto; background: #1a1a1a; border: 1px solid #2d2d2d; border-radius: 12px; padding: 30px; }
h1 { color: #f59e0b; font-size: 20px; margin-bottom: 20px; }
.msg { padding: 8px 0; font-size: 14px; border-bottom: 1px solid #2d2d2d; }
.login-info { margin-top: 20px; background: #222; padding: 16px; border-radius: 8px; font-size: 14px; }
.login-info strong { color: #f59e0b; }
</style>
</head>
<body>
<div class="box">
  <h1>🔧 Meta Shield — Instalação</h1>
  <?php foreach ($msgs as $m): ?>
    <div class="msg"><?= $m ?></div>
  <?php endforeach; ?>
  <div class="login-info">
    <p><strong>Login:</strong> admin@metashield.com</p>
    <p><strong>Senha:</strong> admin123</p>
    <p style="margin-top:12px;"><a href="<?= BASE_URL ?>" style="color:#f59e0b;">→ Ir para o login</a></p>
  </div>
</div>
</body>
</html>
