<?php
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/functions.php';
requireAdmin();

$db = getDB();
$totalSites = $db->query("SELECT COUNT(*) FROM sites")->fetchColumn();
$totalUsers = $db->query("SELECT COUNT(*) FROM users WHERE role='user'")->fetchColumn();
$recentSites = $db->query("SELECT s.*, u.name AS user_name FROM sites s JOIN users u ON s.user_id=u.id ORDER BY s.created_at DESC LIMIT 10")->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin — Meta Shield</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<header class="topbar">
  <div class="topbar-brand">
    <div class="logo-icon">M</div>
    <div><h1>Meta Shield</h1><span>Painel Admin</span></div>
  </div>
  <div class="topbar-actions">
    <a href="../dashboard.php" class="btn btn-ghost btn-sm">🌐 Sites</a>
    <a href="../logout.php" class="btn btn-ghost btn-sm">↪ Sair</a>
  </div>
</header>

<div class="admin-layout">
  <!-- SIDEBAR -->
  <nav class="sidebar">
    <div class="sidebar-label">Painel</div>
    <a href="index.php" class="active">📊 Dashboard</a>
    <div class="sidebar-label">Gestão</div>
    <a href="usuarios.php">👥 Usuários</a>
    <a href="dominios.php">🔗 Domínios</a>
    <div class="sidebar-label">Sistema</div>
    <a href="configuracoes.php">⚙ Configurações</a>
    <a href="../dashboard.php">🌐 Ver Sites</a>
  </nav>

  <div class="content-area">
    <!-- STATS -->
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:14px;margin-bottom:28px;">
      <div style="background:var(--surface);border:1px solid var(--border);border-radius:10px;padding:20px;">
        <div style="font-size:28px;font-weight:800;color:var(--accent);"><?= $totalSites ?></div>
        <div style="font-size:13px;color:var(--muted);margin-top:4px;">Sites Criados</div>
      </div>
      <div style="background:var(--surface);border:1px solid var(--border);border-radius:10px;padding:20px;">
        <div style="font-size:28px;font-weight:800;color:var(--accent);"><?= $totalUsers ?></div>
        <div style="font-size:13px;color:var(--muted);margin-top:4px;">Usuários</div>
      </div>
    </div>

    <!-- SITES RECENTES -->
    <div class="page-header">
      <div><h2>Sites Recentes</h2><p>Últimos 10 sites criados</p></div>
      <a href="../novo-site.php" class="btn btn-primary btn-sm">+ Novo Site</a>
    </div>

    <div style="background:var(--surface);border:1px solid var(--border);border-radius:10px;overflow:hidden;">
      <?php if (empty($recentSites)): ?>
        <div class="empty-state"><div class="icon">🌐</div><h3>Nenhum site ainda</h3></div>
      <?php else: ?>
      <table class="data-table">
        <thead>
          <tr>
            <th>Empresa</th>
            <th>CNPJ</th>
            <th>Usuário</th>
            <th>Criado em</th>
            <th>Ações</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($recentSites as $s): ?>
          <?php
            $display = $s['nome_fantasia'] ?: $s['nome_empresarial'];
            $url = getSiteUrl($s['slug'], $s['custom_domain'] ?: null);
          ?>
          <tr>
            <td><?= htmlspecialchars($display) ?></td>
            <td><code style="font-size:12px;"><?= htmlspecialchars($s['cnpj']) ?></code></td>
            <td><?= htmlspecialchars($s['user_name']) ?></td>
            <td style="color:var(--muted);font-size:12px;"><?= date('d/m/Y H:i', strtotime($s['created_at'])) ?></td>
            <td>
              <a href="<?= htmlspecialchars($url) ?>" target="_blank" class="btn btn-ghost btn-sm">↗</a>
              <a href="../editar.php?id=<?= $s['id'] ?>" class="btn btn-ghost btn-sm">✏</a>
              <button class="btn btn-danger btn-sm" onclick="delSite(<?= $s['id'] ?>, '<?= htmlspecialchars(addslashes($display)) ?>')">🗑</button>
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
async function delSite(id, nome) {
  if (!confirm(`Deletar "${nome}"?`)) return;
  const r = await fetch('../api/deletar-site.php', {method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({id})});
  const d = await r.json();
  d.ok ? location.reload() : alert(d.error);
}
</script>
</body>
</html>
