<?php
require_once __DIR__ . '/db.php';

function slugify(string $text): string {
    $map = [
        'á'=>'a','à'=>'a','ã'=>'a','â'=>'a','é'=>'e','ê'=>'e',
        'í'=>'i','ó'=>'o','ô'=>'o','õ'=>'o','ú'=>'u','ü'=>'u',
        'ç'=>'c','Á'=>'a','À'=>'a','Ã'=>'a','Â'=>'a','É'=>'e',
        'Ê'=>'e','Í'=>'i','Ó'=>'o','Ô'=>'o','Õ'=>'o','Ú'=>'u','Ç'=>'c',
    ];
    $text = strtr(mb_strtolower($text, 'UTF-8'), $map);
    $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
    $text = preg_replace('/[\s-]+/', '-', trim($text));
    return substr($text, 0, 60);
}

function uniqueSlug(string $name): string {
    $db   = getDB();
    $base = slugify($name);
    $slug = $base;
    $i    = 1;
    while (true) {
        $stmt = $db->prepare("SELECT id FROM sites WHERE slug = ?");
        $stmt->execute([$slug]);
        if (!$stmt->fetch()) break;
        $slug = $base . '-' . $i++;
    }
    return $slug;
}

function getSiteUrl(string $slug, ?string $customDomain = null): string {
    if ($customDomain) return 'https://' . $customDomain;
    return 'https://' . $slug . SUBDOMAIN_SUFFIX;
}

function saveSiteFiles(string $slug, string $html): bool {
    $dir = dirname(__DIR__) . '/sites/' . $slug;
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    return file_put_contents($dir . '/index.html', $html) !== false;
}

function deleteSiteFiles(string $slug): void {
    $dir = dirname(__DIR__) . '/sites/' . $slug;
    if (is_dir($dir)) {
        foreach (glob("$dir/*") as $f) unlink($f);
        rmdir($dir);
    }
}

function generateSiteHTML(array $data): string {
    ob_start();
    include dirname(__DIR__) . '/templates/empresa.php';
    return ob_get_clean();
}

function jsonResponse(array $data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function getSetting(string $key, string $default = ''): string {
    $db = getDB();
    $stmt = $db->prepare("SELECT value FROM settings WHERE key_name = ?");
    $stmt->execute([$key]);
    $row = $stmt->fetch();
    return $row ? $row['value'] : $default;
}

function saveSetting(string $key, string $value): void {
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO settings (key_name, value) VALUES (?, ?) ON DUPLICATE KEY UPDATE value = ?");
    $stmt->execute([$key, $value, $value]);
}

