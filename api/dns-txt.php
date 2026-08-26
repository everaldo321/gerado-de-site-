<?php
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/functions.php';

requireLogin();
header('Content-Type: application/json; charset=utf-8');

$input   = json_decode(file_get_contents('php://input'), true);
$siteId  = (int)($input['site_id'] ?? 0);
$dnsTxt  = trim($input['dns_txt'] ?? '');
$user    = sessionUser();
$db      = getDB();

if (!$siteId || !$dnsTxt) {
    jsonResponse(['ok'=>false,'error'=>'Dados inválidos.'], 400);
}

$stmt = $db->prepare("SELECT id, user_id FROM sites WHERE id = ?");
$stmt->execute([$siteId]);
$site = $stmt->fetch();

if (!$site || ($user['role'] !== 'admin' && $site['user_id'] != $user['id'])) {
    jsonResponse(['ok'=>false,'error'=>'Acesso negado.'], 403);
}

// Salva o TXT na tabela domains
$stmt2 = $db->prepare("INSERT INTO domains (site_id, domain, dns_txt) VALUES (?, 'facebook-verification', ?) ON DUPLICATE KEY UPDATE dns_txt = ?");
$stmt2->execute([$siteId, $dnsTxt, $dnsTxt]);

jsonResponse(['ok'=>true, 'message'=>'TXT salvo. Aguarde 2-5 minutos para propagação DNS.']);
