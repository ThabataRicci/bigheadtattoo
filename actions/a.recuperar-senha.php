<?php
session_start();
require_once '../includes/conexao.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];

    // 1. Verificar se esse e-mail realmente existe no banco
    $stmt = $pdo->prepare("SELECT id_usuario FROM usuario WHERE email = ?");
    $stmt->execute([$email]);
    $usuario = $stmt->fetch();

    if ($usuario) {
        // 2. Se o usuário existe, aqui você dispararia um e-mail.
        // Como enviar e-mail real é complexo, geralmente redirecionamos
        // para uma página avisando que o link foi enviado.
        header("Location: ../pages/login.php?recuperacao=enviada");
        exit();
    } else {
        // 3. Se o e-mail não existe, avisamos o erro
        header("Location: ../pages/recuperar-senha.php?erro=email_nao_encontrado");
        exit();
    }
} else {
    header("Location: ../pages/recuperar-senha.php");
    exit();
}
