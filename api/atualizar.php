<?php
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/functions.php';

requireLogin();
header('Content-Type: application/json; charset=utf-8');

$input  = json_decode(file_get_contents('php://input'), true);
$id     = (int)($input['id'] ?? 0);
$action = $input['action'] ?? '';
$user   = sessionUser();
$db     = getDB();

$stmt = $db->prepare("SELECT * FROM sites WHERE id = ?");
$stmt->execute([$id]);
$site = $stmt->fetch();

if (!$site || ($user['role'] !== 'admin' && $site['user_id'] != $user['id'])) {
    jsonResponse(['ok'=>false,'error'=>'Acesso negado.'], 403);
}

switch ($action) {

    case 'slug':
        $newSlug = slugify($input['slug'] ?? '');
        if (!$newSlug) jsonResponse(['ok'=>false,'error'=>'Slug inválido.'],400);

        // Verifica duplicata
        $stmt2 = $db->prepare("SELECT id FROM sites WHERE slug = ? AND id != ?");
        $stmt2->execute([$newSlug, $id]);
        if ($stmt2->fetch()) jsonResponse(['ok'=>false,'error'=>'Subdomínio já em uso.'],409);

        // Move arquivos
        $oldDir = dirname(__DIR__) . '/sites/' . $site['slug'];
        $newDir = dirname(__DIR__) . '/sites/' . $newSlug;
        if (is_dir($oldDir) && !is_dir($newDir)) rename($oldDir, $newDir);

        $db->prepare("UPDATE sites SET slug = ?, updated_at = NOW() WHERE id = ?")->execute([$newSlug, $id]);
        jsonResponse(['ok'=>true]);

    case 'info':
        $db->prepare("
            UPDATE sites SET
                telefone     = ?,
                email_empresa= ?,
                logradouro   = ?,
                numero       = ?,
                cep          = ?,
                municipio    = ?,
                uf           = ?,
                updated_at   = NOW()
            WHERE id = ?
        ")->execute([
            $input['telefone']      ?? $site['telefone'],
            $input['email_empresa'] ?? $site['email_empresa'],
            $input['logradouro']    ?? $site['logradouro'],
            $input['numero']        ?? $site['numero'],
            $input['cep']           ?? $site['cep'],
            $input['municipio']     ?? $site['municipio'],
            $input['uf']            ?? $site['uf'],
            $id
        ]);
        // Regenera HTML
        $stmt2 = $db->prepare("SELECT * FROM sites WHERE id = ?");
        $stmt2->execute([$id]);
        $updated = $stmt2->fetch();
        $html = generateSiteHTML($updated);
        $db->prepare("UPDATE sites SET html_content = ? WHERE id = ?")->execute([$html, $id]);
        saveSiteFiles($updated['slug'], $html);
        jsonResponse(['ok'=>true]);

    case 'custom_domain':
        $domain = trim($input['custom_domain'] ?? '');
        $domain = preg_replace('#^https?://#', '', $domain);
        $db->prepare("UPDATE sites SET custom_domain = ?, updated_at = NOW() WHERE id = ?")->execute([$domain ?: null, $id]);
        jsonResponse(['ok'=>true]);

    case 'meta_tag':
        $metaTag = trim($input['meta_tag'] ?? '');
        // Injeta no HTML existente
        $stmt2 = $db->prepare("SELECT html_content FROM sites WHERE id = ?");
        $stmt2->execute([$id]);
        $row = $stmt2->fetch();
        $html = $row['html_content'] ?? '';
        if ($metaTag && strpos($html, '<head>') !== false) {
            $html = str_replace('<head>', "<head>\n  {$metaTag}", $html);
        }
        $db->prepare("UPDATE sites SET html_content = ?, updated_at = NOW() WHERE id = ?")->execute([$html, $id]);
        saveSiteFiles($site['slug'], $html);
        jsonResponse(['ok'=>true]);

    case 'html':
        $html = $input['html'] ?? '';
        $db->prepare("UPDATE sites SET html_content = ?, updated_at = NOW() WHERE id = ?")->execute([$html, $id]);
        saveSiteFiles($site['slug'], $html);
        jsonResponse(['ok'=>true]);

    default:
        jsonResponse(['ok'=>false,'error'=>'Ação desconhecida.'], 400);
}
