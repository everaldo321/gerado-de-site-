<?php
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/ai.php';
requireAdmin();

$db       = getDB();
$saved    = false;
$provider = getSetting('ai_provider', 'gemini');

$providers = [
    'gemini'   => ['name' => 'Google Gemini',   'free' => true,  'label' => 'Gratuito',      'url' => 'https://aistudio.google.com/app/apikey',         'model' => 'gemini-2.0-flash'],
    'groq'     => ['name' => 'Groq (Llama 3)',  'free' => true,  'label' => 'Gratuito',      'url' => 'https://console.groq.com/keys',                  'model' => 'llama-3.1-8b-instant'],
    'deepseek' => ['name' => 'DeepSeek',        'free' => false, 'label' => 'Muito barato',  'url' => 'https://platform.deepseek.com/api_keys',         'model' => 'deepseek-chat'],
    'openai'   => ['name' => 'OpenAI (ChatGPT)','free' => false, 'label' => 'Pago',          'url' => 'https://platform.openai.com/api-keys',           'model' => 'gpt-4o-mini'],
    'claude'   => ['name' => 'Claude (Anthropic)','free' => false,'label' => 'Pago',         'url' => 'https://console.anthropic.com/settings/api-keys', 'model' => 'claude-haiku'],
];

// Salvar configurações
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save'])) {
    $newProvider = $_POST['ai_provider'] ?? 'gemini';
    $newKey      = trim($_POST['api_key'] ?? '');
    saveSetting('ai_provider', $newProvider);
    if ($newKey) saveSetting('ai_api_key_' . $newProvider, $newKey);
    saveSetting('sistema_nome', trim($_POST['sistema_nome'] ?? 'Meta Shield'));
    $saved    = true;
    $provider = $newProvider;
}

