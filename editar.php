<?php
require_once 'config.php';
require_once 'includes/auth.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';
requireLogin();

$user = sessionUser();
$id   = (int)($_GET['id'] ?? 0);
$db   = getDB();

$stmt = $db->prepare("SELECT * FROM sites WHERE id = ?");
$stmt->execute([$id]);
$site = $stmt->fetch();

if (!$site || ($user['role'] !== 'admin' && $site['user_id'] != $user['id'])) {
    header('Location: ' . BASE_URL . '/dashboard.php');
    exit;
}

$siteUrl = getSiteUrl($site['slug'], $site['custom_domain'] ?: null);

// Atividades secundárias (JSON array)
$atividadesSecArray = [];
if ($site['atividades_secundarias']) {
    $decoded = json_decode($site['atividades_secundarias'], true);
    $atividadesSecArray = is_array($decoded) ? $decoded : [$site['atividades_secundarias']];
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Editar Site — Meta Shield</title>
<link rel="stylesheet" href="assets/css/style.css">
<style>
body { background: var(--bg); }
</style>
</head>
<body>

<header class="topbar">
  <div class="topbar-brand">
    <div class="logo-icon">M</div>
    <div>
      <h1>Meta Shield</h1>
      <span>Editor do Site</span>
    </div>
  </div>
  <div class="topbar-actions">
    <span style="font-size:12px;color:var(--muted);">
      <a href="<?= htmlspecialchars($siteUrl) ?>" target="_blank" style="color:var(--accent);"><?= htmlspecialchars($siteUrl) ?></a>
    </span>
    <button onclick="navigator.clipboard.writeText('<?= htmlspecialchars($siteUrl) ?>')" class="btn btn-ghost btn-sm">📋 Copiar</button>
    <a href="dashboard.php" class="btn btn-ghost btn-sm">← Voltar</a>
    <a href="logout.php" class="btn btn-ghost btn-sm">↪</a>
  </div>
</header>

<div style="max-width:680px;margin:0 auto;padding:30px 20px;">
  <div id="alertGlobal"></div>
  <div class="sections-stack">

    <!-- 1. DOMÍNIO DO SITE -->
    <div class="section-block">
      <div class="section-head">
        <div class="form-card-title" style="margin-bottom:2px;">
          <div class="icon" style="font-size:14px;">🌐</div>
          <h3>Domínio do Site</h3>
        </div>
        <div style="font-size:12px;color:var(--muted);padding-left:42px;">
          Edite o subdomínio <?= htmlspecialchars(SUBDOMAIN_SUFFIX) ?> e republique
        </div>
      </div>
      <div class="section-body">
        <div class="domain-row">
          <input type="text" id="slugInput" value="<?= htmlspecialchars($site['slug']) ?>"
                 placeholder="meu-site" pattern="[a-z0-9\-]+">
          <div class="domain-suffix"><?= htmlspecialchars(SUBDOMAIN_SUFFIX) ?></div>
        </div>
        <div class="hint" style="margin-bottom:14px;">Apenas letras minúsculas, números e hífen. O site ficará em <strong><?= htmlspecialchars($site['slug']) ?><?= htmlspecialchars(SUBDOMAIN_SUFFIX) ?></strong>.</div>
        <button class="btn btn-primary btn-full" onclick="salvarDominio()">💾 Salvar Domínio e Republica</button>
      </div>
    </div>

    <!-- 2. INFORMAÇÕES -->
    <div class="section-block">
      <div class="section-head">
        <div class="form-card-title" style="margin-bottom:2px;">
          <div class="icon" style="font-size:14px;">🌐</div>
          <h3>Informações do Site</h3>
        </div>
        <div style="font-size:12px;color:var(--muted);padding-left:42px;">Edite telefone, e-mail e endereço</div>
      </div>
      <div class="section-body">
        <div class="form-group">
          <label>📞 Telefone</label>
          <input type="text" id="telefone" value="<?= htmlspecialchars($site['telefone'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label>✉ E-mail</label>
          <input type="email" id="emailEmpresa" value="<?= htmlspecialchars($site['email_empresa'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label>📍 Endereço</label>
          <input type="text" id="logradouro" placeholder="Rua/Av" value="<?= htmlspecialchars($site['logradouro'] ?? '') ?>">
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
          <div class="form-group">
            <label>Número</label>
            <input type="text" id="numero" value="<?= htmlspecialchars($site['numero'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label>CEP</label>
            <input type="text" id="cep" value="<?= htmlspecialchars($site['cep'] ?? '') ?>">
          </div>
        </div>
        <div style="display:grid;grid-template-columns:1fr 80px;gap:10px;">
          <div class="form-group">
            <label>Cidade</label>
            <input type="text" id="municipio" value="<?= htmlspecialchars($site['municipio'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label>UF</label>
            <input type="text" id="uf" maxlength="2" value="<?= htmlspecialchars($site['uf'] ?? '') ?>">
          </div>
        </div>
        <button class="btn btn-primary btn-full" onclick="salvarInfo()">💾 Salvar Alterações</button>
      </div>
    </div>

    <!-- 3. DOMÍNIO PRÓPRIO -->
    <div class="section-block">
      <div class="section-head">
        <div class="form-card-title" style="margin-bottom:2px;">
          <div class="icon" style="font-size:14px;">🔗</div>
          <h3>Conectar Domínio Próprio</h3>
        </div>
        <div style="font-size:12px;padding-left:42px;">
          <?php if($site['custom_domain']): ?>
            <span style="color:#10b981;">✅ Domínio conectado: <strong><?= htmlspecialchars($site['custom_domain']) ?></strong></span>
          <?php else: ?>
            <span style="color:var(--muted);">Aponte seu domínio para este site</span>
          <?php endif; ?>
        </div>
      </div>
      <div class="section-body">

        <!-- Passo 1: DNS -->
        <div style="background:rgba(0,0,0,.2);border:1px solid var(--border);border-radius:10px;padding:16px;margin-bottom:14px;">
          <div style="font-size:11px;font-weight:700;color:var(--accent);letter-spacing:1px;margin-bottom:10px;">PASSO 1 — Configure o DNS do seu domínio</div>
          <p style="font-size:12px;color:var(--muted);margin-bottom:12px;">No painel onde seu domínio está registrado, crie <strong>2 registros do tipo A</strong>:</p>
          <div class="dns-box" style="margin-bottom:8px;">
            <div class="dns-row"><span class="dns-label">Tipo</span><span class="dns-val">A</span></div>
            <div class="dns-row"><span class="dns-label">Host</span><span class="dns-val">@ (raiz / domínio principal)</span></div>
            <div class="dns-row">
              <span class="dns-label">IP / Valor</span>
              <span class="dns-val" style="font-family:monospace;font-weight:700;"><?= htmlspecialchars(SERVER_IP) ?></span>
              <button onclick="navigator.clipboard.writeText('<?= SERVER_IP ?>').then(()=>{this.textContent='✅';setTimeout(()=>this.textContent='📋',1500)})" style="background:none;border:1px solid var(--border);color:var(--muted);border-radius:6px;padding:2px 8px;cursor:pointer;font-size:11px;margin-left:6px;">📋</button>
            </div>
          </div>
          <div class="dns-box">
            <div class="dns-row"><span class="dns-label">Tipo</span><span class="dns-val">A</span></div>
            <div class="dns-row"><span class="dns-label">Host</span><span class="dns-val">www</span></div>
            <div class="dns-row"><span class="dns-label">IP / Valor</span><span class="dns-val" style="font-family:monospace;font-weight:700;"><?= htmlspecialchars(SERVER_IP) ?></span></div>
          </div>
          <p style="font-size:11px;color:var(--muted);margin-top:10px;">⏱ A propagação do DNS pode levar até 24 horas. Use o botão "Verificar" abaixo para checar.</p>
        </div>

        <!-- Passo 2: Salvar -->
        <div style="background:rgba(0,0,0,.2);border:1px solid var(--border);border-radius:10px;padding:16px;">
          <div style="font-size:11px;font-weight:700;color:var(--accent);letter-spacing:1px;margin-bottom:10px;">PASSO 2 — Informe o domínio aqui e salve</div>
          <div class="form-group" style="margin-bottom:10px;">
            <label>Domínio (sem https:// e sem www)</label>
            <input type="text" id="customDomain" placeholder="meudominio.com.br"
                   value="<?= htmlspecialchars($site['custom_domain'] ?? '') ?>">
          </div>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:10px;">
            <button class="btn btn-primary" onclick="salvarDominioPropio()">🔗 Salvar Domínio</button>
            <button class="btn btn-ghost" id="btnVerificar" onclick="verificarDNS()">🔍 Verificar DNS</button>
          </div>
          <div id="dnsStatus" style="display:none;"></div>
          <?php if($site['custom_domain']): ?>
          <button onclick="if(confirm('Remover domínio próprio e voltar ao subdomínio?')){document.getElementById('customDomain').value='';salvarDominioPropio()}" style="background:none;border:none;color:var(--muted);font-size:12px;cursor:pointer;padding:4px 0;text-decoration:underline;">✕ Remover domínio próprio</button>
          <?php endif; ?>
        </div>

      </div>
    </div>

    <!-- 4. META TAG FACEBOOK -->
    <div class="section-block">
      <div class="section-head">
        <div class="form-card-title" style="margin-bottom:2px;">
          <div class="icon" style="font-size:14px;">🛡</div>
          <h3>Meta Tag do Facebook</h3>
        </div>
        <div style="font-size:12px;color:var(--muted);padding-left:42px;">Adicione a Meta Tag e republique</div>
      </div>
      <div class="section-body">
        <div class="form-group">
          <label>Meta tag completa</label>
          <input type="text" id="metaTagFb" placeholder='&lt;meta name="facebook-domain-verification" content="codigo"&gt;'>
        </div>
        <button class="btn btn-primary btn-full" onclick="salvarMetaTag()">🛡 Salvar Meta Tag e Republica</button>
      </div>
    </div>

    <!-- 5. EDITAR HTML -->
    <div class="section-block">
      <div class="section-head">
        <div class="form-card-title" style="margin-bottom:2px;">
          <div class="icon" style="font-size:14px;">✂️</div>
          <h3>Editar HTML (index)</h3>
        </div>
        <div style="font-size:12px;color:var(--muted);padding-left:42px;">Edição manual do HTML publicado</div>
      </div>
      <div class="section-body">
        <div class="form-group">
          <textarea class="code-editor" id="htmlContent"><?= htmlspecialchars($site['html_content'] ?? '') ?></textarea>
        </div>
        <button class="btn btn-primary btn-full" onclick="salvarHTML()">💾 Salvar HTML e Republica</button>
      </div>
    </div>

  </div>
</div>

<script>
const SITE_ID = <?= $site['id'] ?>;

function showAlert(msg, type='success') {
  const box = document.getElementById('alertGlobal');
  box.innerHTML = `<div class="alert alert-${type}" style="margin-bottom:16px;">${msg}</div>`;
  setTimeout(() => box.innerHTML = '', 4000);
}

async function post(url, data) {
  const res = await fetch(url, {
    method: 'POST',
    headers: {'Content-Type':'application/json'},
    body: JSON.stringify(data)
  });
  return res.json();
}

async function salvarDominio() {
  const slug = document.getElementById('slugInput').value.trim();
  if (!slug) return showAlert('Preencha o subdomínio.', 'error');
  const d = await post('api/atualizar.php', {id: SITE_ID, action:'slug', slug});
  d.ok ? showAlert('Domínio atualizado e site republicado!') : showAlert(d.error, 'error');
}

async function salvarInfo() {
  const d = await post('api/atualizar.php', {
    id: SITE_ID, action:'info',
    telefone: document.getElementById('telefone').value,
    email_empresa: document.getElementById('emailEmpresa').value,
    logradouro: document.getElementById('logradouro').value,
    numero: document.getElementById('numero').value,
    cep: document.getElementById('cep').value,
    municipio: document.getElementById('municipio').value,
    uf: document.getElementById('uf').value,
  });
  d.ok ? showAlert('Informações salvas e site republicado!') : showAlert(d.error, 'error');
}

async function salvarDominioPropio() {
  const domain = document.getElementById('customDomain').value.trim();
  const d = await post('api/atualizar.php', {id: SITE_ID, action:'custom_domain', custom_domain: domain});
  if (d.ok) {
    showAlert(domain ? `✅ Domínio "${domain}" salvo! Configure o DNS conforme as instruções acima.` : '✅ Domínio próprio removido.');
    setTimeout(() => location.reload(), 2000);
  } else {
    showAlert(d.error, 'error');
  }
}

async function verificarDNS() {
  const domain = document.getElementById('customDomain').value.trim();
  if (!domain) return showAlert('Informe o domínio antes de verificar.', 'error');

  const btn    = document.getElementById('btnVerificar');
  const status = document.getElementById('dnsStatus');
  btn.disabled = true;
  btn.textContent = '⏳ Verificando...';
  status.style.display = 'none';

  try {
    const res = await fetch(`api/verificar-dominio.php?domain=${encodeURIComponent(domain)}`);
    const d   = await res.json();

    status.style.display = 'block';
    const cor    = d.connected ? '#064e3b' : '#1c1917';
    const texto  = d.connected ? '#a7f3d0' : '#fbbf24';
    status.innerHTML = `<div style="background:${cor};color:${texto};border:1px solid ${d.connected?'rgba(16,185,129,.3)':'rgba(251,191,36,.3)'};padding:12px 14px;border-radius:8px;font-size:12px;line-height:1.6;">${d.message}</div>`;
  } catch (e) {
    status.style.display = 'block';
    status.innerHTML = `<div style="background:#1c0a0a;color:#f87171;padding:12px;border-radius:8px;font-size:12px;">❌ Erro ao verificar. Tente novamente.</div>`;
  }

  btn.disabled = false;
  btn.textContent = '🔍 Verificar DNS';
}

async function salvarMetaTag() {
  const tag = document.getElementById('metaTagFb').value.trim();
  const d = await post('api/atualizar.php', {id: SITE_ID, action:'meta_tag', meta_tag: tag});
  d.ok ? showAlert('Meta Tag salva e site republicado!') : showAlert(d.error, 'error');
}

async function salvarHTML() {
  const html = document.getElementById('htmlContent').value;
  const d = await post('api/atualizar.php', {id: SITE_ID, action:'html', html});
  d.ok ? showAlert('HTML salvo e publicado!') : showAlert(d.error, 'error');
}
</script>
</body>
</html>

