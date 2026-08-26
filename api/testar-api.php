<?php
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/ai.php';

requireAdmin();
header('Content-Type: application/json; charset=utf-8');

$input    = json_decode(file_get_contents('php://input'), true);
$action   = $input['action'] ?? 'testar';
$provider = $input['provider'] ?? '';

$validProviders = ['claude','openai','gemini','groq','deepseek'];
if (!in_array($provider, $validProviders)) {
    jsonResponse(['ok'=>false,'error'=>'Provedor inválido.'], 400);
}

// Remover chave
if ($action === 'remover') {
    saveSetting('ai_api_key_' . $provider, '');
    jsonResponse(['ok'=>true]);
}

// Testar chave
$apiKey = trim($input['api_key'] ?? '');
if (!$apiKey) {
    jsonResponse(['ok'=>false,'message'=>'Chave vazia.'], 400);
}

$result = testAPIKey($provider, $apiKey);
jsonResponse($result);