// Chaves salvas por provedor
$keysStatus = [];
foreach ($providers as $key => $info) {
    $k = getSetting('ai_api_key_' . $key, '');
    $keysStatus[$key] = !empty($k);
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Configurações — Admin</title>
<link rel="stylesheet" href="../assets/css/style.css">
<style>
.provider-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(200px,1fr)); gap:12px; margin-bottom:20px; }
.provider-card {
  background:var(--surface2);
  border:2px solid var(--border);
  border-radius:10px;
  padding:16px;
  cursor:pointer;
  transition:all .15s;
  position:relative;
}
.provider-card:hover { border-color:#555; }
.provider-card.selected { border-color:var(--accent); background:rgba(245,158,11,.08); }
.provider-card input[type=radio] { position:absolute; opacity:0; }
.prov-name { font-size:14px; font-weight:700; margin-bottom:4px; }
.prov-model { font-size:11px; color:var(--muted); margin-bottom:8px; }
.prov-key-status { font-size:11px; }
.test-result { font-size:13px; padding:8px 14px; border-radius:8px; margin-top:10px; display:none; }
</style>
</head>
<body>

<header class="topbar">
  <div class="topbar-brand">
    <div class="logo-icon">M</div>
    <div><h1>Meta Shield</h1><span>Configurações</span></div>
  </div>
  <div class="topbar-actions">
    <a href="../logout.php" class="btn btn-ghost btn-sm">↪ Sair</a>
  </div>
</header>

<div class="admin-layout">
  <nav class="sidebar">
    <div class="sidebar-label">Painel</div>
    <a href="index.php">📊 Dashboard</a>
    <div class="sidebar-label">Gestão</div>
    <a href="usuarios.php">👥 Usuários</a>
    <a href="dominios.php">🔗 Domínios</a>
    <div class="sidebar-label">Sistema</div>
    <a href="configuracoes.php" class="active">⚙ Configurações</a>
    <a href="../dashboard.php">🌐 Ver Sites</a>
  </nav>

  <div class="content-area">
    <?php if ($saved): ?>
      <div class="alert alert-success" style="margin-bottom:20px;">✅ Configurações salvas!</div>
    <?php endif; ?>

    <div class="page-header">
      <div><h2>Configurações do Sistema</h2><p>Escolha o provedor de IA e configure as chaves</p></div>
    </div>

    <form method="POST" style="max-width:680px;" id="configForm">

      <!-- PROVEDOR DE IA -->
      <div class="section-block" style="margin-bottom:16px;">
        <div class="section-head">
          <div class="form-card-title" style="margin-bottom:2px;">
            <div class="icon">🤖</div>
            <h3>Provedor de IA</h3>
          </div>
          <div style="font-size:12px;color:var(--muted);padding-left:42px;">
            Escolha qual IA vai ler e extrair os dados do Cartão CNPJ
          </div>
        </div>
        <div class="section-body">

          <div class="provider-grid">
            <?php foreach ($providers as $key => $info): ?>
            <label class="provider-card <?= $provider === $key ? 'selected' : '' ?>"
                   onclick="selectProvider('<?= $key ?>')">
              <input type="radio" name="ai_provider" value="<?= $key ?>"
                     <?= $provider === $key ? 'checked' : '' ?>>
              <div class="prov-name"><?= $info['name'] ?></div>
              <div class="prov-model">Modelo: <?= $info['model'] ?></div>
              <div style="display:flex;gap:6px;align-items:center;flex-wrap:wrap;">
                <span class="badge <?= $info['free'] ? 'badge-green' : 'badge-yellow' ?>">
                  <?= $info['label'] ?>
                </span>
                <?php if ($keysStatus[$key]): ?>
                  <span class="badge badge-green">✓ Chave salva</span>
                <?php endif; ?>
              </div>
            </label>
            <?php endforeach; ?>
          </div>

          <!-- CHAVE DA API -->
          <div class="form-group">
            <label>Chave de API do provedor selecionado</label>
            <div style="display:flex;gap:8px;">
              <input type="password" name="api_key" id="apiKeyInput"
                     placeholder="Cole sua chave aqui..."
                     style="flex:1;">
              <button type="button" class="btn btn-ghost" onclick="testarAPI()" id="btnTestar">
                ⚡ Testar
              </button>
            </div>
            <div id="testResult" class="test-result"></div>
            <div class="hint" id="apiHint">
              <?php $cur = $providers[$provider]; ?>
              Obtenha sua chave gratuita em:
              <a href="<?= $cur['url'] ?>" target="_blank" style="color:var(--accent);"><?= $cur['url'] ?></a>
            </div>
          </div>

          <!-- CHAVES SALVAS -->
          <div style="background:var(--surface2);border:1px solid var(--border);border-radius:8px;padding:14px;margin-top:4px;">
            <div style="font-size:12px;font-weight:600;color:var(--muted);margin-bottom:10px;text-transform:uppercase;letter-spacing:.5px;">
              Chaves configuradas
            </div>
            <?php foreach ($providers as $key => $info): ?>
            <div style="display:flex;align-items:center;justify-content:space-between;padding:6px 0;border-bottom:1px solid var(--border);font-size:13px;">
              <span><?= $info['name'] ?></span>
              <div style="display:flex;align-items:center;gap:8px;">
                <?php if ($keysStatus[$key]): ?>
                  <span class="badge badge-green">✓ Configurada</span>
                  <button type="button" class="btn btn-danger btn-sm"
                          onclick="removerChave('<?= $key ?>')">Remover</button>
                <?php else: ?>
                  <span class="badge badge-gray">Não configurada</span>
                <?php endif; ?>
              </div>
            </div>
            <?php endforeach; ?>
          </div>

        </div>
      </div>

      <!-- SISTEMA -->
      <div class="section-block" style="margin-bottom:16px;">
        <div class="section-head">
          <div class="form-card-title" style="margin-bottom:2px;">
            <div class="icon">⚙</div>
            <h3>Configurações Gerais</h3>
          </div>
        </div>
        <div class="section-body">
          <div class="form-group">
            <label>Nome do Sistema</label>
            <input type="text" name="sistema_nome"
                   value="<?= htmlspecialchars(getSetting('sistema_nome','Meta Shield')) ?>">
          </div>
          <div class="form-group">
            <label>URL Base</label>
            <input type="text" value="<?= htmlspecialchars(BASE_URL) ?>" disabled style="opacity:.5;">
            <div class="hint">Edite no arquivo <code>config.php</code></div>
          </div>
        </div>
      </div>

      <!-- INFO DO SISTEMA -->
      <div class="section-block" style="margin-bottom:16px;">
        <div class="section-head">
          <div class="form-card-title" style="margin-bottom:2px;">
            <div class="icon">🖥</div>
            <h3>Status do Servidor</h3>
          </div>
        </div>
        <div class="section-body" style="display:flex;flex-direction:column;gap:8px;font-size:13px;">
          <div class="result-row"><strong style="color:var(--muted);min-width:140px;">PHP</strong> <span><?= phpversion() ?></span></div>
          <div class="result-row"><strong style="color:var(--muted);min-width:140px;">cURL</strong>
            <span><?= function_exists('curl_init') ? '<span class="badge badge-green">OK</span>' : '<span class="badge badge-red">Indisponível</span>' ?></span>
          </div>
          <div class="result-row"><strong style="color:var(--muted);min-width:140px;">Pasta sites/</strong>
            <span><?= is_writable(dirname(__DIR__).'/sites') ? '<span class="badge badge-green">Gravável ✓</span>' : '<span class="badge badge-red">Sem permissão — chmod 755</span>' ?></span>
          </div>
          <div class="result-row"><strong style="color:var(--muted);min-width:140px;">Provedor ativo</strong>
            <span class="badge badge-yellow"><?= $providers[$provider]['name'] ?></span>
          </div>
        </div>
      </div>

      <button type="submit" name="save" class="btn btn-primary">💾 Salvar Configurações</button>
    </form>

  </div>
</div>

<script>
const providerInfo = <?= json_encode($providers) ?>;
let currentProvider = '<?= $provider ?>';

function selectProvider(key) {
  currentProvider = key;
  document.querySelectorAll('.provider-card').forEach(c => c.classList.remove('selected'));
  event.currentTarget.classList.add('selected');
  document.querySelector(`input[value="${key}"]`).checked = true;

  // Atualiza dica da chave
  const info = providerInfo[key];
  document.getElementById('apiHint').innerHTML =
    `Obtenha sua chave ${info.free ? 'gratuita ' : ''}em: <a href="${info.url}" target="_blank" style="color:var(--accent);">${info.url}</a>`;

  document.getElementById('apiKeyInput').placeholder = `Cole sua chave ${info.name} aqui...`;
  document.getElementById('testResult').style.display = 'none';
}

async function testarAPI() {
  const key  = document.getElementById('apiKeyInput').value.trim();
  const res  = document.getElementById('testResult');
  const btn  = document.getElementById('btnTestar');

  if (!key) {
    res.style.display = 'block';
    res.className = 'test-result alert alert-error';
    res.textContent = 'Cole a chave de API antes de testar.';
    return;
  }

  btn.disabled = true;
  btn.textContent = 'Testando...';
  res.style.display = 'block';
  res.className = 'test-result alert alert-warn';
  res.textContent = 'Conectando...';

  try {
    const r = await fetch('../api/testar-api.php', {
      method: 'POST',
      headers: {'Content-Type':'application/json'},
      body: JSON.stringify({provider: currentProvider, api_key: key})
    });
    const d = await r.json();
    res.className = 'test-result alert ' + (d.ok ? 'alert-success' : 'alert-error');
    res.textContent = d.ok ? '✅ ' + d.message : '❌ ' + d.message;
  } catch(e) {
    res.className = 'test-result alert alert-error';
    res.textContent = 'Erro de conexão.';
  }

  btn.disabled = false;
  btn.textContent = '⚡ Testar';
}

async function removerChave(provider) {
  if (!confirm(`Remover a chave da API de ${providerInfo[provider].name}?`)) return;
  const r = await fetch('../api/testar-api.php', {
    method: 'POST',
    headers: {'Content-Type':'application/json'},
    body: JSON.stringify({action: 'remover', provider})
  });
  const d = await r.json();
  if (d.ok) location.reload();
}
</script>
</body>
</html>

