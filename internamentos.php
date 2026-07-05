<?php
require_once 'config.php';
require_once 'verifica_sessao.php';
verificarPermissao(['admin', 'medico', 'enfermeiro']);

$busca = '';
if (isset($_GET['buscar']) && isset($_GET['busca']) && !empty($_GET['busca'])) {
    $busca = trim($_GET['busca']);
}

// Adicionar
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['adicionar'])) {
    $stmt = $pdo->prepare("INSERT INTO internamentos (id_paciente, data_entrada, data_saida_prevista, leito, diagnostico_inicial) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$_POST['id_paciente'], $_POST['data_entrada'], $_POST['data_saida_prevista'], $_POST['leito'], $_POST['diagnostico']]);
    header('Location: internamentos.php');
    exit;
}
// Dar alta
if (isset($_POST['alta'])) {
    $stmt = $pdo->prepare("UPDATE internamentos SET data_saida_real = CURDATE() WHERE id = ?");
    $stmt->execute([$_POST['id']]);
    header('Location: internamentos.php');
    exit;
}
// Editar
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['editar'])) {
    $stmt = $pdo->prepare("UPDATE internamentos SET data_entrada=?, data_saida_prevista=?, leito=?, diagnostico_inicial=? WHERE id=?");
    $stmt->execute([$_POST['data_entrada'], $_POST['data_saida_prevista'], $_POST['leito'], $_POST['diagnostico'], $_POST['id']]);
    header('Location: internamentos.php');
    exit;
}
// Excluir (admin)
if (isset($_GET['excluir']) && $_SESSION['tipo'] == 'admin') {
    $stmt = $pdo->prepare("DELETE FROM internamentos WHERE id = ?");
    $stmt->execute([$_GET['excluir']]);
    header('Location: internamentos.php');
    exit;
}

// Pesquisa
if (!empty($busca)) {
    $sql = "SELECT i.*, p.nome as paciente_nome FROM internamentos i 
            JOIN pacientes p ON i.id_paciente = p.id 
            WHERE p.nome LIKE ? OR i.leito LIKE ? OR i.diagnostico_inicial LIKE ? 
            ORDER BY i.data_entrada DESC";
    $stmt = $pdo->prepare($sql);
    $termo = "%$busca%";
    $stmt->execute([$termo, $termo, $termo]);
    $internamentos = $stmt->fetchAll();
} else {
    $internamentos = $pdo->query("SELECT i.*, p.nome as paciente_nome FROM internamentos i JOIN pacientes p ON i.id_paciente = p.id ORDER BY i.data_entrada DESC")->fetchAll();
}

$pacientes = $pdo->query("SELECT id, nome FROM pacientes ORDER BY nome")->fetchAll();

$editar = null;
if (isset($_GET['editar'])) {
    $stmt = $pdo->prepare("SELECT * FROM internamentos WHERE id = ?");
    $stmt->execute([$_GET['editar']]);
    $editar = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html>
<head><title>Internamentos</title><link rel="stylesheet" href="style.css">
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
    <h2>Internamentos</h2>

    <?php if ($editar): ?>
    <h3>Editar Internamento</h3>
    <form method="post">
        <input type="hidden" name="id" value="<?= $editar['id'] ?>">
        <input type="date" name="data_entrada" value="<?= $editar['data_entrada'] ?>" required>
        <input type="date" name="data_saida_prevista" value="<?= $editar['data_saida_prevista'] ?>">
        <input type="text" name="leito" value="<?= htmlspecialchars($editar['leito']) ?>">
        <textarea name="diagnostico"><?= htmlspecialchars($editar['diagnostico_inicial']) ?></textarea>
        <button type="submit" name="editar">Salvar</button>
        <a href="internamentos.php">Cancelar</a>
    </form>
    <hr>
    <?php else: ?>
    <h3>Internar Paciente</h3>
    <form method="post">
        <select name="id_paciente" required><option value="">Paciente</option><?php foreach($pacientes as $p) echo "<option value='{$p['id']}'>{$p['nome']}</option>"; ?></select>
        <input type="date" name="data_entrada" required>
        <input type="date" name="data_saida_prevista">
        <input type="text" name="leito" placeholder="Leito">
        <textarea name="diagnostico" placeholder="Diagnóstico inicial"></textarea>
        <button type="submit" name="adicionar">Internar</button>
    </form>
    <hr>
    <?php endif; ?>

    <!-- BARRA DE PESQUISA -->
    <div class="barra-pesquisa">
        <form method="get" action="" style="display:flex; width:100%; gap:10px;">
            <input type="text" name="busca" placeholder="🔍 Pesquisar por paciente, leito ou diagnóstico..." value="<?= htmlspecialchars($busca) ?>">
            <button type="submit" name="buscar">Pesquisar</button>
            <?php if (!empty($busca)): ?>
                <a href="internamentos.php" class="limpar">Limpar</a>
            <?php endif; ?>
        </form>
    </div>

    <?php if (!empty($busca)): ?>
        <div class="resultado-info">Resultados para <strong><?= htmlspecialchars($busca) ?></strong> (<?= count($internamentos) ?> encontrados)</div>
    <?php endif; ?>

    <table>
        <tr><th>Paciente</th><th>Entrada</th><th>Saída Prevista</th><th>Saída Real</th><th>Leito</th><th>Diagnóstico</th><th>Ações</th></tr>
        <?php if (count($internamentos) == 0): ?>
            <tr><td colspan="7" class="mensagem-vazia"><?= !empty($busca) ? 'Nenhum internamento encontrado.' : 'Nenhum internamento registado.' ?></td></tr>
        <?php endif; ?>
        <?php foreach($internamentos as $i): ?>
        <tr>
            <td><?= htmlspecialchars($i['paciente_nome']) ?></td>
            <td><?= date('d/m/Y', strtotime($i['data_entrada'])) ?></td>
            <td><?= $i['data_saida_prevista'] ? date('d/m/Y', strtotime($i['data_saida_prevista'])) : '-' ?></td>
            <td><?= $i['data_saida_real'] ? date('d/m/Y', strtotime($i['data_saida_real'])) : 'Internado' ?></td>
            <td><?= htmlspecialchars($i['leito']) ?></td>
            <td><?= htmlspecialchars(substr($i['diagnostico_inicial'],0,50)) ?></td>
            <td>
                <a href="?editar=<?= $i['id'] ?>">Editar</a>
                <?php if(!$i['data_saida_real']): ?>
                <form method="post" style="display:inline">
                    <input type="hidden" name="id" value="<?= $i['id'] ?>">
                    <button type="submit" name="alta">Dar Alta</button>
                </form>
                <?php endif; ?>
                <?php if($_SESSION['tipo']=='admin'): ?>
                <a href="?excluir=<?= $i['id'] ?>" onclick="return confirm('Excluir internamento?')">Excluir</a>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
    <a href="dashboard.php">← Voltar</a>
</div>
</body>
</html>