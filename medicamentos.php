<?php
require_once 'config.php';
require_once 'verifica_sessao.php';
verificarPermissao(['admin']);

$busca = '';
if (isset($_GET['buscar']) && isset($_GET['busca']) && !empty($_GET['busca'])) {
    $busca = trim($_GET['busca']);
}

// Adicionar
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['adicionar'])) {
    $stmt = $pdo->prepare("INSERT INTO medicamentos (nome, principio_ativo, dosagem, quantidade_stock) VALUES (?, ?, ?, ?)");
    $stmt->execute([$_POST['nome'], $_POST['principio'], $_POST['dosagem'], $_POST['quantidade']]);
    header('Location: medicamentos.php');
    exit;
}
// Editar
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['editar'])) {
    $stmt = $pdo->prepare("UPDATE medicamentos SET nome=?, principio_ativo=?, dosagem=?, quantidade_stock=? WHERE id=?");
    $stmt->execute([$_POST['nome'], $_POST['principio'], $_POST['dosagem'], $_POST['quantidade'], $_POST['id']]);
    header('Location: medicamentos.php');
    exit;
}
// Excluir
if (isset($_GET['excluir'])) {
    $stmt = $pdo->prepare("DELETE FROM medicamentos WHERE id = ?");
    $stmt->execute([$_GET['excluir']]);
    header('Location: medicamentos.php');
    exit;
}

// Pesquisa
if (!empty($busca)) {
    $sql = "SELECT * FROM medicamentos WHERE nome LIKE ? OR principio_ativo LIKE ? ORDER BY nome";
    $stmt = $pdo->prepare($sql);
    $termo = "%$busca%";
    $stmt->execute([$termo, $termo]);
    $medicamentos = $stmt->fetchAll();
} else {
    $medicamentos = $pdo->query("SELECT * FROM medicamentos ORDER BY nome")->fetchAll();
}

$editar = null;
if (isset($_GET['editar'])) {
    $stmt = $pdo->prepare("SELECT * FROM medicamentos WHERE id = ?");
    $stmt->execute([$_GET['editar']]);
    $editar = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html>
<head><title>Medicamentos</title><link rel="stylesheet" href="style.css">
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
    <h2>Estoque de Medicamentos</h2>

    <?php if ($editar): ?>
    <h3>Editar Medicamento</h3>
    <form method="post">
        <input type="hidden" name="id" value="<?= $editar['id'] ?>">
        <input type="text" name="nome" value="<?= htmlspecialchars($editar['nome']) ?>" required>
        <input type="text" name="principio" value="<?= htmlspecialchars($editar['principio_ativo']) ?>" placeholder="Princípio ativo">
        <input type="text" name="dosagem" value="<?= htmlspecialchars($editar['dosagem']) ?>" placeholder="Dosagem">
        <input type="number" name="quantidade" value="<?= $editar['quantidade_stock'] ?>" required>
        <button type="submit" name="editar">Salvar</button>
        <a href="medicamentos.php">Cancelar</a>
    </form>
    <hr>
    <?php else: ?>
    <h3>Adicionar Medicamento</h3>
    <form method="post">
        <input type="text" name="nome" placeholder="Nome" required>
        <input type="text" name="principio" placeholder="Princípio ativo">
        <input type="text" name="dosagem" placeholder="Dosagem">
        <input type="number" name="quantidade" placeholder="Quantidade em stock" required>
        <button type="submit" name="adicionar">Adicionar</button>
    </form>
    <hr>
    <?php endif; ?>

    <!-- BARRA DE PESQUISA -->
    <div class="barra-pesquisa">
        <form method="get" action="" style="display:flex; width:100%; gap:10px;">
            <input type="text" name="busca" placeholder="🔍 Pesquisar por nome ou princípio ativo..." value="<?= htmlspecialchars($busca) ?>">
            <button type="submit" name="buscar">Pesquisar</button>
            <?php if (!empty($busca)): ?>
                <a href="medicamentos.php" class="limpar">Limpar</a>
            <?php endif; ?>
        </form>
    </div>

    <?php if (!empty($busca)): ?>
        <div class="resultado-info">Resultados para <strong><?= htmlspecialchars($busca) ?></strong> (<?= count($medicamentos) ?> encontrados)</div>
    <?php endif; ?>

    <table>
        <tr><th>ID</th><th>Nome</th><th>Princípio Ativo</th><th>Dosagem</th><th>Stock</th><th>Ações</th></tr>
        <?php if (count($medicamentos) == 0): ?>
            <tr><td colspan="6" class="mensagem-vazia"><?= !empty($busca) ? 'Nenhum medicamento encontrado.' : 'Nenhum medicamento cadastrado.' ?></td></tr>
        <?php endif; ?>
        <?php foreach($medicamentos as $m): ?>
        <tr>
            <td><?= $m['id'] ?></td>
            <td><?= htmlspecialchars($m['nome']) ?></td>
            <td><?= htmlspecialchars($m['principio_ativo']) ?></td>
            <td><?= $m['dosagem'] ?></td>
            <td><?= $m['quantidade_stock'] ?></td>
            <td>
                <a href="?editar=<?= $m['id'] ?>">Editar</a> |
                <a href="?excluir=<?= $m['id'] ?>" onclick="return confirm('Excluir medicamento?')">Excluir</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
    <a href="dashboard.php">← Voltar</a>
</div>
</body>
</html>