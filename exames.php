<?php
require_once 'config.php';
require_once 'verifica_sessao.php';
verificarPermissao(['admin', 'medico']);

$busca = '';
if (isset($_GET['buscar']) && isset($_GET['busca']) && !empty($_GET['busca'])) {
    $busca = trim($_GET['busca']);
}

// --- Processar adição ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['adicionar'])) {
    // Se for admin, usar o médico selecionado no formulário
    if ($_SESSION['tipo'] == 'admin') {
        $id_medico = $_POST['id_medico_solicitante'] ?? null;
        if (!$id_medico) {
            $erro = "Selecione um médico solicitante.";
        }
    } else {
        // Médico comum: buscar o próprio id na tabela medicos
        $stmtMedico = $pdo->prepare("SELECT id FROM medicos WHERE id_utilizador = ?");
        $stmtMedico->execute([$_SESSION['usuario_id']]);
        $medico = $stmtMedico->fetch();
        $id_medico = $medico ? $medico['id'] : null;
        if (!$id_medico) {
            $erro = "Você não está cadastrado como médico.";
        }
    }

    // Se não houver erro, inserir
    if (!isset($erro) && $id_medico) {
        $stmt = $pdo->prepare("INSERT INTO exames (id_paciente, tipo_exame, data_solicitacao, id_medico_solicitante) VALUES (?, ?, CURDATE(), ?)");
        $stmt->execute([$_POST['id_paciente'], $_POST['tipo_exame'], $id_medico]);
        header('Location: exames.php');
        exit;
    }
}

// --- Inserir resultado (upload) ---
if (isset($_POST['inserir_resultado'])) {
    $resultado_arquivo = '';
    if (isset($_FILES['resultado']) && $_FILES['resultado']['error'] == 0) {
        $upload_dir = 'uploads/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        $resultado_arquivo = $upload_dir . uniqid() . '_' . basename($_FILES['resultado']['name']);
        move_uploaded_file($_FILES['resultado']['tmp_name'], $resultado_arquivo);
    }
    $stmt = $pdo->prepare("UPDATE exames SET data_resultado = CURDATE(), resultado_arquivo = ? WHERE id = ?");
    $stmt->execute([$resultado_arquivo, $_POST['id']]);
    header('Location: exames.php');
    exit;
}

// --- Editar ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['editar'])) {
    $stmt = $pdo->prepare("UPDATE exames SET tipo_exame = ? WHERE id = ?");
    $stmt->execute([$_POST['tipo_exame'], $_POST['id']]);
    header('Location: exames.php');
    exit;
}

// --- Excluir (admin) ---
if (isset($_GET['excluir']) && $_SESSION['tipo']=='admin') {
    $stmt = $pdo->prepare("DELETE FROM exames WHERE id = ?");
    $stmt->execute([$_GET['excluir']]);
    header('Location: exames.php');
    exit;
}

