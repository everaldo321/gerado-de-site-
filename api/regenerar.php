<?php
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/functions.php';

requireLogin();
header('Content-Type: application/json; charset=utf-8');

$input = json_decode(file_get_contents('php://input'), true);
$id    = (int)($input['id'] ?? 0);
$user  = sessionUser();
$db    = getDB();

$stmt = $db->prepare("SELECT * FROM sites WHERE id = ?");
$stmt->execute([$id]);
$site = $stmt->fetch();

if (!$site || ($user['role'] !== 'admin' && $site['user_id'] != $user['id'])) {
    jsonResponse(['ok' => false, 'error' => 'Acesso negado.'], 403);
}

// Regenera HTML com template mais recente
$html = generateSiteHTML($site);
$db->prepare("UPDATE sites SET html_content = ?, updated_at = NOW() WHERE id = ?")->execute([$html, $id]);
saveSiteFiles($site['slug'], $html);

jsonResponse(['ok' => true, 'url' => getSiteUrl($site['slug'], $site['custom_domain'] ?: null)]);
