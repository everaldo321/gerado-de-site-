<?php
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/db.php';
requireAdmin();

$db    = getDB();
$users = $db->query("SELECT u.*, (SELECT COUNT(*) FROM sites WHERE user_id=u.id) AS total_sites FROM users u ORDER BY u.created_at DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Usuários — Admin</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<header class="topbar">
  <div class="topbar-brand">
    <div class="logo-icon">M</div>
    <div><h1>Meta Shield</h1><span>Gestão de Usuários</span></div>
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
    <a href="usuarios.php" class="active">👥 Usuários</a>
    <a href="dominios.php">🔗 Domínios</a>
    <div class="sidebar-label">Sistema</div>
    <a href="configuracoes.php">⚙ Configurações</a>
    <a href="../dashboard.php">🌐 Ver Sites</a>
  </nav>

  <div class="content-area">
    <div id="alertBox"></div>

    <div class="page-header">
      <div><h2>Usuários</h2><p>Gerencie logins e acessos</p></div>
      <button class="btn btn-primary btn-sm" onclick="toggleForm()">+ Novo Usuário</button>
    </div>

    <!-- FORM CRIAR -->
    <div id="formCriar" style="display:none;background:var(--surface);border:1px solid var(--border);border-radius:10px;padding:24px;margin-bottom:20px;">
      <h3 style="margin-bottom:16px;font-size:15px;">Criar Novo Usuário</h3>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
        <div class="form-group">
          <label>Nome</label>
          <input type="text" id="newName" placeholder="Nome completo">
        </div>
        <div class="form-group">
          <label>E-mail</label>
          <input type="email" id="newEmail" placeholder="email@dominio.com">
        </div>
        <div class="form-group">
          <label>Senha</label>
          <input type="password" id="newPass" placeholder="mínimo 6 caracteres">
        </div>
        <div class="form-group">
          <label>Perfil</label>
          <select id="newRole">
            <option value="user">Usuário</option>
            <option value="admin">Admin</option>
          </select>
        </div>
      </div>
      <div style="display:flex;gap:10px;margin-top:8px;">
        <button class="btn btn-primary" onclick="criarUsuario()">Criar Usuário</button>
        <button class="btn btn-ghost" onclick="toggleForm()">Cancelar</button>
      </div>
    </div>

    <!-- TABELA -->
    <div style="background:var(--surface);border:1px solid var(--border);border-radius:10px;overflow:hidden;">
      <table class="data-table">
        <thead>
          <tr>
            <th>Nome</th>
            <th>E-mail</th>
            <th>Perfil</th>
            <th>Sites</th>
            <th>Status</th>
            <th>Criado em</th>
            <th>Ações</th>
          </tr>
        </thead>
        <tbody id="tbodyUsers">
        <?php foreach ($users as $u): ?>
          <tr id="row-<?= $u['id'] ?>">
            <td><?= htmlspecialchars($u['name']) ?></td>
            <td style="color:var(--muted);"><?= htmlspecialchars($u['email']) ?></td>
            <td>
              <span class="badge <?= $u['role']==='admin' ? 'badge-yellow' : 'badge-gray' ?>">
                <?= $u['role'] ?>
              </span>
            </td>
            <td style="color:var(--accent);font-weight:700;"><?= $u['total_sites'] ?></td>
            <td>
              <span class="badge <?= $u['ativo'] ? 'badge-green' : 'badge-red' ?>">
                <?= $u['ativo'] ? 'Ativo' : 'Inativo' ?>
              </span>
            </td>
            <td style="color:var(--muted);font-size:12px;"><?= date('d/m/Y', strtotime($u['created_at'])) ?></td>
            <td>
              <button class="btn btn-ghost btn-sm" onclick="trocarSenha(<?= $u['id'] ?>)">🔑</button>
              <button class="btn btn-ghost btn-sm" onclick="toggleAtivo(<?= $u['id'] ?>)">
                <?= $u['ativo'] ? '🔴' : '🟢' ?>
              </button>
              <button class="btn btn-danger btn-sm" onclick="deletarUsuario(<?= $u['id'] ?>, '<?= htmlspecialchars(addslashes($u['name'])) ?>')">🗑</button>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<script>
function showAlert(msg, type='success') {
  const b = document.getElementById('alertBox');
  b.innerHTML = `<div class="alert alert-${type}" style="margin-bottom:16px;">${msg}</div>`;
  setTimeout(() => b.innerHTML='', 4000);
}

function toggleForm() {
  const f = document.getElementById('formCriar');
  f.style.display = f.style.display==='none' ? '' : 'none';
}

async function post(url, data) {
  const r = await fetch(url, {method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(data)});
  return r.json();
}

async function criarUsuario() {
  const d = await post('../api/usuario.php', {
    action:'criar',
    name: document.getElementById('newName').value,
    email: document.getElementById('newEmail').value,
    password: document.getElementById('newPass').value,
    role: document.getElementById('newRole').value,
  });
  if (d.ok) { showAlert('Usuário criado!'); location.reload(); }
  else showAlert(d.error, 'error');
}

async function deletarUsuario(id, nome) {
  if (!confirm(`Deletar usuário "${nome}" e todos os seus sites?`)) return;
  const d = await post('../api/usuario.php', {action:'deletar', id});
  d.ok ? location.reload() : showAlert(d.error, 'error');
}

async function toggleAtivo(id) {
  const d = await post('../api/usuario.php', {action:'toggle_ativo', id});
  d.ok ? location.reload() : showAlert(d.error, 'error');
}

async function trocarSenha(id) {
  const pass = prompt('Nova senha (mínimo 6 caracteres):');
  if (!pass || pass.length < 6) return;
  const d = await post('../api/usuario.php', {action:'trocar_senha', id, password:pass});
  d.ok ? showAlert('Senha alterada!') : showAlert(d.error, 'error');
}
</script>
</body>
</html>
