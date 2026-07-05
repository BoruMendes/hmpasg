<?php
require_once 'config.php';

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';

    // Buscar usuário pelo e-mail
    $stmt = $pdo->prepare("SELECT * FROM utilizadores WHERE email = ?");
    $stmt->execute([$email]);
    $usuario = $stmt->fetch();

    if ($usuario) {
        // Verificar a senha
        if (password_verify($senha, $usuario['senha_hash'])) {
            // Sucesso!
            $_SESSION['usuario_id'] = $usuario['id'];
            $_SESSION['usuario_nome'] = $usuario['nome'];
            $_SESSION['tipo'] = $usuario['tipo'];
            header('Location: dashboard.php');
            exit;
        } else {
            $erro = "Senha incorreta!";
        }
    } else {
        $erro = "E-mail não encontrado!";
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Login - HMPASG</title>
    <style>
        body { font-family: Arial;
         background: #f0f2f5;
         display: flex; 
         justify-content: center; 
         align-items: center; 
         height: 100vh; 
        }

        .login-container { background: white; 
        padding: 30px; 
        border-radius: 8px; 
        box-shadow: 0 0 10px rgba(0,0,0,0.1); 
        width: 300px; 
    }
        input, button { width: 100%; 
        padding: 10px; 
        margin: 8px 0; 
        border-radius: 4px; 
        border: 1px solid #ccc; 
    }
        button { background: #007bff; 
        color: white; 
        border: none; 
        cursor: pointer; 
    }
        .erro { color: red; 
        text-align: center; 
    }
        .sucesso { color: green; 
        text-align: center; 
    }
    </style>
</head>
<body style="background-image: url('/hmpasg/image14-1024x678.jpg');">
    <div class="login-container">
        <h2 style="text-align:center">HMPASG - Login</h2>
        
        <?php if ($erro): ?>
            <p class="erro"><?php echo $erro; ?></p>
        <?php endif; ?>
        
        <form method="post">
            <input type="email" name="email" placeholder="E-mail" required autofocus>
            <input type="password" name="senha" placeholder="Senha" required>
            <button type="submit">Entrar</button>
        </form>
       
    </div>
</body>
</html>