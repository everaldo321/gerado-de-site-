<?php
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/functions.php';

requireAdmin();
header('Content-Type: application/json; charset=utf-8');

$input  = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';
$id     = (int)($input['id'] ?? 0);
$db     = getDB();

switch ($action) {
    case 'verificar':
        $db->prepare("UPDATE domains SET verified=1 WHERE id=?")->execute([$id]);
        jsonResponse(['ok'=>true]);
    case 'deletar':
        $db->prepare("DELETE FROM domains WHERE id=?")->execute([$id]);
        jsonResponse(['ok'=>true]);
    default:
        jsonResponse(['ok'=>false,'error'=>'Ação inválida.'],400);
}
