<?php
require_once 'config.php';
require_once 'verifica_sessao.php';
verificarPermissao(['admin', 'medico']);

$data_inicio = isset($_GET['data_inicio']) ? $_GET['data_inicio'] : date('Y-m-01');
$data_fim = isset($_GET['data_fim']) ? $_GET['data_fim'] : date('Y-m-t');
$id_medico = isset($_GET['id_medico']) ? $_GET['id_medico'] : '';

$sql = "SELECT c.*, p.nome as paciente_nome, u.nome as medico_nome 
        FROM consultas c
        JOIN pacientes p ON c.id_paciente = p.id
        JOIN medicos m ON c.id_medico = m.id
        JOIN utilizadores u ON m.id_utilizador = u.id
        WHERE DATE(c.data_hora) BETWEEN ? AND ?";
$params = [$data_inicio, $data_fim];

if (!empty($id_medico)) {
    $sql .= " AND c.id_medico = ?";
    $params[] = $id_medico;
}
$sql .= " ORDER BY c.data_hora DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$consultas = $stmt->fetchAll();

// Totais
$total_consultas = count($consultas);
$total_realizadas = 0;
$total_canceladas = 0;
foreach ($consultas as $c) {
    if ($c['status'] == 'realizada') $total_realizadas++;
    if ($c['status'] == 'cancelada') $total_canceladas++;
}

// Lista de médicos para filtro
$medicos = $pdo->query("SELECT m.id, u.nome FROM medicos m JOIN utilizadores u ON m.id_utilizador = u.id ORDER BY u.nome")->fetchAll();
?>
<!DOCTYPE html>
<html>
<head><title>Relatório de Consultas</title><link rel="stylesheet" href="style.css">
<style>
    .filtros { display:flex; gap:15px; flex-wrap:wrap; align-items:end; background:#f8f9fa; padding:15px; border-radius:8px; }
    .filtros label { font-weight:bold; }
    .totais { display:flex; gap:20px; margin:20px 0; flex-wrap:wrap; }
    .total-card { background:#007bff; color:white; padding:15px 25px; border-radius:8px; }
    .total-card.green { background:#28a745; }
    .total-card.red { background:#dc3545; }
    .btn-export { background:#28a745; color:white; padding:8px 20px; border-radius:30px; text-decoration:none; display:inline-block; margin-top:10px; }
</style>
</head>
<body>
<div class="container">
    <h2>📋 Relatório de Consultas</h2>

    <form method="get" class="filtros">
        <div>
            <label>Data Início:</label>
            <input type="date" name="data_inicio" value="<?= $data_inicio ?>">
        </div>
        <div>
            <label>Data Fim:</label>
            <input type="date" name="data_fim" value="<?= $data_fim ?>">
        </div>
        <div>
            <label>Médico:</label>
            <select name="id_medico">
                <option value="">Todos</option>
                <?php foreach ($medicos as $m): ?>
                    <option value="<?= $m['id'] ?>" <?= $id_medico==$m['id']?'selected':'' ?>><?= htmlspecialchars($m['nome']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit">Filtrar</button>
        <a href="?data_inicio=<?= date('Y-m-01') ?>&data_fim=<?= date('Y-m-t') ?>" class="btn-secondary" style="padding:8px 20px; background:#6c757d; color:white; border-radius:30px; text-decoration:none;">Limpar</a>
    </form>

    <div class="totais">
        <div class="total-card">Total de Consultas: <?= $total_consultas ?></div>
        <div class="total-card green">Realizadas: <?= $total_realizadas ?></div>
        <div class="total-card red">Canceladas: <?= $total_canceladas ?></div>
    </div>

    <table border="1">
        <tr><th>Data/Hora</th><th>Paciente</th><th>Médico</th><th>Motivo</th><th>Status</th></tr>
        <?php if (count($consultas) == 0): ?>
            <tr><td colspan="5" style="text-align:center;color:#999;">Nenhuma consulta encontrada no período.</td></tr>
        <?php endif; ?>
        <?php foreach ($consultas as $c): ?>
        <tr>
            <td><?= date('d/m/Y H:i', strtotime($c['data_hora'])) ?></td>
            <td><?= htmlspecialchars($c['paciente_nome']) ?></td>
            <td><?= htmlspecialchars($c['medico_nome']) ?></td>
            <td><?= htmlspecialchars($c['motivo']) ?></td>
            <td><?= $c['status'] ?></td>
        </tr>
        <?php endforeach; ?>
    </table>

    <a href="dashboard.php" style="display:inline-block; margin-top:20px;">← Voltar</a>
    <a href="#" onclick="window.print()" class="btn-export" style="float:right;">🖨️ Imprimir</a>
</div>
</body>
</html>