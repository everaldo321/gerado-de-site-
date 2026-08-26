<?php
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/ai.php';
require_once dirname(__DIR__) . '/includes/functions.php';

requireLogin();
header('Content-Type: application/json; charset=utf-8');

$input    = json_decode(file_get_contents('php://input'), true);
$cnpjText = trim($input['cnpj_text'] ?? '');
$subdomain= trim($input['subdomain'] ?? '');

if (strlen($cnpjText) < 50) {
    jsonResponse(['ok'=>false,'error'=>'Texto do CNPJ muito curto. Cole o conteúdo completo.'], 400);
}

// 1. Extrai dados do CNPJ via IA
$info = parseCNPJText($cnpjText);
if (!$info) {
    jsonResponse(['ok'=>false,'error'=>'Falha ao processar o CNPJ. Verifique a chave de API nas configurações.'], 500);
}

$db   = getDB();
$user = sessionUser();

// 2. Slug / subdomínio
if ($subdomain) {
    $slug = slugify($subdomain);
    $stmt = $db->prepare("SELECT id FROM sites WHERE slug = ?");
    $stmt->execute([$slug]);
    if ($stmt->fetch()) jsonResponse(['ok'=>false,'error'=>'Esse subdomínio já está em uso. Escolha outro.'], 409);
} else {
    $name = $info['nome_fantasia'] ?: $info['nome_empresarial'] ?: 'empresa';
    $slug = uniqueSlug($name);
}

// 3. Gera conteúdo de marketing via IA (sobre, visão, valores, serviços...)
$conteudo = gerarConteudoSite($info) ?? [];

// 4. Insere no banco
$stmt = $db->prepare("
    INSERT INTO sites
        (user_id, slug, cnpj, nome_empresarial, nome_fantasia, data_abertura, situacao, porte,
         atividade_principal, atividades_secundarias, natureza_juridica,
         logradouro, numero, complemento, bairro, municipio, uf, cep, telefone, email_empresa,
         sobre_empresa, visao, missao, valores, diferenciais, servicos, beneficios)
    VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
");
$stmt->execute([
    $user['id'],
    $slug,
    $info['cnpj']                ?? '',
    $info['nome_empresarial']    ?? '',
    $info['nome_fantasia']       ?? '',
    $info['data_abertura']       ?? '',
    $info['situacao']            ?? '',
    $info['porte']               ?? '',
    $info['atividade_principal'] ?? '',
    json_encode($info['atividades_secundarias'] ?? [], JSON_UNESCAPED_UNICODE),
    $info['natureza_juridica']   ?? '',
    $info['logradouro']          ?? '',
    $info['numero']              ?? '',
    $info['complemento']         ?? '',
    $info['bairro']              ?? '',
    $info['municipio']           ?? '',
    $info['uf']                  ?? '',
    $info['cep']                 ?? '',
    $info['telefone']            ?? '',
    $info['email']               ?? '',
    $conteudo['sobre']           ?? '',
    $conteudo['visao']           ?? '',
    $conteudo['missao']          ?? '',
    json_encode($conteudo['valores']      ?? [], JSON_UNESCAPED_UNICODE),
    json_encode($conteudo['diferenciais'] ?? [], JSON_UNESCAPED_UNICODE),
    json_encode($conteudo['servicos']     ?? [], JSON_UNESCAPED_UNICODE),
    json_encode($conteudo['beneficios']   ?? [], JSON_UNESCAPED_UNICODE),
]);
$siteId = $db->lastInsertId();

// 5. Busca registro completo para o template
$stmt = $db->prepare("SELECT * FROM sites WHERE id = ?");
$stmt->execute([$siteId]);
$data = $stmt->fetch();

// 6. Gera HTML e salva
$html = generateSiteHTML($data);
$db->prepare("UPDATE sites SET html_content = ? WHERE id = ?")->execute([$html, $siteId]);
saveSiteFiles($slug, $html);

// 7. URL correta — subdomínio se disponível, senão pasta
$siteUrl = getSiteUrl($slug);

jsonResponse([
    'ok'      => true,
    'site_id' => $siteId,
    'url'     => $siteUrl,
    'info'    => [
        'nome_empresarial' => $data['nome_empresarial'],
        'nome_fantasia'    => $data['nome_fantasia'],
        'cnpj'             => $data['cnpj'],
        'telefone'         => $data['telefone'],
        'email_empresa'    => $data['email_empresa'],
        'logradouro'       => $data['logradouro'],
        'numero'           => $data['numero'],
        'municipio'        => $data['municipio'],
        'uf'               => $data['uf'],
    ],
]);

