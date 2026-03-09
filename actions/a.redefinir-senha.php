<?php
session_start();
require_once '../includes/conexao.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nova_senha = $_POST['nova_senha'];
    $confirmar_senha = $_POST['confirmar_senha'];
    $usuario_id = $_POST['usuario_id']; // O ID que veio escondido no formulário

    // 1. Verificar se as senhas são iguais
    if ($nova_senha !== $confirmar_senha) {
        header("Location: ../pages/redefinir-senha.php?erro=confirmacao&id=$usuario_id");
        exit();
    }

    // 2. Criar a "versão secreta" da senha (Hash)
    $senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);

    try {
        // 3. Atualizar no banco de dados
        $sql = "UPDATE usuario SET senha = ? WHERE id_usuario = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$senha_hash, $usuario_id]);

        // 4. Sucesso! Manda para o login
        header("Location: ../pages/login.php?sucesso=senha_redefinida");
        exit();

    } catch (PDOException $e) {
        die("Erro ao atualizar senha: " . $e->getMessage());
    }
} else {
    header("Location: ../pages/login.php");
    exit();
}