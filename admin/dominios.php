<?php
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/db.php';
requireAdmin();

$db = getDB();
$domains = $db->query("
    SELECT d.*, s.nome_fantasia, s.nome_empresarial, s.slug, u.name AS user_name
    FROM domains d
    JOIN sites s ON d.site_id = s.id
    JOIN users u ON s.user_id = u.id
    ORDER BY d.created_at DESC
")->fetchAll();

$sitesComDominio = $db->query("
    SELECT s.id, s.slug, s.custom_domain, s.nome_fantasia, s.nome_empresarial, u.name AS user_name
    FROM sites s JOIN users u ON s.user_id=u.id
    WHERE s.custom_domain IS NOT NULL AND s.custom_domain != ''
    ORDER BY s.updated_at DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Domínios — Admin</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<header class="topbar">
  <div class="topbar-brand">
    <div class="logo-icon">M</div>
    <div><h1>Meta Shield</h1><span>Gestão de Domínios</span></div>
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
    <a href="dominios.php" class="active">🔗 Domínios</a>
    <div class="sidebar-label">Sistema</div>
    <a href="configuracoes.php">⚙ Configurações</a>
    <a href="../dashboard.php">🌐 Ver Sites</a>
  </nav>

  <div class="content-area">
    <div class="page-header">
      <div><h2>Domínios Conectados</h2><p>Domínios próprios e verificações</p></div>
    </div>

    <!-- INSTRUÇÕES DE DNS -->
    <div style="background:var(--surface);border:1px solid var(--border);border-radius:10px;padding:20px;margin-bottom:20px;max-width:680px;">
      <h3 style="font-size:14px;margin-bottom:12px;">📋 Como conectar domínio próprio</h3>
      <p style="font-size:13px;color:var(--muted);margin-bottom:14px;">
        O usuário precisa apontar o DNS do domínio dele para o servidor:
      </p>
      <div class="dns-box">
        <div class="dns-row">
          <span class="dns-label">Tipo</span>
          <span class="dns-val">A</span>
        </div>
        <div class="dns-row">
          <span class="dns-label">Host</span>
          <span class="dns-val">@ ou www</span>
        </div>
        <div class="dns-row">
          <span class="dns-label">Valor</span>
          <span class="dns-val"><?= htmlspecialchars(SERVER_IP) ?></span>
        </div>
        <div class="dns-row">
          <span class="dns-label">TTL</span>
          <span class="dns-val">300 (ou Auto)</span>
        </div>
      </div>
      <p style="font-size:12px;color:var(--muted);margin-top:10px;">
        Após apontar o DNS, o usuário adiciona o domínio na tela "Editar Site". A propagação leva entre 5min e 24h.
      </p>
    </div>

    <!-- DOMÍNIOS PRÓPRIOS -->
    <div class="page-header" style="margin-top:24px;">
      <div><h2 style="font-size:15px;">Domínios Próprios</h2></div>
    </div>
    <div style="background:var(--surface);border:1px solid var(--border);border-radius:10px;overflow:hidden;margin-bottom:24px;">
      <?php if (empty($sitesComDominio)): ?>
        <div class="empty-state"><div class="icon">🔗</div><h3>Nenhum domínio próprio conectado</h3></div>
      <?php else: ?>
      <table class="data-table">
        <thead>
          <tr><th>Domínio</th><th>Empresa</th><th>Usuário</th><th>Ações</th></tr>
        </thead>
        <tbody>
        <?php foreach ($sitesComDominio as $s): ?>
          <tr>
            <td><a href="https://<?= htmlspecialchars($s['custom_domain']) ?>" target="_blank" style="color:var(--accent);"><?= htmlspecialchars($s['custom_domain']) ?></a></td>
            <td><?= htmlspecialchars($s['nome_fantasia'] ?: $s['nome_empresarial']) ?></td>
            <td style="color:var(--muted);"><?= htmlspecialchars($s['user_name']) ?></td>
            <td>
              <a href="../editar.php?id=<?= $s['id'] ?>" class="btn btn-ghost btn-sm">✏ Editar</a>
              <button class="btn btn-danger btn-sm" onclick="removerDominio(<?= $s['id'] ?>)">🗑 Remover</button>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      <?php endif; ?>
    </div>

    <!-- VERIFICAÇÕES DNS TXT (Facebook) -->
    <div class="page-header">
      <div><h2 style="font-size:15px;">Verificações DNS TXT (Facebook)</h2></div>
    </div>
    <div style="background:var(--surface);border:1px solid var(--border);border-radius:10px;overflow:hidden;">
      <?php if (empty($domains)): ?>
        <div class="empty-state"><div class="icon">🛡</div><h3>Nenhuma verificação pendente</h3></div>
      <?php else: ?>
      <table class="data-table">
        <thead>
          <tr><th>Empresa</th><th>Usuário</th><th>TXT</th><th>Status</th><th>Ações</th></tr>
        </thead>
        <tbody>
        <?php foreach ($domains as $d): ?>
          <tr>
            <td><?= htmlspecialchars($d['nome_fantasia'] ?: $d['nome_empresarial']) ?></td>
            <td style="color:var(--muted);"><?= htmlspecialchars($d['user_name']) ?></td>
            <td><code style="font-size:11px;background:var(--surface2);padding:2px 6px;border-radius:4px;"><?= htmlspecialchars($d['dns_txt'] ?? '—') ?></code></td>
            <td>
              <span class="badge <?= $d['verified'] ? 'badge-green' : 'badge-yellow' ?>">
                <?= $d['verified'] ? 'Verificado' : 'Pendente' ?>
              </span>
            </td>
            <td>
              <?php if (!$d['verified']): ?>
                <button class="btn btn-ghost btn-sm" onclick="marcarVerificado(<?= $d['id'] ?>)">✓ Marcar OK</button>
              <?php endif; ?>
              <button class="btn btn-danger btn-sm" onclick="removerDns(<?= $d['id'] ?>)">🗑</button>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      <?php endif; ?>
    </div>
  </div>
</div>

<script>
async function post(url, data) {
  const r = await fetch(url, {method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(data)});
  return r.json();
}

async function removerDominio(siteId) {
  if (!confirm('Remover domínio próprio deste site?')) return;
  const d = await post('../api/atualizar.php', {id:siteId, action:'custom_domain', custom_domain:''});
  d.ok ? location.reload() : alert(d.error);
}

async function marcarVerificado(domainId) {
  const r = await fetch('../api/dominio-admin.php', {method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'verificar',id:domainId})});
  const d = await r.json();
  d.ok ? location.reload() : alert(d.error);
}

async function removerDns(domainId) {
  if (!confirm('Remover este registro de verificação?')) return;
  const r = await fetch('../api/dominio-admin.php', {method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'deletar',id:domainId})});
  const d = await r.json();
  d.ok ? location.reload() : alert(d.error);
}
</script>
</body>
</html>
