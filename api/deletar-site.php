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

$stmt = $db->prepare("SELECT slug, user_id FROM sites WHERE id = ?");
$stmt->execute([$id]);
$site = $stmt->fetch();

if (!$site || ($user['role'] !== 'admin' && $site['user_id'] != $user['id'])) {
    jsonResponse(['ok'=>false,'error'=>'Acesso negado.'], 403);
}

deleteSiteFiles($site['slug']);
$db->prepare("DELETE FROM sites WHERE id = ?")->execute([$id]);

jsonResponse(['ok'=>true]);
