<?php
session_start();
require_once '../includes/conexao.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];

    // 1. verificar se esse e-mail realmente existe no banco
    $stmt = $pdo->prepare("SELECT id_usuario FROM usuario WHERE email = ?");
    $stmt->execute([$email]);
    $usuario = $stmt->fetch();

    if ($usuario) {
        // 2. se o usuário existe, vamos direcionar um e-mail (fazer essa parte q ainda está pendente)
        header("Location: ../pages/login.php?recuperacao=enviada");
        exit();
    } else {
        // 3. se o e-mail não existe, avisamos o erro
        header("Location: ../pages/recuperar-senha.php?erro=email_nao_encontrado");
        exit();
    }
} else {
    header("Location: ../pages/recuperar-senha.php");
    exit();
}
