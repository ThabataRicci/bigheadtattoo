<?php
session_start();
require_once '../includes/conexao.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nova_senha = $_POST['nova_senha'];
    $confirmar_senha = $_POST['confirmar_senha'];
    $token = $_POST['token'];

    // 1. verificar se as senhas são iguais
    if ($nova_senha !== $confirmar_senha) {
        header("Location: ../pages/redefinir-senha.php?token=$token&erro=senhas_diferentes");
        exit();
    }

    try {
        // 2. verifica de quem é o token e se ainda vale
        $stmt = $pdo->prepare("SELECT id_usuario FROM usuario WHERE token_recuperacao = ? AND expiracao_token > NOW()");
        $stmt->execute([$token]);
        $usuario = $stmt->fetch();

        if ($usuario) {
            // 3. criar a "versão secreta" da senha
            $senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);

            // 4. atualizar a senha E limpar o token para não ser usado de novo
            $sql = "UPDATE usuario SET senha = ?, token_recuperacao = NULL, expiracao_token = NULL WHERE id_usuario = ?";
            $stmt_update = $pdo->prepare($sql);
            $stmt_update->execute([$senha_hash, $usuario['id_usuario']]);

            // 5. se der sucesso manda de volta pro login
            header("Location: ../pages/login.php?sucesso=senha_redefinida");
            exit();
        } else {
            // Se o cara tentar mandar um POST forjado com token falso
            die("Token inválido ou expirado.");
        }
    } catch (PDOException $e) {
        die("Erro ao atualizar senha: " . $e->getMessage());
    }
} else {
    header("Location: ../pages/login.php");
    exit();
}
