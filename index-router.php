<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';

$db   = getDB();
$site = null;
$host = strtolower(preg_replace('/^www\./', '', $_SERVER['HTTP_HOST'] ?? ''));

// ─────────────────────────────────────────────────────────────
// 1. Slug via caminho: /s/slug  (chamado pelo .htaccess)
// ─────────────────────────────────────────────────────────────
if (isset($_GET['slug']) && $_GET['slug'] !== '') {
    $slug = preg_replace('/[^a-z0-9-]/', '', strtolower(trim($_GET['slug'])));
    if ($slug) {
        $stmt = $db->prepare("SELECT html_content FROM sites WHERE slug = ?");
        $stmt->execute([$slug]);
        $site = $stmt->fetch();
    }
}

// ─────────────────────────────────────────────────────────────
// 2. Subdomínio do sistema: slug.camillefournier.shop
//    (funciona quando wildcard DNS estiver configurado)
// ─────────────────────────────────────────────────────────────
if (!$site) {
    $suffix = ltrim(SUBDOMAIN_SUFFIX, '.');
    if (preg_match('/^([a-z0-9-]+)\.' . preg_quote($suffix, '/') . '$/', $host, $m)) {
        $slug = $m[1];
        $stmt = $db->prepare("SELECT html_content FROM sites WHERE slug = ?");
        $stmt->execute([$slug]);
        $site = $stmt->fetch();
    }
}

// ─────────────────────────────────────────────────────────────
// 3. Domínio próprio do cliente: clientedomain.com.br
// ─────────────────────────────────────────────────────────────
if (!$site) {
    $stmt = $db->prepare("SELECT html_content FROM sites WHERE custom_domain = ?");
    $stmt->execute([$host]);
    $site = $stmt->fetch();
}

// ─────────────────────────────────────────────────────────────
// 4. Serve o site ou 404
// ─────────────────────────────────────────────────────────────
if ($site && $site['html_content']) {
    header('Content-Type: text/html; charset=utf-8');
    header('Cache-Control: public, max-age=300');
    echo $site['html_content'];
    exit;
}

http_response_code(404);
echo '<!DOCTYPE html><html lang="pt-BR"><head><meta charset="UTF-8">
<title>Site não encontrado</title>
<style>
  body{font-family:sans-serif;background:#0d0d0d;color:#fff;display:flex;align-items:center;justify-content:center;height:100vh;text-align:center;margin:0}
  h1{font-size:72px;font-weight:900;color:#f59e0b;margin:0;line-height:1}
  p{color:#6b7280;margin-top:12px;font-size:16px}
  a{color:#f59e0b;text-decoration:none;font-size:14px;margin-top:20px;display:block}
</style></head>
<body>
  <div>
    <h1>404</h1>
    <p>Site não encontrado</p>
    <a href="' . BASE_URL . '">← Voltar ao sistema</a>
  </div>
</body></html>';

