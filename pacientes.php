<?php
require_once 'config.php';
require_once 'verifica_sessao.php';
verificarPermissao(['admin', 'recepcao', 'medico']);

// --- Capturar termo de pesquisa ---
$busca = '';
if (isset($_GET['buscar']) && isset($_GET['busca']) && !empty($_GET['busca'])) {
    $busca = trim($_GET['busca']);
}

// --- Processar formulários (adicionar, editar, excluir) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['adicionar'])) {
    $stmt = $pdo->prepare("INSERT INTO pacientes (nome, data_nasc, genero, posto_militar, unidade, contacto, id_utilizador_criador) VALUES (?,?,?,?,?,?,?)");
    $stmt->execute([$_POST['nome'], $_POST['data_nasc'], $_POST['genero'], $_POST['posto_militar'], $_POST['unidade'], $_POST['contacto'], $_SESSION['usuario_id']]);
    header('Location: pacientes.php');
    exit;
}

if (isset($_GET['excluir'])) {
    $stmt = $pdo->prepare("DELETE FROM pacientes WHERE id = ?");
    $stmt->execute([$_GET['excluir']]);
    header('Location: pacientes.php');
    exit;
}

// --- Edição ---
$editar = null;
if (isset($_GET['editar'])) {
    $stmt = $pdo->prepare("SELECT * FROM pacientes WHERE id = ?");
    $stmt->execute([$_GET['editar']]);
    $editar = $stmt->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['editar'])) {
    $stmt = $pdo->prepare("UPDATE pacientes SET nome=?, data_nasc=?, genero=?, posto_militar=?, unidade=?, contacto=? WHERE id=?");
    $stmt->execute([$_POST['nome'], $_POST['data_nasc'], $_POST['genero'], $_POST['posto_militar'], $_POST['unidade'], $_POST['contacto'], $_POST['id']]);
    header('Location: pacientes.php');
    exit;
}

// --- Consulta com pesquisa ou sem ---
if (!empty($busca)) {
    $stmt = $pdo->prepare("SELECT * FROM pacientes WHERE nome LIKE ? OR posto_militar LIKE ? OR unidade LIKE ? OR contacto LIKE ? ORDER BY nome");
    $termo = "%$busca%";
    $stmt->execute([$termo, $termo, $termo, $termo]);
    $pacientes = $stmt->fetchAll();
} else {
    $pacientes = $pdo->query("SELECT * FROM pacientes ORDER BY nome")->fetchAll();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Pacientes - HMPASG</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .barra-pesquisa {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            align-items: center;
            background: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
        }
        .barra-pesquisa input {
            flex: 1;
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        .barra-pesquisa button {
            padding: 8px 20px;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .barra-pesquisa .limpar {
            background: #6c757d;
            padding: 8px 20px;
            color: white;
            border-radius: 4px;
            text-decoration: none;
        }
        .resultado-info {
            margin-bottom: 10px;
            font-size: 0.9em;
            color: #555;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background: #007bff;
            color: white;
        }
        .mensagem-vazia {
            text-align: center;
            color: #999;
            padding: 20px;
        }
    </style>
</head>
<body>
<div class="container">
    <h2>Gestão de Pacientes</h2>

    <?php if ($editar): ?>
    <h3>Editar Paciente</h3>
    <form method="post">
        <input type="hidden" name="id" value="<?= $editar['id'] ?>">
        <input type="text" name="nome" value="<?= htmlspecialchars($editar['nome']) ?>" required>
        <input type="date" name="data_nasc" value="<?= $editar['data_nasc'] ?>" required>
        <select name="genero">
            <option value="M" <?= $editar['genero']=='M'?'selected':'' ?>>Masculino</option>
            <option value="F" <?= $editar['genero']=='F'?'selected':'' ?>>Feminino</option>
        </select>
        <input type="text" name="posto_militar" value="<?= htmlspecialchars($editar['posto_militar']) ?>" placeholder="Posto militar">
        <input type="text" name="unidade" value="<?= htmlspecialchars($editar['unidade']) ?>" placeholder="Unidade">
        <input type="text" name="contacto" value="<?= htmlspecialchars($editar['contacto']) ?>" placeholder="Contacto">
        <button type="submit" name="editar">Salvar</button>
        <a href="pacientes.php">Cancelar</a>
    </form>
    <hr>
    <?php else: ?>
    <h3>Adicionar Paciente</h3>
    <form method="post">
        <input type="text" name="nome" placeholder="Nome" required>
        <input type="date" name="data_nasc" required>
        <select name="genero" required>
            <option value="M">Masculino</option>
            <option value="F">Feminino</option>
        </select>
        <input type="text" name="posto_militar" placeholder="Posto militar">
        <input type="text" name="unidade" placeholder="Unidade">
        <input type="text" name="contacto" placeholder="Contacto">
        <button type="submit" name="adicionar">Adicionar</button>
    </form>
    <hr>
    <?php endif; ?>

    <!-- BARRA DE PESQUISA (CORRIGIDA) -->
    <div class="barra-pesquisa">
        <form method="get" action="" style="display:flex; width:100%; gap:10px;">
            <input type="text" name="busca" placeholder="🔍 Pesquisar por nome, posto, unidade ou contacto..." value="<?= htmlspecialchars($busca) ?>">
            <button type="submit" name="buscar">Pesquisar</button>
            <?php if (!empty($busca)): ?>
                <a href="pacientes.php" class="limpar">Limpar</a>
            <?php endif; ?>
        </form>
    </div>

    <?php if (!empty($busca)): ?>
        <div class="resultado-info">
            Resultados para: <strong><?= htmlspecialchars($busca) ?></strong> (<?= count($pacientes) ?> encontrados)
        </div>
    <?php endif; ?>

    <!-- TABELA -->
    <table>
        <tr>
            <th>ID</th>
            <th>Nome</th>
            <th>Nascimento</th>
            <th>Género</th>
            <th>Posto</th>
            <th>Unidade</th>
            <th>Contacto</th>
            <th>Ações</th>
        </tr>
        <?php if (count($pacientes) === 0): ?>
            <tr>
                <td colspan="8" class="mensagem-vazia">
                    <?= !empty($busca) ? 'Nenhum paciente encontrado para esta pesquisa.' : 'Nenhum paciente cadastrado.' ?>
                </td>
            </tr>
        <?php else: ?>
            <?php foreach ($pacientes as $p): ?>
            <tr>
                <td><?= $p['id'] ?></td>
                <td><?= htmlspecialchars($p['nome']) ?></td>
                <td><?= date('d/m/Y', strtotime($p['data_nasc'])) ?></td>
                <td><?= $p['genero'] == 'M' ? 'Masculino' : 'Feminino' ?></td>
                <td><?= htmlspecialchars($p['posto_militar']) ?></td>
                <td><?= htmlspecialchars($p['unidade']) ?></td>
                <td><?= htmlspecialchars($p['contacto']) ?></td>
                <td>
                    <a href="?editar=<?= $p['id'] ?>">Editar</a> |
                    <a href="?excluir=<?= $p['id'] ?>" onclick="return confirm('Excluir este paciente?')">Excluir</a>
                </td>
            </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </table>

    <br>
    <a href="dashboard.php">← Voltar ao Dashboard</a>
</div>
</body>
</html>