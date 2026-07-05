<?php
require_once 'config.php';
require_once 'verifica_sessao.php';

// Estatísticas simples pra saber quantos pacientes,consultas, internamentos tem 
$totalPacientes = $pdo->query("SELECT COUNT(*) FROM pacientes")->fetchColumn();
$totalConsultas = $pdo->query("SELECT COUNT(*) FROM consultas")->fetchColumn();
$totalInternamentos = $pdo->query("SELECT COUNT(*) FROM internamentos WHERE data_saida_real IS NULL")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - HMPASG</title>
    <link rel="stylesheet" href="style.css">
</head>
<body style="background-image: url('/hmpasg/image14-1024x678.jpg');">
    <div class="container">
        <header>
            <h1>Hospital Militar HMPASG</h1>
            <p>Bem-vindo, <?= htmlspecialchars($_SESSION['usuario_nome']) ?> (<?= $_SESSION['tipo'] ?>)</p>
            <a href="logout.php" class="btn-sair">Sair</a>
        </header>
        <nav>
            <a href="pacientes.php">Pacientes</a>
            <a href="medicos.php">Médicos</a>
            <a href="consultas.php">Consultas</a>
            <a href="internamentos.php">Internamentos</a>
            <a href="medicamentos.php">Medicamentos</a>
            <a href="prescricoes.php">Prescrições</a>
            <a href="exames.php">Exames</a>
            <a href="relatorio_consultas.php">Relatório Consultas</a>
        </nav>
        <section class="stats">
            <div class="card">Pacientes: <?= $totalPacientes ?></div>
            <div class="card">Consultas: <?= $totalConsultas ?></div>
            <div class="card">Internamentos ativos: <?= $totalInternamentos ?></div>
        </section>
    </div>
</body>
</html>