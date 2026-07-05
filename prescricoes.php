<?php
require_once 'config.php';
require_once 'verifica_sessao.php';
verificarPermissao(['admin', 'medico']);

$busca = '';
if (isset($_GET['buscar']) && isset($_GET['busca']) && !empty($_GET['busca'])) {
    $busca = trim($_GET['busca']);
}

// Adicionar
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['adicionar'])) {
    $stmt = $pdo->prepare("INSERT INTO prescricoes (id_consulta, id_internamento, id_medicamento, quantidade_por_tomada, frequencia, duracao_dias) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $_POST['id_consulta'] ?: null,
        $_POST['id_internamento'] ?: null,
        $_POST['id_medicamento'],
        $_POST['quantidade'],
        $_POST['frequencia'],
        $_POST['duracao']
    ]);
    header('Location: prescricoes.php');
    exit;
}
// Editar
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['editar'])) {
    $stmt = $pdo->prepare("UPDATE prescricoes SET quantidade_por_tomada=?, frequencia=?, duracao_dias=? WHERE id=?");
    $stmt->execute([$_POST['quantidade'], $_POST['frequencia'], $_POST['duracao'], $_POST['id']]);
    header('Location: prescricoes.php');
    exit;
}
// Excluir (admin)
if (isset($_GET['excluir']) && $_SESSION['tipo']=='admin') {
    $stmt = $pdo->prepare("DELETE FROM prescricoes WHERE id = ?");
    $stmt->execute([$_GET['excluir']]);
    header('Location: prescricoes.php');
    exit;
}