// --- Consulta com pesquisa ---
if (!empty($busca)) {
    $sql = "SELECT e.*, p.nome as paciente_nome, u.nome as medico_nome FROM exames e 
            JOIN pacientes p ON e.id_paciente = p.id 
            JOIN medicos m ON e.id_medico_solicitante = m.id 
            JOIN utilizadores u ON m.id_utilizador = u.id
            WHERE p.nome LIKE ? OR e.tipo_exame LIKE ? 
            ORDER BY e.data_solicitacao DESC";
    $stmt = $pdo->prepare($sql);
    $termo = "%$busca%";
    $stmt->execute([$termo, $termo]);
    $exames = $stmt->fetchAll();
} else {
    $exames = $pdo->query("SELECT e.*, p.nome as paciente_nome, u.nome as medico_nome FROM exames e 
        JOIN pacientes p ON e.id_paciente = p.id 
        JOIN medicos m ON e.id_medico_solicitante = m.id 
        JOIN utilizadores u ON m.id_utilizador = u.id 
        ORDER BY e.data_solicitacao DESC")->fetchAll();
}

$pacientes = $pdo->query("SELECT id, nome FROM pacientes ORDER BY nome")->fetchAll();
// Para admin: lista de médicos para selecionar
$medicos = [];
if ($_SESSION['tipo'] == 'admin') {
    $medicos = $pdo->query("SELECT m.id, u.nome FROM medicos m JOIN utilizadores u ON m.id_utilizador = u.id ORDER BY u.nome")->fetchAll();
}

$editar = null;
if (isset($_GET['editar'])) {
    $stmt = $pdo->prepare("SELECT * FROM exames WHERE id = ?");
    $stmt->execute([$_GET['editar']]);
    $editar = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html>
<head><title>Exames - HMPASG</title><link rel="stylesheet" href="style.css">
<style>
    .barra-pesquisa { display:flex; gap:10px; margin-bottom:20px; align-items:center; }
    .barra-pesquisa input { flex:1; padding:8px; border:1px solid #ccc; border-radius:4px; }
    .barra-pesquisa button { padding:8px 20px; background:#007bff; color:white; border:none; border-radius:4px; cursor:pointer; }
    .barra-pesquisa .limpar { background:#6c757d; padding:8px 20px; color:white; border-radius:4px; text-decoration:none; }
</style>
</head>
<body>
<div class="container">
    <h2>Solicitação de Exames</h2>

    <?php if ($editar): ?>
    <h3>Editar Exame</h3>
    <form method="post">
        <input type="hidden" name="id" value="<?= $editar['id'] ?>">
        <input type="text" name="tipo_exame" value="<?= htmlspecialchars($editar['tipo_exame']) ?>" required>
        <button type="submit" name="editar">Salvar</button>
        <a href="exames.php">Cancelar</a>
    </form>
    <hr>
    <?php else: ?>
    <h3>Solicitar Exame</h3>
    <form method="post">
        <select name="id_paciente" required>
            <option value="">Paciente</option>
            <?php foreach($pacientes as $p) echo "<option value='{$p['id']}'>{$p['nome']}</option>"; ?>
        </select>
        <input type="text" name="tipo_exame" placeholder="Tipo de exame (ex: Hemograma, Raio-X)" required>

        <!-- Se for admin, mostrar select para escolher o médico solicitante -->
        <?php if ($_SESSION['tipo'] == 'admin'): ?>
        <select name="id_medico_solicitante" required>
            <option value="">Médico Solicitante</option>
            <?php foreach($medicos as $m): ?>
                <option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['nome']) ?></option>
            <?php endforeach; ?>
        </select>
        <?php endif; ?>

        <button type="submit" name="adicionar">Solicitar</button>
        <?php if(isset($erro)) echo "<p class='erro'>$erro</p>"; ?>
    </form>
    <hr>
    <?php endif; ?>

    <!-- Barra de pesquisa -->
    <div class="barra-pesquisa">
        <form method="get" action="" style="display:flex; width:100%; gap:10px;">
            <input type="text" name="busca" placeholder="🔍 Pesquisar por paciente ou tipo de exame..." value="<?= htmlspecialchars($busca) ?>">
            <button type="submit" name="buscar">Pesquisar</button>
            <?php if (!empty($busca)): ?>
                <a href="exames.php" class="limpar">Limpar</a>
            <?php endif; ?>
        </form>
    </div>
    <?php if (!empty($busca)): ?>
        <div style="margin-bottom:10px;font-size:0.9em;color:#555;">Resultados para <strong><?= htmlspecialchars($busca) ?></strong> (<?= count($exames) ?> encontrados)</div>
    <?php endif; ?>

    <table border="1">
        <tr><th>Paciente</th><th>Tipo</th><th>Solicitado por</th><th>Data Solicitação</th><th>Data Resultado</th><th>Resultado</th><th>Ações</th></tr>
        <?php if (count($exames) == 0): ?>
            <tr><td colspan="7" style="text-align:center;color:#999;">Nenhum exame encontrado.</td></tr>
        <?php endif; ?>
        <?php foreach($exames as $e): ?>
        <tr>
            <td><?= htmlspecialchars($e['paciente_nome']) ?></td>
            <td><?= htmlspecialchars($e['tipo_exame']) ?></td>
            <td><?= htmlspecialchars($e['medico_nome']) ?></td>
            <td><?= date('d/m/Y', strtotime($e['data_solicitacao'])) ?></td>
            <td><?= $e['data_resultado'] ? date('d/m/Y', strtotime($e['data_resultado'])) : 'Pendente' ?></td>
            <td><?= $e['resultado_arquivo'] ? "<a href='{$e['resultado_arquivo']}' target='_blank'>Ver</a>" : '--' ?></td>
            <td>
                <a href="?editar=<?= $e['id'] ?>">Editar</a>
                <?php if(!$e['data_resultado']): ?>
                <form method="post" enctype="multipart/form-data" style="display:inline">
                    <input type="hidden" name="id" value="<?= $e['id'] ?>">
                    <input type="file" name="resultado" accept=".pdf,.jpg,.png" required style="display:inline; width:auto;">
                    <button type="submit" name="inserir_resultado">Anexar</button>
                </form>
                <?php endif; ?>
                <?php if($_SESSION['tipo']=='admin'): ?>
                <a href="?excluir=<?= $e['id'] ?>" onclick="return confirm('Excluir exame?')">Excluir</a>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
    <a href="dashboard.php">← Voltar</a>
</div>
</body>
</html>