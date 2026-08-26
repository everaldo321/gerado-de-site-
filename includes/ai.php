<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

// ──────────────────────────────────────────────
// Retorna as configurações do provedor ativo
// ──────────────────────────────────────────────
function getAIConfig(): array {
    $provider = getSetting('ai_provider', '');

    // Compatibilidade com versão antiga (chave salva como claude_api_key)
    if (!$provider) {
        $oldKey = getSetting('claude_api_key', '');
        if ($oldKey) {
            return ['provider' => 'claude', 'api_key' => $oldKey];
        }
        $provider = 'gemini';
    }

    $apiKey = getSetting('ai_api_key_' . $provider, '');

    // Se não achar a nova chave, tenta fallback para a chave antiga do Claude
    if (!$apiKey && $provider === 'claude') {
        $apiKey = getSetting('claude_api_key', '');
    }

    return ['provider' => $provider, 'api_key' => $apiKey];
}

// ──────────────────────────────────────────────
// Função principal — chama o provedor correto
// ──────────────────────────────────────────────
function parseCNPJText(string $text): ?array {
    $config = getAIConfig();

    if (!$config['api_key']) return null;

    $prompt = buildPrompt($text);

    $json = match($config['provider']) {
        'claude'   => callClaude($config['api_key'], $prompt),
        'openai'   => callOpenAI($config['api_key'], $prompt, 'https://api.openai.com/v1/chat/completions', 'gpt-4o-mini'),
        'gemini'   => callGemini($config['api_key'], $prompt),
        'groq'     => callOpenAI($config['api_key'], $prompt, 'https://api.groq.com/openai/v1/chat/completions', 'llama-3.1-8b-instant'),
        'deepseek' => callOpenAI($config['api_key'], $prompt, 'https://api.deepseek.com/v1/chat/completions', 'deepseek-chat'),
        default    => null,
    };

    if (!$json) return null;

    // Remove markdown code blocks se existir
    $json = preg_replace('/```json?\s*|\s*```/', '', $json);
    return json_decode(trim($json), true);
}

// ──────────────────────────────────────────────
// Prompt padrão (igual para todos os provedores)
// ──────────────────────────────────────────────
function buildPrompt(string $text): string {
    return "Analise o texto do Cartão CNPJ abaixo e extraia todas as informações disponíveis.
Retorne APENAS um JSON válido, sem texto extra, sem markdown, sem code blocks.

Texto do cartão:
{$text}

Retorne exatamente neste formato (use null para campos não encontrados):
{
  \"cnpj\": \"número formatado XX.XXX.XXX/XXXX-XX\",
  \"nome_empresarial\": \"razão social completa\",
  \"nome_fantasia\": \"nome de fantasia ou null\",
  \"data_abertura\": \"DD/MM/AAAA ou null\",
  \"situacao\": \"situação cadastral\",
  \"porte\": \"porte da empresa\",
  \"atividade_principal\": \"código e descrição da atividade principal\",
  \"atividades_secundarias\": [\"atividade 1\", \"atividade 2\"],
  \"natureza_juridica\": \"natureza jurídica\",
  \"logradouro\": \"nome da rua/av\",
  \"numero\": \"número\",
  \"complemento\": \"complemento ou null\",
  \"bairro\": \"bairro\",
  \"municipio\": \"cidade\",
  \"uf\": \"UF (sigla)\",
  \"cep\": \"CEP formatado\",
  \"telefone\": \"telefone ou null\",
  \"email\": \"e-mail ou null\"
}";
}

// ──────────────────────────────────────────────
// CLAUDE (Anthropic)
// ──────────────────────────────────────────────
function callClaude(string $apiKey, string $prompt): ?string {
    $payload = [
        'model'      => 'claude-haiku-4-5-20251001',
        'max_tokens' => 1500,
        'messages'   => [['role' => 'user', 'content' => $prompt]],
    ];

    $res = httpPost('https://api.anthropic.com/v1/messages', $payload, [
        'x-api-key: ' . $apiKey,
        'anthropic-version: 2023-06-01',
        'Content-Type: application/json',
    ]);

    if (!$res) return null;
    $data = json_decode($res, true);
    return $data['content'][0]['text'] ?? null;
}