// Pesquisa
if (!empty($busca)) {
    $sql = "SELECT p.*, 
            c.data_hora as consulta_data, pac.nome as paciente_consulta,
            i.data_entrada as internamento_data, pac2.nome as paciente_internamento,
            m.nome as medicamento_nome
            FROM prescricoes p
            LEFT JOIN consultas c ON p.id_consulta = c.id
            LEFT JOIN pacientes pac ON c.id_paciente = pac.id
            LEFT JOIN internamentos i ON p.id_internamento = i.id
            LEFT JOIN pacientes pac2 ON i.id_paciente = pac2.id
            JOIN medicamentos m ON p.id_medicamento = m.id
            WHERE pac.nome LIKE ? OR pac2.nome LIKE ? OR m.nome LIKE ?
            ORDER BY p.id DESC";
    $stmt = $pdo->prepare($sql);
    $termo = "%$busca%";
    $stmt->execute([$termo, $termo, $termo]);
    $prescricoes = $stmt->fetchAll();
} else {
    $prescricoes = $pdo->query("SELECT p.*, 
        c.data_hora as consulta_data, pac.nome as paciente_consulta,
        i.data_entrada as internamento_data, pac2.nome as paciente_internamento,
        m.nome as medicamento_nome
        FROM prescricoes p
        LEFT JOIN consultas c ON p.id_consulta = c.id
        LEFT JOIN pacientes pac ON c.id_paciente = pac.id
        LEFT JOIN internamentos i ON p.id_internamento = i.id
        LEFT JOIN pacientes pac2 ON i.id_paciente = pac2.id
        JOIN medicamentos m ON p.id_medicamento = m.id
        ORDER BY p.id DESC")->fetchAll();
}

$medicamentos = $pdo->query("SELECT id, nome FROM medicamentos ORDER BY nome")->fetchAll();
$consultas = $pdo->query("SELECT c.id, p.nome as paciente_nome, c.data_hora FROM consultas c JOIN pacientes p ON c.id_paciente = p.id WHERE c.status != 'cancelada' ORDER BY c.data_hora DESC")->fetchAll();
$internamentos = $pdo->query("SELECT i.id, p.nome as paciente_nome, i.data_entrada FROM internamentos i JOIN pacientes p ON i.id_paciente = p.id WHERE i.data_saida_real IS NULL ORDER BY i.data_entrada DESC")->fetchAll();

$editar = null;
if (isset($_GET['editar'])) {
    $stmt = $pdo->prepare("SELECT * FROM prescricoes WHERE id = ?");
    $stmt->execute([$_GET['editar']]);
    $editar = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html>
<head><title>Prescrições</title><link rel="stylesheet" href="style.css">
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
    <h2>Prescrições Médicas</h2>

    <?php if ($editar): ?>
    <h3>Editar Prescrição</h3>
    <form method="post">
        <input type="hidden" name="id" value="<?= $editar['id'] ?>">
        <input type="text" name="quantidade" value="<?= htmlspecialchars($editar['quantidade_por_tomada']) ?>" required>
        <input type="text" name="frequencia" value="<?= htmlspecialchars($editar['frequencia']) ?>" required>
        <input type="number" name="duracao" value="<?= $editar['duracao_dias'] ?>" required>
        <button type="submit" name="editar">Salvar</button>
        <a href="prescricoes.php">Cancelar</a>
    </form>
    <hr>
    <?php else: ?>
    <h3>Prescrever Medicamento</h3>
    <form method="post">
        <select name="id_consulta"><option value="">Associado à Consulta (opcional)</option><?php foreach($consultas as $c) echo "<option value='{$c['id']}'>{$c['paciente_nome']} - {$c['data_hora']}</option>"; ?></select>
        <select name="id_internamento"><option value="">Associado ao Internamento (opcional)</option><?php foreach($internamentos as $i) echo "<option value='{$i['id']}'>{$i['paciente_nome']} - Internado em {$i['data_entrada']}</option>"; ?></select>
        <select name="id_medicamento" required><option value="">Medicamento</option><?php foreach($medicamentos as $med) echo "<option value='{$med['id']}'>{$med['nome']}</option>"; ?></select>
        <input type="text" name="quantidade" placeholder="Ex: 1 comprimido" required>
        <input type="text" name="frequencia" placeholder="Ex: 8/8h" required>
        <input type="number" name="duracao" placeholder="Duração (dias)" required>
        <button type="submit" name="adicionar">Prescrever</button>
    </form>
    <hr>
    <?php endif; ?>

    <!-- BARRA DE PESQUISA -->
    <div class="barra-pesquisa">
        <form method="get" action="" style="display:flex; width:100%; gap:10px;">
            <input type="text" name="busca" placeholder="🔍 Pesquisar por paciente ou medicamento..." value="<?= htmlspecialchars($busca) ?>">
            <button type="submit" name="buscar">Pesquisar</button>
            <?php if (!empty($busca)): ?>
                <a href="prescricoes.php" class="limpar">Limpar</a>
            <?php endif; ?>
        </form>
    </div>

    <?php if (!empty($busca)): ?>
        <div class="resultado-info">Resultados para <strong><?= htmlspecialchars($busca) ?></strong> (<?= count($prescricoes) ?> encontrados)</div>
    <?php endif; ?>

    <table>
        <tr><th>Paciente</th><th>Contexto</th><th>Medicamento</th><th>Posologia</th><th>Ações</th></tr>
        <?php if (count($prescricoes) == 0): ?>
            <tr><td colspan="5" class="mensagem-vazia"><?= !empty($busca) ? 'Nenhuma prescrição encontrada.' : 'Nenhuma prescrição registada.' ?></td></tr>
        <?php endif; ?>
        <?php foreach($prescricoes as $pr): 
            $paciente = $pr['paciente_consulta'] ?? $pr['paciente_internamento'] ?? 'Desconhecido';
            $contexto = $pr['consulta_data'] ? "Consulta em " . date('d/m/Y', strtotime($pr['consulta_data'])) : ($pr['internamento_data'] ? "Internamento em " . date('d/m/Y', strtotime($pr['internamento_data'])) : "Geral");
        ?>
        <tr>
            <td><?= htmlspecialchars($paciente) ?></td>
            <td><?= htmlspecialchars($contexto) ?></td>
            <td><?= htmlspecialchars($pr['medicamento_nome']) ?></td>
            <td><?= "{$pr['quantidade_por_tomada']} - {$pr['frequencia']} por {$pr['duracao_dias']} dias" ?></td>
            <td>
                <a href="?editar=<?= $pr['id'] ?>">Editar</a>
                <?php if($_SESSION['tipo']=='admin') echo " | <a href='?excluir={$pr['id']}' onclick='return confirm(\"Excluir prescrição?\")'>Excluir</a>"; ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
    <a href="dashboard.php">← Voltar</a>
</div>
</body>
</html>