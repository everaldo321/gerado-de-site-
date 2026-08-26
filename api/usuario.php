<?php
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/functions.php';

requireAdmin();
header('Content-Type: application/json; charset=utf-8');

$input  = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';
$db     = getDB();

switch ($action) {

    case 'criar':
        $name  = trim($input['name'] ?? '');
        $email = trim($input['email'] ?? '');
        $pass  = $input['password'] ?? '';
        $role  = ($input['role'] ?? 'user') === 'admin' ? 'admin' : 'user';

        if (!$name || !$email || strlen($pass) < 6) {
            jsonResponse(['ok'=>false,'error'=>'Preencha nome, e-mail e senha (mín. 6 caracteres).'], 400);
        }

        $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) jsonResponse(['ok'=>false,'error'=>'E-mail já cadastrado.'], 409);

        $db->prepare("INSERT INTO users (name, email, password, role) VALUES (?,?,?,?)")
           ->execute([$name, $email, password_hash($pass, PASSWORD_DEFAULT), $role]);

        jsonResponse(['ok'=>true, 'id' => $db->lastInsertId()]);

    case 'deletar':
        $id = (int)($input['id'] ?? 0);
        if (!$id) jsonResponse(['ok'=>false,'error'=>'ID inválido.'], 400);

        // Não pode deletar a si mesmo
        $current = sessionUser();
        if ($id === (int)$current['id']) jsonResponse(['ok'=>false,'error'=>'Você não pode deletar sua própria conta.'], 400);

        $db->prepare("DELETE FROM users WHERE id = ?")->execute([$id]);
        jsonResponse(['ok'=>true]);

    case 'toggle_ativo':
        $id = (int)($input['id'] ?? 0);
        $db->prepare("UPDATE users SET ativo = NOT ativo WHERE id = ?")->execute([$id]);
        jsonResponse(['ok'=>true]);

    case 'trocar_senha':
        $id   = (int)($input['id'] ?? 0);
        $pass = $input['password'] ?? '';
        if (strlen($pass) < 6) jsonResponse(['ok'=>false,'error'=>'Senha mínima de 6 caracteres.'], 400);
        $db->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([password_hash($pass, PASSWORD_DEFAULT), $id]);
        jsonResponse(['ok'=>true]);

    default:
        jsonResponse(['ok'=>false,'error'=>'Ação desconhecida.'], 400);
}
