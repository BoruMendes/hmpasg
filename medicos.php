<?php
require_once 'config.php';
require_once 'verifica_sessao.php';
verificarPermissao(['admin']);

$busca = '';
if (isset($_GET['buscar']) && isset($_GET['busca']) && !empty($_GET['busca'])) {
    $busca = trim($_GET['busca']);
}

// Adicionar médico
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['adicionar'])) {
    $senha_hash = password_hash('123456', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO utilizadores (nome, email, senha_hash, tipo) VALUES (?, ?, ?, 'medico')");
    $stmt->execute([$_POST['nome'], $_POST['email'], $senha_hash]);
    $id_utilizador = $pdo->lastInsertId();
    $stmt2 = $pdo->prepare("INSERT INTO medicos (id_utilizador, especialidade, crm, horario_atendimento) VALUES (?, ?, ?, ?)");
    $stmt2->execute([$id_utilizador, $_POST['especialidade'], $_POST['crm'], $_POST['horario']]);
    header('Location: medicos.php');
    exit;
}

// Editar
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['editar'])) {
    $stmt = $pdo->prepare("UPDATE medicos SET especialidade=?, crm=?, horario_atendimento=? WHERE id=?");
    $stmt->execute([$_POST['especialidade'], $_POST['crm'], $_POST['horario'], $_POST['id']]);
    $stmt2 = $pdo->prepare("UPDATE utilizadores SET nome=?, email=? WHERE id = (SELECT id_utilizador FROM medicos WHERE id=?)");
    $stmt2->execute([$_POST['nome'], $_POST['email'], $_POST['id']]);
    header('Location: medicos.php');
    exit;
}

// Excluir
if (isset($_GET['excluir'])) {
    $stmt = $pdo->prepare("DELETE FROM medicos WHERE id = ?");
    $stmt->execute([$_GET['excluir']]);
    header('Location: medicos.php');
    exit;
}

// Pesquisa
if (!empty($busca)) {
    $sql = "SELECT m.*, u.nome, u.email FROM medicos m 
            JOIN utilizadores u ON m.id_utilizador = u.id 
            WHERE u.nome LIKE ? OR m.especialidade LIKE ? OR m.crm LIKE ? 
            ORDER BY u.nome";
    $stmt = $pdo->prepare($sql);
    $termo = "%$busca%";
    $stmt->execute([$termo, $termo, $termo]);
    $medicos = $stmt->fetchAll();
} else {
    $medicos = $pdo->query("SELECT m.*, u.nome, u.email FROM medicos m JOIN utilizadores u ON m.id_utilizador = u.id ORDER BY u.nome")->fetchAll();
}

$editar = null;
if (isset($_GET['editar'])) {
    $stmt = $pdo->prepare("SELECT m.*, u.nome, u.email FROM medicos m JOIN utilizadores u ON m.id_utilizador = u.id WHERE m.id = ?");
    $stmt->execute([$_GET['editar']]);
    $editar = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html>
<head><title>Médicos - HMPASG</title><link rel="stylesheet" href="style.css">
<style>
    .barra-pesquisa { display:flex; gap:10px; margin-bottom:20px; align-items:center; background:#f8f9fa; padding:10px; border-radius:5px; }
    .barra-pesquisa input { flex:1; padding:8px; border:1px solid #ccc; border-radius:4px; }
    .barra-pesquisa button { padding:8px 20px; background:#007bff; color:white; border:none; border-radius:4px; cursor:pointer; }
    .barra-pesquisa .limpar { background:#6c757d; padding:8px 20px; color:white; border-radius:4px; text-decoration:none; }
    .resultado-info { margin-bottom:10px; font-size:0.9em; color:#555; }
    table { width:100%; border-collapse:collapse; }
    th, td { border:1px solid #ddd; padding:8px; text-align:left; }
    th { background:#007bff; color:white; }
    .mensagem-vazia { text-align:center; color:#999; padding:20px; }
</style>
</head>
<body>
<div class="container">
    <h2>Gestão de Médicos</h2>

    <?php if ($editar): ?>
    <h3>Editar Médico</h3>
    <form method="post">
        <input type="hidden" name="id" value="<?= $editar['id'] ?>">
        <input type="text" name="nome" value="<?= htmlspecialchars($editar['nome']) ?>" required>
        <input type="email" name="email" value="<?= htmlspecialchars($editar['email']) ?>" required>
        <input type="text" name="especialidade" value="<?= htmlspecialchars($editar['especialidade']) ?>" required>
        <input type="text" name="crm" value="<?= htmlspecialchars($editar['crm']) ?>" required>
        <input type="text" name="horario" value="<?= htmlspecialchars($editar['horario_atendimento']) ?>">
        <button type="submit" name="editar">Salvar</button>
        <a href="medicos.php">Cancelar</a>
    </form>
    <hr>
    <?php else: ?>
    <h3>Adicionar Médico</h3>
    <form method="post">
        <input type="text" name="nome" placeholder="Nome completo" required>
        <input type="email" name="email" placeholder="E-mail" required>
        <input type="text" name="especialidade" placeholder="Especialidade" required>
        <input type="text" name="crm" placeholder="CRM" required>
        <input type="text" name="horario" placeholder="Horário">
        <button type="submit" name="adicionar">Adicionar</button>
    </form>
    <hr>
    <?php endif; ?>

    <!-- BARRA DE PESQUISA -->
    <div class="barra-pesquisa">
        <form method="get" action="" style="display:flex; width:100%; gap:10px;">
            <input type="text" name="busca" placeholder="🔍 Pesquisar por nome, especialidade ou CRM..." value="<?= htmlspecialchars($busca) ?>">
            <button type="submit" name="buscar">Pesquisar</button>
            <?php if (!empty($busca)): ?>
                <a href="medicos.php" class="limpar">Limpar</a>
            <?php endif; ?>
        </form>
    </div>

    <?php if (!empty($busca)): ?>
        <div class="resultado-info">Resultados para <strong><?= htmlspecialchars($busca) ?></strong> (<?= count($medicos) ?> encontrados)</div>
    <?php endif; ?>

    <table>
        <tr><th>ID</th><th>Nome</th><th>Email</th><th>Especialidade</th><th>CRM</th><th>Horário</th><th>Ações</th></tr>
        <?php if (count($medicos) == 0): ?>
            <tr><td colspan="7" class="mensagem-vazia"><?= !empty($busca) ? 'Nenhum médico encontrado.' : 'Nenhum médico cadastrado.' ?></td></tr>
        <?php endif; ?>
        <?php foreach($medicos as $m): ?>
        <tr>
            <td><?= $m['id'] ?></td>
            <td><?= htmlspecialchars($m['nome']) ?></td>
            <td><?= htmlspecialchars($m['email']) ?></td>
            <td><?= htmlspecialchars($m['especialidade']) ?></td>
            <td><?= htmlspecialchars($m['crm']) ?></td>
            <td><?= htmlspecialchars($m['horario_atendimento']) ?></td>
            <td>
                <a href="?editar=<?= $m['id'] ?>">Editar</a> |
                <a href="?excluir=<?= $m['id'] ?>" onclick="return confirm('Excluir médico?')">Excluir</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
    <a href="dashboard.php">← Voltar</a>
</div>
</body>
</html>