
<?php
session_start();

$host = 'localhost';
$dbname = 'hmpasg';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Erro de conexão: " . $e->getMessage());
}

// FUNÇÃO DE PERMISSÃO
function verificarPermissao($tiposPermitidos) {
    if (!isset($_SESSION['usuario_id'])) {
        header('Location: login.php');
        exit;
    }
    if (!in_array($_SESSION['tipo'], $tiposPermitidos)) {
        die("Acesso negado! Você não tem permissão para acessar esta página.");
    }
}
?>