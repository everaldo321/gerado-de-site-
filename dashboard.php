<?php
require_once 'config.php';
require_once 'includes/auth.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';
requireLogin();

$user = sessionUser();
$db   = getDB();

if ($user['role'] === 'admin') {
    $sites = $db->query("SELECT s.*, u.name AS user_name FROM sites s JOIN users u ON s.user_id = u.id ORDER BY s.created_at DESC")->fetchAll();
} else {
    $stmt = $db->prepare("SELECT * FROM sites WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$user['id']]);
    $sites = $stmt->fetchAll();
}

// ── Estatísticas de uso de domínios ──
$totalSites     = count($sites);
$customDomains  = 0;
$subDomains     = 0;
$dominioMap     = []; // conta quantas vezes cada domínio-base foi usado

foreach ($sites as $s) {
    if (!empty($s['custom_domain'])) {
        $customDomains++;
        // Extrai o domínio raiz (ex: meusite.com.br)
        $parts = explode('.', $s['custom_domain']);
        $domBase = count($parts) >= 2
            ? implode('.', array_slice($parts, -2))
            : $s['custom_domain'];
        $dominioMap[$domBase] = ($dominioMap[$domBase] ?? 0) + 1;
    } else {
        $subDomains++;
        $sufixo = ltrim(SUBDOMAIN_SUFFIX, '.');
        $dominioMap[$sufixo] = ($dominioMap[$sufixo] ?? 0) + 1;
    }
}
arsort($dominioMap);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard — Meta Shield</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<!-- TOPBAR -->
<header class="topbar">
  <div class="topbar-brand">
    <div class="logo-icon">M</div>
    <div>
      <h1>Meta Shield</h1>
      <span>Gerador de Sites</span>
    </div>
  </div>
  <div class="topbar-actions">
    <?php if ($user['role'] === 'admin'): ?>
      <a href="admin/index.php" class="btn btn-ghost btn-sm">⚙ Admin</a>
    <?php endif; ?>
    <a href="admin/index.php?tab=verificacoes" class="btn btn-ghost btn-sm">🛡 Verificações FB</a>
    <a href="novo-site.php" class="btn btn-primary btn-sm">+ Novo Site</a>
    <a href="logout.php" class="btn btn-ghost btn-sm" title="Sair">↪</a>
  </div>
</header>

<main class="main">

  <!-- BARRA DE USO DE DOMÍNIOS -->
  <?php if ($totalSites > 0): ?>
  <div class="domain-stats-bar">
    <div class="domain-stats-header">
      <div class="domain-stats-title">
        <span class="domain-stats-icon">📊</span>
        <span>Uso de Domínios</span>
      </div>
      <div class="domain-stats-total"><?= $totalSites ?> site<?= $totalSites !== 1 ? 's' : '' ?> no total</div>
    </div>

    <div class="domain-stats-counters">
      <div class="domain-stat-chip domain-stat-sub">
        <span class="domain-stat-number"><?= $subDomains ?></span>
        <span class="domain-stat-label">Subdomínio do sistema</span>
      </div>
      <div class="domain-stat-chip domain-stat-custom">
        <span class="domain-stat-number"><?= $customDomains ?></span>
        <span class="domain-stat-label">Domínio próprio</span>
      </div>
    </div>

    <!-- Barra visual de proporção -->
    <div class="domain-progress-bar">
      <?php if ($subDomains > 0): ?>
        <div class="domain-progress-fill domain-progress-sub" style="width:<?= round(($subDomains / $totalSites) * 100) ?>%"
             title="<?= $subDomains ?> subdomínio<?= $subDomains !== 1 ? 's' : '' ?>"></div>
      <?php endif; ?>
      <?php if ($customDomains > 0): ?>
        <div class="domain-progress-fill domain-progress-custom" style="width:<?= round(($customDomains / $totalSites) * 100) ?>%"
             title="<?= $customDomains ?> domínio<?= $customDomains !== 1 ? 's' : '' ?> próprio<?= $customDomains !== 1 ? 's' : '' ?>"></div>
      <?php endif; ?>
    </div>

    <!-- Detalhamento por domínio -->
    <?php if (count($dominioMap) > 0): ?>
    <div class="domain-stats-detail">
      <?php foreach ($dominioMap as $dom => $count): ?>
        <div class="domain-detail-row">
          <span class="domain-detail-name">
            <span class="domain-detail-dot" style="background:<?= ($dom === ltrim(SUBDOMAIN_SUFFIX, '.')) ? 'var(--accent)' : 'var(--success)' ?>"></span>
            <?= htmlspecialchars($dom) ?>
          </span>
          <span class="domain-detail-count"><?= $count ?> site<?= $count !== 1 ? 's' : '' ?></span>
        </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
  <?php endif; ?>

  <!-- TOOLBAR -->
  <div class="toolbar">
    <div class="search-box">
      <span style="color:var(--muted);font-size:14px;">🔍</span>
      <input type="text" id="searchInput" placeholder="Buscar por nome, CNPJ...">
    </div>
    <div class="count-badge" id="countBadge"><?= count($sites) ?> sites</div>
  </div>

  <?php if (empty($sites)): ?>
    <div class="empty-state">
      <div class="icon">🌐</div>
      <h3>Nenhum site criado ainda</h3>
      <p>Clique em "+ Novo Site" para começar</p>
      <a href="novo-site.php" class="btn btn-primary" style="margin-top:16px;">+ Criar primeiro site</a>
    </div>
  <?php else: ?>
  <div class="cards-grid" id="cardsGrid">
    <?php foreach ($sites as $s): ?>
    <?php
      $siteUrl = getSiteUrl($s['slug'], $s['custom_domain'] ?: null);
      $displayName = $s['nome_fantasia'] ?: $s['nome_empresarial'];
    ?>
    <div class="site-card" data-search="<?= strtolower(htmlspecialchars($displayName . ' ' . $s['cnpj'])) ?>">
      <div class="card-header">
        <div class="card-icon">🌐</div>
        <div>
          <div class="card-title"><?= htmlspecialchars($displayName) ?></div>
          <div class="card-cnpj"><?= htmlspecialchars($s['cnpj']) ?></div>
        </div>
      </div>
      <div class="card-url"><?= htmlspecialchars($siteUrl) ?></div>
      <div class="card-date"><?= date('d/m/Y', strtotime($s['created_at'])) ?></div>
      <?php if ($user['role'] === 'admin' && isset($s['user_name'])): ?>
        <div style="font-size:11px;color:var(--muted);margin-top:4px;">👤 <?= htmlspecialchars($s['user_name']) ?></div>
      <?php endif; ?>

      <div class="card-actions">
        <button class="btn btn-sm" onclick="copyUrl('<?= htmlspecialchars($siteUrl) ?>')">📋 URL</button>
        <a href="<?= htmlspecialchars($siteUrl) ?>" target="_blank" class="btn btn-sm">↗ Abrir</a>
        <a href="editar.php?id=<?= $s['id'] ?>" class="btn btn-sm">✏ Editar</a>
        <button class="btn btn-sm" onclick="regenerar(<?= $s['id'] ?>, this)" title="Republica com template mais recente">↺</button>
        <button class="btn-del" onclick="confirmarDelete(<?= $s['id'] ?>, '<?= htmlspecialchars($displayName) ?>')">🗑</button>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</main>

<script>
const searchInput = document.getElementById('searchInput');
const cards = document.querySelectorAll('.site-card');
const badge = document.getElementById('countBadge');

searchInput.addEventListener('input', () => {
  const q = searchInput.value.toLowerCase();
  let visible = 0;
  cards.forEach(c => {
    const match = c.dataset.search.includes(q);
    c.style.display = match ? '' : 'none';
    if (match) visible++;
  });
  badge.textContent = visible + ' sites';
});

function copyUrl(url) {
  navigator.clipboard.writeText(url).then(() => alert('URL copiada!'));
}

function regenerar(id, btn) {
  if (!confirm('Republicar este site com o template mais recente?\nO conteúdo gerado pela IA será mantido.')) return;
  btn.disabled = true;
  btn.textContent = '⏳';
  fetch('api/regenerar.php', {
    method: 'POST',
    headers: {'Content-Type':'application/json'},
    body: JSON.stringify({id})
  })
  .then(r => r.json())
  .then(d => {
    if (d.ok) { btn.textContent = '✅'; setTimeout(() => { btn.textContent = '↺'; btn.disabled = false; }, 2000); }
    else { alert('Erro: ' + d.error); btn.textContent = '↺'; btn.disabled = false; }
  })
  .catch(() => { btn.textContent = '↺'; btn.disabled = false; });
}

function confirmarDelete(id, nome) {
  if (!confirm(`Deletar o site "${nome}"?\n\nEsta ação não pode ser desfeita.`)) return;
  fetch('api/deletar-site.php', {
    method: 'POST',
    headers: {'Content-Type':'application/json'},
    body: JSON.stringify({id})
  })
  .then(r => r.json())
  .then(d => {
    if (d.ok) location.reload();
    else alert('Erro: ' + d.error);
  });
}
</script>
</body>
</html>

