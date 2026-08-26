<?php
require_once 'config.php';
require_once 'includes/auth.php';
requireLogin();
$user = sessionUser();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Novo Site — Meta Shield</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<header class="topbar">
  <div class="topbar-brand">
    <div class="logo-icon">M</div>
    <div>
      <h1>Meta Shield</h1>
      <span>Gerador de Sites</span>
    </div>
  </div>
  <div class="topbar-actions">
    <a href="dashboard.php" class="btn btn-ghost btn-sm">← Voltar</a>
    <a href="logout.php" class="btn btn-ghost btn-sm">↪</a>
  </div>
</header>

<div class="page-center" id="stepInput">
  <div class="form-card">
    <!-- STEP 1: CNPJ -->
    <div class="form-card-title">
      <div class="icon">✂️</div>
      <h3>1. Dados do CNPJ</h3>
    </div>
    <div class="form-card-sub">Cole o CNPJ para publicar o site primeiro</div>

    <div class="form-group">
      <textarea id="cnpjText" placeholder="Cole aqui o texto copiado do PDF do Cartão CNPJ...

REPÚBLICA FEDERATIVA DO BRASIL
CADASTRO NACIONAL DA PESSOA JURÍDICA

NÚMERO DE INSCRIÇÃO
12.345.678/0001-99
..."></textarea>
    </div>

    <div class="form-group">
      <label>Domínio personalizado (opcional)</label>
      <div class="domain-row">
        <input type="text" id="subdomainInput" placeholder="meu-site-legal"
               pattern="[a-z0-9\-]+" title="Só letras minúsculas, números e hífen">
        <div class="domain-suffix"><?= htmlspecialchars(SUBDOMAIN_SUFFIX) ?></div>
      </div>
      <div class="hint">Deixe em branco para gerar um automático. Só letras, números e hífen.</div>
    </div>

    <div id="alertBox"></div>

    <button class="btn btn-primary btn-full" id="btnGerar" onclick="gerarSite()">
      ✂️ Publicar Site
    </button>
  </div>
</div>

<!-- STEP 2: RESULTADO -->
<div class="page-center" id="stepResult" style="display:none;">
  <div style="width:100%;max-width:560px;display:flex;flex-direction:column;gap:16px;">

    <!-- Site criado -->
    <div class="form-card">
      <div class="form-card-title">
        <div class="icon">✅</div>
        <h3>Site criado</h3>
      </div>
      <div id="resultInfo" class="result-box" style="margin-top:0;">
        <!-- preenchido via JS -->
      </div>
    </div>

    <!-- URL do site -->
    <div class="form-card">
      <div class="form-card-title">
        <div class="icon">🌐</div>
        <h3>Site Publicado</h3>
      </div>
      <div class="form-card-sub" style="padding-left:0;">Site gerado e salvo com sucesso</div>
      <div class="url-copy">
        <span id="resultUrl"></span>
        <button onclick="copyResult()">📋</button>
        <a id="resultOpenBtn" href="#" target="_blank" class="btn btn-sm btn-ghost">↗</a>
      </div>
      <div style="font-size:12px;color:var(--muted);margin-top:8px;">
        ⚡ Copie esse link para usar no Facebook.
      </div>
    </div>

    <!-- Verificação DNS TXT -->
    <div class="form-card">
      <div class="form-card-title">
        <div class="icon">🛡</div>
        <h3>2. Verificação por DNS TXT (opcional)</h3>
      </div>
      <div class="form-card-sub">Cole o código que o Facebook deu (ex: e04qegu...) e eu adiciono no DNS automaticamente.</div>
      <div class="form-group">
        <input type="text" id="dnsTxtInput" placeholder="e04qegu598agbjksgus01yiabh6q1c">
      </div>
      <div style="font-size:11px;color:var(--muted);margin-bottom:12px;">
        Aguarde 2-5min após adicionar e clique em Verify domain no Facebook.
      </div>
      <button class="btn btn-ghost btn-full" onclick="salvarDnsTxt()">🛡 Adicionar TXT no DNS</button>
    </div>

    <a href="dashboard.php" class="btn btn-primary btn-full">Concluir</a>
  </div>
</div>

<script>
let createdSiteId = null;
let createdSiteUrl = null;

async function gerarSite() {
  const text = document.getElementById('cnpjText').value.trim();
  const sub  = document.getElementById('subdomainInput').value.trim();
  const btn  = document.getElementById('btnGerar');
  const alertBox = document.getElementById('alertBox');

  if (text.length < 50) {
    alertBox.innerHTML = '<div class="alert alert-error">Cole o texto completo do Cartão CNPJ.</div>';
    return;
  }

  alertBox.innerHTML = '<div class="alert alert-warn"><span class="spinner"></span> Gerando site com IA… aguarde.</div>';
  btn.disabled = true;

  try {
    const res = await fetch('api/gerar.php', {
      method: 'POST',
      headers: {'Content-Type':'application/json'},
      body: JSON.stringify({cnpj_text: text, subdomain: sub})
    });
    const data = await res.json();

    if (!data.ok) {
      alertBox.innerHTML = `<div class="alert alert-error">${data.error || 'Erro ao gerar site.'}</div>`;
      btn.disabled = false;
      return;
    }

    createdSiteId  = data.site_id;
    createdSiteUrl = data.url;

    // Preenche resultado
    const info = data.info;
    document.getElementById('resultInfo').innerHTML = `
      <div class="result-row"><strong>Empresa:</strong> <span>${esc(info.nome_fantasia || info.nome_empresarial)}</span></div>
      <div class="result-row"><strong>CNPJ:</strong> <span>${esc(info.cnpj)}</span></div>
      <div class="result-row"><strong>Telefone:</strong> <span>${esc(info.telefone || '—')}</span></div>
      <div class="result-row"><strong>Email:</strong> <span>${esc(info.email_empresa || '—')}</span></div>
      <div class="result-row"><strong>Endereço:</strong> <span>${esc(info.logradouro)}, ${esc(info.numero)} — ${esc(info.municipio)}/${esc(info.uf)}</span></div>
    `;

    document.getElementById('resultUrl').textContent = data.url;
    document.getElementById('resultOpenBtn').href = data.url;

    document.getElementById('stepInput').style.display = 'none';
    document.getElementById('stepResult').style.display = '';

  } catch(e) {
    alertBox.innerHTML = '<div class="alert alert-error">Erro de conexão. Tente novamente.</div>';
    btn.disabled = false;
  }
}

function copyResult() {
  navigator.clipboard.writeText(createdSiteUrl).then(() => alert('URL copiada!'));
}

async function salvarDnsTxt() {
  const txt = document.getElementById('dnsTxtInput').value.trim();
  if (!txt || !createdSiteId) return;

  const res = await fetch('api/dns-txt.php', {
    method: 'POST',
    headers: {'Content-Type':'application/json'},
    body: JSON.stringify({site_id: createdSiteId, dns_txt: txt})
  });
  const data = await res.json();
  alert(data.ok ? 'TXT salvo! Aguarde 2-5 minutos para propagar.' : 'Erro: ' + data.error);
}

function esc(s) {
  return String(s || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}
</script>
</body>
</html>
