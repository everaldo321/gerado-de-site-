<?php
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
requireLogin();
header('Content-Type: application/json; charset=utf-8');

$domain = trim($_GET['domain'] ?? '');
if (!$domain) {
    echo json_encode(['ok' => false, 'error' => 'Domínio não informado.']);
    exit;
}

// Remove protocolo e barras
$domain = preg_replace('#^https?://#', '', $domain);
$domain = rtrim($domain, '/');
$domain = strtolower($domain);

if (!preg_match('/^[a-z0-9.-]+\.[a-z]{2,}$/', $domain)) {
    echo json_encode(['ok' => false, 'error' => 'Domínio inválido.']);
    exit;
}

$serverIp  = SERVER_IP;

// Resolve o domínio
$resolved  = gethostbyname($domain);
$resolvedW = gethostbyname('www.' . $domain);

$connected    = ($resolved === $serverIp);
$connectedWww = ($resolvedW === $serverIp);

if ($connected || $connectedWww) {
    $msg = "✅ DNS configurado corretamente! O domínio aponta para o servidor ({$serverIp}).";
    if ($connected && $connectedWww) {
        $msg .= " Tanto o @ quanto o www estão configurados.";
    } elseif ($connected) {
        $msg .= " O @ (raiz) está correto. Configure também o www para garantir o acesso via www.";
    } else {
        $msg .= " O www está correto, mas o @ (raiz) ainda não está configurado.";
    }
} else {
    $currentIp = ($resolved !== $domain) ? $resolved : 'não resolvido';
    $msg = "⏳ DNS ainda não propagado. IP atual: {$currentIp}. IP esperado: {$serverIp}. Aguarde até 24h após configurar.";
}

echo json_encode([
    'ok'           => true,
    'domain'       => $domain,
    'resolved_ip'  => $resolved !== $domain ? $resolved : null,
    'expected_ip'  => $serverIp,
    'connected'    => $connected || $connectedWww,
    'connected_root'=> $connected,
    'connected_www' => $connectedWww,
    'message'      => $msg,
]);