// ──────────────────────────────────────────────
// OPENAI compatível (OpenAI, Groq, DeepSeek)
// ──────────────────────────────────────────────
function callOpenAI(string $apiKey, string $prompt, string $endpoint, string $model): ?string {
    $payload = [
        'model'    => $model,
        'messages' => [['role' => 'user', 'content' => $prompt]],
        'max_tokens' => 1500,
        'temperature' => 0,
    ];

    $res = httpPost($endpoint, $payload, [
        'Authorization: Bearer ' . $apiKey,
        'Content-Type: application/json',
    ]);

    if (!$res) return null;
    $data = json_decode($res, true);
    return $data['choices'][0]['message']['content'] ?? null;
}

// ──────────────────────────────────────────────
// GOOGLE GEMINI
// ──────────────────────────────────────────────
function callGemini(string $apiKey, string $prompt): ?string {
    $model   = 'gemini-1.5-flash';
    $url     = "https://generativelanguage.googleapis.com/v1/models/{$model}:generateContent?key={$apiKey}";

    $payload = [
        'contents' => [
            ['parts' => [['text' => $prompt]]]
        ],
        'generationConfig' => [
            'temperature'     => 0,
            'maxOutputTokens' => 1500,
        ],
    ];

    $res = httpPost($url, $payload, ['Content-Type: application/json']);

    if (!$res) return null;
    $data = json_decode($res, true);
    return $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
}

// ──────────────────────────────────────────────
// Helper HTTP POST com cURL
// ──────────────────────────────────────────────
function httpPost(string $url, array $payload, array $headers, bool $returnError = false): ?string {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_HTTPHEADER     => $headers,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    curl_close($ch);

    if ($curlErr) return $returnError ? "cURL erro: $curlErr" : null;
    if ($httpCode < 200 || $httpCode >= 300) {
        return $returnError ? "HTTP $httpCode: $response" : null;
    }
    return $response ?: null;
}

// ──────────────────────────────────────────────
// Testa se a API key está funcionando
// ──────────────────────────────────────────────
function testAPIKey(string $provider, string $apiKey): array {
    $testPrompt = 'Responda apenas com a palavra: OK';

    try {
        $result = match($provider) {
            'claude'   => callClaude($apiKey, $testPrompt),
            'openai'   => callOpenAI($apiKey, $testPrompt, 'https://api.openai.com/v1/chat/completions', 'gpt-4o-mini'),
            'gemini'   => callGeminiTest($apiKey, $testPrompt),
            'groq'     => callOpenAI($apiKey, $testPrompt, 'https://api.groq.com/openai/v1/chat/completions', 'llama-3.1-8b-instant'),
            'deepseek' => callOpenAI($apiKey, $testPrompt, 'https://api.deepseek.com/v1/chat/completions', 'deepseek-chat'),
            default    => null,
        };

        if ($result !== null) {
            return ['ok' => true, 'message' => 'Conexão estabelecida com sucesso! Resposta: ' . substr(strip_tags($result), 0, 50)];
        }

        return ['ok' => false, 'message' => 'Sem resposta da API. Chave inválida ou sem créditos.'];

    } catch (\Throwable $e) {
        return ['ok' => false, 'message' => 'Erro: ' . $e->getMessage()];
    }
}

// Teste do Gemini com diagnóstico detalhado
function callGeminiTest(string $apiKey, string $prompt): ?string {
    $model = 'gemini-1.5-flash';
    $url   = "https://generativelanguage.googleapis.com/v1/models/{$model}:generateContent?key={$apiKey}";

    $payload = [
        'contents' => [['parts' => [['text' => $prompt]]]],
        'generationConfig' => ['temperature' => 0, 'maxOutputTokens' => 50],
    ];

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    curl_close($ch);

    if ($curlErr) throw new \RuntimeException("cURL: $curlErr");

    $data = json_decode($response, true);

    if ($httpCode !== 200) {
        $errMsg = $data['error']['message'] ?? $response;
        throw new \RuntimeException("HTTP $httpCode — $errMsg");
    }

    return $data['candidates'][0]['content']['parts'][0]['text'] ?? 'ok';
}

