<?php
require_once 'config.php';
require_once 'verifica_sessao.php';
verificarPermissao(['admin', 'medico', 'recepcao']);

$busca = '';
if (isset($_GET['buscar']) && isset($_GET['busca']) && !empty($_GET['busca'])) {
    $busca = trim($_GET['busca']);
}

// Adicionar
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['adicionar'])) {
    $stmt = $pdo->prepare("INSERT INTO consultas (id_paciente, id_medico, data_hora, motivo, observacoes) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$_POST['id_paciente'], $_POST['id_medico'], $_POST['data_hora'], $_POST['motivo'], $_POST['observacoes']]);
    header('Location: consultas.php');
    exit;
}
// Atualizar status
if (isset($_POST['atualizar_status'])) {
    $stmt = $pdo->prepare("UPDATE consultas SET status = ? WHERE id = ?");
    $stmt->execute([$_POST['status'], $_POST['id']]);
    header('Location: consultas.php');
    exit;
}
// Excluir (admin)
if (isset($_GET['excluir']) && $_SESSION['tipo'] == 'admin') {
    $stmt = $pdo->prepare("DELETE FROM consultas WHERE id = ?");
    $stmt->execute([$_GET['excluir']]);
    header('Location: consultas.php');
    exit;
}
// Editar
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['editar_consulta'])) {
    $stmt = $pdo->prepare("UPDATE consultas SET data_hora=?, motivo=?, observacoes=? WHERE id=?");
    $stmt->execute([$_POST['data_hora'], $_POST['motivo'], $_POST['observacoes'], $_POST['id']]);
    header('Location: consultas.php');
    exit;
}

// Pesquisa
if (!empty($busca)) {
    $sql = "SELECT c.*, p.nome as paciente_nome, u.nome as medico_nome 
            FROM consultas c 
            JOIN pacientes p ON c.id_paciente = p.id 
            JOIN medicos m ON c.id_medico = m.id
            JOIN utilizadores u ON m.id_utilizador = u.id
            WHERE p.nome LIKE ? OR u.nome LIKE ? OR c.motivo LIKE ? 
            ORDER BY c.data_hora DESC";
    $stmt = $pdo->prepare($sql);
    $termo = "%$busca%";
    $stmt->execute([$termo, $termo, $termo]);
    $consultas = $stmt->fetchAll();
} else {
    $consultas = $pdo->query("SELECT c.*, p.nome as paciente_nome, u.nome as medico_nome 
        FROM consultas c 
        JOIN pacientes p ON c.id_paciente = p.id 
        JOIN medicos m ON c.id_medico = m.id
        JOIN utilizadores u ON m.id_utilizador = u.id
        ORDER BY c.data_hora DESC")->fetchAll();
}

$pacientes = $pdo->query("SELECT id, nome FROM pacientes ORDER BY nome")->fetchAll();
$medicos = $pdo->query("SELECT m.id, u.nome FROM medicos m JOIN utilizadores u ON m.id_utilizador = u.id ORDER BY u.nome")->fetchAll();

$editar = null;
if (isset($_GET['editar'])) {
    $stmt = $pdo->prepare("SELECT * FROM consultas WHERE id = ?");
    $stmt->execute([$_GET['editar']]);
    $editar = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html>
<head><title>Consultas - HMPASG</title>
<link rel="stylesheet" href="style.css">
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
    <h2>Agenda de Consultas</h2>

    <?php if ($editar): ?>
    <h3>Editar Consulta</h3>
    <form method="post">
        <input type="hidden" name="id" value="<?= $editar['id'] ?>">
        <input type="datetime-local" name="data_hora" value="<?= date('Y-m-d\TH:i', strtotime($editar['data_hora'])) ?>" required>
        <input type="text" name="motivo" value="<?= htmlspecialchars($editar['motivo']) ?>" placeholder="Motivo">
        <input type="text" name="observacoes" value="<?= htmlspecialchars($editar['observacoes']) ?>" placeholder="Observações">
        <button type="submit" name="editar_consulta">Salvar</button>
        <a href="consultas.php">Cancelar</a>
    </form>
    <hr>
    <?php else: ?>
    <h3>Agendar Consulta</h3>
    <form method="post">
        <select name="id_paciente" required><option value="">Paciente</option><?php foreach($pacientes as $p) echo "<option value='{$p['id']}'>{$p['nome']}</option>"; ?></select>
        <select name="id_medico" required><option value="">Médico</option><?php foreach($medicos as $m) echo "<option value='{$m['id']}'>{$m['nome']}</option>"; ?></select>
        <input type="datetime-local" name="data_hora" required>
        <input type="text" name="motivo" placeholder="Motivo">
        <input type="text" name="observacoes" placeholder="Observações">
        <button type="submit" name="adicionar">Agendar</button>
    </form>
    <hr>
    <?php endif; ?>

    <!-- BARRA DE PESQUISA -->
    <div class="barra-pesquisa">
        <form method="get" action="" style="display:flex; width:100%; gap:10px;">
            <input type="text" name="busca" placeholder="🔍 Pesquisar por paciente, médico ou motivo..." value="<?= htmlspecialchars($busca) ?>">
            <button type="submit" name="buscar">Pesquisar</button>
            <?php if (!empty($busca)): ?>
                <a href="consultas.php" class="limpar">Limpar</a>
            <?php endif; ?>
        </form>
    </div>

    <?php if (!empty($busca)): ?>
        <div class="resultado-info">Resultados para <strong><?= htmlspecialchars($busca) ?></strong> (<?= count($consultas) ?> encontrados)</div>
    <?php endif; ?>

    <table>
        <tr>
            <th>Data/Hora</th>
            <th>Paciente</th>
            <th>Médico</th>
            <th>Motivo</th>
            <th>Status</th>
            <th>Ações</th>
        </tr>
        <?php if (count($consultas) == 0): ?>
            <tr>
                <td colspan="6" class="mensagem-vazia"><?= !empty($busca) ? 'Nenhuma consulta encontrada.' : 'Nenhuma consulta agendada.' ?></td>
            </tr>
        <?php endif; ?>
        <?php foreach($consultas as $c): ?>
        <tr>
            <td><?= date('d/m/Y H:i', strtotime($c['data_hora'])) ?></td>
            <td><?= htmlspecialchars($c['paciente_nome']) ?></td>
            <td><?= htmlspecialchars($c['medico_nome']) ?></td>
            <td><?= htmlspecialchars($c['motivo']) ?></td>
            <td><?= $c['status'] ?></td>
            <td>
                <form method="post" style="display:inline-block">
                    <input type="hidden" name="id" value="<?= $c['id'] ?>">
                    <select name="status">
                        <option value="agendada" <?= $c['status']=='agendada'?'selected':'' ?>>Agendada</option>
                        <option value="realizada" <?= $c['status']=='realizada'?'selected':'' ?>>Realizada</option>
                        <option value="cancelada" <?= $c['status']=='cancelada'?'selected':'' ?>>Cancelada</option>
                    </select>
                    <button type="submit" name="atualizar_status">Atualizar</button>
                </form>
                <a href="?editar=<?= $c['id'] ?>">Editar</a>
                <?php if($_SESSION['tipo']=='admin'): ?>
                    <a href="?excluir=<?= $c['id'] ?>" onclick="return confirm('Excluir consulta?')">Excluir</a>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
    <a href="dashboard.php">← Voltar</a>
</div>
</body>
</html>