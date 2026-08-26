<?php
/**
 * Router para PHP Built-in Server (Railway)
 * Substitui o .htaccess que só funciona com Apache
 */

$uri  = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$host = strtolower(preg_replace('/^www\./', '', $_SERVER['HTTP_HOST'] ?? ''));

// ── Protege config.php ──
if ($uri === '/config.php') {
    http_response_code(403);
    echo '403 Forbidden';
    exit;
}

// ── Rota /s/slug → index-router.php ──
if (preg_match('#^/s/([a-z0-9-]+)/?$#', $uri, $m)) {
    $_GET['slug'] = $m[1];
    require __DIR__ . '/index-router.php';
    exit;
}

// ── Subdomínio do sistema → index-router.php ──
$baseHost = ltrim(getenv('SUBDOMAIN_SUFFIX') ?: '.gerasite.camillefournier.shop', '.');
if ($baseHost && $host !== $baseHost && str_ends_with($host, '.' . $baseHost)) {
    require __DIR__ . '/index-router.php';
    exit;
}

// ── Domínio próprio (diferente do sistema) → index-router.php ──
$systemHost = parse_url(getenv('BASE_URL') ?: 'https://gerasite.camillefournier.shop', PHP_URL_HOST);
if ($host !== $systemHost && $host !== 'www.' . $systemHost && $host !== 'localhost' && !str_starts_with($host, '127.') && !str_starts_with($host, '0.0.0.0')) {
    require __DIR__ . '/index-router.php';
    exit;
}

// ── Serve arquivos estáticos se existirem ──
$filePath = __DIR__ . $uri;
if ($uri !== '/' && is_file($filePath)) {
    // Arquivos PHP → executa
    if (str_ends_with($uri, '.php')) {
        require $filePath;
        exit;
    }
    // Outros arquivos (css, js, imgs) → retorna false para o built-in server servir
    return false;
}

// ── Diretório raiz → index.php ──
if ($uri === '/' || $uri === '') {
    require __DIR__ . '/index.php';
    exit;
}

// ── 404 ──
http_response_code(404);
echo '404 Not Found';
